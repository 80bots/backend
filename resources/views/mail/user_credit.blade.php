<p>{{ __('mail.hello') }}, <strong>{{$user->name}}</strong></p>

<p>{{ __('mail.credit.low', $user->remaining_credits) }}</p>

<p>{{ __('mail.thanks') }},</p>

<br>
{{ config('app.name') }}
