# auth-statusbar

Zentrale, rein präsentationale Statuszeilen-Komponente für die PHP/Docker-Apps im `C:\Projekte`-Workspace
(HTML-Startseite, checklisten, adventskalender-2025, systeminfo-tool, wochendisposition, ...).

Zweck: die **Benutzerverwaltungs-Elemente** (Abmelden, Profil, Benutzerverwaltung) sollen in identischer Form
in allen Anwendungen erscheinen und nur einmal zentral gepflegt werden — nicht wie bisher pro Projekt kopiert
und dann unabhängig auseinanderlaufen. Anwendungsspezifische Buttons (z.B. "Konfiguration") bekommen eine
zweite, optische ähnliche Leiste am oberen Bildrand, deren Inhalt aber vollständig von der einbindenden App
bestimmt wird.

Diese Komponente kennt **keine** Session, Konfiguration oder Rollenlogik der einbindenden App — sie bekommt
fertige, bereits escapte Strings/URLs übergeben und rendert nur HTML/CSS.

## Einbindung (Git-Submodule)

```bash
git submodule add https://github.com/GitHubWilli/auth-statusbar.git www/auth/statusbar
git config -f .gitmodules submodule.www/auth/statusbar.branch main
git add .gitmodules www/auth/statusbar
git commit -m "chore: gemeinsame Auth-Statusleiste als Submodule einbinden"
```

Beim Klonen des einbindenden Projekts:

```bash
git clone --recurse-submodules <projekt-repo-url>
# oder nachträglich:
git submodule update --init --recursive
```

## API

```php
require_once __DIR__ . '/statusbar/src/statusbar.php';

// Untere Leiste (Benutzerverwaltung) — in identischer Form in allen Apps.
echo auth_statusbar_bottom([
    'username'   => $user['username'],
    'roleLabel'  => $isAdmin ? 'Admin' : null,
    'usersUrl'   => $isAdmin ? $usersUrl : null,   // leer/null => kein Benutzerverwaltung-Link
    'profileUrl' => $profileUrl,
    'logoutUrl'  => $logoutUrl,
]);

// Obere Leiste (anwendungsspezifisch) — gleiche Optik, freier Inhalt.
// type "button" statt "link": kein href, dafuer eine (optionale) id, an die
// die App per JS einen eigenen Klick-Handler haengt (z.B. fuer Download/Import,
// die keine URL-Navigation sind).
echo auth_statusbar_top([
    'title' => 'HTML-Startseite',
    // leftItems: am linken Rand der Leiste (z.B. ein "Zurück"), unabhaengig von items.
    'leftItems' => [
        ['type' => 'button', 'label' => 'Zurück', 'icon' => 'arrow-left', 'id' => 'authTopBack'],
    ],
    'items' => [
        ['type' => 'link', 'label' => 'Konfiguration', 'href' => $menuBuilderUrl, 'icon' => 'gear'],
        ['type' => 'button', 'label' => 'Download JSON', 'icon' => 'download', 'id' => 'authTopDownloadJson'],
        // type "select": <form>+<select>, submitted automatisch bei Aenderung (z.B. Mandanten-Umschalter).
        // hidden ist eine reine name=>value-Map (Klartext, z.B. CSRF-Token) - kein roher HTML-Parameter.
        [
            'type' => 'select', 'name' => 'clientId', 'formAction' => '/api/set-client.php',
            'options' => [['value' => 'buero', 'label' => 'buero'], ['value' => 'lager', 'label' => 'lager']],
            'selected' => 'buero', 'label' => 'Client', 'icon' => 'tag',
            'hidden' => ['csrf_token' => $csrfToken],
        ],
    ],
]);

// Bequemer: Styles + optionale obere Leiste + untere Leiste in einem Aufruf.
echo auth_statusbar_render($bottomCtx, $topCtx); // $topCtx = null => keine obere Leiste
```

Details zu allen Parametern stehen als PHPDoc direkt in `src/statusbar.php`.

### Design-Regeln

1. Alle Texte/URLs werden intern escaped (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`) — kein Parameter
   akzeptiert rohes HTML.
2. Icons nur über feste Keys aus `src/icons.php` (`users`, `profile`, `logout`, `gear`, `tag`, `play`,
   `download`, `upload`, `printer`, `trash`, `arrow-left`). Unbekannter Key → kein Icon, kein Fehler.
3. "Falls Admin"/"falls relevant" entscheidet ausschließlich der Aufrufer — die Komponente rendert
   `usersUrl` genau dann als Link, wenn er nicht leer ist. Rollenlogik bleibt in der jeweiligen App.
4. Alle Funktionen sind `function_exists()`-geschützt, doppelte `require`-Pfade sind ungefährlich.
5. Keine externen Abhängigkeiten, kein Composer, PHP 8.3, `declare(strict_types=1)`.
6. CSS wird als Inline-`<style>`-Block ausgeliefert (nicht als `<link>`-Datei) — kompatibel mit Setups, die
   `www/auth/` per `.htaccess` sperren.

## Update-Prozedur (pro einbindendem Projekt)

```bash
cd <projekt>
git submodule update --remote --merge www/auth/statusbar
git add www/auth/statusbar
git commit -m "chore: auth-statusbar auf <version> aktualisieren"
git push
```

Projekte müssen nicht synchron aktualisiert werden — jedes Projekt pinnt seine eigene Version über den
Submodule-Pointer.

## Demo

```bash
php -S localhost:8099 -t demo
```

Zeigt vier Zustände (Admin, Nicht-Admin, App-Badge, schmaler Viewport) ohne Login/Docker.

Siehe auch `MIGRATION.md` für ein Schritt-für-Schritt-Rezept zur Einbindung in ein bestehendes Projekt.
