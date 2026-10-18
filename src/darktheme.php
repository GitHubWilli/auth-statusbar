<?php

declare(strict_types=1);

/**
 * Echtes Dark Theme (ab v3.0.0) - automatisch aus dem CSS jeder Seite abgeleitet.
 *
 * Statt die Seite per Filter zu invertieren, wird beim Ausliefern jeder HTML-Seite (Output-Buffer) das
 * vorhandene CSS (<style>-Bloecke, lokale <link>-Stylesheets, statische style="..."-Attribute) gelesen und
 * fuer jede Regel mit Farbangaben eine Dunkel-Variante erzeugt, die nur unter `html.auth-dark` gilt.
 * Farben werden dabei nach ihrer Rolle umgerechnet (Hintergrund / Text / Rahmen), Farbton bleibt erhalten.
 * Ergebnis steht als <style id="auth-dark-theme"> am Ende des <head> und wird je CSS-Stand zwischengespeichert.
 */

if (!function_exists('auth_darktheme_start')) {
    /** Einmal frueh pro Request aufrufen (z.B. in www/auth/bootstrap.php). */
    function auth_darktheme_start(): void
    {
        static $started = false;
        if ($started || PHP_SAPI === 'cli') {
            return;
        }
        $started = true;
        ob_start('auth_darktheme_buffer', 4194304);
    }
}

if (!function_exists('auth_darktheme_buffer')) {
    function auth_darktheme_buffer(string $out, int $phase = 0): string
    {
        if (strlen($out) < 300 || stripos($out, '</head>') === false || stripos($out, 'id="auth-dark-theme"') !== false) {
            return $out;
        }
        try {
            $css = auth_darktheme_build($out);
        } catch (Throwable $e) {
            return $out;
        }
        if ($css === '') {
            return $out;
        }

        return (string) preg_replace('~</head>~i', '<style id="auth-dark-theme">' . str_replace('\\', '\\\\', $css) . '</style></head>', $out, 1);
    }
}

