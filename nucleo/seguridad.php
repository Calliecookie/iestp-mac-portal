<?php
declare(strict_types=1);

if (!function_exists('mac_start_session')) {
    function mac_start_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('MACSESSID');
        $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $esHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

if (!function_exists('mac_csrf_token')) {
    function mac_csrf_token(): string
    {
        mac_start_session();
        if (!isset($_SESSION['mac_csrf_token']) || !is_string($_SESSION['mac_csrf_token'])) {
            $_SESSION['mac_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['mac_csrf_token'];
    }
}

if (!function_exists('mac_csrf_is_valid')) {
    function mac_csrf_is_valid(mixed $candidate): bool
    {
        mac_start_session();
        return is_string($candidate)
            && isset($_SESSION['mac_csrf_token'])
            && is_string($_SESSION['mac_csrf_token'])
            && hash_equals($_SESSION['mac_csrf_token'], $candidate);
    }
}

if (!function_exists('mac_secreto_valido')) {
    function mac_secreto_valido(string $valor): bool
    {
        return (bool) preg_match('/^[\x21-\x7E]{32,512}$/', $valor);
    }
}

if (!function_exists('mac_secreto_leer')) {
    function mac_secreto_leer(string $archivo): ?string
    {
        if (!is_file($archivo) || !is_readable($archivo)) return null;
        $contenido = @file_get_contents($archivo, false, null, 0, 1024);
        if (!is_string($contenido)) return null;
        $contenido = trim($contenido);
        return mac_secreto_valido($contenido) ? $contenido : null;
    }
}

if (!function_exists('mac_secreto_permisos')) {
    function mac_secreto_permisos(string $archivo): void
    {
        if (DIRECTORY_SEPARATOR !== '/') return;
        $permisos = @fileperms($archivo);
        if ($permisos === false || ($permisos & 0077) === 0) return;
        if (!@chmod($archivo, 0600)) {
            error_log('IESTP MAC: el archivo del secreto privado tiene permisos abiertos y no se pudieron restringir a 0600.');
        }
        clearstatcache(true, $archivo);
    }
}

if (!function_exists('mac_secret_key')) {
    function mac_secret_key(): ?string
    {
        static $memoria = null;
        if ($memoria !== null) {
            return $memoria !== '' ? $memoria : null;
        }

        $configurado = trim((string) mac_config('app_secret', ''));
        if ($configurado !== '') {
            if (mac_secreto_valido($configurado)) {
                return $memoria = $configurado;
            }
            error_log('IESTP MAC: APP_SECRET no es válida (se requieren de 32 a 512 caracteres imprimibles); se usará el secreto persistente.');
        }

        $archivo = mac_storage_path('privado/secreto.key');
        $existente = mac_secreto_leer($archivo);
        if ($existente !== null) {
            mac_secreto_permisos($archivo);
            return $memoria = $existente;
        }

        $dir = dirname($archivo);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            error_log('IESTP MAC: no se pudo crear el directorio privado del secreto.');
            $memoria = '';
            return null;
        }

        $bloqueo = @fopen($dir . '/secreto.lock', 'cb');
        if ($bloqueo === false) {
            error_log('IESTP MAC: no se pudo abrir el archivo de bloqueo del secreto privado.');
            $memoria = '';
            return null;
        }
        @chmod($dir . '/secreto.lock', 0600);
        if (!flock($bloqueo, LOCK_EX)) {
            fclose($bloqueo);
            error_log('IESTP MAC: no se pudo bloquear la creación del secreto privado.');
            $memoria = '';
            return null;
        }

        $resultado = null;
        $temporal = '';
        try {
            clearstatcache(true, $archivo);
            $existente = mac_secreto_leer($archivo);
            if ($existente !== null) {
                mac_secreto_permisos($archivo);
                $resultado = $existente;
            } elseif (file_exists($archivo) || is_link($archivo)) {
                error_log('IESTP MAC: el archivo del secreto privado existe pero no se puede leer o no es válido; no se regenerará automáticamente.');
            } else {
                $nuevo = bin2hex(random_bytes(32));
                $temporal = $archivo . '.' . bin2hex(random_bytes(6)) . '.tmp';
                $manejador = @fopen($temporal, 'xb');
                $escrito = false;
                if ($manejador !== false) {
                    @chmod($temporal, 0600);
                    $escrito = fwrite($manejador, $nuevo) === strlen($nuevo) && fflush($manejador);
                    fclose($manejador);
                }
                if (!$escrito) {
                    error_log('IESTP MAC: no se pudo escribir el secreto privado.');
                } elseif (!@rename($temporal, $archivo)) {
                    error_log('IESTP MAC: no se pudo colocar el secreto privado en su ubicación definitiva.');
                } else {
                    clearstatcache(true, $archivo);
                    if (mac_secreto_leer($archivo) === $nuevo) {
                        mac_secreto_permisos($archivo);
                        $resultado = $nuevo;
                    } else {
                        error_log('IESTP MAC: la verificación de integridad del secreto privado falló después de crearlo.');
                    }
                }
            }
        } catch (\Throwable $error) {
            error_log('IESTP MAC: error al preparar el secreto privado (' . get_class($error) . ').');
        } finally {
            if ($temporal !== '' && is_file($temporal)) @unlink($temporal);
            flock($bloqueo, LOCK_UN);
            fclose($bloqueo);
        }

        $memoria = $resultado ?? '';
        return $resultado;
    }
}

if (!function_exists('mac_rate_limit_contact')) {
    function mac_rate_limit_contact(int $maxAttempts = 3, int $windowSeconds = 600): string
    {
        mac_start_session();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            error_log('IESTP MAC: limitador de contacto no disponible, la sesión no está activa.');
            return 'no_disponible';
        }

        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            error_log('IESTP MAC: limitador de contacto no disponible, no se pudo determinar la dirección de origen.');
            return 'no_disponible';
        }

        $secreto = mac_secret_key();
        if ($secreto === null) {
            error_log('IESTP MAC: limitador de contacto no disponible, no hay un secreto válido (APP_SECRET o almacenamiento privado).');
            return 'no_disponible';
        }

        $dir = mac_storage_path('temporal');
        if ((!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) || !is_writable($dir)) {
            error_log('IESTP MAC: limitador de contacto no disponible, el almacenamiento temporal no admite escritura.');
            return 'no_disponible';
        }
        mac_limpiar_temporal('rate-contact-', $windowSeconds);

        $now = time();
        $limite = $now - $windowSeconds;
        $archivo = $dir . '/rate-contact-' . substr(hash_hmac('sha256', $ip, $secreto . '|' . gmdate('Y-m-d')), 0, 16) . '.json';
        $fp = @fopen($archivo, 'c+b');
        if ($fp === false) {
            error_log('IESTP MAC: limitador de contacto no disponible, no se pudo abrir el registro de intentos.');
            return 'no_disponible';
        }
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            error_log('IESTP MAC: limitador de contacto no disponible, no se pudo bloquear el registro de intentos.');
            return 'no_disponible';
        }

        try {
            $crudo = stream_get_contents($fp);
            $datos = is_string($crudo) && $crudo !== '' ? json_decode($crudo, true) : null;
            $registro = is_array($datos['payload']['attempts'] ?? null) ? $datos['payload']['attempts'] : [];
            $ipAttempts = array_values(array_filter($registro, static fn ($t) => is_int($t) && $t > $limite));

            $attempts = $_SESSION['mac_contact_attempts'] ?? [];
            if (!is_array($attempts)) $attempts = [];
            $attempts = array_values(array_filter($attempts, static fn ($t) => is_int($t) && $t > $limite));

            if (count($attempts) >= $maxAttempts || count($ipAttempts) >= ($maxAttempts * 2)) {
                $_SESSION['mac_contact_attempts'] = $attempts;
                return 'limitado';
            }

            $ipAttempts[] = $now;
            $nuevo = json_encode(['saved_at' => $now, 'payload' => ['attempts' => $ipAttempts]], JSON_UNESCAPED_SLASHES);
            $guardado = is_string($nuevo)
                && ftruncate($fp, 0)
                && rewind($fp)
                && fwrite($fp, $nuevo) === strlen($nuevo)
                && fflush($fp);
            if (!$guardado) {
                error_log('IESTP MAC: limitador de contacto no disponible, no se pudo registrar el intento.');
                return 'no_disponible';
            }
            @chmod($archivo, 0640);

            $attempts[] = $now;
            $_SESSION['mac_contact_attempts'] = $attempts;
            return 'permitido';
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}

if (!function_exists('mac_escudo_clave')) {
    function mac_escudo_clave(): string
    {
        return mac_secret_key() ?? hash('sha256', __FILE__ . '|' . php_uname('n'));
    }
}

if (!function_exists('mac_escudo_trampa')) {
    function mac_escudo_trampa(string $csrf): string
    {
        return 'ref_' . substr(hash_hmac('sha256', 'trampa|' . $csrf, mac_escudo_clave()), 0, 12);
    }
}

if (!function_exists('mac_escudo_sello')) {
    function mac_escudo_sello(string $csrf, ?int $momento = null): string
    {
        $momento = $momento ?? time();
        return $momento . '.' . hash_hmac('sha256', 'sello|' . $momento . '|' . $csrf, mac_escudo_clave());
    }
}

if (!function_exists('mac_escudo_html')) {
    function mac_escudo_html(string $csrf): string
    {
        $seguro = static fn (string $valor): string => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
        return '<div class="form-trampa" aria-hidden="true">'
            . '<label>No completar este campo<input type="text" name="website" tabindex="-1" autocomplete="off"></label>'
            . '<label>Dejar vacío<input type="text" name="' . $seguro(mac_escudo_trampa($csrf)) . '" tabindex="-1" autocomplete="off"></label>'
            . '</div>'
            . '<input type="hidden" name="csrf_token" value="' . $seguro($csrf) . '">'
            . '<input type="hidden" name="sello" value="' . $seguro(mac_escudo_sello($csrf)) . '">';
    }
}

if (!function_exists('mac_escudo_evaluar')) {
    function mac_escudo_evaluar(array $datos, int $minimo = 3, int $maximo = 7200): string
    {
        mac_start_session();
        $csrf = $_SESSION['mac_csrf_token'] ?? '';
        if (!is_string($csrf) || $csrf === '') return 'vencido';
        $trampas = ['website', mac_escudo_trampa($csrf)];
        foreach ($trampas as $campo) {
            if (trim((string) ($datos[$campo] ?? '')) !== '') return 'bot';
        }
        $sello = (string) ($datos['sello'] ?? '');
        if (!preg_match('/^(\d{10})\.[a-f0-9]{64}$/', $sello, $partes)) return 'bot';
        if (!hash_equals(mac_escudo_sello($csrf, (int) $partes[1]), $sello)) return 'bot';
        $edad = time() - (int) $partes[1];
        if ($edad < $minimo) return 'bot';
        if ($edad > $maximo) return 'vencido';
        return 'ok';
    }
}
