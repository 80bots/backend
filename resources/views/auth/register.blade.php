@extends('layouts.register')

@section('css')

@endsection

@section('title')
    <title>Sign Up | 80bots</title>
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
                <h4 class="text-center">Sign Up</h4>
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
                    <label for="">Email</label>
                    <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                           name="email" value="{{ old('email') }}" required>
                    @if ($errors->has('email'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group">
                    <label for="">Password</label>
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
                    <label for="">Confirm Password</label>
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation"
                           required>
                    @if ($errors->has('password'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary btn-block text-uppercase mb-3">Sign Up</button>
            </form>
        </div>
        <h5 class="text-white mb-0">Already a member?
            <a href="{{route('login')}}" class="text-white font-weight-bold">Sign in</a>
        </h5>
    </div>
@endsection

@section('scripts')

@endsection
