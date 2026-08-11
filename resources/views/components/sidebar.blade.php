<style>
  #sidebar-container {
    width: 224px;
    flex-shrink: 0;
    transition: width 0.2s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  /* Desktop Collapse rules */
  @media (min-width: 768px) {
    #sidebar-container.collapsed {
      width: 56px;
    }
    #sidebar-container.collapsed .sidebar-text,
    #sidebar-container.collapsed .sidebar-header-title,
    #sidebar-container.collapsed .sidebar-category-label,
    #sidebar-container.collapsed .logout-text {
      display: none !important;
    }
    #sidebar-container.collapsed .nav-item-wrapper {
      justify-content: center;
    }
    #sidebar-container.collapsed .nav-link-custom {
      justify-content: center;
      padding: 0.35rem 0 !important;
    }
    #sidebar-container.collapsed .nav-link-custom i {
      margin-right: 0 !important;
      font-size: 1.05rem;
    }
    #sidebar-container.collapsed .sidebar-category-label-wrapper {
      display: flex;
      justify-content: center;
    }
    #sidebar-container.collapsed .sidebar-category-divider {
      width: 20px;
      height: 1px;
      background-color: #334155;
      margin: 0.35rem 0;
    }
  }
  
  /* Sidebar Scrollbar Customization */
  #sidebar-container *::-webkit-scrollbar {
    width: 4px;
    height: 4px;
  }
  #sidebar-container *::-webkit-scrollbar-track {
    background: #020617 !important; /* Matches bg-slate-950 */
  }
  #sidebar-container *::-webkit-scrollbar-thumb {
    background: #1e293b !important; /* Lighter slate-800 */
    border-radius: 9999px !important;
  }
  #sidebar-container *::-webkit-scrollbar-thumb:hover {
    background: #334155 !important; /* Slate-700 on hover */
  }

  /* Mobile offcanvas rules */
  @media (max-width: 767.98px) {
    #sidebar-container {
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      height: 100vh;
      transform: translateX(-100%);
      z-index: 1050;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
    }
    #sidebar-container.mobile-open {
      transform: translateX(0);
    }
  }


  
  .sidebar-category-label {
    font-size: 0.675rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    padding-left: 0.75rem;
    margin-top: 0.65rem;
    margin-bottom: 0.2rem;
  }
  .nav-link-custom {
    display: flex;
    align-items: center;
    padding: 0.3rem 0.65rem;
    color: #94a3b8;
    font-size: 0.775rem;
    border-radius: 0.375rem;
    text-decoration: none;
    transition: all 0.15s ease-in-out;
    margin-bottom: 0.125rem;
  }
  .nav-link-custom:hover {
    background-color: #1e293b;
    color: #f8fafc;
  }
  .nav-link-custom.active {
    background-color: #ffffff;
    color: #0f172a;
    font-weight: 600;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }
</style>

