<?php
namespace CP\Admin;

use CP\Support\Logger;

if (!defined('ABSPATH')) exit;

final class LogsPage {
    private const CAPABILITY = 'manage_options';
    private const MENU_SLUG = 'country-pages-logs';

    public function register(): void {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_init', [$this, 'handleActions']);
    }

    public function addMenu(): void {
        add_submenu_page(
            'country-pages',
            __('Logs', 'country-pages'),
            __('Logs', 'country-pages'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderPage']
        );
    }

    public function enqueueAssets(string $hook): void {
        if ($hook !== 'country-pages_page_' . self::MENU_SLUG) {
            return;
        }
        wp_enqueue_style('cp-admin', \CP_PLUGIN_URL . 'src/Assets/admin.css', [], \CP_VERSION);
    }

    public function handleActions(): void {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }
        if (!isset($_POST['cp_logs_action']) || $_POST['cp_logs_action'] !== 'clear') {
            return;
        }
        check_admin_referer('cp_clear_logs');
        Logger::clear();
        wp_safe_redirect(add_query_arg(['page' => self::MENU_SLUG, 'cp_logs_cleared' => '1'], admin_url('admin.php')));
        exit;
    }

    public function renderPage(): void {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }
        $logs = array_reverse(Logger::all());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Logs do Country Pages', 'country-pages'); ?></h1>

            <?php if (isset($_GET['cp_logs_cleared'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Logs removidos com sucesso.', 'country-pages'); ?></p>
                </div>
            <?php endif; ?>

            <p><?php esc_html_e('Chamadas, avisos e erros do plugin ficam registrados aqui. Ao salvar um novo log, registros com mais de 5 dias sao removidos automaticamente.', 'country-pages'); ?></p>

            <form method="post" style="margin: 1rem 0;">
                <?php wp_nonce_field('cp_clear_logs'); ?>
                <input type="hidden" name="cp_logs_action" value="clear" />
                <?php submit_button(__('Limpar logs', 'country-pages'), 'secondary', 'submit', false); ?>
            </form>

            <?php if ($logs === []): ?>
                <div class="notice notice-info inline">
                    <p><?php esc_html_e('Nenhum log registrado ate o momento.', 'country-pages'); ?></p>
                </div>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Data', 'country-pages'); ?></th>
                            <th scope="col"><?php esc_html_e('Nivel', 'country-pages'); ?></th>
                            <th scope="col"><?php esc_html_e('Mensagem', 'country-pages'); ?></th>
                            <th scope="col"><?php esc_html_e('Contexto', 'country-pages'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $entry): ?>
                            <tr>
                                <td>
                                    <?php
                                    $timestamp = (int) ($entry['timestamp'] ?? 0);
                                    echo esc_html($timestamp > 0 ? wp_date('d/m/Y H:i:s', $timestamp) : ($entry['created_at'] ?? '-'));
                                    ?>
                                </td>
                                <td><?php echo esc_html(strtoupper((string) ($entry['level'] ?? 'info'))); ?></td>
                                <td><?php echo esc_html((string) ($entry['message'] ?? '')); ?></td>
                                <td>
                                    <?php
                                    $context = $entry['context'] ?? [];
                                    if (is_array($context) && $context !== []) {
                                        echo '<pre style="white-space:pre-wrap;margin:0;">' . esc_html(wp_json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</pre>';
                                    } else {
                                        echo esc_html('-');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}
