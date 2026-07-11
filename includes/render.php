<?php
/**
 * BHFE Homepage — markup renderers.
 *
 * Self-contained: the homepage UI lives here, not in ACF flexible content.
 * The HERO still reads the ACF hero band on the front page (so staff can edit
 * heading/subheading/CTAs/background image in wp-admin), falling back to
 * sensible defaults if those fields are empty.
 *
 * Data is wired to BHFE's verified routes:
 *  - "Full catalog" tiles -> real credential category pages
 *  - Ethics tiles -> portable credit_type slug filter
 *  - CPA ethics -> state-gated; state <option>s from the live `state` taxonomy (sentinels excluded)
 *  - Browse pills -> subject term IDs resolved by name at render time (catalog filters on ID, not slug)
 *  - Multi-license -> /courses/?credit_type[]=a&credit_type[]=b… (intersected once the catalog ANDs)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Escape hero text. NOTE: the theme hooks the `esc_html` filter (bhfe_allow_sup) to
 *  superscript ® site-wide, so esc_html() both escapes AND superscripts — we must NOT
 *  convert ® again here or it double-wraps (<sup><sup>…</sup></sup>). */
function bhfe_hp_sup( $text ) {
    return esc_html( $text );
}

/** Ethics catalog URL for a credit_type slug (portable; verified). */
function bhfe_hp_ethics_url( $slug ) {
    return '/courses/ethics-courses-for-accountants/?credit_type%5B%5D=' . rawurlencode( $slug );
}

/** Resolve a subject term ID by name at render time; fallback to known local ID. */
function bhfe_hp_subject_id( $name, $fallback_id ) {
    $t = get_term_by( 'name', $name, 'subject' );
    return ( $t && ! is_wp_error( $t ) ) ? (int) $t->term_id : (int) $fallback_id;
}

/** Subject filter URL — resolve term ID by name at render time, fallback to known local ID. */
function bhfe_hp_subject_url( $name, $fallback_id ) {
    return '/courses/?subject=' . rawurlencode( bhfe_hp_subject_id( $name, $fallback_id ) );
}

/** Subject filter URL scoped to one credential (credit_type[] + subject). */
function bhfe_hp_license_subject_url( $slug, $name, $fallback_id ) {
    return '/courses/?credit_type%5B%5D=' . rawurlencode( $slug )
        . '&subject=' . rawurlencode( bhfe_hp_subject_id( $name, $fallback_id ) );
}

/** The six credentials. 'all' -> real category page; 'slug' feeds the multi-license chips. */
function bhfe_hp_credentials() {
    return array(
        array( 'id'=>'cpa', 'name'=>'CPA', 'chip'=>'CPA', 'short'=>'CPA', 'slug'=>'cpa', 'tag'=>'Certified Public Accountant',
            'ethics'=>'state',
            'all'=>array( 'label'=>'All CPA Courses', 'href'=>'/courses/all-cpa-courses/' ) ),
        array( 'id'=>'cfp', 'name'=>'CFP<sup>&reg;</sup>', 'chip'=>'CFP<sup>&reg;</sup>', 'short'=>'CFP', 'slug'=>'cfp', 'tag'=>'Certified Financial Planner',
            'ethics'=>array( 'label'=>'CFP Board Ethics', 'href'=>bhfe_hp_ethics_url('cfp') ),
            'all'=>array( 'label'=>'All CFP Courses', 'href'=>'/courses/cfp-courses/' ) ),
        array( 'id'=>'eaotrp', 'name'=>'EA / OTRP / ERPA', 'chip'=>'EA / OTRP / ERPA', 'short'=>'EA/OTRP/ERPA', 'slug'=>'eaotrp', 'tag'=>'IRS-enrolled professionals',
            'ethics'=>array( 'label'=>'IRS Ethics Courses', 'href'=>bhfe_hp_ethics_url('eaotrp') ),
            'all'=>array( 'label'=>'All IRS Courses', 'href'=>'/eaotrp-courses/' ) ),
        array( 'id'=>'iar', 'name'=>'IAR', 'chip'=>'IAR', 'short'=>'IAR', 'slug'=>'iar', 'tag'=>'Investment Adviser Representative',
            'ethics'=>array( 'label'=>'IAR Ethics', 'href'=>bhfe_hp_ethics_url('iar') ),
            'all'=>array( 'label'=>'All IAR Courses', 'href'=>'/courses/all-iar-courses/' ) ),
        array( 'id'=>'cima', 'name'=>'CIMA<sup>&reg;</sup> / CPWA<sup>&reg;</sup> / RMA<sup>&reg;</sup>', 'chip'=>'CIMA<sup>&reg;</sup>', 'short'=>'CIMA', 'slug'=>'iwicimaall', 'tag'=>'Investments &amp; Wealth Institute',
            'ethics'=>null,
            'all'=>array( 'label'=>'All CIMA Courses', 'href'=>'/courses/cima-cpwa-rma-courses/' ) ),
        array( 'id'=>'cdfa', 'name'=>'CDFA<sup>&reg;</sup>', 'chip'=>'CDFA<sup>&reg;</sup>', 'short'=>'CDFA', 'slug'=>'cdfa', 'tag'=>'Certified Divorce Financial Analyst',
            'ethics'=>null,
            'all'=>array( 'label'=>'All CDFA Courses', 'href'=>'/courses/all-cdfa-courses/' ) ),
    );
}

/** A drawer option tile (anchor). */
function bhfe_hp_opt_tile( $kicker, $label, $desc, $href ) {
    return '<a class="bhfe-opt" href="' . esc_url( $href ) . '">'
        . '<span class="bhfe-opt__kicker">' . esc_html( $kicker ) . '</span>'
        . '<span><span class="bhfe-opt__label">' . esc_html( $label ) . '</span>'
        . '<span class="bhfe-opt__desc">' . wp_kses_post( $desc ) . '</span></span>'
        . '<span class="bhfe-opt__cta">Browse <span aria-hidden="true">&rarr;</span></span>'
        . '</a>';
}

