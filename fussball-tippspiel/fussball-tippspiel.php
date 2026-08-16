<?php
/**
 * Plugin Name:       Tippstube
 * Description:       Tippstube — das private Fußball-Tippspiel für deine Tipprunde. Echtes WordPress-Login, Tipprunden, Statistik/Achievements, Pinnwand-Chat pro Runde. Spieldaten: 1./2. Bundesliga via OpenLigaDB (aktuelle Saison, gratis), DFB-Pokal/CL/EL/Nations League via API-Football.
 * Version:           0.8.12
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Tippstube
 * License:           GPL-2.0-or-later
 * Text Domain:       fussball-tippspiel
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'FTIPP_VERSION', '0.8.12' );
define( 'FTIPP_DB_VERSION', '5' );

/** Wettbewerbe: interne ID => [Name, API-Football Liga-ID, Art] */
function ftipp_leagues() {
    return array(
        'BL1' => array( 'name' => '1. Bundesliga',     'api' => 78,  'kind' => 'league' ),
        'BL2' => array( 'name' => '2. Bundesliga',     'api' => 79,  'kind' => 'league' ),
        'DFB' => array( 'name' => 'DFB-Pokal',         'api' => 81,  'kind' => 'cup' ),
        'CL'  => array( 'name' => 'Champions League',  'api' => 2,   'kind' => 'cup' ),
        'EL'  => array( 'name' => 'Europa League',     'api' => 3,   'kind' => 'cup' ),
        'NL'  => array( 'name' => 'Nations League',    'api' => 5,   'kind' => 'cup' ),
    );
}
function ftipp_comp_ids() { return array_keys( ftipp_leagues() ); }
function ftipp_default_cfg() {
    return array( 'pTend' => 1, 'pExact' => 3, 'pKO' => 3, 'malusOn' => true, 'malus' => -1, 'deadlineMin' => 60 );
}

/* ============================================================
 * DB-Schema
 * ============================================================ */
function ftipp_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $p = $wpdb->prefix;

    dbDelta( "CREATE TABLE {$p}ftipp_rounds (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(190) NOT NULL,
        code VARCHAR(20) NOT NULL,
        mode VARCHAR(20) NOT NULL DEFAULT 'friendly',
        admin_user_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY code (code)
    ) $charset_collate;" );

    dbDelta( "CREATE TABLE {$p}ftipp_round_members (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        round_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        joined_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY round_user (round_id,user_id)
    ) $charset_collate;" );

    dbDelta( "CREATE TABLE {$p}ftipp_round_config (
        round_id BIGINT UNSIGNED NOT NULL,
        comp_id VARCHAR(10) NOT NULL,
        p_tend INT NOT NULL DEFAULT 1,
        p_exact INT NOT NULL DEFAULT 3,
        p_ko INT NOT NULL DEFAULT 3,
        malus_on TINYINT NOT NULL DEFAULT 1,
        malus INT NOT NULL DEFAULT -1,
        deadline_min INT NOT NULL DEFAULT 60,
        seeded_specials TINYINT NOT NULL DEFAULT 0,
        seeded_special_keys TEXT NULL,
        PRIMARY KEY  (round_id,comp_id)
    ) $charset_collate;" );

    dbDelta( "CREATE TABLE {$p}ftipp_subs (
        user_id BIGINT UNSIGNED NOT NULL,
        comp_id VARCHAR(10) NOT NULL,
        active TINYINT NOT NULL DEFAULT 0,
        PRIMARY KEY  (user_id,comp_id)
    ) $charset_collate;" );

    dbDelta( "CREATE TABLE {$p}ftipp_tips (
        user_id BIGINT UNSIGNED NOT NULL,
        comp_id VARCHAR(10) NOT NULL,
        fixture_id VARCHAR(64) NOT NULL,
        hg INT NULL,
        ag INT NULL,
        ko_decided VARCHAR(10) NULL,
        ko_winner VARCHAR(10) NULL,
        committed TINYINT NOT NULL DEFAULT 0,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (user_id,comp_id,fixture_id)
    ) $charset_collate;" );

    dbDelta( "CREATE TABLE {$p}ftipp_special (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        round_id BIGINT UNSIGNED NOT NULL,
        comp_id VARCHAR(10) NOT NULL,
        label VARCHAR(190) NOT NULL,
        type VARCHAR(30) NOT NULL DEFAULT 'custom',
        points INT NOT NULL DEFAULT 10,
        deadline DATETIME NULL,
        result VARCHAR(190) NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;" );

    dbDelta( "CREATE TABLE {$p}ftipp_special_tips (
        special_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        value VARCHAR(190) NULL,
        PRIMARY KEY  (special_id,user_id)
    ) $charset_collate;" );

    dbDelta( "CREATE TABLE {$p}ftipp_chat (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        round_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        message VARCHAR(500) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY round_id (round_id)
    ) $charset_collate;" );

    dbDelta( "CREATE TABLE {$p}ftipp_notified (
        fixture_id VARCHAR(64) NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        type VARCHAR(20) NOT NULL,
        sent_at DATETIME NOT NULL,
        PRIMARY KEY  (fixture_id,user_id,type)
    ) $charset_collate;" );

    update_option( 'ftipp_db_version', FTIPP_DB_VERSION );
}
add_filter( 'cron_schedules', function ( $s ) {
    $s['ftipp_15min'] = array( 'interval' => 15 * 60, 'display' => 'Alle 15 Minuten (Tippstube)' );
    return $s;
} );
register_activation_hook( __FILE__, function () {
    ftipp_install();
    if ( ! wp_next_scheduled( 'ftipp_weekly_fetch' ) ) {
        wp_schedule_event( time() + 60, 'weekly', 'ftipp_weekly_fetch' );
    }
    if ( ! wp_next_scheduled( 'ftipp_reminder_check' ) ) {
        wp_schedule_event( time() + 120, 'ftipp_15min', 'ftipp_reminder_check' );
    }
    if ( ! wp_next_scheduled( 'ftipp_newsletter_check' ) ) {
        wp_schedule_event( time() + 180, 'hourly', 'ftipp_newsletter_check' );
    }
} );
add_action( 'plugins_loaded', function () {
    if ( get_option( 'ftipp_db_version' ) !== FTIPP_DB_VERSION ) { ftipp_install(); }
    // Cron-Jobs nachrüsten, falls das Plugin schon vor dieser Version aktiv war (kein erneutes "Aktivieren" nötig).
    if ( ! wp_next_scheduled( 'ftipp_reminder_check' ) ) { wp_schedule_event( time() + 120, 'ftipp_15min', 'ftipp_reminder_check' ); }
    if ( ! wp_next_scheduled( 'ftipp_newsletter_check' ) ) { wp_schedule_event( time() + 180, 'hourly', 'ftipp_newsletter_check' ); }
} );
register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'ftipp_weekly_fetch' );
    wp_clear_scheduled_hook( 'ftipp_reminder_check' );
    wp_clear_scheduled_hook( 'ftipp_newsletter_check' );
} );

/* ============================================================
 * Spieldaten-Abruf — Hybrid:
 *   1. Bundesliga + 2. Bundesliga  -> OpenLigaDB (aktuelle Saison, gratis, ohne Key)
 *   DFB-Pokal + CL + EL + Nations League -> API-Football (verlässliche n.V./Elfmeter-Kennung)
 * Grund für den Split: OpenLigaDB liefert bei Pokal-/K.o.-Spielen keine zuverlässige
 * Kennzeichnung "nach Verlängerung/Elfmeterschießen" — das würde den K.o.-Zusatztipp
 * (Punkte für richtig getippte Verlängerung/Elfmeterschießen) riskant/falsch machen.
 * Bei BL1/BL2 gibt es diese Mehrdeutigkeit nicht (normale Ligaspiele ohne Verlängerung).
 * ============================================================ */
function ftipp_current_de_season() {
    $m = intval( gmdate( 'n' ) ); $y = intval( gmdate( 'Y' ) );
    return ( $m >= 7 ) ? $y : ( $y - 1 );
}
function ftipp_openligadb_shortcut( $comp_id ) {
    $map = array( 'BL1' => 'bl1', 'BL2' => 'bl2' );
    return isset( $map[ $comp_id ] ) ? $map[ $comp_id ] : null;
}

/** Rundenbezeichnungen von API-Football (englisch) ins Deutsche übersetzen. */
function ftipp_translate_round( $text ) {
    $text = trim( (string) $text );
    $map = array(
        '/^Regular Season\s*-\s*(\d+)$/i' => 'Spieltag $1',
        '/^1st Round$/i'                  => '1. Runde',
        '/^2nd Round$/i'                  => '2. Runde',
        '/^3rd Round$/i'                  => '3. Runde',
        '/^Round of 16$/i'                => 'Achtelfinale',
        '/^Round of (\d+)$/i'             => 'Runde der letzten $1',
        '/^Quarter-?finals?$/i'           => 'Viertelfinale',
        '/^Semi-?finals?$/i'              => 'Halbfinale',
        '/^Final$/i'                      => 'Finale',
        '/^Group Stage\s*-\s*(\d+)$/i'    => 'Gruppenphase, Spieltag $1',
        '/^League Stage\s*-\s*(\d+)$/i'   => 'Ligaphase, Spieltag $1',
        '/^League ([ABCD])\s*-\s*(\d+)$/i' => 'Liga $1, Spieltag $2',
        '/^Preliminary Round$/i'          => 'Vorrunde',
        '/^Qualifying Round$/i'           => 'Qualifikation',
    );
    foreach ( $map as $pattern => $repl ) {
        $out = preg_replace( $pattern, $repl, $text );
        if ( null !== $out && $out !== $text ) { return $out; }
    }
    return $text;
}
/** Team-Namen von API-Football (teils englische Schreibweise) für den DFB-Pokal eindeutschen. */
function ftipp_translate_team( $name ) {
    $map = array(
        'Bayern Munich' => 'Bayern München', 'Borussia Monchengladbach' => "Borussia M'gladbach",
        'Borussia M.Gladbach' => "Borussia M'gladbach", 'FC Cologne' => '1. FC Köln',
        '1.FC Koln' => '1. FC Köln', 'Fortuna Duesseldorf' => 'Fortuna Düsseldorf',
        'Union Berlin' => '1. FC Union Berlin', 'Nurnberg' => '1. FC Nürnberg',
        'Greuther Furth' => 'SpVgg Greuther Fürth', 'Kaiserslautern' => '1. FC Kaiserslautern',
        'Hertha Berlin' => 'Hertha BSC', 'Darmstadt' => 'SV Darmstadt 98',
        'St Pauli' => 'FC St. Pauli', 'St. Pauli' => 'FC St. Pauli',
        'Wurzburger Kickers' => 'Würzburger Kickers', 'Bielefeld' => 'Arminia Bielefeld',
    );
    return isset( $map[ $name ] ) ? $map[ $name ] : $name;
}

/** OpenLigaDB-Abruf für eine deutsche Liga (bl1/bl2), gratis, ohne Auth. */
function ftipp_fetch_openligadb( $shortcut, $season ) {
    $url  = "https://api.openligadb.de/getmatchdata/{$shortcut}/{$season}";
    $resp = wp_remote_get( $url, array( 'timeout' => 25 ) );
    if ( is_wp_error( $resp ) ) { return array( 'ok' => false, 'error' => $resp->get_error_message(), 'fixtures' => array() ); }
    $code = (int) wp_remote_retrieve_response_code( $resp );
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( 200 !== $code || ! is_array( $body ) ) { return array( 'ok' => false, 'error' => 'HTTP ' . $code, 'fixtures' => array() ); }

    $fx = array();
    foreach ( $body as $m ) {
        $finished = ! empty( $m['matchIsFinished'] );
        $hg = null; $ag = null;
        if ( ! empty( $m['matchResults'] ) && is_array( $m['matchResults'] ) ) {
            foreach ( $m['matchResults'] as $r ) {
                if ( 2 === intval( $r['resultTypeID'] ) ) { $hg = intval( $r['pointsTeam1'] ); $ag = intval( $r['pointsTeam2'] ); }
            }
        }
        $rawDate = isset( $m['matchDateTime'] ) ? $m['matchDateTime'] : '';
        $date = substr( str_replace( ' ', 'T', $rawDate ), 0, 16 );
        $fx[] = array(
            'id'      => 'oldb-' . $m['matchID'],
            'round'   => isset( $m['group']['groupName'] ) ? $m['group']['groupName'] : 'Spieltag',
            'date'    => $date,
            'home'    => isset( $m['team1']['teamName'] ) ? $m['team1']['teamName'] : '',
            'away'    => isset( $m['team2']['teamName'] ) ? $m['team2']['teamName'] : '',
            'hg'      => ( $finished && null !== $hg ) ? $hg : null,
            'ag'      => ( $finished && null !== $ag ) ? $ag : null,
            'status'  => ( $finished && null !== $hg ) ? 'FT' : 'NS',
            'ko'      => false, 'decided' => null, 'winner' => null,
        );
    }
    return array( 'ok' => true, 'fixtures' => $fx );
}

