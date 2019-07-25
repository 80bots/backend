@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('auth.verify.verify_your_email') }}</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('auth.verify.link_sent') }}
                        </div>
                    @endif

                    {{ __('auth.verify.verify_first') }}
                    {{ __('auth.verify.did_not_received') }},
                    <a href="{{ route('verification.resend') }}">{{ __('auth.verify.request_link') }}</a>.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
