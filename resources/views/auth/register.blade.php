@extends('layouts.register')

@section('css')

@endsection

@section('title')
    <title>{{ __('auth.register.title') . ' | ' . __('keywords.brand') }}</title>
@endsection

@section('content')
    <div class="login-box-wrapper d-flex align-items-center justify-content-center flex-column container">
          <div class="mb-3 d-flex align-items-center">
            @include('layouts.imports.messages')
          </div>
          <div class="p-4 login-box mb-3">
            <form method="POST" action="{{ route('register') }}" id="frmSignup" class="smart-form client-form">
                @csrf
                <a href="/" class="sidebar-brand text-decoration-none"><img src="{{ asset('assets/images/80bots.svg') }}" alt=""></a>
                <input type="hidden" id="timezone" name="timezone">
                <h4 class="text-center">{{ __('auth.sign_up') }}</h4>
                {{--<div class="form-group">
                    <label for="name">Username</label>
                    <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}"
                           name="name" value="{{ old('name') }}" required autofocus>
                    @if ($errors->has('name'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('name') }}</strong>
                        </span>
                    @endif
                </div>--}}
                <div class="form-group">
                    <label for="">{{ __('auth.email') }}</label>
                    <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                           name="email" value="{{ old('email') }}" required>
                    @if ($errors->has('email'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group">
                    <label for="">{{ __('auth.password') }}</label>
                    <input id="password" type="password"
                           class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password"
                           required>
                    @if ($errors->has('password'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group">
                    <label for="">{{ __('auth.register.confirm_password') }}</label>
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation"
                           required>
                    @if ($errors->has('password'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary btn-block text-uppercase mb-3">
                    {{ __('auth.sign_up') }}
                </button>
            </form>
        </div>
        <h5 class="text-white mb-0">{{ __('auth.register.already_member') }}
            <a href="{{route('login')}}" class="text-white font-weight-bold">{{ __('auth.sign_in') }}</a>
        </h5>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/jstz.min.js') }}"></script>
    <script language="javascript">
        getTimezoneName();
        function getTimezoneName() {
            timezone = jstz.determine()
            $('#timezone').val(timezone.name());
        }
    </script>
@endsection
