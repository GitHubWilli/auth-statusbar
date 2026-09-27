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
