<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Search Configuration
    |--------------------------------------------------------------------------
    */

    // Minimum query length to trigger a search
    'min_query_length' => 2,

    // Max results per entity type in quick search (dropdown)
    'quick_limit' => 5,

    // Max results per entity type in full search
    'full_limit' => 50,

    // Max navigation/page results
    'nav_limit' => 5,

    /*
    |--------------------------------------------------------------------------
    | Searchable Models
    |--------------------------------------------------------------------------
    | Listed by priority group. All models must implement SearchableInterface.
    */

    'models' => [

        // Core — always searched first
        \Modules\Product\Models\Product::class,
        \Modules\Sale\Models\Sale::class,
        \Modules\Purchase\Models\Purchase::class,
        \Modules\Customer\Models\Customer::class,
        \Modules\Supplier\Models\Supplier::class,

        // High priority
        \Modules\Quotation\Models\Quotation::class,
        \Modules\Customer\Models\CustomerGroup::class,
        \Modules\Supplier\Models\SupplierGroup::class,
        \Modules\Employee\Models\Employee::class,
        \Modules\SaleReturn\Models\SaleReturn::class,
        \Modules\PurchaseReturn\Models\PurchaseReturn::class,
        \Modules\Accounting\Models\Account::class,
        \Modules\Category\Models\Category::class,
        \Modules\Brand\Models\Brand::class,
        \Modules\Unit\Models\Unit::class,
        \Modules\Expense\Models\Expense::class,
        \Modules\Payment\Models\Payment::class,
        \Modules\Accounting\Models\PaymentReceipt::class,

        // Medium priority
        \Modules\Inventory\Models\StockAdjustment::class,
        \Modules\Asset\Models\Asset::class,
        \Modules\Loan\Models\Loan::class,
        \Modules\Loan\Models\Lender::class,
        \Modules\Manufacturing\Models\ProductionOrder::class,
        \Modules\Accounting\Models\JournalEntry::class,

        // Low priority
        \Modules\Ecommerce\Models\EcommerceOrder::class,
        \Modules\Ecommerce\Models\BlogPost::class,
        \Modules\Ecommerce\Models\Coupon::class,
        \Modules\Customer\Models\Area::class,
        \Modules\Branch\Models\Branch::class,
        \Modules\AdSpend\Models\AdCampaign::class,
    ],

];
