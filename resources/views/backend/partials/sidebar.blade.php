  <!-- navbar vertical -->
  <div class="app-menu">
      <div class="navbar-vertical navbar nav-dashboard">
          <div class="h-100" data-simplebar>
              <!-- Brand logo -->
              <a class="navbar-brand" href="{{ route('dashboard') }}">
                  <img
                      src="{{ isset($admin_setting->logo) ? asset($admin_setting->logo) : asset('uploads/default.png') }}" />
              </a>
              <!-- Navbar nav -->
              <ul class="navbar-nav flex-column" id="sideNavbar">
                  <li class="nav-item">
                      <a class="nav-link" href="{{ route('dashboard') }}">
                          <i data-feather="bar-chart-2" class="nav-icon me-2 icon-xxs"></i>
                          Dashboard
                      </a>
                  </li>

                  {{-- banner management --}}
                  {{-- <li class="nav-item">
                      <a class="nav-link" href="{{ route('banner.index') }}">
                          <i data-feather="image" class="nav-icon me-2 icon-xxs"></i>
                          Banner
                      </a>
                  </li> --}}

                  {{-- Product Management --}}
                  {{-- Product Management --}}
                  <li class="nav-item {{ request()->routeIs('product.*', 'category.*') ? 'active' : '' }}">
                      <a class="nav-link has-arrow" href="#!" data-bs-toggle="collapse"
                          data-bs-target="#productCollapse"
                          aria-expanded="{{ request()->routeIs('product.*', 'category.*') ? 'true' : 'false' }}"
                          aria-controls="productCollapse">
                          <i data-feather="box" class="nav-icon me-2 icon-xxs"></i>Product Management
                      </a>
                      <div id="productCollapse"
                          class="collapse {{ request()->routeIs('product.*', 'category.*') ? 'show' : '' }}"
                          data-bs-parent="#productCollapse">
                          <ul class="nav flex-column ms-3">
                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('product.index') ? 'active' : '' }}"
                                      href="{{ route('product.index') }}">
                                      Product List
                                  </a>
                              </li>
                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('category.index') ? 'active' : '' }}"
                                      href="{{ route('category.index') }}">
                                      Category List
                                  </a>
                              </li>
                          </ul>
                      </div>
                  </li>



                  {{-- Review Management --}}
                  {{-- <li class="nav-item {{ request()->routeIs('review.*', 'user.*') ? 'active' : '' }}">
                      <a class="nav-link has-arrow" data-bs-toggle="collapse" data-bs-target="#reviewCollapse"
                          aria-expanded="{{ request()->routeIs('review.*', 'user.*') ? 'true' : 'false' }}"
                          aria-controls="reviewCollapse">
                          <i data-feather="star" class="nav-icon me-2 icon-xxs"></i>
                          Review Management
                      </a>

                      <div id="reviewCollapse"
                          class="collapse {{ request()->routeIs('review.*', 'user.*') ? 'show' : '' }}"
                          data-bs-parent="#sidebarMenu">

                          <ul class="nav flex-column ms-3">

                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('review.index') ? 'active' : '' }}"
                                      href="">
                                      Admin Review List
                                  </a>
                              </li>


                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('user.create.review') ? 'active' : '' }}"
                                      href="">
                                      User Review List
                                  </a>
                              </li>
                          </ul>
                      </div>
                  </li> --}}


                  {{-- <li class="nav-item">
                      <a class="nav-link" href="">
                          <i data-feather="shopping-cart" class="nav-icon me-2 icon-xxs"></i>
                          Package
                      </a>
                  </li> --}}


                  {{-- <li class="nav-item">
                      <a class="nav-link" href="{{ route('get.newsletter.index') }}">
                          <i data-feather="mail" class="nav-icon me-2 icon-xxs"></i>
                          Newsletter
                      </a>
                  </li> --}}

                  <li class="nav-item">
                      <a class="nav-link" href="{{ route('dynamic-pages.index') }}">
                          <i class="bi bi-file-earmark-text fs-4 me-2"></i>
                          Dynamic Pages
                      </a>
                  </li>

                  {{-- User Management
                  <li class="nav-item {{ request()->routeIs('product.*', 'category.*') ? 'active' : '' }}">
                      <a class="nav-link has-arrow" href="#!" data-bs-toggle="collapse"
                          data-bs-target="#productCollapse"
                          aria-expanded="{{ request()->routeIs('product.*', 'category.*') ? 'true' : 'false' }}"
                          aria-controls="productCollapse">
                          <i data-feather="box" class="nav-icon me-2 icon-xxs"></i>Customer Management
                      </a>
                      <div id="productCollapse"
                          class="collapse {{ request()->routeIs('product.*', 'category.*') ? 'show' : '' }}"
                          data-bs-parent="#productCollapse">
                          <ul class="nav flex-column ms-3">
                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('product.index') ? 'active' : '' }}"
                                      href="">
                                      Customer List
                                  </a>
                              </li>

                          </ul>
                      </div>
                  </li> --}}

                  {{-- Role Management --}}
                  <li class="nav-item {{ request()->routeIs('user.*', 'role.*', 'permission.*') ? 'active' : '' }}">
                      <a class="nav-link has-arrow" data-bs-toggle="collapse" data-bs-target="#roleManagementCollapse"
                          aria-expanded="{{ request()->routeIs('users.*', 'roles.*', 'permissions.*') ? 'true' : 'false' }}"
                          aria-controls="roleManagementCollapse">
                          <i data-feather="shield" class="nav-icon me-2 icon-xxs"></i>
                          Role Management
                      </a>

                      <div id="roleManagementCollapse"
                          class="collapse {{ request()->routeIs('user.*', 'role.*', 'permission.*') ? 'show' : '' }}"
                          data-bs-parent="#sidebarMenu">

                          <ul class="nav flex-column ms-3">
                              {{-- Users --}}
                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('user.*') ? 'active' : '' }}"
                                      href="{{ route('user.index') }}">
                                      Users
                                  </a>
                              </li>

                              {{-- Roles --}}
                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('role.*') ? 'active' : '' }}"
                                      href="{{ route('role.index') }}">
                                      Roles
                                  </a>
                              </li>

                              {{-- Permissions --}}
                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('permission.*') ? 'active' : '' }}"
                                      href="{{ route('permission.index') }}">
                                      Permissions
                                  </a>
                              </li>
                          </ul>
                      </div>
                  </li>




                  {{-- Settings --}}
                  <li
                      class="nav-item {{ request()->routeIs('profile.*', 'mail.*', 'system.*', 'admin.*') ? 'active' : '' }}">
                      <a class="nav-link has-arrow" href="" data-bs-toggle="collapse"
                          data-bs-target="#settingsCollapse"
                          aria-expanded="{{ request()->routeIs('profile.*', 'mail.*', 'system.*', 'admin.*') ? 'true' : 'false' }}"
                          aria-controls="settingsCollapse">
                          <i data-feather="settings" class="nav-icon me-2 icon-xxs"></i>Settings
                      </a>

                      <div id="settingsCollapse"
                          class="collapse {{ request()->routeIs('profile.*', 'mail.*', 'system.*', 'admin.*') ? 'show' : '' }}"
                          data-bs-parent="#sidebarMenu">
                          <ul class="nav flex-column ms-3">
                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('profile.index') ? 'active' : '' }}"
                                      href="{{ route('profile.index') }}">
                                      Profile Setting
                                  </a>
                              </li>
                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('system.index') ? 'active' : '' }}"
                                      href="{{ route('system.index') }}">
                                      Website Setting
                                  </a>
                              </li>
                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('admin.setting.index') ? 'active' : '' }}"
                                      href="{{ route('admin.setting.index') }}">
                                      Admin Setting
                                  </a>
                              </li>
                              <li class="nav-item">
                                  <a class="nav-link {{ request()->routeIs('mail.index') ? 'active' : '' }}"
                                      href="{{ route('mail.index') }}">
                                      Mail Setting
                                  </a>
                              </li>
                          </ul>
                      </div>
                  </li>

                  {{-- Logout --}}
                  <li class="nav-item">
                      <a class="nav-link" href="#"
                          onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                          <i data-feather="log-out" class="nav-icon me-2 icon-xxs"></i>
                          Logout
                      </a>
                      <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                          @csrf
                      </form>
                  </li>
              </ul>

          </div>
      </div>
  </div>
