<?php
namespace CP\Shortcodes;

use CP\API\Client;
use CP\API\Normalizer;
use CP\Support\Helpers;
use CP\Support\Template as TemplateSupport;

if (!defined('ABSPATH')) exit;

/**
 * TODO: Adicionar um parametro para o Pollylang
 * [country slug="brazil"]
 */
final class CountryCardShortcode {
    public function register(): void {
        add_shortcode('country', [$this, 'handle']);
    }

    public function handle($atts = [], $content = null, $tag = ''): string {
        $atts = shortcode_atts([
            'slug' => '',
        ], $atts, $tag);

        $slug = sanitize_title($atts['slug']);
        if (!$slug) return '';

        $client = new Client();
        $raw = $client->getCountryBySlug($slug);
        if (!$raw) return '';

        $country = Normalizer::country($raw);

        /**
         * Permite filtrar os dados antes do template.
         * @param array $country
         */
        $country = apply_filters('cp_country_data', $country);

        //Verificando se tem um template custom:
        $mode = get_option('cp_template_mode_country', 'default');
        if ($mode === 'custom') {
            $html = TemplateSupport::load_custom('country', ['country' => $country]);
            if ($html !== null && $html !== '') {
                return (string) apply_filters('cp_country_card_html', $html);
            }
        }
        // fallback default
        $html = \CP\Support\Helpers::renderTemplate('country-card.php', ['country' => $country]);
        return (string) apply_filters('cp_country_card_html', $html);
    }
}
