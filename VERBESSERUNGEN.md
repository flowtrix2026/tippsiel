# Tippstube — offene Baustellen

Was noch verbessert werden muss. Angelegt am **14.09.2026**, zuletzt ergänzt **15.09.2026**, Stand **v1.22.0**.

Diese Datei wird bei jeder Änderung mitgepflegt. Ganz zum Schluss, wenn alle Sportarten drin sind,
kommt der große Durchgang — dann wird hier abgehakt, was erledigt ist, und ergänzt, was der Scan neu
findet.

Reihenfolge: oben, was den Nutzer im Alltag am meisten stört.

---

## 1. Datenquellen, die nicht gut genug sind

### 1.1 Liga ACB und WNBA hängen an sportscore.com — HOCH

**Das Problem:** Die Quelle weist derzeit **rund 79 % aller Anfragen mit HTTP 503 ab** (am 14.09.2026
gemessen: 19 von 24 Versuchen auf denselben Tag). Sie liefert außerdem nur **einen Kalendertag pro
Anfrage**. Beides zusammen macht das Laden zäh:

- Eine komplette Saison rückwärts nachzuladen braucht **rund 30 Abruf-Läufe**.
- Acht Testläufe brachten 44 gewertete WNBA-Spiele und kamen bis zum 11.08. zurück.
- Bis der Nachlauf durch ist, ist die Tabelle unvollständig.

**Was schon getan wurde:** Wiederholungen (bis zu 8 Versuche je Tag), ein Abruf je Kalendertag für alle
Ligen zusammen statt einer je Liga, Fortschritts-Zeiger vorwärts und rückwärts, Retry-Liste für
gescheiterte Tage, Fortschrittsanzeige im Adminbereich.

**Was noch fehlt:** Eine bessere Quelle. Bisher erfolglos gesucht:

| Quelle | Ergebnis |
|---|---|
| `cdn.wnba.com`, `data.wnba.com` | liefert HTML statt Daten bzw. HTTP 403 |
| `stats.wnba.com` | nicht erreichbar |
| `acb.com` (vermutete Endpunkte) | HTTP 301, leitet um |

**Liga ACB, am 14.09.2026 geprüft:** `acb.com` ist inzwischen eine Next.js-Anwendung ohne öffentliche
JSON-Schnittstelle — die Daten stecken in internen Render-Paketen (`?_rsc=…`), die beim nächsten Umbau
der Seite brechen würden. Als Fundament ungeeignet. Der Filter auf unserer Seite stimmt nachweislich
(„Liga Asociación de Clubs de Baloncesto" trifft, sauber abgegrenzt gegen die mexikanische LNBP, die
spanische Primera FEB und die Liga Femenina) — es hakt allein am zähen Abholen.

**Nächster Schritt:** Für die WNBA die NBA-eigene Infrastruktur genauer ansehen. Für die ACB ist der
realistischste Weg inzwischen TheSportsDB (siehe 1.7).

### 1.2 Acht Fußball-Wettbewerbe hängen an derselben Quelle — MITTEL

Ligue 1, Eredivisie, Süper Lig, Primeira Liga, Saudi Professional League, Österreich und Brasilien
laufen über sportscore.com (Serie A ist seit v1.23.0 an der offiziellen Ligaquelle) und sind vom selben 79-%-Problem betroffen. Seit v1.20.0 mit
Wiederholungen und Zeitlimit, aber der Saison-Nachlauf bleibt langsam.

**Offen:** Für jede dieser Ligen prüfen, ob es eine offizielle oder verlässlichere Gratis-Quelle gibt —
so wie es bei der EuroLeague geklappt hat.

### 1.3 NBA fehlt komplett — MITTEL

Aus sportscore.com bewusst **nicht** übernommen: im Spielplan stand eine Mannschaft zweimal zur selben
Zeit angesetzt (San Antonio Spurs am 21.10.2026 gleichzeitig gegen die Clippers und gegen OKC). Ein
Fehler auf 90 Spiele, sonst sauber — der Nutzer hat entschieden, die NBA woanders zu holen.

**Offen:** Quelle finden. Der direkte Weg über die NBA-eigene API war von hier aus mit HTTP 403
geblockt. Ein Schutz gegen genau solche Widersprüche ist bereits eingebaut
(`ftipp_basket_drop_clashes`) und würde bei jeder Quelle greifen.

### 1.4 Türkische Basketball-Ligen — NIEDRIG

Ebenfalls bewusst nicht übernommen: Was sportscore.com „Turkish Basketball First League" nennt, ist
tatsächlich die **zweite** Liga (Harem Spor, Balıkesir, Darüşşafaka). Die erste heißt dort
`basketbol-super-ligi` (Anadolu Efes, Fenerbahçe, Galatasaray, Beşiktaş, 16 Teams).

