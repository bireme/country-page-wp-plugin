<?php
/** @var array<int, array<string, mixed>> $countries */
/** @var array<string, mixed> $list_pagination */
/** @var array<string, mixed> $list_filters */

if (!defined('ABSPATH')) exit;

$countries = isset($countries) && is_array($countries) ? $countries : [];
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

$excerptForCard = static function (array $country): string {
    $raw = (string) ($country['excerpt'] ?? '');
    $plain = wp_strip_all_tags($raw);
    if ($plain !== '') {
        return wp_trim_words($plain, 22, '…');
    }
    $content = wp_strip_all_tags((string) ($country['content'] ?? ''));
    return $content !== '' ? wp_trim_words($content, 22, '…') : '';
};

$findTermNameById = static function (array $terms, int $id): string {
    if ($id < 1) {
        return '';
    }
    foreach ($terms as $term) {
        if ((int) ($term['id'] ?? 0) === $id) {
            return (string) ($term['name'] ?? '');
        }
    }
    return '';
};

$listPageUrl = static function (int $n) use ($pv): string {
    $clean = remove_query_arg($pv);
    if ($n <= 1) {
        return $clean;
    }
    return add_query_arg($pv, $n, $clean);
};

$paginationNumbers = static function (int $current, int $total): array {
    if ($total <= 1) {
        return [];
    }
    if ($total <= 7) {
        return range(1, $total);
    }
    $nums = [1, $total];
    for ($i = max(2, $current - 1); $i <= min($total - 1, $current + 1); $i++) {
        $nums[] = $i;
    }
    $nums = array_values(array_unique($nums));
    sort($nums);
    $out = [];
    $prev = 0;
    foreach ($nums as $num) {
        if ($prev > 0 && $num - $prev > 1) {
            $out[] = 0;
        }
        $out[] = $num;
        $prev = $num;
    }
    return $out;
};

$activeFilters = [];
if ($filterSearch !== '') {
    $activeFilters[] = sprintf(__('Busca: %s', 'country-pages'), $filterSearch);
}
$activeTagName = $findTermNameById($filterTags, $filterTagId);
if ($activeTagName !== '') {
    $activeFilters[] = sprintf(__('Tag: %s', 'country-pages'), $activeTagName);
}
$activeCategoryName = $findTermNameById($filterCategories, $filterCatId);
if ($activeCategoryName !== '') {
    $activeFilters[] = sprintf(__('Categoria: %s', 'country-pages'), $activeCategoryName);
}
$hasActiveFilters = $filterSearch !== '' || $filterTagId > 0 || $filterCatId > 0;
?>

