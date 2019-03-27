<div class="sidebar">
    <div class="">
        <a href="{{route('user.dashboard')}}" class="sidebar-brand text-decoration-none active">AWS SAAS</a>
    </div>
    <div class="sidebar-nav">
        <ul class="list-unstyled mb-0">
            <li class="sidebar-header">Navigation</li>
            <li class="sidebar-item ">
                    <span data-toggle="collapse" data-target="#userInstance" class="sidebar-link"
                          aria-expanded="false">
                        <span class="align-middle"><i class="fas fa-user mr-2"></i> Instance</span>
                    </span>
                <div id="userInstance" class="collapse in show">
                    <ul class="sidebar-dropdown list-unstyled">
                        <li class="sidebar-item "><a href="{{route('user.instance.index')}}" class="sidebar-link {{ (request()->is('user/instance')) ? 'active' : '' }}">List</a></li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</div>
