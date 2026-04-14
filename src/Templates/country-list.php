<?php
/** @var array<int, array<string, mixed>> $countries */
/** @var array<string, mixed> $list_pagination */
/** @var array<string, mixed> $list_filters */

if (!defined('ABSPATH')) {
    exit;
}

$list_pagination = isset($list_pagination) && is_array($list_pagination) ? $list_pagination : [];
$list_filters = isset($list_filters) && is_array($list_filters) ? $list_filters : [];

$pv = (string) ($list_pagination['query_var'] ?? 'cp_cl_page');
$currentPage = max(1, (int) ($list_pagination['current_page'] ?? 1));
$totalPages = max(1, (int) ($list_pagination['total_pages'] ?? 1));
$totalItems = (int) ($list_pagination['total_items'] ?? 0);

$fkSearch = (string) ($list_filters['key_search'] ?? 'cp_cl_search');
$fkTag = (string) ($list_filters['key_tag'] ?? 'cp_cl_tag');
$fkCat = (string) ($list_filters['key_category'] ?? 'cp_cl_cat');
$filterSearch = (string) ($list_filters['search'] ?? '');
$filterTagId = (int) ($list_filters['tag_id'] ?? 0);
$filterCatId = (int) ($list_filters['category_id'] ?? 0);
$filterTags = isset($list_filters['tags']) && is_array($list_filters['tags']) ? $list_filters['tags'] : [];
$filterCategories = isset($list_filters['categories']) && is_array($list_filters['categories']) ? $list_filters['categories'] : [];
$clearFiltersUrl = isset($list_filters['clear_url']) ? (string) $list_filters['clear_url'] : remove_query_arg([$pv, $fkSearch, $fkTag, $fkCat]);
$formAction = isset($list_filters['form_action']) && $list_filters['form_action'] !== ''
    ? (string) $list_filters['form_action']
    : home_url('/');

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

$list_page_url = static function (int $n) use ($pv): string {
    $clean = remove_query_arg($pv);
    if ($n <= 1) {
        return $clean;
    }
    return add_query_arg($pv, $n, $clean);
};

$excerpt_for_card = static function (array $country): string {
    $raw = (string) ($country['excerpt'] ?? '');
    $plain = wp_strip_all_tags($raw);
    if ($plain !== '') {
        return wp_trim_words($plain, 26, '…');
    }
    $content = wp_strip_all_tags((string) ($country['content'] ?? ''));
    return $content !== '' ? wp_trim_words($content, 26, '…') : '';
};

$pagination_numbers = static function (int $current, int $total): array {
    if ($total <= 1) {
        return [];
    }
    if ($total <= 9) {
        return range(1, $total);
    }
    $nums = [1];
    $lo = max(2, $current - 2);
    $hi = min($total - 1, $current + 2);
    for ($i = $lo; $i <= $hi; $i++) {
        $nums[] = $i;
    }
    if ($total > 1) {
        $nums[] = $total;
    }
    $nums = array_values(array_unique(array_filter($nums, static fn ($n) => $n >= 1 && $n <= $total)));
    sort($nums);
    $out = [];
    $prev = 0;
    foreach ($nums as $p) {
        if ($prev > 0 && $p - $prev > 1) {
            $out[] = 0;
        }
        $out[] = $p;
        $prev = $p;
    }
    return $out;
};

$hasActiveFilters = $filterSearch !== '' || $filterTagId > 0 || $filterCatId > 0;
?>

