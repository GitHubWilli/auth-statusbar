<?php

declare(strict_types=1);

require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/design-system.php';

const AUTH_STATUSBAR_VERSION = '2.1.0';

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

if (!function_exists('auth_statusbar_menu_script')) {
    /**
     * Einmalig emittierter, delegierter Handler fuer das Menue oben rechts:
     * Klick auf den Button oeffnet/schliesst, Klick daneben, Esc und Fokusverlust schliessen,
     * Pfeil hoch/runter wandert durch die Eintraege.
     */
    function auth_statusbar_menu_script(): string
    {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;

        return '<script>(function(){'
            . 'document.addEventListener("click",function(e){var d=e.target.closest&&e.target.closest("#authSbDarkMode");if(!d)return;'
            . 'var on=!document.documentElement.classList.contains("auth-dark");document.documentElement.classList.toggle("auth-dark",on);'
            . 'try{localStorage.setItem("auth_darkmode",on?"1":"0");}catch(x){}});'
            . 'function box(){return document.getElementById("authSbMenu");}'
            . 'function set(open){var m=box();if(!m)return;m.classList.toggle("is-open",open);'
            . 'var b=document.getElementById("authSbMenuBtn");if(b){b.setAttribute("aria-expanded",open?"true":"false");}}'
            . 'function items(){var m=box();return m?[].slice.call(m.querySelectorAll(".auth-sb-menu__panel a,.auth-sb-menu__panel button,.auth-sb-menu__panel select")):[];}'
            . 'document.addEventListener("click",function(e){var m=box();if(!m)return;'
            . 'var b=e.target.closest&&e.target.closest("#authSbMenuBtn");'
            . 'if(b){set(!m.classList.contains("is-open"));return;}'
            . 'if(!m.classList.contains("is-open"))return;'
            . 'if(!e.target.closest(".auth-sb-menu__panel")){set(false);}'
            . 'else if(e.target.closest("a,button")){set(false);}});'
            . 'document.addEventListener("keydown",function(e){var m=box();if(!m||!m.classList.contains("is-open"))return;'
            . 'if(e.key==="Escape"){set(false);var b=document.getElementById("authSbMenuBtn");if(b)b.focus();return;}'
            . 'if(e.key==="ArrowDown"||e.key==="ArrowUp"){var l=items();if(!l.length)return;e.preventDefault();'
            . 'var i=l.indexOf(document.activeElement);i=e.key==="ArrowDown"?(i+1)%l.length:(i<=0?l.length-1:i-1);l[i].focus();}});'
            . 'document.addEventListener("focusin",function(e){var m=box();if(m&&m.classList.contains("is-open")&&!m.contains(e.target)){set(false);}});'
            . '})();</script>';
    }
}

