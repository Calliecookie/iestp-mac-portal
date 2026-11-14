<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

const MAC_PUNTUADORES = ['>>>=', '...', '===', '!==', '**=', '<<=', '>>=', '>>>', '&&=', '||=', '??=', '=>', '==', '!=', '<=', '>=', '&&', '||', '??', '?.', '++', '--', '+=', '-=', '*=', '/=', '%=', '&=', '|=', '^=', '**', '<<', '>>'];
const MAC_REGEX_TRAS_PALABRA = ['return', 'typeof', 'instanceof', 'in', 'of', 'new', 'delete', 'void', 'throw', 'case', 'do', 'else', 'yield', 'await'];
const MAC_UNE_TRAS = ['{', '(', '[', ',', ';', ':', '=', '==', '===', '!=', '!==', '<', '>', '<=', '>=', '+', '-', '*', '/', '%', '**', '&&', '||', '??', '?', '!', '~', '=>', '+=', '-=', '*=', '/=', '%=', '**=', '&=', '|=', '^=', '<<=', '>>=', '>>>=', '&&=', '||=', '??=', '&', '|', '^', '<<', '>>', '>>>', '.', '?.', '...'];
const MAC_UNE_ANTES = [')', ']', '}', ',', ';', '.', '?.', ':', '?', '=', '==', '===', '!=', '!==', '&&', '||', '??'];

function mac_css_minificar(string $css): string
{
    $partes = [];
    $n = strlen($css);
    $i = 0;
    $buffer = '';
    while ($i < $n) {
        $c = $css[$i];
        if ($c === '/' && ($css[$i + 1] ?? '') === '*') {
            $fin = strpos($css, '*/', $i + 2);
            $i = $fin === false ? $n : $fin + 2;
            $buffer .= ' ';
            continue;
        }
        if ($c === '"' || $c === "'") {
            $partes[] = [false, $buffer];
            $buffer = '';
            $j = $i + 1;
            while ($j < $n && $css[$j] !== $c) $j += $css[$j] === '\\' ? 2 : 1;
            $partes[] = [true, substr($css, $i, $j - $i + 1)];
            $i = $j + 1;
            continue;
        }
        if (strncasecmp(substr($css, $i, 4), 'url(', 4) === 0 && !in_array(substr(ltrim(substr($css, $i + 4, 8)), 0, 1), ['"', "'"], true)) {
            $fin = strpos($css, ')', $i);
            $fin = $fin === false ? $n - 1 : $fin;
            $partes[] = [false, $buffer];
            $buffer = '';
            $partes[] = [true, substr($css, $i, $fin - $i + 1)];
            $i = $fin + 1;
            continue;
        }
        $buffer .= $c;
        $i++;
    }
    $partes[] = [false, $buffer];

    $salida = '';
    foreach ($partes as [$literal, $texto]) {
        if ($literal) {
            $salida .= $texto;
            continue;
        }
        $texto = preg_replace('/\s+/', ' ', $texto);
        $texto = preg_replace('/ ?([{};,>]) ?/', '$1', $texto);
        $texto = preg_replace('/\( /', '(', $texto);
        $texto = preg_replace('/ \)/', ')', $texto);
        if ($salida !== '' && str_starts_with($texto, ' ') && preg_match('/[{};,>(]$/', $salida)) $texto = substr($texto, 1);
        $salida .= $texto;
    }
    $salida = str_replace(';}', '}', $salida);
    return trim($salida) . "\n";
}

function mac_js_regex_permitida(?array $previo): bool
{
    if ($previo === null) return true;
    if ($previo['tipo'] === 'punt') return !in_array($previo['v'], [')', ']', '}', '++', '--'], true);
    if ($previo['tipo'] === 'id') return in_array($previo['v'], MAC_REGEX_TRAS_PALABRA, true);
    return false;
}

