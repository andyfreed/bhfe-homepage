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

/** All subjects from the live taxonomy (for the shop-filter subject dropdown). */
function bhfe_hp_subjects() {
    $subjects = get_terms( array( 'taxonomy' => 'subject', 'hide_empty' => false, 'orderby' => 'name' ) );
    return is_wp_error( $subjects ) ? array() : $subjects;
}

/** Shop-filter customization for the CPA state fallback: array( 'subject' => term_id|0 ). */
function bhfe_hp_cpa_filter() {
    $f = get_option( 'bhfe_hp_cpa_filter', array() );
    return array( 'subject' => isset( $f['subject'] ) ? (int) $f['subject'] : 0 );
}

/** Per-state CPA ethics override URL ('' = none). Highest priority for a state. */
function bhfe_hp_cpa_state_link( $term_id ) {
    $map = get_option( 'bhfe_hp_cpa_state_links', array() );
    return isset( $map[ (int) $term_id ] ) ? (string) $map[ (int) $term_id ] : '';
}

/** Per-state subject override for the shop-filter route (0 = none — use the default subject). */
function bhfe_hp_cpa_state_subject( $term_id ) {
    $map = get_option( 'bhfe_hp_cpa_state_subjects', array() );
    return isset( $map[ (int) $term_id ] ) ? (int) $map[ (int) $term_id ] : 0;
}

/** The shop-filter URL for a state: CPA + state + subject (state's own subject if set,
 *  else the default subject, else none). Mirrors what the native GET form submits. */
function bhfe_hp_cpa_state_default_url( $term_id ) {
    $base    = bhfe_hp_link( 'cpa_ethics' );
    $sep     = ( false === strpos( $base, '?' ) ) ? '?' : '&';
    $subject = bhfe_hp_cpa_state_subject( $term_id );
    if ( $subject <= 0 ) {
        $filter  = bhfe_hp_cpa_filter();
        $subject = $filter['subject'];
    }
    $url = $base . $sep . 'credit_type%5B%5D=cpa';
    if ( $subject > 0 ) {
        $url .= '&subject=' . $subject;
    }
    return $url . '&state=' . (int) $term_id;
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
    register_setting( 'bhfe_hp_links_group', 'bhfe_hp_cpa_filter', array(
        'type'              => 'array',
        'sanitize_callback' => 'bhfe_hp_sanitize_cpa_filter',
        'default'           => array(),
    ) );
    register_setting( 'bhfe_hp_links_group', 'bhfe_hp_cpa_state_subjects', array(
        'type'              => 'array',
        'sanitize_callback' => 'bhfe_hp_sanitize_state_subjects',
        'default'           => array(),
    ) );
}

/** Normalize a term name for CSV matching: decode entities, collapse whitespace, lowercase.
 *  WP stores '&' in term names as '&amp;', and hand-edited CSVs pick up stray spaces —
 *  without this, visually-identical names silently fail to match. */
function bhfe_hp_norm_name( $name ) {
    $name = html_entity_decode( (string) $name, ENT_QUOTES );
    $name = str_replace( array( '-', '–' ), ' ', $name ); // dashes count as spaces ("Ethics-State" == "Ethics State")
    $name = preg_replace( '/\s+/', ' ', trim( $name ) );
    return strtolower( $name );
}

/** Keep only state_term_id => subject_term_id pairs (0/empty = no override, dropped). */
function bhfe_hp_sanitize_state_subjects( $input ) {
    $clean = array();
    if ( ! is_array( $input ) ) {
        return $clean;
    }
    foreach ( $input as $term_id => $subject_id ) {
        $term_id    = (int) $term_id;
        $subject_id = absint( $subject_id );
        if ( $term_id > 0 && $subject_id > 0 ) {
            $clean[ $term_id ] = $subject_id;
        }
    }
    return $clean;
}