<aside id="sidebar-container" class="bg-slate-950 text-slate-200 flex flex-col h-screen z-40 sticky top-0 overflow-hidden">
  <!-- Brand logo/Header area -->
  <div class="h-12 border-b border-slate-800 flex items-center justify-between px-3 flex-shrink-0">
    <div class="flex items-center gap-2 overflow-hidden">
      <img src="{{ asset('storage/4ps-logo.png') }}" alt="4Ps Logo" style="width: 24px; height: 24px;" class="flex-shrink-0">
      <span class="sidebar-header-title font-semibold text-sm tracking-wider text-white whitespace-nowrap">PANTAWID AFS</span>
    </div>
    <!-- Mobile close button -->
    <button class="btn btn-outline-secondary p-1 border-0 d-md-none text-slate-400 hover:text-white" type="button" id="sidebarCloseBtn" aria-label="Close Sidebar">
      <i class="bi bi-x-lg fs-6"></i>
    </button>
  </div>


  <!-- Navigation menu -->
  <div class="flex-1 overflow-y-auto px-2 py-3">
    <!-- Dashboard Section -->
    <div>
      <div class="sidebar-category-label-wrapper">
        <div class="sidebar-category-label sidebar-text">Dashboard</div>
        <div class="sidebar-category-divider d-none"></div>
      </div>
      <div class="flex flex-col">
        @if(Auth::user()->hasSidebarAccess('dashboard'))
        <a class="nav-link-custom @if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}" title="Dashboard">
          <i class="bi bi-speedometer2 me-2"></i>
          <span class="sidebar-text">Dashboard</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('tracking-dashboard.index'))
        <a class="nav-link-custom @if(request()->routeIs('tracking-dashboard.*') && !request()->boolean('report')) active @endif" href="{{ route('tracking-dashboard.index') }}" title="{{ __('tracking_dashboard.title') }}">
          <i class="bi bi-diagram-3 me-2"></i>
          <span class="sidebar-text">{{ __('tracking_dashboard.title') }}</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('low-stock.index'))
        <a class="nav-link-custom @if(request()->routeIs('low-stock.*')) active @endif" href="{{ route('low-stock.index') }}" title="Low Stock Alert">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <span class="sidebar-text">Low Stock Alert</span>
        </a>
        @endif
      </div>
    </div>

    <!-- Transactions Section -->
    <div>
      <div class="sidebar-category-label-wrapper">
        <div class="sidebar-category-label sidebar-text">Transactions</div>
        <div class="sidebar-category-divider d-none"></div>
      </div>
      <div class="flex flex-col">
        @if(Auth::user()->hasSidebarAccess('stock-in.index'))
        <a class="nav-link-custom @if(request()->routeIs('stock-in.*')) active @endif" href="{{ route('stock-in.index') }}" title="Stock In (Receiving)">
          <i class="bi bi-box-arrow-in-down me-2"></i>
          <span class="sidebar-text">Stock In (Receiving)</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('stock-out.index'))
        <a class="nav-link-custom @if(request()->routeIs('stock-out.*')) active @endif" href="{{ route('stock-out.index') }}" title="Stock Out (Issuance)">
          <i class="bi bi-box-arrow-up me-2"></i>
          <span class="sidebar-text">Stock Out (Issuance)</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('borrowings.index'))
        <a class="nav-link-custom @if(request()->routeIs('borrowings.*')) active @endif" href="{{ route('borrowings.index') }}" title="Borrow Item">
          <i class="bi bi-person-up me-2"></i>
          <span class="sidebar-text">Borrow Item</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('damaged-items.index'))
        <a class="nav-link-custom @if(request()->routeIs('damaged-items.*')) active @endif" href="{{ route('damaged-items.index') }}" title="Unserviceable Items">
          <i class="bi bi-heartbreak me-2"></i>
          <span class="sidebar-text">Unserviceable Items</span>
        </a>
        @endif
      </div>
    </div>

    <!-- Document Tracking Section -->
    <div>
      <div class="sidebar-category-label-wrapper">
        <div class="sidebar-category-label sidebar-text">Document Tracking</div>
        <div class="sidebar-category-divider d-none"></div>
      </div>
      <div class="flex flex-col">
        @if(Auth::user()->hasSidebarAccess('inbox.index'))
        <a class="nav-link-custom @if(request()->routeIs('inbox.index')) active @endif" href="{{ route('inbox.index') }}" title="Inbox">
          <i class="bi bi-envelope-arrow-down me-2"></i>
          <span class="sidebar-text">Inbox</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('incoming-documents.index'))
        <a class="nav-link-custom @if(request()->routeIs('incoming-documents.*') && !request()->routeIs('incoming-documents.monthly-report')) active @endif" href="{{ route('incoming-documents.index') }}" title="Tracking">
          <i class="bi bi-arrow-90deg-down me-2"></i>
          <span class="sidebar-text">Tracking</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('inbox.batch'))
        <a class="nav-link-custom @if(request()->routeIs('inbox.batch*')) active @endif" href="{{ route('inbox.batch') }}" title="Route Slip">
          <i class="bi bi-journals me-2"></i>
          <span class="sidebar-text">Route Slip</span>
        </a>
        @endif
      </div>
    </div>

    <!-- Reports Section -->
    <div>
      <div class="sidebar-category-label-wrapper">
        <div class="sidebar-category-label sidebar-text">Reports</div>
        <div class="sidebar-category-divider d-none"></div>
      </div>
      <div class="flex flex-col">
        @if(Auth::user()->hasSidebarAccess('incoming-documents.index'))
        <a class="nav-link-custom @if(request()->routeIs('monthly-transactions.*')) active @endif" href="{{ route('monthly-transactions.index') }}" title="Monthly Transactions">
          <i class="bi bi-calendar3 me-2"></i>
          <span class="sidebar-text">Monthly Transactions</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('tracking-dashboard.index'))
        <a class="nav-link-custom @if(request()->routeIs('tracking-dashboard.*') && request()->boolean('report')) active @endif" href="{{ route('tracking-dashboard.index', ['report' => 1]) }}" title="Tracking Reports">
          <i class="bi bi-repeat me-2"></i>
          <span class="sidebar-text">Tracking Reports</span>
        </a>
        @endif
      </div>
    </div>

    <!-- Libraries Section -->
    <div>
      <div class="sidebar-category-label-wrapper">
        <div class="sidebar-category-label sidebar-text">Libraries</div>
        <div class="sidebar-category-divider d-none"></div>
      </div>
      <div class="flex flex-col">
        @if(Auth::user()->hasSidebarAccess('items.index'))
        <a class="nav-link-custom @if(request()->routeIs('items.create') || request()->routeIs('items.index')) active @endif" href="{{ route('items.index') }}" title="Stock Items">
          <i class="bi bi-collection me-2"></i>
          <span class="sidebar-text">Stock Items</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('categories.index'))
        <a class="nav-link-custom @if(request()->routeIs('categories.*')) active @endif" href="{{ route('categories.index') }}" title="Categories">
          <i class="bi bi-tags me-2"></i>
          <span class="sidebar-text">Categories</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('unit_of_measures.index'))
        <a class="nav-link-custom @if(request()->routeIs('unit_of_measures.*')) active @endif" href="{{ route('unit_of_measures.index') }}" title="Units of Measure">
          <i class="bi bi-rulers me-2"></i>
          <span class="sidebar-text">Units of Measure</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('groups.index'))
        <a class="nav-link-custom @if(request()->routeIs('groups.*')) active @endif" href="{{ route('groups.index') }}" title="Group Section">
          <i class="bi bi-people me-2"></i>
          <span class="sidebar-text">Group Section</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('document-sources.index'))
        <a class="nav-link-custom @if(request()->routeIs('document-sources.*')) active @endif" href="{{ route('document-sources.index') }}" title="Document Sources">
          <i class="bi bi-diagram-3 me-2"></i>
          <span class="sidebar-text">Document Sources</span>
        </a>
        @endif
        @if(Auth::user()->hasSidebarAccess('document-types.index'))
        <a class="nav-link-custom @if(request()->routeIs('document-types.*')) active @endif" href="{{ route('document-types.index') }}" title="Document Types">
          <i class="bi bi-card-checklist me-2"></i>
          <span class="sidebar-text">Document Types</span>
        </a>
        @endif
      </div>
    </div>

    <!-- Account Section -->
    <div>
      <div class="sidebar-category-label-wrapper">
        <div class="sidebar-category-label sidebar-text">Account</div>
        <div class="sidebar-category-divider d-none"></div>
      </div>
      <div class="flex flex-col">
        @if(Auth::user()->hasSidebarAccess('users.index'))
        <a class="nav-link-custom @if(request()->routeIs('users.*')) active @endif" href="{{ route('users.index') }}" title="User Management">
          <i class="bi bi-person-fill-gear me-2"></i>
          <span class="sidebar-text">User Management</span>
        </a>
        @endif
        @if((int) Auth::user()->level_id === 1)
        <a class="nav-link-custom @if(request()->routeIs('access.*')) active @endif" href="{{ route('access.index') }}" title="Access">
          <i class="bi bi-list-check me-2"></i>
          <span class="sidebar-text">Access</span>
        </a>
        @endif
        <a class="nav-link-custom @if(request()->routeIs('profile.*')) active @endif" href="{{ route('profile.edit') }}" title="Profile">
          <i class="bi bi-person me-2"></i>
          <span class="sidebar-text">Profile</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Logout/Bottom section -->
  <div class="p-2 border-t border-slate-800 flex-shrink-0">
    <form action="{{ route('logout') }}" method="POST" class="w-100 m-0">
      @csrf
      <button type="submit" class="nav-link-custom border-0 w-full text-left bg-transparent p-2" title="Logout">
        <i class="bi bi-box-arrow-right me-2 text-danger"></i>
        <span class="logout-text text-danger font-semibold">Logout</span>
      </button>
    </form>
  </div>
