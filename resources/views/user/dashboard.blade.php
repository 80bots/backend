@extends('layouts.app')

@section('title')
    {{ __('user.dashboard.title') }}
@endsection

@section('content')
    <div class="wrapper">
        @include('layouts.imports.messages')
        <h1>{{ __('user.dashboard.welcome') }}</h1>
    </div>
@endsection

@yield('scripts')
