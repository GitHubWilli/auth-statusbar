<?php

declare(strict_types=1);

require_once __DIR__ . '/icons.php';

const AUTH_STATUSBAR_VERSION = '1.4.0';

if (!function_exists('auth_statusbar_e')) {
    function auth_statusbar_e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('auth_statusbar_link')) {
    /**
     * Rendert einen einzelnen Aktions-Link (z.B. Profil, Abmelden).
     */
    function auth_statusbar_link(string $href, string $label, string $title, string $iconKey = ''): string
    {
        $icon = $iconKey !== '' ? auth_statusbar_icon($iconKey) : '';
        $iconHtml = $icon !== '' ? '<span class="auth-sb__icon" aria-hidden="true">' . $icon . '</span>' : '';

        return '<a class="auth-sb__link" href="' . auth_statusbar_e($href) . '" title="' . auth_statusbar_e($title) . '">'
            . $iconHtml
            . '<span class="auth-sb__link-label">' . auth_statusbar_e($label) . '</span>'
            . '</a>';
    }
}

if (!function_exists('auth_statusbar_autosubmit_script')) {
    /**
     * Einmalig emittierter, delegierter change-Listener fuer type "select" Items.
     * Delegiert auf document, damit er auch nach preg_replace-Injektion hinter
     * <body> zuverlaessig greift (das select existiert zu dem Zeitpunkt bereits).
     */
    function auth_statusbar_autosubmit_script(): string
    {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;

        return '<script>document.addEventListener("change",function(e){'
            . 'var s=e.target;'
            . 'if(s&&s.matches&&s.matches("select[data-auth-sb-autosubmit]")&&s.form){s.form.submit();}'
            . '},true);</script>';
    }
}

if (!function_exists('auth_statusbar_top_item')) {
    /**
     * Rendert ein einzelnes Item der oberen, anwendungsspezifischen Leiste.
     *
     * type "button" rendert ein echtes <button type="button">, damit die
     * einbindende App per (optionaler) id einen eigenen Klick-Handler
     * anhaengen kann (z.B. fuer JS-Aktionen wie Download/Import, die keine
     * URL-Navigation sind). Die Komponente selbst kennt kein onclick.
     *
     * type "select" rendert ein <form> mit <select>, das bei Aenderung
     * automatisch abgeschickt wird (z.B. ein Mandanten-/Bereichs-Umschalter
     * direkt in der Leiste). hidden ist eine reine name=>value-Map (Klartext,
     * z.B. CSRF-Token, Ruecksprung-URL) - kein roher HTML-Parameter, alles
     * wird escaped.
     *
     * @param array{
     *   type?: string, label: string, href?: ?string, title?: ?string, icon?: ?string,
     *   id?: ?string, accent?: bool,
     *   name?: string, formAction?: string, method?: string,
     *   options?: list<array{value: string, label: string}>, selected?: ?string,
     *   hidden?: array<string,string>
     * } $item
     */
    function auth_statusbar_top_item(array $item): string
    {
        $type = $item['type'] ?? 'link';
        $label = (string) ($item['label'] ?? '');
        $title = (string) ($item['title'] ?? $label);
        $iconKey = (string) ($item['icon'] ?? '');
        $id = (string) ($item['id'] ?? '');
        $accentClass = !empty($item['accent']) ? ' auth-sb__link--accent' : '';
        $idAttr = $id !== '' ? ' id="' . auth_statusbar_e($id) . '"' : '';

        $icon = $iconKey !== '' ? auth_statusbar_icon($iconKey) : '';
        $iconHtml = $icon !== '' ? '<span class="auth-sb__icon" aria-hidden="true">' . $icon . '</span>' : '';

        if ($type === 'select') {
            $name = (string) ($item['name'] ?? '');
            $formAction = (string) ($item['formAction'] ?? '');
            $method = (string) ($item['method'] ?? 'post');
            $options = $item['options'] ?? [];
            $selected = $item['selected'] ?? null;
            $hidden = $item['hidden'] ?? [];

            if ($name === '' || $formAction === '' || $options === []) {
                return '';
            }

            $hiddenHtml = '';
            foreach ($hidden as $hiddenName => $hiddenValue) {
                $hiddenHtml .= '<input type="hidden" name="' . auth_statusbar_e((string) $hiddenName)
                    . '" value="' . auth_statusbar_e((string) $hiddenValue) . '">';
            }

            $optionsHtml = '';
            foreach ($options as $option) {
                $value = (string) ($option['value'] ?? '');
                $optionLabel = (string) ($option['label'] ?? $value);
                $selectedAttr = $selected !== null && $value === (string) $selected ? ' selected' : '';
                $optionsHtml .= '<option value="' . auth_statusbar_e($value) . '"' . $selectedAttr . '>'
                    . auth_statusbar_e($optionLabel) . '</option>';
            }

            $labelHtml = $label !== ''
                ? '<span class="auth-sb__link-label auth-sb__select-label">' . auth_statusbar_e($label) . '</span>'
                : '';

            return '<form class="auth-sb__form" method="' . auth_statusbar_e($method) . '" action="' . auth_statusbar_e($formAction) . '">'
                . $hiddenHtml . $iconHtml . $labelHtml
                . '<select class="auth-sb__select"' . $idAttr . ' name="' . auth_statusbar_e($name) . '"'
                . ' aria-label="' . auth_statusbar_e($title) . '" title="' . auth_statusbar_e($title) . '" data-auth-sb-autosubmit>'
                . $optionsHtml . '</select>'
                . '<noscript><button type="submit" class="auth-sb__link">OK</button></noscript>'
                . '</form>' . auth_statusbar_autosubmit_script();
        }

        if ($label === '') {
            return '';
        }

        if ($type === 'badge') {
            return '<span class="auth-sb__badge' . $accentClass . '"' . $idAttr . '>' . $iconHtml . auth_statusbar_e($label) . '</span>';
        }

        if ($type === 'text') {
            return '<span class="auth-sb__title' . $accentClass . '"' . $idAttr . '>' . $iconHtml . auth_statusbar_e($label) . '</span>';
        }

        if ($type === 'button') {
            return '<button type="button" class="auth-sb__link' . $accentClass . '"' . $idAttr . ' title="' . auth_statusbar_e($title) . '">'
                . $iconHtml . '<span class="auth-sb__link-label">' . auth_statusbar_e($label) . '</span></button>';
        }

        $href = (string) ($item['href'] ?? '');
        if ($href === '') {
            return '<span class="auth-sb__link' . $accentClass . '"' . $idAttr . ' title="' . auth_statusbar_e($title) . '">'
                . $iconHtml . '<span class="auth-sb__link-label">' . auth_statusbar_e($label) . '</span></span>';
        }

        return '<a class="auth-sb__link' . $accentClass . '"' . $idAttr . ' href="' . auth_statusbar_e($href) . '" title="' . auth_statusbar_e($title) . '">'
            . $iconHtml . '<span class="auth-sb__link-label">' . auth_statusbar_e($label) . '</span></a>';
    }
}

