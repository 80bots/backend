@php
    use Illuminate\Support\Facades\Auth;

    $requestUrl = explode('/', ltrim($_SERVER['REQUEST_URI'],'/'));
    $role = $user->role->name ?? null;
@endphp
@if (!empty($role) && $role == 'User' || $role == 'Admin')
    <nav class="bg-white navbar navbar-expand d-flex justify-content-between align-items-center border-bottom">
                <span class="sidebar-toggle d-flex mr-2">
                    <span class="hamburger align-self-center"></span>
                </span>
        <div class="nav-right">
            <div class="dropdown">
                <span class="align-items-center d-flex dropdown-toggle" id="dropdownMenuButton"
                      data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-user-circle mr-2"></i> {{!empty($user->name) ? $user->name : ''}}
                </span>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a href="{{route('user.profile',['id' => $user->id])}}"
                       class="dropdown-item d-flex align-items-center justify-content-between">
                        {{ __('layouts.header.profile') }} <i class="fa fa-user-circle mr-2"></i>
                    </a>
                    <a class="dropdown-item d-flex align-items-center justify-content-between"
                       href="{{ route('logout') }}"
                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                        <span>{{ __('layouts.header.logout') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <h1>{{ __('layouts.header.hello') }}</h1>
                </div>
            </div>
        </div>
    </nav>
@endif
