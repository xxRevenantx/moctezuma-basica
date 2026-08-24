@echo off
setlocal EnableExtensions
title Moctezuma Basica - Inicio completo

cd /d "%~dp0"

echo ============================================================
echo        CENTRO UNIVERSITARIO MOCTEZUMA - BASICA
echo              INICIO COMPLETO DEL PROYECTO
echo ============================================================
echo.

REM ------------------------------------------------------------
REM Validar que estamos en la raiz del proyecto
REM ------------------------------------------------------------
if not exist "artisan" (
    echo [ERROR] No se encontro el archivo artisan.
    echo Coloca este archivo en la carpeta raiz de Moctezuma Basica.
    echo.
    pause
    exit /b 1
)

if not exist "package.json" (
    echo [ERROR] No se encontro package.json.
    echo Coloca este archivo en la carpeta raiz de Moctezuma Basica.
    echo.
    pause
    exit /b 1
)

REM ------------------------------------------------------------
REM Buscar Git Bash
REM ------------------------------------------------------------
set "GIT_BASH="

if exist "C:\Program Files\Git\git-bash.exe" (
    set "GIT_BASH=C:\Program Files\Git\git-bash.exe"
)

if not defined GIT_BASH if exist "C:\Program Files (x86)\Git\git-bash.exe" (
    set "GIT_BASH=C:\Program Files (x86)\Git\git-bash.exe"
)

if not defined GIT_BASH (
    echo [ERROR] No se encontro Git Bash.
    echo Instala Git for Windows o revisa su ruta de instalacion.
    echo.
    pause
    exit /b 1
)

REM ------------------------------------------------------------
REM Preparar .env si no existe
REM ------------------------------------------------------------
if not exist ".env" (
    if exist ".env.example" (
        echo [INFO] No existe .env. Creando desde .env.example...
        copy /Y ".env.example" ".env" >nul
    ) else (
        echo [ERROR] No existe .env ni .env.example.
        echo.
        pause
        exit /b 1
    )
)

REM ------------------------------------------------------------
REM Dependencias PHP
REM ------------------------------------------------------------
if not exist "vendor\autoload.php" (
    echo [INFO] No existe vendor. Ejecutando composer install...
    where composer >nul 2>&1
    if errorlevel 1 (
        echo [ERROR] Composer no esta disponible en el PATH.
        echo Instala Composer o abre el proyecto desde el entorno donde ya funciona.
        echo.
        pause
        exit /b 1
    )

    call composer install

    if errorlevel 1 (
        echo.
        echo [ERROR] composer install fallo.
        pause
        exit /b 1
    )
)

REM ------------------------------------------------------------
REM APP_KEY
REM ------------------------------------------------------------
findstr /B /C:"APP_KEY=base64:" ".env" >nul 2>&1
if errorlevel 1 (
    echo [INFO] APP_KEY no detectada. Generando clave...
    call php artisan key:generate

    if errorlevel 1 (
        echo.
        echo [ERROR] No fue posible generar APP_KEY.
        pause
        exit /b 1
    )
)

REM ------------------------------------------------------------
REM Dependencias Node
REM ------------------------------------------------------------
if not exist "node_modules" (
    echo [INFO] No existe node_modules. Ejecutando npm install...
    where npm >nul 2>&1
    if errorlevel 1 (
        echo [ERROR] NPM no esta disponible en el PATH.
        echo Instala Node.js o revisa su configuracion.
        echo.
        pause
        exit /b 1
    )

    call npm install

    if errorlevel 1 (
        echo.
        echo [ERROR] npm install fallo.
        pause
        exit /b 1
    )
)

REM ------------------------------------------------------------
REM Limpiar caches de Laravel
REM ------------------------------------------------------------
echo [INFO] Limpiando caches de Laravel...
call php artisan optimize:clear >nul 2>&1

REM ------------------------------------------------------------
REM Abrir Laravel en Git Bash
REM ------------------------------------------------------------
echo [OK] Abriendo servidor Laravel...
start "Moctezuma Basica - Laravel" "%GIT_BASH%" --cd="%~dp0" -c "printf '\033]0;Moctezuma Basica - Laravel\007'; echo '=============================================='; echo ' MOCTEZUMA BASICA - LARAVEL'; echo '=============================================='; echo; echo '[OK] Ejecutando php artisan serve...'; echo; php artisan serve --host=127.0.0.1 --port=8000; echo; echo '[INFO] Laravel se detuvo.'; read -p 'Presiona Enter para cerrar...'"

REM ------------------------------------------------------------
REM Abrir Vite en Git Bash
REM ------------------------------------------------------------
echo [OK] Abriendo servidor Vite...
start "Moctezuma Basica - Vite" "%GIT_BASH%" --cd="%~dp0" -c "printf '\033]0;Moctezuma Basica - Vite\007'; echo '=============================================='; echo ' MOCTEZUMA BASICA - VITE'; echo '=============================================='; echo; echo '[OK] Ejecutando npm run dev...'; echo; npm run dev; echo; echo '[INFO] Vite se detuvo.'; read -p 'Presiona Enter para cerrar...'"

REM ------------------------------------------------------------
REM Esperar un poco y abrir navegador
REM ------------------------------------------------------------
echo [INFO] Abriendo Moctezuma Basica en el navegador...
timeout /t 4 /nobreak >nul

start "" "http://127.0.0.1:8000"

echo.
echo ============================================================
echo [OK] MOCTEZUMA BASICA ESTA INICIANDO
echo.
echo Laravel : http://127.0.0.1:8000
echo Vite    : servidor de desarrollo activo
echo.
echo Se abrieron dos consolas Git Bash:
echo   1. Laravel
echo   2. Vite
echo.
echo Puedes cerrar esta ventana.
echo Las otras dos deben permanecer abiertas mientras trabajas.
echo ============================================================
echo.

timeout /t 3 /nobreak >nul
exit /b 0
