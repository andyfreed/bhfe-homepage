<?php
/**
 * BHFE Homepage — markup renderers.
 *
 * Self-contained: the homepage UI lives here, not in ACF flexible content.
 * The HERO still reads the ACF hero band on the front page (so staff can edit
 * heading/subheading/CTAs/background image in wp-admin), falling back to
 * sensible defaults if those fields are empty.
 *
 * Bands: hero · bundle-discount strip · Find Your Courses (2C tiles) ·
 * accreditation logos + disclosure popovers · benefit cards.
 * Every destination in the course finder is editable in wp-admin under
 * Settings → BHFE Homepage (see includes/settings.php); CPA ethics is
 * state-gated with per-state URL/subject overrides.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Escape hero text. NOTE: the theme hooks the `esc_html` filter (bhfe_allow_sup) to
 *  superscript ® site-wide, so esc_html() both escapes AND superscripts — we must NOT
 *  convert ® again here or it double-wraps (<sup><sup>…</sup></sup>). */
function bhfe_hp_sup( $text ) {
    return esc_html( $text );
}

/** Just the <option>s for a state <select> (sentinels excluded). Shared by the pickers.
 *  A state with a per-state override (Settings → BHFE Homepage) carries it as
 *  data-url; homepage.js routes there on submit instead of the shop filter.
 *  With JS off the data-url is inert and the form submits to the filter. */
function bhfe_hp_state_options( $placeholder = 'Choose your state&hellip;' ) {
    $opts = '<option value="">' . $placeholder . '</option>';
    foreach ( bhfe_hp_states() as $t ) {
        $url = bhfe_hp_cpa_state_link( $t->term_id );
        if ( '' === $url && bhfe_hp_cpa_state_subject( $t->term_id ) > 0 ) {
            // state has its own subject: route to the shop filter carrying it
            // (the no-JS form submit still works, just with the default subject)
            $url = bhfe_hp_cpa_state_default_url( $t->term_id );
        }
        $opts .= '<option value="' . esc_attr( $t->term_id ) . '"'
            . ( $url ? ' data-url="' . esc_url( $url ) . '"' : '' )
            . '>' . esc_html( $t->name ) . '</option>';
    }
    return $opts;
}

