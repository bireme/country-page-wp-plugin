<?php
namespace CP\Support;

if (!defined('ABSPATH')) exit;

class Template {
    private static function plugin_root(): string {
        return trailingslashit(dirname(__DIR__, 1)); 
    }

    /**
     * Carrega template customizado da pasta Templates/Custom
     * 
     * @param string $which - 'country', 'list', ou 'bvs-journals'
     * @param array $vars - Variáveis para o template
     * @return string|null
     */
    public static function load_custom(string $which, array $vars): ?string {
        $file = null;
        if ($which === 'country') {
            $file = self::plugin_root() . 'Templates/Custom/custom-country.php';
        }
        if ($which === 'list') {
            $file = self::plugin_root() . 'Templates/Custom/custom-list.php';
        }
        if ($which === 'bvs-journals') {
            $file = self::plugin_root() . 'Templates/Custom/custom-bvs-journals.php';
        }

        // Verifica se o arquivo customizado existe na pasta Custom
        if ($file && file_exists($file)) {
            ob_start();
            extract($vars, EXTR_SKIP);
            include $file;
            return ob_get_clean();
        }

        return null;
    }

    /**
     * Verifica se existe template customizado para o tipo especificado
     * 
     * @param string $which - 'country', 'list', ou 'bvs-journals'
     * @return bool
     */
    public static function has_custom_template(string $which): bool {
        $file = null;
        if ($which === 'country') {
            $file = self::plugin_root() . 'Templates/Custom/custom-country.php';
        }
        if ($which === 'list') {
            $file = self::plugin_root() . 'Templates/Custom/custom-list.php';
        }
        if ($which === 'bvs-journals') {
            $file = self::plugin_root() . 'Templates/Custom/custom-bvs-journals.php';
        }

        return $file && file_exists($file);
    }

    /**
     * Carrega template padrão de journals BVS
     * 
     * @param string $template - 'default', 'compact', 'detailed', 'grid'
     * @param array $vars - Variáveis para o template
     * @return string|null
     */
    public static function load_bvs_template(string $template, array $vars): ?string {
        $file = self::plugin_root() . "Templates/bvs-journals-{$template}.php";
        
        if (file_exists($file)) {
            ob_start();
            extract($vars, EXTR_SKIP);
            include $file;
            return ob_get_clean();
        }

        return null;
    }
}
