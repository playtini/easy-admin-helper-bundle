.PHONY: all composer test

all: composer test help

help: ## show this help
	@egrep -h '\s##\s' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

test: ## phpunit
	docker run --rm -v $(CURDIR):/code/ php php /code/vendor/bin/phpunit -c /code/phpunit.xml.dist

composer: ## composer install
	docker run --rm -v $(CURDIR):/code/ composer:2 composer install --ignore-platform-reqs -n -d /code/