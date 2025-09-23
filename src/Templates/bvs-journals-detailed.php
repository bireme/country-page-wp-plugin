<?php
/** @var array $journals */
/** @var array $atts */
/** @var int $total */
?>

<div class="bvs-journals-container" data-template="detailed">
    
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
                <div class="journal-detailed">
                    <?php if (in_array('title', $showFields) && $journal->title): ?>
                        <h4 class="journal-title"><?= esc_html($journal->title) ?></h4>
                    <?php endif; ?>
                    
                    <div class="journal-details">
                        <?php if (in_array('issn', $showFields)): ?>
                            <?php if ($journal->issn): ?>
                                <p><strong>ISSN:</strong> <?= esc_html($journal->issn) ?></p>
                            <?php endif; ?>
                            <?php if ($journal->eissn): ?>
                                <p><strong>eISSN:</strong> <?= esc_html($journal->eissn) ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (in_array('publisher', $showFields) && $journal->publisher): ?>
                            <p><strong>Editor:</strong> <?= esc_html($journal->publisher) ?></p>
                        <?php endif; ?>
                        
                        <?php if (in_array('country', $showFields) && $journal->country): ?>
                            <p><strong>País:</strong> <?= esc_html($journal->country) ?></p>
                        <?php endif; ?>
                        
                        <?php if (in_array('languages', $showFields) && $journal->getLanguagesString()): ?>
                            <p><strong>Idiomas:</strong> <?= esc_html($journal->getLanguagesString()) ?></p>
                        <?php endif; ?>
                        
                        <?php if ($journal->subject_area): ?>
                            <p><strong>Área:</strong> <?= esc_html($journal->subject_area) ?></p>
                        <?php endif; ?>
                        
                        <?php if ($journal->url): ?>
                            <p><a href="<?= esc_url($journal->url) ?>" target="_blank" rel="noopener" class="journal-link">Acessar Journal</a></p>
                        <?php endif; ?>
                    </div>
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