function mac_js_tokens(string $js, int &$i, bool $enPlantilla): array
{
    $n = strlen($js);
    $tokens = [];
    $salto = false;
    $profundidad = 0;
    while ($i < $n) {
        $c = $js[$i];
        if ($c === "\n" || $c === "\r") { $salto = true; $i++; continue; }
        if ($c === ' ' || $c === "\t" || $c === "\f" || $c === "\v") { $i++; continue; }
        if (substr($js, $i, 3) === "\u{2028}" || substr($js, $i, 3) === "\u{2029}") { $salto = true; $i += 3; continue; }
        if ($c === '/' && ($js[$i + 1] ?? '') === '/') {
            while ($i < $n && $js[$i] !== "\n" && $js[$i] !== "\r") $i++;
            continue;
        }
        if ($c === '/' && ($js[$i + 1] ?? '') === '*') {
            $fin = strpos($js, '*/', $i + 2);
            if ($fin === false) throw new RuntimeException('Comentario sin cerrar.');
            if (strpbrk(substr($js, $i, $fin - $i), "\n\r") !== false) $salto = true;
            $i = $fin + 2;
            continue;
        }
        $previo = $tokens ? $tokens[count($tokens) - 1] : null;
        $inicio = $i;
        if ($c === '"' || $c === "'") {
            $i++;
            while ($i < $n && $js[$i] !== $c) {
                if ($js[$i] === "\n") throw new RuntimeException('Cadena sin cerrar.');
                $i += $js[$i] === '\\' ? 2 : 1;
            }
            $i++;
            $tokens[] = ['tipo' => 'str', 'v' => substr($js, $inicio, $i - $inicio), 'salto' => $salto];
        } elseif ($c === '`') {
            $texto = '`';
            $i++;
            while (true) {
                if ($i >= $n) throw new RuntimeException('Plantilla sin cerrar.');
                $d = $js[$i];
                if ($d === '\\') { $texto .= substr($js, $i, 2); $i += 2; continue; }
                if ($d === '`') { $texto .= '`'; $i++; break; }
                if ($d === '$' && ($js[$i + 1] ?? '') === '{') {
                    $i += 2;
                    $interior = mac_js_tokens($js, $i, true);
                    $texto .= '${' . mac_js_unir($interior) . '}';
                    $i++;
                    continue;
                }
                $texto .= $d;
                $i++;
            }
            $tokens[] = ['tipo' => 'str', 'v' => $texto, 'salto' => $salto];
        } elseif ($c === '/' && mac_js_regex_permitida($previo)) {
            $i++;
            $clase = false;
            while (true) {
                if ($i >= $n || $js[$i] === "\n") throw new RuntimeException('Expresión regular sin cerrar.');
                $d = $js[$i];
                if ($d === '\\') { $i += 2; continue; }
                if ($d === '[') $clase = true;
                elseif ($d === ']') $clase = false;
                elseif ($d === '/' && !$clase) { $i++; break; }
                $i++;
            }
            while ($i < $n && preg_match('/[a-z]/', $js[$i])) $i++;
            $tokens[] = ['tipo' => 'regex', 'v' => substr($js, $inicio, $i - $inicio), 'salto' => $salto];
        } elseif (preg_match('/\G(?:0[xXbBoO][0-9a-fA-F_]+n?|(?:\d[\d_]*(?:\.[\d_]*)?|\.\d[\d_]*)(?:[eE][+-]?\d[\d_]*)?n?)/', $js, $m, 0, $i)) {
            $i += strlen($m[0]);
            $tokens[] = ['tipo' => 'num', 'v' => $m[0], 'salto' => $salto];
        } elseif (preg_match('/\G[#\p{L}_$][\p{L}\p{N}_$\x{200C}\x{200D}]*/u', $js, $m, 0, $i)) {
            $i += strlen($m[0]);
            $tokens[] = ['tipo' => 'id', 'v' => $m[0], 'salto' => $salto];
        } else {
            $v = $c;
            foreach (MAC_PUNTUADORES as $p) {
                if (substr($js, $i, strlen($p)) === $p) { $v = $p; break; }
            }
            if ($v === '?.' && ctype_digit($js[$i + 2] ?? '')) $v = '?';
            if (!preg_match('/^[{}()\[\];,<>+\-*\/%&|^!~?:=.]+$/', $v)) throw new RuntimeException('Carácter inesperado: ' . $v);
            if ($enPlantilla && $v === '}' && $profundidad === 0) return $tokens;
            if ($v === '{') $profundidad++;
            if ($v === '}') $profundidad--;
            $i += strlen($v);
            $tokens[] = ['tipo' => 'punt', 'v' => $v, 'salto' => $salto];
        }
        $salto = false;
    }
    if ($enPlantilla) throw new RuntimeException('Expresión de plantilla sin cerrar.');
    return $tokens;
}

