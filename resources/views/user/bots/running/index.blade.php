@extends('layouts.app')

@section('title')
    {{ __('user.bots.running.title') }}
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        @include('includes.banner')
        <input type="hidden" name="instance_id" value="{{ Session::get('instance_id') }}" id="instance_id">
        @include('layouts.imports.messages')
        @if(!empty($UserInstance) && isset($UserInstance))
            <div class="my-3 p-3 bg-white rounded shadow-sm">
                <h6 class="border-bottom  pb-2 mb-0">{{ __('user.bots.running.title') }}</h6>
                @foreach($UserInstance as $instance)
                    <div class="media text-muted pt-3 d-flex align-items-start instance-{{ $instance->id }}">
                        <svg class="bd-placeholder-img mr-2 rounded flex-shrink-0" width="32" height="32"
                             xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
                             focusable="false" role="img" aria-label="Placeholder: 32x32">
                            <title>{{ __('keywords.placeholder') }}</title>
                            <rect width="100%" height="100%" fill="#007bff"></rect>
                            <text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text>
                        </svg>
                        <div class="row flex-grow-1 ml-0 mr-0 border-bottom pb-3">
                            <div class="col-md-2 col-sm-2">
                                @include('layouts.imports.loader')
                                <strong data-toggle="tooltip" class="tag_name d-block text-gray-dark"
                                    title="{{isset($instance->aws_instance_id)?$instance->aws_instance_id:''}}">
                                    {{isset($instance->tag_name) ? $instance->tag_name : ''}}
                                </strong>
                            </div>
                            <div class="uptime col-md-2 col-sm-2">
                                {{!empty($instance->up_time) ? $instance->up_time : 0}}
                            </div>
                            <div class="publicIp col-md-2 col-sm-2">
                                {{!empty($instance->aws_public_ip) ? $instance->aws_public_ip : ''}}
                            </div>
                            <div class="statusSelect col-md-2 col-sm-2">
                                @if($instance->is_in_queue == 1)
                                    <a href="javascript:void(0)" data-toggle="modal" data-target="#launch-instance"
                                       class="badge badge-primary ml-2 font-size-16"
                                       title="{{ __('keywords.bots.running.process_in_queue') }}">
                                        {{ __('keywords.bots.statuses.in_queue') }}
                                    </a>
                                @else
                                    <select name="instStatus" class="form-control instStatus" data-id="{{$instance->id}}">
                                        @if(!empty($instance->status) && $instance->status == 'running')
                                            <option value="running">
                                                {{ __('keywords.bots.statuses.running') }}
                                            </option>
                                            <option value="stop">
                                                {{ __('keywords.bots.statuses.stopped') }}
                                            </option>
                                            <option value="terminated">
                                                {{ __('keywords.bots.statuses.terminated') }}
                                            </option>
                                        @elseif(!empty($instance->status) && $instance->status == 'stop')
                                            <option value="stop">
                                                {{ __('keywords.bots.statuses.stopped') }}
                                            </option>
                                            <option value="start">
                                                {{ __('keywords.bots.statuses.starting') }}
                                            </option>
                                            <option value="terminated">
                                                {{ __('keywords.bots.statuses.terminated') }}
                                            </option>
                                        @else
                                            <option value="terminated">
                                                {{ __('keywords.bots.statuses.terminated') }}
                                            </option>
                                        @endif
                                    </select>
                                @endif
                            </div>
                            <div class="col-md-2 col-sm-2">
                                {{ !empty($instance->created_at) ? \App\Helpers\CommonHelper::convertTimeZone($instance->created_at, auth()->user()->timezone) : ''}}
                            </div>
                            <div class="col-md-2 col-sm-2 d-flex align-items-center">
                                <a href="{{!empty($instance->aws_public_ip) ? 'http://'.$instance->aws_public_ip : ''}}"
                                   class="badge badge-primary mr-2 font-size-16" target="_blank">
                                    <i class="fa fa-eye"></i>
                                </a>
                                @php $bot_name=isset($instance->bots->bot_name)?$instance->bots->bot_name:''@endphp
                                <a href="javascript:void(0)" data-toggle="modal" data-target="#create-scheduler"
                                   onclick="SetBotName('{{$bot_name}}','{{$instance->id}}')"
                                   class="badge badge-primary font-size-16"><i class="fa fa-pencil-alt"></i>
                                </a>
                                @if($instance->is_in_queue == 1)
                                    <a href="javascript:void(0)" data-toggle="modal" data-target="#launch-instance"
                                       class="badge badge-primary ml-2 font-size-16 refresh"
                                       title="{{ __('keywords.bots.running.process_in_queue') }}">
                                        <i class="fa fa-sync-alt"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @include('user.scheduling.create-schedule-popup')
@endsection

@section('script')
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script>
        let current_time_zone =  moment().format('Z');
        $('#user-time-zone').val(current_time_zone);

        $(document).ready(function(){
            $('#instance-list').DataTable();
            checkBotIdInQueue();
        });

        function checkBotIdInQueue(){
            $.ajax({
                url : `{{ route('bots.running.check') }}`,
                type : 'GET',
                success : function(response){
                    if(response.type === 'success'){
                        console.log(response);
                        if(response.data !== undefined && response.data.length) {
                           response.data.forEach((val, i)=> {
                                $('.instance-' + val + ' .loader').removeClass('d-none').addClass('d-block')
                           })
                        }
                    }
                },
                error : function(response){
                    console.log(response);
                    alert('Something went wrong!');
                }
            });
        }

        $(document).on('change', '.instStatus', function (e) {
            let status = $(this).val();
            let instanceId = $(this).data('id');
            $.ajax({
                type: 'PUT',
                url: `{{route('bots.running.update.status')}}`,
                cache: false,
                data: {
                    id : instanceId,
                    status: status
                },
                success: function (data) {
                    location.reload();
                }
            });
        });

        function  dispatchLaunchInstance(instance_id) {
            $.ajax({
                type: 'POST',
                url: `{{route('bots.running.dispatch')}}`,
                cache: false,
                data: {
                    instance_id : instance_id
                },
                success: function (response) {
                    if(response.type === "success"){
                        location.reload();
                    }
                }
            });
        }

        $(document).on('click', '.refresh', function () {
            location.reload();
        })
    </script>
    @include('user.scheduling.scheduler-scripts')
@endsection