<div class="cp-country-list-layout">
  <aside class="cp-country-list__sidebar" aria-label="<?php esc_attr_e('Filtros da lista', 'country-pages'); ?>">
    <div class="cp-country-list__filters-card">
      <h2 class="cp-country-list__filters-title"><?php esc_html_e('Filtros', 'country-pages'); ?></h2>
      <form class="cp-country-list__filters" method="get" action="<?= esc_url($formAction) ?>">
        <div class="cp-country-list__field">
          <label class="cp-country-list__label" for="cp-cl-search"><?php esc_html_e('Busca', 'country-pages'); ?></label>
          <input
            type="search"
            id="cp-cl-search"
            class="cp-country-list__input"
            name="<?= esc_attr($fkSearch) ?>"
            value="<?= esc_attr($filterSearch) ?>"
            placeholder="<?php esc_attr_e('Digite para buscar…', 'country-pages'); ?>"
            autocomplete="off"
          >
        </div>
        <div class="cp-country-list__field">
          <label class="cp-country-list__label" for="cp-cl-tag"><?php esc_html_e('Tag', 'country-pages'); ?></label>
          <select class="cp-country-list__select" id="cp-cl-tag" name="<?= esc_attr($fkTag) ?>">
            <option value=""><?php esc_html_e('Todas as tags', 'country-pages'); ?></option>
            <?php foreach ($filterTags as $t): ?>
              <?php
                $tid = (int) ($t['id'] ?? 0);
                if ($tid < 1) {
                    continue;
                }
                $tname = (string) ($t['name'] ?? '');
              ?>
              <option value="<?= (int) $tid ?>"<?php selected($filterTagId, $tid); ?>><?= esc_html($tname) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="cp-country-list__field">
          <label class="cp-country-list__label" for="cp-cl-cat"><?php esc_html_e('Categoria', 'country-pages'); ?></label>
          <select class="cp-country-list__select" id="cp-cl-cat" name="<?= esc_attr($fkCat) ?>">
            <option value=""><?php esc_html_e('Todas as categorias', 'country-pages'); ?></option>
            <?php foreach ($filterCategories as $c): ?>
              <?php
                $cid = (int) ($c['id'] ?? 0);
                if ($cid < 1) {
                    continue;
                }
                $cname = (string) ($c['name'] ?? '');
              ?>
              <option value="<?= (int) $cid ?>"<?php selected($filterCatId, $cid); ?>><?= esc_html($cname) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="cp-country-list__field cp-country-list__field--actions">
          <button type="submit" class="cp-country-list__submit"><?php esc_html_e('Aplicar filtros', 'country-pages'); ?></button>
          <?php if ($hasActiveFilters): ?>
            <a class="cp-country-list__clear" href="<?= esc_url($clearFiltersUrl) ?>"><?php esc_html_e('Limpar', 'country-pages'); ?></a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </aside>

  <div class="cp-country-list__main">
    <div class="cp-country-list">
      <?php if (empty($countries)): ?>
        <p class="cp-country-list__empty"><?php esc_html_e('Nenhum país encontrado.', 'country-pages'); ?></p>
      <?php else: ?>
        <div class="cp-country-list__grid">
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
              $excerpt = $excerpt_for_card($country);
              $tags = !empty($country['tags']) && is_array($country['tags']) ? $country['tags'] : [];
              $itemSlug = (string) ($country['slug'] ?? '');
              $localUrl = \CP\Front\CountryPageRoute::permalinkForSlug($itemSlug);
              $viewUrl = (string) apply_filters('cp_country_list_item_url', $localUrl, $country);
          if ($viewUrl === '') {
              $viewUrl = '#';
          }
            ?>
            <article class="cp-country-card">
              <div class="cp-country-card__media">
                <?php if ($cover !== ''): ?>
                  <img
                    class="cp-country-card__img"
                    src="<?= esc_url($cover) ?>"
                    alt="<?= esc_attr($title) ?>"
                    loading="lazy"
                    decoding="async"
                  >
                <?php else: ?>
                  <div class="cp-country-card__placeholder" aria-hidden="true"></div>
                <?php endif; ?>
              </div>
              <div class="cp-country-card__body">
                <h3 class="cp-country-card__title"><?= esc_html($title) ?></h3>
                <?php if (!empty($tags)): ?>
                  <div class="cp-country-card__tags" aria-label="<?php esc_attr_e('Tags do país', 'country-pages'); ?>">
                    <?php foreach ($tags as $tag): ?>
                      <?php $tagName = (string) ($tag['name'] ?? ''); ?>
                      <?php if ($tagName === '') continue; ?>
                      <span class="cp-country-card__tag"><?= esc_html($tagName) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <?php if ($excerpt !== ''): ?>
                  <p class="cp-country-card__excerpt"><?= esc_html($excerpt) ?></p>
                <?php endif; ?>
                <div class="cp-country-card__footer">
                  <a
                    class="cp-country-card__btn"
                    href="<?= esc_url($viewUrl) ?>"
                    <?php echo $viewUrl !== '#' ? ' rel="noopener noreferrer"' : ''; ?>
                  >
                    <?php esc_html_e('Visualizar', 'country-pages'); ?>
                  </a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
          <?php
            $nums = $pagination_numbers($currentPage, $totalPages);
            $prevUrl = $currentPage > 1 ? $list_page_url($currentPage - 1) : '';
            $nextUrl = $currentPage < $totalPages ? $list_page_url($currentPage + 1) : '';
          ?>
          <nav class="cp-country-list__pagination" aria-label="<?php esc_attr_e('Paginação da lista de países', 'country-pages'); ?>">
            <p class="cp-country-list__pagination-meta">
              <?php
                echo esc_html(
                    sprintf(
                        /* translators: 1: current page number, 2: total pages */
                        __('Página %1$d de %2$d', 'country-pages'),
                        $currentPage,
                        $totalPages
                    )
                );
              ?>
              <?php if ($totalItems > 0): ?>
                <span class="cp-country-list__pagination-count">
                  <?php
                    echo ' ' . esc_html(
                        sprintf(
                            /* translators: %d: total number of items */
                            __('(%d itens)', 'country-pages'),
                            $totalItems
                        )
                    );
                  ?>
                </span>
              <?php endif; ?>
            </p>
            <ul class="cp-country-list__pagination-list">
              <li>
                <?php if ($prevUrl !== ''): ?>
                  <a class="cp-country-list__page cp-country-list__page--nav" href="<?= esc_url($prevUrl) ?>" rel="prev">
                    <?php esc_html_e('Anterior', 'country-pages'); ?>
                  </a>
                <?php else: ?>
                  <span class="cp-country-list__page cp-country-list__page--nav cp-country-list__page--disabled"><?php esc_html_e('Anterior', 'country-pages'); ?></span>
                <?php endif; ?>
              </li>
              <?php foreach ($nums as $n): ?>
                <li>
                  <?php if ($n === 0): ?>
                    <span class="cp-country-list__ellipsis" aria-hidden="true">…</span>
                  <?php elseif ($n === $currentPage): ?>
                    <span class="cp-country-list__page cp-country-list__page--current" aria-current="page"><?= (int) $n ?></span>
                  <?php else: ?>
                    <a class="cp-country-list__page" href="<?= esc_url($list_page_url($n)) ?>"><?= (int) $n ?></a>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
              <li>
                <?php if ($nextUrl !== ''): ?>
                  <a class="cp-country-list__page cp-country-list__page--nav" href="<?= esc_url($nextUrl) ?>" rel="next">
                    <?php esc_html_e('Próxima', 'country-pages'); ?>
                  </a>
                <?php else: ?>
                  <span class="cp-country-list__page cp-country-list__page--nav cp-country-list__page--disabled"><?php esc_html_e('Próxima', 'country-pages'); ?></span>
                <?php endif; ?>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
