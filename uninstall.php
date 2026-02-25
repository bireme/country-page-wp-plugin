<?php
// Se desejar remover opções ao desinstalar:
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

delete_option('cp_api_endpoint');
delete_option('cp_custom_css');
delete_option('cp_custom_js');
