<?php
namespace CP\API;

if (!defined('ABSPATH')) exit;

final class Normalizer {
    /**
     * @param array $raw Ex.: item do /wp/v2/countries
     * @return array{
     *   id:int|string, slug:string, title:string, excerpt?:string,
     *   region?:string, capital?:string, code?:string, flag_url?:string,
     *   content?:string, link?:string, meta?:array
     * }
     */

    //TODO: Eu deveria abstrair mais esta classe (ela funciona não só com country, 
    // poderia receber um mapeamento de ACF como atrubuto e reutilizar para outros CPT ou Post)
    public static function country(array $raw): array {

        //Esses caras são padrão para posts do WP 
        $id    = $raw['id'] ?? '';
        $slug  = $raw['slug'] ?? '';
        $title = $raw['title']['rendered'] ?? '';
        $link  = $raw['link'] ?? '';
        $content = $raw['content']['rendered'] ?? '';
        $excerpt = $raw['excerpt']['rendered'] ?? '';

       
        //Ai podemos ter campos do ACF
        $meta   = $raw['meta'] ?? [];
        $acf    = $raw['acf'] ?? [];


        //Aqui são exemplos para a demo da próxima reunião 
        // (depois vamos mudar conforme o template do marcio)
        //Salvar as chaves no adm e mapear aqui.
        $region   = $acf['region'] ?? ($meta['region'] ?? '');
        $capital  = $acf['capital'] ?? ($meta['capital'] ?? '');
        $code     = $acf['iso_code'] ?? ($meta['iso_code'] ?? '');
        $flag_url = $acf['flag'] ?? ($meta['flag'] ?? '');

        //Aqui é um objeto normalizado (mas esse cara é só para POC)
        return [
            'id'      => $id,
            'slug'    => $slug,
            'title'   => wp_strip_all_tags($title),
            'excerpt' => $excerpt,
            'content' => $content,
            'region'  => $region,
            'capital' => $capital,
            'code'    => $code,
            'flag_url'=> $flag_url,
            'link'    => $link,
            'meta'    => [
                'raw_meta' => $meta,
                'raw_acf'  => $acf,
            ],
        ];
    }


    public static function countryList(array $items): array {
        return array_values(array_map([self::class, 'country'], $items));
    }
}
