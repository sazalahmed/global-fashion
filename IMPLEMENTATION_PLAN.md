# BizPOS Pro — Product & Inventory Implementation Plan

> **Created:** 13 Mar 2026
> **Status:** Not Started
> **Architecture:** Laravel 12 + nwidart/laravel-modules | MySQL 8.0+ | Blade + Bootstrap 5.3.3

---

## Current State Assessment

| Layer | Product | Category | Brand | Unit | Variant | Barcode | Inventory | Warehouse |
|-------|---------|----------|-------|------|---------|---------|-----------|-----------|
| Routes | Done | Done | Done | Done | Done | Done | Done | Missing |
| Views | Done | Done | Done | Done | Done | Done | Done | Missing |
| Controllers | Stub | Stub | Stub | Stub | Stub | Stub | Stub | Missing |
| Models | Missing | Missing | Missing | Missing | Missing | Missing | Missing | Missing |
| Migrations | Missing | Missing | Missing | Missing | Missing | Missing | Missing | Missing |
| Services | Missing | Missing | Missing | Missing | Missing | Missing | Missing | Missing |
| Form Requests | Missing | Missing | Missing | Missing | Missing | Missing | Missing | Missing |
| Policies | Missing | Missing | Missing | Missing | Missing | Missing | Missing | Missing |

**Key Finding:** All frontend (views, routes) is scaffolded with mock data. Zero backend implementation exists — no database tables, no models, no business logic.

---

## Dependency Graph

```
Phase 1 (Foundation) ──→ Phase 2 (Products) ──→ Phase 3 (Variants) ──┐
                                   │                                   ├──→ Phase 5 (Inventory Tracking)
                                   └──→ Phase 4 (Warehouses) ─────────┘           │
                                                                                   ↓
                                                                          Phase 6 (Integration)
```

- Phase 1 has no dependencies — start here
- Phase 2 depends on Phase 1 (products reference categories, brands, units)
- Phase 3 depends on Phase 2 (variants belong to products)
- Phase 4 has no dependency on Phase 3 — can run in parallel
- Phase 5 depends on Phases 2, 3, and 4 (stock tracks products + variants in warehouses)
- Phase 6 ties everything together

---

## Phase 1 — Foundation Tables (Categories, Brands, Units)

Lookup/reference tables that other modules depend on. No foreign key dependencies on other application tables.

### 1.1 Category Module

**Location:** `Modules/Category/`

#### Migration: `create_categories_table`

```
categories
├── id                  BIGINT UNSIGNED AUTO_INCREMENT PK
├── name                VARCHAR(255) NOT NULL
├── slug                VARCHAR(255) NOT NULL UNIQUE
├── parent_id           BIGINT UNSIGNED NULLABLE FK → categories.id ON DELETE SET NULL
├── image               VARCHAR(500) NULLABLE
├── description         TEXT NULLABLE
├── sort_order          INT DEFAULT 0
├── is_active           BOOLEAN DEFAULT TRUE
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
├── deleted_at          TIMESTAMP NULLABLE
│
├── INDEX(parent_id)
├── INDEX(is_active)
└── INDEX(sort_order)
```

**Key design decisions:**
- Self-referential `parent_id` enables unlimited nesting (Electronics → Mobile → Smartphone)
- `slug` is unique for URL-friendly routes and future eCommerce SEO
- `sort_order` controls display ordering in dropdowns and sidebar
- Soft deletes prevent orphaning products when a category is removed

#### Model: `Category`

```php
// Fillable
$fillable = ['name', 'slug', 'parent_id', 'image', 'description', 'sort_order', 'is_active'];

// Relationships
belongsTo(Category::class, 'parent_id')           → parent
hasMany(Category::class, 'parent_id')              → children
hasMany(Product::class)                            → products

// Scopes
scopeActive($query)       → where('is_active', true)
scopeRoot($query)         → whereNull('parent_id')
scopeOrdered($query)      → orderBy('sort_order')->orderBy('name')

// Accessors
getProductCountAttribute() → products()->count() (use withCount in queries)
getFullPathAttribute()     → "Electronics > Mobile > Smartphone" (recursive parent chain)

// Boot
static::creating → auto-generate slug from name if not provided
static::deleting → reassign child categories to parent (or root) before soft-delete
```

#### Service: `CategoryService`

| Method | Description |
|--------|-------------|
| `list(array $filters = [])` | Paginated list with optional parent_id, is_active, search filters |
| `getTree()` | Returns nested tree structure for dropdowns and sidebar |
| `create(array $data): Category` | Create category, auto-generate slug, handle image upload |
| `update(Category $category, array $data): Category` | Update with slug regeneration if name changes |
| `delete(Category $category): bool` | Soft delete — reassign children to parent, check for linked products |
| `generateSlug(string $name, ?int $excludeId = null): string` | Unique slug generation with suffix if duplicate |
| `reorder(array $orderedIds): void` | Bulk update sort_order based on array position |

#### Form Requests

**StoreCategoryRequest:**
```
name        → required | string | max:255
slug        → nullable | string | max:255 | unique:categories,slug
parent_id   → nullable | integer | exists:categories,id
image       → nullable | file | mimes:jpg,png,webp | max:2048
description → nullable | string | max:1000
sort_order  → nullable | integer | min:0
is_active   → boolean
```

**UpdateCategoryRequest:**
```
Same as Store, except:
slug        → unique:categories,slug,{category_id} (ignore current)
```

#### Controller Methods

| Method | Action |
|--------|--------|
| `index()` | Inject `CategoryService::list()`, pass to existing `category::index` view |
| `create()` | Fetch parent categories for dropdown, pass to `category::create` view |
| `store(StoreCategoryRequest)` | Call `CategoryService::create()`, redirect with success flash |
| `edit(Category)` | Fetch category + parent options, pass to `category::edit` view |
| `update(UpdateCategoryRequest, Category)` | Call `CategoryService::update()`, redirect |
| `destroy(Category)` | Call `CategoryService::delete()`, redirect |

---

### 1.2 Brand Module

**Location:** `Modules/Brand/`

#### Migration: `create_brands_table`

```
brands
├── id                  BIGINT UNSIGNED AUTO_INCREMENT PK
├── name                VARCHAR(255) NOT NULL
├── slug                VARCHAR(255) NOT NULL UNIQUE
├── logo                VARCHAR(500) NULLABLE
├── description         TEXT NULLABLE
├── website             VARCHAR(500) NULLABLE
├── is_active           BOOLEAN DEFAULT TRUE
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
├── deleted_at          TIMESTAMP NULLABLE
│
└── INDEX(is_active)
```

#### Model: `Brand`

```php
$fillable = ['name', 'slug', 'logo', 'description', 'website', 'is_active'];

// Relationships
hasMany(Product::class) → products

// Scopes
scopeActive($query) → where('is_active', true)
scopeOrdered($query) → orderBy('name')

// Boot
static::creating → auto-generate slug from name
```

#### Service: `BrandService`

| Method | Description |
|--------|-------------|
| `list(array $filters = [])` | Paginated list with search, is_active filters |
| `getAll()` | All active brands for dropdowns (no pagination) |
| `create(array $data): Brand` | Create brand, handle logo upload |
| `update(Brand $brand, array $data): Brand` | Update, replace logo if new one uploaded |
| `delete(Brand $brand): bool` | Soft delete — check for linked products first |

#### Form Requests

**StoreBrandRequest:**
```
name        → required | string | max:255
slug        → nullable | string | max:255 | unique:brands,slug
logo        → nullable | file | mimes:jpg,png,webp,svg | max:1024
description → nullable | string | max:1000
website     → nullable | url | max:500
is_active   → boolean
```

**UpdateBrandRequest:**
```
Same as Store, slug ignores current ID
```

---

### 1.3 Unit Module

**Location:** `Modules/Unit/`

#### Migration: `create_units_table`

```
units
├── id                  BIGINT UNSIGNED AUTO_INCREMENT PK
├── name                VARCHAR(100) NOT NULL
├── short_name          VARCHAR(20) NOT NULL
├── base_unit_id        BIGINT UNSIGNED NULLABLE FK → units.id ON DELETE SET NULL
├── conversion_factor   DECIMAL(15,6) DEFAULT 1.000000
├── is_active           BOOLEAN DEFAULT TRUE
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
├── deleted_at          TIMESTAMP NULLABLE
│
├── INDEX(base_unit_id)
└── INDEX(is_active)
```

**Key design decisions:**
- `base_unit_id` + `conversion_factor` enables unit conversion (e.g., 1 kg = 1000 g → g has base_unit_id = kg.id, conversion_factor = 0.001)
- Base units have `base_unit_id = NULL` and `conversion_factor = 1`
- `short_name` is used in UI displays (pcs, kg, g, L, m)

#### Model: `Unit`

```php
$fillable = ['name', 'short_name', 'base_unit_id', 'conversion_factor', 'is_active'];

// Relationships
belongsTo(Unit::class, 'base_unit_id')        → baseUnit
hasMany(Unit::class, 'base_unit_id')           → derivedUnits
hasMany(Product::class)                        → products

// Scopes
scopeActive($query)   → where('is_active', true)
scopeBase($query)     → whereNull('base_unit_id')

// Methods
isBaseUnit(): bool     → base_unit_id === null
convertTo(float $qty, Unit $targetUnit): float → convert quantity between units
```

#### Service: `UnitService`

| Method | Description |
|--------|-------------|
| `list(array $filters = [])` | Paginated list with search filter |
| `getAll()` | All active units for dropdowns |
| `getBaseUnits()` | Only base units (for "base unit" dropdown in create form) |
| `create(array $data): Unit` | Create unit with conversion factor validation |
| `update(Unit $unit, array $data): Unit` | Update — recalculate derived units if conversion_factor changes |
| `delete(Unit $unit): bool` | Soft delete — block if products are using this unit |
| `convert(float $qty, Unit $from, Unit $to): float` | Convert quantity between compatible units |

