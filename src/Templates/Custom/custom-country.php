<?php
/** @var array $country */

if (!defined('ABSPATH')) exit;
?>

<?php
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
    if (is_array($value) && !empty($value['url'])) {
        return (string) $value['url'];
    }
    if (is_string($value) && $value !== '' && filter_var($value, FILTER_VALIDATE_URL)) {
        return $value;
    }
    return is_string($value) ? trim($value) : '';
};

$displayableAcfFields = [];
foreach ($acfFields as $field) {
    $type = (string) ($field['type'] ?? 'string');
    $value = $field['value'] ?? '';

    if ($type === 'image') {
        if ($resolveAcfImageUrl($value) === '') {
            continue;
        }
        $displayableAcfFields[] = $field;
        continue;
    }

    if ($type === 'object') {
        if (empty($value)) {
            continue;
        }
        $displayableAcfFields[] = $field;
        continue;
    }

    if (is_array($value)) {
        if ($value === []) {
            continue;
        }
        $displayableAcfFields[] = $field;
        continue;
    }

    if (trim((string) $value) === '') {
        continue;
    }

    $displayableAcfFields[] = $field;
}

$heroImage = '';
foreach ($acfFields as $field) {
    if (($field['type'] ?? '') === 'image' && ($field['key'] ?? '') === 'flag' && !empty($field['value'])) {
        $heroImage = (string) $field['value'];
        break;
    }
}
if ($heroImage === '') {
    foreach ($acfFields as $field) {
        if (($field['type'] ?? '') === 'image' && !empty($field['value'])) {
            $value = $field['value'];
            if (is_array($value) && !empty($value['url'])) {
                $heroImage = (string) $value['url'];
            } else {
                $heroImage = (string) $value;
            }
            break;
        }
    }
}
if ($heroImage === '' && $featuredImage !== '') {
    $heroImage = $featuredImage;
}
?>

<div class="cp-card custom-country-card">
    <?php if ($cp_show_image && $heroImage !== ''): ?>
    <header class="cp-card__header">
        <img class="cp-card__flag" src="<?= esc_url($heroImage) ?>" alt="<?= esc_attr($title) ?>">
        <?php if ($cp_show_title): ?>
        <h2 class="cp-card__title"><?= esc_html($title) ?></h2>
        <?php endif; ?>
        <?php if ($cp_show_meta && !empty($tags)): ?>
        <div class="cp-card__tags" aria-label="<?php esc_attr_e('Tags do país', 'country-pages'); ?>">
            <?php foreach ($tags as $tag): ?>
                <?php $tagName = (string) ($tag['name'] ?? ''); ?>
                <?php if ($tagName === '') continue; ?>
                <span class="cp-card__tag"><?= esc_html($tagName) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </header>
    <?php elseif ($cp_show_title): ?>
    <header class="cp-card__header">
        <h2 class="cp-card__title"><?= esc_html($title) ?></h2>
        <?php if ($cp_show_meta && !empty($tags)): ?>
        <div class="cp-card__tags" aria-label="<?php esc_attr_e('Tags do país', 'country-pages'); ?>">
            <?php foreach ($tags as $tag): ?>
                <?php $tagName = (string) ($tag['name'] ?? ''); ?>
                <?php if ($tagName === '') continue; ?>
                <span class="cp-card__tag"><?= esc_html($tagName) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </header>
    <?php endif; ?>

    <?php if ($cp_show_excerpt && $excerpt !== ''): ?>
        <div class="excerpt">
            <?= wp_kses_post($excerpt) ?>
        </div>
    <?php endif; ?>

    <?php if ($cp_show_content && $content !== ''): ?>
        <div class="cp-card__content">
            <?= wp_kses_post($content) ?>
        </div>
    <?php endif; ?>

    <?php if ($cp_show_meta && !empty($displayableAcfFields)): ?>
        <div class="cp-card__mapped-fields<?= !empty($cp_acf_mapping) ? ' cp-card__mapped-fields--from-mapping' : '' ?>">
        <ul class="cp-card__meta cp-card__meta--mapped">
            <?php foreach ($displayableAcfFields as $field): ?>
                <?php
                    $key   = (string) ($field['key'] ?? '');
                    $value = $field['value'] ?? '';
                    $type  = (string) ($field['type'] ?? 'string');
                    $isTextual = ($type === 'string' || $type === 'number');
                ?>
                <?php if ($isTextual): ?>
                <li class="cp-card__meta-item cp-card__meta-item--badges acf-field acf-<?= esc_attr($type) ?>">
                    <span class="cp-card__badge cp-card__badge--key"><?= esc_html($key) ?></span>
                    <span class="cp-card__badge cp-card__badge--value"><?= esc_html((string) $value) ?></span>
                </li>
                <?php elseif ($type === 'image'): ?>
                    <?php
                        $imageUrl = $resolveAcfImageUrl($value);
                    ?>
                    <?php if ($imageUrl !== ''): ?>
                <li class="cp-card__meta-item cp-card__meta-item--media acf-field acf-image">
                    <span class="cp-card__badge cp-card__badge--key"><?= esc_html($key) ?></span>
                    <div class="cp-card__value-block cp-card__value-block--media">
                        <span class="cp-card__meta-value">
                            <img class="cp-card__meta-thumb" src="<?= esc_url($imageUrl) ?>" alt="<?= esc_attr($key) ?>">
                        </span>
                    </div>
                </li>
                    <?php endif; ?>
                <?php elseif ($type === 'object'): ?>
                <li class="cp-card__meta-item cp-card__meta-item--object acf-field acf-object">
                    <span class="cp-card__badge cp-card__badge--key"><?= esc_html($key) ?></span>
                    <div class="cp-card__value-block cp-card__value-block--object">
                        <pre class="cp-card__meta-pre"><?= esc_html(print_r($value, true)) ?></pre>
                    </div>
                </li>
                <?php else: ?>
                <li class="cp-card__meta-item cp-card__meta-item--badges acf-field acf-string">
                    <span class="cp-card__badge cp-card__badge--key"><?= esc_html($key) ?></span>
                    <span class="cp-card__badge cp-card__badge--value"><?= esc_html((string) $value) ?></span>
                </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        </div>
    <?php endif; ?>
</div>