if (!function_exists('auth_darktheme_build')) {
    /** Dunkel-CSS fuer die uebergebene fertige HTML-Seite (mit Dateicache). */
    function auth_darktheme_build(string $html): string
    {
        $source = '';
        if (preg_match_all('~<style\b[^>]*>(.*?)</style>~is', $html, $m)) {
            foreach ($m[1] as $block) {
                $source .= $block . "\n";
            }
        }
        if (preg_match_all('~<link\b[^>]*rel=["\']stylesheet["\'][^>]*>~i', $html, $lm)) {
            foreach ($lm[0] as $tag) {
                if (!preg_match('~href=["\']([^"\']+)["\']~i', $tag, $h) || preg_match('~^(https?:)?//|^data:~i', $h[1])) {
                    continue;
                }
                $href = (string) preg_replace('~[?#].*$~', '', $h[1]);
                $candidates = [
                    dirname((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) . '/' . $href,
                    rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/' . ltrim($href, '/'),
                ];
                foreach ($candidates as $file) {
                    if (is_file($file)) {
                        $source .= (string) file_get_contents($file) . "\n";
                        break;
                    }
                }
            }
        }

        // statische Inline-Styles (ohne dynamische Anteile)
        $inline = [];
        if (preg_match_all('~\sstyle=(?:"([^"]*)"|\'([^\']*)\')~i', $html, $sm)) {
            foreach ($sm[0] as $i => $_) {
                $val = $sm[1][$i] !== '' ? $sm[1][$i] : $sm[2][$i];
                if ($val === '' || preg_match('~\$\{|<\?|\{\{|\'\s*\+|"\s*\+|\+\s*[\'"]~', $val)) {
                    continue;
                }
                $inline[$val] = true;
            }
        }
        $inlineKeys = array_keys($inline);
        sort($inlineKeys);

        $key = md5($source . "\0" . implode("\0", $inlineKeys) . "\0v6");
        $cacheFile = sys_get_temp_dir() . '/auth-darktheme-' . $key . '.css';
        if (is_file($cacheFile)) {
            return (string) file_get_contents($cacheFile);
        }

        $css = auth_dt_generate($source, $inlineKeys);
        @file_put_contents($cacheFile, $css, LOCK_EX);

        return $css;
    }
}

// ---------------------------------------------------------------------------------------------------------
// Erzeugung
// ---------------------------------------------------------------------------------------------------------

if (!function_exists('auth_dt_generate')) {
    function auth_dt_generate(string $source, array $inlineStyles): string
    {
        $P = 'html.auth-dark:not(#authdt)';
        $source = (string) preg_replace('~/\*.*?\*/~s', '', $source);

        $roles = auth_dt_var_roles($source);
        $out = 'html.auth-dark{color-scheme:dark}'
            . $P . ' body{background-color:#121212!important;color:#e5e7eb}'
            . $P . ' ::placeholder{color:#8b95a5}';
        $out .= auth_dt_walk($source, $P, $roles);

        // Variablen, die nur zur Laufzeit per JS gesetzt werden (nicht im CSS definiert): Dunkel-Standard je Rolle
        preg_match_all('~(--[\w-]+)\s*:~', $source, $defMatches);
        $defined = array_flip($defMatches[1]);
        $defaults = ['bg' => '#1e1e1e', 'fg' => '#e5e7eb', 'border' => '#3a3a3a'];
        $varDefaults = '';
        foreach ($roles as $name => $use) {
            if (isset($defined[$name]) || stripos($name, 'shadow') !== false
                || preg_match('~btn|button|accent|primary|brand|success|danger|warn|info|link~i', $name)) {
                continue;
            }
            $role = isset($use['bg']) ? 'bg' : (isset($use['fg']) ? 'fg' : 'border');
            $varDefaults .= $name . ':' . $defaults[$role] . '!important;';
        }
        if ($varDefaults !== '') {
            $out .= $P . '{' . $varDefaults . '}';
        }

        foreach ($inlineStyles as $style) {
            $decoded = html_entity_decode($style, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $decls = auth_dt_decls($decoded, $roles, true);
            if ($decls === '') {
                continue;
            }
            $sel = str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\a ', ''], $decoded);
            $out .= $P . ' [style="' . $sel . '"]{' . $decls . '}';
        }

        return $out;
    }
}

if (!function_exists('auth_dt_prop_role')) {
    function auth_dt_prop_role(string $prop): ?string
    {
        $prop = strtolower(trim($prop));
        if ($prop === 'background' || $prop === 'background-color' || $prop === 'background-image' || $prop === '-webkit-background' ) {
            return 'bg';
        }
        if (in_array($prop, ['color', 'fill', 'stroke', 'caret-color', 'text-decoration-color', '-webkit-text-fill-color', 'accent-color'], true)) {
            return 'fg';
        }
        if ($prop === 'outline' || $prop === 'outline-color' || $prop === 'column-rule' || $prop === 'column-rule-color'
            || $prop === 'border' || strpos($prop, 'border') === 0 && (substr($prop, -5) === 'color' || strpos($prop, '-') === false || preg_match('~^border-(top|right|bottom|left|inline|block)(-(start|end))?$~', $prop))) {
            return 'border';
        }

        return null;
    }
}

if (!function_exists('auth_dt_var_roles')) {
    /** Rolle (bg/fg/border) je CSS-Variable aus ihrer Verwendung ableiten. */
    function auth_dt_var_roles(string $css): array
    {
        $uses = [];
        if (preg_match_all('~([a-zA-Z-]+)\s*:\s*([^;{}]*var\(\s*(--[\w-]+)[^;{}]*)~', $css, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                $role = auth_dt_prop_role($hit[1]);
                if ($role === null) {
                    continue;
                }
                if (preg_match_all('~var\(\s*(--[\w-]+)~', $hit[2], $vm)) {
                    foreach ($vm[1] as $name) {
                        $uses[$name][$role] = true;
                    }
                }
            }
        }

        return $uses;
    }
}

if (!function_exists('auth_dt_walk')) {
    function auth_dt_walk(string $css, string $P, array $roles): string
    {
        $out = '';
        $len = strlen($css);
        $i = 0;
        while ($i < $len) {
            // Praeludium bis '{' oder ';'
            $start = $i;
            $paren = 0;
            $quote = '';
            while ($i < $len) {
                $ch = $css[$i];
                if ($quote !== '') {
                    if ($ch === '\\') {
                        $i++;
                    } elseif ($ch === $quote) {
                        $quote = '';
                    }
                } elseif ($ch === '"' || $ch === "'") {
                    $quote = $ch;
                } elseif ($ch === '(') {
                    $paren++;
                } elseif ($ch === ')') {
                    $paren--;
                } elseif ($paren <= 0 && ($ch === '{' || $ch === ';' || $ch === '}')) {
                    break;
                }
                $i++;
            }
            if ($i >= $len) {
                break;
            }
            $prelude = trim(substr($css, $start, $i - $start));
            $ch = $css[$i];
            if ($ch === ';' || $ch === '}') {
                $i++;
                continue;
            }
            // Block bis zur passenden '}'
            $i++;
            $bodyStart = $i;
            $depth = 1;
            $quote = '';
            $paren = 0;
            while ($i < $len && $depth > 0) {
                $c = $css[$i];
                if ($quote !== '') {
                    if ($c === '\\') {
                        $i++;
                    } elseif ($c === $quote) {
                        $quote = '';
                    }
                } elseif ($c === '"' || $c === "'") {
                    $quote = $c;
                } elseif ($c === '(') {
                    $paren++;
                } elseif ($c === ')') {
                    $paren--;
                } elseif ($paren <= 0 && $c === '{') {
                    $depth++;
                } elseif ($paren <= 0 && $c === '}') {
                    $depth--;
                }
                $i++;
            }
            $body = substr($css, $bodyStart, $i - 1 - $bodyStart);

            if ($prelude === '') {
                continue;
            }
            if ($prelude[0] === '@') {
                if (preg_match('~^@(media|supports|layer|container)\b~i', $prelude)) {
                    $inner = auth_dt_walk($body, $P, $roles);
                    if ($inner !== '') {
                        $out .= $prelude . '{' . $inner . '}';
                    }
                }
                continue;
            }

            $decls = auth_dt_body($body, $roles);
            if ($decls === '') {
                continue;
            }
            $out .= auth_dt_prefix($prelude, $P) . '{' . $decls . '}';
        }

        return $out;
    }
}

if (!function_exists('auth_dt_prefix')) {
    function auth_dt_prefix(string $selectors, string $P): string
    {
        $parts = [];
        $depth = 0;
        $quote = '';
        $cur = '';
        $n = strlen($selectors);
        for ($i = 0; $i < $n; $i++) {
            $c = $selectors[$i];
            if ($quote !== '') {
                if ($c === '\\') {
                    $cur .= $c . ($selectors[$i + 1] ?? '');
                    $i++;
                    continue;
                }
                if ($c === $quote) {
                    $quote = '';
                }
            } elseif ($c === '"' || $c === "'") {
                $quote = $c;
            } elseif ($c === '(' || $c === '[') {
                $depth++;
            } elseif ($c === ')' || $c === ']') {
                $depth--;
            } elseif ($c === ',' && $depth === 0) {
                $parts[] = trim($cur);
                $cur = '';
                continue;
            }
            $cur .= $c;
        }
        $parts[] = trim($cur);

        $res = [];
        foreach ($parts as $sel) {
            if ($sel === '') {
                continue;
            }
            if (preg_match('~^(html|:root)(?![\w-])~i', $sel)) {
                $res[] = (string) preg_replace('~^(html|:root)~i', $P, $sel, 1);
            } else {
                $res[] = $P . ' ' . $sel;
            }
        }

        return implode(',', $res);
    }
}

if (!function_exists('auth_dt_split_decls')) {
    function auth_dt_split_decls(string $body): array
    {
        $list = [];
        $cur = '';
        $paren = 0;
        $quote = '';
        $n = strlen($body);
        for ($i = 0; $i < $n; $i++) {
            $c = $body[$i];
            if ($quote !== '') {
                if ($c === '\\') {
                    $cur .= $c . ($body[$i + 1] ?? '');
                    $i++;
                    continue;
                }
                if ($c === $quote) {
                    $quote = '';
                }
            } elseif ($c === '"' || $c === "'") {
                $quote = $c;
            } elseif ($c === '(') {
                $paren++;
            } elseif ($c === ')') {
                $paren--;
            } elseif ($c === ';' && $paren <= 0) {
                $list[] = $cur;
                $cur = '';
                continue;
            }
            $cur .= $c;
        }
        $list[] = $cur;

        return $list;
    }
}

if (!function_exists('auth_dt_body')) {
    /** Regelkoerper inkl. verschachtelter Regeln (natives CSS-Nesting, z.B. "& select { ... }"). */
    function auth_dt_body(string $body, array $roles): string
    {
        $out = '';
        $n = strlen($body);
        $cur = '';
        $paren = 0;
        $quote = '';
        for ($i = 0; $i < $n; $i++) {
            $c = $body[$i];
            if ($quote !== '') {
                if ($c === '\\') {
                    $cur .= $c . ($body[$i + 1] ?? '');
                    $i++;
                    continue;
                }
                if ($c === $quote) {
                    $quote = '';
                }
                $cur .= $c;
                continue;
            }
            if ($c === '"' || $c === "'") {
                $quote = $c;
                $cur .= $c;
                continue;
            }
            if ($c === '(') {
                $paren++;
            } elseif ($c === ')') {
                $paren--;
            }
            if ($paren <= 0 && $c === ';') {
                $out .= auth_dt_decls($cur, $roles, false);
                $cur = '';
                continue;
            }
            if ($paren <= 0 && $c === '{') {
                $prelude = trim($cur);
                $cur = '';
                $depth = 1;
                $start = $i + 1;
                $q2 = '';
                $p2 = 0;
                for ($i = $start; $i < $n && $depth > 0; $i++) {
                    $d = $body[$i];
                    if ($q2 !== '') {
                        if ($d === '\\') {
                            $i++;
                        } elseif ($d === $q2) {
                            $q2 = '';
                        }
                    } elseif ($d === '"' || $d === "'") {
                        $q2 = $d;
                    } elseif ($d === '(') {
                        $p2++;
                    } elseif ($d === ')') {
                        $p2--;
                    } elseif ($p2 <= 0 && $d === '{') {
                        $depth++;
                    } elseif ($p2 <= 0 && $d === '}') {
                        $depth--;
                    }
                }
                $inner = substr($body, $start, $i - 1 - $start);
                $i--;
                if ($prelude !== '' && !preg_match('~^@(keyframes|font-face|page)~i', $prelude)) {
                    $nested = auth_dt_body($inner, $roles);
                    if ($nested !== '') {
                        $out .= $prelude . '{' . $nested . '}';
                    }
                }
                continue;
            }
            $cur .= $c;
        }
        $out .= auth_dt_decls($cur, $roles, false);

        return $out;
    }
}

if (!function_exists('auth_dt_decls')) {
    /** Farb-Deklarationen eines Regelblocks in Dunkel-Farben (leer, wenn keine Farben enthalten sind). */
    function auth_dt_decls(string $body, array $roles, bool $forceImportant): string
    {
        $out = '';
        foreach (auth_dt_split_decls($body) as $decl) {
            $pos = strpos($decl, ':');
            if ($pos === false) {
                continue;
            }
            $prop = trim(substr($decl, 0, $pos));
            $value = trim(substr($decl, $pos + 1));
            if ($prop === '' || $value === '') {
                continue;
            }
            $important = (bool) preg_match('~!\s*important\s*$~i', $value);
            $value = trim((string) preg_replace('~!\s*important\s*$~i', '', $value));

            $role = null;
            if (strpos($prop, '--') === 0) {
                if (stripos($prop, 'shadow') !== false) {
                    continue;
                }
                $use = $roles[$prop] ?? [];
                if (isset($use['bg']) && !isset($use['fg'])) {
                    $role = 'bg';
                } elseif (isset($use['fg']) && !isset($use['bg'])) {
                    $role = 'fg';
                } elseif (isset($use['bg'])) {
                    $role = 'bg';
                } elseif (isset($use['border']) && count($use) === 1) {
                    $role = 'border';
                } else {
                    $role = 'auto';
                }
            } else {
                $role = auth_dt_prop_role($prop);
            }
            if ($role === null) {
                continue;
            }
            $mapped = auth_dt_map_value($value, $role);
            if ($mapped === null) {
                continue;
            }
            $isVar = strpos($prop, '--') === 0;
            if ($isVar && strtolower(preg_replace('~\s+~', '', $mapped)) === strtolower(preg_replace('~\s+~', '', $value))) {
                continue; // unveraenderte Variable (Akzentfarbe): Laufzeit-Einstellung der App bleibt wirksam
            }
            $out .= $prop . ':' . $mapped . (($important || $forceImportant || $isVar) ? '!important' : '') . ';';
        }

        return $out;
    }
}

// ---------------------------------------------------------------------------------------------------------
// Farben
// ---------------------------------------------------------------------------------------------------------

if (!function_exists('auth_dt_named')) {
    function auth_dt_named(): array
    {
        return [
            'white' => [255, 255, 255], 'black' => [0, 0, 0], 'gray' => [128, 128, 128], 'grey' => [128, 128, 128],
            'silver' => [192, 192, 192], 'lightgray' => [211, 211, 211], 'lightgrey' => [211, 211, 211],
            'whitesmoke' => [245, 245, 245], 'gainsboro' => [220, 220, 220], 'dimgray' => [105, 105, 105],
            'darkgray' => [169, 169, 169], 'darkgrey' => [169, 169, 169], 'red' => [255, 0, 0],
            'green' => [0, 128, 0], 'blue' => [0, 0, 255], 'orange' => [255, 165, 0], 'yellow' => [255, 255, 0],
            'navy' => [0, 0, 128], 'darkred' => [139, 0, 0], 'lightyellow' => [255, 255, 224],
            'snow' => [255, 250, 250], 'ivory' => [255, 255, 240], 'aliceblue' => [240, 248, 255],
        ];
    }
}

if (!function_exists('auth_dt_map_value')) {
    /** Ersetzt alle Farben in $value; null, wenn keine Farbe enthalten ist. */
    function auth_dt_map_value(string $value, string $role): ?string
    {
        $named = auth_dt_named();
        $names = implode('|', array_keys($named));
        $found = false;
        $pattern = '~(url\([^)]*\)|"[^"]*"|\'[^\']*\')|(#[0-9a-fA-F]{8}\b|#[0-9a-fA-F]{6}\b|#[0-9a-fA-F]{3,4}\b)|(rgba?\([^)]*\)|hsla?\([^)]*\))|(?<![\w#.-])(' . $names . ')(?![\w-])~i';
        $res = preg_replace_callback($pattern, function (array $m) use ($role, $named, &$found): string {
            if ($m[1] !== '') {
                return $m[1];
            }
            $rgba = null;
            if (isset($m[2]) && $m[2] !== '') {
                $rgba = auth_dt_parse_hex($m[2]);
            } elseif (isset($m[3]) && $m[3] !== '') {
                $rgba = auth_dt_parse_func($m[3]);
            } elseif (isset($m[4]) && $m[4] !== '') {
                $c = $named[strtolower($m[4])];
                $rgba = [$c[0], $c[1], $c[2], 1.0];
            }
            if ($rgba === null) {
                return $m[0];
            }
            $found = true;

            return auth_dt_format(auth_dt_map_color($rgba, $role));
        }, $value);

        return $found && $res !== null ? $res : null;
    }
}

if (!function_exists('auth_dt_parse_hex')) {
    function auth_dt_parse_hex(string $hex): ?array
    {
        $h = ltrim($hex, '#');
        if (strlen($h) === 3 || strlen($h) === 4) {
            $h = preg_replace('~(.)~', '$1$1', $h);
        }
        if (strlen($h) !== 6 && strlen($h) !== 8) {
            return null;
        }
        $a = strlen($h) === 8 ? hexdec(substr($h, 6, 2)) / 255 : 1.0;

        return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2)), $a];
    }
}

