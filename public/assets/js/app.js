/**
 * map
 */
var map;

/**
 * markers
 *
 * @type {*[]}
 */
var markers = [];

/**
 * route
 */
var directionsDisplay;

/**
 * initialize map, centered at users' location
 */
function initMap() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 8,
                center: userLocation,
                fullscreenControl: false,
                streetViewControl: false
            });

            initAutocomplete();

            initDirectionDisplay();
        }, function() {
            // Handle geolocation error
            alert('Error: The Geolocation service failed.');
        });
    }
}

/**
 * initialize autocomplete on 'from' and 'to' addresses fields
 */
function initAutocomplete() {
    new google.maps.places.Autocomplete(document.getElementById('from'));
    new google.maps.places.Autocomplete(document.getElementById('to'));
}

/**
 * initialize directions display
 */
function initDirectionDisplay() {
    directionsDisplay = new google.maps.DirectionsRenderer();
}

/**
 * find distance between 'from' and 'to' addresses and draw route on map
 */
function findDistance() {
    var geocoder = new google.maps.Geocoder();
    var fromAddress = document.getElementById('from').value;
    var toAddress = document.getElementById('to').value;

    if(fromAddress !== '' && toAddress !== '') {
        removeMarkers();

        geocoder.geocode({
            'address': fromAddress
        }, function(results, status) {

            if (status == google.maps.GeocoderStatus.OK) {
                var latitude = results[0].geometry.location.lat();
                var longitude = results[0].geometry.location.lng();
                placeMarker({lat: latitude, lng: longitude})
            }
        });

        geocoder.geocode({
            'address': toAddress
        }, function(results, status) {

            if (status == google.maps.GeocoderStatus.OK) {
                var latitude = results[0].geometry.location.lat();
                var longitude = results[0].geometry.location.lng();
                placeMarker({lat: latitude, lng: longitude});
            }
        });
    } else {
        alert("Please add both from and to addresses!")
    }
}

/**
 * remove all markers
 */
function removeMarkers() {
    for (let i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
    markers = [];
}

/**
 * add marker on map
 *
 * @param location
 */
function placeMarker(location) {
    var marker = new google.maps.Marker({
        position: location,
        map: map
    });
    markers.push(marker);
    map.panTo(location);
    calculateAndDisplayRoute();
}

/**
 * calculate and show the route on map with distance and time duration details
 */
function calculateAndDisplayRoute() {
    if(markers.length === 2) { // only if primary and secondary addresses have been placed
        let directionsService = new google.maps.DirectionsService();
        let start = new google.maps.LatLng(markers[0].getPosition().lat(), markers[0].getPosition().lng());
        let end = new google.maps.LatLng(markers[1].getPosition().lat(), markers[1].getPosition().lng());
        let bounds = new google.maps.LatLngBounds();
        bounds.extend(start);
        bounds.extend(end);
        map.fitBounds(bounds);
        let request = {
            origin: start,
            destination: end,
            travelMode: google.maps.TravelMode.DRIVING
        };
        directionsService.route(request, function (response, status) {
            if (status === google.maps.DirectionsStatus.OK) {
                directionsDisplay.setDirections(response);
                directionsDisplay.setMap(map); // show the route on map
                directionsDisplay.setOptions( { suppressMarkers: true } );
                var directionsData = response.routes[0].legs[0]; // get data about the mapped route
                var distanceText = directionsData.distance.text;
                var unit = distanceText.split(' ')[1];
                var distanceValue = parseFloat(distanceText.replace(/,/g, ''));
                var convertedDistance = convertDistance(distanceValue, unit);
                $('#distanceDetails').html('<b>Travel Distance:</b> ' + (unit === "mi" ? (directionsData.distance.text + " | " + convertedDistance) : (convertedDistance + " | " + directionsData.distance.text)) + '<br> <b>Estimated Time Duration:</b> ' + directionsData.duration.text).show();
            } else {
                directionsDisplay.setMap(null);
                alert('Directions request from ' + start.toUrlValue(6) + ' to ' + end.toUrlValue(6) + ' failed: ' + status + '.');
            }
        });
    }
}

/**
 * get km <=> mi
 *
 * @param distance
 * @param unit
 * @returns {string}
 */
function convertDistance(distance, unit) {
    if (unit === 'km') {
        var miles = distance * 0.621371;
        return miles.toFixed(2) + ' mi';
    } else if (unit === 'mi') {
        var kilometers = distance * 1.60934;
        return kilometers.toFixed(2) + ' km';
    } else {
        return 'Invalid unit: ' + unit;
    }
}

/**
 * get addresses count
 */
function getAddressesCount() {
    var formData = new FormData(document.getElementById('commuteForm'));
    $.ajax({
        url: '/get-addresses-count',
        headers: {'X-CSRF-TOKEN': $('#commuteForm meta[name="csrf-token"]').attr('content')},
        before: $('#cover-spin').show(0),
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            $('#cover-spin').hide();
            response = JSON.parse(response);
            $(".step-1 #count").html(response.records_count);
            $(".step-1 .number-of-rows").show();
            $(".process-commute-btn").show();
        },
        error: function(xhr, status, error) {
            $('#cover-spin').hide();
            var errorMessage = JSON.parse(xhr.responseText).error;
            alert("Error: " + errorMessage);
            $(".step-1 .number-of-rows").hide();
            $(".step-2").hide();
            resetDownloadSection();
        }
    });
}

