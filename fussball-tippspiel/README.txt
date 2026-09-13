=== Tippstube ===
Contributors: florianhenschke
Tags: fussball, tippspiel, sport, community, bundesliga
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.4.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Privates Tippspiel für deine Familie oder Freundesrunde — echtes WordPress-Login, eigene Tipprunden, automatischer Datenabruf. Fußball (19 Wettbewerbe) und jetzt auch Formel 1 (Podium-Tipp).

== Description ==

Tippstube ist ein WordPress-Plugin für private Fußball-Tippspiele unter Familie und Freunden. Jeder Nutzer meldet sich mit seinem echten WordPress-Konto an, tritt per Einladungscode einer oder mehreren "Tipprunden" bei und tippt dort gemeinsam mit den anderen Mitgliedern — jede Runde hat für jeden Wettbewerb ihre eigene, unabhängige Rangliste.

= Funktionen für Mitspieler =

* **19 Fußball-Wettbewerbe**: 1./2./3. Bundesliga, DFB-Pokal, Champions League, Europa League, Nations League, Premier League, LaLiga, Süper Lig, Serie A, Ligue 1, Frauen-Bundesliga, Regionalliga Nordost, Eredivisie, Primeira Liga, Saudi Pro League, Österreichische Bundesliga, Brasilianische Serie A
* **Formel 1 (neu)**: Podium-Tipp — vor jedem Rennen Platz 1, 2 und 3 tippen, eigene Rangliste je Tipprunde, komplett unabhängig von Fußball aktivierbar; dazu eine Sonderwertung "Fahrer-Weltmeisterschaft" (wer wird Meister — Top 3 der Saison tippen, Punkte wie bei einem echten Rennen)
* **Punktesystem**: 1 Punkt für die richtige Tendenz, 3 Punkte fürs exakte Ergebnis, optionaler K.o.-Zusatztipp bei Pokalspielen (Fußball); bei Formel 1 Punkte für exakte Position und für richtigen Fahrer in falscher Position
* **Zwei Ranglisten-Modi**: Freundschaftlich (geteilter Platz bei Gleichstand) oder Challenge (harter Tie-Break)
* **Sonderwertungen** je Wettbewerb (Meister, Torschützenkönig etc.), automatische Auflösung per Textvergleich
* **Pinnwand-Chat** und **Statistik/Achievements** pro Runde
* **E-Mail-Benachrichtigungen**: Fristen-Erinnerung, wöchentlicher Ranking-Newsletter

= Funktionen für den Plattform-Admin =

Acht Verwaltungsseiten unter "Tippstube" im WordPress-Adminmenü:

* **Einstellungen** — API-Football-Key, automatischer Datenabruf, CSV-Import für Wettbewerbe ohne gute kostenlose Quelle
* **Design** — Akzentfarbe, Logo und Untertitel jeder Tipprunde zentral einsehbar und bearbeitbar
* **Cron-Job** — frei einstellbarer Abrufplan, dazu ein optionaler zuverlässiger externer Cron-Dienst (manuell oder automatisch über die cron-job.org-API)
* **History** — Protokoll aller automatischen und manuellen Datenänderungen
* **Changelog** — die komplette Versionshistorie direkt im Adminbereich
* **Info** — Kurzanleitung und Glossar
* **Datensicherung** — alle Tippstube-Daten als Datei sichern (Download oder automatisch per E-Mail) und bei Bedarf wiederherstellen, inklusive automatischer Sicherheitskopie vor jedem Restore
* **Formel 1** — eigener, von Fußball unabhängiger Datenabruf (f1api.dev, kostenlos, kein Key nötig) für den Podium-Tipp

Updates erscheinen wie bei einem regulären WordPress.org-Plugin direkt im Plugins-Bereich ("Update verfügbar").

= Datenquellen =

