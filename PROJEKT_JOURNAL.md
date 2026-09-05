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

## Projekt auf GitHub veröffentlicht
- **Repo:** [github.com/flowtrix2026/tippsiel](https://github.com/flowtrix2026/tippsiel) (öffentlich).
- **Vor dem Push geprüft:** keine hartcodierten API-Keys/Secrets im Code (echter API-Key liegt ausschließlich
  in der WordPress-Datenbank auf Hostinger, nie im Code). Zwei echte E-Mail-Adressen im Journal gefunden und
  entfernt, bevor irgendwas hochging — Nutzer hatte explizit nach "keine echten Nutzerdaten/Zugangsdaten"
  gefragt. Lokale Claude-Code-Konfiguration (`.claude/`) per `.gitignore` ausgeschlossen (lokale Rechte-
  Freigaben mit Pfaden, gehört nicht ins Repo).
  - Für den Push auf den `flowtrix2026`-GitHub-Account gewechselt (der vorher aktive Account `kubus-concept`
    hatte nur Lesezugriff auf das Zielrepo).
- **Initial-Commit (v0.8.12):** Plugin-Code, App, Journal, Glossar (`CONTEXT.md`), Design-Unterlagen (PDFs).
- **Zukunftsplan (Notiz):** Nutzer will Tippstube perspektivisch als kostenloses Downloadprojekt auf seiner
  Firmenwebseite zeigen (eigene Unterseite, Download über GitHub oder direkt von der Seite) — noch nicht
  begonnen, kommt später.
- **Hinweis:** künftige Plugin-Updates werden NICHT automatisch auf GitHub gepusht — nur auf explizite
  Anfrage, damit kein Push ungefragt passiert.

## v0.8.13 — Wettbewerbe der Runde auf einen Blick
- **Anfrage:** Neue Testrunde "Fam" angelegt, Nutzer fragte: "Sollte da nicht drinstehen, welche Wettbewerbe
  dabei sind?" — die Runden-Karte zeigte bisher nur Einladungs-Code + Mitgliederliste, nicht welche der 6
  Wettbewerbe für diese Runde überhaupt relevant sind (das ergibt sich implizit daraus, welche Mitglieder
  welche Wettbewerbe global abonniert haben — "Wettbewerbs-Abo" ist pro Nutzer, nicht pro Runde).
- **Umgesetzt:**
  - Neue Server-Funktion `ftipp_round_active_comps()`: prüft für jeden der 6 Wettbewerbe, ob mindestens ein
    Mitglied der Runde ihn abonniert hat — als neues Feld `active_comps` im Runden-Objekt (`ftipp_round_object()`).
  - Neue Zeile in der Runden-Karte: "Wettbewerbe in dieser Runde: 1. Bundesliga · DFB-Pokal · …" direkt unter
    Einladungs-Code/Mitglieder. Zeigt einen Hinweistext, falls noch niemand einen Wettbewerb abonniert hat.
  - JS-Syntax per `node --check` geprüft, PHP-Klammerbalance geprüft (identischer Kontrollwert).
- *Noch nicht live getestet:* Plugin-Update einspielen, Runden-Karte "Fam" ansehen — sollte jetzt die
  abonnierten Wettbewerbe der Mitglieder auflisten.

## v0.8.14 — Wettbewerbs-Abo von global auf pro Runde umgestellt
- **Anfrage:** "Wenn ich eine Gruppe mit BL1/DFB/CL/NL habe, aber ein eingeladener Mitspieler will nur BL1 und
  DFB-Pokal mitmachen — wie geht das?" Antwort war zunächst: geht schon, über die globale "Meine
  Wettbewerbe"-Auswahl — aber die gilt kontoweit, nicht pro Runde. Nutzer fand das unpraktisch: "Ich möchte mit
  meinen Eltern spielen, die spielen 1./2. Bundesliga, ich will da aber nur 2. Bundesliga mitspielen" — mit
  mehreren Runden mit unterschiedlichem Wettbewerbs-Wunsch reicht eine globale Einstellung nicht. Vor dem Umbau
  kurz nachgefragt, ob die globale Auswahl komplett ersetzt oder als Vorauswahl erhalten bleiben soll — Nutzer:
  **komplett ersetzen**, einfacher zu verstehen.
- **Größter Umbau bisher an der Kernlogik.** Wettbewerbs-Abo (`Abo`) ist jetzt **pro Tipprunde**, nicht mehr
  global fürs Konto — Glossar (`CONTEXT.md`) entsprechend aktualisiert. Wichtig dabei: der **Tipp selbst**
  (die getippte Torzahl) bleibt weiterhin global pro Nutzer+Wettbewerb gespeichert — nur ob ein Wettbewerb in
  einer bestimmten Runde überhaupt "zählt", ist jetzt pro Runde einstellbar. Ist man mit demselben Wettbewerb
  in zwei Runden dabei, ist es also weiterhin derselbe Tipp in beiden — nur die Teilnahme selbst ist getrennt.
- **Datenbank:** neue Tabelle `ftipp_round_subs` (round_id, user_id, comp_id, active), DB-Version 5→6. Alte
  `ftipp_subs`-Tabelle bleibt bestehen (nur noch als Migrationsquelle, nicht mehr aktiv genutzt).
  **Einmalige Migration** (`ftipp_migrate_round_subs()`, Flag in `wp_options`): für jede bestehende
  Rundenmitgliedschaft werden die bisherigen globalen Abos als Startwert in die neue Tabelle übernommen — damit
  niemandem beim Update plötzlich ein Wettbewerb aus seinen Runden verschwindet.
- **Backend:** `ftipp_effective_subs($user_id)` ersetzt durch `ftipp_round_subs($round_id, $user_id)` (inkl.
  DFB-Automatik, unverändert: 1.+2. Bundesliga zusammen → automatisch DFB-Pokal). Alle Aufrufstellen
  durchgegangen und umgestellt: Rangliste (`ftipp_compute_leaderboard`), Runden-Export, Statistik/Achievements
  (`/roundstats`), Ranking-Newsletter. Für die **Fristen-Erinnerung** (läuft pro Nutzer, nicht pro Runde, weil
  Tipps ja rundenübergreifend derselbe Wert sind) neue Funktion `ftipp_user_active_comps()`: "ist dieser
  Wettbewerb in IRGENDEINER Runde des Nutzers aktiv" — reicht dafür, da die Erinnerung nur "hast du getippt"
  prüft, unabhängig davon, für welche Runde es zählt.
  - `/subs`-REST-Routen (GET/POST) ersetzt durch `/rounds/{id}/subs` — jedes Rundenmitglied darf seine eigene
    Teilnahme für diese eine Runde setzen (nicht nur der Admin).
  - `account/export`: `wettbewerbs_abos` zeigt jetzt pro Runde, welche Wettbewerbe abonniert sind (statt
    einer flachen globalen Liste).
  - `account/delete`: löscht Wettbewerbs-Abos jetzt nur noch für Runden, in denen der Nutzer NICHT Admin ist
    (analog zur bereits bestehenden Logik bei `round_members`) — Admin-Runden bleiben unangetastet.
- **Frontend:** globale "⚽ Meine Wettbewerbe"-Karte im Runden-Tab komplett entfernt. Stattdessen: in **jeder
  einzelnen Runden-Karte** eine neue Sektion "Meine Wettbewerbe in „[Rundenname]"" mit denselben Auswahl-Kacheln
  wie vorher, aber jetzt pro Runde gespeichert (`POST rounds/{id}/subs`). Die "Tippen"-Ansicht filtert die
  Wettbewerbs-Auswahl jetzt aus `round.my_subs` der aktuell gewählten Runde statt aus einem globalen Zustand —
  wechselt man die Runde, ändert sich die Wettbewerbsliste entsprechend mit.
  - Globaler JS-Zustand `MY_SUBS`/`myEffectiveSubs()` komplett entfernt, `GET bootstrap`-Aufruf für Abos
    entfällt (jedes Runden-Objekt liefert jetzt sein eigenes `my_subs`-Feld gleich mit).
  - JS-Syntax per `node --check` geprüft, PHP-Klammerbalance geprüft (identischer Kontrollwert), keine
    doppelten Funktionsdefinitionen, keine verbliebenen Referenzen auf die alte globale Logik (per Sweep-Suche
    bestätigt).
- *Noch nicht live getestet:* Plugin-Update einspielen (Datenbank-Migration läuft automatisch), prüfen dass
  bestehende Runden ("Fam", "TEST" etc.) ihre bisherigen Wettbewerbe weiterhin zeigen (Migration gegriffen),
  dann in einer Runde testweise einen Wettbewerb ab-/anhaken und prüfen, dass eine ANDERE Runde davon
  unberührt bleibt.
- **Kritischer Vorfall gemeldet:** "Jetzt geht gar nichts mehr" — die Tippstube-Seite zeigte WordPress' eigene
  "Keine Ergebnisse gefunden"-Themenseite (404/Suche-Template) statt der App, Seite auch im wp-admin nicht
  mehr bearbeitbar.
- **Root-Cause-Suche:** erstmals **echtes PHP lokal installiert** (`brew install php`, vorher nie verfügbar,
  Prüfungen liefen bisher nur über einen selbstgebauten Klammern-Zähler) — `php -l` bestätigte: **keine
  Syntax-Fehler**, zusätzlicher Cross-Check bestätigte auch keine Aufrufe nicht-existierender Funktionen. Der
  entscheidende Test: Nutzer hat das Plugin **deaktiviert** — die Original-Seite blieb trotzdem kaputt. Das
  beweist zweifelsfrei: **nicht das Plugin war die Ursache**, sondern etwas an der einen WordPress-Seite selbst
  (Datenbank/Permalink/Hosting-seitig) — außerhalb dessen, was der Plugin-Code beeinflusst.
  - **Workaround:** neue WordPress-Seite mit `[tippspiel]`-Shortcode angelegt, Plugin wieder aktiviert — läuft
    seitdem einwandfrei. Die alte kaputte Seite bleibt ungenutzt liegen (kein Datenverlust, da alle Tippstube-
    Daten in eigenen DB-Tabellen liegen, nicht am WordPress-Post selbst hängen).
  - **Lehre für künftige Fehlerdiagnosen:** ab jetzt bei Verdacht auf einen Plugin-Bug immer zuerst `php -l`
    lokal laufen lassen (jetzt möglich, PHP ist installiert) statt nur die bisherige Klammernbalance-Heuristik
    — echte Syntaxprüfung statt Annäherung.
- **Vollständiger Testdurchlauf der v0.8.14-Checkliste (7 Punkte) — alle bestanden ✅:** Migration, Pro-Runde-
  Trennung, neue Runde manuell einstellbar, Tippen-Tab reagiert auf Rundenwechsel, Rangliste korrekt gefiltert,
  Runden-Export funktioniert, Konto-Export zeigt Abos jetzt pro Runde.

## v0.8.15 — Runden-Import (Backup-Wiederherstellung)
- **Anfrage im Anschluss an den Export-Test:** "der die Gruppe erstellt hat, müsste dann noch [...]
  Gruppendaten importieren [können]" — Gegenfrage geklärt: Backup-Wiederherstellung (nicht Vorlage für neue
  Saison).
- **Umgesetzt:**
  - `GET /rounds/{id}/export` liefert jetzt zusätzlich pro Mitglied dessen Wettbewerbs-Abos in dieser Runde
    (`abos`) mit — fehlte vorher, wäre für eine vollständige Wiederherstellung nötig gewesen.
  - Neuer Endpunkt `POST /rounds/import`: legt aus einer hochgeladenen Export-JSON-Datei eine **komplett neue**
    Runde an (überschreibt nie eine bestehende) — mit Regeln, Mitgliedern + deren Abos, Sonderwertungen samt
    Tipps, Spieltipps und Pinnwand-Chronik. Mitglieder, deren WordPress-Konto nicht mehr existiert, werden
    übersprungen und in der Antwort gemeldet (`skipped_members`), statt den Import scheitern zu lassen.
  - Neuer Button "⬆️ Rundendaten importieren (JSON)" direkt unter "Runde erstellen oder beitreten" — liest die
    Datei lokal im Browser (`FileReader`), keine Zwischenspeicherung nötig.
  - Ranglisten werden bewusst NICHT importiert (sind reine Berechnungsergebnisse aus Tipps+Spieldaten, kein
    Quelldatum — berechnen sich nach dem Import automatisch neu).
  - PHP-Syntax jetzt **wirklich** mit `php -l` geprüft (nicht mehr nur die Klammernbalance-Heuristik).
