<?php
namespace CP\API;

use CP\Support\Cache;
use CP\Admin\SettingsPage;

if (!defined('ABSPATH')) exit;

/**
 * Cliente para a API BVS Saúde - Search Journals endpoint
 */
final class BvsaludClient {
    private string $apiUrl;
    private string $token;
    private int $timeout;

    public function __construct(?string $apiUrl = null, ?string $token = null) {
        $this->apiUrl = $apiUrl ?: SettingsPage::getJournalsApiUrl();
        $this->token = $token ?: SettingsPage::getBvsaludToken();
        $this->timeout = 15;
    }

    /**
     * Busca journals por termo de pesquisa usando formato correto da API BVS
     */
    public function searchJournals(array $params = []): array {
        if (!$this->apiUrl || !$this->token) {
            return ['error' => 'API URL ou token não configurados'];
        }

        $defaults = [
            'q' => '*:*',        // termo de busca (sempre *:* ver com o Vini)
            'count' => 20,       // limite de resultados (count ao invés de limit)
            'start' => 0,        // offset para paginação (start ao invés de offset)
            'format' => 'json',  // formato da resposta
            'fq' => '',          // filtro query para país
        ];

        $queryParams = array_filter(array_merge($defaults, $params), function($value) {
            return $value !== '' && $value !== null;
        });

        $baseUrl = rtrim($this->apiUrl, '/') . '/search/';
        $url = add_query_arg($queryParams, $baseUrl);
        
        $cacheKey = 'cp_bvs_journals_' . md5($url);



        return $this->makeRequest($url);

    }


    public function getJournalByIssn(string $issn): ?JournalDto {
        $results = $this->searchJournals(['q' => 'issn:' . $issn, 'count' => 1]);
        
        if (isset($results['error']) || empty($results['journals'])) {
            return null;
        }

        return new JournalDto($results['journals'][0]);
    }

    
    public function getJournalsByCountry(string $country, int $count = 20): array {
       
        $countryFilter = $this->buildCountryFilter($country);
        
        $results = $this->searchJournals([
            'q' => '*:*',
            'fq' => 'country:' . $countryFilter,
            'count' => $count
        ]);

        if (isset($results['error'])) {
            return [];
        }

        $journals = $results['journals'] ?? [];
        
        return $this->normalizeJournals($journals);
    }


    public function getJournalsBySubject(string $subject, int $limit = 20): array {
        $results = $this->searchJournals([
            'q' => 'subject_area:"' . $subject . '"',
            'count' => $limit  
        ]);

        if (isset($results['error'])) {
            return [];
        }

        $journals = $results['journals'] ?? [];
        return $this->normalizeJournals($journals);
    }

