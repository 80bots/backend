<p>Hello, <strong>{{ $user->name ?? '' }}</strong></p>

<p>Your account credit is low. available credit is {{ $user->remaining_credits ?? 0 }}. please update your credit before your bots stoping.</p>

<p>Thanks,</p>

<br>
{{ config('app.name') }}