/** Only a subject term id (0 = don't pre-select a subject). */
function bhfe_hp_sanitize_cpa_filter( $input ) {
    return array( 'subject' => isset( $input['subject'] ) ? absint( $input['subject'] ) : 0 );
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

/* ---------- CSV export / import ----------
 * Format: section,name,url
 *   link,<key>,<url>        e.g. link,cpa_all,/courses/all-cpa-courses/
 *   cpa_state,<State>,<url> e.g. cpa_state,California,/courses/ca-ethics/
 * Export writes EVERY row (empty url = no override, default in use), so the
 * file doubles as a fill-in template. Import is authoritative for the rows it
 * contains: a url sets that override, an empty url clears it; rows not in the
 * file are left untouched; unknown names are counted and skipped. */

add_action( 'admin_post_bhfe_hp_export_csv', 'bhfe_hp_export_csv' );
function bhfe_hp_export_csv() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Insufficient permissions.' );
    }
    check_admin_referer( 'bhfe_hp_csv' );

    $opts       = get_option( 'bhfe_hp_links', array() );
    $state_map  = get_option( 'bhfe_hp_cpa_state_links', array() );
    $state_subs = get_option( 'bhfe_hp_cpa_state_subjects', array() );
    $sub_names  = array();
    foreach ( bhfe_hp_subjects() as $s ) {
        $sub_names[ (int) $s->term_id ] = $s->name;
    }

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="bhfe-homepage-links-' . gmdate( 'Ymd' ) . '.csv"' );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'section', 'name', 'url', 'subject' ) );
    foreach ( bhfe_hp_link_fields() as $key => $f ) {
        fputcsv( $out, array( 'link', $key, isset( $opts[ $key ] ) ? $opts[ $key ] : '', '' ) );
    }
    foreach ( bhfe_hp_states() as $t ) {
        $sub = isset( $state_subs[ $t->term_id ] ) ? (int) $state_subs[ $t->term_id ] : 0;
        fputcsv( $out, array(
            'cpa_state',
            $t->name,
            isset( $state_map[ $t->term_id ] ) ? $state_map[ $t->term_id ] : '',
            ( $sub && isset( $sub_names[ $sub ] ) ) ? $sub_names[ $sub ] : '',
        ) );
    }
    fclose( $out );
    exit;
}