<div class="cp-country-list-layout cp-country-atlas-layout">
  <aside class="cp-country-list__sidebar cp-country-atlas__sidebar" aria-label="<?php esc_attr_e('Filtros da lista', 'country-pages'); ?>">
    <div class="cp-country-list__filters-card cp-country-atlas__filters-card">
      <div class="cp-country-atlas__sidebar-header">
        <p class="cp-country-atlas__eyebrow"><?php esc_html_e('Country Atlas', 'country-pages'); ?></p>
        <h2 class="cp-country-list__filters-title"><?php esc_html_e('Refinar exploracao', 'country-pages'); ?></h2>
      </div>

      <form class="cp-country-list__filters" method="get" action="<?= esc_url($formAction) ?>">
        <div class="cp-country-list__field">
          <label class="cp-country-list__label" for="cp-atlas-search"><?php esc_html_e('Busca', 'country-pages'); ?></label>
          <input
            type="search"
            id="cp-atlas-search"
            class="cp-country-list__input"
            name="<?= esc_attr($fkSearch) ?>"
            value="<?= esc_attr($filterSearch) ?>"
            placeholder="<?php esc_attr_e('Digite para buscar…', 'country-pages'); ?>"
            autocomplete="off"
          >
        </div>

        <div class="cp-country-list__field">
          <label class="cp-country-list__label" for="cp-atlas-tag"><?php esc_html_e('Tag', 'country-pages'); ?></label>
          <select class="cp-country-list__select" id="cp-atlas-tag" name="<?= esc_attr($fkTag) ?>">
            <option value=""><?php esc_html_e('Todas as tags', 'country-pages'); ?></option>
            <?php foreach ($filterTags as $term): ?>
              <?php
              $termId = (int) ($term['id'] ?? 0);
              if ($termId < 1) {
                  continue;
              }
              $termName = (string) ($term['name'] ?? '');
              ?>
              <option value="<?= (int) $termId ?>"<?php selected($filterTagId, $termId); ?>><?= esc_html($termName) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="cp-country-list__field">
          <label class="cp-country-list__label" for="cp-atlas-cat"><?php esc_html_e('Categoria', 'country-pages'); ?></label>
          <select class="cp-country-list__select" id="cp-atlas-cat" name="<?= esc_attr($fkCat) ?>">
            <option value=""><?php esc_html_e('Todas as categorias', 'country-pages'); ?></option>
            <?php foreach ($filterCategories as $term): ?>
              <?php
              $termId = (int) ($term['id'] ?? 0);
              if ($termId < 1) {
                  continue;
              }
              $termName = (string) ($term['name'] ?? '');
              ?>
              <option value="<?= (int) $termId ?>"<?php selected($filterCatId, $termId); ?>><?= esc_html($termName) ?></option>
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

  <div class="cp-country-list__main cp-country-atlas__main">
    <section class="cp-country-atlas">
      <header class="cp-country-atlas__hero">
        <div class="cp-country-atlas__headline">
          <h2 class="cp-country-atlas__title"><?php esc_html_e('Explorar destinos e perfis de paises', 'country-pages'); ?></h2>
          <p class="cp-country-atlas__lead">
            <?php
            echo esc_html(
                sprintf(
                    /* translators: 1: total items, 2: current page, 3: total pages */
                    __('%1$d paises encontrados. Pagina %2$d de %3$d.', 'country-pages'),
                    $totalItems,
                    $currentPage,
                    $totalPages
                )
            );
            ?>
          </p>
        </div>

        <?php if (!empty($activeFilters)): ?>
          <div class="cp-country-atlas__active-filters" aria-label="<?php esc_attr_e('Filtros ativos', 'country-pages'); ?>">
            <?php foreach ($activeFilters as $filterLabel): ?>
              <span class="cp-country-atlas__filter-pill"><?= esc_html($filterLabel) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </header>

      <?php if (empty($countries)): ?>
        <p class="cp-country-atlas__empty"><?php esc_html_e('Nenhum país encontrado.', 'country-pages'); ?></p>
      <?php else: ?>
        <div class="cp-country-atlas__grid">
          <?php foreach ($countries as $country): ?>
            <?php
            $title = (string) ($country['title'] ?? $country['name'] ?? '');
            $excerpt = $excerptForCard($country);
            $tags = !empty($country['tags']) && is_array($country['tags']) ? $country['tags'] : [];
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

            $itemSlug = (string) ($country['slug'] ?? '');
            $localUrl = \CP\Front\CountryPageRoute::permalinkForSlug($itemSlug);
            $viewUrl = (string) apply_filters('cp_country_list_item_url', $localUrl, $country);
            if ($viewUrl === '') {
                $viewUrl = '#';
            }
            ?>
            <article class="cp-country-atlas__card">
              <a class="cp-country-atlas__link" href="<?= esc_url($viewUrl) ?>">
                <div class="cp-country-atlas__media">
                  <?php if ($cover !== ''): ?>
                    <img
                      class="cp-country-atlas__image"
                      src="<?= esc_url($cover) ?>"
                      alt="<?= esc_attr($title) ?>"
                      loading="lazy"
                      decoding="async"
                    >
                  <?php else: ?>
                    <div class="cp-country-atlas__placeholder" aria-hidden="true"></div>
                  <?php endif; ?>
                </div>

                <div class="cp-country-atlas__body">
                  <h3 class="cp-country-atlas__card-title"><?= esc_html($title) ?></h3>

                  <?php if (!empty($tags)): ?>
                    <div class="cp-country-atlas__tags" aria-label="<?php esc_attr_e('Tags do país', 'country-pages'); ?>">
                      <?php foreach ($tags as $tag): ?>
                        <?php $tagName = (string) ($tag['name'] ?? ''); ?>
                        <?php if ($tagName === '') continue; ?>
                        <span class="cp-country-atlas__tag"><?= esc_html($tagName) ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <?php if ($excerpt !== ''): ?>
                    <p class="cp-country-atlas__excerpt"><?= esc_html($excerpt) ?></p>
                  <?php endif; ?>

                  <span class="cp-country-atlas__cta"><?php esc_html_e('Abrir perfil', 'country-pages'); ?></span>
                </div>
              </a>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
          <?php
          $prevUrl = $currentPage > 1 ? $listPageUrl($currentPage - 1) : '';
          $nextUrl = $currentPage < $totalPages ? $listPageUrl($currentPage + 1) : '';
          $nums = $paginationNumbers($currentPage, $totalPages);
          ?>
          <nav class="cp-country-atlas__pagination" aria-label="<?php esc_attr_e('Paginação da lista customizada de países', 'country-pages'); ?>">
            <?php if ($prevUrl !== ''): ?>
              <a class="cp-country-atlas__page cp-country-atlas__page--nav" href="<?= esc_url($prevUrl) ?>" rel="prev"><?php esc_html_e('Anterior', 'country-pages'); ?></a>
            <?php else: ?>
              <span class="cp-country-atlas__page cp-country-atlas__page--nav cp-country-atlas__page--disabled"><?php esc_html_e('Anterior', 'country-pages'); ?></span>
            <?php endif; ?>

            <?php foreach ($nums as $num): ?>
              <?php if ($num === 0): ?>
                <span class="cp-country-atlas__ellipsis" aria-hidden="true">…</span>
              <?php elseif ($num === $currentPage): ?>
                <span class="cp-country-atlas__page cp-country-atlas__page--current" aria-current="page"><?= (int) $num ?></span>
              <?php else: ?>
                <a class="cp-country-atlas__page" href="<?= esc_url($listPageUrl($num)) ?>"><?= (int) $num ?></a>
              <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($nextUrl !== ''): ?>
              <a class="cp-country-atlas__page cp-country-atlas__page--nav" href="<?= esc_url($nextUrl) ?>" rel="next"><?php esc_html_e('Próxima', 'country-pages'); ?></a>
            <?php else: ?>
              <span class="cp-country-atlas__page cp-country-atlas__page--nav cp-country-atlas__page--disabled"><?php esc_html_e('Próxima', 'country-pages'); ?></span>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>
</div>
