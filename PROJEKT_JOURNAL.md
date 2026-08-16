# Fußball-Tippspiel — Projekt-Journal & Spec

> Nachfolger der `WM2026_Tipprunde Kopie.html`. Dieses Dokument hält alle Entscheidungen
> aus der Planungsphase fest und listet offene Aufgaben. Stand: laufende Planung (Grilling).

## Kurzfassung / Vision
Ein Fußball-Tippspiel für **mehrere laufende Wettbewerbe** (nicht nur ein Turnier).
Ziel: das leidige **manuelle Eintragen** aus der WM-Datei loswerden — Spielpläne und
Ergebnisse kommen **automatisch per API**.

## Phasen-Plan
- **Phase 1 — JETZT: lokal/offline auf dem Mac.**
  Self-contained App, voll funktionsfähig, zunächst Single-Admin (du legst Teilnehmer & Tipps/Abos an).
  Erst alles fertigstellen und testen.
- **Phase 2 — SPÄTER (offene Aufgabe): Umbau zu WordPress-Plugin.**
  WordPress-nativ, **kein Supabase**:
  - Login/Registrierung → **WP-Benutzerkonten** (Selbst-Anmeldung der Mitspieler)
  - Datenbank → **eigene WP-Tabellen** (keine separate DB)
  - Zeitgesteuerter API-Abruf → **WP-Cron** (holt Ergebnisse ein paar Mal am Tag serverseitig)
  - Fristen-E-Mails → `wp_mail` und/oder **n8n**
  - API-Key bleibt serverseitig (sicher, nicht im Browser)
- **Mobil/Plattform:** **responsive Webseite** (gut am Handy nutzbar) — **kein PWA, keine native App**.

## Wettbewerbe (alle 6)
1. Bundesliga · 2. Bundesliga · DFB-Pokal · UEFA Champions League · UEFA Europa League · UEFA Conference League

## Teilnahme-Modell (pro Mitspieler wählbar)
- Jeder wählt, an welchen Wettbewerben er mittippt.
- **Regel:** Wer *sowohl* 1. *als auch* 2. Bundesliga tippt → **automatisch DFB-Pokal** dabei.
- CL / EL / Conference einzeln wählbar.
- Phase 1: Admin legt Teilnehmer + Abos an. Phase 2: Selbst-Anmeldung.

