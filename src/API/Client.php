<?php
namespace CP\API;

use CP\Support\Cache;

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

    public function __construct(?string $endpoint = null) {
        $this->endpoint = $endpoint ?: (string) get_option('cp_api_endpoint', '');
    }

    public function getCountryBySlug(string $slug): ?array {
        if (!$this->endpoint) return null;

        $url = add_query_arg(['slug' => $slug, 'per_page' => 1], $this->endpoint);
        $cacheKey = 'cp_country_' . md5($url);

        //TODO: será que vale a pena mesmo usar esse cache (vai me dar dor de cabeça...)
        $result = Cache::remember($cacheKey, function () use ($url) {
            $res = wp_remote_get($url, ['timeout' => 12, 'sslverify' => true]);
            if (is_wp_error($res)) return null;
            $code = wp_remote_retrieve_response_code($res);
            if ($code !== 200) return null;
            $body = json_decode(wp_remote_retrieve_body($res), true);
            if (!is_array($body) || empty($body[0])) return null;
            return $body[0];
        });

        // Ensure we return the correct type - array or null
        return is_array($result) ? $result : null;
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
        $cacheKey = 'cp_country_list_' . md5($url);

        //TODO: avaliar se isso é necessário mesmo, não sei se vai ajudar ou só encher a paciencia...
        return Cache::remember($cacheKey, function () use ($url) {
            $res = wp_remote_get($url, ['timeout' => 12, 'sslverify' => true]);
            if (is_wp_error($res)) return [];
            $code = wp_remote_retrieve_response_code($res);
            if ($code !== 200) return [];
            $body = json_decode(wp_remote_retrieve_body($res), true);
            return is_array($body) ? $body : [];
        });
    }
}
