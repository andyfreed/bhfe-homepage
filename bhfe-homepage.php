<?php
/**
 * Plugin Name:       BHFE Homepage
 * Plugin URI:        https://github.com/andyfreed/bhfe-homepage
 * Description:       Self-contained front page for bhfe.com — credential finder (expand-in-place drawers), multi-license picker, promo, benefit cards, and browse row. Takes over the static front page via template_include and enqueues its own front-page-only CSS/JS. Theme-independent; the hero still reads the ACF hero band so staff can edit it in wp-admin.
 * Version:           1.0.0
 * Author:            Beacon Hill Financial Educators
 * License:           GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * Notes:
 *  - Does NOT contain the site-wide .screen-reader-text / Woo price-table fixes; those live in
 *    the theme (site-fixes.css) because they apply everywhere, not just the homepage.
 *  - Reads the ACF hero band from the front page (heading/subheading/CTAs/background image);
 *    falls back to built-in defaults if ACF or the fields are absent.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'BHFE_HP_DIR', plugin_dir_path( __FILE__ ) );
define( 'BHFE_HP_URL', plugin_dir_url( __FILE__ ) );

require_once BHFE_HP_DIR . 'includes/render.php';

/**
 * Take over the static front page with our own template (wins over theme + ACF builder).
 */
add_filter( 'template_include', 'bhfe_hp_template_include', 99 );
function bhfe_hp_template_include( $template ) {
    if ( is_front_page() && ! is_admin() ) {
        $own = BHFE_HP_DIR . 'templates/front-page.php';
        if ( file_exists( $own ) ) {
            return $own;
        }
    }
    return $template;
}

/**
 * Front-page-only assets (filemtime-versioned for cache-busting; Autoptimize aggregates them).
 */
add_action( 'wp_enqueue_scripts', 'bhfe_hp_assets' );
function bhfe_hp_assets() {
    if ( ! is_front_page() ) {
        return;
    }
    $css = BHFE_HP_DIR . 'assets/homepage.css';
    $js  = BHFE_HP_DIR . 'assets/homepage.js';
    wp_enqueue_style( 'bhfe-homepage', BHFE_HP_URL . 'assets/homepage.css', array(), file_exists( $css ) ? filemtime( $css ) : '1.0.0' );
    wp_enqueue_script( 'bhfe-homepage', BHFE_HP_URL . 'assets/homepage.js', array(), file_exists( $js ) ? filemtime( $js ) : '1.0.0', true );
}
