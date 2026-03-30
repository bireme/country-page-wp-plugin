<?php
/**
 * Plugin Name:       Country Pages
 * Description:       Consome API REST de países, shortcodes [country], [country_list] e [country_slider], templates customizáveis e URLs /prefixo/slug/.
 * Version:           1.0.0
 * Author:            Jefferson Augusto Lopes
 * Text Domain:       country-pages
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) exit;

define('CP_PLUGIN_FILE', __FILE__);
define('CP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CP_VERSION', '1.0.0');

require_once CP_PLUGIN_DIR . 'src/Autoloader.php';
CP\Autoloader::init('CP', CP_PLUGIN_DIR . 'src');

register_activation_hook(__FILE__, static function (): void {
    if (get_option('cp_country_url_base', null) === null) {
        add_option('cp_country_url_base', 'pais');
    }
    CP\Front\CountryPageRoute::activateFlush();
});

add_action('plugins_loaded', function () {
    load_plugin_textdomain('country-pages', false, dirname(plugin_basename(__FILE__)) . '/languages');
    (new CP\Plugin())->boot();
});
