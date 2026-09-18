<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Models\Banner;
use Modules\Ecommerce\Models\BlogPost;
use Modules\Ecommerce\Models\EcommerceSetting;
use Modules\Ecommerce\Models\FlashDeal;
use Modules\Ecommerce\Models\HomepageSection;
use Modules\Ecommerce\Models\ProductReview;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Unit\Models\Unit;

class StorefrontSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedBrands();
        $this->seedProducts();
        $this->seedBanners();
        $this->seedFlashDeal();
        $this->seedBlogPosts();
        $this->seedHomepageSections();
        $this->seedReviews();
        $this->seedSettings();

        $this->command->info('Storefront seed data created successfully.');
    }

    private function seedCategories(): void
    {
        $categories = [
            ['name' => "Men's Fashion",     'slug' => 'mens-fashion',     'sort_order' => 1, 'description' => 'Shirts, pants, jackets and more for men'],
            ['name' => "Women's Fashion",   'slug' => 'womens-fashion',   'sort_order' => 2, 'description' => 'Sharee, kurti, tops and more for women'],
            ['name' => "Kid's Fashion",     'slug' => 'kids-fashion',     'sort_order' => 3, 'description' => 'Clothing and accessories for children'],
            ['name' => 'Denim Collection',  'slug' => 'denim-collection', 'sort_order' => 4, 'description' => 'Jeans, jackets and denim wear'],
            ['name' => 'Western Wear',      'slug' => 'western-wear',     'sort_order' => 5, 'description' => 'Dresses, tops, skirts and western outfits'],
            ['name' => 'Sport Wear',        'slug' => 'sport-wear',       'sort_order' => 6, 'description' => 'Activewear and sports clothing'],
            ['name' => 'Footwear',          'slug' => 'footwear',         'sort_order' => 7, 'description' => 'Shoes, sandals, boots and slippers'],
            ['name' => 'Fashion Jewellery', 'slug' => 'fashion-jewellery','sort_order' => 8, 'description' => 'Necklaces, earrings, bracelets'],
            ['name' => 'Beauty & Cosmetics','slug' => 'beauty-cosmetics', 'sort_order' => 9, 'description' => 'Skincare, makeup and beauty products'],
        ];

        $childCategories = [
            'mens-fashion' => [
                ['name' => 'Casual Shirts', 'slug' => 'casual-shirts'],
                ['name' => 'Formal Shirts', 'slug' => 'formal-shirts'],
                ['name' => 'T-Shirts',      'slug' => 'mens-tshirts'],
                ['name' => 'Pants',         'slug' => 'mens-pants'],
            ],
            'womens-fashion' => [
                ['name' => 'Sharee',    'slug' => 'sharee'],
                ['name' => 'Kurti',     'slug' => 'kurti'],
                ['name' => 'Tops',      'slug' => 'womens-tops'],
                ['name' => '3-Piece',   'slug' => 'three-piece'],
            ],
            'kids-fashion' => [
                ['name' => "Boys' Fashion",  'slug' => 'boys-fashion'],
                ['name' => "Girls' Fashion", 'slug' => 'girls-fashion'],
                ['name' => 'Party Wear',     'slug' => 'kids-party-wear'],
            ],
            'footwear' => [
                ['name' => "Men's Shoes",   'slug' => 'mens-shoes'],
                ['name' => "Women's Shoes", 'slug' => 'womens-shoes'],
                ['name' => 'Sandals',       'slug' => 'sandals'],
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], array_merge($cat, ['status' => 'active']));
        }

        foreach ($childCategories as $parentSlug => $children) {
            $parent = Category::where('slug', $parentSlug)->first();
            if (!$parent) continue;

            foreach ($children as $i => $child) {
                Category::firstOrCreate(['slug' => $child['slug']], array_merge($child, [
                    'parent_id'  => $parent->id,
                    'status'     => 'active',
                    'sort_order' => $i + 1,
                ]));
            }
        }
    }

    private function seedBrands(): void
    {
        $brands = [
            ['name' => 'Yellow',     'slug' => 'yellow',     'is_featured' => true,  'sort_order' => 1],
            ['name' => 'Aarong',     'slug' => 'aarong',     'is_featured' => true,  'sort_order' => 2],
            ['name' => 'Cats Eye',   'slug' => 'cats-eye',   'is_featured' => true,  'sort_order' => 3],
            ['name' => 'Richman',    'slug' => 'richman',    'is_featured' => true,  'sort_order' => 4],
            ['name' => 'Ecstasy',    'slug' => 'ecstasy',    'is_featured' => true,  'sort_order' => 5],
            ['name' => 'Gentle Park','slug' => 'gentle-park','is_featured' => true,  'sort_order' => 6],
            ['name' => 'Easy',       'slug' => 'easy',       'is_featured' => false, 'sort_order' => 7],
            ['name' => 'Dorji Bari', 'slug' => 'dorji-bari', 'is_featured' => false, 'sort_order' => 8],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['slug' => $brand['slug']], array_merge($brand, ['status' => 'active']));
        }
    }

    private function seedProducts(): void
    {
        $unit = Unit::where('name', 'Piece')->first() ?? Unit::first();
        if (!$unit) return;

        $categories = Category::pluck('id', 'slug');
        $brands = Brand::pluck('id', 'slug');

        $products = [
            ['name' => 'Full Sleeve Hoodie Jacket',       'sku' => 'EC-HOODIE-001',    'category' => 'mens-fashion',     'brand' => 'richman',    'cost' => 800,  'sell' => 1450,  'discount_type' => 'percentage', 'discount_value' => 20],
            ['name' => 'Denim Casual Blazer for Men',      'sku' => 'EC-BLAZER-001',    'category' => 'denim-collection', 'brand' => 'cats-eye',   'cost' => 1500, 'sell' => 2800,  'discount_type' => 'percentage', 'discount_value' => 15],
            ['name' => "Women's Western Party Dress",      'sku' => 'EC-DRESS-001',     'category' => 'western-wear',     'brand' => 'yellow',     'cost' => 900,  'sell' => 1650,  'discount_type' => 'percentage', 'discount_value' => 10],
            ['name' => "Kid's Western Party Dress",        'sku' => 'EC-KIDRESS-001',   'category' => 'kids-fashion',     'brand' => null,         'cost' => 500,  'sell' => 950,   'discount_type' => 'percentage', 'discount_value' => 25],
            ['name' => "Men's Premium Formal Shirt",       'sku' => 'EC-FSHIRT-001',    'category' => 'mens-fashion',     'brand' => 'gentle-park','cost' => 650,  'sell' => 1200,  'discount_type' => 'fixed',      'discount_value' => 150],
            ['name' => 'Kids Cotton Combo Set',            'sku' => 'EC-KIDCOMBO-001',  'category' => 'kids-fashion',     'brand' => null,         'cost' => 400,  'sell' => 750,   'discount_type' => 'percentage', 'discount_value' => 30],
            ['name' => "Men's Trendy Formal Shoes",        'sku' => 'EC-SHOES-001',     'category' => 'footwear',         'brand' => 'ecstasy',    'cost' => 1200, 'sell' => 2200,  'discount_type' => 'none',       'discount_value' => 0],
            ['name' => 'Denim 2 Quarter Pant',             'sku' => 'EC-QPANT-001',     'category' => 'denim-collection', 'brand' => 'richman',    'cost' => 550,  'sell' => 990,   'discount_type' => 'none',       'discount_value' => 0],
            ['name' => "Men's Denim Combo Set",            'sku' => 'EC-DCOMBO-001',    'category' => 'denim-collection', 'brand' => 'cats-eye',   'cost' => 1100, 'sell' => 1950,  'discount_type' => 'percentage', 'discount_value' => 10],
            ['name' => 'Half Sleeve Tops for Women',       'sku' => 'EC-WTOPS-001',     'category' => 'womens-fashion',   'brand' => 'yellow',     'cost' => 350,  'sell' => 650,   'discount_type' => 'none',       'discount_value' => 0],
            ['name' => 'Sharee Petticoat For Women',       'sku' => 'EC-PETTI-001',     'category' => 'womens-fashion',   'brand' => 'aarong',     'cost' => 800,  'sell' => 1450,  'discount_type' => 'none',       'discount_value' => 0],
            ['name' => 'Jeans Pants For Women',            'sku' => 'EC-WJEANS-001',    'category' => 'womens-fashion',   'brand' => 'cats-eye',   'cost' => 600,  'sell' => 1100,  'discount_type' => 'percentage', 'discount_value' => 15],
            ['name' => 'Cherry Fabric Western Tops',       'sku' => 'EC-CFTOPS-001',    'category' => 'western-wear',     'brand' => 'yellow',     'cost' => 400,  'sell' => 750,   'discount_type' => 'none',       'discount_value' => 0],
            ['name' => 'Denim Shirt For Men',              'sku' => 'EC-DSHIRT-001',    'category' => 'denim-collection', 'brand' => 'richman',    'cost' => 500,  'sell' => 950,   'discount_type' => 'percentage', 'discount_value' => 20],
            ['name' => 'Denim Jeans Pants For Men',        'sku' => 'EC-DJEANS-001',    'category' => 'denim-collection', 'brand' => 'cats-eye',   'cost' => 700,  'sell' => 1350,  'discount_type' => 'none',       'discount_value' => 0],
            ['name' => 'Comfortable Sports Sneakers',      'sku' => 'EC-SNEAK-001',     'category' => 'footwear',         'brand' => 'ecstasy',    'cost' => 1000, 'sell' => 1800,  'discount_type' => 'none',       'discount_value' => 0],
            ['name' => "Men's Casual Winter Jacket",       'sku' => 'EC-WJACKET-001',   'category' => 'mens-fashion',     'brand' => 'gentle-park','cost' => 1300, 'sell' => 2400,  'discount_type' => 'percentage', 'discount_value' => 12],
            ['name' => "Men's T-Shirt Combo Set",          'sku' => 'EC-TCOMBO-001',    'category' => 'mens-fashion',     'brand' => 'easy',       'cost' => 500,  'sell' => 890,   'discount_type' => 'fixed',      'discount_value' => 100],
            ['name' => "Women's T-Shirt Combo",            'sku' => 'EC-WTCOMBO-001',   'category' => 'womens-fashion',   'brand' => 'yellow',     'cost' => 450,  'sell' => 850,   'discount_type' => 'percentage', 'discount_value' => 18],
            ['name' => 'Tops Pant Beautiful Dress',        'sku' => 'EC-TPDRESS-001',   'category' => 'western-wear',     'brand' => null,         'cost' => 600,  'sell' => 1100,  'discount_type' => 'percentage', 'discount_value' => 22],
            ['name' => 'Gold Necklace Set',                'sku' => 'EC-NECKLACE-001',  'category' => 'fashion-jewellery','brand' => null,         'cost' => 350,  'sell' => 690,   'discount_type' => 'none',       'discount_value' => 0],
            ['name' => 'Skincare Face Wash Combo',         'sku' => 'EC-SKINCARE-001',  'category' => 'beauty-cosmetics', 'brand' => null,         'cost' => 250,  'sell' => 480,   'discount_type' => 'percentage', 'discount_value' => 10],
            ['name' => "Kid's Dresses for Summer",         'sku' => 'EC-KIDSUMMER-001', 'category' => 'kids-fashion',     'brand' => null,         'cost' => 400,  'sell' => 750,   'discount_type' => 'none',       'discount_value' => 0],
            ['name' => 'Women Denim Jacket',               'sku' => 'EC-WDJACKET-001', 'category' => 'denim-collection', 'brand' => 'cats-eye',   'cost' => 900,  'sell' => 1650,  'discount_type' => 'percentage', 'discount_value' => 15],
        ];

        foreach ($products as $data) {
            $catSlug = $data['category'];
            $brandSlug = $data['brand'];
            $categoryId = $categories[$catSlug] ?? $categories->first();
            $brandId = $brandSlug ? ($brands[$brandSlug] ?? null) : null;

            Product::firstOrCreate(['sku' => $data['sku']], [
                'name'          => $data['name'],
                'slug'          => Str::slug($data['name']),
                'sku'           => $data['sku'],
                'category_id'   => $categoryId,
                'brand_id'      => $brandId,
                'unit_id'       => $unit->id,
                'product_type'  => 'simple',
                'cost_price'    => $data['cost'],
                'sell_price'    => $data['sell'],
                'vat_rate'      => 15,
                'discount_type' => $data['discount_type'],
                'discount_value'=> $data['discount_value'],
                'status'        => 'active',
                'show_in_pos'   => true,
                'ecom_sync'     => true,
                'ecom_visible'  => true,
                'track_stock'   => true,
                'description'   => 'High quality ' . strtolower($data['name']) . ' available at the best price.',
            ]);
        }

        // Set existing products as ecom_visible too
        Product::where('ecom_visible', false)->orWhereNull('ecom_visible')
            ->update(['ecom_visible' => true, 'ecom_sync' => true]);
    }

    private function seedBanners(): void
    {
        $banners = [
            ['title' => 'New Arrivals of ' . date('Y'), 'subtitle' => 'Trending Collection', 'button_text' => 'Shop Now', 'button_url' => '/shop', 'image' => 'website/assets/images/slider_1.jpg', 'position' => 'hero', 'sort_order' => 1],
            ['title' => 'Make Your Fashion Look More Changing', 'subtitle' => 'Trending of this month', 'button_text' => 'Shop Now', 'button_url' => '/shop', 'image' => 'website/assets/images/slider_2.jpg', 'position' => 'hero', 'sort_order' => 2],
            ['title' => 'Discover Your Best Fitting Clothes', 'subtitle' => 'Best Selling of ' . date('Y'), 'button_text' => 'Shop Now', 'button_url' => '/shop', 'image' => 'website/assets/images/slider_3.jpg', 'position' => 'hero', 'sort_order' => 3],
            ['title' => 'Make Your Fashion Story Unique Every Day', 'subtitle' => 'Summer Offer', 'button_text' => 'Shop Now', 'button_url' => '/shop', 'image' => 'website/assets/images/banner_3_add_bg_1.jpg', 'position' => 'promo_large', 'sort_order' => 1],
        ];

        foreach ($banners as $banner) {
            Banner::firstOrCreate(
                ['title' => $banner['title'], 'position' => $banner['position']],
                array_merge($banner, ['is_active' => true])
            );
        }
    }

    private function seedFlashDeal(): void
    {
        $deal = FlashDeal::firstOrCreate(['slug' => 'weekly-flash-sale'], [
            'title'     => 'Weekly Flash Sale',
            'slug'      => 'weekly-flash-sale',
            'starts_at' => now(),
            'ends_at'   => now()->addDays(7),
            'is_active' => true,
        ]);

        $discountProducts = Product::where('ecom_visible', true)
            ->where('discount_value', '>', 0)
            ->limit(10)
            ->pluck('id');

        $syncData = [];
        foreach ($discountProducts as $i => $productId) {
            $syncData[$productId] = [
                'sort_order'     => $i + 1,
                'discount_type'  => 'percentage',
                'discount_value' => rand(10, 30),
            ];
        }
        $deal->products()->syncWithoutDetaching($syncData);
    }

    private function seedBlogPosts(): void
    {
        $posts = [
            [
                'title'     => 'How to Plop Hair for Bouncy, Beautiful Curls',
                'slug'      => 'how-to-plop-hair-for-bouncy-beautiful-curls',
                'excerpt'   => 'Learn the best technique for plopping your hair to achieve bouncy and beautiful curls naturally.',
                'content'   => '<p>Plopping is a technique that uses a cotton t-shirt or microfiber towel to dry curly hair. Here is how you do it step by step...</p>',
                'category'  => 'Beauty',
                'published_at' => now()->subDays(10),
            ],
            [
                'title'     => 'Fast Fashion: How Clothes Are Linked to Climate Change',
                'slug'      => 'fast-fashion-climate-change',
                'excerpt'   => 'The fashion industry contributes significantly to carbon emissions. Here is what you can do.',
                'content'   => '<p>Fast fashion has revolutionized the clothing industry but at a cost to the environment. Learn about sustainable alternatives...</p>',
                'category'  => 'Fashion',
                'published_at' => now()->subDays(5),
            ],
            [
                'title'     => 'Which Foundation Formula Is Right for Your Skin?',
                'slug'      => 'which-foundation-formula-right-for-your-skin',
                'excerpt'   => 'Choosing the right foundation can make or break your makeup look. Here is our guide.',
                'content'   => '<p>From matte to dewy, liquid to powder, choosing the right foundation depends on your skin type and coverage preference...</p>',
                'category'  => 'Beauty',
                'published_at' => now()->subDays(3),
            ],
            [
                'title'     => 'How To Choose The Right Sofa for Your Home',
                'slug'      => 'how-to-choose-right-sofa-for-home',
                'excerpt'   => 'A sofa is the centerpiece of your living room. Here is how to pick the perfect one.',
                'content'   => '<p>Consider size, material, comfort, and style when choosing a sofa. Our guide walks you through each factor...</p>',
                'category'  => 'Lifestyle',
                'published_at' => now()->subDays(1),
            ],
        ];

        $adminId = \App\Models\User::first()?->id ?? 1;

        foreach ($posts as $post) {
            BlogPost::firstOrCreate(['slug' => $post['slug']], array_merge($post, [
                'author_id'    => $adminId,
                'is_published' => true,
            ]));
        }
    }

    private function seedHomepageSections(): void
    {
        $sections = [
            ['section_type' => 'hero_slider',   'title' => 'Hero Slider',          'sort_order' => 1],
            ['section_type' => 'features',      'title' => 'Features',             'sort_order' => 2],
            ['section_type' => 'flash_deals',   'title' => 'Flash Deals',          'sort_order' => 3],
            ['section_type' => 'categories',    'title' => 'Categories',           'sort_order' => 4],
            ['section_type' => 'special_brand', 'title' => 'Special Products','sort_order' => 5],
            ['section_type' => 'trending',      'title' => 'Trending Products',    'sort_order' => 6],
            ['section_type' => 'best_selling',  'title' => 'Best Selling',         'sort_order' => 7],
            ['section_type' => 'new_arrivals',  'title' => 'New Arrivals',         'sort_order' => 8],
            ['section_type' => 'favourite',     'title' => 'Favourite Products',   'sort_order' => 9],
            ['section_type' => 'brands',        'title' => 'Our Brands',           'sort_order' => 10],
            ['section_type' => 'blog',          'title' => 'Blog',                 'sort_order' => 11],
            ['section_type' => 'newsletter',    'title' => 'Newsletter',           'sort_order' => 12],
        ];

        foreach ($sections as $section) {
            HomepageSection::firstOrCreate(
                ['section_type' => $section['section_type']],
                array_merge($section, [
                    'is_active' => true,
                    'settings'  => ['items_count' => 12],
                ])
            );
        }
    }

    private function seedReviews(): void
    {
        $products = Product::where('ecom_visible', true)->get();
        $names = ['Rahim Uddin', 'Fatima Akter', 'Kamal Hossain', 'Nasrin Jahan', 'Arif Rahman', 'Sadia Islam', 'Tanvir Ahmed', 'Mitu Begum', 'Shakib Hasan', 'Ruma Khatun'];

        foreach ($products as $product) {
            $reviewCount = rand(2, 6);
            for ($i = 0; $i < $reviewCount; $i++) {
                ProductReview::create([
                    'product_id'    => $product->id,
                    'reviewer_name' => $names[array_rand($names)],
                    'reviewer_email'=> 'reviewer' . rand(1, 100) . '@example.com',
                    'rating'        => rand(3, 5),
                    'comment'       => $this->getRandomComment(),
                    'is_approved'   => true,
                    'created_at'    => now()->subDays(rand(1, 60)),
                ]);
            }
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            'store_name'      => 'BizPOS Store',
            'store_tagline'   => 'Your Fashion Destination',
            'contact_phone'   => '+880 1700-000000',
            'contact_email'   => 'info@bizpos.store',
            'store_address'   => 'Dhaka, Bangladesh',
        ];

        foreach ($settings as $key => $value) {
            EcommerceSetting::set($key, $value);
        }
    }

    private function getRandomComment(): string
    {
        $comments = [
            'Great product! Exactly as described.',
            'Very happy with the quality. Will buy again.',
            'Good value for money. Recommended!',
            'Fast delivery and excellent packaging.',
            'Nice product, fits perfectly.',
            'Color is slightly different from the picture but overall good.',
            'Amazing quality at this price point!',
            'My family loved it. Thank you!',
            'Decent product for the price.',
            'Very comfortable and stylish.',
            'Bought this as a gift and it was well received.',
            'Product quality exceeded my expectations.',
        ];

        return $comments[array_rand($comments)];
    }
}
