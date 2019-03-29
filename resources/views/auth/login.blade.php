@extends('layouts.register')

@section('css')

@endsection

@section('title')
    <title>Login | 80bots</title>
@endsection

@section('content')

    <div class="login-box-wrapper d-flex align-items-center justify-content-center flex-column">
        @include('layouts.imports.messages')
        <div class="p-4 login-box mb-3 d-flex align-items-center">
            <form method="POST" action="{{ route('login') }}" id="frmSignin" class="flex-grow-1">
                @csrf
                <h2 class="text-primary text-center">80bots</h2>
                <h4 class="text-center">Sign In</h4>
                <div class="form-group">
                    <label for="email">Username / E-mail</label>
                    <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                           name="email" value="{{ old('email') }}" required autofocus>
                    @if ($errors->has('email'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" type="password"
                           class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password"
                           value="{{ old('password') }}" required autofocus>
                    @if ($errors->has('password'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary btn-block text-uppercase mb-3">Sign in</button>
                <div class="text-right">
                    <a href="#frmForgotPwd" data-toggle="form" data-slide="left" class="text-dark text-decoration-none">Forgot
                        Password?</a>
                </div>
            </form>
            <form id="frmForgotPwd" class="flex-grow-1" action="javascript:void(0)" style="display: none;">
                <h4 class="text-primary text-center">Forgot password?</h4>
                <p class="text-center">Reset password link will be sent on email id</p>
                <div class="form-group">
                    <label for="">Email</label>
                    <input type="email" class="form-control"/>
                </div>
                <button type="submit" class="btn btn-primary btn-block text-uppercase mb-3">Send reset link</button>
                <div>
                    <a href="#frmSignin" data-toggle="form" data-slide="right" class="text-dark text-decoration-none">Back
                        to Sign In</a>
                </div>
            </form>
        </div>
        <h5 class="text-white mb-0">Not registered? <a href="{{route('register')}}" class="text-white font-weight-bold">Sign up</a>
        </h5>
    </div>

@endsection

@section('scripts')
    <script type="text/javascript">
        $(document).on('click', '[data-toggle="form"]', function () {
            let $targetEle = $(this.getAttribute('href'));
            let $parentForm = $(this).parents('form');
            let slideTo = this.dataset.slide;
            let slideTargetTo = $targetEle.find('[data-toggle="form"]').data('slide');
            if ($parentForm.parents('.login-box').attr('style') === undefined) {
                $parentForm.parents('.login-box').css('height', ($parentForm.outerHeight() + 48) + 'px');
            }
            $parentForm.hide('slide', {direction: slideTo}, function () {
                $targetEle.show('slide', {direction: slideTargetTo}, function () {
                    if ($parentForm.parents('.login-box').attr('style') === undefined) {
                        $parentForm.parents('.login-box').animate({
                            height: ($targetEle.outerHeight() + 48) + 'px'
                        }, 250);
                    }
                });
            });
        })
    </script>
@endsection
