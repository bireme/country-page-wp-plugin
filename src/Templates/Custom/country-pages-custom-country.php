<?php
/** @var array $country */

if (!defined('ABSPATH')) exit;

$cp_show_title = $cp_show_title ?? true;
$cp_show_image = $cp_show_image ?? true;
$cp_show_content = $cp_show_content ?? true;
$cp_show_excerpt = $cp_show_excerpt ?? true;
$cp_show_meta = $cp_show_meta ?? true;
$cp_display_mode = isset($cp_display_mode) ? (string) $cp_display_mode : 'default';
$cp_acf_mapping = (isset($cp_acf_mapping) && is_array($cp_acf_mapping)) ? $cp_acf_mapping : [];

$acfFields = (isset($country['acf']) && is_array($country['acf'])) ? $country['acf'] : [];
$tags = !empty($country['tags']) && is_array($country['tags']) ? $country['tags'] : [];
$title = (string) ($country['title'] ?? $country['name'] ?? '');
$excerpt = (string) ($country['excerpt'] ?? '');
$content = (string) ($country['content'] ?? '');
$featuredImage = (string) ($country['featured_image'] ?? '');

$resolveAcfImageUrl = static function ($value): string {
    if (is_array($value)) {
        if (!empty($value['url'])) {
            return (string) $value['url'];
        }
        foreach (['large', 'medium_large', 'medium', 'thumbnail'] as $size) {
            if (!empty($value['sizes'][$size])) {
                return (string) $value['sizes'][$size];
            }
        }
    }
    if (is_string($value) && $value !== '' && filter_var($value, FILTER_VALIDATE_URL)) {
        return $value;
    }
    return '';
};

$formatFieldLabel = static function (string $key, array $mapping): string {
    if ($key !== '' && isset($mapping[$key]) && is_array($mapping[$key])) {
        $label = trim((string) ($mapping[$key]['label'] ?? ''));
        if ($label !== '') {
            return $label;
        }
    }
    $label = trim(str_replace(['_', '-'], ' ', $key));
    return $label !== '' ? ucwords($label) : 'Campo';
};

$stringifyValue = static function ($value): string {
    if (is_bool($value)) {
        return $value ? 'Sim' : 'Nao';
    }
    if (is_scalar($value) || $value === null) {
        return trim((string) $value);
    }
    if (is_array($value)) {
        $parts = [];
        foreach ($value as $item) {
            if (is_bool($item)) {
                $parts[] = $item ? 'Sim' : 'Nao';
                continue;
            }
            if (is_scalar($item) || $item === null) {
                $itemString = trim((string) $item);
                if ($itemString !== '') {
                    $parts[] = $itemString;
                }
            }
        }
        return implode(', ', $parts);
    }
    return '';
};

$heroImage = '';
foreach ($acfFields as $field) {
    if (($field['type'] ?? '') === 'image' && ($field['key'] ?? '') === 'flag') {
        $heroImage = $resolveAcfImageUrl($field['value'] ?? null);
        if ($heroImage !== '') {
            break;
        }
    }
}
if ($heroImage === '') {
    foreach ($acfFields as $field) {
        if (($field['type'] ?? '') === 'image') {
            $heroImage = $resolveAcfImageUrl($field['value'] ?? null);
            if ($heroImage !== '') {
                break;
            }
        }
    }
}
if ($heroImage === '' && $featuredImage !== '') {
    $heroImage = $featuredImage;
}

$highlightFields = [];
$detailFields = [];

foreach ($acfFields as $field) {
    $key = (string) ($field['key'] ?? '');
    $type = (string) ($field['type'] ?? 'string');
    $value = $field['value'] ?? '';
    $label = $formatFieldLabel($key, $cp_acf_mapping);

    if ($type === 'image') {
        $imageUrl = $resolveAcfImageUrl($value);
        if ($imageUrl !== '') {
            $detailFields[] = [
                'label' => $label,
                'type' => 'image',
                'value' => $imageUrl,
            ];
        }
        continue;
    }

    if ($type === 'object') {
        $detailFields[] = [
            'label' => $label,
            'type' => 'object',
            'value' => $value,
        ];
        continue;
    }

    if (is_array($value) && $value !== []) {
        $detailFields[] = [
            'label' => $label,
            'type' => 'array',
            'value' => $value,
        ];
        continue;
    }

    $text = $stringifyValue($value);
    if ($text === '') {
        continue;
    }

    if (count($highlightFields) < 4) {
        $highlightFields[] = [
            'label' => $label,
            'value' => $text,
        ];
        continue;
    }

    $detailFields[] = [
        'label' => $label,
        'type' => 'text',
        'value' => $text,
    ];
}
?>

