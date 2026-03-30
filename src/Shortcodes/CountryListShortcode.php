<?php
namespace CP\Shortcodes;

use CP\API\Client;
use CP\API\Normalizer;
use CP\Support\Helpers;
use CP\Support\Template as TemplateSupport;

if (!defined('ABSPATH')) exit;

/** Shortcode [country_list]. Query vars: cp_cl_page, cp_cl_search, cp_cl_tag, cp_cl_cat. */
final class CountryListShortcode {
    public const PAGINATION_QUERY_VAR = 'cp_cl_page';
    public const FILTER_SEARCH = 'cp_cl_search';
    public const FILTER_TAG = 'cp_cl_tag';
    public const FILTER_CATEGORY = 'cp_cl_cat';

    public function register(): void {
        add_shortcode('country_list', [$this, 'handle']);
        add_filter('query_vars', [$this, 'registerQueryVars']);
        add_filter('redirect_canonical', [$this, 'preserveListQueryOnCanonical'], 10, 2);
    }

    /** @param array<int, string> $vars */
    public function registerQueryVars(array $vars): array {
        $vars[] = self::PAGINATION_QUERY_VAR;
        $vars[] = self::FILTER_SEARCH;
        $vars[] = self::FILTER_TAG;
        $vars[] = self::FILTER_CATEGORY;
        return $vars;
    }

    /** @param string|false $redirect_url */
    public function preserveListQueryOnCanonical($redirect_url, $requested_url = '') {
        $keys = [
            self::PAGINATION_QUERY_VAR,
            self::FILTER_SEARCH,
            self::FILTER_TAG,
            self::FILTER_CATEGORY,
        ];
        foreach ($keys as $key) {
            if (array_key_exists($key, $_GET)) {
                return false;
            }
        }
        return $redirect_url;
    }

    private static function filterFormActionUrl(): string {
        global $post;
        if ($post instanceof \WP_Post && (int) $post->ID > 0) {
            $u = get_permalink($post);
            if (is_string($u) && $u !== '') {
                return $u;
            }
        }
        $obj = get_queried_object();
        if ($obj instanceof \WP_Post) {
            $u = get_permalink($obj);
            if (is_string($u) && $u !== '') {
                return $u;
            }
        }
        global $wp;
        if ($wp instanceof \WP && is_string($wp->request) && $wp->request !== '') {
            return home_url(user_trailingslashit($wp->request));
        }
        return home_url('/');
    }

    public function handle($atts = [], $content = null, $tag = ''): string {
        $atts = shortcode_atts([
            'per_page' => 12,
            'page'     => 1,
            'search'   => '',
            'region'   => '',
            'tag'      => '',
            'category' => '',
        ], $atts, $tag);

        $perPage = max(1, (int) $atts['per_page']);
        $fromUrl = isset($_GET[self::PAGINATION_QUERY_VAR])
            ? absint(wp_unslash($_GET[self::PAGINATION_QUERY_VAR]))
            : 0;
        $requestedPage = $fromUrl > 0 ? $fromUrl : max(1, (int) $atts['page']);

        $search = sanitize_text_field((string) $atts['search']);
        if (isset($_GET[self::FILTER_SEARCH])) {
            $search = sanitize_text_field(wp_unslash((string) $_GET[self::FILTER_SEARCH]));
        }

        $tagId = $atts['tag'] !== '' ? absint($atts['tag']) : 0;
        if (isset($_GET[self::FILTER_TAG])) {
            $tagId = absint(wp_unslash($_GET[self::FILTER_TAG]));
        }

        $catId = $atts['category'] !== '' ? absint($atts['category']) : 0;
        if (isset($_GET[self::FILTER_CATEGORY])) {
            $catId = absint(wp_unslash($_GET[self::FILTER_CATEGORY]));
        }

        $args = [
            'per_page' => $perPage,
            'page'     => max(1, $requestedPage),
        ];
        if ($search !== '') {
            $args['search'] = $search;
        }
        if (!empty($atts['region'])) {
            $args['region'] = sanitize_text_field($atts['region']);
        }
        if ($tagId > 0) {
            $args['tags'] = $tagId;
        }
        if ($catId > 0) {
            $args['categories'] = $catId;
        }

        $args = apply_filters('cp_country_list_rest_query', $args, [
            'atts' => $atts,
            'search' => $search,
            'tag_id' => $tagId,
            'category_id' => $catId,
        ]);

        $client = new Client();
        $tagOptions = apply_filters('cp_country_list_filter_tags', $client->getTags([
            'orderby' => 'name',
            'order'   => 'asc',
        ]));
        $catOptions = apply_filters('cp_country_list_filter_categories', $client->getCategories([
            'orderby' => 'name',
            'order'   => 'asc',
        ]));

        $result = $client->listCountries($args);
        $totalPages = max(1, (int) ($result['total_pages'] ?? 1));
        if ($args['page'] > $totalPages) {
            $args['page'] = $totalPages;
            $result = $client->listCountries($args);
            $totalPages = max(1, (int) ($result['total_pages'] ?? 1));
        }

        $currentPage = (int) $args['page'];
        $rawList = $result['items'];

        $countries = Normalizer::countryList($rawList);

        $countries = apply_filters('cp_country_list_data', $countries, $args);

        $pagination = [
            'current_page'  => $currentPage,
            'total_pages'   => $totalPages,
            'per_page'      => $perPage,
            'total_items'   => (int) ($result['total'] ?? 0),
            'query_var'     => self::PAGINATION_QUERY_VAR,
        ];

        $clearFiltersUrl = remove_query_arg([
            self::PAGINATION_QUERY_VAR,
            self::FILTER_SEARCH,
            self::FILTER_TAG,
            self::FILTER_CATEGORY,
        ]);

        $templateVars = [
            'countries'           => $countries,
            'list_pagination'     => $pagination,
            'list_filters'        => [
                'search'           => $search,
                'tag_id'           => $tagId,
                'category_id'      => $catId,
                'tags'             => $tagOptions,
                'categories'       => $catOptions,
                'clear_url'        => $clearFiltersUrl,
                'form_action'      => self::filterFormActionUrl(),
                'key_search'       => self::FILTER_SEARCH,
                'key_tag'          => self::FILTER_TAG,
                'key_category'     => self::FILTER_CATEGORY,
            ],
        ];

        $mode = get_option('cp_template_mode_list', 'default');
        if ($mode === 'custom') {
            $html = TemplateSupport::load_custom('list', $templateVars);
            if ($html !== null && $html !== '') {
                return (string) apply_filters('cp_country_list_html', $html, $templateVars);
            }
        }
        $html = \CP\Support\Helpers::renderTemplate('country-list.php', $templateVars);
        return (string) apply_filters('cp_country_list_html', $html, $templateVars);
    }
}
