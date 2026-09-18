<!-- ============================================================
     SIDEBAR — ordered by frequency of use in a typical retail shop.
     Workspace (daily) → Catalog → Purchasing → Money → Online →
     Reports → Marketing → HR → Manufacturing (collapsed) → System.
     ============================================================ -->
@php($isSimpleMode = \Modules\Setting\Services\SettingService::isSimpleMode())
<aside class="bp-sidebar" id="sidebar">
  <div class="bp-sidebar-brand">
    @if(!empty($companyLogo))
      <a href="{{ route('dashboard') }}" class="bp-sidebar-logo">
        <img src="{{ $companyLogo }}" alt="{{ $companyName }}">
      </a>
    @else
      <h4><span class="brand-text">{{ $companyName }}</span></h4>
    @endif
  </div>
  <ul class="bp-sidebar-menu">

    <!-- ==================== WORKSPACE ==================== -->
    <li class="menu-header">Workspace</li>
    <li class="menu-item">
      <a href="{{ route('dashboard') }}" class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
        <i class="fa-solid fa-gauge-high"></i>
        <span class="menu-text">Dashboard</span>
      </a>
    </li>
    @bpCanAny('sales.view','sales.create','quotations.view')
    <li class="menu-item {{ request()->routeIs('sales.*', 'quotations.*', 'sale-returns.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('sales.*', 'quotations.*', 'sale-returns.*') ? 'active' : '' }}" data-toggle="submenu" title="Sales">
        <i class="fa-solid fa-chart-line"></i>
        <span class="menu-text">Sales</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('sales.view')
        <li><a href="{{ route('sales.index') }}" class="menu-link {{ request()->routeIs('sales.index', 'sales.show', 'sales.edit') ? 'active' : '' }}">Sales List</a></li>
        @endbpCan
        @bpCan('sales.create')
        <li><a href="{{ route('sales.create') }}" class="menu-link {{ request()->routeIs('sales.create') ? 'active' : '' }}">Create Order</a></li>
        @endbpCan
        @bpCan('quotations.view')
        <li><a href="{{ route('quotations.index') }}" class="menu-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}">Quotations</a></li>
        @endbpCan
        @bpCan('sales.view')
        <li><a href="{{ route('sale-returns.index') }}" class="menu-link {{ request()->routeIs('sale-returns.*') ? 'active' : '' }}">Sales Returns</a></li>
        @endbpCan
      </ul>
    </li>
    @endbpCanAny
    @bpCanAny('customers.view')
    <li class="menu-item {{ request()->routeIs('customers.*', 'customer-groups.*', 'customer-areas.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('customers.*', 'customer-groups.*', 'customer-areas.*') ? 'active' : '' }}" data-toggle="submenu" title="Customers">
        <i class="fa-solid fa-users"></i>
        <span class="menu-text">Customers</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('customers.view')
        <li><a href="{{ route('customers.index') }}" class="menu-link {{ request()->routeIs('customers.index', 'customers.show', 'customers.create', 'customers.edit') ? 'active' : '' }}">Customers List</a></li>
        @endbpCan
        @bpCan('customers.view')
        <li><a href="{{ route('customers.due-receive') }}" class="menu-link {{ request()->routeIs('customers.due-receive') ? 'active' : '' }}">Due List</a></li>
        @endbpCan
        @bpCan('customers.view')
        <li><a href="{{ route('customers.ledger') }}" class="menu-link {{ request()->routeIs('customers.ledger') ? 'active' : '' }}">Customer Ledger</a></li>
        @endbpCan
        @bpCan('customers.view')
        <li><a href="{{ route('customers.advances') }}" class="menu-link {{ request()->routeIs('customers.advances') ? 'active' : '' }}">Advances</a></li>
        @endbpCan
        @bpCan('customers.view')
        <li><a href="{{ route('customer-groups.index') }}" class="menu-link {{ request()->routeIs('customer-groups.*') ? 'active' : '' }}">Customer Groups</a></li>
        @endbpCan
        @bpCan('customers.view')
        <li><a href="{{ route('customer-areas.index') }}" class="menu-link {{ request()->routeIs('customer-areas.*') ? 'active' : '' }}">Areas</a></li>
        @endbpCan
      </ul>
    </li>
    @endbpCanAny

    <!-- ==================== CATALOG ==================== -->
    @bpCanAny('products.view','categories.view','brands.view','units.view','variants.view','barcode.view','inventory.view')
    <li class="menu-header">Catalog</li>
    @endbpCanAny
    @bpCanAny('products.view','categories.view','brands.view','units.view','variants.view','barcode.view','ecommerce.view')
    <li class="menu-item {{ request()->routeIs('products.*', 'categories.*', 'brands.*', 'units.*', 'variants.*', 'barcode.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('products.*', 'categories.*', 'brands.*', 'units.*', 'variants.*', 'barcode.*') ? 'active' : '' }}" data-toggle="submenu" title="Products">
        <i class="fa-solid fa-boxes-stacked"></i>
        <span class="menu-text">Products</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('products.view')
        <li><a href="{{ route('products.index') }}" class="menu-link {{ request()->routeIs('products.index', 'products.show', 'products.edit', 'products.combos.*') ? 'active' : '' }}">Products List</a></li>
        @endbpCan
        @bpCan('products.create')
        <li><a href="{{ route('products.create') }}" class="menu-link {{ request()->routeIs('products.create') ? 'active' : '' }}">Add Product</a></li>
        @endbpCan
        @bpCan('categories.view')
        <li><a href="{{ route('categories.index') }}" class="menu-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">Categories</a></li>
        @endbpCan
        @bpCan('brands.view')
        <li><a href="{{ route('brands.index') }}" class="menu-link {{ request()->routeIs('brands.*') ? 'active' : '' }}">Brands</a></li>
        @endbpCan
        @bpCan('units.view')
        <li><a href="{{ route('units.index') }}" class="menu-link {{ request()->routeIs('units.*') ? 'active' : '' }}">Units</a></li>
        @endbpCan
        @bpCan('variants.view')
        <li><a href="{{ route('variants.index') }}" class="menu-link {{ request()->routeIs('variants.*') ? 'active' : '' }}">Variants</a></li>
        @endbpCan
        @bpCan('barcode.view')
        <li><a href="{{ route('barcode.index') }}" class="menu-link {{ request()->routeIs('barcode.*') ? 'active' : '' }}">Print Barcode</a></li>
        @endbpCan
      </ul>
    </li>
    @endbpCanAny
    @bpCanAny('inventory.view')
    <li class="menu-item {{ request()->routeIs('inventory.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" data-toggle="submenu" title="Stock">
        <i class="fa-solid fa-cubes-stacked"></i>
        <span class="menu-text">Stock</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('inventory.view')
        <li><a href="{{ route('inventory.index') }}" class="menu-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}">Overview</a></li>
        @endbpCan
        @bpCan('inventory.view')
        <li><a href="{{ route('inventory.alerts') }}" class="menu-link {{ request()->routeIs('inventory.alerts') ? 'active' : '' }}">Stock Alerts</a></li>
        @endbpCan
        @bpCan('inventory.view')
        <li><a href="{{ route('inventory.adjustments') }}" class="menu-link {{ request()->routeIs('inventory.adjustments*') ? 'active' : '' }}">Adjustments</a></li>
        @endbpCan
        @bpCan('inventory.view')
        <li><a href="{{ route('inventory.adjustment-reasons.index') }}" class="menu-link {{ request()->routeIs('inventory.adjustment-reasons.*') ? 'active' : '' }}">Adjustment Reasons</a></li>
        @endbpCan
      </ul>
    </li>
    @endbpCanAny

    <!-- ==================== PURCHASING ==================== -->
    @bpCanAny('purchases.view','suppliers.view')
    <li class="menu-header">Purchasing</li>
    @endbpCanAny
    @bpCanAny('purchases.view')
    <li class="menu-item {{ request()->routeIs('purchases.*', 'purchase-returns.*', 'requisitions.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('purchases.*', 'purchase-returns.*', 'requisitions.*') ? 'active' : '' }}" data-toggle="submenu" title="Purchases">
        <i class="fa-solid fa-cart-plus"></i>
        <span class="menu-text">Purchases</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('purchases.view')
        <li><a href="{{ route('requisitions.index') }}" class="menu-link {{ request()->routeIs('requisitions.*') ? 'active' : '' }}">Requisitions</a></li>
        @endbpCan
        @bpCan('purchases.view')
        <li><a href="{{ route('purchases.index') }}" class="menu-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">Purchase Orders</a></li>
        @endbpCan
        @bpCan('purchases.view')
        <li><a href="{{ route('purchase-returns.index') }}" class="menu-link {{ request()->routeIs('purchase-returns.*') ? 'active' : '' }}">Purchase Returns</a></li>
        @endbpCan
      </ul>
    </li>
    @endbpCanAny
    @bpCanAny('suppliers.view')
    <li class="menu-item {{ request()->routeIs('supplier.*', 'supplier-groups.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('supplier.*', 'supplier-groups.*') ? 'active' : '' }}" data-toggle="submenu" title="Suppliers">
        <i class="fa-solid fa-truck-field"></i>
        <span class="menu-text">Suppliers</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('suppliers.view')
        <li><a href="{{ route('supplier.index') }}" class="menu-link {{ request()->routeIs('supplier.index', 'supplier.show', 'supplier.create', 'supplier.edit', 'supplier.ledger') ? 'active' : '' }}">Suppliers List</a></li>
        @endbpCan
        @bpCan('suppliers.view')
        <li><a href="{{ route('supplier.payable') }}" class="menu-link {{ request()->routeIs('supplier.payable') ? 'active' : '' }}">Payable List</a></li>
        @endbpCan
        @bpCan('suppliers.view')
        <li><a href="{{ route('supplier-groups.index') }}" class="menu-link {{ request()->routeIs('supplier-groups.*') ? 'active' : '' }}">Supplier Groups</a></li>
        @endbpCan
      </ul>
    </li>
    @endbpCanAny

    <!-- ==================== MONEY / FINANCE ==================== -->
    @bpCanAny('finance.view','payments.view','accounting.view')
    <li class="menu-header">Finance</li>
    @endbpCanAny
    @bpCanAny('finance.view','payments.view','accounting.view')
    {{-- In simple mode the full Accounting menu is hidden, so accounting pages
         (journal entries, ledgers, etc.) have no dedicated sidebar item — light up
         "Manage Accounts" for them instead. In full mode the dedicated Accounting
         menu handles accounting.*, so don't double-highlight here. --}}
    @php($manageAccountsActive = request()->routeIs('money.cashflow', 'payments.*', 'payment-accounts.*', 'investment.*', 'ecommerce.steadfast.*') || ($isSimpleMode && request()->routeIs('accounting.*')))
    <li class="menu-item {{ $manageAccountsActive ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ $manageAccountsActive ? 'active' : '' }}" data-toggle="submenu" title="Manage Accounts">
        <i class="fa-solid fa-right-left"></i>
        <span class="menu-text">Manage Accounts</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('finance.view')
        <li><a href="{{ route('money.cashflow') }}" class="menu-link {{ request()->routeIs('money.cashflow') ? 'active' : '' }}">Cash Flow</a></li>
        @endbpCan
        @bpCan('payments.view')
        <li><a href="{{ route('payments.index') }}" class="menu-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">Payments</a></li>
        @endbpCan
        @bpCan('payments.view')
        <li><a href="{{ route('payment-accounts.index') }}" class="menu-link {{ request()->routeIs('payment-accounts.*') ? 'active' : '' }}">Account List</a></li>
        @endbpCan
        @bpCan('accounting.view')
        <li><a href="{{ route('investment.dashboard') }}" class="menu-link {{ request()->routeIs('investment.*') ? 'active' : '' }}">Investment</a></li>
        @endbpCan
        {{-- Steadfast courier settlements / COD withdrawals — a payments concern,
             so it lives here rather than under Website. --}}
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.steadfast.index') }}" class="menu-link {{ request()->routeIs('ecommerce.steadfast.*') ? 'active' : '' }}">Steadfast Payments</a></li>
        @endbpCan
      </ul>
    </li>
    <li class="menu-item {{ request()->routeIs('expenses.*', 'expense-categories.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('expenses.*', 'expense-categories.*') ? 'active' : '' }}" data-toggle="submenu" title="Expenses">
        <i class="fa-solid fa-money-bill-wave"></i>
        <span class="menu-text">Expenses</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('finance.view')
        <li><a href="{{ route('expenses.index') }}" class="menu-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">Expenses List</a></li>
        @endbpCan
        @bpCan('finance.view')
        <li><a href="{{ route('expense-categories.index') }}" class="menu-link {{ request()->routeIs('expense-categories.*') ? 'active' : '' }}">Categories</a></li>
        @endbpCan
      </ul>
    </li>
    <li class="menu-item {{ request()->routeIs('assets.*', 'asset-categories.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('assets.*', 'asset-categories.*') ? 'active' : '' }}" data-toggle="submenu" title="Assets">
        <i class="fa-solid fa-building"></i>
        <span class="menu-text">Assets</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('finance.view')
        <li><a href="{{ route('assets.index') }}" class="menu-link {{ request()->routeIs('assets.*') ? 'active' : '' }}">Asset List</a></li>
        @endbpCan
        @bpCan('finance.view')
        <li><a href="{{ route('asset-categories.index') }}" class="menu-link {{ request()->routeIs('asset-categories.*') ? 'active' : '' }}">Categories</a></li>
        @endbpCan
      </ul>
    </li>
    <li class="menu-item {{ request()->routeIs('loans.*', 'lenders.*', 'personal-loans.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('loans.*', 'lenders.*', 'personal-loans.*') ? 'active' : '' }}" data-toggle="submenu" title="Loans">
        <i class="fa-solid fa-hand-holding-dollar"></i>
        <span class="menu-text">Loans</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('finance.view')
        <li><a href="{{ route('loans.index') }}" class="menu-link {{ request()->routeIs('loans.*') ? 'active' : '' }}">Loans List</a></li>
        @endbpCan
        @bpCan('finance.view')
        <li><a href="{{ route('lenders.index') }}" class="menu-link {{ request()->routeIs('lenders.*') ? 'active' : '' }}">Lenders</a></li>
        @endbpCan
        @bpCan('finance.view')
        <li><a href="{{ route('personal-loans.index') }}" class="menu-link {{ request()->routeIs('personal-loans.*') ? 'active' : '' }}">Personal Loans</a></li>
        @endbpCan
      </ul>
    </li>
    @if(!$isSimpleMode)
      <li class="menu-item {{ request()->routeIs('accounting.*') ? 'open' : '' }}">
        <a href="javascript:;" class="menu-link {{ request()->routeIs('accounting.*') ? 'active' : '' }}" data-toggle="submenu" title="Accounting">
          <i class="fa-solid fa-calculator"></i>
          <span class="menu-text">Accounting</span>
          <i class="fa-solid fa-chevron-right menu-arrow"></i>
        </a>
        <ul class="submenu">
          @bpCan('accounting.view')
          <li><a href="{{ route('investment.dashboard') }}" class="menu-link {{ request()->routeIs('investment.*') ? 'active' : '' }}">Investment</a></li>
          @endbpCan
          @bpCan('accounting.view')
          <li><a href="{{ route('accounting.journal-entries') }}" class="menu-link {{ request()->routeIs('accounting.journal-entries*') ? 'active' : '' }}">Journal Entries</a></li>
          @endbpCan
          @bpCan('accounting.view')
          <li><a href="{{ route('accounting.general-ledger') }}" class="menu-link {{ request()->routeIs('accounting.general-ledger') ? 'active' : '' }}">General Ledger</a></li>
          @endbpCan
          @bpCan('accounting.view')
          <li><a href="{{ route('accounting.trial-balance') }}" class="menu-link {{ request()->routeIs('accounting.trial-balance') ? 'active' : '' }}">Trial Balance</a></li>
          @endbpCan
          @bpCan('accounting.view')
          <li><a href="{{ route('accounting.profit-loss') }}" class="menu-link {{ request()->routeIs('accounting.profit-loss') ? 'active' : '' }}">Profit & Loss</a></li>
          @endbpCan
          @bpCan('accounting.view')
          <li><a href="{{ route('accounting.balance-sheet') }}" class="menu-link {{ request()->routeIs('accounting.balance-sheet') ? 'active' : '' }}">Balance Sheet</a></li>
          @endbpCan
          @bpCan('accounting.view')
          <li><a href="{{ route('accounting.cash-flow') }}" class="menu-link {{ request()->routeIs('accounting.cash-flow') ? 'active' : '' }}">Cash Flow</a></li>
          @endbpCan
          @bpCan('accounting.view')
          <li><a href="{{ route('accounting.credit-notes.index') }}" class="menu-link {{ request()->routeIs('accounting.credit-notes.*') ? 'active' : '' }}">Credit Notes</a></li>
          @endbpCan
          @bpCan('accounting.view')
          <li><a href="{{ route('accounting.debit-notes.index') }}" class="menu-link {{ request()->routeIs('accounting.debit-notes.*') ? 'active' : '' }}">Debit Notes</a></li>
          @endbpCan
          @bpCan('accounting.view')
          <li><a href="{{ route('accounting.receipts.index') }}" class="menu-link {{ request()->routeIs('accounting.receipts.*') ? 'active' : '' }}">Receipts</a></li>
          @endbpCan
        </ul>
      </li>
    @endif
    @endbpCanAny

    <!-- ==================== ONLINE STORE ==================== -->
    @bpCanAny('ecommerce.view')
    <li class="menu-header">Online Store</li>
    @endbpCanAny
    @bpCanAny('ecommerce.view')
    {{-- Steadfast now lives under Manage Accounts, so exclude it here to avoid
         opening/highlighting Website when on the Steadfast page. --}}
    @php($ecommerceActive = request()->routeIs('ecommerce.*') && ! request()->routeIs('ecommerce.steadfast.*'))
    <li class="menu-item {{ $ecommerceActive ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ $ecommerceActive ? 'active' : '' }}" data-toggle="submenu" title="Website">
        <i class="fa-solid fa-globe"></i>
        <span class="menu-text">Website</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.campaigns.index') }}" class="menu-link {{ request()->routeIs('ecommerce.campaigns.*') ? 'active' : '' }}">Campaigns</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.coupons') }}" class="menu-link {{ request()->routeIs('ecommerce.coupons*') ? 'active' : '' }}">Coupons</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.flash-deals') }}" class="menu-link {{ request()->routeIs('ecommerce.flash-deals*') ? 'active' : '' }}">Flash Deals</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.collections') }}" class="menu-link {{ request()->routeIs('ecommerce.collections*') ? 'active' : '' }}">Collections</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.banners') }}" class="menu-link {{ request()->routeIs('ecommerce.banners*') ? 'active' : '' }}">Banners</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.homepage-sections') }}" class="menu-link {{ request()->routeIs('ecommerce.homepage-sections*') ? 'active' : '' }}">Manage Sections</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.menus.index') }}" class="menu-link {{ request()->routeIs('ecommerce.menus.*') ? 'active' : '' }}">Menus</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.blog') }}" class="menu-link {{ request()->routeIs('ecommerce.blog') || request()->routeIs('ecommerce.blog.*') ? 'active' : '' }}">Blog</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.blog-categories') }}" class="menu-link {{ request()->routeIs('ecommerce.blog-categories*') ? 'active' : '' }}">Blog Categories</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.blog-comments') }}" class="menu-link {{ request()->routeIs('ecommerce.blog-comments*') ? 'active' : '' }}">Blog Comments</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.faqs.index') }}" class="menu-link {{ request()->routeIs('ecommerce.faqs.*') ? 'active' : '' }}">FAQs</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.pages.index') }}" class="menu-link {{ request()->routeIs('ecommerce.pages.*') ? 'active' : '' }}">Pages</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.shipping') }}" class="menu-link {{ request()->routeIs('ecommerce.shipping*') ? 'active' : '' }}">Shipping Zones</a></li>
        @endbpCan
        @bpCan('ecommerce.view')
        <li><a href="{{ route('ecommerce.settings') }}" class="menu-link {{ request()->routeIs('ecommerce.settings*') ? 'active' : '' }}">Store Settings</a></li>
        @endbpCan
      </ul>
    </li>
    @endbpCanAny
    <!-- ==================== REPORTS ==================== -->
    @bpCanAny('reports.view')
    <li class="menu-header">Reports</li>
    @endbpCanAny
    @bpCanAny('reports.view')
    <li class="menu-item {{ request()->routeIs('reports.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" data-toggle="submenu" title="Reports">
        <i class="fa-solid fa-file-lines"></i>
        <span class="menu-text">Reports</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('reports.view')
        <li><a href="{{ route('reports.dts') }}" class="menu-link {{ request()->routeIs('reports.dts') ? 'active' : '' }}">Daily Summary (DTS)</a></li>
        @endbpCan
        @bpCan('reports.view')
        <li><a href="{{ route('reports.monthly-summary') }}" class="menu-link {{ request()->routeIs('reports.monthly-summary') ? 'active' : '' }}">Monthly Summary</a></li>
        @endbpCan
        @bpCan('reports.view')
        <li><a href="{{ route('reports.sales') }}" class="menu-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}">Sales Reports</a></li>
        @endbpCan
        @bpCan('reports.view')
        <li><a href="{{ route('reports.detail-sales') }}" class="menu-link {{ request()->routeIs('reports.detail-sales') ? 'active' : '' }}">Detail Sales</a></li>
        @endbpCan
        @bpCan('reports.view')
        <li><a href="{{ route('reports.category-wise') }}" class="menu-link {{ request()->routeIs('reports.category-wise') ? 'active' : '' }}">Category-wise</a></li>
        @endbpCan
        @bpCan('reports.view')
        <li><a href="{{ route('reports.inventory') }}" class="menu-link {{ request()->routeIs('reports.inventory') ? 'active' : '' }}">Inventory Reports</a></li>
        @endbpCan
        @bpCan('reports.view')
        <li><a href="{{ route('reports.purchase') }}" class="menu-link {{ request()->routeIs('reports.purchase') ? 'active' : '' }}">Purchase Reports</a></li>
        @endbpCan
        @bpCan('reports.view')
        <li><a href="{{ route('reports.receivables-aging') }}" class="menu-link {{ request()->routeIs('reports.receivables-aging') ? 'active' : '' }}">Receivables Aging</a></li>
        @endbpCan
        @bpCan('reports.view')
        <li><a href="{{ route('reports.profit-loss') }}" class="menu-link {{ request()->routeIs('reports.profit-loss') ? 'active' : '' }}">Profit &amp; Loss</a></li>
        @endbpCan
        @bpCan('reports.view')
        <li><a href="{{ route('reports.cash-movement') }}" class="menu-link {{ request()->routeIs('reports.cash-movement') ? 'active' : '' }}">Cash Movement</a></li>
        @endbpCan
        @bpCan('reports.view')
        <li><a href="{{ route('reports.supplier-payments') }}" class="menu-link {{ request()->routeIs('reports.supplier-payments') ? 'active' : '' }}">Supplier Payments</a></li>
        @endbpCan
        @bpCan('reports.view')
        <li><a href="{{ route('reports.customer') }}" class="menu-link {{ request()->routeIs('reports.customer') ? 'active' : '' }}">Customer Reports</a></li>
        @endbpCan
        @if(!$isSimpleMode)
          @bpCan('reports.view')
          <li><a href="{{ route('reports.financial') }}" class="menu-link {{ request()->routeIs('reports.financial') ? 'active' : '' }}">Financial Reports</a></li>
          @endbpCan
        @endif
        @bpCan('reports.view')
        <li><a href="{{ route('reports.staff') }}" class="menu-link {{ request()->routeIs('reports.staff') ? 'active' : '' }}">Staff Reports</a></li>
        @endbpCan
        @if(!$isSimpleMode)
          @bpCan('reports.view')
          <li><a href="{{ route('reports.custom') }}" class="menu-link {{ request()->routeIs('reports.custom') ? 'active' : '' }}">Custom Builder</a></li>
          @endbpCan
        @endif
      </ul>
    </li>
    @endbpCanAny

    <!-- ==================== MARKETING ==================== -->
    @bpCanAny('marketing.view')
    <li class="menu-header">Marketing</li>
    @endbpCanAny
    @bpCanAny('marketing.view')
    <li class="menu-item {{ request()->routeIs('marketing.*', 'adspend.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('marketing.*', 'adspend.*') ? 'active' : '' }}" data-toggle="submenu" title="Marketing">
        <i class="fa-solid fa-bullhorn"></i>
        <span class="menu-text">Marketing</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('marketing.view')
        <li><a href="{{ route('adspend.index') }}" class="menu-link {{ request()->routeIs('adspend.*') ? 'active' : '' }}">Ad Spend</a></li>
        @endbpCan
        @bpCan('marketing.view')
        <li><a href="{{ route('marketing.sms-campaigns') }}" class="menu-link {{ request()->routeIs('marketing.sms-campaigns*') ? 'active' : '' }}">SMS Campaigns</a></li>
        @endbpCan
        @bpCan('marketing.view')
        <li><a href="{{ route('marketing.email') }}" class="menu-link {{ request()->routeIs('marketing.email*') ? 'active' : '' }}">Email Marketing</a></li>
        @endbpCan
        @bpCan('marketing.view')
        <li><a href="{{ route('marketing.loyalty') }}" class="menu-link {{ request()->routeIs('marketing.loyalty*') ? 'active' : '' }}">Loyalty Program</a></li>
        @endbpCan
      </ul>
    </li>
    @endbpCanAny

    <!-- ==================== HR ==================== -->
    @bpCanAny('hr.view')
    <li class="menu-header">HR</li>
    @endbpCanAny
    @bpCanAny('hr.view')
    <li class="menu-item {{ request()->routeIs('employee.*', 'departments.*', 'designations.*', 'attendance.*', 'payroll.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('employee.*', 'departments.*', 'designations.*', 'attendance.*', 'payroll.*') ? 'active' : '' }}" data-toggle="submenu" title="Staff">
        <i class="fa-solid fa-id-badge"></i>
        <span class="menu-text">Staff</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('hr.view')
        <li><a href="{{ route('employee.index') }}" class="menu-link {{ request()->routeIs('employee.*') ? 'active' : '' }}">Employees</a></li>
        @endbpCan
        @bpCan('hr.view')
        <li><a href="{{ route('departments.index') }}" class="menu-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">Departments</a></li>
        @endbpCan
        @bpCan('hr.view')
        <li><a href="{{ route('designations.index') }}" class="menu-link {{ request()->routeIs('designations.*') ? 'active' : '' }}">Designations</a></li>
        @endbpCan
        @bpCan('hr.view')
        <li><a href="{{ route('attendance.index') }}" class="menu-link {{ request()->routeIs('attendance.index', 'attendance.create', 'attendance.report') ? 'active' : '' }}">Attendance</a></li>
        @endbpCan
        @bpCan('hr.view')
        <li><a href="{{ route('attendance.leave') }}" class="menu-link {{ request()->routeIs('attendance.leave', 'attendance.leave.*') ? 'active' : '' }}">Leave Management</a></li>
        @endbpCan
        @bpCan('hr.view')
        <li><a href="{{ route('attendance.leave-types.index') }}" class="menu-link {{ request()->routeIs('attendance.leave-types.*') ? 'active' : '' }}">Leave Types</a></li>
        @endbpCan
        @bpCan('hr.view')
        <li><a href="{{ route('attendance.config') }}" class="menu-link {{ request()->routeIs('attendance.config*') ? 'active' : '' }}">Weekends & Holidays</a></li>
        @endbpCan
        @bpCan('hr.view')
        <li><a href="{{ route('payroll.index') }}" class="menu-link {{ request()->routeIs('payroll.*') ? 'active' : '' }}">Payroll</a></li>
        @endbpCan
      </ul>
    </li>
    @endbpCanAny

    <!-- ==================== MANUFACTURING (collapsed) ==================== -->
    {{-- Most shops don't manufacture — collapse the 8 standalone mfg items
         into a single submenu near the bottom so it stays out of the way. --}}
    @bpCanAny('manufacturing.view','manufacturing.create')
    <li class="menu-header">Manufacturing</li>
    @endbpCanAny
    @bpCanAny('manufacturing.view','manufacturing.create')
    <li class="menu-item {{ request()->routeIs('manufacturing.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('manufacturing.*') ? 'active' : '' }}" data-toggle="submenu" title="Manufacturing">
        <i class="fa-solid fa-industry"></i>
        <span class="menu-text">Manufacturing</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.dashboard') }}" class="menu-link {{ request()->routeIs('manufacturing.dashboard') ? 'active' : '' }}">Mfg Dashboard</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.production-orders.index') }}" class="menu-link {{ request()->routeIs('manufacturing.production-orders.*') ? 'active' : '' }}">Production Orders</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.rm-purchases.index') }}" class="menu-link {{ request()->routeIs('manufacturing.rm-purchases.*') ? 'active' : '' }}">Purchase Orders (RM)</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.raw-materials.index') }}" class="menu-link {{ request()->routeIs('manufacturing.raw-materials.*') ? 'active' : '' }}">Raw Materials</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.colors.index') }}" class="menu-link {{ request()->routeIs('manufacturing.colors.*') ? 'active' : '' }}">Colors</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.sizes.index') }}" class="menu-link {{ request()->routeIs('manufacturing.sizes.*') ? 'active' : '' }}">Sizes</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.suppliers.index') }}" class="menu-link {{ request()->routeIs('manufacturing.suppliers.*') ? 'active' : '' }}">Suppliers (RM)</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.factories.index') }}" class="menu-link {{ request()->routeIs('manufacturing.factories.*') ? 'active' : '' }}">Factories</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.catalogs.index') }}" class="menu-link {{ request()->routeIs('manufacturing.catalogs.*') ? 'active' : '' }}">Catalogs</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.damages.index') }}" class="menu-link {{ request()->routeIs('manufacturing.damages.*') ? 'active' : '' }}">Damages</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.wastes.rm.index') }}" class="menu-link {{ request()->routeIs('manufacturing.wastes.rm.*') ? 'active' : '' }}">RM Wastes</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.wastes.products.index') }}" class="menu-link {{ request()->routeIs('manufacturing.wastes.products.*') ? 'active' : '' }}">Product Wastes</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.reports.rm-stock') }}" class="menu-link {{ request()->routeIs('manufacturing.reports.rm-stock') ? 'active' : '' }}">RM Stock Report</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.reports.production') }}" class="menu-link {{ request()->routeIs('manufacturing.reports.production') ? 'active' : '' }}">Production Report</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.reports.damage') }}" class="menu-link {{ request()->routeIs('manufacturing.reports.damage') ? 'active' : '' }}">Damage Report</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.reports.waste') }}" class="menu-link {{ request()->routeIs('manufacturing.reports.waste') ? 'active' : '' }}">Waste Report</a></li>
        @endbpCan
        @bpCan('manufacturing.view')
        <li><a href="{{ route('manufacturing.reports.cost-analysis') }}" class="menu-link {{ request()->routeIs('manufacturing.reports.cost-analysis') ? 'active' : '' }}">Cost Analysis</a></li>
        @endbpCan
      </ul>
    </li>
    @endbpCanAny

    <!-- ==================== SYSTEM ==================== -->
    @bpCanAny('locations.view','settings.view','users.view','roles.view','activities.view')
    <li class="menu-header">System</li>
    @endbpCanAny
    @bpCanAny('locations.view')
    <li class="menu-item">
      <a href="{{ route('locations.index') }}" class="menu-link {{ request()->routeIs('locations.*') ? 'active' : '' }}" title="Locations">
        <i class="fa-solid fa-location-dot"></i>
        <span class="menu-text">Locations</span>
      </a>
    </li>
    @endbpCanAny
    @bpCanAny('settings.view')
    <li class="menu-item {{ request()->routeIs('settings.*') || request()->routeIs('admin.ai-assistant.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('settings.*') || request()->routeIs('admin.ai-assistant.*') ? 'active' : '' }}" data-toggle="submenu" title="Settings">
        <i class="fa-solid fa-gears"></i>
        <span class="menu-text">Settings</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('settings.view')
        <li><a href="{{ route('settings.index') }}" class="menu-link {{ request()->routeIs('settings.index') ? 'active' : '' }}">General</a></li>
        @endbpCan
        @bpCan('settings.view')
        <li><a href="{{ route('settings.email') }}" class="menu-link {{ request()->routeIs('settings.email*') ? 'active' : '' }}">Email Config</a></li>
        @endbpCan
        @bpCan('settings.view')
        <li><a href="{{ route('settings.printers.index') }}" class="menu-link {{ request()->routeIs('settings.printers*') ? 'active' : '' }}">Printers</a></li>
        @endbpCan
        @bpCan('settings.view')
        <li><a href="{{ route('settings.sidebar') }}" class="menu-link {{ request()->routeIs('settings.sidebar*') ? 'active' : '' }}">Sidebar Menu</a></li>
        @endbpCan
        @if(Route::has('admin.ai-assistant.settings'))
          {{-- AI Assistant settings hidden for now (WIP). Remove `d-none` to restore. --}}
          <li class="d-none"><a href="{{ route('admin.ai-assistant.settings') }}" class="menu-link {{ request()->routeIs('admin.ai-assistant.*') ? 'active' : '' }}">AI Assistant</a></li>
        @endif
      </ul>
    </li>
    @endbpCanAny
    @bpCanAny('users.view','roles.view')
    <li class="menu-item {{ request()->routeIs('security.*') ? 'open' : '' }}">
      <a href="javascript:;" class="menu-link {{ request()->routeIs('security.*') ? 'active' : '' }}" data-toggle="submenu" title="Security">
        <i class="fa-solid fa-shield-halved"></i>
        <span class="menu-text">Security</span>
        <i class="fa-solid fa-chevron-right menu-arrow"></i>
      </a>
      <ul class="submenu">
        @bpCan('users.view')
        <li><a href="{{ route('security.users.index') }}" class="menu-link {{ request()->routeIs('security.users*') ? 'active' : '' }}">User Management</a></li>
        @endbpCan
        @bpCan('roles.view')
        <li><a href="{{ route('security.roles') }}" class="menu-link {{ request()->routeIs('security.roles*') ? 'active' : '' }}">Roles & Permissions</a></li>
        @endbpCan
        @bpCan('users.view')
        <li><a href="{{ route('security.backup') }}" class="menu-link {{ request()->routeIs('security.backup*') ? 'active' : '' }}">Backup & Restore</a></li>
        @endbpCan
        @bpCan('users.view')
        <li><a href="{{ route('security.api-keys') }}" class="menu-link {{ request()->routeIs('security.api-keys*') ? 'active' : '' }}">API Management</a></li>
        @endbpCan
        <li><a href="{{ route('security.two-factor') }}" class="menu-link {{ request()->routeIs('security.two-factor*') ? 'active' : '' }}">Two-Factor Authentication</a></li>
      </ul>
    </li>
    @endbpCanAny
    @bpCanAny('activities.view')
    <li class="menu-item">
      <a href="{{ route('activities.index') }}" class="menu-link {{ request()->routeIs('activities.*') ? 'active' : '' }}" title="Activity Log">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span class="menu-text">Activity Log</span>
      </a>
    </li>
    @endbpCanAny
    @bpCanAny('settings.view')
    <li class="menu-item">
      <a href="{{ route('settings.system-logs.index') }}" class="menu-link {{ request()->routeIs('settings.system-logs*') ? 'active' : '' }}" title="System Logs">
        <i class="fa-solid fa-terminal"></i>
        <span class="menu-text">System Logs</span>
      </a>
    </li>
    @endbpCanAny
  </ul>
</aside>
