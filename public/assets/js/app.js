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
 * commute payment modal original content
 */
var commutePaymentModalOriginalContent;


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
                $('span#distanceDetails').html('<b>Travel Distance:</b> ' + (unit === "km" ? (directionsData.distance.text + ", " + convertedDistance) : (convertedDistanceconvertedDistance + ", " + directionsData.distance.text)) + '<br> <b>Estimated Time Duration:</b> ' + directionsData.duration.text);
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

$(document).ready(function() {
    /**
     * handle commute file change
     */
    $('#commuteFile').bind('change', function () {
        var filename = $("#commuteFile").val();
        if (/^\s*$/.test(filename)) {
            $(".commute-calculator .file-upload").removeClass('active');
            $(".commute-calculator #noFile").text("No file chosen...");
        }
        else {
            $(".commute-calculator .file-upload").addClass('active');
            $(".commute-calculator #noFile").text(filename.replace("C:\\fakepath\\", ""));
        }
    });

    commutePaymentModalOriginalContent = $('#commutePaymentModal').html();
});

/**
 * submit commute file
 */
function submitCommute() {
    loadStripeModal();
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
                var link = document.createElement('a');
                link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response);
                link.download = 'output_file.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                $("#amountToPay").html("Please pay <b>" + parsedJSON.charge + " USD</b> amount to proceed.");
                $("#commutePaymentModal").modal("show");
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
 * reload stripe modal
 */
function loadStripeModal() {
    $('#commutePaymentModal').html(commutePaymentModalOriginalContent);
    loadStripe();
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
            var link = document.createElement('a');
            link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response);
            link.download = 'output_file.csv';
            link.textContent = "Download";
            $(".submit-payment-btn").hide();
            $("#commutePaymentModal .modal-title").html("<b>Payment Successful!</b>")
            var message = "Download your file by clicking on the link below or send it to an email address: <form id='commuteEmailForm'><input id='customerEmailAddress'> <button type='button' class='btn btn-primary' onclick='sendCommuteFile($(\"#customerEmailAddress\").val(), " + JSON.stringify(response) + ")'>Send</button></form>";
            $("#commutePaymentModal .modal-body").html(message).append(link);
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