#### Form Requests

**StoreUnitRequest:**
```
name              → required | string | max:100
short_name        → required | string | max:20
base_unit_id      → nullable | integer | exists:units,id
conversion_factor → required_with:base_unit_id | numeric | gt:0
is_active         → boolean
```

#### Seeder: `UnitSeeder`

| Name | Short Name | Base Unit | Conversion Factor |
|------|------------|-----------|-------------------|
| Piece | pcs | — (base) | 1 |
| Kilogram | kg | — (base) | 1 |
| Gram | g | Kilogram | 0.001 |
| Liter | L | — (base) | 1 |
| Milliliter | ml | Liter | 0.001 |
| Meter | m | — (base) | 1 |
| Centimeter | cm | Meter | 0.01 |
| Box | box | — (base) | 1 |
| Dozen | dz | Piece | 12 |
| Carton | ctn | — (base) | 1 |

---

### Phase 1 — Files to Create

```
Modules/Category/
├── database/migrations/YYYY_MM_DD_000001_create_categories_table.php
├── app/Models/Category.php
├── app/Http/Requests/StoreCategoryRequest.php
├── app/Http/Requests/UpdateCategoryRequest.php
├── app/Services/CategoryService.php
└── app/Http/Controllers/CategoryController.php  ← UPDATE existing

Modules/Brand/
├── database/migrations/YYYY_MM_DD_000001_create_brands_table.php
├── app/Models/Brand.php
├── app/Http/Requests/StoreBrandRequest.php
├── app/Http/Requests/UpdateBrandRequest.php
├── app/Services/BrandService.php
└── app/Http/Controllers/BrandController.php  ← UPDATE existing

Modules/Unit/
├── database/migrations/YYYY_MM_DD_000001_create_units_table.php
├── app/Models/Unit.php
├── app/Http/Requests/StoreUnitRequest.php
├── app/Http/Requests/UpdateUnitRequest.php
├── app/Services/UnitService.php
├── database/seeders/UnitSeeder.php
└── app/Http/Controllers/UnitController.php  ← UPDATE existing
```

**Total:** ~20 new files + 3 controller updates

---

## Phase 2 — Product Module (Core)

### 2.1 Migrations

#### Migration 1: `create_products_table`

```
products
├── id                      BIGINT UNSIGNED AUTO_INCREMENT PK
├── name                    VARCHAR(255) NOT NULL
├── slug                    VARCHAR(255) NOT NULL UNIQUE
├── sku                     VARCHAR(100) NOT NULL UNIQUE
├── barcode                 VARCHAR(100) NULLABLE UNIQUE
├── category_id             BIGINT UNSIGNED NOT NULL FK → categories.id ON DELETE RESTRICT
├── brand_id                BIGINT UNSIGNED NULLABLE FK → brands.id ON DELETE SET NULL
├── supplier_id             BIGINT UNSIGNED NULLABLE FK → suppliers.id ON DELETE SET NULL
├── unit_id                 BIGINT UNSIGNED NOT NULL FK → units.id ON DELETE RESTRICT
├── product_type            ENUM('single','variable','service') DEFAULT 'single'
│
├── cost_price              DECIMAL(15,2) NOT NULL DEFAULT 0.00
├── sell_price              DECIMAL(15,2) NOT NULL DEFAULT 0.00
├── wholesale_price         DECIMAL(15,2) NULLABLE
├── vat_rate                DECIMAL(5,2) DEFAULT 0.00
├── vat_inclusive            BOOLEAN DEFAULT FALSE
├── discount_type           ENUM('fixed','percentage') NULLABLE
├── discount_value          DECIMAL(15,2) NULLABLE
│
├── description             TEXT NULLABLE
├── long_description        LONGTEXT NULLABLE
├── warranty                VARCHAR(255) NULLABLE
├── weight                  DECIMAL(10,3) NULLABLE
├── country_of_origin       VARCHAR(100) NULLABLE
│
├── min_stock_alert         INT UNSIGNED DEFAULT 10
├── max_stock_level         INT UNSIGNED NULLABLE
├── valuation_method        ENUM('fifo','lifo','weighted_avg') DEFAULT 'fifo'
│
├── is_active               BOOLEAN DEFAULT TRUE
├── show_in_pos             BOOLEAN DEFAULT TRUE
├── track_stock             BOOLEAN DEFAULT TRUE
├── allow_negative_stock    BOOLEAN DEFAULT FALSE
├── ecom_sync               BOOLEAN DEFAULT FALSE
├── ecom_visible            BOOLEAN DEFAULT FALSE
│
├── seo_title               VARCHAR(255) NULLABLE
├── seo_description         TEXT NULLABLE
│
├── created_by              BIGINT UNSIGNED NULLABLE FK → users.id ON DELETE SET NULL
├── created_at              TIMESTAMP
├── updated_at              TIMESTAMP
├── deleted_at              TIMESTAMP NULLABLE
│
├── INDEX(category_id)
├── INDEX(brand_id)
├── INDEX(supplier_id)
├── INDEX(unit_id)
├── INDEX(product_type)
├── INDEX(is_active)
├── INDEX(show_in_pos)
├── FULLTEXT INDEX(name, description)
└── INDEX(created_by)
```

**Key design decisions:**
- `RESTRICT` on category_id and unit_id — cannot delete a category/unit that has products
- `SET NULL` on brand_id and supplier_id — optional relationships, safe to remove
- `product_type` determines if the product uses variants (variable) or is standalone (single) or non-stock (service)
- `valuation_method` per product allows mixed FIFO/weighted average in the same store
- `FULLTEXT INDEX` on name + description for fast product search
- All money fields are `DECIMAL(15,2)` — never float

#### Migration 2: `create_product_images_table`

```
product_images
├── id                  BIGINT UNSIGNED AUTO_INCREMENT PK
├── product_id          BIGINT UNSIGNED NOT NULL FK → products.id ON DELETE CASCADE
├── image_path          VARCHAR(500) NOT NULL
├── alt_text            VARCHAR(255) NULLABLE
├── sort_order          INT UNSIGNED DEFAULT 0
├── is_primary          BOOLEAN DEFAULT FALSE
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
│
├── INDEX(product_id)
└── INDEX(is_primary)
```

**Rules:**
- Only ONE image per product can have `is_primary = true` — enforce in service layer
- On product delete, images cascade delete (also delete physical files in service)
- Store in `storage/app/public/products/` — access via `Storage::url()`
- Max 10 images per product — enforce in form request

#### Migration 3: `create_tags_table` + `create_product_tag_table`

```
tags
├── id                  BIGINT UNSIGNED AUTO_INCREMENT PK
├── name                VARCHAR(100) NOT NULL
├── slug                VARCHAR(100) NOT NULL UNIQUE
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP

product_tag (pivot)
├── product_id          BIGINT UNSIGNED FK → products.id ON DELETE CASCADE
├── tag_id              BIGINT UNSIGNED FK → tags.id ON DELETE CASCADE
│
└── PRIMARY KEY(product_id, tag_id)
```

**Usage:** Tags are free-form labels (e.g., "bestseller", "new-arrival", "eid-sale", "winter-collection"). Products can have multiple tags. Tags are created on-the-fly when assigning to products.

---

### 2.2 Model: `Product`

```php
$fillable = [
    'name', 'slug', 'sku', 'barcode', 'category_id', 'brand_id', 'supplier_id',
    'unit_id', 'product_type', 'cost_price', 'sell_price', 'wholesale_price',
    'vat_rate', 'vat_inclusive', 'discount_type', 'discount_value', 'description',
    'long_description', 'warranty', 'weight', 'country_of_origin', 'min_stock_alert',
    'max_stock_level', 'valuation_method', 'is_active', 'show_in_pos', 'track_stock',
    'allow_negative_stock', 'ecom_sync', 'ecom_visible', 'seo_title', 'seo_description',
    'created_by',
];

$casts = [
    'cost_price'       => 'decimal:2',
    'sell_price'       => 'decimal:2',
    'wholesale_price'  => 'decimal:2',
    'vat_rate'         => 'decimal:2',
    'discount_value'   => 'decimal:2',
    'weight'           => 'decimal:3',
    'vat_inclusive'     => 'boolean',
    'is_active'        => 'boolean',
    'show_in_pos'      => 'boolean',
    'track_stock'      => 'boolean',
    'allow_negative_stock' => 'boolean',
    'ecom_sync'        => 'boolean',
    'ecom_visible'     => 'boolean',
    'product_type'     => ProductType::class,  // PHP Enum
];

// Relationships
belongsTo(Category::class)              → category
belongsTo(Brand::class)                 → brand
belongsTo(Supplier::class)              → supplier
belongsTo(Unit::class)                  → unit
belongsTo(User::class, 'created_by')    → creator
hasMany(ProductImage::class)            → images
hasMany(ProductVariant::class)          → variants
hasMany(WarehouseStock::class)          → warehouseStocks
hasMany(StockLedger::class)             → stockLedgerEntries
belongsToMany(Tag::class, 'product_tag') → tags

// Scopes
scopeActive($query)           → where('is_active', true)
scopeForPos($query)           → where('show_in_pos', true)->where('is_active', true)
scopeLowStock($query)         → whereHas stock < min_stock_alert (subquery on warehouse_stock)
scopeOutOfStock($query)       → whereHas stock = 0
scopeByCategory($query, $id)  → where('category_id', $id)
scopeByBrand($query, $id)     → where('brand_id', $id)
scopeByType($query, $type)    → where('product_type', $type)
scopeSearch($query, $term)    → whereFullText(['name', 'description'], $term)

// Accessors
getPrimaryImageAttribute()     → images()->where('is_primary', true)->first()?->image_path
getFormattedCostPriceAttribute() → Formatter::bdt($this->cost_price)
getFormattedSellPriceAttribute() → Formatter::bdt($this->sell_price)
getProfitMarginAttribute()     → (sell_price - cost_price) / sell_price * 100
getTotalStockAttribute()       → warehouseStocks->sum('quantity')
getAvailableStockAttribute()   → warehouseStocks->sum(quantity - reserved_quantity)
isVariable(): bool             → product_type === 'variable'
isSingle(): bool               → product_type === 'single'
isService(): bool              → product_type === 'service'

// Boot
static::creating → auto-generate slug, auto-generate SKU if not provided
```

