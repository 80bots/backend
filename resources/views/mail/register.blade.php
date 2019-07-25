<p>{{ __('mail.hello') }}, <strong>{{$user->name}}</strong></p>

<p>{{ __('mail.register.completed') }}</p>
@php
    $url = env('APP_URL','http://127.0.0.1:8000').'/user-activation/'.$user->verification_token;
@endphp

<a href="{{$url}}">{{ __('mail.register.activate') }}</a>

<p>{{ __('mail.thanks') }},</p>
<br>
{{ config('app.name') }}
