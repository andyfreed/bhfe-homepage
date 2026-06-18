<?php
/**
 * BHFE Homepage — front-page template (served via template_include).
 * Theme chrome (header/nav, footer) wraps the plugin-rendered homepage.
 * header.php opens <main role="main" id="Main">; footer.php closes it.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

if ( function_exists( 'bhfe_hp_render' ) ) {
    bhfe_hp_render();
}

get_footer();
