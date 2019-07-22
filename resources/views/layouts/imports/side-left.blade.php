<div class="sidebar">
    <div class="">
        {{--<a href="{{route('user.dashboard')}}" class="sidebar-brand text-decoration-none active">80bots</a>--}}
        <a href="#" class="sidebar-brand text-decoration-none active"><img src="{{ asset('assets/images/80bots.svg') }}" alt=""></a>
    </div>
    <div class="sidebar-nav">
        <ul class="list-unstyled mb-0">
            <li class="sidebar-header">Navigation</li>
            <li class="sidebar-item">
                <ul class="sidebar-dropdown list-unstyled">
                    <li class="sidebar-item "><a href="{{route('user.instance.index')}}" class="sidebar-link {{ (request()->is('user/instance')) ? 'active' : '' }}">My Bots</a></li>
                    <li class="sidebar-item "><a href="{{route('user.bots.list')}}" class="sidebar-link {{ (request()->is('user/bots')) ? 'active' : '' }}">Bots List</a></li>
                    <li class="sidebar-item "><a href="{{route('user.scheduling.index')}}" class="sidebar-link {{ (request()->is('user/scheduling')) ? 'active' : '' }}">Scheduling List</a></li>
                </ul>
            </li>

            <li class="sidebar-item">
                <ul class="sidebar-dropdown list-unstyled">
                    <li class="sidebar-item"><a href="{{route('user.subscription-plans.index')}}" class="sidebar-link {{ (request()->is('user/subscription-plans')) ? 'active' : '' }}">Subscription Plans</a></li>
                </ul>
            </li>
        </ul>
    </div>
    <hr>
    <div class>
        <a href="{{route('chatter.home')}}" class="text-light pb-5 pl-5 "><i class="fa fa-comments mr-2"></i>Forum</a>
    </div>
</div>
