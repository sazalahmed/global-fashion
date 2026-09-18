# BizPOS — Definitive Implementation Blueprint

> Created: 30 Mar 2026
> This is the BUILD document. Every file, every column, every relationship is specified.
> Follow BizPOS conventions exactly. No guessing.

---

## Conventions Reference (from existing codebase)

```
MODELS:         Modules/{Module}/app/Models/{Model}.php
SERVICES:       Modules/{Module}/app/Services/{Service}.php
CONTROLLERS:    Modules/{Module}/app/Http/Controllers/{Controller}.php
REQUESTS:       Modules/{Module}/app/Http/Requests/{Request}.php
ROUTES:         Modules/{Module}/routes/web.php
MIGRATIONS:     Modules/{Module}/database/migrations/YYYY_MM_DD_HHMMSS_description.php
VIEWS:          Modules/{Module}/resources/views/{view}.blade.php
EXPORTS:        app/Exports/{Export}.php
IMPORTS:        app/Imports/{Import}.php
SEEDERS:        Modules/{Module}/database/seeders/{Seeder}.php

Money columns:  decimal(15, 2) with cast 'decimal:2'
Status columns: string(20) — never enum
Foreign keys:   foreignId('x_id')->constrained()->nullOnDelete()
Booleans:       boolean('is_x')->default(true|false)
Traits:         SoftDeletes, LogsActivity (with $activityLogName)
Routes:         middleware('auth'), prefix added by RouteServiceProvider (/admin)
Views:          @extends('core::layouts.master')
Settings:       Setting::get('group', 'key', $default)
```

---

# PHASE 1 — Core Business Gaps

---

## 1.1 Customer Groups

### Migration: `Modules/Customer/database/migrations/2026_03_30_100001_create_customer_groups_table.php`

```php
Schema::create('customer_groups', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('description', 500)->nullable();
    $table->decimal('discount_percentage', 5, 2)->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->unique('name');
});
```

### Migration: `Modules/Customer/database/migrations/2026_03_30_100002_replace_customer_group_string_with_fk.php`

```php
// Step 1: Add FK column
Schema::table('customers', function (Blueprint $table) {
    $table->foreignId('customer_group_id')->nullable()->after('tin')
          ->constrained('customer_groups')->nullOnDelete();
    $table->index('customer_group_id');
});

// Step 2: Migrate existing string values to FK
// (run in seeder or migration: create groups from distinct values, then map)

// Step 3: Drop old string column
Schema::table('customers', function (Blueprint $table) {
    $table->dropIndex(['customer_group']);
    $table->dropColumn('customer_group');
});
```

### Model: `Modules/Customer/app/Models/CustomerGroup.php`

```php
<?php

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class CustomerGroup extends Model
{
    protected $fillable = [
        'name', 'description', 'discount_percentage', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
            'is_active'           => 'boolean',
        ];
    }

    // ── Relationships ──
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    // ── Scopes ──
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
```

### Update Model: `Modules/Customer/app/Models/Customer.php`

```php
// ADD to $fillable (replace 'customer_group' with):
'customer_group_id',

// ADD relationship:
public function customerGroup(): BelongsTo
{
    return $this->belongsTo(CustomerGroup::class);
}

// UPDATE scopeByGroup:
public function scopeByGroup(Builder $query, ?int $groupId): Builder
{
    return $groupId ? $query->where('customer_group_id', $groupId) : $query;
}
```

### Controller: `Modules/Customer/app/Http/Controllers/CustomerGroupController.php`

```php
<?php

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Customer\Models\CustomerGroup;

class CustomerGroupController extends Controller
{
    public function index()
    {
        $groups = CustomerGroup::withCount('customers')->latest()->get();
        return view('customer::groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100|unique:customer_groups,name',
            'description'         => 'nullable|string|max:500',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active'           => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        CustomerGroup::create($validated);

        return redirect()->route('customer-groups.index')->with('success', 'Customer group created.');
    }

    public function update(Request $request, CustomerGroup $group)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100|unique:customer_groups,name,' . $group->id,
            'description'         => 'nullable|string|max:500',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active'           => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $group->update($validated);

        return redirect()->route('customer-groups.index')->with('success', 'Customer group updated.');
    }

    public function destroy(CustomerGroup $group)
    {
        if ($group->customers()->count() > 0) {
            return back()->with('error', 'Cannot delete group with assigned customers.');
        }

        $group->delete();
        return redirect()->route('customer-groups.index')->with('success', 'Customer group deleted.');
    }
}
```

### Routes: Add to `Modules/Customer/routes/web.php`

```php
// Add BEFORE the customers resource group:
Route::middleware('auth')->prefix('customer-groups')->name('customer-groups.')->group(function () {
    Route::get('/', [CustomerGroupController::class, 'index'])->name('index');
    Route::post('/', [CustomerGroupController::class, 'store'])->name('store');
    Route::put('/{group}', [CustomerGroupController::class, 'update'])->name('update');
    Route::delete('/{group}', [CustomerGroupController::class, 'destroy'])->name('destroy');
});
```

### View: `Modules/Customer/resources/views/groups/index.blade.php`

- Extends `core::layouts.master`
- Table: Name, Description, Discount %, Customers Count, Status, Actions
- Inline create modal (bp-card with form)
- Inline edit modal (populated via JS)
- Delete with confirmation

### Update Views: Customer create.blade.php + edit.blade.php

- Replace `<input name="customer_group">` with:
```blade
<select name="customer_group_id" class="bp-form-select w-100">
    <option value="">Select Group</option>
    @foreach($customerGroups as $group)
        <option value="{{ $group->id }}" {{ old('customer_group_id', $customer->customer_group_id ?? '') == $group->id ? 'selected' : '' }}>
            {{ $group->name }}{{ $group->discount_percentage > 0 ? ' (' . $group->discount_percentage . '% off)' : '' }}
        </option>
    @endforeach
</select>
```

### Update Controller: CustomerController.php

- In `create()`: add `$customerGroups = CustomerGroup::active()->get();`
- In `edit()`: add `$customerGroups = CustomerGroup::active()->get();`
- Pass to views via `compact()`

