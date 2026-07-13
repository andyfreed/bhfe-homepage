<?php
/**
 * Admin settings — Settings → BHFE Homepage.
 * Maps every destination in the homepage "Find Your Courses" band to an
 * editable URL. An empty field falls back to the built-in default (shown
 * as the field's placeholder), so a fresh install needs no configuration.
 *
 * Two option arrays:
 *  - bhfe_hp_links            key => URL      (section links / chip targets)
 *  - bhfe_hp_cpa_state_links  term_id => URL  (per-state CPA ethics override:
 *        set = send that state straight to the URL, e.g. the course page;
 *        empty = submit to the shop filter targeting that state)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Every mappable link: key => array( admin label, default URL ). */
function bhfe_hp_link_fields() {
    return array(
        'catalog'       => array( 'Intro — “view our full catalog”', '/courses/' ),
        'cpa_all'       => array( 'CPA — All CPA Courses', '/courses/all-cpa-courses/' ),
        'cpa_ethics'    => array( 'CPA — state ethics form target', '/courses/ethics-courses-for-accountants/' ),
        'cfp_all'       => array( 'CFP® — All CFP® Courses', '/courses/cfp-courses/' ),
        'cfp_ethics'    => array( 'CFP® — CFP® Ethics', '/courses/ethics-courses-for-accountants/?credit_type%5B%5D=cfp' ),
        'eaotrp_all'    => array( 'EA / OTRP / ERPA — All Courses', '/eaotrp-courses/' ),
        'eaotrp_ethics' => array( 'EA / OTRP / ERPA — Ethics', '/courses/ethics-courses-for-accountants/?credit_type%5B%5D=eaotrp' ),
        'iar_all'       => array( 'IAR — All IAR Courses', '/courses/all-iar-courses/' ),
        'iar_ethics'    => array( 'IAR — Ethics', '/courses/ethics-courses-for-accountants/?credit_type%5B%5D=iar' ),
        'cima_all'      => array( 'CIMA® / CPWA® / RMA® — All Courses', '/courses/cima-cpwa-rma-courses/' ),
        'cdfa_all'      => array( 'CDFA® — All CDFA® Courses', '/courses/all-cdfa-courses/' ),
    );
}

/** Resolve a mapped link: the saved option if set, else the built-in default. */
function bhfe_hp_link( $key ) {
    $fields = bhfe_hp_link_fields();
    $opts   = get_option( 'bhfe_hp_links', array() );
    $saved  = isset( $opts[ $key ] ) ? trim( (string) $opts[ $key ] ) : '';
    if ( '' !== $saved ) {
        return $saved;
    }
    return isset( $fields[ $key ] ) ? $fields[ $key ][1] : '';
}

/** All selectable states from the live taxonomy (sentinel "All states" terms excluded). */
function bhfe_hp_states() {
    $states = get_terms( array( 'taxonomy' => 'state', 'hide_empty' => false, 'orderby' => 'name' ) );
    $out = array();
    if ( ! is_wp_error( $states ) ) {
        foreach ( $states as $t ) {
            if ( preg_match( '/^all(\s|-)?(states)?$/i', trim( $t->name ) ) ) { continue; }
            $out[] = $t;
        }
    }
    return $out;
}

/** Per-state CPA ethics override URL ('' = none — use the shop filter). */
function bhfe_hp_cpa_state_link( $term_id ) {
    $map = get_option( 'bhfe_hp_cpa_state_links', array() );
    return isset( $map[ (int) $term_id ] ) ? (string) $map[ (int) $term_id ] : '';
}

/** Where a state lands when it has no override: the shop filter targeting CPA + that state. */
function bhfe_hp_cpa_state_default_url( $term_id ) {
    $base = bhfe_hp_link( 'cpa_ethics' );
    $sep  = ( false === strpos( $base, '?' ) ) ? '?' : '&';
    return $base . $sep . 'credit_type%5B%5D=cpa&state=' . (int) $term_id;
}

add_action( 'admin_menu', 'bhfe_hp_admin_menu' );
function bhfe_hp_admin_menu() {
    add_options_page( 'BHFE Homepage', 'BHFE Homepage', 'manage_options', 'bhfe-homepage', 'bhfe_hp_settings_page' );
}

