<?php

namespace App\Http\Controllers;

use App\Mail\SendEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;

class CommuteController extends Controller
{
    /**
     * process commute file
     *
     * @return void
     */
    public function processCommuteFile() {
        $response = array();
        if(isset($_FILES['commuteFile']) && !empty($_FILES['commuteFile']['tmp_name'])) {
            $file = $_FILES['commuteFile']['tmp_name'];
            if (fopen($file, "r") !== FALSE) {
                $records = $this->getRecordsCount(fopen($file, "r"));
                $response['success'] = true;
                $response['request_payment'] = true;
                if($records > 50 && $records <= 500) {
                    $response['charge'] = 0.015 * $records + 1.00;
                } else if($records > 500 && $records <= 1000) {
                    $response['charge'] = 0.014 * $records + 0.75;
                } else if($records > 1000) {
                    $response['charge'] = 0.013 * $records + 0.50;
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
     * @param $handle
     * @return int
     */
    private function getRecordsCount($handle) {
        $rowCount = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rowCount++;
        }
        return $rowCount - 1;
    }

    /**
     * process 'from' and 'to' addresses and show distance and travel time
     *
     * @param $file
     * @return void
     */
    private function processRoutes($file) {
        $output = [];

        $handle = fopen($file, "r");
        $i = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if($i) {
                $from = urlencode($data[0]);
                $to = urlencode($data[1]);

                list($distance, $duration) = $this->getTravelInfo($from, $to);

                $data[] = $distance;
                $data[] = $duration;
                $output[] = $data;
            } else {
                $output[] = ['From', 'To', 'Distance', 'Time'];
            }
            $i ++;
        }

        fclose($handle);

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
            $distance = $data['rows'][0]['elements'][0]['distance']['text'];
            $duration = $data['rows'][0]['elements'][0]['duration']['text'];

            return [$this->convertDistance($this->extractNumericValue($distance), (str_contains($distance, 'km') ? "km" : str_contains($distance, 'mi')) ? "mi" : ""), $duration];
        } else {
            return [null, null];
        }
    }

    /**
     * get numeric value from distance text
     *
     * @param $str
     * @return array|string|string[]|null
     */
    function extractNumericValue($str) {
        return preg_replace('/[^0-9]/', '', $str);
    }

    /**
     * get distance in km if in mi and mi if in km
     *
     * @param $distance
     * @param $unit
     * @return string
     */
    function convertDistance($distance, $unit) {
        if ($unit == 'km') {
            // Convert miles to kilometers
            return round($distance * 1.60934, 2)." km, ".$distance." mi";
        } elseif ($unit == 'mi') {
            // Convert kilometers to miles
            return $distance." km, ".round($distance * 0.621371, 2)." mi";
        } else {
            return "Invalid unit ". " ".$unit;
        }
    }

    public function processPayment(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $token = $request->input('stripeToken');
        $amount = 1000; // Amount in cents

        $response = array();
        try {
            Charge::create([
                'amount' => $amount,
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
