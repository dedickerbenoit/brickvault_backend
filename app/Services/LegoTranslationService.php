<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LegoTranslationService
{
    private const GRAPHQL_URL = 'https://www.lego.com/api/graphql/SearchSuggestions';

    private const SEARCH_QUERY = 'query SearchSuggestions($query: String!, $suggestionLimit: Int, $productLimit: Int, $visibility: ProductVisibility) {
        searchSuggestions(query: $query, suggestionLimit: $suggestionLimit, productLimit: $productLimit, visibility: $visibility) {
            __typename
            ... on Product {
                id
                productCode
                name
                __typename
            }
            ... on SingleVariantProduct {
                id
                productCode
                name
                __typename
            }
        }
    }';

    public function translateBatch(array $setNums, string $locale): array
    {
        $results = [];
        $localeSlug = $this->localeToSlug($locale);
        $rateLimitMs = config('lego.graphql.rate_limit_ms', 200);

        foreach ($setNums as $setNum) {
            $productId = self::extractProductCode($setNum);

            $name = $this->queryGraphQL($productId, $localeSlug);

            if ($name) {
                $results[$productId] = $name;
            }

            usleep($rateLimitMs * 1000);
        }

        return $results;
    }

    private function queryGraphQL(string $productCode, string $localeSlug): ?string
    {
        $response = Http::withHeaders([
            'x-locale' => $localeSlug,
            'Content-Type' => 'application/json',
        ])->retry(3, 1000, throw: false)->post(self::GRAPHQL_URL, [
            'operationName' => 'SearchSuggestions',
            'query' => self::SEARCH_QUERY,
            'variables' => [
                'query' => $productCode,
                'suggestionLimit' => 0,
                'productLimit' => 5,
                'visibility' => ['includeRetiredProducts' => true],
            ],
        ]);

        if (! $response->successful()) {
            Log::warning('LegoTranslationService: GraphQL error', [
                'status' => $response->status(),
                'productCode' => $productCode,
            ]);

            return null;
        }

        $suggestions = $response->json('data.searchSuggestions') ?? [];

        foreach ($suggestions as $suggestion) {
            if (! str_contains($suggestion['__typename'] ?? '', 'Product')) {
                continue;
            }

            if (($suggestion['productCode'] ?? '') === $productCode) {
                return $suggestion['name'] ?? null;
            }
        }

        return null;
    }

    public function scrapeSetName(string $setNum, string $locale): ?string
    {
        $baseUrl = config('lego.scraping.base_url');
        $productId = self::extractProductCode($setNum);
        $localeSlug = $this->localeToSlug($locale);

        $url = "{$baseUrl}/{$localeSlug}/product/{$productId}";

        usleep(config('lego.scraping.rate_limit_ms', 500) * 1000);

        $response = Http::withHeaders([
            'User-Agent' => 'BrickVault/1.0',
        ])->get($url);

        if (! $response->successful()) {
            return null;
        }

        $html = $response->body();

        if (preg_match('/<h1[^>]*data-test="product-overview-name"[^>]*>\s*(.+?)\s*<\/h1>/si', $html, $matches)) {
            return strip_tags(html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8'));
        }

        if (preg_match('/<h1[^>]*class="[^"]*ProductOverviewstyles__ProductOverviewName[^"]*"[^>]*>\s*(.+?)\s*<\/h1>/si', $html, $matches)) {
            return strip_tags(html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8'));
        }

        return null;
    }

    public function scrapeThemeName(string $themeSlug, string $locale = 'fr'): ?string
    {
        $baseUrl = config('lego.scraping.base_url');
        $localeSlug = $this->localeToSlug($locale);

        $url = "{$baseUrl}/{$localeSlug}/themes/{$themeSlug}";

        usleep(config('lego.scraping.rate_limit_ms', 500) * 1000);

        $response = Http::withHeaders([
            'User-Agent' => 'BrickVault/1.0',
        ])->get($url);

        if (! $response->successful()) {
            return null;
        }

        $html = $response->body();

        if (preg_match('/<h1[^>]*data-test="[^"]*theme[^"]*"[^>]*>\s*(.+?)\s*<\/h1>/si', $html, $matches)
            || preg_match('/<h1[^>]*>\s*(.+?)\s*<\/h1>/si', $html, $matches)) {
            $name = strip_tags(html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8'));

            if (mb_strlen($name) > 0 && mb_strlen($name) <= 100) {
                return $name;
            }
        }

        return null;
    }

    public function scrapeNewSetsPage(int $page, string $locale): array
    {
        $baseUrl = config('lego.scraping.base_url');
        $localeSlug = $this->localeToSlug($locale);

        $url = "{$baseUrl}/{$localeSlug}/categories/new-sets-and-products?page={$page}";

        usleep(config('lego.scraping.rate_limit_ms', 500) * 1000);

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders([
            'User-Agent' => 'BrickVault/1.0',
        ])->get($url);

        if (! $response->successful()) {
            Log::warning('LegoTranslationService: scrapeNewSetsPage HTTP error', [
                'status' => $response->status(),
                'page' => $page,
                'locale' => $locale,
            ]);

            return ['total' => 0, 'products' => []];
        }

        return $this->parseNewSetsHtml($response->body());
    }

    private function parseNewSetsHtml(string $html): array
    {
        if (! preg_match('/<script[^>]*id="__NEXT_DATA__"[^>]*>\s*({.+?})\s*<\/script>/s', $html, $matches)) {
            Log::warning('LegoTranslationService: __NEXT_DATA__ not found in HTML');

            return ['total' => 0, 'products' => []];
        }

        $json = json_decode($matches[1], true);

        if (! $json) {
            Log::warning('LegoTranslationService: Failed to decode __NEXT_DATA__ JSON');

            return ['total' => 0, 'products' => []];
        }

        $apolloState = $json['props']['apolloState'] ?? [];
        $total = 0;
        $products = [];

        foreach ($apolloState as $key => $value) {
            if (str_starts_with($key, 'ProductQueryResult:')) {
                $total = $value['total'] ?? 0;
            }

            if (! str_starts_with($key, 'Product:') && ! str_starts_with($key, 'SingleVariantProduct:')) {
                continue;
            }

            $code = $value['productCode'] ?? null;
            $name = $value['name'] ?? null;

            if (! $code || ! $name) {
                continue;
            }

            $imgUrl = null;
            $imageRef = $value['primaryImage']['__ref'] ?? null;
            if ($imageRef && isset($apolloState[$imageRef]['url'])) {
                $imgUrl = $apolloState[$imageRef]['url'];
            }

            $products[$code] = [
                'code' => $code,
                'name' => $name,
                'img_url' => $imgUrl,
            ];
        }

        return ['total' => $total, 'products' => array_values($products)];
    }

    public static function extractProductCode(string $setNum): string
    {
        return preg_replace('/-\d+$/', '', $setNum);
    }

    private function localeToSlug(string $locale): string
    {
        return match ($locale) {
            'fr' => 'fr-FR',
            'en' => 'en-US',
            'de' => 'de-DE',
            'es' => 'es-ES',
            default => "{$locale}-" . strtoupper($locale),
        };
    }
}
