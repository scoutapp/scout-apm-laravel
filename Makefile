.PHONY: *

default: unit cs static-analysis ## all the things

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

unit: ## run unit tests
	vendor/bin/phpunit

cs: ## verify code style rules
	vendor/bin/phpcs

static-analysis: ## verify that no new static analysis issues were introduced
	vendor/bin/psalm

coverage: ## generate code coverage reports
	vendor/bin/phpunit --testsuite unit --coverage-html build/coverage-html --coverage-text
