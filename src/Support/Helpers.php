<?php
namespace CP\Support;

if (!defined('ABSPATH')) exit;

final class Helpers {
    /**
     * Tema: country-pages/{template}; fallback no plugin. $preferTheme false força só o plugin.
     */
    public static function renderTemplate(string $templateName, array $vars = [], bool $preferTheme = true): string {
        $themePath = locate_template('country-pages/' . $templateName);
        $pluginPath = \CP_PLUGIN_DIR . 'src/Templates/' . $templateName;

        if ($preferTheme && $themePath) {
            $file = $themePath;
        } elseif (!$preferTheme && file_exists($pluginPath)) {
            $file = $pluginPath;
        } else {
            $file = $themePath ?: $pluginPath;
        }
        if (!file_exists($file)) return '';

        ob_start();
        extract($vars, EXTR_SKIP);
        include $file;
        return (string) ob_get_clean();
    }
}