### 2.3 Model: `ProductImage`

```php
$fillable = ['product_id', 'image_path', 'alt_text', 'sort_order', 'is_primary'];

$casts = ['is_primary' => 'boolean'];

// Relationships
belongsTo(Product::class) → product

// Scopes
scopePrimary($query)  → where('is_primary', true)
scopeOrdered($query)  → orderBy('sort_order')
```

### 2.4 Model: `Tag`

```php
$fillable = ['name', 'slug'];

// Relationships
belongsToMany(Product::class, 'product_tag') → products

// Boot
static::creating → auto-generate slug from name
```

---

### 2.5 Service: `ProductService`

| Method | Description | Key Logic |
|--------|-------------|-----------|
| `list(array $filters, int $perPage = 15)` | Paginated product list | Eager load: category, brand, unit, primaryImage. Filters: search, category_id, brand_id, product_type, is_active, stock_status (low/out/in). Uses scopes. |
| `find(int $id): Product` | Single product with all relations | Eager load: category, brand, unit, supplier, images, variants, tags, warehouseStocks.warehouse |
| `create(array $data): Product` | Create product + images + tags | `DB::transaction` — create product, upload/attach images, sync tags, create initial warehouse_stock entry (qty 0) in default warehouse |
| `update(Product $product, array $data): Product` | Update product + manage images/tags | `DB::transaction` — update product, handle new image uploads, remove deleted images (delete files too), sync tags |
| `delete(Product $product): bool` | Soft delete product | Check: has pending orders? has stock > 0? If yes, block deletion and return error message. Otherwise soft delete. |
| `generateSku(int $categoryId): string` | Auto-generate SKU | Format: `{CATEGORY_PREFIX}-{PADDED_ID}`. Example: category "Electronics" → prefix "ELE", next product → `ELE-00045`. Category prefix = first 3 uppercase letters of category name. Padded to 5 digits. |
| `generateBarcode(?string $type = 'EAN13'): string` | Generate unique barcode number | EAN-13: country code (880 for Bangladesh) + random 9 digits + check digit. Validate uniqueness in DB. |
| `uploadImages(Product $product, array $files, ?int $primaryIndex = 0): void` | Handle image uploads | Store in `storage/app/public/products/{product_id}/`. Resize if > 1200px. First image is primary unless specified. |
| `deleteImage(ProductImage $image): void` | Remove single image | Delete file from storage + delete DB record. If was primary, set next image as primary. |
| `syncTags(Product $product, array $tagNames): void` | Attach/detach tags | Find or create tags by name, then `$product->tags()->sync($tagIds)` |
| `getStats(): array` | Dashboard stats | Returns: totalProducts, activeProducts, lowStockProducts, outOfStockProducts |
| `duplicate(Product $product): Product` | Clone a product | Copy all fields except SKU/barcode (generate new ones), copy images, copy tags. Do NOT copy stock. |

---

### 2.6 Form Requests

**StoreProductRequest:**
```
name                → required | string | max:255
sku                 → nullable | string | max:100 | unique:products,sku
barcode             → nullable | string | max:100 | unique:products,barcode
category_id         → required | integer | exists:categories,id
brand_id            → nullable | integer | exists:brands,id
supplier_id         → nullable | integer | exists:suppliers,id
unit_id             → required | integer | exists:units,id
product_type        → required | in:single,variable,service
cost_price          → required | numeric | min:0
sell_price          → required | numeric | min:0 | gte:cost_price
wholesale_price     → nullable | numeric | min:0
vat_rate            → nullable | numeric | min:0 | max:100
vat_inclusive        → boolean
discount_type       → nullable | in:fixed,percentage
discount_value      → nullable | numeric | min:0 | required_with:discount_type
description         → nullable | string | max:2000
long_description    → nullable | string | max:10000
warranty            → nullable | string | max:255
weight              → nullable | numeric | min:0
country_of_origin   → nullable | string | max:100
min_stock_alert     → nullable | integer | min:0
max_stock_level     → nullable | integer | min:0 | gt:min_stock_alert
valuation_method    → nullable | in:fifo,lifo,weighted_avg
is_active           → boolean
show_in_pos         → boolean
track_stock         → boolean
allow_negative_stock → boolean
images              → nullable | array | max:10
images.*            → file | mimes:jpg,jpeg,png,webp | max:2048
tags                → nullable | array
tags.*              → string | max:50
seo_title           → nullable | string | max:255
seo_description     → nullable | string | max:500
```

**UpdateProductRequest:**
```
Same as Store, except:
sku     → unique:products,sku,{product_id}
barcode → unique:products,barcode,{product_id}
images  → not required (existing images preserved)
```

---

### 2.7 Barcode Module

**Location:** `Modules/Barcode/`

**Package Required:** `composer require picqer/php-barcode-generator`

#### Service: `BarcodeService`

| Method | Description |
|--------|-------------|
| `generateImage(string $code, string $type = 'EAN13'): string` | Returns base64 barcode image |
| `generateEan13(): string` | Generate valid EAN-13 number (880 prefix for Bangladesh) |
| `generateCode128(string $sku): string` | Generate Code128 from SKU |
| `generateBatchPdf(array $products, string $labelSize = '38x25'): string` | Generate printable PDF with barcode labels |
| `validateEan13(string $code): bool` | Validate EAN-13 check digit |

**Supported label sizes:**
- 38mm x 25mm (standard barcode label)
- 50mm x 25mm (wide label)
- 50mm x 30mm (with product name + price)

**Label content:**
```
┌─────────────────────┐
│ Product Name Here    │
│ |||||||||||||||||||  │  ← Barcode image
│ 8801234567890        │  ← Barcode number
│ BDT 1,250           │  ← Sell price
└─────────────────────┘
```

#### Controller: `BarcodeController`

| Method | Action |
|--------|--------|
| `index()` | Show barcode generator page — product search, label size selector, quantity input |
| `generate(Request)` | Accept product_ids[], type, return barcode preview (AJAX) |
| `print(Request)` | Accept product_ids[], type, label_size, quantities[], generate downloadable PDF |

---

### Phase 2 — Files to Create

```
Modules/Product/
├── database/migrations/YYYY_MM_DD_000001_create_products_table.php
├── database/migrations/YYYY_MM_DD_000002_create_product_images_table.php
├── database/migrations/YYYY_MM_DD_000003_create_tags_table.php
├── database/migrations/YYYY_MM_DD_000004_create_product_tag_table.php
├── app/Models/Product.php
├── app/Models/ProductImage.php
├── app/Models/Tag.php
├── app/Enums/ProductType.php
├── app/Http/Requests/StoreProductRequest.php
├── app/Http/Requests/UpdateProductRequest.php
├── app/Services/ProductService.php
└── app/Http/Controllers/ProductController.php  ← UPDATE existing

Modules/Barcode/
├── app/Services/BarcodeService.php
└── app/Http/Controllers/BarcodeController.php  ← UPDATE existing
```

**Total:** ~14 new files + 2 controller updates

---

## Phase 3 — Product Variants

### 3.1 Migrations

#### Migration 1: `create_variant_attributes_table`

```
variant_attributes
├── id                  BIGINT UNSIGNED AUTO_INCREMENT PK
├── name                VARCHAR(100) NOT NULL (e.g., "Color", "Size", "Storage")
├── display_name        VARCHAR(100) NULLABLE (e.g., "রঙ" for Bangla display)
├── sort_order          INT UNSIGNED DEFAULT 0
├── is_active           BOOLEAN DEFAULT TRUE
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
│
└── UNIQUE(name)
```

**Examples:** Color, Size, Storage, RAM, Material, Flavor

#### Migration 2: `create_variant_attribute_values_table`

```
variant_attribute_values
├── id                      BIGINT UNSIGNED AUTO_INCREMENT PK
├── variant_attribute_id    BIGINT UNSIGNED NOT NULL FK → variant_attributes.id ON DELETE CASCADE
├── value                   VARCHAR(100) NOT NULL (e.g., "Red", "XL", "128GB")
├── color_code              VARCHAR(7) NULLABLE (e.g., "#FF0000" — only for color attributes)
├── sort_order              INT UNSIGNED DEFAULT 0
├── created_at              TIMESTAMP
├── updated_at              TIMESTAMP
│
├── INDEX(variant_attribute_id)
└── UNIQUE(variant_attribute_id, value)
```

#### Migration 3: `create_product_variants_table`

