@echo off
chcp 65001 >nul
title CoupleSplit - Instalação

echo.
echo ╔══════════════════════════════════════╗
echo ║        CoupleSplit - Instalador      ║
echo ╚══════════════════════════════════════╝
echo.

:: ─── 1. Verifica se PHP está disponível ───────────────────────────────────────
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] PHP não encontrado. Baixando e instalando o Laragon...
    echo     Isso pode levar alguns minutos. Aguarde.
    echo.

    set LARAGON_URL=https://github.com/leokhoa/laragon/releases/download/6.0.0/laragon-wamp.exe
    set LARAGON_FILE=%TEMP%\laragon-setup.exe

    powershell -Command "Invoke-WebRequest -Uri '%LARAGON_URL%' -OutFile '%LARAGON_FILE%' -UseBasicParsing"

    if not exist "%LARAGON_FILE%" (
        echo [ERRO] Falha ao baixar o Laragon. Verifique sua conexão e tente novamente.
        pause
        exit /b 1
    )

    echo [✓] Download concluído. Abrindo instalador...
    echo.
    echo     IMPORTANTE: Instale o Laragon e depois FECHE e REABRA este arquivo .bat
    echo.
    "%LARAGON_FILE%"
    pause
    exit /b 0
)

echo [✓] PHP encontrado.

:: ─── 2. Verifica Composer ─────────────────────────────────────────────────────
composer -V >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] Composer não encontrado. Baixando...

    powershell -Command "Invoke-WebRequest -Uri 'https://getcomposer.org/Composer-Setup.exe' -OutFile '%TEMP%\composer-setup.exe' -UseBasicParsing"
    "%TEMP%\composer-setup.exe" /VERYSILENT

    echo [✓] Composer instalado. Feche e reabra este arquivo .bat para continuar.
    pause
    exit /b 0
)

echo [✓] Composer encontrado.

:: ─── 3. Entra na pasta do projeto ─────────────────────────────────────────────
cd /d "%~dp0..\couplesplit"

:: ─── 4. Copia o .env se não existir ──────────────────────────────────────────
if not exist ".env" (
    echo [✓] Criando arquivo .env...
    copy ".env.example" ".env" >nul
)

:: ─── 5. Instala dependências PHP ──────────────────────────────────────────────
echo.
echo [→] Instalando dependências PHP...
composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo [ERRO] Falha ao instalar dependências PHP.
    pause
    exit /b 1
)
echo [✓] Dependências PHP instaladas.

:: ─── 6. Gera a chave da aplicação ────────────────────────────────────────────
echo.
echo [→] Gerando chave da aplicação...
php artisan key:generate --force
echo [✓] Chave gerada.

:: ─── 7. Verifica Node/NPM ────────────────────────────────────────────────────
node -v >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo [!] Node.js não encontrado. O Laragon já inclui Node.
    echo     Feche e reabra o Laragon, depois rode este arquivo novamente.
    pause
    exit /b 1
)

:: ─── 8. Instala dependências JS e compila assets ─────────────────────────────
echo.
echo [→] Instalando dependências JavaScript...
call npm install --silent
echo [→] Compilando assets...
call npm run build
echo [✓] Assets compilados.

:: ─── 9. Executa as migrations ────────────────────────────────────────────────
echo.
echo [→] Configurando banco de dados...
php artisan migrate --force
if %errorlevel% neq 0 (
    echo [ERRO] Falha ao configurar o banco de dados.
    echo        Verifique se o banco de dados está rodando no Laragon.
    pause
    exit /b 1
)
echo [✓] Banco de dados configurado.

:: ─── 10. Sobe o servidor ─────────────────────────────────────────────────────
echo.
echo ══════════════════════════════════════════
echo   Instalação concluída!
echo   Acesse: http://localhost:8000
echo   Pressione Ctrl+C para encerrar.
echo ══════════════════════════════════════════
echo.
php artisan serve