## Ranglisten
- **Pro Wettbewerb eine eigene Wertung.** Wer nur DFB-Pokal tippt, taucht nur dort auf.
- Anzeige: **alle Mitspieler des Wettbewerbs** (vollständige Liste), eigene Zeile hervorgehoben.
  *(Update aus Test-Feedback — vorher „Top 3 + eigene Position".)*
- **Keine Gesamt-Rangliste** über alle Wettbewerbe — bewusst nur getrennt pro Wettbewerb (fairste Variante).
- **Runden-Modus (pro Runde, vom Runden-Admin gewählt — Standard: freundschaftlich):**
  - **Freundschaftlich** (Standard): bei Gleichstand **geteilter Platz** („keine Neider, wir spielen zusammen" — z.B. Familie).
  - **Challenge** (optional): harter Tie-Break (mehr exakte Treffer → mehr Tendenzen → sonst Stichtipp), für kompetitive Runden.

## Rollen & Sichtbarkeit (aus Test-Feedback)
- **Super-Admin** vs. **Spieler.** (Lokal per Umschalter simuliert; später WordPress-Benutzerrollen.)
  - Nur Admin: Ergebnisse eintragen, Punktwerte, Sonderwertungen anlegen/Punkte, Teilnehmerverwaltung, Einstellungen.
  - Spieler: nur tippen + eigene Tipps sehen.
- **Tipp-Geheimhaltung:** Fremde Tipps eines Spiels sieht man **erst nach eigenem „Tipp abgeben"** (Tipp wird dann fix)
  — vorher verdeckt. Nach Ablauf der Frist ohnehin sichtbar. Admin sieht alles.

## Login, Tipprunden & Rollen (Phase 2 — jetzt lokal simuliert)
- **Vorgehen:** erst lokal simulieren (Konten/Runden als lokale Daten, „Login" = Konto wählen). Echtes Login/Passwort/E-Mail dann 1:1 in WordPress.
- **Tipprunde (= „Gruppe")** = privater Kreis. Man erstellt eine Runde oder tritt bei. *Innerhalb* der Runde laufen die Wettbewerbe mit je eigener Rangliste — **gewertet nur unter den Mitgliedern dieser Runde**. Man sieht die Mitglieder seiner Runde.
- **3 Rollen-Ebenen:** Plattform-Super-Admin (du — Support/Notfall, kann alles/überall) → **Runden-Admin** (Ersteller: legt Punkte/Sonderwertungen/Fristen SEINER Runde fest, verwaltet Mitglieder) → **Mitspieler** (tippt, sieht eigene Runde).
- **Konto × Runden:** ein Konto kann in **mehreren Runden gleichzeitig** sein. **Wettbewerbs-Auswahl ist global** (gilt in allen Runden gleich) → man tippt ein Spiel **einmal**, es zählt in allen Runden. Unterschiede zwischen Runden: Regeln/Punkte/Sonderwertungen/Mitglieder/Ranglisten.
- **Einstiegs-Regel:** Einem Wettbewerb kann man nur beitreten, **solange er noch nicht begonnen hat** (bis zum ersten Spiel). Jeder Wettbewerb einzeln (z.B. Champions League noch möglich, wenn Bundesliga schon läuft).
- **Runde erstellen/beitreten:** Jeder registrierte Nutzer kann eine Runde erstellen (wird ihr Admin) und teilt einen **Einladungs-Code/-Link**; Beitritt per Code/Link. Runden-Admin kann Mitglieder entfernen.
- **Runden-Individualisierung:** nur **Name** (kein Logo, keine Beschreibung) — bewusst minimal.
- **Login-Methode:** **E-Mail + Passwort** mit Bestätigungs-Mail (WordPress-Benutzerkonten, Passwort-Reset inklusive).
- **Sichtbarkeit in der Runde:** Anzeigename + Rangliste + Einzel-Tipps (erst nach eigener Abgabe). **E-Mail bleibt privat.**
- **Onboarding-Reihenfolge:** immer erst Konto registrieren + bestätigen → danach Runde erstellen/beitreten → danach Wettbewerbe wählen (auch bei Einladungslink).
- *Design-Detail:* Sonderwertungs-Tipps sind pro Runde (vom Runden-Admin definiert).

## Tippen pro Spiel
- Genaues Ergebnis (wie WM). Punkte **pro Wettbewerb konfigurierbar**.
- **Grundpunkte (Default): richtige Tendenz = 1, exaktes Ergebnis = 3.**
  Die 2-Punkte-Stufe "richtige Tordifferenz" aus der WM-Datei ist **raus** (bei Bedarf pro Wettbewerb wieder aktivierbar).
- **K.o.-Spiele:** Tipp fürs Ergebnis nach 90 Min. Tippt man **unentschieden** (z.B. 1:1), gibt es **einen
  optionalen Zusatztipp** — wie die Partie letztlich ausgeht: **Ergebnis nach Verlängerung** *oder*
  **Ausgang im Elfmeterschießen** → **3 Extrapunkte**.
- **Offene K.o.-Paarungen** (Pokal/Europapokal vor der Auslosung): ein Spiel ist **erst tippbar, wenn die Paarung feststeht** (kommt automatisch beim wöchentlichen Refresh). Kein Vorab-Tippen auf Platzhalter.
- **Kein Joker/Multiplier** — Regeln bewusst schlicht gehalten.

## Sonderwertungen (pro Wettbewerb)
- Im Backend **frei anlegbar/änderbar**, jede einzeln **auswählbar**, Punkte einstellbar (Default ~**10**).
- Beispiele: Meister/Sieger je Wettbewerb · Torschützenkönig · **bester Passgeber (Vorlagengeber)** · Mannschaft mit den **meisten roten Karten** · weitere.
- **Auflösung automatisch per API** (Team-rote-Karten via Aggregation aus Spieldaten).
- **Frist pro Sonderwertung:** vom Admin im Backend individuell gesetzt (z.B. Meister früher dicht als Torschützenkönig).

## Datenquelle & Abruf
- ⚠️ **HARTE VORGABE: Die API/Datenquelle darf NICHTS kosten — dauerhaft 0 €.** Keine Bezahltarife,
  keine Bezahl-Features. Wenn etwas Geld kosten würde, wird es anders gebaut oder weggelassen.
- **API-Football (api-sports.io)**, **Gratis-Tarif dauerhaft** (100 Anfragen/Tag, keine Zahlung/keine Kreditkarte).
- Bedarf ~35–40 Anfragen/Woche → weit unter dem Limit (selbst täglicher Abruf bliebe gratis).
- **Fallback (auch gratis):** OpenLigaDB (deutsche Wettbewerbe) + API-Football-Gratis nur für UEFA-Wettbewerbe.
- **Betriebsmodus: Hybrid mit Cache.** Daten werden **~1× pro Woche** gezogen (nach den Spieltagen)
  und lokal in der Datenbank gespeichert. Kein Minuten-Live, kein Abruf alle paar Minuten.
- Dazwischen arbeitet die App **offline mit den zuletzt geladenen Daten** (Tippen geht immer).
- Verschobene/ausgefallene Spiele (Wetter etc.) fängt der nächste wöchentliche Refresh ab.
- Liga-IDs: BL=78 · 2.BL=79 · DFB-Pokal=81 · CL=2 · EL=3 · Conference=848.
- Basis-URL `https://v3.football.api-sports.io`, Header `x-apisports-key`.
- Stats-Endpunkte für Sonderwertungen: `topscorers`, `topassists`, `topredcards`.

## Saison
- **Echtbetrieb mit Saison 2026/27** (API `season=2026`). Saison im Backend **umschaltbar**
  (z.B. 2025/26 laden, um die Auswertung mit fertigen Ergebnissen zu testen).
- Hinweis: UEFA-Spielpläne 2026/27 stehen erst ab ~Ende August komplett fest → Auswertung erst ab dann voll prüfbar.

## Fristen & Malus
- **Frist: 1 Stunde vor Anpfiff** sperrt der Tipp (Standardwert, einstellbar).
- **Malus verpasste Spiele: −1 Punkt** (Option ein-/ausschaltbar, Höhe einstellbar).
- **Alle Punktwerte im Backend konfigurierbar.**

## Admin-Backend (Konfiguration)
- Punktwerte (Grundsystem + Sonderwertungen + Malus)
- Sonderwertungen anlegen/ändern (nur Admin)
- Fristen
- Teilnehmer + Abos
- Manuelle Ergebnis-Korrektur (Override bei API-Fehlern)
- **CSV-Spielplan-Upload** (Alternative zur API, falls kein Key): `Wettbewerb;Runde;Datum;Heim;Gast;Heimtore;Gasttore`.
- **SMTP-Zugang + Newsletter-Zeitplan pro Wettbewerb** hinterlegbar (Host/Port/User/Pass/Absender, Tag+Uhrzeit je Wettbewerb).

## Benachrichtigungen & Kommunikation (alle gewünscht — Optionen/Einstellungen vorbereiten)
- **Fristen-Erinnerung per E-Mail:** an Mitglieder, die vor Frist noch nicht (vollständig) getippt haben.
- **Ranking-Newsletter per E-Mail** pro Wettbewerb (SMTP), zu einstellbaren Zeiten — Führung, größte Sprünge, eigene Platzierung.
- **In-App-Chat/Pinnwand pro Runde** (Shoutbox) — Mitglieder können schreiben/lästern; hält die Runde lebendig.
- **Messenger via n8n** (WhatsApp/Telegram) — Benachrichtigungen zusätzlich über Messenger.
- Für jeden Kanal **Einstellungen/Schalter** vorsehen (an/aus, Zeitpunkt, Empfänger).
- ⚠️ **Echter Versand erst in der WordPress-Phase** (serverseitig, WP-Cron/wp_mail/n8n). Lokal nur konfigurieren/speichern, kein echtes Senden.

## Statistik, Verlauf & Gamification
- ✅ **Persönliche Statistik** (pro Spieler & Wettbewerb): Trefferquote, exakte Tipps, Ø Punkte/Spieltag, beste/schlechteste Spieltage.
- ✅ **Ranglisten-Verlauf** über die Saison: Platzierung je Spieltag (Kurve), Spieltags-Sieger, größte Auf-/Abstiege.
- ✅ **Serien & Achievements (Badges):** Streaks, „5 exakte in Folge", „Spieltags-Sieger" usw.
- ⏳ **Head-to-Head-Vergleich:** nice-to-have, später (mehr Aufwand).

---

## Status
- **V1 lokal gebaut & getestet:** `tippspiel.html` (eine Datei, im Browser öffnen).
- Getestet (Browser, fehlerfrei): Rollen Admin/Spieler, Tipp-Geheimhaltung (abgeben → andere sichtbar, Tipp fix/gesperrt),
  Frist-Sperre, Malus, K.o.-Zusatztipp (+3), Rangliste pro Wettbewerb (alle Mitspieler), Teilnehmer+Abos (DFB-Automatik),
  Sonderwertungen (Admin konfiguriert / Spieler tippt), Ergebnis-Editor, CSV-Upload+Vorlage, SMTP/Newsletter-Konfiguration.
- API-Abruf als Beta (lokal ggf. CORS-blockiert → Phase 2/WordPress serverseitig).

## DSGVO / Datenschutz — harte, durchgehende Vorgabe ⚠️
- **Grundsatz Datensparsamkeit:** nur nötige Daten (E-Mail, Anzeigename, Tipps, Chat). **E-Mail bleibt privat.**
- **Einwilligung & Rechtsgrundlage:** bei Registrierung Zustimmung zu Datenschutzerklärung (Checkbox, protokolliert), klarer Zweck.
- **Betroffenenrechte:** Auskunft, Berichtigung, **Löschung** (Konto löschen entfernt personenbezogene Daten), Datenexport.
- **Auftragsverarbeitung (AVV nötig):** Hosting/WordPress, SMTP-Anbieter, ggf. n8n + WhatsApp/Telegram.
- **Drittland/Transfer:** möglichst **EU-Hosting**. API-Football: es werden **keine personenbezogenen Daten** gesendet (nur Abruf) → unkritisch.
- **Messenger (WhatsApp/Telegram):** nur mit Einwilligung, Datenschutz-Hinweis; Meta ggf. kritisch → **optional/abschaltbar** halten.
- **Chat/Pinnwand:** nutzergenerierte Inhalte → Melde-/Moderationsfunktion, Aufbewahrungsdauer, Löschbarkeit.
- **Uploads (Logo/Emblem):** Hinweis auf Rechte Dritter; keine personenbezogenen Bilder erzwingen.
- **WordPress-Pflichten:** Impressum, Datenschutzerklärung, Cookie-/Consent-Banner (nur nötige Cookies), HTTPS, Passwort-Hashing (WP-Standard).
- **Löschkonzept:** inaktive Konten / alte Runden nach definierter Frist löschen oder anonymisieren.

## WordPress-Test
- **Test-Umgebung:** Test-WordPress bei **Hostinger** (nicht die Live-Seite).
- **Stufe 1 — Schnell-Plugin gebaut:** `fussball-tippspiel.zip` (Shortcode `[tippspiel]`, App isoliert im iframe mit Auto-Höhe).
  Upload via WP-Admin → Plugins → hochladen → aktivieren → Shortcode auf eine Seite. Noch ohne WP-Login/DB (Browser-Speicher).
- **Stufe 1 LÄUFT LIVE ✅** — verifiziert auf `saddlebrown-goldfish-377252.hostingersite.com/start/` (Shortcode auf Seite „Start"). App rendert, Spiele/Rollen/Reveal funktionieren, iframe-Auto-Höhe passt.
- **Stufe 2a — Auto-Datenabruf LÄUFT LIVE ✅ (Plugin v0.2.0):** serverseitiger Abruf via `wp_remote_get` (API-Football), Speicherung in `ftipp_fixtures`, REST `/wp-json/ftipp/v1/data`, WP-Cron wöchentlich. Auf Hostinger verifiziert: Saison 2023 → 308/308/63/214/175/417 Spiele, echte Ergebnisse in der App sichtbar.
- ⚠️ **Freie-API-Grenze bestätigt:** API-Football Gratis-Tarif deckt **nur Saisons 2021–2023**. Aktuelle Saison (2026/27) ist NICHT gratis. → Für Echtbetrieb: **OpenLigaDB** (gratis) für deutsche Wettbewerbe; **UEFA aktuell gratis** noch offen (zu lösen).
- *Kosmetik offen:* Team-Namen englisch (API), Runden-Label „Regular Season - 1"; verwirrenden In-App-„Von API laden"-Button in WP-Version ausblenden.
- **Stufe 2b — GEBAUT (Plugin v0.3.0): echtes WP-Login + Tipprunden + Tipps in der DB.**
  - Neue DB-Tabellen (`ftipp_rounds`, `ftipp_round_members`, `ftipp_round_config`, `ftipp_subs`, `ftipp_tips`, `ftipp_special`, `ftipp_special_tips`).
  - Shortcode zeigt bei Nicht-Login ein `wp_login_form()` + Registrierungs-Link; eingeloggt läuft die App im iframe auf einer same-origin-URL (`?ftipp_app=1`), die serverseitig ein Boot-Objekt (Nutzer, Nonce, REST-URL, Fixtures) injiziert.
  - REST-API `ftipp/v1`: `bootstrap`, `subs`, `rounds` (erstellen/beitreten/config/mode/remove/leave), `tips` (lesen inkl. Tipp-Geheimhaltung server-seitig, speichern, abgeben), `leaderboard` (inkl. Friendly/Challenge-Rangberechnung), `special` (CRUD + Tipp).
  - App bekommt neuen Tab **„👥 Runden"** (Runde erstellen/beitreten per Code, Wettbewerbe global wählen, Regeln pro Runde, Mitglieder verwalten/verlassen). Tabs „Teilnehmer"/„Einstellungen" (API-Key etc.) sind im WP-Modus ausgeblendet (Einstellungen liegen jetzt im WP-Admin).
  - Lokaler Standalone-Modus (Datei im Browser) bleibt **unverändert** funktionsfähig (localStorage-Demo mit Rollen-Simulator) — verifiziert, keine Regression.
  - **LIVE VERIFIZIERT ✅** auf Hostinger: Login-Formular (v0.3.1, neu gestylt), Registrierung, Runden-Tab funktioniert —
    Nutzer "Florian H." ist Runden-Admin der Runde "Henschke" (Code EWTHY2), Wettbewerbs-Checkboxen, Modus-Umschalter,
    "Regeln dieser Runde" alles sichtbar und bedienbar.
  - *Als Nächstes zu prüfen:* Spiel tippen + abgeben (Tipp-Geheimhaltung), Rangliste in der Runde, zweites Konto beitreten lassen per Code.
- **v0.3.2 — Fix „nichts tippbar":** Mit Test-Saison 2023 sind ALLE Spiele in der Vergangenheit → korrekt gesperrt (kein Bug,
  aber blockiert das Testen). Neuer Button in den WP-Einstellungen: **„🎲 Test-Spiele laden"** — erzeugt ein paar Spiele mit
  Anpfiff in den nächsten Tagen (u.a. eines in 30 Min, um die Sperre live zu sehen), unabhängig von der API, für alle 6
  Wettbewerbe inkl. K.o.-Beispielen. Überschreibt `ftipp_fixtures`; per „Spieldaten jetzt abrufen" wieder rückgängig.
- **Tipp-Abgabe LIVE VERIFIZIERT ✅:** Ergebnis eintragen → „Tipp abgeben" → Status „abgegeben ✓", Felder korrekt gesperrt,
  Frist-Sperre bei abgelaufener Deadline korrekt „gesperrt". Kompletter Tippen-Flow läuft gegen die echte DB.
- **Mehrbenutzer-Test LIVE VERIFIZIERT ✅:** Zweitkonto "flowtrix" per Einladungs-Code (EWTHY2) beigetreten, eigene
  Wettbewerbs-Abos pro Person, Rangliste zeigt beide Mitglieder mit korrektem geteiltem Platz.
  Hinweis: "flowtrix" = WP-Hauptkonto (manage_options) → sieht laut Design überall Runden-Admin-Rechte (Plattform-Admin-
  Bypass), auch wenn Florian H. der eigentliche Runden-Ersteller ist. Für einen "normaler Mitspieler ohne Rechte"-Test
  wäre ein drittes, nicht-administratives Konto nötig.
- **Tipp-Geheimhaltung LIVE VERIFIZIERT ✅ zwischen zwei echten Nutzern:** Vorher "Tipps der Mitspieler: —" bei beiden;
  nach eigener Abgabe erscheint sofort der Tipp des anderen (Florian sieht flowtrix' 1:1 nach eigenem 2:2+K.o.-Zusatztipp;
  flowtrix sieht Florians 0:2 nach eigenem 2:1). Serverseitig erzwungen, nicht nur Browser-Simulation.
- **➡️ STUFE 2b VOLLSTÄNDIG VERIFIZIERT:** Login/Registrierung, Runde erstellen/beitreten per Code, globale
  Wettbewerbs-Abos, Tippen inkl. K.o.-Zusatztipp, Abgabe/Sperre, Tipp-Geheimhaltung, Rangliste (inkl. geteiltem Platz)
  — alles live auf Hostinger-WordPress mit echten Nutzern bestätigt. Kompletter Kern-Loop steht.
  - *Bewusst nicht in diesem Schritt:* OpenLigaDB/aktuelle-Saison-Automatik, Statistik/Achievements, In-App-Chat, echter E-Mail-Versand — bleiben eigene Folgeschritte.

## v0.4.0 — Feinschliff + OpenLigaDB + Statistik/Achievements/Chat (alle 3 in einem Rutsch)
- **1. Feinschliff:** Rundenbezeichnungen von API-Football (DFB/CL/EL/ECL) ins Deutsche übersetzt ("Regular Season - 1"
  → "Spieltag 1", "Round of 16" → "Achtelfinale" usw.); DFB-Pokal-Teamnamen eingedeutscht (z.B. "Bayern Munich" →
  "Bayern München").
- **2. OpenLigaDB verifiziert & eingebaut** (echte API geprüft, nicht geraten): `/getmatchdata/{bl1|bl2}/{season}`
  liefert die **aktuelle Saison, gratis, ohne Key**. **1./2. Bundesliga laufen jetzt über OpenLigaDB.**
  **DFB-Pokal bleibt bewusst bei API-Football** — OpenLigaDB kennzeichnet n.V./Elfmeterschießen bei Pokalspielen
  nicht zuverlässig, das hätte den K.o.-Zusatztipp verfälschen können (Korrektheit vor Vollständigkeit).
  Admin-Einstellungsseite zeigt jetzt pro Wettbewerb die **Quelle** an.
- **3. Statistik, Verlauf, Achievements:** neuer Bereich in "🏆 Auswertung" — Trefferquote, Ø Punkte/Spieltag,
  bester/schwächster Spieltag, **Ranglisten-Verlauf als Liniendiagramm** (Inline-SVG, kein externes Chart-Lib),
  Achievements (5 exakte in Folge, nie gekniffen, X× Spieltags-Sieger, 50/100-Punkte-Marke). Alles serverseitig
  berechnet (`/roundstats`).
- **3. Chat/Pinnwand pro Runde:** neue DB-Tabelle `ftipp_chat`, REST `/chat` (GET/POST), aufklappbares Panel in
  jeder Runden-Karte im "👥 Runden"-Tab.
- PHP-Syntax geprüft (klammern-/brace-balanciert in den echten `<?php ?>`-Bereichen), lokaler Standalone-Modus
  weiterhin fehlerfrei (keine Regression).
- **LIVE VERIFIZIERT ✅ auf Hostinger:** BL1/BL2 zeigen jetzt 306 Spiele/Saison über OpenLigaDB (aktuelle Saison
  2026/27), DFB/CL/EL/ECL weiter über API-Football (Quelle-Spalte bestätigt beides). Rundenbezeichnungen für
  DFB-Pokal korrekt eingedeutscht ("1. Runde · 2. Runde · Achtelfinale · Viertelfinale · Halbfinale · Finale"),
  Statistik + Ranglisten-Verlauf-Diagramm + Achievements rendern und rechnen korrekt.
- **v0.4.1 — Bugfix:** "Spieltags-Sieger" zählte bei Gleichstand für ALLE (z.B. wenn niemand tippt, sind alle per
  Malus gleich → jeder bekam die Auszeichnung, entwertete das Achievement). Fix: zählt nur noch bei eindeutigem
  Alleingang (kein geteilter Sieg mehr).
- **Entscheidung:** Gratis-API-Tarif reicht dem Nutzer aus — kein bezahlter API-Football-Plan nötig. DFB-Pokal/CL/EL/ECL
  bleiben auf Test-/2023-Daten, bis ggf. später ein Bezahltarif gewünscht wird.

## v0.5.0 — Design-Feinschliff (App-Optik, Mobile, Login/Registrierung)
- **Farbpalette & Typografie verfeinert:** von generischem Navy/Teal/Gold zu einer durchdachteren, etwas wärmeren
  Palette (Tokens in `:root`), Schatten/Tiefe an Karten, Hover-/Fokus-Zustände an Buttons/Feldern, `tabular-nums`
  für Zahlen-Spalten, größere Tap-Ziele.
- **Mobile-Fix (im Test gefunden):** die 5 Tabs quetschten sich auf schmalen Screens unlesbar ineinander →
  jetzt horizontal wischbare Tab-Leiste (Standard-Mobile-Muster), im Browser-Test auf 375px verifiziert (scrollbar
  bestätigt: 712px Inhalt in 375px Ansicht). Formularfelder auf 16px gesetzt (verhindert iOS-Auto-Zoom beim Fokus).
- **WordPress-Login-/Registrierungsseite (wp-login.php) gestylt** via `login_enqueue_scripts` — dunkles Theme,
  passende Farben statt WP-Standardoptik; betrifft Login/Registrieren/Passwort-vergessen sitejweit.
- **In-App-Login-Kasten** (Shortcode-Ansicht für Ausgeloggte) farblich an neue Palette angeglichen.
- PHP-Klammern/Braces geprüft (balanciert), JS-Syntax geprüft, lokaler Standalone-Modus + Mobile-Ansicht im
  Browser visuell verifiziert (Desktop- und 375px-Screenshot).
- **Mobile-Test auf echtem Handy zeigte altes Layout (2×2-Tab-Kacheln statt wischbarer Leiste) → Ursache: Caching**,
  nicht die neue CSS war fehlerhaft. Der iframe lädt `app/index.html` über eine **statische, unveränderte URL** —
  Handy-/Server-Caches (z.B. LiteSpeed Cache auf Hostinger) können nach einem Plugin-Update weiter die alte
  Version davon ausliefern.
- **v0.5.1 — Cache-Busting-Fix:** iframe-URL bekommt jetzt `&v=<PLUGIN-VERSION>` angehängt → bei jedem Update
  entsteht eine neue URL, alte Caches werden nicht mehr getroffen. Zusätzlich explizite `Cache-Control`/`Pragma`-
  Header neben `nocache_headers()` gesetzt.
- **Separat (keine Plugin-Sache, WP-Konto-Einstellung):** ein Nutzer zeigte statt Namen seine E-Mail-Adresse an
  — liegt an WordPress-Profil "Name öffentlich anzeigen als". Nutzer per Profil-Einstellung selbst behebbar.
- **Cache-Fix LIVE VERIFIZIERT ✅ auf echtem Handy:** Tab-Leiste jetzt korrekt einzeilig/wischbar (2×2-Kacheln waren
  tatsächlich nur Cache, kein CSS-Fehler).
- **Neuer Mobile-Fund:** "Meine Wettbewerbe" zeigte auf dem Handy nackte Checkboxen ohne Beschriftung (Kopfzeile
  BL1/BL2/... wird auf schmalen Screens ausgeblendet, aber die Checkboxen darunter hatten keinen eigenen Bezug mehr).
- **v0.5.2 — Fix:** "Meine Wettbewerbe" komplett umgebaut von der geteilten Kopfzeile-Tabelle zu **einzeln
  beschrifteten Auswahl-Kacheln** ("1. Bundesliga [✓]" usw., als Pille) — funktioniert ohne Kopfzeile auf jeder
  Bildschirmgröße, auch besser lesbar auf Desktop. Nebeneffekt: zeigt auch nicht mehr die (ggf. als E-Mail
  konfigurierte) Namenszeile, die ohnehin redundant war.
- *Hinweis zur QA:* Der Cache-Fix wurde live am Handy geprüft; der neue Chip-Umbau ("Meine Wettbewerbe") ließ sich in
  dieser Session mangels Zugriff auf eine mit WordPress-Login simulierte Vorschau NICHT visuell vorab testen (Tool-
  Grenze) — Code-Review + JS-Syntax-Check bestanden, aber **erste echte Bestätigung steht noch aus.**
- *Noch nicht getestet:* v0.5.2 auf Hostinger hochladen, "👥 Runden" → "Meine Wettbewerbe" auf Mobile UND Desktop
  ansehen.

## v0.6.0 — Echter E-Mail-Versand + DSGVO-Grundausstattung
- **Fristen-Erinnerung (echt, nicht mehr nur UI):** neue Tabelle `ftipp_notified` zum Dedup. Cron alle 15 Min
  (`ftipp_reminder_check`, neues Intervall `ftipp_15min`) prüft alle 6 Wettbewerbe auf Spiele in <3h Anpfiff;
  für jeden betroffenen, noch nicht committeten Nutzer wird **eine gesammelte** Mail mit allen fälligen Spielen
  verschickt (kein Spam pro Einzelspiel), max. 1× pro Spiel&Nutzer dank Dedup-Tabelle.
- **Ranking-Newsletter (echt):** Cron stündlich (`ftipp_newsletter_check`), sendet nur zur konfigurierten
  Wochentag/Uhrzeit-Kombination und max. 1×/Woche (Dedup via `ftipp_newsletter_last_sent`). Pro Runde+Wettbewerb
  mit ≥2 relevanten Mitgliedern: personalisierte Mail mit Top 3 + eigener Platzierung (falls nicht in Top 3).
  Ranglisten-Logik aus dem REST-Endpunkt in `ftipp_compute_leaderboard()` extrahiert und wiederverwendet
  (keine Logik-Duplizierung).
- **Admin-UI:** neuer Bereich "✉️ Benachrichtigungen" in den Plugin-Einstellungen — an/aus-Schalter, Tag/Uhrzeit
  für den Newsletter, plus zwei "jetzt ausführen"-Buttons zum Testen ohne auf den Cron zu warten.
- **DSGVO — Mein Konto:** neue Sektion im "👥 Runden"-Tab — **Daten exportieren** (JSON-Download aller eigenen
  Tipps/Abos/Sonder-Tipps/Runden/Chat-Nachrichten) und **Tippspiel-Daten löschen** (löscht Tipps/Abos/Sonder-Tipps/
  Chat, verlässt alle Runden außer denen, wo man selbst Admin ist — mit klarem Hinweis dazu). Löscht bewusst NICHT
  das WordPress-Konto selbst (zu riskant für einen Selbstbedienungs-Button; das bleibt Admin-Aufgabe).
- **Registrierung:** neue Pflicht-Checkbox "Datenschutzerklärung gelesen" (via `register_form`/`registration_errors`),
  blockiert die Registrierung ohne Zustimmung.
- **Neuer Shortcode `[tippspiel_datenschutz]`:** Textbaustein-Gerüst für eine Datenschutzerklärung mit klar
  markierten `[PLATZHALTERN]` (Name/Anschrift/Hosting/SMTP-Anbieter) — bewusst **kein fertiger Rechtstext**,
  keine Rechtsberatung, muss vom Betreiber ausgefüllt/geprüft werden.
- PHP-Syntax geprüft (string-/kommentar-bewusster Klammern-/Brace-Check: 0 offen, keine negative Tiefe), keine
  doppelten Funktionsdefinitionen, JS-Syntax geprüft.
- *QA-Hinweis:* Live-Browser-Vorschau war in dieser Sitzung durch ein Tool-/Umgebungslimit blockiert (wie schon
  beim Chip-Umbau in v0.5.2) — **nur durch Code-Review + Syntax-Checks abgesichert, noch nicht live getestet.**
- **Live-Screenshot der Registrierungsseite zeigte Bug:** Konsens-Checkbox + Texte rendern korrekt, aber die
  Formular-**Karte selbst blieb weiß** (WP-Standard) statt dunkel — Ursache: `wp-login.php` nutzt für die
  Registrierung ein anderes Formular-Element (`#registerform`) als für Login (`#loginform`); meine CSS-Regel
  hatte nur `#loginform` gestylt.
- **v0.6.1 — Fix:** Karten-Styling jetzt auf `#loginform, #registerform, #lostpasswordform` erweitert (alle drei
  wp-login.php-Ansichten), zusätzlich Absatztext-Farbe, Checkbox-Akzentfarbe und Link-Farbe innerhalb der Karten
  sowie dunkles Styling für WP-Hinweisboxen (`.message`) ergänzt.
- **Neuer Bug gemeldet:** Runde erstellt → Seite aktualisiert → Runde weg ("Noch keine Runde"), obwohl sie in der
  DB existiert (Rundenerstellung selbst schreibt korrekt in `ftipp_rounds` + `ftipp_round_members`).
- **Root Cause (Code-Review):** die App-Seite (`?ftipp_app=1`) enthält einen **personenbezogenen Sicherheits-Code
  (Nonce)**, der bei jedem Aufruf neu generiert werden muss. Wenn ein Server-Cache (z.B. LiteSpeed Cache auf
  Hostinger) diese Antwort einmal zwischenspeichert, bekommen spätere Aufrufe einen **veralteten/falschen Nonce**
  → alle REST-Aufrufe (`/bootstrap`, `/subs`) schlagen mit 403 fehl. Verschärft durch einen zweiten Bug:
  `initConnected()` hat diesen Fehler nur mit `console.error()` **stillschweigend verschluckt** — sah für den
  Nutzer exakt wie "Runde verschwunden" statt "Fehler beim Laden" aus.
- **v0.6.2 — Doppel-Fix:**
  1. `DONOTCACHEPAGE`/`DONOTCACHEOBJECT`-Konstanten + LiteSpeed-„kein Cache"-Signal auf der `?ftipp_app=1`-Route
     gesetzt (breite Unterstützung über gängige Cache-Plugins hinweg), zusätzlich zu den schon vorhandenen
     `nocache_headers()`/`Cache-Control`-Headern.
  2. Lade-Fehler in `initConnected()` werden jetzt **sichtbar** angezeigt (Warnbox + "Seite neu laden"-Button)
     statt lautlos zu leeren Runden zu führen — macht zukünftige Probleme dieser Art sofort erkennbar statt
     wie Datenverlust auszusehen.
- *Noch nicht getestet:* v0.6.2 hochladen, idealerweise **einmal den Hostinger-/Cache-Plugin-Cache manuell leeren**,
  danach erneut Runde erstellen → Seite komplett neu laden → prüfen, ob sie erhalten bleibt. Falls der Fehler
  nochmal auftritt, sollte jetzt zumindest eine sichtbare Fehlermeldung statt "Runde weg" erscheinen.

## v0.7.0 — Rebrand "Tippstube" (Grilling-Session: Design-Feinschliff)
- **Grilling-Sitzung** (Skills `grilling` + `domain-modeling`) zur Frage "richtig fett und geil machen" — Ergebnis:
  Fokus auf Optik/Markenidentität. Neues Glossar `CONTEXT.md` im Projektordner angelegt (Tippstube, Tipprunde,
  Runden-Admin, Plattform-Admin, Mitspieler, Wettbewerb, Abo, Tipp, K.o.-Zusatztipp, Sonderwertung, Modus, Malus).
- **Entscheidungen:** Name **„Tippstube"** (statt generisch „Fußball-Tippspiel"), komplett neue warme
  „Stammtisch"-Optik (nicht nur Akzentfarbe), **Symbol-Wappen** (Schild + Fenster/Haus-Motiv + Fußball),
  Farbwelt **Dunkelgrün (Rasen) + Bernstein/Gold**, markante **Serif-Headline-Schrift** für Überschriften/Logo
  (Fließtext/Buttons bleiben neutral für Lesbarkeit). Umfang: nur das echte Produkt (WP-Plugin/App) — ältere
  PDFs/Pitch-Webseite bleiben vorerst unverändert.
- **Logo-Iteration:** erster Entwurf (Ball mit 5 radialen Speichen) sah aus wie ein Kompass/Rad — korrigiert zu
  einem klar lesbaren Fußball (Zentral-Pentagon + 2 Nähte statt Speichen). Fenstersteg lief anfangs ÜBER dem Ball
  (Fadenkreuz-Optik) — Reihenfolge korrigiert (Ball vor dem Steg = "durchs Fenster schauen"-Effekt). Finale Version:
  generisches Wappen ohne Personennamen (Banner-Idee mit Namen verworfen — Nutzer wollte Produkt-Branding bewusst
  allgemein halten, DSGVO/Datenschutz-Angaben bleiben ebenfalls bewusst als Platzhalter, da es ein privates/internes
  Tippspiel ist und keine öffentlichen Kontaktdaten preisgegeben werden sollen).
- **Umgesetzt:**
  - Vollständige Farbpaletten-Migration (22 Hex-Werte in der App, 35 im Plugin, plus rgba-Glows/Gradients)
    von Navy/Türkis auf Rasengrün/Bernstein — systematisch per Skript gemappt, dabei einen **echten Bug gefunden
    und behoben**: die automatische Ersetzung hätte zwei Diagrammfarben im Ranglisten-Verlauf-Chart auf denselben
    Wert gemappt (zwei Mitspieler-Linien nicht mehr unterscheidbar) — korrigiert, kategoriale Chart-Farben bewusst
    NICHT ans Marken-Schema angeglichen (Unterscheidbarkeit geht vor Markenkonsistenz bei Datenvisualisierung).
    Auch einen übersehenen Alt-Farbwert aus der Zeit vor der v0.5-Design-Politur gefunden (Chart-Nulllinie).
  - Wappen als Inline-SVG eingebaut: App-Header, In-App-Login-Karte, Browser-Tab-Icon (Favicon — nur auf der
    Tippstube-Seite selbst sowie auf wp-login.php, nicht sitejweit).
  - Umbenennung überall: Plugin-Header (Name/Description/Author), `<title>`, App-Header, wp-admin-Menü/Überschrift,
    Login-Seiten-Wortmarke, `login_headertext`-Filter, Datenschutz-Textbaustein-Überschrift, Cron-Job-Anzeigename.
    Interner Ordner-/Datei-Slug (`fussball-tippspiel`) und WP-Text-Domain bewusst UNVERÄNDERT gelassen (Update-
    Kontinuität — WordPress identifiziert Plugins über den Datei-Pfad, nicht den Anzeigenamen).
  - Headline-Serif-Schrift (Georgia-Stack) auf Header-H1, Kartenüberschriften (`.phase-h`), In-App-Login-Titel
    und Login-Seiten-Wortmarke angewendet.
  - PHP-Syntax geprüft (balanciert), keine doppelten Funktionsdefinitionen, JS-Syntax geprüft, **visuell im
    Browser verifiziert** (Desktop- und Mobile-Screenshot, Standalone-Modus) — Optik rendert wie geplant,
    keine Regression.
- **LIVE VERIFIZIERT ✅ auf Hostinger:** Rebrand komplett sichtbar (Wappen im Header, "Tippstube", Rasengrün/Gold,
  Serif-Headline bei "1. Spieltag"), Runden-Auswahl (Henschke) und Wettbewerbs-Tabs funktionieren im neuen Look.
- **Feedback:** Datum/Uhrzeit unter jedem Spiel (z.B. "Fr., 28.08., 20:30") war im gedämpften Grau-Grün zu
  unauffällig — sollte kräftiges Weiß sein.
- **v0.7.1 — Fix:** `.mno` (Datum/Uhrzeit-Zeile je Spiel) von `var(--muted)` auf reines Weiß (`#ffffff`) + etwas
  fetter (600) gestellt, für bessere Lesbarkeit/Kontrast.
- *Aufklärung für Nutzer:* Plugin vs. Theme geklärt — das Plugin verändert NUR die Tippspiel-Seite selbst plus
  wp-login.php (inkl. Favicon dort), der Rest der WordPress-Seite (Divi-Theme, andere Seiten) bleibt unberührt.
- **LIVE VERIFIZIERT ✅:** Datum/Uhrzeit jetzt kräftig weiß — Kontrast-Fix bestätigt.
- **Kritischer Bug gemeldet:** Ergebnis bei Heim-Team eingetragen, beim Wechsel ins Auswärts-Feld verschwinden
  die gerade eingegebenen Ziffern sofort wieder.
- **Root Cause gefunden (Code-Review):** `mkMatchRowConnected` speicherte Tipps nur per `onchange` (feuert erst
  beim Verlassen des Feldes) und rief danach — nach einem kompletten Server-Roundtrip — `renderTippenConnected()`
  auf, was **die gesamte Spieleliste inkl. aller Eingabefelder neu aufbaut** (inklusive einem erneuten
  `api_get('tips?...')`-Fetch). Tippte man währenddessen im zweiten Feld weiter (sehr wahrscheinlich, da man
  direkt nach dem Verlassen des ersten Felds dorthin wechselt), zerstörte der asynchrone Neuaufbau die noch nicht
  gespeicherte Eingabe — klassische Race Condition zwischen Nutzereingabe und Server-Roundtrip-Rerender.
- **Fix (Patch-Version):**
  - Eingabefelder auf `oninput` umgestellt — Wert wird **sofort bei jedem Tastendruck** ins lokale Tipp-Objekt
    übernommen (kein Datenverlust mehr möglich, unabhängig vom Netzwerk-Timing).
  - Speichern zum Server läuft jetzt **entkoppelt & debounced** (600ms nach letzter Eingabe) im Hintergrund,
    **ohne** danach die komplette Liste neu zu laden/aufzubauen.
  - Der K.o.-Zusatztipp-Kasten (erscheint bei unentschiedenem Tipp) wird bei Bedarf nur **lokal und synchron**
    ein-/ausgeblendet (kein Netzwerk-Roundtrip nötig, kein Race-Risiko) — die restliche Liste bleibt unangetastet.
  - "Tipp abgeben" (Commit) und K.o.-Auswahlfelder bleiben bewusst wie gehabt (diskrete Klick-Aktionen ohne
    Tipp-Race-Risiko).
  - JS-Syntax geprüft.
- *Noch nicht live getestet:* neue Patch-Version hochladen, Ergebnis eintragen (beide Felder nacheinander,
  zügig tippen) und prüfen, dass nichts mehr verschwindet.
- **Nachfrage (Live-Test):** "Nur der die Gruppe leitet, kann die Sonderauswertung hinzufügen, richtig?" →
  bestätigt: Sonderwertungen anlegen/bearbeiten/löschen darf ausschließlich der Runden-Admin (`ftipp_is_round_admin`),
  Mitspieler können nur ihren eigenen Tipp abgeben.
- **v0.7.3 — Vorbelegte Standard-Sonderwertungen:** Anschlussfrage: "Können wir schon vorher Sachen anlegen, die
  schon da sind, ... wir legen jetzt für die erste Bundesliga an, wer wird Herbstmeister und Meister? Der ist aber
  immer drin, wenn man den nicht haben will, muss man ihn selber löschen." → gebaut als **einmalige automatische
  Vorbelegung pro Runde+Wettbewerb**, KEIN manueller Button nötig:
  - Neue Funktion `ftipp_default_specials($comp_id)`: Standard-Sonderwertungen je Wettbewerb — BL1: "Deutscher
    Meister" + **"Herbstmeister"** (neuer Bet-Typ, Tabellenführer zur Winterpause — rein kosmetisches Label, die
    Auflösung läuft wie bei allen Sonderwertungen über manuelle Admin-Eingabe im Ergebnis-Feld, keine automatische
    Tabellen-Berechnung). BL2/DFB/CL/EL/ECL bekommen je einen "Sieger/Meister"-Eintrag als sinnvollen Default.
  - Neue Funktion `ftipp_seed_default_specials($round_id, $comp_id)`, aufgerufen bei jedem `GET /special`-Abruf:
    prüft ein neues `seeded_specials`-Flag in `ftipp_round_config` (DB-Version 3→4, automatische Migration über
    den bestehenden `plugins_loaded`-Hook). Ist das Flag noch nicht gesetzt, werden die Default-Einträge einmalig
    per `INSERT` angelegt und das Flag dauerhaft auf 1 gesetzt — **egal ob danach gelöscht wird, es kommt nie
    wieder** (Flag verhindert erneutes Seeding, genau wie vom Nutzer gewünscht: "muss man ihn selber löschen").
  - Gilt automatisch auch für die bereits bestehende Testrunde "Henschke" (Seeding hängt am ersten Tab-Aufruf,
    nicht an der Rundenerstellung — kein rückwirkendes Migrations-Skript nötig).
  - Frontend: `BET_TYPES` um `herbstmeister`-Label ergänzt (nur für den Demo-/Standalone-Modus-Editor relevant —
    im WordPress-Modus zeigt die Sonderwertungs-Karte ohnehin nur Label/Punkte/Frist/Ergebnis, kein Typ-Feld).
  - PHP-Klammerbalance gegen letzten funktionierenden Stand (v0.7.2) geprüft (identischer Kontrollwert, da lokal
    kein PHP-Interpreter verfügbar war), JS-Syntax per `node --check` geprüft.
  - *Noch nicht live getestet:* Sonderwertungen-Tab für "Henschke" → 1. Bundesliga öffnen, prüfen dass "Deutscher
    Meister" und "Herbstmeister" automatisch erscheinen.

## v0.8.0 — Vollständige Standard-Sonderwertungen + Nations League statt Conference League
- **Grilling-Sitzung** (`/grill-with-docs`, Skills `grilling` + `domain-modeling`) auf Wunsch: "Wir sammeln jetzt
  mal alle Sondersachen, die schon vorher eingestellt sind" — vollständige, durchdachte Liste an
  Standard-Sonderwertungen für alle Wettbewerbe, statt nur des einen Platzhalter-Eintrags aus v0.7.3.
- **Zwischenzeitlich Scope-Erweiterung:** Nutzer wollte während der Sitzung zusätzlich die **Conference League
  komplett entfernen** und durch die **UEFA Nations League** ersetzen ("hier sind ja wieder die Länderspiele").
  Vorher recherchiert (WebSearch) und bestätigt: Nations League ist bei API-Football verfügbar (Liga-ID 5),
  unterliegt aber derselben bereits akzeptierten Gratis-Tarif-Einschränkung (nur Test-Saisons, keine aktuelle
  Saison kostenlos) wie DFB-Pokal/CL/EL schon heute — kein neues Problem, nur derselbe bekannte Kompromiss.
  Reihenfolge auf Wunsch des Nutzers: erst Sonderwertungen für die 5 bestehenden Wettbewerbe grillen, dann
  eigene Runde für die Nations League (Struktur/Punkte/Fristen/Sonderwertungen) — der Nutzer ist während der
  Bestätigungsfrage aber direkt selbst in die Nations-League-Details eingestiegen, deswegen in einem Rutsch
  weitergemacht und am Ende alles zusammen umgesetzt.
- **Entscheidungen (Sonderwertungen 5 Wettbewerbe):**
  - Standardliste **unterschiedlich je Wettbewerbstyp** (Liga vs. Pokal vs. Europapokal), kein Einheitstemplate.
  - **BL1/BL2** (identisches Set): Meister (BL1: "Deutscher Meister", BL2: "2.-Liga-Meister"), Herbstmeister,
    **Absteiger** (neu), **Torschützenkönig** (neu), **Bester Passgeber** (neu) — je 10 Punkte.
  - **DFB-Pokal**: nur Pokalsieger (Finalist/"erster Bundesligist raus" bewusst weggelassen — zu unscharf/selten).
  - **Champions League / Europa League**: nur Sieger (kein "bestes deutsches Team" — Nutzer wollte es schlank).
  - Punkte einheitlich **10** für alle (keine Prestige-Staffelung — Admin kann manuell anpassen).
  - **Neu: automatische Frist** "Kickoff 1. Spieltag/Runde" für alle Standard-Sonderwertungen (vorher gar keine
    Frist → Risiko, dass jemand mit Tabellen-Vorwissen spät noch tippt).
  - **Rückwirkend nachrüsten**: bereits bestehende Runden (z.B. "Henschke") bekommen die neuen Kategorien beim
    nächsten Tab-Aufruf automatisch ergänzt, ohne bereits gelöschte/vorhandene Einträge zu verdoppeln.
- **Entscheidungen (Nations League):** Struktur/Punkte auf ausdrücklichen Nutzerwunsch:
  - **Alle Divisionen/Gruppen** tippbar (nicht nur Deutschlands Gruppe) — gegen meine Empfehlung, aber klarer
    Nutzerwunsch.
  - Normale Spieltipps **genauso gewertet wie überall** (Tendenz 1 / Exakt 3) — keine Sonderpunkte pro Spiel.
  - K.o.-Zusatztipp aktiv bei Halbfinale/Finale (`kind: 'cup'`, wie DFB/CL/EL) — Nutzer wollte explizit bestätigt
    haben, dass der Haupt-Tipp überall nur bis Minute 90 geht und Verlängerung/Elfmeterschießen ausschließlich
    über den separaten K.o.-Zusatztipp laufen — bereits so implementiert, nichts geändert.
  - **Sonderwertungen:** "Gruppensieger vorher tippen" = **5 Punkte je Gruppe** (alle ~16 Gruppen automatisch,
    nicht nur Deutschlands), "Torschützenkönig" = **10 Punkte** (ein gesamter, nicht je Division),
    "Nations-League-Sieger" = **15 Punkte** (höher gewichtet als der Rest, weil prestigeträchtigster Titel).
    Kein Auf-/Abstieg als Sonderwertung (zu unscharf bei mehreren Divisionen gleichzeitig).
  - Frist Gruppensieger: **individuell pro Gruppe** (Kickoff des ersten Spiels genau dieser Gruppe, nicht ein
    pauschaler Termin für alle).
- **Technisch umgesetzt:**
  - `ftipp_leagues()`: `ECL` raus, `NL` (Nations League, API-Football-Liga-ID 5, `kind: 'cup'`) rein — betrifft
    Wettbewerbsliste überall (Abos, Punkte-Konfiguration, Datenabruf), nicht nur Sonderwertungen.
  - **Sonderwertungen-Engine grundlegend umgebaut**: von einem einzelnen Ja/Nein-Flag (`seeded_specials`) auf
    eine **Liste stabiler Keys** (`seeded_special_keys`, neue Spalte in `ftipp_round_config`, DB-Version 4→5) —
    jede Standard-Kategorie hat einen eigenen Key, nur noch-nicht-vergebene Keys werden neu angelegt. Damit
    können künftige Erweiterungen (wie diese hier) automatisch bei bestehenden Runden nachgerüstet werden, ohne
    bereits gelöschte Einträge wiederzubeleben. **Migration eingebaut**: Runden, die noch das alte v0.7.3-Flag
    gesetzt haben, werden beim ersten Aufruf so behandelt, als wären "champion"+"herbstmeister" (BL1/BL2) bzw.
    "champion" (Rest) schon vergeben — verhindert Duplikate bei "Henschke".
  - **Automatische Frist-Berechnung**: neue Helfer `ftipp_earliest_kickoff()` (frühester Anstoß einer Spielliste)
    und `ftipp_deadline_from_ts()`. `ftipp_translate_round()` um das Muster `League [ABCD] - N` → `Liga X,
    Spieltag N` ergänzt (nötig, damit die Gruppenerkennung die Ligaphase erkennt).
  - **Neue Gruppenerkennung `ftipp_nl_groups()`**: die Nations-League-Spieldaten von API-Football enthalten
    keine feste Gruppen-ID, nur "Liga A - Spieltag N". Gruppen werden deshalb rein aus den tatsächlichen
    Begegnungen abgeleitet — Union-Find über den "hat gegen gespielt"-Graphen der Ligaphase-Spiele. Jede erkannte
    Gruppe bekommt einen stabilen Key (Hash der sortierten Team-Namen) und ein lesbares Label ("Gruppensieger
    Liga A – Gruppe 1 (Team · Team · Team · Team)"). Funktioniert erst, sobald echte NL-Spieldaten geladen sind —
    vorher werden nur Sieger+Torschützenkönig gesetzt, die Gruppensieger-Einträge kommen automatisch nach, sobald
    Spieldaten vorliegen (derselbe Nachrüst-Mechanismus wie oben).
  - **Demo-/Testdaten** (`ftipp_load_test_fixtures`, JS-Standalone-Demo): `ECL`-Block durch `NL`-Block mit 3
    Testspielen ersetzt (Deutschland/Ungarn/Niederlande/Bosnien und Herzegowina), so verbunden, dass die
    Gruppenerkennung eine einzelne 4-Team-Gruppe liefert — manuell durchgerechnet (Union-Find von Hand
    nachvollzogen), damit die Logik ohne lokalen PHP-Interpreter zumindest auf dem Papier verifiziert ist.
  - Alle Text-Referenzen auf "Conference League"/"ECL" durchsucht und ersetzt (Plugin-Beschreibung, Einstellungs-
    Seite, Kommentare, App-Untertitel, Datenquellen-Hinweis) — vollständige Sweep-Suche am Ende bestätigt: keine
    Reste mehr.
  - Glossar (`CONTEXT.md`) um **Standard-Sonderwertung** und **Nations-League-Gruppe** ergänzt.
  - PHP-Klammerbalance gegen letzten funktionierenden Stand geprüft (identischer Kontrollwert), keine doppelten
    Funktionsdefinitionen, JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update einspielen, "🎲 Test-Spiele laden" nutzen, prüfen dass bei "Henschke"
  → 1. Bundesliga die drei neuen Kategorien (Absteiger/Torschützenkönig/Passgeber) automatisch dazukommen, und
  dass bei Nations League nach dem Testdaten-Laden eine Gruppensieger-Sonderwertung für die Deutschland-Gruppe
  automatisch erscheint.
- **Live-Test gemeldet:** Bayern gegen Stuttgart 1:1 getippt (beide Felder ausgefüllt, sichtbar korrekt befüllt),
  „Tipp abgeben" bleibt trotzdem ausgegraut, Hinweistext zeigt weiter "Erst beide Felder ausfüllen."
- **v0.8.1 — Fix (Folgefehler aus dem v0.7.2-Race-Condition-Fix):** `mkMatchRowConnected` aktualisierte beim
  Tippen zwar korrekt den lokalen Zustand + debounced den Server-Save (das hat v0.7.2 schon behoben), aber der
  „Tipp abgeben"-Button (disabled/enabled) und der Hinweistext darunter wurden **nur noch beim Neuaufbau von
  K.o.-Spielen** aktualisiert (Nebeneffekt der K.o.-Box-Toggle-Logik) — bei normalen Liga-Spielen (kein K.o.,
  z.B. jedes BL1-Spiel) gab es dafür gar keinen Auslöser mehr. Der Button blieb dadurch dauerhaft auf dem
  Anfangszustand (leere Felder → disabled) eingefroren, unabhängig davon, was man eintippte.
  - **Fix:** `commitBtn`/`commitHint`-Referenzen vorgemerkt, `onScoreInput` aktualisiert sie jetzt bei jedem
    Tastendruck **synchron und lokal** (nur `disabled`-Attribut + Hinweistext ändern, kein DOM-Neuaufbau) — löst
    das Problem, ohne die Race Condition aus v0.7.2 wieder einzuführen.
  - Betraf nur den WordPress-verbundenen Modus (`mkMatchRowConnected`); der lokale Standalone-Demo-Modus
    (`mkMatchRow`) baut bei jeder Eingabe ohnehin komplett neu auf (synchron, kein Netzwerk-Risiko) und war nie
    betroffen.
  - JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update einspielen, 1:1 (oder beliebiges Ergebnis) bei einem BL1-Spiel
  eintippen, prüfen dass „Tipp abgeben" sofort anklickbar wird.
- **Live-Test gemeldet:** Button wird jetzt korrekt anklickbar (v0.8.1 bestätigt), aber Klick auf „Tipp abgeben"
  „lockt" den Tipp nicht ein — beim ersten Spiel passiert nichts Sichtbares, beim zweiten (Leipzig–Gladbach)
  springt die Seite nach oben und das gerade eingetragene Ergebnis ist wieder weg.
- **v0.8.2 — Fix (weiterer Folgefehler aus v0.7.2):** `btn.onclick` von „Tipp abgeben" rief `POST tip/commit`
  **sofort** auf, ohne auf das debounced (600ms verzögerte) Speichern der zuletzt eingetippten Werte zu warten.
  Klickt man schnell nach dem Tippen — was durch den v0.8.1-Fix (Button wird jetzt sofort aktiv) noch
  wahrscheinlicher wurde — kommt die Bestätigung beim Server an, bevor der eigentliche Tipp dort gespeichert ist;
  das Backend lehnt "Commit ohne vollständigen Tipp" ab (`incomplete`-Fehler), und der anschließende Neuaufbau
  der Liste zeigt wieder den alten (leeren) Stand.
  - **Fix:** `btn.onclick` bricht jetzt zuerst das laufende Debounce-Timeout ab und **wartet den Save explizit
    ab** (`await persistNow()`), bevor die Commit-Anfrage rausgeht — der zuletzt eingetippte Wert ist dadurch
    garantiert beim Server angekommen, unabhängig vom Timing des Klicks.
  - JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update einspielen, Ergebnis eintippen und **sofort** (ohne zu warten) auf
  „Tipp abgeben" klicken — Tipp sollte zuverlässig fix werden, auch bei sehr schnellem Klick.
- **Live-Test bestätigt ✅:** Bayern-Stuttgart und Leipzig-Gladbach beide korrekt fix, „abgegeben ✓"-Pill sichtbar.

## v0.8.3 — Tipp nach Abgabe noch bearbeiten können
- **Anfrage:** "Können wir auch einen Button einbauen, wenn man sich vertippt hat, dass man dann nochmal tippen
  kann?" — kurz gegengefragt, weil das an der Tipp-Geheimhaltung rührt (mit Abgabe sieht man sofort die Tipps
  der anderen; freie Nachbearbeitung hebelt den "erst eigenen Tipp, dann erst die anderen sehen"-Schutz
  faktisch aus). Nutzer hat sich bewusst für **frei bearbeitbar bis zur Frist** entschieden (statt Zeitfenster
  oder Admin-only-Freigabe) — für die private Runde kein Problem.
- **Umgesetzt:**
  - Neuer **"✏️ Bearbeiten"**-Button (Ghost-Style) neben "✅ abgegeben — Tipp ist fix." — aktiviert einen
    Editier-Modus, der die Eingabefelder trotz `committed=true` wieder freischaltet (bisher fest deaktiviert).
  - Änderungen im Editier-Modus werden wie beim ursprünglichen Tippen live+debounced gespeichert (derselbe
    `oninput`-Mechanismus wie schon seit v0.7.2), kein erneutes "Tipp abgeben" nötig — Commit-Status bleibt
    unangetastet, nur Ergebnis/K.o.-Tipp werden aktualisiert. **"✅ Fertig"**-Button erzwingt einen letzten Save
    und schließt den Editier-Modus.
  - **Backend-Sperre entfernt**: `POST /tip` lehnte Änderungen an bereits abgegebenen (`committed=1`) Tipps
    bisher explizit ab — das war die Sperre, die den neuen Button erst nötig macht zu umgehen. Der **Frist-Check
    darüber bleibt unverändert bestehen** und regelt "bis zur Frist" jetzt ganz von selbst, keine doppelte Logik
    nötig.
  - Kleiner Fix nebenbei gefunden: die K.o.-Zusatztipp-Box löst bei Unentschieden-Wechsel einen Teil-Neuaufbau
    der Zeile aus (bestehender Mechanismus aus v0.7.2) — dabei wurde der `editing`-Zustand nicht mitgegeben, hätte
    mitten im Bearbeiten die Felder wieder gesperrt. Beim Umbau direkt mit gefixt (`editing` wird jetzt
    durchgereicht).
  - PHP-Klammerbalance geprüft (identischer Kontrollwert), JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* einen bereits abgegebenen Tipp über "✏️ Bearbeiten" ändern, prüfen dass der neue
  Wert nach "✅ Fertig" bei den Mitspielern sichtbar ankommt und der Tipp weiterhin als "fix" zählt.
- **Feedback (Screenshot-Vergleich):** Registrierungsseite (wp-login.php) hat den vollen dunkelgrünen
  Tippstube-Hintergrund — sieht "cooler" aus. Die In-App-Login-Karte (Shortcode-Seite, nicht eingeloggt) hat
  dagegen einen weißen Hintergrund hinter dem dunklen Kasten — "ungleichmäßig", sieht laut Nutzer schlecht aus.
- **v0.8.4 — Fix:** Root Cause: `.ftipp-auth` (der Login-Kasten) war schon immer dunkelgrün gestylt, aber der
  äußere `.ftipp-auth-wrap` hatte nie einen eigenen Hintergrund — dahinter schien einfach die normale
  WordPress-Seite (Divi-Theme, standardmäßig weiß) durch. Anders als bei wp-login.php, wo das Plugin die
  komplette Login-Seite übernimmt, wird hier bewusst NUR eine Komponente innerhalb einer normalen Seite
  gerendert (siehe "Plugin vs. Theme"-Prinzip aus v0.7.0).
  - **Fix:** neue `.ftipp-auth-page-bg`-Hülle um den Login-Kasten, volle Breite (`calc(50%-50vw)`-Trick, bricht
    aus dem Seiten-Container aus ohne `<body>` selbst anzufassen) mit dem App-Hintergrundton `#0b160f` +
    großzügigem vertikalem Padding — wirkt dadurch wie ein durchgehendes dunkles Band, genau wie bei
    wp-login.php, **ohne** Header/Footer des restlichen Divi-Themes zu berühren (Prinzip bleibt gewahrt: nur der
    Tippstube-Bereich selbst wird dunkel, nicht die ganze Seite).
  - Nur den Login-Prompt betroffen (`ftipp_render_shortcode()`, nicht eingeloggter Zustand) — der eigentliche
    App-Inhalt (iframe nach Login) war nie betroffen, der hat sein eigenes dunkles Grundlayout.
  - PHP-Klammerbalance + `<div>`-Tag-Zählung manuell geprüft (kein lokaler PHP-Interpreter verfügbar).
- *Noch nicht live getestet:* Plugin-Update einspielen, ausgeloggt die Tippspiel-Seite aufrufen, prüfen dass der
  Login-Kasten jetzt auf einem durchgehenden dunkelgrünen Band statt weißem Hintergrund sitzt — inkl. mobiler
  Ansicht (`calc(50%-50vw)`-Trick kann in manchen verschachtelten Divi-Layouts mit `overflow:hidden` clippen,
  im Zweifel nochmal gegenprüfen).
- **Feedback:** In der Rangliste erscheint die volle E-Mail-Adresse statt des gewünschten Spitznamens "Floh" —
  Screenshots zeigen: Spitzname im WP-Profil ist zwar eingetragen, aber
  "Öffentlicher Name" (Dropdown) steht weiterhin auf der E-Mail. Nutzer merkt zu Recht an, das sei
  "DSGVO-schlecht" — die Registrierungs-Einwilligung nennt ausdrücklich nur den **Anzeigenamen**, nicht die
  E-Mail-Adresse, als das, was andere Mitspieler sehen.
- **v0.8.5 — Fix (Datenschutz, nicht nur Kosmetik):** statt sich darauf zu verlassen, dass jedes Konto seinen
  WordPress-"Öffentlichen Namen" korrekt konfiguriert (fehleranfällig, siehe genau dieser Fall — betrifft
  typischerweise vorbestehende/manuell angelegte Konten wie das ursprüngliche Admin-Konto, bei denen der
  Benutzername zufällig die E-Mail-Adresse ist), jetzt eine harte Absicherung direkt im Plugin:
  - Neue Funktion `ftipp_public_name( $user )`: liefert `display_name`, **außer** der sieht wie eine
    E-Mail-Adresse aus (`is_email()`) — dann Fallback auf das `nickname`-Usermeta-Feld (auch das nur, wenn
    keine E-Mail), sonst zuletzt `"Spieler #<ID>"`. Die E-Mail-Adresse wird dadurch **nie** an andere
    Mitspieler ausgegeben, unabhängig davon, wie das Konto entstanden ist oder ob das Profil korrekt gepflegt
    wurde.
  - Angewendet an den zwei Stellen, an denen Namen **anderen Mitspielern** gezeigt werden: `ftipp_round_members()`
    (Basis für Rangliste + Ranking-Newsletter-E-Mail, da beide darüber laufen) und die Chat/Pinnwand-Auflösung
    (`GET /chat`). **Bewusst NICHT** an den Stellen geändert, wo der Name nur der eigenen Person selbst gezeigt
    wird (E-Mail-Begrüßung "Hallo [dein Name]," an sich selbst, Datenexport der eigenen Kontodaten, App-Bootstrap
    "wer bin ich") — dort ist die rohe `display_name` unproblematisch, weil niemand sonst sie sieht.
  - PHP-Klammerbalance geprüft (identischer Kontrollwert), keine doppelten Funktionsdefinitionen.
- *Noch nicht live getestet:* Plugin-Update einspielen, Rangliste neu laden — sollte jetzt "Spieler #&lt;ID&gt;"
  statt der E-Mail-Adresse zeigen (bis der "Öffentliche Name" im WP-Profil korrekt auf "Floh" gestellt wird,
  dann erscheint automatisch "Floh").
- **Live-Test:** aus Versehen die Runde "Henschke" zweimal angelegt (zwei verschiedene Einladungs-Codes). Drei
  Fragen/Wünsche: (1) eine Runde löschen können, falls falsch angelegt — gab es in der UI bisher gar nicht; (2)
  ob "Meine Tippspiel-Daten löschen" (Mein Konto) "für den ganzen Wettbewerb" geht; (3) einen Runden-weiten
  JSON-Export, sobald alle getippt haben, als Backup falls "irgendwas kaputt geht".
- **Antwort zu (2):** "Meine Tippspiel-Daten löschen" ist **kontoweit**, nicht auf einen Wettbewerb oder eine
  Runde beschränkt — löscht Tipps/Abos/Sonder-Tipps/Pinnwand-Nachrichten über ALLE Wettbewerbe hinweg und
  entfernt aus allen Runden, in denen man NICHT Admin ist (Admin-Runden bleiben bewusst bestehen, um andere
  Mitglieder nicht zu beschädigen). War schon immer so, hier nur erklärt — kein Code geändert.
- **v0.8.6 — Zwei neue Features (1) + (3):**
  - **Runde löschen** (nur Runden-Admin): neuer `🗑️ Runde löschen`-Button in der Runden-Karte, mit
    Bestätigungsdialog. Neuer `DELETE /rounds/{id}`-Endpunkt + gemeinsame Helper-Funktion
    `ftipp_delete_round()`, die vollständig kaskadiert (Mitgliedschaften, Regeln/`round_config`,
    Sonderwertungen + deren Tipps, Pinnwand-Nachrichten, dann die Runde selbst) — **anders als die bisherige
    "Verlassen"-Logik**, die bei Admin+alleine zwar auch die Runde löschte, aber `round_config`/`special`/
    `special_tips`/`chat`-Zeilen verwaist zurückließ (Datenhygiene-Bug nebenbei gefunden und mit demselben
    Helper gefixt, `/leave` nutzt jetzt denselben kaskadierenden Code). Löschen funktioniert bewusst auch bei
    mehreren Mitgliedern (anders als "Verlassen", das den Admin blockiert, solange andere da sind) — der
    Runden-Admin soll seine eigene Runde jederzeit komplett auflösen können, mit deutlicher Warnung im
    Bestätigungsdialog ("alle Mitglieder verlieren den Zugriff").
  - **Runden-Export als JSON** (nur Runden-Admin): neuer `⬇️ Rundendaten exportieren (JSON)`-Button, neuer
    `GET /rounds/{id}/export`-Endpunkt — liefert Rundendaten (Name/Code/Modus), Mitgliederliste, Regeln je
    Wettbewerb, aktuelle Ranglisten, alle Spieltipps + Sonderwertungen-Tipps der Mitglieder (nur für
    Wettbewerbe, die sie abonniert haben) und die Pinnwand-Chronik — als Backup/Exportmöglichkeit, unabhängig
    vom bereits bestehenden rein-persönlichen "Meine Daten exportieren".
  - PHP-Klammerbalance geprüft (identischer Kontrollwert), JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update einspielen, eine der beiden doppelten "Henschke"-Runden über
  "🗑️ Runde löschen" entfernen (Bestätigungsdialog beachten!), bei der verbleibenden Runde einmal
  "⬇️ Rundendaten exportieren" testen und die heruntergeladene JSON-Datei stichprobenartig prüfen.
- **Feedback:** Login-Kasten (nicht angemeldet) hat trotz v0.8.4-Fix immer noch weißen Hintergrund statt des
  dunklen Bands — Registrierungsseite (wp-login.php) sieht dagegen weiterhin korrekt aus.
- **v0.8.7 — Root Cause (Cache, nicht der CSS-Fix selbst):** dieselbe Kategorie Bug wie schon einmal zuvor
  (siehe v0.4.x/v0.5.x "Stale-Cache"-Fix für die App-Seite) — die Login-Prompt-Ansicht (`[tippspiel]`-Shortcode
  für nicht angemeldete Besucher) ist eine ganz normale WordPress-Seite und wurde bislang **nicht** von der
  Cache-Ausnahme erfasst, die nur für die eigentliche App-Seite (`?ftipp_app=1`, via `template_redirect`)
  galt. LiteSpeed Cache liefert nach jedem Plugin-Update also weiter die alte, vor dem v0.8.4-Fix gecachte
  Seite aus, bis der Cache abläuft oder manuell geleert wird — der CSS-Fix selbst war korrekt, kam nur nie an.
  - **Fix:** dieselben Cache-Ausnahme-Direktiven (`DONOTCACHEPAGE`, `DONOTCACHEOBJECT`,
    `litespeed_control_set_nocache`), die die App-Seite schon nutzt, jetzt auch am Anfang des
    nicht-angemeldet-Zweigs von `ftipp_render_shortcode()` gesetzt — verhindert, dass dieser Bug bei
    künftigen Updates wiederkehrt.
  - **Wichtig:** das verhindert nur *künftiges* Stale-Caching — der schon bestehende alte Cache-Eintrag muss
    einmalig manuell geleert werden (Hostinger-Cache-Button oder LiteSpeed-Cache-Plugin "Purge All"), damit
    der v0.8.4-Fix überhaupt sichtbar wird.
  - PHP-Klammerbalance geprüft (identischer Kontrollwert).
- *Noch nicht live getestet:* Plugin-Update einspielen, **Cache einmal manuell leeren**, dann ausgeloggt die
  Tippspiel-Seite neu aufrufen — Login-Kasten sollte jetzt auf dunklem Band sitzen, genau wie die
  Registrierungsseite.
- **Anfrage:** "Wenn man sich angemeldet hat, soll man automatisch auf die Tippspielseite kommen, ohne dass
  man in WordPress rumlungert" — nach dem Login über die eigentliche wp-login.php (z.B. Direktaufruf, Lesezeichen,
  "Passwort vergessen"-Flow) landete man bisher im WordPress-Standard-Ziel (wp-admin-Dashboard bzw. Profilseite
  bei Nicht-Admins), nicht auf der Tippstube. Das In-App-Login-Formular selbst war davon nicht betroffen (nutzt
  schon `wp_login_form(['redirect'=>get_permalink()])`, führt also schon korrekt zur aktuellen Seite zurück).
- **v0.8.8 — Fix:** neuer `login_redirect`-Filter — leitet nach jedem erfolgreichen Login standardmäßig auf die
  Seite mit dem `[tippspiel]`-Shortcode weiter (nutzt den schon bestehenden Helfer `ftipp_app_url()`, der genau
  diese Seite anhand ihres Post-Contents findet). Ein bereits explizit angefordertes Redirect-Ziel
  (`requested_redirect_to`, z.B. vom In-App-Formular) hat weiterhin Vorrang, wird also nicht überschrieben. Gilt
  bewusst auch für den Plattform-Admin (keine Sonderbehandlung) — die Admin-Werkzeugleiste oben bleibt als
  Schnellzugriff ins wp-admin trotzdem verfügbar, falls doch mal nötig.
  - PHP-Klammerbalance geprüft (identischer Kontrollwert), keine doppelten Funktionsdefinitionen.
- *Noch nicht live getestet:* Plugin-Update einspielen, ausloggen, über wp-login.php (nicht über den
  In-App-Kasten) neu einloggen — sollte direkt auf der Tippstube-Seite landen statt im wp-admin.
- **Feedback:** Mobile-Reiterleiste (Tippen/Auswertung/Runden/Sonderwertungen) erfordert seitliches Wischen,
  um alle Reiter zu erreichen — ohne jeden Hinweis, dass mehr da ist (Scrollbar bewusst versteckt). "Das muss
  anders sein."
- **v0.8.9 — Fix:** Mobile-Reiterleiste von horizontal scrollendem Streifen (`overflow-x:auto`, versteckte
  Scrollbar) auf ein **2-Spalten-Raster** umgestellt (`flex-wrap:wrap`, je Reiter `flex:1 1 calc(50% - 3px)`)
  — bei den 4 Reitern im WordPress-Modus ergibt das ein sauberes 2×2-Raster, alle auf einen Blick sichtbar,
  kein Wischen mehr nötig. Visuell im Browser geprüft (Mobile-Viewport 375×812, Standalone-Modus mit 5 Reitern
  zeigt 2+2+1 — funktioniert auch bei ungerader Anzahl).
- **LIVE VERIFIZIERT ✅ (visuell, Standalone):** 2×2/2+2+1-Raster rendert wie geplant, keine abgeschnittenen
  Labels, keine Überlappung.
- *Noch nicht live getestet auf Hostinger:* Plugin-Update einspielen, auf echtem Handy (oder Chrome-Mobile-
  Emulation) prüfen, dass alle 4 Reiter ohne Wischen erreichbar sind.
- **Feedback (zwei Bugs):** (1) Runden "zicken rum" — nach dem Löschen einer der doppelten "Henschke"-Runden
  taucht sie manchmal wieder auf, oder die andere verschwindet zwischenzeitlich. (2) Auf dem Handy speichert
  "Tipp abgeben" nicht — Button "leuchtet einmal kurz auf", dann passiert nichts.
- **v0.8.10 — Root Cause (dritter Fall derselben Cache-Kategorie) + Fix:** beide Symptome passen exakt zum
  bereits zweimal aufgetretenen Stale-Cache-Muster (siehe v0.8.7, v0.4.x) — zwei bisher nicht abgedeckte
  Lücken gefunden:
  - **Die äußere, eingeloggte Tippstube-Seite** (die Seite, die den `<iframe>` mit der eigentlichen App
    einbettet) hatte noch KEINE Cache-Ausnahme — nur die separate `?ftipp_app=1`-App-Seite selbst (via
    `template_redirect`) und seit v0.8.7 der Login-Prompt für nicht angemeldete Besucher. Wird diese äußere
    Seite gecacht, bleibt die darin eingebettete `?v=<Version>`-Iframe-URL auf einer alten Versionsnummer
    hängen → die eingebettete App lädt dauerhaft alten, längst ausgelieferten Code (erklärt das Handy-Symptom:
    der Button-Klick-Handler war schlicht eine alte Version ohne die v0.8.2-Race-Condition-Fixes).
  - **Die REST-API-Antworten selbst** (`/wp-json/ftipp/v1/...` — Runden-Liste, Tipp speichern, Rangliste, …)
    hatten keine expliziten No-Cache-Header. Ein zwischengeschalteter Cache (LiteSpeed, ggf. CDN) konnte daher
    theoretisch eine veraltete `GET /rounds`-Antwort ausliefern (erklärt das Runden-Symptom: gelöschte Runde
    "kommt wieder", weil eine alte Liste erneut ausgeliefert wird, obwohl die Datenbank längst aktuell ist).
  - **Fix:** Cache-Ausnahme-Direktiven (`DONOTCACHEPAGE`, `DONOTCACHEOBJECT`, `litespeed_control_set_nocache`)
    jetzt an den Anfang von `ftipp_render_shortcode()` gezogen, gelten dadurch für BEIDE Zweige (Login-Prompt
    UND eingeloggte App-Einbettung), statt nur für den Login-Zweig. Zusätzlich neuer
    `rest_pre_serve_request`-Filter, der für JEDE `/ftipp/v1/`-Route `nocache_headers()` +
    `DONOTCACHEPAGE` + das LiteSpeed-No-Cache-Signal setzt — deckt jetzt lückenlos: Login-Seite,
    App-Einbettungs-Seite, App-Seite selbst, UND alle REST-Endpunkte.
  - **Wichtig, wie schon bei v0.8.7:** behebt nur *künftiges* Stale-Caching. Der schon bestehende alte
    Cache-Eintrag der äußeren Seite muss einmalig manuell geleert werden.
  - PHP-Klammerbalance geprüft (identischer Kontrollwert).
- *Noch nicht live getestet:* Plugin-Update einspielen, **Cache einmal manuell komplett leeren**, dann: eine
  der beiden "Henschke"-Runden löschen und mehrfach neu laden (sollte dauerhaft weg bleiben), sowie auf dem
  Handy ein Ergebnis eintippen und "Tipp abgeben" testen (sollte jetzt zuverlässig fix werden).
- **Feedback:** Login-Kasten sitzt jetzt korrekt auf dem dunklen Band (Cache-Fix wirkt), aber das Band füllt
  nicht die ganze Bildschirmhöhe — oben und vor allem unten bleibt noch der weiße Theme-Hintergrund sichtbar.
- **v0.8.11 — Fix:** `.ftipp-auth-page-bg` bekommt `min-height:100vh` (statt nur schmalem `padding:6vh`) und
  zentriert den Login-Kasten darin vertikal (`display:flex;align-items:center;justify-content:center`) — das
  Band füllt dadurch mindestens die komplette sichtbare Bildschirmhöhe. Ein schmaler weißer Streifen ganz oben
  könnte dennoch von der Divi-Seite selbst kommen (z.B. Seiten-Padding vor dem Shortcode-Modul) — das liegt
  außerhalb dessen, was das Plugin beeinflusst (Prinzip "nur der Tippstube-Bereich wird gestylt"); falls das
  noch stört, müsste es direkt im Divi-Seiten-Editor angepasst werden.
  - PHP-Klammerbalance geprüft (identischer Kontrollwert).
- *Noch nicht live getestet:* Plugin-Update einspielen (Cache diesmal nicht zwingend nötig zu leeren, da rein
  optische CSS-Änderung ohne Funktionsbezug, aber schadet nicht), ausgeloggt die Tippspiel-Seite ansehen.
- **Feedback (Screenshot, eingeloggt am Handy):** derselbe weiße Rand jetzt auch bei der eingeloggten Ansicht
  (eingebettetes App-Fenster) sichtbar — oben UND seitlich, nicht nur bei der Login-Ansicht.
- **Feedback (zweiter Screenshot, mobil):** nach "Tipp abgeben" "verrutscht" das Layout — die "abgegeben
  ✓"-Pille links neben dem Vereinsnamen kollidiert/überlappt mit dem Team-Namen (sichtbar bei "Bayern München",
  "Leipzig", "Mainz 05"), weil unten ohnehin schon "✅ abgegeben — Tipp ist fix." + "✏️ Bearbeiten" stehen —
  Dopplung soll weg.