/** CPA state-gated ethics form. State options from the live taxonomy; sentinels excluded. */
function bhfe_hp_state_form() {
    $states = get_terms( array( 'taxonomy'=>'state', 'hide_empty'=>false, 'orderby'=>'name' ) );
    $opts = '<option value="">Choose your state&hellip;</option>';
    if ( ! is_wp_error( $states ) ) {
        foreach ( $states as $t ) {
            if ( preg_match( '/^all(\s|-)?(states)?$/i', trim( $t->name ) ) ) { continue; }
            $opts .= '<option value="' . esc_attr( $t->term_id ) . '">' . esc_html( $t->name ) . '</option>';
        }
    }
    return '<form class="bhfe-state" method="get" action="' . esc_url( '/courses/ethics-courses-for-accountants/' ) . '">'
        . '<span class="bhfe-opt__kicker">Ethics</span>'
        . '<span><span class="bhfe-opt__label">CPA Ethics</span>'
        . '<span class="bhfe-opt__desc">Ethics requirements differ by state &mdash; pick yours to get the right course.</span></span>'
        . '<input type="hidden" name="credit_type[]" value="cpa">'
        . '<label class="bhfe-sr-only" for="bhfe-cpa-state">Your state</label>'
        . '<select class="bhfe-state__select" id="bhfe-cpa-state" name="state">' . $opts . '</select>'
        . '<button class="bhfe-state__go" type="submit"><span class="bhfe-state__go-label">View ethics course</span> <span aria-hidden="true">&rarr;</span></button>'
        . '</form>';
}

/** Just the <option>s for a state <select> (sentinels excluded). Shared by the pickers. */
function bhfe_hp_state_options( $placeholder = 'Choose your state&hellip;' ) {
    $states = get_terms( array( 'taxonomy'=>'state', 'hide_empty'=>false, 'orderby'=>'name' ) );
    $opts = '<option value="">' . $placeholder . '</option>';
    if ( ! is_wp_error( $states ) ) {
        foreach ( $states as $t ) {
            if ( preg_match( '/^all(\s|-)?(states)?$/i', trim( $t->name ) ) ) { continue; }
            $opts .= '<option value="' . esc_attr( $t->term_id ) . '">' . esc_html( $t->name ) . '</option>';
        }
    }
    return $opts;
}

/** HERO — reads the ACF hero band on the front page; falls back to defaults. */
function bhfe_hp_hero() {
    $pid     = (int) get_option( 'page_on_front' );
    $heading = 'CPE & CE Courses for Financial Professionals';
    $sub     = 'Self-study courses in print or PDF — approved for CPA, CFP®, EA, and more. Instant access after checkout.';
    $cta     = array( 'title'=>'Find your courses', 'url'=>'#find-courses', 'target'=>'' );
    $cta2    = array( 'title'=>'Ethics courses', 'url'=>'/courses/ethics-courses-for-accountants/', 'target'=>'' );
    $bg      = 0;

    if ( $pid && function_exists( 'have_rows' ) && have_rows( 'flexible_content', $pid ) ) {
        while ( have_rows( 'flexible_content', $pid ) ) {
            the_row();
            if ( get_row_layout() === 'hero' ) {
                $h = get_sub_field( 'heading' );          if ( $h ) { $heading = $h; }
                $s = get_sub_field( 'subheading' );        if ( $s ) { $sub = $s; }
                $b = get_sub_field( 'cta_button' );        if ( $b ) { $cta = $b; }
                $b2 = get_sub_field( 'cta_button_2' );     if ( $b2 ) { $cta2 = $b2; }
                $i = get_sub_field( 'background_image' );  if ( $i ) { $bg = $i; }
                break;
            }
        }
    }

    $btn = function( $link, $ghost = false, $anchor = '' ) {
        if ( empty( $link ) || empty( $link['url'] ) ) { return ''; }
        $cls = 'bhfe-hero__btn' . ( $ghost ? ' bhfe-hero__btn--ghost' : '' );
        // Primary CTA scrolls to the course-finder band instead of navigating away.
        if ( $anchor ) {
            return '<a class="' . $cls . ' bhfe-hero__btn--scroll" href="' . esc_attr( $anchor ) . '">' . bhfe_hp_sup( $link['title'] ) . '</a>';
        }
        $target = ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '"' : '';
        return '<a class="' . $cls . '" href="' . esc_url( $link['url'] ) . '"' . $target . '>' . bhfe_hp_sup( $link['title'] ) . '</a>';
    };

    $media = '';
    if ( $bg ) {
        $media = '<div class="bhfe-hero__media">'
            . wp_get_attachment_image( $bg, 'large', false, array( 'sizes'=>'100vw', 'fetchpriority'=>'high', 'loading'=>'eager', 'alt'=>'' ) )
            . '</div>';
    }

    return '<section class="bhfe-hero">'
        . $media
        . '<div class="bhfe-hero__overlay" aria-hidden="true"></div>'
        . '<div class="bhfe-hero__inner">'
        .   '<h1 class="bhfe-hero__title">' . bhfe_hp_sup( $heading ) . '</h1>'
        .   '<p class="bhfe-hero__sub">' . bhfe_hp_sup( $sub ) . '</p>'
        // Hero CTA buttons hidden for now (preview). Restore by removing the '0 &&' guard.
        .   ( 0 && true ? '<div class="bhfe-hero__ctas">' . $btn( $cta, false, '#find-courses' ) . $btn( $cta2, true ) . '</div>' : '' )
        . '</div>'
        . '</section>';
}

