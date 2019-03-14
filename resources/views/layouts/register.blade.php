<?php
use Illuminate\Contracts\Session\Session;
?>
<!DOCTYPE html>

<html lang="en" class="smart-style-0">
<head>
    <title>AWS SaaS</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,300,400,500,700">
    <link rel="shortcut icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" media="screen, print" href="{{ asset('assets/vendors/vendors.bundle.css') }}">
    <link rel="stylesheet" media="screen, print" href="{{ asset('assets/app/app.bundle.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/pages/login.css') }}">

    @yield('css')

</head>
<body class="publicHeader-active animated fadeInDown smart-style-0">

<!-- BEGIN .sa-wrapper -->
<div class="sa-wrapper">
    @include('layouts.imports.header')
    <div class="sa-page-body">
        <!-- BEGIN .sa-content-wrapper -->
        <div class="sa-content-wrapper">
            <div class="sa-content">
                <div class="main" role="main">
                    @if ($message = session('success'))
                        <div class="alert alert-success alert-block">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            <strong>{{ $message }}</strong>
                        </div>
                    @endif

                    @if ($message = session('error'))
                        <div class="alert alert-danger alert-block">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            <strong>{{ $message }}</strong>
                        </div>
                    @endif

                    @if ($message = session('warning'))
                        <div class="alert alert-warning alert-block">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            <strong>{{ $message }}</strong>
                        </div>
                    @endif

                    @if ($message = session('info'))
                        <div class="alert alert-info alert-block">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            <strong>{{ $message }}</strong>
                        </div>
                    @endif
                    @yield('content')
                </div>
            </div>
        </div>
        <!-- END .sa-content-wrapper -->
    </div>
</div>
<!-- END .sa-wrapper -->

<script src="{{ asset('assets/vendors/vendors.bundle.js') }}"></script>
<script src="{{ asset('assets/app/app.bundle.js') }}"></script>

<script>
    $(function () {
        $('#menu1').metisMenu();
    });
</script>

@yield('script')

</body>
</html>
