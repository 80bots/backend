@extends('layouts.app')

@section('title')
    Bots Listing
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
    {{--<div class="mb-3 p-3 bg-white rounded shadow-sm">
        <h6 class="border-bottom  pb-2 mb-0">Recent updates</h6>
        <div class="media text-muted pt-3">
            <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#007bff"></rect><text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text></svg>
            <p class="media-body pb-3 mb-0 small  border-bottom ">
                <strong class="d-block text-gray-dark">@username</strong>
                Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.
            </p>
        </div>
        <div class="media text-muted pt-3">
            <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#e83e8c"></rect><text x="50%" y="50%" fill="#e83e8c" dy=".3em">32x32</text></svg>
            <p class="media-body pb-3 mb-0 small  border-bottom ">
                <strong class="d-block text-gray-dark">@username</strong>
                Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.
            </p>
        </div>
        <div class="media text-muted pt-3">
            <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#6f42c1"></rect><text x="50%" y="50%" fill="#6f42c1" dy=".3em">32x32</text></svg>
            <p class="media-body pb-3 mb-0 small  border-bottom ">
                <strong class="d-block text-gray-dark">@username</strong>
                Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.
            </p>
        </div>
        <small class="d-block text-right mt-3">
            <a href="#">All updates</a>
        </small>
    </div>--}}
    <div class="my-3 p-3 bg-white rounded shadow-sm">
        <h6 class="border-bottom  pb-2 mb-0">Suggestions</h6>
        @if(!$bots->isEmpty())
            @foreach($bots as $bot)
                <div class="media text-muted pt-3">
                    <svg class="bd-placeholder-img mr-2 rounded" width="32" height="32" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img" aria-label="Placeholder: 32x32"><title>Placeholder</title><rect width="100%" height="100%" fill="#007bff"></rect><text x="50%" y="50%" fill="#007bff" dy=".3em">32x32</text></svg>
                    <div class="media-body pb-3 mb-0 small  border-bottom ">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <strong class="d-block text-gray-dark">{{$bot->bot_name}} | Platform: {{$bot->platform->name}}</strong>
                            <div>{{$bot->description}}</div>
                            <a href="#">Follow</a>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
        <small class="d-block text-right mt-3">
            <a href="#">All suggestions</a>
        </small>
    </div>
</div>
@endsection

@section('script')

@endsection