/** Band — credential finder (rows of tiles + row-aware drawers). Carries id="find-courses". */
function bhfe_hp_band_finder() {
    // SIMPLE MODE: every credential is a static link straight to its full catalog
    // (same tile style CIMA/CDFA already used). The interactive drawer version
    // — ethics sub-tiles + CPA state form — is preserved below in
    // bhfe_hp_band_finder_drawers() so it can be restored later.
    $rows  = array_chunk( bhfe_hp_credentials(), 3 );
    $stack = '';
    foreach ( $rows as $row ) {
        $tiles = '';
        foreach ( $row as $c ) {
            $tiles .= '<a class="bhfe-cred" href="' . esc_url( $c['all']['href'] ) . '">'
                . '<span class="bhfe-cred__name">' . wp_kses_post( $c['name'] ) . '</span>'
                . '<span class="bhfe-cred__mark bhfe-cred__mark--go" aria-hidden="true">&rarr;</span>'
                . '</a>';
        }
        $stack .= '<div class="bhfe-credrow">' . $tiles . '</div>';
    }
    return '<section class="bhfe-band bhfe-finder bhfe-card" id="find-courses" aria-labelledby="bhfe-finder-title">'
        . '<div class="bhfe-finder__head">'
        .   '<p class="bhfe-finder__kicker">Find your courses</p>'
        .   '<h2 class="bhfe-finder__title" id="bhfe-finder-title">Find the course you need now</h2>'
        .   '<p class="bhfe-finder__sub">Pick your license to browse every approved course.</p>'
        . '</div>'
        . '<div class="bhfe-finder__stack">' . $stack . '</div>'
        . '</section>';
}

/*
 * INTERACTIVE DRAWER VERSION (disabled for now — keep for later).
 * To re-enable: rename this to bhfe_hp_band_finder() and rename the static
 * version above to something else (or delete it). The drawer CSS/JS is still
 * shipped in assets/, so nothing else needs to change.
 *
function bhfe_hp_band_finder_drawers() {
    $rows  = array_chunk( bhfe_hp_credentials(), 3 );
    $stack = '';
    foreach ( $rows as $row ) {
        $tiles = ''; $drawers = ''; $col = 0;
        foreach ( $row as $c ) {
            if ( empty( $c['ethics'] ) ) {
                $tiles .= '<a class="bhfe-cred" href="' . esc_url( $c['all']['href'] ) . '">'
                    . '<span><span class="bhfe-cred__name">' . wp_kses_post( $c['name'] ) . '</span>'
                    . '<span class="bhfe-cred__tag">' . esc_html( $c['all']['label'] ) . '</span></span>'
                    . '<span class="bhfe-cred__mark bhfe-cred__mark--go" aria-hidden="true">&rarr;</span>'
                    . '</a>';
            } else {
                $did = 'bhfe-drawer-' . $c['id'];
                $tiles .= '<button class="bhfe-cred" type="button" aria-expanded="false" aria-controls="' . esc_attr( $did ) . '">'
                    . '<span><span class="bhfe-cred__name">' . wp_kses_post( $c['name'] ) . '</span>'
                    . '<span class="bhfe-cred__tag">' . wp_kses_post( $c['tag'] ) . '</span></span>'
                    . '<span class="bhfe-cred__mark" aria-hidden="true"><span class="bhfe-cred__mark--plus">+</span><span class="bhfe-cred__mark--minus">&ndash;</span></span>'
                    . '</button>';
                $ethics = ( $c['ethics'] === 'state' )
                    ? bhfe_hp_state_form()
                    : bhfe_hp_opt_tile( 'Ethics', $c['ethics']['label'], 'Meet your ethics requirement.', $c['ethics']['href'] );
                $all = bhfe_hp_opt_tile( 'Full catalog', $c['all']['label'], 'Every approved course for this credential.', $c['all']['href'] );
                $drawers .= '<div class="bhfe-drawer" id="' . esc_attr( $did ) . '" data-col="' . esc_attr( $col ) . '" hidden>'
                    . '<span class="bhfe-drawer__pointer" aria-hidden="true"></span>'
                    . '<div class="bhfe-drawer__head">'
                    .   '<div><div class="bhfe-drawer__kicker">Selected credential</div>'
                    .     '<div class="bhfe-drawer__name">' . wp_kses_post( $c['name'] ) . '</div></div>'
                    .   '<div class="bhfe-drawer__tag">' . wp_kses_post( $c['tag'] ) . '</div>'
                    . '</div>'
                    . '<div class="bhfe-drawer__opts">' . $ethics . $all . '</div>'
                    . '</div>';
            }
            $col++;
        }
        $stack .= '<div class="bhfe-credrow">' . $tiles . '</div>' . $drawers;
    }
    return '<section class="bhfe-band bhfe-finder bhfe-card" id="find-courses" aria-labelledby="bhfe-finder-title">'
        . '<div class="bhfe-finder__head">'
        .   '<p class="bhfe-finder__kicker">Find your courses</p>'
        .   '<h2 class="bhfe-finder__title" id="bhfe-finder-title">Find courses for your credential</h2>'
        .   '<p class="bhfe-finder__sub">Pick your license, then jump straight to your ethics requirement or the full catalog.</p>'
        . '</div>'
        . '<div class="bhfe-finder__stack">' . $stack . '</div>'
        . '</section>';
}
*/