if (!function_exists('auth_statusbar_styles')) {
    /**
     * <style>-Block mit dem CSS der Statusleisten. Wird pro Request nur einmal ausgegeben.
     */
    function auth_statusbar_styles(): string
    {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;

        $css = file_get_contents(__DIR__ . '/statusbar.css');
        if ($css === false) {
            $css = '';
        }

        return '<style>' . $css . '</style>';
    }
}

if (!function_exists('auth_statusbar_bottom')) {
    /**
     * Untere Benutzerverwaltungs-Leiste. In identischer Form in allen Anwendungen verwendet.
     *
     * Reihenfolge im DOM ist bewusst NICHT gespiegelt (kein row-reverse), damit Tab-Reihenfolge
     * und Screenreader-Vorlesereihenfolge der visuellen Reihenfolge entsprechen: DOM-Reihenfolge
     * Benutzerverwaltung -> Profil -> Abmelden, rechtsbündig, ergibt visuell von rechts nach links
     * gelesen genau Abmelden, Profil, Benutzerverwaltung.
     *
     * @param array{
     *   username?: string,
     *   roleLabel?: ?string,
     *   usersUrl?: ?string,
     *   profileUrl?: ?string,
     *   logoutUrl?: ?string,
     *   labels?: array<string,string>,
     *   reserveSpace?: bool
     * } $ctx
     */
    function auth_statusbar_bottom(array $ctx): string
    {
        $username = (string) ($ctx['username'] ?? '');
        $roleLabel = $ctx['roleLabel'] ?? null;
        $usersUrl = $ctx['usersUrl'] ?? null;
        $profileUrl = $ctx['profileUrl'] ?? null;
        $logoutUrl = $ctx['logoutUrl'] ?? null;
        $labels = array_merge([
            'users' => 'Benutzer',
            'profile' => 'Profil',
            'logout' => 'Abmelden',
        ], $ctx['labels'] ?? []);
        $reserveSpace = $ctx['reserveSpace'] ?? true;

        $identity = '';
        if ($username !== '' || $roleLabel) {
            $identity .= '<div class="auth-sb__identity">';
            if ($username !== '') {
                $identity .= '<span class="auth-sb__user">' . auth_statusbar_e($username) . '</span>';
            }
            if (!empty($roleLabel)) {
                $identity .= '<span class="auth-sb__badge">' . auth_statusbar_e((string) $roleLabel) . '</span>';
            }
            $identity .= '</div>';
        }

        $links = '';
        if (!empty($usersUrl)) {
            $links .= auth_statusbar_link((string) $usersUrl, $labels['users'], 'Benutzerverwaltung', 'users');
        }
        if (!empty($profileUrl)) {
            $links .= auth_statusbar_link((string) $profileUrl, $labels['profile'], 'Profil', 'profile');
        }
        if (!empty($logoutUrl)) {
            $links .= auth_statusbar_link((string) $logoutUrl, $labels['logout'], 'Abmelden', 'logout');
        }

        $actions = $links !== '' ? '<nav class="auth-sb__actions" aria-label="Benutzerkonto">' . $links . '</nav>' : '';

        $html = '<div class="auth-sb auth-sb--bottom" role="contentinfo" aria-label="Benutzerverwaltung">'
            . '<div class="auth-sb__inner">' . $identity . $actions . '</div>'
            . '</div>';

        if ($reserveSpace) {
            $html .= '<script>document.body.classList.add("auth-sb-space-bottom");</script>';
        }

        return $html;
    }
}