**Offen:** Entscheidung des Nutzers, ob eine davon rein soll — und dann besser über eine Quelle, die
die Ligen korrekt benennt.

### 1.5 Highlightly — HEISSESTER KANDIDAT, wartet auf Schlüssel

`highlightly.net` — **neun Sportarten unter einem Schlüssel**, gleiche Endpunkt-Struktur für alle
(`/{sportart}/matches`, `/{sportart}/leagues`, `/{sportart}/standings` …). Eine Anbindung, danach kostet
jede weitere Sportart fast nichts.

**Tarif BASIC: 0 $/Monat, 100 Anfragen am Tag, ohne Kreditkarte.**

| Sportart | Ligen | genannt |
|---|---|---|
| Fußball | 950+ | Premier League, La Liga, Serie A, Bundesliga, CL |
| Basketball | 340+ | **EuroLeague, ACB** |
| Eishockey | 170+ | KHL, SHL, Liiga (**DEL nicht genannt — prüfen**) |
| Volleyball | 230+ | CEV Champions League, Serie A, Superliga |
| Handball | 180+ | EHF Champions League, **Bundesliga**, Starligue |
| dazu | | Cricket, Rugby, NBA/NCAAB, NFL/NCAAF, NHL/NCAAH, MLB |

**Direkter Zugang ohne RapidAPI:** Basis-URL `https://sports.highlightly.net`, angetestet (antwortet mit
403 „Missing mandatory HTTP Headers"). Der Header heißt auch dort `x-rapidapi-key` — das sieht falsch
aus, ist aber so dokumentiert. Konto direkt auf highlightly.net, kein RapidAPI nötig.

**Würde treffen:** Handball-Kachel, Volleyball-Kachel, Eishockey-Kachel, Liga ACB, WNBA, NBA. Damit
wären die 9 $ bei TheSportsDB (1.6) und die ~8 $ bei MySportsFeeds (1.7) **beide hinfällig**.

**NICHT ersetzen:** EuroLeague (offizielle Veranstalter-API ist besser — ganze Saison in einer
Anfrage, echte Spieltage), NHL (offiziell, gratis), Fußball (haben wir gratis), Cricket (gerade auf
100 % Auflösungsquote gemessen). Ein Sammeldienst für neun Sportarten schlägt selten die Quelle des
Veranstalters — er ist gut da, wo wir gar nichts haben.

**Vor dem Bauen zu messen (offen, braucht den Schlüssel):**
1. Sind DEL, Handball-Bundesliga und Volleyball-Bundesliga wirklich drin — oder nur Werbetext?
2. Kommen Spielpläne nach vorne? Ohne kommende Partien kann man nicht tippen.
3. Sind die Ergebnisse vollständig? Das ist die Messung, an der SportScore beim Cricket scheiterte
   (dort fehlten 45 %). Highlightly kommt von Video-Highlights her — die Sorgfalt bei Spieldaten ist
   unbewiesen.

### 1.6 api-sports.io — WARTET

Zugang noch nicht freigeschaltet, der Nutzer hat mit dem Support geredet. Nicht nachfassen, bis er
von selbst darauf zurückkommt.

### 1.7 TheSportsDB — ENTSCHEIDUNG OFFEN (evtl. hinfällig, siehe 1.5)

Deckt Eishockey (DEL, DEL 2, SHL, Liiga, National League …), American Football (NFL, NCAA, CFL, GFL,
European League of Football) **und die Liga ACB** (`Spanish Liga ACB`, ID 4408, Saison 2026-27, erster
Spieltag 26.09. korrekt) ab, und zwar mit echten, tagesaktuellen Daten. **Damit zeigen drei offene
Punkte dieser Liste auf dasselbe Abo.** Der Gratis-Zugang
kappt aber jede Liste auf etwa 5 Treffer und ist damit für Spieltage unbrauchbar. Voller Zugriff:
**9 $/Monat**, ein Abo deckt alle Sportarten ab.

**Fallstrick beim späteren Bauen:** `lookup_all_teams.php` ignoriert im Gratis-Zugang die Liga-ID und
gibt englische Fußballklubs zurück. `search_all_teams.php?l=<Liganame>` nehmen.

### 1.9 Offizielle Gratis-Quellen, live getestet — BAUREIF

Aufgestöbert über das Verzeichnis `github.com/DanielTomaro13/sportsdata-mcp` (64 Anbieter, 44 davon
ohne Schlüssel). Selbst nachgemessen am 15.09.2026:

| Quelle | Befund | füllt / ersetzt |
|---|---|---|
| ~~**MLB** `statsapi.mlb.com`~~ **ERLEDIGT in v1.24.0** | ✅ offiziell, ohne Key. 103 Spiele in 8 Tagen, **56 von 56** vergangenen mit Endstand. | MLB-Lücke im US-Sport |
| ~~**Serie A** `api-sdp.legaseriea.it`~~ **ERLEDIGT in v1.23.0** | ✅ **380 Partien der Saison in EINER Anfrage** (40 gespielt, 340 kommend), Opta-Daten vom Ligaverband, Teams/Tore/Status/Termine vollständig. Pfad: `/v1/serie-a/football/seasons/{seasonId}/matches`, Saison-Liste über die `competitions/.../seasons`-Route. | ersetzt Serie A bei SportScore (79 % Abweisungen) |
| NBA `cdn.nba.com` | ⚠️ **HTTP 403 von hier** — vermutlich Sperre für diese Leitung, nicht generell. Vom WordPress-Server aus zu testen. | NBA-Lücke |
| UFC `ufc.com/jsonapi` | ⚠️ **HTTP 302** von hier (Weiterleitung). Ebenfalls vom Server aus zu testen. | MMA-Kachel |

**Bestätigt nebenbei unsere bestehenden Entscheidungen:** Das Verzeichnis führt EuroLeague
(`api-live.euroleague.net`), NHL (`api-web.nhle.com`), OpenLigaDB und Squiggle als die empfohlenen
Gratis-Quellen — genau die, die wir schon nutzen.

**Weitere Spuren aus derselben Liste, noch nicht geprüft:** Premier League (`premierleague.com`),
LaLiga (`apim.laliga.com`), Cricket Australia (`cricket.com.au`, Big Bash direkt), WTA offiziell
(`wtatennis.com`), NCAA, Jolpica F1, OpenF1, Football-Data.co.uk. Mehrere davon würden
SportScore-Ligen ersetzen.

### 1.10 Geprüft und verworfen — nicht erneut vorschlagen

| Quelle | Befund |
|---|---|
| **sportdevs.com** | Eigene Domain seit ca. Okt. 2025 ohne DNS-Einträge; lief nur noch über RapidAPI. |
| **sportsall.com** | **Domain löst nicht auf** (SERVFAIL bei Google und Cloudflare: „Name servers refused query"). Das Verzeichnis `apisports.net` führt sie mit Note 7,0/10 und „Last verified 2026-08-06 ✓ Pricing checked ✓ Coverage checked" — für eine Domain, die nicht antwortet. Verzeichnisse mit Referral-Links taugen als Ideengeber, nicht als Beleg. |
| **sportscore.com für Cricket** | Nur **55 %** der beendeten Partien mit verwertbarem Ergebnis; Ergebnis als „225/1" statt als Zahl. |
| **mysportsfeeds.com** | Nur 6 Ligen (NFL, MLB, NBA, NHL, NCAA BB/FB). Abrechnung **pro Liga**: privat je 5 CAD/Monat (Qualifikation nötig), kommerziell 25–49 $. Für unsere drei Lücken ~8 €/Monat — aber ohne DEL und ohne ACB. Crowd-sourced, keine offizielle Ligaquelle. |
| **thestatsapi.com** | **Nur Fußball**, trotz des Namens. Im Kern eine Wettquoten-API (Bet365, Pinnacle, Betfair; Arbitrage, Closing Line Value). Ab **50 $/Monat**. Wir haben 19 Fußball-Wettbewerbe gratis. |
| **isportsapi.com** | Nur Fußball und Basketball. **49 $/Monat für eine einzelne Liga** (z. B. Premier League), 149 $ für die europäischen Top-Ligen. Anbieter für Wettfirmen. |

---

## 2. Sportarten und Ligen, die noch fehlen

| Bereich | Status |
|---|---|
| **Eishockey** | Kachel ist da, aber **leer** — DEL und europäische Ligen fehlen. Wartet auf eine Quelle (TheSportsDB hätte sie). |
| **EuroCup** | Läuft über dieselbe offizielle API wie die EuroLeague, 224 Spiele geprüft, ohne Frauen-Teams. Wäre ein Einzeiler in der Liga-Registry. Auf Wunsch des Nutzers zurückgestellt. |
| **US-Sport** | NFL und NBA fehlen (MLB seit v1.24.0 drin). |
| **Cricket** | Drin seit v1.22.0 — aber nur **ODI und T20**. Test-Partien bewusst ausgelassen (fünf Tage Spieldauer, häufige Unentschieden). Falls doch gewünscht, wäre ein eigener Wettbewerbs-Eintrag nötig. |
| Baseball, Handball, Feldhockey, MMA, Volleyball | Kacheln im Hub vorhanden, noch nicht angebunden. |

---

## 3. Technische Altlasten

### 3.1 Interne Namen passen nicht mehr — NIEDRIG, aber wächst

Die Zwei-Mannschaften-Maschinerie wurde für die NHL gebaut und heißt überall `ftipp_hockey_*` —
inzwischen trägt sie **auch AFL, WNBA, EuroLeague und Liga ACB**. Auch die Tabellen heißen
`ftipp_hockey_tips`, `ftipp_hockey_round_config` und so weiter.

Das verwirrt beim Lesen des Codes. **Umbenennen ist aber riskant**: Tabellen umzubenennen kann Daten
kosten. Wenn, dann als eigener, sauber abgesicherter Schritt — nicht nebenbei.

### 3.2 Grober Schnitt bei widersprüchlichen Spielplänen — NIEDRIG

`ftipp_basket_drop_clashes()` wirft **beide** Partien weg, wenn eine Mannschaft zweimal gleichzeitig
angesetzt ist. Das ist bewusst so (raten wäre schlimmer), verliert aber auch das richtige Spiel.
Besser wäre, gegen eine zweite Quelle abzugleichen — sobald es eine gibt.

### 3.3 Vorlauf nur 28 Tage bei den SportScore-Ligen — NIEDRIG

Bei ACB und WNBA reicht der Spielplan nur vier Wochen nach vorn. Für ein Tippspiel reicht das, aber
wer weiter vorausplanen will, kann es nicht. Bei der EuroLeague ist das Thema erledigt: dort liegt die
ganze Saison vor.

### 3.4 Breite Tabellen auf schmalen Bildschirmen — NIEDRIG

Die Tabelle hat sieben Spalten, die Fußball-Tabelle neun. Auf einem schmalen Handy wird rechts
abgeschnitten. Betrifft beide gleichermaßen; eine seitlich scrollbare Hülle wäre die Lösung.

### 3.5 Cricket: Wettbewerbe werden über Textbausteine erkannt — NIEDRIG

`ftipp_cricket_leagues()` ordnet eine Partie ihrem Wettbewerb über Textbausteine im Namen zu
(„caribbean premier league"), weil die Quelle die Saison im Namen führt. Benennt die Quelle einen
Wettbewerb um, fällt er still heraus. Dasselbe Risiko wie beim ACB-Filter — im Adminbereich sieht man
es daran, dass die Zahl der Partien je Wettbewerb auf 0 fällt.

### 3.6 Cricket-Ergebnisse hängen an einem Klartext-Satz — NIEDRIG

Der Sieger wird aus `status` gelesen („England won by 8 wkts"). Formuliert die Quelle das anders,
bleibt die Partie ohne Sieger stehen. Abgesichert ist es durch die Vorprüfung (nur Partien, die sich
auswerten lassen, werden überhaupt angeboten) und durch die drei-Tage-Regel für überfällige Partien.

### 3.7 Cron-Takt fest verdrahtet — NIEDRIG

Für Formel 1, Tennis, die Zwei-Mannschaften-Ligen und Sumo steht der automatische Abruf fest auf vier
Stunden, für Cricket auf sechs. Nur beim Fußball ist er einstellbar.

---

## 4. Dauerregeln, die bei jeder Änderung gelten

- **Sonderwertungen:** In *jeder* Sportart braucht es neben der eingebauten Wertung auch frei
  anlegbare. Ausformuliert im Projekt-Journal unter „GRUNDSATZ: Sonderwertungen in JEDER Sportart
  frei anlegbar".
- **Bessere Quelle gefunden → sofort melden.** Der Nutzer entscheidet dann: tauschen oder beide
  behalten. Er hat eine eigene Liste mit rund 150 APIs.
- **Fußball ist die Vorlage.** Neue Sportarten übernehmen dessen Aufbau und Bedienung, nicht nur
  sinngemäß, sondern eins zu eins.
- **Immer die Liga neben der Runde anzeigen** — auch wenn es nur eine gibt.

---

## 5. Erledigt (zur Erinnerung, warum es so ist)

- **SportDevs** — verworfen. Die eigene Domain hat seit etwa Oktober 2025 keine DNS-Einträge mehr, die
  API lief nur noch über RapidAPI. Nicht wieder vorschlagen.
- **EuroLeague** — seit v1.21.0 auf der offiziellen API des Veranstalters: eine Anfrage statt rund 200,
  echte Spieltage, Ergebnisse inklusive Viertel-Stände.
- **Cricket über sportscore.com** — verworfen. Dort hatten nur **55 %** der beendeten Partien ein
  verwertbares Ergebnis, und das Ergebnis kommt als „225/1" statt als Zahl. Stattdessen
  cricketdata.org mit eigenem Schlüssel.
- **Tabelle** — seit v1.20.0 in US-Sport, Rugby und Basketball, berechnet aus den geladenen Spielen und
  ehrlich als „nicht der amtliche Ligastand" beschriftet.
