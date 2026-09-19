@echo off
REM ============================================================
REM  INSTALADOR DEFINITIVO FACTURACION ELECTRONICA DIAN COLOMBIA
REM ------------------------------------------------------------
REM  Usa PHP+PDO (no mysql.exe) para importar el SQL: es 100%%
REM  fiable y evita los problemas conocidos de redireccion de
REM  stdin de cmd.exe contra mysql.exe en Windows.
REM ============================================================

setlocal

set "DB_NAME=col_restaurante_db"
set "DB_USER=root"
set "DB_PASS="
set "DB_HOST=127.0.0.1"
set "DB_PORT=3306"
set "SQL_FILE=instalar_dian_completo.sql"

cd /d "%~dp0"

echo.
echo ====================================================
echo   INSTALADOR FACTURACION ELECTRONICA DIAN COLOMBIA
echo ====================================================
echo.

REM ---------- 1) Verificar PHP --------------------------------
echo [1/3] Verificando PHP...
where php >nul 2>nul
if errorlevel 1 (
    echo    ERROR: PHP no esta en el PATH.
    echo    Abre la terminal desde Laragon ^(que ya tiene PHP en el PATH^),
    echo    o agrega C:\laragon\bin\php\php-^<version^> al PATH.
    pause
    exit /b 1
)
echo    OK
echo.

REM ---------- 2) Verificar archivo SQL ------------------------
echo [2/3] Verificando archivo SQL...
if not exist "%SQL_FILE%" (
    echo    ERROR: no encuentro %SQL_FILE% en %CD%
    pause
    exit /b 1
)
for %%A in ("%SQL_FILE%") do echo    OK -^> %SQL_FILE% ^(%%~zA bytes^)
echo.

REM ---------- 3) Importar via PHP+PDO -------------------------
echo [3/3] Importando %SQL_FILE% via PHP/PDO...
echo ------------------------------------------------------------
php importar_sql.php "%SQL_FILE%" --db=%DB_NAME% --user=%DB_USER% --pass=%DB_PASS% --host=%DB_HOST% --port=%DB_PORT%
set "RC=%ERRORLEVEL%"
echo ------------------------------------------------------------
if not "%RC%"=="0" (
    echo.
    echo ERROR: el importador devolvio codigo %RC%.
    echo Revisa los mensajes de error arriba.
    pause
    exit /b %RC%
)

REM ---------- 4) Limpiar caches de Laravel --------------------
echo.
echo Limpiando caches de Laravel...
if exist "bootstrap\cache\packages.php" del /q "bootstrap\cache\packages.php"
if exist "bootstrap\cache\services.php" del /q "bootstrap\cache\services.php"
if exist "bootstrap\cache\config.php"   del /q "bootstrap\cache\config.php"
if exist "bootstrap\cache\routes-v7.php" del /q "bootstrap\cache\routes-v7.php"

call php artisan optimize:clear
if errorlevel 1 (
    echo    AVISO: optimize:clear devolvio error, revisalo manualmente.
)

echo.
echo ============================================
echo   INSTALACION DIAN COMPLETADA CORRECTAMENTE
echo ============================================
echo.
echo Siguientes pasos:
echo   1. Arranca el sitio en Laragon y entra como admin.
echo   2. Ve a Configuracion ^> DIAN Colombia y completa:
echo        - NIT, DV, razon social, direccion, ciudad.
echo        - Resolucion DIAN (numero, prefijo, rango, vigencia).
echo        - Software ID, PIN y clave tecnica del set de habilitacion.
echo        - Sube el certificado digital .p12 (de pruebas o produccion).
echo   3. Genera una venta tipo "Factura DIAN" desde el POS para probar.
echo.
pause
endlocal
