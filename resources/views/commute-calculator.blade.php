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
                <a class="nav-link" href="/distance-calculator">
                    Distance Calculator
                </a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="/commute-calculator">
                    Commute Calculator
                </a>
            </li>
        </ul>
    </div>
</nav>
<body>
<div id="cover-spin"></div>
<div class="panel">
    <div class="commute-calculator">
        <h6 class="upload-file-text"><b>Upload an Excel or CSV file with 'From' and 'To' addresses and we will calculate the routes for you.</b></h6>
        <div class="file-upload">
            <div class="file-select">
                <form id="commuteForm" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="file-select-button" id="fileName">Choose File</div>
                    <div class="file-select-name" id="noFile">No file chosen...</div>
                    <input type="file" name="commuteFile" id="commuteFile" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                </form>
            </div>
            <br>
            <button class="btn btn-secondary" onclick="submitCommute()">Submit</button>
        </div>
        <div class="modal fade" id="commutePaymentModal" tabindex="-1" role="dialog" aria-labelledby="commutePaymentModalLabel" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="commutePaymentModalLabel"><b>Please Pay First!</b></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="commutePaymentForm" action="/process-payment" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="cardElement">Credit or debit card</label>
                                <div id="cardElement"></div>
                                <div id="cardErrors" role="alert"></div>
                            </div>
                            <div id="amountToPay"></div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary submit-payment-btn" onclick="submitPayment()">Submit Payment</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const stripe_key = "{{ env('STRIPE_KEY') }}";
</script>
<script src="https://js.stripe.com/v3/"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