- *Noch nicht live getestet:* eine bestehende Runde exportieren, über den neuen Import-Button wieder einspielen
  und prüfen, dass die neue Runde Regeln/Mitglieder/Sonderwertungen/Tipps korrekt übernimmt.

## v0.8.16 — "Tipps der Mitspieler" bei vielen Mitgliedern einklappbar
- **Anfrage:** "Tipps der Mitspieler" zeigt alle Mitspieler-Tipps direkt inline an — bei aktuell wenigen
  Mitgliedern unproblematisch, aber Nutzer fragte vorausschauend: bei z.B. 50 Mitspielern müsste man sonst bei
  jedem einzelnen Spiel durch eine riesige Liste scrollen. Wunsch: ab einer gewissen Anzahl hinter einem
  "+"-Ausklapper verstecken.
- **Umgesetzt:** ab mehr als 5 Mitspieler-Tipps werden nur noch die ersten 5 direkt angezeigt, der Rest steckt
  hinter einem `<details>`-Ausklapper ("+N weitere anzeigen") — bei 5 oder weniger bleibt es wie bisher, keine
  Verhaltensänderung. In beiden Rendering-Pfaden umgesetzt: `mkMatchRowConnected` (WordPress-Modus) und
  `mkOthers` (Standalone-Demo-Modus, aus Konsistenzgründen mitgemacht).
  - JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* bräuchte eigentlich >5 Mitspieler in einer Runde zum echten Testen — mit aktuell
  wenigen Mitgliedern bleibt die Liste ohnehin unter dem Limit und sieht unverändert aus (kein Regressionsrisiko
  für die bestehende Anzeige).
- **Frage:** "API zieht die DFB-Pokal-Spiele nicht" — Screenshot zeigte "Saison"-Feld auf "0" statt einer echten
  Jahreszahl, dadurch 0 Spiele für alle vier API-Football-Wettbewerbe. Direkt behoben (Saison auf 2023 stellen).
  Anschließend Anschlussfrage: "Wo bekomme ich DFB-Spiele kostenlos her" (für die AKTUELLE Saison, nicht nur
  zum Testen mit alten 2023er-Daten).
- **Recherche (mit WebSearch/WebFetch, nicht nur aus dem Gedächtnis):** OpenLigaDB hat den DFB-Pokal tatsächlich
  für die aktuelle Saison 2026/27 im Angebot (Shortcut "dfb", League-ID 4945) — kostenlos, kein Key. Stichprobe
  der Vorsaison (2025/26) direkt gegen die echte API geprüft: von 47 abgeschlossenen Spielen hatten nur 5 die
  vollständige 5-Werte-Ergebnisstruktur (inkl. "nach Verlängerung"/"nach Elfmeterschießen"), mehrere Spiele mit
  bekannter Verlängerung fehlte diese Kennzeichnung — bestätigt die schon früher (v0.4.0) getroffene Einschätzung
  "nicht zuverlässig", nicht nur eine alte Vermutung. football-data.org zusätzlich geprüft: deren Gratis-Tarif
  enthält den DFB-Pokal gar nicht (nur Liga-Wettbewerbe).
- **Entscheidung (mit Nutzer abgestimmt):** DFB-Pokal auf OpenLigaDB umstellen (echte aktuelle Saison, gratis),
  dafür K.o.-Zusatztipp beim DFB-Pokal abschalten (Datenqualität reicht dafür nicht) — statt weiter auf
  API-Football zu setzen, das den Pokal nur für alte Test-Saisons 2021–2023 kostenlos hergibt.
- **v0.8.17 — DFB-Pokal-Datenquelle umgestellt:**
  - `ftipp_openligadb_shortcut()`: `DFB => 'dfb'` ergänzt. `ftipp_fetch_all()`: DFB-Pokal jetzt im selben
    OpenLigaDB-Loop wie BL1/BL2 (mit automatischem Fallback auf API-Football, falls OpenLigaDB mal ausfällt —
    bestehendes Verhalten, unverändert übernommen).
  - **Wichtiger technischer Zusatzfund beim Umbau:** OpenLigaDB liefert bei "Endergebnis" (resultTypeID 2) für
    Spiele, die in Verlängerung/Elfmeterschießen gingen, den Elfmeterschießen-Stand OBENDRAUF (z.B. 7:5 statt
    3:3 nach 90 Minuten) — hätte bei direkter Übernahme nicht nur den K.o.-Zusatztipp verfälscht, sondern auch
    den normalen Tendenz/Exakt-Tipp. `ftipp_fetch_openligadb()` bevorzugt jetzt explizit resultTypeID 3
    ("nach 90 Minuten"), fällt nur auf "Endergebnis" zurück, wenn dieser Wert fehlt (bei normalen
    Ligaspielen ohnehin identisch, kein Verhaltensunterschied für BL1/BL2).
  - K.o.-Zusatztipp für DFB-Pokal entfällt automatisch (OpenLigaDB-Pfad setzt `ko: false` grundsätzlich für
    alle Spiele) — keine separate Änderung an `kind` nötig, DFB bleibt in `ftipp_leagues()` weiterhin als
    `kind: 'cup'` gekennzeichnet (bleibt inhaltlich korrekt, betrifft nur noch den API-Football-Fallback-Pfad).
  - **Bekannter Restrisiko, transparent dokumentiert (Code-Kommentar):** für die seltenen Fälle, in denen ein
    Spiel in Verlängerung geht UND OpenLigaDB die 90-Minuten-Aufschlüsselung nicht liefert, könnte auch der
    normale Tendenz/Exakt-Tipp betroffen sein — inhärente Grenze einer kostenlosen, community-gepflegten
    Datenquelle, kein Plugin-Bug.
  - Texte angepasst: Plugin-Beschreibung, Einstellungsseiten-Hinweistext, Saison-Feld-Label (jetzt nur noch
    "Champions League/Europa League/Nations League" statt vier Wettbewerbe).
  - Nebenbei entdeckten Fehler im Glossar (`CONTEXT.md`) gefixt: "Conference League" stand dort noch als einer
    der sechs Wettbewerbe, obwohl schon seit v0.8.0 durch Nations League ersetzt — war bei der Umstellung
    damals übersehen worden.
  - PHP-Syntax mit `php -l` geprüft (kein Syntaxfehler).
- *Noch nicht live getestet:* Plugin-Update einspielen, "📥 Spieldaten jetzt abrufen" klicken, prüfen dass
  DFB-Pokal jetzt echte aktuelle Spiele zeigt (Quelle-Spalte sollte "OpenLigaDB (aktuelle Saison)" zeigen statt
  "API-Football" bzw. "—"), und dass beim DFB-Pokal kein K.o.-Zusatztipp-Kasten mehr erscheint.
- **Frage:** "Muss ich beim Update wieder auf 'Aktualisieren' klicken, oder wie läuft das? Wo werden die
  Ergebnisse angezeigt?" — beantwortet: automatischer WP-Cron läuft aktuell nur **wöchentlich**
  (`ftipp_weekly_fetch`), zusätzlich manuell per Button erzwingbar. Ergebnisse erscheinen im "Tippen"-Tab pro
  Spiel + in der "Auswertung"-Rangliste. Vorschlag gemacht, die Frequenz auf mehrmals täglich zu erhöhen
  (analog zur schon bestehenden 15-Minuten-Fristen-Erinnerung) — **noch keine Entscheidung des Nutzers dazu,
  offen für nächstes Mal.**
- **Zwei Anschlussfragen zu Sonderwertungen (Screenshot "DFB-Pokalsieger"):** (1) Frist-Sperre nach Ablauf
  bestätigt (schon vorhanden, keine Änderung nötig). (2) "Wenn jemand 'St. Pauli' statt 'FC St. Pauli' tippt
  und das gewinnt — kann man das nachträglich manuell als richtig markieren?" — echte Lücke im Code gefunden:
  Punktevergabe lief bisher über exakten Textvergleich (`strcasecmp`), keine Fuzzy-Logik, keine manuelle
  Korrektur möglich.
- **v0.8.18 — Admin-Korrektur für Sonderwertungen-Tipps:**
  - Neue Spalte `override` (nullable TINYINT) in `ftipp_special_tips` — NULL = automatischer Textvergleich
    (unverändertes Verhalten), 1 = zählt erzwungen als richtig, 0 = zählt erzwungen als falsch. DB-Version 6→7.
  - `ftipp_compute_leaderboard()`: Override hat jetzt Vorrang vor dem Textvergleich bei der Punkteberechnung.
  - Zwei neue Admin-only-Endpunkte: `GET /special/{id}/tipps` (listet alle Mitglieder-Tipps zu einer
    Sonderwertung samt aktuellem Override-Status) und `POST /special/{id}/tipps/{user_id}/override` (setzt/
    löscht den Override für eine Person). Beide nur für den Runden-Admin zugänglich.
  - **Bewusste Erweiterung der bisherigen Geheimhaltung:** bislang sah niemand (auch der Admin nicht) die
    Sonderwertungs-Tipps anderer Mitglieder. Jetzt kann der Runden-Admin sie über einen neuen, standardmäßig
    eingeklappten Bereich "👀 Alle Tipps ansehen & korrigieren" pro Sonderwertung einsehen — nötig, damit er
    überhaupt weiß, wessen Tipp er korrigieren soll. Bewusst NUR für den Admin, normale Mitglieder sehen
    weiterhin nur ihren eigenen Tipp.
  - Frontend: neuer `<details>`-Bereich je Sonderwertungs-Karte (nur für Admin sichtbar), lazy-geladen beim
    Aufklappen (gleiches Muster wie die Pinnwand), pro Mitglied ein Dropdown "Automatisch / ✅ zählt trotzdem
    als richtig / ❌ zählt trotzdem als falsch".
  - Glossar (`CONTEXT.md`) ergänzt: Sonderwertung-Eintrag erklärt jetzt den Textvergleich + die
    Korrekturmöglichkeit.
  - PHP-Syntax mit `php -l` geprüft, JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update einspielen, bei einer Sonderwertung "👀 Alle Tipps ansehen &
  korrigieren" aufklappen, einen Tipp auf "zählt trotzdem als richtig" stellen und prüfen, dass die Punkte in
  der Rangliste entsprechend gezählt werden.
- **Frage/Feedback:** "Wo sehe ich, ob mein 0:4-Tipp beim DFB-Pokal-Spiel eingetroffen ist? Steht das in der
  Auswertung?" — beantwortet: nein, "Auswertung" zeigte bisher nur die aggregierte Rangliste + Statistik-Kennzahlen,
  keine Spiel-für-Spiel-Aufschlüsselung; einzelne Ergebnisse waren nur im "Tippen"-Tab pro Spiel sichtbar.
  Nutzer-Wunsch direkt danach: "Die müssten unter Statistik und Verlauf dann komplett stehen."
- **v0.8.19 — Meine Tipp-Historie unter Statistik & Verlauf:**
  - `GET /roundstats` liefert jetzt zusätzlich ein `matches`-Array: für jedes abgeschlossene Spiel des
    aktuellen Nutzers in diesem Wettbewerb — Spieltag, Teams, echtes Ergebnis, eigener Tipp, erzielte Punkte
    und Treffer-Art (exakt/tendenz/verpasst). Neueste Spiele zuerst (wie ein Feed/Verlauf).
  - Baut auf bereits vorhandener Berechnung auf (dieselbe Schleife, die schon Trefferquote/Exakt/Tendenz für
    "Meine Statistik" ermittelt) — kein doppelter Rechenaufwand, nur zusätzlich pro Spiel mitgeschrieben.
  - Frontend: neue Tabelle "Meine Tipp-Historie" unter dem Ranglisten-Verlauf-Diagramm in "🏆 Auswertung" →
    "📊 Statistik & Verlauf" — Spieltag, Spielpaarung, Ergebnis, eigener Tipp, farbige Punkte-Pille (grün/gelb/rot
    wie im Tippen-Tab).
  - PHP-Syntax mit `php -l` geprüft, JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update einspielen, "Auswertung" → Statistik & Verlauf ansehen, sobald
  mindestens ein Spiel ein Ergebnis hat — sollte dort jetzt Spiel für Spiel mit eigenem Tipp + Punkten auftauchen.
- **Frage:** "Wo bekomme ich DFB-Spiele... äh, Nationalmannschaften anderer Länder kostenlos her?" (Nations
  League) — recherchiert (WebFetch gegen die echte OpenLigaDB-API): der einzige passende Eintrag "Nations
  League 2024/25 (DE)" enthält nur 2 Spiele, überwiegend mit deutscher U21-Beteiligung, kein vollständiger
  europaweiter Wettbewerb — anders als beim DFB-Pokal keine brauchbare kostenlose Alternative zu API-Football.
