<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\EcommerceSetting;

class SeoSchemaService
{
    public function organization(): array
    {
        $name = EcommerceSetting::get('seo_org_name')
            ?: (EcommerceSetting::get('seo_site_name') ?: config('app.name', 'BizPOS Pro'));
        $logo = EcommerceSetting::get('seo_org_logo');

        $sameAs = array_values(array_filter([
            EcommerceSetting::get('facebook_url'),
            EcommerceSetting::get('instagram_url'),
            EcommerceSetting::get('youtube_url'),
            EcommerceSetting::get('twitter_url'),
            EcommerceSetting::get('linkedin_url'),
        ]));

        $node = [
            '@type' => 'Organization',
            '@id'   => url('/') . '/#organization',
            'name'  => $name,
            'url'   => url('/'),
        ];
        if ($logo) {
            $node['logo'] = str_starts_with($logo, 'http') ? $logo : url($logo);
        }
        if ($sameAs) {
            $node['sameAs'] = $sameAs;
        }

        return $node;
    }

    public function website(): array
    {
        return [
            '@type'     => 'WebSite',
            '@id'       => url('/') . '/#website',
            'url'       => url('/'),
            'name'      => EcommerceSetting::get('seo_site_name') ?: config('app.name', 'BizPOS Pro'),
            'publisher' => ['@id' => url('/') . '/#organization'],
            'potentialAction' => [
                '@type'  => 'SearchAction',
                'target' => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => url('/shop') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function breadcrumbList(array $items): array
    {
        $elements = [];
        foreach (array_values($items) as $i => $item) {
            $el = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $item['name'],
            ];
            if (! empty($item['url'])) {
                $el['item'] = $item['url'];
            }
            $elements[] = $el;
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $elements];
    }

    public function product(\Modules\Product\Models\Product $p, string $canonical): array
    {
        $price = (float) $p->displayPrice()->effective;
        $inStock = (bool) $p->is_in_stock;

        $node = [
            '@type'       => 'Product',
            '@id'         => $canonical . '#product',
            'name'        => $p->name,
            'description' => trim(\Illuminate\Support\Str::limit(strip_tags($p->seo_description ?: $p->description), 300)),
            'sku'         => $p->sku,
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => $canonical,
                'priceCurrency' => 'BDT',
                'price'         => $price,
                'availability'  => 'https://schema.org/' . ($inStock ? 'InStock' : 'OutOfStock'),
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];

        $primary = $p->images->firstWhere('is_primary', true) ?? $p->images->first();
        if ($primary) {
            $node['image'] = [url($primary->image_path)];
        }
        if ($p->brand) {
            $node['brand'] = ['@type' => 'Brand', 'name' => optional($p->brand)->name];
        }

        $count = $p->approvedReviews()->count();
        if ($count > 0) {
            $node['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => round((float) $p->approvedReviews()->avg('rating'), 1),
                'reviewCount' => $count,
            ];
        }

        return $node;
    }

    public function blogPosting(\Modules\Ecommerce\Models\BlogPost $post, string $canonical): array
    {
        $node = [
            '@type'            => 'BlogPosting',
            '@id'              => $canonical . '#article',
            'headline'         => $post->title,
            'mainEntityOfPage' => $canonical,
            'description'      => trim(\Illuminate\Support\Str::limit(strip_tags($post->seo_description ?: $post->excerpt), 200)),
            'publisher'        => ['@id' => url('/') . '/#organization'],
        ];
        if ($post->featured_image) {
            $node['image'] = url($post->featured_image);
        }
        if ($post->published_at) {
            $node['datePublished'] = $post->published_at->toIso8601String();
        }
        if ($post->updated_at) {
            $node['dateModified'] = $post->updated_at->toIso8601String();
        }
        if ($post->author) {
            $node['author'] = ['@type' => 'Person', 'name' => optional($post->author)->name];
        }
        return $node;
    }

    public function collectionPage(string $name, string $canonical, iterable $items): array
    {
        $elements = [];
        $i = 0;
        foreach ($items as $it) {
            $elements[] = array_filter([
                '@type'    => 'ListItem',
                'position' => ++$i,
                'url'      => $it['url'] ?? null,
                'name'     => $it['name'] ?? null,
                'image'    => $it['image'] ?? null,
            ]);
        }
        return [
            '@type'      => 'CollectionPage',
            '@id'        => $canonical . '#collection',
            'name'       => $name,
            'url'        => $canonical,
            'mainEntity' => ['@type' => 'ItemList', 'numberOfItems' => $i, 'itemListElement' => $elements],
        ];
    }

    public function localBusiness(\Modules\Branch\Models\Branch $b): array
    {
        $node = [
            '@type'     => 'Store',
            'name'      => $b->name,
            'telephone' => $b->phone,
            'address'   => array_filter([
                '@type'           => 'PostalAddress',
                'streetAddress'   => $b->address,
                'addressLocality' => $b->city,
                'addressRegion'   => $b->district,
                'postalCode'      => $b->zip_code ?? null,
                'addressCountry'  => 'BD',
            ]),
        ];
        if ($b->opening_time && $b->closing_time) {
            $node['openingHours'] = $b->opening_time . '-' . $b->closing_time;
        }
        return $node;
    }
}
