<?php
namespace CP;

use CP\Admin\SettingsPage;
use CP\Shortcodes\CountryCardShortcode;
use CP\Shortcodes\CountryListShortcode;

final class Plugin {
    public function boot(): void {
        
        // Assets públicos
        add_action('wp_enqueue_scripts', [$this, 'enqueuePublicAssets']);

        // Admin
        (new SettingsPage())->register();

        // Shortcodes 
        (new CountryCardShortcode())->register();
        (new CountryListShortcode())->register();

        // Custom CSS/JS do admin (config) — só imprime no front se houver e usuário tiver salvo
        add_action('wp_head', [$this, 'printCustomCSS']);
        add_action('wp_footer', [$this, 'printCustomJS']);
    }

    public function enqueuePublicAssets(): void {
        wp_enqueue_style('cp-public', CP_PLUGIN_URL . 'src/Assets/public.css', [], CP_VERSION);
        wp_enqueue_script('cp-public', CP_PLUGIN_URL . 'src/Assets/public.js', ['wp-element'], CP_VERSION, true);
    }

    public function printCustomCSS(): void {
        $css = get_option('cp_custom_css');
        if (!empty($css)) {
            // Permite HTML cru apenas para quem tem unfiltered_html (admins); senão, limpa
            // Evista que um usuário crie algum css que interfira no site em locais onde não deveria (como aconteceu no portal)
            if (current_user_can('unfiltered_html')) {
                echo "<style id='cp-custom-css'>\n" . $css . "\n</style>";
            }
        }
    }

    public function printCustomJS(): void {
        $js = get_option('cp_custom_js');
        if (!empty($js)) {
            //Mesma coisa do css.
            //Evita uso indiscriminado e interferência no site
            if (current_user_can('unfiltered_html')) {
                echo "<script id='cp-custom-js'>\n" . $js . "\n</script>";
            }
        }
    }
}