### Update Requests: StoreCustomerRequest + UpdateCustomerRequest

- Replace `'customer_group' => 'required|string|in:Retail,Wholesale,VIP,Corporate'`
- With: `'customer_group_id' => 'nullable|exists:customer_groups,id'`

### Sidebar: `Modules/Core/resources/views/partials/sidebar.blade.php`

- Add `<li><a href="{{ route('customer-groups.index') }}" class="menu-link {{ request()->routeIs('customer-groups.*') ? 'active' : '' }}">Customer Groups</a></li>` inside Customers submenu

### Seeder: `Modules/Customer/database/seeders/CustomerGroupSeeder.php`

```php
CustomerGroup::insert([
    ['name' => 'Retail', 'description' => 'Regular retail customers', 'discount_percentage' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ['name' => 'Wholesale', 'description' => 'Bulk buyers', 'discount_percentage' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ['name' => 'VIP', 'description' => 'Premium customers', 'discount_percentage' => 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ['name' => 'Corporate', 'description' => 'Business accounts', 'discount_percentage' => 8, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
]);
```

### Files Checklist

- [ ] Migration: create_customer_groups_table
- [ ] Migration: replace_customer_group_string_with_fk
- [ ] Model: CustomerGroup.php
- [ ] Model: Customer.php (update relationships, fillable, scope)
- [ ] Controller: CustomerGroupController.php
- [ ] Controller: CustomerController.php (update create/edit to pass groups)
- [ ] Request: StoreCustomerRequest.php (update validation)
- [ ] Request: UpdateCustomerRequest.php (update validation)
- [ ] Route: web.php (add customer-groups routes)
- [ ] View: groups/index.blade.php
- [ ] View: create.blade.php (update dropdown)
- [ ] View: edit.blade.php (update dropdown)
- [ ] View: index.blade.php (update group display column)
- [ ] View: show.blade.php (update group display)
- [ ] Sidebar: sidebar.blade.php (add link)
- [ ] Seeder: CustomerGroupSeeder.php

---

## 1.2 Supplier Groups

### Migration: `Modules/Supplier/database/migrations/2026_03_30_100003_create_supplier_groups_table.php`

```php
Schema::create('supplier_groups', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('description', 500)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->unique('name');
});
```

### Migration: `Modules/Supplier/database/migrations/2026_03_30_100004_add_supplier_group_id_to_suppliers.php`

```php
Schema::table('suppliers', function (Blueprint $table) {
    $table->foreignId('supplier_group_id')->nullable()->after('status')
          ->constrained('supplier_groups')->nullOnDelete();
    $table->index('supplier_group_id');
});
```

### Model: `Modules/Supplier/app/Models/SupplierGroup.php`

```php
<?php

namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class SupplierGroup extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
```

### Update Model: Supplier.php

```php
// ADD to $fillable:
'supplier_group_id',

// ADD relationship:
public function supplierGroup(): BelongsTo
{
    return $this->belongsTo(SupplierGroup::class);
}
```

### Controller: `Modules/Supplier/app/Http/Controllers/SupplierGroupController.php`

Same pattern as CustomerGroupController — CRUD with `withCount('suppliers')`, validation, destroy guard.

### Routes: Add to `Modules/Supplier/routes/web.php`

```php
Route::middleware('auth')->prefix('supplier-groups')->name('supplier-groups.')->group(function () {
    Route::get('/', [SupplierGroupController::class, 'index'])->name('index');
    Route::post('/', [SupplierGroupController::class, 'store'])->name('store');
    Route::put('/{group}', [SupplierGroupController::class, 'update'])->name('update');
    Route::delete('/{group}', [SupplierGroupController::class, 'destroy'])->name('destroy');
});
```

### View: `Modules/Supplier/resources/views/groups/index.blade.php`

Same pattern as customer groups — table with inline modal CRUD.

### Sidebar Update

Change Suppliers from single link to submenu:
```blade
<li class="menu-item {{ request()->routeIs('supplier.*', 'supplier-groups.*') ? 'open' : '' }}">
  <a href="#" class="menu-link {{ request()->routeIs('supplier.*', 'supplier-groups.*') ? 'active' : '' }}" data-toggle="submenu">
    <i class="fa-solid fa-truck-field"></i>
    <span class="menu-text">Suppliers</span>
    <i class="fa-solid fa-chevron-right menu-arrow"></i>
  </a>
  <ul class="submenu">
    <li><a href="{{ route('supplier.index') }}" class="menu-link {{ request()->routeIs('supplier.*') ? 'active' : '' }}">All Suppliers</a></li>
    <li><a href="{{ route('supplier-groups.index') }}" class="menu-link {{ request()->routeIs('supplier-groups.*') ? 'active' : '' }}">Supplier Groups</a></li>
  </ul>
</li>
```

### Seeder: `Modules/Supplier/database/seeders/SupplierGroupSeeder.php`

Seeds: Local Manufacturer, Importer, Distributor, Wholesaler

### Files Checklist

- [ ] Migration: create_supplier_groups_table
- [ ] Migration: add_supplier_group_id_to_suppliers
- [ ] Model: SupplierGroup.php
- [ ] Model: Supplier.php (update)
- [ ] Controller: SupplierGroupController.php
- [ ] Controller: SupplierController.php (update create/edit)
- [ ] Route: web.php (add supplier-groups)
- [ ] View: groups/index.blade.php
- [ ] View: create.blade.php (add dropdown)
- [ ] View: edit.blade.php (add dropdown)
- [ ] Sidebar: sidebar.blade.php (convert to submenu)
- [ ] Seeder: SupplierGroupSeeder.php

---

## 1.3 Supplier CSV Import

### Import Class: `app/Imports/SuppliersImport.php`

