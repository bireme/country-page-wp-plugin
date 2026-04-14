<?php
namespace CP\Support;

if (!defined('ABSPATH')) exit;

class Template {
    private const COUNTRY_TEMPLATE_CANDIDATES = [
        'Templates/Custom/country-pages-custom-country.php',
        'Templates/Custom/custom-country.php',
    ];

    private const LIST_TEMPLATE_CANDIDATES = [
        'Templates/Custom/country-pages-custom-list.php',
        'Templates/Custom/custom-list.php',
    ];

    private const SLIDER_TEMPLATE_CANDIDATES = [
        'Templates/Custom/country-pages-custom-slider.php',
        'Templates/Custom/custom-slider.php',
    ];

    private static function plugin_root(): string {
        return trailingslashit(dirname(__DIR__, 1)); 
    }

    /** @param 'country'|'list'|'slider' $which */
    public static function load_custom(string $which, array $vars): ?string {
        $file = self::resolve_custom_template($which);

        if ($file && file_exists($file)) {
            ob_start();
            extract($vars, EXTR_SKIP);
            include $file;
            return ob_get_clean();
        }

        return null;
    }

    /** @param 'country'|'list'|'slider' $which */
    public static function has_custom_template(string $which): bool {
        $file = self::resolve_custom_template($which);

        return $file && file_exists($file);
    }

    /** @return list<string> */
    public static function custom_template_candidates(string $which): array {
        $paths = [];
        $candidates = [];
        if ($which === 'country') {
            $candidates = self::COUNTRY_TEMPLATE_CANDIDATES;
        }
        if ($which === 'list') {
            $candidates = self::LIST_TEMPLATE_CANDIDATES;
        }
        if ($which === 'slider') {
            $candidates = self::SLIDER_TEMPLATE_CANDIDATES;
        }
        foreach ($candidates as $candidate) {
            $paths[] = self::plugin_root() . $candidate;
        }
        return $paths;
    }

    private static function resolve_custom_template(string $which): ?string {
        $paths = self::custom_template_candidates($which);
        foreach ($paths as $file) {
            if (file_exists($file)) {
                return $file;
            }
        }
        return $paths[0] ?? null;
    }
}
