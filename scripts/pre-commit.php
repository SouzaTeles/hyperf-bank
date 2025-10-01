<?php

/**
 * Pre-commit hook for code quality checks
 * Runs inside Docker container
 */

// ANSI color codes
const RED = "\033[0;31m";
const GREEN = "\033[0;32m";
const YELLOW = "\033[1;33m";
const BLUE = "\033[0;34m";
const RESET = "\033[0m";

function printHeader(string $text): void {
    echo "\n" . BLUE . $text . RESET . "\n";
}

function printSuccess(string $text): void {
    echo GREEN . "[OK] " . $text . RESET . "\n";
}

function printError(string $text): void {
    echo RED . "[ERROR] " . $text . RESET . "\n";
}

function printWarning(string $text): void {
    echo YELLOW . "[!] " . $text . RESET . "\n";
}

function runCommand(string $command): int {
    passthru($command, $returnCode);
    return $returnCode;
}

echo BLUE . "\n=== Running code quality checks ===\n" . RESET;

$failed = false;

// 1. PHP CS Fixer
printHeader("1. Checking code style (PHP CS Fixer)...");
$csFixerResult = runCommand("vendor/bin/php-cs-fixer fix --dry-run --diff --config=.php-cs-fixer.php");

if ($csFixerResult !== 0) {
    printError("Code style issues found!");
    printWarning("Run 'make cs-fix' to fix them automatically");
    echo "\n";
    $failed = true;
} else {
    printSuccess("Code style OK");
    echo "\n";
}

// 2. PHPStan
printHeader("2. Running static analysis (PHPStan level 9)...");
$phpstanResult = runCommand("vendor/bin/phpstan analyse --memory-limit=200M");

if ($phpstanResult !== 0) {
    printError("PHPStan found errors!");
    printWarning("Fix the errors or adjust phpstan.neon.dist");
    echo "\n";
    $failed = true;
} else {
    printSuccess("PHPStan OK");
    echo "\n";
}

// 3. Result
if ($failed) {
    echo RED . "\n=================================\n" . RESET;
    printError("Pre-commit checks FAILED!");
    printWarning("Fix the issues above before committing.");
    echo RED . "=================================\n\n" . RESET;
    exit(1);
}

echo GREEN . "\n=================================\n" . RESET;
printSuccess("All checks passed!");
echo GREEN . "=================================\n\n" . RESET;
exit(0);
