@extends('layouts.app')

@section('title')
Plans Listing
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="align-items-center bg-purple d-flex p-3 rounded shadow-sm text-white-50 mb-3">
            <h4 class="border mb-0 mr-2 pb-2 pl-3 pr-3 pt-2 rounded text-white">8</h4>
            <div class="lh-100">
                <h6 class="mb-0 text-white lh-100">80bots</h6>
                <small>Since 2019</small>
            </div>
        </div>
        @include('layouts.imports.messages')
        <div class="my-3 p-3 bg-white rounded shadow-sm">
            <div class="table-responsive">
                <table id="pricing-plans" class="table thead-default vertical-middle mb-0">
                    <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Credits</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(isset($plans) && !empty($plans))
                        @foreach($plans as $plan)
                            <tr>
                                <td> {{!empty($plan->name) ? $plan->name : ''}} </td>
                                <td> {{!empty($plan->price) ? $plan->price : ''}} </td>
                                <td> {{!empty($plan->credit) ? $plan->credit : ''}} </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('user.scheduling.include-schedule-popup')
@endsection

@section('script')
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script>
        var current_time_zone =  moment().format('Z');
        $('#user-time-zone').val(current_time_zone);

        $(document).ready(function() {
            alert("ds");
            $('#pricing-plans').DataTable();
        });
    </script>
@endsection
