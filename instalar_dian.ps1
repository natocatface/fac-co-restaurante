# ============================================================
# Script de instalación / migración a Facturación DIAN Colombia
# ------------------------------------------------------------
# Recrea desde cero la base de datos col_restaurante_db en el
# MySQL de Laragon, importa el backup bk_basededatos_pre_dian.sql
# y aplica las migraciones DIAN.
#
# Uso:
#   1. Abre PowerShell en la raíz del proyecto:
#         cd C:\Webs\PHP\col_restaurante
#   2. Ejecuta:
#         powershell -ExecutionPolicy Bypass -File .\instalar_dian.ps1
#
# Si tu MySQL tiene contraseña, edítala más abajo en $DB_PASS.
# ============================================================

$ErrorActionPreference = 'Stop'

# --- Configuración -----------------------------------------
$DB_NAME    = 'col_restaurante_db'
$DB_USER    = 'root'
$DB_PASS    = ''                                # ← pon aquí tu password si la tienes
$BACKUP_SQL = 'bk_basededatos_pre_dian.sql'     # respaldo previo (mismo contenido que bk_basededatos.sql)
# -----------------------------------------------------------

Write-Host ''
Write-Host '====================================================' -ForegroundColor Cyan
Write-Host '  INSTALADOR FACTURACION ELECTRONICA DIAN COLOMBIA' -ForegroundColor Cyan
Write-Host '====================================================' -ForegroundColor Cyan
Write-Host ''

# Construye los argumentos comunes para invocar mysql.exe
function Get-MysqlArgs {
    $a = @('-u', $DB_USER)
    if ($DB_PASS) { $a += "-p$DB_PASS" }
    $a += '--default-character-set=utf8mb4'
    return $a
}

# 1) Localizar mysql.exe dentro de Laragon
Write-Host '[1/6] Buscando mysql.exe en Laragon...' -ForegroundColor Yellow

$mysqlCandidatos = @(
    'C:\laragon\bin\mysql\mysql-8.4.*-winx64\bin\mysql.exe',
    'C:\laragon\bin\mysql\mysql-8.0.*-winx64\bin\mysql.exe',
    'C:\laragon\bin\mysql\mysql-5.7.*-winx64\bin\mysql.exe',
    'C:\laragon\bin\mysql\mariadb-*\bin\mysql.exe'
)

$mysqlExe = $null
foreach ($pattern in $mysqlCandidatos) {
    $hit = Get-ChildItem -Path $pattern -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($hit) { $mysqlExe = $hit.FullName; break }
}

if (-not $mysqlExe) {
    Write-Host '   ERROR: no encuentro mysql.exe en C:\laragon\bin\mysql\' -ForegroundColor Red
    Write-Host '   Dime la ruta exacta y la pongo manualmente en el script.' -ForegroundColor Red
    exit 1
}
Write-Host "   OK -> $mysqlExe" -ForegroundColor Green

# 2) Comprobar que el backup existe
Write-Host '[2/6] Verificando backup...' -ForegroundColor Yellow
if (-not (Test-Path $BACKUP_SQL)) {
    Write-Host "   ERROR: no encuentro $BACKUP_SQL en la carpeta actual." -ForegroundColor Red
    exit 1
}
$tam = (Get-Item $BACKUP_SQL).Length
Write-Host "   OK -> $BACKUP_SQL ($tam bytes)" -ForegroundColor Green

# 3) Comprobar conexión MySQL
Write-Host '[3/6] Probando conexion a MySQL...' -ForegroundColor Yellow
$argsTest = Get-MysqlArgs
& $mysqlExe @argsTest -e 'SELECT VERSION();' 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host '   ERROR: no puedo conectar a MySQL.' -ForegroundColor Red
    Write-Host '   Asegurate de que el servicio MySQL de Laragon este corriendo,' -ForegroundColor Red
    Write-Host '   y que el usuario/contrasena en este script sean correctos.' -ForegroundColor Red
    exit 1
}
Write-Host '   OK -> conexion correcta' -ForegroundColor Green

# 4) Recrear la base de datos (DROP + CREATE)
Write-Host "[4/6] Recreando base de datos '$DB_NAME'..." -ForegroundColor Yellow
$dropCreate = "DROP DATABASE IF EXISTS ``$DB_NAME``; CREATE DATABASE ``$DB_NAME`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
& $mysqlExe @argsTest -e $dropCreate
if ($LASTEXITCODE -ne 0) { Write-Host '   ERROR recreando BD' -ForegroundColor Red; exit 1 }
Write-Host '   OK -> BD limpia' -ForegroundColor Green

