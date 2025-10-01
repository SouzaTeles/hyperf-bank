bash:
	docker exec -it hyperf-pix /bin/bash

stop:
	docker stop hyperf-pix

migrate:
	docker compose exec hyperf-pix php bin/hyperf.php migrate

db-test:
	docker compose exec hyperf-pix php bin/hyperf.php db:test

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
