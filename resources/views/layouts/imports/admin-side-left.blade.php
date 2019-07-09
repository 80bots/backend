<div class="sidebar">
    <div class="">
        <a href="#" class="sidebar-brand text-decoration-none">80bots</a>
        {{--<a href="{{route('user.dashboard')}}" class="sidebar-brand text-decoration-none">80bots</a>--}}
    </div>
    <div class="sidebar-nav">
        <ul class="list-unstyled mb-0">
            <li class="sidebar-header">Navigation</li>
            <li class="sidebar-item">
                <ul class="sidebar-dropdown list-unstyled">
                    <li class="sidebar-item "><a href="{{route('admin.user.index')}}" class="sidebar-link {{ (request()->is('admin/user')) ? 'active' : '' }}">Users List</a></li>
                    <li class="sidebar-item "><a href="{{route('admin.instance.running')}}" class="sidebar-link {{ (request()->is('admin/instance/running')) ? 'active' : '' }}">Running Bots</a></li>
                    <li class="sidebar-item "><a href="{{route('admin.bots.index')}}" class="sidebar-link {{ (request()->is('admin/bots')) ? 'active' : '' }}">All Bots</a></li>
                    <li class="sidebar-item "><a href="{{route('admin.bots.create')}}" class="sidebar-link {{ (request()->is('admin/bots/create')) ? 'active' : '' }}">Create Bot</a></li>
                    <li class="sidebar-item "><a href="{{route('admin.listsessions')}}" class="sidebar-link {{ (request()->is('admin/list-sessions')) ? 'active' : '' }}">Instance Sessions</a></li>
                </ul>
            </li>
            <li class="sidebar-item">
                    <span data-toggle="collapse" data-target="#plan" class="sidebar-link {{ (request()->is('admin/plan*')) ? '' : 'collapsed' }}"
                          aria-expanded="{{ (request()->is('admin/plan*')) ? 'true' : 'false' }}">
                        <span class="align-middle">Subscription Plan</span>
                    </span>
                <div id="plan" class="collapse in {{ (request()->is('admin/plan*')) ? 'show' : '' }}">
                    <ul class="sidebar-dropdown list-unstyled">
                        <li class="sidebar-item "><a href="{{route('admin.plan.index')}}" class="sidebar-link {{ (request()->is('admin/plan')) ? 'active' : '' }}">List</a></li>
                        <li class="sidebar-item "><a href="{{route('admin.plan.create')}}" class="sidebar-link {{ (request()->is('admin/plan/create')) ? 'active' : '' }}">Create</a></li>
                    </ul>
                </div>
            </li>
            <li class="sidebar-item">
                    <span data-toggle="collapse" data-target="#percentage" class="sidebar-link {{ (request()->is('admin/creditPercent')) ? '' : 'collapsed' }}"
                          aria-expanded="{{ (request()->is('admin/creditPercent*')) ? 'true' : 'false' }}">
                        <span class="align-middle">Credit Percentage</span>
                    </span>
                <div id="percentage" class="collapse in {{ (request()->is('admin/percent*')) ? 'show' : '' }}">
                    <ul class="sidebar-dropdown list-unstyled">

                        <li class="sidebar-item "><a href="{{route('percent.index')}}" class="sidebar-link {{ (request()->is('admin/percent')) ? 'active' : '' }}">List</a></li>

                        <li class="sidebar-item "><a href="{{route('percent.create')}}" class="sidebar-link {{ (request()->is('admin/percent/create')) ? 'active' : '' }}">Create</a></li>
                    </ul>
                </div>
            </li>
            <!-- END -->

        </ul>
    </div>
</div>
