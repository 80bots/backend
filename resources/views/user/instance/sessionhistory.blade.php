@extends('layouts.app')

@section('title')
Instance Sessions Listing
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
        @if(!empty($sessions) && isset($sessions))
            <div class="my-3 p-3 bg-white rounded shadow-sm">
                <h6 class="border-bottom  pb-2 mb-0">Instance Schedules</h6>
                    <table id="scheduling_instances" class="table thead-default vertical-middle mb-0">
                        <thead>
                            <tr>
                                <th width="3%"></th>
                                <th width="15%">User</th>
                                <th width="29%">Instance Id</th>
                                <th width="15%">Type</th>
                                <th width="30%">Ran On</th>
                            </tr>
                        </thead>
                    </table>
                    @foreach($sessions as $session)
                        <div class="media text-muted pt-3 d-flex align-items-start">
                            <svg class="bd-placeholder-img mr-2 rounded flex-shrink-0" width="32" height="32"
                                 xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
                                 focusable="false" role="img" aria-label="Placeholder: 32x32"><title>
                                    Placeholder</title>
                                <rect width="100%" height="100%" fill="#007bff"></rect>
                                <text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text>
                            </svg>
                            <div class="row flex-grow-1 ml-0 mr-0 border-bottom pb-3">
                                <div class="col-md-2 col-sm-2">
                                    {{!empty($session->schedulingInstance->userInstances->user) ? $session->schedulingInstance->userInstances->user->name : ' -- '}}
                                </div>
                                <div class="col-md-4 col-sm-2">
                                    {{!empty($session->schedulingInstance->userInstances) ? $session->schedulingInstance->userInstances->aws_instance_id : ' -- '}}
                                </div>
                                <div class="col-md-2 col-sm-2">
                                    {{!empty($session->schedule_type) ? $session->schedule_type : ''}}
                                </div>
                                <div class="col-md-4 col-sm-2">
                                    {{!empty($session->created_at) ? date("jS F, Y H:i A", strtotime($session->created_at)) : ''}}
                                </div>
                            </div>
                        </div>
                @endforeach
            </div>

            {{ $sessions->links() }}
        @endif
    </div>
@endsection

@section('script')
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script>
        var current_time_zone =  moment().format('Z');
        $('#user-time-zone').val(current_time_zone);

        $(document).ready(function() {
            $('#instance-list').DataTable();
        });
    </script>
@endsection
