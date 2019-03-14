<p>Hello, <strong>{{$user->name}}</strong></p>

<p>Your Registration has been Completed! Please click below link for activate your account</p>
@php
    $url = env('APP_URL','http://127.0.0.1:8000').'/user-activation/'.$user->verification_token;
@endphp

<a href="{{$url}}">Click TO Active</a>

<p>Thanks,</p>
<br>
{{ config('app.name') }}