```php
<?php

namespace App\Imports;

use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierGroup;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\Importable;

class SuppliersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use Importable, SkipsErrors;

    private int $imported = 0;
    private array $groupCache = [];

    public function model(array $row): ?Supplier
    {
        if (!empty($row['phone']) && Supplier::where('phone', $row['phone'])->exists()) {
            return null;
        }

        $this->imported++;

        return new Supplier([
            'company_name'      => $row['company_name'],
            'contact_person'    => $row['contact_person'],
            'phone'             => $row['phone'] ?? null,
            'email'             => $row['email'] ?? null,
            'division'          => $row['division'] ?? null,
            'district'          => $row['district'] ?? null,
            'area'              => $row['area'] ?? null,
            'address'           => $row['address'] ?? null,
            'opening_balance'   => $row['opening_balance'] ?? 0,
            'credit_limit'      => $row['credit_limit'] ?? 0,
            'payment_terms'     => $row['payment_terms'] ?? 'Net 30',
            'supplier_group_id' => $this->resolveGroupId($row['supplier_group'] ?? null),
            'status'            => 'active',
        ]);
    }

    public function rules(): array
    {
        return [
            'company_name'    => 'required|string|max:255',
            'contact_person'  => 'required|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'credit_limit'    => 'nullable|numeric|min:0',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    private function resolveGroupId(?string $groupName): ?int
    {
        if (empty($groupName)) {
            return null;
        }

        if (!isset($this->groupCache[$groupName])) {
            $this->groupCache[$groupName] = SupplierGroup::where('name', $groupName)->value('id');
        }

        return $this->groupCache[$groupName];
    }
}
```

### Update: Global import handler in `routes/web.php`

The existing `POST /import/{module}` route already handles module dispatch. Add `'suppliers'` case to the import controller switch.

### Files Checklist

- [ ] Import: app/Imports/SuppliersImport.php
- [ ] Update: ImportController (add 'suppliers' case)
- [ ] View: Supplier index (add import button + sample CSV download)

---

## 1.4 Expense Vendors

### Migration: `Modules/Expense/database/migrations/2026_03_30_100005_create_expense_vendors_table.php`

```php
Schema::create('expense_vendors', function (Blueprint $table) {
    $table->id();
    $table->string('name', 150);
    $table->string('company_name', 255)->nullable();
    $table->string('phone', 20)->nullable();
    $table->string('email')->nullable();
    $table->string('contact_person', 150)->nullable();
    $table->text('address')->nullable();
    $table->decimal('opening_balance', 15, 2)->default(0);
    $table->decimal('total_expense', 15, 2)->default(0);
    $table->decimal('total_paid', 15, 2)->default(0);
    $table->decimal('advance_balance', 15, 2)->default(0);
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
    $table->index('is_active');
    $table->index('phone');
});
```

### Migration: `Modules/Expense/database/migrations/2026_03_30_100006_add_vendor_and_due_fields_to_expenses.php`

```php
Schema::table('expenses', function (Blueprint $table) {
    $table->foreignId('expense_vendor_id')->nullable()->after('expense_category_id')
          ->constrained('expense_vendors')->nullOnDelete();
    $table->date('due_date')->nullable()->after('expense_date');
    $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
    $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
    $table->string('payment_status', 20)->default('unpaid')->after('status');
    $table->index('expense_vendor_id');
    $table->index('payment_status');
});
```

### Model: `Modules/Expense/app/Models/ExpenseVendor.php`

```php
<?php

namespace Modules\Expense\Models;

use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseVendor extends Model
{
    use SoftDeletes, LogsActivity;

    protected string $activityLogName = 'expense_vendors';

    protected $fillable = [
        'name', 'company_name', 'phone', 'email', 'contact_person',
        'address', 'opening_balance', 'total_expense', 'total_paid',
        'advance_balance', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'total_expense'   => 'decimal:2',
            'total_paid'      => 'decimal:2',
            'advance_balance' => 'decimal:2',
            'is_active'       => 'boolean',
        ];
    }

    // ── Relationships ──
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_vendor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ──
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('company_name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    // ── Accessors ──
    public function getDueAmountAttribute(): float
    {
        return (float) ($this->total_expense - $this->total_paid + $this->opening_balance);
    }
}
```

### Update Model: `Modules/Expense/app/Models/Expense.php`

```php
// ADD to $fillable:
'expense_vendor_id', 'due_date', 'paid_amount', 'due_amount', 'payment_status',

// ADD to casts():
'due_date'       => 'date',
'paid_amount'    => 'decimal:2',
'due_amount'     => 'decimal:2',

// ADD relationship:
public function expenseVendor(): BelongsTo
{
    return $this->belongsTo(ExpenseVendor::class);
}
```

### Service: `Modules/Expense/app/Services/ExpenseVendorService.php`

```php
<?php

namespace Modules\Expense\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Expense\Models\ExpenseVendor;
use Modules\Expense\Models\Expense;

class ExpenseVendorService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return ExpenseVendor::withCount('expenses')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when(isset($filters['status']), fn ($q) => $q->where('is_active', $filters['status'] === 'active'))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ExpenseVendor
    {
        return ExpenseVendor::with(['expenses' => fn ($q) => $q->latest('expense_date')->limit(20)])
            ->findOrFail($id);
    }

    public function create(array $data): ExpenseVendor
    {
        $data['created_by'] = auth()->id();
        return ExpenseVendor::create($data);
    }

    public function update(ExpenseVendor $vendor, array $data): ExpenseVendor
    {
        $vendor->update($data);
        return $vendor->fresh();
    }

    public function getLedger(ExpenseVendor $vendor, ?string $from = null, ?string $to = null): array
    {
        $expenses = Expense::where('expense_vendor_id', $vendor->id)
            ->when($from, fn ($q, $d) => $q->where('expense_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->where('expense_date', '<=', $d))
            ->orderBy('expense_date')
            ->get(['id', 'expense_number', 'expense_date', 'total_amount', 'paid_amount', 'due_amount', 'payment_status']);

        return [
            'vendor'   => $vendor,
            'expenses' => $expenses,
            'totals'   => [
                'total_expense' => $expenses->sum('total_amount'),
                'total_paid'    => $expenses->sum('paid_amount'),
                'total_due'     => $expenses->sum('due_amount'),
            ],
        ];
    }

    public function payDue(ExpenseVendor $vendor, array $data): void
    {
        DB::transaction(function () use ($vendor, $data) {
            // Update expense paid/due amounts
            if (!empty($data['expense_id'])) {
                $expense = Expense::findOrFail($data['expense_id']);
                $expense->paid_amount += $data['amount'];
                $expense->due_amount = $expense->total_amount - $expense->paid_amount;
                $expense->payment_status = $expense->due_amount <= 0 ? 'paid' : 'partial';
                $expense->save();
            }

            // Update vendor totals
            $vendor->total_paid += $data['amount'];
            $vendor->save();
        });
    }

    public function addAdvance(ExpenseVendor $vendor, float $amount): void
    {
        $vendor->increment('advance_balance', $amount);
    }

    public function getStats(): array
    {
        return [
            'total'      => ExpenseVendor::count(),
            'active'     => ExpenseVendor::where('is_active', true)->count(),
            'total_due'  => ExpenseVendor::selectRaw('SUM(total_expense - total_paid + opening_balance) as due')->value('due') ?? 0,
        ];
    }
}
```