if (!function_exists('auth_dt_parse_func')) {
    function auth_dt_parse_func(string $f): ?array
    {
        if (!preg_match('~^(rgba?|hsla?)\(\s*(.*?)\s*\)$~is', $f, $m)) {
            return null;
        }
        $isHsl = strtolower(substr($m[1], 0, 3)) === 'hsl';
        $nums = preg_split('~[\s,/]+~', trim($m[2]), -1, PREG_SPLIT_NO_EMPTY);
        if (!$nums || count($nums) < 3 || strpos($m[2], 'var(') !== false) {
            return null;
        }
        $val = static function (string $s, float $scale): float {
            if (substr($s, -1) === '%') {
                return (float) $s / 100 * $scale;
            }

            return (float) $s;
        };
        $a = isset($nums[3]) ? max(0.0, min(1.0, $val($nums[3], 1.0))) : 1.0;
        if ($isHsl) {
            $h = (float) $nums[0];
            $s = $val($nums[1], 1.0);
            $l = $val($nums[2], 1.0);
            $s = $s > 1 ? $s / 100 : $s;
            $l = $l > 1 ? $l / 100 : $l;
            [$r, $g, $b] = auth_dt_hsl2rgb(fmod(($h % 360 + 360), 360) / 360, $s, $l);

            return [$r, $g, $b, $a];
        }

        return [
            max(0, min(255, $val($nums[0], 255))),
            max(0, min(255, $val($nums[1], 255))),
            max(0, min(255, $val($nums[2], 255))),
            $a,
        ];
    }
}