add_action( 'admin_init', 'bhfe_hp_register_settings' );
function bhfe_hp_register_settings() {
    register_setting( 'bhfe_hp_links_group', 'bhfe_hp_links', array(
        'type'              => 'array',
        'sanitize_callback' => 'bhfe_hp_sanitize_links',
        'default'           => array(),
    ) );
    register_setting( 'bhfe_hp_links_group', 'bhfe_hp_cpa_state_links', array(
        'type'              => 'array',
        'sanitize_callback' => 'bhfe_hp_sanitize_state_links',
        'default'           => array(),
    ) );
}

/** Keep only known keys; empty/invalid values are dropped so the default kicks in. */
function bhfe_hp_sanitize_links( $input ) {
    $clean = array();
    if ( ! is_array( $input ) ) {
        return $clean;
    }
    foreach ( array_keys( bhfe_hp_link_fields() ) as $key ) {
        if ( empty( $input[ $key ] ) ) {
            continue;
        }
        $url = esc_url_raw( trim( (string) $input[ $key ] ) );
        if ( '' !== $url ) {
            $clean[ $key ] = $url;
        }
    }
    return $clean;
}

/** Keep only term_id => URL pairs with a real URL; empty rows fall back to the filter. */
function bhfe_hp_sanitize_state_links( $input ) {
    $clean = array();
    if ( ! is_array( $input ) ) {
        return $clean;
    }
    foreach ( $input as $term_id => $url ) {
        $term_id = (int) $term_id;
        if ( $term_id <= 0 || '' === trim( (string) $url ) ) {
            continue;
        }
        $url = esc_url_raw( trim( (string) $url ) );
        if ( '' !== $url ) {
            $clean[ $term_id ] = $url;
        }
    }
    return $clean;
}

function bhfe_hp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $opts      = get_option( 'bhfe_hp_links', array() );
    $state_map = get_option( 'bhfe_hp_cpa_state_links', array() );
    ?>
    <div class="wrap">
        <h1>BHFE Homepage &mdash; Find Your Courses links</h1>
        <p>Where each link and button in the homepage &ldquo;Find Your Courses&rdquo; section goes.
           Leave a field empty to use the default (shown greyed out).
           Relative URLs like <code>/courses/</code> are recommended &mdash; they work unchanged on dev and production.</p>
        <form method="post" action="options.php">
            <?php settings_fields( 'bhfe_hp_links_group' ); ?>
            <h2>Section links</h2>
            <table class="form-table" role="presentation">
                <?php foreach ( bhfe_hp_link_fields() as $key => $f ) :
                    $val = isset( $opts[ $key ] ) ? $opts[ $key ] : '';
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="bhfe-hp-link-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $f[0] ); ?></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text code"
                                id="bhfe-hp-link-<?php echo esc_attr( $key ); ?>"
                                name="bhfe_hp_links[<?php echo esc_attr( $key ); ?>]"
                                value="<?php echo esc_attr( $val ); ?>"
                                placeholder="<?php echo esc_attr( $f[1] ); ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h2>CPA state ethics &mdash; where each state goes</h2>
            <p>Each state has two options:</p>
            <ul style="list-style:disc;padding-left:20px">
                <li><strong>Straight to a course page</strong> &mdash; paste the course&rsquo;s URL in the state&rsquo;s field.</li>
                <li><strong>Shop filter for that state</strong> &mdash; leave the field empty. The visitor lands on the
                    &ldquo;state ethics form target&rdquo; page above, filtered to CPA + their state
                    (the default URL is shown greyed out).</li>
            </ul>
            <table class="form-table" role="presentation">
                <?php foreach ( bhfe_hp_states() as $t ) :
                    $val = isset( $state_map[ $t->term_id ] ) ? $state_map[ $t->term_id ] : '';
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="bhfe-hp-state-<?php echo (int) $t->term_id; ?>"><?php echo esc_html( $t->name ); ?></label>
                        </th>
                        <td>
                            <input type="text" class="large-text code"
                                id="bhfe-hp-state-<?php echo (int) $t->term_id; ?>"
                                name="bhfe_hp_cpa_state_links[<?php echo (int) $t->term_id; ?>]"
                                value="<?php echo esc_attr( $val ); ?>"
                                placeholder="<?php echo esc_attr( bhfe_hp_cpa_state_default_url( $t->term_id ) ); ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php submit_button( 'Save links' ); ?>
        </form>
        <p class="description">State overrides need JavaScript in the visitor&rsquo;s browser; with JS off the dropdown
            always submits to the shop filter, so both routes stay functional.</p>
    </div>
    <?php
}