if (!function_exists('auth_statusbar_top')) {
    /**
     * Obere, anwendungsspezifische Leiste. Gleiche Optik/Position wie die untere Leiste,
     * Inhalt wird komplett vom Aufrufer bestimmt.
     *
     * leftItems rendert am linken Rand der Leiste (z.B. ein "Zurück"), items wie bisher
     * rechtsbündig. Beide nutzen dasselbe Item-Schema.
     *
     * @param array{
     *   title?: ?string,
     *   leftItems?: list<array{type?: string, label: string, href?: ?string, title?: ?string, icon?: ?string, id?: ?string, accent?: bool, name?: string, formAction?: string, method?: string, options?: list<array{value: string, label: string}>, selected?: ?string, hidden?: array<string,string>}>,
     *   items?: list<array{type?: string, label: string, href?: ?string, title?: ?string, icon?: ?string, id?: ?string, accent?: bool, name?: string, formAction?: string, method?: string, options?: list<array{value: string, label: string}>, selected?: ?string, hidden?: array<string,string>}>,
     *   reserveSpace?: bool
     * } $ctx
     */
    function auth_statusbar_top(array $ctx): string
    {
        $title = $ctx['title'] ?? null;
        $leftItems = $ctx['leftItems'] ?? [];
        $items = $ctx['items'] ?? [];
        $reserveSpace = $ctx['reserveSpace'] ?? true;

        if (empty($title) && empty($leftItems) && empty($items)) {
            return '';
        }

        $leftItemsHtml = '';
        foreach ($leftItems as $item) {
            $leftItemsHtml .= auth_statusbar_top_item($item);
        }

        $leftActions = $leftItemsHtml !== ''
            ? '<nav class="auth-sb__actions auth-sb__actions--left" aria-label="Zurück">' . $leftItemsHtml . '</nav>'
            : '';

        $titleHtml = !empty($title)
            ? '<span class="auth-sb__title">' . auth_statusbar_e((string) $title) . '</span>'
            : '';

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= auth_statusbar_top_item($item);
        }

        $actions = $itemsHtml !== ''
            ? '<nav class="auth-sb__actions" aria-label="Anwendungsfunktionen">' . $itemsHtml . '</nav>'
            : '';

        $html = '<div class="auth-sb auth-sb--top" role="navigation" aria-label="Anwendungsfunktionen">'
            . '<div class="auth-sb__inner">' . $leftActions . $titleHtml . $actions . '</div>'
            . '</div>';

        if ($reserveSpace) {
            $html .= '<script>document.body.classList.add("auth-sb-space-top");</script>';
        }

        return $html;
    }
}

if (!function_exists('auth_statusbar_render')) {
    /**
     * Komfort-Wrapper: Styles + optionale obere Leiste + untere Leiste in korrekter Reihenfolge.
     *
     * @param array $bottom Siehe auth_statusbar_bottom().
     * @param array|null $top Siehe auth_statusbar_top(). null = keine obere Leiste.
     */
    function auth_statusbar_render(array $bottom, ?array $top = null): string
    {
        $html = auth_statusbar_styles();
        if ($top !== null) {
            $html .= auth_statusbar_top($top);
        }
        $html .= auth_statusbar_bottom($bottom);

        return $html;
    }
}
