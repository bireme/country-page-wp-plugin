<?php
namespace CP\Admin;

if (!defined('ABSPATH')) exit;

class AcfMappingPage {
    const CAPABILITY = 'manage_options';
    const OPTION_KEY = 'cp_acf_mapping';

    public static function boot(): void {
        add_action('admin_menu', [self::class, 'menu']);
    }

    public static function menu(): void {
        add_submenu_page(
            'options-general.php',
            'ACF Mapping',
            'ACF Mapping',
            self::CAPABILITY,
            'cp-acf-mapping',
            [self::class, 'render_page']
        );
    }

    public static function render_page(): void {
        if (!current_user_can(self::CAPABILITY)) wp_die('Sem permissão.');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('cp_acf_mapping')) {
            $mapping = [];
            if (!empty($_POST['acf_key']) && is_array($_POST['acf_key'])) {
                foreach ($_POST['acf_key'] as $i => $key) {
                    $key   = sanitize_text_field($key);
                    $type  = sanitize_text_field($_POST['acf_type'][$i] ?? 'string');
                    if ($key) {
                        $mapping[$key] = $type;
                    }
                }
            }
            update_option(self::OPTION_KEY, $mapping);
            echo '<div class="updated"><p>Mapeamento salvo.</p></div>';
        }

        $mapping = get_option(self::OPTION_KEY, []);
        ?>
        <div class="wrap">
          <h1>Mapeamento de ACF</h1>
          <form method="post">
            <?php wp_nonce_field('cp_acf_mapping'); ?>
            <table class="widefat">
              <thead>
                <tr><th>Campo</th><th>Tipo</th></tr>
              </thead>
              <tbody id="acf-mapping-rows">
                <?php foreach ($mapping as $key => $type): ?>
                  <tr>
                    <td><input type="text" name="acf_key[]" value="<?php echo esc_attr($key); ?>" /></td>
                    <td>
                      <select name="acf_type[]">
                        <option value="string" <?php selected($type, 'string'); ?>>String</option>
                        <option value="number" <?php selected($type, 'number'); ?>>Number</option>
                        <option value="image" <?php selected($type, 'image'); ?>>Image</option>
                        <option value="object" <?php selected($type, 'object'); ?>>Object</option>
                      </select>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr>
                  <td><input type="text" name="acf_key[]" value="" placeholder="novo campo"/></td>
                  <td>
                    <select name="acf_type[]">
                      <option value="string">String</option>
                      <option value="number">Number</option>
                      <option value="image">Image</option>
                      <option value="object">Object</option>
                    </select>
                  </td>
                </tr>
              </tbody>
            </table>
            <p><?php submit_button('Salvar Mapeamento'); ?></p>
          </form>
        </div>
        <?php
    }
}
