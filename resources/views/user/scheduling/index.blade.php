@extends('layouts.app')

@section('title')
    {{ __('keywords.scheduling.title') }}
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        @include('includes.banner')
        @include('layouts.imports.messages')
        @if(!empty($results) && isset($results))
            <div class="table-responsive">
                <table id="scheduling_instances" class="table thead-default vertical-middle mb-0">
                    <thead>
                    <tr>
                        <th>{{ __('keywords.bots.instance_id') }}</th>
                        <th>{{ __('keywords.bots.bot_name') }}</th>
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
                                            <option selected="selected" value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        @else
                                            <option selected="selected" value="inactive">Inactive</option>
                                            <option value="active">Active</option>
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

                                        <form action="{{ route('scheduling.destroy',$row->id) }}" method="POST">
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
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @include('user.scheduling.create-schedule-popup')
@endsection

@section('script')
    <script src="{{asset('js/jquery.validate.min.js')}}" type="text/javascript"></script>
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script>
        let current_time_zone =  moment().format('Z');
        $('#user-time-zone').val(current_time_zone);

        $(document).on('change', '.schedulingStatus', function () {
            let status = $(this).val();
            let schedulingId = $(this).data('id');
            $.ajax({
                type: 'put',
                url: `{{route('scheduling.update.status')}}`,
                cache: false,
                data: {
                    id: schedulingId,
                    status: status
                },
                success: function (data) {
                    location.reload();
                }
            });
        })
    </script>
    @include('user.scheduling.scheduler-scripts')
@endsection
