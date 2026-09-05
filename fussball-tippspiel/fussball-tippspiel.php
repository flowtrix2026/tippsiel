<?php
/**
 * Plugin Name:       Tippstube
 * Description:       Tippstube — das private Fußball-Tippspiel für deine Tipprunde. Echtes WordPress-Login, Tipprunden, Statistik/Achievements, Pinnwand-Chat pro Runde. Spieldaten: 1./2./3. Liga + DFB-Pokal + Champions/Europa League + Premier League + LaLiga + Frauen-Bundesliga + Regionalliga Nordost via OpenLigaDB (aktuelle Saison, gratis), Nations League + Süper Lig + Serie A + Ligue 1 + Ekstraklasa per CSV-Import oder API-Football.
 * Version:           0.8.4
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Florian Henschke
 * License:           GPL-2.0-or-later
 * Text Domain:       fussball-tippspiel
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'FTIPP_VERSION', '0.8.4' );
define( 'FTIPP_DB_VERSION', '10' );

/**
 * Automatische Update-Prüfung gegen GitHub-Releases (statt WordPress.org-Verzeichnis) —
 * damit "Update verfügbar" im Plugins-Bereich erscheint, muss künftig zu jeder neuen Version
 * ein echtes GitHub-Release (mit Versions-Tag) angelegt und die gebaute Zip als Release-Asset
 * angehängt werden, nicht nur ein normaler Push nach main.
 */
require_once plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';
if ( class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
    $ftipp_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/flowtrix2026/tippsiel/',
        __FILE__,
        'fussball-tippspiel'
    );
    $ftipp_update_checker->getVcsApi()->enableReleaseAssets( '/\.zip($|[?&#])/i' );
}

