# Script para ver el log de creación de productos
Write-Host "=== LOG DE CREAR PRODUCTO ===" -ForegroundColor Green
Write-Host ""

Get-Content "c:\wamp64\logs\php_error.log" -Tail 100 | Where-Object {
    $_ -match "CREAR PRODUCTO|PROCESANDO IMAGENES|FILES|archivos a procesar|Procesando imagen|movido exitosamente"
} | ForEach-Object {
    if ($_ -match "ERROR") {
        Write-Host $_ -ForegroundColor Red
    } elseif ($_ -match "exitosamente") {
        Write-Host $_ -ForegroundColor Green
    } else {
        Write-Host $_ -ForegroundColor Cyan
    }
}
