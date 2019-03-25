@extends('layouts.app')

@section('title')
Instance Listing
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            @include('layouts.imports.messages')
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Instance List</h5>
            </div>
            <div class="card-body p-0">
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
