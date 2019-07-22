@extends('layouts.app')

@section('title')
Running Bots
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Running Bots</h5>
                <h5 class="mb-0">
                    <div class="form-check flex">
                        <form action="{{ url('admin/instance/running') }}" method="get" id="filter-my-bot">
                            <select name="list" id="bot-filter-dropdown" class="form-control">
                                <option value="all" {{ isset($filters['list']) && $filters['list'] == 'all' ? 'selected' : '' }}>All</option>
                                <option value="my_bots" {{ isset($filters['list']) && $filters['list'] == 'my_bots'? 'selected' : '' }}>My Bots</option>
                            </select>
                        </form>
                        <a href="{{route('admin.instance.sync')}}" class="sync-instances">
                          <i class="fa fa-sync-alt" aria-hidden="true"></i>
                        </a>
                    </div>
                </h5>
            </div>
            <div class="card-body">
                <input type="hidden" name="instance_id" value="{{ Session::get('instance_id') }}" id="instance_id">
                @include('layouts.imports.messages')
                <div class="table-responsive">
                    <table id="instance-list" class="table thead-default vertical-middle mb-0">
                        <thead>
                        <tr>
                            <th>Launched By</th>
                            <th>Name</th>
                            <th>Instance Id</th>
                            <th>Up-Time</th>
                            <th>AWS Public Ip</th>
                            <th>Status</th>
                            <th>Launch Time</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($userInstances as $userInstance)
                                <tr>
                                    <td>{{ $userInstance->user ? $userInstance->user->email : '' }}</td>
                                    <td>{{!empty($userInstance->name) ? $userInstance->name : ''}}</td>
                                    <td>{{!empty($userInstance->aws_instance_id) ? $userInstance->aws_instance_id : ''}}</td>
                                    <td>{{!empty($userInstance->up_time) ? $userInstance->up_time : 0}}</td>
                                    <td>{{!empty($userInstance->aws_public_ip) ? $userInstance->aws_public_ip : ''}}</td>
                                    <td>
                                        @if($userInstance->is_in_queue == 1)
                                            <a href="javascript:void(0)" data-toggle="modal" data-target="#launch-instance"
                                            class="badge badge-primary ml-2 font-size-16" title="Process In Queue">IN-Queue</a>
                                        @else
                                            <select name="instStatus" class="form-control instStatus" data-id="{{$userInstance->id}}">
                                                @if(!empty($userInstance->status) && $userInstance->status == 'running')
                                                    <option value="running">Running</option>
                                                    <option value="stop">Stop</option>
                                                    <option value="terminated">Terminate</option>
                                                @elseif(!empty($userInstance->status) && $userInstance->status == 'stop')
                                                    <option value="stop">Stop</option>
                                                    <option value="start">Start</option>
                                                    <option value="terminated">Terminate</option>
                                                @else
                                                    <option value="terminated">Terminate</option>
                                                @endif
                                            </select>
                                        @endif
                                    </td>
                                    <td>{{!empty($userInstance->created_at) ? $userInstance->created_at : ''}}</td>
                                    <td><a href="{{!empty($userInstance->aws_pem_file_path) ? $userInstance->aws_pem_file_path : 'javascript:void(0)'}}" title="Download pem file" download>
                                            <i class="fa fa-download"></i>
                                        </a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>

        $(document).ready(function() {
            $('#instance-list').DataTable();

            $('#bot-filter-dropdown').on('change',function(){
                $('#filter-my-bot').submit();
            });
        });

        $(document).ready(function() {

            $('#instance-list').DataTable();
            let instance_id = $('#instance_id').val();

            if(instance_id.length != ''){
                dispatchLaunchInstance(instance_id);
            }
        });

        function  dispatchLaunchInstance(instance_id){
            var URL = '{{route('admin.dispatch.launch_instance')}}';
            $.ajax({
                type: 'POST',
                url: URL,
                cache: false,
                data: {
                    _token : function () {
                        return '{{csrf_token()}}';
                    },
                    instance_id : instance_id
                },
                success: function (response) {
                    if(response.type == "success"){
                        location.reload();
                    }

                }
            });
        }

        $(document).on('change', '.instStatus', function () {
            var status = $(this).val();
            var instanceId = $(this).data('id');
            var URL = '{{route('admin.user.instance.change-status')}}';
            $.ajax({
                type: 'post',
                url: URL,
                cache: false,
                data: {
                    _token : function () {
                        return '{{csrf_token()}}';
                    },
                    id : instanceId,
                    status: status
                },
                success: function (data) {
                    location.reload();
                }
            });
        })
    </script>
@endsection
