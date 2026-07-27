# Changelog

## v1.0.0

- Initiale Version: `auth_statusbar_bottom()`, `auth_statusbar_top()`, `auth_statusbar_styles()`,
  `auth_statusbar_render()`.
- Untere Leiste (Benutzerverwaltung): Identität (Username + Rollen-Badge) links, Benutzerverwaltung/Profil/
  Abmelden rechts (DOM-Reihenfolge so, dass visuell von rechts nach links Abmelden, Profil, Benutzerverwaltung
  erscheint).
- Obere Leiste (anwendungsspezifisch): Titel + generische Items (`link`/`badge`/`text`).
- Icon-Set: `users`, `profile`, `logout`, `gear`, `tag`.
- Responsive: Text-Labels ab < 560px ausgeblendet, Icons bleiben.
