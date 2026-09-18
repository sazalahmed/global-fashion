@extends('core::layouts.pos')

@section('content')

  <!-- LEFT: Product Selection Area -->
  <div class="bp-pos-products">
    <!-- Search Bar -->
    <div class="bp-pos-search" id="posSearchBar">
      <i class="fa-solid fa-barcode"></i>
      <input type="text" id="posSearch" placeholder="Scan barcode or search product (name, SKU)..." autofocus>
      <kbd class="bp-pos-search-hint d-none d-lg-inline-block">F1</kbd>
    </div>

    <!-- Category Tabs -->
    <div class="bp-pos-categories">
      <button class="bp-pos-cat-btn active" data-category="all">All</button>
      @foreach($categories as $category)
        <button class="bp-pos-cat-btn" data-category="{{ $category->id }}">{{ $category->name }}</button>
      @endforeach
    </div>

    <!-- Product Grid -->
    <div class="bp-pos-grid" id="posProductGrid">
      @forelse($products as $product)
        <div class="bp-pos-item"
             data-id="{{ $product->id }}"
             data-name="{{ $product->name }}"
             data-price="{{ $product->sell_price }}"
             data-sku="{{ $product->sku }}"
             data-barcode="{{ $product->barcode ?? '' }}"
             data-stock="999"
             data-category="{{ $product->category_id }}"
             data-vat="{{ $product->vat_rate ?? 0 }}"
             data-type="{{ $product->product_type }}">
          <div class="bp-pos-item-img">
            @if($product->thumbnail)
              <img src="{{ upload_url($product->thumbnail) }}" alt="{{ $product->name }}">
            @elseif($product->image)
              <img src="{{ upload_url($product->image) }}" alt="{{ $product->name }}">
            @else
              <i class="fa-solid fa-box"></i>
            @endif
          </div>
          <div class="bp-pos-item-details">
            <div class="bp-pos-item-name">{{ Str::limit($product->name, 30) }}</div>
            <div class="bp-pos-item-sku">{{ $product->sku }}</div>
            <div class="bp-pos-item-price">{{ currency_symbol() }} {{ number_format($product->sell_price, 0) }}</div>
          </div>
          @if($product->product_type === 'variable' && $product->variants->isNotEmpty())
            <span class="bp-pos-variant-badge"><i class="fa-solid fa-swatchbook"></i> {{ $product->variants->count() }}</span>
          @endif
        </div>
      @empty
        <div class="text-center text-muted py-5 w-100">
          <i class="fa-solid fa-box-open fa-3x mb-3 d-block"></i>
          <p>No products available for POS.</p>
        </div>
      @endforelse
    </div>
  </div>

  <!-- RIGHT: Cart Panel -->
  <div class="bp-pos-cart">
    <!-- Cart Header -->
    <div class="bp-pos-cart-header">
      <div>
        <span class="fw-800 fs-5">Cart</span>
        <span class="text-muted fs-12 ms-2" id="cartItemCount">0 item(s)</span>
      </div>
      <div class="d-flex gap-2">
        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="modal" data-bs-target="#customerModal" title="Select Customer">
          <i class="fa-solid fa-user-plus"></i>
        </button>
        <button class="bp-btn bp-btn-sm bp-btn-outline position-relative" id="holdOrder" title="Hold Order (F2)">
          <i class="fa-solid fa-pause"></i>
          <span class="bp-pos-held-badge d-none" id="heldBadge">0</span>
        </button>
        <button class="bp-btn bp-btn-sm bp-btn-outline" id="recallOrder" title="Recall Order (F4)" data-bs-toggle="modal" data-bs-target="#heldOrdersModal">
          <i class="fa-solid fa-rotate-left"></i>
        </button>
        <button class="bp-btn bp-btn-sm bp-btn-danger" id="clearCart" title="Clear Cart (F8)">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    </div>

    <!-- Customer Bar -->
    <div class="bp-pos-customer-bar" id="customerBar">
      <i class="fa-solid fa-user text-muted"></i>
      <span class="fs-13 fw-600" id="selectedCustomerName">Walk-in Customer</span>
      <span class="bp-badge bp-badge-danger ms-2 d-none" id="customerDueBadge" title="Outstanding due"></span>
      <span class="bp-badge bp-badge-info ms-2 d-none" id="customerAdvanceBadge" title="Available advance balance"></span>
      <a href="#" class="ms-auto fs-12 text-primary" data-bs-toggle="modal" data-bs-target="#customerModal">Change</a>
    </div>

    <!-- Cart Items -->
    <div class="bp-pos-cart-items" id="cartItemsContainer">
      <div class="bp-empty-state" id="cartEmptyState">
        <i class="fa-solid fa-cart-shopping"></i>
        <h5>Cart is empty</h5>
        <p>Scan barcode or click a product</p>
      </div>
    </div>

    <!-- Cart Summary -->
    <div class="bp-pos-cart-summary">
      <div class="bp-cart-summary-row">
        <span>Subtotal</span>
        <span class="fw-700" id="cartSubtotal">{{ currency_symbol() }} 0</span>
      </div>
      <div class="bp-cart-summary-row">
        <span><i class="fa-solid fa-tag me-1 fs-11"></i>Discount</span>
        <div class="bp-pos-discount-row">
          <select class="bp-pos-discount-type" id="cartDiscountType">
            <option value="flat">{{ currency_symbol() }}</option>
            <option value="percent">%</option>
          </select>
          <input type="number" class="bp-form-control bp-pos-discount-input" id="cartDiscountValue" value="0" min="0" step="any">
        </div>
      </div>
      <div class="bp-cart-summary-row">
        <span>Item Discounts</span>
        <span class="fw-600 text-danger" id="cartItemDiscounts">- {{ currency_symbol() }} 0</span>
      </div>
      <div class="bp-cart-summary-row">
        <span><i class="fa-solid fa-percent me-1 fs-11"></i>VAT</span>
        <div class="bp-pos-discount-row">
          <input type="number" class="bp-form-control bp-pos-discount-input" id="cartVatRate" value="0" min="0" max="100" step="any">
          <span class="fw-600 ms-1" id="cartVat">{{ currency_symbol() }} 0</span>
        </div>
      </div>
      <div class="bp-cart-summary-row total">
        <span>Grand Total</span>
        <span id="cartTotal">{{ currency_symbol() }} 0</span>
      </div>
    </div>

    <!-- Cart Actions -->
    <div class="bp-pos-cart-actions">
      <button class="bp-btn bp-btn-outline" id="holdOrder2" title="Hold (F2)">
        <i class="fa-solid fa-pause"></i> Hold
      </button>
      <button class="bp-btn bp-btn-success" id="payNowBtn" disabled title="Pay Now (F3)">
        <i class="fa-solid fa-bangladeshi-taka-sign"></i> Pay Now
      </button>
    </div>
  </div>

  <!-- ============================================================
       PAYMENT MODAL — Split Payment Support
       ============================================================ -->
  <div class="modal fade bp-payment-modal" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-800"><i class="fa-solid fa-cash-register me-2"></i>Complete Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <!-- Hero: Grand Total -->
          <div class="bp-payment-hero">
            <div class="bp-payment-hero-label">Total Payable</div>
            <div class="bp-payment-hero-amount" id="paymentGrandTotal">{{ currency_symbol() }} 0</div>
          </div>

          <div class="bp-payment-body">

            <!-- Advance Adjustment (visible only when customer has advance) -->
            <div class="bp-advance-adjust d-none" id="advanceAdjustSection">
              <div class="bp-advance-adjust-header">
                <div class="bp-advance-adjust-info">
                  <i class="fa-solid fa-wallet"></i>
                  <div>
                    <div class="bp-advance-adjust-title">Customer Advance Available</div>
                    <div class="bp-advance-adjust-balance" id="advanceAvailableLabel">{{ currency_symbol() }} 0</div>
                  </div>
                </div>
                <label class="bp-advance-toggle">
                  <input type="checkbox" id="useAdvanceCheck">
                  <span class="bp-advance-toggle-label">Use Advance</span>
                </label>
              </div>
              <div class="bp-advance-adjust-body d-none" id="advanceAmountRow">
                <div class="bp-advance-adjust-input-row">
                  <label class="bp-form-label mb-0">Adjust Amount</label>
                  <div class="bp-advance-input-group">
                    <input type="number" class="bp-form-control" id="advanceAdjustAmount" placeholder="0" step="0.01" min="0">
                    <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="advanceUseAll" title="Use full advance or total">Max</button>
                  </div>
                </div>
                <div class="bp-advance-adjust-remaining">
                  Remaining to pay: <strong id="remainingAfterAdvance">{{ currency_symbol() }} 0</strong>
                </div>
              </div>
            </div>

            <!-- Payment Method Rows -->
            <div class="bp-payment-section-label">Payment Method</div>
            <div id="paymentRows"></div>

            <button type="button" class="bp-payment-add-split" id="addSplitPayment">
              <i class="fa-solid fa-plus"></i> Add Split Payment
            </button>

            <!-- Quick Amount Buttons -->
            <div class="bp-payment-section-label mt-3">Quick Amount</div>
            <div class="bp-quick-amounts">
              <button type="button" class="bp-quick-amount-btn quick-amount" data-amount="100">100</button>
              <button type="button" class="bp-quick-amount-btn quick-amount" data-amount="500">500</button>
              <button type="button" class="bp-quick-amount-btn quick-amount" data-amount="1000">1,000</button>
              <button type="button" class="bp-quick-amount-btn quick-amount" data-amount="2000">2,000</button>
              <button type="button" class="bp-quick-amount-btn quick-amount" data-amount="5000">5,000</button>
              <button type="button" class="bp-quick-amount-btn bp-quick-exact quick-amount" data-amount="exact">Exact</button>
            </div>

            <!-- Received & Change -->
            <div class="bp-payment-summary">
              <div class="bp-payment-summary-item">
                <div class="bp-payment-summary-label">Received</div>
                <div class="bp-payment-summary-value" id="totalReceived">{{ currency_symbol() }} 0</div>
              </div>
              <div class="bp-payment-summary-item">
                <div class="bp-payment-summary-label" id="changeLabel">Change</div>
                <div class="bp-payment-summary-value bp-change-positive" id="changeAmount">{{ currency_symbol() }} 0</div>
              </div>
            </div>

            <!-- Walk-in Customer Name (shown only when no customer selected) -->
            <div class="bp-walkin-name-row d-none" id="walkinNameRow">
              <div class="bp-payment-section-label mt-3">Walk-in Customer</div>
              <input type="text" class="bp-form-control" id="walkinCustomerName" placeholder="Customer name (optional)">
            </div>

            <!-- Due Warning for Walk-in -->
            <div class="bp-walkin-due-warning d-none" id="walkinDueWarning">
              <i class="fa-solid fa-triangle-exclamation me-1"></i>
              Walk-in customer cannot have due. Please collect full amount or select a customer.
            </div>

            <!-- Sale Date & Note -->
            <div class="bp-payment-footer-row">
              <div>
                <label class="bp-form-label">Sale Date *</label>
                <input type="date" class="bp-form-control" id="saleDate" value="{{ date('Y-m-d') }}">
              </div>
              <div>
                <label class="bp-form-label">Note (Optional)</label>
                <input type="text" class="bp-form-control" id="paymentNote" placeholder="Any sale note...">
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Cancel</button>
          <button type="button" class="bp-btn bp-btn-success" id="completePayment" disabled>
            <i class="fa-solid fa-check me-1"></i> Complete Sale & Print
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ============================================================
       CUSTOMER SELECT MODAL
       ============================================================ -->
  <div class="modal fade bp-customer-modal" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body p-0">

          <!-- Walk-in banner (shown when walk-in is selected) -->
          <div class="bp-cust-current" id="custCurrentBar">
            <div class="bp-cust-current-avatar"><i class="fa-solid fa-user"></i></div>
            <div class="bp-cust-current-info">
              <div class="fw-700 fs-13" id="custCurrentName">Walk-in Customer</div>
              <div class="fs-12 text-muted" id="custCurrentPhone"></div>
            </div>
            <button type="button" class="bp-btn bp-btn-sm bp-btn-outline" id="walkInCustomer">
              <i class="fa-solid fa-user-xmark me-1"></i>Walk-in
            </button>
            <button type="button" class="btn-close ms-1" data-bs-dismiss="modal"></button>
          </div>

          <!-- Tabs -->
          <div class="bp-cust-tabs">
            <button type="button" class="bp-cust-tab active" data-tab="search">
              <i class="fa-solid fa-magnifying-glass me-1"></i>Search
            </button>
            <button type="button" class="bp-cust-tab" data-tab="add">
              <i class="fa-solid fa-user-plus me-1"></i>New Customer
            </button>
          </div>

          <!-- Tab: Search -->
          <div class="bp-cust-tab-content active" id="custTabSearch">
            <div class="bp-cust-search-wrap">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" id="customerSearch" placeholder="Search by name, phone, or email..." autocomplete="off">
            </div>
            <div class="bp-cust-results" id="customerList"></div>
          </div>

          <!-- Tab: Quick Add -->
          <div class="bp-cust-tab-content" id="custTabAdd">
            <div class="bp-cust-add-form">
              <div class="row g-3">
                <div class="col-12">
                  <label class="bp-form-label">Full Name *</label>
                  <input type="text" class="bp-form-control" id="newCustomerName" placeholder="e.g. Rahim Uddin">
                </div>
                <div class="col-6">
                  <label class="bp-form-label">Phone *</label>
                  <input type="text" class="bp-form-control" id="newCustomerPhone" data-phone>
                </div>
                <div class="col-6">
                  <label class="bp-form-label">Email</label>
                  <input type="email" class="bp-form-control" id="newCustomerEmail" placeholder="Optional">
                </div>
                <div class="col-12">
                  <label class="bp-form-label">Address</label>
                  <input type="text" class="bp-form-control" id="newCustomerAddress" placeholder="Optional">
                </div>
              </div>
              <button type="button" class="bp-btn bp-btn-primary w-100 mt-3" id="addAndSelectCustomer">
                <i class="fa-solid fa-user-plus me-1"></i> Add & Select Customer
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ============================================================
       HELD ORDERS MODAL
       ============================================================ -->
  <div class="modal fade" id="heldOrdersModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-800"><i class="fa-solid fa-pause me-2"></i>Held Orders</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="heldOrdersList">
            <div class="text-center text-muted py-4">
              <i class="fa-solid fa-inbox fs-1 mb-2 d-block"></i>
              <p>No held orders</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============================================================
       VARIANT SELECTION MODAL
       ============================================================ -->
  <div class="modal fade" id="variantModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title fw-800"><i class="fa-solid fa-swatchbook me-2"></i>Select Variant</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="fw-700 fs-5 mb-1" id="variantProductName"></div>
          <div class="fs-12 text-muted mb-3" id="variantProductSku"></div>
          <div id="variantOptionsList"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============================================================
       ITEM DISCOUNT MODAL
       ============================================================ -->
  <div class="modal fade" id="itemDiscountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title fw-800"><i class="fa-solid fa-tag me-2"></i>Item Discount</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="discountItemSku">
          <div class="fw-700 mb-3" id="discountItemName"></div>
          <div class="row g-2">
            <div class="col-5">
              <label class="bp-form-label">Type</label>
              <select class="bp-form-select w-100" id="itemDiscountType">
                <option value="flat">{{ currency_symbol() }} (Flat)</option>
                <option value="percent">% (Percent)</option>
              </select>
            </div>
            <div class="col-7">
              <label class="bp-form-label">Value</label>
              <input type="number" class="bp-form-control" id="itemDiscountValue" value="0" min="0" step="any">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="bp-btn bp-btn-outline bp-btn-sm" id="clearItemDiscount">Clear</button>
          <button type="button" class="bp-btn bp-btn-primary bp-btn-sm" id="applyItemDiscount">Apply</button>
        </div>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
