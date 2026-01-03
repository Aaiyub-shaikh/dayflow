@echo off
echo Starting Employee Management System Server...
echo.
echo Checking PHP installation...

php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH!
    echo.
    echo Please install PHP or add it to your system PATH.
    echo You can download PHP from: https://windows.php.net/download/
    echo.
    pause
    exit /b 1
)

echo PHP is available!
echo.

echo Initializing database...
php debug_db.php >nul 2>&1
echo Database setup complete!
echo.

echo Starting PHP development server...
echo.
echo Server will be available at: http://localhost:8000
echo.
echo Open your browser and go to:
echo   - Main App: http://localhost:8000/index.html
echo   - API Test: http://localhost:8000/api/
echo   - Database Setup: http://localhost:8000/debug_db.php
echo.
echo Press Ctrl+C to stop the server
echo.

php -S localhost:8000
pause