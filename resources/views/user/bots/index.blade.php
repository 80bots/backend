@extends('layouts.app')

@section('title')
    {{ __('user.bots.title') }}
@endsection

@section('css')
@endsection

@section('content')
    <div class="wrapper">
        @include('includes.banner')
        @forelse($platforms as $platform)
            <div class="mb-3 p-3 bg-white rounded shadow-sm">
                <h6 class="border-bottom  pb-2 mb-0">{{$platform->name}}</h6>
                @forelse ($platform->activeBotsWithPrivate as $bot)
                    <div class="media text-muted pt-3 d-flex align-items-start">
                        <svg class="bd-placeholder-img mr-2 rounded flex-shrink-0" width="32" height="32"
                             xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice"
                             focusable="false" role="img" aria-label="Placeholder: 32x32">
                             <title>{{ __('keywords.placeholder') }}</title>
                            <rect width="100%" height="100%" fill="#007bff"></rect>
                            <text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text>
                        </svg>
                        <div class="row flex-grow-1 ml-0 mr-0 border-bottom pb-3">
                            <div class="col-md-2 col-sm-3">
                                <strong class="d-block text-gray-dark">{{$bot->name}}</strong>
                            </div>
                            <div class="col-md-5 col-sm-9">
                                {{$bot->description}}
                            </div>
                            <div class="col-md-3 col-sm-12">
                                @foreach($bot->tags as $tag)
                                    <span class="badge badge-pill badge-info font-size-16 mr-1 mb-1">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                            @if(!empty(Session::get('bot_id')))
                                <div class="col-md-2 col-sm-12 text-right">
                                    <a href="javascript:void(0)" data-toggle="modal" data-target="#launch-instance"
                                       class="badge badge-primary font-size-16"
                                       onclick="javascript:launchInstance({{$bot->id}});"
                                       data-id="{{$bot->id}}">
                                        {{ __('keywords.launch') }}
                                    </a>
                                </div>
                            @else
                                <div class="col-md-2 col-sm-12 text-right">
                                    <a href="javascript:void(0)" data-toggle="modal" data-target="#launch-instance"
                                       class="badge badge-primary font-size-16"
                                       onclick="javascript:launchInstance({{$bot->id}});"
                                       data-id="{{$bot->id}}">{{ __('keywords.launch') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <h3>{{ __('keywords.bots.not_found') }}</h3>
                @endforelse
                <small class="d-block text-right mt-3">
                    <a href="{{route('bots.index', ['id' => $platform->id])}}">
                        {{ __('user.bots.all') }}
                    </a>
                </small>
            </div>
        @empty
            <h3>{{ __('keywords.bots.not_found') }}</h3>
        @endforelse
    </div>
    @include('includes.launch-instance')
@endsection

@section('script')
    <script src="{{asset('js/jquery.validate.min.js')}}" type="text/javascript"></script>
    <script type="text/javascript" src="{{ asset('js/moment.min.js')}}"></script>
    <script>
      function launchInstance(botId) {
        $('#launch-instance').modal('show');
        $('[name="bot_id"]').val(botId);
      }

      $(document).on('click', '#launch-inspection-submit-btn', function () {
          $.ajax({
              url : `{{route('session.create')}}`,
              type : 'POST',
              beforeSend: function() {
                $('#launch-inspection-submit-btn').attr('disabled', true);
              },
              data : {
                  user_id : '{{ auth()->id() }}',
                  bot_id : $('[name="bot_id"]').val(),
              },
              success : function(response){
                  if(response.type === 'success'){
                      window.location = '/bots/running';
                  }
                  $('[name="bot_id"]').val('');
                  $('#launch-instance').modal('hide');
                  $('#launch-inspection-submit-btn').removeAttr('disabled');
              },
              error : function(response){
                console.log(response);
                alert('Something went wrong!');
                $('#launch-inspection-submit-btn').removeAttr('disabled');
              }
          });
      });
    </script>
@endsection
