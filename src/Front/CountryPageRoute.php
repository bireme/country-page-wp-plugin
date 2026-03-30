<?php
namespace CP\Front;

use CP\Shortcodes\CountryCardShortcode;

if (!defined('ABSPATH')) exit;

/** Rewrite rule /{base}/{slug}/ → página do país. */
final class CountryPageRoute {
    public const QUERY_VAR = 'cp_country_slug';
    public const OPTION_BASE = 'cp_country_url_base';

    public function register(): void {
        add_action('init', [$this, 'addRewriteRules']);
        add_filter('query_vars', [$this, 'filterQueryVars']);
        add_filter('template_include', [$this, 'templateInclude'], 99);
        add_filter('document_title_parts', [$this, 'documentTitleParts'], 20);
        add_filter('body_class', [$this, 'bodyClass']);
        add_action('update_option_' . self::OPTION_BASE, [$this, 'onBaseOptionChanged'], 10, 2);
    }

    public static function activateFlush(): void {
        (new self())->addRewriteRules();
        flush_rewrite_rules();
    }

    public function addRewriteRules(): void {
        $base = self::getBaseSlug();
        if ($base === '') {
            return;
        }
        add_rewrite_rule(
            '^' . preg_quote($base, '/') . '/([^/]+)/?$',
            'index.php?' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );
    }

    /**
     * @param mixed $old
     * @param mixed $new
     */
    public function onBaseOptionChanged($old, $new): void {
        if ((string) $old !== (string) $new) {
            flush_rewrite_rules();
        }
    }

    /**
     * @param list<string> $classes
     * @return list<string>
     */
    public function bodyClass(array $classes): array {
        if (self::getRequestedSlug() !== null) {
            $classes[] = 'cp-country-page';
        }
        return $classes;
    }

    /**
     * @param array<string, string> $parts
     * @return array<string, string>
     */
    public function documentTitleParts(array $parts): array {
        $cp = $GLOBALS['cp_country_standalone'] ?? null;
        if (is_array($cp) && !empty($cp['page_title'])) {
            $parts['title'] = (string) $cp['page_title'];
        }
        return $parts;
    }

    public function templateInclude(string $template): string {
        $slug = self::getRequestedSlug();
        if ($slug === null) {
            unset($GLOBALS['cp_country_standalone']);
            return $template;
        }

        $shortcode = new CountryCardShortcode();
        $result = $shortcode->renderStandalonePage($slug);

        if (!$result['success']) {
            unset($GLOBALS['cp_country_standalone']);
            status_header(404);
            nocache_headers();
            return get_404_template();
        }

        $GLOBALS['cp_country_standalone'] = [
            'html' => $result['html'],
            'page_title' => $result['page_title'] ?? '',
        ];

        return CP_PLUGIN_DIR . 'src/Templates/standalone-country-page.php';
    }

    /**
     * @param list<string> $vars
     * @return list<string>
     */
    public function filterQueryVars(array $vars): array {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    public static function getBaseSlug(): string {
        $base = get_option(self::OPTION_BASE, 'pais');
        if (!is_string($base)) {
            return 'pais';
        }
        $base = sanitize_title(trim($base));
        return $base !== '' ? $base : 'pais';
    }

    public static function permalinkForSlug(string $slug): string {
        $slug = sanitize_title($slug);
        if ($slug === '') {
            return '';
        }
        $base = self::getBaseSlug();
        if ($base === '') {
            return '';
        }
        $path = $base . '/' . $slug;
        return home_url(user_trailingslashit($path));
    }

    private static function getRequestedSlug(): ?string {
        $raw = get_query_var(self::QUERY_VAR);
        if ($raw === '' || $raw === false || $raw === null) {
            return null;
        }
        $slug = sanitize_title((string) $raw);
        return $slug !== '' ? $slug : null;
    }
}