/** Wettbewerbe: interne ID => [Name, API-Football Liga-ID, Art] */
function ftipp_leagues() {
    return array(
        'BL1' => array( 'name' => '1. Bundesliga',     'api' => 78,  'kind' => 'league' ),
        'BL2' => array( 'name' => '2. Bundesliga',     'api' => 79,  'kind' => 'league' ),
        'BL3' => array( 'name' => '3. Liga',           'api' => 80,  'kind' => 'league' ),
        'DFB' => array( 'name' => 'DFB-Pokal',         'api' => 81,  'kind' => 'cup' ),
        'CL'  => array( 'name' => 'Champions League',  'api' => 2,   'kind' => 'cup' ),
        'EL'  => array( 'name' => 'Europa League',     'api' => 3,   'kind' => 'cup' ),
        'NL'  => array( 'name' => 'Nations League',    'api' => 5,   'kind' => 'cup' ),
        'PL'  => array( 'name' => 'Premier League',    'api' => 39,  'kind' => 'league' ),
        'LA1' => array( 'name' => 'LaLiga',            'api' => 140, 'kind' => 'league' ),
        'TR1' => array( 'name' => 'Süper Lig',         'api' => 203, 'kind' => 'league' ),
        'FBL' => array( 'name' => 'Frauen-Bundesliga', 'api' => 0,   'kind' => 'league' ),
        'RLNO' => array( 'name' => 'Regionalliga Nordost', 'api' => 0, 'kind' => 'league' ),
        'ITA1' => array( 'name' => 'Serie A',          'api' => 135, 'kind' => 'league' ),
        'FRA1' => array( 'name' => 'Ligue 1',          'api' => 61,  'kind' => 'league' ),
        'POL1' => array( 'name' => 'Ekstraklasa',      'api' => 106, 'kind' => 'league' ),
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

    // Ab v0.8.14: Wettbewerbs-Abo ist pro Runde statt global (ftipp_subs bleibt bestehen, wird aber nicht
    // mehr aktiv genutzt — nur noch als Migrationsquelle beim Upgrade, siehe ftipp_migrate_round_subs()).
    dbDelta( "CREATE TABLE {$p}ftipp_round_subs (
        round_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        comp_id VARCHAR(10) NOT NULL,
        active TINYINT NOT NULL DEFAULT 0,
        PRIMARY KEY  (round_id,user_id,comp_id)
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
        override TINYINT NULL,
        committed TINYINT NOT NULL DEFAULT 0,
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
    ftipp_migrate_round_subs();
    ftipp_reset_nl_group_specials();
}

/**
 * Einmalige Bereinigung (v0.9.7): Die erste Migration (v0.9.6) versuchte, bestehende "Gruppensieger"-
 * Sonderwertungen anhand des in der alten Bezeichnung eingebetteten Team-Textes umzubenennen. Das ging bei
 * mindestens einer Gruppe schief (Team-Erkennung durch Vermischung mit den Demo-Test-Spielen verfälscht, siehe
 * ftipp_nl_groups()-Fix) — Ergebnis waren doppelte/falsch benannte Gruppen (z.B. zweimal "A1" statt einmal
 * "A1" und einmal "A2"). Statt die fehlerhaften Bezeichnungen weiter zu reparieren, werden alle bisherigen
 * NL-"Gruppensieger"-Sonderwertungen (samt bereits abgegebener Tipps dazu) einmalig gelöscht und beim nächsten
 * Öffnen des Sonderwertungen-Tabs sauber neu aus den jetzt korrekt erkannten Gruppen angelegt. Betrifft nur
 * die 14 Gruppensieger-Wetten — "Nations-League-Sieger" und "Torschützenkönig" bleiben unangetastet.
 */
function ftipp_reset_nl_group_specials() {
    if ( '1' === get_option( 'ftipp_nl_groups_reset_v1' ) ) { return; }
    global $wpdb;
    $specialTable = "{$wpdb->prefix}ftipp_special";
    $tipsTable = "{$wpdb->prefix}ftipp_special_tips";
    $rows = $wpdb->get_results( "SELECT id, round_id FROM {$specialTable} WHERE comp_id='NL' AND label LIKE 'Gruppensieger %'", ARRAY_A );
    $roundIds = array();
    foreach ( $rows as $r ) {
        $wpdb->delete( $tipsTable, array( 'special_id' => $r['id'] ) );
        $wpdb->delete( $specialTable, array( 'id' => $r['id'] ) );
        $roundIds[ $r['round_id'] ] = true;
    }
    // "nlgrp_"-Keys aus seeded_special_keys entfernen, damit ftipp_seed_default_specials() beim nächsten
    // Aufruf denkt, diese Gruppen seien noch nie vergeben worden, und sie sauber neu anlegt.
    foreach ( array_keys( $roundIds ) as $rid ) {
        $cfgRow = $wpdb->get_row( $wpdb->prepare(
            "SELECT seeded_special_keys FROM {$wpdb->prefix}ftipp_round_config WHERE round_id=%d AND comp_id='NL'", $rid
        ), ARRAY_A );
        if ( ! $cfgRow ) { continue; }
        $keys = array_filter( explode( ',', (string) $cfgRow['seeded_special_keys'] ) );
        $keys = array_values( array_filter( $keys, function ( $k ) { return 0 !== strpos( $k, 'nlgrp_' ); } ) );
        $wpdb->update( "{$wpdb->prefix}ftipp_round_config", array( 'seeded_special_keys' => implode( ',', $keys ) ), array( 'round_id' => $rid, 'comp_id' => 'NL' ) );
    }
    update_option( 'ftipp_nl_groups_reset_v1', '1' );
}

/**
 * Einmalige Migration beim Umstieg von globalem auf Pro-Runde-Wettbewerbs-Abo (v0.8.14): für jede bestehende
 * Rundenmitgliedschaft werden die bisherigen globalen Abos (ftipp_subs) als Startwert in die neue
 * ftipp_round_subs übernommen — damit niemandem beim Update plötzlich Wettbewerbe aus seinen Runden
 * verschwinden. Läuft nur einmal (Flag in wp_options), danach ist jede Runde unabhängig anpassbar.
 */
function ftipp_migrate_round_subs() {
    if ( '1' === get_option( 'ftipp_round_subs_migrated' ) ) { return; }
    global $wpdb;
    $memberships = $wpdb->get_results( "SELECT round_id, user_id FROM {$wpdb->prefix}ftipp_round_members", ARRAY_A );
    foreach ( $memberships as $rm ) {
        $subs = $wpdb->get_results( $wpdb->prepare(
            "SELECT comp_id, active FROM {$wpdb->prefix}ftipp_subs WHERE user_id=%d", $rm['user_id']
        ), ARRAY_A );
        foreach ( $subs as $s ) {
            if ( ! intval( $s['active'] ) ) { continue; }
            $wpdb->replace( "{$wpdb->prefix}ftipp_round_subs", array(
                'round_id' => $rm['round_id'], 'user_id' => $rm['user_id'], 'comp_id' => $s['comp_id'], 'active' => 1,
            ) );
        }
    }
    update_option( 'ftipp_round_subs_migrated', '1' );
}
add_filter( 'cron_schedules', function ( $s ) {
    $s['ftipp_15min'] = array( 'interval' => 15 * 60, 'display' => 'Alle 15 Minuten (Tippstube)' );
    return $s;
} );
/**
 * Frei einstellbares Intervall für den automatischen Spieldaten-Abruf (Cron-Job-Seite, v0.8.2):
 * ein einziger, immer gleich benannter Schedule-Key liest live aus der Option — kein Intervall
 * pro gewähltem Wert nötig. Untergrenze 15 Minuten gegen versehentliches Dauerfeuer auf die APIs.
 */
add_filter( 'cron_schedules', function ( $s ) {
    $seconds = max( 900, intval( get_option( 'ftipp_cron_interval_seconds', WEEK_IN_SECONDS ) ) );
    $s['ftipp_fetch_custom'] = array( 'interval' => $seconds, 'display' => 'Tippstube: benutzerdefiniert' );
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
    if ( ! wp_next_scheduled( 'ftipp_weekly_fetch' ) ) { wp_schedule_event( time() + 60, 'weekly', 'ftipp_weekly_fetch' ); }
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
 *   1./2./3. Liga + DFB-Pokal + Champions League + Europa League + Premier League + LaLiga -> OpenLigaDB (aktuelle Saison, gratis, ohne Key)
 *   Nations League + Süper Lig -> CSV-Import (siehe ftipp_import_fixtures_csv()) — für beide gibt es keine
 *   zuverlässige kostenlose Automatik-Quelle (OpenLigaDB bei Süper Lig seit 2013/2014 veraltet; ESPN wurde
 *   live getestet, wird aber von manchen Servern per PHP-Anfrage geblockt, siehe ftipp_fetch_espn_soccer())
 * DFB-Pokal (v0.8.17) und Champions/Europa League (v0.8.22) bewusst auf OpenLigaDB umgestellt
 * (Nutzerentscheidung): einzige Quelle, die diese Wettbewerbe in der AKTUELLEN Saison kostenlos
 * abdeckt. Nachteil bekannt und akzeptiert: OpenLigaDB kennzeichnet "nach Verlängerung/
 * Elfmeterschießen" bei K.o.-Spielen nicht immer zuverlässig (stichprobenartig geprüft) — deshalb
 * bekommen diese drei Wettbewerbe keinen K.o.-Zusatztipp mehr (siehe ftipp_fetch_openligadb():
 * Score-Extraktion bevorzugt "nach 90 Minuten", um wenigstens den normalen Tendenz/Exakt-Tipp so
 * korrekt wie möglich zu halten). Nations League bleibt bei API-Football — dafür gibt es auf
 * OpenLigaDB keine brauchbare, vollständige Datenquelle (separat recherchiert, siehe Journal).
 * Achtung: CL/EL-Shortcuts bei OpenLigaDB sind saisonabhängig instabil benannt (siehe
 * ftipp_openligadb_shortcut()) — ggf. jede Saison neu prüfen, ob der Abruf noch Daten liefert.
 * ============================================================ */
function ftipp_current_de_season() {
    $m = intval( gmdate( 'n' ) ); $y = intval( gmdate( 'Y' ) );
    return ( $m >= 7 ) ? $y : ( $y - 1 );
}
function ftipp_openligadb_shortcut( $comp_id ) {
    // Achtung: bei BL1/BL2/BL3/DFB/PL/LA1 ist der Shortcut über die Jahre stabil (nur die Saisonzahl in der
    // URL ändert sich). Bei Champions/Europa League hat die Community den Shortcut in der Vergangenheit fast
    // jede Saison umbenannt (z.B. CL: cl -> cl1011 -> ucl2014 -> ucl2024 -> ucl) — die Werte hier sind Stand
    // 2026/27 und müssen ggf. zur nächsten Saison manuell geprüft/aktualisiert werden (siehe
    // api.openligadb.de/getavailableleagues).
    $map = array(
        'BL1' => 'bl1', 'BL2' => 'bl2', 'BL3' => 'bl3', 'DFB' => 'dfb', 'CL' => 'ucl', 'EL' => 'uel2026',
        'PL' => 'pl', 'LA1' => 'la1', 'FBL' => 'ffb1', 'RLNO' => 'rlno',
    );
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

/** OpenLigaDB-Abruf für eine deutsche Liga/den DFB-Pokal (bl1/bl2/dfb), gratis, ohne Auth. */
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
        $hg = null; $ag = null; $hg90 = null; $ag90 = null;
        if ( ! empty( $m['matchResults'] ) && is_array( $m['matchResults'] ) ) {
            foreach ( $m['matchResults'] as $r ) {
                if ( 2 === intval( $r['resultTypeID'] ) ) { $hg = intval( $r['pointsTeam1'] ); $ag = intval( $r['pointsTeam2'] ); }
                // resultTypeID 3 = "nach 90 Minuten" — bei K.o.-Spielen (DFB-Pokal), die in Verlängerung/
                // Elfmeterschießen gehen, ist NUR das der korrekte Stand für den normalen Tendenz/Exakt-Tipp;
                // "Endergebnis" (Typ 2) würde dort fälschlich den Elfmeterschießen-Stand mitzählen. Bei
                // normalen Ligaspielen (BL1/BL2) gibt es diesen Fall nicht, dort bleibt Typ 2 maßgeblich.
                if ( 3 === intval( $r['resultTypeID'] ) ) { $hg90 = intval( $r['pointsTeam1'] ); $ag90 = intval( $r['pointsTeam2'] ); }
            }
        }
        if ( null !== $hg90 ) { $hg = $hg90; $ag = $ag90; }
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

/**
 * Wettbewerbe, für die es keine brauchbare OpenLigaDB-Quelle gibt, aber die kostenlose (inoffizielle)
 * ESPN-API aktuelle Daten liefert (Stand 2026/27 live geprüft: echte, laufende Saison, komplette Spielliste).
 * ESPN-Slug-Format: "{land}.{liga}", z.B. "tur.1" für die türkische Süper Lig.
 */
/**
 * Auf dem echten Server live getestet (v0.12.0): ESPNs Bot-Schutz blockt Anfragen, die aus PHP kommen, mit
 * HTTP 403 — auf diesem Hosting funktioniert der automatische Abruf also nicht. Süper Lig läuft deshalb ab
 * v1.0.1 wie die Nations League per CSV-Import (siehe ftipp_import_fixtures_csv()). Diese Funktion bleibt
 * absichtlich als leere Zuordnung stehen (statt komplett gelöscht) — falls ESPN auf einem anderen Server
 * (andere PHP/TLS-Konfiguration) doch funktioniert, reicht ein Eintrag hier, um es erneut zu versuchen.
 */
function ftipp_espn_soccer_map() {
    return array();
}

/**
 * Erzwingt HTTP/1.1 für Anfragen an ESPN — PHPs curl-Modul wird von Akamai (ESPNs CDN) mit HTTP 403
 * geblockt, wenn es HTTP/2 verhandelt (Terminal-curl ist davon nicht betroffen, nutzt eine andere
 * TLS/HTTP2-Implementierung). Läuft nur für ESPN-URLs, alle anderen wp_remote_get()-Aufrufe unangetastet.
 */
add_filter( 'http_api_curl', function ( $handle, $parsed_args, $url ) {
    if ( false !== strpos( $url, 'site.api.espn.com' ) && defined( 'CURL_HTTP_VERSION_1_1' ) ) {
        curl_setopt( $handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
    }
}, 10, 3 );

/**
 * ESPN liefert bei Liga-Spielen keine Spieltag-Nummer — wird hier selbst aus dem Kalender abgeleitet:
 * chronologisch sortieren und in Blöcken von (Team-Anzahl / 2) Spielen gruppieren (ein normaler Spieltag
 * bei n Teams hat genau n/2 Partien). Live gegen echte Süper-Liga-Daten geprüft: ergibt saubere, durch
 * mehrere Tage getrennte Blöcke, passt zur echten Spieltag-Einteilung.
 */
function ftipp_fetch_espn_soccer( $slug, $season_start_year ) {
    $from = $season_start_year . '0701';
    $to   = ( $season_start_year + 1 ) . '0630';
    $url  = "https://site.api.espn.com/apis/site/v2/sports/soccer/{$slug}/scoreboard?dates={$from}-{$to}&limit=500";
    // ESPNs inoffizielle API läuft hinter Akamai — von der Kommandozeile (curl) aus klappt ein normaler
    // Aufruf problemlos, aber PHPs curl-Modul verhandelt HTTP/2 anders und wird dabei mit HTTP 403 "Access
    // Denied" geblockt (live getestet und verglichen). Fix: für genau diese Anfrage HTTP/1.1 erzwingen
    // (siehe ftipp_force_http11_for_espn() weiter unten) plus ein normaler Browser-User-Agent.
    $resp = wp_remote_get( $url, array(
        'timeout' => 25,
        'headers' => array( 'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36' ),
    ) );
    if ( is_wp_error( $resp ) ) { return array( 'ok' => false, 'error' => $resp->get_error_message(), 'fixtures' => array() ); }
    $code = (int) wp_remote_retrieve_response_code( $resp );
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( 200 !== $code || ! isset( $body['events'] ) || ! is_array( $body['events'] ) ) {
        return array( 'ok' => false, 'error' => 'HTTP ' . $code, 'fixtures' => array() );
    }

    $events = $body['events'];
    usort( $events, function ( $a, $b ) { return strcmp( $a['date'], $b['date'] ); } );

    $teamIds = array();
    foreach ( $events as $e ) {
        $comp = isset( $e['competitions'][0] ) ? $e['competitions'][0] : null;
        if ( ! $comp ) { continue; }
        foreach ( $comp['competitors'] as $c ) { $teamIds[ $c['team']['id'] ] = true; }
    }
    $perRound = count( $teamIds ) >= 2 ? intval( count( $teamIds ) / 2 ) : 1;

    $fx = array();
    foreach ( $events as $i => $e ) {
        $comp = isset( $e['competitions'][0] ) ? $e['competitions'][0] : null;
        if ( ! $comp || empty( $comp['competitors'] ) ) { continue; }
        $home = null; $away = null;
        foreach ( $comp['competitors'] as $c ) {
            if ( 'home' === $c['homeAway'] ) { $home = $c; } else { $away = $c; }
        }
        if ( ! $home || ! $away ) { continue; }
        $finished = ! empty( $comp['status']['type']['completed'] );
        // ESPN liefert UTC — in die Standort-Zeitzone von WordPress umrechnen, damit Anpfiffzeiten korrekt
        // angezeigt werden (sonst z.B. 2 Stunden daneben, siehe DFB-Pokal-Lehre aus v0.8.17ff.).
        $ts = strtotime( $e['date'] );
        $date = $ts ? get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $ts ), 'Y-m-d\TH:i' ) : '';
        $fx[] = array(
            'id'     => 'espn-' . $e['id'],
            'round'  => 'Spieltag ' . ( intval( $i / $perRound ) + 1 ),
            'date'   => $date,
            'home'   => $home['team']['displayName'],
            'away'   => $away['team']['displayName'],
            'hg'     => $finished ? intval( $home['score'] ) : null,
            'ag'     => $finished ? intval( $away['score'] ) : null,
            'status' => $finished ? 'FT' : 'NS',
            'ko'     => false, 'decided' => null, 'winner' => null,
        );
    }
    return array( 'ok' => true, 'error' => '', 'fixtures' => $fx );
}

function ftipp_fetch_all() {
    $key       = trim( (string) get_option( 'ftipp_api_key', '' ) );
    $season    = intval( get_option( 'ftipp_season', 2026 ) );
    $de_season = ftipp_current_de_season();

    $all = array(); $counts = array(); $errors = array(); $sources = array();

    // 1) Deutsche Ligen + DFB-Pokal + Champions/Europa League + Premier League/LaLiga zuerst über OpenLigaDB
    //    (aktuelle Saison, gratis).
    foreach ( array( 'BL1', 'BL2', 'BL3', 'DFB', 'CL', 'EL', 'PL', 'LA1', 'FBL', 'RLNO' ) as $cid ) {
        $r = ftipp_fetch_openligadb( ftipp_openligadb_shortcut( $cid ), $de_season );
        if ( $r['ok'] && count( $r['fixtures'] ) > 0 ) {
            $all[ $cid ] = $r['fixtures']; $counts[ $cid ] = count( $r['fixtures'] );
            $errors[ $cid ] = ''; $sources[ $cid ] = 'OpenLigaDB (aktuelle Saison)';
        } else {
            $errors[ $cid ] = $r['ok'] ? 'OpenLigaDB: keine Daten' : ( 'OpenLigaDB: ' . $r['error'] );
        }
    }

    // 1b) Wettbewerbe ohne brauchbare OpenLigaDB-Quelle, aber mit aktueller ESPN-Abdeckung (aktuell:
    //     Süper Lig — bei OpenLigaDB seit 2013/2014 keine aktuellen Daten mehr, siehe Journal).
    foreach ( ftipp_espn_soccer_map() as $cid => $slug ) {
        $r = ftipp_fetch_espn_soccer( $slug, $de_season );
        if ( $r['ok'] && count( $r['fixtures'] ) > 0 ) {
            $all[ $cid ] = $r['fixtures']; $counts[ $cid ] = count( $r['fixtures'] );
            $errors[ $cid ] = ''; $sources[ $cid ] = 'ESPN (aktuelle Saison)';
        } else {
            $errors[ $cid ] = $r['ok'] ? 'ESPN: keine Daten' : ( 'ESPN: ' . $r['error'] );
        }
    }

    // 2) Alles, was noch offen ist (NL immer, außerdem Fallback falls OpenLigaDB/ESPN für einen der
    //    obigen Wettbewerbe mal ausfällt/leer ist) über API-Football.
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
    wp_safe_redirect( add_query_arg( array( 'page' => 'ftipp', 'ftipp_demo_done' => '1' ), admin_url( 'admin.php' ) ) );
    exit;
} );

/**
 * Manueller Spieldaten-Import per CSV — für Wettbewerbe ohne gute kostenlose API (aktuell: Nations League).
 * Spalten: Wettbewerb,Spieltag,Datum,Heim,Auswaerts,ToreHeim,ToreAusw (ToreHeim/ToreAusw leer = noch nicht
 * gespielt). Ergänzt/aktualisiert nur — überschreibt nie den ganzen Wettbewerb, landet in einem eigenen
 * Speicher (ftipp_manual_fixtures), damit der automatische Abruf das nicht wieder wegputzt (siehe
 * ftipp_fixtures_for()). Dieselbe reale Begegnung bekommt über eine aus Wettbewerb+Teams+Datum berechnete
 * ID immer denselben Eintrag — erneuter Upload (z.B. mit jetzt bekanntem Ergebnis) aktualisiert statt
 * zu duplizieren, damit Tipps nicht wie beim DFB-Pokal-Vorfall verwaisen.
 */
function ftipp_import_fixtures_csv( $tmp_path ) {
    $handle = @fopen( $tmp_path, 'r' );
    if ( ! $handle ) { return array( 'added' => 0, 'updated' => 0, 'skipped' => 0, 'error' => 'Datei konnte nicht gelesen werden.' ); }

    $header = fgetcsv( $handle );
    if ( ! $header ) { fclose( $handle ); return array( 'added' => 0, 'updated' => 0, 'skipped' => 0, 'error' => 'Datei ist leer.' ); }
    $map = array_flip( array_map( function ( $h ) { return strtolower( trim( (string) $h, "\xEF\xBB\xBF \t" ) ); }, $header ) );
    foreach ( array( 'wettbewerb', 'spieltag', 'datum', 'heim', 'auswaerts' ) as $col ) {
        if ( ! isset( $map[ $col ] ) ) {
            fclose( $handle );
            return array( 'added' => 0, 'updated' => 0, 'skipped' => 0, 'error' => "Spalte \"$col\" fehlt in der Kopfzeile." );
        }
    }

    $manual = get_option( 'ftipp_manual_fixtures', array() );
    $added = 0; $updated = 0; $skipped = 0;

    while ( ( $row = fgetcsv( $handle ) ) !== false ) {
        if ( 1 === count( $row ) && null === $row[0] ) { continue; } // leere Zeile
        $get = function ( $col ) use ( $map, $row ) { return isset( $map[ $col ], $row[ $map[ $col ] ] ) ? trim( (string) $row[ $map[ $col ] ] ) : ''; };

        $comp = strtoupper( $get( 'wettbewerb' ) );
        $round = $get( 'spieltag' );
        $home = $get( 'heim' );
        $away = $get( 'auswaerts' );
        $dateRaw = $get( 'datum' );

        if ( ! in_array( $comp, ftipp_comp_ids(), true ) || '' === $home || '' === $away || '' === $dateRaw ) { $skipped++; continue; }
        $ts = strtotime( $dateRaw );
        if ( ! $ts ) { $skipped++; continue; }

        $hgRaw = $get( 'toreheim' ); $agRaw = $get( 'toreausw' );
        $hg = ( '' !== $hgRaw && is_numeric( $hgRaw ) ) ? max( 0, intval( $hgRaw ) ) : null;
        $ag = ( '' !== $agRaw && is_numeric( $agRaw ) ) ? max( 0, intval( $agRaw ) ) : null;

        $key = $comp . '|' . mb_strtolower( $home ) . '|' . mb_strtolower( $away ) . '|' . gmdate( 'Y-m-d', $ts );
        $fid = 'csv-' . substr( md5( $key ), 0, 16 );

        if ( ! isset( $manual[ $comp ] ) ) { $manual[ $comp ] = array(); }
        $existingIdx = null;
        foreach ( $manual[ $comp ] as $idx => $f ) { if ( $f['id'] === $fid ) { $existingIdx = $idx; break; } }

        $fixture = array(
            'id' => $fid, 'round' => $round, 'date' => gmdate( 'Y-m-d\TH:i', $ts ),
            'home' => $home, 'away' => $away, 'hg' => $hg, 'ag' => $ag,
            'status' => ( null !== $hg && null !== $ag ) ? 'FT' : 'NS',
            'ko' => false, 'decided' => null, 'winner' => null,
        );
        if ( null !== $existingIdx ) { $manual[ $comp ][ $existingIdx ] = $fixture; $updated++; }
        else { $manual[ $comp ][] = $fixture; $added++; }
    }
    fclose( $handle );
    update_option( 'ftipp_manual_fixtures', $manual, false );
    return array( 'added' => $added, 'updated' => $updated, 'skipped' => $skipped );
}
add_action( 'admin_post_ftipp_import_csv', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Keine Berechtigung.' ); }
    check_admin_referer( 'ftipp_import_csv' );
    $args = array( 'page' => 'ftipp' );
    if ( empty( $_FILES['csv_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
        $args['ftipp_csv'] = 'error'; $args['ftipp_csv_msg'] = rawurlencode( 'Keine Datei ausgewählt.' );
    } else {
        $result = ftipp_import_fixtures_csv( $_FILES['csv_file']['tmp_name'] );
        if ( ! empty( $result['error'] ) ) {
            $args['ftipp_csv'] = 'error'; $args['ftipp_csv_msg'] = rawurlencode( $result['error'] );
        } else {
            $args['ftipp_csv'] = 'ok'; $args['added'] = $result['added']; $args['updated'] = $result['updated']; $args['skipped'] = $result['skipped'];
        }
    }
    wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
    exit;
} );

/**
 * Frühester Sperrzeitpunkt (Kickoff − Frist-Minuten) über ALLE Spiele desselben Spieltags/derselben Runde
 * hinweg — der ganze Spieltag sperrt (und wird für alle Mitspieler sichtbar) gemeinsam ab diesem einen
 * Zeitpunkt, nicht Spiel für Spiel einzeln zu seinem jeweils eigenen Anpfiff. Verhindert, dass jemand nach
 * schon gesperrten früheren Spielen des Spieltags seine Tipps zu den noch offenen späteren Spielen anpasst.
 */
function ftipp_round_lock_ts( $fixtures, $round_name, $deadline_min ) {
    $ts = null;
    foreach ( $fixtures as $f ) {
        if ( $f['round'] !== $round_name ) { continue; }
        $t = ftipp_kickoff_ts( $f ) - $deadline_min * 60;
        if ( null === $ts || $t < $ts ) { $ts = $t; }
    }
    return $ts;
}

/* Fixture per id finden (für Punkteberechnung) — geht über ftipp_fixtures_for(), damit auch per CSV
   importierte manuelle Spiele gefunden werden, nicht nur automatisch abgerufene. */
function ftipp_fixture_by_id( $comp_id, $fixture_id ) {
    foreach ( ftipp_fixtures_for( $comp_id ) as $f ) { if ( $f['id'] === $fixture_id ) { return $f; } }
    return null;
}
/**
 * Alle Spiele eines Wettbewerbs: automatisch abgerufene (ftipp_fixtures) zusammengeführt mit manuell per
 * CSV importierten (ftipp_manual_fixtures, siehe ftipp_import_fixtures_csv()) — bewusst getrennt gespeichert,
 * damit der wöchentliche automatische Abruf hochgeladene CSV-Daten nicht überschreibt. Bei gleicher Spiel-ID
 * gewinnt die manuelle Version (z.B. um ein Ergebnis nachzutragen, das die automatische Quelle nicht kennt).
 */
function ftipp_fixtures_for( $comp_id ) {
    $auto = get_option( 'ftipp_fixtures', array() );
    $manual = get_option( 'ftipp_manual_fixtures', array() );
    $byId = array();
    foreach ( ( isset( $auto[ $comp_id ] ) ? $auto[ $comp_id ] : array() ) as $f ) { $byId[ $f['id'] ] = $f; }
    foreach ( ( isset( $manual[ $comp_id ] ) ? $manual[ $comp_id ] : array() ) as $f ) { $byId[ $f['id'] ] = $f; }
    $out = array_values( $byId );
    usort( $out, function ( $a, $b ) { return strcmp( $a['date'], $b['date'] ); } );
    return $out;
}
function ftipp_kickoff_ts( $fixture ) { return strtotime( $fixture['date'] ); }
function ftipp_has_result( $fixture ) { return isset( $fixture['hg'], $fixture['ag'] ) && null !== $fixture['hg'] && null !== $fixture['ag'] && 'FT' === $fixture['status']; }

/**
 * Findet Tipps, deren fixture_id in der AKTUELL geladenen Spielliste nicht mehr vorkommt — das passiert,
 * wenn sich für einen Wettbewerb die Datenquelle ändert (z.B. DFB-Pokal: API-Football -> OpenLigaDB, v0.8.17)
 * und dadurch neue Spiel-IDs vergeben werden. Die Tipp-Rohdaten gehen dabei NICHT verloren, sind nur nicht
 * mehr automatisch zuordenbar — diese Liste macht sie sichtbar, damit nichts stillschweigend verschwindet.
 */
function ftipp_orphaned_tips() {
    global $wpdb;
    $out = array();
    foreach ( ftipp_comp_ids() as $cid ) {
        $validIds = array();
        foreach ( ftipp_fixtures_for( $cid ) as $f ) { $validIds[ $f['id'] ] = true; }
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT user_id, fixture_id, hg, ag, ko_decided, ko_winner, committed, updated_at FROM {$wpdb->prefix}ftipp_tips WHERE comp_id=%s ORDER BY updated_at DESC", $cid
        ), ARRAY_A );
        foreach ( $rows as $r ) {
            if ( isset( $validIds[ $r['fixture_id'] ] ) ) { continue; }
            $u = get_userdata( $r['user_id'] );
            $out[] = array_merge( $r, array( 'comp_id' => $cid, 'user_name' => $u ? ftipp_public_name( $u ) : ( 'Nutzer #' . $r['user_id'] ) ) );
        }
    }
    return $out;
}

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
    // Akzeptiert sowohl "Liga A - Spieltag 1" (echtes Format aus dem CSV-Import) als auch
    // "Liga A, Spieltag 1" (Format der Demo-Daten/alten API-Football-Übersetzung). Demo-Spiele (IDs mit
    // "demo-" Präfix, siehe ftipp_load_test_fixtures()) werden dabei bewusst ausgeschlossen — sonst können sie
    // sich über gemeinsame Teamnamen (z.B. "Deutschland") mit den echten Gruppen verbinden und eine viel zu
    // große Gruppe vortäuschen, die zu keinem echten Team-Satz mehr passt.
    $stage = array_values( array_filter( ftipp_fixtures_for( 'NL' ), function ( $f ) {
        return isset( $f['round'] ) && preg_match( '/^Liga [ABCD]\s*[,-]\s*Spieltag/', $f['round'] ) && 0 !== strpos( $f['id'], 'demo-' );
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

    // Offizielle UEFA-Gruppennummerierung 2026/27 (aus Wikipedia/UEFA.com geprüft) — die Reihenfolge A1-A4/
    // B1-B4/C1-C4/D1-D2 ergibt sich aus der Auslosung/Setzliste und lässt sich NICHT aus den Spieldaten selbst
    // ableiten. Key = alphabetisch sortierte Teams mit "|" verbunden (exakt wie $g['teams'] gebildet wird).
    // Muss bei der nächsten Auslosung (Saison 2028/29) neu geprüft und aktualisiert werden.
    $officialCode = array(
        'Belgien|Frankreich|Italien|Türkei' => 'A1', 'Deutschland|Griechenland|Niederlande|Serbien' => 'A2',
        'England|Kroatien|Spanien|Tschechien' => 'A3', 'Dänemark|Norwegen|Portugal|Wales' => 'A4',
        'Nordmazedonien|Schottland|Schweiz|Slowenien' => 'B1', 'Georgien|Nordirland|Ukraine|Ungarn' => 'B2',
        'Irland|Israel|Kosovo|Österreich' => 'B3', 'Bosnien-Herzegowina|Polen|Rumänien|Schweden' => 'B4',
        'Albanien|Belarus|Finnland|San Marino' => 'C1', 'Armenien|Lettland|Montenegro|Zypern' => 'C2',
        'Färöer|Kasachstan|Moldau|Slowakei' => 'C3', 'Bulgarien|Estland|Island|Luxemburg' => 'C4',
        'Andorra|Gibraltar|Malta' => 'D1', 'Aserbaidschan|Liechtenstein|Litauen' => 'D2',
    );
    $seq = array();
    foreach ( $groups as &$g ) {
        $seq[ $g['division'] ] = isset( $seq[ $g['division'] ] ) ? $seq[ $g['division'] ] + 1 : 1;
        $g['key']  = 'nlgrp_' . substr( md5( implode( '|', $g['teams'] ) ), 0, 10 );
        $teamKey   = implode( '|', $g['teams'] );
        // Fallback auf die alte, selbst gezählte Bezeichnung, falls sich die Auslosung geändert hat und der
        // Team-Satz nicht mehr in der Tabelle oben steht (z.B. andere Saison).
        $code      = isset( $officialCode[ $teamKey ] ) ? $officialCode[ $teamKey ] : ( 'Liga ' . $g['division'] . ' – Gruppe ' . $seq[ $g['division'] ] );
        $g['label'] = 'Gruppensieger ' . $code;
    }
    unset( $g );
    return $groups;
}

/**
 * Echte Tabelle (nicht die Tipp-Rangliste!) für ein festes Team-Set aus einer Liste Spiele berechnen —
 * Standard-Fußballregeln (3/1/0 Punkte), sortiert nach Punkte, Tordifferenz, Tore, dann Teamname.
 * Nur für Wettbewerbe ohne Fremd-API-Tabelle nötig (aktuell: Nations League, aus den CSV-Spieldaten).
 */
function ftipp_mini_table( $teams, $fixtures ) {
    $stats = array();
    foreach ( $teams as $t ) {
        $stats[ $t ] = array( 'team' => $t, 'matches' => 0, 'won' => 0, 'draw' => 0, 'lost' => 0, 'goals' => 0, 'opponentGoals' => 0, 'points' => 0 );
    }
    foreach ( $fixtures as $f ) {
        if ( ! ftipp_has_result( $f ) ) { continue; }
        if ( ! isset( $stats[ $f['home'] ], $stats[ $f['away'] ] ) ) { continue; }
        $hg = intval( $f['hg'] ); $ag = intval( $f['ag'] );
        $stats[ $f['home'] ]['matches']++; $stats[ $f['away'] ]['matches']++;
        $stats[ $f['home'] ]['goals'] += $hg; $stats[ $f['home'] ]['opponentGoals'] += $ag;
        $stats[ $f['away'] ]['goals'] += $ag; $stats[ $f['away'] ]['opponentGoals'] += $hg;
        if ( $hg > $ag ) { $stats[ $f['home'] ]['won']++; $stats[ $f['home'] ]['points'] += 3; $stats[ $f['away'] ]['lost']++; }
        elseif ( $hg < $ag ) { $stats[ $f['away'] ]['won']++; $stats[ $f['away'] ]['points'] += 3; $stats[ $f['home'] ]['lost']++; }
        else { $stats[ $f['home'] ]['draw']++; $stats[ $f['away'] ]['draw']++; $stats[ $f['home'] ]['points']++; $stats[ $f['away'] ]['points']++; }
    }
    $rows = array_values( $stats );
    foreach ( $rows as &$r ) { $r['goalDiff'] = $r['goals'] - $r['opponentGoals']; }
    unset( $r );
    usort( $rows, function ( $a, $b ) {
        if ( $a['points'] !== $b['points'] ) { return $b['points'] - $a['points']; }
        if ( $a['goalDiff'] !== $b['goalDiff'] ) { return $b['goalDiff'] - $a['goalDiff']; }
        if ( $a['goals'] !== $b['goals'] ) { return $b['goals'] - $a['goals']; }
        return strcmp( $a['team'], $b['team'] );
    } );
    foreach ( $rows as $i => &$r ) { $r['rank'] = $i + 1; }
    unset( $r );
    return $rows;
}

/**
 * Echte Liga-Tabelle über OpenLigaDB (nicht Tipp-Rangliste). 15 Minuten gecacht (Transient), damit nicht
 * bei jedem Tabelle-Tab-Aufruf ein externer API-Call nötig ist. Bewusst OHNE Team-Logos im Payload — die
 * kommen bei OpenLigaDB teils als riesige eingebettete Base64-Bilder statt URLs zurück.
 */
function ftipp_fetch_bltable( $shortcut, $season ) {
    $cacheKey = 'ftipp_bltable_' . $shortcut . '_' . $season;
    $cached = get_transient( $cacheKey );
    if ( false !== $cached ) { return $cached; }
    $resp = wp_remote_get( "https://api.openligadb.de/getbltable/{$shortcut}/{$season}", array( 'timeout' => 20 ) );
    if ( is_wp_error( $resp ) ) { return array(); }
    $code = (int) wp_remote_retrieve_response_code( $resp );
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( 200 !== $code || ! is_array( $body ) ) { return array(); }
    $rows = array();
    foreach ( $body as $i => $t ) {
        $rows[] = array(
            'rank' => $i + 1, 'team' => isset( $t['teamName'] ) ? $t['teamName'] : '?',
            'matches' => intval( $t['matches'] ), 'won' => intval( $t['won'] ), 'draw' => intval( $t['draw'] ), 'lost' => intval( $t['lost'] ),
            'goals' => intval( $t['goals'] ), 'opponentGoals' => intval( $t['opponentGoals'] ), 'goalDiff' => intval( $t['goalDiff'] ),
            'points' => intval( $t['points'] ),
        );
    }
    set_transient( $cacheKey, $rows, 15 * MINUTE_IN_SECONDS );
    return $rows;
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
        'BL3' => array(
            array( 'key' => 'champion',      'label' => '3.-Liga-Meister',      'type' => 'champion',      'points' => 10 ),
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
        // Keine "Herbstmeister"-Wertung bei Premier League/LaLiga — das ist eine rein deutsche
        // Bundesliga-Tradition, in England/Spanien gibt es dafür keine vergleichbare feste Kategorie.
        'PL' => array(
            array( 'key' => 'champion',   'label' => 'Englischer Meister',   'type' => 'champion',   'points' => 10 ),
            array( 'key' => 'relegation', 'label' => 'Absteiger',            'type' => 'relegation', 'points' => 10 ),
            array( 'key' => 'topscorer',  'label' => 'Torschützenkönig',     'type' => 'topscorer',  'points' => 10 ),
            array( 'key' => 'topassist',  'label' => 'Bester Passgeber',     'type' => 'topassist',  'points' => 10 ),
        ),
        'LA1' => array(
            array( 'key' => 'champion',   'label' => 'Spanischer Meister',   'type' => 'champion',   'points' => 10 ),
            array( 'key' => 'relegation', 'label' => 'Absteiger',            'type' => 'relegation', 'points' => 10 ),
            array( 'key' => 'topscorer',  'label' => 'Torschützenkönig',     'type' => 'topscorer',  'points' => 10 ),
            array( 'key' => 'topassist',  'label' => 'Bester Passgeber',     'type' => 'topassist',  'points' => 10 ),
        ),
        'TR1' => array(
            array( 'key' => 'champion',   'label' => 'Türkischer Meister',   'type' => 'champion',   'points' => 10 ),
            array( 'key' => 'relegation', 'label' => 'Absteiger',            'type' => 'relegation', 'points' => 10 ),
            array( 'key' => 'topscorer',  'label' => 'Torschützenkönig',     'type' => 'topscorer',  'points' => 10 ),
            array( 'key' => 'topassist',  'label' => 'Bester Passgeber',     'type' => 'topassist',  'points' => 10 ),
        ),
        'FBL' => array(
            array( 'key' => 'champion',      'label' => 'Deutsche Meisterin',   'type' => 'champion',      'points' => 10 ),
            array( 'key' => 'herbstmeister', 'label' => 'Herbstmeisterin',      'type' => 'herbstmeister', 'points' => 10 ),
            array( 'key' => 'relegation',    'label' => 'Absteigerin',          'type' => 'relegation',    'points' => 10 ),
            array( 'key' => 'topscorer',     'label' => 'Torschützenkönigin',   'type' => 'topscorer',     'points' => 10 ),
            array( 'key' => 'topassist',     'label' => 'Beste Passgeberin',    'type' => 'topassist',     'points' => 10 ),
        ),
        'RLNO' => array(
            array( 'key' => 'champion',      'label' => 'Meister Regionalliga Nordost', 'type' => 'champion',      'points' => 10 ),
            array( 'key' => 'herbstmeister', 'label' => 'Herbstmeister',                'type' => 'herbstmeister', 'points' => 10 ),
            array( 'key' => 'relegation',    'label' => 'Absteiger',                    'type' => 'relegation',    'points' => 10 ),
            array( 'key' => 'topscorer',     'label' => 'Torschützenkönig',             'type' => 'topscorer',     'points' => 10 ),
            array( 'key' => 'topassist',     'label' => 'Bester Passgeber',             'type' => 'topassist',     'points' => 10 ),
        ),
        'ITA1' => array(
            array( 'key' => 'champion',   'label' => 'Italienischer Meister', 'type' => 'champion',   'points' => 10 ),
            array( 'key' => 'relegation', 'label' => 'Absteiger',             'type' => 'relegation', 'points' => 10 ),
            array( 'key' => 'topscorer',  'label' => 'Torschützenkönig',      'type' => 'topscorer',  'points' => 10 ),
            array( 'key' => 'topassist',  'label' => 'Bester Passgeber',      'type' => 'topassist',  'points' => 10 ),
        ),
        'FRA1' => array(
            array( 'key' => 'champion',   'label' => 'Französischer Meister', 'type' => 'champion',   'points' => 10 ),
            array( 'key' => 'relegation', 'label' => 'Absteiger',             'type' => 'relegation', 'points' => 10 ),
            array( 'key' => 'topscorer',  'label' => 'Torschützenkönig',      'type' => 'topscorer',  'points' => 10 ),
            array( 'key' => 'topassist',  'label' => 'Bester Passgeber',      'type' => 'topassist',  'points' => 10 ),
        ),
        'POL1' => array(
            array( 'key' => 'champion',   'label' => 'Polnischer Meister',    'type' => 'champion',   'points' => 10 ),
            array( 'key' => 'relegation', 'label' => 'Absteiger',             'type' => 'relegation', 'points' => 10 ),
            array( 'key' => 'topscorer',  'label' => 'Torschützenkönig',      'type' => 'topscorer',  'points' => 10 ),
            array( 'key' => 'topassist',  'label' => 'Bester Passgeber',      'type' => 'topassist',  'points' => 10 ),
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

/** Wettbewerbs-Abo eines Nutzers INNERHALB einer bestimmten Runde, inkl. DFB-Automatik. */
function ftipp_round_subs( $round_id, $user_id ) {
    global $wpdb;
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT comp_id, active FROM {$wpdb->prefix}ftipp_round_subs WHERE round_id=%d AND user_id=%d", $round_id, $user_id
    ), ARRAY_A );
    $subs = array();
    foreach ( ftipp_comp_ids() as $cid ) { $subs[ $cid ] = false; }
    foreach ( $rows as $r ) { $subs[ $r['comp_id'] ] = (bool) intval( $r['active'] ); }
    if ( $subs['BL1'] && $subs['BL2'] ) { $subs['DFB'] = true; }
    return $subs;
}
/**
 * Ist ein Wettbewerb für einen Nutzer in IRGENDEINER seiner Runden aktiv? Tipps selbst sind pro Nutzer+
 * Wettbewerb global (nicht pro Runde) — für die Fristen-Erinnerung reicht daher "in mindestens einer Runde
 * dabei", unabhängig davon, in welcher genau.
 */
function ftipp_user_active_comps( $user_id ) {
    global $wpdb;
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT comp_id FROM {$wpdb->prefix}ftipp_round_subs WHERE user_id=%d AND active=1", $user_id
    ), ARRAY_A );
    $subs = array();
    foreach ( ftipp_comp_ids() as $cid ) { $subs[ $cid ] = false; }
    foreach ( $rows as $r ) { $subs[ $r['comp_id'] ] = true; }
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
/** Welche Wettbewerbe hat mindestens ein Mitglied dieser Runde abonniert (für die Kurzübersicht in der Runden-Karte). */
function ftipp_round_active_comps( $round_id ) {
    $members = ftipp_round_members( $round_id );
    $active = array();
    foreach ( ftipp_comp_ids() as $cid ) {
        foreach ( $members as $m ) {
            $subs = ftipp_round_subs( $round_id, $m['id'] );
            if ( ! empty( $subs[ $cid ] ) ) { $active[] = $cid; break; }
        }
    }
    return $active;
}
function ftipp_round_object( $round_row, $user_id ) {
    return array(
        'id'           => intval( $round_row['id'] ),
        'name'         => $round_row['name'],
        'code'         => $round_row['code'],
        'mode'         => $round_row['mode'],
        'is_admin'     => ftipp_is_round_admin( $round_row['id'], $user_id ),
        'members'      => ftipp_round_members( $round_row['id'] ),
        'config'       => ftipp_round_cfg_all( $round_row['id'] ),
        'active_comps' => ftipp_round_active_comps( $round_row['id'] ),
        'my_subs'      => ftipp_round_subs( $round_row['id'], $user_id ),
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
        $subs = ftipp_round_subs( $rid, $m['id'] );
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
            $st = $wpdb->get_row( $wpdb->prepare(
                "SELECT value, override FROM {$wpdb->prefix}ftipp_special_tips WHERE special_id=%d AND user_id=%d", $b['id'], $m['id']
            ), ARRAY_A );
            // Admin-Override hat Vorrang vor dem automatischen Textvergleich — nötig, weil "St. Pauli" und
            // "FC St. Pauli" inhaltlich dasselbe meinen, aber per Textvergleich nicht übereinstimmen würden.
            if ( $st && null !== $st['override'] ) {
                $isCorrect = (bool) intval( $st['override'] );
            } else {
                $isCorrect = $st && null !== $st['value'] && strcasecmp( trim( $st['value'] ), trim( $b['result'] ) ) === 0;
            }
            if ( $isCorrect ) { $total += intval( $b['points'] ); $special += intval( $b['points'] ); }
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
    $candidates = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$wpdb->prefix}ftipp_round_subs WHERE active=1" );
    if ( ! $candidates ) { return; }

    $duePerUser = array();
    foreach ( ftipp_leagues() as $cid => $lg ) {
        $fixtures = ftipp_fixtures_for( $cid );
        foreach ( $fixtures as $f ) {
            if ( 'FT' === $f['status'] ) { continue; }
            $ko = strtotime( $f['date'] );
            if ( ! $ko || $ko < $now || $ko > $until ) { continue; }
            foreach ( $candidates as $uid ) {
                $subs = ftipp_user_active_comps( $uid );
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
            $relevant = array_values( array_filter( $members, function ( $m ) use ( $cid, $round ) {
                $s = ftipp_round_subs( $round['id'], $m['id'] ); return ! empty( $s[ $cid ] );
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
            $fixturesByComp = array();
            foreach ( ftipp_comp_ids() as $cid ) { $fixturesByComp[ $cid ] = ftipp_fixtures_for( $cid ); }
            return array(
                'season'   => intval( get_option( 'ftipp_season', 2026 ) ),
                'fixtures' => $fixturesByComp,
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

    register_rest_route( 'ftipp/v1', '/rounds/(?P<id>\d+)/subs', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            $uid = get_current_user_id(); $rid = intval( $req['id'] );
            if ( ! ftipp_is_round_member( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }
            return array( 'subs' => ftipp_round_subs( $rid, $uid ) );
        },
    ) );
    register_rest_route( 'ftipp/v1', '/rounds/(?P<id>\d+)/subs', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'args' => array( 'comp_id' => array( 'required' => true ), 'active' => array( 'required' => true ) ),
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $rid = intval( $req['id'] );
            if ( ! ftipp_is_round_member( $rid, $uid ) ) { return new WP_Error( 'forbidden', 'Kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }
            $comp = sanitize_text_field( $req['comp_id'] );
            if ( ! in_array( $comp, ftipp_comp_ids(), true ) ) { return new WP_Error( 'bad_comp', 'Unbekannter Wettbewerb.', array( 'status' => 400 ) ); }
            $wpdb->replace( "{$wpdb->prefix}ftipp_round_subs", array( 'round_id' => $rid, 'user_id' => $uid, 'comp_id' => $comp, 'active' => $req['active'] ? 1 : 0 ) );
            return array( 'subs' => ftipp_round_subs( $rid, $uid ) );
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
            // Abos je Mitglied mit exportieren, sonst fehlt bei einer Wiederherstellung die Info, wer in
            // dieser Runde welchen Wettbewerb überhaupt mitspielt.
            $membersWithSubs = array_map( function ( $m ) use ( $rid ) {
                return array_merge( $m, array( 'abos' => ftipp_round_subs( $rid, $m['id'] ) ) );
            }, $members );

            $tipsByComp = array();
            $specialByComp = array();
            foreach ( ftipp_comp_ids() as $cid ) {
                $relevant = array_values( array_filter( $members, function ( $m ) use ( $cid, $rid ) {
                    $s = ftipp_round_subs( $rid, $m['id'] ); return ! empty( $s[ $cid ] );
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
                'mitglieder'    => $membersWithSubs,
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

    /**
     * Wiederherstellung aus einer zuvor exportierten JSON-Datei (siehe /rounds/{id}/export) — legt eine NEUE
     * Runde an (nicht die alte überschreiben, falls die noch existiert) mit denselben Regeln, Mitgliedern
     * (nur soweit deren WordPress-Konto noch existiert — sonst werden sie übersprungen und gemeldet),
     * Sonderwertungen samt Tipps, Spieltipps und Pinnwand-Nachrichten.
     */
    register_rest_route( 'ftipp/v1', '/rounds/import', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id();
            $data = $req->get_json_params();
            if ( ! is_array( $data ) || empty( $data['runde']['name'] ) || ! isset( $data['mitglieder'] ) ) {
                return new WP_Error( 'bad_data', 'Das sieht nicht nach einer gültigen Tippstube-Export-Datei aus.', array( 'status' => 400 ) );
            }

            $wpdb->insert( "{$wpdb->prefix}ftipp_rounds", array(
                'name' => sanitize_text_field( $data['runde']['name'] ),
                'code' => ftipp_generate_code(),
                'mode' => in_array( ( $data['runde']['modus'] ?? '' ), array( 'friendly', 'challenge' ), true ) ? $data['runde']['modus'] : 'friendly',
                'admin_user_id' => $uid, 'created_at' => current_time( 'mysql' ),
            ) );
            $newRid = $wpdb->insert_id;

            $skipped = array(); $idMap = array(); $importerIncluded = false;
            foreach ( (array) $data['mitglieder'] as $m ) {
                $mid = intval( $m['id'] ?? 0 );
                if ( ! $mid ) { continue; }
                $u = get_userdata( $mid );
                if ( ! $u ) { $skipped[] = isset( $m['name'] ) ? sanitize_text_field( $m['name'] ) : ( 'Nutzer #' . $mid ); continue; }
                $idMap[ $mid ] = true;
                if ( $mid === $uid ) { $importerIncluded = true; }
                $wpdb->replace( "{$wpdb->prefix}ftipp_round_members", array( 'round_id' => $newRid, 'user_id' => $mid, 'joined_at' => current_time( 'mysql' ) ) );
                foreach ( (array) ( $m['abos'] ?? array() ) as $cid => $active ) {
                    if ( ! in_array( $cid, ftipp_comp_ids(), true ) || ! $active ) { continue; }
                    $wpdb->replace( "{$wpdb->prefix}ftipp_round_subs", array( 'round_id' => $newRid, 'user_id' => $mid, 'comp_id' => $cid, 'active' => 1 ) );
                }
            }
            if ( ! $importerIncluded ) {
                $wpdb->replace( "{$wpdb->prefix}ftipp_round_members", array( 'round_id' => $newRid, 'user_id' => $uid, 'joined_at' => current_time( 'mysql' ) ) );
            }

            foreach ( (array) ( $data['regeln'] ?? array() ) as $cid => $cfg ) {
                if ( ! in_array( $cid, ftipp_comp_ids(), true ) || ! is_array( $cfg ) ) { continue; }
                $wpdb->replace( "{$wpdb->prefix}ftipp_round_config", array(
                    'round_id' => $newRid, 'comp_id' => $cid,
                    'p_tend' => intval( $cfg['pTend'] ?? 1 ), 'p_exact' => intval( $cfg['pExact'] ?? 3 ), 'p_ko' => intval( $cfg['pKO'] ?? 3 ),
                    'malus_on' => ! empty( $cfg['malusOn'] ) ? 1 : 0, 'malus' => intval( $cfg['malus'] ?? -1 ), 'deadline_min' => intval( $cfg['deadlineMin'] ?? 60 ),
                ) );
            }

            foreach ( (array) ( $data['sonderwertungen'] ?? array() ) as $cid => $bets ) {
                if ( ! in_array( $cid, ftipp_comp_ids(), true ) ) { continue; }
                foreach ( (array) $bets as $b ) {
                    $wpdb->insert( "{$wpdb->prefix}ftipp_special", array(
                        'round_id' => $newRid, 'comp_id' => $cid,
                        'label' => sanitize_text_field( $b['label'] ?? 'Sonderwertung' ), 'type' => sanitize_text_field( $b['type'] ?? 'custom' ),
                        'points' => intval( $b['points'] ?? 10 ), 'deadline' => $b['deadline'] ?? null, 'result' => $b['result'] ?? null,
                    ) );
                    $newSpecialId = $wpdb->insert_id;
                    foreach ( (array) ( $b['tipps'] ?? array() ) as $t ) {
                        $tuid = intval( $t['user_id'] ?? 0 );
                        if ( ! $tuid || ! isset( $idMap[ $tuid ] ) ) { continue; }
                        $wpdb->replace( "{$wpdb->prefix}ftipp_special_tips", array( 'special_id' => $newSpecialId, 'user_id' => $tuid, 'value' => $t['value'] ?? null ) );
                    }
                }
            }

            foreach ( (array) ( $data['tipps'] ?? array() ) as $cid => $rows ) {
                if ( ! in_array( $cid, ftipp_comp_ids(), true ) ) { continue; }
                foreach ( (array) $rows as $t ) {
                    $tuid = intval( $t['user_id'] ?? 0 );
                    if ( ! $tuid || ! isset( $idMap[ $tuid ] ) || empty( $t['fixture_id'] ) ) { continue; }
                    $wpdb->replace( "{$wpdb->prefix}ftipp_tips", array(
                        'user_id' => $tuid, 'comp_id' => $cid, 'fixture_id' => sanitize_text_field( $t['fixture_id'] ),
                        'hg' => $t['hg'] ?? null, 'ag' => $t['ag'] ?? null,
                        'ko_decided' => $t['ko_decided'] ?? null, 'ko_winner' => $t['ko_winner'] ?? null,
                        'committed' => ! empty( $t['committed'] ) ? 1 : 0, 'updated_at' => $t['updated_at'] ?? current_time( 'mysql' ),
                    ) );
                }
            }

            foreach ( (array) ( $data['pinnwand'] ?? array() ) as $msg ) {
                $muid = intval( $msg['user_id'] ?? 0 );
                if ( ! $muid || ! isset( $idMap[ $muid ] ) || empty( $msg['message'] ) ) { continue; }
                $wpdb->insert( "{$wpdb->prefix}ftipp_chat", array(
                    'round_id' => $newRid, 'user_id' => $muid, 'message' => sanitize_text_field( $msg['message'] ),
                    'created_at' => $msg['created_at'] ?? current_time( 'mysql' ),
                ) );
            }

            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ftipp_rounds WHERE id=%d", $newRid ), ARRAY_A );
            $result = ftipp_round_object( $row, $uid );
            $result['skipped_members'] = $skipped;
            return $result;
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

            // Ein ganzer Spieltag sperrt UND wird sichtbar GEMEINSAM, sobald das früheste Spiel dieses
            // Spieltags seine Frist erreicht (nicht mehr einzeln pro Spiel zu dessen eigenem Anpfiff) — sonst
            // könnte jemand nach schon gesperrten Freitagsspielen seine Tipps für die noch offenen
            // Sonntagsspiele anpassen, nachdem er die Tipps der anderen zum Freitagsspiel schon gesehen hat.
            $roundLockTs = array();
            foreach ( $fixtures as $f ) {
                $rn = $f['round']; $ts = ftipp_kickoff_ts( $f ) - $cfg['deadlineMin'] * 60;
                if ( ! isset( $roundLockTs[ $rn ] ) || $ts < $roundLockTs[ $rn ] ) { $roundLockTs[ $rn ] = $ts; }
            }

            $out = array();
            foreach ( $fixtures as $f ) {
                $locked = time() >= $roundLockTs[ $f['round'] ];
                $mineRow = isset( $byUserFixture[ $uid ][ $f['id'] ] ) ? $byUserFixture[ $uid ][ $f['id'] ] : null;
                $mine = $mineRow ? array(
                    'h' => $mineRow['hg'] === null ? null : intval( $mineRow['hg'] ),
                    'a' => $mineRow['ag'] === null ? null : intval( $mineRow['ag'] ),
                    'ko' => ( $mineRow['ko_decided'] || $mineRow['ko_winner'] ) ? array( 'decided' => $mineRow['ko_decided'], 'winner' => $mineRow['ko_winner'] ) : null,
                    'committed' => (bool) intval( $mineRow['committed'] ),
                ) : null;
                $revealed = $locked;
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
            // Der ganze Spieltag sperrt gemeinsam, sobald dessen frühestes Spiel seine Frist erreicht — nicht
            // erst beim eigenen Anpfiff dieses einen Spiels (siehe ftipp_round_lock_ts()).
            $lockTs = ftipp_round_lock_ts( ftipp_fixtures_for( $comp ), $fixture['round'], $cfg['deadlineMin'] );
            if ( time() >= $lockTs ) {
                return new WP_Error( 'locked', 'Dieser Spieltag ist bereits gesperrt.', array( 'status' => 400 ) );
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
            $members = ftipp_round_members( $rid );
            // Bei der Nations League ist z.B. "Gruppensieger A1" allein nicht selbsterklärend — dafür hier
            // die zugehörigen Teams mitgeben (nachschlagbar über das identische Label aus ftipp_nl_groups()).
            $nlTeamsByLabel = array();
            if ( 'NL' === $comp ) {
                foreach ( ftipp_nl_groups() as $g ) { $nlTeamsByLabel[ $g['label'] ] = $g['teams']; }
            }
            $out = array();
            foreach ( $bets as $b ) {
                $rows = $wpdb->get_results( $wpdb->prepare(
                    "SELECT user_id, value, committed FROM {$wpdb->prefix}ftipp_special_tips WHERE special_id=%d", $b['id']
                ), ARRAY_A );
                $byUser = array(); foreach ( $rows as $r ) { $byUser[ intval( $r['user_id'] ) ] = $r; }
                $mine = isset( $byUser[ $uid ] ) ? $byUser[ $uid ] : null;
                // Sonderwertungen sind Saison-Wetten — anders als bei Spiel-Tipps zeigen wir die Tipps der
                // anderen NICHT schon, sobald man selbst abgegeben hat, sondern erst gemeinsam für alle,
                // sobald die Frist abgelaufen ist (kein Vorteil durch "wer zuerst tippt sieht als Erster").
                $locked = $b['deadline'] && strtotime( $b['deadline'] ) <= time();
                $others = array();
                if ( $locked ) {
                    foreach ( $members as $m ) {
                        if ( $m['id'] === $uid ) { continue; }
                        $r = isset( $byUser[ $m['id'] ] ) ? $byUser[ $m['id'] ] : null;
                        if ( $r && '' !== (string) $r['value'] ) { $others[] = array( 'name' => $m['name'], 'value' => $r['value'] ); }
                    }
                }
                $out[] = array(
                    'id' => intval( $b['id'] ), 'label' => $b['label'], 'type' => $b['type'], 'points' => intval( $b['points'] ),
                    'deadline' => $b['deadline'] ? str_replace( ' ', 'T', substr( $b['deadline'], 0, 16 ) ) : null,
                    'result' => $b['result'], 'my_value' => $mine ? $mine['value'] : null,
                    'my_committed' => $mine ? (bool) intval( $mine['committed'] ) : false,
                    'locked' => $locked, 'others' => $others,
                    'teams' => isset( $nlTeamsByLabel[ $b['label'] ] ) ? $nlTeamsByLabel[ $b['label'] ] : null,
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
            // Bereits abgegebene Tipps dürfen bis zur Frist weiter geändert werden ("Bearbeiten" im Frontend,
            // wie bei den Spiel-Tipps) — der Commit-Status bleibt dabei unangetastet.
            $existing = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ftipp_special_tips WHERE special_id=%d AND user_id=%d", $id, $uid
            ), ARRAY_A );
            $wpdb->replace( "{$wpdb->prefix}ftipp_special_tips", array(
                'special_id' => $id, 'user_id' => $uid, 'value' => sanitize_text_field( $req['value'] ),
                'override' => $existing ? $existing['override'] : null,
                'committed' => $existing ? intval( $existing['committed'] ) : 0,
            ) );
            return array( 'ok' => true );
        },
    ) );
    register_rest_route( 'ftipp/v1', '/special/(?P<id>\d+)/tip/commit', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $id = intval( $req['id'] );
            $bet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ftipp_special WHERE id=%d", $id ), ARRAY_A );
            if ( ! $bet ) { return new WP_Error( 'not_found', 'Nicht gefunden.', array( 'status' => 404 ) ); }
            if ( ! ftipp_is_round_member( $bet['round_id'], $uid ) ) { return new WP_Error( 'forbidden', 'Kein Mitglied dieser Runde.', array( 'status' => 403 ) ); }
            if ( $bet['deadline'] && strtotime( $bet['deadline'] ) <= time() ) { return new WP_Error( 'locked', 'Frist ist vorbei.', array( 'status' => 400 ) ); }
            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ftipp_special_tips WHERE special_id=%d AND user_id=%d", $id, $uid
            ), ARRAY_A );
            if ( ! $row || '' === (string) $row['value'] ) {
                return new WP_Error( 'incomplete', 'Bitte zuerst einen Tipp eintragen.', array( 'status' => 400 ) );
            }
            $wpdb->update( "{$wpdb->prefix}ftipp_special_tips", array( 'committed' => 1 ), array( 'special_id' => $id, 'user_id' => $uid ) );
            return array( 'ok' => true );
        },
    ) );

    /**
     * Admin-Ansicht aller abgegebenen Tipps zu einer Sonderwertung — Grundlage für die manuelle Korrektur,
     * falls jemand z.B. "St. Pauli" statt "FC St. Pauli" getippt hat und der reine Textvergleich das nicht
     * als richtig erkennen würde.
     */
    register_rest_route( 'ftipp/v1', '/special/(?P<id>\d+)/tipps', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $id = intval( $req['id'] );
            $bet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ftipp_special WHERE id=%d", $id ), ARRAY_A );
            if ( ! $bet ) { return new WP_Error( 'not_found', 'Nicht gefunden.', array( 'status' => 404 ) ); }
            if ( ! ftipp_is_round_admin( $bet['round_id'], $uid ) ) { return new WP_Error( 'forbidden', 'Nur der Runden-Admin darf alle Tipps sehen.', array( 'status' => 403 ) ); }
            $members = ftipp_round_members( $bet['round_id'] );
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT user_id, value, override, committed FROM {$wpdb->prefix}ftipp_special_tips WHERE special_id=%d", $id
            ), ARRAY_A );
            $byUser = array();
            foreach ( $rows as $r ) { $byUser[ intval( $r['user_id'] ) ] = $r; }
            $out = array();
            foreach ( $members as $m ) {
                $r = isset( $byUser[ $m['id'] ] ) ? $byUser[ $m['id'] ] : null;
                $out[] = array(
                    'user_id' => $m['id'], 'name' => $m['name'],
                    'value' => $r ? $r['value'] : null,
                    'committed' => $r ? (bool) intval( $r['committed'] ) : false,
                    'override' => ( $r && null !== $r['override'] ) ? (bool) intval( $r['override'] ) : null,
                );
            }
            return array( 'tipps' => $out );
        },
    ) );
    register_rest_route( 'ftipp/v1', '/special/(?P<id>\d+)/tipps/(?P<uid>\d+)/override', array(
        'methods' => 'POST', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            global $wpdb; $uid = get_current_user_id(); $id = intval( $req['id'] ); $targetUid = intval( $req['uid'] );
            $bet = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ftipp_special WHERE id=%d", $id ), ARRAY_A );
            if ( ! $bet ) { return new WP_Error( 'not_found', 'Nicht gefunden.', array( 'status' => 404 ) ); }
            if ( ! ftipp_is_round_admin( $bet['round_id'], $uid ) ) { return new WP_Error( 'forbidden', 'Nur der Runden-Admin darf das korrigieren.', array( 'status' => 403 ) ); }
            $override = $req->get_param( 'override' ); // true/false = erzwingen, null = zurück auf automatischen Textvergleich
            $existing = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ftipp_special_tips WHERE special_id=%d AND user_id=%d", $id, $targetUid
            ), ARRAY_A );
            // Der Admin darf hier zusätzlich (auf Nutzerwunsch) auch den eingetippten Text selbst direkt
            // ändern — z.B. wenn ein Mitspieler nicht mehr selbst ändern kann/darf und den Admin bittet.
            $newValue = $req->get_param( 'value' );
            $wpdb->replace( "{$wpdb->prefix}ftipp_special_tips", array(
                'special_id' => $id, 'user_id' => $targetUid,
                'value' => null !== $newValue ? sanitize_text_field( $newValue ) : ( $existing ? $existing['value'] : null ),
                'override' => null === $override ? null : ( $override ? 1 : 0 ),
                'committed' => $existing ? intval( $existing['committed'] ) : 0,
            ) );
            return array( 'ok' => true );
        },
    ) );

    /* -------- Echte Tabelle (nicht die Tipp-Rangliste!) -------- */
    register_rest_route( 'ftipp/v1', '/table', array(
        'methods' => 'GET', 'permission_callback' => $auth,
        'callback' => function ( $req ) {
            $comp = sanitize_text_field( $req->get_param( 'comp' ) );
            // DFB-Pokal ist reines K.o.-System — dafür gibt's keine sinnvolle Tabelle.
            if ( 'DFB' === $comp ) { return array( 'supported' => false ); }
            if ( 'NL' === $comp ) {
                $fixtures = ftipp_fixtures_for( 'NL' );
                $groups = ftipp_nl_groups();
                $out = array();
                foreach ( $groups as $g ) {
                    $out[] = array(
                        'label' => str_replace( 'Gruppensieger ', 'Gruppe ', $g['label'] ),
                        'rows'  => ftipp_mini_table( $g['teams'], $fixtures ),
                    );
                }
                return array( 'supported' => true, 'mode' => 'groups', 'groups' => $out );
            }
            $shortcut = ftipp_openligadb_shortcut( $comp );
            if ( $shortcut ) {
                $season = ftipp_current_de_season();
                return array( 'supported' => true, 'mode' => 'league', 'season' => $season, 'rows' => ftipp_fetch_bltable( $shortcut, $season ) );
            }
            // Kein OpenLigaDB-Shortcut, aber evtl. eigene (CSV-)Spieldaten vorhanden (aktuell: Süper Lig) —
            // Tabelle selbst berechnen, gleiche Funktion wie bei der Nations League.
            $fixtures = ftipp_fixtures_for( $comp );
            if ( ! $fixtures ) { return array( 'supported' => false ); }
            $teams = array();
            foreach ( $fixtures as $f ) { $teams[ $f['home'] ] = true; $teams[ $f['away'] ] = true; }
            return array( 'supported' => true, 'mode' => 'league', 'rows' => ftipp_mini_table( array_keys( $teams ), $fixtures ) );
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
            $members = array_values( array_filter( $members, function ( $m ) use ( $comp, $rid ) {
                $s = ftipp_round_subs( $rid, $m['id'] ); return ! empty( $s[ $comp ] );
            } ) );
            if ( ! $members ) { return array( 'history' => array(), 'mine' => null, 'badges' => array(), 'matches' => array() ); }

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

            $mine = null; $badges = array(); $matches = array();
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
                            $matches[] = array(
                                'round' => $rn, 'date' => $f['date'], 'home' => $f['home'], 'away' => $f['away'],
                                'hg' => intval( $f['hg'] ), 'ag' => intval( $f['ag'] ),
                                'my_h' => intval( $t['hg'] ), 'my_a' => intval( $t['ag'] ),
                                'kind' => $r['kind'], 'pts' => $r['pts'] + $r['ko'],
                            );
                        } else {
                            $streak = 0; $miss++;
                            if ( $cfg['malusOn'] ) { $sum += $cfg['malus']; }
                            $matches[] = array(
                                'round' => $rn, 'date' => $f['date'], 'home' => $f['home'], 'away' => $f['away'],
                                'hg' => intval( $f['hg'] ), 'ag' => intval( $f['ag'] ),
                                'my_h' => null, 'my_a' => null,
                                'kind' => 'verpasst', 'pts' => $cfg['malusOn'] ? $cfg['malus'] : 0,
                            );
                        }
                    }
                    if ( $any ) { $roundTotals[ $rn ] = $sum; }
                }
                $matches = array_reverse( $matches ); // neueste zuerst, wie ein Verlauf/Feed
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

            // Bei der Nations League zusätzlich: Wer hat innerhalb JEDER Gruppe am besten getippt (nicht nur
            // Gesamt-Rangliste), und eine komplette Spiel-für-Spiel-Übersicht mit den Tipps ALLER Mitspieler
            // (nicht nur die eigenen, wie bei "matches" oben) — beides nur, wenn Gruppen erkannt werden.
            $nlGroupBoards = array(); $nlMatchBoard = array();
            if ( 'NL' === $comp ) {
                foreach ( ftipp_nl_groups() as $g ) {
                    $groupFixtureIds = array();
                    foreach ( $fixtures as $f ) {
                        if ( in_array( $f['home'], $g['teams'], true ) && in_array( $f['away'], $g['teams'], true ) ) {
                            $groupFixtureIds[ $f['id'] ] = true;
                        }
                    }
                    $rowsOut = array();
                    foreach ( $members as $m ) {
                        $pts = 0; $exact = 0; $tend = 0; $missed = 0;
                        foreach ( $fixtures as $f ) {
                            if ( ! isset( $groupFixtureIds[ $f['id'] ] ) || ! ftipp_has_result( $f ) ) { continue; }
                            $t = isset( $byUserFixture[ $m['id'] ][ $f['id'] ] ) ? $byUserFixture[ $m['id'] ][ $f['id'] ] : null;
                            if ( $t && null !== $t['hg'] && null !== $t['ag'] ) {
                                $r = ftipp_match_points( $f, $t, $cfg ); $pts += $r['pts'] + $r['ko'];
                                if ( 'exakt' === $r['kind'] ) { $exact++; } elseif ( 'tendenz' === $r['kind'] ) { $tend++; }
                            } else { $missed++; if ( $cfg['malusOn'] ) { $pts += $cfg['malus']; } }
                        }
                        $rowsOut[] = array( 'name' => $m['name'], 'points' => $pts, 'exact' => $exact, 'tend' => $tend, 'missed' => $missed );
                    }
                    usort( $rowsOut, function ( $a, $b ) { return $b['points'] - $a['points']; } );
                    $nlGroupBoards[] = array( 'label' => str_replace( 'Gruppensieger ', 'Gruppe ', $g['label'] ), 'rows' => $rowsOut );
                }
                foreach ( $fixtures as $f ) {
                    if ( ! ftipp_has_result( $f ) ) { continue; }
                    $tipsOut = array();
                    foreach ( $members as $m ) {
                        $t = isset( $byUserFixture[ $m['id'] ][ $f['id'] ] ) ? $byUserFixture[ $m['id'] ][ $f['id'] ] : null;
                        if ( $t && null !== $t['hg'] && null !== $t['ag'] ) {
                            $r = ftipp_match_points( $f, $t, $cfg );
                            $tipsOut[] = array( 'name' => $m['name'], 'tip' => $t['hg'] . ':' . $t['ag'], 'pts' => $r['pts'] + $r['ko'], 'kind' => $r['kind'] );
                        } else {
                            $tipsOut[] = array( 'name' => $m['name'], 'tip' => null, 'pts' => $cfg['malusOn'] ? $cfg['malus'] : 0, 'kind' => 'verpasst' );
                        }
                    }
                    $nlMatchBoard[] = array( 'round' => $f['round'], 'home' => $f['home'], 'away' => $f['away'], 'hg' => intval( $f['hg'] ), 'ag' => intval( $f['ag'] ), 'tips' => $tipsOut );
                }
                $nlMatchBoard = array_reverse( $nlMatchBoard );
            }

            return array( 'history' => $history, 'mine' => $mine, 'badges' => $badges, 'matches' => $matches, 'nlGroupBoards' => $nlGroupBoards, 'nlMatchBoard' => $nlMatchBoard );
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
                'wettbewerbs_abos' => $wpdb->get_results( $wpdb->prepare( "SELECT round_id, comp_id, active FROM {$wpdb->prefix}ftipp_round_subs WHERE user_id=%d", $uid ), ARRAY_A ),
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

            $wpdb->delete( "{$wpdb->prefix}ftipp_tips", array( 'user_id' => $uid ) );
            $wpdb->delete( "{$wpdb->prefix}ftipp_special_tips", array( 'user_id' => $uid ) );
            $wpdb->delete( "{$wpdb->prefix}ftipp_chat", array( 'user_id' => $uid ) );
            // Aus allen Runden austreten, in denen der Nutzer NICHT Admin ist (Admin-Runden zuerst übertragen/löschen)
            // — Wettbewerbs-Abos genauso nur für diese Runden löschen, in Admin-Runden bleiben sie erhalten.
            $wpdb->query( $wpdb->prepare(
                "DELETE rs FROM {$wpdb->prefix}ftipp_round_subs rs
                 JOIN {$wpdb->prefix}ftipp_rounds r ON r.id=rs.round_id
                 WHERE rs.user_id=%d AND r.admin_user_id<>%d", $uid, $uid
            ) );
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
 * Icon fürs WP-Admin-Menü: WordPress erkennt nur den Präfix "data:image/svg+xml;base64,"
 * als Menü-Icon und faerbt es per CSS passend zum Adminmenü-Theme ein — dafür muss das SVG
 * einfarbig (schwarz) sein, nicht die bunte Favicon-Version aus ftipp_favicon_href().
 */
function ftipp_menu_icon_href() {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 110">'
         . '<path fill-rule="evenodd" fill="black" d="M10 12 Q10 8 14 8 L86 8 Q90 8 90 12 L90 54 Q90 80 70 92 L50 104 L30 92 Q10 80 10 54 Z M50 29 A15 15 0 1 0 50 59 A15 15 0 1 0 50 29 Z M50 36 L57.6 41.5 L54.7 50.5 L45.3 50.5 L42.4 41.5 Z" />'
         . '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode( $svg );
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
    // Pro Wettbewerb über ftipp_fixtures_for() lesen, NICHT direkt die Option 'ftipp_fixtures' —
    // sonst fehlen der App die per CSV importierten Spiele (z.B. Nations League), da die in der
    // separaten Option 'ftipp_manual_fixtures' liegen und nur ftipp_fixtures_for() beides mischt.
    $fixturesByComp = array();
    foreach ( ftipp_comp_ids() as $cid ) { $fixturesByComp[ $cid ] = ftipp_fixtures_for( $cid ); }
    $boot = array(
        'userId'          => $user->ID,
        'userName'        => $user->display_name,
        'isPlatformAdmin' => current_user_can( 'manage_options' ),
        'restUrl'         => esc_url_raw( rest_url( 'ftipp/v1/' ) ),
        'nonce'           => wp_create_nonce( 'wp_rest' ),
        'season'          => intval( get_option( 'ftipp_season', 2026 ) ),
        'fixtures'        => $fixturesByComp,
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
 * Einstellungsseite — eigener Menüpunkt direkt im Admin-Menü (nicht mehr unter "Einstellungen" versteckt),
 * jetzt mit Untermenü (Einstellungen/Design/Cron-Job/History/Changelog/Info).
 * ============================================================ */
add_action( 'admin_menu', function () {
    add_menu_page( 'Tippstube', 'Tippstube', 'manage_options', 'ftipp', 'ftipp_settings_page', ftipp_menu_icon_href(), 30 );
    // Gleicher Slug wie der Parent ersetzt WordPress' automatisch erzeugten Default-Untermenüpunkt
    // (sonst gäbe es "Tippstube" doppelt ganz oben in der Liste).
    add_submenu_page( 'ftipp', 'Einstellungen', 'Einstellungen', 'manage_options', 'ftipp', 'ftipp_settings_page' );
    add_submenu_page( 'ftipp', 'Design', 'Design', 'manage_options', 'ftipp_design', 'ftipp_page_design' );
    add_submenu_page( 'ftipp', 'Cron-Job', 'Cron-Job', 'manage_options', 'ftipp_cron', 'ftipp_page_cron' );
    add_submenu_page( 'ftipp', 'History', 'History', 'manage_options', 'ftipp_history', 'ftipp_page_history' );
    add_submenu_page( 'ftipp', 'Changelog', 'Changelog', 'manage_options', 'ftipp_changelog', 'ftipp_page_changelog' );
    add_submenu_page( 'ftipp', 'Info', 'Info', 'manage_options', 'ftipp_info', 'ftipp_page_info' );
} );

/**
 * Platzhalter für die neuen Untermenüpunkte — Inhalt folgt in v0.8.1-v0.8.5,
 * jeweils einzeln lokal und live getestet, bevor der nächste Punkt gebaut wird.
 */
function ftipp_page_placeholder( $title ) {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1><p>Kommt in Kürze.</p></div>';
}
function ftipp_page_design()    { ftipp_page_placeholder( 'Design' ); }
function ftipp_page_history()   { ftipp_page_placeholder( 'History' ); }

/**
 * Liest CHANGELOG.md (eine bei jedem Release mitkopierte Kopie von PROJEKT_JOURNAL.md, siehe
 * ftipp_page_changelog()) und zerlegt sie in einzelne Versions-Einträge.
 *
 * Format: "## v<Version> — <Titel>" (Em-Dash), alles danach bis zur nächsten "##"-Überschrift ist
 * der Eintragstext. Nicht-versionierte Zwischenüberschriften (z.B. "## Projekt auf GitHub
 * veröffentlicht") schließen den laufenden Eintrag ab, ohne selbst einer zu werden. Am
 * "## OFFENE AUFGABEN"-Abschnitt wird abgebrochen. Versionsnummern sind nicht eindeutig/monoton
 * (Korrekturen im Projektverlauf) — die Reihenfolge kommt daher ausschließlich aus der Position in
 * der Datei, nicht aus einem Versionsvergleich.
 */
function ftipp_parse_changelog_md( $path ) {
    if ( ! file_exists( $path ) ) { return null; }
    $lines = file( $path, FILE_IGNORE_NEW_LINES );
    if ( ! $lines ) { return array(); }

    $entries = array();
    $current = null;
    foreach ( $lines as $line ) {
        if ( preg_match( '/^##\s+OFFENE AUFGABEN/i', $line ) ) {
            break;
        }
        if ( preg_match( '/^##\s+v(\S+)\s+—\s+(.+)$/u', $line, $m ) ) {
            if ( $current ) { $entries[] = $current; }
            $current = array( 'version' => $m[1], 'title' => $m[2], 'body' => array() );
            continue;
        }
        if ( preg_match( '/^##\s+/', $line ) ) {
            if ( $current ) { $entries[] = $current; }
            $current = null;
            continue;
        }
        if ( $current ) { $current['body'][] = $line; }
    }
    if ( $current ) { $entries[] = $current; }

    return array_reverse( $entries );
}

/**
 * Sehr einfacher, absichtlich eingeschränkter Markdown-zu-HTML-Konverter fürs Changelog: erst
 * esc_html() auf den kompletten Rohtext (nie ungefiltertes HTML ausgeben), danach gezielt Markdown-
 * Inline-Formatierung ersetzen. Reihenfolge wichtig: Code vor Fett (sonst Konflikt mit Sternchen in
 * Code-Beispielen), Fett vor Kursiv (sonst frisst "**" das erste "*" eines "*kursiv*").
 */
function ftipp_changelog_md_to_html( array $bodyLines ) {
    $html = '';
    $inList = false;
    foreach ( $bodyLines as $line ) {
        $trimmed = trim( $line );
        if ( '' === $trimmed ) {
            if ( $inList ) { $html .= '</ul>'; $inList = false; }
            continue;
        }
        $isListItem = ( 0 === strpos( $trimmed, '- ' ) );
        $text = $isListItem ? substr( $trimmed, 2 ) : $trimmed;

        $text = esc_html( $text );
        $text = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $text );
        $text = preg_replace( '/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text );
        $text = preg_replace( '/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $text );
        $text = preg_replace( '/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $text );

        if ( $isListItem ) {
            if ( ! $inList ) { $html .= '<ul>'; $inList = true; }
            $html .= '<li>' . $text . '</li>';
        } else {
            if ( $inList ) { $html .= '</ul>'; $inList = false; }
            $html .= '<p>' . $text . '</p>';
        }
    }
    if ( $inList ) { $html .= '</ul>'; }
    return $html;
}

/**
 * Changelog-Seite: liest fussball-tippspiel/CHANGELOG.md — eine Kopie von PROJEKT_JOURNAL.md, die bei
 * jedem Release manuell mitkopiert wird (das Journal selbst liegt nur im Projektordner, nicht im
 * Plugin-Zip, der Live-Server hat also sonst keinen Zugriff darauf).
 */
function ftipp_page_changelog() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $entries = ftipp_parse_changelog_md( plugin_dir_path( __FILE__ ) . 'CHANGELOG.md' );
    ?>
    <div class="wrap">
        <h1>📜 Changelog</h1>
        <?php if ( null === $entries ) : ?>
            <p>Kein Changelog gefunden.</p>
        <?php elseif ( empty( $entries ) ) : ?>
            <p>Der Changelog ist derzeit leer.</p>
        <?php else : ?>
            <?php foreach ( $entries as $entry ) : ?>
                <div style="margin-bottom:28px;padding-bottom:20px;border-bottom:1px solid #dcdcde">
                    <h2 style="margin-bottom:6px">
                        v<?php echo esc_html( $entry['version'] ); ?> — <?php echo esc_html( $entry['title'] ); ?>
                    </h2>
                    <?php echo ftipp_changelog_md_to_html( $entry['body'] ); // phpcs:ignore -- bereits in ftipp_changelog_md_to_html() escaped ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Cron-Job-Seite: frei einstellbares Intervall für den automatischen Spieldaten-Abruf (ftipp_weekly_fetch).
 */
function ftipp_page_cron() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $value = intval( get_option( 'ftipp_cron_interval_value', 7 ) );
    $unit  = get_option( 'ftipp_cron_interval_unit', 'days' );
    $next  = wp_next_scheduled( 'ftipp_weekly_fetch' );
    ?>
    <div class="wrap">
        <h1>⏱️ Cron-Job</h1>
        <p>Legt fest, wie oft die Spieldaten automatisch abgerufen werden (OpenLigaDB/API-Football, dieselbe
           Aktion wie der Button „Spieldaten jetzt abrufen" auf der Einstellungen-Seite).</p>

        <?php if ( isset( $_GET['ftipp_cron_done'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Zeitplan gespeichert.</p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="ftipp_save_cron" />
            <?php wp_nonce_field( 'ftipp_save_cron' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Automatisch abrufen alle</th>
                    <td>
                        <input type="number" name="ftipp_cron_interval_value" min="1" step="1" value="<?php echo esc_attr( $value ); ?>" style="width:80px" />
                        <select name="ftipp_cron_interval_unit">
                            <option value="minutes" <?php selected( $unit, 'minutes' ); ?>>Minuten</option>
                            <option value="hours"   <?php selected( $unit, 'hours' ); ?>>Stunden</option>
                            <option value="days"    <?php selected( $unit, 'days' ); ?>>Tage</option>
                        </select>
                        <p class="description">Mindestens 15 Minuten (Sicherheitsuntergrenze gegen zu häufige Anfragen an OpenLigaDB/API-Football).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Speichern' ); ?>
        </form>

        <h2>Status</h2>
        <p><strong>Nächster automatischer Abruf:</strong>
           <?php echo $next ? esc_html( wp_date( 'd.m.Y H:i', $next ) ) : 'nicht geplant'; ?></p>
        <p class="description">Hinweis: WordPress-Cron („WP-Cron") wird durch Seitenaufrufe ausgelöst — bei
           wenig Besucherverkehr kann der Abruf verzögert stattfinden. Bei Bedarf lässt sich stattdessen ein
           externer Cron-Dienst (z. B. cron-job.org) auf <code>wp-cron.php</code> einrichten, der regelmäßig
           aufgerufen wird.</p>
    </div>
    <?php
}
add_action( 'admin_post_ftipp_save_cron', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Keine Berechtigung.' ); }
    check_admin_referer( 'ftipp_save_cron' );

    $value = max( 1, intval( $_POST['ftipp_cron_interval_value'] ?? 7 ) );
    $unit  = in_array( $_POST['ftipp_cron_interval_unit'] ?? 'days', array( 'minutes', 'hours', 'days' ), true )
        ? $_POST['ftipp_cron_interval_unit'] : 'days';
    $unit_seconds = array( 'minutes' => MINUTE_IN_SECONDS, 'hours' => HOUR_IN_SECONDS, 'days' => DAY_IN_SECONDS );
    $seconds = max( 900, $value * $unit_seconds[ $unit ] );

    update_option( 'ftipp_cron_interval_value', $value );
    update_option( 'ftipp_cron_interval_unit', $unit );
    update_option( 'ftipp_cron_interval_seconds', $seconds );

    wp_clear_scheduled_hook( 'ftipp_weekly_fetch' );
    wp_schedule_event( time() + 60, 'ftipp_fetch_custom', 'ftipp_weekly_fetch' );

    wp_safe_redirect( add_query_arg( array( 'page' => 'ftipp_cron', 'ftipp_cron_done' => '1' ), admin_url( 'admin.php' ) ) );
    exit;
} );

/**
 * Info-Seite: kurze Anleitung/Glossar für den Plattform-Admin, rein statisch, kein Formular.
 */
function ftipp_page_info() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    ?>
    <div class="wrap">
        <h1>ℹ️ Info</h1>

        <h2>So funktioniert das Tippen</h2>
        <p>Jedes Spiel kann getippt werden, bis es anpfeift — danach ist die Frist abgelaufen und der Tipp
           gesperrt. Solange ein Tipp nicht aktiv über „Tipp abgeben" bestätigt wurde, ist er editierbar und
           für andere Mitspieler verdeckt; erst nach der Bestätigung wird er fix und sichtbar. Bei K.o.-Spielen
           (Pokal/Europapokal) erscheint zusätzlich ein optionaler <strong>K.o.-Zusatztipp</strong>, sobald der
           reguläre 90-Minuten-Tipp unentschieden lautet: wie die Partie letztlich ausgeht (nach Verlängerung
           oder im Elfmeterschießen) — das bringt Extrapunkte on top.</p>

        <h2>Punktesystem &amp; Modus</h2>
        <p>Grundpunkte: <strong>1 Punkt für die richtige Tendenz</strong> (Sieg/Unentschieden/Niederlage),
           <strong>3 Punkte für das exakte Ergebnis</strong> (keine Tordifferenz-Zwischenstufe). Ein richtiger
           K.o.-Zusatztipp bringt 3 weitere Punkte. Wer ein Spiel bis zur Frist nicht getippt hat, kann pro
           Runde mit einem <strong>Malus</strong> (Punktabzug) belegt werden — Höhe und Ein/Aus legt der
           Runden-Admin fest. Für den Umgang mit Punktgleichstand wählt der Runden-Admin pro Runde einen
           <strong>Modus</strong>: „Freundschaftlich" (geteilter Platz bei Gleichstand) oder „Challenge"
           (harter Tie-Break: mehr exakte Treffer, dann mehr K.o.-Bonuspunkte entscheiden).</p>

        <h2>Sonderwertungen</h2>
        <p>Eine Sonderwertung ist eine saisonlange Wette pro Wettbewerb (z. B. Meister, Torschützenkönig), die
           der Runden-Admin mit eigenen Punkten und eigener Frist anlegt — getrennt von den Spiel-Tipps. Die
           Auflösung läuft standardmäßig über einen automatischen Textvergleich zwischen Tipp und eingetragenem
           Ergebnis (Groß-/Kleinschreibung egal, sonst exakt); der Runden-Admin kann das für einzelne Mitglieder
           manuell übersteuern, falls der Wortlaut nur leicht abweicht (z. B. „St. Pauli" vs. „FC St. Pauli").</p>

        <h2>CSV-Import</h2>
        <p>Für Wettbewerbe ohne zuverlässige kostenlose Quelle (aktuell z. B. Nations League, Süper Lig, Serie A,
           Ligue 1, Ekstraklasa) lässt sich der Spielplan — und später das Ergebnis — per CSV-Datei auf der
           Einstellungen-Seite hochladen. Bereits automatisch geladene Spiele bleiben unangetastet, und ein
           erneuter Upload derselben Begegnung (gleicher Wettbewerb + gleiche Teams + gleiches Datum)
           aktualisiert nur den bestehenden Eintrag, statt ihn zu duplizieren — so gehen keine Tipps verloren.</p>

        <h2>Rollen</h2>
        <p><strong>Plattform-Admin:</strong> der Betreiber der gesamten Tippstube (WordPress-Nutzer mit
           <code>manage_options</code>) — hat für Support-/Notfälle Zugriff auf jede Tipprunde.<br>
           <strong>Runden-Admin:</strong> der Ersteller einer Tipprunde — legt Punktesystem, Malus, Modus und
           Sonderwertungen fest und verwaltet die Mitglieder seiner Runde.<br>
           <strong>Mitspieler:</strong> ein Mitglied einer Tipprunde, das tippt und die Rangliste/Pinnwand dieser
           Runde sieht.</p>

        <h2>Über &amp; Kontakt</h2>
        <p>Tippstube — ein Projekt von <strong>Florian Henschke</strong>.<br>
           Quellcode: <a href="https://github.com/flowtrix2026/tippsiel" target="_blank" rel="noopener noreferrer">github.com/flowtrix2026/tippsiel</a></p>
    </div>
    <?php
}
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
    wp_safe_redirect( add_query_arg( array( 'page' => 'ftipp', 'ftipp_done' => $res['ok'] ? 'ok' : 'err' ), admin_url( 'admin.php' ) ) );
    exit;
} );
add_action( 'admin_post_ftipp_test_reminder', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Keine Berechtigung.' ); }
    check_admin_referer( 'ftipp_test_reminder' );
    ftipp_run_reminder_check();
    wp_safe_redirect( add_query_arg( array( 'page' => 'ftipp', 'ftipp_test' => 'reminder' ), admin_url( 'admin.php' ) ) );
    exit;
} );
add_action( 'admin_post_ftipp_test_newsletter', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Keine Berechtigung.' ); }
    check_admin_referer( 'ftipp_test_newsletter' );
    $n = ftipp_run_newsletter_check( true );
    wp_safe_redirect( add_query_arg( array( 'page' => 'ftipp', 'ftipp_test' => 'newsletter', 'n' => $n ), admin_url( 'admin.php' ) ) );
    exit;
} );

function ftipp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $meta = get_option( 'ftipp_meta', array() );
    ?>
    <div class="wrap">
        <h1>🏠⚽ Tippstube</h1>
        <p><strong>1./2./3. Liga, DFB-Pokal, Champions League, Europa League, Premier League, LaLiga,
           Frauen-Bundesliga und Regionalliga Nordost</strong>
           kommen automatisch über
           <strong>OpenLigaDB</strong> — gratis, ohne Key, immer die <strong>aktuelle Saison</strong>. Bei den
           Pokal-/Europapokal-Wettbewerben gibt es dafür bewusst <strong>keinen K.o.-Zusatztipp</strong> (Verlängerung/Elfmeterschießen)
           mehr — OpenLigaDB kennzeichnet das nicht zuverlässig genug, der normale Tendenz/Exakt-Tipp funktioniert
           aber einwandfrei. Die <strong>Süper Lig, Serie A, Ligue 1</strong> und <strong>Ekstraklasa</strong>
           laufen — wie die Nations League — per <strong>CSV-Import</strong> weiter unten, da es dafür keine
           zuverlässige kostenlose Automatik-Quelle gibt (OpenLigaDB hat diese vier gar nicht in der aktuellen
           Saison, ESPN wird von manchen Servern blockiert). Für <strong>Nations League, Serie A, Ligue 1 und
           Ekstraklasa</strong> versucht das Plugin zusätzlich automatisch <strong>API-Football</strong>, falls
           du dort einen Key hinterlegst. <strong>Hinweis:</strong> der Gratis-Tarif von API-Football deckt
           nur alte Saisons (2021–2023) ab — für die aktuelle Saison ist (noch) ein Bezahltarif nötig, oder du
           nutzt zum Testen „🎲 Test-Spiele laden" weiter unten.</p>

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
                    <th scope="row"><label for="ftipp_season">Saison (API-Football)</label></th>
                    <td><input name="ftipp_season" id="ftipp_season" type="number" value="<?php echo esc_attr( get_option( 'ftipp_season', 2026 ) ); ?>" style="width:110px" />
                        <p class="description">Gilt für alle API-Football-Wettbewerbe (Nations League, Serie A,
                        Ligue 1, Ekstraklasa). Startjahr der Saison. 2026 = Saison 2026/27. Zum Testen mit
                        vollständigen Ergebnissen: 2023.
                        1./2./3. Liga, DFB-Pokal, Champions League, Europa League, Premier League, LaLiga,
                        Frauen-Bundesliga und Regionalliga Nordost laufen unabhängig davon immer auf der
                        aktuellen Saison via OpenLigaDB, die Süper Lig per CSV-Import.</p></td>
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

        <hr>
        <h2 style="margin-top:24px">📄 Spieldaten per CSV importieren</h2>
        <p>Für Wettbewerbe ohne gute kostenlose API (aktuell: <strong>Nations League</strong>,
           <strong>Süper Lig</strong>, <strong>Serie A</strong>, <strong>Ligue 1</strong> und
           <strong>Ekstraklasa</strong> — für Serie A/Ligue 1/Ekstraklasa alternativ auch per
           API-Football, falls du dort einen Bezahltarif mit aktueller Saison hast) kannst du den
           Spielplan (und später die Ergebnisse) selbst per CSV-Datei hochladen. Das <strong>ergänzt</strong> nur —
           bereits automatisch geladene Spiele bleiben unangetastet, und ein erneuter Upload derselben Begegnung
           (gleicher Wettbewerb + gleiche Teams + gleiches Datum) aktualisiert den Eintrag, statt ihn zu
           duplizieren (damit dabei keine Tipps verwaisen).</p>
        <p><strong>Spalten (erste Zeile = Kopfzeile, Komma-getrennt):</strong>
           <code>Wettbewerb,Spieltag,Datum,Heim,Auswaerts,ToreHeim,ToreAusw</code><br>
           <code>Wettbewerb</code> = Kürzel wie <code>NL</code>, <code>CL</code> usw. · <code>Datum</code> im Format
           <code>YYYY-MM-DD HH:MM</code> · <code>ToreHeim</code>/<code>ToreAusw</code> leer lassen, solange das
           Spiel noch nicht gespielt ist — dieselbe Datei mit ausgefülltem Ergebnis später einfach nochmal hochladen.</p>
        <?php if ( isset( $_GET['ftipp_csv'] ) ) : ?>
            <?php if ( 'ok' === $_GET['ftipp_csv'] ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>Import fertig: <?php echo intval( $_GET['added'] ?? 0 ); ?> neu, <?php echo intval( $_GET['updated'] ?? 0 ); ?> aktualisiert,
                       <?php echo intval( $_GET['skipped'] ?? 0 ); ?> übersprungen (fehlerhafte/unvollständige Zeilen).</p>
                </div>
            <?php else : ?>
                <div class="notice notice-error is-dismissible">
                    <p>Import fehlgeschlagen: <?php echo esc_html( isset( $_GET['ftipp_csv_msg'] ) ? rawurldecode( $_GET['ftipp_csv_msg'] ) : 'Unbekannter Fehler.' ); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="ftipp_import_csv" />
            <?php wp_nonce_field( 'ftipp_import_csv' ); ?>
            <input type="file" name="csv_file" accept=".csv,text/csv" required />
            <?php submit_button( '📄 CSV importieren', 'secondary', 'submit', false ); ?>
        </form>

        <?php if ( ! empty( $meta ) && is_array( $meta ) ) : ?>
            <h2>Letzter Abruf</h2>
            <p><strong>Zeitpunkt:</strong> <?php echo isset( $meta['last_fetch'] ) ? esc_html( wp_date( 'd.m.Y H:i', $meta['last_fetch'] ) ) : '—'; ?>
               &nbsp;·&nbsp; <strong>Saison (API-Football):</strong> <?php echo esc_html( isset( $meta['season'] ) ? $meta['season'] : '—' ); ?>
               &nbsp;·&nbsp; <strong>Aktuelle dt. Saison (OpenLigaDB):</strong> <?php echo esc_html( isset( $meta['de_season'] ) ? $meta['de_season'] : '—' ); ?></p>
            <table class="widefat striped" style="max-width:900px">
                <thead><tr><th>Wettbewerb</th><th>Quelle</th><th>Spiele geladen</th><th>davon per CSV</th><th>Hinweis/Fehler</th></tr></thead>
                <tbody>
                <?php $manualAll = get_option( 'ftipp_manual_fixtures', array() ); ?>
                <?php foreach ( ftipp_leagues() as $cid => $lg ) :
                    $c = isset( $meta['counts'][ $cid ] ) ? intval( $meta['counts'][ $cid ] ) : 0;
                    $m = isset( $manualAll[ $cid ] ) ? count( $manualAll[ $cid ] ) : 0;
                    $e = isset( $meta['errors'][ $cid ] ) ? $meta['errors'][ $cid ] : '';
                    $s = isset( $meta['sources'][ $cid ] ) ? $meta['sources'][ $cid ] : '—'; ?>
                    <tr><td><?php echo esc_html( $lg['name'] ); ?></td><td><?php echo esc_html( $s ); ?></td><td><?php echo esc_html( $c ); ?></td><td><?php echo $m ? esc_html( $m ) : '—'; ?></td><td style="color:#b32d2e"><?php echo esc_html( $e ); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="description">"Spiele geladen" zeigt nur den automatischen Abruf — per CSV importierte Spiele
               stehen separat in "davon per CSV" und werden in der App zusätzlich angezeigt (siehe
               <code>ftipp_fixtures_for()</code>), unabhängig davon, ob der automatische Abruf für diesen
               Wettbewerb etwas findet.</p>
        <?php endif; ?>

        <?php $orphaned = ftipp_orphaned_tips(); if ( $orphaned ) : ?>
            <h2 style="color:#b32d2e">⚠️ Verwaiste Tipps gefunden</h2>
            <p>Diese Tipps gehören zu einem Spiel, das in der aktuell geladenen Spielliste nicht mehr unter
               derselben ID vorkommt — meistens, weil sich für den Wettbewerb die Datenquelle geändert hat
               (z.B. DFB-Pokal: früher API-Football, jetzt OpenLigaDB). <strong>Die Daten sind nicht gelöscht</strong>,
               nur nicht mehr automatisch zuordenbar. Bitte mit den Mitspielern abgleichen, zu welchem echten Spiel
               der jeweilige Tipp gehörte, und ihn dort manuell neu eintragen — sofern die Frist des neuen Spiels
               noch nicht abgelaufen ist.</p>
            <table class="widefat striped" style="max-width:900px">
                <thead><tr><th>Wettbewerb</th><th>Spieler</th><th>Getippt</th><th>K.o.-Tipp</th><th>Zuletzt geändert</th><th>Interne Spiel-ID</th></tr></thead>
                <tbody>
                <?php foreach ( $orphaned as $o ) :
                    $lg = ftipp_leagues();
                    $compName = isset( $lg[ $o['comp_id'] ] ) ? $lg[ $o['comp_id'] ]['name'] : $o['comp_id']; ?>
                    <tr>
                        <td><?php echo esc_html( $compName ); ?></td>
                        <td><?php echo esc_html( $o['user_name'] ); ?></td>
                        <td><?php echo ( null !== $o['hg'] && null !== $o['ag'] ) ? esc_html( $o['hg'] . ':' . $o['ag'] ) : '—'; ?><?php echo $o['committed'] ? ' <span style="color:#2e7d32">(fix)</span>' : ''; ?></td>
                        <td><?php echo esc_html( $o['ko_decided'] ? $o['ko_decided'] . ' · ' . $o['ko_winner'] : '—' ); ?></td>
                        <td><?php echo esc_html( $o['updated_at'] ); ?></td>
                        <td><code style="font-size:11px"><?php echo esc_html( $o['fixture_id'] ); ?></code></td>
                    </tr>
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
    $links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=ftipp' ) ) . '">Einstellungen</a>';
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