function ftipp_fetch_all() {
    $key       = trim( (string) get_option( 'ftipp_api_key', '' ) );
    $season    = intval( get_option( 'ftipp_season', 2026 ) );
    $de_season = ftipp_current_de_season();

    $all = array(); $counts = array(); $errors = array(); $sources = array();

    // 1) Deutsche Ligen zuerst über OpenLigaDB (aktuelle Saison, gratis).
    foreach ( array( 'BL1', 'BL2' ) as $cid ) {
        $r = ftipp_fetch_openligadb( ftipp_openligadb_shortcut( $cid ), $de_season );
        if ( $r['ok'] && count( $r['fixtures'] ) > 0 ) {
            $all[ $cid ] = $r['fixtures']; $counts[ $cid ] = count( $r['fixtures'] );
            $errors[ $cid ] = ''; $sources[ $cid ] = 'OpenLigaDB (aktuelle Saison)';
        } else {
            $errors[ $cid ] = $r['ok'] ? 'OpenLigaDB: keine Daten' : ( 'OpenLigaDB: ' . $r['error'] );
        }
    }

    // 2) Alles, was noch offen ist (DFB/CL/EL/NL immer, BL1/BL2 nur als Fallback) über API-Football.
    foreach ( ftipp_leagues() as $cid => $lg ) {
        if ( isset( $all[ $cid ] ) ) { continue; } // schon per OpenLigaDB geladen
        if ( $key === '' ) {
            $counts[ $cid ] = 0; $all[ $cid ] = array(); $sources[ $cid ] = '—';
            $errors[ $cid ] = trim( ( isset( $errors[ $cid ] ) ? $errors[ $cid ] . ' · ' : '' ) . 'Kein API-Football-Key hinterlegt.' );
            continue;
        }
        $url = add_query_arg( array( 'league' => $lg['api'], 'season' => $season ), 'https://v3.football.api-sports.io/fixtures' );
        $resp = wp_remote_get( $url, array( 'headers' => array( 'x-apisports-key' => $key ), 'timeout' => 25 ) );
        $sources[ $cid ] = 'API-Football';

        if ( is_wp_error( $resp ) ) {
            $all[ $cid ] = array(); $counts[ $cid ] = 0; $errors[ $cid ] = $resp->get_error_message();
            continue;
        }
        $code = (int) wp_remote_retrieve_response_code( $resp );
        $body = json_decode( wp_remote_retrieve_body( $resp ), true );

        if ( 200 !== $code || ! isset( $body['response'] ) || ! is_array( $body['response'] ) ) {
            $all[ $cid ] = array(); $counts[ $cid ] = 0;
            $errors[ $cid ] = 'HTTP ' . $code . ( isset( $body['errors'] ) ? ' · ' . wp_json_encode( $body['errors'] ) : '' );
            continue;
        }

        $fx = array();
        foreach ( $body['response'] as $m ) {
            $short  = isset( $m['fixture']['status']['short'] ) ? $m['fixture']['status']['short'] : 'NS';
            $isFT   = in_array( $short, array( 'FT', 'AET', 'PEN' ), true );
            $winner = null;
            if ( ! empty( $m['teams']['home']['winner'] ) )      { $winner = 'home'; }
            elseif ( ! empty( $m['teams']['away']['winner'] ) )  { $winner = 'away'; }
            $decided = ( 'AET' === $short ) ? 'aet' : ( ( 'PEN' === $short ) ? 'pen' : null );
            $home = isset( $m['teams']['home']['name'] ) ? $m['teams']['home']['name'] : '';
            $away = isset( $m['teams']['away']['name'] ) ? $m['teams']['away']['name'] : '';
            if ( 'DFB' === $cid ) { $home = ftipp_translate_team( $home ); $away = ftipp_translate_team( $away ); }

            $fx[] = array(
                'id'      => 'api-' . $m['fixture']['id'],
                'round'   => ftipp_translate_round( isset( $m['league']['round'] ) ? $m['league']['round'] : 'Spieltag' ),
                'date'    => isset( $m['fixture']['date'] ) ? substr( $m['fixture']['date'], 0, 16 ) : '',
                'home'    => $home, 'away' => $away,
                'hg'      => isset( $m['goals']['home'] ) ? $m['goals']['home'] : null,
                'ag'      => isset( $m['goals']['away'] ) ? $m['goals']['away'] : null,
                'status'  => $isFT ? 'FT' : 'NS',
                'ko'      => ( 'cup' === $lg['kind'] ),
                'decided' => $decided,
                'winner'  => $winner,
            );
        }
        $all[ $cid ] = $fx; $counts[ $cid ] = count( $fx );
    }

    update_option( 'ftipp_fixtures', $all, false );
    $meta = array( 'last_fetch' => time(), 'season' => $season, 'de_season' => $de_season, 'counts' => $counts, 'errors' => $errors, 'sources' => $sources );
    update_option( 'ftipp_meta', $meta, false );

    return array( 'ok' => true, 'meta' => $meta );
}
add_action( 'ftipp_weekly_fetch', 'ftipp_fetch_all' );

/**
 * Test-Spiele mit Anpfiff in den nächsten Tagen — unabhängig von der API.
 * Zum Ausprobieren der Tipp-Mechanik (Frist/Sperre, K.o.-Zusatztipp), wenn die
 * echten Daten (Gratis-API nur 2021–2023) alle schon in der Vergangenheit liegen.
 * Überschreibt die aktuell geladenen Spieldaten; per "Spieldaten jetzt abrufen"
 * jederzeit wieder durch echte Daten ersetzbar.
 */
function ftipp_load_test_fixtures() {
    $now = time(); $DAY = 86400;
    $iso = function ( $ts ) { return gmdate( 'Y-m-d\TH:i', $ts ); };
    $past     = $iso( $now - 3 * $DAY );
    $veryPast = $iso( $now - 5 * $DAY );
    $soon     = $iso( $now + 4 * $DAY );
    $verySoon = $iso( $now + 30 * 60 ); // in 30 Min, damit die 60-Min-Frist gleich greift und man das Sperren live sieht

    $fixtures = array(
        'BL1' => array(
            array( 'id' => 'demo-bl1-1', 'round' => 'Spieltag 1', 'date' => $past, 'home' => 'Bayern München', 'away' => 'Bayer Leverkusen', 'hg' => 2, 'ag' => 1, 'status' => 'FT', 'ko' => false, 'decided' => null, 'winner' => null ),
            array( 'id' => 'demo-bl1-2', 'round' => 'Spieltag 2', 'date' => $verySoon, 'home' => '1. FC Union Berlin', 'away' => 'SC Freiburg', 'hg' => null, 'ag' => null, 'status' => 'NS', 'ko' => false, 'decided' => null, 'winner' => null ),
            array( 'id' => 'demo-bl1-3', 'round' => 'Spieltag 2', 'date' => $soon, 'home' => 'Borussia Dortmund', 'away' => 'RB Leipzig', 'hg' => null, 'ag' => null, 'status' => 'NS', 'ko' => false, 'decided' => null, 'winner' => null ),
        ),
        'BL2' => array(
            array( 'id' => 'demo-bl2-1', 'round' => 'Spieltag 1', 'date' => $past, 'home' => 'Hamburger SV', 'away' => '1. FC Köln', 'hg' => 1, 'ag' => 0, 'status' => 'FT', 'ko' => false, 'decided' => null, 'winner' => null ),
            array( 'id' => 'demo-bl2-2', 'round' => 'Spieltag 2', 'date' => $soon, 'home' => 'Schalke 04', 'away' => 'Fortuna Düsseldorf', 'hg' => null, 'ag' => null, 'status' => 'NS', 'ko' => false, 'decided' => null, 'winner' => null ),
        ),
        'DFB' => array(
            array( 'id' => 'demo-dfb-1', 'round' => '1. Runde', 'date' => $veryPast, 'home' => 'SV Elversberg', 'away' => 'Bayern München', 'hg' => 1, 'ag' => 1, 'status' => 'FT', 'ko' => true, 'decided' => 'pen', 'winner' => 'away' ),
            array( 'id' => 'demo-dfb-2', 'round' => '1. Runde', 'date' => $soon, 'home' => 'Preußen Münster', 'away' => 'Borussia Dortmund', 'hg' => null, 'ag' => null, 'status' => 'NS', 'ko' => true, 'decided' => null, 'winner' => null ),
        ),
        'CL' => array(
            array( 'id' => 'demo-cl-1', 'round' => 'Achtelfinale', 'date' => $veryPast, 'home' => 'Real Madrid', 'away' => 'Manchester City', 'hg' => 1, 'ag' => 1, 'status' => 'FT', 'ko' => true, 'decided' => 'aet', 'winner' => 'home' ),
            array( 'id' => 'demo-cl-2', 'round' => 'Achtelfinale', 'date' => $soon, 'home' => 'FC Barcelona', 'away' => 'Paris Saint-Germain', 'hg' => null, 'ag' => null, 'status' => 'NS', 'ko' => true, 'decided' => null, 'winner' => null ),
        ),
        'EL' => array(
            array( 'id' => 'demo-el-1', 'round' => 'Achtelfinale', 'date' => $soon, 'home' => 'AS Rom', 'away' => 'Ajax Amsterdam', 'hg' => null, 'ag' => null, 'status' => 'NS', 'ko' => true, 'decided' => null, 'winner' => null ),
        ),
        'NL' => array(
            array( 'id' => 'demo-nl-1', 'round' => 'Liga A, Spieltag 1', 'date' => $veryPast, 'home' => 'Deutschland', 'away' => 'Ungarn', 'hg' => 2, 'ag' => 0, 'status' => 'FT', 'ko' => true, 'decided' => null, 'winner' => null ),
            array( 'id' => 'demo-nl-2', 'round' => 'Liga A, Spieltag 1', 'date' => $veryPast, 'home' => 'Bosnien und Herzegowina', 'away' => 'Niederlande', 'hg' => 1, 'ag' => 1, 'status' => 'FT', 'ko' => true, 'decided' => null, 'winner' => null ),
            array( 'id' => 'demo-nl-3', 'round' => 'Liga A, Spieltag 2', 'date' => $soon, 'home' => 'Niederlande', 'away' => 'Deutschland', 'hg' => null, 'ag' => null, 'status' => 'NS', 'ko' => true, 'decided' => null, 'winner' => null ),
        ),
    );

    update_option( 'ftipp_fixtures', $fixtures, false );
    update_option( 'ftipp_meta', array(
        'last_fetch' => time(), 'season' => 'demo (Test-Spiele)',
        'counts' => array_map( 'count', $fixtures ), 'errors' => array(),
    ), false );
    return true;
}
add_action( 'admin_post_ftipp_demo', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Keine Berechtigung.' ); }
    check_admin_referer( 'ftipp_demo' );
    ftipp_load_test_fixtures();
    wp_safe_redirect( add_query_arg( array( 'page' => 'ftipp', 'ftipp_demo_done' => '1' ), admin_url( 'options-general.php' ) ) );
    exit;
} );

/* Fixture per id aus der Options-Tabelle finden (für Punkteberechnung) */
function ftipp_fixture_by_id( $comp_id, $fixture_id ) {
    $all = get_option( 'ftipp_fixtures', array() );
    if ( empty( $all[ $comp_id ] ) ) { return null; }
    foreach ( $all[ $comp_id ] as $f ) { if ( $f['id'] === $fixture_id ) { return $f; } }
    return null;
}
function ftipp_fixtures_for( $comp_id ) {
    $all = get_option( 'ftipp_fixtures', array() );
    return isset( $all[ $comp_id ] ) ? $all[ $comp_id ] : array();
}
function ftipp_kickoff_ts( $fixture ) { return strtotime( $fixture['date'] ); }
function ftipp_has_result( $fixture ) { return isset( $fixture['hg'], $fixture['ag'] ) && null !== $fixture['hg'] && null !== $fixture['ag'] && 'FT' === $fixture['status']; }

/* ============================================================
 * Punkte-Logik (Server, Spiegel der Client-Logik in app/index.html)
 * ============================================================ */
function ftipp_match_points( $fixture, $tip, $cfg ) {
    if ( ! ftipp_has_result( $fixture ) || ! $tip || null === $tip['hg'] || null === $tip['ag'] ) {
        return array( 'pts' => 0, 'kind' => '-', 'ko' => 0 );
    }
    $th = intval( $tip['hg'] ); $ta = intval( $tip['ag'] );
    $rh = intval( $fixture['hg'] ); $ra = intval( $fixture['ag'] );
    $pts = 0; $kind = 'daneben';
    if ( $th === $rh && $ta === $ra ) { $pts = $cfg['pExact']; $kind = 'exakt'; }
    elseif ( ( $th <=> $ta ) === ( $rh <=> $ra ) ) { $pts = $cfg['pTend']; $kind = 'tendenz'; }
    $ko = 0;
    if ( ! empty( $fixture['ko'] ) && $th === $ta && ! empty( $fixture['decided'] ) && in_array( $fixture['decided'], array( 'aet', 'pen' ), true )
         && ! empty( $tip['ko_decided'] ) ) {
        if ( $tip['ko_decided'] === $fixture['decided'] && $tip['ko_winner'] === $fixture['winner'] ) { $ko = $cfg['pKO']; }
    }
    return array( 'pts' => $pts, 'kind' => $kind, 'ko' => $ko );
}

function ftipp_round_cfg( $round_id, $comp_id ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ftipp_round_config WHERE round_id=%d AND comp_id=%s", $round_id, $comp_id
    ), ARRAY_A );
    $d = ftipp_default_cfg();
    if ( ! $row ) { return $d; }
    return array(
        'pTend' => intval( $row['p_tend'] ), 'pExact' => intval( $row['p_exact'] ), 'pKO' => intval( $row['p_ko'] ),
        'malusOn' => (bool) intval( $row['malus_on'] ), 'malus' => intval( $row['malus'] ), 'deadlineMin' => intval( $row['deadline_min'] ),
    );
}
function ftipp_round_cfg_all( $round_id ) {
    $out = array();
    foreach ( ftipp_comp_ids() as $cid ) { $out[ $cid ] = ftipp_round_cfg( $round_id, $cid ); }
    return $out;
}

/** Frühester Anstoß-Zeitpunkt einer Spielliste (oder null, wenn keine Termine vorliegen). */
function ftipp_earliest_kickoff( $fixtures ) {
    $min = null;
    foreach ( $fixtures as $f ) {
        if ( empty( $f['date'] ) ) { continue; }
        $ts = ftipp_kickoff_ts( $f );
        if ( ! $ts ) { continue; }
        if ( null === $min || $ts < $min ) { $min = $ts; }
    }
    return $min;
}
function ftipp_deadline_from_ts( $ts ) { return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null; }

/**
 * Erkennt die Nations-League-Gruppen aus den geladenen Ligaphase-Spielen: Teams, die in der
 * Ligaphase (Liga A/B/C/D) gegeneinander spielen, gehören zur selben Gruppe. Es gibt bei der
 * Nations League keine feste Gruppen-ID in den Spieldaten, deswegen wird das rein aus den
 * tatsächlichen Begegnungen abgeleitet (Union-Find über den "hat gegen gespielt"-Graphen).
 */
function ftipp_nl_groups() {
    $stage = array_values( array_filter( ftipp_fixtures_for( 'NL' ), function ( $f ) {
        return isset( $f['round'] ) && preg_match( '/^Liga [ABCD], Spieltag/', $f['round'] );
    } ) );
    if ( ! $stage ) { return array(); }

    $parent = array();
    $find = function ( $x ) use ( &$parent, &$find ) {
        if ( ! isset( $parent[ $x ] ) ) { $parent[ $x ] = $x; }
        if ( $parent[ $x ] !== $x ) { $parent[ $x ] = $find( $parent[ $x ] ); }
        return $parent[ $x ];
    };
    $union = function ( $a, $b ) use ( &$parent, $find ) {
        $ra = $find( $a ); $rb = $find( $b );
        if ( $ra !== $rb ) { $parent[ $ra ] = $rb; }
    };

    $division = array();
    foreach ( $stage as $f ) {
        $find( $f['home'] ); $find( $f['away'] );
        $union( $f['home'], $f['away'] );
        if ( preg_match( '/^Liga ([ABCD])/', $f['round'], $mm ) ) {
            $division[ $f['home'] ] = $mm[1]; $division[ $f['away'] ] = $mm[1];
        }
    }
    $byRoot = array();
    foreach ( $stage as $f ) { $byRoot[ $find( $f['home'] ) ][] = $f; }

    $groups = array();
    foreach ( $byRoot as $fx ) {
        $teams = array();
        foreach ( $fx as $f ) { $teams[ $f['home'] ] = true; $teams[ $f['away'] ] = true; }
        $teamNames = array_keys( $teams );
        sort( $teamNames );
        $groups[] = array(
            'division' => isset( $division[ $teamNames[0] ] ) ? $division[ $teamNames[0] ] : '?',
            'teams'    => $teamNames,
            'deadline' => ftipp_deadline_from_ts( ftipp_earliest_kickoff( $fx ) ),
        );
    }
    usort( $groups, function ( $a, $b ) { return strcmp( $a['division'] . implode( '', $a['teams'] ), $b['division'] . implode( '', $b['teams'] ) ); } );

    $seq = array();
    foreach ( $groups as &$g ) {
        $seq[ $g['division'] ] = isset( $seq[ $g['division'] ] ) ? $seq[ $g['division'] ] + 1 : 1;
        $g['key']   = 'nlgrp_' . substr( md5( implode( '|', $g['teams'] ) ), 0, 10 );
        $g['label'] = 'Gruppensieger Liga ' . $g['division'] . ' – Gruppe ' . $seq[ $g['division'] ] . ' (' . implode( ' · ', $g['teams'] ) . ')';
    }
    unset( $g );
    return $groups;
}

