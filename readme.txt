=== Country Pages ===
Contributors: Jefferson Augusto Lopes
Tags: countries, rest api, shortcode, acf, slider
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Consome uma API REST de países (por exemplo CPT exposto no WordPress) e exibe os dados com shortcodes, templates e páginas públicas em URL amigável.

== Descrição ==

O **Country Pages** integra o site a um endpoint REST de países e oferece:

* **Configurações** — URL da API, prefixo das páginas públicas (`/prefixo/slug/`, padrão `pais`), CSS e JavaScript customizados no front (apenas quando quem salvar tiver permissão para HTML não filtrado).
* **Shortcodes**
  * `[country slug="…"]` — cartão ou bloco do país; opcionais `title-only`, `content-only`, `image-only` (valor `true` restringe a parte exibida).
  * `[country_list]` — lista com filtros (busca, região, tag, categoria), paginação e query strings `cp_cl_search`, `cp_cl_tag`, `cp_cl_cat`, `cp_cl_page`.
  * `[country_slider]` — carrossel; atributos como `itens`, `itens-per-round`, `loop`, `tag-filter`, `category-filter`, `region`, `search` (também aceita aliases com sublinhado: `itens_per_round`, `tag_filter`, `category_filter`).
* **URLs públicas** — o mesmo conteúdo ampliado do país em `/{prefixo}/{slug}/`, alinhado ao slug usado em `[country]`. Após alterar o prefixo, verifique **Ajustes → Links permanentes** se necessário.
* **Templates** — modo padrão ou arquivos em `Templates/Custom/custom-country.php` e `Templates/Custom/custom-list.php`.
* **Mapeamento ACF** — define quais campos ACF da API aparecem nos templates.

No admin: **Country Pages** (visão geral e Sobre), **Configurações**, **Templates** e **Mapeamento ACF**.

Text domain: `country-pages` (pasta `languages/`).

== Instalação ==

1. Envie a pasta do plugin para `wp-content/plugins/` ou instale pelo painel.
2. Ative o plugin em **Plugins**.
3. Em **Country Pages → Configurações**, informe o endpoint da API (ex.: `https://exemplo.com/wp-json/wp/v2/countries`).
4. Opcional: ajuste o prefixo da URL do país, templates customizados e mapeamento ACF.
5. Use os shortcodes em páginas ou posts, ou acesse `/prefixo/slug-do-pais/`.

== Perguntas frequentes ==

= O que preciso na API? =

Um endpoint REST que devolva países no formato esperado pelo plugin (normalização no código). O endpoint padrão configurável aponta para a rota de países no estilo `wp/v2/countries`.

= Mudei o prefixo da URL e deu 404 =

Salve as configurações e, se o 404 continuar, abra **Ajustes → Links permanentes** e salve de novo para atualizar as regras de reescrita.

= O CSS/JS customizado não aparece =

Só é impresso no site se houver conteúdo salvo e o usuário que salvou tiver a capacidade `unfiltered_html` (comportamento de segurança).

== Changelog ==

= 1.0.0 =
* Versão inicial pública: shortcodes country, country_list e country_slider, rotas públicas, templates, ACF e opções no admin.
