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
                    <div class="form-check">
                        <form action="{{ route('admin.instance.running') }}" method="get" id="filter-my-bot">
                            <select name="bots_filter" id="bot-filter-dropdown" class="form-control">
                                <option value="all" {{ $filter == 'all'? 'selected' : '' }}>All</option>
                                <option value="mybots" {{ $filter == 'mybots'? 'selected' : '' }}>My Bots</option>
                            </select>
                        </form>
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
                        @if(isset($UserInstance) && !empty($UserInstance))
                            @foreach($UserInstance as $instance)
                                <tr>
                                    <td>{{ $instance->user->email }}</td>
                                    <td>{{!empty($instance->name) ? $instance->name : ''}}</td>
                                    <td>{{!empty($instance->aws_instance_id) ? $instance->aws_instance_id : ''}}</td>
                                    <td>{{!empty($instance->up_time) ? $instance->up_time : 0}}</td>
                                    <td>{{!empty($instance->aws_public_ip) ? $instance->aws_public_ip : ''}}</td>
                                    <td>
                                        @if($instance->is_in_queue == 1)
                                            <a href="javascript:void(0)" data-toggle="modal" data-target="#launch-instance"
                                            class="badge badge-primary ml-2 font-size-16" title="Process In Queue">IN-Queue</a>
                                        @else
                                            <select name="instStatus" class="form-control instStatus" data-id="{{$instance->id}}">
                                                @if(!empty($instance->status) && $instance->status == 'running')
                                                    <option value="running">Running</option>
                                                    <option value="stop">Stop</option>
                                                    <option value="terminated">Terminate</option>
                                                @elseif(!empty($instance->status) && $instance->status == 'stop')
                                                    <option value="stop">Stop</option>
                                                    <option value="start">Start</option>
                                                    <option value="terminated">Terminate</option>
                                                @else
                                                    <option value="terminated">Terminate</option>
                                                @endif
                                            </select>
                                        @endif
                                    </td>
                                    <td>{{!empty($instance->created_at) ? $instance->created_at : ''}}</td>
                                    <td><a href="{{!empty($instance->aws_pem_file_path) ? $instance->aws_pem_file_path : 'javascript:void(0)'}}" title="Download pem file" download>
                                            <i class="fa fa-download"></i>
                                        </a></td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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

            $('#bot-filter-dropdown').on('change',function(){
                $('#filter-my-bot').submit();
            });
        });

        $(document).ready(function(){
            checkBotIdInQueue();
        });

        function checkBotIdInQueue(){
          $.ajax({
              url : "/admin/checkBotIdInQueue",
              type : "POST",
              data : {
                  _token : function () {
                      return '{{csrf_token()}}';
                  }
              },
              success : function(response){
                  if(response.type === 'success'){
                       console.log(response);
                      // if(response.data !== undefined && response.data.length) {
                      //     let $botWrapper = $('#dvBotWrapper');
                      //     for(let eachData of response.data) {
                      //         $botWrapper.find('[data-id="'+eachData+'"]').attr('data-target','').prepend('<i class="fa fa-spinner fa-spin"></i>');
                      //     }
                      // }
                  }
              },
              error : function(response){
                console.log(response);
                alert('Something went wrong!');
              }
          });
        }

        $(document).ready(function() {

            $('#instance-list').DataTable();
            let instance_id = $('#instance_id').val();

            if(instance_id.length != ''){
                //dispatchLaunchInstance(instance_id);
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
