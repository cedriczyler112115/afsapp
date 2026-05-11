<style>
  .offcanvas-fullscreen {
    width: 100vw;
    max-width: 100vw;
    height: 100vh;
  }
  .offcanvas-body .nav-link {
    border-radius: .375rem;
    transition: background-color .15s ease-in-out, color .15s ease-in-out;
  }
  .offcanvas-body .nav-link:hover {
    background-color: rgba(255,255,255,.10);
    color: #fff;
  }
  .offcanvas-body .nav-link.active {
    background-color: rgba(255,255,255,.20);
    color: #fff;
    font-weight: 600;
  }
</style>
<div class="offcanvas offcanvas-start text-bg-dark" style="width: 280px" tabindex="-1" id="sideMenu" aria-labelledby="sideMenuLabel" data-bs-scroll="true">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="sideMenuLabel">Menu</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <div class="mb-3">
      <div class="text-secondary text-uppercase small mb-2"><i class="bi bi-speedometer2 me-2"></i>Dashboard</div>
      <ul class="nav flex-column">
        @if(Auth::user()->hasSidebarAccess('dashboard'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('tracking-dashboard.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('tracking-dashboard.*')) active @endif" href="{{ route('tracking-dashboard.index') }}"><i class="bi bi-diagram-3 me-2"></i>{{ __('tracking_dashboard.title') }}</a></li>
        @endif
        {{-- <li class="nav-item"><a class="nav-link text-white" href="#"><i class="bi bi-list-check me-2"></i>Stock Summary</a></li> --}}
        @if(Auth::user()->hasSidebarAccess('low-stock.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('low-stock.*')) active @endif" href="{{ route('low-stock.index') }}"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alert</a></li>
        @endif

      </ul>
    </div>
    <div class="mb-3">
      <div class="text-secondary text-uppercase small mb-2"><i class="bi bi-repeat me-2"></i>Transactions</div>
      <ul class="nav flex-column">
        @if(Auth::user()->hasSidebarAccess('stock-in.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('stock-in.*')) active @endif" href="{{ route('stock-in.index') }}"><i class="bi bi-box-arrow-in-down me-2"></i>Stock In (Receiving)</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('stock-out.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('stock-out.*')) active @endif" href="{{ route('stock-out.index') }}"><i class="bi bi-box-arrow-up me-2"></i>Stock Out (Issuance)</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('borrowings.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('borrowings.*')) active @endif" href="{{ route('borrowings.index') }}"><i class="bi bi-person-up me-2"></i>Borrow Item</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('damaged-items.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('damaged-items.*')) active @endif" href="{{ route('damaged-items.index') }}"><i class="bi bi-heartbreak me-2"></i>Unserviceable Items </a></li>
        @endif
        {{-- <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('returns.*')) active @endif" href="{{ route('returns.index') }}"><i class="bi bi-arrow-counterclockwise me-2"></i>Returns</a></li> --}}
      </ul>
    </div>
        <div class="mb-3">
      <div class="text-secondary text-uppercase small mb-2"><i class="bi bi-file-earmark-richtext"></i> Document Tracking</div>
      <ul class="nav flex-column">
        @if(Auth::user()->hasSidebarAccess('inbox.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('inbox.index')) active @endif" href="{{ route('inbox.index') }}"><i class="bi bi-envelope-arrow-down"></i> &nbsp;Inbox</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('incoming-documents.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('incoming-documents.*')) active @endif" href="{{ route('incoming-documents.index') }}"><i class="bi bi-arrow-90deg-down me-2"></i>Tracking</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('inbox.batch'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('inbox.batch*')) active @endif" href="{{ route('inbox.batch') }}"><i class="bi bi-journals"></i> &nbsp;Route Slip</a></li>
        @endif
        {{-- <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('returns.*')) active @endif" href="{{ route('returns.index') }}"><i class="bi bi-arrow-counterclockwise me-2"></i>Returns</a></li> --}}
      </ul>
    </div>
        <div class="mb-3">
      <div class="text-secondary text-uppercase small mb-2"><i class="bi bi-bar-chart me-2"></i>Reports</div>
      <ul class="nav flex-column">
        @if(Auth::user()->hasSidebarAccess('stock-in.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('stock-in.*')) active @endif" href="{{ route('stock-in.index') }}"><i class="bi bi-calendar3 me-2"></i>Monthly Transactions</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('borrowings.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('borrowings.*')) active @endif" href="{{ route('borrowings.index') }}"><i class="bi bi-repeat me-2"></i>Tracking Reports</a></li>
        @endif
      </ul>
    </div>

    {{-- <div class="mb-3">
      <div class="text-secondary text-uppercase small mb-2"><i class="bi bi-box-seam me-2"></i>Inventory</div>
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('items.create')) active @endif" href="{{ route('items.index') }}"><i class="bi bi-collection me-2"></i>All Stock Items</a></li> --}}
        {{-- <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('categories.*')) active @endif" href="{{ route('categories.index') }}"><i class="bi bi-tags me-2"></i>Categories</a></li>
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('unit_of_measures.*')) active @endif" href="{{ route('unit_of_measures.index') }}"><i class="bi bi-rulers me-2"></i>Units of Measure</a></li> --}}
        {{-- <li class="nav-item"><a class="nav-link text-white" href="#"><i class="bi bi-arrow-left-right me-2"></i>Item Adjustments</a></li> --}}
      {{-- </ul>
    </div> --}}
    <div class="mb-3">
      <div class="text-secondary text-uppercase small mb-2"><i class="bi bi-box-seam me-2"></i>Libraries</div>
      <ul class="nav flex-column">
        @if(Auth::user()->hasSidebarAccess('items.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('items.create') || request()->routeIs('items.index')) active @endif" href="{{ route('items.index') }}"><i class="bi bi-collection me-2"></i>Stock Items</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('categories.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('categories.*')) active @endif" href="{{ route('categories.index') }}"><i class="bi bi-tags me-2"></i>Categories</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('unit_of_measures.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('unit_of_measures.*')) active @endif" href="{{ route('unit_of_measures.index') }}"><i class="bi bi-rulers me-2"></i>Units of Measure</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('groups.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('groups.*')) active @endif" href="{{ route('groups.index') }}"><i class="bi bi-people me-2"></i>Group Section</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('document-sources.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('document-sources.*')) active @endif" href="{{ route('document-sources.index') }}"><i class="bi bi-diagram-3 me-2"></i>Document Sources</a></li>
        @endif
        @if(Auth::user()->hasSidebarAccess('document-types.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('document-types.*')) active @endif" href="{{ route('document-types.index') }}"><i class="bi bi-card-checklist me-2"></i>Document Types</a></li>
        @endif
        {{-- <li class="nav-item"><a class="nav-link text-white" href="#"><i class="bi bi-arrow-left-right me-2"></i>Item Adjustments</a></li> --}}
      </ul>
    </div>
    {{-- <div class="mb-3">
      <div class="text-secondary text-uppercase small mb-2"><i class="bi bi-check2-circle me-2"></i>Requests & Approvals</div>
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link text-white" href="#"><i class="bi bi-file-text me-2"></i>Item Requests</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#"><i class="bi bi-check2-square me-2"></i>Approval Queue</a></li>
      </ul>
    </div> --}}
    <div class="mb-3">
      <div class="text-secondary text-uppercase small mb-2"><i class="bi bi-people me-2"></i>Account</div>
      <ul class="nav flex-column">
        @if(Auth::user()->hasSidebarAccess('users.index'))
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('users.*')) active @endif" href="{{ route('users.index') }}"><i class="bi bi-person-fill-gear me-2"></i>User Management</a></li>
        @endif
        @if((int) Auth::user()->level_id === 1)
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('access.*')) active @endif" href="{{ route('access.index') }}"><i class="bi bi-list-check me-2"></i>Access</a></li>
        @endif
        <li class="nav-item"><a class="nav-link text-white @if(request()->routeIs('profile.*')) active @endif" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
      </ul>
    </div>
    <div class="mt-4">
      <form action="{{ route('logout') }}" method="POST" class="d-inline">
        @csrf
        <button class="btn btn-danger w-100" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
      </form>
    </div>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const offcanvasBody = document.querySelector('#sideMenu .offcanvas-body');
    if (!offcanvasBody) return;
    const links = offcanvasBody.querySelectorAll('.nav-link');
    const ACTIVE_KEY = 'activeMenuLabel';
    function setActiveByLabel(label) {
      links.forEach(a => a.classList.remove('active'));
      const target = Array.from(links).find(a => a.textContent.trim() === label);
      if (target) target.classList.add('active');
    }
    const currentPath = window.location.pathname;
    if (currentPath === '/dashboard' || currentPath.startsWith('/dashboard')) {
      localStorage.setItem(ACTIVE_KEY, 'Dashboard');
    }
    const stored = localStorage.getItem(ACTIVE_KEY);
    // Check if server rendered an active link
    const serverActive = offcanvasBody.querySelector('.nav-link.active');
    if (serverActive) {
      // Update storage to match server state
      localStorage.setItem(ACTIVE_KEY, serverActive.textContent.trim());
    } else if (stored) {
      setActiveByLabel(stored);
    }
    offcanvasBody.addEventListener('click', function (e) {
      const anchor = e.target.closest('.nav-link');
      if (!anchor) return;
      const label = anchor.textContent.trim();
      localStorage.setItem(ACTIVE_KEY, label);
      setActiveByLabel(label);
    });
  });
</script>
