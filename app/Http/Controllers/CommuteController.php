<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Stripe\Stripe;
use Stripe\Charge;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CommuteController extends Controller
{
    /**
     * get addresses count
     *
     * @return void
     * @throws Exception
     */
    public function getAddressesCount() {
        $response = array();
        if(isset($_FILES['commuteFile']) && !empty($_FILES['commuteFile']['tmp_name'])) {
            $file = $_FILES['commuteFile']['tmp_name'];
            if (fopen($file, "r") !== FALSE) {
                $records = $this->getRecordsCount($file);
                $response['success'] = true;
                $response['records_count'] = $records;
            } else {
                $response['error'] = "Error opening file.";
            }
        } else {
            $response['error'] = "No file uploaded.";
        }
        if(isset($response['error'])) {
            http_response_code(400);// Set HTTP status code to indicate a bad request
            echo json_encode($response);
            exit();
        } else {
            echo json_encode($response);
        }
    }

    /**
     * process commute file
     *
     * @return void
     * @throws Exception
     */
    public function processCommuteFile() {
        $response = array();
        if(isset($_FILES['commuteFile']) && !empty($_FILES['commuteFile']['tmp_name'])) {
            $file = $_FILES['commuteFile']['tmp_name'];
            if (fopen($file, "r") !== FALSE) {
                $records = $this->getRecordsCount($file);
                $response['success'] = true;
                $response['request_payment'] = true;
                if($records > 50 && $records <= 500) {
                    $response['charge'] = number_format(0.015 * $records + 1.00, 2);
                } else if($records > 500 && $records <= 1000) {
                    $response['charge'] = number_format(0.014 * $records + 0.75, 2);
                } else if($records > 1000) {
                    $response['charge'] = number_format(0.013 * $records + 0.50, 2);
                } else {
                    $response['request_payment'] = false;
                    $this->processRoutes($file);
                }
            } else {
                $response['error'] = "Error opening file.";
            }
        } else {
            $response['error'] = "No file uploaded.";
        }
        if(isset($response['error'])) {
            http_response_code(400);// Set HTTP status code to indicate a bad request
            echo json_encode($response);
            exit();
        } else if (!($response['success'] && !$response['request_payment'])) {
            echo json_encode($response);
        }
    }

    /**
     * get records count
     *
     * @param $file
     * @return int
     * @throws Exception
     */
    private function getRecordsCount($file) {
        $inputFileType = IOFactory::identify($file);
        $reader = IOFactory::createReader($inputFileType);
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();

        $rowCount = 0;

        foreach ($worksheet->getRowIterator() as $row) {
            $rowCount++;
        }

        return $rowCount - 1; // Subtract 1 to exclude header row
    }

    /**
     * process 'from' and 'to' addresses and show distance and travel time
     *
     * @param $file
     * @return void
     * @throws Exception
     */
    private function processRoutes($file) {
        $output = [];

        $inputFileType = IOFactory::identify($file);
        $reader = IOFactory::createReader($inputFileType);

        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();

        $isFirstRow = true; // Flag to identify the first row

        foreach ($worksheet->getRowIterator() as $row) {
            $rowData = [];
            foreach ($row->getCellIterator() as $cell) {
                $rowData[] = $cell->getValue();
            }

            if ($isFirstRow) {
                $output[] = ['From', 'To', 'Distance in Kilometers', 'Distance in Miles', 'Travel Days', 'Travel Hours', 'Travel Minutes'];
                $isFirstRow = false;
            } else if (!empty($rowData)) {
                if (!empty($rowData[0]) && !empty($rowData[1])) {
                    $from = urlencode($rowData[0]);
                    $to = urlencode($rowData[1]);

                    list($distanceKm, $distanceMi, $timeDays, $timeHrs, $timeMns) = $this->getTravelInfo($from, $to);

                    $rowData[] = $distanceKm;
                    $rowData[] = $distanceMi;
                    $rowData[] = $timeDays;
                    $rowData[] = $timeHrs;
                    $rowData[] = $timeMns;

                    $output[] = $rowData;
                }
            }
        }

        // Output the CSV data directly
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=output_file.csv');

        $outputFile = fopen('php://output', 'w');
        foreach ($output as $row) {
            fputcsv($outputFile, $row);
        }
        fclose($outputFile);
    }

    /**
     * get travel information between two addresses
     *
     * @param $from
     * @param $to
     * @return array|null[]
     */
    private function getTravelInfo($from, $to)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY'); // Replace with your actual API key
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins={$from}&destinations={$to}&key={$apiKey}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] == 'OK') {
            $distance = isset($data['rows'][0]['elements'][0]['distance']) ? $data['rows'][0]['elements'][0]['distance']['text'] : false;
            $duration = isset($data['rows'][0]['elements'][0]['duration']) ? $data['rows'][0]['elements'][0]['duration']['text'] : false;
            $distanceKm = $distance ? (str_contains($distance, 'km') ? $distance : $this->convertDistance($this->extractNumericValue($distance), "km")) : null;
            $distanceMi = $distance ? (str_contains($distance, 'mi') ? $distance : $this->convertDistance($this->extractNumericValue($distance), "mi")) : null;
            $time = $this->convertTravelTimeToDaysHoursMinutes($duration);
            return [$distanceKm, $distanceMi, $time['days'], $time['hours'], $time['minutes']];
        } else {
            return [null, null, null, null];
        }
    }

    /**
     * get numeric value from distance text
     *
     * @param $str
     * @return float|null
     */
    function extractNumericValue($str) {
        // Use regular expression to extract numeric value
        preg_match('/[\d,]+(\.\d+)?/', $str, $matches);

        // Check if a match was found
        if(isset($matches[0])) {
            return floatval(str_replace(',', '', $matches[0])); // Convert the matched value to float after removing commas
        } else {
            return null; // Return null if no numeric value was found
        }
    }

    /**
     * convert kilometers to miles or miles to kilometers
     *
     * @param $distance
     * @param $unit
     * @return string
     */
    function convertDistance($distance, $unit) {
        if ($unit == 'km') {
            // Convert miles to kilometers
            return round($distance * 1.60934, 2)." km,";
        } elseif ($unit == 'mi') {
            // Convert kilometers to miles
            return round($distance / 1.60934, 2)." mi";
        } else {
            return "Invalid unit ". " ".$unit;
        }
    }

    /**
     * convert travel time to days, hours and minutes
     *
     * @param $travelTime
     * @return array
     */
    function convertTravelTimeToDaysHoursMinutes($travelTime) {
        $matches = [];
        preg_match_all('/(\d+)\s*(day|hour|min)/', $travelTime, $matches);

        $days = 0;
        $hours = 0;
        $minutes = 0;

        foreach ($matches[2] as $index => $unit) {
            $value = (int)$matches[1][$index];
            if ($unit == 'day') {
                $days += $value;
            } elseif ($unit == 'hour') {
                $hours += $value;
            } elseif ($unit == 'min') {
                $minutes += $value;
            }
        }

        return ['days' => $days, 'hours' => $hours, 'minutes' => $minutes];
    }

    /**
     * process stripe payment
     *
     * @param Request $request
     * @return void
     */
    public function processPayment(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $token = $request->input('stripeToken');
        $amount = $request->input('amount');

        $response = array();
        try {
            Charge::create([
                'amount' => $amount * 100,
                'currency' => 'usd',
                'description' => 'Example Charge',
                'source' => $token,
            ]);

            $file = $_FILES['commuteFile']['tmp_name'];
            $this->processRoutes($file);
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }
        if(isset($response['error'])) {
            http_response_code(400);// Set HTTP status code to indicate a bad request
            echo json_encode($response);
            exit();
        }
    }

    /**
     * send commute file
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendCommuteFile(Request $request)
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFilePath, $request->input('fileContent'));

        $details = [
            'email'  => $request->input('emailAddress'),
            'title' => 'Kuvosys - Commute File',
            'body' => 'Please see the attached file. Thanks!',
            'tempFilePath' => $tempFilePath
        ];

        Mail::html($details['body'], function($message) use ($details) {
            $message->to($details['email'])
                ->subject($details['title'])
                ->attach($details['tempFilePath'], ['as' => 'output_file.csv', 'mime' => 'application/csv']);

        });

        return response()->json(['message' => 'Email sent successfully']);
    }
}
