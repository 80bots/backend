<div class="sidebar">
    <div class="">
        <a href="{{route('user.dashboard')}}" class="sidebar-brand text-decoration-none">AWS SAAS</a>
    </div>
    <div class="sidebar-nav">
        <ul class="list-unstyled mb-0">
            <li class="sidebar-header">Navigation</li>
            <li class="sidebar-item">
                    <span data-toggle="collapse" data-target="#userInstance" class="sidebar-link {{ (request()->is('admin/user*')) ? '' : 'collapsed' }}"
                          aria-expanded="{{ (request()->is('admin/user*')) ? 'true' : 'false' }}">
                        <span class="align-middle"><i class="fas fa-user mr-2"></i> Users</span>
                    </span>
                <div id="userInstance" class="collapse in {{ (request()->is('admin/user*')) ? 'show' : '' }}">
                    <ul class="sidebar-dropdown list-unstyled">
                        <li class="sidebar-item "><a href="{{route('admin.user.index')}}" class="sidebar-link {{ (request()->is('admin/user')) ? 'active' : '' }}">List</a></li>
                        {{--<li class="sidebar-item "><a href="#." class="sidebar-link">Create</a></li>--}}
                    </ul>
                </div>
            </li>
            <li class="sidebar-item">
                    <span data-toggle="collapse" data-target="#bots" class="sidebar-link {{ (request()->is('admin/bots*')) ? '' : 'collapsed' }}"
                          aria-expanded="{{ (request()->is('admin/bots*')) ? 'true' : 'false' }}">
                        <span class="align-middle"><i class="fas fa-user mr-2"></i> Bots</span>
                    </span>
                <div id="bots" class="collapse in {{ (request()->is('admin/bots*')) ? 'show' : '' }}">
                    <ul class="sidebar-dropdown list-unstyled">
                        <li class="sidebar-item "><a href="{{route('admin.bots.index')}}" class="sidebar-link {{ (request()->is('admin/bots')) ? 'active' : '' }}">List</a></li>
                        <li class="sidebar-item "><a href="{{route('admin.bots.create')}}" class="sidebar-link {{ (request()->is('admin/bots/create')) ? 'active' : '' }}">Create</a></li>
                        {{--<li class="sidebar-item "><a href="#." class="sidebar-link">Create</a></li>--}}
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</div>
