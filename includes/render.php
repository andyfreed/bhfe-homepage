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

/** Subject filter URL — resolve term ID by name at render time, fallback to known local ID. */
function bhfe_hp_subject_url( $name, $fallback_id ) {
    $t  = get_term_by( 'name', $name, 'subject' );
    $id = ( $t && ! is_wp_error( $t ) ) ? $t->term_id : $fallback_id;
    return '/courses/?subject=' . rawurlencode( $id );
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

    $btn = function( $link, $ghost = false ) {
        if ( empty( $link ) || empty( $link['url'] ) ) { return ''; }
        $cls = 'bhfe-hero__btn' . ( $ghost ? ' bhfe-hero__btn--ghost' : '' );
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
        .   '<div class="bhfe-hero__ctas">' . $btn( $cta, false ) . $btn( $cta2, true ) . '</div>'
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
        .     '<p class="bhfe-benefit__body">Registered with NASBA (#107615), CFP Board, the IRS, IDFA, the Investments &amp; Wealth Institute, and NASAA.</p></div>'
        .   '<div class="bhfe-benefit"><h3 class="bhfe-benefit__title">Trusted since 2001</h3>'
        .     '<p class="bhfe-benefit__body">Independent, family-run provider serving CPAs and financial professionals for over two decades.</p></div>'
        .   '<div class="bhfe-benefit"><h3 class="bhfe-benefit__title">Instant online access</h3>'
        .     '<p class="bhfe-benefit__body">Courses and exams open the moment checkout completes. Prefer paper? Printed courses ship by mail.</p></div>'
        .   '<div class="bhfe-benefit"><h3 class="bhfe-benefit__title">A deep catalog</h3>'
        .     '<div class="bhfe-benefit__stat-group"><div class="bhfe-benefit__stat">378+</div><div class="bhfe-benefit__stat-label">courses across every credential</div></div>'
        .     '<div class="bhfe-benefit__stat-group"><div class="bhfe-benefit__stat">2,179</div><div class="bhfe-benefit__stat-label">CPE &amp; CE credit hours</div></div></div>'
        . '</div>'
        . '</section>';
}

/** Band — browse row. Subject pills resolve term IDs by name at render time. */
function bhfe_hp_band_browse() {
    $pills = array(
        array( 'Ethics courses', '/courses/ethics-courses-for-accountants/' ),
        array( 'Taxes', bhfe_hp_subject_url( 'Taxes', 1348 ) ),
        array( 'Estate Planning', bhfe_hp_subject_url( 'Estate Planning', 1364 ) ),
        array( 'Retirement Planning', bhfe_hp_subject_url( 'Retirement Savings & Income Planning', 1420 ) ),
        array( 'Investments', bhfe_hp_subject_url( 'Investment Planning', 1406 ) ),
        array( 'Accounting', bhfe_hp_subject_url( 'Accounting', 1352 ) ),
    );
    $lis = '';
    foreach ( $pills as $p ) {
        $lis .= '<li><a class="bhfe-pill" href="' . esc_url( $p[1] ) . '">' . esc_html( $p[0] ) . '</a></li>';
    }
    return '<section class="bhfe-band bhfe-card" aria-labelledby="bhfe-browse-title">'
        . '<div class="bhfe-browse">'
        .   '<h2 class="bhfe-browse__title" id="bhfe-browse-title">Browse the catalog</h2>'
        .   '<a class="bhfe-browse__btn" href="' . esc_url( '/courses/' ) . '">Browse all 378+ courses <span aria-hidden="true">&rarr;</span></a>'
        .   '<ul class="bhfe-pills">' . $lis . '</ul>'
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
        .   '<div class="bhfe-cf-band">'
        .     '<div class="bhfe-cf-band-eyebrow">Bundle &amp; save &middot; discounts applied automatically</div>'
        .     '<div class="bhfe-cf-tiers">'
        .       '<div class="bhfe-cf-tier bhfe-cf-tier--std">'
        .         '<div class="bhfe-cf-pct">20<sup>%</sup></div>'
        .         '<div class="bhfe-cf-when">off when you</div>'
        .         '<div class="bhfe-cf-add">add 3&ndash;4 courses</div>'
        .       '</div>'
        .       '<div class="bhfe-cf-tier bhfe-cf-tier--best">'
        .         '<div class="bhfe-cf-badge">Best value</div>'
        .         '<div class="bhfe-cf-pct">25<sup>%</sup></div>'
        .         '<div class="bhfe-cf-when">off when you</div>'
        .         '<div class="bhfe-cf-add">add 5 or more</div>'
        .       '</div>'
        .     '</div>'
        .   '</div>'
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
 * Band — accreditations & required disclosures.
 * Logo row (CFP, NASBA/QAS, CFP Board, IRS CE, NASAA, IWI) over the CFP-marks
 * and NASBA-sponsor legal text. Logos load from the active theme's /img/ dir.
 */
function bhfe_hp_band_accreditation() {
    $img = get_template_directory_uri() . '/img/';
    $logos = array(
        array( 'Affiliation-CFP.png',                 'CFP® certification marks' ),
        array( 'RegistryAndQASComboLogo53By73p.jpg',  'NASBA National Registry of CPE Sponsors — QAS Self Study' ),
        array( 'cfp-board.png',                        'CFP Board — CE Quality Partner' ),
        array( 'ce-blue.png',                          'IRS — Approved Continuing Education Provider' ),
        array( 'nasaa_logo_blue.png',                  'NASAA — North American Securities Administrators Association' ),
        array( 'iwi_logo.png',                         'Investments & Wealth Institute' ),
    );
    $tiles = '';
    foreach ( $logos as $l ) {
        $tiles .= '<div class="bhfe-accred__logo">'
            . '<img src="' . esc_url( $img . $l[0] ) . '" alt="' . esc_attr( $l[1] ) . '" loading="lazy" decoding="async">'
            . '</div>';
    }

    $p1 = bhfe_hp_sup( 'CFP®, CERTIFIED FINANCIAL PLANNER® are certification marks owned by the Certified Financial Planner Board of Standards, Inc. These marks are awarded to individuals who successfully complete CFP® Board\'s initial and ongoing certification requirements.' );
    $p2 = bhfe_hp_sup( 'Beacon Hill Financial Educators, Inc. (sponsor I.D. #107615) is registered with the National Association of State Boards of Accountancy (NASBA) as a sponsor of continuing professional education on the National Registry of CPE Sponsors. State boards of accountancy have final authority on the acceptance of individual courses for CPE credit. Complaints regarding registered sponsors may be submitted to the National Registry of CPE Sponsors through its website: ' )
        . '<a href="' . esc_url( 'https://www.nasbaregistry.org' ) . '" target="_blank" rel="noopener">www.nasbaregistry.org</a>.';

    return '<section class="bhfe-band bhfe-card bhfe-accred" aria-labelledby="bhfe-accred-title">'
        . '<div class="bhfe-accred__head">'
        .   '<p class="bhfe-accred__kicker">Accredited &amp; approved</p>'
        .   '<h2 class="bhfe-accred__title" id="bhfe-accred-title">Recognized by the boards that set the standards</h2>'
        . '</div>'
        . '<div class="bhfe-accred__logos">' . $tiles . '</div>'
        . '<div class="bhfe-accred__legal">'
        .   '<p>' . $p1 . '</p>'
        .   '<p>' . $p2 . '</p>'
        . '</div>'
        . '</section>';
}

/** Echo the whole homepage (called by the plugin's front-page template). */
function bhfe_hp_render() {
    echo bhfe_hp_hero();
    echo bhfe_hp_band_courses();   // replaces the old finder + multi-license bands
    echo bhfe_hp_band_accreditation();
    // echo bhfe_hp_band_promo();  // disabled: the bundle/discount band now lives inside bhfe_hp_band_courses()
    echo bhfe_hp_band_benefits();
    echo bhfe_hp_band_browse();
}