- **Kritischer Vorfall gemeldet:** "Wir haben zu dritt getippt, Update geklickt, Ergebnisse laden nicht rein" —
  betraf konkret **DFB-Pokal**. Root Cause identifiziert: die v0.8.17-Umstellung von API-Football auf
  OpenLigaDB hat für denselben Wettbewerb neue interne Spiel-IDs eingeführt (Präfix `oldb-` statt `api-`) —
  Tipps, die VOR der Umstellung abgegeben wurden, hängen an der alten ID, die es in der neu geladenen
  Spielliste nicht mehr gibt. Das Spiel erscheint dadurch als "neu, nicht getippt", das Ergebnis wird dem alten
  (jetzt unsichtbaren) Tipp nie zugeordnet. **Eigener Fehler** — bei der v0.8.17-Umstellung nicht an
  Tipp-Kontinuität für schon abgegebene Tipps gedacht.
  - Automatische Reparatur nicht möglich: die alte API-Football-Spielliste (mit Vereinsnamen je alter ID) wurde
    beim neuen Abruf bereits überschrieben, es gibt keine gespeicherte Zuordnung alte-ID → welches echte Spiel
    mehr, über die man automatisch neu verknüpfen könnte.
- **v0.8.20 — Sichtbarkeit für verwaiste Tipps (Datenrettung, keine Automatik):**
  - Neue Funktion `ftipp_orphaned_tips()`: findet für JEDEN Wettbewerb (nicht nur DFB-Pokal — allgemeiner
    Schutz für jeden künftigen Datenquellen-Wechsel) alle `ftipp_tips`-Einträge, deren Spiel-ID in der aktuell
    geladenen Spielliste nicht mehr vorkommt.
  - Neuer Abschnitt "⚠️ Verwaiste Tipps gefunden" auf der Einstellungsseite (nur sichtbar, wenn tatsächlich
    welche existieren) — zeigt Wettbewerb, Spieler, getippten Stand, K.o.-Tipp, Zeitpunkt der letzten Änderung
    und die interne Spiel-ID. **Nichts wird automatisch gelöscht oder verändert** — reine Sichtbarkeit, damit
    Admin + Mitspieler gemeinsam rekonstruieren können, welcher Tipp zu welchem echten Spiel gehörte, und ihn
    dort manuell neu eintragen (solange die Frist des neuen Spiels noch nicht um ist).
  - PHP-Syntax mit `php -l` geprüft.
- *Noch nicht live getestet:* Plugin-Update einspielen, Einstellungsseite aufrufen, prüfen dass die
  verwaisten DFB-Pokal-Tipps der drei Mitspieler dort auftauchen — dann gemeinsam abgleichen, welcher Tipp zu
  welchem der aktuell geladenen Spiele gehörte, und manuell neu eintragen.
- **Notiz für später:** grundsätzliche Lehre — ein Wechsel der Datenquelle für einen Wettbewerb sollte
  künftig vor der Umstellung geprüft werden, ob dabei laufende Tipps verwaisen; ggf. vorher eine Umbenennung/
  Migration der betroffenen fixture_ids einplanen, statt es erst hinterher zu bemerken.

## 2026-08-26 — Grafische Präsentation (Showcase) für Floh (Web-Wanda)
- **Auftrag:** Floh möchte Tippstube "einmal für alle Leute zeigen" (Familie/Freunde/Mitspieler) — warmes
  "Seht mal was ich gebaut habe"-Showcase, kein aggressiver Sales-Pitch. Auftrag kam über Operator Ole,
  bearbeitet von Web-Wanda (Creative Director / Webdesign).
- **Format-Entscheidung:** selbst-enthaltenes HTML-Web-Artifact (per Link teilbar, fühlt sich nach echtem
  Produkt an) — Priorität laut Auftrag. Eine begleitende PowerPoint wurde bewusst zurückgestellt (nicht
  gebaut), da das Web-Artifact als wichtigster Kern reicht; Angebot an Floh, die PPT bei Bedarf (z. B. für
  Beamer/Fernseher ohne Internet) nachzuliefern.
- **Marke 1:1 übernommen, nichts neu erfunden:** Farben, Typografie (Georgia-Serif für Headlines, System-Sans
  für Fließtext) und das Wappen-Logo exakt aus `tippstube_designguide.html` übernommen (Inline-SVG unverändert
  kopiert, nur mit neuen Gradient-IDs pro Instanz dupliziert). Die beiden alten Konzept-PDFs
  (`Fussball-Tippspiel_Pitch.pdf`, `..._Praesentation.pdf`) mit generischem Navy/Teal-SaaS-Look wurden
  bewusst NICHT als visuelles Vorbild verwendet — nur ihre grobe Content-Gliederung diente als Inspiration
  für den roten Faden.
- **Inhalt** (Stand v0.8.20, gegen dieses Journal geprüft): Hero → "Was es ist" (3 Kurzfakten) → die 6
  Wettbewerbe als Karten-Grid → Punktesystem mit 1:1 nachgebauter App-Tipp-Karte (Design-Guide-Optik: Spieltag,
  Fixture, Zahlen-Boxen, Button, Pille) → Automatisierung & Fairness (Auto-Refresh/Cache, Tippschluss 1h
  vorher, fremde Tipps erst nach eigenem Tipp sichtbar) → private Tipprunden, Sonderwertungen, Pinnwand,
  E-Mail-Erinnerungen → Rangliste + "Meine Tipp-Historie" mit farbigen Pillen (grün/gelb/rot) + Saisonverlauf-
  Sparkline (SVG) → DSGVO/Kostenlos/Open-Source-Vertrauensblock → Footer mit GitHub-Link
  (github.com/flowtrix2026/tippsiel) und Versionsangabe.
- **Technisch:** ein einziges HTML-File, kein externes CDN/Font, alle Icons als eigene Inline-SVGs (Linien-
  Stil, kein Emoji), Scroll-Reveal-Animation mit `prefers-reduced-motion`-Guard, dezente animierte Sparkline.
  Alle Umlaute (ä/ö/ü/ß) konsequent als HTML-Entities geschrieben (nicht als rohes UTF-8), nachdem ein lokaler
  Test-Webserver ohne Charset-Header sonst Mojibake gezeigt hätte — Absicherung gegen Hosting-Umgebungen ohne
  garantierten UTF-8-Header.
- **QA:** lokal per Browser-Vorschau (mobile + Desktop-Breite via DOM-Check, da die Sandbox-Vorschau bei
  emulierter Desktop-Breite ein Screenshot-Darstellungsartefakt hatte — per `getComputedStyle`/
  `elementFromPoint` verifiziert, dass Layout/Hintergrund tatsächlich über die volle Breite korrekt ist).
- **Ablage:** Artifact veröffentlicht (privat, nur für Floh sichtbar bis er es teilt); zusätzlich Kopie unter
  `~/Downloads/Tippstube-Showcase.html` für den Fall, dass er die Datei offline zeigen will.
- **Offen:** Artifact ist bewusst privat — Floh muss es selbst über das Teilen-Menü für die Gruppe freigeben,
  falls er den Link verschicken will. PowerPoint-Begleitdeck nicht gebaut (siehe oben), auf Zuruf nachreichbar.

## v0.8.21 — Spieltag-Auswahl im Tippen-Tab (statt endlos scrollen)
- **Anfrage (Screenshot):** "Wenn ich Bundesliga tippe, geht das von Spieltag 1 zu 2 zu 3 und immer weiter
  runter — kannst du neben Bundesliga oben immer alle 34 Spieltage eintragen? Vom ersten zum dritten
  Spieltag runterzuscrollen dauert ewig." Bisher zeigte der "Tippen"-Tab alle Spieltage/Runden eines
  Wettbewerbs als eine durchgehende, lange Liste — kein Sprung zwischen einzelnen Spieltagen möglich.
- **Umgesetzt:**
  - Neues Auswahlfeld **"Spieltag"** direkt neben "Wettbewerb" — filtert die Liste auf genau einen
    Spieltag/eine Runde, statt alle anzuzeigen. Funktioniert für alle Wettbewerbe gleich (bei Pokal-
    Wettbewerben stehen dort die echten Rundennamen wie "Achtelfinale" statt Spieltag-Nummern — kommt
    automatisch aus den schon vorhandenen Rundenbezeichnungen der Spiele).
  - Zusätzlich **◀ / ▶ Buttons** direkt daneben, um schnell zum vorherigen/nächsten Spieltag zu blättern,
    ohne das Dropdown extra öffnen zu müssen (an den Enden jeweils deaktiviert).
  - **Sinnvoller Standard-Spieltag**: beim ersten Öffnen (oder Wettbewerbswechsel) wird automatisch der erste
    Spieltag mit noch offenem Ergebnis vorausgewählt ("aktuell dran") — nicht stur immer Spieltag 1. Ist die
    Saison schon komplett durchgespielt, wird der letzte Spieltag gezeigt.
  - Auswahlfeld erscheint nur, wenn es tatsächlich mehr als einen Spieltag gibt (z.B. bei ganz frisch
    geladenen Testdaten mit nur 1-2 Spielen bleibt die Liste wie bisher ohne Filter).
  - JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update einspielen, "Tippen"-Tab öffnen, prüfen dass automatisch ein
  sinnvoller aktueller Spieltag vorausgewählt ist, und dass Dropdown + Pfeile korrekt zwischen Spieltagen
  wechseln, ohne dabei bereits eingetragene Tipps zu verlieren.
- **Frage:** "Wie bekommen wir die Champions-League-Daten jetzt hier rein?" — daraufhin recherchiert
  (WebFetch gegen die echte OpenLigaDB-API, wie schon beim DFB-Pokal): anders als bei der Nations League gibt
  es für Champions League ("ucl", Saison 2026) UND Europa League ("uel2026") echte, korrekt terminierte
  Spieldaten für die aktuelle Saison 2026/27 — 18 Spiele für den 1. CL-Spieltag ab 8. September 2026 geprüft
  und dem Nutzer als Tabelle gezeigt (u.a. Real Madrid–Inter, Bayern–Bodø/Glimt, Dortmund–Villarreal).
  Zwischenzeitlich auch eine Idee des Nutzers besprochen (CSV/Excel-Upload für Wettbewerbe ohne gute API) —
  zurückgestellt zugunsten des direkten, schnelleren Wegs für CL/EL, da hierfür ja eine Quelle existiert;
  die CSV-Idee bleibt für später vorgemerkt (z.B. für Nations League, wo weiterhin keine brauchbare
  kostenlose API-Quelle existiert).
- **v0.8.22 — Champions League + Europa League auf OpenLigaDB umgestellt:**
  - Gleiches Vorgehen wie beim DFB-Pokal (v0.8.17): `ftipp_openligadb_shortcut()` um `CL => 'ucl'` und
    `EL => 'uel2026'` ergänzt, beide in den OpenLigaDB-Loop von `ftipp_fetch_all()` aufgenommen (mit
    automatischem Fallback auf API-Football, falls OpenLigaDB mal leer/down ist — unverändertes bestehendes
    Verhalten). Die bereits vorhandene Absicherung in `ftipp_fetch_openligadb()` (bevorzugt "nach 90 Minuten"
    statt "Endergebnis" bei K.o.-Spielen) greift dadurch automatisch auch für CL/EL, ohne Codeänderung nötig.
  - **Wichtige Eigenheit dokumentiert (Code-Kommentar):** anders als bei BL1/BL2/DFB-Pokal ist der
    OpenLigaDB-Shortcut für Champions/Europa League **nicht über die Jahre stabil** — die Community hat ihn
    historisch fast jede Saison neu benannt (cl → cl1011 → ucl2014 → ucl2024 → ucl bei der CL). Die aktuell
    hinterlegten Shortcuts gelten für 2026/27 und müssten zur nächsten Saison manuell gegengeprüft werden
    (via `api.openligadb.de/getavailableleagues`), falls der Abruf plötzlich leer läuft.
  - K.o.-Zusatztipp entfällt jetzt auch bei Champions League und Europa League (automatisch, da der
    OpenLigaDB-Pfad grundsätzlich `ko: false` setzt) — betrifft damit inzwischen drei Wettbewerbe
    (DFB-Pokal, CL, EL). Nations League bleibt als einziger API-Football-Wettbewerb übrig.
  - Texte überall aktualisiert: Plugin-Beschreibung, großer Hybrid-Kommentar, Einstellungsseiten-Hinweistext,
    Saison-Feld-Label (jetzt nur noch "Nations League" statt drei Wettbewerbe).
  - PHP-Syntax mit `php -l` geprüft.
