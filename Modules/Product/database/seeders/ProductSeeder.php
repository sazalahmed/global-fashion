<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Unit\Models\Unit;

class ProductSeeder extends Seeder
{
    /**
     * Local folder (relative to public/) where the catalog images were
     * downloaded, one file per SKU: public/uploads/products/{sku}.{ext}.
     */
    private const IMAGE_DIR = 'uploads/products';

    /**
     * Seed the Global Fashion product catalog (simple products, no variants).
     * Depends on the category tree (CategoryDatabaseSeeder) and units.
     *
     * Stock is intentionally NOT set here — opening stock is assigned by
     * Modules\Inventory\Database\Seeders\InventoryStockSeeder.
     */
    public function run(): void
    {
        $this->ensureDependencies();

        $unitId = Unit::where('name', 'Piece')->value('id') ?? Unit::value('id');

        // name, sku, category (CSV value; '' = blank), sell, cost, wholesale, image (raw CSV)
        $products = [
            ['2 PC Combo Zero Nick Kurta, GF-0108', '20250108', 'KATUA', 1800, 600, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/W41e6b7jtbDMBOqkJsK3kxc9RTIsuutS0ZHvit7H1744383600.jpg'],
            ['2 PC Combo Zero Nick Kurta, GF-0109', '20250109', 'KATUA', 1800, 600, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/ylN2l5Jv7VPxzw1yUa3ZtNfynQe5j1ehG25KBVIv1744383800.jpg'],
            ['2 PC COMBO, CONTRAST KATUA, GF-081', '2025081', '', 3400, 1000, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/2%20PC%20COMBO%20CONTRAST-011744467226.png'],
            ['2 PC COMBO, CONTRAST KATUA, GF-084', '2025084', '', 3400, 1000, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/B2%20PC%20COMBO%20CONTRAST%20olive%20%20%20%26amp%3B%20black-01-011744519310.png'],
            ['Full Sleeve Katua II GLK-004', 'GLK-2025004', '', 1100, 500, null, 'images/zummXD2dvAtI.png'],
            ['Full Sleeve Katua II GLK-009', 'GLK-2025009', '', 1100, 500, null, 'images/zummXD2dvAtI.png'],
            ['Full Sleeve Katua II GLK-31', 'GLK-20250031', '', 1100, 500, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, GF-001', 'GF2025001', '', 1350, 540, 810.00, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/IMG-20250510-WA000917471408241772705225.jpg'],
            ['GLOBAL CASUAL SHIRT, GF-002', 'GF2025002', '', 1350, 540, 810.00, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/IMG-20250510-WA00031747141458.jpg'],
            ['GLOBAL CASUAL SHIRT, GF-0021', '20250021', '', 850, 370, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/00211744993515.png'],
            ['GLOBAL CASUAL SHIRT, GF-0022', '20250022', '', 850, 370, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/2025_04_09_02_12_IMG_58941744269839.PNG'],
            ['GLOBAL CASUAL SHIRT, GF-0023', '20250023', '', 850, 370, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/2025_04_09_02_12_IMG_58951744269869.PNG'],
            ['GLOBAL CASUAL SHIRT, GF-0024', '20250024', '', 850, 350, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/2025_04_09_02_12_IMG_58921744269896.PNG'],
            ['GLOBAL CASUAL SHIRT, GF-0025', '20250025', '', 850, 370, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/1-011744993553.png'],
            ['GLOBAL CASUAL SHIRT, GF-0026', '20250026', '', 850, 370, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/22-011744993588.png'],
            ['GLOBAL CASUAL SHIRT, GF-0027', '20250027', '', 850, 350, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/2025_04_09_02_11_IMG_58831744270327.PNG'],
            ['GLOBAL CASUAL SHIRT, GF-0028', '20250028', '', 850, 350, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/2025_04_09_02_12_IMG_58851744270388.PNG'],
            ['GLOBAL CASUAL SHIRT, GF-003', 'GF2025003', '', 1350, 540, 810.00, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/IMG-20250510-WA00021747141522.jpg'],
            ['GLOBAL CASUAL SHIRT, GF-005', 'GF2025005', '', 1350, 540, 810.00, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/IMG-20250510-WA00061747140953.jpg'],
            ['GLOBAL CASUAL SHIRT, MPS-013', 'MPS-2025013', 'POLO & T-SHIRT', 1490, 745, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-026', 'MS-2025026', '', 1450, 580, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-029', 'MS-2025029', '', 1450, 580, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-031', 'MS-2025031', '', 1650, 660, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-037', 'MS-2025037', '', 1350, 540, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-039', 'MS-2025039', 'FULL SLEEVES', 1350, 540, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-040', 'MS-2025040', '', 1350, 540, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-044', 'MS-2025044', '', 1450, 580, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-050', 'MS-2025050', '', 1550, 620, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-054', 'MS-2025054', '', 1350, 540, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-055', 'MS-2025055', '', 1350, 540, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-060', 'MS-2025060', '', 1550, 620, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-062', 'MS-2025062', '', 1350, 540, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-071', 'MS-2025071', '', 1450, 580, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-076', 'MS-2025076', '', 1450, 580, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-077', 'MS-2025077', '', 1650, 660, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-091', 'MS-2025091', '', 1650, 660, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-095', 'MS-2025095', '', 1350, 540, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CASUAL SHIRT, MS-096', 'MS-2025096', '', 1350, 540, null, 'images/zummXD2dvAtI.png'],
            ['GLOBAL CONTRAST KATUA, GF-070', '2025070', '', 1700, 500, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/IMG_18061745068318.JPG'],
            ['GLOBAL CONTRAST KATUA, GF-071', '2025071', '', 1700, 500, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/IMG_18201745068582.JPG'],
            ['GLOBAL CONTRAST KATUA, GF-072', '2025072', '', 1700, 500, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/IMG_18091745068196.JPG'],
            ['GLOBAL CONTRAST KATUA, GF-073', '2025073', '', 1700, 500, 1000.00, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/IMG_18121745068240.JPG'],
            ['GLOBAL CONTRAST KATUA, GF-074', '2025074', '', 1700, 500, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/IMG_18151745068276.JPG'],
            ['GLOBAL CONTRAST KATUA, GF-075', '2025075', '', 1700, 500, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/IMG_18161745068418.JPG'],
            ['GLOBAL CONTRAST KATUA, GF-076', '2025076', '', 1700, 500, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/IMG_18071745068478.JPG'],
            ['GLOBAL CONTRAST KATUA, GF-077', '2025077', '', 500, 1700, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/katua1744876257.jpeg'],
            ['GLOBAL Cotton Shirt Black, GCS-02', 'GCS202502', 'HALF SLEEVES', 900, 400, 550.00, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/BLACK-removebg-preview1744614841.png'],
            ['GLOBAL Cotton Shirt Gray, GCS-05', 'GCS202505', 'HALF SLEEVES', 900, 400, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/ASH-removebg-preview1744615058.png'],
            ['GLOBAL Cotton Shirt Sky, GCS-03', 'GCS202503', 'HALF SLEEVES', 900, 400, null, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/SKY-removebg-preview1744614924.png'],
            ['GLOBAL Cotton Shirt White, GCS-01', 'GCS202501', 'HALF SLEEVES', 900, 400, 500.00, 'https://globalfashion.com.bd/posadmin/images/product/xsmall/WHITE-removebg-preview1744614350.png'],
        ];

        // Cache category ids by slug for fast lookup.
        $categoryIds = Category::pluck('id', 'slug');
        $fallbackCategoryId = $categoryIds['shirt'] ?? Category::value('id');

        $count = 0;
        foreach ($products as [$name, $sku, $csvCategory, $sell, $cost, $wholesale, $rawImage]) {
            $thumbnail = $this->localImagePath($sku, $rawImage);

            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name'            => $name,
                    'slug'            => Str::slug($name),
                    'product_type'    => 'simple',
                    'category_id'     => $this->resolveCategoryId($csvCategory, $name, $categoryIds, $fallbackCategoryId),
                    'unit_id'         => $unitId,
                    'cost_price'      => $cost,
                    'sell_price'      => $sell,
                    'wholesale_price' => $wholesale,
                    'thumbnail'       => $thumbnail,
                    'status'          => 'active',
                    'show_in_pos'     => true,
                    'track_stock'     => true,
                    'ecom_visible'    => true,
                    'ecom_sync'       => true,
                ]
            );

            // Mirror the thumbnail as a primary product image so galleries
            // (product show / storefront) render it too.
            if ($thumbnail) {
                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'image_path' => $thumbnail],
                    ['alt_text' => $name, 'sort_order' => 0, 'is_primary' => true]
                );
            }

            $count++;
        }

        $this->command->info("Seeded {$count} Global Fashion products.");
    }

    // ── Helpers ──

    /**
     * Resolve the locally-downloaded image for a product. Images were saved as
     * public/uploads/products/{sku}.{ext}, where {ext} comes from the source
     * file. Returns the relative path (for Upload::url / asset) when the file
     * exists on disk, or null for placeholders and any image that failed to
     * download — so the seeder always reflects what is actually present.
     */
    private function localImagePath(string $sku, ?string $raw): ?string
    {
        // Placeholder / blank rows have no real image.
        if (empty($raw) || Str::startsWith($raw, 'images/')) {
            return null;
        }

        $ext = strtolower(pathinfo(parse_url($raw, PHP_URL_PATH) ?? $raw, PATHINFO_EXTENSION));
        if ($ext === '') {
            return null;
        }

        $relative = self::IMAGE_DIR . '/' . $sku . '.' . $ext;

        return file_exists(public_path($relative)) ? $relative : null;
    }

    /**
     * Resolve a product's category. Uses the CSV category when present;
     * otherwise infers from the product name (Katua / Combo) and finally
     * falls back to the generic "Shirt" category.
     */
    private function resolveCategoryId(string $csvCategory, string $name, $categoryIds, int $fallbackId): int
    {
        if ($csvCategory !== '') {
            return $categoryIds[Str::slug($csvCategory)] ?? $fallbackId;
        }

        $upper = strtoupper($name);
        if (Str::contains($upper, 'COMBO')) {
            return $categoryIds['combo-offers'] ?? $fallbackId;
        }
        if (Str::contains($upper, 'KATUA')) {
            return $categoryIds['katua'] ?? $fallbackId;
        }

        return $fallbackId;
    }

    /**
     * Ensure the category tree and a base unit exist before seeding products.
     */
    private function ensureDependencies(): void
    {
        if (Category::count() === 0) {
            $this->call(\Modules\Category\Database\Seeders\CategoryDatabaseSeeder::class);
        }

        if (Unit::count() === 0) {
            $this->call(\Modules\Unit\Database\Seeders\UnitSeeder::class);
        }
    }
}
