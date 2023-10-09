<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
<div class="navbar-container">
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
                    <a class="nav-link site-blue-btn" href="/distance-calculator">
                        Distance Calculator
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link site-white-btn" href="/commute-calculator">
                        Commute Calculator
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</div>
<div class="content distance-calculator">
    <h5><b>Calculate Travel Distance</b></h5>
    <br>
    <div class="row g-3 align-items-center">
        <div class="col-auto">
            <label for="from" class="col-form-label"><b>From:</b></label>
        </div>
        <div class="col-auto">
            <input name="from" id="from" class="form-control" />
        </div>
        <div class="col-auto">
            <label for="to" class="col-form-label"><b>To:</b></label>
        </div>
        <div class="col-auto">
            <input name="to" id="to" class="form-control" />
        </div>
        <div class="col-auto">
            <button class="btn site-grey-btn find-distance" onclick="findDistance()">Find</button>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-lg-11">
            <div id="map"></div>
            <div class="row">
                <div class="col-lg-3">
                    <a href="/commute-calculator" class="btn site-white-btn distance-tool-btn"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Bulk Distance Calculation Tool</a>
                </div>
                <div class="col-lg-9">
                    <div id="distanceDetails"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-1">

        </div>
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
<footer>
    <a href="">Contact Us</a>
    <a href="">Terms</a>
    <a href="">Privacy Policy</a>
</footer>

