<?php
/** @var array $country */
?>

<div class="country-card">
  <h2><?= esc_html($country['title']) ?></h2>

  <?php if (!empty($country['excerpt'])): ?>
    <div class="excerpt">
      <?= wp_kses_post($country['excerpt']) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($country['acf'])): ?>
    <ul class="acf-fields">
      <?php foreach ($country['acf'] as $field): ?>
        <?php
          $key   = esc_html($field['key']);
          $value = $field['value'];
          $type  = $field['type'];
        ?>
        <li class="acf-field acf-<?= esc_attr($type) ?>">
          <strong><?= $key ?>:</strong>
          <?php if ($type === 'image' && $value): ?>
            <img src="<?= esc_url($value) ?>" alt="<?= $key ?>" style="max-width:100px;height:auto;">
          <?php elseif ($type === 'number'): ?>
            <?= esc_html((string) $value) ?>
          <?php elseif ($type === 'object'): ?>
            <pre><?= esc_html(print_r($value, true)) ?></pre>
          <?php else: ?>
            <?= esc_html((string) $value) ?>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