/** HERO — reads the ACF hero band on the front page; falls back to defaults. */
function bhfe_hp_hero() {
    $pid     = (int) get_option( 'page_on_front' );
    $heading = 'CPE & CE Courses for Financial Professionals';
    $sub     = 'Self-study courses in print or PDF — approved for CPA, CFP®, EA, and more. Instant access after checkout.';
    $bg      = 0;

    if ( $pid && function_exists( 'have_rows' ) && have_rows( 'flexible_content', $pid ) ) {
        while ( have_rows( 'flexible_content', $pid ) ) {
            the_row();
            if ( get_row_layout() === 'hero' ) {
                $h = get_sub_field( 'heading' );          if ( $h ) { $heading = $h; }
                $s = get_sub_field( 'subheading' );        if ( $s ) { $sub = $s; }
                $i = get_sub_field( 'background_image' );  if ( $i ) { $bg = $i; }
                break;
            }
        }
    }

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

/**
 * "Find Your Courses" — final design (Claude Design option 2C).
 * Compact tiles: credential name top-left, "Pick an option +" cue bottom-left;
 * on hover / keyboard focus / tap the option chips slide up over the tile.
 * CPA reveals the native state-ethics form (works without JS).
 * Every destination comes from bhfe_hp_link() — editable in wp-admin under
 * Settings → BHFE Homepage; defaults live in bhfe_hp_link_fields().
 */
function bhfe_hp_band_courses_c() {
    // tile schema: link key prefix, display name (chip labels derive from it),
    // ethics chip label ('' = no ethics chip; CPA is special-cased below)
    $defs = array(
        array( 'cpa',    'CPA', '' ),
        array( 'cfp',    'CFP<sup>&reg;</sup>', 'CFP<sup>&reg;</sup> Ethics' ),
        array( 'eaotrp', 'EA / OTRP / ERPA', 'Ethics' ),
        array( 'iar',    'IAR', 'Ethics' ),
        array( 'cima',   'CIMA<sup>&reg;</sup> / CPWA<sup>&reg;</sup> / RMA<sup>&reg;</sup>', '' ),
        array( 'cdfa',   'CDFA<sup>&reg;</sup>', '' ),
    );
    $tiles = '';
    foreach ( $defs as $d ) {
        list( $key, $name, $eth_label ) = $d;
        // chips render ® inline (no <sup>) like the design — at 12.5px a superscript
        // makes the following space look collapsed
        $all_label = 'All ' . str_replace( array( '<sup>', '</sup>' ), '', $name ) . ' Courses';
        $eth_label = str_replace( array( '<sup>', '</sup>' ), '', $eth_label );
        $col       = '';
        if ( 'cpa' === $key ) {
            $col   = ' is-col';
            $filter  = bhfe_hp_cpa_filter();
            // subject must be a hidden input, not a query arg on the action URL —
            // browsers drop the action's query string on GET submit
            $subject = $filter['subject'] > 0
                ? '<input type="hidden" name="subject" value="' . (int) $filter['subject'] . '">'
                : '';
            $chips = '<a class="bhfe-cf-xopt bhfe-cf-xopt--all is-cpa-all" href="' . esc_url( bhfe_hp_link( 'cpa_all' ) ) . '">' . $all_label . '</a>'
                . '<form class="bhfe-cf-stateform" method="get" action="' . esc_url( bhfe_hp_link( 'cpa_ethics' ) ) . '">'
                .   '<input type="hidden" name="credit_type[]" value="cpa">'
                .   $subject
                .   '<label class="bhfe-sr-only" for="bhfe-cpa-state-c">Your state for CPA ethics</label>'
                .   '<select class="bhfe-cf-stateselect" id="bhfe-cpa-state-c" name="state">' . bhfe_hp_state_options( 'State Ethics: Select your state&hellip;' ) . '</select>'
                .   '<button class="bhfe-cf-statego" type="submit" aria-label="View CPA ethics for the selected state">Go</button>'
                . '</form>';
        } elseif ( $eth_label ) {
            $chips = '<a class="bhfe-cf-xopt bhfe-cf-xopt--all is-grow" href="' . esc_url( bhfe_hp_link( $key . '_all' ) ) . '">' . $all_label . '</a>'
                . '<a class="bhfe-cf-xopt bhfe-cf-xopt--eth" href="' . esc_url( bhfe_hp_link( $key . '_ethics' ) ) . '">' . $eth_label . '</a>';
        } else {
            $chips = '<a class="bhfe-cf-xopt bhfe-cf-xopt--all" href="' . esc_url( bhfe_hp_link( $key . '_all' ) ) . '">' . $all_label . '</a>';
        }
        $tiles .= '<div class="bhfe-cf-xtile" role="group" aria-label="' . esc_attr( wp_strip_all_tags( str_replace( '&reg;', "\xC2\xAE", $name ) ) ) . ' course options" tabindex="0">'
            . '<div class="bhfe-cf-xname">' . wp_kses_post( $name ) . '</div>'
            . '<div class="bhfe-cf-xcue" aria-hidden="true">Pick an option <b>+</b></div>'
            . '<div class="bhfe-cf-xchips' . $col . '">' . $chips . '</div>'
            . '</div>';
    }
    return '<section class="bhfe-band bhfe-cf-courses bhfe-cf-courses--c" id="find-courses" aria-labelledby="bhfe-cf-title-c">'
        . '<div class="bhfe-cf-card">'
        .   '<div class="bhfe-cf-picker">'
        .     '<div class="bhfe-cf-head">'
        .       '<h2 class="bhfe-cf-h2" id="bhfe-cf-title-c">Find the course(s) you need now</h2>'
        .       '<p class="bhfe-cf-lead">Hover over a credential to view options or <a href="' . esc_url( bhfe_hp_link( 'catalog' ) ) . '">view our full catalog</a>.</p>'
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
    $p_nasaa = 'NASAA does not endorse any particular provider of CE courses. The content of the course and any views expressed are my/our own and do not necessarily reflect the views of NASAA or any of its member jurisdictions. Sponsor ID S17460.';
    $p_iwi   = 'Provider ID 222740';

    // 4th element = disclosure popover shown on hover / focus / tap of the tile
    // (empty = no popover); optional 5th = extra note class (e.g. wide for long text).
    $logos = array(
        array( $img . 'Affiliation-CFP.png',   'CFP® certification marks', '', $p1 ),
        array( $pimg . 'nasba.png',            'NASBA National Registry of CPE Sponsors — QAS Self Study', ' bhfe-accred__logo--tall', $p2 ),
        array( $pimg . 'idfa-logo.png',        'IDFA — Institute for Divorce Financial Analysts', '', $p_idfa ),
        array( $pimg . 'ce.png',               'IRS — Approved Continuing Education Provider', '', $p_irs, ' bhfe-accred__note--wide' ),
        array( $img . 'nasaa_logo_blue.png',   'NASAA — North American Securities Administrators Association', '', $p_nasaa ),
        array( $img . 'iwi_logo.png',          'Investments & Wealth Institute', '', $p_iwi ),
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
        . '</section>';
}

/** Echo the whole homepage (called by the plugin's front-page template). */
function bhfe_hp_render() {
    echo bhfe_hp_hero();
    echo bhfe_hp_band_discount();
    echo bhfe_hp_band_courses_c(); // links mapped in Settings → BHFE Homepage
    echo bhfe_hp_band_accreditation();
    echo bhfe_hp_band_benefits();
}
