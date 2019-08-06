<div class="sidebar">
    <div class="">
        <a href="#" class="sidebar-brand text-decoration-none active">
            <img src="{{ asset('assets/images/80bots.svg') }}" alt="">
        </a>
    </div>
    <div class="sidebar-nav">
        <ul class="sidebar-dropdown list-unstyled">
            <li class="sidebar-item ">
                <a href="{{route('bots.running')}}" class="sidebar-link
                    {{ (request()->is('bots/running')) ? 'active' : '' }}">
                    My Bots
                </a>
            </li>
            <li class="sidebar-item ">
                <a href="{{route('bots.index')}}"
                   class="sidebar-link {{ (request()->is('bots')) ? 'active' : '' }}">
                    Bots List
                </a>
            </li>
            <li class="sidebar-item ">
                <a href="{{route('scheduling.index')}}"
                   class="sidebar-link {{ (request()->is('scheduling')) ? 'active' : '' }}">
                    Scheduling List
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{route('subscription.index')}}"
                   class="sidebar-link {{ (request()->is('subscription')) ? 'active' : '' }}">
                    My Subscription
                </a>
            </li>
        </ul>
    </div>
    <hr>
    <div class>
        <a href="{{route('chatter.home')}}" class="pb-5 pl-5 "><i class="fa fa-comments mr-2"></i>Forum</a>
    </div>
</div>