/** Vorbelegte Sonderwertungen je Wettbewerb (werden Runden automatisch einmalig mitgegeben). */
function ftipp_default_specials( $comp_id ) {
    $seasonDeadline = ftipp_deadline_from_ts( ftipp_earliest_kickoff( ftipp_fixtures_for( $comp_id ) ) );
    $map = array(
        'BL1' => array(
            array( 'key' => 'champion',      'label' => 'Deutscher Meister',    'type' => 'champion',      'points' => 10 ),
            array( 'key' => 'herbstmeister', 'label' => 'Herbstmeister',        'type' => 'herbstmeister', 'points' => 10 ),
            array( 'key' => 'relegation',    'label' => 'Absteiger',            'type' => 'relegation',    'points' => 10 ),
            array( 'key' => 'topscorer',     'label' => 'Torschützenkönig',     'type' => 'topscorer',     'points' => 10 ),
            array( 'key' => 'topassist',     'label' => 'Bester Passgeber',     'type' => 'topassist',     'points' => 10 ),
        ),
        'BL2' => array(
            array( 'key' => 'champion',      'label' => '2.-Liga-Meister',      'type' => 'champion',      'points' => 10 ),
            array( 'key' => 'herbstmeister', 'label' => 'Herbstmeister',        'type' => 'herbstmeister', 'points' => 10 ),
            array( 'key' => 'relegation',    'label' => 'Absteiger',            'type' => 'relegation',    'points' => 10 ),
            array( 'key' => 'topscorer',     'label' => 'Torschützenkönig',     'type' => 'topscorer',     'points' => 10 ),
            array( 'key' => 'topassist',     'label' => 'Bester Passgeber',     'type' => 'topassist',     'points' => 10 ),
        ),
        'DFB' => array(
            array( 'key' => 'champion', 'label' => 'DFB-Pokalsieger', 'type' => 'champion', 'points' => 10 ),
        ),
        'CL' => array(
            array( 'key' => 'champion', 'label' => 'Champions-League-Sieger', 'type' => 'champion', 'points' => 10 ),
        ),
        'EL' => array(
            array( 'key' => 'champion', 'label' => 'Europa-League-Sieger', 'type' => 'champion', 'points' => 10 ),
        ),
    );

    if ( isset( $map[ $comp_id ] ) ) {
        $out = array();
        foreach ( $map[ $comp_id ] as $d ) { $out[] = $d + array( 'deadline' => $seasonDeadline ); }
        return $out;
    }

    if ( 'NL' === $comp_id ) {
        $out = array(
            array( 'key' => 'champion',  'label' => 'Nations-League-Sieger', 'type' => 'champion',  'points' => 15, 'deadline' => $seasonDeadline ),
            array( 'key' => 'topscorer', 'label' => 'Torschützenkönig',      'type' => 'topscorer', 'points' => 10, 'deadline' => $seasonDeadline ),
        );
        foreach ( ftipp_nl_groups() as $g ) {
            $out[] = array( 'key' => $g['key'], 'label' => $g['label'], 'type' => 'champion', 'points' => 5, 'deadline' => $g['deadline'] );
        }
        return $out;
    }

    return array();
}

/**
 * Legt die fehlenden Standard-Sonderwertungen für eine Runde+Wettbewerb einmalig an.
 * Pro Runde+Wettbewerb wird gemerkt, welche Kategorien (per stabilem "key") schon einmal
 * angeboten wurden — löscht der Runden-Admin danach eine davon, kommt genau diese nicht wieder,
 * aber neu hinzukommende Kategorien (z.B. bei einem Plugin-Update, oder neu erkannte
 * Nations-League-Gruppen sobald Spieldaten vorliegen) werden trotzdem nachgerüstet.
 */
function ftipp_seed_default_specials( $round_id, $comp_id ) {
    global $wpdb;
    $table = "{$wpdb->prefix}ftipp_round_config";
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT seeded_specials, seeded_special_keys FROM {$table} WHERE round_id=%d AND comp_id=%s", $round_id, $comp_id
    ), ARRAY_A );

    $already = array();
    if ( $row && ! empty( $row['seeded_special_keys'] ) ) {
        $already = array_filter( explode( ',', $row['seeded_special_keys'] ) );
    } elseif ( $row && intval( $row['seeded_specials'] ) === 1 ) {
        // Migration von der alten v0.7.3-Logik (einzelnes Ja/Nein-Flag): die damals einmalig
        // gesetzten Kategorien gelten als bereits vergeben, damit sie nicht doppelt entstehen.
        $already = ( 'BL1' === $comp_id || 'BL2' === $comp_id ) ? array( 'champion', 'herbstmeister' ) : array( 'champion' );
    }
    $alreadySet = array_flip( $already );

    $newKeys = array();
    foreach ( ftipp_default_specials( $comp_id ) as $d ) {
        if ( isset( $alreadySet[ $d['key'] ] ) ) { continue; }
        $wpdb->insert( "{$wpdb->prefix}ftipp_special", array(
            'round_id' => $round_id, 'comp_id' => $comp_id,
            'label' => $d['label'], 'type' => $d['type'], 'points' => $d['points'],
            'deadline' => isset( $d['deadline'] ) ? $d['deadline'] : null,
        ) );
        $newKeys[] = $d['key'];
    }
    if ( ! $newKeys ) { return; }

    $updated = implode( ',', array_merge( $already, $newKeys ) );
    if ( $row ) {
        $wpdb->update( $table, array( 'seeded_special_keys' => $updated ), array( 'round_id' => $round_id, 'comp_id' => $comp_id ) );
    } else {
        $d = ftipp_default_cfg();
        $wpdb->insert( $table, array(
            'round_id' => $round_id, 'comp_id' => $comp_id,
            'p_tend' => $d['pTend'], 'p_exact' => $d['pExact'], 'p_ko' => $d['pKO'],
            'malus_on' => $d['malusOn'] ? 1 : 0, 'malus' => $d['malus'], 'deadline_min' => $d['deadlineMin'],
            'seeded_special_keys' => $updated,
        ) );
    }
}

/** Globale Wettbewerbs-Abos eines Nutzers inkl. DFB-Automatik */
function ftipp_effective_subs( $user_id ) {
    global $wpdb;
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT comp_id, active FROM {$wpdb->prefix}ftipp_subs WHERE user_id=%d", $user_id
    ), ARRAY_A );
    $subs = array();
    foreach ( ftipp_comp_ids() as $cid ) { $subs[ $cid ] = false; }
    foreach ( $rows as $r ) { $subs[ $r['comp_id'] ] = (bool) intval( $r['active'] ); }
    if ( $subs['BL1'] && $subs['BL2'] ) { $subs['DFB'] = true; }
    return $subs;
}

/**
 * Name, der ANDEREN Mitspielern angezeigt wird (Rangliste, Chat, Newsletter) — bewusst NIE die
 * E-Mail-Adresse, auch wenn WordPress' display_name (z.B. bei manuell angelegten Konten, deren
 * Benutzername zufällig die E-Mail-Adresse ist) noch nicht auf einen echten Anzeigenamen umgestellt
 * wurde. Die Registrierungs-Einwilligung erlaubt ausdrücklich nur den Anzeigenamen, nicht die
 * E-Mail — dieser Fallback verhindert, dass versehentlich mehr preisgegeben wird als zugestimmt.
 */
function ftipp_public_name( $user ) {
    if ( ! $user ) { return 'Unbekannt'; }
    $name = trim( (string) $user->display_name );
    if ( '' !== $name && ! is_email( $name ) ) { return $name; }
    $nick = trim( (string) get_user_meta( $user->ID, 'nickname', true ) );
    if ( '' !== $nick && ! is_email( $nick ) ) { return $nick; }
    return 'Spieler #' . $user->ID;
}

function ftipp_is_round_member( $round_id, $user_id ) {
    global $wpdb;
    $n = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ftipp_round_members WHERE round_id=%d AND user_id=%d", $round_id, $user_id
    ) );
    return intval( $n ) > 0;
}
function ftipp_round_admin_id( $round_id ) {
    global $wpdb;
    return intval( $wpdb->get_var( $wpdb->prepare(
        "SELECT admin_user_id FROM {$wpdb->prefix}ftipp_rounds WHERE id=%d", $round_id
    ) ) );
}
function ftipp_is_round_admin( $round_id, $user_id ) {
    return ftipp_round_admin_id( $round_id ) === intval( $user_id ) || current_user_can( 'manage_options' );
}

/** Löscht eine Runde vollständig inkl. aller zugehörigen Daten (Mitgliedschaften, Regeln, Sonderwertungen, Chat). */
function ftipp_delete_round( $round_id ) {
    global $wpdb;
    $specialIds = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}ftipp_special WHERE round_id=%d", $round_id ) );
    if ( $specialIds ) {
        $placeholders = implode( ',', array_fill( 0, count( $specialIds ), '%d' ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ftipp_special_tips WHERE special_id IN ($placeholders)", $specialIds ) );
    }
    $wpdb->delete( "{$wpdb->prefix}ftipp_special", array( 'round_id' => $round_id ) );
    $wpdb->delete( "{$wpdb->prefix}ftipp_round_config", array( 'round_id' => $round_id ) );
    $wpdb->delete( "{$wpdb->prefix}ftipp_chat", array( 'round_id' => $round_id ) );
    $wpdb->delete( "{$wpdb->prefix}ftipp_round_members", array( 'round_id' => $round_id ) );
    $wpdb->delete( "{$wpdb->prefix}ftipp_rounds", array( 'id' => $round_id ) );
}
function ftipp_round_members( $round_id ) {
    global $wpdb;
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT user_id FROM {$wpdb->prefix}ftipp_round_members WHERE round_id=%d ORDER BY joined_at ASC", $round_id
    ), ARRAY_A );
    $out = array();
    foreach ( $rows as $r ) {
        $u = get_userdata( intval( $r['user_id'] ) );
        $out[] = array( 'id' => intval( $r['user_id'] ), 'name' => $u ? ftipp_public_name( $u ) : ( 'Nutzer #' . $r['user_id'] ) );
    }
    return $out;
}
function ftipp_round_object( $round_row, $user_id ) {
    return array(
        'id'       => intval( $round_row['id'] ),
        'name'     => $round_row['name'],
        'code'     => $round_row['code'],
        'mode'     => $round_row['mode'],
        'is_admin' => ftipp_is_round_admin( $round_row['id'], $user_id ),
        'members'  => ftipp_round_members( $round_row['id'] ),
        'config'   => ftipp_round_cfg_all( $round_row['id'] ),
    );
}
/** Rangliste einer Runde für einen Wettbewerb — genutzt von REST /leaderboard und vom Newsletter-Versand. */
function ftipp_compute_leaderboard( $rid, $comp ) {
    global $wpdb;
    $cfg = ftipp_round_cfg( $rid, $comp );
    $fixtures = ftipp_fixtures_for( $comp );
    $members = ftipp_round_members( $rid );
    $round = $wpdb->get_row( $wpdb->prepare( "SELECT mode FROM {$wpdb->prefix}ftipp_rounds WHERE id=%d", $rid ), ARRAY_A );
    $mode = $round ? $round['mode'] : 'friendly';

    $bets = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ftipp_special WHERE round_id=%d AND comp_id=%s", $rid, $comp
    ), ARRAY_A );

    $rows = array();
    foreach ( $members as $m ) {
        $subs = ftipp_effective_subs( $m['id'] );
        if ( empty( $subs[ $comp ] ) ) { continue; }

        $tipRows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ftipp_tips WHERE user_id=%d AND comp_id=%s", $m['id'], $comp
        ), ARRAY_A );
        $byFixture = array();
        foreach ( $tipRows as $t ) { $byFixture[ $t['fixture_id'] ] = $t; }

        $total = 0; $exact = 0; $tend = 0; $koPts = 0; $missed = 0;
        foreach ( $fixtures as $f ) {
            if ( ! ftipp_has_result( $f ) ) { continue; }
            $t = isset( $byFixture[ $f['id'] ] ) ? $byFixture[ $f['id'] ] : null;
            if ( $t && null !== $t['hg'] && null !== $t['ag'] ) {
                $r = ftipp_match_points( $f, $t, $cfg );
                $total += $r['pts'] + $r['ko']; $koPts += $r['ko'];
                if ( 'exakt' === $r['kind'] ) { $exact++; } elseif ( 'tendenz' === $r['kind'] ) { $tend++; }
            } elseif ( $cfg['malusOn'] ) {
                $missed++; $total += $cfg['malus'];
            }
        }
        $special = 0;
        foreach ( $bets as $b ) {
            if ( null === $b['result'] || '' === trim( (string) $b['result'] ) ) { continue; }
            $mv = $wpdb->get_var( $wpdb->prepare(
                "SELECT value FROM {$wpdb->prefix}ftipp_special_tips WHERE special_id=%d AND user_id=%d", $b['id'], $m['id']
            ) );
            if ( $mv !== null && strcasecmp( trim( $mv ), trim( $b['result'] ) ) === 0 ) { $total += intval( $b['points'] ); $special += intval( $b['points'] ); }
        }
        $rows[] = array( 'user_id' => $m['id'], 'name' => $m['name'], 'total' => $total, 'exact' => $exact, 'tend' => $tend, 'ko' => $koPts, 'special' => $special, 'missed' => $missed );
    }

    usort( $rows, function ( $a, $b ) {
        if ( $b['total'] !== $a['total'] ) { return $b['total'] <=> $a['total']; }
        if ( $b['exact'] !== $a['exact'] ) { return $b['exact'] <=> $a['exact']; }
        return $b['ko'] <=> $a['ko'];
    } );

    $rank = 0; $lastTotal = null; $lastKey = null;
    foreach ( $rows as $i => &$r ) {
        if ( 'friendly' === $mode ) {
            if ( $lastTotal === null || $r['total'] !== $lastTotal ) { $rank = $i + 1; }
            $lastTotal = $r['total'];
        } else {
            $key = $r['total'] . '|' . $r['exact'] . '|' . $r['ko'];
            if ( $lastKey === null || $key !== $lastKey ) { $rank = $i + 1; }
            $lastKey = $key;
        }
        $r['rank'] = $rank;
    }
    unset( $r );
    return $rows;
}

