# Script para abrir el puerto 3001 en el Firewall de Windows
# Ejecutar como Administrador

Write-Host "🔥 Abriendo puerto 3001 en el Firewall de Windows..." -ForegroundColor Cyan

try {
    New-NetFirewallRule -DisplayName "HandinHand Socket.IO (TCP 3001)" `
                        -Direction Inbound `
                        -Protocol TCP `
                        -LocalPort 3001 `
                        -Action Allow `
                        -Profile Private,Public `
                        -ErrorAction Stop
    
    Write-Host "✅ Puerto 3001 abierto correctamente" -ForegroundColor Green
    Write-Host ""
    Write-Host "Verificando regla creada:" -ForegroundColor Yellow
    Get-NetFirewallRule -DisplayName "*HandinHand*" | Select-Object DisplayName, Enabled, Direction, Action | Format-Table
} catch {
    Write-Host "❌ Error al crear la regla de firewall" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ""
    Write-Host "⚠️ Asegúrate de ejecutar este script como Administrador" -ForegroundColor Yellow
    Write-Host "Click derecho en PowerShell → 'Ejecutar como Administrador'" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Presiona cualquier tecla para salir..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