# 5) Importar el backup
#
# Importar SQL en MySQL desde PowerShell es notoriamente frágil:
#  - "cmd /c mysql -u root db < file" falla con rutas entrecomilladas anidadas
#  - "Get-Content -Raw | mysql.exe" no envía stdin de forma fiable a procesos nativos
#
# La forma 100% confiable es Start-Process -RedirectStandardInput, que conecta
# directamente el archivo al stdin del proceso vía API de Windows.
Write-Host "[5/6] Importando $BACKUP_SQL en $DB_NAME ..." -ForegroundColor Yellow

$absBackup = (Resolve-Path $BACKUP_SQL).Path
$importArgs = @('-u', $DB_USER)
if ($DB_PASS) { $importArgs += "-p$DB_PASS" }
$importArgs += '--default-character-set=utf8mb4'
$importArgs += '--binary-mode=1'
$importArgs += $DB_NAME

$stdoutLog = Join-Path $env:TEMP "dian_import_out.log"
$stderrLog = Join-Path $env:TEMP "dian_import_err.log"

$proc = Start-Process -FilePath $mysqlExe `
                      -ArgumentList $importArgs `
                      -RedirectStandardInput  $absBackup `
                      -RedirectStandardOutput $stdoutLog `
                      -RedirectStandardError  $stderrLog `
                      -NoNewWindow -Wait -PassThru

if ($proc.ExitCode -ne 0) {
    Write-Host '   ERROR importando backup' -ForegroundColor Red
    Write-Host '   stderr de mysql:' -ForegroundColor Red
    Get-Content $stderrLog | ForEach-Object { Write-Host "     $_" -ForegroundColor Red }
    exit 1
}

# Verificar que las tablas se importaron de verdad
$countQuery = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';"
$tables = (& $mysqlExe @argsTest -N -B -e $countQuery | Select-Object -First 1).Trim()
if ([int]$tables -lt 10) {
    Write-Host "   ERROR: el backup no parece haberse importado (solo $tables tablas)." -ForegroundColor Red
    if (Test-Path $stderrLog) {
        Write-Host '   stderr de mysql:' -ForegroundColor Red
        Get-Content $stderrLog | ForEach-Object { Write-Host "     $_" -ForegroundColor Red }
    }
    exit 1
}
Write-Host "   OK -> $tables tablas importadas" -ForegroundColor Green
Remove-Item $stdoutLog -ErrorAction SilentlyContinue
Remove-Item $stderrLog -ErrorAction SilentlyContinue

# 6) Aplicar migraciones DIAN y limpiar cachés
Write-Host '[6/6] Aplicando migraciones DIAN y limpiando caches...' -ForegroundColor Yellow

# Borrar caches del bootstrap por si quedó algo del SUNAT viejo
@('packages.php','services.php','config.php','routes-v7.php') | ForEach-Object {
    $p = Join-Path 'bootstrap\cache' $_
    if (Test-Path $p) { Remove-Item $p -Force }
}

php artisan migrate --force
if ($LASTEXITCODE -ne 0) { Write-Host '   ERROR en migrate' -ForegroundColor Red; exit 1 }

php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { Write-Host '   ERROR en optimize:clear' -ForegroundColor Red; exit 1 }

Write-Host ''
Write-Host '============================================' -ForegroundColor Green
Write-Host '  INSTALACION DIAN COMPLETADA CORRECTAMENTE' -ForegroundColor Green
Write-Host '============================================' -ForegroundColor Green
Write-Host ''
Write-Host 'Siguientes pasos:' -ForegroundColor Cyan
Write-Host '  1. Arranca el sitio en Laragon y entra como admin.'
Write-Host '  2. Ve a Configuracion > DIAN Colombia y completa:'
Write-Host '       - NIT, DV, razon social, direccion, ciudad.'
Write-Host '       - Resolucion DIAN (numero, prefijo, rango, vigencia).'
Write-Host '       - Software ID, PIN y clave tecnica del set de habilitacion.'
Write-Host '       - Sube el certificado digital .p12 (de pruebas o produccion).'
Write-Host '  3. Genera una venta tipo "Factura DIAN" desde el POS para probar.'
Write-Host ''
