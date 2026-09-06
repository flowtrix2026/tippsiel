=== Tippstube ===
Contributors: florianhenschke
Tags: fussball, tippspiel, sport, community, bundesliga
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.9.5
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Privates Fußball-Tippspiel für deine Familie oder Freundesrunde — echtes WordPress-Login, eigene Tipprunden, automatischer Datenabruf.

== Description ==

Tippstube ist ein WordPress-Plugin für private Fußball-Tippspiele unter Familie und Freunden. Jeder Nutzer meldet sich mit seinem echten WordPress-Konto an, tritt per Einladungscode einer oder mehreren "Tipprunden" bei und tippt dort gemeinsam mit den anderen Mitgliedern — jede Runde hat für jeden Wettbewerb ihre eigene, unabhängige Rangliste.

= Funktionen für Mitspieler =

* **14 Wettbewerbe**: 1./2./3. Bundesliga, DFB-Pokal, Champions League, Europa League, Nations League, Premier League, LaLiga, Süper Lig, Serie A, Ligue 1, Frauen-Bundesliga, Regionalliga Nordost
* **Punktesystem**: 1 Punkt für die richtige Tendenz, 3 Punkte fürs exakte Ergebnis, optionaler K.o.-Zusatztipp bei Pokalspielen
* **Zwei Ranglisten-Modi**: Freundschaftlich (geteilter Platz bei Gleichstand) oder Challenge (harter Tie-Break)
* **Sonderwertungen** je Wettbewerb (Meister, Torschützenkönig etc.), automatische Auflösung per Textvergleich
* **Pinnwand-Chat** und **Statistik/Achievements** pro Runde
* **E-Mail-Benachrichtigungen**: Fristen-Erinnerung, wöchentlicher Ranking-Newsletter

= Funktionen für den Plattform-Admin =

Sieben Verwaltungsseiten unter "Tippstube" im WordPress-Adminmenü:

* **Einstellungen** — API-Football-Key, automatischer Datenabruf, CSV-Import für Wettbewerbe ohne gute kostenlose Quelle
* **Design** — Akzentfarbe, Logo und Untertitel jeder Tipprunde zentral einsehbar und bearbeitbar
* **Cron-Job** — frei einstellbarer Abrufplan, dazu ein optionaler zuverlässiger externer Cron-Dienst (manuell oder automatisch über die cron-job.org-API)
* **History** — Protokoll aller automatischen und manuellen Datenänderungen
* **Changelog** — die komplette Versionshistorie direkt im Adminbereich
* **Info** — Kurzanleitung und Glossar
* **Datensicherung** — alle Tippstube-Daten als Datei sichern (Download oder automatisch per E-Mail) und bei Bedarf wiederherstellen, inklusive automatischer Sicherheitskopie vor jedem Restore

Updates erscheinen wie bei einem regulären WordPress.org-Plugin direkt im Plugins-Bereich ("Update verfügbar").

= Datenquellen =

1./2./3. Liga, DFB-Pokal, Champions League, Europa League, Premier League, LaLiga, Frauen-Bundesliga und Regionalliga Nordost laufen automatisch über [OpenLigaDB](https://www.openligadb.de/) (kostenlos, kein Key nötig). Für Nations League, Süper Lig, Serie A und Ligue 1 gibt es dafür keine zuverlässige kostenlose Automatik-Quelle — hier hilft CSV-Import oder wahlweise [API-Football](https://www.api-football.com/) mit eigenem Key.

== Installation ==

1. Plugin hochladen und aktivieren (Plugins → Installieren → Plugin hochladen).
2. Eine neue WordPress-Seite anlegen und den Shortcode `[tippspiel]` in den Inhalt eintragen, dann veröffentlichen.
3. Unter Tippstube → Einstellungen ggf. einen API-Football-Key hinterlegen und die Spieldaten abrufen.
4. Fertig — Nutzer können sich anmelden, eine Tipprunde erstellen oder per Einladungscode beitreten.

Ausführliche Erklärungen zu allen Funktionen: Tippstube → Info im WordPress-Adminmenü.

== Changelog ==

Die vollständige, laufend aktualisierte Versionshistorie steht direkt im WordPress-Admin unter Tippstube → Changelog, sowie im Projekt-Repository unter [github.com/flowtrix2026/tippsiel](https://github.com/flowtrix2026/tippsiel).

= 0.9.5 =
Ekstraklasa (Polen) entfernt — es wurde nie eine funktionierende kostenlose Datenquelle dafür gefunden.

= 0.9.0 – 0.9.4 =
Echte cron-job.org-API-Integration für Datenabruf und Datensicherung, Info-Seite mit Erste-Schritte-Anleitung, Untermenü-Reihenfolge angepasst.

= 0.8.0 – 0.8.9 =
Kompletter Ausbau des Adminbereichs in sieben Untermenüpunkte (Einstellungen, Design, Cron-Job, History, Changelog, Info, Datensicherung), automatischer Update-Mechanismus über GitHub-Releases.