<article class="cp-country-showcase">
  <?php if ($cp_show_image && $heroImage !== ''): ?>
    <div class="cp-country-showcase__hero">
      <div class="cp-country-showcase__media">
        <img
          class="cp-country-showcase__image"
          src="<?= esc_url($heroImage) ?>"
          alt="<?= esc_attr($title) ?>"
          loading="lazy"
          decoding="async"
        >
      </div>

      <div class="cp-country-showcase__intro">
        <?php if ($cp_show_title): ?>
          <?php if ($cp_display_mode === 'default'): ?>
          <p class="cp-country-showcase__eyebrow"><?php esc_html_e('Country Profile', 'country-pages'); ?></p>
          <?php endif; ?>
          <h1 class="cp-country-showcase__title"><?= esc_html($title) ?></h1>
        <?php endif; ?>

        <?php if ($cp_show_excerpt && $excerpt !== ''): ?>
          <div class="cp-country-showcase__excerpt">
            <?= wp_kses_post($excerpt) ?>
          </div>
        <?php endif; ?>

        <?php if ($cp_show_meta && !empty($tags)): ?>
          <div class="cp-country-showcase__tags" aria-label="<?php esc_attr_e('Tags do país', 'country-pages'); ?>">
            <?php foreach ($tags as $tag): ?>
              <?php $tagName = (string) ($tag['name'] ?? ''); ?>
              <?php if ($tagName === '') continue; ?>
              <span class="cp-country-showcase__tag"><?= esc_html($tagName) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($cp_show_meta && !empty($highlightFields)): ?>
          <dl class="cp-country-showcase__highlights">
            <?php foreach ($highlightFields as $item): ?>
              <div class="cp-country-showcase__highlight">
                <dt><?= esc_html($item['label']) ?></dt>
                <dd><?= esc_html($item['value']) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <header class="cp-country-showcase__header">
      <?php if ($cp_show_title): ?>
        <?php if ($cp_display_mode === 'default'): ?>
        <p class="cp-country-showcase__eyebrow"><?php esc_html_e('Country Profile', 'country-pages'); ?></p>
        <?php endif; ?>
        <h1 class="cp-country-showcase__title"><?= esc_html($title) ?></h1>
      <?php endif; ?>

      <?php if ($cp_show_excerpt && $excerpt !== ''): ?>
        <div class="cp-country-showcase__excerpt">
          <?= wp_kses_post($excerpt) ?>
        </div>
      <?php endif; ?>

      <?php if ($cp_show_meta && !empty($tags)): ?>
        <div class="cp-country-showcase__tags" aria-label="<?php esc_attr_e('Tags do país', 'country-pages'); ?>">
          <?php foreach ($tags as $tag): ?>
            <?php $tagName = (string) ($tag['name'] ?? ''); ?>
            <?php if ($tagName === '') continue; ?>
            <span class="cp-country-showcase__tag"><?= esc_html($tagName) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($cp_show_meta && !empty($highlightFields)): ?>
        <dl class="cp-country-showcase__highlights">
          <?php foreach ($highlightFields as $item): ?>
            <div class="cp-country-showcase__highlight">
              <dt><?= esc_html($item['label']) ?></dt>
              <dd><?= esc_html($item['value']) ?></dd>
            </div>
          <?php endforeach; ?>
        </dl>
      <?php endif; ?>
    </header>
  <?php endif; ?>

  <?php if ($cp_show_content && $content !== ''): ?>
    <section class="cp-country-showcase__section cp-country-showcase__section--story">
      <div class="cp-country-showcase__section-heading">
        <span class="cp-country-showcase__section-index">01</span>
        <h2><?php esc_html_e('Visao geral', 'country-pages'); ?></h2>
      </div>
      <div class="cp-country-showcase__prose">
        <?= wp_kses_post($content) ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($cp_show_meta && !empty($acfFields)): ?>
    <section class="cp-country-showcase__section cp-country-showcase__section--details">
      <div class="cp-country-showcase__section-heading">
        <span class="cp-country-showcase__section-index">02</span>
        <h2><?php esc_html_e('Dados complementares', 'country-pages'); ?></h2>
      </div>

      <?php if (!empty($detailFields)): ?>
        <div class="cp-country-showcase__details-grid">
          <?php foreach ($detailFields as $field): ?>
            <article class="cp-country-showcase__detail-card">
              <h3><?= esc_html($field['label']) ?></h3>
              <?php if ($field['type'] === 'image'): ?>
                <img
                  class="cp-country-showcase__detail-image"
                  src="<?= esc_url((string) $field['value']) ?>"
                  alt="<?= esc_attr($field['label']) ?>"
                  loading="lazy"
                  decoding="async"
                >
              <?php elseif ($field['type'] === 'object' || $field['type'] === 'array'): ?>
                <pre class="cp-country-showcase__code"><?= esc_html(wp_json_encode($field['value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
              <?php else: ?>
                <p><?= esc_html((string) $field['value']) ?></p>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="cp-country-showcase__empty"><?php esc_html_e('Nenhum dado complementar disponivel para este pais.', 'country-pages'); ?></p>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</article>
