<?php
/** @var array $journals */
/** @var array $atts */
/** @var int $total */
/** @var array $showFields */

if (!defined('ABSPATH')) exit;
?>

<div class="custom-bvs-journals">
    
    <?php if ($total > 0): ?>
        <div class="journals-header">
            <h3>Periódicos Científicos</h3>
            <p class="journals-count">
                <?php 
                printf(
                    _n(
                        'Encontrado %d periódico',
                        'Encontrados %d periódicos',
                        $total,
                        'country-pages'
                    ),
                    $total
                ); 
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="journals-list">
        <?php foreach ($journals as $journal): ?>
            <?php if (!$journal->isValid()) continue; ?>
            
            <article class="journal-card">
                
                <?php if (in_array('title', $showFields) && $journal->title): ?>
                    <header class="journal-header">
                        <h4 class="journal-title">
                            <?php if ($journal->url): ?>
                                <a href="<?= esc_url($journal->url) ?>" target="_blank" rel="noopener">
                                    <?= esc_html($journal->title) ?>
                                </a>
                            <?php else: ?>
                                <?= esc_html($journal->title) ?>
                            <?php endif; ?>
                        </h4>
                    </header>
                <?php endif; ?>
                
                <div class="journal-content">
                    
                    <?php if (in_array('issn', $showFields) && ($journal->issn || $journal->eissn)): ?>
                        <div class="journal-issn-info">
                            <?php if ($journal->issn): ?>
                                <span class="issn-print">ISSN: <?= esc_html($journal->issn) ?></span>
                            <?php endif; ?>
                            <?php if ($journal->eissn): ?>
                                <span class="issn-online">eISSN: <?= esc_html($journal->eissn) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('publisher', $showFields) && $journal->publisher): ?>
                        <div class="journal-publisher">
                            <strong>Editora:</strong> <?= esc_html($journal->publisher) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="journal-metadata">
                        <?php if (in_array('country', $showFields) && $journal->country): ?>
                            <span class="journal-country">
                                <span class="dashicons dashicons-location"></span>
                                <?= esc_html($journal->country) ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (in_array('languages', $showFields) && $journal->getLanguagesString()): ?>
                            <span class="journal-languages">
                                <span class="dashicons dashicons-translation"></span>
                                <?= esc_html($journal->getLanguagesString()) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($journal->subject_area): ?>
                        <div class="journal-subject">
                            <strong>Área:</strong> <?= esc_html($journal->subject_area) ?>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
                <?php if ($journal->url): ?>
                    <footer class="journal-footer">
                        <a href="<?= esc_url($journal->url) ?>" target="_blank" rel="noopener" class="journal-access-button">
                            <span class="dashicons dashicons-external"></span>
                            Acessar Periódico
                        </a>
                    </footer>
                <?php endif; ?>
                
            </article>
            
        <?php endforeach; ?>
    </div>
    
    <?php if ($atts['show_pagination'] && $total > $atts['limit']): ?>
        <nav class="journals-pagination" aria-label="Navegação dos periódicos">
            <?php
            $currentPage = $atts['page'];
            $perPage = $atts['limit'];
            $totalPages = ceil($total / $perPage);
            
            if ($totalPages > 1): ?>
                <div class="pagination-links">
                    <?php if ($currentPage > 1): ?>
                        <a href="#" class="page-link prev" data-page="<?= $currentPage - 1 ?>" aria-label="Página anterior">
                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                            Anterior
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $currentPage - 2);
                    $end = min($totalPages, $currentPage + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                        $class = $i === $currentPage ? 'page-link current' : 'page-link';
                        $ariaCurrent = $i === $currentPage ? 'aria-current="page"' : '';
                        ?>
                        <a href="#" class="<?= $class ?>" data-page="<?= $i ?>" <?= $ariaCurrent ?>><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="#" class="page-link next" data-page="<?= $currentPage + 1 ?>" aria-label="Próxima página">
                            Próxima
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="pagination-info">
                    Página <?= $currentPage ?> de <?= $totalPages ?>
                </div>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
    
</div>

<style>
/* Estilos customizados para os periódicos */
.custom-bvs-journals {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    margin: 2rem 0;
}

.journals-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.journals-header h3 {
    color: #2d3748;
    font-size: 1.75rem;
    margin-bottom: 0.5rem;
}

.journals-count {
    color: #718096;
    font-size: 0.9rem;
}

.journals-list {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.journal-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.journal-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-color: #3182ce;
}

.journal-title {
    color: #2d3748;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.journal-title a {
    color: inherit;
    text-decoration: none;
}

.journal-title a:hover {
    color: #3182ce;
}

.journal-issn-info {
    margin-bottom: 0.75rem;
}

.journal-issn-info span {
    display: inline-block;
    margin-right: 1rem;
    font-size: 0.875rem;
    color: #4a5568;
    font-weight: 500;
}

.journal-publisher,
.journal-subject {
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
    color: #4a5568;
}

.journal-metadata {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}

.journal-metadata span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #718096;
}

.journal-access-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: #3182ce;
    color: white !important;
    text-decoration: none;
    border-radius: 0.375rem;
    font-weight: 500;
    transition: background-color 0.3s ease;
}

.journal-access-button:hover {
    background: #2c5282;
    color: white !important;
}

.journals-pagination {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.pagination-links {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.page-link {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    background: #f7fafc;
    color: #4a5568;
    text-decoration: none;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.page-link:hover {
    background: #e2e8f0;
    color: #2d3748;
}

.page-link.current {
    background: #3182ce;
    color: white;
}

.pagination-info {
    font-size: 0.875rem;
    color: #718096;
}
</style>
