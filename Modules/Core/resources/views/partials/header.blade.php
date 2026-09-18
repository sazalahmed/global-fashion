<!-- ============================================================
     HEADER
     ============================================================ -->
<header class="bp-header">
  <div class="bp-header-left">
    <button class="bp-toggle-btn" id="sidebarToggle">
      <i class="fa-solid fa-bars"></i>
    </button>
    <!-- Mobile search button -->
    <button class="bp-header-icon bp-mobile-search-btn" id="mobileSearchBtn" title="Search">
      <i class="fa-solid fa-magnifying-glass"></i>
    </button>

    <!-- Desktop global search -->
    <div class="bp-global-search d-none d-lg-block" id="globalSearchContainer"
         data-search-url="{{ route('search') }}"
         data-full-search-url="{{ route('search.full') }}"
         data-results-url="{{ route('search.results') }}">
      <div class="bp-global-search-input-wrapper">
        <i class="fa-solid fa-magnifying-glass bp-global-search-icon"></i>
        <input type="text"
               id="globalSearchInput"
               class="bp-global-search-input"
               placeholder="Search anything..."
               autocomplete="off"
               spellcheck="false">
        <span class="bp-global-search-shortcut">Ctrl+K</span>
        <button class="bp-global-search-clear" id="globalSearchClear" type="button">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <div class="bp-global-search-dropdown" id="globalSearchDropdown"></div>
    </div>
  </div>

  <div class="bp-header-right">
    <!-- Quick Actions -->
    <div class="dropdown bp-quick-actions">
      <button class="bp-btn bp-btn-primary bp-btn-sm bp-quick-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-solid fa-bolt"></i>
        <span class="d-none d-sm-inline ms-1">{{ __('Quick Action') }}</span>
        <i class="fa-solid fa-chevron-down fs-11 ms-1"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="{{ route('sales.create') }}"><i class="fa-solid fa-cash-register"></i> {{ __('New Sale') }}</a></li>
        <li><a class="dropdown-item" href="{{ route('products.create') }}"><i class="fa-solid fa-plus"></i> {{ __('Add Product') }}</a></li>
        <li><a class="dropdown-item" href="{{ route('purchases.create') }}"><i class="fa-solid fa-cart-plus"></i> {{ __('New Purchase') }}</a></li>
        <li><a class="dropdown-item" href="{{ route('customers.create') }}"><i class="fa-solid fa-user-plus"></i> {{ __('Add Customer') }}</a></li>
        <li><a class="dropdown-item" href="{{ route('expenses.create') }}"><i class="fa-solid fa-money-bill-wave"></i> {{ __('Add Expense') }}</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="{{ route('reports.index') }}"><i class="fa-solid fa-file-lines"></i> {{ __('View Reports') }}</a></li>
      </ul>
    </div>

    <!-- Visit Storefront -->
    <a href="{{ route('storefront.home') }}" target="_blank" rel="noopener" class="bp-header-icon" title="{{ __('Visit Storefront') }}">
      <i class="fa-solid fa-store"></i>
    </a>

    <!-- Dark Mode -->
    <button class="bp-header-icon" id="darkModeToggle" title="Toggle Dark Mode">
      <i class="fa-solid fa-moon"></i>
    </button>

    <!-- Fullscreen -->
    <button class="bp-header-icon d-none d-md-flex" id="fullscreenToggle" title="Fullscreen">
      <i class="fa-solid fa-expand"></i>
    </button>

    <!-- Notifications -->
    <div class="dropdown"
         id="notificationWrap"
         data-list-url="{{ route('notifications.index') }}"
         data-mark-all-url="{{ route('notifications.markAllRead') }}"
         data-mark-read-url="{{ route('notifications.read', ['id' => '__ID__']) }}">
      <div data-bs-toggle="dropdown">
        <div class="bp-header-icon" id="notificationBtn" title="Notifications">
          <i class="fa-solid fa-bell"></i>
          <span class="badge-count" id="notificationCount" style="display:none">0</span>
        </div>
      </div>
      <div class="dropdown-menu dropdown-menu-end p-0 bp-notification-list" id="notificationDropdown">
        <div class="bp-notif-header">
          <strong class="fs-14">Notifications</strong>
          <a href="javascript:void(0)" class="fs-12 text-primary" id="markAllRead">Mark all read</a>
        </div>
        @superAdmin
        <button type="button" id="pushToggle" class="bp-push-toggle" data-state="disabled" title="Enable browser notifications">
          <span class="bp-push-icon"><i class="fa-solid fa-bell-slash"></i></span>
          <span class="bp-push-label fs-12 fw-600">Enable notifications</span>
        </button>
        @endsuperAdmin
        <div class="bp-notif-body" id="notificationItems">
          <div class="bp-notif-empty">
            <i class="fa-solid fa-bell-slash"></i>
            <div class="fs-13">No notifications yet</div>
          </div>
        </div>
        <div class="bp-notif-footer">
          <a href="{{ route('notifications.index') }}" class="fs-12 fw-600 text-primary">View All Notifications</a>
        </div>
      </div>
    </div>

    <!-- User -->
    <div class="bp-user-dropdown dropdown">
      <div data-bs-toggle="dropdown">
        <div class="d-flex align-items-center gap-2">
          <div class="bp-user-avatar">
            @if(auth()->user()->image)
              <img src="{{ upload_url(auth()->user()->image) }}" alt="{{ auth()->user()->name }}">
            @else
              {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}{{ strtoupper(substr(explode(' ', auth()->user()->name ?? 'Admin')[1] ?? '', 0, 1)) }}
            @endif
          </div>
          <div class="bp-user-info d-none d-lg-block">
            <div class="user-name">{{ auth()->user()->name ?? 'Admin' }}</div>
            <div class="user-role">{{ auth()->user()->roles->first()->name ?? 'User' }}</div>
          </div>
          <i class="fa-solid fa-chevron-down fs-11 text-muted d-none d-lg-block"></i>
        </div>
      </div>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="{{ route('security.users.show', auth()->id()) }}"><i class="fa-solid fa-user"></i> My Profile</a></li>
        <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="fa-solid fa-gear"></i> Settings</a></li>
        <li><a class="dropdown-item" href="{{ route('security.change-password') }}"><i class="fa-solid fa-key"></i> Change Password</a></li>
        <li><a class="dropdown-item" href="{{ route('security.two-factor') }}"><i class="fa-solid fa-mobile-screen-button"></i> {{ __('Two-Factor Authentication') }}</a></li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <form method="POST" action="{{ route('logout') }}" id="logoutForm">
            @csrf
            <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
              <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
          </form>
        </li>
      </ul>
    </div>
  </div>
</header>