function ftipp_generate_code() {
    global $wpdb;
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ( $tries = 0; $tries < 20; $tries++ ) {
        $code = '';
        for ( $i = 0; $i < 6; $i++ ) { $code .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ]; }
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}ftipp_rounds WHERE code=%s", $code ) );
        if ( ! $exists ) { return $code; }
    }
    return strtoupper( wp_generate_password( 6, false ) );
}

/** Beste Schätzung für den Link zur Tippspiel-Seite (Seite mit [tippspiel]-Shortcode), sonst Startseite. */
function ftipp_app_url() {
    global $wpdb;
    $id = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_status='publish' AND post_content LIKE '%[tippspiel%' LIMIT 1" );
    return $id ? get_permalink( $id ) : home_url( '/' );
}

/* ============================================================
 * E-Mail-Benachrichtigungen: Fristen-Erinnerung & Ranking-Newsletter
 * Läuft serverseitig via WP-Cron + wp_mail() (nutzt ein evtl. installiertes
 * SMTP-Plugin wie "WP Mail SMTP" automatisch für zuverlässige Zustellung).
 * ============================================================ */

/** Fristen-Erinnerung: läuft alle 15 Minuten, mailt einmalig pro Spiel+Nutzer (Dedup über ftipp_notified). */
function ftipp_run_reminder_check() {
    if ( ! get_option( 'ftipp_notify_reminders', true ) ) { return; }
    global $wpdb;
    $now = time(); $until = $now + 3 * HOUR_IN_SECONDS;
    $candidates = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$wpdb->prefix}ftipp_subs WHERE active=1" );
    if ( ! $candidates ) { return; }

    $duePerUser = array();
    foreach ( ftipp_leagues() as $cid => $lg ) {
        $fixtures = ftipp_fixtures_for( $cid );
        foreach ( $fixtures as $f ) {
            if ( 'FT' === $f['status'] ) { continue; }
            $ko = strtotime( $f['date'] );
            if ( ! $ko || $ko < $now || $ko > $until ) { continue; }
            foreach ( $candidates as $uid ) {
                $subs = ftipp_effective_subs( $uid );
                if ( empty( $subs[ $cid ] ) ) { continue; }
                $already = $wpdb->get_var( $wpdb->prepare(
                    "SELECT 1 FROM {$wpdb->prefix}ftipp_notified WHERE fixture_id=%s AND user_id=%d AND type='reminder'",
                    $f['id'], $uid
                ) );
                if ( $already ) { continue; }
                $tip = $wpdb->get_row( $wpdb->prepare(
                    "SELECT committed FROM {$wpdb->prefix}ftipp_tips WHERE user_id=%d AND comp_id=%s AND fixture_id=%s",
                    $uid, $cid, $f['id']
                ), ARRAY_A );
                if ( $tip && ! empty( $tip['committed'] ) ) { continue; }
                $duePerUser[ $uid ][] = array( 'comp' => $lg['name'], 'home' => $f['home'], 'away' => $f['away'], 'date' => $ko, 'fixture_id' => $f['id'] );
            }
        }
    }
    if ( ! $duePerUser ) { return; }

    $url = ftipp_app_url();
    foreach ( $duePerUser as $uid => $items ) {
        $u = get_userdata( $uid );
        if ( ! $u || ! $u->user_email ) { continue; }
        $lines = array();
        foreach ( $items as $it ) {
            $lines[] = esc_html( $it['home'] ) . ' – ' . esc_html( $it['away'] ) . ' (' . esc_html( $it['comp'] ) . '), Anpfiff ' . esc_html( wp_date( 'd.m. H:i', $it['date'] ) );
        }
        $subject = '⚽ Du musst noch tippen! (' . count( $items ) . ( 1 === count( $items ) ? ' Spiel' : ' Spiele' ) . ')';
        $body  = '<p>Hallo ' . esc_html( $u->display_name ) . ',</p>';
        $body .= '<p>diese Spiele beginnen bald — du hast noch nicht getippt:</p>';
        $body .= '<p>– ' . implode( '<br>– ', $lines ) . '</p>';
        $body .= '<p><a href="' . esc_url( $url ) . '">Jetzt tippen →</a></p>';
        wp_mail( $u->user_email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
        foreach ( $items as $it ) {
            $wpdb->replace( "{$wpdb->prefix}ftipp_notified", array(
                'fixture_id' => $it['fixture_id'], 'user_id' => $uid, 'type' => 'reminder', 'sent_at' => current_time( 'mysql' ),
            ) );
        }
    }
}
add_action( 'ftipp_reminder_check', 'ftipp_run_reminder_check' );

/** Ranking-Newsletter: läuft stündlich, verschickt nur zum konfigurierten Wochentag/Uhrzeit (einmal pro Woche). */
function ftipp_run_newsletter_check( $force = false ) {
    if ( ! $force ) {
        if ( ! get_option( 'ftipp_notify_newsletter', true ) ) { return 0; }
        $day  = intval( get_option( 'ftipp_newsletter_day', 1 ) );
        $hour = intval( get_option( 'ftipp_newsletter_hour', 9 ) );
        if ( intval( wp_date( 'w' ) ) !== $day || intval( wp_date( 'G' ) ) !== $hour ) { return 0; }
        $weekKey = wp_date( 'oW' );
        if ( get_option( 'ftipp_newsletter_last_sent' ) === $weekKey ) { return 0; }
    }

    global $wpdb;
    $rounds = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}ftipp_rounds", ARRAY_A );
    $url = ftipp_app_url();
    $sent = 0;

    foreach ( $rounds as $round ) {
        $members = ftipp_round_members( $round['id'] );
        foreach ( ftipp_leagues() as $cid => $lg ) {
            $relevant = array_values( array_filter( $members, function ( $m ) use ( $cid ) {
                $s = ftipp_effective_subs( $m['id'] ); return ! empty( $s[ $cid ] );
            } ) );
            if ( count( $relevant ) < 2 ) { continue; }
            $rows = ftipp_compute_leaderboard( $round['id'], $cid );
            if ( ! $rows ) { continue; }
            $top = array_slice( $rows, 0, 3 );

            foreach ( $relevant as $m ) {
                $u = get_userdata( $m['id'] );
                if ( ! $u || ! $u->user_email ) { continue; }
                $mine = null; foreach ( $rows as $r ) { if ( $r['user_id'] === $m['id'] ) { $mine = $r; break; } }

                $lines = array();
                foreach ( $top as $r ) { $lines[] = intval( $r['rank'] ) . '. ' . esc_html( $r['name'] ) . ' — ' . intval( $r['total'] ) . ' Punkte'; }
                $body  = '<p>Hallo ' . esc_html( $u->display_name ) . ',</p>';
                $body .= '<p>aktueller Stand in <b>' . esc_html( $round['name'] ) . '</b> — ' . esc_html( $lg['name'] ) . ':</p>';
                $body .= '<p>' . implode( '<br>', $lines ) . '</p>';
                if ( $mine && intval( $mine['rank'] ) > 3 ) {
                    $body .= '<p>Du: Platz ' . intval( $mine['rank'] ) . ' mit ' . intval( $mine['total'] ) . ' Punkten.</p>';
                }
                $body .= '<p><a href="' . esc_url( $url ) . '">Zur Rangliste →</a></p>';
                wp_mail( $u->user_email, '🏆 Rangliste-Update: ' . $round['name'] . ' — ' . $lg['name'], $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
                $sent++;
            }
        }
    }
    if ( ! $force ) { update_option( 'ftipp_newsletter_last_sent', $weekKey ); }
    return $sent;
}
add_action( 'ftipp_newsletter_check', 'ftipp_run_newsletter_check' );

/**
 * Alle ftipp/v1-REST-Antworten explizit vom Caching ausnehmen (Runden-Liste, Tipp-Speichern, Rangliste, …) —
 * ohne das könnte ein zwischengeschalteter Cache (LiteSpeed, CDN) z.B. eine bereits gelöschte Runde wieder
 * ausliefern oder einen frisch gespeicherten Tipp nicht zeigen, obwohl die Datenbank längst aktuell ist.
 */
add_filter( 'rest_pre_serve_request', function ( $served, $result, $request ) {
    if ( 0 === strpos( $request->get_route(), '/ftipp/v1/' ) ) {
        nocache_headers();
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        if ( function_exists( 'do_action' ) ) { do_action( 'litespeed_control_set_nocache', 'ftipp: REST-API nie cachen' ); }
    }
    return $served;
}, 10, 3 );

/* ============================================================
 * REST API — ftipp/v1
 * ============================================================ */
