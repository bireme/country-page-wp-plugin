<?php
/**
 * Template customizado para exibição de país individual
 * 
 * @var array $country - Dados do país
 * 
 * Campos disponíveis:
 * - $country['name'] - Nome do país
 * - $country['capital'] - Capital
 * - $country['population'] - População
 * - $country['flag'] - URL da bandeira
 * - Outros campos conforme configuração da API
 */

if (!defined('ABSPATH')) exit;
?>

<div class="custom-country-card">
    <h2 class="country-name"><?php echo esc_html($country['name'] ?? ''); ?></h2>
    
    <?php if (!empty($country['flag'])): ?>
        <div class="country-flag">
            <img src="<?php echo esc_url($country['flag']); ?>" alt="<?php echo esc_attr($country['name'] ?? ''); ?>" />
        </div>
    <?php endif; ?>
    
    <?php if (!empty($country['capital'])): ?>
        <p class="country-capital">
            <strong><?php esc_html_e('Capital:', 'country-pages'); ?></strong> 
            <?php echo esc_html($country['capital']); ?>
        </p>
    <?php endif; ?>
    
    <?php if (!empty($country['population'])): ?>
        <p class="country-population">
            <strong><?php esc_html_e('População:', 'country-pages'); ?></strong> 
            <?php echo esc_html(number_format($country['population'])); ?>
        </p>
    <?php endif; ?>
</div>