/**
 * submit commute file
 */
function submitCommute() {
    resetDownloadSection();
    loadStripe();
    var formData = new FormData(document.getElementById('commuteForm'));
    $.ajax({
        url: '/process-commute',
        headers: {'X-CSRF-TOKEN': $('#commuteForm meta[name="csrf-token"]').attr('content')},
        before: $('#cover-spin').show(0),
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            $('#cover-spin').hide();
            var parsedJSON;
            try {
                parsedJSON = JSON.parse(response);
            } catch (error) {
                parsedJSON = null;
            }
            if(parsedJSON === null) {
                showDownloadSection(response);
            } else {
                $("#amountToPay").html("<b>Based on the number of rows, your total is $" + parsedJSON.charge + "<br> Please complete the payment to generate this report.</b> ");
                $(".submit-payment-btn").prop('disabled', false);
                $(".step-2").show();
            }
        },
        error: function(xhr, status, error) {
            $('#cover-spin').hide();
            var errorMessage = JSON.parse(xhr.responseText).error;
            alert("Error: " + errorMessage);
        }
    });
}

/**
 * show download section
 *
 * @param data
 */
function showDownloadSection(data) {
    var link = document.createElement('a');
    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(data);
    link.download = 'output_file.csv';
    var icon = document.createElement('i');
    icon.classList.add('ml-1', 'fa', 'fa-download');
    link.textContent = "DOWNLOAD";
    link.classList.add('btn', 'download-btn');
    link.appendChild(icon);
    var html = "<div class='col-sm-6 download-link centered-content'></div>" +
        "<div class='col-sm-6'>" +
        "<b>Email it to me</b>" +
        "<form id='commuteEmailForm'>" +
        "<div class='row'>" +
        "<div class='col-sm-9'>" +
        "<input id='customerEmailAddress' class='form-control'>" +
        "</div>" +
        "<div class='col-sm-3'>" +
        "<button type='button' class='btn site-blue-btn' onclick='sendCommuteFile($(\"#customerEmailAddress\").val(), " + JSON.stringify(data) + ")'>Send</button>" +
        "</div>" +
        "</div>" +
        "</form>" +
        "</div>";
    $(".step-3 .download-file-section").html(html);
    $(".step-3 .download-link").append(link);
    $(".step-3").show();
}

/**
 * reset download section
 */
function resetDownloadSection() {
    $(".step-3 .download-file-section").empty();
    $(".step-3").hide();

}
/**
 * submit payment
 */
function submitPayment() {
    $("#commutePaymentForm").submit()
}

/**
 * load stripe
 */
function loadStripe() {
    var stripe = Stripe(stripe_key);
    var elements = stripe.elements();
    var cardElement = elements.create('card');
    cardElement.mount('#cardElement');
    var form = $('#commutePaymentForm');
    var errorElement = $('#cardErrors');
    form.on('submit', function(event) {
        event.preventDefault();

        stripe.createToken(cardElement).then(function(result) {
            if (result.error) {
                errorElement.text(result.error.message);
            } else {
                errorElement.text("");
                stripeTokenHandler(result.token);
            }
        });
    });
}

/**
 * stripe token handler, carries the file too
 *
 * @param token
 */
function stripeTokenHandler(token) {
    var form = $('#commutePaymentForm');
    var commuteForm = $('#commuteForm')[0];
    var formData = new FormData(commuteForm);
    formData.append('stripeToken', token.id);
    $('#cover-spin').show();
    $.ajax({
        type: 'POST',
        url: form.attr('action'), // Assuming your form has the correct action URL
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            $('#cover-spin').hide();
            $(".submit-payment-btn").prop("disabled", true);
            showDownloadSection(response);
        },
        error: function(xhr, status, error) {
            $('#cover-spin').hide();
            var errorMessage = JSON.parse(xhr.responseText).error;
            alert("Error: " + errorMessage);
        }
    });
}

/**
 * send commute file to email
 *
 * @param email
 * @param fileContent
 */
function sendCommuteFile(email, fileContent) {
    $.ajax({
        url: '/send-commute-file',
        type: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        before: $('#cover-spin').show(0),
        data: {
            emailAddress: email,
            fileContent: fileContent
        },
        success: function(response) {
            $('#cover-spin').hide();
            alert('Email sent successfully!');
        },
        error: function(error) {
            $('#cover-spin').hide();
            alert('Error sending email!');
        }
    });
}


