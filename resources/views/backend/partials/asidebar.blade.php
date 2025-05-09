{{-- @php
$systemSetting = App\Models\SystemSetting::first();
@endphp --}}
<!-- Start Sidebar Area -->
<div class="sidebar-area" id="sidebar-area">
    <div class="logo position-relative">
        <a href="{{ route('admin.dashboard') }}" class="d-block text-decoration-none position-relative">
            <img src="{{ asset($systemSetting->logo ?? 'backend/admin/assets/logo.png') }}" alt="logo-icon">
            {{-- <span class="logo-text fw-bold text-dark">Switch</span> --}}
        </a>
        <button
            class="sidebar-burger-menu bg-transparent p-0 border-0 opacity-0 z-n1 position-absolute top-50 end-0 translate-middle-y"
            id="sidebar-burger-menu">
            <i data-feather="x"></i>
        </button>
    </div>

    <aside id="layout-menu" class="layout-menu menu-vertical menu active" data-simplebar>
        <ul class="menu-inner">
            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">MAIN</span>
            </li>
            <!-- Dashboard Menu Item -->
            <li class="menu-item open">
                <a href="{{ route('admin.dashboard') }}"
                    class="menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">dashboard</span>
                    <span class="title">Dashboard</span>
                </a>
            </li>

            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">APPS</span>
            </li>
            <!-- Contact Support Message Menu Item -->
            {{-- <li class="menu-item open">
                <a href="{{ route('admin_contact_us.index') }}"
                    class="menu-link {{ request()->routeIs('admin_contact_us.index') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">content_paste</span>
                    <span class="title">Contact Support Message</span>
                </a>
            </li> --}}
            <!-- Faqs Menu Item -->
            <li class="menu-item {{ request()->routeIs('faqs.*') ? 'open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle active">
                    <span class="material-symbols-outlined menu-icon">help</span>
                    <span class="title">Faqs</span>
                </a>
                <ul class="menu-sub">
                    <!-- Account Settings Submenu -->
                    <li class="menu-item ">
                        <a href="{{ route('faqs.index') }}"
                            class="menu-link {{ request()->routeIs('faqs.index') ? 'active' : '' }} ">
                            Faqs List
                        </a>
                    </li>
                    <!-- Change Password Submenu -->
                    <li class="menu-item">
                        <a href="{{ route('faqs.create') }}"
                            class="menu-link {{ request()->routeIs('faqs.create') ? 'active' : '' }}">
                            Add New Faqs
                        </a>
                    </li>

                </ul>
            </li>
            
            <!-- Category Menu Item -->
            <li class="menu-item {{ request()->routeIs('category.*') ? 'open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle active">
                    <span class="material-symbols-outlined">category</span>
                    <span class="title">Category</span>
                </a>
                <ul class="menu-sub">
                    <!-- Account Settings Submenu -->
                    <li class="menu-item ">
                        <a href="{{ route('category.index') }}"
                            class="menu-link {{ request()->routeIs('category.index') ? 'active' : '' }} ">
                            Category List
                        </a>
                    </li>
                    <!-- Change Password Submenu -->
                    <li class="menu-item">
                        <a href="{{ route('category.create') }}"
                            class="menu-link {{ request()->routeIs('category.create') ? 'active' : '' }}">
                            Add New Category
                        </a>
                    </li>

                </ul>
            </li>
            


            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">OTHERS</span>
            </li>

            <!-- Settings Menu Item -->
            <li class="menu-item {{ request()->routeIs('profile_settings.*', 'system_settings.*') ? 'open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle active">
                    <span class="material-symbols-outlined menu-icon">settings</span>
                    <span class="title">Settings</span>
                </a>
                <ul class="menu-sub">
                    <!-- Account Settings Submenu -->
                    <li class="menu-item ">
                        <a href="{{ route('profile_settings.index') }}"
                            class="menu-link {{ request()->routeIs('profile_settings.index') ? 'active' : '' }} ">
                            Profile Settings
                        </a>
                    </li>
                    <!-- Change Password Submenu -->
                    <li class="menu-item">
                        <a href="{{ route('profile_settings.password_change') }}"
                            class="menu-link {{ request()->routeIs('profile_settings.password_change') ? 'active' : '' }}">
                            Change Password
                        </a>
                    </li>
                    <!-- System Settings Configaration Submenu -->
                    <li class="menu-item">
                        <a href="{{ route('system_settings.index') }}"
                            class="menu-link {{ request()->routeIs('system_settings.*') ? 'active' : '' }}">
                            System Configaration
                        </a>
                    </li>

                </ul>
            </li>
            <!-- dynamic page Menu Item -->
            <li class="menu-item {{ request()->routeIs('dynamic_page.*') ? 'open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle active">
                    <span class="material-symbols-outlined menu-icon">clarify</span>
                    <span class="title">Daynamic page</span>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ route('dynamic_page.index') }}"
                            class="menu-link {{ request()->routeIs('dynamic_page.index') ? 'active' : '' }}">
                            Pages
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('dynamic_page.create') }}"
                            class="menu-link {{ request()->routeIs('dynamic_page.create') ? 'active' : '' }}">
                            Add New
                        </a>
                    </li>

                </ul>
            </li>
            <!-- dynamic page Menu Item -->
            <li class="menu-item {{ request()->routeIs('privacy.*') ? 'open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle active">
                    <span class="material-symbols-outlined">visibility</span>
                    <span class="title">privacy Policy</span>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ route('privacy.index') }}"
                            class="menu-link {{ request()->routeIs('dynamic_page.index') ? 'active' : '' }}">
                            Privacy Policy
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">CMS</span>
            </li>
            <!-- CMS Menu Item -->
            <li class="menu-item {{ request()->routeIs('cms.home_page.*') ? 'open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle active">
                    <span class="material-symbols-outlined menu-icon">handshake</span>
                    <span class="title">Social Info</span>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ route('cms.home_page.social_link.index') }}"
                            class="menu-link {{ request()->routeIs('cms.home_page.social_link.*') ? 'active' : '' }}">
                            Social Link
                        </a>
                    </li>

                </ul>
            </li>


            <!-- Logout Menu Item -->
            <li class="menu-item">
                <a class="menu-link"
                    onclick="event.preventDefault(); document.getElementById('logout-form-asidebar').submit();">
                    <span class="material-symbols-outlined menu-icon">logout</span>
                    <span class="title">Logout</span>
                </a>
            </li>
        </ul>
        <form id="logout-form-asidebar" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </aside>
</div>
<!-- End Sidebar Area -->
