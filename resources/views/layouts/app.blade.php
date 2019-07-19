@php
use Illuminate\Support\Facades\Auth;
$user = Auth::user();
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

    <title>@yield('title') | 80bots</title>

    <link rel="stylesheet" href="{{asset('css/app.css')}}">
    <!--link rel="stylesheet" href="{{asset('css/bootstrap.min.css')}}"-->
    <link rel="stylesheet" href="{{asset('css/datatables.min.css')}}">
    <link rel="stylesheet" href="{{asset('vendors/font-awesome/css/all.min.css')}}">
    <link rel="stylesheet" href="{{asset('vendors/select2/css/select2.min.css')}}">
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,500,600" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('css/style.css')}}">
    <link rel="stylesheet" href="{{asset('css/theme.css')}}">
    <link rel="stylesheet" href="{{ URL::asset('css/all.min.css') }}">
    @yield('css')

    @if(Request::is( Config::get('chatter.routes.home') ) || Request::is( Config::get('chatter.routes.home') . '/*' ))
    <!-- LINK TO YOUR CUSTOM STYLESHEET FOR FORUM -->
    <link rel="stylesheet" href="{{asset('css/forums.css')}}" />
    @endif
</head>
<body>
<div class="main-wrapper d-flex align-items-stretch">
    @if(isset($user))
        @if($user->role->name == 'User')
            @include('layouts.imports.side-left')
        @else
            @include('layouts.imports.admin-side-left')
        @endif
    @endif

    <div class="body-content flex-grow-1">

        @include('layouts.imports.header')

        @yield('content')
    </div>
</div>
<script>
    window.Laravel = {
        'csrfToken': '{{ csrf_token() }}',
        'user': '{{Auth::user()->id}}'
    };
</script>
<script type="text/javascript" src="{{asset('js/jquery-3.3.1.min.js')}}"></script>
<!--script type="text/javascript" src="{{asset('js/bootstrap.min.js')}}"></script-->
<script type="text/javascript" src="{{asset('js/app.js')}}"></script>
<script type="text/javascript" src="{{asset('js/bootbox.all.min.js')}}"></script>
<script type="text/javascript" src="{{asset('js/datatables.min.js')}}"></script>
<script type="text/javascript" src="{{asset('vendors/select2/js/select2.full.min.js')}}"></script>
<script type="text/javascript" src="{{asset('js/script.js')}}"></script>
@yield('script')
@yield('js')


</body>
</html>
