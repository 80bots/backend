<div class="sidebar">
    <div class="">
        <a href="#" class="sidebar-brand text-decoration-none"><img src="{{ asset('assets/images/80bots.svg') }}"
                                                                    alt=""></a>
        {{--<a href="{{route('user.dashboard')}}" class="sidebar-brand text-decoration-none">80bots</a>--}}
    </div>
    <div class="sidebar-nav">
        <ul class="sidebar-dropdown list-unstyled">
            <li class="sidebar-item "><a href="{{route('admin.user.index')}}"
                                         class="sidebar-link {{ (request()->is('admin/user')) ? 'active' : '' }}">
                    {{ __('layouts.sidebar.users_list') }}
                </a></li>
            <li class="sidebar-item "><a href="{{ url('admin/bots/running') }}"
                                         class="sidebar-link {{ (request()->is('admin/bots/running')) ? 'active' : '' }}">
                    {{ __('layouts.sidebar.running_bots') }}
                </a></li>
            <li class="sidebar-item "><a href="{{route('admin.bots.index')}}"
                                         class="sidebar-link {{ (request()->is('admin/bots')) ? 'active' : '' }}">
                    {{ __('layouts.sidebar.all_bots') }}</a></li>
            <li class="sidebar-item "><a href="{{route('admin.listsessions')}}"
                                         class="sidebar-link {{ (request()->is('admin/list-sessions')) ? 'active' : '' }}">
                    {{ __('layouts.sidebar.bots_session') }}</a></li>
            <li class="sidebar-item "><a href="{{route('admin.plan.index')}}"
                                         class="sidebar-link {{ (request()->is('admin/plan*')) ? 'active' : '' }}">
                    {{ __('layouts.sidebar.subscription_plan') }}</a></li>
            <li class="sidebar-item "><a href="{{route('admin.percent.index')}}"
                                         class="sidebar-link {{ (request()->is('admin/percent*')) ? 'active' : '' }}">
                    {{ __('layouts.sidebar.low_credit_notification') }}</a></li>
        </ul>
    </div>
</div>