### Controller: `Modules/Expense/app/Http/Controllers/ExpenseVendorController.php`

```php
<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Expense\Models\ExpenseVendor;
use Modules\Expense\Services\ExpenseVendorService;

class ExpenseVendorController extends Controller
{
    public function __construct(
        protected ExpenseVendorService $service,
    ) {}

    public function index(Request $request)
    {
        $stats = $this->service->getStats();
        $vendors = $this->service->list($request->all());
        return view('expense::vendors.index', compact('stats', 'vendors'));
    }

    public function create()
    {
        return view('expense::vendors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:150',
            'company_name'    => 'nullable|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'contact_person'  => 'nullable|string|max:150',
            'address'         => 'nullable|string|max:2000',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $vendor = $this->service->create($validated);

        return redirect()->route('expense-vendors.show', $vendor)
            ->with('success', 'Expense vendor created.');
    }

    public function show(ExpenseVendor $vendor)
    {
        $vendor = $this->service->find($vendor->id);
        $ledger = $this->service->getLedger($vendor);
        return view('expense::vendors.show', compact('vendor', 'ledger'));
    }

    public function edit(ExpenseVendor $vendor)
    {
        return view('expense::vendors.edit', compact('vendor'));
    }

    public function update(Request $request, ExpenseVendor $vendor)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:150',
            'company_name'    => 'nullable|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'contact_person'  => 'nullable|string|max:150',
            'address'         => 'nullable|string|max:2000',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $this->service->update($vendor, $validated);

        return redirect()->route('expense-vendors.show', $vendor)
            ->with('success', 'Expense vendor updated.');
    }

    public function destroy(ExpenseVendor $vendor)
    {
        if ($vendor->expenses()->count() > 0) {
            return back()->with('error', 'Cannot delete vendor with linked expenses.');
        }
        $vendor->delete();
        return redirect()->route('expense-vendors.index')->with('success', 'Vendor deleted.');
    }

    public function payDue(Request $request, ExpenseVendor $vendor)
    {
        $validated = $request->validate([
            'expense_id' => 'nullable|exists:expenses,id',
            'amount'     => 'required|numeric|min:0.01',
        ]);
        $this->service->payDue($vendor, $validated);
        return back()->with('success', 'Payment recorded.');
    }

    public function addAdvance(Request $request, ExpenseVendor $vendor)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01']);
        $this->service->addAdvance($vendor, $request->amount);
        return back()->with('success', 'Advance recorded.');
    }
}
```

### Routes: Add to `Modules/Expense/routes/web.php`

```php
Route::middleware('auth')->prefix('expense-vendors')->name('expense-vendors.')->group(function () {
    Route::get('/', [ExpenseVendorController::class, 'index'])->name('index');
    Route::get('/create', [ExpenseVendorController::class, 'create'])->name('create');
    Route::post('/', [ExpenseVendorController::class, 'store'])->name('store');
    Route::get('/{vendor}', [ExpenseVendorController::class, 'show'])->name('show');
    Route::get('/{vendor}/edit', [ExpenseVendorController::class, 'edit'])->name('edit');
    Route::put('/{vendor}', [ExpenseVendorController::class, 'update'])->name('update');
    Route::delete('/{vendor}', [ExpenseVendorController::class, 'destroy'])->name('destroy');
    Route::post('/{vendor}/pay-due', [ExpenseVendorController::class, 'payDue'])->name('pay-due');
    Route::post('/{vendor}/add-advance', [ExpenseVendorController::class, 'addAdvance'])->name('add-advance');
});
```

### Views

- `expense::vendors/index.blade.php` — stats cards (total, active, total due) + table + search
- `expense::vendors/create.blade.php` — form (name, company, phone, email, contact, address, opening_balance)
- `expense::vendors/edit.blade.php` — same form pre-filled
- `expense::vendors/show.blade.php` — vendor info card + ledger table + pay due modal + add advance modal

### Update Expense create/edit views

- Add vendor dropdown: `<select name="expense_vendor_id">` populated from `ExpenseVendor::active()->get()`
- Add due_date field: `<input type="date" name="due_date">`

### Sidebar

Add "Expense Vendors" under Finance section in sidebar.blade.php

### Files Checklist

- [ ] Migration: create_expense_vendors_table
- [ ] Migration: add_vendor_and_due_fields_to_expenses
- [ ] Model: ExpenseVendor.php
- [ ] Model: Expense.php (update fillable, casts, relationship)
- [ ] Service: ExpenseVendorService.php
- [ ] Controller: ExpenseVendorController.php
- [ ] Route: web.php (add expense-vendors group)
- [ ] View: vendors/index.blade.php
- [ ] View: vendors/create.blade.php
- [ ] View: vendors/edit.blade.php
- [ ] View: vendors/show.blade.php (with ledger)
- [ ] View: expenses create.blade.php (update — add vendor, due_date)
- [ ] View: expenses edit.blade.php (update)
- [ ] Sidebar: sidebar.blade.php (add link)

