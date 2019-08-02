@extends('layouts.app')

@section('title')
    {{ __('keywords.bots.running.my_bots') }}
@endsection

@section('css')
@endsection

@section('content')
    <div class="wrapper">
        @include('includes.banner')
        <input type="hidden" name="instance_id" value="{{ Session::get('instance_id') }}" id="instance_id">
        @include('layouts.imports.messages')
        <div class="my-3 p-3 bg-white rounded shadow-sm">
            <h6 class="border-bottom  pb-2 mb-0">{{ __('keywords.bots.running.title') }}</h6>
            @foreach($userInstances as $instance)
                <div class="media text-muted pt-3 d-flex align-items-start">
                    <svg class="bd-placeholder-img mr-2 rounded flex-shrink-0" width="32" height="32"
                         xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
                         focusable="false" role="img" aria-label="Placeholder: 32x32"><title>
                            {{ __('keywords.placeholder') }}</title>
                        <rect width="100%" height="100%" fill="#007bff"></rect>
                        <text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text>
                    </svg>
                    <div class="row flex-grow-1 ml-0 mr-0 border-bottom pb-3">
                        <div class="col-md-2 col-sm-2">
                            <strong class="d-block text-gray-dark">
                                {{ $instance->bots ? $instance->bots->bot_name : '' }}
                            </strong>
                        </div>
                        <div class="col-md-2 col-sm-2">
                            {{ $instance->up_time ??  0 }}
                        </div>
                        <div class="col-md-2 col-sm-2">
                            {{ $instance->aws_public_ip ??  '' }}
                        </div>
                        <div class="col-md-2 col-sm-2">
                            @if($instance->is_in_queue == 1)
                                <a href="javascript:void(0)" data-toggle="modal" data-target="#launch-instance"
                                   class="badge badge-primary ml-2 font-size-16"
                                   title="{{ __('keywords.bots.running.process_in_queue') }}">
                                    {{ __('keywords.bots.statuses.in_queue') }}
                                </a>
                            @else
                                <select name="instStatus" class="form-control instStatus" data-id="{{$instance->id}}">
                                    @if($instance->status)
                                      @if($instance->status == 'running')
                                          <option value="running" selected>
                                              {{ __('keywords.bots.statuses.running') }}
                                          </option>
                                      @elseif($instance->status == 'stop')
                                          <option value="start" selected>
                                              {{ __('keywords.bots.actions.start') }}
                                          </option>
                                      @endif
                                    @endif
                                    <option value="stop" {{$instance->status && $instance->status == 'stop' ? 'selected' : ''}}>
                                        {{ __('keywords.bots.actions.stop') }}
                                    </option>
                                    <option value="terminated" {{$instance->status && $instance->status == 'terminated' ? 'selected' : ''}}>
                                        {{ __('keywords.bots.actions.terminate') }}
                                    </option>
                                </select>
                            @endif
                        </div>
                        <div class="col-md-2 col-sm-2">
                            {{ $instance->created_at ? date('Y-m-d', strtotime($instance->created_at)) : ''}}
                        </div>
                        <div class="col-md-2 col-sm-2 d-flex align-items-center">
                            <a href="{{ $instance->aws_public_ip ? 'http://'.$instance->aws_public_ip : '' }}" class="badge badge-primary mr-2 font-size-16" target="_blank"><i class="fa fa-eye"></i></a>
                            @php $botName = $instance->bots ? $instance->bots->bot_name :''; @endphp
                            <a href="javascript:void(0)" data-toggle="modal" data-target="#create-scheduler"
                               onclick="SetBotName('{{$botName}}','{{$instance->id}}')" class="badge badge-primary font-size-16"><i class="fa fa-pencil-alt"></i></a>
                            @if($instance->is_in_queue == 1)
                                <a href="javascript:void(0)" data-toggle="modal" data-target="#launch-instance"
                                   class="badge badge-primary ml-2 font-size-16 refresh" title="Process In Queue"><i class="fa fa-sync-alt"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @include('admin.scheduling.include-schedule-popup')
@endsection

@section('script')
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script>
        const current_time_zone =  moment().format('Z');
        $('#user-time-zone').val(current_time_zone);

        $(document).ready(function() {
            $('#instance-list').DataTable();
            let instance_id = $('#instance_id').val();
            if(instance_id.length > 0){
                dispatchLaunchInstance(instance_id);
            }
        });

        $(document).on('change', '.instStatus', function (e) {
            const status = $(this).val();
            const instanceId = $(this).data('id');
            $.ajax({
                type: 'PUT',
                url: `{{route('admin.bots.running.update.status')}}`,
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
                url: `{{route('admin.bots.running.dispatch')}}`,
                cache: false,
                data: {
                    instance_id : instance_id
                },
                success: function (response, textStatus, xhr) {
                    if(xhr.status === 200){
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