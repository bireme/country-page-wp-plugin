<?php
namespace CP\API;

if (!defined('ABSPATH')) exit;

final class Normalizer {
    public static function country(array $raw): array {
        // Campos padrão de post WP
        $id      = $raw['id'] ?? '';
        $slug    = $raw['slug'] ?? '';
        $title   = $raw['title']['rendered'] ?? '';
        $link    = $raw['link'] ?? '';
        $content = $raw['content']['rendered'] ?? '';
        $excerpt = $raw['excerpt']['rendered'] ?? '';

        $meta = $raw['meta'] ?? [];
        $acf  = $raw['acf'] ?? [];

        // mapeamento salvo no admin
        $mapping = get_option('cp_acf_mapping', []); 
        // Exemplo no banco: [ 'region' => 'string', 'flag' => 'image' ]

        $acfFields = [];
        foreach ($mapping as $key => $type) {
            if (!array_key_exists($key, $acf)) {
                continue; // ignora se o campo não veio do WP
            }
            $value = $acf[$key];
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
            'acf'     => $acfFields,
            'link'    => $link,
            'meta'    => [
                'raw_meta' => $meta,
                'raw_acf'  => $acf,
            ],
        ];
    }

    public static function countryList(array $items): array {
        return array_values(array_map([self::class, 'country'], $items));
    }
}
