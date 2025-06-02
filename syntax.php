<?php
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
if (!defined('DOKU_DATA')) define('DOKU_DATA', DOKU_INC . 'data/');
require_once(DOKU_PLUGIN . 'syntax.php');

class syntax_plugin_oldpages extends DokuWiki_Syntax_Plugin {

    function getType() { return 'formatting'; }
    function getPType() { return 'block'; }
    function getSort() { return 999; }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~OLDPAGES\|.*?~~', $mode, 'plugin_oldpages');
    }

    function handle($match, $state, $pos, Doku_Handler $handler) {
        $raw = trim(substr($match, 11, -2)); // remove ~~OLDPAGES| and ~~
        [$ns, $age] = explode('|', $raw, 2) + [null, null];
        $ns = trim((string)$ns);
        $age = trim((string)$age);

        //error_log("Plugin oldpages active – received namespace: '$ns', age: '$age'");
        return ['namespace' => $ns, 'age' => $age];
    }

    function render($mode, Doku_Renderer $renderer, $data) {
        if ($mode !== 'xhtml') return false;

        $cutoff = $this->parseAge($data['age']);
        if (!$cutoff) {
            $msg = sprintf($this->getLang('invalid_age'), hsc($data['age']));
            $renderer->doc .= "<p><strong>$msg</strong></p>";
            return true;
        }

        $pages = $this->getAllPages($data['namespace']);
        //error_log("Plugin oldpages – found pages: " . implode(', ', $pages));

        $found = false;
        $html = "<ul>";

        foreach ($pages as $page) {
            $file = wikiFN($page);
            $mtime = @filemtime($file);
            if ($mtime && $mtime < $cutoff) {
                $found = true;
                $date = strftime('%Y-%m-%d', $mtime);
                $entry = sprintf($this->getLang('page_entry'), hsc($page), $date);
                $link = wl($page);
                $html .= "<li><a href=\"$link\">$entry</a></li>";
            }
        }
        $html .= "</ul>";

        if ($found) {
            $header = sprintf($this->getLang('page_list_header'), hsc($data['age']));
            $renderer->doc .= "<div class=\"oldpages-warning\"><strong>$header</strong>$html</div>";
        }

        return true;
    }

    private function parseAge($ageStr) {
        if (!preg_match('/^(\d+)([dmy])$/', $ageStr, $matches)) return false;
        list(, $amount, $unit) = $matches;

        $now = time();
        switch ($unit) {
            case 'd': return $now - ($amount * 86400);
            case 'm': return $now - ($amount * 30 * 86400);
            case 'y': return $now - ($amount * 365 * 86400);
            default: return false;
        }
    }

    private function getAllPages($namespace = '') {
        require_once(DOKU_INC . 'inc/search.php');
        $pages = [];

        $relpath = str_replace(':', '/', $namespace);
        $absdir = DOKU_DATA . 'pages/' . $relpath;

        if (!is_dir($absdir)) {
            error_log("Plugin oldpages – invalid base directory: $absdir");
            return [];
        }

        //error_log("Plugin oldpages – resolved search dir: $absdir");

        search($pages, $absdir, 'search_universal', [
            'listfiles' => true,
            'listdirs' => false,
            'pagesonly' => true,
        ]);

        //error_log("Plugin oldpages – found pages: " . implode(', ', array_map(fn($p) => $p['id'], $pages)));

        return array_map(fn($p) =>
            $namespace ? "$namespace:{$p['id']}" : $p['id'],
            $pages
        );
    }

}
