<div class="sidebar">
    <div class="">
        {{--<a href="{{route('user.dashboard')}}" class="sidebar-brand text-decoration-none active">80bots</a>--}}
        <a href="#" class="sidebar-brand text-decoration-none active">
            <img src="{{ asset('assets/images/80bots.svg') }}" alt="">
        </a>
    </div>
    <div class="sidebar-nav">
        <ul class="sidebar-dropdown list-unstyled">
            <li class="sidebar-item ">
                <a href="{{route('user.bots.running.index')}}"
                   class="sidebar-link {{ (request()->is('user/bots/running')) ? 'active' : '' }}"
                >{{ __('layouts.sidebar.my_bots') }}
                </a>
            </li>
            <li class="sidebar-item ">
                <a href="{{route('user.bots.list')}}"
                   class="sidebar-link {{ (request()->is('user/bots')) ? 'active' : '' }}">
                    {{ __('layouts.sidebar.bots_list') }}
                </a>
            </li>
            <li class="sidebar-item ">
                <a href="{{route('user.scheduling.index')}}"
                   class="sidebar-link {{ (request()->is('user/scheduling')) ? 'active' : '' }}">
                    {{ __('layouts.sidebar.scheduling_list') }}
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{route('user.subscription-plans.index')}}"
                   class="sidebar-link {{ (request()->is('user/subscription-plans')) ? 'active' : '' }}">
                    {{ __('layouts.sidebar.my_subscription') }}
                </a>
            </li>
        </ul>
    </div>
    <hr>
    <div class>
        <a href="{{route('chatter.home')}}" class="pb-5 pl-5 "><i class="fa fa-comments mr-2"></i>
            {{ __('layouts.sidebar.forum') }}
        </a>
    </div>
</div>
