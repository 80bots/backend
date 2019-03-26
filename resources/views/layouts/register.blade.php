@php
    use Illuminate\Contracts\Session\Session;
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @yield('title')

    <link rel="stylesheet" href="{{asset('css/jquery-ui.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/bootstrap.min.css')}} ">
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,500,600" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('css/style.css')}} ">
    <link rel="stylesheet" href="{{asset('css/theme.css')}}">
    @yield('css')
</head>
<body>

@yield('content')

<script type="text/javascript" src="{{asset('js/jquery-3.3.1.min.js')}}"></script>
<script type="text/javascript" src="{{asset('js/bootstrap.min.js')}}"></script>
<script type="text/javascript" src="{{asset('js/jquery-ui.min.js')}}"></script>
@yield('scripts')
</body>
</html>
