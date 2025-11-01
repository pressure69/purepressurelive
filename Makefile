check:
	php vendor/bin/phpcs -n
	php vendor/bin/phpstan analyse --no-progress --memory-limit=512M
	php vendor/bin/phpunit --no-progress --colors=always --do-not-cache-result