```
product_variants
├── id                  BIGINT UNSIGNED AUTO_INCREMENT PK
├── product_id          BIGINT UNSIGNED NOT NULL FK → products.id ON DELETE CASCADE
├── sku                 VARCHAR(100) NOT NULL UNIQUE
├── barcode             VARCHAR(100) NULLABLE UNIQUE
├── cost_price          DECIMAL(15,2) NULLABLE (NULL = inherit from parent product)
├── sell_price          DECIMAL(15,2) NULLABLE (NULL = inherit from parent product)
├── wholesale_price     DECIMAL(15,2) NULLABLE
├── weight              DECIMAL(10,3) NULLABLE
├── image_path          VARCHAR(500) NULLABLE
├── is_active           BOOLEAN DEFAULT TRUE
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
├── deleted_at          TIMESTAMP NULLABLE
│
├── INDEX(product_id)
└── INDEX(is_active)
```

**Key design decisions:**
- Price fields are NULLABLE — when NULL, inherit from parent product
- Each variant has its own SKU (required) and optional barcode
- Each variant can have its own image or inherit from parent product
- Variants are soft-deleted independently of the parent product

#### Migration 4: `create_product_variant_values_table` (pivot)

```
product_variant_values
├── product_variant_id          BIGINT UNSIGNED FK → product_variants.id ON DELETE CASCADE
├── variant_attribute_value_id  BIGINT UNSIGNED FK → variant_attribute_values.id ON DELETE CASCADE
│
└── PRIMARY KEY(product_variant_id, variant_attribute_value_id)
```

**Example data for a T-Shirt product:**

| product_variant_id | attribute | value | SKU | sell_price |
|---|---|---|---|---|
| 1 | Color=Red, Size=S | — | TSH-001-RED-S | NULL (inherit 500) |
| 2 | Color=Red, Size=M | — | TSH-001-RED-M | NULL (inherit 500) |
| 3 | Color=Red, Size=L | — | TSH-001-RED-L | 550 (override) |
| 4 | Color=Blue, Size=S | — | TSH-001-BLU-S | NULL (inherit 500) |
| 5 | Color=Blue, Size=M | — | TSH-001-BLU-M | NULL (inherit 500) |
| 6 | Color=Blue, Size=L | — | TSH-001-BLU-L | 550 (override) |

---

### 3.2 Models

#### `VariantAttribute`

```php
$fillable = ['name', 'display_name', 'sort_order', 'is_active'];

// Relationships
hasMany(VariantAttributeValue::class) → values

// Scopes
scopeActive($query) → where('is_active', true)
scopeOrdered($query) → orderBy('sort_order')
```

#### `VariantAttributeValue`

```php
$fillable = ['variant_attribute_id', 'value', 'color_code', 'sort_order'];

// Relationships
belongsTo(VariantAttribute::class) → attribute
belongsToMany(ProductVariant::class, 'product_variant_values') → productVariants
```

#### `ProductVariant`

```php
$fillable = [
    'product_id', 'sku', 'barcode', 'cost_price', 'sell_price',
    'wholesale_price', 'weight', 'image_path', 'is_active',
];

$casts = [
    'cost_price'      => 'decimal:2',
    'sell_price'      => 'decimal:2',
    'wholesale_price' => 'decimal:2',
    'weight'          => 'decimal:3',
    'is_active'       => 'boolean',
];

// Relationships
belongsTo(Product::class) → product
belongsToMany(VariantAttributeValue::class, 'product_variant_values') → attributeValues
hasMany(WarehouseStock::class) → warehouseStocks
hasMany(StockLedger::class) → stockLedgerEntries

// Accessors — inherit from parent product if NULL
getEffectiveCostPriceAttribute()  → $this->cost_price ?? $this->product->cost_price
getEffectiveSellPriceAttribute()  → $this->sell_price ?? $this->product->sell_price
getEffectiveWeightAttribute()     → $this->weight ?? $this->product->weight
getEffectiveImageAttribute()      → $this->image_path ?? $this->product->primaryImage
getVariantNameAttribute()         → "Red / XL" (joined attribute values)
getTotalStockAttribute()          → warehouseStocks->sum('quantity')

// Scopes
scopeActive($query) → where('is_active', true)
```

---

### 3.3 Service: `VariantService`

| Method | Description | Key Logic |
|--------|-------------|-----------|
| `getAttributes()` | List all variant attributes with values | For variant configuration UI |
| `createAttribute(array $data): VariantAttribute` | Create new attribute (e.g., "Storage") | Auto-sort at end |
| `addAttributeValue(VariantAttribute, string $value): VariantAttributeValue` | Add value to attribute | e.g., add "256GB" to "Storage" |
| `generateVariants(Product $product, array $attributeValueIds): Collection` | Generate all variant combinations | Input: `[[color_red_id, color_blue_id], [size_s_id, size_m_id]]` → creates 4 variants (Red-S, Red-M, Blue-S, Blue-M). Auto-generates SKU for each. `DB::transaction`. |
| `updateVariant(ProductVariant $variant, array $data): ProductVariant` | Update single variant pricing/status | Override price, barcode, image, active status |
| `bulkUpdateVariants(Product $product, array $variantsData): void` | Update multiple variants at once | For the pricing matrix UI |
| `deleteVariant(ProductVariant $variant): bool` | Soft delete variant | Check for stock > 0 or pending orders first |
| `regenerateSkus(Product $product): void` | Rebuild SKUs for all variants | Format: `{PRODUCT_SKU}-{ATTR1_ABBREV}-{ATTR2_ABBREV}` e.g., `TSH-001-RED-XL` |
| `getVariantMatrix(Product $product): array` | Return attribute × variant pricing grid | For the edit variant pricing UI (table with rows=combinations, cols=price/stock/sku) |

**SKU Generation Logic:**
```
Parent SKU: TSH-001
Attribute abbreviations: Color → first 3 uppercase letters of value (RED, BLU)
                         Size  → exact value (S, M, L, XL, XXL)
                         Storage → numeric value (128, 256, 512)

Result: TSH-001-RED-S, TSH-001-RED-M, TSH-001-BLU-S, TSH-001-BLU-M
```

---

### 3.4 Controller Updates

**VariantController** (update existing stub):

| Method | Action |
|--------|--------|
| `index()` | List all variant attributes with their values (attribute management page) |
| `create()` | Show attribute creation form |
| `store(StoreVariantAttributeRequest)` | Create attribute |
| `show(VariantAttribute)` | Show attribute with all values |
| `edit(VariantAttribute)` | Edit attribute form |
| `update(UpdateVariantAttributeRequest, VariantAttribute)` | Update attribute |
| `destroy(VariantAttribute)` | Delete attribute (block if used by products) |

**ProductController** (add variant methods or handle via AJAX):
- Variant generation happens on the product create/edit page
- Product create form: select attributes → select values → preview combinations → save
- AJAX endpoint: `POST /products/{product}/variants/generate` → `ProductController@generateVariants`

---

### Phase 3 — Files to Create

```
Modules/Variant/
├── database/migrations/YYYY_MM_DD_000001_create_variant_attributes_table.php
├── database/migrations/YYYY_MM_DD_000002_create_variant_attribute_values_table.php
├── database/migrations/YYYY_MM_DD_000003_create_product_variants_table.php
├── database/migrations/YYYY_MM_DD_000004_create_product_variant_values_table.php
├── app/Models/VariantAttribute.php
├── app/Models/VariantAttributeValue.php
├── app/Models/ProductVariant.php
├── app/Http/Requests/StoreVariantAttributeRequest.php
├── app/Http/Requests/UpdateVariantAttributeRequest.php
├── app/Services/VariantService.php
└── app/Http/Controllers/VariantController.php  ← UPDATE existing

Modules/Product/
└── routes/web.php  ← ADD variant generation route
```

**Total:** ~12 new files + 2 updates

---

## Phase 4 — Warehouse Module (New Module)

This module does not exist yet. Must be created from scratch.

### 4.1 Create Module Structure

```bash
php artisan module:make Warehouse
```

### 4.2 Migrations

#### Migration 1: `create_warehouses_table`

```
warehouses
├── id                  BIGINT UNSIGNED AUTO_INCREMENT PK
├── name                VARCHAR(255) NOT NULL
├── code                VARCHAR(50) NOT NULL UNIQUE
├── address             TEXT NULLABLE
├── city                VARCHAR(100) NULLABLE
├── phone               VARCHAR(20) NULLABLE
├── email               VARCHAR(255) NULLABLE
├── manager_id          BIGINT UNSIGNED NULLABLE FK → users.id ON DELETE SET NULL
├── is_default          BOOLEAN DEFAULT FALSE
├── is_active           BOOLEAN DEFAULT TRUE
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
├── deleted_at          TIMESTAMP NULLABLE
│
├── INDEX(manager_id)
├── INDEX(is_default)
└── INDEX(is_active)
```

**Rules:**
- Only ONE warehouse can have `is_default = true` at a time — enforce in service layer
- Default warehouse is used when no warehouse is specified (e.g., POS sales)
- Warehouse code is a short unique identifier (e.g., "WH-MAIN", "WH-DHANMONDI")

#### Migration 2: `create_warehouse_locations_table`

```
warehouse_locations
├── id                  BIGINT UNSIGNED AUTO_INCREMENT PK
├── warehouse_id        BIGINT UNSIGNED NOT NULL FK → warehouses.id ON DELETE CASCADE
├── name                VARCHAR(255) NOT NULL (e.g., "Aisle A - Shelf 3", "Cold Storage Room 2")
├── code                VARCHAR(50) NOT NULL
├── description         TEXT NULLABLE
├── is_active           BOOLEAN DEFAULT TRUE
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
│
├── INDEX(warehouse_id)
└── UNIQUE(warehouse_id, code)
```

**Usage:** Optional sub-locations within a warehouse for precise stock placement. Useful for larger warehouses. Small shops may not use locations at all.

---

### 4.3 Models

#### `Warehouse`