</aside>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const sidebarContainer = document.getElementById('sidebar-container');
    if (!sidebarContainer) return;
    
    const links = sidebarContainer.querySelectorAll('.nav-link-custom');
    const ACTIVE_KEY = 'activeMenuLabel';

    function setActiveByLabel(label) {
      links.forEach(a => a.classList.remove('active'));
      const target = Array.from(links).find(a => {
        const textSpan = a.querySelector('.sidebar-text');
        return textSpan && textSpan.textContent.trim() === label;
      });
      if (target) target.classList.add('active');
    }

    const currentPath = window.location.pathname;
    if (currentPath === '/dashboard' || currentPath.startsWith('/dashboard')) {
      localStorage.setItem(ACTIVE_KEY, 'Dashboard');
    }

    const stored = localStorage.getItem(ACTIVE_KEY);
    const serverActive = sidebarContainer.querySelector('.nav-link-custom.active');
    if (serverActive) {
      const textSpan = serverActive.querySelector('.sidebar-text');
      if (textSpan) {
        localStorage.setItem(ACTIVE_KEY, textSpan.textContent.trim());
      }
    } else if (stored) {
      setActiveByLabel(stored);
    }

    sidebarContainer.addEventListener('click', function (e) {
      const anchor = e.target.closest('.nav-link-custom');
      if (!anchor) return;
      const textSpan = anchor.querySelector('.sidebar-text');
      if (textSpan) {
        localStorage.setItem(ACTIVE_KEY, textSpan.textContent.trim());
      }
    });

    // Handle collapsed display for headers/dividers
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'class') {
          const isCollapsed = sidebarContainer.classList.contains('collapsed');
          const dividers = sidebarContainer.querySelectorAll('.sidebar-category-divider');
          dividers.forEach(div => {
            if (isCollapsed) {
              div.classList.remove('d-none');
            } else {
              div.classList.add('d-none');
            }
          });
        }
      });
    });

    observer.observe(sidebarContainer, { attributes: true });
    
    // Initial check
    if (sidebarContainer.classList.contains('collapsed')) {
      const dividers = sidebarContainer.querySelectorAll('.sidebar-category-divider');
      dividers.forEach(div => div.classList.remove('d-none'));
    }
  });
</script>
