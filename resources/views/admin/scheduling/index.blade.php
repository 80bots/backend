@extends('layouts.app')

@section('title')
    {{ __('keywords.scheduling.title') }}
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="align-items-center bg-purple d-flex p-3 rounded shadow-sm text-white-50 mb-3">
            <h4 class="border mb-0 mr-2 pb-2 pl-3 pr-3 pt-2 rounded text-white">8</h4>
            <div class="lh-100">
                <h6 class="mb-0 text-white lh-100">{{ __('keywords.brand') }}</h6>
                <small>{{ __('keywords.since_year') }}</small>
            </div>
        </div>
        @include('layouts.imports.messages')
        @if(!empty($results) && isset($results))

            <div class="table-responsive">
                <table id="scheduling_instances" class="table thead-default vertical-middle mb-0">
                    <thead>
                    <tr>
                        <th>{{ __('keywords.scheduling.instance_id') }}</th>
                        <th>{{ __('keywords.scheduling.bot_name') }}</th>
                        <th>{{ __('keywords.status') }}</th>
                        <th>{{ __('keywords.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(isset($results) && !empty($results))
                        @foreach($results as $row)
                            <tr>
                                <td> {{!empty($row->userInstances['aws_instance_id']) ? $row->userInstances['aws_instance_id'] : ''}}</td>
                                <td>{{isset($row->userInstances->bots) && !empty($row->userInstances->bots->bot_name) ? $row->userInstances->bots->bot_name : ''}}</td>
                                <td>
                                    <select name="status" class="form-control schedulingStatus" data-id="{{$row->id}}">
                                        @if(!empty($row->status) && $row->status == 'active')
                                            <option selected="selected" value="active">
                                                {{ __('keywords.scheduling.statuses.active') }}
                                            </option>
                                            <option value="inactive">
                                                {{ __('keywords.scheduling.statuses.inactive') }}
                                            </option>
                                        @else
                                            <option selected="selected" value="inactive">
                                                {{ __('keywords.scheduling.statuses.inactive') }}
                                            </option>
                                            <option value="active">
                                                {{ __('keywords.scheduling.statuses.active') }}
                                            </option>
                                        @endif
                                    </select>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @php $bot_name=isset($row->userInstances->bots) && !empty($row->userInstances->bots->bot_name) ? $row->userInstances->bots->bot_name : ''@endphp

                                        <a href="javascript:void(0)" data-toggle="modal" data-target="#create-scheduler"
                                           onclick="SetBotName('{{$bot_name}}','{{$row->userInstances->id}}')"
                                           class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                           title="Edit Bot"><i class="fa fa-edit"></i></a>

                                        <form action="{{ route('admin.scheduling.destroy',$row->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    onclick="return confirm({{ __('keywords.are_you_sure') }})"
                                                    class="form-group btn btn-icon btn-danger change-credit-model mb-0">
                                                <i class="fa fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @include('admin.scheduling.include-schedule-popup')
@endsection

@section('script')
    <script src="{{asset('js/jquery.validate.min.js')}}" type="text/javascript"></script>
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script>
        var current_time_zone =  moment().format('Z');
        $('#user-time-zone').val(current_time_zone);

        $(document).on('change', '.schedulingStatus', function () {
            var status = $(this).val();
            var schedulingId = $(this).data('id');
            var URL = '{{route('admin.scheduling.change-status')}}';
            $.ajax({
                type: 'post',
                url: URL,
                cache: false,
                data: {
                    _token: function () {
                        return '{{csrf_token()}}';
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
