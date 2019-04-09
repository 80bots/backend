<div class="sidebar">
    <div class="">
        {{--<a href="{{route('user.dashboard')}}" class="sidebar-brand text-decoration-none active">80bots</a>--}}
        <a href="#" class="sidebar-brand text-decoration-none active">80bots</a>
    </div>
    <div class="sidebar-nav">
        <ul class="list-unstyled mb-0">
            <li class="sidebar-header">Navigation</li>
            {{--<li class="sidebar-item ">
                    <span data-toggle="collapse" data-target="#userInstance" class="sidebar-link {{ (request()->is('user/instance*')) ? '' : 'collapsed' }}"
                          aria-expanded="{{ (request()->is('user/instance*')) ? 'true' : 'false' }}">
                        <span class="align-middle"><i class="fas fa-user mr-2"></i> Instance</span>
                    </span>
                <div id="userInstance" class="collapse in {{ (request()->is('user/instance*')) ? 'show' : '' }}">
                    <ul class="sidebar-dropdown list-unstyled">
                        <li class="sidebar-item "><a href="{{route('user.instance.index')}}" class="sidebar-link {{ (request()->is('user/instance')) ? 'active' : '' }}">List</a></li>
                    </ul>
                </div>
            </li>--}}

            <li class="sidebar-item">
                <ul class="sidebar-dropdown list-unstyled">
                    <li class="sidebar-item "><a href="{{route('user.instance.index')}}" class="sidebar-link {{ (request()->is('user/instance')) ? 'active' : '' }}">My Bots</a></li>
                    <li class="sidebar-item "><a href="{{route('user.bots.list')}}" class="sidebar-link {{ (request()->is('user/bots-list')) ? 'active' : '' }}">Bots List</a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>