---

## 1.5 POS Cart Hold & Resume

### Migration: `Modules/POS/database/migrations/2026_03_30_100007_create_pos_held_carts_table.php`

```php
Schema::create('pos_held_carts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->string('reference', 100)->nullable();
    $table->json('items');
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('discount_amount', 15, 2)->default(0);
    $table->text('notes')->nullable();
    $table->foreignId('held_by')->constrained('users');
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->timestamps();
    $table->index('held_by');
    $table->index('branch_id');
});
```

### Model: `Modules/POS/app/Models/HeldCart.php`

```php
<?php

namespace Modules\POS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Branch\Models\Branch;
use Modules\Customer\Models\Customer;

class HeldCart extends Model
{
    protected $table = 'pos_held_carts';

    protected $fillable = [
        'customer_id', 'reference', 'items', 'subtotal',
        'discount_amount', 'notes', 'held_by', 'branch_id',
    ];

    protected function casts(): array
    {
        return [
            'items'           => 'array',
            'subtotal'        => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function heldByUser(): BelongsTo { return $this->belongsTo(User::class, 'held_by'); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
```

### Controller: Add to `Modules/POS/app/Http/Controllers/POSController.php`

```php
public function holdCart(Request $request): JsonResponse
{
    $validated = $request->validate([
        'customer_id' => 'nullable|exists:customers,id',
        'reference'   => 'nullable|string|max:100',
        'items'       => 'required|array|min:1',
        'subtotal'    => 'required|numeric|min:0',
        'discount_amount' => 'nullable|numeric|min:0',
        'notes'       => 'nullable|string|max:500',
    ]);

    $cart = HeldCart::create([
        ...$validated,
        'held_by'   => auth()->id(),
        'branch_id' => auth()->user()->branch_id,
    ]);

    return response()->json(['success' => true, 'cart_id' => $cart->id]);
}

public function getHeldCarts(): JsonResponse
{
    $carts = HeldCart::with('customer:id,name,phone')
        ->where('held_by', auth()->id())
        ->latest()
        ->get(['id', 'customer_id', 'reference', 'subtotal', 'items', 'created_at']);

    return response()->json($carts);
}

public function resumeCart(HeldCart $heldCart): JsonResponse
{
    $data = $heldCart->toArray();
    $heldCart->delete();
    return response()->json($data);
}

public function deleteHeldCart(HeldCart $heldCart): JsonResponse
{
    $heldCart->delete();
    return response()->json(['success' => true]);
}
```

### Routes: Add to `Modules/POS/routes/web.php`

```php
Route::post('/pos/hold-cart', [POSController::class, 'holdCart'])->name('pos.hold-cart');
Route::get('/pos/held-carts', [POSController::class, 'getHeldCarts'])->name('pos.held-carts');
Route::post('/pos/resume-cart/{heldCart}', [POSController::class, 'resumeCart'])->name('pos.resume-cart');
Route::delete('/pos/held-cart/{heldCart}', [POSController::class, 'deleteHeldCart'])->name('pos.delete-held-cart');
```

### View Update: POS terminal

- Add "Hold" button next to payment button
- Add "Held Carts" badge/button showing count
- Held carts panel (sidebar or modal) listing saved carts with Resume/Delete actions

### Files Checklist

- [ ] Migration: create_pos_held_carts_table
- [ ] Model: HeldCart.php
- [ ] Controller: POSController.php (add 4 methods)
- [ ] Route: web.php (add 4 routes)
- [ ] View: POS terminal (add hold button, held carts panel)

---

## 1.6 — 1.9 (DTS Report, Stock Reconciliation, Opening Balance UI, Payment Account Ledger)

> These follow the same patterns as above. Keeping the blueprint concise — the detailed schema, routes, and logic are already specified in `implementation-plan.md`. The key files for each:

### 1.6 DTS Report
- [ ] Controller: ReportController.php (add `dts()` method)
- [ ] Service: ReportService.php (add `getDailyTransactionSummary()`)
- [ ] View: Modules/Report/resources/views/dts.blade.php
- [ ] Export: app/Exports/DTSExport.php
- [ ] Route: `GET /reports/dts`
- [ ] Sidebar: Add under Reports

### 1.7 Stock Reconciliation
- [ ] Command: app/Console/Commands/StockReconcile.php
- [ ] Command: app/Console/Commands/LedgerReconcile.php
- [ ] Service: Modules/Inventory/app/Services/ReconciliationService.php
- [ ] Controller: InventoryController.php (add `reconciliation()`, `runReconciliation()`)
- [ ] View: Modules/Inventory/resources/views/reconciliation.blade.php
- [ ] Route: `GET /inventory/reconciliation`, `POST /inventory/reconciliation/run`
- [ ] Sidebar: Add under Stock submenu

### 1.8 Opening Balance UI
- [ ] Controller: AccountingController.php (add `openingBalances()`, `saveOpeningBalances()`)
- [ ] View: Modules/Accounting/resources/views/opening-balances.blade.php
- [ ] Route: `GET /accounting/opening-balances`, `POST /accounting/opening-balances`
- [ ] Sidebar: Add under Accounting

### 1.9 Payment Account Ledger
- [ ] Service: Modules/Payment/app/Services/PaymentAccountService.php (add `getAccountLedger()`)
- [ ] Controller: PaymentAccountController.php (add `ledger()`)
- [ ] View: Modules/Payment/resources/views/accounts/ledger.blade.php
- [ ] Export: app/Exports/AccountLedgerExport.php
- [ ] Route: `GET /payment-accounts/{account}/ledger`, `GET /payment-accounts/{account}/ledger/export`

---

# PHASE 2 — Operational Enhancements

### 2.1 Hierarchical Expense Categories
- [ ] Migration: add_parent_id_to_expense_categories
- [ ] Model: ExpenseCategory.php (add `parent()`, `children()`, `scopeRoots()`)
- [ ] View: Update category list (tree view), expense forms (nested dropdown)

