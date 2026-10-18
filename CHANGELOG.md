# Changelog

## v3.1.1

- Mobil (<= 768 px): `.btn` mindestens 44 px hoch (`--tap-min`).

## v3.1.0

- `auth-pages.css`: Verwaltungsseiten (Konfiguration, Profil, Bereiche ...) laufen am Handy nicht mehr ueber die Bildschirmbreite (Formular-Grid `minmax(0,1fr)`, `select`/`textarea` wie Eingabefelder 100 % breit, 44 px hoch); bei Design "dasda" sind Karten, Buttons und Felder dieser Seiten eckig wie die Hauptseiten.

## v3.0.0

- **Echtes Dark Theme** statt Filter/Overlay: `src/darktheme.php` leitet beim Ausliefern jeder HTML-Seite (Output-Buffer, `auth_darktheme_start()`) aus dem vorhandenen CSS (style-Bloecke, lokale Stylesheets, statische style-Attribute) automatisch Dunkel-Regeln unter `html.auth-dark` ab (Farben nach Rolle Hintergrund/Text/Rahmen umgerechnet, Farbton bleibt; Ergebnis je CSS-Stand gecacht). Keine Laufzeit-Filter mehr, dadurch flott.
- Umschalter: nur Mond bei "Dark Mode", nur Sonne bei "Light Mode".

## v2.2.0

- Dark Mode schneller: Filter direkt auf `html` statt vollflaechigem backdrop-filter-Overlay; Leiste, Menue, Bilder und Videos mit Gegenfilter (farbtreu). iOS behaelt den Overlay (Klasse `auth-dark-bd`), weil dort ein Filter auf html `position: fixed` bricht.

## v2.1.1

- `auth_darkmode_head()`: Dark-Mode-CSS + Sofort-Anwendung auch fuer Seiten, die `auth_design_system_head()` nicht einbinden (z.B. Systeminfo-Tool, Wochendisposition).

## v2.1.0

- **Alle Aktionen im Menue:** `items` der oberen Leiste stehen jetzt im Hamburger-Menue; in der Leiste bleiben Logo, `leftItems` (Zurueck) und Titel.
- **Dark/Light Mode fuer alle Apps:** Umschalter mit Mond-/Sonnen-Symbol in jedem Menue, Einstellung je Geraet und Anwendung (`localStorage` `auth_darkmode`), wird ueber `auth_design_system_head()` auf jeder Seite (auch oeffentliche Links) sofort angewendet. Neue Icons `moon`, `sun`, neue Datei `src/darkmode.css`.

## v2.0.0

- **Keine untere Leiste mehr.** `auth_statusbar_render($bottom, $top)` zeigt nur noch die obere Leiste; der bisherige Konto-Kontext `$bottom`
  (Name, Rolle, Benutzer/Profil/Abmelden) steht als **Hamburger-Menue** ganz rechts in der oberen Leiste (`auth_statusbar_menu()`).
- Items in `items`/`leftItems`-Kontext mit `'menu' => true` wandern automatisch ins Menue. Neuer optionaler Schluessel `menuItems` im `$top`-Kontext: zusaetzliche Eintraege im Menue (Item-Schema wie `items`, z.B. Bereich-Auswahl, Konfiguration, Dark Mode).
- `--auth-sb-h` ist jetzt 0 (Seiten, die `var(--auth-sb-h)` fuer Abstaende nutzen, passen sich selbst an); `auth_statusbar_bottom()` bleibt als Funktion erhalten, wird aber nicht mehr aufgerufen.
- Icon `menu` (Lucide, drei Striche). Menue: Esc/Klick daneben schliesst, Pfeiltasten wandern durch die Eintraege, Eintraege am Handy 44 px hoch.

## v1.9.1

- Icon `trash` vereinheitlicht: Papierkorb wie in allen Webanwendungen (Lucide "trash-2", mit zwei Strichen im Korb).

## v1.9.0

- Sortieren per Ziehen (Maus, Finger, Stift) als gemeinsame Komponente im Design-System - Ersatz
  fuer Hoch/Runter-Pfeile: `makeSortable(container, options)`, `createSortHandle()`,
  `moveArrayItem(arr, from, to)` in `design-system.js`, Klassen `.ds-sort-handle` (Griff, am Handy
  44 px), `.ds-sort-dragging`, `.ds-sr-only` in `design-system.css`, Icon `grip`.
- Griff per Tastatur: Pfeil hoch/runter, Pos1, Ende; Ansage per `aria-live`; Esc bricht das Ziehen ab;
  Auto-Scroll am Rand des Fensters bzw. Scrollbereichs. Demo: `demo/sortable.html`.

## v1.8.1

