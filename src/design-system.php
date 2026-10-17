<?php

declare(strict_types=1);

/*
 * Gemeinsames Design-System aller DAS-DA-Web-Apps. Die Dateien liegen im
 * Submodul und werden inline ausgegeben (das Submodul-Verzeichnis ist nicht
 * zwingend per HTTP erreichbar). Jede Ausgabe erfolgt hoechstens einmal pro
 * Request.
 */

if (!function_exists('auth_design_system_read')) {
    function auth_design_system_read(string $file): string
    {
        $content = file_get_contents(__DIR__ . '/' . $file);

        return $content === false ? '' : $content;
    }
}

if (!function_exists('auth_darkmode_head')) {
    /**
     * Dark-Mode-CSS und sofortiges Anwenden der gespeicherten Einstellung (je Geraet und Anwendung).
     * Einmal pro Seite; auch fuer Seiten ohne vollstaendiges Design-System (auth_design_system_head).
     */
    function auth_darkmode_head(): string
    {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;

        return '<style>' . auth_design_system_read('darkmode.css') . '</style>'
            . '<script>try{if(localStorage.getItem("auth_darkmode")==="1"){document.documentElement.classList.add("auth-dark");}}catch(e){}</script>';
    }
}

if (!function_exists('auth_design_system_head')) {
    /**
     * Design-System (CSS + JS: Buttons, Dialoge, Meldungen, Suchfeld,
     * Kopfbereich, Tabelle) fuer App-Seiten. Gehoert moeglichst weit nach
     * oben in <head>, damit app-eigene Regeln es gezielt ueberschreiben koennen.
     */
    function auth_design_system_head(): string
    {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;

        return '<style>' . auth_design_system_read('design-system.css') . '</style>'
            . auth_darkmode_head()
            . '<script>' . auth_design_system_read('design-system.js') . '</script>';
    }
}

if (!function_exists('auth_pages_styles')) {
    /**
     * Gestaltung der Anmelde- und Verwaltungsseiten (renderPage in auth/view.php).
     */
    function auth_pages_styles(): string
    {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;

        return '<style>' . auth_design_system_read('auth-pages.css') . '</style>';
    }
}