```php
$fillable = ['name', 'code', 'address', 'city', 'phone', 'email', 'manager_id', 'is_default', 'is_active'];

$casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

// Relationships
belongsTo(User::class, 'manager_id')       → manager
hasMany(WarehouseLocation::class)           → locations
hasMany(WarehouseStock::class)              → stocks

// Scopes
scopeActive($query)    → where('is_active', true)
scopeDefault($query)   → where('is_default', true)

// Accessors
getTotalStockValueAttribute()  → stocks->sum(quantity * product.cost_price)
getTotalProductsAttribute()    → stocks->where('quantity', '>', 0)->count()
```

#### `WarehouseLocation`

```php
$fillable = ['warehouse_id', 'name', 'code', 'description', 'is_active'];

// Relationships
belongsTo(Warehouse::class)         → warehouse
hasMany(WarehouseStock::class)      → stocks

// Scopes
scopeActive($query) → where('is_active', true)
```

---

### 4.4 Service: `WarehouseService`

| Method | Description | Key Logic |
|--------|-------------|-----------|
| `list(array $filters = [])` | Paginated warehouse list | Include: location count, total stock value, total products |
| `create(array $data): Warehouse` | Create warehouse | If is_default, unset other defaults first. Auto-generate code if not provided. |
| `update(Warehouse $warehouse, array $data): Warehouse` | Update warehouse | Handle is_default toggle (only one at a time) |
| `delete(Warehouse $warehouse): bool` | Soft delete warehouse | Block if warehouse has stock > 0. Cannot delete default warehouse. |
| `setDefault(Warehouse $warehouse): void` | Set as default warehouse | Unset all other defaults, set this one |
| `getDefault(): Warehouse` | Get default warehouse | Used by POS, stock-in, etc. |
| `addLocation(Warehouse, array $data): WarehouseLocation` | Add location to warehouse | |
| `updateLocation(WarehouseLocation, array $data): WarehouseLocation` | Update location | |
| `deleteLocation(WarehouseLocation): bool` | Delete location | Block if stock exists at this location |
| `getStockSummary(Warehouse): array` | Stock overview for warehouse | Products, total value, low stock items, top items by value |

---

### 4.5 Routes

```php
Route::middleware('auth')->prefix('warehouses')->name('warehouses.')->group(function () {
    Route::get('/', [WarehouseController::class, 'index'])->name('index');
    Route::get('/create', [WarehouseController::class, 'create'])->name('create');
    Route::post('/', [WarehouseController::class, 'store'])->name('store');
    Route::get('/{warehouse}', [WarehouseController::class, 'show'])->name('show');
    Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('edit');
    Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
    Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');
    Route::post('/{warehouse}/set-default', [WarehouseController::class, 'setDefault'])->name('set-default');

    // Nested locations
    Route::get('/{warehouse}/locations', [WarehouseLocationController::class, 'index'])->name('locations.index');
    Route::post('/{warehouse}/locations', [WarehouseLocationController::class, 'store'])->name('locations.store');
    Route::put('/{warehouse}/locations/{location}', [WarehouseLocationController::class, 'update'])->name('locations.update');
    Route::delete('/{warehouse}/locations/{location}', [WarehouseLocationController::class, 'destroy'])->name('locations.destroy');
});
```

### 4.6 Views to Create

| View | Description |
|------|-------------|
| `index.blade.php` | Warehouse list — cards or table with: name, code, city, manager, location count, stock value, status, actions |
| `create.blade.php` | Form: name, code, address, city, phone, email, manager (select), is_default toggle |
| `edit.blade.php` | Same as create, pre-filled |
| `show.blade.php` | Warehouse detail — info card + locations table + stock summary + top products chart |

### 4.7 Sidebar Update

Add "Warehouses" to the Inventory group in `Modules/Core/resources/views/partials/sidebar.blade.php`:

```
INVENTORY
├── Products
│   ├── All Products
│   ├── Categories
│   ├── Brands
│   ├── Units
│   ├── Variants
│   └── Print Barcode
├── Warehouses          ← NEW
│   └── All Warehouses
└── Stock
    ├── Overview
    ├── Adjustments
    ├── Transfer
    └── Stock Alerts
```

### 4.8 Seeder: `WarehouseSeeder`

| Name | Code | City | Is Default |
|------|------|------|------------|
| Main Warehouse | WH-MAIN | Dhaka | Yes |
| Branch Store | WH-BRANCH | Chattogram | No |

---

### Phase 4 — Files to Create

```
Modules/Warehouse/
├── module.json
├── composer.json
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── WarehouseController.php
│   │   │   └── WarehouseLocationController.php
│   │   └── Requests/
│   │       ├── StoreWarehouseRequest.php
│   │       ├── UpdateWarehouseRequest.php
│   │       ├── StoreWarehouseLocationRequest.php
│   │       └── UpdateWarehouseLocationRequest.php
│   ├── Models/
│   │   ├── Warehouse.php
│   │   └── WarehouseLocation.php
│   ├── Services/
│   │   └── WarehouseService.php
│   └── Providers/
│       ├── WarehouseServiceProvider.php
│       └── RouteServiceProvider.php
├── config/config.php
├── database/
│   ├── migrations/
│   │   ├── YYYY_MM_DD_000001_create_warehouses_table.php
│   │   └── YYYY_MM_DD_000002_create_warehouse_locations_table.php
│   └── seeders/
│       └── WarehouseSeeder.php
├── resources/views/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── routes/
│   ├── web.php
│   └── api.php
└── tests/

Modules/Core/resources/views/partials/sidebar.blade.php  ← UPDATE
```

**Total:** ~22 new files + 1 sidebar update

---

## Phase 5 — Inventory Tracking

The core stock management engine. This phase connects Products, Variants, and Warehouses through stock tracking.

### 5.1 Migrations

#### Migration 1: `create_warehouse_stock_table`

```
warehouse_stock
├── id                      BIGINT UNSIGNED AUTO_INCREMENT PK
├── warehouse_id            BIGINT UNSIGNED NOT NULL FK → warehouses.id ON DELETE RESTRICT
├── warehouse_location_id   BIGINT UNSIGNED NULLABLE FK → warehouse_locations.id ON DELETE SET NULL
├── product_id              BIGINT UNSIGNED NOT NULL FK → products.id ON DELETE RESTRICT
├── product_variant_id      BIGINT UNSIGNED NULLABLE FK → product_variants.id ON DELETE RESTRICT
├── quantity                DECIMAL(15,2) NOT NULL DEFAULT 0.00
├── reserved_quantity       DECIMAL(15,2) NOT NULL DEFAULT 0.00
├── created_at              TIMESTAMP
├── updated_at              TIMESTAMP
│
├── UNIQUE(warehouse_id, product_id, product_variant_id)  ← composite unique
├── INDEX(product_id)
├── INDEX(product_variant_id)
├── INDEX(warehouse_location_id)
└── CHECK(quantity >= 0 OR product.allow_negative_stock = true)  ← enforced in service
```

**Key design decisions:**
- `RESTRICT` on delete — cannot delete a warehouse or product that has stock entries
- `reserved_quantity` tracks stock reserved for pending orders (not yet shipped)
- Available stock = `quantity - reserved_quantity`
- For single products: `product_variant_id = NULL`
- For variable products: one row per variant per warehouse
- Composite unique prevents duplicate stock entries

#### Migration 2: `create_stock_adjustments_table`

```
stock_adjustments
├── id                  BIGINT UNSIGNED AUTO_INCREMENT PK
├── reference_no        VARCHAR(50) NOT NULL UNIQUE
├── warehouse_id        BIGINT UNSIGNED NOT NULL FK → warehouses.id
├── adjustment_date     DATE NOT NULL
├── reason              ENUM('damaged','expired','lost','found','correction','opening_stock','production','other') NOT NULL
├── notes               TEXT NULLABLE
├── status              ENUM('draft','approved','rejected') DEFAULT 'draft'
├── total_items         INT UNSIGNED DEFAULT 0
├── total_amount        DECIMAL(15,2) DEFAULT 0.00
├── approved_by         BIGINT UNSIGNED NULLABLE FK → users.id
├── approved_at         TIMESTAMP NULLABLE
├── created_by          BIGINT UNSIGNED NOT NULL FK → users.id
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
├── deleted_at          TIMESTAMP NULLABLE
│
├── INDEX(warehouse_id)
├── INDEX(status)
├── INDEX(adjustment_date)
└── INDEX(created_by)
```

**Reference number format:** `ADJ-YYYYMM-XXXXX` (e.g., `ADJ-202603-00001`)

#### Migration 3: `create_stock_adjustment_items_table`

```
stock_adjustment_items
├── id                      BIGINT UNSIGNED AUTO_INCREMENT PK
├── stock_adjustment_id     BIGINT UNSIGNED NOT NULL FK → stock_adjustments.id ON DELETE CASCADE
├── product_id              BIGINT UNSIGNED NOT NULL FK → products.id
├── product_variant_id      BIGINT UNSIGNED NULLABLE FK → product_variants.id
├── current_qty             DECIMAL(15,2) NOT NULL
├── adjusted_qty            DECIMAL(15,2) NOT NULL
├── type                    ENUM('addition','subtraction') NOT NULL
├── unit_cost               DECIMAL(15,2) DEFAULT 0.00
├── reason                  VARCHAR(255) NULLABLE
├── created_at              TIMESTAMP
├── updated_at              TIMESTAMP
│
├── INDEX(stock_adjustment_id)
├── INDEX(product_id)
└── INDEX(product_variant_id)
```