- **v0.8.12 — Zwei Fixes:**
  - **Weißer Rand bei eingeloggter Ansicht:** dieselbe Behandlung wie beim Login-Kasten (v0.8.4/v0.8.11) jetzt
    auch um das eingebettete App-`<iframe>` gelegt — volles dunkles Band (`calc(50%-50vw)`-Breakout,
    Hintergrundton `#0b160f`) statt durchscheinendem weißen Divi-Seiten-Hintergrund rund um den Shortcode.
    War bisher nur für den Login-Prompt gemacht worden, nicht für die eigentliche eingeloggte Ansicht.
  - **Doppelte "abgegeben"-Pille entfernt:** in `mkMatchRowConnected` (WordPress-Modus) UND `mkMatchRow`
    (Standalone-Demo-Modus, aus Konsistenzgründen mit gefixt) zeigt die linke Info-Spalte bei einem bereits
    abgegebenen Tipp jetzt keine "abgegeben ✓"-Pille mehr (nur noch das Datum) — die Information steht bereits
    redundant und unmissverständlich im "✅ abgegeben — Tipp ist fix."-Text direkt unter dem Spiel. Behebt die
    Text-Überlappung bei längeren Vereinsnamen auf schmalen Bildschirmen.
  - JS-Syntax per `node --check` geprüft, PHP-Klammerbalance geprüft (identischer Kontrollwert).