/** Band — multi-license finder. Copy assumes the catalog ANDs multiple credit_type[]. */
function bhfe_hp_band_multilicense() {
    $chips = '';
    foreach ( bhfe_hp_credentials() as $c ) {
        $cid = 'bhfe-lic-' . $c['id'];
        $chips .= '<input class="bhfe-chip__input" type="checkbox" id="' . esc_attr( $cid ) . '" name="credit_type[]"'
            . ' value="' . esc_attr( $c['slug'] ) . '" data-short="' . esc_attr( $c['short'] ) . '">'
            . '<label class="bhfe-chip" for="' . esc_attr( $cid ) . '">'
            . '<span class="bhfe-chip__box" aria-hidden="true"></span>'
            . '<span>' . wp_kses_post( $c['chip'] ) . '</span>'
            . '</label>';
    }
    return '<section class="bhfe-band bhfe-multi-band bhfe-card" aria-labelledby="bhfe-multi-title">'
        . '<div class="bhfe-multi">'
        .   '<div>'
        .     '<p class="bhfe-multi__kicker">Hold more than one?</p>'
        .     '<h2 class="bhfe-multi__title" id="bhfe-multi-title">Find courses that count for every credential you hold</h2>'
        .     '<p class="bhfe-multi__text">Many of our courses are registered for several licenses at once. Select the credentials you carry and we&rsquo;ll show only the courses approved for <strong>all</strong> of them &mdash; so one course can satisfy multiple requirements.</p>'
        .   '</div>'
        .   '<form class="bhfe-multi__form bhfe-multi__panel" method="get" action="' . esc_url( '/courses/' ) . '">'
        .     '<fieldset class="bhfe-chips">'
        .       '<legend class="bhfe-multi__legend">Select your credentials</legend>'
        .       $chips
        .     '</fieldset>'
        .     '<div class="bhfe-multi__result">'
        .       '<div class="bhfe-multi__row" hidden>'
        .         '<div><div class="bhfe-multi__rlabel">Courses that count for</div>'
        .           '<div class="bhfe-multi__rnames"></div></div>'
        .         '<button class="bhfe-multi__go" type="submit">Find matching courses <span aria-hidden="true">&rarr;</span></button>'
        .       '</div>'
        .       '<p class="bhfe-multi__hint"><span aria-hidden="true">+</span> Select one or more credentials to see matching courses.</p>'
        .     '</div>'
        .   '</form>'
        . '</div>'
        . '</section>';
}

/** Band — promo. */
function bhfe_hp_band_promo() {
    return '<section class="bhfe-band bhfe-card" aria-labelledby="bhfe-promo-title">'
        . '<div class="bhfe-promo">'
        .   '<div>'
        .     '<span class="bhfe-promo__badge">No code needed</span>'
        .     '<h2 class="bhfe-promo__title" id="bhfe-promo-title">Buy more,<br>save more.</h2>'
        .     '<p class="bhfe-promo__text">Multi-course discounts apply automatically at checkout &mdash; the more you add to your cart, the more you save.</p>'
        .   '</div>'
        .   '<div class="bhfe-promo__tiers">'
        .     '<div class="bhfe-tier bhfe-tier--base"><div class="bhfe-tier__pct">20<sup>%</sup></div><div class="bhfe-tier__note">off when you<strong>add 3&ndash;4 courses</strong></div></div>'
        .     '<div class="bhfe-tier bhfe-tier--best"><span class="bhfe-tier__flag">Best value</span><div class="bhfe-tier__pct">25<sup>%</sup></div><div class="bhfe-tier__note">off when you<strong>add 5 or more</strong></div></div>'
        .   '</div>'
        . '</div>'
        . '</section>';
}

/** Band — benefit cards. */
function bhfe_hp_band_benefits() {
    return '<section class="bhfe-band bhfe-card" aria-label="Why Beacon Hill">'
        . '<div class="bhfe-benefits">'
        .   '<div class="bhfe-benefit"><h3 class="bhfe-benefit__title">Approved sponsor</h3>'
        .     '<p class="bhfe-benefit__body">Registered with NASBA, CFP Board, the IRS, IDFA, the Investments &amp; Wealth Institute, and NASAA.</p></div>'
        .   '<div class="bhfe-benefit"><h3 class="bhfe-benefit__title">Trusted since 1995</h3>'
        .     '<p class="bhfe-benefit__body">Independent, family-run provider serving CPAs, CFPs and other financial professionals for over three decades.</p></div>'
        .   '<div class="bhfe-benefit"><h3 class="bhfe-benefit__title">Instant online access</h3>'
        .     '<p class="bhfe-benefit__body">Courses and exams open the moment checkout completes. Prefer paper? Printed courses ship by mail.</p></div>'
        .   '<div class="bhfe-benefit"><h3 class="bhfe-benefit__title">A deep catalog</h3>'
        .     '<div class="bhfe-benefit__stat-group"><div class="bhfe-benefit__stat">400+</div><div class="bhfe-benefit__stat-label">courses across every credential</div></div>'
        .     '<div class="bhfe-benefit__stat-group"><div class="bhfe-benefit__stat">2,179</div><div class="bhfe-benefit__stat-label">CPE &amp; CE credit hours</div></div></div>'
        . '</div>'
        . '</section>';
}

/**
 * Browse-by-category data — computed LIVE from the catalog and cached.
 * For each credential, find the 'subject' terms that actually have courses
 * carrying that license's credit (the only catalog-filterable subject route),
 * ranked by course count. Auto-refreshes via a transient, so it tracks the
 * catalog with no manual upkeep. Returns: [ credential_id => [ ['label','id'], … ] ].
 * Bust the cache anytime with: delete_transient('bhfe_hp_browse_v1').
 */
