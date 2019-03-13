@extends('layouts.register')

@section('css')

@endsection

@section('content')
<!-- MAIN CONTENT -->
<div id="content" class="container padding-top-10">
    <div class="row">
        <div class="col-sm-12 col-md-7 col-lg-8 hidden-xs hidden-sm">
            <h1 class="text-red login-header-big">{{config('app.name')}}</h1>
            <div class="clearfix">
                <div class="hero">
                    <div class="pull-left login-desc-box-l">
                        <h4 class="paragraph-header">It's Okay to be Smart. Experience the simplicity of SmartAdmin, everywhere you go!</h4>
                        <div class="login-app-icons">
                            <a href="javascript:void(0);" class="btn sa-btn-danger btn-sm">Frontend Template</a>
                            <a href="javascript:void(0);" class="btn sa-btn-danger btn-sm">Find out more</a>
                        </div>
                    </div>

                    <img src="{{asset('assets/img/demo/iphoneview.png')}}" class="pull-right display-image" alt="" style="width:210px">

                </div>
            </div>


            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                    <h5 class="about-heading">About SmartAdmin - Are you up to date?</h5>
                    <p>
                        Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa.
                    </p>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                    <h5 class="about-heading">Not just your average template!</h5>
                    <p>
                        Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi voluptatem accusantium!
                    </p>
                </div>
            </div>

        </div>
        <div class="col-sm-12 col-lg-4">
            <div class="well no-padding">
                <form method="POST" action="{{ route('login') }}" id="login-form" class="smart-form client-form">
                    @csrf
                    <header>
                        Sign In
                    </header>

                    <fieldset>
                        <section>
                            <label class="label">E-mail</label>
                            <label class="input mb-3"> <i class="icon-append fa fa-user"></i>
                                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required autofocus>
                                @if ($errors->has('email'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                                <b class="tooltip tooltip-top-right"><i class="fa fa-user txt-color-teal"></i> Please enter email address/username</b></label>
                        </section>

                        <section>
                            <label class="label">Password</label>
                            <label class="input mb-1"> <i class="icon-append fa fa-lock"></i>
                                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                                <b class="tooltip tooltip-top-right"><i class="fa fa-lock txt-color-teal"></i> Enter your password</b> </label>
                            <div class="note mb-3">
                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                        </section>

                        <section>
                            <label for="gra-0" class="vcheck mb-3">
                                <input type="checkbox" name="remember"  id="gra-0" {{ old('remember') ? 'checked' : '' }}>
                                <span></span> {{ __('Remember Me') }}
                            </label>

                        </section>
                    </fieldset>
                    <footer>
                        <button type="submit" class="btn btn-primary">
                            {{ __('Login') }}
                        </button>
                    </footer>
                </form>

            </div>

            <h5 class="text-center"> - Or sign in using -</h5>

            <ul class="list-inline text-center">
                <li>
                    <a href="javascript:void(0);" class="btn sa-btn-primary btn-circle"><i class="fa fa-facebook"></i></a>
                </li>
                <li>
                    <a href="javascript:void(0);" class="btn sa-btn-info btn-circle"><i class="fa fa-twitter"></i></a>
                </li>
                <li>
                    <a href="javascript:void(0);" class="btn sa-btn-warning btn-circle"><i class="fa fa-linkedin"></i></a>
                </li>
            </ul>

        </div>
    </div>
</div>
@endsection

@section('scripts')

@endsection
