# Employee Management System Server Startup Script
# PowerShell version for Windows

Write-Host "Starting Employee Management System Server..." -ForegroundColor Green
Write-Host ""

# Check if PHP is installed
Write-Host "Checking PHP installation..." -ForegroundColor Yellow
try {
    $phpVersion = php --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ PHP is available!" -ForegroundColor Green
        Write-Host ($phpVersion -split "`n")[0] -ForegroundColor Gray
    } else {
        throw "PHP not found"
    }
} catch {
    Write-Host "✗ ERROR: PHP is not installed or not in PATH!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please install PHP or add it to your system PATH." -ForegroundColor Yellow
    Write-Host "You can download PHP from: https://windows.php.net/download/" -ForegroundColor Cyan
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host ""

# Initialize database
Write-Host "Initializing database..." -ForegroundColor Yellow
try {
    php debug_db.php | Out-Null
    Write-Host "✓ Database setup complete!" -ForegroundColor Green
} catch {
    Write-Host "⚠ Database setup had issues, but continuing..." -ForegroundColor Yellow
}

Write-Host ""

# Start the server
Write-Host "Starting PHP development server..." -ForegroundColor Green
Write-Host ""
Write-Host "Server will be available at: " -NoNewline -ForegroundColor White
Write-Host "http://localhost:8000" -ForegroundColor Cyan
Write-Host ""
Write-Host "Open your browser and go to:" -ForegroundColor White
Write-Host "  • Main App: " -NoNewline -ForegroundColor White
Write-Host "http://localhost:8000/index.html" -ForegroundColor Cyan
Write-Host "  • API Test: " -NoNewline -ForegroundColor White
Write-Host "http://localhost:8000/api.php" -ForegroundColor Cyan
Write-Host "  • Database Setup: " -NoNewline -ForegroundColor White
Write-Host "http://localhost:8000/debug_db.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host ""

# Start PHP built-in server
php -S localhost:8000