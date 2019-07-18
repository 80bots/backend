@extends('layouts.app')

@section('title')
    Bots Listing
@endsection

@section('css')
@endsection

@section('content')
    <div  class="wrapper">
        @include('includes.banner')
        @foreach($platforms as $platform)
            <div class="mb-3 p-3 bg-white rounded shadow-sm">
                <h6 class="border-bottom  pb-2 mb-0">{{$platform->name}}</h6>
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
                                @foreach($bot->botTags as $botTag)
                                  @if($tag = $botTag->tags)
                                    <span class="badge badge-pill badge-info font-size-16 mr-1 mb-1">
                                        {{ $tag->name }}
                                    </span>
                                  @endif
                                @endforeach
                            </div>
                            @if(!empty(Session::get('bot_id')))
                                <div class="col-md-2 col-sm-12 text-right">
                                    <a href="javascript:void(0)" data-toggle="modal" data-target="#launch-instance"
                                       class="badge badge-primary font-size-16" data-id="{{$bot->id}}">Launch</a>
                                </div>
                            @else
                                <div class="col-md-2 col-sm-12 text-right">
                                    <a href="javascript:void(0)" data-toggle="modal" data-target="#launch-instance"
                                       class="badge badge-primary font-size-16" data-id="{{$bot->id}}">Launch</a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                <small class="d-block text-right mt-3">
                    <a href="{{route('user.bots.all.list', ['id' => $platform->id])}}">All Bots</a>
                </small>
            </div>
        @endforeach
    </div>
    @include('includes.launch-instance')
@endsection

@section('script')
    <script src="{{asset('js/jquery.validate.min.js')}}" type="text/javascript"></script>
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script>

        $(document).ready(function(){
            var botId = null ;
            checkBotIdInQueue();
        });

        $(document).on('click', '#launch-inspection-submit-btn', function () {
            $('#launch-instance').hide();

            $.ajax({
                url : "/user/storeSession",
                type : "POST",
                data : {
                    _token : function () {
                        return '{{csrf_token()}}';
                    },
                    user_id : '{{ Auth::id() }}',
                    bot_id : botId,

                },
                success : function(response){
                    if(response.type === 'success'){
                        window.location = "/user/instance";
                    }
                },
                error : function(response){
                  console.log(response);
                  alert('Something went wrong!');
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
                  console.log(response);
                  alert('Something went wrong!');
                }
            });

        }
    </script>
@endsection
