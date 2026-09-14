=== Tippstube ===
Contributors: florianhenschke
Tags: fussball, tippspiel, sport, community, bundesliga
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.21.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Privates Tippspiel für deine Familie oder Freundesrunde — echtes WordPress-Login, eigene Tipprunden, automatischer Datenabruf. Fußball (19 Wettbewerbe), Formel 1, Tennis, US-Sport (NHL, WNBA), Basketball (EuroLeague, Liga ACB), Rugby (AFL) und Sumo.

== Description ==

Tippstube ist ein WordPress-Plugin für private Fußball-Tippspiele unter Familie und Freunden. Jeder Nutzer meldet sich mit seinem echten WordPress-Konto an, tritt per Einladungscode einer oder mehreren "Tipprunden" bei und tippt dort gemeinsam mit den anderen Mitgliedern — jede Runde hat für jeden Wettbewerb ihre eigene, unabhängige Rangliste.

= Funktionen für Mitspieler =

* **19 Fußball-Wettbewerbe**: 1./2./3. Bundesliga, DFB-Pokal, Champions League, Europa League, Nations League, Premier League, LaLiga, Süper Lig, Serie A, Ligue 1, Frauen-Bundesliga, Regionalliga Nordost, Eredivisie, Primeira Liga, Saudi Pro League, Österreichische Bundesliga, Brasilianische Serie A
* **Formel 1 (neu)**: Podium-Tipp — vor jedem Rennen Platz 1, 2 und 3 tippen, eigene Rangliste je Tipprunde, komplett unabhängig von Fußball aktivierbar; dazu eine Sonderwertung "Fahrer-Weltmeisterschaft" (wer wird Meister — Top 3 der Saison tippen, Punkte wie bei einem echten Rennen)
* **Tennis (neu)**: Sieger-Tipp — wer gewinnt das Match? ATP & WTA Einzel (Hauptfeld), eine Karte je Match, ein Klick genügt; **eigene Rangliste je Turnier**, genau wie jeder Fußball-Wettbewerb seine eigene hat; dazu je Turnier eine Sonderwertung "Turniersieger"
* **US-Sport (neu)**: NHL und WNBA — Ergebnis tippen exakt wie beim Fußball, komplette Saison inklusive Playoffs, nach Spieltagen sortiert; jede Liga mit eigener Rangliste (weitere Ligen wie die DEL lassen sich ergänzen); dazu die Sonderwertung "Stanley-Cup-Sieger"
* **Rugby (neu)**: AFL (Australian Football) — Ergebnis tippen wie beim Fußball, komplette Saison bis zum Grand Final, nach Spielrunden sortiert
* **Basketball (neu)**: Ergebnis-Tipp für EuroLeague und Liga ACB (Spanien); die WNBA liegt unter US-Sport. Die EuroLeague kommt von der offiziellen API des Veranstalters — ein Abruf lädt die komplette Saison mit echten Spieltagen („Runde 1" statt Kalendertag); ACB und WNBA über sportscore.com. Alles kostenlos und ohne Key
* **Tabelle (neu)** in US-Sport, Rugby und Basketball: Spiele, Siege, Niederlagen, Punkte und Differenz je Liga, sortiert nach Siegquote — berechnet aus den geladenen Spielen, mit ehrlichem Hinweis, dass es nicht der amtliche Ligastand ist
* **Sumo**: Sieger-Tipp je Kampf in der Makuuchi-Division — eigene Rangliste je Basho, dazu der Yusho-Tipp, der sich automatisch auflöst
* **Punktesystem**: 1 Punkt für die richtige Tendenz, 3 Punkte fürs exakte Ergebnis, optionaler K.o.-Zusatztipp bei Pokalspielen (Fußball); bei Formel 1 Punkte für exakte Position und für richtigen Fahrer in falscher Position
* **Zwei Ranglisten-Modi**: Freundschaftlich (geteilter Platz bei Gleichstand) oder Challenge (harter Tie-Break)
* **Sonderwertungen** in **jeder** Sportart: die eingebaute Wertung (F1-Weltmeister, Tennis-Turniersieger, Stanley Cup, EuroLeague-Sieger, AFL-Meister, Sumo-Yusho) plus beliebig viele **selbst angelegte** — Name, Punkte, Frist und Ergebnis bestimmt der Runden-Admin
* **Pinnwand-Chat** und **Statistik/Achievements** pro Runde
* **E-Mail-Benachrichtigungen**: Fristen-Erinnerung, wöchentlicher Ranking-Newsletter

= Funktionen für den Plattform-Admin =

Sieben Menüpunkte unter "Tippstube" im WordPress-Adminmenü:

* **Einstellungen** — E-Mail-Benachrichtigungen (Fristen-Erinnerung, Ranking-Newsletter samt Versandtag/-zeit) und Hinweis zur Selbstregistrierung; gilt für alle Sportarten
* **Tippstube** (die Hauptseite) — ein Tab je Sportart:
    * *Fußball* — Datenabruf der Fußball-Wettbewerbe, API-Football-Key, CSV-Import für Wettbewerbe ohne gute kostenlose Quelle
    * *Formel 1* — eigener, von Fußball unabhängiger Datenabruf (f1api.dev, kostenlos, kein Key nötig)
    * *US-Sport* — Datenabruf der NHL über api-web.nhle.com und der WNBA über sportscore.com (beides kostenlos, ohne Key)
    * *Rugby* — Datenabruf der AFL über api.squiggle.com.au (kostenlos, ohne Key)
    * *Basketball* — EuroLeague über api-live.euroleague.net (offiziell, ein Abruf lädt die ganze Saison), Liga ACB über sportscore.com (tageweise in einem rollenden Fenster, gescheiterte Tage werden automatisch nachgeholt); beides kostenlos und ohne Key
    * *Sumo* — Datenabruf über sumo-api.com (kostenlos, ohne Key); zeigt das laufende Basho, die Kampftage und den Yusho (kostenlos, kein Key); erster Abruf lädt die ganze Saison, danach nur noch die laufende Woche
    * *Tennis* — eigener Datenabruf über livetennisapi.com mit selbst eingetragenem, kostenlosem API-Key; zeigt das genutzte Tagesbudget und alle geladenen Matches
* **Design** — Akzentfarbe, Logo und Untertitel jeder Tipprunde zentral einsehbar und bearbeitbar
* **Cron-Job** — frei einstellbarer Abrufplan, dazu ein optionaler zuverlässiger externer Cron-Dienst (manuell oder automatisch über die cron-job.org-API)
* **History** — Protokoll aller automatischen und manuellen Datenänderungen
* **Changelog** — die komplette Versionshistorie direkt im Adminbereich
* **Info** — Kurzanleitung und Glossar
* **Datensicherung** — alle Tippstube-Daten als Datei sichern (Download oder automatisch per E-Mail) und bei Bedarf wiederherstellen, inklusive automatischer Sicherheitskopie vor jedem Restore

Updates erscheinen wie bei einem regulären WordPress.org-Plugin direkt im Plugins-Bereich ("Update verfügbar").

= Datenquellen =

1./2./3. Liga, DFB-Pokal, Champions League, Europa League, Premier League, LaLiga, Frauen-Bundesliga und Regionalliga Nordost laufen automatisch über [OpenLigaDB](https://www.openligadb.de/) (kostenlos, kein Key nötig). Serie A, Ligue 1, Süper Lig, Eredivisie, Primeira Liga, Saudi Pro League, Österreichische Bundesliga und Brasilianische Serie A laufen automatisch über [SportScore.com](https://sportscore.com/developers/) (ebenfalls kostenlos, kein Key nötig, dafür Spieltag für Spieltag statt auf einmal — nach der Aktivierung dauert der komplette Erstabruf einige Cron-Läufe). Nur für die Nations League gibt es (noch) keine zuverlässige kostenlose Automatik-Quelle — hier hilft CSV-Import oder wahlweise [API-Football](https://www.api-football.com/) mit eigenem Key.

== Installation ==

1. Plugin hochladen und aktivieren (Plugins → Installieren → Plugin hochladen).
2. Eine neue WordPress-Seite anlegen und den Shortcode `[tippspiel]` in den Inhalt eintragen, dann veröffentlichen.
3. Unter Tippstube → Sportarten → Fußball die Spieldaten abrufen (und nur falls nötig einen API-Football-Key hinterlegen).
4. Fertig — Nutzer können sich anmelden, eine Tipprunde erstellen oder per Einladungscode beitreten.

Ausführliche Erklärungen zu allen Funktionen: Tippstube → Info im WordPress-Adminmenü.

== Changelog ==

Die vollständige, laufend aktualisierte Versionshistorie steht direkt im WordPress-Admin unter Tippstube → Changelog, sowie im Projekt-Repository unter [github.com/flowtrix2026/tippsiel](https://github.com/flowtrix2026/tippsiel).

= 1.18.1 =
Der Menüpunkt "Sportarten" ist entfallen — er führte auf dieselbe Seite wie "Tippstube". Die
Sportarten-Tabs stecken jetzt direkt hinter "Tippstube".

= 1.18.0 =
Eigene Sonderwertungen gibt es jetzt in jeder Sportart, nicht nur bei Fußball: Neben der eingebauten
Wertung kann der Runden-Admin beliebig viele eigene anlegen — etwa "Bester Spieler / MVP" — mit Name,
Punkten, Frist und selbst eingetragenem Ergebnis.

= 1.17.0 =
Basketball ist die siebte Sportart: EuroLeague und Liga ACB im neuen Bereich Basketball, die WNBA
unter US-Sport. Datenquelle ist sportscore.com — dieselbe, die beim Fußball schon läuft, kostenlos
und ohne Key. NBA, EuroCup und die türkischen Ligen wurden bewusst nicht übernommen: beim EuroCup
mischt die Quelle den Frauen-Wettbewerb mit hinein, die "Turkish Basketball First League" ist trotz
des Namens die zweite Liga, und im NBA-Spielplan stand eine Mannschaft zweimal gleichzeitig angesetzt.
Gegen genau solche Widersprüche filtert das Plugin jetzt auch aktiv.

Sumo ist die sechste Sportart: Sieger-Tipp je Kampf in der Makuuchi-Division, eigene Rangliste je Basho
(15 Kampftage), dazu die Sonderwertung "Yusho" auf den Turniersieger — die löst sich automatisch auf,
sobald die Turnierdatenbank den Sieger meldet. Datenquelle sumo-api.com, kostenlos und ohne Key.

= 1.16.1 =
Rugby hat jetzt einen eigenen Tab im Adminbereich — vorher hing der AFL-Abruf unsichtbar am
US-Sport-Tab. Außerdem zeigte der US-Sport-Tab fälschlich auch die AFL-Spiele; jetzt zeigt jeder Tab
nur die Ligen seines Bereichs.

= 1.16.0 =
Neu: AFL (Australian Football) in der Kachel "Rugby" — Ergebnis tippen wie beim Fußball, komplette
Saison bis zum Grand Final, sortiert nach Spielrunden. Datenquelle api.squiggle.com.au, kostenlos und
ohne Key.

= 1.15.0 =
Die NHL steht jetzt unter "US-Sport" statt unter "Eishockey" — dort gehört sie zu den großen US-Ligen,
zu denen später NFL, NBA und MLB dazukommen. Der Bereich "Eishockey" bleibt damit frei für die DEL und
andere europäische Ligen.

= 1.14.1 =
Der Zugang zu den Tipprunden ist jetzt eine große Kachel über den Sportarten statt eines kleinen
Textlinks — vorher ging er neben den Sport-Kacheln unter.

= 1.14.0 =
Die Tipprunden sind aus der Fußball-Ansicht herausgelöst und haben jetzt einen eigenen Bereich auf
gleicher Ebene wie die Sportarten — erreichbar vom Sportarten-Hub und aus jeder Sportart heraus. Eine
Tipprunde gilt ja für alle Sportarten; bisher musste man dafür umständlich zu Fußball wechseln.

= 1.13.1 =
Die Liga steht bei Eishockey jetzt immer sichtbar neben der Runde ("Liga: NHL") — auch solange es nur
eine gibt, genau wie beim Fußball der Wettbewerb.

= 1.13.0 =
Eishockey funktioniert jetzt genau wie Fußball: gleiche Spielzeile mit Teamnamen links und rechts der
Torfelder, gleicher Ablauf beim Abgeben und Bearbeiten, Spieltag-Blättern per Pfeiltasten, und in der
Auswertung dieselben Spalten inklusive Tendenz. Dazu eine Liga-Ebene — jede Liga hat ihre eigene
Rangliste und eigene Regeln, sodass weitere Ligen (DEL & Co.) einfach ergänzt werden können.

= 1.12.0 =
Eishockey ist die vierte Sportart: NHL-Ergebnisse tippen wie beim Fußball, komplette Saison inklusive
Playoffs, sortiert nach Spieltagen. Dazu die Sonderwertung "Stanley-Cup-Sieger". Der Datenabruf läuft
kostenlos und ohne Key über die offizielle NHL-Schnittstelle; der erste Abruf lädt die ganze Saison,
danach wird nur noch die laufende Woche aufgefrischt.

= 1.11.0 =
Die drei Sportarten stehen jetzt unter einem gemeinsamen Menüpunkt "Sportarten" mit je einem Tab für
Fußball, Formel 1 und Tennis — vorher waren sie über das Menü verstreut. Alte Lesezeichen auf die
Formel-1- und Tennis-Seite funktionieren weiterhin.

= 1.10.0 =
Neuer Adminmenü-Punkt "Einstellungen" für alles, was nicht zu einer einzelnen Sportart gehört:
E-Mail-Benachrichtigungen und Registrierung. Diese Schalter standen bisher mit auf der Fußball-Seite,
obwohl sie für alle Sportarten gelten. Dabei ein Fehler behoben: weil beide Formulare dieselbe
Optionsgruppe nutzten, löschte ein Speichern auf der einen Seite jeweils die Einstellungen der anderen
(Speichern beim API-Key schaltete die Benachrichtigungen ab und umgekehrt).

= 1.9.1 =
Der Adminmenü-Punkt "Einstellungen" heißt jetzt "Fußball" — er enthält ja die Fußball-Spieldaten, und
seit Formel 1 und Tennis eigene Seiten haben, war der alte Name irreführend.

= 1.9.0 =
Neue Tennis-Sonderwertung "Turniersieger": Je Turnier tippen die Mitspieler vor dem ersten Match, wer
gewinnt — ausgewählt aus den Spielern des Turniers. Der Runden-Admin legt die Punkte fest und trägt nach
dem Finale den Sieger ein, genau wie bei den Fußball-Sonderwertungen.

= 1.8.0 =
Tennis wertet jetzt pro Turnier aus: jedes Turnier hat seine eigene Rangliste, auswählbar über ein
Dropdown — genau wie beim Fußball jeder Wettbewerb. Außerdem ein wichtiger Fix: die Datenquelle hatte
trotz gegenteiliger Anfrage auch Doppel und Qualifikations-Matches geliefert, die jetzt zuverlässig
herausgefiltert werden.

= 1.7.0 =
Tennis ist die dritte fertige Sportart: Sieger-Tipp (wer gewinnt das Match?) für ATP- und WTA-Einzel im
Hauptfeld, eine Karte je Match, ein Klick genügt. Eigene Rangliste je Tipprunde, eigener Datenabruf über
livetennisapi.com mit selbst eingetragenem kostenlosem API-Key, automatischer Abruf alle 4 Stunden mit
eingebautem Tageslimit, damit das Gratis-Kontingent nie gesprengt wird.

= 1.6.0 =
Neuer Bonus bei Formel 1: Wer bei einem Rennen alle drei Podiumsplätze exakt trifft, bekommt zusätzliche
Punkte obendrauf (zwei von drei reichen nicht). Höhe frei einstellbar, für Rennen und Meisterschaft
getrennt — auf 0 setzen schaltet den Bonus ab.

= 1.5.0 =
Bei Formel 1 legt der Runden-Admin jetzt alle Regeln selbst fest: Punkte und Tipp-Frist für die
Meisterschafts-Sonderwertung direkt in deren Karte, sowie Punkte, Tipp-Frist (Minuten vor Rennstart)
und Malus für die Rennen im neuen Tab "⚙️ Einstellungen". Die Meisterschaft hat dabei eigene Punkte,
unabhängig von den Rennen (Standard: 10 je exaktem Platz, 4 je richtigem Fahrer).

= 1.4.3 =
Die Formel-1-Sonderwertung hat jetzt einen eigenen Tab "⭐ Sonderwertungen" — genauso benannt und
aufgebaut wie bei Fußball, damit die App überall gleich funktioniert. Vorher steckte sie nur im
Rennen-Dropdown und war praktisch nicht zu finden.

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
