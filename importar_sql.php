<?php
/**
 * Importador de SQL via PDO.
 *
 * USO:
 *   php importar_sql.php <archivo.sql> [--db=col_restaurante_db]
 *                                       [--user=root] [--pass=]
 *                                       [--host=127.0.0.1] [--port=3306]
 *
 * Funciona en Windows sin depender de la redirección de stdin de cmd.exe,
 * que es notoriamente frágil con mysql.exe.
 *
 * - Recrea la base (DROP DATABASE + CREATE DATABASE) ANTES de importar.
 * - Parsea sentencias respetando ';', strings, /* * /, -- comentarios y
 *   DELIMITER (por si hay triggers/procedures).
 * - Muestra cada error con número de línea aproximado.
 */

ini_set('memory_limit', '1024M');
error_reporting(E_ALL);

// ---- 1) Parseo de argumentos -------------------------------------------------
$args = $argv;
array_shift($args);

if (empty($args)) {
    fwrite(STDERR, "USO: php importar_sql.php <archivo.sql> [--db=...] [--user=...] [--pass=...]\n");
    exit(2);
}

$sqlFile = null;
$opts = [
    'db'   => 'col_restaurante_db',
    'user' => 'root',
    'pass' => '',
    'host' => '127.0.0.1',
    'port' => '3306',
];
foreach ($args as $a) {
    if (str_starts_with($a, '--')) {
        $eq = strpos($a, '=');
        if ($eq !== false) {
            $k = substr($a, 2, $eq - 2);
            $v = substr($a, $eq + 1);
            $opts[$k] = $v;
        }
    } else {
        $sqlFile = $a;
    }
}

if (!$sqlFile || !is_file($sqlFile)) {
    fwrite(STDERR, "ERROR: no encuentro el archivo SQL: {$sqlFile}\n");
    exit(2);
}

echo "============================================================\n";
echo " IMPORTADOR SQL (PHP/PDO)\n";
echo "============================================================\n";
echo " Archivo : {$sqlFile} (" . filesize($sqlFile) . " bytes)\n";
echo " Host    : {$opts['host']}:{$opts['port']}\n";
echo " Usuario : {$opts['user']}\n";
echo " BD      : {$opts['db']}\n";
echo "------------------------------------------------------------\n";

// ---- 2) Conexión inicial SIN base de datos ---------------------------------
try {
    $dsn = "mysql:host={$opts['host']};port={$opts['port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $opts['user'], $opts['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES   => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);
    echo "[1/4] Conexion MySQL: OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR de conexion: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Verifica que MySQL de Laragon este corriendo y que el\n");
    fwrite(STDERR, "usuario/contrasena sean correctos. Si root tiene password,\n");
    fwrite(STDERR, "ejecuta: php importar_sql.php archivo.sql --pass=TU_PASSWORD\n");
    exit(1);
}

// ---- 3) Drop + create de la base de datos (idempotente) --------------------
try {
    $pdo->exec("DROP DATABASE IF EXISTS `{$opts['db']}`");
    $pdo->exec("CREATE DATABASE `{$opts['db']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$opts['db']}`");
    echo "[2/4] Base `{$opts['db']}` recreada\n";
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR creando la base: " . $e->getMessage() . "\n");
    exit(1);
}

// ---- 4) Lectura y parseo del SQL en sentencias -----------------------------
echo "[3/4] Leyendo SQL...\n";
$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "ERROR leyendo archivo SQL\n");
    exit(1);
}
// Eliminar BOM UTF-8 si está al inicio
if (substr($sql, 0, 3) === "\xEF\xBB\xBF") {
    $sql = substr($sql, 3);
}