if (!function_exists('auth_statusbar_menu')) {
    /**
     * Hamburger-Menue ganz rechts in der oberen Leiste (ersetzt die frueher untere Leiste):
     * Kopf mit Benutzername/Rolle, danach $menuItems (gleiches Item-Schema wie die oberen Items,
     * z.B. Bereich-Auswahl, Konfiguration, Dark Mode), danach Benutzer / Profil / Abmelden.
     * Gibt '' zurueck, wenn nichts anzuzeigen ist (z.B. oeffentliche Ansicht ohne Konto).
     *
     * @param array $bottom Konto-Kontext wie frueher fuer die untere Leiste (username, roleLabel, usersUrl, profileUrl, logoutUrl, labels).
     * @param array $menuItems Zusaetzliche Eintraege (Item-Schema wie auth_statusbar_top_item()).
     */
    function auth_statusbar_menu(array $bottom, array $menuItems = []): string
    {
        $username = (string) ($bottom['username'] ?? '');
        $roleLabel = $bottom['roleLabel'] ?? null;
        $labels = array_merge([
            'users' => 'Benutzer',
            'profile' => 'Profil',
            'logout' => 'Abmelden',
            'menu' => 'Menü',
        ], $bottom['labels'] ?? []);

        $head = '';
        if ($username !== '' || !empty($roleLabel)) {
            $head .= '<div class="auth-sb-menu__head">';
            if ($username !== '') {
                $head .= '<span class="auth-sb__user">' . auth_statusbar_e($username) . '</span>';
            }
            if (!empty($roleLabel)) {
                $head .= '<span class="auth-sb__badge">' . auth_statusbar_e((string) $roleLabel) . '</span>';
            }
            $head .= '</div>';
        }

        $extra = '';
        foreach ($menuItems as $item) {
            $extra .= auth_statusbar_top_item($item);
        }

        // Umschalter Dark/Light Mode: in jedem Menue (auch oeffentliche Ansichten), Wahl wird je Geraet gemerkt
        $extra .= '<button type="button" class="auth-sb__link" id="authSbDarkMode" title="Zwischen hellem und dunklem Design wechseln">'
            . '<span class="auth-sb__icon auth-sb-dm-moon" aria-hidden="true">' . auth_statusbar_icon('moon') . '</span>'
            . '<span class="auth-sb__icon auth-sb-dm-sun" aria-hidden="true">' . auth_statusbar_icon('sun') . '</span>'
            . '<span class="auth-sb__link-label auth-sb-dm-dark">Dark Mode</span>'
            . '<span class="auth-sb__link-label auth-sb-dm-light">Light Mode</span></button>';

        $account = '';
        if (!empty($bottom['usersUrl'])) {
            $account .= auth_statusbar_link((string) $bottom['usersUrl'], $labels['users'], 'Benutzerverwaltung', 'users');
        }
        if (!empty($bottom['profileUrl'])) {
            $account .= auth_statusbar_link((string) $bottom['profileUrl'], $labels['profile'], 'Profil', 'profile');
        }
        if (!empty($bottom['logoutUrl'])) {
            $account .= '<span class="auth-sb-menu__logout">'
                . auth_statusbar_link((string) $bottom['logoutUrl'], $labels['logout'], 'Abmelden', 'logout') . '</span>';
        }

        if ($extra === '' && $account === '') {
            return '';
        }

        $body = $head . $extra;
        if ($extra !== '' && $account !== '') {
            $body .= '<div class="auth-sb-menu__sep" role="separator"></div>';
        }
        $body .= $account;

        $menuIcon = auth_statusbar_icon('menu');

        return '<div class="auth-sb-menu" id="authSbMenu">'
            . '<button type="button" class="auth-sb__link auth-sb__menu-btn" id="authSbMenuBtn" aria-haspopup="true" aria-expanded="false"'
            . ' aria-controls="authSbMenuPanel" aria-label="' . auth_statusbar_e($labels['menu']) . '" title="' . auth_statusbar_e($labels['menu']) . '">'
            . '<span class="auth-sb__icon" aria-hidden="true">' . $menuIcon . '</span></button>'
            . '<div class="auth-sb-menu__panel" id="authSbMenuPanel" role="menu" aria-label="' . auth_statusbar_e($labels['menu']) . '">' . $body . '</div>'
            . '</div>' . auth_statusbar_menu_script();
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
     * logo (src, alt?, href?) zeigt ein Logo ganz links, so hoch wie die Leiste. leftItems (z.B. "Zurück")
     * stehen dann direkt rechts vom Logo, items bleiben wie bisher rechtsbündig
     * (Logo | leftItems ... Titel | items); die Leiste wird mit Logo auch ohne Items/Titel ausgegeben.
     *
     * @param array{
     *   logo?: ?array{src: string, alt?: ?string, href?: ?string},
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
        $menuHtml = (string) ($ctx['menuHtml'] ?? '');
        $logo = is_array($ctx['logo'] ?? null) ? $ctx['logo'] : null;
        $logoSrc = $logo !== null ? trim((string) ($logo['src'] ?? '')) : '';
        $hasLogo = $logoSrc !== '';

        if (empty($title) && empty($leftItems) && empty($items) && !$hasLogo && $menuHtml === '') {
            return '';
        }

        $logoHtml = '';
        if ($hasLogo) {
            $logoAlt = trim((string) ($logo['alt'] ?? ''));
            $logoHref = trim((string) ($logo['href'] ?? ''));
            $logoImg = '<img src="' . auth_statusbar_e($logoSrc) . '" alt="' . auth_statusbar_e($logoAlt) . '" draggable="false">';
            $logoHtml = $logoHref !== ''
                ? '<a class="auth-sb__logo" href="' . auth_statusbar_e($logoHref) . '"'
                    . ($logoAlt !== '' ? ' title="' . auth_statusbar_e($logoAlt) . '"' : '') . '>' . $logoImg . '</a>'
                : '<span class="auth-sb__logo">' . $logoImg . '</span>';
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

        $itemsHtml .= $menuHtml;

        $actions = $itemsHtml !== ''
            ? '<nav class="auth-sb__actions" aria-label="Anwendungsfunktionen">' . $itemsHtml . '</nav>'
            : '';

        // Reihenfolge wie bisher; ein Logo steht davor: Logo | leftItems ... Titel | items (rechts)
        $content = $logoHtml . $leftActions . $titleHtml . $actions;

        $html = '<div class="auth-sb auth-sb--top' . ($hasLogo ? ' auth-sb--has-logo' : '') . '" role="navigation" aria-label="Anwendungsfunktionen">'
            . '<div class="auth-sb__inner">' . $content . '</div>'
            . '</div>';

        if ($reserveSpace) {
            $html .= '<script>document.body.classList.add("auth-sb-space-top");</script>';
        }

        return $html;
    }
}

if (!function_exists('auth_statusbar_render')) {
    /**
     * Komfort-Wrapper: Styles + obere Leiste mit Hamburger-Menue (ersetzt seit v2.0.0 die untere Leiste).
     *
     * @param array $bottom Siehe auth_statusbar_bottom().
     * @param array|null $top Siehe auth_statusbar_top(). null = keine obere Leiste.
     */
    function auth_statusbar_render(array $bottom, ?array $top = null): string
    {
        // Seit v2.0.0 gibt es keine untere Leiste mehr: der Konto-Kontext ($bottom) wird zum
        // Hamburger-Menue ganz rechts in der oberen Leiste (zusammen mit $top['menuItems'] und allen items mit 'menu' => true).
        $top = $top ?? [];
        // Alle Seiten-Aktionen ($top['items']) stehen seit v2.1.0 im Menue; links bleiben nur Logo, Zurueck und Titel.
        $menuItems = array_merge($top['menuItems'] ?? [], $top['items'] ?? []);
        $top['items'] = [];
        $top['menuHtml'] = auth_statusbar_menu($bottom, $menuItems);

        return auth_statusbar_styles() . auth_statusbar_top($top);
    }
}
