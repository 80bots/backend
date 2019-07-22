<div class="sidebar">
    <div class="">
        <a href="#" class="sidebar-brand text-decoration-none"><img src="{{ asset('assets/images/80bots.svg') }}"
                                                                    alt=""></a>
        {{--<a href="{{route('user.dashboard')}}" class="sidebar-brand text-decoration-none">80bots</a>--}}
    </div>
    <div class="sidebar-nav">
        <ul class="list-unstyled mb-0">
            <li class="sidebar-item">
                <ul class="sidebar-dropdown list-unstyled">
                    <li class="sidebar-item "><a href="{{route('admin.user.index')}}"
                                                 class="sidebar-link {{ (request()->is('admin/user')) ? 'active' : '' }}">Users
                            List</a></li>
                    <li class="sidebar-item "><a href="{{ url('admin/instance/running') }}"
                                                 class="sidebar-link {{ (request()->is('admin/instance/running')) ? 'active' : '' }}">Running
                            Bots</a></li>
                    <li class="sidebar-item "><a href="{{route('admin.bots.index')}}"
                                                 class="sidebar-link {{ (request()->is('admin/bots')) ? 'active' : '' }}">All
                            Bots</a></li>
                    <li class="sidebar-item "><a href="{{route('admin.listsessions')}}"
                                                 class="sidebar-link {{ (request()->is('admin/list-sessions')) ? 'active' : '' }}">Bots
                            Sessions</a></li>
                    <li class="sidebar-item "><a href="{{route('admin.plan.index')}}"
                                                 class="sidebar-link {{ (request()->is('admin/plan*')) ? 'active' : '' }}">Subscription
                            Plan</a></li>
                    <li class="sidebar-item "><a href="{{route('admin.percent.index')}}"
                                                 class="sidebar-link {{ (request()->is('admin/percent*')) ? 'active' : '' }}">Low
                            Credit Notification</a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>
