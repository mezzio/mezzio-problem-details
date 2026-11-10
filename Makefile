# Minimum version of PHP supported by this project
PHP_VERSION ?= 8.3
PHP_EXTENSIONS ?= mbstring json xdebug
DOCKER_IMAGE_NAME = mezzio/problem-details

# Includes _everything_ supplied by the internal tooling lib
include vendor/laminas/internal-tooling/Makefile

rector-ci:
	cd tools/rector && composer install
	tools/rector/vendor/bin/rector process --dry-run --output-format=github -c tools/rector/rector.php
.PHONY: rector-ci

require-checker-ci:
	cd tools/crc && composer install
	tools/crc/vendor/bin/composer-require-checker check --config-file=tools/crc/config.json
.PHONY: require-checker-ci

unused-ci:
	cd tools/unused && composer install
	tools/unused/vendor/bin/composer-unused --output-format=github
.PHONY: unused-ci
