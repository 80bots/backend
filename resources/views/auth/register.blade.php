@extends('layouts.register')

@section('css')

@endsection

@section('content')
    <!-- MAIN CONTENT -->
    <div id="content" class="container padding-top-10">
        <div class="row">
            <div class="col col-lg-7 d-lg-block d-none">
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

                        <img src="{{ asset('assets/img/demo/iphoneview.png') }}" class="pull-right display-image" alt="" style="width:210px">

                    </div>
                </div>


                <div class="row">
                    <div class="col-sm-12 col-md-6 col-lg-6">
                        <h5 class="about-heading">About SmartAdmin - Are you up to date?</h5>
                        <p>
                            Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa.
                        </p>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-6">
                        <h5 class="about-heading">Not just your average template!</h5>
                        <p>
                            Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi voluptatem accusantium!
                        </p>
                    </div>
                </div>

            </div>
            <div class="col-12 col-lg-5">
                <div class="well no-padding">
                    <form method="POST" action="{{ route('register') }}" id="smart-form-register" class="smart-form client-form">
                        @csrf
                        <header>
                            Registration is FREE*
                        </header>

                        <fieldset>
                            <section class="mb-3">
                                <label class="input"> <i class="icon-append fa fa-user"></i>
                                    <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" required autofocus>
                                    @if ($errors->has('name'))
                                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                    @endif
                                    <b class="tooltip tooltip-bottom-right">{{ __('Name') }}</b>
                                </label>
                            </section>

                            <section class="mb-3">
                                <label class="input"> <i class="icon-append fa fa-user"></i>
                                    <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required>
                                    @if ($errors->has('email'))
                                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                    @endif
                                    <b class="tooltip tooltip-bottom-right">{{ __('E-Mail Address') }}</b>
                                </label>
                            </section>

                            <section class="mb-3">
                                <label class="input"> <i class="icon-append fa fa-lock"></i>
                                    <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
                                    @if ($errors->has('password'))
                                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                    @endif
                                    <b class="tooltip tooltip-bottom-right">{{ __('Password') }}</b>
                                </label>
                            </section>

                            <section class="mb-3">
                                <label class="input"> <i class="icon-append fa fa-lock"></i>
                                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
                                    @if ($errors->has('password'))
                                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                    @endif
                                    <b class="tooltip tooltip-bottom-right">{{ __('Confirm Password') }}</b>
                                </label>
                            </section>


                        </fieldset>
                        <footer>
                            <button type="submit" class="btn sa-btn-primary">
                                {{ __('Register') }}
                            </button>
                        </footer>

                        <div class="message">
                            <i class="fa fa-check"></i>
                            <p>
                                Thank you for your registration!
                            </p>
                        </div>
                    </form>

                </div>

                <p class="note text-center">*FREE Registration ends on October 2015.</p>
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
    <script type="text/javascript">
        // Model i agree button
        $("#i-agree").click(function(){
            $this=$("#terms");
            if($this.checked) {
                $('#myModal').modal('toggle');
            } else {
                $this.prop('checked', true);
                $('#myModal').modal('toggle');
            }
        });

        // Validation
        $(function() {
            // Validation
            $("#smart-form-register").validate({

                // Rules for form validation
                rules : {
                    username : {
                        required : true
                    },
                    email : {
                        required : true,
                        email : true
                    },
                    password : {
                        required : true,
                        minlength : 3,
                        maxlength : 20
                    },
                    passwordConfirm : {
                        required : true,
                        minlength : 3,
                        maxlength : 20,
                        equalTo : '#password'
                    },
                    firstname : {
                        required : true
                    },
                    lastname : {
                        required : true
                    },
                    gender : {
                        required : true
                    },
                    terms : {
                        required : true
                    }
                },

                // Messages for form validation
                messages : {
                    login : {
                        required : 'Please enter your login'
                    },
                    email : {
                        required : 'Please enter your email address',
                        email : 'Please enter a VALID email address'
                    },
                    password : {
                        required : 'Please enter your password'
                    },
                    passwordConfirm : {
                        required : 'Please enter your password one more time',
                        equalTo : 'Please enter the same password as above'
                    },
                    firstname : {
                        required : 'Please select your first name'
                    },
                    lastname : {
                        required : 'Please select your last name'
                    },
                    gender : {
                        required : 'Please select your gender'
                    },
                    terms : {
                        required : 'You must agree with Terms and Conditions'
                    }
                },

                // Ajax form submition
                submitHandler : function(form) {
                    $(form).ajaxSubmit({
                        success : function() {
                            $("#smart-form-register").addClass('submited');
                        }
                    });
                },

                // Do not change code below
                errorPlacement : function(error, element) {
                    error.insertAfter(element.parent());
                }
            });

        });
    </script>
@endsection
