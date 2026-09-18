<?php

namespace App\Services\Search;

use App\Contracts\SearchableInterface;

class SearchResultBuilder
{
    /**
     * Build a standardized search result array from a model instance.
     */
    public function build(SearchableInterface $model, string $term): array
    {
        return [
            'title'    => $this->highlight($model->getSearchTitle(), $term),
            'subtitle' => $this->highlight($model->getSearchSubtitle(), $term),
            'url'      => $model->getSearchUrl(),
            'icon'     => $model::getSearchIcon(),
            'type'     => $model::getSearchType(),
        ];
    }

    /**
     * Build a navigation search result. Settings-tab items carry an `anchor`
     * (a tab pane id); the URL gets a matching `#fragment` so the page opens
     * on the right tab.
     */
    public function buildNavigation(array $item, string $term): array
    {
        $url = route($item['route'], $item['params'] ?? []);

        if (!empty($item['anchor'])) {
            $url .= '#' . $item['anchor'];
        }

        return [
            'title'    => $this->highlight($item['label'], $term),
            'subtitle' => $item['group'] ?? '',
            'url'      => $url,
            'icon'     => $item['icon'],
            'type'     => 'Page',
        ];
    }

    /**
     * Highlight matching substrings with <mark> tags.
     * Text is HTML-escaped first, then safe <mark> tags are injected.
     */
    private function highlight(string $text, string $term): string
    {
        $escaped = e($text);

        if (strlen($term) < 1) {
            return $escaped;
        }

        $escapedTerm = preg_quote(e($term), '/');

        return preg_replace(
            '/(' . $escapedTerm . ')/iu',
            '<mark>$1</mark>',
            $escaped
        );
    }
}