### 2.2 Purchase Return Types
- [ ] Migration: create_purchase_return_types_table
- [ ] Migration: add_return_type_id_to_purchase_returns
- [ ] Model: PurchaseReturnType.php
- [ ] Controller: PurchaseReturnTypeController.php
- [ ] Route: `purchase-return-types` resource
- [ ] View: types/index.blade.php
- [ ] Seeder: PurchaseReturnTypeSeeder.php
- [ ] Update: Purchase return create/edit (add type dropdown)

### 2.3 Additional Reports (6 types)
For each: add method to ReportController + ReportService + blade view + export class
- [ ] Category-wise Sales: `GET /reports/category-wise`
- [ ] Monthly Summary: `GET /reports/monthly-summary`
- [ ] Detail Sales: `GET /reports/detail-sales`
- [ ] Receivables Aging: `GET /reports/receivables-aging`
- [ ] Cash Movement: `GET /reports/cash-movement`
- [ ] Supplier Payments: `GET /reports/supplier-payments`
- [ ] Sidebar: Add all under Reports submenu

### 2.4 Weekend & Holiday Setup
- [ ] Migration: create_weekend_days_table
- [ ] Migration: create_holidays_table
- [ ] Model: WeekendDay.php, Holiday.php
- [ ] Controller: AttendanceConfigController.php
- [ ] Route: `attendance/config` routes
- [ ] View: config.blade.php (tabbed)
- [ ] Seeder: WeekendDaySeeder.php (Friday + Saturday for BD)

### 2.5 Email Configuration & Templates
- [ ] Migration: create_email_templates_table
- [ ] Model: EmailTemplate.php
- [ ] Controller: EmailSettingController.php
- [ ] Service: EmailService.php
- [ ] Route: `settings/email` routes
- [ ] View: email/config.blade.php, email/templates.blade.php, email/template-edit.blade.php
- [ ] Seeder: EmailTemplateSeeder.php

### 2.6 Export Classes (7 new)
- [ ] app/Exports/StockExport.php
- [ ] app/Exports/EmployeesExport.php
- [ ] app/Exports/PayrollExport.php
- [ ] app/Exports/AssetsExport.php
- [ ] app/Exports/SuppliersExport.php
- [ ] app/Exports/AccountLedgerExport.php
- [ ] app/Exports/ReceivablesExport.php
- [ ] Update: Add export buttons to each module's index view

### 2.7 POS Settings Enhancement
- [ ] Controller: POSController.php (add `settings()`, `saveSettings()`)
- [ ] Route: `GET /pos/settings`, `POST /pos/settings`
- [ ] View: Modules/POS/resources/views/settings.blade.php
- [ ] Sidebar: Add "POS Settings"

### 2.8 Sidebar Menu Configuration
- [ ] Controller: SettingController.php (add `sidebarConfig()`, `saveSidebarConfig()`)
- [ ] Route: `GET /settings/sidebar`, `POST /settings/sidebar`
- [ ] View: Modules/Setting/resources/views/sidebar-config.blade.php
- [ ] Update: sidebar.blade.php (wrap each section in `@if(setting())` checks)

---

# PHASE 3 — Advanced Features

### 3.1 Service Sales Flow
- [ ] Update: SaleService.php — skip stock deduction for product_type='service'
- [ ] Update: Sale create view — service product filter

### 3.2 Customer Area Management
- [ ] Migration: create_areas_table
- [ ] Migration: add_area_id_to_customers
- [ ] Model: Area.php (self-referencing parent/children)
- [ ] Controller: AreaController.php
- [ ] Route: `customer-areas` routes
- [ ] View: areas/index.blade.php (tree)
- [ ] Seeder: AreaSeeder.php (BD divisions)
- [ ] Update: Customer forms (cascading select)

### 3.3 Offset Customer Due with Advance
- [ ] Controller: CustomerController.php (add `offsetDue()`)
- [ ] Service: CustomerService.php (add offset logic with payment allocation)
- [ ] Route: `POST /customers/{customer}/offset-due`
- [ ] View: Customer show page (add offset button + modal)

### 3.4 Other Income / Non-Transaction Dues
- [ ] Migration: create_other_transactions_table
- [ ] Model: OtherTransaction.php
- [ ] Service: OtherTransactionService.php
- [ ] Controller: OtherTransactionController.php
- [ ] Route: `accounting/other-transactions` resource
- [ ] View: other-transactions/index.blade.php, create.blade.php
- [ ] Sidebar: Add under Accounting

### 3.5 Activity Log Enhancement
- [ ] Update: 21 models — add `use LogsActivity` trait (see list in implementation-plan.md)
- [ ] Update: EventServiceProvider — login/logout/failed listeners
- [ ] Update: Activity views — module tabs, timeline view, export
- [ ] Export: app/Exports/ActivityLogExport.php
- [ ] Route: `GET /activity/export`
- [ ] Scheduler: Auto-cleanup old logs

---

# PHASE 4 — Logging, Printing & System Admin

### 4.1 Laravel System Log Viewer
- [ ] Service: Modules/Setting/app/Services/LogViewerService.php
- [ ] Controller: Modules/Setting/app/Http/Controllers/SystemLogController.php
- [ ] Route: `system-logs` routes (index, show, download, clear, delete)
- [ ] View: logs/index.blade.php (file list)
- [ ] View: logs/show.blade.php (parsed entries with level badges, expandable traces)
- [ ] Sidebar: Add "System Logs" under System

### 4.2 Activity Log Enhancement
(Covered in 3.5)

### 4.3 Printer IP Management
- [ ] Migration: create_printers_table
- [ ] Model: Modules/Setting/app/Models/Printer.php
- [ ] Service: Modules/Setting/app/Services/PrinterService.php
- [ ] Service: app/Services/EscPosService.php (receipt builder)
- [ ] Controller: Modules/Setting/app/Http/Controllers/PrinterController.php
- [ ] Route: `printers` resource + test/print-job routes
- [ ] View: printers/index.blade.php, create.blade.php, edit.blade.php
- [ ] Sidebar: Add "Printers" under System
- [ ] Update: POS settings — add default printer dropdown
- [ ] JS: Add `printToNetworkPrinter()` helper to app.js