function bhfe_hp_browse_data() {
    $cached = get_transient( 'bhfe_hp_browse_v1' );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    global $wpdb;
    $table = defined( 'FLMS_COURSE_QUERY_TABLE' ) ? FLMS_COURSE_QUERY_TABLE : $wpdb->prefix . 'flms_course_metadata';

    // credential id => course-credit meta key(s) meaning "carries this license"
    $credit_keys = array(
        'cpa'    => array( 'cpa' ),
        'cfp'    => array( 'cfp' ),
        'eaotrp' => array( 'eaotrp', 'erpa' ),
        'iar'    => array( 'iar' ),
        'cima'   => array( 'iwicimaall', 'iwicimatr', 'cimacpwarmaethics' ),
        'cdfa'   => array( 'cdfa' ),
    );
    // friendlier labels for a few verbose subject names
    $friendly = array(
        'Retirement Savings & Income Planning'       => 'Retirement Planning',
        'Investment Planning'                        => 'Investments',
        'Product & Practice (IAR)'                   => 'Product & Practice',
        'Ethics & Professional Responsibility (IAR)' => 'Ethics & Professional Responsibility',
    );
    // ethics has its own dedicated link; skip ethics buckets + vague CPE catch-alls
    $skip = array( 'ethics', 'regulatory ethics', 'behavioral ethics',
        'ethics & professional responsibility (iar)', 'specialized knowledge',
        'personal development', 'personnel/hr', 'production' );

    $cap  = 6;
    $min  = 3;
    $data = array();

    foreach ( $credit_keys as $cid => $keys ) {
        $data[ $cid ] = array();
        $in  = implode( ',', array_map( function ( $k ) { return "'" . esc_sql( $k ) . "'"; }, $keys ) );
        $ids = $wpdb->get_col( "SELECT DISTINCT course_id FROM $table WHERE meta_key IN ($in)" ); // phpcs:ignore
        $ids = array_filter( array_map( 'intval', (array) $ids ) );
        if ( empty( $ids ) ) {
            continue;
        }
        $idlist = implode( ',', $ids );
        $rows   = $wpdb->get_results(
            "SELECT t.term_id id, t.name name, COUNT(*) c
             FROM {$wpdb->term_relationships} tr
             JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'subject'
             JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
             WHERE tr.object_id IN ($idlist)
             GROUP BY t.term_id ORDER BY c DESC"
        ); // phpcs:ignore
        foreach ( (array) $rows as $r ) {
            if ( count( $data[ $cid ] ) >= $cap ) {
                break;
            }
            if ( (int) $r->c < $min ) {
                continue;
            }
            $name = html_entity_decode( $r->name, ENT_QUOTES );
            if ( in_array( strtolower( $name ), $skip, true ) ) {
                continue;
            }
            $data[ $cid ][] = array(
                'label' => isset( $friendly[ $name ] ) ? $friendly[ $name ] : $name,
                'id'    => (int) $r->id,
            );
        }
    }

    set_transient( 'bhfe_hp_browse_v1', $data, 12 * HOUR_IN_SECONDS );
    return $data;
}

/**
 * Band — browse by category. One column per license: heading links to that
 * license's catalog; subject links below are pulled LIVE (bhfe_hp_browse_data)
 * and scoped to the license (credit_type[]+subject), plus a dedicated Ethics link.
 */
function bhfe_hp_band_browse() {
    $ethics_for = array( 'cpa' => 1, 'cfp' => 1, 'eaotrp' => 1, 'iar' => 1 );
    $dyn        = bhfe_hp_browse_data();

    $cols = '';
    foreach ( bhfe_hp_credentials() as $c ) {
        $links = '';
        if ( ! empty( $ethics_for[ $c['id'] ] ) ) {
            $links .= '<li><a href="' . esc_url( bhfe_hp_ethics_url( $c['slug'] ) ) . '">Ethics</a></li>';
        }
        $subjects = isset( $dyn[ $c['id'] ] ) ? $dyn[ $c['id'] ] : array();
        foreach ( $subjects as $s ) {
            $href = '/courses/?credit_type%5B%5D=' . rawurlencode( $c['slug'] ) . '&subject=' . rawurlencode( $s['id'] );
            $links .= '<li><a href="' . esc_url( $href ) . '">' . esc_html( $s['label'] ) . '</a></li>';
        }
        $links .= '<li><a class="bhfe-browse__viewall" href="' . esc_url( $c['all']['href'] ) . '">All courses <span aria-hidden="true">&rarr;</span></a></li>';

        $cols .= '<div class="bhfe-browse__col">'
            . '<h3 class="bhfe-browse__col-title"><a href="' . esc_url( $c['all']['href'] ) . '">' . wp_kses_post( $c['name'] ) . '</a></h3>'
            . '<ul class="bhfe-browse__links">' . $links . '</ul>'
            . '</div>';
    }

    return '<section class="bhfe-band bhfe-card" aria-labelledby="bhfe-browse-title">'
        . '<div class="bhfe-browse">'
        .   '<h2 class="bhfe-browse__title" id="bhfe-browse-title">Browse by subject</h2>'
        .   '<a class="bhfe-browse__btn" href="' . esc_url( '/courses/' ) . '">Browse all 400+ courses <span aria-hidden="true">&rarr;</span></a>'
        .   '<div class="bhfe-browse__grid">' . $cols . '</div>'
        .   '<p class="bhfe-browse__pdf">Prefer a list? <a href="' . esc_url( '/wp-content/uploads/2026/05/CPA-Course-List-2026-5-2-26.pdf' ) . '" target="_blank" rel="noopener">Download the CPA course list (PDF)</a></p>'
        . '</div>'
        . '</section>';
}

/**
 * Band — combined "Find Your Courses — Bundle & Save".
 * Replaces the separate finder + multi-license bands with one card:
 * a bundle-discount band (20% / 25%) over a multi-select credential picker.
 * Pick one credential -> its dedicated catalog page; pick several -> the
 * AND-filtered /courses/?credit_type[]=… link (built in JS from data attrs).
 * (Design from Claude Design, mapped to brand tokens/fonts; JS in homepage.js.)
 */
/**
 * Band — standalone bundle-discount tiers (20% / 25%). Rendered ONCE, right
 * below the hero. Split out of the course-finder card so the discount messaging
 * and the credential picker read as two separate boxes.
 */