- *Noch nicht live getestet:* Plugin-Update einspielen, "📥 Spieldaten jetzt abrufen" klicken, prüfen dass
  Champions League und Europa League jetzt "OpenLigaDB (aktuelle Saison)" als Quelle zeigen und die echten
  Spiele ab 8./16. September 2026 laden — inkl. Gegenprüfung, dass kein K.o.-Zusatztipp-Kasten mehr erscheint.
- **Nations League bleibt ungelöst über APIs:** trotz Nachfrage ("gibt es eine andere API dafür, Andy?")
  zusätzlich **football-data.org** (Nations League nicht im Gratis-Tarif) und **TheSportsDB** (Nations League
  gar nicht im Programm) geprüft — beide kein Treffer. Damit vier Quellen ergebnislos geprüft (OpenLigaDB,
  API-Football, football-data.org, TheSportsDB). Nutzer bestand ausdrücklich auf "alle Nationen" (keine
  Reduktion auf nur Deutschlands Gruppe, obwohl dafür ein brauchbarer OpenLigaDB-Eintrag "DFB Nationalspiele"
  gefunden wurde) — für diesen Fall bleibt nur eine manuelle Lösung.
- **Nutzer-Idee:** CSV/Excel-Upload für Wettbewerbe ohne gute Gratis-API — von ihm selbst vorgeschlagen, jetzt
  umgesetzt. Zusatzfrage "kriegen wir die Nations-League-Daten als CSV irgendwoher" beantwortet: ja, per
  Recherche zusammenstellbar statt von Hand abzutippen — dafür einen Hintergrund-Agenten gestartet
  (Wikipedia/UEFA.com als Quelle, alle 4 Divisionen A–D, Ziel-Datei `nations-league-2026-27.csv`).
- **v0.8.23 — CSV-Import für Spieldaten:**
  - Neue Funktion `ftipp_import_fixtures_csv()`: liest eine hochgeladene CSV (Spalten `Wettbewerb,Spieltag,
    Datum,Heim,Auswaerts,ToreHeim,ToreAusw`), erkennt Kopfzeile robust (Groß-/Kleinschreibung, Spaltenreihenfolge
    egal), validiert Wettbewerbs-Kürzel gegen `ftipp_comp_ids()`.
  - **Stabile, deterministische Spiel-ID** aus Wettbewerb+Teams+Datum (Hash) — dieselbe reale Begegnung bekommt
    bei jedem Upload dieselbe ID, ein erneuter Upload mit jetzt bekanntem Ergebnis **aktualisiert** den
    bestehenden Eintrag statt einen zweiten anzulegen. Direkte Lehre aus dem DFB-Pokal-Vorfall (v0.8.20)
    umgesetzt, damit das hier nicht nochmal passieren kann.
  - **Architektur-Entscheidung, um denselben Fehler nicht zu wiederholen:** manuell importierte Spiele landen
    in einem **eigenen** Options-Speicher (`ftipp_manual_fixtures`), getrennt von den automatisch abgerufenen
    (`ftipp_fixtures`) — sonst hätte der nächste wöchentliche Automatik-Abruf (der `ftipp_fixtures` komplett
    neu schreibt) die CSV-Daten beim nächsten Mal wieder gelöscht. `ftipp_fixtures_for()` (die zentrale
    Stelle, über die JEDER andere Teil des Plugins Spieldaten liest — Rangliste, Tipp-Anzeige, Statistik, siehe
    auch `ftipp_fixture_by_id()`, jetzt ebenfalls umgestellt) führt beide Speicher automatisch zusammen, bei
    ID-Kollision gewinnt die manuelle Version. Damit kann ein Wettbewerb künftig auch **teils automatisch,
    teils manuell** befüllt sein (z.B. CL größtenteils über OpenLigaDB, einzelne fehlende Spieltage per CSV
    ergänzt), ohne dass sich beide Quellen gegenseitig überschreiben.
  - Neuer Bereich "📄 Spieldaten per CSV importieren" auf der Einstellungsseite: Datei-Upload-Formular,
    Spalten-Erklärung, Rückmeldung nach Import (neu/aktualisiert/übersprungen). "Letzter Abruf"-Tabelle um
    Spalte "davon per CSV" ergänzt, damit auf einen Blick sichtbar ist, wie viele Spiele manuell drin sind.
  - K.o.-Zusatztipp bleibt bei CSV-importierten Spielen bewusst aus (`ko: false`, wie bei den OpenLigaDB-
    Wettbewerben) — CSV-Format sieht (noch) keine Verlängerung/Elfmeterschießen-Angabe vor.
  - PHP-Syntax mit `php -l` geprüft.
- *Noch nicht live getestet:* Plugin-Update einspielen, sobald die recherchierte Nations-League-CSV vorliegt
  hochladen, prüfen dass die Spiele in der App auftauchen (auch ohne dass der automatische Abruf für NL
  irgendwas findet), und dass ein zweiter Upload derselben Datei (z.B. mit einem nachgetragenen Ergebnis)
  bestehende Einträge aktualisiert statt sie zu duplizieren.

## v0.8.24 — Bugfix: CSV-importierte Spiele erschienen nicht in der App
- **Live-Test von v0.8.23:** Nations-League-CSV (156 Spiele, recherchiert per Hintergrund-Agent) hochgeladen —
  Import lief fehlerfrei durch, "Letzter Abruf"-Tabelle zeigte korrekt "davon per CSV: 156". Trotzdem stand im
  Tippen-Tab bei Nations League weiterhin "Noch keine Spiele geladen". Systematisch eingegrenzt (Datei lokal
  gegen die echte Import-Funktion getestet → 156 fehlerfrei importiert, also Datei/Import selbst i.O.; dann
  Plugin-Liste, Einstellungsseite und "Letzter Abruf"-Tabelle per Screenshot geprüft → Daten sind definitiv in
  der Datenbank).
- **Ursache gefunden:** Die App bekommt ihre Spieldaten beim Laden nicht (nur) über die REST-API, sondern
  direkt beim Seitenaufruf server-seitig als `window.FTIPP_BOOT` eingebettet (`template_redirect`-Hook für
  `?ftipp_app=1`). Diese Stelle las die Spiele bisher direkt über `get_option('ftipp_fixtures', ...)` — also
  nur den automatischen Abruf — statt über `ftipp_fixtures_for()`, die zentrale Funktion, die automatische und
  per CSV importierte Spiele zusammenführt. Dieselbe Abkürzung steckte auch im (ungenutzten, aber vorsorglich
  mitgefixten) REST-Endpunkt `GET /ftipp/v1/data`. Ergebnis: Punkteberechnung, Rangliste und Statistik nutzten
  bereits korrekt `ftipp_fixtures_for()` und "sahen" die CSV-Spiele — nur der allererste Ladepunkt der App tat
  es nicht, weshalb die Spiele im Tippen-Tab schlicht fehlten, obwohl der Import erfolgreich war.
- **Fix:** Beide Stellen (App-Boot in `template_redirect`, REST-Route `/data`) bauen die `fixtures`-Struktur
  jetzt pro Wettbewerb über `ftipp_fixtures_for( $cid )` auf, genau wie der Rest des Plugins. Kein neuer Fehler-
  Modus für die Zukunft: jede neue Stelle, die Spieldaten braucht, sollte künftig ebenfalls `ftipp_fixtures_for()`
  statt der rohen Option verwenden — das ist jetzt an beiden Boot-Stellen per Code-Kommentar festgehalten.
  PHP-Syntax mit `php -l` geprüft.
- *Noch nicht live getestet:* Plugin-Update auf v0.8.24 einspielen (kein neuer CSV-Upload nötig, die 156 Spiele
  liegen schon in der Datenbank), Tippen-Tab → Nations League öffnen, prüfen dass jetzt alle Spiele erscheinen.
- **Live getestet, bestätigt ("Das hat jetzt geklappt"):** Nations-League-Spiele erscheinen im Tippen-Tab.

## v0.8.25 — Zweistufige Spieltag-Auswahl für die Nations League (Liga + Spieltag)
- **Anfrage (Screenshot):** Der "Spieltag"-Dropdown aus v0.8.21 zeigte bei der Nations League alle 24
  Kombinationen aus 4 Ligen × 6 Spieltagen als einen einzigen, langen Dropdown — und die Reihenfolge innerhalb
  eines Spieltags war zusätzlich uneinheitlich (Liga D, A, B, C in Spieltag 1; A, C, B, D in Spieltag 5, …),
  weil mehrere Ligen oft zur exakt gleichen Uhrzeit anpfeifen und die Liste bisher nur nach Datum sortiert
  eindeutige Rundennamen in Auftrittsreihenfolge sammelte. Nutzerwunsch: "wie beim Spieltag Gruppe A, B, C und
  D... man muss in der Nations League pro Gruppe und den Spieltag auswählen können."
- **Umgesetzt:** Erkennt automatisch, wenn alle Rundennamen eines Wettbewerbs dem Muster "<Liga> - Spieltag <n>"
  folgen (aktuell nur Nations League, funktioniert aber generisch für jeden künftigen Wettbewerb mit gleicher
  Struktur). In dem Fall erscheint vor dem bestehenden "Spieltag"-Dropdown zusätzlich ein **"Liga"-Dropdown**
  (A–D, alphabetisch sortiert); der "Spieltag"-Dropdown zeigt danach nur noch die Spieltage der gewählten Liga,
  sauber numerisch sortiert (1–6), mit nur noch der Kurzbezeichnung ("Spieltag 3" statt "Liga A - Spieltag 3").
  Die ◀/▶-Pfeile blättern dadurch automatisch nur noch innerhalb der gewählten Liga. Für alle anderen
  Wettbewerbe (Bundesliga, Pokale) bleibt exakt das bisherige Verhalten aus v0.8.21 unverändert — Erkennung
  greift nur, wenn wirklich alle Rundennamen dem Zwei-Ebenen-Muster entsprechen.
  Sinnvoller Default wie gehabt: die Liga *und* der Spieltag mit dem nächsten noch offenen Spiel werden
  automatisch vorausgewählt. JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update auf v0.8.25 einspielen, Tippen-Tab → Nations League öffnen, prüfen
  dass jetzt zuerst "Liga" (A–D) und danach "Spieltag" (1–6) sauber getrennt auswählbar sind, inkl. ◀/▶.

## v0.8.26 — "Runde" statt "Spieltag" bei Pokal-Wettbewerben; Klarstellung Update-Verhalten
- **Anfrage:** Am Beispiel DFB-Pokal gefragt, was beim Klick auf "Spieldaten jetzt abrufen" passiert, sobald am
  Samstag die 2. Runde dazukommt, und ob man analog zur Spieltag-Auswahl auch alte Pokal-Runden zum
  Zurückblättern sehen kann.
- **Geklärt (kein Code nötig):** Der generische Spieltag-Selektor aus v0.8.21 funktioniert bereits für beliebige
  Rundennamen, nicht nur "Spieltag N" — bei Pokal-Wettbewerben stehen dort automatisch die echten OpenLigaDB-
  Rundennamen ("1. Runde", "2. Runde", "Achtelfinale", …). Er war beim DFB-Pokal bisher nur unsichtbar, weil
  aktuell nur eine einzige Runde geladen ist (Dropdown erscheint erst ab 2 unterschiedlichen Runden) — taucht
  von selbst auf, sobald die 2. Runde geladen wird. Und weil DFB-Pokal/CL/EL über OpenLigaDB laufen, das pro
  Saison stabile Spiel-IDs vergibt, überschreibt ein normaler Update-Klick bestehende Runden nicht, sondern
  ergänzt nur neue — bereits abgegebene Tipps zu Runde 1 bleiben unangetastet (im Unterschied zum Vorfall aus
  v0.8.20, der eine einmalige Quellen-**Umstellung** betraf, nicht den normalen wöchentlichen Refresh).
- **Kleine UI-Korrektur:** Das Auswahlfeld hieß bisher überall "Spieltag", auch bei Pokal-Wettbewerben, wo es
  eigentlich "Runde" heißen sollte. Heißt jetzt automatisch "Runde" bei allen Wettbewerben vom Typ `kind:'cup'`
  (DFB-Pokal, Champions League, Europa League, Nations League), "Spieltag" bleibt nur bei den beiden
  Bundesliga-Wettbewerben. JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update auf v0.8.26 einspielen, DFB-Pokal im Tippen-Tab öffnen und prüfen,
  dass (sobald mehr als eine Runde geladen ist) das Auswahlfeld "Runde" statt "Spieltag" heißt.

