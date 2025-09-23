<?php
namespace CP\Admin;

use CP\Support\Template;

if (!defined('ABSPATH')) exit;

class TemplatesPage {
    const CAPABILITY = 'manage_options';
    const OPT_MODE_COUNTRY = 'cp_template_mode_country'; // 'default' | 'custom'
    const OPT_MODE_LIST    = 'cp_template_mode_list';    // 'default' | 'custom'
    const OPT_MODE_BVS     = 'cp_template_mode_bvs';     // 'default' | 'custom'

    public static function boot(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_init', [self::class, 'cleanup_old_options'], 1);
    }

    public static function menu(): void {
        add_submenu_page(
            'country-pages',
            __('Templates', 'country-pages'),
            __('Templates', 'country-pages'),
            self::CAPABILITY,
            'cp-country-templates',
            [self::class, 'render_page']
        );
    }

    public static function register_settings(): void {
        register_setting('cp_templates', self::OPT_MODE_COUNTRY);
        register_setting('cp_templates', self::OPT_MODE_LIST);
        register_setting('cp_templates', self::OPT_MODE_BVS);
    }

    /**
     * Remove opções antigas do sistema de templates baseado em código no banco
     */
    public static function cleanup_old_options(): void {
        // Remove as opções antigas apenas uma vez
        if (get_option('cp_templates_cleaned', false)) {
            return;
        }

        // Remove opções antigas do editor de código
        delete_option('cp_template_code_country');
        delete_option('cp_template_code_list');
        
        // Marca como limpo para não executar novamente
        update_option('cp_templates_cleaned', true);
    }

    private static function get_custom_template_path(string $type): string {
        $plugin_root = trailingslashit(dirname(__DIR__, 1));
        if ($type === 'country') {
            return $plugin_root . 'Templates/Custom/custom-country.php';
        }
        if ($type === 'list') {
            return $plugin_root . 'Templates/Custom/custom-list.php';
        }
        if ($type === 'bvs') {
            return $plugin_root . 'Templates/Custom/custom-bvs-journals.php';
        }
        return '';
    }

    public static function render_page(): void {
        if (!current_user_can(self::CAPABILITY)) wp_die('Sem permissão.');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('cp_templates_nonce', 'cp_templates_nonce_field');
            self::handle_post();
        }

        $modeCountry = get_option(self::OPT_MODE_COUNTRY, 'default');
        $modeList    = get_option(self::OPT_MODE_LIST, 'default');
        $modeBvs     = get_option(self::OPT_MODE_BVS, 'default');
        
        $countryTemplatePath = self::get_custom_template_path('country');
        $listTemplatePath = self::get_custom_template_path('list');
        $bvsTemplatePath = self::get_custom_template_path('bvs');
        
        $countryTemplateExists = file_exists($countryTemplatePath);
        $listTemplateExists = file_exists($listTemplatePath);
        $bvsTemplateExists = file_exists($bvsTemplatePath);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Templates', 'country-pages'); ?></h1>
            <?php settings_errors('cp_templates'); ?>
            
            <div class="card">
                <h2><?php esc_html_e('Como usar templates customizados', 'country-pages'); ?></h2>
                <p><?php esc_html_e('Para usar templates personalizados, você deve subir manualmente os arquivos na pasta correta do plugin:', 'country-pages'); ?></p>
                <ul>
                    <li><strong><?php esc_html_e('Para países individuais:', 'country-pages'); ?></strong> <code>Templates/Custom/custom-country.php</code></li>
                    <li><strong><?php esc_html_e('Para lista de países:', 'country-pages'); ?></strong> <code>Templates/Custom/custom-list.php</code></li>
                    <li><strong><?php esc_html_e('Para journals BVS:', 'country-pages'); ?></strong> <code>Templates/Custom/custom-bvs-journals.php</code></li>
                </ul>
                <p><?php esc_html_e('Após subir os arquivos, ative o modo "Custom" nas opções abaixo.', 'country-pages'); ?></p>
            </div>

