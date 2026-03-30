<?php
/** Layout mínimo para rota de país; HTML vem de $GLOBALS['cp_country_standalone']. */
if (!defined('ABSPATH')) exit;

get_header();

$cp = $GLOBALS['cp_country_standalone'] ?? null;
$html = is_array($cp) ? (string) ($cp['html'] ?? '') : '';
?>
<main id="cp-country-standalone" class="site-main cp-country-standalone">
    <?php echo $html; ?>
</main>
<?php
get_footer();
