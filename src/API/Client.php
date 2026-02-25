<?php
namespace CP\API;

if (!defined('ABSPATH')) exit;

/**
 * Cliente simples para a API WP REST do site remoto.
 * Espera endpoints como: /wp-json/wp/v2/countries?per_page=...&search=...
 * Endpoint que eu recebo lá na página de cadastro do plugin...
 */

//TODO: assim como o normilizer esse cara pode ser abstraido para ser reutilizado,
// os nomes remetendo a country tem que sair (fazer isso depois)
final class Client {
    private string $endpoint;

    /** Última mensagem de erro (para exibição no shortcode) */
    private string $lastError = '';

    public function __construct(?string $endpoint = null) {
        $this->endpoint = $endpoint ?: (string) get_option('cp_api_endpoint', '');
    }

    public function getLastError(): string {
        return $this->lastError;
    }

    public function getCountryBySlug(string $slug): ?array {
        $this->lastError = '';

        if (!$this->endpoint) {
            $this->lastError = __('Endpoint da API não configurado. Configure em Ajustes → Country Pages.', 'country-pages');
            return null;
        }

        $url = add_query_arg(['slug' => $slug, 'per_page' => 1], $this->endpoint);
        $res = wp_remote_get($url, ['timeout' => 12, 'sslverify' => false]);

        if (is_wp_error($res)) {
            var_dump($res->get_error_message());
            $this->lastError = __('Erro na requisição: ', 'country-pages') . $res->get_error_message()
                . $this->formatRawResponse(null, null, $url);
            return null;
        }

        $code = wp_remote_retrieve_response_code($res);
        $bodyRaw = wp_remote_retrieve_body($res);

        if ($code !== 200) {
            var_dump($code, $bodyRaw);
            $this->lastError = __('Resposta inesperada da API. Código HTTP: ', 'country-pages') . $code
                . $this->formatRawResponse($code, $bodyRaw, $url);
            return null;
        }

        $body = json_decode($bodyRaw, true);
        if (!is_array($body) || empty($body[0])) {
            $this->lastError = __('Nenhum país encontrado para o slug informado.', 'country-pages')
                . $this->formatRawResponse($code, $bodyRaw, $url);
            return null;
        }

        return $body[0];
    }

    private function formatRawResponse(?int $code, ?string $body, string $url): string {
        $maxLen = 2000;
        $parts = [
            'URL' => $url,
            'HTTP' => $code !== null ? (string) $code : '-',
            'Body' => $body !== null && $body !== '' ? (strlen($body) > $maxLen ? substr($body, 0, $maxLen) . '…' : $body) : '(vazio)',
        ];
        $out = "\n\n" . __('Resposta exata da requisição:', 'country-pages') . "\n";
        foreach ($parts as $label => $value) {
            $out .= $label . ': ' . esc_html($value) . "\n";
        }
        return $out;
    }

    //Função de Listagem (pode mudar parametros em breve)
    public function listCountries(array $args = []): array {
        if (!$this->endpoint) return [];

        $defaults = [
            'per_page' => 12,
            'page'     => 1,
            'search'   => '',
        ];
        $query = array_filter(array_merge($defaults, $args), fn($v) => $v !== '' && $v !== null);
        $url   = add_query_arg($query, $this->endpoint);

        $res = wp_remote_get($url, ['timeout' => 12, 'sslverify' => false]);
        if (is_wp_error($res)) return [];
        if (wp_remote_retrieve_response_code($res) !== 200) return [];

        $body = json_decode(wp_remote_retrieve_body($res), true);
        return is_array($body) ? $body : [];
    }
}
