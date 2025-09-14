<?php
/** @var array $country */
if (!defined('ABSPATH')) exit;
?>
<article class="cp-card" itemscope itemtype="https://schema.org/Country">
  <header class="cp-card__header">
    <?php if (!empty($country['flag_url'])): ?>
      <img class="cp-card__flag" src="<?php echo esc_url($ z['flag_url']); ?>" alt="<?php echo esc_attr($country['title']); ?>">
    <?php endif; ?>
    <h2 class="cp-card__title" itemprop="name"><?php echo esc_html($country['title']); ?></h2>
  </header>

  <ul class="cp-card__meta">
    <?php if (!empty($country['code'])): ?>
      <li><strong>ISO:</strong> <?php echo esc_html($country['code']); ?></li>
    <?php endif; ?>
    <?php if (!empty($country['capital'])): ?>
      <li><strong><?php echo esc_html__('Capital', 'country-pages'); ?>:</strong> <?php echo esc_html($country['capital']); ?></li>
    <?php endif; ?>
    <?php if (!empty($country['region'])): ?>
      <li><strong><?php echo esc_html__('Região', 'country-pages'); ?>:</strong> <?php echo esc_html($country['region']); ?></li>
    <?php endif; ?>
  </ul>

  <?php if (!empty($country['content'])): ?>
    <div class="cp-card__content"><?php echo wp_kses_post($country['content']); ?></div>
  <?php elseif (!empty($country['excerpt'])): ?>
    <div class="cp-card__excerpt"><?php echo wp_kses_post($country['excerpt']); ?></div>
  <?php endif; ?>

  <?php if (!empty($country['link'])): ?>
    <p><a class="cp-button" href="<?php echo esc_url($country['link']); ?>"><?php echo esc_html__('Saiba mais', 'country-pages'); ?></a></p>
  <?php endif; ?>
</article>
