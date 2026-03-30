<?php
namespace CP\API;

if (!defined('ABSPATH')) exit;

final class Normalizer {
    public static function country(array $raw): array {
        $id      = $raw['id'] ?? '';
        $slug    = $raw['slug'] ?? '';
        $titleRaw = $raw['title']['rendered'] ?? ($raw['title'] ?? '');
        $title   = is_string($titleRaw) ? $titleRaw : '';
        $link    = $raw['link'] ?? '';

        $rawContent = $raw['content'] ?? '';
        if (is_array($rawContent)) {
            $content = (string) ($rawContent['rendered'] ?? $rawContent['raw'] ?? '');
        } else {
            $content = is_string($rawContent) ? $rawContent : '';
        }

        $rawExcerpt = $raw['excerpt'] ?? '';
        if (is_array($rawExcerpt)) {
            $excerpt = (string) ($rawExcerpt['rendered'] ?? $rawExcerpt['raw'] ?? '');
        } else {
            $excerpt = is_string($rawExcerpt) ? $rawExcerpt : '';
        }

        $meta = $raw['meta'] ?? [];
        $featuredImage = self::extractFeaturedImage($raw);

        /** @var array<string, string> $mapping */
        $mapping = get_option('cp_acf_mapping', []);
        if (!is_array($mapping)) {
            $mapping = [];
        }

        $acfLookup = self::buildAcfLookupFromRaw($raw, $mapping);

        $acfFields = [];
        foreach ($mapping as $key => $type) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            $type = is_string($type) ? $type : 'string';
            $normalizedKey = strtolower($key);
            if (!array_key_exists($normalizedKey, $acfLookup)) {
                continue;
            }
            $value = $acfLookup[$normalizedKey];
            $acfFields[] = [
                'key'   => $key,
                'value' => $value,
                'type'  => $type,
            ];
        }

        return [
            'id'      => $id,
            'slug'    => $slug,
            'title'   => wp_strip_all_tags($title),
            'excerpt' => $excerpt,
            'content' => $content,
            'featured_image' => $featuredImage,
            'acf'     => $acfFields,
            'link'    => $link,
            'meta'    => [
                'raw_meta' => $meta,
                'raw_acf'  => is_array($raw['acf'] ?? null) ? $raw['acf'] : [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @param array<string, string> $mapping
     * @return array<string, mixed>
     */
    private static function buildAcfLookupFromRaw(array $raw, array $mapping): array {
        $lookup = [];

        $acf = $raw['acf'] ?? null;
        if (is_array($acf)) {
            if (self::isNumericSequentialArray($acf)) {
                foreach ($acf as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $fieldKey = $item['key'] ?? $item['name'] ?? $item['field'] ?? '';
                    if ($fieldKey === '') {
                        continue;
                    }
                    $nk = strtolower((string) $fieldKey);
                    if (!array_key_exists($nk, $lookup)) {
                        $lookup[$nk] = $item['value'] ?? $item['acf_value'] ?? $item['rendered'] ?? null;
                    }
                }
            } else {
                foreach ($acf as $acfKey => $acfValue) {
                    $lookup[strtolower((string) $acfKey)] = $acfValue;
                }
            }
        }

        $meta = $raw['meta'] ?? [];
        if (is_array($meta)) {
            foreach ($meta as $metaKey => $metaValue) {
                $sk = strtolower((string) $metaKey);
                if (!array_key_exists($sk, $lookup)) {
                    $lookup[$sk] = $metaValue;
                }
                if (strncmp($sk, 'acf_', 4) === 0) {
                    $bare = substr($sk, 4);
                    if ($bare !== '' && !array_key_exists($bare, $lookup)) {
                        $lookup[$bare] = $metaValue;
                    }
                }
            }
        }

        $reserved = [
            'id', 'slug', 'date', 'date_gmt', 'modified', 'modified_gmt',
            'title', 'content', 'excerpt', 'link', 'meta', 'acf', '_embedded',
            'author', 'featured_media', 'template', 'categories', 'tags',
        ];
        foreach ($mapping as $mapKey => $_t) {
            if (!is_string($mapKey) || $mapKey === '') {
                continue;
            }
            $nk = strtolower($mapKey);
            if (array_key_exists($nk, $lookup)) {
                continue;
            }
            if (!array_key_exists($mapKey, $raw)) {
                continue;
            }
            if (in_array(strtolower($mapKey), $reserved, true)) {
                continue;
            }
            $lookup[$nk] = $raw[$mapKey];
        }

        return $lookup;
    }

    /** @param array<mixed> $arr */
    private static function isNumericSequentialArray(array $arr): bool {
        if ($arr === []) {
            return true;
        }
        $i = 0;
        foreach (array_keys($arr) as $k) {
            if ($k !== $i) {
                return false;
            }
            $i++;
        }
        return true;
    }

    public static function countryList(array $items): array {
        return array_values(array_map([self::class, 'country'], $items));
    }

    private static function extractFeaturedImage(array $raw): string {
        if (!empty($raw['better_featured_image']['source_url'])) {
            return (string) $raw['better_featured_image']['source_url'];
        }
        if (!empty($raw['featured_image_url'])) {
            return (string) $raw['featured_image_url'];
        }
        $embedded = $raw['_embedded']['wp:featuredmedia'][0] ?? null;
        if (is_array($embedded)) {
            if (!empty($embedded['source_url'])) {
                return (string) $embedded['source_url'];
            }
            $sizes = $embedded['media_details']['sizes'] ?? [];
            foreach (['full', 'large', 'medium_large', 'medium'] as $size) {
                if (!empty($sizes[$size]['source_url'])) {
                    return (string) $sizes[$size]['source_url'];
                }
            }
        }
        if (!empty($raw['jetpack_featured_media_url'])) {
            return (string) $raw['jetpack_featured_media_url'];
        }
        return '';
    }
}
