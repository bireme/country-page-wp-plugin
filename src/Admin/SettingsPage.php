<?php
namespace CP\Admin;

if (!defined('ABSPATH')) exit;

final class SettingsPage {
    const OPTION_ENDPOINT = 'cp_api_endpoint';
    const OPTION_CUSTOM_CSS = 'cp_custom_css';
    const OPTION_CUSTOM_JS  = 'cp_custom_js';
    const OPTION_JOURNALS_API_URL = 'cp_journals_api_url';
    const OPTION_BVSALUD_TOKEN = 'cp_bvsalud_token';

    public function register(): void {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function addMenu(): void {
        add_submenu_page(
            'country-pages',
            __('Configurações', 'country-pages'),
            __('Configurações', 'country-pages'),
            'manage_options',
            'country-pages-settings',
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void {
        register_setting('cp_settings', self::OPTION_ENDPOINT, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        register_setting('cp_settings', self::OPTION_CUSTOM_CSS, [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitizeMaybe'],
            'default' => '',
        ]);

        register_setting('cp_settings', self::OPTION_CUSTOM_JS, [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitizeMaybe'],
            'default' => '',
        ]);

        register_setting('cp_settings', self::OPTION_JOURNALS_API_URL, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        register_setting('cp_settings', self::OPTION_BVSALUD_TOKEN, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);

        add_settings_section('cp_main', __('Configurações', 'country-pages'), function () {
            echo '<p>' . esc_html__('Configure os endpoints das APIs, tokens de acesso e personalizações CSS/JS.', 'country-pages') . '</p>';
        }, 'country-pages');

        add_settings_field(self::OPTION_ENDPOINT, __('Endpoint da API', 'country-pages'), function () {
            $value = esc_url(get_option(self::OPTION_ENDPOINT, ''));
            echo '<input type="url" name="' . esc_attr(self::OPTION_ENDPOINT) . '" class="regular-text" placeholder="https://site.com/wp-json/wp/v2/countries" value="' . $value . '"/>';
        }, 'country-pages', 'cp_main');

        add_settings_field(self::OPTION_JOURNALS_API_URL, __('URL API de Journals', 'country-pages'), function () {
            $value = esc_url(get_option(self::OPTION_JOURNALS_API_URL, ''));
            echo '<input type="url" name="' . esc_attr(self::OPTION_JOURNALS_API_URL) . '" class="regular-text" placeholder="https://api.journals.com/endpoint" value="' . $value . '"/>';
            echo '<p class="description">' . esc_html__('URL da API para consulta de journals.', 'country-pages') . '</p>';
        }, 'country-pages', 'cp_main');

        add_settings_field(self::OPTION_BVSALUD_TOKEN, __('BVSalud Token', 'country-pages'), function () {
            $value = get_option(self::OPTION_BVSALUD_TOKEN, '');
            echo '<input type="password" name="' . esc_attr(self::OPTION_BVSALUD_TOKEN) . '" class="regular-text" placeholder="' . esc_attr__('Token de acesso', 'country-pages') . '" value="' . esc_attr($value) . '"/>';
            echo '<p class="description">' . esc_html__('Token de autenticação para acesso à API BVSalud.', 'country-pages') . '</p>';
        }, 'country-pages', 'cp_main');

        add_settings_field(self::OPTION_CUSTOM_CSS, __('CSS customizado', 'country-pages'), function () {
            $value = esc_textarea(get_option(self::OPTION_CUSTOM_CSS, ''));
            echo '<textarea name="' . esc_attr(self::OPTION_CUSTOM_CSS) . '" rows="8" class="large-text code" placeholder="/* Seu CSS */">'.$value.'</textarea>';
        }, 'country-pages', 'cp_main');

        add_settings_field(self::OPTION_CUSTOM_JS, __('JS customizado', 'country-pages'), function () {
            $value = esc_textarea(get_option(self::OPTION_CUSTOM_JS, ''));
            echo '<textarea name="' . esc_attr(self::OPTION_CUSTOM_JS) . '" rows="8" class="large-text code" placeholder="// Seu JS">'.$value.'</textarea>';
        }, 'country-pages', 'cp_main');
    }

    public function renderPage(): void {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Country Pages', 'country-pages'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('cp_settings');
                do_settings_sections('country-pages');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueueAdminAssets($hook): void {
        if ($hook !== 'country-pages_page_country-pages-settings') return;
        wp_enqueue_style('cp-admin', \CP_PLUGIN_URL . 'src/Assets/admin.css', [], \CP_VERSION);
        wp_enqueue_script('cp-admin', \CP_PLUGIN_URL . 'src/Assets/admin.js', [], \CP_VERSION, true);
    }

    /**
     * Permite HTML cru apenas para quem tem unfiltered_html (admins); senão, limpa.
     */
    public function sanitizeMaybe($value): string {
        if (current_user_can('unfiltered_html')) return (string) $value;
        return sanitize_textarea_field((string) $value);
    }

    /**
     * Métodos auxiliares para acessar as configurações
     */
    public static function getJournalsApiUrl(): string {
        return get_option(self::OPTION_JOURNALS_API_URL, '');
    }

    public static function getBvsaludToken(): string {
        return get_option(self::OPTION_BVSALUD_TOKEN, '');
    }

    public static function getApiEndpoint(): string {
        return get_option(self::OPTION_ENDPOINT, '');
    }
}
