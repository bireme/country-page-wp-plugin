<?php
namespace CP\Shortcodes;

use CP\API\Client;
use CP\API\Normalizer;
use CP\Support\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/** Shortcode [country_slider]. */
final class CountrySliderShortcode {
    public function register(): void {
        add_shortcode('country_slider', [$this, 'handle']);
    }

    /** @param array<string, mixed> $atts */
    public function handle($atts = [], $content = null, $tag = ''): string {
        $atts = is_array($atts) ? $atts : [];
        $atts = $this->mergeHyphenAliases($atts);

        $atts = shortcode_atts(
            [
                'itens'            => 12,
                'itens-per-round'  => 3,
                'loop'             => 'false',
                'tag-filter'       => '',
                'category-filter'  => '',
                'region'           => '',
                'search'           => '',
            ],
            $atts,
            $tag
        );

        $perPage = max(1, min(100, (int) $atts['itens']));
        $visible = max(1, (int) $atts['itens-per-round']);
        if ($visible > $perPage) {
            $visible = $perPage;
        }

        $loop = $this->parseBoolAttr($atts['loop'] ?? 'false');

        $tagId = $atts['tag-filter'] !== '' ? absint($atts['tag-filter']) : 0;
        $catId = $atts['category-filter'] !== '' ? absint($atts['category-filter']) : 0;

        $args = [
            'per_page' => $perPage,
            'page'     => 1,
        ];
        $search = sanitize_text_field((string) $atts['search']);
        if ($search !== '') {
            $args['search'] = $search;
        }
        if (!empty($atts['region'])) {
            $args['region'] = sanitize_text_field((string) $atts['region']);
        }
        if ($tagId > 0) {
            $args['tags'] = $tagId;
        }
        if ($catId > 0) {
            $args['categories'] = $catId;
        }

        $args = apply_filters('cp_country_slider_rest_query', $args, [
            'atts'          => $atts,
            'tag_id'        => $tagId,
            'category_id'   => $catId,
        ]);

        $client = new Client();
        $result = $client->listCountries($args);
        $rawList = $result['items'];
        $countries = Normalizer::countryList($rawList);
        $countries = apply_filters('cp_country_slider_data', $countries, $args);

        $uid = 'cp-slider-' . (function_exists('wp_unique_id') ? wp_unique_id() : uniqid('', false));

        $templateVars = [
            'countries'       => $countries,
            'slider_uid'      => $uid,
            'slider_visible'  => $visible,
            'slider_loop'     => $loop,
        ];

        $html = Helpers::renderTemplate('country-slider.php', $templateVars);
        return (string) apply_filters('cp_country_slider_html', $html, $templateVars);
    }

    /**
     * @param array<string, mixed> $atts
     * @return array<string, mixed>
     */
    private function mergeHyphenAliases(array $atts): array {
        $pairs = [
            'itens-per-round'  => 'itens_per_round',
            'tag-filter'       => 'tag_filter',
            'category-filter'  => 'category_filter',
        ];
        foreach ($pairs as $hyphen => $underscore) {
            if (!array_key_exists($hyphen, $atts) && array_key_exists($underscore, $atts)) {
                $atts[$hyphen] = $atts[$underscore];
            }
        }
        return $atts;
    }

    private function parseBoolAttr($value): bool {
        if (is_bool($value)) {
            return $value;
        }
        $v = strtolower(trim((string) $value));
        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }
}
