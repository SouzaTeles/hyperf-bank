bash:
	docker exec -it hyperf-pix /bin/bash

stop:
	docker stop hyperf-pix

migrate:
	docker compose exec hyperf-pix php bin/hyperf.php migrate

db-test:
	docker compose exec hyperf-pix php bin/hyperf.php db:test

# Cron
cron-process:
	docker compose exec hyperf-pix php bin/hyperf.php withdraw:process-scheduled

# Logs
logs:
	docker exec -it hyperf-pix tail -f /opt/www/runtime/logs/hyperf.log

logs-tail:
	docker exec -it hyperf-pix tail -n 100 /opt/www/runtime/logs/hyperf.log

logs-email:
	docker exec -it hyperf-pix grep "email" /opt/www/runtime/logs/hyperf.log

logs-withdraw:
	docker exec -it hyperf-pix grep -i "withdraw" /opt/www/runtime/logs/hyperf.log

logs-error:
	docker exec -it hyperf-pix grep "ERROR" /opt/www/runtime/logs/hyperf.log

logs-clear:
	docker exec -it hyperf-pix truncate -s 0 /opt/www/runtime/logs/hyperf.log

# Tests
test:
	docker compose exec hyperf-pix composer test

test-unit:
	docker compose exec hyperf-pix vendor/bin/phpunit --testsuite=Unit --testdox

test-integration:
	docker compose exec hyperf-pix vendor/bin/phpunit --testsuite=Integration --testdox

test-filter:
	docker compose exec hyperf-pix vendor/bin/phpunit --filter=$(filter)

test-coverage:
	docker compose exec hyperf-pix vendor/bin/phpunit --coverage-html coverage

# Code Quality
phpstan:
	docker compose exec hyperf-pix vendor/bin/phpstan analyse --memory-limit=200M

phpstan-baseline:
	docker compose exec hyperf-pix vendor/bin/phpstan analyse --memory-limit=200M --generate-baseline

cs-fix:
	docker compose exec hyperf-pix vendor/bin/php-cs-fixer fix

cs-check:
	docker compose exec hyperf-pix vendor/bin/php-cs-fixer fix --dry-run --diff

# Git Hooks
install-hooks:
	@echo Installing Git hooks...
	@if not exist .git\hooks mkdir .git\hooks
	@copy /Y scripts\pre-commit .git\hooks\pre-commit > nul 2>&1 || cp scripts/pre-commit .git/hooks/pre-commit
	@echo [OK] Pre-commit hook installed successfully!
	@echo Hook runs inside Docker - works on Windows, Linux, and macOS
