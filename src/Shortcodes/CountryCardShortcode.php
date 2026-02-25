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
        if (!$slug) {
            return $this->renderShortcodeError(__('Shortcode [country]: atributo slug é obrigatório.', 'country-pages'));
        }

        try {
            $client = new Client();
            $raw = $client->getCountryBySlug($slug);
            if (!$raw) {
                echo var_dump($client->getLastError()); die();
                $msg = $client->getLastError() ?: __('Não foi possível carregar os dados do país.', 'country-pages');
                return $this->renderShortcodeError($msg);
            }

            $country = Normalizer::country($raw);
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
        } catch (\Throwable $e) {
            return $this->renderShortcodeError(
                __('Erro ao exibir país: ', 'country-pages') . esc_html($e->getMessage())
            );
        }
    }

    private function renderShortcodeError(string $message): string {
        if (!current_user_can('manage_options')) {
            return '';
        }
        return '<div class="cp-shortcode-error" style="padding:1em;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;color:#721c24;white-space:pre-wrap;word-break:break-all;">'
            . '<strong>' . esc_html__('Country Pages:', 'country-pages') . '</strong> '
            . nl2br(esc_html($message))
            . '</div>';
    }
}
