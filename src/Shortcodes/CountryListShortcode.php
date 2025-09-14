<?php
namespace CP\Shortcodes;

use CP\API\Client;
use CP\API\Normalizer;
use CP\Support\Helpers;
use CP\Support\Template as TemplateSupport;

if (!defined('ABSPATH')) exit;

/**
 * [country_list per_page="12" page="1" search="" region="americas"]
 */
final class CountryListShortcode {
    public function register(): void {
        add_shortcode('country_list', [$this, 'handle']);
    }

    public function handle($atts = [], $content = null, $tag = ''): string {
        $atts = shortcode_atts([
            'per_page' => 12,
            'page'     => 1,
            'search'   => '',
          
            'region'   => '',
        ], $atts, $tag);

        $args = [
            'per_page' => max(1, (int) $atts['per_page']),
            'page'     => max(1, (int) $atts['page']),
            'search'   => sanitize_text_field($atts['search']),
        ];
        if (!empty($atts['region'])) {
            $args['region'] = sanitize_text_field($atts['region']);
        }

        $client = new Client();
        $rawList = $client->listCountries($args);
        $countries = Normalizer::countryList($rawList);

        $countries = apply_filters('cp_country_list_data', $countries, $args);

        $mode = get_option('cp_template_mode_list', 'default');
        if ($mode === 'custom') {
            $html = TemplateSupport::load_custom('list', ['countries' => $countries]);
            if ($html !== null && $html !== '') {
                return (string) apply_filters('cp_country_list_html', $html);
            }
        }
        $html = \CP\Support\Helpers::renderTemplate('country-list.php', ['countries' => $countries]);
        return (string) apply_filters('cp_country_list_html', $html);
    }
}
