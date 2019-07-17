@extends('layouts.app')

@section('title')
    Bots Listing
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Bots List</h5>
                <h5 class="mb-0"><a href="{{route('admin.bots.create')}}" class="btn btn-round btn-primary"><i class="fas fa-plus-circle"></i></a></h5>
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="table-responsive">
                    <table id="bot-list" class="table thead-default vertical-middle mb-0">
                        <thead>
                        <tr>
                            <th>Bot Name</th>
                            <th>AMI Image Id</th>
                            <th>AMI Name</th>
                            <th>Instance Type</th>
                            <th>Storage GB</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(isset($botLists) && !empty($botLists))
                            @foreach($botLists as $bots)
                                <tr>
                                    <td>{{!empty($bots->bot_name) ? $bots->bot_name : ''}}</td>
                                    <td>{{!empty($bots->aws_ami_image_id ) ? $bots->aws_ami_image_id : ''}}</td>
                                    <td>{{!empty($bots->aws_ami_name) ? $bots->aws_ami_name : ''}}</td>
                                    <td>{{!empty($bots->aws_instance_type) ? $bots->aws_instance_type : ''}}</td>
                                    <td>{{!empty($bots->aws_storage_gb) ? $bots->aws_storage_gb : ''}}</td>
                                    <td>
                                        @if(!empty($bots->status) && $bots->status == 'active')
                                            <button type="button" class="form-group btn btn-success mb-0"
                                                    onclick="ChangeStatus('{{$bots->id}}','inactive')"
                                                    title="make it inactive">Active
                                            </button>
                                        @else
                                            <button type="button" class="form-group btn btn-danger mb-0"
                                                    onclick="ChangeStatus('{{$bots->id}}','active')"
                                                    title="make it active">Inactive
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.bots.destroy',$bots->id) }}" method="POST">
                                            <div class="d-flex align-items-center">
                                                <a href="{{route('admin.bots.show',$bots->id)}}"
                                                   class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                                   title="Edit Bot"><i class="fa fa-eye"></i></a>

                                                <a href="{{route('admin.bots.edit',$bots->id)}}"
                                                   class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                                   title="Edit Bot"><i class="fa fa-edit"></i></a>

                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Are you sure?')" class="form-group btn btn-icon btn-danger change-credit-model mb-0"><i class="fa fa-trash"></i></button>
                                            </div>
                                        </form>
                                    </td>
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
        $(document).ready(function () {
            $('#bot-list').DataTable();
        });
        function ChangeStatus(id, status) {
            var URL = '{{route('admin.bots.change-status')}}';
            $.ajax({
                type: 'post',
                url: URL,
                cache: false,
                data: {
                    _token: function () {
                        return '{{csrf_token()}}';
                    },
                    id: id,
                    status: status
                },
                success: function (data) {
                    location.reload();
                }
            });
        }
    </script>
@endsection