**Logic:**
- `current_qty`: stock level at the time of adjustment (snapshot)
- `adjusted_qty`: the quantity being added or subtracted
- `type = addition`: new stock after = current_qty + adjusted_qty
- `type = subtraction`: new stock after = current_qty - adjusted_qty
- `unit_cost`: for valuation purposes (total_amount = sum of adjusted_qty × unit_cost)

#### Migration 4: `create_stock_transfers_table`

```
stock_transfers
├── id                      BIGINT UNSIGNED AUTO_INCREMENT PK
├── reference_no            VARCHAR(50) NOT NULL UNIQUE
├── from_warehouse_id       BIGINT UNSIGNED NOT NULL FK → warehouses.id
├── to_warehouse_id         BIGINT UNSIGNED NOT NULL FK → warehouses.id
├── transfer_date           DATE NOT NULL
├── expected_date           DATE NULLABLE
├── received_date           DATE NULLABLE
├── status                  ENUM('draft','in_transit','received','partial','cancelled') DEFAULT 'draft'
├── notes                   TEXT NULLABLE
├── shipping_cost           DECIMAL(15,2) DEFAULT 0.00
├── total_items             INT UNSIGNED DEFAULT 0
├── total_quantity          DECIMAL(15,2) DEFAULT 0.00
├── created_by              BIGINT UNSIGNED NOT NULL FK → users.id
├── received_by             BIGINT UNSIGNED NULLABLE FK → users.id
├── created_at              TIMESTAMP
├── updated_at              TIMESTAMP
├── deleted_at              TIMESTAMP NULLABLE
│
├── INDEX(from_warehouse_id)
├── INDEX(to_warehouse_id)
├── INDEX(status)
├── INDEX(transfer_date)
├── CHECK(from_warehouse_id != to_warehouse_id)
└── INDEX(created_by)
```

**Reference number format:** `TRF-YYYYMM-XXXXX` (e.g., `TRF-202603-00001`)

**Status flow:**
```
draft → in_transit → received
  │         │            │
  └→ cancelled    partial (some items received)
```

#### Migration 5: `create_stock_transfer_items_table`

```
stock_transfer_items
├── id                      BIGINT UNSIGNED AUTO_INCREMENT PK
├── stock_transfer_id       BIGINT UNSIGNED NOT NULL FK → stock_transfers.id ON DELETE CASCADE
├── product_id              BIGINT UNSIGNED NOT NULL FK → products.id
├── product_variant_id      BIGINT UNSIGNED NULLABLE FK → product_variants.id
├── quantity                DECIMAL(15,2) NOT NULL
├── received_quantity       DECIMAL(15,2) NULLABLE
├── unit_cost               DECIMAL(15,2) DEFAULT 0.00
├── notes                   VARCHAR(255) NULLABLE
├── created_at              TIMESTAMP
├── updated_at              TIMESTAMP
│
├── INDEX(stock_transfer_id)
├── INDEX(product_id)
└── INDEX(product_variant_id)
```

**Logic:**
- `quantity`: amount being transferred
- `received_quantity`: actual amount received (may differ due to damage in transit)
- If `received_quantity < quantity` → partial receipt, log difference in stock ledger

#### Migration 6: `create_stock_ledger_table`

```
stock_ledger
├── id                      BIGINT UNSIGNED AUTO_INCREMENT PK
├── warehouse_id            BIGINT UNSIGNED NOT NULL FK → warehouses.id
├── product_id              BIGINT UNSIGNED NOT NULL FK → products.id
├── product_variant_id      BIGINT UNSIGNED NULLABLE FK → product_variants.id
├── reference_type          VARCHAR(50) NOT NULL
├── reference_id            BIGINT UNSIGNED NOT NULL
├── type                    ENUM('in','out') NOT NULL
├── quantity                DECIMAL(15,2) NOT NULL
├── balance_after           DECIMAL(15,2) NOT NULL
├── unit_cost               DECIMAL(15,2) NULLABLE
├── total_cost              DECIMAL(15,2) NULLABLE
├── notes                   VARCHAR(500) NULLABLE
├── created_by              BIGINT UNSIGNED NULLABLE FK → users.id
├── created_at              TIMESTAMP
│
├── INDEX(warehouse_id)
├── INDEX(product_id)
├── INDEX(product_variant_id)
├── INDEX(reference_type, reference_id)
├── INDEX(type)
└── INDEX(created_at)
```

**This is an IMMUTABLE audit trail — never update or delete rows.**

**Reference types:**
| reference_type | reference_id points to | Description |
|---|---|---|
| `App\Models\Sale` | sales.id | Stock out from sale |
| `App\Models\SaleReturn` | sale_returns.id | Stock in from return |
| `App\Models\Purchase` | purchases.id | Stock in from purchase |
| `App\Models\PurchaseReturn` | purchase_returns.id | Stock out from return |
| `App\Models\StockAdjustment` | stock_adjustments.id | Manual adjustment |
| `App\Models\StockTransfer` | stock_transfers.id | Transfer between warehouses |
| `opening_stock` | stock_adjustments.id | Initial stock entry |

**`balance_after`** = running total after this transaction. Calculated as:
- `type = in`: previous_balance + quantity
- `type = out`: previous_balance - quantity

---

### 5.2 Models

#### `WarehouseStock`

```php
$fillable = ['warehouse_id', 'warehouse_location_id', 'product_id', 'product_variant_id', 'quantity', 'reserved_quantity'];

$casts = [
    'quantity'          => 'decimal:2',
    'reserved_quantity' => 'decimal:2',
];

// Relationships
belongsTo(Warehouse::class)          → warehouse
belongsTo(WarehouseLocation::class)  → location
belongsTo(Product::class)            → product
belongsTo(ProductVariant::class)     → variant

// Scopes
scopeLowStock($query)     → whereColumn('quantity', '<=', 'products.min_stock_alert') via join
scopeOutOfStock($query)   → where('quantity', '<=', 0)
scopeInStock($query)      → where('quantity', '>', 0)
scopeByWarehouse($query, $id)  → where('warehouse_id', $id)
scopeByProduct($query, $id)    → where('product_id', $id)

// Accessors
getAvailableQuantityAttribute() → $this->quantity - $this->reserved_quantity
getStockValueAttribute()        → $this->quantity * $this->product->cost_price
```

#### `StockAdjustment`

```php
$fillable = [
    'reference_no', 'warehouse_id', 'adjustment_date', 'reason', 'notes',
    'status', 'total_items', 'total_amount', 'approved_by', 'approved_at', 'created_by',
];

$casts = [
    'adjustment_date' => 'date',
    'approved_at'     => 'datetime',
    'total_amount'    => 'decimal:2',
];

// Relationships
belongsTo(Warehouse::class)           → warehouse
belongsTo(User::class, 'created_by')  → creator
belongsTo(User::class, 'approved_by') → approver
hasMany(StockAdjustmentItem::class)   → items

// Scopes
scopeDraft($query)     → where('status', 'draft')
scopeApproved($query)  → where('status', 'approved')

// Methods
isDraft(): bool       → $this->status === 'draft'
isApproved(): bool    → $this->status === 'approved'
canBeApproved(): bool → isDraft() && items->count() > 0
```

#### `StockAdjustmentItem`

```php
$fillable = [
    'stock_adjustment_id', 'product_id', 'product_variant_id',
    'current_qty', 'adjusted_qty', 'type', 'unit_cost', 'reason',
];

$casts = [
    'current_qty'  => 'decimal:2',
    'adjusted_qty' => 'decimal:2',
    'unit_cost'    => 'decimal:2',
];

// Relationships
belongsTo(StockAdjustment::class) → adjustment
belongsTo(Product::class)         → product
belongsTo(ProductVariant::class)  → variant

// Accessors
getNewQtyAttribute()    → type === 'addition' ? current_qty + adjusted_qty : current_qty - adjusted_qty
getTotalCostAttribute() → adjusted_qty * unit_cost
```

#### `StockTransfer`

```php
$fillable = [
    'reference_no', 'from_warehouse_id', 'to_warehouse_id', 'transfer_date',
    'expected_date', 'received_date', 'status', 'notes', 'shipping_cost',
    'total_items', 'total_quantity', 'created_by', 'received_by',
];

$casts = [
    'transfer_date'  => 'date',
    'expected_date'  => 'date',
    'received_date'  => 'date',
    'shipping_cost'  => 'decimal:2',
    'total_quantity'  => 'decimal:2',
];

// Relationships
belongsTo(Warehouse::class, 'from_warehouse_id')  → fromWarehouse
belongsTo(Warehouse::class, 'to_warehouse_id')    → toWarehouse
belongsTo(User::class, 'created_by')              → creator
belongsTo(User::class, 'received_by')             → receiver
hasMany(StockTransferItem::class)                  → items

// Scopes
scopeInTransit($query)  → where('status', 'in_transit')
scopePending($query)    → whereIn('status', ['draft', 'in_transit'])

// Methods
isEditable(): bool      → status === 'draft'
canBeShipped(): bool    → isDraft() && items->count() > 0
canBeReceived(): bool   → in_array(status, ['in_transit', 'partial'])
```

#### `StockTransferItem`

```php
$fillable = [
    'stock_transfer_id', 'product_id', 'product_variant_id',
    'quantity', 'received_quantity', 'unit_cost', 'notes',
];

$casts = [
    'quantity'          => 'decimal:2',
    'received_quantity' => 'decimal:2',
    'unit_cost'         => 'decimal:2',
];

// Relationships
belongsTo(StockTransfer::class) → transfer
belongsTo(Product::class)       → product
belongsTo(ProductVariant::class) → variant

// Accessors
getShortageAttribute() → quantity - (received_quantity ?? 0)
isFullyReceived(): bool → received_quantity >= quantity
```

#### `StockLedger`