### 4.4–4.7 (Maintenance, Cache, Pagination, SEO)
- [ ] SettingController.php: add toggleMaintenance(), clearCache()
- [ ] Settings page: add sections for pagination, maintenance, cache
- [ ] eCommerce settings: add SEO tab

---

# MASTER FILES CHECKLIST

## New Files to Create

| # | File | Phase |
|---|------|-------|
| 1 | `Modules/Customer/database/migrations/xxxx_create_customer_groups_table.php` | 1.1 |
| 2 | `Modules/Customer/database/migrations/xxxx_replace_customer_group_with_fk.php` | 1.1 |
| 3 | `Modules/Customer/app/Models/CustomerGroup.php` | 1.1 |
| 4 | `Modules/Customer/app/Http/Controllers/CustomerGroupController.php` | 1.1 |
| 5 | `Modules/Customer/resources/views/groups/index.blade.php` | 1.1 |
| 6 | `Modules/Customer/database/seeders/CustomerGroupSeeder.php` | 1.1 |
| 7 | `Modules/Supplier/database/migrations/xxxx_create_supplier_groups_table.php` | 1.2 |
| 8 | `Modules/Supplier/database/migrations/xxxx_add_supplier_group_id_to_suppliers.php` | 1.2 |
| 9 | `Modules/Supplier/app/Models/SupplierGroup.php` | 1.2 |
| 10 | `Modules/Supplier/app/Http/Controllers/SupplierGroupController.php` | 1.2 |
| 11 | `Modules/Supplier/resources/views/groups/index.blade.php` | 1.2 |
| 12 | `Modules/Supplier/database/seeders/SupplierGroupSeeder.php` | 1.2 |
| 13 | `app/Imports/SuppliersImport.php` | 1.3 |
| 14 | `Modules/Expense/database/migrations/xxxx_create_expense_vendors_table.php` | 1.4 |
| 15 | `Modules/Expense/database/migrations/xxxx_add_vendor_and_due_fields_to_expenses.php` | 1.4 |
| 16 | `Modules/Expense/app/Models/ExpenseVendor.php` | 1.4 |
| 17 | `Modules/Expense/app/Services/ExpenseVendorService.php` | 1.4 |
| 18 | `Modules/Expense/app/Http/Controllers/ExpenseVendorController.php` | 1.4 |
| 19 | `Modules/Expense/resources/views/vendors/index.blade.php` | 1.4 |
| 20 | `Modules/Expense/resources/views/vendors/create.blade.php` | 1.4 |
| 21 | `Modules/Expense/resources/views/vendors/edit.blade.php` | 1.4 |
| 22 | `Modules/Expense/resources/views/vendors/show.blade.php` | 1.4 |
| 23 | `Modules/POS/database/migrations/xxxx_create_pos_held_carts_table.php` | 1.5 |
| 24 | `Modules/POS/app/Models/HeldCart.php` | 1.5 |
| 25 | `Modules/Report/resources/views/dts.blade.php` | 1.6 |
| 26 | `app/Exports/DTSExport.php` | 1.6 |
| 27 | `app/Console/Commands/StockReconcile.php` | 1.7 |
| 28 | `app/Console/Commands/LedgerReconcile.php` | 1.7 |
| 29 | `Modules/Inventory/app/Services/ReconciliationService.php` | 1.7 |
| 30 | `Modules/Inventory/resources/views/reconciliation.blade.php` | 1.7 |
| 31 | `Modules/Accounting/resources/views/opening-balances.blade.php` | 1.8 |
| 32 | `Modules/Payment/resources/views/accounts/ledger.blade.php` | 1.9 |
| 33 | `app/Exports/AccountLedgerExport.php` | 1.9 |
| 34 | `Modules/Expense/database/migrations/xxxx_add_parent_id_to_expense_categories.php` | 2.1 |
| 35 | `Modules/Purchase/database/migrations/xxxx_create_purchase_return_types_table.php` | 2.2 |
| 36 | `Modules/Purchase/database/migrations/xxxx_add_return_type_id_to_purchase_returns.php` | 2.2 |
| 37 | `Modules/Purchase/app/Models/PurchaseReturnType.php` | 2.2 |
| 38 | `Modules/Purchase/app/Http/Controllers/PurchaseReturnTypeController.php` | 2.2 |
| 39 | 6x Report views + 6x Export classes | 2.3 |
| 40 | `Modules/Attendance/database/migrations/xxxx_create_weekend_days_table.php` | 2.4 |
| 41 | `Modules/Attendance/database/migrations/xxxx_create_holidays_table.php` | 2.4 |
| 42 | `Modules/Attendance/app/Models/WeekendDay.php` | 2.4 |
| 43 | `Modules/Attendance/app/Models/Holiday.php` | 2.4 |
| 44 | `Modules/Attendance/app/Http/Controllers/AttendanceConfigController.php` | 2.4 |
| 45 | `Modules/Setting/database/migrations/xxxx_create_email_templates_table.php` | 2.5 |
| 46 | `Modules/Setting/app/Models/EmailTemplate.php` | 2.5 |
| 47 | `Modules/Setting/app/Http/Controllers/EmailSettingController.php` | 2.5 |
| 48 | `Modules/Setting/app/Services/EmailService.php` | 2.5 |
| 49 | 7x Export classes (Stock, Employees, Payroll, Assets, Suppliers, Receivables, AccountLedger) | 2.6 |
| 50 | `Modules/POS/resources/views/settings.blade.php` | 2.7 |
| 51 | `Modules/Setting/resources/views/sidebar-config.blade.php` | 2.8 |
| 52 | `Modules/Customer/database/migrations/xxxx_create_areas_table.php` | 3.2 |
| 53 | `Modules/Customer/app/Models/Area.php` | 3.2 |
| 54 | `Modules/Customer/app/Http/Controllers/AreaController.php` | 3.2 |
| 55 | `Modules/Accounting/database/migrations/xxxx_create_other_transactions_table.php` | 3.4 |
| 56 | `Modules/Accounting/app/Models/OtherTransaction.php` | 3.4 |
| 57 | `Modules/Accounting/app/Http/Controllers/OtherTransactionController.php` | 3.4 |
| 58 | `app/Exports/ActivityLogExport.php` | 3.5 |
| 59 | `Modules/Setting/app/Services/LogViewerService.php` | 4.1 |
| 60 | `Modules/Setting/app/Http/Controllers/SystemLogController.php` | 4.1 |
| 61 | `Modules/Setting/resources/views/logs/index.blade.php` | 4.1 |
| 62 | `Modules/Setting/resources/views/logs/show.blade.php` | 4.1 |
| 63 | `Modules/Setting/database/migrations/xxxx_create_printers_table.php` | 4.3 |
| 64 | `Modules/Setting/app/Models/Printer.php` | 4.3 |
| 65 | `Modules/Setting/app/Services/PrinterService.php` | 4.3 |
| 66 | `app/Services/EscPosService.php` | 4.3 |
| 67 | `Modules/Setting/app/Http/Controllers/PrinterController.php` | 4.3 |

