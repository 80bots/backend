@extends('layouts.app')

@section('title')
    {{ __('user.bots.sessions.title') }}
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        @include('includes.banner')
        @if(isset($sessions) && !empty($sessions))
            <div class="my-3 p-3 bg-white rounded shadow-sm">
                <h6 class="border-bottom pb-6">{{ __('user.bots.sessions.title') }}</h6>
                <table id="instances_sessions" class="table thead-default vertical-middle mb-0">
                    <thead>
                        <tr>
                            <th width="3%"></th>
                            @if($admin)
                            <th width="15%">{{ __('user.bots.sessions.user') }}</th>
                            @endif
                            <th width="29%">{{ __('user.bots.sessions.instance_id') }}</th>
                            <th width="15%">{{ __('user.bots.sessions.type') }}</th>
                            <th width="30%">{{ __('user.bots.sessions.date_time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $session) 
                            <tr role="row" class="odd">
                                <td>
                                    <svg class="bd-placeholder-img mr-2 rounded flex-shrink-0" width="32" height="32"
                                        xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
                                        focusable="false" role="img" aria-label="Placeholder: 32x32"><title>
                                            {{ __('keywords.placeholder') }}</title>
                                        <rect width="100%" height="100%" fill="#007bff"></rect>
                                        <text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text>
                                    </svg>
                                </td>
                                @if($admin)
                                <td>{{!empty($session->schedulingInstance->userInstances->user) ? $session->schedulingInstance->userInstances->user->name : ' -- '}}
                                </td>
                                @endif
                                <td>{{!empty($session->schedulingInstance->userInstances) ? $session->schedulingInstance->userInstances->aws_instance_id : ''}}</td>
                                <td>{{!empty($session->schedule_type) ? $session->schedule_type : ''}}</td>
                                @php
                                    $currentDate = App\Helpers\CommonHelper::convertTimeZone($session->created_at, auth()->user()->timezone, 'jS F, Y h:i A');
                                @endphp
                                <td>{{!empty($currentDate) ? $currentDate : ''}}</td>
                            </tr>                                
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $sessions->links() }}
        @endif
    </div>
@endsection

@section('script')
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script>
        const current_time_zone = moment().format('Z');
        $('#user-time-zone').val(current_time_zone);

        $(document).ready(function() {
            $('#instances_sessions').DataTable();
        });
    </script>
@endsection
