<?php
/** @var array<int, array<string, mixed>> $countries */
/** @var string $slider_uid */
/** @var int $slider_visible */
/** @var bool $slider_loop */

if (!defined('ABSPATH')) {
    exit;
}

$countries = isset($countries) && is_array($countries) ? $countries : [];
$slider_uid = isset($slider_uid) ? (string) $slider_uid : 'cp-slider';
$slider_visible = isset($slider_visible) ? max(1, (int) $slider_visible) : 3;
$slider_loop = !empty($slider_loop);
$loopAttr = $slider_loop ? '1' : '0';
$count = count($countries);

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

$excerptForCard = static function (array $country): string {
    $raw = (string) ($country['excerpt'] ?? '');
    $plain = wp_strip_all_tags($raw);
    if ($plain !== '') {
        return wp_trim_words($plain, 22, '…');
    }
    $content = wp_strip_all_tags((string) ($country['content'] ?? ''));
    return $content !== '' ? wp_trim_words($content, 22, '…') : '';
};
?>

<?php if ($count === 0): ?>
  <p class="cp-country-slider__empty"><?php esc_html_e('Nenhum país encontrado.', 'country-pages'); ?></p>
<?php else: ?>
  <section class="cp-country-spotlight">
    <header class="cp-country-spotlight__header">
      <div>
        <p class="cp-country-spotlight__eyebrow"><?php esc_html_e('Country Highlights', 'country-pages'); ?></p>
        <h2 class="cp-country-spotlight__title"><?php esc_html_e('Carrossel editorial de destinos', 'country-pages'); ?></h2>
      </div>
      <p class="cp-country-spotlight__meta">
        <?php
        echo esc_html(
            sprintf(
                /* translators: %d: number of countries */
                __('%d países em destaque', 'country-pages'),
                $count
            )
        );
        ?>
      </p>
    </header>

    <div
      id="<?= esc_attr($slider_uid) ?>"
      class="cp-country-slider cp-country-slider--spotlight"
      data-cp-slider
      data-visible="<?= (int) $slider_visible ?>"
      data-loop="<?= esc_attr($loopAttr) ?>"
      role="region"
      aria-roledescription="<?php esc_attr_e('Carrossel', 'country-pages'); ?>"
      aria-label="<?php esc_attr_e('Países em destaque', 'country-pages'); ?>"
    >
      <div class="cp-country-slider__viewport">
        <div class="cp-country-slider__track" data-cp-slider-track>
          <?php foreach ($countries as $country): ?>
            <?php
            $title = (string) ($country['title'] ?? '');
            $featured = (string) ($country['featured_image'] ?? '');
            $cover = $featured;
            if ($cover === '' && !empty($country['acf']) && is_array($country['acf'])) {
                foreach ($country['acf'] as $field) {
                    if (($field['type'] ?? '') === 'image' && ($field['key'] ?? '') === 'flag') {
                        $cover = $resolveAcfImageUrl($field['value'] ?? null);
                        if ($cover !== '') {
                            break;
                        }
                    }
                }
                if ($cover === '') {
                    foreach ($country['acf'] as $field) {
                        if (($field['type'] ?? '') === 'image') {
                            $cover = $resolveAcfImageUrl($field['value'] ?? null);
                            if ($cover !== '') {
                                break;
                            }
                        }
                    }
                }
            }
            $excerpt = $excerptForCard($country);
            $tags = !empty($country['tags']) && is_array($country['tags']) ? $country['tags'] : [];
            $itemSlug = (string) ($country['slug'] ?? '');
            $localUrl = \CP\Front\CountryPageRoute::permalinkForSlug($itemSlug);
            $viewUrl = (string) apply_filters('cp_country_list_item_url', $localUrl, $country);
            if ($viewUrl === '') {
                $viewUrl = '#';
            }
            ?>
            <div class="cp-country-slider__slide cp-country-slider__slide--spotlight" role="group" aria-roledescription="<?php esc_attr_e('Slide', 'country-pages'); ?>">
              <article class="cp-country-spotlight-card">
                <a class="cp-country-spotlight-card__link" href="<?= esc_url($viewUrl) ?>">
                  <div class="cp-country-spotlight-card__media">
                    <?php if ($cover !== ''): ?>
                      <img
                        class="cp-country-spotlight-card__image"
                        src="<?= esc_url($cover) ?>"
                        alt="<?= esc_attr($title) ?>"
                        loading="lazy"
                        decoding="async"
                      >
                    <?php else: ?>
                      <div class="cp-country-spotlight-card__placeholder" aria-hidden="true"></div>
                    <?php endif; ?>
                  </div>

                  <div class="cp-country-spotlight-card__body">
                    <h3 class="cp-country-spotlight-card__title"><?= esc_html($title) ?></h3>

                    <?php if (!empty($tags)): ?>
                      <div class="cp-country-spotlight-card__tags" aria-label="<?php esc_attr_e('Tags do país', 'country-pages'); ?>">
                        <?php foreach ($tags as $tag): ?>
                          <?php $tagName = (string) ($tag['name'] ?? ''); ?>
                          <?php if ($tagName === '') continue; ?>
                          <span class="cp-country-spotlight-card__tag"><?= esc_html($tagName) ?></span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>

                    <?php if ($excerpt !== ''): ?>
                      <p class="cp-country-spotlight-card__excerpt"><?= esc_html($excerpt) ?></p>
                    <?php endif; ?>

                    <span class="cp-country-spotlight-card__cta"><?php esc_html_e('Explorar país', 'country-pages'); ?></span>
                  </div>
                </a>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <?php
      $numPages = (int) max(1, ceil($count / $slider_visible));
      $showNav = $numPages > 1;
      ?>
      <?php if ($showNav): ?>
        <div class="cp-country-slider__nav cp-country-slider__nav--spotlight" data-cp-slider-nav>
          <button
            type="button"
            class="cp-country-slider__btn cp-country-slider__btn--prev"
            data-cp-slider-prev
            aria-controls="<?= esc_attr($slider_uid) ?>"
            aria-label="<?php esc_attr_e('Slide anterior', 'country-pages'); ?>"
          >
            <span aria-hidden="true">‹</span>
          </button>
          <button
            type="button"
            class="cp-country-slider__btn cp-country-slider__btn--next"
            data-cp-slider-next
            aria-controls="<?= esc_attr($slider_uid) ?>"
            aria-label="<?php esc_attr_e('Próximo slide', 'country-pages'); ?>"
          >
            <span aria-hidden="true">›</span>
          </button>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
