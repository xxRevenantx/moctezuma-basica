$ErrorActionPreference = 'SilentlyContinue'

Write-Host "Diagnóstico OCR de tutores" -ForegroundColor Cyan
Write-Host ""

function Test-Command($name, $arguments) {
    $cmd = Get-Command $name -ErrorAction SilentlyContinue
    if (-not $cmd) {
        Write-Host "[NO DETECTADO] $name" -ForegroundColor Yellow
        return
    }

    Write-Host "[OK] $name -> $($cmd.Source)" -ForegroundColor Green
    & $cmd.Source @arguments 2>&1 | Select-Object -First 2
    Write-Host ""
}

Test-Command "tesseract" @('--version')
Test-Command "pdftoppm" @('-v')
Test-Command "magick" @('-version')

Write-Host "Idiomas de Tesseract:" -ForegroundColor Cyan
$tesseract = Get-Command tesseract -ErrorAction SilentlyContinue
if ($tesseract) {
    & $tesseract.Source --list-langs 2>&1
} else {
    Write-Host "Tesseract no está disponible en PATH. Configura TESSERACT_BINARY en .env." -ForegroundColor Yellow
}
