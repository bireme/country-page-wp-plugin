<?php
namespace CP\Admin;

if (!defined('ABSPATH')) exit;

class TemplatesPage {
    const CAPABILITY    = 'manage_options';
    const FILE_COUNTRY  = 'templates/custom-country.php';
    const FILE_LIST     = 'templates/custom-list.php';


    const OPT_MODE_COUNTRY = 'cp_template_mode_country'; // 'default' | 'custom'
    const OPT_MODE_LIST    = 'cp_template_mode_list';    // 'default' | 'custom'
    const OPT_CODE_COUNTRY = 'cp_template_code_country';
    const OPT_CODE_LIST    = 'cp_template_code_list';

    public static function boot(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_code_editor']);
    }

    public static function menu(): void {
        add_submenu_page(
            'options-general.php',
            'Country Templates',
            'Country Templates',
            self::CAPABILITY,
            'cp-country-templates',
            [self::class, 'render_page']
        );
    }

    public static function register_settings(): void {
        register_setting('cp_templates', self::OPT_MODE_COUNTRY);
        register_setting('cp_templates', self::OPT_MODE_LIST);
    }

    public static function enqueue_code_editor($hook): void {
        if ($hook !== 'settings_page_cp-country-templates') return;
        $settings = wp_enqueue_code_editor(['type' => 'text/x-php']);
        if ($settings) {
            wp_add_inline_script('code-editor', 'window.cpCodeEditorSettings = ' . wp_json_encode($settings) . ';');
        }
        wp_enqueue_script('code-editor');
        wp_enqueue_style('code-editor');
    }

    private static function plugin_root(): string {
        // este arquivo está em src/Admin/, sobe dois níveis para a raiz do plugin
        return trailingslashit(dirname(__DIR__, 1)); 
    }

    private static function file_path(string $rel): string {
        return trailingslashit(self::plugin_root()) . ltrim($rel, '/');
    }

    private static function read_file_or_opt(string $fileRel, string $optCode, callable $defaultStub): string {
        $file = self::file_path($fileRel);
        if (file_exists($file)) {
            $code = file_get_contents($file);
            if ($code !== false && $code !== '') return $code;
        }
        $opt = get_option($optCode, '');
        if ($opt !== '') return $opt;
        return $defaultStub();
    }

    public static function render_page(): void {
        if (!current_user_can(self::CAPABILITY)) wp_die('Sem permissão.');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('cp_templates_nonce', 'cp_templates_nonce_field');
            self::handle_post();
        }

        $countryCode = self::read_file_or_opt(self::FILE_COUNTRY, self::OPT_CODE_COUNTRY, [self::class, 'defaultCountryStub']);
        $listCode    = self::read_file_or_opt(self::FILE_LIST,    self::OPT_CODE_LIST,    [self::class, 'defaultListStub']);

        $modeCountry = get_option(self::OPT_MODE_COUNTRY, 'default');
        $modeList    = get_option(self::OPT_MODE_LIST, 'default');
        ?>
        <div class="wrap">
          <h1>Country Templates</h1>
          <?php settings_errors('cp_templates'); ?>
          <form method="post">
            <?php settings_fields('cp_templates'); ?>
            <?php wp_nonce_field('cp_templates_nonce', 'cp_templates_nonce_field'); ?>

            <h2 class="title">Country (single)</h2>
            <p>
              <label for="cp_template_mode_country"><strong>Modo</strong></label><br/>
              <select name="<?php echo esc_attr(self::OPT_MODE_COUNTRY); ?>" id="cp_template_mode_country">
                <option value="default" <?php selected($modeCountry, 'default'); ?>>Default</option>
                <option value="custom"  <?php selected($modeCountry, 'custom'); ?>>Custom</option>
              </select>
            </p>
            <p><em>Arquivo editado:</em> <code><?php echo esc_html(self::FILE_COUNTRY); ?></code></p>
            <textarea id="cp-custom-country" name="cp_custom_country" style="width:100%;height:320px;"><?php echo esc_textarea($countryCode); ?></textarea>

            <hr/>