add_action( 'rest_api_init', function () {

    $auth = function () { return is_user_logged_in(); };

    register_rest_route( 'ftipp/v1', '/data', array(
        'methods' => 'GET', 'permission_callback' => '__return_true',
        'callback' => function () {
            return array(
                'season'   => intval( get_option( 'ftipp_season', 2026 ) ),
                'fixtures' => get_option( 'ftipp_fixtures', (object) array() ),
                'meta'     => get_option( 'ftipp_meta', (object) array() ),
            );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/bootstrap', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function () {
            global $wpdb; $uid = get_current_user_id();
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT r.* FROM {$wpdb->prefix}ftipp_rounds r
                 INNER JOIN {$wpdb->prefix}ftipp_round_members m ON m.round_id=r.id
                 WHERE m.user_id=%d ORDER BY r.created_at ASC", $uid
            ), ARRAY_A );
            $rounds = array();
            foreach ( $rows as $row ) { $rounds[] = ftipp_round_object( $row, $uid ); }
            return array(
                'userId' => $uid, 'userName' => wp_get_current_user()->display_name,
                'isPlatformAdmin' => current_user_can( 'manage_options' ),
                'rounds' => $rounds,
            );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/subs', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function () { return array( 'subs' => ftipp_effective_subs( get_current_user_id() ) ); },
    ) );
    register_rest_route( 'ftipp/v1', '/subs', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'args' => array( 'comp_id' => array( 'required' => true ), 'active' => array( 'required' => true ) ),
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id();
            $comp = sanitize_text_field( $req['comp_id'] );
            if ( ! in_array( $comp, ftipp_comp_ids(), true ) ) { return new WP_Error( 'bad_comp', 'Unbekannter Wettbewerb.', array( 'status' => 400 ) ); }
            $wpdb->replace( "{$wpdb->prefix}ftipp_subs", array( 'user_id' => $uid, 'comp_id' => $comp, 'active' => $req['active'] ? 1 : 0 ) );
            return array( 'subs' => ftipp_effective_subs( $uid ) );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/rounds', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'args' => array( 'name' => array( 'required' => true ) ),
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id();
            $name = trim( sanitize_text_field( $req['name'] ) );
            if ( $name === '' ) { return new WP_Error( 'bad_name', 'Bitte einen Namen angeben.', array( 'status' => 400 ) ); }
            $code = ftipp_generate_code();
            $wpdb->insert( "{$wpdb->prefix}ftipp_rounds", array(
                'name' => $name, 'code' => $code, 'mode' => 'friendly',
                'admin_user_id' => $uid, 'created_at' => current_time( 'mysql' ),
            ) );
            $rid = $wpdb->insert_id;
            $wpdb->insert( "{$wpdb->prefix}ftipp_round_members", array(
                'round_id' => $rid, 'user_id' => $uid, 'joined_at' => current_time( 'mysql' ),
            ) );
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ftipp_rounds WHERE id=%d", $rid ), ARRAY_A );
            return ftipp_round_object( $row, $uid );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/rounds/join', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'args' => array( 'code' => array( 'required' => true ) ),
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id();
            $code = strtoupper( trim( sanitize_text_field( $req['code'] ) ) );
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ftipp_rounds WHERE code=%s", $code ), ARRAY_A );
            if ( ! $row ) { return new WP_Error( 'not_found', 'Kein Runde mit diesem Code gefunden.', array( 'status' => 404 ) ); }
            if ( ! ftipp_is_round_member( $row['id'], $uid ) ) {
                $wpdb->insert( "{$wpdb->prefix}ftipp_round_members", array(
                    'round_id' => $row['id'], 'user_id' => $uid, 'joined_at' => current_time( 'mysql' ),
                ) );
            }
            return ftipp_round_object( $row, $uid );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/rounds/(?P<id>\d+)/config', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $rid = intval( $req['id'] );
            if ( ! ftipp_is_round_admin( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Nur der Runden-Admin darf die Regeln ändern.', array( 'status' => 403 ) ); }
            $comp = sanitize_text_field( $req['comp_id'] );
            if ( ! in_array( $comp, ftipp_comp_ids(), true ) ) { return new WP_Error( 'bad_comp', 'Unbekannter Wettbewerb.', array( 'status' => 400 ) ); }
            $wpdb->replace( "{$wpdb->prefix}ftipp_round_config", array(
                'round_id' => $rid, 'comp_id' => $comp,
                'p_tend' => intval( $req['pTend'] ), 'p_exact' => intval( $req['pExact'] ), 'p_ko' => intval( $req['pKO'] ),
                'malus_on' => ! empty( $req['malusOn'] ) ? 1 : 0, 'malus' => intval( $req['malus'] ), 'deadline_min' => intval( $req['deadlineMin'] ),
            ) );
            return ftipp_round_cfg( $rid, $comp );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/rounds/(?P<id>\d+)/mode', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $rid = intval( $req['id'] );
            if ( ! ftipp_is_round_admin( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Nur der Runden-Admin darf den Modus ändern.', array( 'status' => 403 ) ); }
            $mode = ( 'challenge' === $req['mode'] ) ? 'challenge' : 'friendly';
            $wpdb->update( "{$wpdb->prefix}ftipp_rounds", array( 'mode' => $mode ), array( 'id' => $rid ) );
            return array( 'ok' => true, 'mode' => $mode );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/rounds/(?P<id>\d+)/remove', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $rid = intval( $req['id'] );
            if ( ! ftipp_is_round_admin( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Nur der Runden-Admin darf Mitglieder entfernen.', array( 'status' => 403 ) ); }
            $target = intval( $req['user_id'] );
            if ( $target === ftipp_round_admin_id( $rid ) ) { return new WP_Error( 'bad_target', 'Der Runden-Admin kann nicht entfernt werden.', array( 'status' => 400 ) ); }
            $wpdb->delete( "{$wpdb->prefix}ftipp_round_members", array( 'round_id' => $rid, 'user_id' => $target ) );
            return array( 'ok' => true );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/rounds/(?P<id>\d+)/leave', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $rid = intval( $req['id'] );
            $isAdminHere = ( ftipp_round_admin_id( $rid ) === $uid );
            $memberCount = intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ftipp_round_members WHERE round_id=%d", $rid ) ) );
            if ( $isAdminHere && $memberCount > 1 ) {
                return new WP_Error( 'admin_stuck', 'Als Runden-Admin kannst du nicht verlassen, solange andere Mitglieder da sind.', array( 'status' => 400 ) );
            }
            if ( $isAdminHere ) { ftipp_delete_round( $rid ); }
            else { $wpdb->delete( "{$wpdb->prefix}ftipp_round_members", array( 'round_id' => $rid, 'user_id' => $uid ) ); }
            return array( 'ok' => true );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/rounds/(?P<id>\d+)', array(
        'methods' => 'DELETE', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            $uid = get_current_user_id(); $rid = intval( $req['id'] );
            if ( ! ftipp_is_round_admin( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Nur der Runden-Admin darf die Runde löschen.', array( 'status' => 403 ) ); }
            ftipp_delete_round( $rid );
            return array( 'ok' => true );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/rounds/(?P<id>\d+)/export', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $rid = intval( $req['id'] );
            if ( ! ftipp_is_round_admin( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Nur der Runden-Admin darf die Runde exportieren.', array( 'status' => 403 ) ); }
            $round = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ftipp_rounds WHERE id=%d", $rid ), ARRAY_A );
            if ( ! $round ) { return new WP_Error( 'not_found', 'Runde nicht gefunden.', array( 'status' => 404 ) ); }

            $members = ftipp_round_members( $rid );
            $memberIds = array_map( function ( $m ) { return $m['id']; }, $members );

            $tipsByComp = array();
            $specialByComp = array();
            foreach ( ftipp_comp_ids() as $cid ) {
                $relevant = array_values( array_filter( $members, function ( $m ) use ( $cid ) {
                    $s = ftipp_effective_subs( $m['id'] ); return ! empty( $s[ $cid ] );
                } ) );
                if ( ! $relevant ) { continue; }
                $relevantIds = array_map( function ( $m ) { return $m['id']; }, $relevant );
                $placeholders = implode( ',', array_fill( 0, count( $relevantIds ), '%d' ) );
                $rows = $wpdb->get_results( $wpdb->prepare(
                    "SELECT user_id, fixture_id, hg, ag, ko_decided, ko_winner, committed, updated_at FROM {$wpdb->prefix}ftipp_tips WHERE comp_id=%s AND user_id IN ($placeholders)",
                    array_merge( array( $cid ), $relevantIds )
                ), ARRAY_A );
                if ( $rows ) { $tipsByComp[ $cid ] = $rows; }

                $bets = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ftipp_special WHERE round_id=%d AND comp_id=%s", $rid, $cid ), ARRAY_A );
                if ( $bets ) {
                    foreach ( $bets as &$b ) {
                        $b['tipps'] = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, value FROM {$wpdb->prefix}ftipp_special_tips WHERE special_id=%d", $b['id'] ), ARRAY_A );
                    }
                    unset( $b );
                    $specialByComp[ $cid ] = $bets;
                }
            }

            $ranglisten = array();
            foreach ( ftipp_comp_ids() as $cid ) {
                $rows = ftipp_compute_leaderboard( $rid, $cid );
                if ( $rows ) { $ranglisten[ $cid ] = $rows; }
            }

            return array(
                'exportiert_am' => current_time( 'mysql' ),
                'runde'         => array( 'id' => intval( $round['id'] ), 'name' => $round['name'], 'code' => $round['code'], 'modus' => $round['mode'], 'erstellt_am' => $round['created_at'] ),
                'mitglieder'    => $members,
                'regeln'        => ftipp_round_cfg_all( $rid ),
                'ranglisten'    => $ranglisten,
                'tipps'         => $tipsByComp,
                'sonderwertungen' => $specialByComp,
                'pinnwand'      => $wpdb->get_results( $wpdb->prepare(
                    "SELECT c.user_id, c.message, c.created_at FROM {$wpdb->prefix}ftipp_chat c WHERE c.round_id=%d ORDER BY c.id ASC", $rid
                ), ARRAY_A ),
            );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/tips', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            $uid = get_current_user_id();
            $rid = intval( $req->get_param( 'round' ) );
            $comp = sanitize_text_field( $req->get_param( 'comp' ) );
            if ( ! ftipp_is_round_member( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Du bist kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }

            global $wpdb;
            $cfg = ftipp_round_cfg( $rid, $comp );
            $fixtures = ftipp_fixtures_for( $comp );
            $members = ftipp_round_members( $rid );
            $memberIds = array_map( function ( $m ) { return $m['id']; }, $members );

            $placeholders = implode( ',', array_fill( 0, count( $memberIds ), '%d' ) );
            $tipRows = array();
            if ( $memberIds ) {
                $args = array_merge( array( $comp ), $memberIds );
                $sql = "SELECT * FROM {$wpdb->prefix}ftipp_tips WHERE comp_id=%s AND user_id IN ($placeholders)";
                $tipRows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
            }
            $byUserFixture = array();
            foreach ( $tipRows as $t ) { $byUserFixture[ $t['user_id'] ][ $t['fixture_id'] ] = $t; }

            $out = array();
            foreach ( $fixtures as $f ) {
                $locked = time() >= ( ftipp_kickoff_ts( $f ) - $cfg['deadlineMin'] * 60 );
                $mineRow = isset( $byUserFixture[ $uid ][ $f['id'] ] ) ? $byUserFixture[ $uid ][ $f['id'] ] : null;
                $mine = $mineRow ? array(
                    'h' => $mineRow['hg'] === null ? null : intval( $mineRow['hg'] ),
                    'a' => $mineRow['ag'] === null ? null : intval( $mineRow['ag'] ),
                    'ko' => ( $mineRow['ko_decided'] || $mineRow['ko_winner'] ) ? array( 'decided' => $mineRow['ko_decided'], 'winner' => $mineRow['ko_winner'] ) : null,
                    'committed' => (bool) intval( $mineRow['committed'] ),
                ) : null;
                $revealed = $locked || ( $mine && $mine['committed'] );
                $others = array();
                if ( $revealed ) {
                    foreach ( $members as $m ) {
                        if ( $m['id'] === $uid ) { continue; }
                        $t = isset( $byUserFixture[ $m['id'] ][ $f['id'] ] ) ? $byUserFixture[ $m['id'] ][ $f['id'] ] : null;
                        if ( $t && null !== $t['hg'] && null !== $t['ag'] ) {
                            $others[] = array( 'name' => $m['name'], 'h' => intval( $t['hg'] ), 'a' => intval( $t['ag'] ) );
                        }
                    }
                }
                $out[ $f['id'] ] = array( 'mine' => $mine, 'locked' => $locked, 'revealed' => $revealed, 'others' => $others );
            }
            return array( 'fixtures' => $out );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/tip', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id();
            $rid = intval( $req['round_id'] ); $comp = sanitize_text_field( $req['comp_id'] ); $fid = sanitize_text_field( $req['fixture_id'] );
            if ( ! ftipp_is_round_member( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }
            $fixture = ftipp_fixture_by_id( $comp, $fid );
            if ( ! $fixture ) { return new WP_Error( 'not_found', 'Spiel nicht gefunden.', array( 'status' => 404 ) ); }
            $cfg = ftipp_round_cfg( $rid, $comp );
            if ( time() >= ( ftipp_kickoff_ts( $fixture ) - $cfg['deadlineMin'] * 60 ) ) {
                return new WP_Error( 'locked', 'Dieses Spiel ist bereits gesperrt.', array( 'status' => 400 ) );
            }
            $existing = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ftipp_tips WHERE user_id=%d AND comp_id=%s AND fixture_id=%s", $uid, $comp, $fid
            ), ARRAY_A );
            // Bereits abgegebene Tipps dürfen bis zur Frist weiter geändert werden (siehe "Bearbeiten"-Button
            // im Frontend) — der Commit-Status bleibt dabei unangetastet, nur Ergebnis/K.o.-Tipp werden aktualisiert.
            $h = ( $req['h'] === null || $req['h'] === '' ) ? null : max( 0, intval( $req['h'] ) );
            $a = ( $req['a'] === null || $req['a'] === '' ) ? null : max( 0, intval( $req['a'] ) );
            $ko = $req->get_param( 'ko' );
            $wpdb->replace( "{$wpdb->prefix}ftipp_tips", array(
                'user_id' => $uid, 'comp_id' => $comp, 'fixture_id' => $fid,
                'hg' => $h, 'ag' => $a,
                'ko_decided' => is_array( $ko ) && ! empty( $ko['decided'] ) ? sanitize_text_field( $ko['decided'] ) : null,
                'ko_winner'  => is_array( $ko ) && ! empty( $ko['winner'] )  ? sanitize_text_field( $ko['winner'] )  : null,
                'committed' => $existing ? intval( $existing['committed'] ) : 0,
                'updated_at' => current_time( 'mysql' ),
            ) );
            return array( 'ok' => true );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/tip/commit', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id();
            $rid = intval( $req['round_id'] ); $comp = sanitize_text_field( $req['comp_id'] ); $fid = sanitize_text_field( $req['fixture_id'] );
            if ( ! ftipp_is_round_member( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }
            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ftipp_tips WHERE user_id=%d AND comp_id=%s AND fixture_id=%s", $uid, $comp, $fid
            ), ARRAY_A );
            if ( ! $row || null === $row['hg'] || null === $row['ag'] ) {
                return new WP_Error( 'incomplete', 'Bitte zuerst ein vollständiges Ergebnis eintragen.', array( 'status' => 400 ) );
            }
            $wpdb->update( "{$wpdb->prefix}ftipp_tips", array( 'committed' => 1 ), array( 'user_id' => $uid, 'comp_id' => $comp, 'fixture_id' => $fid ) );
            return array( 'ok' => true );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/leaderboard', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            $uid = get_current_user_id();
            $rid = intval( $req->get_param( 'round' ) );
            $comp = sanitize_text_field( $req->get_param( 'comp' ) );
            if ( ! ftipp_is_round_member( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }
            return array( 'rows' => ftipp_compute_leaderboard( $rid, $comp ) );
        },
    ) );

    register_rest_route( 'ftipp/v1', '/special', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id();
            $rid = intval( $req->get_param( 'round' ) ); $comp = sanitize_text_field( $req->get_param( 'comp' ) );
            if ( ! ftipp_is_round_member( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }
            ftipp_seed_default_specials( $rid, $comp );
            $bets = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ftipp_special WHERE round_id=%d AND comp_id=%s ORDER BY id ASC", $rid, $comp
            ), ARRAY_A );
            $out = array();
            foreach ( $bets as $b ) {
                $mv = $wpdb->get_var( $wpdb->prepare(
                    "SELECT value FROM {$wpdb->prefix}ftipp_special_tips WHERE special_id=%d AND user_id=%d", $b['id'], $uid
                ) );
                $out[] = array(
                    'id' => intval( $b['id'] ), 'label' => $b['label'], 'type' => $b['type'], 'points' => intval( $b['points'] ),
                    'deadline' => $b['deadline'] ? str_replace( ' ', 'T', substr( $b['deadline'], 0, 16 ) ) : null,
                    'result' => $b['result'], 'my_value' => $mv,
                );
            }
            return array( 'bets' => $out );
        },
    ) );
    register_rest_route( 'ftipp/v1', '/special', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $rid = intval( $req['round_id'] );
            if ( ! ftipp_is_round_admin( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Nur der Runden-Admin darf Sonderwertungen anlegen.', array( 'status' => 403 ) ); }
            $wpdb->insert( "{$wpdb->prefix}ftipp_special", array(
                'round_id' => $rid, 'comp_id' => sanitize_text_field( $req['comp_id'] ),
                'label' => sanitize_text_field( $req['label'] ), 'type' => sanitize_text_field( $req['type'] ),
                'points' => intval( $req['points'] ), 'deadline' => $req['deadline'] ? str_replace( 'T', ' ', $req['deadline'] ) . ':00' : null,
            ) );
            return array( 'ok' => true, 'id' => $wpdb->insert_id );
        },
    ) );
    register_rest_route( 'ftipp/v1', '/special/(?P<id>\d+)', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $id = intval( $req['id'] );
            $bet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ftipp_special WHERE id=%d", $id ), ARRAY_A );
            if ( ! $bet ) { return new WP_Error( 'not_found', 'Nicht gefunden.', array( 'status' => 404 ) ); }
            if ( ! ftipp_is_round_admin( $bet['round_id'], $uid ) ) { return new WP_Error( 'forbidden', 'Nur der Runden-Admin darf das ändern.', array( 'status' => 403 ) ); }
            $upd = array();
            foreach ( array( 'label', 'type', 'result' ) as $f ) { if ( $req->get_param( $f ) !== null ) { $upd[ $f ] = sanitize_text_field( $req[ $f ] ); } }
            if ( $req->get_param( 'points' ) !== null ) { $upd['points'] = intval( $req['points'] ); }
            if ( $req->get_param( 'deadline' ) !== null ) { $upd['deadline'] = $req['deadline'] ? str_replace( 'T', ' ', $req['deadline'] ) . ':00' : null; }
            if ( $upd ) { $wpdb->update( "{$wpdb->prefix}ftipp_special", $upd, array( 'id' => $id ) ); }
            return array( 'ok' => true );
        },
    ) );
    register_rest_route( 'ftipp/v1', '/special/(?P<id>\d+)', array(
        'methods' => 'DELETE', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $id = intval( $req['id'] );
            $bet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ftipp_special WHERE id=%d", $id ), ARRAY_A );
            if ( ! $bet ) { return array( 'ok' => true ); }
            if ( ! ftipp_is_round_admin( $bet['round_id'], $uid ) ) { return new WP_Error( 'forbidden', 'Nur der Runden-Admin darf löschen.', array( 'status' => 403 ) ); }
            $wpdb->delete( "{$wpdb->prefix}ftipp_special", array( 'id' => $id ) );
            $wpdb->delete( "{$wpdb->prefix}ftipp_special_tips", array( 'special_id' => $id ) );
            return array( 'ok' => true );
        },
    ) );
    register_rest_route( 'ftipp/v1', '/special/(?P<id>\d+)/tip', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $id = intval( $req['id'] );
            $bet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ftipp_special WHERE id=%d", $id ), ARRAY_A );
            if ( ! $bet ) { return new WP_Error( 'not_found', 'Nicht gefunden.', array( 'status' => 404 ) ); }
            if ( ! ftipp_is_round_member( $bet['round_id'], $uid ) ) { return new WP_Error( 'forbidden', 'Kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }
            if ( $bet['deadline'] && strtotime( $bet['deadline'] ) <= time() ) { return new WP_Error( 'locked', 'Frist ist vorbei.', array( 'status' => 400 ) ); }
            $wpdb->replace( "{$wpdb->prefix}ftipp_special_tips", array( 'special_id' => $id, 'user_id' => $uid, 'value' => sanitize_text_field( $req['value'] ) ) );
            return array( 'ok' => true );
        },
    ) );

    /* -------- Statistik, Ranglisten-Verlauf & Achievements -------- */
    register_rest_route( 'ftipp/v1', '/roundstats', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            $uid = get_current_user_id();
            $rid = intval( $req->get_param( 'round' ) ); $comp = sanitize_text_field( $req->get_param( 'comp' ) );
            if ( ! ftipp_is_round_member( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }

            global $wpdb;
            $cfg = ftipp_round_cfg( $rid, $comp );
            $fixtures = ftipp_fixtures_for( $comp );
            $members = ftipp_round_members( $rid );
            $members = array_values( array_filter( $members, function ( $m ) use ( $comp ) {
                $s = ftipp_effective_subs( $m['id'] ); return ! empty( $s[ $comp ] );
            } ) );
            if ( ! $members ) { return array( 'history' => array(), 'mine' => null, 'badges' => array() ); }

            $memberIds = array_map( function ( $m ) { return $m['id']; }, $members );
            $placeholders = implode( ',', array_fill( 0, count( $memberIds ), '%d' ) );
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ftipp_tips WHERE comp_id=%s AND user_id IN ($placeholders)",
                array_merge( array( $comp ), $memberIds )
            ), ARRAY_A );
            $byUserFixture = array();
            foreach ( $rows as $r ) { $byUserFixture[ $r['user_id'] ][ $r['fixture_id'] ] = $r; }

            // Nach Runden-Bezeichnung gruppieren (Reihenfolge = erstes Auftreten in den Fixtures)
            $groups = array(); $order = array();
            foreach ( $fixtures as $f ) {
                $rn = $f['round'];
                if ( ! isset( $groups[ $rn ] ) ) { $groups[ $rn ] = array(); $order[] = $rn; }
                $groups[ $rn ][] = $f;
            }

            $cumulative = array(); foreach ( $members as $m ) { $cumulative[ $m['id'] ] = 0; }
            $roundWins = array(); foreach ( $members as $m ) { $roundWins[ $m['id'] ] = 0; }
            $history = array();

            foreach ( $order as $rn ) {
                $anyResult = false;
                $deltas = array(); foreach ( $members as $m ) { $deltas[ $m['id'] ] = 0; }
                foreach ( $groups[ $rn ] as $f ) {
                    if ( ! ftipp_has_result( $f ) ) { continue; }
                    $anyResult = true;
                    foreach ( $members as $m ) {
                        $t = isset( $byUserFixture[ $m['id'] ][ $f['id'] ] ) ? $byUserFixture[ $m['id'] ][ $f['id'] ] : null;
                        if ( $t && null !== $t['hg'] && null !== $t['ag'] ) {
                            $r = ftipp_match_points( $f, $t, $cfg );
                            $deltas[ $m['id'] ] += $r['pts'] + $r['ko'];
                        } elseif ( $cfg['malusOn'] ) {
                            $deltas[ $m['id'] ] += $cfg['malus'];
                        }
                    }
                }
                if ( ! $anyResult ) { continue; }
                $rowsOut = array(); $maxDelta = null; $winners = array();
                foreach ( $members as $m ) {
                    $cumulative[ $m['id'] ] += $deltas[ $m['id'] ];
                    $rowsOut[] = array( 'user_id' => $m['id'], 'name' => $m['name'], 'delta' => $deltas[ $m['id'] ], 'cum' => $cumulative[ $m['id'] ] );
                    if ( null === $maxDelta || $deltas[ $m['id'] ] > $maxDelta ) { $maxDelta = $deltas[ $m['id'] ]; $winners = array( $m['id'] ); }
                    elseif ( $deltas[ $m['id'] ] === $maxDelta ) { $winners[] = $m['id']; }
                }
                // "Spieltags-Sieger" nur bei eindeutigem Alleingang — bei Gleichstand gewinnt niemand die Auszeichnung.
                if ( count( $members ) > 1 && 1 === count( $winners ) ) { $roundWins[ $winners[0] ]++; }
                $history[] = array( 'round' => $rn, 'rows' => $rowsOut );
            }

            $mine = null; $badges = array();
            if ( in_array( $uid, $memberIds, true ) ) {
                $tips = isset( $byUserFixture[ $uid ] ) ? $byUserFixture[ $uid ] : array();
                $exact = 0; $tend = 0; $miss = 0; $finished = 0; $streak = 0; $maxStreak = 0;
                $roundTotals = array();
                foreach ( $order as $rn ) {
                    $sum = 0; $any = false;
                    foreach ( $groups[ $rn ] as $f ) {
                        if ( ! ftipp_has_result( $f ) ) { continue; }
                        $any = true; $finished++;
                        $t = isset( $tips[ $f['id'] ] ) ? $tips[ $f['id'] ] : null;
                        if ( $t && null !== $t['hg'] && null !== $t['ag'] ) {
                            $r = ftipp_match_points( $f, $t, $cfg );
                            $sum += $r['pts'] + $r['ko'];
                            if ( 'exakt' === $r['kind'] ) { $exact++; $streak++; $maxStreak = max( $maxStreak, $streak ); }
                            else { $streak = 0; if ( 'tendenz' === $r['kind'] ) { $tend++; } }
                        } else {
                            $streak = 0; $miss++;
                            if ( $cfg['malusOn'] ) { $sum += $cfg['malus']; }
                        }
                    }
                    if ( $any ) { $roundTotals[ $rn ] = $sum; }
                }
                $bestRound = null; $worstRound = null;
                foreach ( $roundTotals as $rn => $sum ) {
                    if ( null === $bestRound || $sum > $roundTotals[ $bestRound ] ) { $bestRound = $rn; }
                    if ( null === $worstRound || $sum < $roundTotals[ $worstRound ] ) { $worstRound = $rn; }
                }
                $wins = isset( $roundWins[ $uid ] ) ? $roundWins[ $uid ] : 0;
                $total = array_sum( $roundTotals );
                $mine = array(
                    'finished' => $finished, 'exact' => $exact, 'tend' => $tend, 'missed' => $miss,
                    'trefferquote' => $finished > 0 ? round( ( ( $exact + $tend ) / $finished ) * 100 ) : null,
                    'avgPerRound' => count( $roundTotals ) > 0 ? round( $total / count( $roundTotals ), 1 ) : null,
                    'best'  => null !== $bestRound ? array( 'round' => $bestRound, 'pts' => $roundTotals[ $bestRound ] ) : null,
                    'worst' => null !== $worstRound ? array( 'round' => $worstRound, 'pts' => $roundTotals[ $worstRound ] ) : null,
                    'roundWins' => $wins,
                );
                if ( $maxStreak >= 5 ) { $badges[] = array( 'key' => 'streak5', 'icon' => '🔥', 'label' => '5 exakte Tipps in Folge' ); }
                if ( 0 === $miss && $finished > 0 ) { $badges[] = array( 'key' => 'no_miss', 'icon' => '🎯', 'label' => 'Nie gekniffen — kein Spiel verpasst' ); }
                if ( $wins >= 1 ) { $badges[] = array( 'key' => 'round_winner', 'icon' => '🏅', 'label' => $wins . '× Spieltags-Sieger' ); }
                if ( $total >= 50 ) { $badges[] = array( 'key' => 'p50', 'icon' => '⭐', 'label' => '50-Punkte-Marke geknackt' ); }
                if ( $total >= 100 ) { $badges[] = array( 'key' => 'p100', 'icon' => '🌟', 'label' => '100-Punkte-Marke geknackt' ); }
            }

            return array( 'history' => $history, 'mine' => $mine, 'badges' => $badges );
        },
    ) );

    /* -------- Chat / Pinnwand pro Runde -------- */
    register_rest_route( 'ftipp/v1', '/chat', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id();
            $rid = intval( $req->get_param( 'round' ) );
            if ( ! ftipp_is_round_member( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ftipp_chat WHERE round_id=%d ORDER BY id DESC LIMIT 100", $rid
            ), ARRAY_A );
            $rows = array_reverse( $rows );
            $out = array();
            foreach ( $rows as $r ) {
                $u = get_userdata( intval( $r['user_id'] ) );
                $out[] = array(
                    'id' => intval( $r['id'] ), 'user_id' => intval( $r['user_id'] ),
                    'name' => $u ? ftipp_public_name( $u ) : ( 'Nutzer #' . $r['user_id'] ),
                    'message' => $r['message'], 'created_at' => str_replace( ' ', 'T', $r['created_at'] ),
                );
            }
            return array( 'messages' => $out );
        },
    ) );
    register_rest_route( 'ftipp/v1', '/chat', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id();
            $rid = intval( $req['round_id'] );
            if ( ! ftipp_is_round_member( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }
            $msg = trim( sanitize_text_field( $req['message'] ) );
            if ( '' === $msg ) { return new WP_Error( 'empty', 'Leere Nachricht.', array( 'status' => 400 ) ); }
            $msg = mb_substr( $msg, 0, 500 );
            $wpdb->insert( "{$wpdb->prefix}ftipp_chat", array(
                'round_id' => $rid, 'user_id' => $uid, 'message' => $msg, 'created_at' => current_time( 'mysql' ),
            ) );
            return array( 'ok' => true );
        },
    ) );

    /* -------- Mein Konto: Datenexport & Datenlöschung (DSGVO) -------- */
    register_rest_route( 'ftipp/v1', '/account/export', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function () {
            global $wpdb; $uid = get_current_user_id();
            $u = get_userdata( $uid );
            $out = array(
                'exportiert_am' => current_time( 'mysql' ),
                'konto'         => array( 'anzeigename' => $u->display_name, 'benutzername' => $u->user_login, 'registriert_am' => $u->user_registered ),
                'wettbewerbs_abos' => $wpdb->get_results( $wpdb->prepare( "SELECT comp_id, active FROM {$wpdb->prefix}ftipp_subs WHERE user_id=%d", $uid ), ARRAY_A ),
                'tipps'         => $wpdb->get_results( $wpdb->prepare( "SELECT comp_id, fixture_id, hg, ag, ko_decided, ko_winner, committed, updated_at FROM {$wpdb->prefix}ftipp_tips WHERE user_id=%d", $uid ), ARRAY_A ),
                'sonder_tipps'  => $wpdb->get_results( $wpdb->prepare( "SELECT special_id, value FROM {$wpdb->prefix}ftipp_special_tips WHERE user_id=%d", $uid ), ARRAY_A ),
                'runden'        => $wpdb->get_results( $wpdb->prepare(
                    "SELECT r.id, r.name, r.code, (r.admin_user_id=%d) AS bin_admin FROM {$wpdb->prefix}ftipp_round_members rm
                     JOIN {$wpdb->prefix}ftipp_rounds r ON r.id=rm.round_id WHERE rm.user_id=%d", $uid, $uid
                ), ARRAY_A ),
                'chat_nachrichten' => $wpdb->get_results( $wpdb->prepare( "SELECT round_id, message, created_at FROM {$wpdb->prefix}ftipp_chat WHERE user_id=%d", $uid ), ARRAY_A ),
            );
            return $out;
        },
    ) );
    register_rest_route( 'ftipp/v1', '/account/delete', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function () {
            global $wpdb; $uid = get_current_user_id();
            $adminOf = $wpdb->get_col( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}ftipp_rounds WHERE admin_user_id=%d", $uid ) );

            $wpdb->delete( "{$wpdb->prefix}ftipp_subs", array( 'user_id' => $uid ) );
            $wpdb->delete( "{$wpdb->prefix}ftipp_tips", array( 'user_id' => $uid ) );
            $wpdb->delete( "{$wpdb->prefix}ftipp_special_tips", array( 'user_id' => $uid ) );
            $wpdb->delete( "{$wpdb->prefix}ftipp_chat", array( 'user_id' => $uid ) );
            // Aus allen Runden austreten, in denen der Nutzer NICHT Admin ist (Admin-Runden zuerst übertragen/löschen).
            $wpdb->query( $wpdb->prepare(
                "DELETE rm FROM {$wpdb->prefix}ftipp_round_members rm
                 JOIN {$wpdb->prefix}ftipp_rounds r ON r.id=rm.round_id
                 WHERE rm.user_id=%d AND r.admin_user_id<>%d", $uid, $uid
            ) );
            $wpdb->delete( "{$wpdb->prefix}ftipp_notified", array( 'user_id' => $uid ) );

            return array( 'ok' => true, 'admin_of_rounds_untouched' => $adminOf );
        },
    ) );
} );

