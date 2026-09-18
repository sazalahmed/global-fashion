<?php

namespace Modules\LandingPage\Services;

use App\Helpers\Upload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\LandingPage\Models\LandingPage;
use Modules\Setting\Models\Setting;

class LandingPageService
{
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return LandingPage::with('creator')
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): LandingPage
    {
        return LandingPage::findOrFail($id);
    }

    public function getActive(): ?LandingPage
    {
        $mode = Setting::get('landing_page', 'mode', 'full_site');
        if ($mode !== 'landing_page') {
            return null;
        }

        $id = Setting::get('landing_page', 'active_landing_page_id');
        if (!$id) {
            return null;
        }

        return LandingPage::find($id);
    }

    public function create(array $data): LandingPage
    {
        $data['created_by'] = auth()->id();

        if (isset($data['hero_image']) && $data['hero_image']) {
            $data['hero_image'] = \App\Helpers\Upload::store($data['hero_image'], 'landing-pages');
        }

        $data['sections'] = $this->buildSections($data);
        $data['product_ids'] = $data['product_ids'] ?? [];

        if (!empty($data['custom_css'])) {
            $data['custom_css'] = $this->sanitizeCss($data['custom_css']);
        }

        return LandingPage::create($data);
    }

    public function update(LandingPage $page, array $data): LandingPage
    {
        if (isset($data['hero_image']) && $data['hero_image']) {
            if ($page->hero_image) {
                \App\Helpers\Upload::delete($page->hero_image);
            }
            $data['hero_image'] = \App\Helpers\Upload::store($data['hero_image'], 'landing-pages');
        } else {
            unset($data['hero_image']);
        }

        $data['sections'] = $this->buildSections($data);
        $data['product_ids'] = $data['product_ids'] ?? [];

        if (!empty($data['custom_css'])) {
            $data['custom_css'] = $this->sanitizeCss($data['custom_css']);
        }

        $page->update($data);

        return $page->fresh();
    }

    public function delete(LandingPage $page): void
    {
        if ($page->hero_image) {
            \App\Helpers\Upload::delete($page->hero_image);
        }

        // If this was the active page, deactivate landing mode
        $activeId = Setting::get('landing_page', 'active_landing_page_id');
        if ((int) $activeId === $page->id) {
            Setting::set('landing_page', 'mode', 'full_site');
            Setting::set('landing_page', 'active_landing_page_id', null, 'integer');
        }

        $page->delete();
    }

    public function activate(LandingPage $page): void
    {
        // Deactivate all others
        LandingPage::where('is_active', true)->update(['is_active' => false]);

        $page->update(['is_active' => true]);

        Setting::set('landing_page', 'mode', 'landing_page');
        Setting::set('landing_page', 'active_landing_page_id', $page->id, 'integer');
    }

    public function deactivate(): void
    {
        LandingPage::where('is_active', true)->update(['is_active' => false]);

        Setting::set('landing_page', 'mode', 'full_site');
        Setting::set('landing_page', 'active_landing_page_id', null, 'integer');
    }

    public function isLandingMode(): bool
    {
        return Setting::get('landing_page', 'mode', 'full_site') === 'landing_page';
    }

    private function sanitizeCss(string $css): string
    {
        // Strip dangerous CSS patterns
        $css = preg_replace('/expression\s*\(/i', '', $css);
        $css = preg_replace('/javascript\s*:/i', '', $css);
        $css = preg_replace('/behavior\s*:/i', '', $css);
        $css = preg_replace('/@import/i', '', $css);
        $css = preg_replace('/url\s*\(\s*["\']?\s*data:/i', 'url(blocked:', $css);
        $css = preg_replace('/<\/?script/i', '', $css);
        $css = preg_replace('/<\/?style/i', '', $css);

        return trim($css);
    }

    private function buildSections(array $data): array
    {
        $sections = [];

        if (!empty($data['faqs'])) {
            $sections['faqs'] = array_values(array_filter($data['faqs'], fn ($f) => !empty($f['question'])));
        }

        if (!empty($data['benefits'])) {
            $sections['benefits'] = array_values(array_filter($data['benefits'], fn ($b) => !empty($b['text'])));
        }

        if (!empty($data['sizes'])) {
            $sections['sizes'] = array_values(array_filter($data['sizes'], fn ($s) => !empty($s['size'])));
        }

        if (!empty($data['details'])) {
            $sections['details'] = array_values(array_filter($data['details'], fn ($d) => !empty($d['title'])));
        }

        if (!empty($data['ingredients'])) {
            $sections['ingredients'] = array_values(array_filter($data['ingredients'], fn ($i) => !empty($i['text'])));
        }

        return $sections;
    }
}
