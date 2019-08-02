@extends('layouts.app')

@section('title')
    All Bots
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">All Bots</h5>
                <h5 class="mb-0">
                    <a href="{{route('admin.bots.create')}}" class="btn btn-round btn-primary"><i
                                class="fas fa-plus-circle"></i></a>
                </h5>
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
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($bots as $bot)
                            <tr>
                                <td>{{ $bot->bot_name ?? '' }}</td>
                                <td>{{ $bot->aws_ami_image_id ?? '' }}</td>
                                <td>{{ $bot->aws_ami_name ?? '' }}</td>
                                <td>{{ $bot->aws_instance_type ?? '' }}</td>
                                <td>{{ $bot->aws_storage_gb ?? '' }}</td>
                                <td>
                                    @if( $bot->status && $bot->status == 'active')
                                        <button type="button" class="form-group btn btn-success mb-0"
                                                onclick="changeStatus('{{route('admin.bots.update.status', ['id' => $bot->id])}}','inactive')"
                                                title="make it inactive">Active
                                        </button>
                                    @else
                                        <button type="button" class="form-group btn btn-danger mb-0"
                                                onclick="changeStatus('{{route('admin.bots.update.status', ['id' => $bot->id])}}','active')"
                                                title="make it active">Inactive
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('admin.bots.destroy',$bot->id) }}" method="POST">
                                        <div class="d-flex align-items-center">
                                            <a href="{{route('admin.bots.show',$bot->id)}}"
                                               class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                               title="Edit Bot"><i class="fa fa-eye"></i></a>

                                            <a href="{{route('admin.bots.edit',$bot->id)}}"
                                               class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                               title="Edit Bot"><i class="fa fa-edit"></i></a>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Are you sure?')"
                                                    class="form-group btn btn-icon btn-danger change-credit-model mb-0">
                                                <i class="fa fa-trash"></i></button>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <a href="javascript:void(0)" onclick="launchInstance({{$bot->id}});"
                                       class="btn font-size-16" data-id="{{$bot->id}}">
                                        Launch <i class="fa fa-rocket" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <h3>No bots found!</h3>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('includes.launch-instance')
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $('#bot-list').DataTable();
        });

        function launchInstance(botId) {
            $('#launch-instance').modal('show');
            $('[name="bot_id"]').val(botId);
        }

        function changeStatus(url, status) {
            $.ajax({
                type: 'PUT',
                url,
                cache: false,
                data: { status },
                success: function (data) {
                    location.reload();
                }
            });
        }

        $(document).ready(function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            var bot_ids = 0;
            //checkBotIdInQueue();
        });

        function launchInstance(botId) {
            $('#launch-instance').modal('show');
            console.log(botId)
            $('[name="bot_id"]').val(botId);
        }

        $(document).on('click', '#launch-inspection-submit-btn', function () {
            $.ajax({
                url: `{{ route('admin.session.create') }}`,
                type: 'POST',
                beforeSend: function () {
                    $('#launch-inspection-submit-btn').attr('disabled', true);
                },
                data: {
                    user_id: '{{ Auth::id() }}',
                    bot_id: $('[name="bot_id"]').val(),
                },
                success: function (response) {
                    $('[name="bot_id"]').val('');
                    $('#launch-instance').modal('hide');
                    $('#launch-inspection-submit-btn').removeAttr('disabled');
                    if (response.type === 'success') {
                        window.location = "/admin/instance/running?bots_filter=mybots";
                    }
                },
                error: function (response) {
                    console.log(response);
                    alert('Something went wrong!');
                    $('#launch-inspection-submit-btn').removeAttr('disabled');
                }
            });
        });

        function checkBotIdInQueue() {
            $.ajax({
                url: `{{ route('admin.bots.running.check') }}`,
                type: 'POST',
                success: function (response) {
                    if (response.type === 'success') {
                    }
                },
                error: function (response) {
                }
            });
        }
    </script>
@endsection
