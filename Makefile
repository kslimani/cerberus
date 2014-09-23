usage:
	@printf "Usage: make {test|cc|csp|cs|clean}\n\n"
	@printf "   test    Run PHP test suites\n"
	@printf "   cc      Generate PHP code coverage report\n"
	@printf "   csp     Run PHP Coding Standards Fixer preview\n"
	@printf "   cs      Run PHP Coding Standards Fixer\n"
	@printf "   clean   Delete code coverage report\n\n"
test:
	vendor/bin/phpunit -v --stderr
cc:
	$(MAKE) clean
	vendor/bin/phpunit -v --stderr --coverage-html coverage-html
csp:
	vendor/bin/php-cs-fixer -vv fix src --dry-run --diff
	vendor/bin/php-cs-fixer -vv fix tests --dry-run --diff
cs:
	vendor/bin/php-cs-fixer -vv fix src
	vendor/bin/php-cs-fixer -vv fix tests
clean:
	rm -rf coverage-html