## v0.8.27 — Ranglisten-Verlauf-Graph bei nur einer gespielten Runde
- **Meldung (Screenshot):** "Im DFB-Pokal funktioniert der Graph nicht. 1. Bundesliga geht ja." — beim DFB-Pokal
  zeigte der "Ranglisten-Verlauf"-Graph in der Statistik nur 3 eng beieinanderliegende Punkte oben, mit viel
  Leerraum darunter bis zur gestrichelten Null-Linie.
- **Kein Rechenfehler, aber irreführend:** Mit erst einer gespielten Runde (DFB-Pokal: nur "1. Runde") gibt es
  pro Spieler nur einen einzigen Datenpunkt statt einer Linie über mehrere Runden. Die Y-Achse geht dabei
  bewusst immer bis 0 runter (damit man den Punktabstand einordnen kann) — bei Punktständen wie 33/28/26 landen
  alle drei Punkte nah beieinander ganz oben, der Rest der Fläche bis 0 bleibt leer. Mathematisch korrekt,
  sieht aber wie ein kaputter Graph aus. Bei der 1. Bundesliga (schon mehrere Spieltage gespielt) ergibt sich
  dagegen eine echte Linie über mehrere Punkte, die normal aussieht.
- **Fix:** Der Graph erscheint jetzt erst ab **2 gespielten Runden** (dann macht eine Linie überhaupt Sinn).
  Bei genau 1 gespielten Runde steht stattdessen ein kurzer Hinweistext ("Braucht mindestens 2 gespielte
  Runden..."). Betrifft nur die Anzeige, keine Änderung an Punkteberechnung oder Rangliste.
  JS-Syntax per `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update auf v0.8.27 einspielen, Statistik & Verlauf beim DFB-Pokal öffnen,
  prüfen dass jetzt der Hinweistext statt des unübersichtlichen Ein-Punkt-Graphen erscheint.

## v0.9.0 — Neuer Tab "📊 Tabelle": echte Liga-/Gruppentabelle (nicht die Tipp-Rangliste)
- **Anfrage:** "Können wir neben Bundesliga auch noch eine Tabelle machen, dass man sehen kann, wer auf welchem
  Platz gerade ist? Champions League, DFB-Pokal — die Tabellen einfach." Gewünscht war die **echte**
  Wettbewerbstabelle (wer steht wo in der richtigen Bundesliga/Champions League), nicht die bestehende
  Tipp-Rangliste unter "Auswertung".
- **Vorab recherchiert (echte API-Calls, nicht nur Dokumentation):** OpenLigaDB bietet einen eigenen
  `getbltable`-Endpunkt, der eine fertige, echte Tabelle liefert (Punkte, Tore, Siege/Niederlagen) — getestet
  gegen Bundesliga, 2. Bundesliga, Champions League und Europa League, funktioniert bei allen vieren.
  Beim DFB-Pokal liefert derselbe Endpunkt zwar auch Daten zurück, die sind aber bei einem reinen K.o.-System
  bedeutungslos (eine "Tabelle" ergibt bei einem Turnier ohne Rückspiele/Gruppenphase keinen Sinn) — deshalb auf
  Nutzerwunsch für den Pokal komplett ausgeblendet (auch nicht im Wettbewerbs-Dropdown des neuen Tabs wählbar).
- **Umgesetzt:**
  - Neuer Tab **"📊 Tabelle"** (nur im verbundenen WordPress-Modus sichtbar, wie "Runden"), getrennt von
    "🏆 Auswertung" (das bleibt exklusiv die Tipp-Rangliste).
  - Neue REST-Route `GET /table?comp=...`: für BL1/BL2/CL/EL wird die echte OpenLigaDB-Tabelle geholt (15
    Minuten serverseitig gecacht, damit nicht bei jedem Tab-Aufruf ein externer API-Call nötig ist; bewusst
    ohne Team-Logos im Payload, da OpenLigaDB die teils als riesige eingebettete Bilder statt URLs liefert).
  - Für die **Nations League** gibt's keine API-Tabelle — die wird stattdessen **selbst aus den CSV-Spieldaten
    berechnet**, pro erkannter Gruppe einzeln (Standard-Fußballregeln: 3 Punkte Sieg, 1 Unentschieden), über die
    bereits vorhandene Gruppenerkennung (`ftipp_nl_groups()`, siehe Bugfix unten).
  - **Nebenbei gefundener und gefixter Bug:** `ftipp_nl_groups()` (Grundlage sowohl für die neue Tabelle als
    auch die schon länger geplante "Gruppensieger"-Sonderwertung) suchte Rundennamen im Format "Liga A**,**
    Spieltag 1" (Komma) — das echte CSV-Format ist aber "Liga A **-** Spieltag 1" (Bindestrich, siehe v0.8.25).
    Dadurch wurden bisher **keine** Nations-League-Gruppen erkannt und folglich auch keine
    Gruppensieger-Sonderwertungen automatisch angelegt, obwohl das schon länger so vorgesehen war. Jetzt werden
    beide Schreibweisen akzeptiert — sobald das Update läuft, sollten die 14 Gruppensieger-Sonderwertungen
    einmalig automatisch nachgerüstet werden (wie im Standard-Sonderwertung-Mechanismus vorgesehen).
  - PHP-Syntax mit `php -l`, JS-Syntax mit `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update auf v0.9.0 einspielen, neuen Tab "📊 Tabelle" öffnen, für Bundesliga/
  Champions League/Europa League prüfen dass eine echte Tabelle mit Punkten erscheint, für Nations League dass
  pro Gruppe eine eigene Mini-Tabelle erscheint, und dass DFB-Pokal im Wettbewerbs-Dropdown gar nicht auftaucht.
  Außerdem unter ⭐ Sonderwertungen bei Nations League prüfen, ob jetzt Gruppensieger-Sonderwertungen auftauchen.

## v0.9.1 — Bugfix: Nations-League-Gruppenerkennung erkannte immer noch nichts
- **Live-Test von v0.9.0:** Neuer Tab "📊 Tabelle" bei Nations League zeigte "Noch keine Gruppen erkennbar",
  obwohl die 156 CSV-Spiele längst importiert waren.
- **Ursache:** Der Regex-Fix aus v0.9.0 (Komma **oder** Bindestrich akzeptieren) hatte noch eine Lücke — er
  erwartete den Bindestrich direkt hinter dem Liga-Buchstaben ("Liga A**-**Spieltag"), das echte CSV-Format hat
  aber ein Leerzeichen davor **und** danach ("Liga A **-** Spieltag", also Leerzeichen-Bindestrich-Leerzeichen).
  Dadurch griff die Erkennung immer noch nicht.
- **Fix:** Regex erlaubt jetzt beliebige Leerzeichen vor und nach dem Trennzeichen. Mit einem echten PHP-Test
  gegen beide Schreibweisen ("Liga A - Spieltag 1" und "Liga A, Spieltag 1") verifiziert, bevor gepackt wurde.
- *Noch nicht live getestet:* Plugin-Update auf v0.9.1 einspielen, Tab "📊 Tabelle" bei Nations League öffnen —
  sollte jetzt 14 Gruppen-Mini-Tabellen zeigen. Danach auch bei ⭐ Sonderwertungen prüfen, ob die
  Gruppensieger-Sonderwertungen jetzt auftauchen (hängt an derselben Erkennungsfunktion).

## v0.9.2 — Nations League: Gruppen-Rangliste + Spiel-für-Spiel-Übersicht aller Mitspieler
- **Anfrage:** "Bei der Bewertung Nations League — Gesamtstatistik oben (meiste Punkte, gibt's schon), darunter
  wer am besten in der Gruppe getippt hat, und darunter alle Spiele, wer am besten getippt hat." Bisher zeigte
  "Statistik & Verlauf" nur **persönliche** Werte (nur die eigenen Tipps), keine Übersicht über alle Mitspieler
  pro Gruppe oder pro Einzelspiel.
- **Umgesetzt (nur bei Nations League, da nur dort "Gruppen" existieren):**
  - Neuer Abschnitt **"Wer hat in welcher Gruppe am besten getippt?"**: für jede der 14 erkannten Gruppen eine
    eigene Mini-Rangliste (Punkte/Exakt/Tendenz/Verpasst) — aber nur aus den Spielen **dieser einen Gruppe**,
    nicht der Gesamtpunktzahl. Nutzt dieselbe Gruppenerkennung wie die neue Tabelle (v0.9.0/v0.9.1) und die
    Gruppensieger-Sonderwertung.
  - Neuer Abschnitt **"Alle Spiele — wer hat wie getippt"**: pro ausgetragenem Spiel (neueste zuerst) eine Zeile
    mit dem Ergebnis, darunter für **jeden Mitspieler** Tipp + Punkte als farbige Pille (grün=exakt,
    gelb=Tendenz, rot=verpasst) — vorher gab's das nur als "Tipps der Mitspieler" beim Tippen selbst, aber
    nicht gesammelt in der Auswertung.
  - Backend liefert beides nur für `comp=NL` (bei anderen Wettbewerben leere Arrays, keine Änderung an deren
    Anzeige) — Berechnung läuft über die bereits vorhandene Tipp-/Punkte-Logik, keine neue Datenquelle nötig.
  - PHP-Syntax mit `php -l`, JS-Syntax mit `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update auf v0.9.2 einspielen, Auswertung → Nations League öffnen, unter
  "Statistik & Verlauf" prüfen, dass nach der bestehenden persönlichen Statistik jetzt die Gruppen-Ranglisten
  und die Spiel-für-Spiel-Übersicht mit allen Mitspielern erscheinen (setzt mindestens 1 ausgetragenes
  NL-Spiel voraus).

## v0.9.3 — Sonderwertungen: Tipp abgeben + Tipps der Mitspieler erst nach Frist sichtbar
- **Anfrage (Screenshot):** "Wenn man was getippt hat, sehen die anderen das auch [wie bei Spiel-Tipps]. Kannst
  du das bei den Sonderwertungen auch einrichten?" Bisher war ein Sonderwertungs-Tipp einfach ein frei
  jederzeit änderbares Textfeld ohne "Tipp abgeben"-Schritt, ohne Sperre danach, und **niemand** konnte sehen,
  was andere getippt hatten — auch nicht nach der Frist.
- **Wichtige Klarstellung während der Umsetzung:** Anders als bei Spiel-Tipps (dort: sobald ICH selbst
  abgegeben habe, sehe ich die anderen) sollen Sonderwertungen NICHT pro Person einzeln aufgedeckt werden,
  sondern **gemeinsam für alle erst, sobald die Frist abgelaufen ist** — sonst hätte, wer zuerst tippt, einen
  Vorteil bzw. es könnte "hin und her getippt" werden, nachdem man die Tipps anderer schon gesehen hat.
- **Umgesetzt:**
  - `ftipp_special_tips`-Tabelle um `committed`-Spalte erweitert (FTIPP_DB_VERSION 7 → 8).
  - Neuer "✅ Tipp abgeben"-Button (nur klickbar, wenn ein Tipp eingetragen ist) + "✏️ Bearbeiten"-Button danach
    — exakt wie bei den Spiel-Tipps, frei änderbar bis zur Frist.
  - Neue REST-Route `POST /special/{id}/tip/commit`.
  - `GET /special` liefert jetzt zusätzlich `locked` (Frist abgelaufen?) und `others` (Tipps der Mitspieler) —
    `others` ist nur gefüllt, wenn `locked` true ist, unabhängig vom eigenen Commit-Status. Vor der Frist steht
    stattdessen ein Hinweistext, dass alle Tipps bis dahin geheim bleiben.
  - Admin-Korrekturpanel ("👀 Alle Tipps ansehen & korrigieren") kann jetzt zusätzlich zum
    Richtig/Falsch-Übersteuern auch **den eingetippten Text direkt bearbeiten** (auf Nutzerwunsch) — z.B. falls
    ein Mitspieler selbst nicht mehr ändern kann/darf und den Admin bittet.
  - PHP-Syntax mit `php -l`, JS-Syntax mit `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update auf v0.9.3 einspielen, bei einer Sonderwertung "Tipp abgeben"
  klicken, prüfen dass vor Fristablauf kein Mitspieler-Tipp sichtbar ist (auch nicht nach eigenem Abgeben),
  und dass nach Fristablauf alle Tipps gemeinsam erscheinen. Admin-Korrektur mit direkter Textänderung testen.

## v0.9.4 — Spiel-Tipps: Mitspieler-Tipps erst für den ganzen Spieltag gemeinsam sichtbar
- **Anfrage:** "Bei der Bundesliga: keiner darf den Tipp sehen, bis alle Freitags-/Samstags-/Sonntagsspiele
  gemeinsam aufgedeckt werden — nicht schon einzeln pro Spiel, sobald man selbst abgegeben hat. Sonst tippt
  jemand hin und her, nachdem er die Tipps anderer schon gesehen hat." Bisher galt: sobald DU dein eigenes
  Ergebnis zu einem Spiel abgegeben hattest, hast du sofort die (ebenfalls schon abgegebenen) Tipps der
  anderen zu genau diesem einen Spiel gesehen — jedes Spiel einzeln, unabhängig vom Rest des Spieltags.
- **Per Nachfrage geklärt:** Aufdecken soll gemeinsam für den **ganzen Spieltag/die ganze Runde** passieren,
  und zwar automatisch **sobald das früheste Spiel dieses Spieltags anpfeift** (kein zusätzlicher admin-seitig
  einstellbarer Zeitpunkt nötig) — einheitlich für **alle** Wettbewerbe (Liga, Pokal, Europapokale, Nations
  League), als feste neue Regel ohne Ein-/Ausschalter pro Runde.
- **Umgesetzt:** In der REST-Route `GET /tips` wird jetzt zuerst pro Spieltag/Runde der früheste Sperrzeitpunkt
  alle darin enthaltenen Spiele ermittelt (`Anpfiff − Frist-Minuten` des frühesten Spiels). Die Sichtbarkeit der
  Mitspieler-Tipps ("revealed") hängt jetzt an diesem gemeinsamen Zeitpunkt statt am eigenen Commit-Status.
  Das eigene Eintragen/Sperren einzelner Spiele (kann ein bereits angepfiffenes Spiel nicht mehr getippt
  werden) bleibt unverändert pro Spiel. Hinweistext im Tippen-Tab entsprechend angepasst.
  PHP-Syntax mit `php -l`, JS-Syntax mit `node --check` geprüft. Keine Datenbank-Änderung nötig.
- *Noch nicht live getestet:* Plugin-Update auf v0.9.4 einspielen, bei einem Spieltag mit mehreren Spielen an
  verschiedenen Tagen prüfen, dass Mitspieler-Tipps zu einem bereits gesperrten Freitagsspiel **nicht**
  sichtbar sind, solange andere Spiele desselben Spieltags noch offen sind — erst sobald das ERSTE Spiel des
  Spieltags angepfiffen hat, sollten alle (bis dahin abgegebenen) Tipps des ganzen Spieltags erscheinen.

## v0.9.5 — Ganzer Spieltag sperrt gemeinsam (nicht nur sichtbar, auch änderbar) + Bugfix "Tipp abgeben" bei Sonderwertungen
- **Nachfrage zu v0.9.4:** "Man muss bis 19 Uhr für Freitag, Samstag, Sonntag tippen, dann sieht man auch die
  Tipps der anderen." — Klargestellt: v0.9.4 hatte nur die **Sichtbarkeit** an den gemeinsamen Zeitpunkt
  gekoppelt, aber einzelne Spiele blieben bis zu ihrem **eigenen** Anpfiff weiter änderbar. Das hätte die
  eigentliche Lücke nicht geschlossen: Jemand hätte nach dem Freitags-Stichtag die (jetzt sichtbaren) Tipps
  der anderen zu Samstag/Sonntag sehen und seinen eigenen Tipp danach noch anpassen können.
- **Fix:** Neue zentrale Funktion `ftipp_round_lock_ts()` — der früheste Anpfiff-minus-Frist-Zeitpunkt über
  alle Spiele eines Spieltags. Ab diesem einen gemeinsamen Zeitpunkt sind jetzt **alle** Spiele dieses
  Spieltags gleichzeitig **komplett gesperrt** (kein Eintragen/Ändern mehr möglich, nicht nur "sichtbar") —
  angewendet in `GET /tips` (Anzeige) und `POST /tip` (Speichern, lehnt jetzt mit derselben Sperre ab). Gilt
  einheitlich für alle Wettbewerbe. Kein Datenbank-Update nötig.
- **Bugfix "Tipp abgeben" bei Sonderwertungen (v0.9.3) hing fest:** Nutzer meldete, der Button bliebe
  ausgegraut, obwohl ein Tipp eingetragen war — "man muss immer erst den Cache löschen". Ursache: der
  "Tipp abgeben"-Button wurde beim Tippen nie live wieder aktiviert (`disabled` blieb am gerenderten
  Button-Element hängen, weil `oninput` nur die Variable, nicht das DOM-Attribut aktualisierte) — dieselbe
  Fehlerklasse wie der "Tipp abgeben"-Bug bei Spiel-Tipps aus v0.8.1. Mit demselben, dort schon bewährten
  Muster (`commitBtn`-Referenz + Live-Sync bei jedem Tastenanschlag) behoben — kein Cache-Leeren nötig.
  JS-Syntax mit `node --check`, PHP-Syntax mit `php -l` geprüft.
- *Noch nicht live getestet:* Plugin-Update auf v0.9.5 einspielen. (1) Bei einem Spieltag mit mehreren Tagen
  prüfen, dass nach dem Freitags-Stichtag **auch** Samstag-/Sonntagsspiele nicht mehr änderbar sind (nicht nur
  sichtbar). (2) Bei einer Sonderwertung einen Tipp eintippen und direkt "Tipp abgeben" klicken — sollte jetzt
  ohne Cache-Löschen sofort klickbar werden.

## v0.9.6 — Nations-League-Gruppen: kurze offizielle Bezeichnung (A1–D2) statt langer Eigenbau-Namen
- **Anfrage:** Die "Gruppensieger"-Sonderwertungen ("Gruppensieger Liga A – Gruppe 2 (Deutschland ·
  Griechenland · Niederlande · Serbien)") waren so lang, dass die Bezeichnung im Eingabefeld abgeschnitten
  wurde — Screenshot zeigte 14 kaum unterscheidbare, abgeschnittene Einträge. Nutzer bat, stattdessen die
  Bezeichnung von einer Google-Suche zur echten Nations-League-Tabelle zu übernehmen.
- **Recherchiert (Wikipedia + UEFA.com, alle 4 Ligen einzeln nachgeprüft):** Die UEFA nummeriert die 14
  Gruppen offiziell **nicht** fortlaufend A–N, sondern als **A1–A4, B1–B4, C1–C4, D1–D2** — pro Liga eigene
  Zählung. Die Zuordnung ergibt sich aus der Setzliste/Auslosung und lässt sich nicht aus den Spieldaten
  ableiten, deshalb fest hinterlegt (mit Kommentar, dass das zur nächsten Auslosung 2028/29 neu geprüft werden
  muss — gleiches Prinzip wie schon bei den CL/EL-Shortcuts). Dabei auch geprüft: **die schon vorhandenen
  Team-Zusammensetzungen aus der recherchierten CSV waren alle korrekt** — nur die eigene, alphabetisch
  durchgezählte Nummerierung (unsere "Gruppe 1/2/3/4") stimmte nicht in jedem Fall mit der offiziellen
  A1-A4-Nummerierung überein (z.B. war unsere "Gruppe 3" bei Liga A offiziell "A4").
- **Umgesetzt:**
  - `ftipp_nl_groups()` ordnet jeder Gruppe jetzt über eine feste Team→Code-Tabelle den offiziellen Code zu
    (z.B. "A1"), Bezeichnung ist jetzt schlicht **"Gruppensieger A1"** statt der langen Variante. Fallback auf
    die alte Zählweise, falls sich die Team-Zusammensetzung mal ändert (Auslosung stimmt nicht mehr überein).
  - Neue **einmalige Migration** (`ftipp_migrate_nl_group_labels()`, FTIPP_DB_VERSION 8 → 9): liest bei bereits
    angelegten Sonderwertungen die Teams aus der Klammer der alten Bezeichnung, ordnet sie derselben Tabelle zu
    und benennt sie automatisch um — **kein manuelles Nachbearbeiten der 14 schon angelegten Einträge nötig.**
  - Tabelle-Tab und die neue Gruppen-Auswertung (v0.9.2) zeigen entsprechend jetzt "Gruppe A1" usw. statt der
    langen Variante.
  - PHP-Syntax mit `php -l` geprüft, Migrations-Logik isoliert mit einem Beispiel-Label gegengetestet.
- *Noch nicht live getestet:* Plugin-Update auf v0.9.6 einspielen (löst automatisch die Migration aus), bei
  ⭐ Sonderwertungen → Nations League prüfen, dass alle 14 "Gruppensieger"-Einträge jetzt kurz "Gruppensieger
  A1" bis "Gruppensieger D2" heißen und nicht mehr abgeschnitten werden. Ebenso Tabelle-Tab und Auswertung
  gegenprüfen.

## v0.9.7 — Ursache der Gruppensieger-Verwechslung gefunden + sauberer Reset statt Text-Reparatur
- **Live-Test von v0.9.6:** Nutzer meldete zwei "Gruppensieger A1"-Einträge mit unterschiedlicher Frist
  (25.09. und 24.09.) und eine komplett fehlende "Gruppensieger A4". Anhand der Fristen erkannt: die zweite
  "A1" hatte tatsächlich die Frist von Deutschlands Gruppe (A2, erstes Spiel 24.09.) — also eine echte
  Verwechslung, kein Wahrnehmungsfehler.
- **Wahrscheinliche Ursache gefunden:** `ftipp_nl_groups()` erkennt Gruppen per Union-Find rein aus den
  Team-Paarungen in den geladenen Spielen. Der "🎲 Test-Spiele laden"-Button legt aber **eigene Demo-Spiele**
  für die Nations League an (u.a. "Deutschland–Ungarn" und "Bosnien und Herzegowina–Niederlande") — mit
  **denselben Teamnamen** wie in der echten Gruppe A2 (Deutschland, Niederlande, …). Landen diese Demo-Spiele
  zusätzlich zu den echten CSV-Spielen im System, verbindet die Gruppenerkennung über die gemeinsamen
  Teamnamen fälschlich mehrere echte Gruppen zu einer viel zu großen "Gruppe", die zu keinem echten 4er-Team-
  Satz mehr passt — mit Folgeeffekten wie einer falsch zugeordneten Bezeichnung nach der v0.9.6-Umbenennung.
- **Zwei Fixes:**
  1. `ftipp_nl_groups()` schließt Demo-Spiele (IDs mit "demo-"-Präfix) jetzt explizit von der
     Gruppenerkennung aus — behebt die Ursache dauerhaft, auch wenn nochmal Test-Spiele geladen werden.
  2. Statt zu versuchen, die durch den Fehler bereits falsch benannten Sonderwertungen textbasiert zu
     reparieren (fehleranfällig, siehe v0.9.6), werden alle bestehenden NL-"Gruppensieger"-Einträge (inkl.
     ihrer bisherigen Tipps dazu) einmalig **gelöscht** und beim nächsten Öffnen des Sonderwertungen-Tabs aus
     der jetzt korrekten Gruppenerkennung **sauber neu angelegt** — inklusive der zuvor fehlenden Gruppe A4.
     "Nations-League-Sieger" und "Torschützenkönig" bleiben unangetastet.
  - PHP-Syntax mit `php -l` geprüft.
- ⚠️ **Nebenwirkung, die der Nutzer wissen muss:** Die 13 bisherigen Gruppensieger-Tipps (u.a. "Frankreich"
  für A1) gehen dabei verloren und müssen neu eingetippt werden — bei dem Stand (Saison hat gerade erst
  begonnen, nur ein Test-Tipp vorhanden) unkritisch. Falls jemand eine Gruppensieger-Wette bewusst gelöscht
  hatte, kommt die durch den Reset ebenfalls wieder zurück (kann danach erneut gelöscht werden).
- *Noch nicht live getestet:* Plugin-Update auf v0.9.7 einspielen, Sonderwertungen → Nations League öffnen
  (löst den Reset + die Neu-Anlage aus), prüfen dass jetzt genau 14 Gruppensieger-Einträge (A1–D2, keine
  Duplikate) mit korrekten, zu den echten Spielterminen passenden Fristen erscheinen.

## v0.9.8 — Teams neben der Gruppensieger-Bezeichnung anzeigen
- **Anfrage:** "Gruppensieger A1" allein sagt nicht, welche Teams gemeint sind — Nutzer bat, die Teams rechts
  neben dem Bezeichnungsfeld anzuzeigen (nicht ins Feld selbst reinschreiben).
- **Umgesetzt:** `GET /special` liefert jetzt bei der Nations League zusätzlich `teams` pro Sonderwertung
  (nachgeschlagen über `ftipp_nl_groups()`, gematcht per Label). Frontend zeigt das als kleinen Text direkt
  neben "Gruppensieger A1" an — z.B. "Frankreich, Italien, Belgien, Türkei" — sowohl in der Admin-Ansicht
  (neben dem editierbaren Bezeichnungsfeld) als auch in der Spieler-Ansicht. Bei anderen Sonderwertungen
  (Meister, Torschützenkönig usw.) erscheint nichts Zusätzliches, da dort kein Team-Bezug existiert.
  PHP-Syntax mit `php -l`, JS-Syntax mit `node --check` geprüft.
- *Noch nicht live getestet:* Plugin-Update auf v0.9.8 einspielen, bei Sonderwertungen → Nations League
  prüfen, dass neben jedem "Gruppensieger A1" bis "D2" die vier (bzw. bei D1/D2 drei) zugehörigen Länder stehen.

## v0.11.0 — Drei neue Wettbewerbe: 3. Liga, Premier League, LaLiga
- **Anfrage:** Bei der Recherche zu OpenLigaDB (siehe gestern) sind noch weitere aktuelle Top-Ligen gefunden
  worden — Nutzer wollte 3. Liga (Deutschland), Premier League (England) und LaLiga (Spanien) zusätzlich dazu.
- **Vorab Datenqualität geprüft (wie immer bei einer neuen Quelle, echte API-Calls):** Alle drei liefern über
  OpenLigaDB für 2026/27 vollständige Saisondaten — je 380 Spiele, 20 Teams, volle Spielzeit August 2026 bis
  Mai 2027, deutsche Rundennamen ("1. Spieltag" usw., keine Übersetzung nötig).
- **Umgesetzt:**
  - Drei neue Wettbewerbe `BL3`, `PL`, `LA1` in `ftipp_leagues()`, jeweils `kind: 'league'`.
  - In den OpenLigaDB-Automatik-Abruf aufgenommen (Shortcuts `bl3`, `pl`, `la1` — laut Community-Historie
    stabil, anders als bei CL/EL keine jährliche Umbenennung zu erwarten).
  - Passende Standard-Sonderwertungen ergänzt: 3. Liga wie BL1/BL2 (inkl. Herbstmeister, deutsche Tradition),
    Premier League/LaLiga mit Meister/Absteiger/Torschützenkönig/Bester Passgeber — bewusst **ohne**
    Herbstmeister, das ist eine rein deutsche Bundesliga-Eigenheit ohne Entsprechung in England/Spanien.
  - "📊 Tabelle" (v0.9.0) und die Wettbewerbs-Dropdowns überall funktionieren automatisch mit, keine
    Extra-Anpassung nötig — beides ist schon generisch für jeden Wettbewerb mit OpenLigaDB-Shortcut gebaut.
  - Header-Untertitel, Plugin-Beschreibung, Einstellungsseiten-Texte und CONTEXT.md-Glossar (jetzt neun statt
    sechs Wettbewerbe) an allen Stellen aktualisiert.
  - **Vor dem Versand komplett lokal durchgetestet** (gleiches lokales WordPress+SQLite-Setup wie beim
    Design-Tab): "Spieldaten jetzt abrufen" ausgelöst → alle drei liefern 380 Spiele; im Tippen-Tab echte
    Premier-League-Spiele geprüft (Ipswich–Liverpool, Man City–Coventry, …); Tabelle-Tab zeigt eine korrekte,
    echte Premier-League-Tabelle mit Tabellenstand nach Spieltag 3.
  - PHP-Syntax mit `php -l`, JS-Syntax mit `node --check` geprüft, zusätzlich zur lokalen End-to-End-Prüfung.
- *Noch nicht auf der echten Seite getestet:* Plugin-Update auf v0.11.0 einspielen, unter 👥 Runden die drei
  neuen Wettbewerbe abonnieren, "Spieldaten jetzt abrufen" klicken, dann Tippen/Tabelle/Sonderwertungen für
  3. Liga, Premier League und LaLiga live gegenprüfen.

## v0.12.0 — Süper Lig (Beta) über ESPN + wichtiger Fund: ESPN aus PHP heraus unzuverlässig
- **Anfrage:** Türkische Süper Lig sollte auch mit rein. OpenLigaDB hat dafür seit 2013/2014 keine aktuellen
  Daten mehr — bei der Recherche zu Alternativ-APIs (siehe gestern) aber die ESPN-"Hidden API" gefunden, die
  über den Slug `tur.1` echte, aktuelle Spieldaten liefert (18 Teams, 306 Saisonspiele, live geprüft).
- **Wichtiger technischer Fund während der Umsetzung:** ESPNs API funktioniert vom Terminal aus (curl)
  zuverlässig, aber Anfragen **aus PHP heraus** (also genau das, was das Plugin auf dem echten Server macht)
  wurden mit HTTP 403 "Access Denied" von ESPNs Bot-Schutz (Akamai) geblockt — auch mit echtem
  Browser-User-Agent und erzwungenem HTTP/1.1. Ursache vermutlich: PHPs curl-Modul nutzt eine andere
  TLS-Bibliothek (OpenSSL) als das Mac-Terminal (SecureTransport), was einen anderen, für Bot-Erkennung
  verdächtigeren "Fingerabdruck" der Verbindung erzeugt — technisch nicht zuverlässig mit ein paar
  Codezeilen zu beheben, kann je nach Hosting-Server auch unterschiedlich ausfallen.
- **Entscheidung (Rücksprache):** Code bleibt drin (bester Versuch — auf manchen Servern könnte es
  funktionieren), aber der Wettbewerb ist überall klar als **"Süper Lig (Beta)"** gekennzeichnet — im
  Dropdown, im Header-Untertitel und mit Erklärung auf der Einstellungsseite, damit klar ist: könnte auf
  eurem Server einfach nicht funktionieren, ohne dass es wie ein Fehler im Plugin aussieht.
- **Umgesetzt:**
  - Neue Fetch-Funktion `ftipp_fetch_espn_soccer()` — übersetzt ESPNs Antwortformat ins interne
    Spiel-Format, inkl. eigener Spieltag-Ableitung (ESPN liefert keine Spieltag-Nummer bei Liga-Spielen —
    wird aus dem Kalender abgeleitet: chronologisch sortieren, in Blöcken von Team-Anzahl/2 gruppieren).
  - Zeitzone bewusst korrekt behandelt: ESPN liefert UTC, wird über `get_date_from_gmt()` in die
    WordPress-Standort-Zeitzone umgerechnet (sonst Anpfiffzeiten falsch angezeigt).
  - Neuer Wettbewerb `TR1` ("Süper Lig (Beta)"), Standard-Sonderwertungen (Meister/Absteiger/
    Torschützenkönig/Bester Passgeber, ohne Herbstmeister wie bei PL/LA1), echte Tabelle über die schon
    vorhandene `ftipp_mini_table()`-Funktion (aus den eigenen Spieldaten berechnet, nicht von ESPN bezogen).
  - PHP-Syntax mit `php -l`, JS-Syntax mit `node --check` geprüft; komplett lokal durchgetestet — dabei
    genau das ESPN-Zuverlässigkeitsproblem entdeckt und dokumentiert, bevor es live gegangen wäre.
- *Noch nicht auf der echten Seite getestet:* Plugin-Update auf v0.12.0 einspielen (enthält zusätzlich die
  v0.11.0-Ligen 3. Liga/Premier League/LaLiga, siehe oben), "Spieldaten jetzt abrufen" klicken, in der
  "Letzter Abruf"-Tabelle bei Süper Lig schauen ob's auf eurem Hostinger-Server funktioniert oder den
  ESPN-Fehler zeigt — beides ist ein gültiges, erwartetes Ergebnis bei einer Beta-Quelle.

## v1.0.0 — Erste Version 1
Auf Wunsch des Nutzers: "Ich glaube, wir sind schon fertig für eine Version 1." Kein Feature-Sprung, reiner
Versionssprung von 0.12.0 auf 1.0.0 (Bugfixes zählen ab hier wieder klassisch als 1.0.x). Markiert den Punkt,
an dem Tippstube als ausgereift genug für eine "1.0" gilt — nach vielen Monaten Entwicklung, echtem Live-Betrieb
mit einer Familienrunde, und zwei ernsthaften Vorfällen (Seitenausfall, verwaiste DFB-Pokal-Tipps), die beide
sauber aufgeklärt und in dauerhafte Absicherungen umgesetzt wurden.

**Stand der Funktionen bei v1.0.0:**
- 10 Wettbewerbe: 1./2./3. Liga, DFB-Pokal, Champions League, Europa League, Nations League, Premier League,
  LaLiga, Süper Lig (Beta) — je nach Wettbewerb über OpenLigaDB, API-Football, CSV-Import oder ESPN
- Tipprunden mit Einladungscode, pro Runde eigenes Punktesystem/Modus/Wettbewerbs-Abo
- Rangliste, echte Liga-/Turniertabelle, Tipp-Historie, Statistiken, Achievements
- Sonderwertungen inkl. automatischer Nations-League-Gruppensieger-Wetten (14 Gruppen)
- K.o.-Zusatztipp, Spieltag-weite Tipp-Sperre/-Sichtbarkeit, Fristen-Erinnerung, Ranking-Newsletter
- Runden-Import/Export (Backup), DSGVO-Grundausstattung, Pinnwand-Chat pro Runde

## v0.5.0 — Süper Lig jetzt per CSV, zwei neue Ligen (Frauen-Bundesliga, Regionalliga Nordost), Versionsnummer & Autor angepasst
- **Süper Lig auf CSV umgestellt:** Nach dem live bestätigten ESPN-HTTP-403-Problem auf dem echten Server (siehe
  v0.12.0) wie besprochen auf CSV-Import umgestellt — genau wie die Nations League. Die komplette Saison
  2026/27 (306 Spiele, 18 Teams, inkl. bereits gespielter Ergebnisse) recherchiert und als
  `sueper-lig-2026-27.csv` bereitgestellt, per Terminal-Zugriff auf dieselbe ESPN-API (die von dort zuverlässig
  funktioniert). Zeitzone bewusst korrekt von UTC nach Europe/Berlin umgerechnet. "(Beta)"-Kennzeichnung wieder
  entfernt, da CSV-Import genauso zuverlässig ist wie bei der Nations League — kein Beta-Vorbehalt mehr nötig.
  Die "📊 Tabelle"-Berechnung wurde dabei generalisiert: jeder Wettbewerb ohne OpenLigaDB-Shortcut bekommt jetzt
  automatisch eine selbst berechnete Tabelle aus den eigenen Spieldaten (vorher war das speziell an "ESPN"
  geknüpft, jetzt komplett quellenunabhängig).
- **Zwei neue Wettbewerbe** (auf Nutzerwunsch, direkt vom OpenLigaDB-Übersichtsscreenshot):
  - **Frauen-Bundesliga** (`FBL`, Shortcut `ffb1`) — 182 Spiele, 14 Teams, live geprüft.
  - **Regionalliga Nordost** (`RLNO`, Shortcut `rlno`) — 306 Spiele, 18 Teams, live geprüft. Entspricht der
    "NOFV Regionalliga NordOst" von der OpenLigaDB-Startseite (derselbe Shortcut, nur der vollständige Name).
  - **Bewusst NICHT eingebaut:** "Frauen-Nationalmannschaft" (OpenLigaDB hat dafür nur 2 Freundschaftsspiele
    insgesamt — auf Rückfrage als zu dünn für einen eigenen Wettbewerb eingestuft) und "Oberliga Nord
    2026/2027" (stellte sich beim Datencheck als **Eishockey**-Liga heraus, nicht Fußball — Team-Namen wie
    "Herner EV Miners" haben das verraten. Tippstube bleibt reines Fußball-Tippspiel.).
  - Passende Standard-Sonderwertungen ergänzt, für die Frauen-Bundesliga bewusst mit weiblichen
    Bezeichnungen (Deutsche Meisterin, Herbstmeisterin, Absteigerin, Torschützenkönigin, Beste Passgeberin).
