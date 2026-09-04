# Tippstube

Ein Fußball-Tippspiel für private Freundes-/Familienkreise über mehrere laufende
Wettbewerbe (1./2. Bundesliga, DFB-Pokal, Champions League, Europa League, Nations League).
Läuft als WordPress-Plugin, Daten in der WordPress-eigenen SQL-Datenbank.

## Language

**Tippstube**:
Der Produktname der gesamten Anwendung. Kein Codename, kein Arbeitstitel — so heißt
das Produkt nach außen (UI, Login-Seite, Marketing).
_Avoid_: Fußball-Tippspiel (alte, rein beschreibende Bezeichnung, wird abgelöst)

**Tipprunde** (kurz: **Runde**):
Ein privater Kreis (z.B. Familie, Freunde), dem man per Einladungscode beitritt
oder den man selbst erstellt. Hat einen eigenen Namen, eigene Regeln (Punkte,
Malus, Modus) und eine eigene Rangliste je Wettbewerb — unabhängig von anderen
Tipprunden. Ein Nutzer kann Mitglied mehrerer Tipprunden gleichzeitig sein.
_Avoid_: Gruppe, Liga, Kreis (allein)

**Runden-Admin**:
Der Ersteller einer Tipprunde. Legt für seine Runde die Regeln fest (Punktesystem,
Malus, Modus, Sonderwertungen) und verwaltet ihre Mitglieder.
_Avoid_: Moderator, Owner

**Plattform-Admin**:
Der Betreiber der gesamten Tippstube (WordPress-Nutzer mit `manage_options`).
Hat für Support-/Notfälle Zugriff auf jede Tipprunde, unabhängig davon, wer sie
erstellt hat. Eine Ebene über dem Runden-Admin.
_Avoid_: Super-Admin (im UI-Rollen-Umschalter des lokalen Prototyps noch so benannt,
im verbundenen WordPress-Modus heißt es korrekt Plattform-Admin)

**Mitspieler**:
Ein Mitglied einer Tipprunde, das tippt und die Rangliste/Pinnwand dieser Runde sieht.
_Avoid_: Spieler (allein, ohne Rundenbezug), Teilnehmer

**Wettbewerb**:
Einer der sechs unterstützten Fußball-Wettbewerbe (1. Bundesliga, 2. Bundesliga,
DFB-Pokal, Champions League, Europa League, Nations League). Jeder Wettbewerb
hat innerhalb jeder Tipprunde seine eigene, getrennte Rangliste.
_Avoid_: Liga (zu eng — DFB-Pokal/Europapokale sind keine Ligen), Turnier

**Wettbewerbs-Abo** (kurz: **Abo**):
Die Entscheidung eines Nutzers, in einer bestimmten Tipprunde an einem bestimmten
Wettbewerb mitzutippen. Gilt **pro Tipprunde**, nicht global fürs Konto (bis
v0.8.13 war das umgekehrt — global, nicht pro Runde; auf Nutzerwunsch geändert,
weil unterschiedliche Runden unterschiedliche Wettbewerbe relevant haben können,
z.B. Familie nur 1./2. Bundesliga, Kollegen zusätzlich Champions League). Nur
wählbar, solange der Wettbewerb noch nicht begonnen hat. Der eigentliche **Tipp**
(die getippte Torzahl) bleibt davon unabhängig weiterhin pro Nutzer+Wettbewerb
global gespeichert — ist man mit demselben Wettbewerb in mehreren Runden dabei,
zählt derselbe Tipp in allen davon.
_Avoid_: Teilnahme, Anmeldung

**Tipp**:
Die vorhergesagte Torzahl für ein einzelnes Spiel. Wird erst **fix** (unveränderlich,
für Mitspieler sichtbar), sobald der Nutzer aktiv "Tipp abgeben" bestätigt hat —
vorher ist er editierbar und für andere Mitspieler verdeckt.
_Avoid_: Wette, Vorhersage

**K.o.-Zusatztipp**:
Ein optionaler Bonus-Tipp bei K.o.-Spielen (Pokal/Europapokal), der nur erscheint,
wenn der reguläre 90-Minuten-Tipp unentschieden lautet: wie die Partie letztlich
ausgeht (nach Verlängerung oder im Elfmeterschießen). Bringt Extrapunkte on top.
_Avoid_: Bonus-Tipp (allein — zu allgemein, verwechselbar mit Sonderwertungen)

**Sonderwertung**:
Eine saisonlange Wette pro Wettbewerb (z.B. Meister, Torschützenkönig), die der
Runden-Admin anlegt, mit eigenen Punkten und eigener Frist. Getrennt von den
Spiel-Tipps. Auflösung standardmäßig über einen automatischen Textvergleich
zwischen Tipp und eingetragenem Ergebnis (Groß-/Kleinschreibung egal, sonst exakt)
— der Runden-Admin kann das für einzelne Mitglieder manuell übersteuern
("zählt trotzdem als richtig/falsch"), z.B. bei nur leicht abweichendem Wortlaut
("St. Pauli" vs. "FC St. Pauli").
_Avoid_: Bonus-Tipp, Zusatzwertung

**Standard-Sonderwertung**:
Eine Sonderwertung, die automatisch beim ersten Öffnen des Sonderwertungen-Tabs
für eine Runde+Wettbewerb-Kombination angelegt wird (kein manuelles Anlegen durch
den Runden-Admin nötig). Je Wettbewerbstyp unterschiedlich (Liga: Meister/
Herbstmeister/Absteiger/Torschützenkönig/Bester Passgeber; Pokal: Sieger; Nations
League: Sieger/Torschützenkönig/Gruppensieger je erkannter Gruppe). Löscht der
Runden-Admin eine davon, kommt genau diese nicht wieder — neu hinzukommende
Kategorien (z.B. nach einem Plugin-Update oder sobald echte Spieldaten vorliegen)
werden trotzdem einmalig nachgerüstet. Technisch über einen pro Kategorie stabilen
"Key" in `ftipp_round_config.seeded_special_keys` gemerkt, nicht über ein einzelnes
Ja/Nein-Flag.
_Avoid_: Standard-Tipp, Vorlage (zu allgemein)

**Nations-League-Gruppe**:
Eine Gruppe von National­mannschaften innerhalb einer Nations-League-Division
(Liga A/B/C/D), die in der Ligaphase gegeneinander antreten. Es gibt dafür keine
feste Gruppen-ID in den Spieldaten der API — die Tippstube leitet die Gruppen
stattdessen aus den tatsächlichen Begegnungen ab (Teams, die gegeneinander
spielen, gehören zusammen). Jede erkannte Gruppe bekommt automatisch eine eigene
Gruppensieger-Sonderwertung.
_Avoid_: Nations-League-Liga (Verwechslung mit der übergeordneten Division A/B/C/D)

**Modus**:
Die pro Tipprunde vom Runden-Admin gewählte Regel für den Umgang mit
Punktgleichstand in der Rangliste. Zwei Ausprägungen: **Freundschaftlich**
(geteilter Platz bei Gleichstand) und **Challenge** (harter Tie-Break: mehr
exakte Treffer, dann mehr K.o.-Bonuspunkte entscheiden).
_Avoid_: Regelwerk, Einstellung (zu allgemein)

**Malus**:
Punktabzug für ein Spiel, das ein Mitspieler bis zur Frist nicht getippt hat.
Höhe und Ein/Aus sind Teil der Regeln einer Tipprunde.
_Avoid_: Strafe, Minuspunkte (im Code so benannt, im Glossar aber "Malus")
