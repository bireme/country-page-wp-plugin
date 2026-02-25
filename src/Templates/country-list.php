<?php
/** @var array $countries */
?>

<div class="country-list">
  <ul>
    <?php foreach ($countries as $country): ?>
      <li class="country-item">
        <h3><?= esc_html($country['title']) ?></h3>

        <?php if (!empty($country['acf'])): ?>
          <ul class="acf-summary">
            <?php foreach ($country['acf'] as $field): ?>
              <?php if (in_array($field['key'], ['region', 'capital', 'code'], true)): ?>
                <li>
                  <strong><?= esc_html($field['key']) ?>:</strong>
                  <?= esc_html((string) $field['value']) ?>
                </li>
              <?php endif; ?>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