```php
$fillable = [
    'warehouse_id', 'product_id', 'product_variant_id', 'reference_type',
    'reference_id', 'type', 'quantity', 'balance_after', 'unit_cost',
    'total_cost', 'notes', 'created_by',
];

$casts = [
    'quantity'      => 'decimal:2',
    'balance_after' => 'decimal:2',
    'unit_cost'     => 'decimal:2',
    'total_cost'    => 'decimal:2',
];

// IMMUTABLE — override delete and update to throw exception
public function delete() { throw new \LogicException('Stock ledger entries cannot be deleted.'); }
public function update() { throw new \LogicException('Stock ledger entries cannot be updated.'); }

// Relationships
belongsTo(Warehouse::class)           → warehouse
belongsTo(Product::class)             → product
belongsTo(ProductVariant::class)      → variant
belongsTo(User::class, 'created_by')  → creator
morphTo()                             → reference (via reference_type + reference_id)

// Scopes
scopeStockIn($query)         → where('type', 'in')
scopeStockOut($query)        → where('type', 'out')
scopeByProduct($query, $id)  → where('product_id', $id)
scopeByWarehouse($query, $id) → where('warehouse_id', $id)
scopeDateRange($query, $from, $to) → whereBetween('created_at', [$from, $to])
```

---

### 5.3 Service: `InventoryService`

This is the central service for all stock operations. All stock mutations MUST go through this service — no direct `WarehouseStock` updates from controllers.

#### Stock Query Methods

| Method | Description |
|--------|-------------|
| `getStockOverview(array $filters, int $perPage = 15)` | Paginated stock list across all warehouses. Filters: warehouse_id, category_id, brand_id, stock_status (low/out/in), search. Eager loads: product.category, product.brand, product.unit, variant. |
| `getProductStock(int $productId): Collection` | All warehouse stock for a single product (all variants). Returns grouped by warehouse. |
| `getAvailableStock(int $productId, ?int $variantId, int $warehouseId): float` | Available quantity = quantity - reserved_quantity. Used by POS and Sale modules before allowing a sale. |
| `getTotalStock(int $productId, ?int $variantId = null): float` | Sum of quantity across all warehouses for a product/variant. |
| `getReservedStock(int $productId, ?int $variantId, int $warehouseId): float` | Reserved quantity from pending orders. |
| `getStockValue(int $warehouseId): float` | Total stock value in a warehouse (sum of qty × cost_price). |

#### Stock Mutation Methods

| Method | Description | Key Logic |
|--------|-------------|-----------|
| `stockIn(int $productId, ?int $variantId, int $warehouseId, float $qty, string $refType, int $refId, ?float $unitCost, ?string $notes, ?int $userId)` | Add stock to warehouse | `DB::transaction` — (1) Update or create WarehouseStock row, increment quantity. (2) Write StockLedger entry with type='in' and calculated balance_after. |
| `stockOut(int $productId, ?int $variantId, int $warehouseId, float $qty, string $refType, int $refId, ?string $notes, ?int $userId)` | Remove stock from warehouse | `DB::transaction` — (1) Check available stock >= qty (unless allow_negative_stock). (2) Decrement WarehouseStock.quantity. (3) Write StockLedger entry with type='out'. Throws `InsufficientStockException` if blocked. |
| `reserveStock(int $productId, ?int $variantId, int $warehouseId, float $qty)` | Reserve stock for pending order | Increment reserved_quantity. Check available_qty >= qty first. |
| `releaseReservedStock(int $productId, ?int $variantId, int $warehouseId, float $qty)` | Release reserved stock (order cancelled) | Decrement reserved_quantity. |
| `convertReservedToOut(int $productId, ?int $variantId, int $warehouseId, float $qty, string $refType, int $refId)` | Ship reserved stock | Decrement both quantity and reserved_quantity. Write ledger. Used when an order is shipped. |

#### Stock Adjustment Methods

| Method | Description | Key Logic |
|--------|-------------|-----------|
| `createAdjustment(array $data): StockAdjustment` | Create draft adjustment with items | Auto-generate reference_no. Populate current_qty from WarehouseStock. Calculate total_items, total_amount. Status = draft. |
| `approveAdjustment(StockAdjustment $adjustment, int $approverId): StockAdjustment` | Approve and apply adjustment | `DB::transaction` — For each item: call `stockIn()` or `stockOut()` based on type. Update status to approved. Set approved_by and approved_at. |
| `rejectAdjustment(StockAdjustment $adjustment, int $approverId): StockAdjustment` | Reject adjustment | Set status to rejected. No stock changes. |
| `listAdjustments(array $filters, int $perPage = 15)` | Paginated adjustment list | Filters: warehouse_id, status, reason, date_range, search. |

#### Stock Transfer Methods

| Method | Description | Key Logic |
|--------|-------------|-----------|
| `createTransfer(array $data): StockTransfer` | Create draft transfer | Auto-generate reference_no. Validate from != to warehouse. Calculate totals. |
| `shipTransfer(StockTransfer $transfer): StockTransfer` | Mark as in_transit, deduct source stock | `DB::transaction` — For each item: call `stockOut()` from source warehouse. Update status to in_transit. |
| `receiveTransfer(StockTransfer $transfer, array $receivedItems, int $receiverId): StockTransfer` | Receive at destination | `DB::transaction` — For each item: update received_quantity, call `stockIn()` to destination warehouse. If all fully received → status=received. If some short → status=partial. Write shortage notes to ledger. |
| `cancelTransfer(StockTransfer $transfer): StockTransfer` | Cancel transfer | If in_transit: reverse stockOut (call stockIn to return to source). Set status=cancelled. If draft: just cancel. |
| `listTransfers(array $filters, int $perPage = 15)` | Paginated transfer list | Filters: from/to warehouse, status, date_range. |

#### Alert Methods

| Method | Description |
|--------|-------------|
| `getLowStockAlerts(?int $warehouseId = null): Collection` | Products where quantity <= min_stock_alert and quantity > 0 |
| `getOutOfStockAlerts(?int $warehouseId = null): Collection` | Products where quantity <= 0 |
| `getOverstockAlerts(?int $warehouseId = null): Collection` | Products where quantity > max_stock_level (if set) |
| `getAlertsSummary(): array` | Counts: low_stock, out_of_stock, overstock |

#### Ledger Methods

| Method | Description |
|--------|-------------|
| `getStockLedger(array $filters, int $perPage = 20)` | Paginated ledger entries. Filters: product_id, variant_id, warehouse_id, type (in/out), reference_type, date_range. |
| `getProductStockLedger(int $productId, ?int $variantId, ?int $warehouseId, ?array $dateRange): Collection` | Full transaction history for a specific product. Shows running balance. |
| `getStockMovementSummary(int $warehouseId, array $dateRange): array` | Summary: total_in, total_out, opening_balance, closing_balance for date range. |

---

### 5.4 Exception Classes

```
app/Exceptions/
├── InsufficientStockException.php    → thrown when stockOut fails due to low stock
├── InvalidTransferException.php      → thrown when source = destination warehouse
└── ImmutableRecordException.php      → thrown when attempting to modify stock ledger
```

---

### 5.5 Controller: `InventoryController` (Update existing)

| Method | View | Service Calls |
|--------|------|---------------|
| `index()` | `inventory::index` | `getStockOverview(filters)`, `getAlertsSummary()` |
| `adjustments()` | `inventory::adjustments` | `listAdjustments(filters)` |
| `createAdjustment()` | `inventory::adjustments-create` | Warehouses list, products for search |
| `storeAdjustment(StoreAdjustmentRequest)` | redirect | `createAdjustment(data)` |
| `approveAdjustment(StockAdjustment)` | redirect | `approveAdjustment(adjustment, auth_id)` |
| `transfers()` | `inventory::transfers` | `listTransfers(filters)` |
| `createTransfer()` | `inventory::transfers-create` | Warehouses list, products for search |
| `storeTransfer(StoreTransferRequest)` | redirect | `createTransfer(data)` |
| `shipTransfer(StockTransfer)` | redirect | `shipTransfer(transfer)` |
| `receiveTransfer(ReceiveTransferRequest, StockTransfer)` | redirect | `receiveTransfer(transfer, items, auth_id)` |
| `alerts()` | `inventory::alerts` | `getLowStockAlerts()`, `getOutOfStockAlerts()` |
| `ledger()` | `inventory::ledger` | `getStockLedger(filters)` |
| `stockLedger()` | `inventory::stock-ledger` | `getProductStockLedger(filters)` |

---

### 5.6 Form Requests

**StoreAdjustmentRequest:**
```
warehouse_id      → required | integer | exists:warehouses,id
adjustment_date   → required | date | before_or_equal:today
reason            → required | in:damaged,expired,lost,found,correction,opening_stock,production,other
notes             → nullable | string | max:1000
items             → required | array | min:1
items.*.product_id         → required | integer | exists:products,id
items.*.product_variant_id → nullable | integer | exists:product_variants,id
items.*.adjusted_qty       → required | numeric | gt:0
items.*.type               → required | in:addition,subtraction
items.*.unit_cost          → nullable | numeric | min:0
items.*.reason             → nullable | string | max:255
```

**StoreTransferRequest:**
```
from_warehouse_id  → required | integer | exists:warehouses,id | different:to_warehouse_id
to_warehouse_id    → required | integer | exists:warehouses,id
transfer_date      → required | date
expected_date      → nullable | date | after_or_equal:transfer_date
notes              → nullable | string | max:1000
shipping_cost      → nullable | numeric | min:0
items              → required | array | min:1
items.*.product_id         → required | integer | exists:products,id
items.*.product_variant_id → nullable | integer | exists:product_variants,id
items.*.quantity           → required | numeric | gt:0
items.*.unit_cost          → nullable | numeric | min:0
```