if (!function_exists('auth_dt_rgb2hsl')) {
    function auth_dt_rgb2hsl(float $r, float $g, float $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        if ($max === $min) {
            return [0.0, 0.0, $l];
        }
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        if ($max === $r) {
            $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
        } elseif ($max === $g) {
            $h = ($b - $r) / $d + 2;
        } else {
            $h = ($r - $g) / $d + 4;
        }

        return [$h / 6, $s, $l];
    }
}

if (!function_exists('auth_dt_hsl2rgb')) {
    function auth_dt_hsl2rgb(float $h, float $s, float $l): array
    {
        if ($s <= 0) {
            $v = (int) round($l * 255);

            return [$v, $v, $v];
        }
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $f = static function (float $t) use ($p, $q): float {
            if ($t < 0) {
                $t += 1;
            }
            if ($t > 1) {
                $t -= 1;
            }
            if ($t < 1 / 6) {
                return $p + ($q - $p) * 6 * $t;
            }
            if ($t < 1 / 2) {
                return $q;
            }
            if ($t < 2 / 3) {
                return $p + ($q - $p) * (2 / 3 - $t) * 6;
            }

            return $p;
        };

        return [(int) round($f($h + 1 / 3) * 255), (int) round($f($h) * 255), (int) round($f($h - 1 / 3) * 255)];
    }
}

