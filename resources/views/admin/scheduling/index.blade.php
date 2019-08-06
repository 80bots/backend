@extends('layouts.app')

@section('title')
    Scheduling instances
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">{{ __('admin.scheduling.title') }}</h5>
                <h5 class="mb-0">
                    <div class="form-check flex">
                        <form action="{{ route('admin.scheduling.index') }}" method="get" id="filter-my-scheduling">
                            <select name="list" id="scheduling-filter-dropdown" class="form-control">
                                <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>
                                    {{ __('admin.scheduling.all_scheduling') }}
                                </option>
                                <option value="my" {{ $filter == 'my'? 'selected' : '' }}>
                                    {{ __('admin.scheduling.my_scheduling') }}
                                </option>
                            </select>
                        </form>
                    </div>
                </h5>
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="table-responsive">
                    <table id="scheduling-instances" class="table thead-default vertical-middle mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('admin.scheduling.user') }}</th>
                            <th>{{ __('admin.scheduling.instance_id') }}</th>
                            <th>{{ __('admin.scheduling.bot_name') }}</th>
                            <th>{{ __('admin.scheduling.status') }}</th>
                            <th>{{ __('admin.scheduling.details') }}</th>
                            <th>{{ __('admin.scheduling.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                            @forelse($results as $row)
                                <tr>
                                    <td>{{ $row->user->email }}</td>
                                    <td>{{ $row->userInstance->aws_instance_id }}</td>
                                    <td>{{ $row->userInstance->bots->bot_name }}</td>
                                    <td>
                                        <select name="status" class="form-control schedulingStatus" data-id="{{ $row->id }}">
                                            @if(!empty($row->status) && $row->status == 'active')
                                                <option selected="selected" value="active">{{ __('admin.active') }}</option>
                                                <option value="inactive">{{ __('admin.inactive') }}</option>
                                            @else
                                                <option selected="selected" value="inactive">{{ __('admin.inactive') }}</option>
                                                <option value="active">{{ __('admin.active') }}</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <ul>
                                        @forelse($row->details as $details)
                                            <li>{{ ucfirst($details->schedule_type) }} ({{ $details->cron_data }})</li>
                                        @empty
                                            <li>{{ __('admin.not_found') }}</li>
                                        @endforelse
                                        </ul>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0)" data-toggle="modal" data-target="#create-scheduler"
                                               onclick="SetBotName('{{ $row->userInstance->bots->bot_name }}','{{$row->userInstance->id}}')"
                                               class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                               title="Edit Bot"><i class="fa fa-edit"></i></a>

                                            <form action="{{ route('admin.scheduling.destroy', $row->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        onclick="return confirm('Are you sure? you want to remove this record')"
                                                        class="form-group btn btn-icon btn-danger change-credit-model mb-0">
                                                    <i class="fa fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('admin.scheduling.include-schedule-popup')
@endsection

@section('script')
    <script src="{{asset('js/jquery.validate.min.js')}}" type="text/javascript"></script>
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script>
        let current_time_zone =  moment().format('Z');
        $('#user-time-zone').val(current_time_zone);

        $(document).ready(function() {
            table = $('#scheduling-instances').DataTable();
        });

        $(document).on('change', '#scheduling-filter-dropdown', function () {
            $('#filter-my-scheduling').submit();
        });

        $(document).on('change', '.schedulingStatus', function () {
            let status = $(this).val();
            let schedulingId = $(this).data('id');
            $.ajax({
                type: 'post',
                url: `{{ route('admin.scheduling.change-status') }}`,
                cache: false,
                data: {
                    _token: function () {
                        return '{{ csrf_token() }}';
                    },
                    id: schedulingId,
                    status: status
                },
                success: function (data) {
                    location.reload();
                }
            });
        })
    </script>
    @include('admin.scheduling.schedulerscripts')
@endsection
