@php
use Illuminate\Support\Facades\Auth;
$user = Auth::user();
@endphp
<!DOCTYPE html>
<html lang="en" class="smart-style-0">
<head>
    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,300,400,500,700">
    <link rel="shortcut icon" href="{{asset('assets/img/favicon/favicon.ico')}}" type="image/x-icon">
    <link rel="icon" href="{{asset('assets/img/favicon/favicon.ico')}}" type="image/x-icon">
    <link rel="stylesheet" media="screen, print" href="{{asset('assets/vendors/vendors.bundle.css')}}">
    <link rel="stylesheet" media="screen, print" href="{{asset('assets/app/app.bundle.css')}}">

    @yield('css')

</head>
<body class="smart-style-0">

<!-- BEGIN .sa-wrapper -->
<div class="sa-wrapper">
    <!-- BEGIN .sa-shortcuts -->
{{--@include('layouts.imports.shortcuts')--}}
<!-- END .sa-shortcuts -->

    <!-- BEGIN .header -->
@include('layouts.imports.header')
<!-- END .header -->

    <div class="sa-page-body">

        <!-- BEGIN .sa-aside-left -->
        @if($user->role->name == 'User')
            @include('layouts.imports.side-left')
        @else
            @include('layouts.imports.admin-side-left')
        @endif
    <!-- END .sa-aside-left -->

        <!-- BEGIN .sa-content-wrapper -->
        <div class="sa-content-wrapper">

            <!-- BEGIN .sa-page-breadcrumb -->
        @include('layouts.imports.breadcrumb')
        <!-- END .sa-page-breadcrumb -->

            <div class="sa-content">
                {{--<div class="d-flex w-100 home-header">
                    <div>
                        <h1 class="page-header"><i class="fa-fw fa fa-puzzle-piece"></i> App Views <span>> Profile</span></h1>
                    </div>
                    --}}{{--<div class="ml-auto">
                        <ul class="sa-sparks">
                            <li class="sparks-info">
                                <h5> <small>My Income</small> <span class="text-blue">$47,171</span></h5>
                                <div class="sparkline text-blue d-none d-xl-block">
                                    1300, 1877, 2500, 2577, 2000, 2100, 3000, 2700, 3631, 2471, 2700, 3631, 2471
                                </div>
                            </li>
                            <li class="sparks-info">
                                <h5> <small>Site Traffic</small> <span class="text-purple"><i class="fa fa-arrow-circle-up" data-rel="bootstrap-tooltip" title="Increased"></i>&nbsp;45%</span></h5>
                                <div class="sparkline text-purple d-none d-xl-block">
                                    110,150,300,130,400,240,220,310,220,300, 270, 210
                                </div>
                            </li>
                            <li class="sparks-info">
                                <h5> <small>Site Orders</small> <span class="text-green-dark"><i class="fa fa-shopping-cart"></i>&nbsp;2447</span></h5>
                                <div class="sparkline text-green-dark d-none d-xl-block">
                                    110,150,300,130,400,240,220,310,220,300, 270, 210
                                </div>
                            </li>
                        </ul>
                    </div>--}}{{--
                </div>--}}
                {{--<div class="d-flex w-100">--}}
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
                {{--</div>--}}
            </div>
            @include('layouts.imports.footer')
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
