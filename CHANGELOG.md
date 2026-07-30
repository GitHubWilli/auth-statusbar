# Changelog

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