**ReceiveTransferRequest:**
```
items                      → required | array | min:1
items.*.stock_transfer_item_id → required | integer | exists:stock_transfer_items,id
items.*.received_quantity  → required | numeric | min:0
items.*.notes              → nullable | string | max:255
```

---

### Phase 5 — Files to Create

```
Modules/Inventory/
├── database/migrations/
│   ├── YYYY_MM_DD_000001_create_warehouse_stock_table.php
│   ├── YYYY_MM_DD_000002_create_stock_adjustments_table.php
│   ├── YYYY_MM_DD_000003_create_stock_adjustment_items_table.php
│   ├── YYYY_MM_DD_000004_create_stock_transfers_table.php
│   ├── YYYY_MM_DD_000005_create_stock_transfer_items_table.php
│   └── YYYY_MM_DD_000006_create_stock_ledger_table.php
├── app/Models/
│   ├── WarehouseStock.php
│   ├── StockAdjustment.php
│   ├── StockAdjustmentItem.php
│   ├── StockTransfer.php
│   ├── StockTransferItem.php
│   └── StockLedger.php
├── app/Http/Requests/
│   ├── StoreAdjustmentRequest.php
│   ├── StoreTransferRequest.php
│   └── ReceiveTransferRequest.php
├── app/Services/
│   └── InventoryService.php
├── app/Exceptions/
│   ├── InsufficientStockException.php
│   ├── InvalidTransferException.php
│   └── ImmutableRecordException.php
└── app/Http/Controllers/InventoryController.php  ← UPDATE existing
```

**Total:** ~18 new files + 1 controller update

---

## Phase 6 — Cross-Module Integration & Seeders

### 6.1 Product → Inventory Auto-Wiring

When a product is created via `ProductService::create()`:
1. Get the default warehouse via `WarehouseService::getDefault()`
2. Create a `WarehouseStock` entry with quantity = 0 for that product in the default warehouse
3. If the product has variants, create one `WarehouseStock` entry per variant

When a product is deleted via `ProductService::delete()`:
1. Check if any `WarehouseStock` has quantity > 0 → block deletion
2. Check if any pending orders reference this product → block deletion

### 6.2 Event-Driven Integration (Laravel Events)

Instead of hard-coupling modules, use events:

| Event | Fired By | Listened By |
|-------|----------|-------------|
| `ProductCreated` | ProductService | InventoryService → create initial stock entries |
| `ProductDeleted` | ProductService | InventoryService → clean up zero-stock entries |
| `SaleCompleted` | SaleService (future) | InventoryService → stockOut for each sale item |
| `SaleReturned` | SaleReturnService (future) | InventoryService → stockIn for returned items |
| `PurchaseReceived` | PurchaseService (future) | InventoryService → stockIn for received items |
| `PurchaseReturned` | PurchaseReturnService (future) | InventoryService → stockOut for returned items |
| `LowStockDetected` | InventoryService | NotificationService (future) → send alert |

**Event/Listener structure:**
```
Modules/Product/app/Events/ProductCreated.php
Modules/Inventory/app/Listeners/CreateInitialStock.php
```

Register in `EventServiceProvider` of each module.

### 6.3 Helpers: `App\Helpers\Formatter`

```php
class Formatter
{
    /**
     * Format amount in BDT with Bangladeshi number system (lakh/crore)
     * Example: 123456.50 → "BDT 1,23,456.50"
     */
    public static function bdt(float $amount): string

    /**
     * Format date as DD MMM YYYY
     * Example: 2026-03-13 → "13 Mar 2026"
     */
    public static function date($date): string

    /**
     * Format number in Bangladeshi system
     * Example: 1234567 → "12,34,567"
     */
    public static function bdNumber(float $number): string

    /**
     * Format quantity with unit
     * Example: (150, "kg") → "150 kg"
     */
    public static function quantity(float $qty, string $unit): string
}
```

**Register as Blade directives in `AppServiceProvider`:**
```php
Blade::directive('bdt', fn($amount) => "<?php echo \App\Helpers\Formatter::bdt($amount); ?>");
Blade::directive('bdtDate', fn($date) => "<?php echo \App\Helpers\Formatter::date($date); ?>");
```

### 6.4 Seeders

#### `CategorySeeder`
```
Electronics
├── Mobile Phones
├── Laptops & Computers
├── Accessories
└── Home Appliances

Fashion & Clothing
├── Men's Wear
├── Women's Wear
├── Kids' Wear
└── Footwear

Grocery & Food
├── Rice & Grains
├── Spices & Masala
├── Beverages
├── Snacks
└── Dairy

Beauty & Health
├── Skincare
├── Haircare
└── Personal Care

Home & Living
├── Kitchen
├── Furniture
└── Decor
```

#### `BrandSeeder`
```
Samsung, Walton, Symphony, Xiaomi, Realme, Oppo, Vivo       (Electronics)
RFL, Pran, ACI, Fresh, Radhuni, Ispahani, Teer              (FMCG)
Apex, Bata, Yellow, Sailor                                    (Fashion)
Meril, Keya, Tibet, Lux                                       (Beauty)
```

#### `ProductSeeder` (20-30 sample products)

| Product | Type | Category | Brand | Cost | Sell |
|---------|------|----------|-------|------|------|
| Samsung Galaxy A15 | variable (Color, Storage) | Mobile Phones | Samsung | 14,500 | 16,990 |
| Walton Primo R9 | variable (Color) | Mobile Phones | Walton | 7,200 | 8,499 |
| Miniket Rice 5kg | single | Rice & Grains | Pran | 520 | 599 |
| Radhuni Turmeric 200g | single | Spices & Masala | Radhuni | 55 | 75 |
| Cotton T-Shirt Men | variable (Color, Size) | Men's Wear | — | 280 | 450 |
| RFL Water Bottle 1L | single | Kitchen | RFL | 85 | 120 |
| Laptop Backpack | single | Accessories | — | 650 | 999 |
| ... | | | | | |

#### `StockSeeder` (Opening stock)
- Assigns random stock quantities (10-500) to products in the default warehouse
- Creates stock ledger entries with reference_type = "opening_stock"

#### `WarehouseSeeder`
- Main Warehouse (WH-MAIN, Dhaka, default)
- Branch Store (WH-CTG, Chattogram)

---

### 6.5 Database Seeder Execution Order

```php
// database/seeders/DatabaseSeeder.php
public function run(): void
{
    $this->call([
        // Phase 1 — Foundation
        UnitSeeder::class,
        CategorySeeder::class,
        BrandSeeder::class,

        // Phase 4 — Warehouses
        WarehouseSeeder::class,

        // Phase 2+3 — Products with variants
        ProductSeeder::class,       // Creates products + variants + images + tags

        // Phase 5 — Opening stock
        StockSeeder::class,         // Assigns stock to products in warehouses
    ]);
}
```

---

### Phase 6 — Files to Create

```
app/Helpers/Formatter.php
app/Providers/AppServiceProvider.php  ← UPDATE (register Blade directives)

Modules/Product/app/Events/ProductCreated.php
Modules/Product/app/Events/ProductDeleted.php
Modules/Inventory/app/Listeners/CreateInitialStock.php
Modules/Inventory/app/Listeners/CleanupStock.php

database/seeders/
├── CategorySeeder.php
├── BrandSeeder.php
├── UnitSeeder.php
├── WarehouseSeeder.php
├── ProductSeeder.php
├── StockSeeder.php
└── DatabaseSeeder.php  ← UPDATE
```

**Total:** ~12 new files + 2 updates

---

## Complete File Count Summary

| Phase | New Files | Updates | Description |
|-------|-----------|---------|-------------|
| Phase 1 | ~20 | 3 controllers | Categories, Brands, Units |
| Phase 2 | ~14 | 2 controllers | Products, Images, Tags, Barcode |
| Phase 3 | ~12 | 2 files | Variants & Attributes |
| Phase 4 | ~22 | 1 sidebar | Warehouse Module (new) |
| Phase 5 | ~18 | 1 controller | Inventory Tracking |
| Phase 6 | ~12 | 2 providers | Integration, Seeders, Helpers |
| **Total** | **~98** | **~11** | |

---

## Database Schema — Complete ER Summary

```
categories (Phase 1)
    ↑ self-ref parent_id
    └── products.category_id

brands (Phase 1)
    └── products.brand_id

units (Phase 1)
    ↑ self-ref base_unit_id
    └── products.unit_id

products (Phase 2)
    ├── product_images (Phase 2)
    ├── product_tag pivot → tags (Phase 2)
    ├── product_variants (Phase 3)
    │   └── product_variant_values pivot → variant_attribute_values → variant_attributes (Phase 3)
    ├── warehouse_stock (Phase 5)
    └── stock_ledger (Phase 5)

warehouses (Phase 4)
    ├── warehouse_locations (Phase 4)
    ├── warehouse_stock (Phase 5)
    ├── stock_adjustments → stock_adjustment_items (Phase 5)
    ├── stock_transfers → stock_transfer_items (Phase 5)
    └── stock_ledger (Phase 5)
```

**Total tables: 18**

```
Phase 1:  categories, brands, units                                    (3 tables)
Phase 2:  products, product_images, tags, product_tag                  (4 tables)
Phase 3:  variant_attributes, variant_attribute_values,                (4 tables)
          product_variants, product_variant_values
Phase 4:  warehouses, warehouse_locations                              (2 tables)
Phase 5:  warehouse_stock, stock_adjustments, stock_adjustment_items,  (5 tables)
          stock_transfers, stock_transfer_items, stock_ledger
```