'use strict';

$(function () {
  var VAT_RATE = 0;
  var cart, heldOrders, selectedCustomer;
  try { cart = JSON.parse(localStorage.getItem('pos_cart')) || []; } catch(e) { cart = []; }
  try { heldOrders = JSON.parse(localStorage.getItem('pos_held_orders')) || []; } catch(e) { heldOrders = []; }
  try { selectedCustomer = JSON.parse(localStorage.getItem('pos_customer')) || null; } catch(e) { selectedCustomer = null; }
  var barcodeBuffer = '';
  var barcodeTimeout = null;

  // ============================================================
  // VARIANT DATA — pre-loaded from server
  // ============================================================
  var productVariants = {};
  @foreach($products as $product)
    @if($product->product_type === 'variable' && $product->variants->isNotEmpty())
    productVariants[{{ $product->id }}] = [
      @foreach($product->variants as $variant)
      {
        id: {{ $variant->id }},
        sku: '{{ e($variant->sku) }}',
        barcode: '{{ e($variant->barcode ?? '') }}',
        price: {{ $variant->effective_sell_price }},
        name: '{{ e($variant->variant_name) }}',
        attributes: [
          @foreach($variant->attributeValues as $av)
          { attribute: '{{ e($av->attribute->display_name ?? $av->attribute->name) }}', value: '{{ e($av->value) }}', colorCode: '{{ e($av->color_code ?? '') }}' },
          @endforeach
        ]
      },
      @endforeach
    ];
    @endif
  @endforeach

  function saveState() {
    localStorage.setItem('pos_cart', JSON.stringify(cart));
    localStorage.setItem('pos_held_orders', JSON.stringify(heldOrders));
    localStorage.setItem('pos_customer', JSON.stringify(selectedCustomer));
  }

  // ============================================================
  // UTILITY — HTML Escaping
  // ============================================================
  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // ============================================================
  // BD Number Formatting (Lakh system)
  // ============================================================
  function formatBDT(num) {
    num = Math.round(num);
    if (num < 0) return '- {{ currency_symbol() }} ' + formatBDT(Math.abs(num)).replace('{{ currency_symbol() }} ', '');
    var str = num.toString();
    var lastThree = str.substring(str.length - 3);
    var otherNumbers = str.substring(0, str.length - 3);
    if (otherNumbers !== '') lastThree = ',' + lastThree;
    return '{{ currency_symbol() }} ' + otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
  }

  // ============================================================
  // BARCODE SCANNER — detects rapid key input
  // ============================================================
  $(document).on('keypress', function (e) {
    if ($(e.target).is('input, textarea, select')) return;
    if (e.which < 32) return;

    clearTimeout(barcodeTimeout);
    barcodeBuffer += String.fromCharCode(e.which);

    barcodeTimeout = setTimeout(function () {
      if (barcodeBuffer.length >= 6) {
        handleBarcodeScan(barcodeBuffer.trim());
      }
      barcodeBuffer = '';
    }, 100);
  });

  // Also handle Enter in search box as barcode submit
  $('#posSearch').on('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      var val = $(this).val().trim();
      if (val.length > 0) {
        handleBarcodeScan(val);
        $(this).val('');
      }
    }
  });

  function handleBarcodeScan(code) {
    // Check variant barcodes first (client-side)
    var variantMatch = findVariantByBarcode(code);
    if (variantMatch) {
      var $item = $('.bp-pos-item[data-id="' + variantMatch.productId + '"]');
      if ($item.length) {
        $('#posSearchBar').addClass('scanning');
        setTimeout(function () { $('#posSearchBar').removeClass('scanning'); }, 500);
        addToCart($item.first(), variantMatch.variant);
        return;
      }
    }

    var $item = $('.bp-pos-item[data-barcode="' + code + '"]');
    if (!$item.length) {
      $item = $('.bp-pos-item[data-sku="' + code.toUpperCase() + '"]');
    }
    if ($item.length) {
      $('#posSearchBar').addClass('scanning');
      setTimeout(function () { $('#posSearchBar').removeClass('scanning'); }, 500);
      addToCart($item.first());
    } else {
      // AJAX fallback for barcode not in loaded products
      $.get('{{ route('pos.get-by-barcode') }}', { barcode: code }, function(data) {
        if (data && data.id) {
          var $newItem = $('<div class="bp-pos-item" data-id="' + data.id + '" data-name="' + escapeHtml(data.name) + '" data-price="' + data.sell_price + '" data-sku="' + escapeHtml(data.sku) + '" data-barcode="' + (data.barcode || '') + '" data-stock="999" data-category="' + (data.category_id || '') + '" data-vat="' + (data.vat_rate || 0) + '" data-type="' + (data.product_type || 'simple') + '"></div>');
          $('#posProductGrid').append($newItem);

          // If AJAX returned variant data, load it into JS
          if (data.variants && data.variants.length > 0) {
            productVariants[data.id] = data.variants.map(function(v) {
              return {
                id: v.id,
                sku: v.sku,
                barcode: v.barcode || '',
                price: parseFloat(v.effective_sell_price || v.sell_price || data.sell_price),
                name: v.variant_name || '',
                attributes: (v.attribute_values || []).map(function(av) {
                  return { attribute: av.attribute ? av.attribute.display_name || av.attribute.name : '', value: av.value, colorCode: av.color_code || '' };
                })
              };
            });
          }

          // If matched a specific variant barcode, add it directly
          if (data.matched_variant_id && productVariants[data.id]) {
            var matchedV = productVariants[data.id].find(function(v) { return v.id === data.matched_variant_id; });
            if (matchedV) {
              addToCart($newItem, matchedV);
              return;
            }
          }
          addToCart($newItem);
        }
      });
    }
  }

  function findVariantByBarcode(code) {
    for (var productId in productVariants) {
      var variants = productVariants[productId];
      for (var i = 0; i < variants.length; i++) {
        if (variants[i].barcode === code || variants[i].sku.toUpperCase() === code.toUpperCase()) {
          return { productId: parseInt(productId), variant: variants[i] };
        }
      }
    }
    return null;
  }

  // ============================================================
  // PRODUCT SEARCH
  // ============================================================
  var searchTimeout;
  $('#posSearch').on('input', function () {
    var query = $(this).val().toLowerCase().trim();
    clearTimeout(searchTimeout);

    if (query === '') {
      $('.bp-pos-item').show();
      $('.bp-pos-cat-btn[data-category]').removeClass('active');
      $('.bp-pos-cat-btn[data-category="all"]').addClass('active');
      return;
    }

    // Client-side filter first
    var found = 0;
    $('.bp-pos-item').each(function () {
      var name = $(this).data('name').toString().toLowerCase();
      var sku = $(this).data('sku').toString().toLowerCase();
      var barcode = ($(this).data('barcode') || '').toString().toLowerCase();
      var match = name.indexOf(query) > -1 || sku.indexOf(query) > -1 || barcode.indexOf(query) > -1;
      $(this).toggle(match);
      if (match) found++;
    });

    // AJAX fallback if no local matches and query is long enough
    if (found === 0 && query.length >= 2) {
      searchTimeout = setTimeout(function () {
        $.get('{{ route('pos.search-products') }}', { q: query }, function (data) {
          if (data && data.length > 0) {
            data.forEach(function (p) {
              if ($('.bp-pos-item[data-id="' + p.id + '"]').length === 0) {
                var imgHtml = p.thumbnail
                  ? '<img src="{{ asset("storage") }}/' + p.thumbnail + '" alt="">'
                  : (p.image ? '<img src="{{ asset("storage") }}/' + p.image + '" alt="">' : '<i class="fa-solid fa-box"></i>');
                var html = '<div class="bp-pos-item" data-id="' + p.id + '" data-name="' + escapeHtml(p.name) + '" data-price="' + p.sell_price + '" data-sku="' + escapeHtml(p.sku) + '" data-barcode="' + (p.barcode || '') + '" data-stock="999" data-category="' + (p.category_id || '') + '" data-vat="' + (p.vat_rate || 0) + '">' +
                  '<div class="bp-pos-item-img">' + imgHtml + '</div>' +
                  '<div class="bp-pos-item-details">' +
                    '<div class="bp-pos-item-name">' + escapeHtml(p.name) + '</div>' +
                    '<div class="bp-pos-item-sku">' + escapeHtml(p.sku) + '</div>' +
                    '<div class="bp-pos-item-price">{{ currency_symbol() }} ' + Math.round(p.sell_price).toLocaleString() + '</div>' +
                  '</div>' +
                '</div>';
                $('#posProductGrid').append(html);
              }
            });
          }
        });
      }, 300);
    }
  });

  // ============================================================
  // CATEGORY FILTERING
  // ============================================================
  $(document).on('click', '.bp-pos-cat-btn[data-category]', function () {
    var category = $(this).data('category');
    $('.bp-pos-cat-btn[data-category]').removeClass('active');
    $(this).addClass('active');
    $('#posSearch').val('');
    if (category === 'all') {
      $('.bp-pos-item').show();
    } else {
      $('.bp-pos-item').hide().filter('[data-category="' + category + '"]').show();
    }
  });

  // ============================================================
  // ADD TO CART — click product
  // ============================================================
  $(document).on('click', '.bp-pos-item', function () {
    addToCart($(this));
  });

  function addToCart($item, variantOverride) {
    var productId = $item.data('id');
    var productType = $item.data('type') || 'simple';

    // If variable product with variants and no override — show variant modal
    if (productType === 'variable' && productVariants[productId] && productVariants[productId].length > 0 && !variantOverride) {
      showVariantModal(productId, $item);
      return;
    }

    var sku = variantOverride ? variantOverride.sku : $item.data('sku');
    var price = variantOverride ? variantOverride.price : parseFloat($item.data('price'));
    var stock = parseInt($item.data('stock')) || 999;
    var variantId = variantOverride ? variantOverride.id : null;
    var variantName = variantOverride ? variantOverride.name : null;
    var displayName = variantOverride ? ($item.data('name') + ' — ' + variantOverride.name) : $item.data('name');

    // Cart key uses product_id + variant_id combo
    var cartKey = variantId ? (productId + '_v' + variantId) : productId.toString();
    var existing = cart.find(function (c) { return c.cartKey === cartKey; });

    if (existing) {
      if (existing.qty >= stock) return;
      existing.qty++;
    } else {
      if (stock <= 0) return;
      cart.push({
        cartKey: cartKey,
        id: productId,
        variantId: variantId,
        variantName: variantName,
        sku: sku,
        name: displayName,
        price: price,
        stock: stock,
        qty: 1,
        discountType: 'flat',
        discountValue: 0
      });
    }
    renderCart();
  }

  // ============================================================
  // VARIANT SELECTION MODAL
  // ============================================================
  var pendingVariantItem = null;

  function showVariantModal(productId, $item) {
    pendingVariantItem = $item;
    var variants = productVariants[productId];
    $('#variantProductName').text($item.data('name'));
    $('#variantProductSku').text('Base SKU: ' + $item.data('sku'));

    var html = '';
    variants.forEach(function(v) {
      var attrHtml = '';
      v.attributes.forEach(function(a) {
        if (a.colorCode) {
          attrHtml += '<span class="bp-variant-attr"><span class="bp-variant-color-dot" style="background-color: ' + escapeHtml(a.colorCode) + '"></span>' + escapeHtml(a.value) + '</span>';
        } else {
          attrHtml += '<span class="bp-variant-attr">' + escapeHtml(a.attribute) + ': <strong>' + escapeHtml(a.value) + '</strong></span>';
        }
      });

      html += '<button type="button" class="bp-variant-option" data-variant-id="' + v.id + '" data-product-id="' + productId + '">' +
        '<div class="bp-variant-option-info">' +
          '<div class="bp-variant-option-name">' + attrHtml + '</div>' +
          '<div class="bp-variant-option-sku fs-11 text-muted">' + escapeHtml(v.sku) + '</div>' +
        '</div>' +
        '<div class="bp-variant-option-price">' + formatBDT(v.price) + '</div>' +
      '</button>';
    });
    $('#variantOptionsList').html(html);
    var modal = new bootstrap.Modal('#variantModal');
    modal.show();
  }

  $(document).on('click', '.bp-variant-option', function () {
    var variantId = $(this).data('variant-id');
    var productId = $(this).data('product-id');
    var variants = productVariants[productId];
    var variant = variants.find(function(v) { return v.id === variantId; });
    if (variant && pendingVariantItem) {
      addToCart(pendingVariantItem, variant);
    }
    bootstrap.Modal.getInstance('#variantModal').hide();
    pendingVariantItem = null;
  });

  // ============================================================
  // RENDER CART
  // ============================================================
  function renderCart() {
    saveState();
    var $container = $('#cartItemsContainer');
    $container.empty();

    if (cart.length === 0) {
      $container.html('<div class="bp-empty-state" id="cartEmptyState"><i class="fa-solid fa-cart-shopping"></i><h5>Cart is empty</h5><p>Scan barcode or click a product</p></div>');
      $('#payNowBtn').prop('disabled', true);
    } else {
      $('#payNowBtn').prop('disabled', false);
      cart.forEach(function (item, idx) {
        var lineDiscount = calcItemDiscount(item);
        var lineTotal = (item.price * item.qty) - lineDiscount;
        var html = '<div class="bp-pos-cart-item" data-idx="' + idx + '">' +
          '<div class="bp-pos-cart-item-info">' +
            '<div class="bp-pos-cart-item-name">' + escapeHtml(item.name) + '</div>' +
            '<div class="bp-pos-cart-item-sku">' + escapeHtml(item.sku) + ' &bull; ' + formatBDT(item.price) + '/pc</div>' +
            '<div class="d-flex align-items-center gap-2 mt-1">' +
              '<div class="bp-pos-qty-stepper">' +
                '<button type="button" class="qty-minus" data-idx="' + idx + '">-</button>' +
                '<input type="number" class="qty-input" data-idx="' + idx + '" value="' + item.qty + '" min="1" max="' + item.stock + '">' +
                '<button type="button" class="qty-plus" data-idx="' + idx + '">+</button>' +
              '</div>' +
              '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline item-discount-btn" data-idx="' + idx + '" title="Item Discount"><i class="fa-solid fa-tag fs-11"></i></button>' +
            '</div>' +
            (lineDiscount > 0 ? '<div class="bp-pos-cart-item-line-discount mt-1"><i class="fa-solid fa-tag me-1"></i>-' + formatBDT(lineDiscount) + '</div>' : '') +
          '</div>' +
          '<div class="d-flex flex-column align-items-end">' +
            '<div class="bp-pos-cart-item-line-total">' + formatBDT(lineTotal) + '</div>' +
            '<button type="button" class="bp-pos-cart-item-remove mt-1" data-idx="' + idx + '" title="Remove"><i class="fa-solid fa-xmark"></i></button>' +
          '</div>' +
        '</div>';
        $container.append(html);
      });
    }

    updateTotals();
  }

  function calcItemDiscount(item) {
    if (!item.discountValue || item.discountValue <= 0) return 0;
    var lineGross = item.price * item.qty;
    if (item.discountType === 'percent') {
      return Math.min(lineGross, lineGross * (item.discountValue / 100));
    }
    return Math.min(lineGross, item.discountValue * item.qty);
  }

  // ============================================================
  // UPDATE TOTALS
  // ============================================================
  function updateTotals() {
    var subtotal = 0;
    var totalItemDiscount = 0;
    var totalItems = 0;

    cart.forEach(function (item) {
      var lineGross = item.price * item.qty;
      var lineDiscount = calcItemDiscount(item);
      subtotal += lineGross;
      totalItemDiscount += lineDiscount;
      totalItems += item.qty;
    });

    // Cart-level discount
    var cartDiscType = $('#cartDiscountType').val();
    var cartDiscVal = parseFloat($('#cartDiscountValue').val()) || 0;
    var afterItemDisc = subtotal - totalItemDiscount;
    var cartDiscount = 0;
    if (cartDiscType === 'percent') {
      cartDiscount = afterItemDisc * (cartDiscVal / 100);
    } else {
      cartDiscount = cartDiscVal;
    }
    cartDiscount = Math.min(afterItemDisc, Math.max(0, cartDiscount));

    var taxableAmount = afterItemDisc - cartDiscount;
    VAT_RATE = (parseFloat($('#cartVatRate').val()) || 0) / 100;
    var vat = Math.round(taxableAmount * VAT_RATE);
    var grandTotal = taxableAmount + vat;

    $('#cartItemCount').text(totalItems + ' item(s)');
    $('#cartSubtotal').text(formatBDT(subtotal));
    $('#cartItemDiscounts').text('- ' + formatBDT(totalItemDiscount));
    $('#cartVat').text(formatBDT(vat));
    $('#cartTotal').text(formatBDT(grandTotal));

    // Store for payment modal
    $('#cartTotal').data('value', grandTotal);
  }

  // Discount/VAT inputs trigger recalc
  $('#cartDiscountType, #cartDiscountValue, #cartVatRate').on('change input', updateTotals);

  // ============================================================
  // QTY CONTROLS
  // ============================================================
  $(document).on('click', '.qty-minus', function () {
    var idx = $(this).data('idx');
    if (cart[idx].qty > 1) {
      cart[idx].qty--;
      renderCart();
    }
  });
  $(document).on('click', '.qty-plus', function () {
    var idx = $(this).data('idx');
    if (cart[idx].qty < cart[idx].stock) {
      cart[idx].qty++;
      renderCart();
    }
  });
  $(document).on('change', '.qty-input', function () {
    var idx = $(this).data('idx');
    var val = parseInt($(this).val()) || 1;
    val = Math.max(1, Math.min(val, cart[idx].stock));
    cart[idx].qty = val;
    renderCart();
  });

  // ============================================================
  // REMOVE ITEM
  // ============================================================
  $(document).on('click', '.bp-pos-cart-item-remove', function () {
    var idx = $(this).data('idx');
    cart.splice(idx, 1);
    renderCart();
  });

  // ============================================================
  // CLEAR CART
  // ============================================================
  $(document).on('click', '#clearCart', function () {
    if (cart.length === 0) return;
    cart = [];
    $('#cartDiscountValue').val(0);
    renderCart();
  });

  // ============================================================
  // ITEM DISCOUNT
  // ============================================================
  $(document).on('click', '.item-discount-btn', function () {
    var idx = $(this).data('idx');
    var item = cart[idx];
    $('#discountItemSku').val(idx);
    $('#discountItemName').text(item.name + ' (' + formatBDT(item.price) + ' x ' + item.qty + ')');
    $('#itemDiscountType').val(item.discountType);
    $('#itemDiscountValue').val(item.discountValue);
    var modal = new bootstrap.Modal('#itemDiscountModal');
    modal.show();
  });

  $(document).on('click', '#applyItemDiscount', function () {
    var idx = parseInt($('#discountItemSku').val());
    cart[idx].discountType = $('#itemDiscountType').val();
    cart[idx].discountValue = parseFloat($('#itemDiscountValue').val()) || 0;
    bootstrap.Modal.getInstance('#itemDiscountModal').hide();
    renderCart();
  });

  $(document).on('click', '#clearItemDiscount', function () {
    var idx = parseInt($('#discountItemSku').val());
    cart[idx].discountType = 'flat';
    cart[idx].discountValue = 0;
    bootstrap.Modal.getInstance('#itemDiscountModal').hide();
    renderCart();
  });

  // ============================================================
  // PAY NOW — open payment modal
  // ============================================================
  $(document).on('click', '#payNowBtn', function () {
    if (cart.length === 0) return;
    var total = $('#cartTotal').data('value') || 0;
    $('#paymentGrandTotal').text(formatBDT(total));

    // Reset advance section
    $('#useAdvanceCheck').prop('checked', false);
    $('#advanceAdjustAmount').val('');
    $('#advanceAmountRow').addClass('d-none');
    if (selectedCustomer && customerAdvanceBalance > 0) {
      $('#advanceAdjustSection').removeClass('d-none');
      $('#advanceAvailableLabel').text(formatBDT(customerAdvanceBalance));
      $('#advanceAdjustAmount').attr('max', Math.min(customerAdvanceBalance, total));
    } else {
      $('#advanceAdjustSection').addClass('d-none');
    }

    // Show walk-in name field if no customer selected
    if (!selectedCustomer) {
      $('#walkinNameRow').removeClass('d-none');
      $('#walkinCustomerName').val('');
    } else {
      $('#walkinNameRow').addClass('d-none');
    }
    $('#walkinDueWarning').addClass('d-none');

    // Reset payment rows to single cash
    paymentRowIndex = 1;
    $('#paymentRows').html(buildPaymentRow(0));
    $('#paymentRows .payment-amount-input:first').val(total).trigger('input');
    updatePaymentTotals();
    var modal = new bootstrap.Modal('#paymentModal');
    modal.show();
    setTimeout(function () { $('#paymentRows .payment-amount-input:first').select(); }, 300);
  });

  // ============================================================
  // SPLIT PAYMENT
  // ============================================================
  var paymentRowIndex = 1;

  // Payment account data with type info for filtering
  var paymentAccountsData = [
    @foreach($paymentAccounts as $acc)
    { id: {{ $acc->id }}, type: '{{ $acc->account_type }}', name: '{{ e($acc->display_name) }}' },
    @endforeach
  ];

  // Map payment method → account_type
  var methodToAccountType = {
    'mobile_banking': 'mobile_banking',
    'card': 'card',
    'bank_transfer': 'bank'
  };

  function buildAccountOptions(method) {
    var accountType = methodToAccountType[method];
    if (!accountType) return '';
    var html = '<option value="">Select Account</option>';
    paymentAccountsData.forEach(function(acc) {
      if (acc.type === accountType) {
        html += '<option value="' + acc.id + '">' + escapeHtml(acc.name) + '</option>';
      }
    });
    return html;
  }

  // Customer advance balance tracking
  var customerAdvanceBalance = 0;

  function fetchCustomerAdvance(customerId) {
    if (!customerId) {
      customerAdvanceBalance = 0;
      $('#customerAdvanceBadge').addClass('d-none');
      $('#customerDueBadge').addClass('d-none');
      return;
    }
    $.get('{{ route("pos.customer-advance") }}', { customer_id: customerId }, function(data) {
      customerAdvanceBalance = data.advance_balance || 0;
      if (customerAdvanceBalance > 0) {
        $('#customerAdvanceBadge').text('Advance: ' + formatBDT(customerAdvanceBalance)).removeClass('d-none');
      } else {
        $('#customerAdvanceBadge').addClass('d-none');
      }

      var dueAmount = data.due_amount || 0;
      if (dueAmount > 0) {
        $('#customerDueBadge').text('Due: ' + formatBDT(dueAmount)).removeClass('d-none');
      } else {
        $('#customerDueBadge').addClass('d-none');
      }
    });
  }

  function buildPaymentRow(idx) {
    var showRemove = idx > 0 ? '' : ' d-none';
    var accountOptionsHtml = '<option value="">Select Payment Method</option>';
    paymentAccountsData.forEach(function(acc) {
        accountOptionsHtml += '<option value="' + acc.id + '" data-type="' + acc.type + '">' + escapeHtml(acc.name) + '</option>';
    });
    return '<div class="bp-split-payment-row" data-index="' + idx + '">' +
      '<div class="bp-pay-amount-col">' +
        '<input type="number" class="bp-form-control payment-amount-input" name="payment_amount[]" placeholder="Amount" step="0.01" min="0">' +
      '</div>' +
      '<div class="bp-pay-method-col">' +
        '<select class="bp-form-select bp-form-control payment-method-select" name="payment_account_id[]">' +
          accountOptionsHtml +
        '</select>' +
      '</div>' +
      '<div class="bp-pay-ref-col">' +
        '<input type="text" class="bp-form-control" name="payment_ref[]" placeholder="Ref / TXN ID">' +
      '</div>' +
      '<button type="button" class="remove-split' + showRemove + '" title="Remove"><i class="fa-solid fa-times"></i></button>' +
    '</div>';
  }

  $(document).on('click', '#addSplitPayment', function () {
    var $rows = $('#paymentRows');
    $rows.append(buildPaymentRow(paymentRowIndex++));
    $rows.find('.remove-split').removeClass('d-none');
    // Remaining amount
    var total = $('#cartTotal').data('value') || 0;
    var paid = 0;
    $rows.find('.payment-amount-input').each(function () {
      paid += parseFloat($(this).val()) || 0;
    });
    var remaining = Math.max(0, total - paid);
    $rows.find('.payment-amount-input:last').val(remaining > 0 ? remaining : '');
  });

  $(document).on('click', '.remove-split', function () {
    $(this).closest('.bp-split-payment-row').remove();
    var $rows = $('#paymentRows .bp-split-payment-row');
    if ($rows.length === 1) $rows.find('.remove-split').addClass('d-none');
    updatePaymentTotals();
  });

  // ============================================================
  // PAYMENT TOTAL TRACKING & CHANGE
  // ============================================================
  $(document).on('input change', '.payment-amount-input', function () {
    updatePaymentTotals();
  });

  function getAdvanceAmount() {
    if ($('#useAdvanceCheck').is(':checked')) {
      return parseFloat($('#advanceAdjustAmount').val()) || 0;
    }
    return 0;
  }

  function updatePaymentTotals() {
    var total = $('#cartTotal').data('value') || 0;
    var advanceUsed = getAdvanceAmount();
    var received = advanceUsed;
    $('.payment-amount-input').each(function () {
      received += parseFloat($(this).val()) || 0;
    });
    $('#totalReceived').text(formatBDT(received));

    // Update remaining after advance label
    var remainingToPay = Math.max(0, total - advanceUsed);
    $('#remainingAfterAdvance').text(formatBDT(remainingToPay));

    var change = Math.round((received - total) * 100) / 100;
    var isPaid = change >= 0;

    if (isPaid) {
      $('#changeLabel').text('Change');
      $('#changeAmount').text(formatBDT(change)).removeClass('bp-change-negative').addClass('bp-change-positive');
      $('#completePayment').prop('disabled', false);
      $('#walkinDueWarning').addClass('d-none');
    } else {
      $('#changeLabel').text('Due Amount');
      $('#changeAmount').text(formatBDT(Math.abs(change))).removeClass('bp-change-positive').addClass('bp-change-negative');
      // Walk-in customers cannot have due — must collect full amount
      if (!selectedCustomer) {
        $('#completePayment').prop('disabled', true);
        $('#walkinDueWarning').removeClass('d-none');
      } else {
        $('#completePayment').prop('disabled', false);
        $('#walkinDueWarning').addClass('d-none');
      }
    }
  }

  // ============================================================
  // QUICK AMOUNT BUTTONS
  // ============================================================
  $(document).on('click', '.quick-amount', function () {
    var amt = $(this).data('amount');
    var $firstInput = $('#paymentRows .payment-amount-input:first');
    var total = $('#cartTotal').data('value') || 0;
    var advanceUsed = getAdvanceAmount();
    var remaining = Math.max(0, total - advanceUsed);
    if (amt === 'exact') {
      $firstInput.val(remaining);
    } else {
      $firstInput.val(amt);
    }
    $firstInput.trigger('input');
  });

  // ============================================================
  // COMPLETE SALE — AJAX POST to server
  // ============================================================
  // POS Settings from server
  var posSettings = @json($posSettings);

  $(document).on('click', '#completePayment', function () {
    var $btn = $(this);

    // Block walk-in due — double-check before processing
    var total = $('#cartTotal').data('value') || 0;
    var advanceUsed = getAdvanceAmount();
    var received = advanceUsed;
    $('.payment-amount-input').each(function () {
      received += parseFloat($(this).val()) || 0;
    });
    if (!selectedCustomer && received < total) {
      $('#walkinDueWarning').removeClass('d-none');
      $btn.prop('disabled', true);
      return;
    }

    var originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...');

    // Build cart items for server
    var cartItems = cart.map(function(item) {
      return {
        product_id: item.id,
        variant_id: item.variantId || null,
        quantity: item.qty,
        unit_price: item.price,
        discount_amount: calcItemDiscount(item)
      };
    });

    // Build payments — include advance adjustment first
    var payments = [];
    var advanceUsed = getAdvanceAmount();
    if (advanceUsed > 0) {
      payments.push({ amount: advanceUsed, method: 'advance', payment_account_id: null, reference: null });
    }

    $('#paymentRows .bp-split-payment-row').each(function() {
      var amount = parseFloat($(this).find('.payment-amount-input').val()) || 0;
      var method = $(this).find('.payment-method-select').val();
      var accountId = $(this).find('.payment-account-select').val() || null;
      var ref = $(this).find('input[name="payment_ref[]"]').val();
      if (amount > 0) {
        payments.push({ amount: amount, method: method, payment_account_id: accountId, reference: ref || null });
      }
    });

    var payload = {
      cart: cartItems,
      customer_id: selectedCustomer ? selectedCustomer.id : null,
      walkin_customer_name: !selectedCustomer ? ($('#walkinCustomerName').val().trim() || null) : null,
      payments: payments,
      sale_date: $('#saleDate').val(),
      discount_type: $('#cartDiscountType').val(),
      discount_value: parseFloat($('#cartDiscountValue').val()) || 0,
      tax_rate: parseFloat($('#cartVatRate').val()) || 0,
      note: $('#paymentNote').val() || null
    };

    $.ajax({
      url: '{{ route('pos.process-sale') }}',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload),
      success: function(data) {
        $btn.prop('disabled', false).html(originalHtml);
        bootstrap.Modal.getInstance('#paymentModal').hide();
        cart = [];
        selectedCustomer = null;
        customerAdvanceBalance = 0;
        $('#selectedCustomerName').text('Walk-in Customer');
        $('#customerAdvanceBadge').addClass('d-none');
        $('#customerDueBadge').addClass('d-none');
        $('#cartDiscountValue').val(0);
        $('#paymentNote').val('');
        renderCart();

        // Success flash
        var invoiceNum = data.sale && data.sale.invoice_number ? escapeHtml(data.sale.invoice_number) : '';
        var $flash = $('<div class="position-fixed top-50 start-50 translate-middle bp-pos-success-flash"><div class="bg-success text-white p-4 rounded-3 shadow-lg text-center"><i class="fa-solid fa-check-circle fs-1 d-block mb-2"></i><div class="fw-800 fs-5">Sale Completed!</div><div class="fs-13 mt-1">' + invoiceNum + '</div></div></div>');
        $('body').append($flash);
        setTimeout(function () { $flash.fadeOut(300, function () { $flash.remove(); }); }, 2500);

        // Auto-print based on POS settings
        if (posSettings.auto_print) {
          if (posSettings.pos_print_format === 'full_invoice' && data.invoice_url) {
            window.open(data.invoice_url, '_blank');
          } else if (data.receipt_url) {
            window.open(data.receipt_url, '_blank', 'width=400,height=600');
          }
        }
      },
      error: function(xhr) {
        $btn.prop('disabled', false).html(originalHtml);
        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to process sale';
        alert('Error: ' + msg);
      }
    });
  });

  // ============================================================
  // HOLD ORDER
  // ============================================================
  $(document).on('click', '#holdOrder, #holdOrder2', function () {
    if (cart.length === 0) {
      // If cart empty but has held orders, open recall modal
      if (heldOrders.length > 0) {
        var modal = new bootstrap.Modal('#heldOrdersModal');
        modal.show();
      }
      return;
    }
    heldOrders.push({
      cart: JSON.parse(JSON.stringify(cart)),
      customer: selectedCustomer,
      discount: { type: $('#cartDiscountType').val(), value: parseFloat($('#cartDiscountValue').val()) || 0 },
      time: new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
      total: $('#cartTotal').data('value') || 0
    });
    cart = [];
    selectedCustomer = null;
    $('#selectedCustomerName').text('Walk-in Customer');
    $('#cartDiscountValue').val(0);
    renderCart();
    updateHeldBadge();
  });

  function updateHeldBadge() {
    saveState();
    var count = heldOrders.length;
    if (count > 0) {
      $('#heldBadge').text(count).removeClass('d-none');
    } else {
      $('#heldBadge').addClass('d-none');
    }
  }

  // ============================================================
  // RECALL HELD ORDER
  // ============================================================
  $('#heldOrdersModal').on('show.bs.modal', function () {
    var $list = $('#heldOrdersList');
    if (heldOrders.length === 0) {
      $list.html('<div class="text-center text-muted py-4"><i class="fa-solid fa-inbox fs-1 mb-2 d-block"></i><p>No held orders</p></div>');
      return;
    }
    var html = '';
    heldOrders.forEach(function (order, idx) {
      var itemCount = order.cart.reduce(function (sum, c) { return sum + c.qty; }, 0);
      html += '<div class="d-flex align-items-center gap-3 p-3 border-bottom">' +
        '<div class="flex-grow-1">' +
          '<div class="fw-700">Order #' + (idx + 1) + ' <span class="text-muted fw-400 fs-12">(' + escapeHtml(order.time) + ')</span></div>' +
          '<div class="fs-12 text-muted">' + itemCount + ' items &bull; ' + (order.customer ? escapeHtml(order.customer.name) : 'Walk-in') + '</div>' +
          '<div class="fw-700 text-primary fs-13 mt-1">' + formatBDT(order.total) + '</div>' +
        '</div>' +
        '<button class="bp-btn bp-btn-sm bp-btn-primary recall-held-btn" data-idx="' + idx + '"><i class="fa-solid fa-rotate-left me-1"></i>Recall</button>' +
        '<button class="bp-btn bp-btn-sm bp-btn-danger delete-held-btn" data-idx="' + idx + '"><i class="fa-solid fa-trash"></i></button>' +
      '</div>';
    });
    $list.html(html);
  });

  $(document).on('click', '.recall-held-btn', function () {
    var idx = $(this).data('idx');
    var order = heldOrders[idx];
    cart = order.cart;
    selectedCustomer = order.customer;
    $('#selectedCustomerName').text(selectedCustomer ? escapeHtml(selectedCustomer.name) : 'Walk-in Customer');
    $('#cartDiscountType').val(order.discount.type);
    $('#cartDiscountValue').val(order.discount.value);
    heldOrders.splice(idx, 1);
    updateHeldBadge();
    renderCart();
    bootstrap.Modal.getInstance('#heldOrdersModal').hide();
  });

  $(document).on('click', '.delete-held-btn', function () {
    var idx = $(this).data('idx');
    heldOrders.splice(idx, 1);
    updateHeldBadge();
    $('#heldOrdersModal').trigger('show.bs.modal');
  });

  // ============================================================
  // CUSTOMER MODAL — Tabs
  // ============================================================
  $(document).on('click', '.bp-cust-tab', function () {
    var tab = $(this).data('tab');
    $('.bp-cust-tab').removeClass('active');
    $(this).addClass('active');
    $('.bp-cust-tab-content').removeClass('active');
    $('#custTab' + tab.charAt(0).toUpperCase() + tab.slice(1)).addClass('active');
    if (tab === 'search') {
      setTimeout(function () { $('#customerSearch').focus(); }, 100);
    } else {
      setTimeout(function () { $('#newCustomerName').focus(); }, 100);
    }
  });

  // Update current customer bar when modal opens
  $('#customerModal').on('show.bs.modal', function () {
    updateCustCurrentBar();
    // Reset to search tab
    $('.bp-cust-tab[data-tab="search"]').click();
    $('#customerSearch').val('');
    showRecentCustomers();
  });

  $('#customerModal').on('shown.bs.modal', function () {
    $('#customerSearch').focus();
  });

  // Initials for an avatar. Splitting on spaces alone takes punctuation
  // as an initial, turning "Shorif (Global Furniture)" into "S(".
  // Unicode-aware so Bengali names keep their vowel signs.
  function nameInitials(name) {
    var words = String(name || '').replace(/[^\p{L}\p{N}\p{M}]+/gu, ' ').trim().split(/\s+/);
    if (!words[0]) return '?';
    return words.slice(0, 2).map(function (w) {
      return Array.from(w)[0] || '';
    }).join('').toUpperCase();
  }

  function updateCustCurrentBar() {
    if (selectedCustomer) {
      var initials = nameInitials(selectedCustomer.name);
      $('#custCurrentBar .bp-cust-current-avatar').html(initials);
      $('#custCurrentName').text(selectedCustomer.name);
      $('#custCurrentPhone').text(selectedCustomer.phone || '');
    } else {
      $('#custCurrentBar .bp-cust-current-avatar').html('<i class="fa-solid fa-user"></i>');
      $('#custCurrentName').text('Walk-in Customer');
      $('#custCurrentPhone').text('No customer selected');
    }
  }

  // ============================================================
  // CUSTOMER SEARCH — AJAX
  // ============================================================
  // Pre-loaded customers from server
  var recentCustomers = @json($recentCustomers);

  function buildCustomerItem(c) {
    var initials = nameInitials(c.name);
    var due = c.due_amount || 0;
    var dueBadge = due > 0
      ? '<span class="bp-badge bp-badge-warning">' + formatBDT(due).replace('{{ currency_symbol() }} ', '') + ' due</span>'
      : '<span class="bp-badge bp-badge-success">No due</span>';
    return '<button type="button" class="bp-cust-item customer-option" data-name="' + escapeHtml(c.name) + '" data-phone="' + escapeHtml(c.phone || '') + '" data-id="' + c.id + '">' +
      '<div class="bp-cust-item-avatar">' + escapeHtml(initials) + '</div>' +
      '<div class="bp-cust-item-info">' +
        '<div class="bp-cust-item-name">' + escapeHtml(c.name) + '</div>' +
        '<div class="bp-cust-item-phone">' + escapeHtml(c.phone || '') + '</div>' +
      '</div>' +
      '<div class="bp-cust-item-due">' + dueBadge + '</div>' +
    '</button>';
  }

  function renderCustomerList(data, emptyMsg) {
    if (!data || data.length === 0) {
      $('#customerList').html('<div class="bp-cust-empty"><i class="fa-solid fa-user-slash"></i><p>' + escapeHtml(emptyMsg || 'No customers found') + '</p></div>');
    } else {
      var html = '';
      data.forEach(function(c) { html += buildCustomerItem(c); });
      $('#customerList').html(html);
    }
  }

  function showRecentCustomers() {
    renderCustomerList(recentCustomers, 'No customers yet');
  }

  var customerSearchTimeout;
  $('#customerSearch').on('input', function () {
    var q = $(this).val().trim();
    clearTimeout(customerSearchTimeout);
    if (q.length < 2) {
      showRecentCustomers();
      return;
    }
    $('#customerList').html('<div class="bp-cust-empty"><i class="fa-solid fa-spinner fa-spin"></i><p>Searching...</p></div>');
    customerSearchTimeout = setTimeout(function() {
      $.get('{{ route('pos.search-customers') }}', { q: q }, function(data) {
        renderCustomerList(data, 'No customers found');
      });
    }, 300);
  });

  // ============================================================
  // CUSTOMER SELECT
  // ============================================================
  $(document).on('click', '.customer-option', function (e) {
    e.preventDefault();
    selectedCustomer = {
      id: $(this).data('id'),
      name: $(this).data('name'),
      phone: $(this).data('phone')
    };
    $('#selectedCustomerName').text(selectedCustomer.name);
    fetchCustomerAdvance(selectedCustomer.id);
    saveState();
    bootstrap.Modal.getInstance('#customerModal').hide();
  });

  $(document).on('click', '#walkInCustomer', function () {
    selectedCustomer = null;
    customerAdvanceBalance = 0;
    $('#selectedCustomerName').text('Walk-in Customer');
    $('#customerAdvanceBadge').addClass('d-none');
    $('#customerDueBadge').addClass('d-none');
    saveState();
    bootstrap.Modal.getInstance('#customerModal').hide();
  });

  // ============================================================
  // QUICK ADD CUSTOMER — AJAX
  // ============================================================
  $(document).on('click', '#addAndSelectCustomer', function () {
    var $btn = $(this);
    var name = $('#newCustomerName').val().trim();
    var phone = $('#newCustomerPhone').val().trim();
    if (!name || !phone) {
      if (!name) $('#newCustomerName').addClass('is-invalid');
      if (!phone) $('#newCustomerPhone').addClass('is-invalid');
      return;
    }
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Adding...');

    $.post('{{ route('pos.quick-add-customer') }}', {
      name: name,
      phone: phone,
      email: $('#newCustomerEmail').val().trim() || null,
      address: $('#newCustomerAddress').val().trim() || null
    }, function(data) {
      selectedCustomer = { id: data.customer.id, name: data.customer.name, phone: data.customer.phone };
      // Add to recent customers list
      recentCustomers.unshift({ id: data.customer.id, name: data.customer.name, phone: data.customer.phone, due_amount: 0 });
      $('#selectedCustomerName').text(selectedCustomer.name);
      customerAdvanceBalance = 0;
      $('#customerAdvanceBadge').addClass('d-none');
      saveState();
      $('#newCustomerName, #newCustomerPhone, #newCustomerEmail, #newCustomerAddress').val('').removeClass('is-invalid');
      $btn.prop('disabled', false).html('<i class="fa-solid fa-user-plus me-1"></i> Add & Select Customer');
      bootstrap.Modal.getInstance('#customerModal').hide();
    }).fail(function(xhr) {
      $btn.prop('disabled', false).html('<i class="fa-solid fa-user-plus me-1"></i> Add & Select Customer');
      var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to add customer';
      alert(msg);
    });
  });

  // Clear invalid state on input
  $(document).on('input', '#newCustomerName, #newCustomerPhone', function () {
    $(this).removeClass('is-invalid');
  });

  // ============================================================
  // KEYBOARD SHORTCUTS
  // ============================================================
  $(document).on('keydown', function (e) {
    // Function keys work globally regardless of focus
    switch (e.key) {
      case 'F1': e.preventDefault(); $('#posSearch').focus(); return;
      case 'F2': e.preventDefault(); $('#holdOrder').click(); return;
      case 'F3': e.preventDefault(); $('#payNowBtn').click(); return;
      case 'F4': e.preventDefault(); $('#recallOrder').click(); return;
      case 'F8': e.preventDefault(); $('#clearCart').click(); return;
      case 'Escape':
        e.preventDefault();
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
          $(e.target).blur();
        }
        return;
    }
  });

  // ============================================================
  // PAYMENT METHOD TABS (legacy compat — now using split rows)
  // ============================================================
  // Show/hide account selector based on payment method & filter accounts
  $(document).on('change', '.payment-method-select', function () {
    var method = $(this).val();
    var $row = $(this).closest('.bp-split-payment-row');
    var $accountCol = $row.find('.bp-pay-account-col');
    var $accountSelect = $row.find('.payment-account-select');
    if (method === 'cash') {
      $accountCol.addClass('d-none');
      $accountSelect.val('');
    } else {
      $accountSelect.html(buildAccountOptions(method));
      $accountCol.removeClass('d-none');
    }
  });

  // ============================================================
  // ADVANCE ADJUSTMENT — toggle & amount
  // ============================================================
  $(document).on('change', '#useAdvanceCheck', function () {
    var total = $('#cartTotal').data('value') || 0;
    if ($(this).is(':checked')) {
      $('#advanceAmountRow').removeClass('d-none');
      var maxAdv = Math.min(customerAdvanceBalance, total);
      $('#advanceAdjustAmount').attr('max', maxAdv).val(maxAdv).focus();
      // Reduce the first payment row amount
      var remaining = Math.max(0, total - maxAdv);
      $('#paymentRows .payment-amount-input:first').val(remaining > 0 ? remaining : 0);
    } else {
      $('#advanceAmountRow').addClass('d-none');
      $('#advanceAdjustAmount').val('');
      // Restore full amount to first payment row
      $('#paymentRows .payment-amount-input:first').val(total);
    }
    updatePaymentTotals();
  });

  $(document).on('input', '#advanceAdjustAmount', function () {
    var total = $('#cartTotal').data('value') || 0;
    var maxAdv = Math.min(customerAdvanceBalance, total);
    var val = parseFloat($(this).val()) || 0;
    // Clamp to max
    if (val > maxAdv) {
      val = maxAdv;
      $(this).val(val);
    }
    // Update remaining payment row
    var remaining = Math.max(0, total - val);
    $('#paymentRows .payment-amount-input:first').val(remaining > 0 ? remaining : 0);
    updatePaymentTotals();
  });

  $(document).on('click', '#advanceUseAll', function () {
    var total = $('#cartTotal').data('value') || 0;
    var maxAdv = Math.min(customerAdvanceBalance, total);
    $('#advanceAdjustAmount').val(maxAdv).trigger('input');
  });

  // Restore state from localStorage
  if (selectedCustomer) {
    $('#selectedCustomerName').text(selectedCustomer.name);
    fetchCustomerAdvance(selectedCustomer.id);
  }
  updateHeldBadge();
  renderCart();
});
</script>
@endpush
