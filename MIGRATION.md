# Migrationsrezept für ein weiteres Projekt

Ausgangslage: Das Projekt hat ein `www/auth/view.php` mit einer eigenen, hartkodierten Bar-Erzeugung
(vermutlich `renderProtectedHtmlApp()` und/oder `renderPage()`).

1. **Submodule einbinden**

   ```bash
   git checkout -b feat/shared-statusbar
   git submodule add https://github.com/GitHubWilli/auth-statusbar.git www/auth/statusbar
   git config -f .gitmodules submodule.www/auth/statusbar.branch main
   ```

   Falls das Projekt `dubious ownership`-Fehler wirft (z.B. weil es auf einem anderen Volume liegt):
   `git config --global --add safe.directory <projektpfad>`.

2. **Require + Guard in `auth/view.php`**

   ```php
   $authStatusbarFile = __DIR__ . '/statusbar/src/statusbar.php';
   if (!is_file($authStatusbarFile)) {
       throw new RuntimeException(
           'Auth-Statusleiste fehlt: ' . $authStatusbarFile
           . ' – bitte "git submodule update --init --recursive" ausführen.'
       );
   }
   require_once $authStatusbarFile;
   ```

3. **Alten Bar-Block ersetzen**

   Alles, was bisher `.auth-bar`/`.auth-app-bar`-HTML und -CSS von Hand zusammenbaut, durch einen Aufruf
   von `auth_statusbar_bottom()` (und optional `auth_statusbar_top()` für App-spezifische Buttons) ersetzen.
   Die Rollenprüfung (wer ist Admin/Superadmin) bleibt in der App — nur das Ergebnis (`usersUrl` gesetzt
   oder `null`) wird übergeben.

4. **Beide Rendering-Pfade prüfen**

   Falls die App sowohl statische HTML-Shells (`renderProtectedHtmlApp`) als auch PHP-Seiten (`renderPage`,
   z.B. Profil/Benutzerverwaltung) rendert, müssen beide auf dieselbe Funktion umgestellt werden — sonst
   existieren zwei unterschiedliche Bar-Varianten in derselben App.

5. **Altes CSS entfernen**

   `grep -rn "auth-bar\|auth-app-bar" www/` muss danach leer sein.

6. **App-spezifische Extras in die obere Leiste verschieben**

   Beispiele: ein Konfigurations-Button (`type: link`), ein Badge für einen aktiven Mandanten/Client
   (`type: badge`). Diese Elemente gehören NICHT mehr in die untere Leiste.

7. **`.htaccess`/Zugriffsschutz prüfen**

   Sicherstellen, dass `www/auth/` (und damit auch `www/auth/statusbar/`) per HTTP gesperrt ist (z.B.
   `RewriteRule ^auth/ - [F,L]`), da die Komponente ihr CSS ohnehin inline ausliefert und keine Datei aus
   diesem Verzeichnis direkt aufgerufen werden muss.

8. **Verifikation**

   Siehe README.md-Abschnitt "Demo" für eine isolierte Prüfung, danach End-to-End im jeweiligen Docker-Setup
   (Admin- und Nicht-Admin-Login, beide Rendering-Pfade, Tab-Reihenfolge, `403` auf `auth/statusbar/README.md`).
