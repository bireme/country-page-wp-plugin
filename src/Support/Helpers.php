<?php
namespace CP\Support;

if (!defined('ABSPATH')) exit;

final class Helpers {
    /**
     * Carrega um template do plugin permitindo override pelo tema:
     *  - theme/country-pages/country-card.php
     *  - plugin/src/Templates/country-card.php (fallback)
     *
     * @param string $templateName
     * @param array  $vars
     */
    public static function renderTemplate(string $templateName, array $vars = []): string {
        $themePath = locate_template('country-pages/' . $templateName);
        $pluginPath = \CP_PLUGIN_DIR . 'src/Templates/' . $templateName;

        $file = $themePath ?: $pluginPath;
        if (!file_exists($file)) return '';

        ob_start();
        // Torna $vars disponíveis como variáveis
        extract($vars, EXTR_SKIP);
        include $file;
        return (string) ob_get_clean();
    }
}
