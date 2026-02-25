<?php
/**
 * Template customizado para exibição de lista de países
 * 
 * @var array $countries - Array com dados dos países
 * 
 * Cada item do array contém:
 * - $country['name'] - Nome do país
 * - $country['capital'] - Capital
 * - $country['population'] - População
 * - $country['flag'] - URL da bandeira
 * - Outros campos conforme configuração da API
 */

if (!defined('ABSPATH')) exit;
?>

<div class="custom-country-list">
    <?php if (!empty($countries) && is_array($countries)): ?>
        <ul class="countries-grid">
            <?php foreach ($countries as $country): ?>
                <li class="country-item">
                    <?php if (!empty($country['flag'])): ?>
                        <div class="country-flag-small">
                            <img src="<?php echo esc_url($country['flag']); ?>" alt="<?php echo esc_attr($country['name'] ?? ''); ?>" />
                        </div>
                    <?php endif; ?>
                    
                    <div class="country-info">
                        <h3 class="country-name"><?php echo esc_html($country['name'] ?? ''); ?></h3>
                        
                        <?php if (!empty($country['capital'])): ?>
                            <p class="country-capital"><?php echo esc_html($country['capital']); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="no-countries"><?php esc_html_e('Nenhum país encontrado.', 'country-pages'); ?></p>
    <?php endif; ?>
</div>