function bhfe_hp_band_discount() {
    return '<section class="bhfe-band bhfe-cf-discount" aria-label="Bundle discounts">'
        . '<div class="bhfe-cf-card">'
        .   '<div class="bhfe-cf-band">'
        .     '<div class="bhfe-cf-band-eyebrow">Bundle &amp; save &middot; discounts applied automatically</div>'
        .     '<div class="bhfe-cf-tiers">'
        .       '<div class="bhfe-cf-tier bhfe-cf-tier--std">'
        .         '<div class="bhfe-cf-pct">20<sup>%</sup></div>'
        .         '<div class="bhfe-cf-when">off when you</div>'
        .         '<div class="bhfe-cf-add">buy 3&ndash;4 courses</div>'
        .       '</div>'
        .       '<div class="bhfe-cf-tier bhfe-cf-tier--best">'
        .         '<div class="bhfe-cf-badge">Best value</div>'
        .         '<div class="bhfe-cf-pct">25<sup>%</sup></div>'
        .         '<div class="bhfe-cf-when">off when you</div>'
        .         '<div class="bhfe-cf-add">buy 5+ courses</div>'
        .       '</div>'
        .     '</div>'
        .   '</div>'
        . '</div>'
        . '</section>';
}

function bhfe_hp_band_courses() {
    $btns = '';
    foreach ( bhfe_hp_credentials() as $c ) {
        $btns .= '<button type="button" class="bhfe-cf-license" aria-pressed="false"'
            . ' data-id="' . esc_attr( $c['id'] ) . '"'
            . ' data-slug="' . esc_attr( $c['slug'] ) . '"'
            . ' data-page="' . esc_url( $c['all']['href'] ) . '"'
            . ' data-label="' . esc_attr( $c['short'] ) . '">'
            . '<span>' . wp_kses_post( $c['name'] ) . '</span>'
            . '<span class="bhfe-cf-dot" aria-hidden="true"></span>'
            . '</button>';
    }
    return '<section class="bhfe-band bhfe-cf-courses" id="find-courses" aria-labelledby="bhfe-cf-title">'
        . '<div class="bhfe-cf-card">'
        .   '<div class="bhfe-cf-picker">'
        .     '<div class="bhfe-cf-head">'
        .       '<div class="bhfe-cf-head-eyebrow">Find Your Courses</div>'
        .       '<h2 class="bhfe-cf-h2" id="bhfe-cf-title">Find the course you need now</h2>'
        .       '<p class="bhfe-cf-lead">Select the credential you hold &mdash; or choose several and we&rsquo;ll show only the courses that count for every one.</p>'
        .       '<div class="bhfe-cf-applyhint">Select all that apply</div>'
        .     '</div>'
        .     '<div class="bhfe-cf-grid">' . $btns . '</div>'
        .     '<div class="bhfe-cf-footer">'
        .       '<div class="bhfe-cf-summary"><strong></strong>Select the credentials you hold to begin.</div>'
        .       '<a class="bhfe-cf-cta is-disabled" href="#" aria-disabled="true">Select your credential(s)</a>'
        .     '</div>'
        .     '<div class="bhfe-cf-note"><span class="bhfe-cf-bullet" aria-hidden="true"></span>One course, multiple credits &mdash; every course shown counts toward all the credentials you selected.</div>'
        .   '</div>'
        . '</div>'
        . '</section>';
}

/**
 * TEMP / TESTING — second copy of the "Find Your Courses" band.
 * An independent sandbox so design tweaks can be tried on a duplicate without
 * touching the live band above. Unique IDs (-b) + .bhfe-cf-courses--b modifier
 * class so it can be targeted/styled separately. Remove this whole function and
 * its echo in bhfe_hp_render() to delete the duplicate.
 */
function bhfe_hp_band_courses_b() {
    $tiles = '';
    foreach ( bhfe_hp_credentials() as $c ) {
        // Every tile fans open an "All Courses" chip. Ethics depends on the credential:
        //  - CPA  -> native state <select> + Go (ethics rules differ by state; works w/o JS)
        //  - CFP/EA/IAR -> a direct ethics-page link
        //  - CIMA/CDFA  -> no ethics courses, so just the All Courses chip (solo)
        $all = '<a class="bhfe-cf-xopt bhfe-cf-xopt--all" href="' . esc_url( $c['all']['href'] ) . '">'
            . 'All Courses</a>';

        $mod = '';
        $fan = $all;
        if ( $c['id'] === 'cpa' ) {
            $mod = ' is-cpa';
            $fan = $all
                . '<form class="bhfe-cf-stateform" method="get" action="' . esc_url( '/courses/ethics-courses-for-accountants/' ) . '">'
                .   '<input type="hidden" name="credit_type[]" value="cpa">'
                .   '<label class="bhfe-sr-only" for="bhfe-cpa-state-b">Your state for CPA ethics</label>'
                .   '<select class="bhfe-cf-stateselect" id="bhfe-cpa-state-b" name="state">' . bhfe_hp_state_options( 'Ethics by state&hellip;' ) . '</select>'
                .   '<button class="bhfe-cf-statego" type="submit" aria-label="View CPA ethics for the selected state">Go <span aria-hidden="true">&rarr;</span></button>'
                . '</form>';
        } elseif ( ! empty( $c['ethics'] ) ) {
            $fan = $all
                . '<a class="bhfe-cf-xopt bhfe-cf-xopt--eth" href="' . esc_url( bhfe_hp_ethics_url( $c['slug'] ) ) . '">'
                . 'Ethics</a>';
        } else {
            $mod = ' is-solo';
        }

        $tiles .= '<div class="bhfe-cf-xtile' . $mod . '" role="group" aria-label="' . esc_attr( wp_strip_all_tags( $c['name'] ) ) . ' course options">'
            . '<span class="bhfe-cf-xname">' . wp_kses_post( $c['name'] ) . '</span>'
            . '<span class="bhfe-cf-xcue" aria-hidden="true">Pick an option</span>'
            . '<div class="bhfe-cf-xfan">' . $fan . '</div>'
            . '</div>';
    }
    return '<section class="bhfe-band bhfe-cf-courses bhfe-cf-courses--b" id="find-courses-b" aria-labelledby="bhfe-cf-title-b">'
        . '<div class="bhfe-cf-card">'
        .   '<div class="bhfe-cf-picker">'
        .     '<div class="bhfe-cf-head">'
        .       '<div class="bhfe-cf-head-eyebrow">Find Your Courses</div>'
        .       '<h2 class="bhfe-cf-h2" id="bhfe-cf-title-b">Find the course you need now</h2>'
        .       '<p class="bhfe-cf-lead">Hover a credential to fan out its options &mdash; jump to the full catalog or go straight to ethics.</p>'
        .     '</div>'
        .     '<div class="bhfe-cf-grid bhfe-cf-xgrid">' . $tiles . '</div>'
        .   '</div>'
        . '</div>'
        . '</section>';
}

