<?php
namespace CP\API;

if (!defined('ABSPATH')) exit;

/**
 * Cliente HTTP para a API REST de países (ex.: wp/v2/countries).
 * A URL base vem da opção configurada no admin.
 */
final class Client {
    private string $endpoint;

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

        $url = add_query_arg(
            ['slug' => $slug, 'per_page' => 1, '_embed' => 1],
            $this->endpoint
        );
        $res = wp_remote_get($url, ['timeout' => 12, 'sslverify' => false]);

        if (is_wp_error($res)) {
            $this->lastError = __('Não foi possível consultar os dados do país no momento.', 'country-pages');
            $this->logRequestError(
                'request_error',
                __('Erro na requisição: ', 'country-pages') . $res->get_error_message(),
                null,
                null,
                $url
            );
            return null;
        }

        $code = wp_remote_retrieve_response_code($res);
        $bodyRaw = wp_remote_retrieve_body($res);

        if ($code !== 200) {
            $this->lastError = __('Não foi possível carregar este país agora. Tente novamente em instantes.', 'country-pages');
            $this->logRequestError(
                'unexpected_http_code',
                __('Resposta inesperada da API. Código HTTP: ', 'country-pages') . $code,
                $code,
                $bodyRaw,
                $url
            );
            return null;
        }

        $body = json_decode($bodyRaw, true);
        if (!is_array($body) || empty($body[0])) {
            $this->lastError = __('Nenhum país foi encontrado para este endereço.', 'country-pages');
            $this->logRequestError(
                'country_not_found',
                __('Nenhum país encontrado para o slug informado.', 'country-pages'),
                $code,
                $bodyRaw,
                $url
            );
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

    private function logRequestError(string $context, string $message, ?int $code, ?string $body, string $url): void {
        error_log(
            sprintf(
                '[Country Pages][%s] %s%s',
                $context,
                wp_strip_all_tags($message),
                $this->formatRawResponse($code, $body, $url)
            )
        );
    }

    /** @return array{items: array<int, array<string, mixed>>, total: int, total_pages: int} */
    public function listCountries(array $args = []): array {
        $empty = ['items' => [], 'total' => 0, 'total_pages' => 0];
        if (!$this->endpoint) {
            return $empty;
        }

        $defaults = [
            'per_page' => 12,
            'page'     => 1,
            'search'   => '',
        ];
        $query = array_merge($defaults, $args, ['_embed' => 1]);
        $query = array_filter(
            $query,
            static function ($v, $k): bool {
                if ($v === '' || $v === null) {
                    return false;
                }
                if (($k === 'tags' || $k === 'categories') && (int) $v < 1) {
                    return false;
                }
                return true;
            },
            ARRAY_FILTER_USE_BOTH
        );
        $url   = add_query_arg($query, $this->endpoint);

        $res = wp_remote_get($url, ['timeout' => 12, 'sslverify' => false]);
        if (is_wp_error($res)) {
            return $empty;
        }
        if (wp_remote_retrieve_response_code($res) !== 200) {
            return $empty;
        }

        $body = json_decode(wp_remote_retrieve_body($res), true);
        $items = is_array($body) ? $body : [];

        $total = (int) wp_remote_retrieve_header($res, 'x-wp-total');
        $totalPages = (int) wp_remote_retrieve_header($res, 'x-wp-totalpages');

        if ($total < 1 && $items !== []) {
            $total = count($items);
        }
        $perPage = max(1, (int) ($query['per_page'] ?? 12));
        if ($totalPages < 1 && $total > 0) {
            $totalPages = (int) max(1, ceil($total / $perPage));
        }
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        return [
            'items'       => $items,
            'total'       => $total,
            'total_pages' => $totalPages,
        ];
    }

    public function getRestBaseUrl(): string {
        $e = trim($this->endpoint);
        if ($e === '') {
            return '';
        }
        $e = (string) strtok($e, '?');
        $pos = strpos($e, '/wp-json');
        if ($pos === false) {
            return '';
        }
        return substr($e, 0, $pos + strlen('/wp-json'));
    }

    /** @return array<int, array<string, mixed>> */
    public function getRestCollection(string $route, array $query = []): array {
        $base = $this->getRestBaseUrl();
        if ($base === '') {
            return [];
        }
        $route = ltrim($route, '/');
        $url = trailingslashit($base) . $route;
        $query = array_merge(
            [
                'per_page' => 100,
            ],
            $query
        );
        $url = add_query_arg($query, $url);

        $res = wp_remote_get($url, ['timeout' => 12, 'sslverify' => false]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
            return [];
        }
        $body = json_decode(wp_remote_retrieve_body($res), true);
        return is_array($body) ? $body : [];
    }

    /** @return array<int, array{id: int, name: string, slug: string}> */
    public function getTags(array $query = []): array {
        $route = (string) apply_filters('cp_country_api_tags_route', 'wp/v2/tags');
        $raw = $this->getRestCollection($route, $query);
        return $this->normalizeTermList($raw);
    }

    /** @return array<int, array{id: int, name: string, slug: string}> */
    public function getCategories(array $query = []): array {
        $route = (string) apply_filters('cp_country_api_categories_route', 'wp/v2/categories');
        $raw = $this->getRestCollection($route, $query);
        return $this->normalizeTermList($raw);
    }

    /**
     * @param array<int, array<string, mixed>> $raw
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    private function normalizeTermList(array $raw): array {
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            if ($id < 1) {
                continue;
            }
            $name = $row['name'] ?? '';
            if (is_array($name)) {
                $name = (string) ($name['rendered'] ?? '');
            } else {
                $name = (string) $name;
            }
            $name = wp_strip_all_tags($name);
            $out[] = [
                'id'   => $id,
                'name' => $name,
                'slug' => (string) ($row['slug'] ?? ''),
            ];
        }
        return $out;
    }
}
