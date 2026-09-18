<?php

namespace Modules\Ecommerce\Support;

use Illuminate\Support\Str;
use Modules\Ecommerce\Models\EcommerceSetting;

class Seo
{
    public ?string $title = null;
    public ?string $description = null;
    public ?string $canonical = null;
    public string $robots = 'index,follow';
    public ?string $image = null;
    public string $type = 'website';
    public array $schema = [];
    public array $breadcrumbs = [];

    public static function make(): self
    {
        $s = new self();
        $s->canonical = url()->current();
        return $s;
    }

    public function title(?string $v): self { if (filled($v)) { $this->title = $v; } return $this; }
    public function description(?string $v): self { if (filled($v)) { $this->description = $v; } return $this; }
    public function canonical(?string $v): self { if (filled($v)) { $this->canonical = $v; } return $this; }
    public function robots(string $v): self { $this->robots = $v; return $this; }
    public function image(?string $v): self { if (filled($v)) { $this->image = $v; } return $this; }
    public function type(string $v): self { $this->type = $v; return $this; }
    public function addSchema(array $node): self { if ($node) { $this->schema[] = $node; } return $this; }
    public function breadcrumbs(array $items): self { $this->breadcrumbs = $items; return $this; }

    public function resolve(?string $admin, ?string $derived, string $defaultKey): ?string
    {
        foreach ([$admin, $derived, EcommerceSetting::get($defaultKey)] as $v) {
            if (filled($v)) { return $v; }
        }
        return null;
    }

    public function siteName(): string
    {
        return EcommerceSetting::get('seo_site_name') ?: config('app.name', 'BizPOS Pro');
    }

    public function renderedTitle(): string
    {
        $site  = $this->siteName();
        $sep   = EcommerceSetting::get('seo_title_separator') ?: '|';
        $title = $this->title ?: (EcommerceSetting::get('seo_default_title') ?: $site);
        $title = trim(Str::limit($title, 60, ''));
        return $title === $site ? $title : "{$title} {$sep} {$site}";
    }

    public function renderedDescription(): string
    {
        $d = $this->description ?: (EcommerceSetting::get('seo_default_description') ?: '');
        return trim(Str::limit(strip_tags($d), 160, ''));
    }

    public function renderedImage(): ?string
    {
        $img = $this->image ?: EcommerceSetting::get('seo_default_image');
        if (! $img) { return null; }
        return Str::startsWith($img, ['http://', 'https://']) ? $img : url($img);
    }

    public function canonicalUrl(): string
    {
        return $this->canonical ?: url()->current();
    }
}
