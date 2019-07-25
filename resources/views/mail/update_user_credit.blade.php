<p>{{ __('mail.hello') }}, <strong>{{$user->name}}</strong></p>

<p>{{ __('mail.credit.updated') }}</p>
<p>{{ __('mail.credit.balance') }} :- <b> {{$user->remaining_credits}}</b>.</p>
<p>{{ __('mail.thanks') }},</p>

<br>
{{ config('app.name') }}