1./2./3. Liga, DFB-Pokal, Champions League, Europa League, Premier League, LaLiga, Frauen-Bundesliga und Regionalliga Nordost laufen automatisch über [OpenLigaDB](https://www.openligadb.de/) (kostenlos, kein Key nötig). Serie A, Ligue 1, Süper Lig, Eredivisie, Primeira Liga, Saudi Pro League, Österreichische Bundesliga und Brasilianische Serie A laufen automatisch über [SportScore.com](https://sportscore.com/developers/) (ebenfalls kostenlos, kein Key nötig, dafür Spieltag für Spieltag statt auf einmal — nach der Aktivierung dauert der komplette Erstabruf einige Cron-Läufe). Nur für die Nations League gibt es (noch) keine zuverlässige kostenlose Automatik-Quelle — hier hilft CSV-Import oder wahlweise [API-Football](https://www.api-football.com/) mit eigenem Key.

== Installation ==

1. Plugin hochladen und aktivieren (Plugins → Installieren → Plugin hochladen).
2. Eine neue WordPress-Seite anlegen und den Shortcode `[tippspiel]` in den Inhalt eintragen, dann veröffentlichen.
3. Unter Tippstube → Einstellungen ggf. einen API-Football-Key hinterlegen und die Spieldaten abrufen.
4. Fertig — Nutzer können sich anmelden, eine Tipprunde erstellen oder per Einladungscode beitreten.

Ausführliche Erklärungen zu allen Funktionen: Tippstube → Info im WordPress-Adminmenü.

== Changelog ==

Die vollständige, laufend aktualisierte Versionshistorie steht direkt im WordPress-Admin unter Tippstube → Changelog, sowie im Projekt-Repository unter [github.com/flowtrix2026/tippsiel](https://github.com/flowtrix2026/tippsiel).

= 1.4.2 =
Formel 1 hat jetzt eine Sonderwertung "Fahrer-Weltmeisterschaft": vor Saisonbeginn tippen, wer am Ende
Meister, Vize und Dritter wird — läuft technisch als ganz normales "Rennen" mit, nutzt also dieselbe
Rangliste und dasselbe Punktesystem wie der Podium-Tipp bei echten Rennen.

= 1.4.1 =
Plugin-Beschreibung im WordPress-Adminbereich (Plugins-Liste) gekürzt — zeigt jetzt die unterstützten
Sportarten (Fußball, Formel 1) statt einer langen Aufzählung aller einzelnen Wettbewerbe und Datenquellen.

= 1.4.0 =
Formel 1 ist die erste im Sportarten-Hub wirklich fertig gebaute Sportart: Podium-Tipp (Platz 1, 2, 3
vor jedem Rennen), eigene Rangliste je Tipprunde, komplett eigenständiger Datenabruf über f1api.dev
(kostenlos, kein Key). Läuft technisch komplett unabhängig von Fußball — eigene Datenbank-Tabellen,
eigene Admin-Seite, eigener Cron-Job, kein Eingriff in die bestehende Fußball-Logik.

= 1.3.0 =
Neue Sportarten-Hub-Startseite: vor dem eigentlichen Tippspiel wählt man jetzt erst per Kachel die
Sportart. Fußball ist aktiv, zehn weitere Sportarten (Basketball, Eishockey, Formel 1, US-Sport, Rugby,
Baseball, Handball, Hockey, MMA, Volleyball) sind als Vorschau ("Bald verfügbar") zu sehen — echtes
Tippen gibt es vorerst nur für Fußball. Wer Fußball einmal gewählt hat, landet beim nächsten Besuch
direkt im Tippspiel; über "Sportart wechseln" kommt man jederzeit zurück zum Hub.

= 1.2.1 =
Ranglisten-Newsletter überarbeitet: Statt einer separaten E-Mail pro Wettbewerb (bei 4 aktiven
Wettbewerben in einer Runde also 4 Mails) kommt jetzt nur noch EINE gesammelte HTML-Mail pro Runde und
Mitspieler mit allen Wettbewerben untereinander.

= 1.2.0 =
Fünf neue Wettbewerbe automatisch über SportScore.com: Eredivisie, Primeira Liga, Saudi Pro League,
Österreichische Bundesliga, Brasilianische Serie A (19 Wettbewerbe insgesamt). Dabei einen Bug behoben:
die Brasilianische Serie A spielt nach Kalenderjahr statt im europäischen Juli-Juni-Rhythmus.

= 1.1.2 =
Live auf der echten Seite entdeckt: sportscore.com antwortet vereinzelt mit "vorübergehend überlastet"
(HTTP 503). Fehlgeschlagene Tage gingen dadurch bisher unbemerkt verloren, weil der Fortschritts-Zeiger
trotzdem weiterrückte. Jetzt merkt sich das Plugin fehlgeschlagene Tage in einer eigenen, auf die
Lauf-Größe gedeckelten Liste und holt sie beim nächsten Abruf automatisch zuerst nach.

= 1.1.1 =
Anzeige-Fehler behoben: Solange der SportScore.com-Erstabruf noch läuft (z.B. während der Sommerpause
legitim 0 Spiele), erschien fälschlich dieselbe Meldung wie bei einem echten Verbindungsfehler. Jetzt
zeigt die Tabelle den echten Fortschritt ("Erstabruf läuft: X/365 Tage") und markiert einen tatsächlichen
Verbindungsfehler klar mit "ACHTUNG".

= 1.1.0 =
Serie A, Ligue 1 und Süper Lig laufen jetzt automatisch über SportScore.com (Spieltag für Spieltag,
kostenlos, kein Key nötig) — CSV-Import/API-Football bleiben als Fallback bzw. für die Nations League.

= 1.0.0 =
Erste stabile Version — kompletter Adminbereich (Einstellungen, Design, Cron-Job, History, Changelog, Info,
Datensicherung), automatische Updates über GitHub, aufgeräumtes öffentliches Repository.

= 0.9.5 – 0.9.7 =
Ekstraklasa (Polen) entfernt (keine Datenquelle gefunden), README/Code aufgeräumt, Datenschutz-Textbaustein-
Shortcode entfernt.

= 0.9.0 – 0.9.4 =
Echte cron-job.org-API-Integration für Datenabruf und Datensicherung, Info-Seite mit Erste-Schritte-Anleitung, Untermenü-Reihenfolge angepasst.

= 0.8.0 – 0.8.9 =
Kompletter Ausbau des Adminbereichs in sieben Untermenüpunkte (Einstellungen, Design, Cron-Job, History, Changelog, Info, Datensicherung), automatischer Update-Mechanismus über GitHub-Releases.
