<?php
/** @var array $journals */
/** @var array $atts */
/** @var int $total */
?>

<div class="bvs-journals-container" data-template="compact">
    
    <?php if ($total > 0): ?>
        <div class="bvs-journals-header">
            <p class="bvs-journals-count">
                <?php 
                printf(
                    _n(
                        '%d journal encontrado',
                        '%d journals encontrados',
                        $total,
                        'country-pages'
                    ),
                    $total
                ); 
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="bvs-journals-list">
        <?php foreach ($journals as $journal): ?>
            <?php if (!$journal->isValid()) continue; ?>
            
            <div class="bvs-journal-item">
                <div class="journal-compact">
                    <?php if (in_array('title', $showFields) && $journal->title): ?>
                        <span class="journal-title"><?= esc_html($journal->title) ?></span>
                    <?php endif; ?>
                    
                    <?php
                    $meta = [];
                    if (in_array('issn', $showFields) && $journal->getPrimaryIssn()) {
                        $meta[] = 'ISSN: ' . $journal->getPrimaryIssn();
                    }
                    if (in_array('country', $showFields) && $journal->country) {
                        $meta[] = $journal->country;
                    }
                    
                    if (!empty($meta)): ?>
                        <small class="journal-meta">(<?= implode(' | ', array_map('esc_html', $meta)) ?>)</small>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php endforeach; ?>
    </div>
    
    <?php if ($atts['show_pagination'] && $total > $atts['limit']): ?>
        <div class="bvs-journals-pagination">
            <?php
            $currentPage = $atts['page'];
            $perPage = $atts['limit'];
            $totalPages = ceil($total / $perPage);
            
            if ($totalPages > 1):
                if ($currentPage > 1): ?>
                    <a href="#" class="page-link" data-page="<?= $currentPage - 1 ?>">« Anterior</a>
                <?php endif;
                
                $start = max(1, $currentPage - 2);
                $end = min($totalPages, $currentPage + 2);
                
                for ($i = $start; $i <= $end; $i++):
                    $class = $i === $currentPage ? 'page-link current' : 'page-link';
                    ?>
                    <a href="#" class="<?= $class ?>" data-page="<?= $i ?>"><?= $i ?></a>
                <?php endfor;
                
                if ($currentPage < $totalPages): ?>
                    <a href="#" class="page-link" data-page="<?= $currentPage + 1 ?>">Próxima »</a>
                <?php endif;
            endif;
            ?>
        </div>
    <?php endif; ?>
    
</div>
