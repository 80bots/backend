@extends('layouts.app')

@section('title')
    Dashboard
@endsection

@section('content')
    <div class="wrapper">
        @include('layouts.imports.messages')
        <h1>Welcome User</h1>
    </div>
@endsection

@yield('scripts')
