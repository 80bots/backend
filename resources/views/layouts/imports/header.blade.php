@php
    use Illuminate\Support\Facades\Auth;

    $requestUrl = explode('/', ltrim($_SERVER['REQUEST_URI'],'/'));
    $role = isset(Auth::user()->role->name) ? Auth::user()->role->name : '';
@endphp
@if (!empty($role) && $role == 'User' || $role == 'Admin')
    <header class="sa-page-header">
        <div class="sa-header-container h-100">
            <div class="d-table d-table-fixed h-100 w-100">
                <div class="sa-logo-space d-table-cell h-100">
                    <div class="flex-row d-flex align-items-center h-100">
                        <a class="sa-logo-link" href="" title="{{config('app.name')}}">
                            <img alt="{{config('app.name')}}" src="{{asset('assets/img/common/sa-logo.png')}}" class="sa-logo"></a>
                        {{--<div class="dropdown ml-auto">
                            <button class="btn btn-default sa-btn-icon sa-activity-dropdown-toggle" type="button"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span
                                    class="fa fa-user"></span><span class="badge bg-red">21</span></button>
                            <div class="dropdown-menu ml-auto ajax-dropdown" aria-labelledby="dropdownMenuButton">
                                <form class="btn-group btn-group-justified" role="group" aria-label="Basic example">
                                    <button type="button" class="btn btn-default" onclick="loadNotifications(this)"
                                            data-url="ajax/mail.html">Msgs (21)
                                    </button>
                                    <button type="button" class="btn btn-default" onclick="loadNotifications(this)"
                                            data-url="ajax/notifications.html">Notify (3)
                                    </button>
                                    <button type="button" class="btn btn-default" onclick="loadNotifications(this)"
                                            data-url="ajax/tasks.html">Tasks (4)
                                    </button>
                                </form>
                                <div class="sa-ajax-notification-container">
                                    <div class="alert sa-ajax-notification-alert">
                                        <h4>Click a button to show messages here</h4>
                                        This blank page message helps protect your privacy, or you can show the first
                                        message here automatically.
                                    </div>
                                    <span class="fa fa-lock fa-4x fa-border"></span>
                                </div>

                                <form role="group"
                                      class="flex-row d-flex align-items-center sa-ajax-notification-footer">
                                    <span>Last updated on: 12/12/2013 9:43AM</span>
                                    <button class="btn btn-xs btn-default ml-auto" type="button"
                                            onclick="toggleReloadButton(this, event)"><span
                                            class="fa fa-refresh"></span></button>
                                </form>

                            </div>
                        </div>--}}
                    </div>
                </div>
                <div class="d-table-cell h-100 w-100 align-middle">
                    <div class="sa-header-menu">
                        <div class="d-flex align-items-center w-100">
                            {{--<div class="sa-header-left-area">
                                <span class="sa-project-label">Projects:</span>
                                <div class="dropdown sa-project-dropdown">
                                    <a href=":;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Recent
                                        projects <span class="fa fa-angle-down"></span></a>
                                    <div class="dropdown-menu ml-auto" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" href="#">Online e-merchant management system -
                                            attaching integration with the iOS</a>
                                        <a class="dropdown-item" href="#">Notes on pipeline upgradee</a>
                                        <a class="dropdown-item" href="#">Assesment Report for merchant account</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#"><span class="fa fa-power-off"></span>
                                            Clear</a>
                                    </div>
                                </div>
                            </div>--}}
                            <div class="ml-auto sa-header-right-area">
                                <div class="form-inline">
                        {{--<span class="dropdown sa-country-dropdown">
                          <a href="javascript:void(0);" data-toggle="dropdown" aria-haspopup="true"
                             aria-expanded="false"><em class="flag flag-us"></em> <span>English (US) <span
                                      class="fa fa-angle-down"></span></span></a>
                          <span class="dropdown-menu dropdown-menu-right ml-auto" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item active" href="javascript:void(0);"><span
                                    class="flag flag-us"></span> English (US)</a>
                            <a class="dropdown-item" href="javascript:void(0);"><span class="flag flag-fr"></span> Français</a>
                            <a class="dropdown-item" href="javascript:void(0);"><span class="flag flag-es"></span> Español</a>
                            <a class="dropdown-item" href="javascript:void(0);"><span class="flag flag-de"></span> Deutsch</a>
                            <a class="dropdown-item" href="javascript:void(0);"><span
                                    class="flag flag-jp"></span> 日本語</a>
                            <a class="dropdown-item" href="javascript:void(0);"><span
                                    class="flag flag-cn"></span> 中文</a>
                            <a class="dropdown-item" href="javascript:void(0);"><span class="flag flag-it"></span> Italiano</a>
                            <a class="dropdown-item" href="javascript:void(0);"><span class="flag flag-pt"></span> Portugal</a>
                            <a class="dropdown-item" href="javascript:void(0);"><span class="flag flag-ru"></span> Русский язык</a>
                            <a class="dropdown-item" href="javascript:void(0);"><span
                                    class="flag flag-kr"></span> 한국어</a>
                          </span>
                        </span>--}}
                                    {{--<button class="btn btn-light sa-btn-icon sa-btn-micro d-none d-lg-block"
                                            type="button"><span class="fa fa-microphone"></span></button>--}}
                                    <button class="btn btn-light sa-btn-icon sa-toggle-full-screen d-none d-lg-block"
                                            type="button" onclick="toggleFullScreen()"><span
                                            class="fa fa-arrows-alt"></span></button>
                                    <form class="sa-header-search-form">
                                        <input type="text" class="form-control" placeholder="Find reports and more">
                                        <button type="submit" class="sa-form-btn"><span class="fa fa-search"></span>
                                        </button>
                                    </form>

                                    <a class="btn btn-default sa-logout-header-toggle sa-btn-icon" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <span class="fa fa-sign-out"></span>
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                    <span class="dropdown sa-user-dropdown">
                          <a href="javascript:void(0);" data-toggle="dropdown" aria-haspopup="true"
                             aria-expanded="false" class="sa-user-dropdown-toggle">
                            <img src="{{asset('assets/img/avatars/sunny.png')}}" alt="John Doe">
                          </a>
                          <span class="dropdown-menu dropdown-menu-right ml-auto" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="javascript:void(0);"><i class="fa fa-cog"></i> Setting</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="javascript:void(0);"> <i class="fa fa-user"></i> <u>P</u>rofile</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="javascript:void(0);"><i
                                    class="fa fa-arrow-down"></i> <u>S</u>hortcut</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="javascript:void(0);"><i
                                    class="fa fa-arrows-alt"></i> Full <u>S</u>creen</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item padding-10 padding-top-5 padding-bottom-5"
                               href="javascript:void(0);" data-action="userLogout"><i class="fa fa-sign-out fa-lg"></i> <strong><u>L</u>ogout</strong></a>
                          </span>
                        </span>

                                    <button class="btn btn-default sa-btn-icon sa-sidebar-hidden-toggle"
                                            onclick="SAtoggleClass(this, 'body', 'sa-hidden-menu')" type="button"><span
                                            class="fa fa-reorder"></span></button>


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </header>
@else
    @if($requestUrl[0] == 'login')
        <header id="header" class="publicheader">
            <div class="logo-group">
                <span class="sa-logo"> <img src="{{ asset('assets/img/common/logo.png') }}" alt="{{config('app.name')}}"></span>
            </div>

            <span class="extr-page-header-space">
        			<span class="hidden-mobile hiddex-xs">Need an account?</span>
        			<a href="{{route('register')}}" class="btn sa-btn-danger">Create account</a>
        		</span>
        </header>
    @else
        <header id="header" class="publicheader">
            <div class="logo-group">
                <span class="sa-logo"> <img src="{{ asset('assets/img/common/logo.png') }}" alt="{{config('app.name')}}"></span>
            </div>
            <span class="extr-page-header-space">
                <span class="hidden-mobile hiddex-xs">Already registered?</span>
                <a href="{{route('login')}}" class="btn sa-btn-danger">Sign In</a>
            </span>
        </header>
    @endif
@endif