// Parser de sentencias respetando strings, comentarios y DELIMITER
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer     = '';
    $delimiter  = ';';
    $len        = strlen($sql);
    $i          = 0;
    $line       = 1;
    $stmtStartLine = 1;

    while ($i < $len) {
        $c  = $sql[$i];
        $nc = ($i + 1 < $len) ? $sql[$i + 1] : '';

        // Saltar comentarios de línea --  (deben tener espacio o tab después según ANSI,
        // pero MySQL acepta -- al inicio también)
        if ($c === '-' && $nc === '-' && ($buffer === '' || ctype_space(substr($buffer, -1)))) {
            // Avanzar hasta fin de línea
            while ($i < $len && $sql[$i] !== "\n") {
                $i++;
            }
            // No incrementamos $line aquí: el \n lo hace el bucle al pasar
            continue;
        }
        // Saltar comentarios de línea estilo #
        if ($c === '#' && ($buffer === '' || ctype_space(substr($buffer, -1)))) {
            while ($i < $len && $sql[$i] !== "\n") {
                $i++;
            }
            continue;
        }
        // Saltar comentarios /* ... */  (excepto los condicionales /*! que mysql ejecuta)
        if ($c === '/' && $nc === '*') {
            $isMysqlHint = ($i + 2 < $len && $sql[$i + 2] === '!');
            if (!$isMysqlHint) {
                $i += 2;
                while ($i < $len) {
                    if ($sql[$i] === "\n") { $line++; }
                    if ($sql[$i] === '*' && $i + 1 < $len && $sql[$i + 1] === '/') {
                        $i += 2;
                        break;
                    }
                    $i++;
                }
                continue;
            }
            // Si es /*! ... */ lo dejamos pasar como SQL normal
        }

        // Strings: ', ", `
        if ($c === "'" || $c === '"' || $c === '`') {
            $quote = $c;
            $buffer .= $c;
            $i++;
            while ($i < $len) {
                $ch = $sql[$i];
                if ($ch === "\n") $line++;
                if ($ch === '\\' && $i + 1 < $len) {
                    // Escape: copiar siguiente sin interpretar
                    $buffer .= $ch . $sql[$i + 1];
                    $i += 2;
                    continue;
                }
                $buffer .= $ch;
                $i++;
                if ($ch === $quote) {
                    // Verificar duplicación ('') que en MySQL no escapa siempre,
                    // pero para SQL importado es seguro tratar como cierre.
                    break;
                }
            }
            continue;
        }

        // DELIMITER (cambio de delimitador)
        if (($c === 'D' || $c === 'd') && stripos(substr($sql, $i, 10), 'delimiter ') === 0
            && ($buffer === '' || ctype_space(substr($buffer, -1)) || substr($buffer, -1) === "\n")) {
            // Leer hasta fin de línea
            $eol = strpos($sql, "\n", $i);
            if ($eol === false) $eol = $len;
            $deLine = trim(substr($sql, $i + 10, $eol - $i - 10));
            if ($deLine !== '') {
                $delimiter = $deLine;
            }
            $i = $eol + 1;
            $line++;
            continue;
        }

        // Detectar delimiter
        $dl = strlen($delimiter);
        if (substr($sql, $i, $dl) === $delimiter) {
            $stmt = trim($buffer);
            if ($stmt !== '') {
                $statements[] = ['sql' => $stmt, 'line' => $stmtStartLine];
            }
            $buffer = '';
            $i += $dl;
            $stmtStartLine = $line;
            continue;
        }

        if ($c === "\n") $line++;
        $buffer .= $c;
        $i++;
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = ['sql' => $tail, 'line' => $stmtStartLine];
    }

    return $statements;
}

$statements = splitSqlStatements($sql);
$total      = count($statements);
echo "       {$total} sentencias detectadas\n";

// ---- 5) Ejecutar sentencias ------------------------------------------------
echo "[4/4] Ejecutando...\n";
$ok      = 0;
$errors  = 0;
$skipped = 0;
$lastReport = 0;

foreach ($statements as $idx => $st) {
    $sqlStmt = $st['sql'];
    $line    = $st['line'];

    // Saltarse cualquier USE de otra base que no sea la nuestra (defensivo)
    if (preg_match('/^\s*USE\s+`?([^`\s;]+)`?\s*$/i', $sqlStmt, $m)) {
        if (strtolower($m[1]) !== strtolower($opts['db'])) {
            $skipped++;
            continue;
        }
    }
    // Saltarse cualquier CREATE DATABASE / DROP DATABASE del SQL (ya lo hicimos)
    if (preg_match('/^\s*(CREATE|DROP)\s+DATABASE\b/i', $sqlStmt)) {
        $skipped++;
        continue;
    }

    try {
        $pdo->exec($sqlStmt);
        $ok++;
    } catch (Throwable $e) {
        $errors++;
        $preview = substr(preg_replace('/\s+/', ' ', $sqlStmt), 0, 120);
        fwrite(STDERR, "  [ERROR linea ~{$line}] {$e->getMessage()}\n");
        fwrite(STDERR, "    SQL: {$preview}...\n");
    }

    // Progreso cada 200 sentencias
    if (($idx + 1) - $lastReport >= 200) {
        echo "       ... " . ($idx + 1) . "/{$total} sentencias\n";
        $lastReport = $idx + 1;
    }
}

echo "------------------------------------------------------------\n";
echo " RESULTADO: OK={$ok}  saltadas={$skipped}  errores={$errors}\n";

// Verificación final: cuántas tablas tenemos
try {
    $cnt = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " .
        $pdo->quote($opts['db'])
    )->fetchColumn();
    echo " Tablas en `{$opts['db']}`: {$cnt}\n";
    if ($cnt < 10) {
        fwrite(STDERR, "AVISO: muy pocas tablas creadas (revisa los errores arriba).\n");
        exit(1);
    }
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR verificando tablas: " . $e->getMessage() . "\n");
    exit(1);
}

echo "============================================================\n";
exit($errors > 0 ? 1 : 0);