if (!function_exists('auth_dt_map_color')) {
    /** Farbe nach Rolle ins Dunkle umrechnen. @param array{0:float,1:float,2:float,3:float} $c */
    function auth_dt_map_color(array $c, string $role): array
    {
        [$h, $s, $l] = auth_dt_rgb2hsl((float) $c[0], (float) $c[1], (float) $c[2]);

        if ($role === 'auto') {
            $role = $l >= 0.6 ? 'bg' : 'fg';
        }

        if ($role === 'bg') {
            if ($s > 0.4 && $l >= 0.2 && $l < 0.72) {
                $l2 = $l; // kraeftige Akzentfarben (Buttons, Status) bleiben
            } elseif ($l >= 0.5) {
                $l2 = 0.08 + ($l - 0.5) * 0.16;
                $s = $s > 0.2 ? $s * 0.4 : $s;
            } else {
                $l2 = $l;
            }
        } elseif ($role === 'border') {
            if ($l >= 0.5) {
                $l2 = 0.20 + ($l - 0.5) * 0.12;
                $s = $s * 0.5;
            } else {
                $l2 = $l;
            }
        } else { // fg
            if ($l < 0.5) {
                $l2 = 0.92 - 0.8 * $l;
                if ($s > 0.35) {
                    $l2 = max($l2, 0.62);
                }
            } else {
                $l2 = $s > 0.35 ? max($l, 0.62) : $l;
            }
        }

        [$r, $g, $b] = auth_dt_hsl2rgb($h, $s, max(0.0, min(1.0, $l2)));

        return [$r, $g, $b, (float) $c[3]];
    }
}

if (!function_exists('auth_dt_format')) {
    function auth_dt_format(array $c): string
    {
        if ($c[3] < 0.999) {
            return 'rgba(' . $c[0] . ',' . $c[1] . ',' . $c[2] . ',' . rtrim(rtrim(number_format((float) $c[3], 3, '.', ''), '0'), '.') . ')';
        }

        return sprintf('#%02x%02x%02x', $c[0], $c[1], $c[2]);
    }
}
