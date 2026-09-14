# Tippstube — offene Baustellen

Was noch verbessert werden muss. Angelegt am **14.09.2026**, Stand **v1.21.0**.

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
realistischste Weg inzwischen TheSportsDB (siehe 1.6).

### 1.2 Acht Fußball-Wettbewerbe hängen an derselben Quelle — MITTEL

Serie A, Ligue 1, Eredivisie, Süper Lig, Primeira Liga, Saudi Professional League, Österreich und
Brasilien laufen über sportscore.com und sind vom selben 79-%-Problem betroffen. Seit v1.20.0 mit
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

### 1.5 api-sports.io — WARTET

Zugang noch nicht freigeschaltet, der Nutzer hat mit dem Support geredet. Nicht nachfassen, bis er
von selbst darauf zurückkommt.

### 1.6 TheSportsDB — ENTSCHEIDUNG OFFEN

Deckt Eishockey (DEL, DEL 2, SHL, Liiga, National League …), American Football (NFL, NCAA, CFL, GFL,
European League of Football) **und die Liga ACB** (`Spanish Liga ACB`, ID 4408, Saison 2026-27, erster
Spieltag 26.09. korrekt) ab, und zwar mit echten, tagesaktuellen Daten. **Damit zeigen drei offene
Punkte dieser Liste auf dasselbe Abo.** Der Gratis-Zugang
kappt aber jede Liste auf etwa 5 Treffer und ist damit für Spieltage unbrauchbar. Voller Zugriff:
**9 $/Monat**, ein Abo deckt alle Sportarten ab.

**Fallstrick beim späteren Bauen:** `lookup_all_teams.php` ignoriert im Gratis-Zugang die Liga-ID und
gibt englische Fußballklubs zurück. `search_all_teams.php?l=<Liganame>` nehmen.

---

## 2. Sportarten und Ligen, die noch fehlen

| Bereich | Status |
|---|---|
| **Eishockey** | Kachel ist da, aber **leer** — DEL und europäische Ligen fehlen. Wartet auf eine Quelle (TheSportsDB hätte sie). |
| **EuroCup** | Läuft über dieselbe offizielle API wie die EuroLeague, 224 Spiele geprüft, ohne Frauen-Teams. Wäre ein Einzeiler in der Liga-Registry. Auf Wunsch des Nutzers zurückgestellt. |
| **US-Sport** | NFL, MLB, NBA fehlen. |
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

### 3.5 Cron-Takt fest verdrahtet — NIEDRIG

Für Formel 1, Tennis, die Zwei-Mannschaften-Ligen und Sumo steht der automatische Abruf fest auf vier
Stunden. Nur beim Fußball ist er einstellbar.

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
- **Tabelle** — seit v1.20.0 in US-Sport, Rugby und Basketball, berechnet aus den geladenen Spielen und
  ehrlich als „nicht der amtliche Ligastand" beschriftet.
