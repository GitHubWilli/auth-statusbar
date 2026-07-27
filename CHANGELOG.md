# Changelog

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
