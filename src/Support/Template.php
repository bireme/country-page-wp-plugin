<?php
namespace CP\Support;

if (!defined('ABSPATH')) exit;

class Template {
    private static function plugin_root(): string {
        return trailingslashit(dirname(__DIR__, 1)); 
    }

    /** @param 'country'|'list' $which */
    public static function load_custom(string $which, array $vars): ?string {
        $file = null;
        if ($which === 'country') {
            $file = self::plugin_root() . 'Templates/Custom/custom-country.php';
        }
        if ($which === 'list') {
            $file = self::plugin_root() . 'Templates/Custom/custom-list.php';
        }

        if ($file && file_exists($file)) {
            ob_start();
            extract($vars, EXTR_SKIP);
            include $file;
            return ob_get_clean();
        }

        return null;
    }

    /** @param 'country'|'list' $which */
    public static function has_custom_template(string $which): bool {
        $file = null;
        if ($which === 'country') {
            $file = self::plugin_root() . 'Templates/Custom/custom-country.php';
        }
        if ($which === 'list') {
            $file = self::plugin_root() . 'Templates/Custom/custom-list.php';
        }

        return $file && file_exists($file);
    }

}
