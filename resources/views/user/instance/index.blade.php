@extends('layouts.app')

@section('title')
Instance Listing
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Instance List</h5>
                <button data-toggle="modal" data-target="#lunch-instance" class="btn btn-round btn-primary"><i class="fas fa-plus"></i> Add Instance</button>
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="table-responsive">
                    <table id="instance-list" class="table thead-default vertical-middle mb-0">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Instance Id</th>
                            <th>Up-Time</th>
                            <th>AWS Public Ip</th>
                            <th>AWS Public DNS</th>
                            <th>Status</th>
                            <th>Launch Time</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(isset($UserInstance) && !empty($UserInstance))
                            @foreach($UserInstance as $instance)
                                <tr>
                                    <td>{{!empty($instance->name) ? $instance->name : ''}}</td>
                                    <td>{{!empty($instance->aws_instance_id) ? $instance->aws_instance_id : ''}}</td>
                                    <td>{{!empty($instance->up_time) ? $instance->up_time : 0}}</td>
                                    <td>{{!empty($instance->aws_public_ip) ? $instance->aws_public_ip : ''}}</td>
                                    <td>{{!empty($instance->aws_public_dns) ? $instance->aws_public_dns : ''}}</td>
                                    <td>
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
                                    </td>
                                    <td>{{!empty($instance->created_at) ? date('Y-m-d', strtotime($instance->created_at)) : ''}}</td>
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
    <div class="modal fade" id="lunch-instance" role="dialog">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form id="lunchInstance" action="{{route('user.instance.store')}}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h4 class="modal-title">Lunch Instance</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Bots</label>
                            <select class="form-control" name="bot_id">
                                <option value="">Select Bot</option>
                                @if(isset($botsArr) && !empty($botsArr))
                                    @foreach($botsArr as $bots)
                                        <option value="{{$bots->id}}">{{isset($bots->bot_name) ? $bots->bot_name : $bots->aws_ami_name}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="submit" class="btn btn-success" value="submit">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
