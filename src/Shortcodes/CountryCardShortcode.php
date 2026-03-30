<?php
namespace CP\Shortcodes;

use CP\API\Client;
use CP\API\Normalizer;
use CP\Support\Helpers;
use CP\Support\Template as TemplateSupport;

if (!defined('ABSPATH')) exit;

/** Shortcode [country]. TODO: Polylang. */
final class CountryCardShortcode {
    public function register(): void {
        add_shortcode('country', [$this, 'handle']);
    }

    public function handle($atts = [], $content = null, $tag = ''): string {
        $atts = is_array($atts) ? $atts : [];
        $atts = $this->mergeDisplayOnlyAliases($atts);

        $atts = shortcode_atts([
            'slug' => '',
            'title-only' => 'false',
            'content-only' => 'false',
            'image-only' => 'false',
        ], $atts, $tag);

        $slug = sanitize_title($atts['slug']);
        if (!$slug) {
            return $this->renderShortcodeError(__('Shortcode [country]: atributo slug é obrigatório.', 'country-pages'));
        }

        $titleOnly = $this->parseBoolAttr($atts['title-only'] ?? 'false');
        $contentOnly = $this->parseBoolAttr($atts['content-only'] ?? 'false');
        $imageOnly = $this->parseBoolAttr($atts['image-only'] ?? 'false');
        $restrict = $titleOnly || $contentOnly || $imageOnly;

        $displayVars = [
            'cp_show_title' => !$restrict || $titleOnly,
            'cp_show_image' => !$restrict || $imageOnly,
            'cp_show_content' => !$restrict || $contentOnly,
            'cp_show_excerpt' => !$restrict,
            'cp_show_meta' => !$restrict,
        ];

        $result = $this->fetchCountryCardHtml($slug, $displayVars, $restrict);
        if (!$result['success']) {
            return $this->renderShortcodeError($result['message']);
        }
        return $result['html'];
    }

    /** @return array{success: true, html: string, page_title: string}|array{success: false, message: string} */
    public function renderStandalonePage(string $slug): array {
        $slug = sanitize_title($slug);
        if ($slug === '') {
            return [
                'success' => false,
                'message' => '',
            ];
        }
        $displayVars = [
            'cp_show_title' => true,
            'cp_show_image' => true,
            'cp_show_content' => true,
            'cp_show_excerpt' => true,
            'cp_show_meta' => true,
        ];
        return $this->fetchCountryCardHtml($slug, $displayVars, false);
    }

    /**
     * @return array{success: true, html: string, page_title: string}|array{success: false, message: string}
     */
    private function fetchCountryCardHtml(string $slug, array $displayVars, bool $restrict): array {
        try {
            $client = new Client();
            $raw = $client->getCountryBySlug($slug);
            if (!$raw) {
                $msg = $client->getLastError() ?: __('Não foi possível carregar os dados do país.', 'country-pages');
                return ['success' => false, 'message' => $msg];
            }

            $country = Normalizer::country($raw);
            $country = apply_filters('cp_country_data', $country);

            $acfMappingOpt = get_option('cp_acf_mapping', []);
            if (!is_array($acfMappingOpt)) {
                $acfMappingOpt = [];
            }

            $templateVars = array_merge(
                [
                    'country' => $country,
                    'cp_acf_mapping' => $acfMappingOpt,
                ],
                $displayVars
            );

            $pageTitle = (string) ($country['title'] ?? $country['name'] ?? '');

            $mode = get_option('cp_template_mode_country', 'default');
            if ($mode === 'custom' && !$restrict) {
                $html = TemplateSupport::load_custom('country', $templateVars);
                if ($html !== null && $html !== '') {
                    $html = (string) apply_filters('cp_country_card_html', $html);
                    return ['success' => true, 'html' => $html, 'page_title' => $pageTitle];
                }
            }
            $html = Helpers::renderTemplate(
                'country-card.php',
                $templateVars,
                !$restrict
            );
            $html = (string) apply_filters('cp_country_card_html', $html);
            return ['success' => true, 'html' => $html, 'page_title' => $pageTitle];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => __('Erro ao exibir país: ', 'country-pages') . $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $atts
     * @return array<string, mixed>
     */
    private function mergeDisplayOnlyAliases(array $atts): array {
        $pairs = [
            'title-only' => 'title_only',
            'content-only' => 'content_only',
            'image-only' => 'image_only',
        ];
        foreach ($pairs as $canonical => $underscore) {
            if (!array_key_exists($canonical, $atts) && array_key_exists($underscore, $atts)) {
                $atts[$canonical] = $atts[$underscore];
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
