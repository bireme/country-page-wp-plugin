<?php
namespace CP\Admin;

if (!defined('ABSPATH')) exit;

final class AdminMenu {
    const MENU_SLUG = 'country-pages';
    const CAPABILITY = 'manage_options';

    public function register(): void {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAboutAssets']);
    }

    public function enqueueAboutAssets(string $hook): void {
        if ($hook !== 'toplevel_page_' . self::MENU_SLUG) {
            return;
        }
        wp_enqueue_style(
            'cp-admin',
            \CP_PLUGIN_URL . 'src/Assets/admin.css',
            [],
            \CP_VERSION
        );
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
        <div class="wrap cp-about">
            <div class="cp-about__layout">
            <header class="cp-about__hero">
                <span class="cp-about__hero-badge" aria-label="<?php echo esc_attr(sprintf(
                    /* translators: %s: plugin version number */
                    __('Versão %s', 'country-pages'),
                    \CP_VERSION
                )); ?>">
                    <?php echo esc_html(\CP_VERSION); ?>
                </span>
                <h1 class="cp-about__hero-title"><?php esc_html_e('Country Pages', 'country-pages'); ?></h1>
                <p class="cp-about__hero-lead"><?php esc_html_e('Este plugin permite consumir dados de países via API REST personalizada, exibindo os dados através de shortcodes flexíveis e templates customizáveis.', 'country-pages'); ?></p>
            </header>

            <div class="cp-about__main">
                <div class="cp-about__section-head">
                    <span class="cp-about__section-accent" aria-hidden="true"></span>
                    <h2 class="cp-about__heading"><?php esc_html_e('Páginas de administração', 'country-pages'); ?></h2>
                </div>
                <div class="cp-about__tiles" role="list">
                    <div class="cp-about__tile" role="listitem">
                        <div class="cp-about__tile-icon-wrap">
                            <span class="cp-about__tile-icon dashicons dashicons-admin-settings" aria-hidden="true"></span>
                        </div>
                        <h3 class="cp-about__tile-title"><?php esc_html_e('Configurações', 'country-pages'); ?></h3>
                        <p class="cp-about__tile-text"><?php esc_html_e('Endpoint da API REST de países; prefixo da URL pública de cada país (ex.: /pais/brasil/), o mesmo slug usado no shortcode [country]; CSS e JavaScript customizados no front (apenas quando quem salvou tiver permissão para HTML não filtrado).', 'country-pages'); ?></p>
                    </div>
                    <div class="cp-about__tile" role="listitem">
                        <div class="cp-about__tile-icon-wrap cp-about__tile-icon-wrap--violet">
                            <span class="cp-about__tile-icon dashicons dashicons-media-code" aria-hidden="true"></span>
                        </div>
                        <h3 class="cp-about__tile-title"><?php esc_html_e('Templates', 'country-pages'); ?></h3>
                        <p class="cp-about__tile-text"><?php esc_html_e('Modo padrão ou customizado para cartão de país e para lista, usando arquivos em Templates/Custom/.', 'country-pages'); ?></p>
                    </div>
                    <div class="cp-about__tile" role="listitem">
                        <div class="cp-about__tile-icon-wrap cp-about__tile-icon-wrap--amber">
                            <span class="cp-about__tile-icon dashicons dashicons-admin-generic" aria-hidden="true"></span>
                        </div>
                        <h3 class="cp-about__tile-title"><?php esc_html_e('Mapeamento ACF', 'country-pages'); ?></h3>
                        <p class="cp-about__tile-text"><?php esc_html_e('Quais campos ACF da API entram nos templates e como são tratados.', 'country-pages'); ?></p>
                    </div>
                </div>

                <div class="cp-about__split">
                    <div class="cp-about__callout">
                        <div class="cp-about__callout-icon-wrap" aria-hidden="true">
                            <span class="cp-about__callout-icon dashicons dashicons-admin-links"></span>
                        </div>
                        <div class="cp-about__callout-body">
                            <h2 class="cp-about__callout-title"><?php esc_html_e('URLs públicas de país', 'country-pages'); ?></h2>
                            <p class="cp-about__callout-text"><?php esc_html_e('Além do shortcode, cada país fica disponível em uma URL amigável: /{prefixo}/{slug}/ (prefixo configurável em Configurações; padrão “pais”). O conteúdo corresponde à página completa do país, como no shortcode. Se mudar o prefixo, confira em Ajustes → Links permanentes se as URLs continuam corretas.', 'country-pages'); ?></p>
                        </div>
                    </div>

                    <aside class="cp-about__panel" aria-labelledby="cp-about-templates-heading">
                        <h2 id="cp-about-templates-heading" class="cp-about__panel-title"><?php esc_html_e('Sistema de Templates:', 'country-pages'); ?></h2>
                        <p class="cp-about__panel-lead"><?php esc_html_e('O plugin oferece templates padrão e a possibilidade de usar templates personalizados. Para templates customizados, suba os arquivos na pasta:', 'country-pages'); ?></p>
                        <ul class="cp-about__path-cards">
                            <li class="cp-about__path-card">
                                <code class="cp-about__path-code">Templates/Custom/custom-country.php</code>
                                <span class="cp-about__path-label"><?php esc_html_e('Template para países individuais', 'country-pages'); ?></span>
                            </li>
                            <li class="cp-about__path-card">
                                <code class="cp-about__path-code">Templates/Custom/custom-list.php</code>
                                <span class="cp-about__path-label"><?php esc_html_e('Template para lista de países', 'country-pages'); ?></span>
                            </li>
                        </ul>
                        <p class="cp-about__panel-foot"><?php esc_html_e('Após subir os arquivos, ative o modo "Customizado" na página Templates para cada tipo.', 'country-pages'); ?></p>
                    </aside>
                </div>

                <div class="cp-about__section-head cp-about__section-head--spaced">
                    <span class="cp-about__section-accent" aria-hidden="true"></span>
                    <h2 class="cp-about__heading"><?php esc_html_e('Shortcodes disponíveis', 'country-pages'); ?></h2>
                </div>
                <div class="cp-about__shortcodes">
                    <section class="cp-about__shortcode" aria-labelledby="cp-about-sc-country">
                        <header class="cp-about__shortcode-head">
                            <span class="cp-about__shortcode-num" aria-hidden="true">01</span>
                            <h3 id="cp-about-sc-country" class="cp-about__shortcode-name"><code>[country]</code></h3>
                        </header>
                        <div class="cp-about__shortcode-body">
                            <div class="cp-about__code-wrap">
                                <span class="cp-about__code-label"><?php esc_html_e('Exemplo', 'country-pages'); ?></span>
                                <pre class="cp-about__pre" tabindex="0"><code>[country slug="brasil"]</code></pre>
                            </div>
                            <p class="cp-about__shortcode-desc"><?php esc_html_e('Cartão ou página de um país pelo slug da API.', 'country-pages'); ?></p>
                            <p class="cp-about__shortcode-meta"><?php esc_html_e('Opcionais: title-only, content-only, image-only (valor true exibe só essa parte).', 'country-pages'); ?></p>
                        </div>
                    </section>

                    <section class="cp-about__shortcode" aria-labelledby="cp-about-sc-list">
                        <header class="cp-about__shortcode-head">
                            <span class="cp-about__shortcode-num" aria-hidden="true">02</span>
                            <h3 id="cp-about-sc-list" class="cp-about__shortcode-name"><code>[country_list]</code></h3>
                        </header>
                        <div class="cp-about__shortcode-body">
                            <div class="cp-about__code-wrap">
                                <span class="cp-about__code-label"><?php esc_html_e('Exemplo', 'country-pages'); ?></span>
                                <pre class="cp-about__pre" tabindex="0"><code>[country_list per_page="12" page="1" search="" region="" tag="" category=""]</code></pre>
                            </div>
                            <p class="cp-about__shortcode-desc"><?php esc_html_e('Lista com filtros, cards e paginação. Parâmetros de URL: cp_cl_search, cp_cl_tag, cp_cl_cat, cp_cl_page. IDs numéricos em tag e category.', 'country-pages'); ?></p>
                        </div>
                    </section>

                    <section class="cp-about__shortcode" aria-labelledby="cp-about-sc-slider">
                        <header class="cp-about__shortcode-head">
                            <span class="cp-about__shortcode-num" aria-hidden="true">03</span>
                            <h3 id="cp-about-sc-slider" class="cp-about__shortcode-name"><code>[country_slider]</code></h3>
                        </header>
                        <div class="cp-about__shortcode-body">
                            <div class="cp-about__code-wrap">
                                <span class="cp-about__code-label"><?php esc_html_e('Exemplo', 'country-pages'); ?></span>
                                <pre class="cp-about__pre" tabindex="0"><code>[country_slider itens="12" itens-per-round="3" loop="false" tag-filter="" category-filter="" region="" search=""]</code></pre>
                            </div>
                            <p class="cp-about__shortcode-desc"><?php esc_html_e('Carrossel horizontal de países. itens = total buscado na API; itens-per-round = quantos slides visíveis; loop = true/false; tag-filter e category-filter = ID numérico. Também aceita aliases com sublinhado: itens_per_round, tag_filter, category_filter.', 'country-pages'); ?></p>
                        </div>
                    </section>
                </div>
            </div>
            </div>
        </div>
        <?php
    }
}
