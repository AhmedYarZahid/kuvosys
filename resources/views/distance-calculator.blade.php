<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<nav class="navbar navbar-icon-top navbar-expand-lg navbar-dark">
    <div class="site-logo">
        <img src="logo.png">
    </div>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
                <a class="nav-link" href="/">
                    Distance Calculator
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    Find Distances
                </a>
            </li>
        </ul>
    </div>
</nav>
<body>
<div class="panel">
    <div class="distance-calculator-fields">
        <div class="form-group">
            <div class="row">
                <div class="col-md-4 col-lg-2">
                    <label for="from"><b>From:</b></label>
                    <input name="from" id="from" />
                </div>
                <div class="col-md-4 col-lg-2">
                    <label for="to"><b>To:</b></label>
                    <input name="to" id="to" />
                </div>
                <div class="col-md-4 col-lg-2">
                    <button class="btn btn-secondary w-50 find-distance" onclick="findDistance()">Find</button>
                </div>
            </div>
        </div>
    </div>
    <div id="map"></div>
    <div class="col-sm-12 text-center mt-3">
        <span id="distanceDetails"></span>
    </div>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <ins class="adsbygoogle"
         style="display:inline-block;width:728px;height:90px"
         data-ad-client="{{ env('GOOGLE_ADD_CLIENT') }}"
         data-ad-slot="{{ env('GOOGLE_ADD_PUB') }}"
    ></ins>
    <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
    </script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places&callback=initMap" async defer></script>
</div>
</body>
</html>
