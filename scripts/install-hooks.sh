#!/bin/bash

echo "🪝 Installing Git hooks..."

# Check if .git directory exists
if [ ! -d ".git" ]; then
    echo "❌ Error: .git directory not found. Are you in the project root?"
    exit 1
fi

# Create hooks directory if it doesn't exist
mkdir -p .git/hooks

# Copy pre-commit hook
cp scripts/pre-commit .git/hooks/pre-commit

# Make it executable
chmod +x .git/hooks/pre-commit

echo "✅ Pre-commit hook installed successfully!"
echo ""
echo "The following checks will run before each commit:"
echo "  - PHP CS Fixer (code style)"
echo "  - PHPStan (static analysis)"
echo ""
echo "To bypass hooks (not recommended): git commit --no-verify"
