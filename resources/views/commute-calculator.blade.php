<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                <li class="nav-item">
                    <a class="nav-link site-white-btn" href="/distance-calculator">
                        Distance Calculator
                    </a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link site-blue-btn" href="/commute-calculator">
                        Commute Calculator
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</div>
<div id="cover-spin"></div>
<div class="content commute-calculator">
    <h5><b>Travel Distance & Time Calculator</b></h5>
    <hr>
    <div class="step-1">
        <h5 class="file-step-header">Upload your file</h5>
        <div class="row">
            <div class="col-sm-3 offset-sm-2 centered-content">
                <h6><b>Upload single Excel or CSV file that include 'From' and 'To' columns with header.</b></h6>
            </div>
            <div class="col-sm-7">
                <img  id="excelImage" src="excel.png">
            </div>
        </div>
        <div class="row file-upload mt-2">
            <div class="col-sm-4">
                <form id="commuteForm" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="commuteFile" id="commuteFile" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                    <button type="button" class="btn site-white-btn" onclick="getAddressesCount()">Upload a file</button>
                </form>
            </div>
            <div class="col-sm-4 centered-content">
                <p class="number-of-rows"><span id="count"></span> rows successfully uploaded.</p>
            </div>
            <div class="col-sm-4 centered-content">
                <button class="btn site-blue-btn process-commute-btn" onclick="submitCommute()">Next</button>
            </div>
        </div>
        <hr>
    </div>
    <div class="step-2">
        <h5 class="file-step-header">Payment</h5>
        <form class="text-center" id="commutePaymentForm" action="/process-payment" enctype="multipart/form-data">
            @csrf
            <div id="amountToPay"></div>
            <div class="form-group">
                <label for="cardElement">Credit or debit card</label>
                <div class="card-element-section">
                    <div id="cardElement"></div>
                </div>
                <div id="cardErrors" role="alert"></div>
            </div>

            <input type="checkbox" id="acceptTerms">&nbsp;<b>Yes, I accept <a class="accept-terms-link" href="">Terms</a></b><br>
            <button type="button" class="btn site-blue-btn submit-payment-btn mt-2" onclick="submitPayment()">Submit Payment</button>
        </form>
        <hr>
    </div>
    <div class="step-3">
        <h5 class="file-step-header">Report Download</h5>
        <div class="row download-file-section">

        </div>
        <hr>
    </div>
</div>
<script>
    const stripe_key = "{{ env('STRIPE_KEY') }}";
</script>
<script src="https://js.stripe.com/v3/"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
<footer>
    <a href="">Contact Us</a>
    <a href="">Terms</a>
    <a href="">Privacy Policy</a>
</footer>
