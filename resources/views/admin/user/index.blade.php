@extends('layouts.app')

@section('title')
    {{ __('admin.users.title') . ' ' . __('keywords.list') }}
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">{{ __('admin.users.title') . ' ' . __('keywords.list') }}</h5>
                {{--<a href="{{route('user.instance.create')}}" class="btn btn-round btn-primary"><i class="fas fa-plus"></i> Add Instance</a>--}}
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="table-responsive">
                    <table id="user-list" class="table thead-default vertical-middle mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('admin.users.name') }}</th>
                            <th>{{ __('admin.users.email') }}</th>
                            <th>{{ __('admin.users.credits') }}</th>
                            <th>{{ __('admin.users.register_date') }}</th>
                            <th>{{ __('admin.users.status') }}</th>
                            <th>{{ __('admin.users.action') }}</th>
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
                                                    title="make it inactive">{{ __('keywords.user.statuses.active') }}
                                            </button>
                                        @else
                                            <button type="button" class="form-group btn btn-danger mb-0"
                                                    onclick="ChangeStatus('{{$user->id}}','active')"
                                                    title="make it active">{{ __('keywords.user.statuses.inactive') }}
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <button class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"  data-val="{{$user->remaining_credits}}"
                                                    value="{{$user->id}}" title="update credits"><i
                                                        class="fa fa-edit"></i></button>
                                            <a href="{{ url('admin/bots/running/all/' . $user->id) }}"
                                               class="form-group btn btn-icon btn-secondary mb-0"
                                               title="{{ __('admin.users.list_all') }}"><i class="fa fa-eye"></i></a>
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
                        <h4 class="modal-title">{{ __('admin.users.credits') }}</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="input-group">
                            <input type="hidden" name="id" id="user_id" value="">
                            <input type="text" id="credit-score" minlength="1" on  name="remaining_credits"
                                   class="form-control" required
                            >
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="submit" class="btn btn-success" value="submit">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            {{ __('keywords.close') }}
                        </button>
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
            // Check on input value is 0 to 9 and , alow
            $('#credit-score').on('input', function (event) {
                    this.value = this.value.replace(/[^0-9\.]/g,'', '');
            });
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
            // Get user id for click button
            var userId = $(this).val();
            // Get amount from user list
            var amount = $(this).attr('data-val');
            $('#credit-score').val(amount);
            $('#update-credit-form').attr('action', '{{route("admin.user.update-credit")}}');
            $('#user_id').val(userId);
            $('#updateCredit').modal('show');
        });
    </script>
@endsection