            <h2 class="title">List (lista de países)</h2>
            <p>
              <label for="cp_template_mode_list"><strong>Modo</strong></label><br/>
              <select name="<?php echo esc_attr(self::OPT_MODE_LIST); ?>" id="cp_template_mode_list">
                <option value="default" <?php selected($modeList, 'default'); ?>>Default</option>
                <option value="custom"  <?php selected($modeList, 'custom'); ?>>Custom</option>
              </select>
            </p>
            <p><em>Arquivo editado:</em> <code><?php echo esc_html(self::FILE_LIST); ?></code></p>
            <textarea id="cp-custom-list" name="cp_custom_list" style="width:100%;height:320px;"><?php echo esc_textarea($listCode); ?></textarea>

            <?php submit_button('Salvar'); ?>
          </form>
        </div>

        <script>
        (function(){
          if (!window.cpCodeEditorSettings || !wp || !wp.codeEditor) return;
          wp.codeEditor.initialize('cp-custom-country', window.cpCodeEditorSettings);
          wp.codeEditor.initialize('cp-custom-list', window.cpCodeEditorSettings);
        }());
        </script>
        <?php
    }

    private static function handle_post(): void {
        if (!current_user_can(self::CAPABILITY)) return;

        // modos : dependendo do que o usuário escolhei no admin
        update_option(self::OPT_MODE_COUNTRY, (isset($_POST[self::OPT_MODE_COUNTRY]) && $_POST[self::OPT_MODE_COUNTRY] === 'custom') ? 'custom' : 'default');
        update_option(self::OPT_MODE_LIST,    (isset($_POST[self::OPT_MODE_LIST])    && $_POST[self::OPT_MODE_LIST]    === 'custom') ? 'custom' : 'default');

        $countryCode = isset($_POST['cp_custom_country']) ? wp_unslash($_POST['cp_custom_country']) : '';
        $listCode    = isset($_POST['cp_custom_list'])    ? wp_unslash($_POST['cp_custom_list'])    : '';

        // tenta escrever nos arquivos do plugin
        $okFiles = self::write_files_atomically([
            self::file_path(self::FILE_COUNTRY) => $countryCode,
            self::file_path(self::FILE_LIST)    => $listCode,
        ]);

        if (!$okFiles) {
            update_option(self::OPT_CODE_COUNTRY, $countryCode);
            update_option(self::OPT_CODE_LIST,    $listCode);
            add_settings_error('cp_templates', 'saved_opt', 'FS somente-leitura: código salvo no banco e será usado via fallback.', 'warning');
        } else {
            delete_option(self::OPT_CODE_COUNTRY);
            delete_option(self::OPT_CODE_LIST);
            add_settings_error('cp_templates', 'saved', 'Templates atualizados no arquivo do plugin.', 'updated');
        }
    }

    private static function write_files_atomically(array $map): bool {
        if ( ! function_exists('request_filesystem_credentials') ) require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        foreach ($map as $path => $contents) {
            $dir = dirname($path);
            if (!file_exists($dir)) {
                return false;
            }
            if (!is_writable($path)) {
                // se o arquivo não for gravável, falha e ativa fallback
                return false;
            }
        }

        foreach ($map as $path => $contents) {
            // grava direto (arquivo já existe no repo)
            $ok = $wp_filesystem ? $wp_filesystem->put_contents($path, $contents) : (bool) file_put_contents($path, $contents);
            if (!$ok) return false;
        }
        return true;
    }

    private static function defaultCountryStub(): string {
        return <<<PHP
<?php
/** @var array \$country */
echo '<div class="country-card">';
echo '<h2>' . esc_html(\$country['name'] ?? '') . '</h2>';
if (!empty(\$country['capital'])) {
  echo '<p>Capital: ' . esc_html(\$country['capital']) . '</p>';
}
echo '</div>';
PHP;
    }

    private static function defaultListStub(): string {
        return <<<PHP
<?php
/** @var array \$countries */
echo '<ul class="country-list">';
foreach ((array) (\$countries ?? []) as \$c) {
  echo '<li>' . esc_html(\$c['name'] ?? '') . '</li>';
}
echo '</ul>';
PHP;
    }
}