            <form method="post">
                <?php settings_fields('cp_templates'); ?>
                <?php wp_nonce_field('cp_templates_nonce', 'cp_templates_nonce_field'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cp_template_mode_country"><?php esc_html_e('Template de País Individual', 'country-pages'); ?></label>
                        </th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPT_MODE_COUNTRY); ?>" id="cp_template_mode_country">
                                <option value="default" <?php selected($modeCountry, 'default'); ?>><?php esc_html_e('Padrão', 'country-pages'); ?></option>
                                <option value="custom" <?php selected($modeCountry, 'custom'); ?>><?php esc_html_e('Customizado', 'country-pages'); ?></option>
                            </select>
                            <p class="description">
                                <strong><?php esc_html_e('Arquivo:', 'country-pages'); ?></strong> <code>Templates/Custom/custom-country.php</code><br>
                                <strong><?php esc_html_e('Status:', 'country-pages'); ?></strong> 
                                <?php if ($countryTemplateExists): ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Arquivo encontrado', 'country-pages'); ?></span>
                                <?php else: ?>
                                    <span style="color: red;">✗ <?php esc_html_e('Arquivo não encontrado', 'country-pages'); ?></span>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cp_template_mode_list"><?php esc_html_e('Template de Lista de Países', 'country-pages'); ?></label>
                        </th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPT_MODE_LIST); ?>" id="cp_template_mode_list">
                                <option value="default" <?php selected($modeList, 'default'); ?>><?php esc_html_e('Padrão', 'country-pages'); ?></option>
                                <option value="custom" <?php selected($modeList, 'custom'); ?>><?php esc_html_e('Customizado', 'country-pages'); ?></option>
                            </select>
                            <p class="description">
                                <strong><?php esc_html_e('Arquivo:', 'country-pages'); ?></strong> <code>Templates/Custom/custom-list.php</code><br>
                                <strong><?php esc_html_e('Status:', 'country-pages'); ?></strong> 
                                <?php if ($listTemplateExists): ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Arquivo encontrado', 'country-pages'); ?></span>
                                <?php else: ?>
                                    <span style="color: red;">✗ <?php esc_html_e('Arquivo não encontrado', 'country-pages'); ?></span>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cp_template_mode_bvs"><?php esc_html_e('Template de Journals BVS', 'country-pages'); ?></label>
                        </th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPT_MODE_BVS); ?>" id="cp_template_mode_bvs">
                                <option value="default" <?php selected($modeBvs, 'default'); ?>><?php esc_html_e('Padrão', 'country-pages'); ?></option>
                                <option value="custom" <?php selected($modeBvs, 'custom'); ?>><?php esc_html_e('Customizado', 'country-pages'); ?></option>
                            </select>
                            <p class="description">
                                <strong><?php esc_html_e('Arquivo:', 'country-pages'); ?></strong> <code>Templates/Custom/custom-bvs-journals.php</code><br>
                                <strong><?php esc_html_e('Status:', 'country-pages'); ?></strong> 
                                <?php if ($bvsTemplateExists): ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Arquivo encontrado', 'country-pages'); ?></span>
                                <?php else: ?>
                                    <span style="color: red;">✗ <?php esc_html_e('Arquivo não encontrado', 'country-pages'); ?></span>
                                <?php endif; ?>
                                <br><strong><?php esc_html_e('Shortcode:', 'country-pages'); ?></strong> <code>[bvs_journals]</code>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Salvar Configurações', 'country-pages')); ?>
            </form>
        </div>
        <?php
    }

    private static function handle_post(): void {
        if (!current_user_can(self::CAPABILITY)) return;

        // Salva apenas os modos selecionados
        update_option(self::OPT_MODE_COUNTRY, (isset($_POST[self::OPT_MODE_COUNTRY]) && $_POST[self::OPT_MODE_COUNTRY] === 'custom') ? 'custom' : 'default');
        update_option(self::OPT_MODE_LIST, (isset($_POST[self::OPT_MODE_LIST]) && $_POST[self::OPT_MODE_LIST] === 'custom') ? 'custom' : 'default');
        update_option(self::OPT_MODE_BVS, (isset($_POST[self::OPT_MODE_BVS]) && $_POST[self::OPT_MODE_BVS] === 'custom') ? 'custom' : 'default');

        add_settings_error('cp_templates', 'saved', __('Configurações de templates salvas com sucesso.', 'country-pages'), 'updated');
    }

    /**
     * Método auxiliar para verificar se deve usar template customizado para BVS
     */
    public static function shouldUseCustomBvsTemplate(): bool {
        return get_option(self::OPT_MODE_BVS, 'default') === 'custom';
    }

}
