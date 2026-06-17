<?php
namespace CP;

use CP\Admin\AdminMenu;
use CP\Admin\LogsPage;
use CP\Admin\SettingsPage;
use CP\Front\CountryPageRoute;
use CP\Shortcodes\CountryCardShortcode;
use CP\Shortcodes\CountryListShortcode;
use CP\Shortcodes\CountrySliderShortcode;
use CP\Support\Logger;

final class Plugin {
    public function boot(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueuePublicAssets']);

        if (is_admin()) {
            (new AdminMenu())->register();
            (new SettingsPage())->register();
            (new LogsPage())->register();
            \CP\Admin\TemplatesPage::boot();
            \CP\Admin\AcfMappingPage::boot();
        }

        (new CountryCardShortcode())->register();
        (new CountryListShortcode())->register();
        (new CountrySliderShortcode())->register();

        (new CountryPageRoute())->register();

        add_action('wp_head', [$this, 'printCustomCSS']);
        add_action('wp_footer', [$this, 'printCustomJS']);

        $this->registerErrorLogging();
    }

    public function enqueuePublicAssets(): void {
        wp_enqueue_style('cp-public', CP_PLUGIN_URL . 'src/Assets/public.css', [], CP_VERSION);
        wp_enqueue_script('cp-public', CP_PLUGIN_URL . 'src/Assets/public.js', ['wp-element'], CP_VERSION, true);
    }

    public function printCustomCSS(): void {
        $css = get_option('cp_custom_css');
        if (!empty($css)) {
            if (current_user_can('unfiltered_html')) {
                echo "<style id='cp-custom-css'>\n" . $css . "\n</style>";
            }
        }
    }

    public function printCustomJS(): void {
        $js = get_option('cp_custom_js');
        if (!empty($js)) {
            if (current_user_can('unfiltered_html')) {
                echo "<script id='cp-custom-js'>\n" . $js . "\n</script>";
            }
        }
    }

    private function registerErrorLogging(): void {
        $previousHandler = null;
        $previousHandler = set_error_handler(static function (int $severity, string $message, string $file, int $line) use (&$previousHandler): bool {
            static $isLogging = false;

            if (strpos($file, CP_PLUGIN_DIR) !== 0) {
                return is_callable($previousHandler) ? (bool) $previousHandler($severity, $message, $file, $line) : false;
            }
            if ($isLogging) {
                return is_callable($previousHandler) ? (bool) $previousHandler($severity, $message, $file, $line) : false;
            }

            $level = in_array($severity, [E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING], true)
                ? 'warning'
                : 'error';

            $isLogging = true;
            try {
                Logger::log($level, 'Erro PHP capturado pelo plugin.', [
                    'php_message' => $message,
                    'file' => str_replace(CP_PLUGIN_DIR, '', $file),
                    'line' => $line,
                    'severity' => $severity,
                ]);
            } finally {
                $isLogging = false;
            }

            return is_callable($previousHandler) ? (bool) $previousHandler($severity, $message, $file, $line) : false;
        });
    }
}