## Existing Files to Update

| # | File | What Changes | Phase |
|---|------|-------------|-------|
| 1 | `Modules/Customer/app/Models/Customer.php` | Replace customer_group with customer_group_id FK, add relationship | 1.1 |
| 2 | `Modules/Customer/app/Http/Controllers/CustomerController.php` | Pass customerGroups to create/edit views | 1.1 |
| 3 | `Modules/Customer/app/Http/Requests/StoreCustomerRequest.php` | Update validation rule | 1.1 |
| 4 | `Modules/Customer/app/Http/Requests/UpdateCustomerRequest.php` | Update validation rule | 1.1 |
| 5 | `Modules/Customer/resources/views/create.blade.php` | Replace text input with select | 1.1 |
| 6 | `Modules/Customer/resources/views/edit.blade.php` | Replace text input with select | 1.1 |
| 7 | `Modules/Customer/routes/web.php` | Add customer-groups routes | 1.1 |
| 8 | `Modules/Supplier/app/Models/Supplier.php` | Add supplier_group_id, relationship | 1.2 |
| 9 | `Modules/Supplier/routes/web.php` | Add supplier-groups routes | 1.2 |
| 10 | `Modules/Expense/app/Models/Expense.php` | Add vendor FK, due fields, relationship | 1.4 |
| 11 | `Modules/Expense/routes/web.php` | Add expense-vendors routes | 1.4 |
| 12 | `Modules/POS/app/Http/Controllers/POSController.php` | Add hold/resume methods | 1.5 |
| 13 | `Modules/POS/routes/web.php` | Add hold cart routes | 1.5 |
| 14 | `Modules/Report/app/Http/Controllers/ReportController.php` | Add DTS + 6 report methods | 1.6, 2.3 |
| 15 | `Modules/Report/app/Services/ReportService.php` | Add DTS + 6 report queries | 1.6, 2.3 |
| 16 | `Modules/Report/routes/web.php` | Add all new report routes | 1.6, 2.3 |
| 17 | `Modules/Inventory/app/Http/Controllers/InventoryController.php` | Add reconciliation methods | 1.7 |
| 18 | `Modules/Inventory/routes/web.php` | Add reconciliation routes | 1.7 |
| 19 | `Modules/Accounting/app/Http/Controllers/AccountingController.php` | Add opening balance methods | 1.8 |
| 20 | `Modules/Accounting/routes/web.php` | Add opening balance + other transaction routes | 1.8, 3.4 |
| 21 | `Modules/Payment/app/Http/Controllers/PaymentAccountController.php` | Add ledger method | 1.9 |
| 22 | `Modules/Payment/routes/web.php` | Add ledger route | 1.9 |
| 23 | `Modules/Expense/app/Models/ExpenseCategory.php` | Add parent_id, parent(), children() | 2.1 |
| 24 | `Modules/Purchase/routes/web.php` | Add return-types routes | 2.2 |
| 25 | `Modules/Attendance/routes/web.php` | Add config routes | 2.4 |
| 26 | `Modules/Setting/routes/web.php` | Add email, sidebar, logs, printer routes | 2.5, 2.8, 4.1, 4.3 |
| 27 | `Modules/Core/resources/views/partials/sidebar.blade.php` | Add all new links + sidebar config wrapping | All |
| 28 | `Modules/Sale/app/Services/SaleService.php` | Skip stock for services | 3.1 |
| 29 | 21 models across all modules | Add LogsActivity trait | 3.5 |
| 30 | `app/Providers/EventServiceProvider.php` | Add login/logout listeners | 3.5 |
| 31 | `Modules/Activity/resources/views/index.blade.php` | Module tabs, timeline, export | 3.5 |
| 32 | `Modules/Activity/routes/web.php` | Add export route | 3.5 |
| 33 | `public/js/app.js` | Add printToNetworkPrinter() | 4.3 |
| 34 | `public/css/style.css` | New component styles for all new views | All |

## New Database Tables (10 total)

| Table | Migration Phase |
|-------|----------------|
| `customer_groups` | 1.1 |
| `supplier_groups` | 1.2 |
| `expense_vendors` | 1.4 |
| `pos_held_carts` | 1.5 |
| `purchase_return_types` | 2.2 |
| `weekend_days` | 2.4 |
| `holidays` | 2.4 |
| `email_templates` | 2.5 |
| `areas` | 3.2 |
| `other_transactions` | 3.4 |
| `printers` | 4.3 |

## Altered Tables (6 total)

| Table | Changes | Phase |
|-------|---------|-------|
| `customers` | Drop customer_group string, add customer_group_id FK, add area_id FK | 1.1, 3.2 |
| `suppliers` | Add supplier_group_id FK | 1.2 |
| `expenses` | Add expense_vendor_id FK, due_date, paid_amount, due_amount, payment_status | 1.4 |
| `expense_categories` | Add parent_id FK, level | 2.1 |
| `purchase_returns` | Add return_type_id FK | 2.2 |