function mac_js_unir(array $tokens): string
{
    $salida = '';
    $previo = null;
    foreach ($tokens as $t) {
        if ($previo !== null) {
            $a = substr($previo['v'], -1);
            $b = $t['v'][0];
            if ($t['salto'] && !($previo['tipo'] === 'punt' && in_array($previo['v'], MAC_UNE_TRAS, true)) && !($t['tipo'] === 'punt' && in_array($t['v'], MAC_UNE_ANTES, true))) {
                $salida .= "\n";
            } elseif (
                (preg_match('/[\p{L}\p{N}_$]/u', $a) && preg_match('/[\p{L}\p{N}_$#\\\\`]/u', $b))
                || ($a === '+' && $b === '+') || ($a === '-' && $b === '-')
                || ($a === '/' && ($b === '/' || $b === '*'))
                || ($a === '-' && $b === '>') || ($a === '<' && $b === '!')
                || ($previo['tipo'] === 'num' && $b === '.' && !preg_match('/[.eExXn]/', $previo['v']))
            ) {
                $salida .= ' ';
            }
        }
        $salida .= $t['v'];
        $previo = $t;
    }
    return $salida;
}

function mac_js_minificar(string $js): string
{
    $i = 0;
    return mac_js_unir(mac_js_tokens($js, $i, false)) . "\n";
}

$raiz = dirname(__DIR__);
$fuentes = array_merge(
    glob($raiz . '/recursos/estilos/*.css') ?: [],
    glob($raiz . '/recursos/scripts/*.js') ?: [],
    glob($raiz . '/recursos/scripts/*/*.js') ?: [],
);
$fuentes = array_values(array_filter($fuentes, static fn (string $f): bool => !preg_match('/\.min\.(css|js)$/', $f)));
sort($fuentes);

$manifiesto = [];
$antes = 0;
$despues = 0;
foreach ($fuentes as $fuente) {
    $tipo = pathinfo($fuente, PATHINFO_EXTENSION);
    $original = (string) file_get_contents($fuente);
    try {
        $min = $tipo === 'css' ? mac_css_minificar($original) : mac_js_minificar($original);
    } catch (RuntimeException $e) {
        fwrite(STDERR, 'Error en ' . substr($fuente, strlen($raiz) + 1) . ': ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    $destino = substr($fuente, 0, -strlen($tipo)) . 'min.' . $tipo;
    file_put_contents($destino, $min);
    $relativa = substr($fuente, strlen($raiz) + 1);
    $manifiesto[$relativa] = hash('sha256', $original);
    $antes += strlen($original);
    $despues += strlen($min);
    printf("%-45s %7d → %7d bytes (%d %%)\n", $relativa, strlen($original), strlen($min), (int) round(100 - strlen($min) * 100 / max(1, strlen($original))));
}

$codigo = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($manifiesto, true) . ";\n";
file_put_contents($raiz . '/configuracion/minificados.php', $codigo);
printf("Total: %d → %d bytes. Manifiesto: configuracion/minificados.php\n", $antes, $despues);
