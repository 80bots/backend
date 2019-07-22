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
                            <th></th>
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
                                                    onclick="changeStatus('{{$bots->id}}','inactive')"
                                                    title="make it inactive">Active
                                            </button>
                                        @else
                                            <button type="button" class="form-group btn btn-danger mb-0"
                                                    onclick="changeStatus('{{$bots->id}}','active')"
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
                                    <td>
                                        <a href="javascript:void(0)" data-toggle="modal" data-target="#lunch-instance"
                                               onclick="launchInstance('{{$bots->id}}')"
                                               class="badge badge-primary font-size-16" data-id="{{$bots->id}}">Launch</a>
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

    <!--div class="modal fade" id="lunch-instance" role="dialog">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form id="lunchInstance" >
                    @csrf
                    <input type="hidden" name="bot_id" value="" id="bot_id">
                    <div class="modal-header">
                        <h4 class="modal-title">Launch Bot</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <h4>Are you sure?</h4>
                    </div>
                    <div class="modal-footer">
                        <input type="button" id="launch-inspection-submit-btn" class="btn btn-success" value="Ok">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div-->
    @include('includes.launch-instance')
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $('#bot-list').DataTable();
        });

        function changeStatus(id, status) {
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

        $(document).ready(function(){
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
            $('#lunch-instance').hide();

            $.ajax({
                url : "/admin/storeSession",
                type : "POST",
                beforeSend: function() {
                    $('#launch-inspection-submit-btn').attr('disabled', true);
                },
                data : {
                    _token : function () {
                        return '{{csrf_token()}}';
                    },
                    user_id : '{{ Auth::id() }}',
                    bot_id : $('[name="bot_id"]').val(),

                },
                success : function(response){
                    $('[name="bot_id"]').val('');
                    $('#launch-instance').modal('hide');
                    $('#launch-inspection-submit-btn').removeAttr('disabled');
                    if(response.type === 'success'){
                        window.location = "/admin/instance/running?bots_filter=mybots";
                    }
                },
                error : function(response){

                }
            });
        });

        function checkBotIdInQueue(){
            $.ajax({
                url : "/admin/checkBotIdInQueue",
                type : "POST",
                data : {
                    _token : function () {
                        return '{{csrf_token()}}';
                    }
                },
                success : function(response){

                    if(response.type === 'success'){
                        // console.log(response);
                        // if(response.data !== undefined && response.data.length) {
                        //     let $botWrapper = $('#dvBotWrapper');
                        //     for(let eachData of response.data) {
                        //         $botWrapper.find('[data-id="'+eachData+'"]').attr('data-target','').prepend('<i class="fa fa-spinner fa-spin"></i>');
                        //     }
                        // }
                    }

                },
                error : function(response){

                }
            });
        }
    </script>
@endsection
