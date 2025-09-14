<?php
namespace CP\Support;

if (!defined('ABSPATH')) exit;

class Template {
    private static function plugin_root(): string {
        return trailingslashit(dirname(__DIR__, 1)); 
    }

    public static function load_custom(string $which, array $vars): ?string {
        $file = null;
        if ($which === 'country') $file = self::plugin_root() . 'templates/custom-country.php';
        if ($which === 'list')    $file = self::plugin_root() . 'templates/custom-list.php';

        // tenta arquivo físico
        if ($file && file_exists($file)) {
            ob_start();
            extract($vars, EXTR_SKIP);
            include $file;
            return ob_get_clean();
        }

        // fallback: código salvo em options (FS somente-leitura)
        $opt = null;
        if ($which === 'country') $opt = get_option('cp_template_code_country', '');
        if ($which === 'list')    $opt = get_option('cp_template_code_list', '');
        if ($opt) {
            ob_start();
            extract($vars, EXTR_SKIP);
            eval('?>' . $opt);
            return ob_get_clean();
        }

        return null;
    }
}