/* ============================================================
 * Shortcode [tippspiel] — Login-Gate + eingebettete App
 * ============================================================ */
function ftipp_render_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'height' => '800' ), $atts, 'tippspiel' );
    $min  = max( 300, intval( $atts['height'] ) );

    // Diese Seite (Login-Prompt ODER eingebettete App) darf NIE gecacht werden — sonst bleibt nach jedem
    // Plugin-Update (a) die alte Optik/Logik der Login-Ansicht hängen und (b) die eingebettete App-Seite
    // referenziert über eine im HTML eingebettete alte "?v="-Versionsnummer weiterhin alten App-Code, obwohl
    // serverseitig längst aktualisiert wurde. Bisher war nur die App-Seite selbst (?ftipp_app=1, separat via
    // template_redirect) ausgenommen — dieser äußere Wrapper hier fehlte, war also die Cache-Lücke.
    if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
    if ( ! defined( 'DONOTCACHEOBJECT' ) ) { define( 'DONOTCACHEOBJECT', true ); }
    if ( function_exists( 'do_action' ) ) { do_action( 'litespeed_control_set_nocache', 'ftipp: Tippstube-Seite darf nie gecacht werden' ); }

    if ( ! is_user_logged_in() ) {
        ob_start(); ?>
        <style>
          /* Volles dunkles Band hinter dem Login-Kasten (statt weißem Theme-Hintergrund durchscheinen zu
             lassen) — bewusst nur dieser Abschnitt, Header/Footer der restlichen WordPress-Seite (Divi-Theme)
             bleiben unangetastet, siehe "Plugin vs. Theme"-Prinzip. min-height:100vh sorgt dafür, dass das
             Band mindestens die komplette Bildschirmhöhe füllt (sonst blieb bei kurzen Seiten oben/unten
             noch der weiße Theme-Hintergrund sichtbar), Kasten wird darin vertikal zentriert. */
          .ftipp-auth-page-bg{background:#0b160f;min-height:100vh;box-sizing:border-box;
            margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw);width:100vw;
            display:flex;align-items:center;justify-content:center;padding:6vh 16px;}
          .ftipp-auth-wrap{max-width:400px;width:100%;font-family:-apple-system,system-ui,"Segoe UI",Roboto,Arial,sans-serif;}
          .ftipp-auth{background:#122417;border:1px solid #2a4a30;border-radius:14px;padding:28px 30px;color:#f3ead9;
            box-shadow:0 1px 2px rgba(0,0,0,.25), 0 10px 26px -16px rgba(0,0,0,.55);}
          .ftipp-auth h3{color:#ffffff !important;font-size:22px;margin:0 0 4px !important;font-weight:700 !important;line-height:1.3 !important;letter-spacing:-.01em;font-family:Georgia,'Iowan Old Style','Palatino Linotype',serif;display:flex;align-items:center;gap:8px;}
          .ftipp-auth .ftipp-sub{color:#a7b89f;font-size:13px;margin-bottom:22px;}
          .ftipp-auth form{margin:0;}
          .ftipp-auth form p{margin:0 0 16px !important;}
          .ftipp-auth label{display:block !important;color:#a7b89f !important;font-size:12px !important;font-weight:700 !important;
            text-transform:uppercase;letter-spacing:.04em;margin:0 0 6px !important;}
          .ftipp-auth input[type="text"],
          .ftipp-auth input[type="password"]{
            display:block !important;width:100% !important;box-sizing:border-box !important;
            background:#0d190f !important;border:1px solid #2a4a30 !important;color:#f3ead9 !important;
            border-radius:8px !important;padding:11px 12px !important;font-size:16px !important;
            margin:0 !important;box-shadow:none !important;height:auto !important;line-height:normal !important;
            transition:border-color .15s ease,box-shadow .15s ease;
          }
          .ftipp-auth input[type="text"]:focus,
          .ftipp-auth input[type="password"]:focus{border-color:#d9a441 !important;outline:none !important;box-shadow:0 0 0 3px rgba(217,164,65,.2) !important;}
          .ftipp-auth .login-remember{display:flex;align-items:center;gap:8px;}
          .ftipp-auth .login-remember label{display:inline-flex !important;align-items:center;gap:8px;color:#a7b89f !important;
            font-size:13px !important;font-weight:400 !important;text-transform:none;margin:0 !important;}
          .ftipp-auth input[type="checkbox"]{width:16px !important;height:16px !important;margin:0 !important;accent-color:#d9a441;}
          .ftipp-auth input[type="submit"]{
            display:inline-block !important;background:#d9a441 !important;color:#241a05 !important;border:none !important;
            border-radius:9px !important;padding:12px 26px !important;font-weight:800 !important;font-size:14px !important;
            cursor:pointer !important;box-shadow:none !important;text-shadow:none !important;-webkit-appearance:none !important;
            transition:filter .15s ease;
          }
          .ftipp-auth input[type="submit"]:hover{filter:brightness(1.08);}
          .ftipp-auth .ftipp-reg{margin:18px 0 0;font-size:14px;color:#a7b89f;}
          .ftipp-auth .ftipp-reg a{color:#d9a441;font-weight:700;text-decoration:none;}
          .ftipp-auth .ftipp-reg a:hover{text-decoration:underline;}
          @media(max-width:480px){ .ftipp-auth-page-bg{padding:24px 6px;} .ftipp-auth{padding:22px 20px;} }
        </style>
        <div class="ftipp-auth-page-bg"><div class="ftipp-auth-wrap">
          <div class="ftipp-auth">
            <h3><svg width="26" height="28" viewBox="0 0 240 260" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex:none">
              <defs><linearGradient id="lgShield" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#173a22"/><stop offset="100%" stop-color="#0d2116"/></linearGradient>
              <radialGradient id="lgWin" cx="50%" cy="35%" r="75%"><stop offset="0%" stop-color="#ffe9b8"/><stop offset="55%" stop-color="#e3a83f"/><stop offset="100%" stop-color="#7a5320"/></radialGradient></defs>
              <path d="M20 30 Q20 20 30 20 L210 20 Q220 20 220 30 L220 130 Q220 190 170 220 L120 250 L70 220 Q20 190 20 130 Z" fill="url(#lgShield)" stroke="#d9a441" stroke-width="6" stroke-linejoin="round"/>
              <path d="M70 150 L70 100 Q70 60 120 60 Q170 60 170 100 L170 150 Z" fill="url(#lgWin)"/>
              <line x1="120" y1="62" x2="120" y2="150" stroke="#d9a441" stroke-width="5"/><line x1="73" y1="112" x2="167" y2="112" stroke="#d9a441" stroke-width="5"/>
              <circle cx="120" cy="112" r="26" fill="#f3ead9" stroke="#1c4127" stroke-width="2"/>
              <path d="M120,101.5 L129,108 L125.7,118.6 L114.3,118.6 L111,108 Z" fill="#1c4127"/>
              <circle cx="138" cy="100" r="5" fill="#1c4127"/><circle cx="101" cy="126" r="5" fill="#1c4127"/>
              <path d="M70 150 L70 100 Q70 60 120 60 Q170 60 170 100 L170 150 Z" fill="none" stroke="#d9a441" stroke-width="6" stroke-linejoin="round"/>
            </svg>Tippstube</h3>
            <div class="ftipp-sub">Melde dich an, um deine Tipprunde zu sehen und mitzutippen.</div>
            <?php wp_login_form( array( 'redirect' => get_permalink() ) ); ?>
            <p class="ftipp-reg">Noch kein Konto? <a href="<?php echo esc_url( wp_registration_url() ); ?>">Jetzt registrieren</a></p>
          </div>
        </div></div>
        <?php
        return ob_get_clean();
    }

    // 'v' erzwingt bei jedem Plugin-Update eine neue URL, damit Handy-/Server-Caches (z.B. LiteSpeed
    // Cache auf Hostinger) nie eine veraltete Version der eingebetteten App ausliefern.
    $src = esc_url( add_query_arg( array( 'ftipp_app' => '1', 'v' => FTIPP_VERSION ), home_url( '/' ) ) );
    ob_start(); ?>
    <!-- Volles dunkles Band auch hier (wie beim Login-Kasten) — sonst blieb rund um das eingebettete
         App-Fenster der weiße Theme-Hintergrund der Divi-Seite sichtbar (Seiten-Padding um den Shortcode). -->
    <div style="background:#0b160f;margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw);width:100vw;padding:16px;box-sizing:border-box;">
        <div class="ftipp-embed" style="max-width:1060px;margin:0 auto;">
            <iframe id="ftipp-frame" src="<?php echo $src; ?>" title="Tippstube" loading="lazy"
                    style="width:100%;border:0;display:block;min-height:<?php echo esc_attr( $min ); ?>px;"></iframe>
        </div>
    </div>
    <script>
    (function () {
        window.addEventListener('message', function (e) {
            if (e && e.data && e.data.t === 'ftipp-h') {
                var f = document.getElementById('ftipp-frame');
                if (f && e.data.h) { f.style.height = (parseInt(e.data.h, 10) + 24) + 'px'; }
            }
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tippspiel', 'ftipp_render_shortcode' );

/**
 * Browser-Tab-Icon (Favicon) — nur auf der Seite mit dem [tippspiel]-Shortcode,
 * nicht sitejweit (der Rest der Website behält ihr eigenes Favicon).
 */
add_action( 'wp_head', function () {
    if ( is_admin() ) { return; }
    $post = get_post();
    if ( ! $post || ! has_shortcode( $post->post_content, 'tippspiel' ) ) { return; }
    echo '<link rel="icon" href="' . esc_attr( ftipp_favicon_href() ) . '" type="image/svg+xml">' . "\n";
}, 1 );

/** Data-URI des Tippstube-Wappens (vereinfacht) für Favicon-Links. */
function ftipp_favicon_href() {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 260">'
         . '<path d="M20 30 Q20 20 30 20 L210 20 Q220 20 220 30 L220 130 Q220 190 170 220 L120 250 L70 220 Q20 190 20 130 Z" fill="#173a22" stroke="#d9a441" stroke-width="8"/>'
         . '<path d="M70 150 L70 100 Q70 60 120 60 Q170 60 170 100 L170 150 Z" fill="#e3a83f"/>'
         . '<line x1="120" y1="62" x2="120" y2="150" stroke="#d9a441" stroke-width="6"/>'
         . '<line x1="73" y1="112" x2="167" y2="112" stroke="#d9a441" stroke-width="6"/>'
         . '<circle cx="120" cy="112" r="27" fill="#f3ead9"/>'
         . '<path d="M120,101.5 L129,108 L125.7,118.6 L114.3,118.6 L111,108 Z" fill="#1c4127"/>'
         . '</svg>';
    return 'data:image/svg+xml,' . rawurlencode( $svg );
}

/**
 * Liefert die App (app/index.html) mit eingebettetem Boot-Objekt aus,
 * wenn ?ftipp_app=1 aufgerufen wird. Läuft same-origin, damit WP-Cookies
 * + REST-Nonce für die fetch()-Aufrufe der App funktionieren.
 */
add_action( 'template_redirect', function () {
    if ( ! isset( $_GET['ftipp_app'] ) ) { return; }

    // Diese Seite enthält einen personenbezogenen Sicherheits-Code (Nonce) und ist NIE cachebar —
    // sonst würde nach dem Cachen für spätere Besucher/Aufrufe ein ungültiger/fremder Nonce
    // ausgeliefert und Aktionen (z.B. "Runden laden") schlagen mit 403 fehl.
    if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
    if ( ! defined( 'DONOTCACHEOBJECT' ) ) { define( 'DONOTCACHEOBJECT', true ); }
    if ( function_exists( 'do_action' ) ) { do_action( 'litespeed_control_set_nocache', 'ftipp: personalisierte App-Seite mit Nonce' ); }

    if ( ! is_user_logged_in() ) { wp_die( 'Bitte zuerst einloggen.', 'Nicht angemeldet', array( 'response' => 401 ) ); }

    $user = wp_get_current_user();
    $boot = array(
        'userId'          => $user->ID,
        'userName'        => $user->display_name,
        'isPlatformAdmin' => current_user_can( 'manage_options' ),
        'restUrl'         => esc_url_raw( rest_url( 'ftipp/v1/' ) ),
        'nonce'           => wp_create_nonce( 'wp_rest' ),
        'season'          => intval( get_option( 'ftipp_season', 2026 ) ),
        'fixtures'        => get_option( 'ftipp_fixtures', new stdClass() ),
        'meta'            => get_option( 'ftipp_meta', new stdClass() ),
    );
    $file = plugin_dir_path( __FILE__ ) . 'app/index.html';
    if ( ! file_exists( $file ) ) { wp_die( 'App-Datei fehlt.' ); }
    $html = file_get_contents( $file );
    $inject = '<script>window.FTIPP_BOOT = ' . wp_json_encode( $boot ) . ';</script>';
    $html = preg_replace( '/<head>/', '<head>' . $inject, $html, 1 );

    nocache_headers();
    header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
    header( 'Pragma: no-cache' );
    header( 'Content-Type: text/html; charset=utf-8' );
    echo $html; // phpcs:ignore -- vollständige, selbst erzeugte HTML-Seite
    exit;
} );

/* ============================================================
 * Einstellungsseite (Einstellungen → Tippstube)
 * ============================================================ */
add_action( 'admin_menu', function () {
    add_options_page( 'Tippstube', 'Tippstube', 'manage_options', 'ftipp', 'ftipp_settings_page' );
} );
add_action( 'admin_init', function () {
    register_setting( 'ftipp_group', 'ftipp_api_key', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
    register_setting( 'ftipp_group', 'ftipp_season',  array( 'sanitize_callback' => 'absint', 'default' => 2026 ) );
    register_setting( 'ftipp_group', 'ftipp_notify_reminders', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
    register_setting( 'ftipp_group', 'ftipp_notify_newsletter', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
    register_setting( 'ftipp_group', 'ftipp_newsletter_day',  array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
    register_setting( 'ftipp_group', 'ftipp_newsletter_hour', array( 'sanitize_callback' => 'absint', 'default' => 9 ) );
} );
add_action( 'admin_post_ftipp_fetch', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Keine Berechtigung.' ); }
    check_admin_referer( 'ftipp_fetch' );
    $res = ftipp_fetch_all();
    wp_safe_redirect( add_query_arg( array( 'page' => 'ftipp', 'ftipp_done' => $res['ok'] ? 'ok' : 'err' ), admin_url( 'options-general.php' ) ) );
    exit;
} );
add_action( 'admin_post_ftipp_test_reminder', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Keine Berechtigung.' ); }
    check_admin_referer( 'ftipp_test_reminder' );
    ftipp_run_reminder_check();
    wp_safe_redirect( add_query_arg( array( 'page' => 'ftipp', 'ftipp_test' => 'reminder' ), admin_url( 'options-general.php' ) ) );
    exit;
} );
add_action( 'admin_post_ftipp_test_newsletter', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Keine Berechtigung.' ); }
    check_admin_referer( 'ftipp_test_newsletter' );
    $n = ftipp_run_newsletter_check( true );
    wp_safe_redirect( add_query_arg( array( 'page' => 'ftipp', 'ftipp_test' => 'newsletter', 'n' => $n ), admin_url( 'options-general.php' ) ) );
    exit;
} );

function ftipp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $meta = get_option( 'ftipp_meta', array() );
    ?>
    <div class="wrap">
        <h1>🏠⚽ Tippstube</h1>
        <p><strong>1. &amp; 2. Bundesliga</strong> kommen automatisch über <strong>OpenLigaDB</strong> — gratis, ohne Key,
           immer die <strong>aktuelle Saison</strong>. Für <strong>DFB-Pokal, Champions League, Europa League und
           Nations League</strong> brauchst du zusätzlich einen kostenlosen <strong>API-Football</strong>-Key.
           <strong>Hinweis:</strong> der Gratis-Tarif von API-Football deckt dort nur die Saisons 2021–2023 ab — für
           die aktuelle Saison dieser vier Wettbewerbe ist (noch) ein Bezahltarif nötig, oder du nutzt zum Testen
           „🎲 Test-Spiele laden" weiter unten.</p>

        <?php if ( isset( $_GET['ftipp_done'] ) ) : ?>
            <div class="notice notice-<?php echo ( 'ok' === $_GET['ftipp_done'] ) ? 'success' : 'error'; ?> is-dismissible">
                <p><?php echo ( 'ok' === $_GET['ftipp_done'] ) ? 'Abruf abgeschlossen.' : 'Abruf fehlgeschlagen — bitte API-Key prüfen.'; ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'ftipp_group' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ftipp_api_key">API-Football Key</label></th>
                    <td><input name="ftipp_api_key" id="ftipp_api_key" type="text" class="regular-text"
                               value="<?php echo esc_attr( get_option( 'ftipp_api_key', '' ) ); ?>" autocomplete="off" placeholder="dein Key von api-football.com" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ftipp_season">Saison (DFB-Pokal/CL/EL/Nations League)</label></th>
                    <td><input name="ftipp_season" id="ftipp_season" type="number" value="<?php echo esc_attr( get_option( 'ftipp_season', 2026 ) ); ?>" style="width:110px" />
                        <p class="description">Gilt nur für die vier API-Football-Wettbewerbe. Startjahr der Saison. 2026 = Saison 2026/27.
                        Zum Testen mit vollständigen Ergebnissen: 2023. 1./2. Bundesliga laufen unabhängig davon immer
                        auf der aktuellen Saison via OpenLigaDB.</p></td>
                </tr>
            </table>
            <?php submit_button( 'Speichern' ); ?>
        </form>

        <hr>
        <h2>Jetzt abrufen</h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="ftipp_fetch" />
            <?php wp_nonce_field( 'ftipp_fetch' ); ?>
            <?php submit_button( '⬇️ Spieldaten jetzt abrufen', 'primary', 'submit', false ); ?>
        </form>

        <?php if ( isset( $_GET['ftipp_demo_done'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Test-Spiele geladen — lade die Tippspiel-Seite neu.</p></div>
        <?php endif; ?>
        <h2 style="margin-top:24px">🎲 Test-Spiele laden</h2>
        <p>Die echten Daten oben (Gratis-Tarif) sind immer eine <strong>vergangene</strong> Saison — jedes Spiel ist
           damit sofort gesperrt, es gibt nichts zum Antippen. Zum <strong>Ausprobieren der Tipp-Mechanik</strong>
           (Frist/Sperre, K.o.-Zusatztipp, Tipp-Geheimhaltung) lädt dieser Button ein paar Beispiel-Spiele mit
           Anpfiff in den nächsten Tagen — unabhängig von der API. Überschreibt die aktuell geladenen Spiele;
           mit „Spieldaten jetzt abrufen" oben jederzeit wieder rückgängig zu machen.</p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="ftipp_demo" />
            <?php wp_nonce_field( 'ftipp_demo' ); ?>
            <?php submit_button( '🎲 Test-Spiele laden (zum Ausprobieren)', 'secondary', 'submit', false ); ?>
        </form>

        <?php if ( ! empty( $meta ) && is_array( $meta ) ) : ?>
            <h2>Letzter Abruf</h2>
            <p><strong>Zeitpunkt:</strong> <?php echo isset( $meta['last_fetch'] ) ? esc_html( wp_date( 'd.m.Y H:i', $meta['last_fetch'] ) ) : '—'; ?>
               &nbsp;·&nbsp; <strong>Saison (API-Football):</strong> <?php echo esc_html( isset( $meta['season'] ) ? $meta['season'] : '—' ); ?>
               &nbsp;·&nbsp; <strong>Aktuelle dt. Saison (OpenLigaDB):</strong> <?php echo esc_html( isset( $meta['de_season'] ) ? $meta['de_season'] : '—' ); ?></p>
            <table class="widefat striped" style="max-width:760px">
                <thead><tr><th>Wettbewerb</th><th>Quelle</th><th>Spiele geladen</th><th>Hinweis/Fehler</th></tr></thead>
                <tbody>
                <?php foreach ( ftipp_leagues() as $cid => $lg ) :
                    $c = isset( $meta['counts'][ $cid ] ) ? intval( $meta['counts'][ $cid ] ) : 0;
                    $e = isset( $meta['errors'][ $cid ] ) ? $meta['errors'][ $cid ] : '';
                    $s = isset( $meta['sources'][ $cid ] ) ? $meta['sources'][ $cid ] : '—'; ?>
                    <tr><td><?php echo esc_html( $lg['name'] ); ?></td><td><?php echo esc_html( $s ); ?></td><td><?php echo esc_html( $c ); ?></td><td style="color:#b32d2e"><?php echo esc_html( $e ); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <hr>
        <h2>✉️ Benachrichtigungen</h2>
        <p>Läuft serverseitig über <code>wp_mail()</code> — falls du ein SMTP-Plugin (z.B. "WP Mail SMTP") installiert hast,
           wird das automatisch für den Versand genutzt.</p>

        <?php if ( isset( $_GET['ftipp_test'] ) ) : ?>
            <div class="notice notice-info is-dismissible">
                <p><?php echo ( 'reminder' === $_GET['ftipp_test'] )
                    ? 'Fristen-Prüfung ausgeführt. Fällige Erinnerungen (falls welche anstanden) wurden verschickt.'
                    : 'Newsletter-Test ausgeführt. Versendete Mails: ' . intval( $_GET['n'] ?? 0 ); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'ftipp_group' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Fristen-Erinnerung</th>
                    <td>
                        <label><input type="checkbox" name="ftipp_notify_reminders" value="1" <?php checked( get_option( 'ftipp_notify_reminders', 1 ) ); ?> />
                        Mail an Mitspieler, wenn Spiele in den nächsten 3 Stunden anpfeifen und sie noch nicht getippt haben.</label>
                        <p class="description">Prüfung läuft automatisch alle 15 Minuten. Pro Spiel &amp; Nutzer nur eine Erinnerung.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ranking-Newsletter</th>
                    <td>
                        <label><input type="checkbox" name="ftipp_notify_newsletter" value="1" <?php checked( get_option( 'ftipp_notify_newsletter', 1 ) ); ?> />
                        Wöchentliche Mail an jede Runde/jeden Wettbewerb mit Top 3 + eigener Platzierung.</label>
                        <p class="description">
                            Versand am
                            <select name="ftipp_newsletter_day">
                                <?php $days = array( 1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 0 => 'Sonntag' );
                                $curDay = intval( get_option( 'ftipp_newsletter_day', 1 ) );
                                foreach ( $days as $v => $label ) : ?>
                                    <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $curDay, $v ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            um
                            <select name="ftipp_newsletter_hour">
                                <?php $curHour = intval( get_option( 'ftipp_newsletter_hour', 9 ) );
                                for ( $h = 0; $h < 24; $h++ ) : ?>
                                    <option value="<?php echo esc_attr( $h ); ?>" <?php selected( $curHour, $h ); ?>><?php echo esc_html( sprintf( '%02d:00', $h ) ); ?></option>
                                <?php endfor; ?>
                            </select>
                            Uhr (Site-Zeitzone).
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Speichern' ); ?>
        </form>

        <div class="row" style="display:flex;gap:10px;margin-top:6px">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="ftipp_test_reminder" />
                <?php wp_nonce_field( 'ftipp_test_reminder' ); ?>
                <?php submit_button( '📩 Fristen-Prüfung jetzt ausführen', 'secondary', 'submit', false ); ?>
            </form>
            &nbsp;
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="ftipp_test_newsletter" />
                <?php wp_nonce_field( 'ftipp_test_newsletter' ); ?>
                <?php submit_button( '🏆 Newsletter jetzt testweise verschicken', 'secondary', 'submit', false ); ?>
            </form>
        </div>
        <p class="description">Der Test-Newsletter ignoriert Wochentag/Uhrzeit/"schon diese Woche verschickt" und
           mailt sofort an alle betroffenen Mitspieler — nur zum Prüfen der Zustellung nutzen.</p>

        <hr>
        <h2>👥 Registrierung für Mitspieler</h2>
        <?php if ( get_option( 'users_can_register' ) ) : ?>
            <p style="color:#177a4a">✅ Neue Nutzer können sich selbst registrieren (Einstellungen → Allgemein → „Jedermann kann sich registrieren").</p>
        <?php else : ?>
            <p style="color:#b32d2e">⚠️ Registrierung ist aktuell <strong>deaktiviert</strong>. Aktiviere sie unter
               <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">Einstellungen → Allgemein</a> bei „Mitgliedschaft",
               sonst können sich neue Mitspieler nicht selbst anmelden.</p>
        <?php endif; ?>
    </div>
    <?php
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
    $links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=ftipp' ) ) . '">Einstellungen</a>';
    return $links;
} );

/* ============================================================
 * Login-/Registrierungs-Seite (wp-login.php) im App-Look
 * ============================================================ */
add_action( 'login_enqueue_scripts', function () {
    echo '<link rel="icon" href="' . esc_attr( ftipp_favicon_href() ) . '" type="image/svg+xml">' . "\n";
    ?>
    <style>
        body.login{ background:#0b160f; font-family:-apple-system,system-ui,"Segoe UI",Roboto,Arial,sans-serif; }
        body.login #login{ width:100%; max-width:360px; padding-top:6vh; }
        body.login h1{ text-align:center; margin-bottom:18px; }
        body.login h1 a{
            background-image:none !important; width:auto; height:auto; margin:0 auto;
            display:block; text-align:center; text-indent:0; font-size:0;
        }
        body.login h1 a::before{
            content:"🏠⚽ Tippstube"; font-size:26px; font-weight:700; color:#f3ead9; letter-spacing:-.01em;
            font-family:Georgia,'Iowan Old Style','Palatino Linotype',serif;
        }
        body.login #loginform, body.login #registerform, body.login #lostpasswordform{
            background:#122417; border:1px solid #2a4a30; border-radius:14px;
            box-shadow:0 1px 2px rgba(0,0,0,.25), 0 10px 26px -16px rgba(0,0,0,.55);
            padding:26px 24px;
        }
        body.login #loginform p, body.login #registerform p, body.login #lostpasswordform p{ color:#cddccb; }
        body.login label{ color:#a7b89f !important; font-size:12.5px; font-weight:600; }
        body.login input[type=text], body.login input[type=password], body.login input[type=email]{
            background:#0d190f !important; border:1px solid #2a4a30 !important; color:#f3ead9 !important;
            border-radius:8px !important; box-shadow:none !important; padding:10px 11px !important; font-size:16px !important;
        }
        body.login input[type=text]:focus, body.login input[type=password]:focus, body.login input[type=email]:focus{
            border-color:#d9a441 !important; box-shadow:0 0 0 3px rgba(217,164,65,.2) !important;
        }
        body.login input[type=checkbox]{ accent-color:#d9a441; }
        body.login #registerform a, body.login #loginform a, body.login #lostpasswordform a{ color:#7cc98a; }
        body.login .button.button-primary{
            background:#d9a441 !important; border-color:#d9a441 !important; color:#241a05 !important;
            text-shadow:none !important; box-shadow:none !important; border-radius:9px !important;
            font-weight:700 !important; padding:6px 18px !important; height:auto !important;
        }
        body.login .button.button-primary:hover{ filter:brightness(1.07); }
        body.login form .forgetmenot label{ color:#a7b89f !important; font-weight:400; }
        body.login #nav, body.login #backtoblog{ text-align:center; }
        body.login #nav a, body.login #backtoblog a{ color:#7cc98a !important; text-decoration:none; }
        body.login #nav a:hover, body.login #backtoblog a:hover{ text-decoration:underline; }
        body.login .privacy-policy-page-link{ text-align:center; }
        body.login .message, body.login #login_error{
            background:#16301c !important; border-left-color:#d9a441 !important; color:#e9f5df !important;
            border-radius:8px; box-shadow:none !important;
        }
    </style>
    <?php
} );
add_filter( 'login_headertext', function () { return 'Tippstube'; } );

/**
 * Nach dem Login direkt zur Tippstube statt ins WordPress-Backend — die Seite dient praktisch
 * ausschließlich der Tippstube, niemand soll erst im wp-admin landen. Ein explizit angefordertes
 * Redirect-Ziel (z.B. vom In-App-Login-Formular, das schon selbst zur aktuellen Seite zurückführt)
 * hat weiterhin Vorrang.
 */
add_filter( 'login_redirect', function ( $redirect_to, $requested_redirect_to, $user ) {
    if ( $requested_redirect_to ) { return $requested_redirect_to; }
    if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) { return $redirect_to; }
    return ftipp_app_url();
}, 10, 3 );

/* ============================================================
 * DSGVO: Einwilligung bei Registrierung + Datenschutz-Textbaustein
 * ============================================================ */
add_action( 'register_form', function () {
    $checked = isset( $_POST['ftipp_consent'] ) ? ' checked' : ''; // phpcs:ignore -- nur zur Wiederherstellung des Häkchens nach Formularfehler
    ?>
    <p>
        <label style="display:flex;align-items:flex-start;gap:8px;font-weight:400">
            <input type="checkbox" name="ftipp_consent" value="1"<?php echo $checked; ?> style="margin-top:3px" />
            <span>Ich habe die <a href="<?php echo esc_url( home_url( '/datenschutz/' ) ); ?>" target="_blank">Datenschutzerklärung</a>
            gelesen und bin einverstanden, dass meine Tipps, mein Anzeigename und meine Pinnwand-Nachrichten
            innerhalb meiner Tipprunden sichtbar sind.</span>
        </label>
    </p>
    <?php
} );
add_filter( 'registration_errors', function ( $errors, $sanitized_user_login, $user_email ) {
    if ( empty( $_POST['ftipp_consent'] ) ) { // phpcs:ignore -- reine Pflichtfeld-Prüfung, kein Datenzugriff
        $errors->add( 'ftipp_consent', '<strong>Fehler</strong>: Bitte bestätige die Datenschutzerklärung, um dich zu registrieren.' );
    }
    return $errors;
}, 10, 3 );

/**
 * Shortcode [tippspiel_datenschutz] — Textbaustein für die Datenschutzerklärung.
 * WICHTIG: Platzhalter in [ECKIGEN KLAMMERN] müssen vom Website-Betreiber ausgefüllt
 * werden (Name/Firma, Anschrift, Kontakt) — dieser Text ist bewusst ein Gerüst, kein
 * fertiges, rechtsgültiges Dokument, und keine Rechtsberatung.
 */
add_shortcode( 'tippspiel_datenschutz', function () {
    ob_start(); ?>
    <div style="max-width:720px;margin:0 auto;line-height:1.6">
        <h2>Datenschutzerklärung — Tippstube</h2>
        <p><em>Hinweis für den Betreiber: Dies ist ein Textbaustein, kein fertiger Rechtstext. Bitte die
        Platzhalter in [eckigen Klammern] ausfüllen bzw. mit einem Anwalt/Generator prüfen.</em></p>

        <h3>Verantwortlicher</h3>
        <p>[DEIN NAME / DEINE FIRMA]<br>[ANSCHRIFT]<br>[E-MAIL-ADRESSE]</p>

        <h3>Welche Daten wir verarbeiten</h3>
        <ul>
            <li><strong>Kontodaten:</strong> E-Mail-Adresse (bleibt privat, nur für Login/Zustellung), Anzeigename (sichtbar für Mitspieler deiner Runden), Passwort (verschlüsselt gespeichert, Standard-WordPress-Verfahren).</li>
            <li><strong>Spieldaten:</strong> deine Tipps, Wettbewerbs-Anmeldungen, Tipprunden-Mitgliedschaften.</li>
            <li><strong>Pinnwand-Nachrichten:</strong> Texte, die du in einer Tipprunde schreibst — sichtbar für die Mitglieder dieser Runde.</li>
        </ul>

        <h3>Zweck & Sichtbarkeit</h3>
        <p>Diese Daten dienen ausschließlich dem Betrieb des Tippspiels. Tipps, Anzeigename und Pinnwand-Nachrichten
        sind für die Mitglieder deiner jeweiligen Tipprunde(n) sichtbar. Deine E-Mail-Adresse ist niemals für andere Mitspieler sichtbar.</p>

        <h3>E-Mail-Benachrichtigungen</h3>
        <p>Sofern aktiviert, erhältst du Fristen-Erinnerungen und/oder einen Ranking-Newsletter per E-Mail. Der Versand
        erfolgt über [SMTP-ANBIETER EINTRAGEN, z.B. dein E-Mail-Provider].</p>

        <h3>Speicherdauer</h3>
        <p>Deine Daten bleiben gespeichert, solange dein Konto besteht. Du kannst deine Tippspiel-Daten jederzeit
        selbst in der App unter „Mein Konto" exportieren oder löschen lassen.</p>

        <h3>Deine Rechte</h3>
        <p>Du hast das Recht auf Auskunft, Berichtigung, Löschung und Datenübertragbarkeit. Kontaktiere uns dazu unter
        [E-MAIL-ADRESSE], oder nutze die Selbstbedienungs-Funktionen in der App.</p>

        <h3>Hosting</h3>
        <p>Diese Website wird gehostet bei [HOSTING-ANBIETER EINTRAGEN].</p>
    </div>
    <?php
    return ob_get_clean();
} );
