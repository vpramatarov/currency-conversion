# Installation

1. Copy repo https://github.com/vpramatarov/currency-conversion
2. Copy .env file to .env.local in ./currency-conversion/app/
3. Change values in env.local as follows:
```
	APP_ENV=dev
	REDIS_DSN="redis://redis:6379"
	APILAYER_KEY="" # obtain api key from https://apilayer.com/.
```
4. Copy ./currency-conversion/timeframe.json and paste it in ./currency-conversion/app/tests/Service/
5. Navigate to ./currency-conversion/ directory and run `docker-compose up --build`
6. Execute in php container (`docker exec -it container-hash-here /bin/sh`)
7. Run `composer install` in root directory
8. Run tests: `php bin/phpunit` in root directory
9. Navigate to `http://localhost/api` for UI
10. For `pair` in `\api\rates\{pair}` provide 2 ISO currency codes separated by underscore. Ex.: `CAD_CHF`. 