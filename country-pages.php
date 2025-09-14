<?php
/**
 * Plugin Name:       Country Pages
 * Description:       Consome API externa (WP CPT), normaliza dados e oferece shortcodes com templates para páginas de países.
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

add_action('plugins_loaded', function () {
    load_plugin_textdomain('country-pages', false, dirname(plugin_basename(__FILE__)) . '/languages');
    if (is_admin()) {
        \CP\Admin\TemplatesPage::boot();
    }
    
    (new CP\Plugin())->boot();
});
