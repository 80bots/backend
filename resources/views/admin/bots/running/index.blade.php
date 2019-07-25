@extends('layouts.app')

@section('title')
    {{ __('keywords.bots.running.title') }}
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">{{ __('keywords.bots.running.title') }}</h5>
                <h5 class="mb-0">
                    <div class="form-check flex">
                        <form action="{{ url('admin/instance/running') }}" method="get" id="filter-my-bot">
                            <select name="list" id="bot-filter-dropdown" class="form-control">
                                <option value="all"
                                    {{ isset($filters['list']) && $filters['list'] == 'all' ? 'selected' : '' }}
                                >
                                    {{ __('keywords.bots.running.all') }}
                                </option>
                                <option value="my_bots"
                                    {{ isset($filters['list']) && $filters['list'] == 'my_bots'? 'selected' : '' }}
                                >
                                    {{ __('keywords.bots.running.my_bots') }}
                                </option>
                            </select>
                        </form>
                        <a href="javascript::void(0);" data-href="{{route('admin.instance.sync')}}" class="sync-instances">
                          <i class="fa fa-sync-alt" aria-hidden="true"></i>
                        </a>
                    </div>
                </h5>
            </div>
            <div class="card-body">
                <div class="hidden" id="sync-loader">
                    <img src="/assets/images/loader.gif">
                </div>
                <input type="hidden" name="instance_id" value="{{ Session::get('instance_id') }}" id="instance_id">
                @include('layouts.imports.messages')
                <div class="table-responsive" id="instance-div">
                    <table id="instance-list" class="table thead-default vertical-middle mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('keywords.bots.running.launched_by') }}</th>
                            <th>{{ __('keywords.bots.running.name') }}</th>
                            <th>{{ __('keywords.bots.running.instance_id') }}</th>
                            <th>{{ __('keywords.bots.running.uptime') }}</th>
                            <th>{{ __('keywords.bots.running.ip') }}</th>
                            <th>{{ __('keywords.bots.running.status') }}</th>
                            <th>{{ __('keywords.bots.running.launched_at') }}</th>
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
                                            class="badge badge-primary ml-2 font-size-16"
                                               title="{{ __('keywords.bots.running.process_in_queue') }}"
                                            >
                                                {{ __('keywords.bots.statuses.in_queue') }}
                                            </a>
                                        @else
                                            <select name="instStatus" class="form-control instStatus" data-id="{{$userInstance->id}}">
                                                @if(!empty($userInstance->status) && $userInstance->status == 'running')
                                                    <option value="running">{{ __('keywords.bots.statuses.running') }}</option>
                                                    <option value="stop">{{ __('keywords.stop') }}</option>
                                                    <option value="terminated">{{ __('keywords.bots.running.terminate') }}</option>
                                                @elseif(!empty($userInstance->status) && $userInstance->status == 'stop')
                                                    <option value="stop">{{ __('keywords.stop') }}</option>
                                                    <option value="start">{{ __('keywords.start') }}</option>
                                                    <option value="terminated">{{ __('keywords.bots.running.terminate') }}</option>
                                                @else
                                                    <option value="terminated">{{ __('keywords.bots.running.terminate') }}</option>
                                                @endif
                                            </select>
                                        @endif
                                    </td>
                                    <td>{{!empty($userInstance->created_at) ? $userInstance->created_at : ''}}</td>
                                    <td>
                                        <a href="{{!empty($userInstance->aws_pem_file_path) ? $userInstance->aws_pem_file_path : 'javascript:void(0)'}}"
                                           title="{{ __('keywords.bots.running.download_pem') }}" download>
                                            <i class="fa fa-download"></i>
                                        </a>
                                    </td>
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
