<?php
/**
 * Admin settings — Settings → BHFE Homepage.
 * Maps every destination in the homepage "Find Your Courses" band to an
 * editable URL. An empty field falls back to the built-in default (shown
 * as the field's placeholder), so a fresh install needs no configuration.
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

function bhfe_hp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $opts = get_option( 'bhfe_hp_links', array() );
    ?>
    <div class="wrap">
        <h1>BHFE Homepage &mdash; Find Your Courses links</h1>
        <p>Where each link and button in the homepage &ldquo;Find Your Courses&rdquo; section goes.
           Leave a field empty to use the default (shown greyed out).
           Relative URLs like <code>/courses/</code> are recommended &mdash; they work unchanged on dev and production.</p>
        <form method="post" action="options.php">
            <?php settings_fields( 'bhfe_hp_links_group' ); ?>
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
            <?php submit_button( 'Save links' ); ?>
        </form>
        <p class="description">The CPA state dropdown submits to the &ldquo;state ethics form target&rdquo; with the chosen state appended automatically.</p>
    </div>
    <?php
}
