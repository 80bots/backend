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
                        <form action="{{ route('admin.bots.running') }}" method="get" id="filter-my-bot">
                            <select name="list" id="bot-filter-dropdown" class="form-control">
                                <option value="all" {{ isset($filters['list']) && $filters['list'] == 'all' ? 'selected' : '' }}>
                                    {{ __('keywords.bots.running.all') }}
                                </option>
                                <option value="my_bots" {{ isset($filters['list']) && $filters['list'] == 'my_bots'? 'selected' : '' }}>
                                    {{ __('keywords.bots.running.my_bots') }}
                                </option>
                            </select>
                        </form>
                    </div>
                </h5>
            </div>
            <div class="card-body">
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
                            <th>{{ __('keywords.status') }}</th>
                            <th>{{ __('keywords.bots.running.launched_at') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($userInstances as $userInstance)
                                <tr class="instance-{{ $userInstance->id }}">
                                    <td class="tag_user_email">
                                        @include('layouts.imports.loader')
                                        <span>{{ $userInstance->tag_user_email ??  '' }}</span>
                                    </td>
                                    <td class="tag_name">
                                        {{ $userInstance->tag_name ?? ''}}
                                    </td>
                                    <td class="instanceId">
                                        {{!empty($userInstance->aws_instance_id) ? $userInstance->aws_instance_id : ''}}
                                    </td>
                                    <td class="uptime">
                                        {{!empty($userInstance->up_time) ? $userInstance->up_time : 0}}
                                    </td>
                                    <td class="publicIp">
                                        {{!empty($userInstance->aws_public_ip) ? $userInstance->aws_public_ip : ''}}
                                    </td>
                                    <td class="statusSelect">
                                        @if($userInstance->is_in_queue == 1)
                                            <a href="javascript:void(0)" data-toggle="modal"
                                                data-target="#launch-instance"
                                                class="badge badge-primary ml-2 font-size-16"
                                                title="{{ __('keywords.bots.running.process_in_queue') }}">
                                                {{ __('keywords.bots.statuses.in_queue') }}
                                            </a>
                                        @else
                                            <select name="instStatus" class="form-control instStatus" data-id="{{$userInstance->id}}">
                                                @if(!empty($userInstance->status) && $userInstance->status == 'running')
                                                    <option value="running">
                                                        {{ __('keywords.bots.actions.start') }}
                                                    </option>
                                                    <option value="stop">
                                                        {{ __('keywords.bots.actions.stop') }}
                                                    </option>
                                                    <option value="terminated">
                                                        {{ __('keywords.bots.actions.terminate') }}
                                                    </option>
                                                @elseif(!empty($userInstance->status) && $userInstance->status == 'stop')
                                                    <option value="stop">
                                                        {{ __('keywords.bots.actions.stop') }}
                                                    </option>
                                                    <option value="start">
                                                        {{ __('keywords.bots.actions.start') }}
                                                    </option>
                                                    <option value="terminated">
                                                        {{ __('keywords.bots.actions.terminate') }}
                                                    </option>
                                                @else
                                                    <option value="terminated">
                                                        {{ __('keywords.bots.actions.terminate') }}
                                                    </option>
                                                @endif
                                            </select>
                                        @endif
                                    </td>
                                    <td>{{!empty($userInstance->created_at) ? \App\Helper\CommonHelper::convertTimeZone($userInstance->created_at, auth()->user()->timezone) : ''}}</td>
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
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script>
        const current_time_zone =  moment().format('Z');
        $('#user-time-zone').val(current_time_zone);
        let table;

        $(document).ready(function() {
            table = $('#instance-list').DataTable();
            $('#bot-filter-dropdown').on('change',function(){
                $('#filter-my-bot').submit();
            });
            checkBotIdInQueue();
        });

        function checkBotIdInQueue(){
          $.ajax({
              url : `{{ route('admin.bots.running.check') }}`,
              type : 'GET',
              success : function(response){
                  if(response.type === 'success'){

                       if(response.data !== undefined && response.data.length) {
                           response.data.forEach((val, i)=> {
                                $('.instance-' + val + ' .loader').removeClass('d-none').addClass('d-block')
                           });
                        let $botWrapper = $('#dvBotWrapper');
                       }
                  }
              },
              error : function(response){
                console.log(response);
                alert('Something went wrong!');
              }
          });
        }

        function dispatchLaunchInstance(instance_id){
            $.ajax({
                type: 'POST',
                url: `{{route('admin.bots.running.dispatch')}}`,
                cache: false,
                data: {
                    instance_id : instance_id
                },
                success: function (response) {
                    if(response.type === 'success'){
                        location.reload();
                    }
                }
            });
        }

        $(document).on('change', '.instStatus', function () {
            const status = $(this).val();
            const instanceId = $(this).data('id');
            $.ajax({
                type: 'PUT',
                url: `{{ route('admin.bots.running.update.status') }}`,
                cache: false,
                data: {
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
