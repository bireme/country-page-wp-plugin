<?php
/** @var array $countries */
/** @var array $args */
if (!defined('ABSPATH')) exit;
?>
<section class="cp-grid">
  <?php if (empty($countries)): ?>
    <p><?php echo esc_html__('Nenhum país encontrado.', 'country-pages'); ?></p>
  <?php else: ?>
    <?php foreach ($countries as $country): ?>
      <article class="cp-grid__item">
        <a href="<?php echo esc_url($country['link'] ?: '#'); ?>" class="cp-grid__card">
          <?php if (!empty($country['flag_url'])): ?>
            <img class="cp-grid__flag" src="<?php echo esc_url($country['flag_url']); ?>" alt="<?php echo esc_attr($country['title']); ?>">
          <?php endif; ?>
          <h3 class="cp-grid__title"><?php echo esc_html($country['title']); ?></h3>
          <?php if (!empty($country['capital'])): ?>
            <p class="cp-grid__subtitle"><?php echo esc_html__('Capital', 'country-pages'); ?>: <?php echo esc_html($country['capital']); ?></p>
          <?php endif; ?>
        </a>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
