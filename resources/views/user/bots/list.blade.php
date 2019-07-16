@extends('layouts.app')

@section('title')
    Bots All Listing
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="align-items-center bg-purple d-flex p-3 rounded shadow-sm text-white-50 mb-3">
            <h4 class="border mb-0 mr-2 pb-2 pl-3 pr-3 pt-2 rounded text-white">8</h4>
            <div class="lh-100">
                <h6 class="mb-0 text-white lh-100">80bots</h6>
                <small>Since 2019</small>
            </div>
        </div>
        @if(!empty($platform) && isset($platform))
            <div class="my-3 p-3 bg-white rounded shadow-sm">
                <h6 class="border-bottom  pb-2 mb-0">{{!empty($platform->name)?$platform->name:'List All Bots'}}</h6>
                @if(!$platform->bots->isEmpty())
                    @foreach($platform->bots as $bot)
                        <div class="media text-muted pt-3 d-flex align-items-start">
                            <svg class="bd-placeholder-img mr-2 rounded flex-shrink-0" width="32" height="32"
                                 xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
                                 focusable="false" role="img" aria-label="Placeholder: 32x32"><title>
                                    Placeholder</title>
                                <rect width="100%" height="100%" fill="#007bff"></rect>
                                <text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text>
                            </svg>
                            <div class="row flex-grow-1 ml-0 mr-0 border-bottom pb-3">
                                <div class="col-md-2 col-sm-3">
                                    <strong class="d-block text-gray-dark">{{$bot->bot_name}}</strong>
                                </div>
                                <div class="col-md-5 col-sm-9">
                                    {{$bot->description}}
                                </div>
                                <div class="col-md-3 col-sm-12">
                                    @if(isset($bot->botTags) && !$bot->botTags->isEmpty())
                                        @foreach($bot->botTags as $botTag)
                                            <span class="badge badge-pill badge-info font-size-16 mr-1 mb-1">
                                                {{$botTag->tags->name}}
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                                <div class="col-md-2 col-sm-12 text-right">
                                    <a href="javascript:void(0)" data-toggle="modal" data-target="#lunch-instance"
                                       onclick="launchInstance('{{$bot->id}}')"
                                       class="badge badge-primary font-size-16">Launch</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif
    </div>
    <div class="modal fade" id="lunch-instance" role="dialog">
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
    </div>
@endsection

@section('script')
    <script>

        $(document).ready(function(){
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            var bot_ids = 0 ;
            checkBotIdInQueue();
        });

        function launchInstance(bot_id) {
            bot_ids = bot_id;
        }

        $(document).on('click', '#launch-inspection-submit-btn', function () {
            $('#lunch-instance').hide();

            $.ajax({
                url : "/user/storeSession",
                type : "POST",
                data : {
                    _token : function () {
                        return '{{csrf_token()}}';
                    },
                    user_id : '{{ Auth::user()->id }}',
                    bot_id : bot_ids,

                },
                success : function(response){

                    if(response.type === 'success'){
                        window.location = "/user/instance";
                    }

                },
                error : function(response){

                }
            });

        });


        function checkBotIdInQueue(){
            $.ajax({
                url : "/user/checkBotIdInQueue",
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
