<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two Factor Challenge - Data Match System</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <b>Data Match</b> System
        </div>

        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg" id="codeMessage">Please confirm access to your account by entering the authentication code provided by your authenticator application.</p>
                <p class="login-box-msg" id="recoveryMessage" style="display: none;">Please confirm access to your account by entering one of your emergency recovery codes.</p>

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('two-factor.login') }}" method="POST">
                    @csrf
                    <div id="codeInput">
                        <div class="input-group mb-3">
                            <input type="text" name="code" class="form-control" placeholder="Authentication Code" autofocus>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-key"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="recoveryInput" style="display: none;">
                        <div class="input-group mb-3">
                            <input type="text" name="recovery_code" class="form-control" placeholder="Recovery Code">
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-shield-alt"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Verify</button>
                        </div>
                    </div>
                </form>

                <p class="mt-3 mb-1">
                    <a href="#" id="toggleRecovery">Use a recovery code</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#toggleRecovery').click(function(e) {
                e.preventDefault();
                $('#codeInput, #recoveryInput, #codeMessage, #recoveryMessage').toggle();
                if ($('#codeInput').is(':visible')) {
                    $(this).text('Use a recovery code');
                    $('#codeInput input').focus();
                } else {
                    $(this).text('Use an authentication code');
                    $('#recoveryInput input').focus();
                }
            });
        });
    </script>
</body>
</html>
