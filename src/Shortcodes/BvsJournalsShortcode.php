<?php
namespace CP\Shortcodes;

use CP\API\BvsaludClient;
use CP\API\JournalDto;
use CP\Support\Template;
use CP\Admin\TemplatesPage;

if (!defined('ABSPATH')) exit;

/**
 * Shortcode para exibir journals da API BVS Saúde
 * 
 * Exemplos de uso:
 * [bvs_journals country="Brasil" max="20"] - Grid 4 colunas com até 20 journals do Brasil
 * [bvs_journals country="Argentina" max="12" template="grid"] - Grid personalizado
 * [bvs_journals subject="medicina" limit="10"] - Lista por assunto
 * [bvs_journals search="cardiologia" limit="5" template="compact"] - Busca compacta
 */
final class BvsJournalsShortcode {
    
    public function register(): void {
        add_shortcode('bvs_journals', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }
    
    public function render($atts, $content = ''): string {
        $atts = shortcode_atts([
            'country' => '',
            'subject' => '',
            'search' => '',
            'issn' => '',
            'limit' => 10,
            'max' => 50, // quantidade máxima de journals a exibir
            'show_pagination' => 'false',
            'page' => 1,
            'template' => 'default', // default, compact, detailed, grid
            'show_fields' => 'title,issn,publisher,country', // campos a exibir
            'columns' => 4, // para template grid
        ], $atts, 'bvs_journals');
        
        // Sanitizar atributos
        $atts['limit'] = max(1, min(50, (int) $atts['limit'])); // entre 1 e 50
        $atts['max'] = max(1, min(200, (int) $atts['max'])); // entre 1 e 200
        $atts['page'] = max(1, (int) $atts['page']);
        $atts['show_pagination'] = $atts['show_pagination'] === 'true';
        
        // Se buscar por país e template não especificado, usar grid por padrão
        if (!empty($atts['country']) && $atts['template'] === 'default') {
            $atts['template'] = 'grid';
        }
        
        $client = new BvsaludClient();
        $journals = [];
        $totalJournals = 0;
        $error = null;
        
        try {
            $connectionTest = $client->testConnection();
            if (!$connectionTest['success']) {
                return $this->renderError('Erro de conexão com a API BVS: ' . $connectionTest['message']);
            }
            
            if (!empty($atts['issn'])) {
                $journal = $client->getJournalByIssn(sanitize_text_field($atts['issn']));
                $journals = $journal && $journal->isValid() ? [$journal] : [];
                $totalJournals = count($journals);
            } elseif (!empty($atts['search'])) {
                $results = $client->searchJournals([
                    'q' => sanitize_text_field($atts['search']),
                    'limit' => $atts['limit'],
                    'offset' => ($atts['page'] - 1) * $atts['limit']
                ]);
                $journals = $results['journals'] ?? [];
                $totalJournals = $results['total'] ?? 0;
            } elseif (!empty($atts['country'])) {
                $journals = $client->getJournalsByCountry(
                    sanitize_text_field($atts['country']), 
                    $atts['max'] // usar 'max' ao invés de 'limit' para busca por país
                );
                $totalJournals = count($journals);

                
            } elseif (!empty($atts['subject'])) {
                $journals = $client->getJournalsBySubject(
                    sanitize_text_field($atts['subject']), 
                    $atts['limit']
                );
                $totalJournals = count($journals);
            } else {

                $results = $client->listJournals($atts['page'], $atts['limit']);
                if (isset($results['error'])) {
                    return $this->renderError($results['error']);
                }
                $journals = $results['journals'] ?? [];
                $totalJournals = $results['total'] ?? 0;
            }
            
        } catch (Exception $e) {
            return $this->renderError('Erro ao buscar journals: ' . $e->getMessage());
        }
        
        if (empty($journals)) {
            return $this->renderEmpty();
        }
        
        return $this->renderJournals($journals, $atts, $totalJournals);
    }
    
    private function renderJournals(array $journals, array $atts, int $total): string {
        $showFields = array_map('trim', explode(',', $atts['show_fields']));
        
        // Variáveis para os templates
        $templateVars = [
            'journals' => $journals,
            'atts' => $atts,
            'total' => $total,
            'showFields' => $showFields
        ];
        
        // Verificar se deve usar template customizado
        if (TemplatesPage::shouldUseCustomBvsTemplate()) {
            $customOutput = Template::load_custom('bvs-journals', $templateVars);
            if ($customOutput !== null) {
                return $customOutput;
            }
        }
        
        // Usar template padrão
        $templateOutput = Template::load_bvs_template($atts['template'], $templateVars);
        if ($templateOutput !== null) {
            return $templateOutput;
        }
        
        // Fallback para renderização inline (caso os templates não existam)
        return $this->renderFallback($journals, $atts, $total);
    }
    
    /**
     * Fallback para renderização inline caso os templates não existam
     */
    private function renderFallback(array $journals, array $atts, int $total): string {
        $showFields = array_map('trim', explode(',', $atts['show_fields']));
        $template = $atts['template'];
        
        $html = '<div class="bvs-journals-container" data-template="' . esc_attr($template) . '">';
        

        if ($total > 0) {
            $html .= '<div class="bvs-journals-header">';
            $html .= '<p class="bvs-journals-count">';
            $html .= sprintf(
                _n(
                    '%d journal encontrado',
                    '%d journals encontrados',
                    $total,
                    'country-pages'
                ),
                $total
            );
            $html .= '</p>';
            $html .= '</div>';
        }
        

        $listClass = $template === 'grid' ? 'bvs-journals-grid' : 'bvs-journals-list';
        $html .= '<div class="' . $listClass . '" data-columns="' . esc_attr($atts['columns']) . '">';
        
        foreach ($journals as $journal) {
            /** @var JournalDto $journal */
            if (!$journal->isValid()) continue;
            
            $html .= $this->renderJournalItem($journal, $showFields, $template);
        }
        
        $html .= '</div>';
        
        // Paginação
        if ($atts['show_pagination'] && $total > $atts['limit']) {
            $html .= $this->renderPagination($atts['page'], $atts['limit'], $total);
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderJournalItem(JournalDto $journal, array $showFields, string $template): string {
        $html = '<div class="bvs-journal-item">';
        
        switch ($template) {
            case 'compact':
                $html .= $this->renderCompactItem($journal, $showFields);
                break;
            case 'detailed':
                $html .= $this->renderDetailedItem($journal, $showFields);
                break;
            case 'grid':
                $html .= $this->renderGridItem($journal, $showFields);
                break;
            default:
                $html .= $this->renderDefaultItem($journal, $showFields);
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderDefaultItem(JournalDto $journal, array $showFields): string {
        $html = '';
        
        if (in_array('title', $showFields) && $journal->title) {
            $html .= '<h4 class="journal-title">';
            if ($journal->url) {
                $html .= '<a href="' . esc_url($journal->url) . '" target="_blank" rel="noopener">';
                $html .= esc_html($journal->title);
                $html .= '</a>';
            } else {
                $html .= esc_html($journal->title);
            }
            $html .= '</h4>';
        }
        
        $html .= '<div class="journal-meta">';
        
        if (in_array('issn', $showFields) && $journal->getPrimaryIssn()) {
            $html .= '<span class="journal-issn"><strong>ISSN:</strong> ' . esc_html($journal->getPrimaryIssn()) . '</span>';
        }
        
        if (in_array('publisher', $showFields) && $journal->publisher) {
            $html .= '<span class="journal-publisher"><strong>Editor:</strong> ' . esc_html($journal->publisher) . '</span>';
        }
        
        if (in_array('country', $showFields) && $journal->country) {
            $html .= '<span class="journal-country"><strong>País:</strong> ' . esc_html($journal->country) . '</span>';
        }
        
        if (in_array('languages', $showFields) && $journal->getLanguagesString()) {
            $html .= '<span class="journal-languages"><strong>Idiomas:</strong> ' . esc_html($journal->getLanguagesString()) . '</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderCompactItem(JournalDto $journal, array $showFields): string {
        $html = '<div class="journal-compact">';
        
        if (in_array('title', $showFields) && $journal->title) {
            $html .= '<span class="journal-title">' . esc_html($journal->title) . '</span>';
        }
        
        $meta = [];
        if (in_array('issn', $showFields) && $journal->getPrimaryIssn()) {
            $meta[] = 'ISSN: ' . $journal->getPrimaryIssn();
        }
        if (in_array('country', $showFields) && $journal->country) {
            $meta[] = $journal->country;
        }
        
        if (!empty($meta)) {
            $html .= ' <small class="journal-meta">(' . implode(' | ', $meta) . ')</small>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderDetailedItem(JournalDto $journal, array $showFields): string {
        $html = '<div class="journal-detailed">';
        
        if (in_array('title', $showFields) && $journal->title) {
            $html .= '<h4 class="journal-title">' . esc_html($journal->title) . '</h4>';
        }
        
        $html .= '<div class="journal-details">';
        
        if (in_array('issn', $showFields)) {
            if ($journal->issn) {
                $html .= '<p><strong>ISSN:</strong> ' . esc_html($journal->issn) . '</p>';
            }
            if ($journal->eissn) {
                $html .= '<p><strong>eISSN:</strong> ' . esc_html($journal->eissn) . '</p>';
            }
        }
        
        if (in_array('publisher', $showFields) && $journal->publisher) {
            $html .= '<p><strong>Editor:</strong> ' . esc_html($journal->publisher) . '</p>';
        }
        
        if (in_array('country', $showFields) && $journal->country) {
            $html .= '<p><strong>País:</strong> ' . esc_html($journal->country) . '</p>';
        }
        
        if (in_array('languages', $showFields) && $journal->getLanguagesString()) {
            $html .= '<p><strong>Idiomas:</strong> ' . esc_html($journal->getLanguagesString()) . '</p>';
        }
        
        if ($journal->subject_area) {
            $html .= '<p><strong>Área:</strong> ' . esc_html($journal->subject_area) . '</p>';
        }
        
        if ($journal->url) {
            $html .= '<p><a href="' . esc_url($journal->url) . '" target="_blank" rel="noopener" class="journal-link">Acessar Journal</a></p>';
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
    
    private function renderGridItem(JournalDto $journal, array $showFields): string {
        $html = '<div class="journal-grid-content">';
        
        // Título
        if (in_array('title', $showFields) && $journal->title) {
            $html .= '<h4 class="journal-grid-title">';
            if ($journal->url) {
                $html .= '<a href="' . esc_url($journal->url) . '" target="_blank" rel="noopener">';
                $html .= esc_html($this->truncateText($journal->title, 60));
                $html .= '</a>';
            } else {
                $html .= esc_html($this->truncateText($journal->title, 60));
            }
            $html .= '</h4>';
        }
        
        // ISSN
        if (in_array('issn', $showFields) && $journal->getPrimaryIssn()) {
            $html .= '<div class="journal-grid-issn">';
            $html .= '<span class="issn-label">ISSN:</span> ';
            $html .= '<span class="issn-value">' . esc_html($journal->getPrimaryIssn()) . '</span>';
            $html .= '</div>';
        }
        
        // Publisher
        if (in_array('publisher', $showFields) && $journal->publisher) {
            $html .= '<div class="journal-grid-publisher">';
            $html .= '<span class="publisher-label">Editor:</span> ';
            $html .= '<span class="publisher-value">' . esc_html($this->truncateText($journal->publisher, 40)) . '</span>';
            $html .= '</div>';
        }
        
        // País
        if (in_array('country', $showFields) && $journal->country) {
            $html .= '<div class="journal-grid-country">';
            $html .= '<span class="country-icon">🌍</span> ';
            $html .= '<span class="country-value">' . esc_html($journal->country) . '</span>';
            $html .= '</div>';
        }
        
        // Botão de acesso
        if ($journal->url) {
            $html .= '<div class="journal-grid-actions">';
            $html .= '<a href="' . esc_url($journal->url) . '" target="_blank" rel="noopener" class="journal-access-btn">';
            $html .= 'Acessar';
            $html .= '</a>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    private function truncateText(string $text, int $maxLength): string {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
    
    private function renderPagination(int $currentPage, int $perPage, int $total): string {
        $totalPages = ceil($total / $perPage);
        
        if ($totalPages <= 1) return '';
        
        $html = '<div class="bvs-journals-pagination">';
        
        if ($currentPage > 1) {
            $html .= '<a href="#" class="page-link" data-page="' . ($currentPage - 1) . '">« Anterior</a>';
        }
        
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $class = $i === $currentPage ? 'page-link current' : 'page-link';
            $html .= '<a href="#" class="' . $class . '" data-page="' . $i . '">' . $i . '</a>';
        }
        
        if ($currentPage < $totalPages) {
            $html .= '<a href="#" class="page-link" data-page="' . ($currentPage + 1) . '">Próxima »</a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderError(string $message): string {
        return '<div class="bvs-journals-error"><p><strong>Erro:</strong> ' . esc_html($message) . '</p></div>';
    }
    
    private function renderEmpty(): string {
        return '<div class="bvs-journals-empty"><p>' . esc_html__('Nenhum journal encontrado.', 'country-pages') . '</p></div>';
    }
    
    public function enqueueAssets(): void {
        // Adicionar CSS específico para o shortcode
        wp_add_inline_style('cp-public', $this->getInlineCSS());
    }
    
    private function getInlineCSS(): string {
        return '
        .bvs-journals-container { margin: 20px 0; }
        .bvs-journals-header { margin-bottom: 15px; }
        .bvs-journals-count { font-size: 14px; color: #666; margin: 0; }
        .bvs-journals-list { display: flex; flex-direction: column; gap: 15px; }
        .bvs-journal-item { padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
        .journal-title { margin: 0 0 10px 0; font-size: 18px; }
        .journal-title a { color: #0073aa; text-decoration: none; }
        .journal-title a:hover { text-decoration: underline; }
        .journal-meta { display: flex; flex-wrap: wrap; gap: 10px; font-size: 14px; }
        .journal-meta span { display: inline-block; }
        .journal-compact { padding: 8px 0; border-bottom: 1px solid #eee; }
        .journal-compact:last-child { border-bottom: none; }
        .journal-detailed .journal-details p { margin: 5px 0; }
        .journal-link { display: inline-block; padding: 5px 10px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; font-size: 12px; }
        .journal-link:hover { background: #005a87; color: white; }
        
        /* Grid Template */
        .bvs-journals-grid { 
            display: grid; 
            gap: 20px; 
            grid-template-columns: repeat(4, 1fr); 
        }
        .bvs-journals-grid[data-columns="3"] { grid-template-columns: repeat(3, 1fr); }
        .bvs-journals-grid[data-columns="2"] { grid-template-columns: repeat(2, 1fr); }
        .bvs-journals-grid[data-columns="5"] { grid-template-columns: repeat(5, 1fr); }
        .bvs-journals-grid[data-columns="6"] { grid-template-columns: repeat(6, 1fr); }
        
        @media (max-width: 1200px) {
            .bvs-journals-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .bvs-journals-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
        }
        @media (max-width: 480px) {
            .bvs-journals-grid { grid-template-columns: 1fr; gap: 15px; }
        }
        
        .bvs-journals-grid .bvs-journal-item {
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .bvs-journals-grid .bvs-journal-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #0073aa;
        }
        
        .journal-grid-content { display: flex; flex-direction: column; height: 100%; }
        .journal-grid-title { margin: 0 0 15px 0; font-size: 16px; font-weight: 600; line-height: 1.4; flex-grow: 1; }
        .journal-grid-title a { color: #2c3e50; text-decoration: none; }
        .journal-grid-title a:hover { color: #0073aa; }
        .journal-grid-issn, .journal-grid-publisher, .journal-grid-country { margin: 6px 0; font-size: 13px; }
        .issn-label, .publisher-label { font-weight: 600; color: #495057; }
        .issn-value, .publisher-value, .country-value { color: #6c757d; }
        .country-icon { margin-right: 4px; }
        .journal-grid-actions { margin-top: auto; padding-top: 15px; }
        .journal-access-btn { 
            display: inline-block; width: 100%; padding: 8px 16px; background: #0073aa; 
            color: white !important; text-align: center; text-decoration: none; border-radius: 4px; 
            font-size: 13px; font-weight: 500; transition: background-color 0.3s ease; 
        }
        .journal-access-btn:hover { background: #005a87; color: white !important; }
        
        .bvs-journals-pagination { margin-top: 20px; text-align: center; }
        .bvs-journals-pagination .page-link { display: inline-block; padding: 8px 12px; margin: 0 2px; background: #f1f1f1; color: #333; text-decoration: none; border-radius: 3px; }
        .bvs-journals-pagination .page-link:hover { background: #ddd; }
        .bvs-journals-pagination .page-link.current { background: #0073aa; color: white; }
        .bvs-journals-error { padding: 15px; background: #ffebee; border: 1px solid #f44336; border-radius: 5px; color: #c62828; }
        .bvs-journals-empty { padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 5px; text-align: center; color: #666; }
        ';
    }
}
