@extends('layouts.app')

@section('title')
Instance Listing
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
        @if(!empty($UserInstance) && isset($UserInstance))
            <div class="my-3 p-3 bg-white rounded shadow-sm">
                <h6 class="border-bottom  pb-2 mb-0">Running Bots</h6>
                    @foreach($UserInstance as $instance)
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
                                    <strong class="d-block text-gray-dark">{{isset($instance->bots->bot_name)?$instance->bots->bot_name:''}}</strong>
                                </div>
                                <div class="col-md-2 col-sm-2">
                                    {{!empty($instance->up_time) ? $instance->up_time : 0}}
                                </div>
                                <div class="col-md-2 col-sm-2">
                                    {{!empty($instance->aws_public_ip) ? $instance->aws_public_ip : ''}}
                                </div>
                                <div class="col-md-2 col-sm-2">
                                    <select name="instStatus" class="form-control instStatus" data-id="{{$instance->id}}">
                                        @if(!empty($instance->status) && $instance->status == 'running')
                                            <option value="running">Running</option>
                                            <option value="stop">Stop</option>
                                            <option value="terminated">Terminated</option>
                                        @elseif(!empty($instance->status) && $instance->status == 'stop')
                                            <option value="stop">Stop</option>
                                            <option value="start">Start</option>
                                            <option value="terminated">Terminated</option>
                                        @else
                                            <option value="terminated">Terminated</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-2 col-sm-2">
                                    {{!empty($instance->created_at) ? date('Y-m-d', strtotime($instance->created_at)) : ''}}
                                </div>
                                <div class="col-md-1 col-sm-1">
                                    <a href="{{!empty($instance->aws_public_ip) ? 'http://'.$instance->aws_public_ip : ''}}" target="_blank"><i class="fa fa-eye"></i></a>
                                </div>
                                <div class="col-md-1 col-sm-1">
                                    @php $bot_name=isset($instance->bots->bot_name)?$instance->bots->bot_name:''@endphp
                                    <a href="javascript:void(0)" data-toggle="modal" data-target="#create-scheduler"
                                       onclick="SetBotName('{{$bot_name}}','{{$instance->id}}')" class="badge badge-primary font-size-16"><i class="fa fa-pen"></i></a>
                                </div>
                            </div>
                        </div>
                @endforeach
            </div>
        @endif
    </div>

    @include('user.scheduling.include-schedule-popup')
@endsection

@section('script')
    <script>

        $(document).ready(function() {
            $('#instance-list').DataTable();
        });

        $(document).on('change', '.instStatus', function () {
            var status = $(this).val();
            var instanceId = $(this).data('id');
            var URL = '{{route('user.instance.change-status')}}';
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