- **Versionsnummer & Autor auf Nutzerwunsch angepasst:** Kurzzeitig als v1.0.0 geführt ("wir sind schon fertig
  für eine Version 1"), dann auf ausdrücklichen Wunsch wieder auf **0.5.0** zurückgesetzt — kein technischer
  Grund, reine Entscheidung des Nutzers zur Versionsnummerierung. Plugin-Autor-Feld von "Tippstube" auf
  "Florian Henschke" geändert (erscheint in der Plugin-Liste als "Version 0.5.0 | Von Florian Henschke").
- PHP-Syntax mit `php -l`, JS-Syntax mit `node --check` geprüft; CSV-Import und beide neuen Ligen komplett
  lokal durchgetestet (echtes WordPress+SQLite-Setup) vor dem Versand.
- *Noch nicht auf der echten Seite getestet:* Plugin-Update auf v0.5.0 einspielen, `sueper-lig-2026-27.csv`
  hochladen (Einstellungen → CSV importieren), "Spieldaten jetzt abrufen" für Frauen-Bundesliga/Regionalliga
  Nordost klicken, alle drei in einer Runde abonnieren und im Tippen-/Tabelle-Tab gegenprüfen.

## v0.6.0 — Drei weitere Top-Ligen: Serie A, Ligue 1, Ekstraklasa (per CSV/API-Football)
- **Anfrage:** Serie A (Italien), Ligue 1 (Frankreich) und Ekstraklasa (Polen) zusätzlich einbauen — sollen
  sowohl über API-Football laufen können als auch alternativ per CSV, genau wie die Nations League.
- **Vorab geprüft:** OpenLigaDB hat für alle drei aktuell keine Daten. ESPN (Terminal-Zugriff) hat Serie A
  (`ita.1`: 380 Spiele, 20 Teams) und Ligue 1 (`fra.1`: 306 Spiele, 18 Teams) einwandfrei — beide als CSV
  recherchiert und beigelegt (`serie-a-2026-27.csv`, `ligue-1-2026-27.csv`, inkl. bereits gespielter
  Ergebnisse, Zeitzone korrekt nach Europe/Berlin umgerechnet). Für **Ekstraklasa** gibt es dagegen bei keiner
  der bekannten kostenlosen Quellen (OpenLigaDB, ESPN, TheSportsDB) überhaupt Daten — komplette ESPN-
  Ligenliste (218 Wettbewerbe) durchsucht, kein einziger Polen-Eintrag vorhanden.
- **Umgesetzt:** Alle drei als neue Wettbewerbe registriert (`ITA1`, `FRA1`, `POL1`) mit echten
  API-Football-Liga-IDs (Serie A 135, Ligue 1 61, Ekstraklasa 106) — dadurch automatisch **beide** vom Nutzer
  gewünschten Wege nutzbar, ganz ohne Sondercode: die bestehende generische API-Football-Fallback-Schleife und
  der generische CSV-Import greifen für jeden registrierten Wettbewerb automatisch. Für Ekstraklasa gibt es von
  mir (mangels Quelle) keine fertige CSV — die Möglichkeit ist aber technisch bereits vorhanden, falls der
  Nutzer selbst eine Datenquelle findet oder einen bezahlten API-Football-Tarif nutzt.
  Passende Standard-Sonderwertungen ergänzt (Meister/Absteiger/Torschützenkönig/Bester Passgeber, wie bei
  Premier League/LaLiga/Süper Lig — ohne Herbstmeister, das bleibt eine deutsche Eigenheit).
- PHP-Syntax mit `php -l`, JS-Syntax mit `node --check` geprüft; CSV-Import für Serie A/Ligue 1 komplett lokal
  durchgetestet (380 bzw. 306 Spiele fehlerfrei importiert, im Tippen-Tab sichtbar).
- *Noch nicht auf der echten Seite getestet:* Plugin-Update auf v0.6.0 einspielen, `serie-a-2026-27.csv` und
  `ligue-1-2026-27.csv` hochladen, alle drei neuen Wettbewerbe in einer Runde abonnieren und gegenprüfen.

## v0.7.0 — Tippstube als eigener Menüpunkt im WP-Admin (nicht mehr unter „Einstellungen")
- **Anfrage:** Screenshot der Admin-Sidebar zeigte, dass „Tippstube" dort nirgends als eigener
  Menüpunkt auftaucht (nur versteckt unter „Einstellungen → Tippstube"). Wunsch: direkt als
  Top-Level-Eintrag im Menü, gut sichtbar.
- **Umgesetzt:** Registrierung von `add_options_page()` auf `add_menu_page()` umgestellt (Position 30,
  eigenes Icon). Dadurch läuft die Einstellungsseite jetzt über `admin.php?page=ftipp` statt
  `options-general.php?page=ftipp` — alle sechs betroffenen Stellen im Code angepasst (fünf
  `wp_safe_redirect()`-Ziele nach Demo/CSV-Import/Abruf/Test-Mail-Aktionen sowie der „Einstellungen"-Link
  in der Plugin-Übersicht). Der unabhängige Link zu WordPress' eigener „Einstellungen → Allgemein"-Seite
  (Hinweistext zur Mitgliedschaft) blieb bewusst unverändert, da er nichts mit Tippstube zu tun hat.
- Für das Menü-Icon eine eigene, einfarbige SVG-Variante (`ftipp_menu_icon_href()`) ergänzt: WordPress
  erkennt fürs Admin-Menü nur den Präfix `data:image/svg+xml;base64,` und färbt ein einfarbiges SVG
  passend zum Menü-Theme ein — die bunte Favicon-Version (`ftipp_favicon_href()`, weiterhin für den
  Browser-Tab-Favicon genutzt) hätte dort nur ein leeres Icon ergeben (erst lokal getestet und genau so
  beobachtet, dann korrigiert).
- **Lokal komplett durchgetestet** (WordPress+SQLite-Testumgebung): neuer Menüpunkt erscheint korrekt
  zwischen „Kommentare" und „Design" in der Sidebar, Icon wird sauber gerendert, „Einstellungen" zeigt
  keinen Tippstube-Eintrag mehr. Alle Aktions-Buttons (Spieldaten abrufen, CSV-Import) per Browser bzw.
  gezieltem `curl`-Test der `admin-post.php`-Endpunkte durchgespielt — beide leiten korrekt wieder auf
  `admin.php?page=ftipp` (mit den erwarteten Status-Parametern) zurück, keine 404/kaputten Links.
- *Noch nicht auf der echten Seite getestet:* Plugin-Update auf v0.7.0 einspielen und den neuen
  Menüpunkt sowie alle Buttons einmal live gegenprüfen.

## v0.7.1 — Cooleres Menü-Icon (Schild mit Ball-Motiv statt reiner Silhouette)
- **Feedback nach Live-Test von v0.7.0:** Der neue Top-Level-Menüpunkt ist da, aber das Icon (reiner
  schwarzer Schild-Umriss ohne Details) wirkte zu plump/langweilig — Wunsch nach einem "coolen Logo".
- **Umgesetzt:** `ftipp_menu_icon_href()` neu gestaltet — Design von grafik-greta (neu ins Projektteam
  geholt, künftig zuständig für visuelle Weiterentwicklung). Statt der reinen Schild-Fläche wird jetzt per
  `fill-rule="evenodd"` ein Kreis (Ball) aus dem Schild ausgestanzt und darin ein Fünfeck (Ball-Pentagon)
  wieder aufgefüllt — dasselbe Ball-im-Tor-Motiv wie im bunten Favicon, nur einfarbig übersetzt und robust
  genug für die 20×20px-Darstellungsgröße im WP-Menü (keine dünnen Linien, die bei der Größe verschwimmen
  würden).
- Lokal geprüft: SVG isoliert bei großer und bei 20×20px-Originalgröße gerendert, danach live in der
  Admin-Sidebar (u.a. 6-fach vergrößert per CSS-Debug-Transform) kontrolliert — Schild mit Ball-Akzent klar
  erkennbar und deutlich von den anderen Menü-Icons unterscheidbar.
- *Noch nicht auf der echten Seite getestet:* Plugin-Update auf v0.7.1 einspielen und Icon im echten
  Adminmenü gegenprüfen.

## v0.8.0 — Tippstube-Adminmenü in Untermenüpunkte aufgeteilt (Einstellungen/Design/Cron-Job/History/Changelog/Info)
- **Anfrage:** Nach dem Umzug ins Hauptmenü (v0.7.0) sollte "Tippstube" jetzt — analog zu WordPress' eigenem
  "Einstellungen"-Menü mit seinen Untereinträgen — ein eigenes Untermenü bekommen: Einstellungen, Design,
  Cron-Job, History, Changelog, Info.
- **Vorab geklärt (Planungsrunde mit zwei Recherche-Agents + Plan-Agent, danach Nutzerentscheidungen):**
  - *Design* wird ein neuer Backend-Überblick aller Tipprunden für den Plattform-Admin (Akzentfarbe, Logo,
    Untertitel zentral einsehbar/editierbar) — NICHT identisch mit dem früher zurückgestellten Design-Tab
    (der war fürs Frontend/Runden-Admin gebaut, siehe v0.10.0-Stash, bleibt separates Zukunftsfeature).
  - *History* protokolliert künftig ALLE datenverändernden Aktionen (Cron-Abruf, manuelles Abrufen,
    CSV-Import, Test-Spiele) — braucht eine neue Tabelle, da die bestehende `ftipp_meta`-Option nur den
    letzten Zustand hält, keinen Verlauf.
  - *Cron-Job* bekommt eine frei einstellbare Zeitangabe (Zahl + Einheit) statt fester Presets — das
    bestehende Cron-System (`ftipp_weekly_fetch`, seit v0.x fest auf `weekly`) wird dafür erweitert.
  - *Changelog* wird automatisch aus diesem Journal generiert. Wichtiger technischer Fund dabei: das Journal
    liegt nur auf Projektebene, nicht im Plugin-Ordner — der Live-Server hat keinen Zugriff darauf. Ab dem
    Changelog-Release (geplant v0.8.5) wird deshalb bei jedem künftigen Release-Build zusätzlich eine Kopie
    als `fussball-tippspiel/CHANGELOG.md` mitgeführt.
- **Umgesetzt (dieser Schritt, v0.8.0):** Nur die Menü-Struktur — sechs `add_submenu_page()`-Aufrufe unter
  dem bestehenden `add_menu_page()` (Slug-Trick: der erste Untermenüpunkt bekommt denselben Slug `ftipp` wie
  der Parent, das ersetzt WordPress' automatisch erzeugten Default-Eintrag statt "Tippstube" doppelt
  anzuzeigen). Die bestehende Einstellungen-Seite (`ftipp_settings_page()`) bleibt komplett unverändert und
  unter demselben Slug `ftipp` erreichbar — alle fünf bestehenden `admin_post`-Redirects und der
  Plugin-Actions-Link mussten dadurch NICHT angepasst werden. Die fünf neuen Untermenüpunkte zeigen vorerst
  nur einen "Kommt in Kürze"-Platzhalter (`ftipp_page_design/cron/history/changelog/info()`), Inhalt folgt
  in den nächsten Versionsschritten (0.8.1 Info, 0.8.2 Cron-Job, 0.8.3 History, 0.8.4 Design, 0.8.5 Changelog
  — bewusst inkrementell, jeder Schritt einzeln lokal und live getestet, DB-Migrationen (History/Design)
  bewusst nach den risikoärmeren Schritten einsortiert).
- Lokal getestet: alle sechs Menüpunkte erscheinen korrekt (kein doppelter "Tippstube"-Eintrag), jeder
  Platzhalter lädt fehlerfrei mit korrekter Hervorhebung im Untermenü, Regressionstest des bestehenden
  "Jetzt abrufen"-Buttons per `curl` bestätigt unveränderten Redirect auf `admin.php?page=ftipp&ftipp_done=ok`.
- *Noch nicht auf der echten Seite getestet:* Plugin-Update auf v0.8.0 einspielen, Untermenü-Struktur und
  bestehende Aktionen (Fetch/CSV/Test-Buttons) einmal live gegenprüfen.

## v0.8.1 — Info-Seite mit Inhalt gefüllt
- Zweiter Schritt des Untermenü-Ausbaus (nach v0.8.0): der Platzhalter unter "Info" bekommt echten Inhalt —
  eine kurze, statische Anleitung/Glossar für den Plattform-Admin, ohne Formular oder DB-Zugriff.
- Abschnitte: Tippen (Frist, Geheimhaltung bis Bestätigung, K.o.-Zusatztipp), Punktesystem & Modus
  (Tendenz/Exakt/K.o.-Punkte, Malus, Freundschaftlich vs. Challenge), Sonderwertungen (automatischer
  Textvergleich, manuelle Übersteuerung), CSV-Import (Kurzfassung, Duplikat-Schutz per Wettbewerb+Teams+Datum),
  Rollen (Plattform-Admin/Runden-Admin/Mitspieler, Begriffe 1:1 aus CONTEXT.md), Über & Kontakt (Autor,
  GitHub-Link).
- Lokal getestet: Seite rendert fehlerfrei, alle Umlaute/Anführungszeichen korrekt, kein PHP-Fehler im Log.
- *Noch nicht auf der echten Seite getestet:* Plugin-Update auf v0.8.1 einspielen, Info-Seite gegenprüfen.

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
