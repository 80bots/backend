@extends('layouts.app')

@section('title')
    User Listing
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">User List</h5>
                {{--<a href="{{route('user.instance.create')}}" class="btn btn-round btn-primary"><i class="fas fa-plus"></i> Add Instance</a>--}}
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="table-responsive">
                    <table id="user-list" class="table thead-default vertical-middle mb-0">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Credits</th>
                            <th>Register Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(isset($userListObj) && !empty($userListObj))
                            @foreach($userListObj as $user)
                                <tr>
                                    <td>{{!empty($user->name) ? $user->name : ''}}</td>
                                    <td>{{!empty($user->email) ? $user->email : ''}}</td>
                                    <td>{{!empty($user->remaining_credits) ? $user->remaining_credits : 0.0}}</td>
                                    <td>{{!empty($user->created_at) ? date('Y-m-d', strtotime($user->created_at)) : ''}}</td>
                                    <td>
                                        @if(!empty($user->status) && $user->status == 'active')
                                            <button type="button" class="form-group btn btn-success mb-0"
                                                    onclick="ChangeStatus('{{$user->id}}','inactive')"
                                                    title="make it inactive">Active
                                            </button>
                                        @else
                                            <button type="button" class="form-group btn btn-danger mb-0"
                                                    onclick="ChangeStatus('{{$user->id}}','active')"
                                                    title="make it active">Inactive
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <button class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                                    value="{{$user->id}}" title="update credits"><i
                                                        class="fa fa-edit"></i></button>
                                            <a href="{{route('admin.user.instance.list',['id' => $user->id])}}"
                                               class="form-group btn btn-icon btn-secondary mb-0"
                                               title="List Of All Instances"><i class="fa fa-eye"></i></a>
                                        </div>
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

    <div class="modal fade" id="updateCredit" role="dialog">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form id="update-credit-form" action="" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="modal-header">
                        <h4 class="modal-title">Credits</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="input-group">
                            <input type="hidden" name="id" id="user_id" value="">
                            <input type="text" id="credit-score" name="remaining_credits" class="form-control" required>
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

        $(document).ready(function () {
            $('#user-list').DataTable();
        });

        function ChangeStatus(userId, status) {
            var URL = '{{route('admin.user.change-status')}}';
            $.ajax({
                type: 'post',
                url: URL,
                cache: false,
                data: {
                    _token: function () {
                        return '{{csrf_token()}}';
                    },
                    id: userId,
                    status: status
                },
                success: function (data) {
                    location.reload();
                }
            });
        }

        $(document).on('click', '.change-credit-model', function () {
            var userId = $(this).val();
            $('#update-credit-form').attr('action', '{{route("admin.user.update-credit")}}');
            $('#user_id').val(userId);
            $('#updateCredit').modal('show');
        });
    </script>
@endsection
