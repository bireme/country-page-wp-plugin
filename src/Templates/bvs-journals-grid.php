<?php
/** @var array $journals */
/** @var array $atts */
/** @var int $total */
?>

<div class="bvs-journals-container" data-template="grid">
    
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
    
    <div class="bvs-journals-grid" data-columns="<?= esc_attr($atts['columns']) ?>">
        <?php foreach ($journals as $journal): ?>
            <?php if (!$journal->isValid()) continue; ?>
            
            <div class="bvs-journal-item">
                <div class="journal-grid-content">
                    
                    <?php if (in_array('title', $showFields) && $journal->title): ?>
                        <h4 class="journal-grid-title">
                            <?php if ($journal->url): ?>
                                <a href="<?= esc_url($journal->url) ?>" target="_blank" rel="noopener">
                                    <?= esc_html(strlen($journal->title) > 60 ? substr($journal->title, 0, 57) . '...' : $journal->title) ?>
                                </a>
                            <?php else: ?>
                                <?= esc_html(strlen($journal->title) > 60 ? substr($journal->title, 0, 57) . '...' : $journal->title) ?>
                            <?php endif; ?>
                        </h4>
                    <?php endif; ?>
                    
                    <?php if (in_array('issn', $showFields) && $journal->getPrimaryIssn()): ?>
                        <div class="journal-grid-issn">
                            <span class="issn-label">ISSN:</span> 
                            <span class="issn-value"><?= esc_html($journal->getPrimaryIssn()) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('publisher', $showFields) && $journal->publisher): ?>
                        <div class="journal-grid-publisher">
                            <span class="publisher-label">Editor:</span> 
                            <span class="publisher-value"><?= esc_html(strlen($journal->publisher) > 40 ? substr($journal->publisher, 0, 37) . '...' : $journal->publisher) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('country', $showFields) && $journal->country): ?>
                        <div class="journal-grid-country">
                            <span class="country-icon">🌍</span> 
                            <span class="country-value"><?= esc_html($journal->country) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($journal->url): ?>
                        <div class="journal-grid-actions">
                            <a href="<?= esc_url($journal->url) ?>" target="_blank" rel="noopener" class="journal-access-btn">
                                Acessar
                            </a>
                        </div>
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