- *Noch nicht live getestet:* Plugin-Update einspielen, eingeloggt auf dem Handy prüfen dass (a) kein weißer
  Rand mehr um das App-Fenster sichtbar ist und (b) nach "Tipp abgeben" keine Text-Überlappung mehr auftritt.

## OFFENE AUFGABEN / TODO
- [x] ~~Phase 2 / Stufe 2: echtes WordPress-Plugin~~ → fertig, live verifiziert (siehe oben).
- [x] ~~E-Mail-Versand (Fristen/Newsletter)~~ → v0.6.0, noch nicht live getestet.
- [x] ~~DSGVO-Grundausstattung~~ → v0.6.0 (Export/Löschung/Consent/Datenschutz-Textbaustein), noch nicht live getestet.
      *Weiterhin offen (Betreiber-Aufgabe, nicht code-lösbar):* echten Namen/Anschrift in Datenschutzerklärung eintragen,
      EU-Hosting-Frage klären, AVV mit Hostinger/SMTP-Anbieter falls nötig.
- [ ] Messenger/n8n-Benachrichtigung (optional, niedrige Prio, weiterhin zurückgestellt)
- [x] ~~Offline-Modus~~ → Hybrid: wöchentlicher Auto-Refresh + lokaler Cache.
- [x] ~~Gesamt-Rangliste~~ → nein, nur pro Wettbewerb.
- [x] ~~Sonderwertungs-Fristen~~ → Admin setzt pro Sonderwertung.
- [x] ~~Saison~~ → 2026/27 Echtbetrieb, umschaltbar.
- [x] ~~Basis-Punktesystem~~ → Tendenz 1 / exakt 3 (keine Tordifferenz-Stufe), pro Wettbewerb einstellbar. K.o.-Zusatztipp = 3 Punkte.
- [x] ~~Anzahl Teilnehmer~~ → gelöst durch offene Selbst-Registrierung + Beitritt per Einladungscode, keine feste Zahl nötig.