    // /**
    //  * Lista todos os journals com paginação
    //  */
    public function listJournals(int $page = 1, int $perPage = 20): array {
        $start = ($page - 1) * $perPage;
        
        $results = $this->searchJournals([
            'q' => '*:*', 
            'count' => $perPage,  
            'start' => $start     
        ]);

        if (isset($results['error'])) {
            return [
                'journals' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'error' => $results['error']
            ];
        }

        $journals = $results['journals'] ?? [];
        
        return [
            'journals' => $this->normalizeJournals($journals),
            'total' => $results['total'] ?? 0,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    /**
     * Constrói a URL com parâmetros
     */
    private function buildUrl(array $params): string {
        $baseUrl = rtrim($this->apiUrl, '/');
        return add_query_arg($params, $baseUrl);
    }

    /**
     * Faz a requisição HTTP para a API usando apikey no header
     */
    private function makeRequest(string $url): array {
        $headers = [
            'accept' => '*/*',
            'apikey' => $this->token, 
            'User-Agent' => 'Country-Pages-Plugin/' . CP_VERSION
        ];

        $args = [
            'headers' => $headers,
            'timeout' => $this->timeout,
            'sslverify' => true,
            'method' => 'GET'
        ];

        $response = wp_remote_get($url, $args);

        

        if (is_wp_error($response)) {
            return ['error' => 'Erro de conexão: ' . $response->get_error_message()];
        }

        $responseCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($responseCode !== 200) {
            return [
                'error' => sprintf(
                    'Erro HTTP %d: %s', 
                    $responseCode, 
                    wp_remote_retrieve_response_message($response)
                )
            ];
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            
            return ['error' => 'Erro ao decodificar JSON: ' . json_last_error_msg()];
        }

        return $this->normalizeApiResponse($data);
    }

    /**
     * Normaliza a resposta da API BVS para um formato consistente
     */
    private function normalizeApiResponse(array $data): array {
        // Formato atual da API BVS com diaServerResponse
        if (isset($data['diaServerResponse'][0]['response']['docs'])) {
            $response = $data['diaServerResponse'][0]['response'];
            return [
                'journals' => $response['docs'],
                'total' => $response['numFound'] ?? count($response['docs'])
            ];
        }
        
        
        if (isset($data['response']['docs'])) {
            return [
                'journals' => $data['response']['docs'],
                'total' => $data['response']['numFound'] ?? count($data['response']['docs'])
            ];
        }

        // Formato direto da API BVS title/v1/search
        if (isset($data['docs'])) {
            return [
                'journals' => $data['docs'],
                'total' => $data['numFound'] ?? count($data['docs'])
            ];
        }

        if (isset($data['data'])) {
            return [
                'journals' => is_array($data['data']) ? $data['data'] : [$data['data']],
                'total' => $data['total'] ?? count($data['data'])
            ];
        }

        if (isset($data['journals'])) {
            return $data;
        }

        if (is_array($data) && !empty($data) && isset($data[0]['title'])) {
            return [
                'journals' => $data,
                'total' => count($data)
            ];
        }

        return $data;
    }

    /**
     * Normaliza array de journals para DTOs
     */
    private function normalizeJournals(array $journals): array {
        return array_filter(
            array_map(function($journal) {
                $dto = new JournalDto($journal);
                return $dto->isValid() ? $dto : null;
            }, $journals)
        );
    }

   
    public function testConnection(): array {
        if (!$this->apiUrl || !$this->token) {
            return [
                'success' => false,
                'message' => 'API URL ou token não configurados'
            ];
        }

        $testResult = $this->searchJournals(['q' => '*:*', 'count' => 1]);

        if (isset($testResult['error'])) {
            return [
                'success' => false,
                'message' => $testResult['error']
            ];
        }

        return [
            'success' => true,
            'message' => 'Conexão com BVS API estabelecida com sucesso',
            'total_journals' => $testResult['total'] ?? 0
        ];
    }

    /**
     * Constrói filtro de país no formato da API BVS
     * Exemplo: "en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"
     */
    private function buildCountryFilter(string $country): string {
        // Mapeamento de países em diferentes idiomas
        $countryMappings = [
            'Brazil' => '"en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"',
            'Brasil' => '"en^Brazil|pt-br^Brasil|es^Brasil|fr^Brézil"',
            'Argentina' => '"en^Argentina|pt-br^Argentina|es^Argentina|fr^Argentine"',
            'Chile' => '"en^Chile|pt-br^Chile|es^Chile|fr^Chili"',
            'Colombia' => '"en^Colombia|pt-br^Colômbia|es^Colombia|fr^Colombie"',
            'Mexico' => '"en^Mexico|pt-br^México|es^México|fr^Mexique"',
            'Peru' => '"en^Peru|pt-br^Peru|es^Perú|fr^Pérou"',
            'Uruguay' => '"en^Uruguay|pt-br^Uruguai|es^Uruguay|fr^Uruguay"',
            'Venezuela' => '"en^Venezuela|pt-br^Venezuela|es^Venezuela|fr^Venezuela"',
            // Verificar com o Vini se ele tem uma ideia melhor para isso
        ];

        // Se o país está no mapeamento, usar o formato completo
        if (isset($countryMappings[$country])) {
            return $countryMappings[$country];
        }

        return '"' . $country . '"';
    }
}