add_action( 'admin_post_bhfe_hp_import_csv', 'bhfe_hp_import_csv' );
function bhfe_hp_import_csv() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Insufficient permissions.' );
    }
    check_admin_referer( 'bhfe_hp_csv' );

    $set = 0; $cleared = 0; $skipped = 0;
    if ( empty( $_FILES['bhfe_hp_csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['bhfe_hp_csv']['tmp_name'] ) ) {
        wp_safe_redirect( add_query_arg( array( 'page' => 'bhfe-homepage', 'bhfe_csv' => 'nofile' ), admin_url( 'options-general.php' ) ) );
        exit;
    }

    $opts       = get_option( 'bhfe_hp_links', array() );
    $state_map  = get_option( 'bhfe_hp_cpa_state_links', array() );
    $state_subs = get_option( 'bhfe_hp_cpa_state_subjects', array() );
    $keys       = array_keys( bhfe_hp_link_fields() );
    $by_name    = array();
    foreach ( bhfe_hp_states() as $t ) {
        $by_name[ bhfe_hp_norm_name( $t->name ) ] = (int) $t->term_id;
    }
    $sub_by_name = array();
    foreach ( bhfe_hp_subjects() as $s ) {
        $sub_by_name[ bhfe_hp_norm_name( $s->name ) ] = (int) $s->term_id;
    }
    $skip_detail = array();

    $fh = fopen( $_FILES['bhfe_hp_csv']['tmp_name'], 'r' );
    if ( $fh ) {
        while ( ( $row = fgetcsv( $fh ) ) !== false ) {
            $section = isset( $row[0] ) ? strtolower( trim( $row[0] ) ) : '';
            $name    = isset( $row[1] ) ? trim( $row[1] ) : '';
            $url     = isset( $row[2] ) ? trim( $row[2] ) : '';
            $subject = isset( $row[3] ) ? trim( $row[3] ) : '';
            if ( '' === $section || 'section' === $section ) { continue; } // blank / header row
            if ( 'link' === $section && in_array( $name, $keys, true ) ) {
                if ( '' === $url ) {
                    if ( isset( $opts[ $name ] ) ) { unset( $opts[ $name ] ); $cleared++; }
                    continue;
                }
                $clean = esc_url_raw( $url );
                if ( '' !== $clean ) { $opts[ $name ] = $clean; $set++; }
                else { $skipped++; $skip_detail[] = "link \"$name\": invalid URL"; }
            } elseif ( 'cpa_state' === $section && isset( $by_name[ bhfe_hp_norm_name( $name ) ] ) ) {
                $tid = $by_name[ bhfe_hp_norm_name( $name ) ];
                // url column
                if ( '' === $url ) {
                    if ( isset( $state_map[ $tid ] ) ) { unset( $state_map[ $tid ] ); $cleared++; }
                } else {
                    $clean = esc_url_raw( $url );
                    if ( '' !== $clean ) { $state_map[ $tid ] = $clean; $set++; }
                    else { $skipped++; $skip_detail[] = "$name: invalid URL"; }
                }
                // subject column (matched by subject NAME so the CSV ports across environments)
                if ( '' === $subject ) {
                    if ( isset( $state_subs[ $tid ] ) ) { unset( $state_subs[ $tid ] ); $cleared++; }
                } elseif ( isset( $sub_by_name[ bhfe_hp_norm_name( $subject ) ] ) ) {
                    $state_subs[ $tid ] = $sub_by_name[ bhfe_hp_norm_name( $subject ) ];
                    $set++;
                } else {
                    $skipped++;
                    $skip_detail[] = "$name: no subject named \"$subject\" exists on this site";
                }
            } else {
                $skipped++;
                $skip_detail[] = "unrecognized row \"$section, $name\"";
            }
        }
        fclose( $fh );
    }
    if ( $skip_detail ) {
        set_transient( 'bhfe_hp_csv_skipped_' . get_current_user_id(), array_slice( $skip_detail, 0, 12 ), 300 );
    }

    update_option( 'bhfe_hp_links', $opts );
    update_option( 'bhfe_hp_cpa_state_links', $state_map );
    update_option( 'bhfe_hp_cpa_state_subjects', $state_subs );

    wp_safe_redirect( add_query_arg( array(
        'page'     => 'bhfe-homepage',
        'bhfe_csv' => $set . '-' . $cleared . '-' . $skipped,
    ), admin_url( 'options-general.php' ) ) );
    exit;
}

function bhfe_hp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $opts      = get_option( 'bhfe_hp_links', array() );
    $state_map = get_option( 'bhfe_hp_cpa_state_links', array() );

    if ( isset( $_GET['bhfe_csv'] ) ) {
        $flag = sanitize_text_field( wp_unslash( $_GET['bhfe_csv'] ) );
        if ( 'nofile' === $flag ) {
            echo '<div class="notice notice-error"><p>Import failed: no CSV file was uploaded.</p></div>';
        } elseif ( preg_match( '/^(\d+)-(\d+)-(\d+)$/', $flag, $m ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>CSV imported: '
                . (int) $m[1] . ' override(s) set, ' . (int) $m[2] . ' cleared (back to default), '
                . (int) $m[3] . ' row(s) skipped.</p>';
            $skip_detail = get_transient( 'bhfe_hp_csv_skipped_' . get_current_user_id() );
            if ( is_array( $skip_detail ) && $skip_detail ) {
                echo '<p><strong>Skipped rows:</strong></p><ul style="list-style:disc;padding-left:20px;margin-top:0">';
                foreach ( $skip_detail as $d ) {
                    echo '<li>' . esc_html( $d ) . '</li>';
                }
                echo '</ul>';
                delete_transient( 'bhfe_hp_csv_skipped_' . get_current_user_id() );
            }
            echo '</div>';
        }
    }
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
            <p>Each state resolves in this order:</p>
            <ol style="list-style:decimal;padding-left:20px">
                <li><strong>Course page URL</strong> &mdash; one required course (e.g. MA)? Paste its URL and the visitor
                    goes straight there.</li>
                <li><strong>State&rsquo;s own subject</strong> &mdash; multiple courses (e.g. CA)? Tag them with a subject and
                    pick it here; the visitor lands on the shop page filtered to that subject + their state.</li>
                <li><strong>Default</strong> &mdash; neither set: shop page filtered to CPA + their state
                    (+ the default subject below, if chosen). The greyed-out URL shows exactly where each state goes.</li>
            </ol>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="bhfe-hp-cpa-subject">Default subject (states without their own)</label></th>
                    <td>
                        <?php $filter = bhfe_hp_cpa_filter(); $subjects = bhfe_hp_subjects(); ?>
                        <select id="bhfe-hp-cpa-subject" name="bhfe_hp_cpa_filter[subject]">
                            <option value="0"<?php selected( 0, $filter['subject'] ); ?>>&mdash; none (page default) &mdash;</option>
                            <?php foreach ( $subjects as $s ) : ?>
                                <option value="<?php echo (int) $s->term_id; ?>"<?php selected( $s->term_id, $filter['subject'] ); ?>><?php echo esc_html( $s->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Pre-selected on the shop-filter landing along with the visitor&rsquo;s chosen state.
                            Ignored for states with a course-page URL or their own subject.</p>
                    </td>
                </tr>
            </table>
            <?php $state_subjects = get_option( 'bhfe_hp_cpa_state_subjects', array() ); ?>
            <table class="form-table" role="presentation">
                <?php foreach ( bhfe_hp_states() as $t ) :
                    $val = isset( $state_map[ $t->term_id ] ) ? $state_map[ $t->term_id ] : '';
                    $sub = isset( $state_subjects[ $t->term_id ] ) ? (int) $state_subjects[ $t->term_id ] : 0;
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="bhfe-hp-state-<?php echo (int) $t->term_id; ?>"><?php echo esc_html( $t->name ); ?></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text code" style="width:420px;max-width:100%"
                                id="bhfe-hp-state-<?php echo (int) $t->term_id; ?>"
                                name="bhfe_hp_cpa_state_links[<?php echo (int) $t->term_id; ?>]"
                                value="<?php echo esc_attr( $val ); ?>"
                                placeholder="<?php echo esc_attr( bhfe_hp_cpa_state_default_url( $t->term_id ) ); ?>">
                            &nbsp;or subject:&nbsp;
                            <label class="screen-reader-text" for="bhfe-hp-state-subject-<?php echo (int) $t->term_id; ?>"><?php echo esc_html( $t->name ); ?> subject</label>
                            <select id="bhfe-hp-state-subject-<?php echo (int) $t->term_id; ?>"
                                name="bhfe_hp_cpa_state_subjects[<?php echo (int) $t->term_id; ?>]">
                                <option value="0"<?php selected( 0, $sub ); ?>>&mdash; default &mdash;</option>
                                <?php foreach ( $subjects as $s ) : ?>
                                    <option value="<?php echo (int) $s->term_id; ?>"<?php selected( $s->term_id, $sub ); ?>><?php echo esc_html( $s->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php submit_button( 'Save links' ); ?>
        </form>
        <p class="description">State overrides need JavaScript in the visitor&rsquo;s browser; with JS off the dropdown
            always submits to the shop filter, so both routes stay functional.</p>

        <hr>
        <h2>Export / import CSV</h2>
        <p>Export downloads every mapping above as a CSV (<code>section,name,url,subject</code>) &mdash; empty cells
           are using the default, so the file doubles as a template: fill in the <code>url</code> column
           (direct course page) and/or the <code>subject</code> column (subject name, for the shop-filter route)
           and import it back. Importing sets the rows in the file (empty cell = back to default) and leaves
           rows not in the file untouched.</p>
        <form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:24px">
            <input type="hidden" name="action" value="bhfe_hp_export_csv">
            <?php wp_nonce_field( 'bhfe_hp_csv' ); ?>
            <?php submit_button( 'Export CSV', 'secondary', 'submit', false ); ?>
        </form>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="display:inline-block">
            <input type="hidden" name="action" value="bhfe_hp_import_csv">
            <?php wp_nonce_field( 'bhfe_hp_csv' ); ?>
            <input type="file" name="bhfe_hp_csv" accept=".csv,text/csv" required>
            <?php submit_button( 'Import CSV', 'secondary', 'submit', false ); ?>
        </form>
    </div>
    <?php
}