/**
 * TEMP / TESTING — Claude Design redesign candidates for "Find Your Courses",
 * rendered from one builder so both stay in sync with the real data:
 *   'd' = Option 2A "Ink flip"    (.bhfe-cf-courses--d) — amber monogram; tile inverts to navy
 *   'e' = Option 2B "Split shine" (.bhfe-cf-courses--e) — layered monogram w/ shine sweep; warm tile
 * Same credentials/URLs/CPA state form as Version B; only presentation differs.
 * Remove this function and its echoes in bhfe_hp_render() once a winner is picked.
 */
function bhfe_hp_band_courses_mono( $v, $label ) {
    // per-credential monogram: text + design-tuned font px for variant d / e
    $monos = array(
        'cpa'    => array( 'CPA', 112, 96 ),
        'cfp'    => array( 'CFP', 104, 88 ),
        'eaotrp' => array( 'EA', 112, 96 ),
        'iar'    => array( 'IAR', 108, 92 ),
        'cima'   => array( 'CIMA', 84, 68 ),
        'cdfa'   => array( 'CDFA', 92, 76 ),
    );
    $long = array( 'eaotrp', 'cima' ); // long credential names get smaller type

    $tiles = '';
    foreach ( bhfe_hp_credentials() as $c ) {
        $m    = $monos[ $c['id'] ];
        $size = ( 'd' === $v ) ? $m[1] : $m[2];
        $mono = ( 'd' === $v )
            ? '<span class="bhfe-cf-xmono" aria-hidden="true" style="font-size:' . $size . 'px">' . $m[0] . '</span>'
            : '<span class="bhfe-cf-xmono2" aria-hidden="true" style="font-size:' . $size . 'px">'
                . '<span class="bhfe-cf-xmono-b">' . $m[0] . '</span><span class="bhfe-cf-xmono-f">' . $m[0] . '</span>'
                . '</span>';

        $all = '<a class="bhfe-cf-xopt bhfe-cf-xopt--all" href="' . esc_url( $c['all']['href'] ) . '">All Courses</a>';
        $mod = in_array( $c['id'], $long, true ) ? ' is-long' : '';
        if ( 'cpa' === $c['id'] ) {
            $mod .= ' is-cpa';
            $tag  = 'Catalog &middot; Ethics by state';
            $fan  = $all
                . '<form class="bhfe-cf-stateform" method="get" action="' . esc_url( '/courses/ethics-courses-for-accountants/' ) . '">'
                .   '<input type="hidden" name="credit_type[]" value="cpa">'
                .   '<label class="bhfe-sr-only" for="bhfe-cpa-state-' . $v . '">Your state for CPA ethics</label>'
                .   '<select class="bhfe-cf-stateselect" id="bhfe-cpa-state-' . $v . '" name="state">' . bhfe_hp_state_options( 'Ethics by state&hellip;' ) . '</select>'
                .   '<button class="bhfe-cf-statego" type="submit" aria-label="View CPA ethics for the selected state">Go</button>'
                . '</form>';
        } elseif ( ! empty( $c['ethics'] ) ) {
            $tag = 'Catalog &middot; Ethics';
            $fan = $all
                . '<a class="bhfe-cf-xopt bhfe-cf-xopt--eth" href="' . esc_url( bhfe_hp_ethics_url( $c['slug'] ) ) . '">Ethics</a>';
        } else {
            $mod .= ' is-solo';
            $tag  = 'Full catalog';
            $fan  = $all;
        }

        $tiles .= '<div class="bhfe-cf-xtile' . $mod . '" role="group" aria-label="' . esc_attr( wp_strip_all_tags( $c['name'] ) ) . ' course options">'
            . $mono
            . '<span class="bhfe-cf-xhead">'
            .   '<span class="bhfe-cf-xname">' . wp_kses_post( $c['name'] ) . '</span>'
            .   '<span class="bhfe-cf-xtag">' . $tag . '</span>'
            . '</span>'
            . '<span class="bhfe-cf-xcue" aria-hidden="true">Pick an option</span>'
            . '<div class="bhfe-cf-xfan">' . $fan . '</div>'
            . '</div>';
    }
    return '<section class="bhfe-band bhfe-cf-courses bhfe-cf-courses--' . $v . '" id="find-courses-' . $v . '" aria-labelledby="bhfe-cf-title-' . $v . '">'
        . '<div class="bhfe-cf-card">'
        .   '<div class="bhfe-cf-picker">'
        .     '<div class="bhfe-cf-head">'
        .       '<div class="bhfe-cf-head-eyebrow">Find Your Courses &middot; ' . $label . '</div>'
        .       '<h2 class="bhfe-cf-h2" id="bhfe-cf-title-' . $v . '">Find the course you need now</h2>'
        .       '<p class="bhfe-cf-lead">Hover a credential to fan out its options &mdash; jump to the full catalog or go straight to ethics.</p>'
        .     '</div>'
        .     '<div class="bhfe-cf-grid bhfe-cf-xgrid">' . $tiles . '</div>'
        .   '</div>'
        . '</div>'
        . '</section>';
}

/**
 * Band — accreditations & required disclosures.
 * Logo grid (CFP, NASBA/QAS, IDFA, IRS CE, NASAA, IWI) over the CFP-marks
 * and NASBA-sponsor legal text. Logos load from the theme's /img/ dir or,
 * for the high-res replacements, this plugin's assets/img/.
 */