- Mit Logo bleiben die Funktionen (`items`) wie bisher rechtsbuendig; nur `leftItems` (z.B. "Zurueck")
  stehen direkt rechts vom Logo (Logo | leftItems ... Titel | items).

## v1.8.0

- Obere Leiste: neuer optionaler Kontext-Schluessel `logo` (`src`, `alt`, `href`) fuer
  `auth_statusbar_top()`. Das Logo steht ganz links und ist so hoch wie die Leiste (40 px, am Handy
  48 px). Mit Logo stehen `leftItems` direkt rechts daneben; die Leiste wird dann auch ohne Items/Titel ausgegeben. Ohne `logo` aendert sich
  nichts. Neue CSS-Klassen `.auth-sb__logo`, `.auth-sb--has-logo`.

## v1.7.0

- Gemeinsames Design-System aller DAS-DA-Web-Apps: `src/design-system.css` und
  `src/design-system.js` (Buttons, Symbol-Buttons, Dialoge, Meldungen, Suchfeld, Kopfbereich,
  Karte, Tabelle mit Handy-Kartenansicht; `openModal`, `closeModal`, `showToast`,
  `confirmDestructive`, `showInfoDialog`, neu `promptDialog` als Ersatz fuer `prompt()`).
  Einbindung inline per `auth_design_system_head()` (einmal pro Request).
- `src/auth-pages.css` + `auth_pages_styles()`: einheitliche Anmelde-/Verwaltungsseiten in der
  DAS-DA-Farbwelt (Blau `#009EE0`, Navy `#001931`), Tippflaechen mind. 44 px, Eingaben 16 px.
- Statusleisten am Handy/Tablet (bis 768 px): Leisten 48 px hoch, Symbole 44 x 44 px.

## v1.5.0

- Icon-Set erweitert um `search`, `edit`, `view`, `rename`, `cancel`, `save`, `live` — deckt die
  gaengigen Aktionen (Suchen, Bearbeiten, Ansehen/Details, Umbenennen, Abbrechen, Speichern,
  Live-Ansicht starten) einheitlich mit Lucide-Icons ab, damit einbindende Apps dafuer nicht mehr
  auf Emojis oder andere Icon-Quellen ausweichen muessen.

## v1.4.0

- Obere Leiste: neuer Item-`type: 'select'` — rendert ein `<form>` mit `<select>`, das bei
  Auswahländerung automatisch abgeschickt wird (z.B. ein Mandanten-/Bereichs-Umschalter direkt in
  der Leiste statt eines Umwegs über eine Profilseite).
  Schlüssel: `name`, `formAction`, `method` (default `post`), `options` (Liste aus `value`/`label`),
  `selected`, `hidden` (Map `name => value`, z.B. CSRF-Token, Rücksprung-URL — Klartext, kein rohes
  HTML), zusätzlich die bekannten `label`, `title`, `icon`, `id`.
- Ohne JavaScript bleibt das Formular über einen `<noscript>`-Submit-Button bedienbar.
- Neue CSS-Klassen `.auth-sb__form`, `.auth-sb__select`, `.auth-sb__select-label`.

## v1.3.0

- Obere Leiste: neuer optionaler `leftItems`-Schlüssel für `auth_statusbar_top()` — rendert
  Items am linken Rand der Leiste (z.B. ein "Zurück"), unabhängig vom rechtsbündigen `items`-Slot.
  Neue CSS-Klasse `.auth-sb__actions--left`.

## v1.2.0

- Icon-Set erweitert um `printer`, `trash`, `arrow-left`.

## v1.1.0

- Obere Leiste: neuer Item-`type: 'button'` (rendert `<button type="button">` statt `<a>`) für
  JS-ausgelöste Aktionen (z.B. Download/Import), die keine URL-Navigation sind.
- Neuer optionaler `id`-Schlüssel für alle Item-Typen, damit die einbindende App per
  `document.getElementById()` einen eigenen Klick-Handler anhängen kann.
- Icon-Set erweitert um `play`, `download`, `upload`.

## v1.0.0

- Initiale Version: `auth_statusbar_bottom()`, `auth_statusbar_top()`, `auth_statusbar_styles()`,
  `auth_statusbar_render()`.
- Untere Leiste (Benutzerverwaltung): Identität (Username + Rollen-Badge) links, Benutzerverwaltung/Profil/
  Abmelden rechts (DOM-Reihenfolge so, dass visuell von rechts nach links Abmelden, Profil, Benutzerverwaltung
  erscheint).
- Obere Leiste (anwendungsspezifisch): Titel + generische Items (`link`/`badge`/`text`).
- Icon-Set: `users`, `profile`, `logout`, `gear`, `tag`.
- Responsive: Text-Labels ab < 560px ausgeblendet, Icons bleiben.
