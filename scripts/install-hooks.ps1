# PowerShell script to install Git hooks

Write-Host "🪝 Installing Git hooks..." -ForegroundColor Cyan

# Check if .git directory exists
if (-not (Test-Path ".git")) {
    Write-Host "❌ Error: .git directory not found. Are you in the project root?" -ForegroundColor Red
    exit 1
}

# Create hooks directory if it doesn't exist
if (-not (Test-Path ".git\hooks")) {
    New-Item -ItemType Directory -Path ".git\hooks" | Out-Null
}

# Copy pre-commit hook (PHP script - works cross-platform)
Copy-Item "scripts\pre-commit" ".git\hooks\pre-commit" -Force

# No need to chmod on Windows - Git will execute it as PHP script

Write-Host "✅ Pre-commit hook installed successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "The hook is a PHP script compatible with Windows, Linux, and macOS"
Write-Host ""
Write-Host "The following checks will run before each commit:"
Write-Host "  - PHP CS Fixer (code style)"
Write-Host "  - PHPStan (static analysis level 9)"
Write-Host ""
Write-Host "To bypass hooks (not recommended): git commit --no-verify" -ForegroundColor Yellow