function bhfe_hp_band_accreditation() {
    $img  = get_template_directory_uri() . '/img/'; // legacy theme images
    $pimg = BHFE_HP_URL . 'assets/img/';            // high-res copies bundled with this plugin

    $p1 = bhfe_hp_sup( 'CFP®, CERTIFIED FINANCIAL PLANNER® are certification marks owned by the Certified Financial Planner Board of Standards, Inc. These marks are awarded to individuals who successfully complete CFP® Board\'s initial and ongoing certification requirements.' );
    $p2 = bhfe_hp_sup( 'Beacon Hill Financial Educators, Inc. (sponsor I.D. #107615) is registered with the National Association of State Boards of Accountancy (NASBA) as a sponsor of continuing professional education on the National Registry of CPE Sponsors. State boards of accountancy have final authority on the acceptance of individual courses for CPE credit. Complaints regarding registered sponsors may be submitted to the National Registry of CPE Sponsors through its website: ' )
        . '<a href="' . esc_url( 'https://www.nasbaregistry.org' ) . '" target="_blank" rel="noopener">www.nasbaregistry.org</a>.';

    $p_idfa  = 'IDFA-Registered CE Sponsor #105392';
    // two paragraphs — the '</p><p>' break is intentional (note body allows markup)
    $p_irs   = 'Beacon Hill Financial Educators is a qualified sponsor of continuing professional education required for individuals enrolled to practice before the Internal Revenue Service (enrolled agents) and are in compliance with the requirements of Treasury Department Circular No 230, section 10.6(g). Sponsor ID: FKKO.'
        . '</p><p>We have entered into an agreement with the Office of Director of Practice, Internal Revenue Service, to meet the requirements of 31 Code of Federal Regulations, section 10.6(g), covering maintenance of attendance records, retention of program outlines, qualifications of instructors and length of class hours. This agreement does not constitute an endorsement by the Director of Practice as to the quality of the program or its contribution to the professional competence of the enrolled individual.';
    $p_nasaa = 'Provider ID 222740';

    // 4th element = disclosure popover shown on hover / focus / tap of the tile
    // (empty = no popover); optional 5th = extra note class (e.g. wide for long text).
    // IWI popover intentionally absent — copy TBD.
    $logos = array(
        array( $img . 'Affiliation-CFP.png',   'CFP® certification marks', '', $p1 ),
        array( $pimg . 'nasba.png',            'NASBA National Registry of CPE Sponsors — QAS Self Study', ' bhfe-accred__logo--tall', $p2 ),
        array( $pimg . 'idfa-logo.png',        'IDFA — Institute for Divorce Financial Analysts', '', $p_idfa ),
        array( $pimg . 'ce.png',               'IRS — Approved Continuing Education Provider', '', $p_irs, ' bhfe-accred__note--wide' ),
        array( $img . 'nasaa_logo_blue.png',   'NASAA — North American Securities Administrators Association', '', $p_nasaa ),
        array( $img . 'iwi_logo.png',          'Investments & Wealth Institute', '', '' ),
    );
    $tiles = '';
    $n = 0;
    foreach ( $logos as $l ) {
        $n++;
        $note  = '';
        $attrs = '';
        if ( ! empty( $l[3] ) ) {
            $mod   = ! empty( $l[4] ) ? $l[4] : '';
            $note  = '<div class="bhfe-accred__note' . $mod . '" id="bhfe-accred-note-' . $n . '" role="note"><p>' . $l[3] . '</p></div>';
            $attrs = ' tabindex="0" aria-describedby="bhfe-accred-note-' . $n . '"';
        }
        $tiles .= '<div class="bhfe-accred__logo' . $l[2] . '"' . $attrs . '>'
            . '<img src="' . esc_url( $l[0] ) . '" alt="' . esc_attr( $l[1] ) . '" loading="lazy" decoding="async">'
            . $note
            . '</div>';
    }

    return '<section class="bhfe-band bhfe-card bhfe-accred" aria-labelledby="bhfe-accred-title">'
        . '<div class="bhfe-accred__head">'
        .   '<p class="bhfe-accred__kicker">Registered &amp; approved</p>'
        .   '<h2 class="bhfe-accred__title" id="bhfe-accred-title">Recognized by the boards that set the standards</h2>'
        . '</div>'
        . '<div class="bhfe-accred__logos">' . $tiles . '</div>'
        // "Registered & in good standing" subhead + CFP/NASBA legal text hidden for now (kept in code)
        // . '<div class="bhfe-accred__subhead">'
        // .   '<p class="bhfe-accred__kicker">Registered &amp; in good standing</p>'
        // .   '<h3 class="bhfe-accred__subtitle">Trusted CPE / CE provider</h3>'
        // . '</div>'
        // . '<div class="bhfe-accred__legal">'
        // .   '<p>' . $p1 . '</p>'
        // .   '<p>' . $p2 . '</p>'
        // . '</div>'
        . '</section>';
}

/** Echo the whole homepage (called by the plugin's front-page template). */
function bhfe_hp_render() {
    echo bhfe_hp_hero();
    echo bhfe_hp_band_discount();  // standalone bundle-discount band — once, right below the hero
    // echo bhfe_hp_band_courses(); // Version A hidden for now (kept in code)
    // echo bhfe_hp_band_courses_b(); // Version B hidden while comparing redesigns (kept in code)
    echo bhfe_hp_band_courses_mono( 'd', 'Option 2A' ); // redesign candidate "Ink flip"
    echo bhfe_hp_band_courses_mono( 'e', 'Option 2B' ); // redesign candidate "Split shine"
    echo bhfe_hp_band_accreditation();
    // echo bhfe_hp_band_promo();  // disabled: the bundle/discount band now lives inside bhfe_hp_band_courses()
    echo bhfe_hp_band_benefits();
    // echo bhfe_hp_band_browse(); // "Browse by subject" hidden for now (kept in code)
}
