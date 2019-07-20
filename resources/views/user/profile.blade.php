@extends('layouts.app')

@section('title')
    Profile
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        @include('layouts.imports.messages')

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">User Profile</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="form-group">
                            <label for="">Email</label>
                            <input type="text" name="email" class="form-control" value="{{!empty($user->email) ? $user->email : ''}}" readonly/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Credit Used</label>
                            <input type="text" name="credit_used" value="{{!empty($used_credit) ? $used_credit : 0}}" readonly class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Credit Remaining</label>
                            <input type="text" name="remaining_credits" value="{{!empty($user->remaining_credits) ? $user->remaining_credits : 0}}" readonly class="form-control"/>
                        </div>
                    </div>
                    @if(!is_null($plan))
                    <div class="col-md-12 col-sm-12">
                        <div class="form-group">
                            <label for="">Active Plan</label>
                            <input type="text" name="plan" class="form-control" value="{{!empty($plan->name) ? $plan->name : ''}}" readonly/>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <br>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Update Timezone</h5>
            </div>
            {{ App\Helper\CommonHelper::convertTimeZone(auth()->user()->created_at, auth()->user()->timezone) }}
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <form action="{{ url('user/update/timezone') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label for="">Timezone</label>
                                <select name="timezone" class="form-control">
                                    @foreach($timezones as $timezone)
                                        <option value="{{ $timezone->timezone }}" {{ ($timezone->timezone == auth()->user()->timezone)? 'selected' : '' }}>{{ $timezone->timezone }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <button class="btn btn-primary">submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')

@endsection

