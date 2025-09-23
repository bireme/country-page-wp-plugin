<?php
namespace CP\Admin;

if (!defined('ABSPATH')) exit;

final class AdminMenu {
    const MENU_SLUG = 'country-pages';
    const CAPABILITY = 'manage_options';

    public function register(): void {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    public function addMenu(): void {
       
        add_menu_page(
            __('Country Pages', 'country-pages'),
            __('Country Pages', 'country-pages'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderMainPage'],
            'dashicons-admin-site-alt3',
            30
        );


        add_submenu_page(
            self::MENU_SLUG,
            __('Sobre', 'country-pages'),
            __('Sobre', 'country-pages'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderMainPage']
        );
    }

    public function renderMainPage(): void {
        if (!current_user_can(self::CAPABILITY)) return;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Country Pages', 'country-pages'); ?></h1>
            <div class="card">
                <h2><?php esc_html_e('Bem-vindo ao Country Pages', 'country-pages'); ?></h2>
                <p><?php esc_html_e('Este plugin permite consumir dados de países via API REST personalizada e periódicos científicos via API BVS Saúde, exibindo os dados através de shortcodes flexíveis e templates customizáveis.', 'country-pages'); ?></p>
                
                <div class="notice notice-info inline">
                    <p><strong><?php esc_html_e('Novidade:', 'country-pages'); ?></strong> <?php esc_html_e('Integração completa com API BVS Saúde para exibição de journals científicos com sistema de templates robusto!', 'country-pages'); ?></p>
                </div>
                
                <h3><?php esc_html_e('Páginas de Configuração:', 'country-pages'); ?></h3>
                <ul>
                    <li><strong><?php esc_html_e('Configurações', 'country-pages'); ?></strong> - <?php esc_html_e('Configure endpoints das APIs (países e BVS Saúde), tokens de acesso e CSS/JS customizados', 'country-pages'); ?></li>
                    <li><strong><?php esc_html_e('Templates', 'country-pages'); ?></strong> - <?php esc_html_e('Configure templates para países, listas e journals BVS (padrão ou customizado)', 'country-pages'); ?></li>
                    <li><strong><?php esc_html_e('Mapeamento ACF', 'country-pages'); ?></strong> - <?php esc_html_e('Configure o mapeamento de campos ACF para países', 'country-pages'); ?></li>
                </ul>

                <h3><?php esc_html_e('Sistema de Templates:', 'country-pages'); ?></h3>
                <p><?php esc_html_e('O plugin oferece templates padrão e a possibilidade de usar templates personalizados. Para templates customizados, suba os arquivos na pasta:', 'country-pages'); ?></p>
                <ul>
                    <li><code>Templates/Custom/custom-country.php</code> - <?php esc_html_e('Template para países individuais', 'country-pages'); ?></li>
                    <li><code>Templates/Custom/custom-list.php</code> - <?php esc_html_e('Template para lista de países', 'country-pages'); ?></li>
                    <li><code>Templates/Custom/custom-bvs-journals.php</code> - <strong><?php esc_html_e('Template para journals BVS (NOVO)', 'country-pages'); ?></strong></li>
                </ul>
                <p><?php esc_html_e('Após subir os arquivos, ative o modo "Customizado" na página Templates para cada tipo.', 'country-pages'); ?></p>

                <h3><?php esc_html_e('Shortcodes Disponíveis:', 'country-pages'); ?></h3>
                
                <h4><?php esc_html_e('Shortcodes de Países:', 'country-pages'); ?></h4>
                <ul>
                    <li><code>[country slug="brasil"]</code> - <?php esc_html_e('Exibe dados de um país específico', 'country-pages'); ?></li>
                    <li><code>[country_list per_page="12"]</code> - <?php esc_html_e('Exibe lista de países com paginação', 'country-pages'); ?></li>
                </ul>
                
                <h4><?php esc_html_e('Shortcodes de Journals (BVS Saúde):', 'country-pages'); ?></h4>
                <ul>
                    <li><code>[bvs_journals country="Brazil"]</code> - <?php esc_html_e('Journals por país', 'country-pages'); ?></li>
                    <!-- Testando ainda -->
                    <!-- <li><code>[bvs_journals search="medicina" template="grid"]</code> - <?php esc_html_e('Busca com template grid responsivo', 'country-pages'); ?></li>
                    <li><code>[bvs_journals issn="1234-5678" template="detailed"]</code> - <?php esc_html_e('Journal específico por ISSN', 'country-pages'); ?></li>
                    <li><code>[bvs_journals subject="saúde pública" show_pagination="true"]</code> - <?php esc_html_e('Por área temática com paginação', 'country-pages'); ?></li> -->
                </ul>
                
                
                
                
            </div>
        </div>
        <?php
    }
}
