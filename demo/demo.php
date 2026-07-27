<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/statusbar.php';

/**
 * Baut ein eigenständiges Mini-HTML-Dokument (für ein iframe), damit "position: fixed"
 * sich auf den iframe-Viewport bezieht statt auf die ganze Demo-Seite.
 */
function auth_statusbar_demo_frame(array $bottom, ?array $top, string $bodyNote): string
{
    $bar = auth_statusbar_render($bottom, $top);
    $note = auth_statusbar_e($bodyNote);

    return <<<HTML
        <!doctype html>
        <html lang="de">
        <head>
            <meta charset="utf-8">
            <style>
                html, body { margin: 0; height: 100%; font: 14px/1.5 system-ui, sans-serif; background: #f8fafc; color: #0f172a; }
                .content { padding: 24px; }
                .content p { max-width: 60ch; }
            </style>
        </head>
        <body>
            <div class="content">
                <p>{$note}</p>
                <p>Scrollen, um zu prüfen, dass Inhalte nicht unter der Leiste verschwinden.</p>
                <div style="height: 600px;"></div>
                <p>Ende des Inhalts.</p>
            </div>
            {$bar}
        </body>
        </html>
        HTML;
}

$frames = [
    'Admin, mit oberer Leiste' => auth_statusbar_demo_frame(
        [
            'username' => 'willi',
            'roleLabel' => 'Admin',
            'usersUrl' => '#users',
            'profileUrl' => '#profile',
            'logoutUrl' => '#logout',
        ],
        [
            'title' => 'HTML-Startseite',
            'items' => [
                ['type' => 'link', 'label' => 'Konfiguration', 'href' => '#config', 'icon' => 'gear', 'title' => 'Menü konfigurieren'],
            ],
        ],
        'Admin-Ansicht: Benutzerverwaltung-Link ist sichtbar, oben die App-spezifische "Konfiguration".'
    ),
    'Nicht-Admin, ohne obere Leiste' => auth_statusbar_demo_frame(
        [
            'username' => 'max.mustermann',
            'roleLabel' => null,
            'usersUrl' => null,
            'profileUrl' => '#profile',
            'logoutUrl' => '#logout',
        ],
        null,
        'Nicht-Admin: kein Benutzerverwaltung-Link, keine obere Leiste aktiv.'
    ),
    'Admin mit Badge (z.B. Client-ID)' => auth_statusbar_demo_frame(
        [
            'username' => 'willi',
            'roleLabel' => 'Admin',
            'usersUrl' => '#users',
            'profileUrl' => '#profile',
            'logoutUrl' => '#logout',
        ],
        [
            'title' => 'Checklisten',
            'items' => [
                ['type' => 'badge', 'label' => 'Client: acme-gmbh', 'icon' => 'tag'],
            ],
        ],
        'Beispiel für ein App-spezifisches Badge (z.B. aktive Client-ID) in der oberen Leiste.'
    ),
    'Schmaler Viewport (< 560px)' => auth_statusbar_demo_frame(
        [
            'username' => 'willi',
            'roleLabel' => 'Admin',
            'usersUrl' => '#users',
            'profileUrl' => '#profile',
            'logoutUrl' => '#logout',
        ],
        [
            'title' => 'App',
            'items' => [
                ['type' => 'link', 'label' => 'Konfiguration', 'href' => '#config', 'icon' => 'gear'],
            ],
        ],
        'Schmaler Rahmen: Text-Labels sollten ausgeblendet sein, Icons bleiben sichtbar.'
    ),
];
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>auth-statusbar Demo</title>
    <style>
        body { font: 14px/1.5 system-ui, sans-serif; margin: 24px; background: #fff; color: #0f172a; }
        h1 { font-size: 1.2rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .frame-card h2 { font-size: 0.95rem; margin: 0 0 8px; }
        iframe { width: 100%; height: 340px; border: 1px solid #cbd5e1; border-radius: 8px; }
        .frame-card:nth-child(4) iframe { width: 380px; }
    </style>
</head>
<body>
    <h1>auth-statusbar — Demo (v<?= auth_statusbar_e(AUTH_STATUSBAR_VERSION) ?>)</h1>
    <p>Standalone-Vorschau ohne Login/Docker. Jede Kachel ist ein eigenständiges Mini-Dokument (iframe).</p>
    <div class="grid">
        <?php foreach ($frames as $label => $srcdoc): ?>
        <div class="frame-card">
            <h2><?= auth_statusbar_e($label) ?></h2>
            <iframe srcdoc="<?= auth_statusbar_e($srcdoc) ?>"></iframe>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
