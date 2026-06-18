# Makefile
#
# @category    Library
# @package     TCPDF
# @author      Nicola Asuni <info@tecnick.com>
# @copyright   2002-2026 Nicola Asuni - Tecnick.com LTD
# @license     https://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
# @link        https://github.com/tecnickcom/TCPDF
#
# This file is part of tcpdf software library.
# ----------------------------------------------------------------------------------------------------------------------

SHELL=/bin/bash
.SHELLFLAGS=-o pipefail -c

# Project owner
OWNER=tecnickcom

# Project vendor
VENDOR=${OWNER}

# Project name
PROJECT=tcpdf

# Project version (strip trailing line endings and accidental literal "\\n")
VERSION=$(shell sed -E 's/\\n$$//' VERSION | tr -d '\r\n')

# Current directory
CURRENTDIR=$(dir $(realpath $(firstword $(MAKEFILE_LIST))))

# Target directory
TARGETDIR=$(CURRENTDIR)target

# sed argument for in-place substitutions
SEDINPLACE=-i
ifeq ($(shell uname -s),Darwin)
	SEDINPLACE=-i ''
endif

# Default port number for the example server
PORT?=8971

# PHP binary
PHP=$(shell which php)

# Composer executable
COMPOSER=$(PHP) -d "apc.enable_cli=0" $(shell which composer)

# tc-lib-pdf-font package path and sentinel asset
TCPDF_FONT_PKGDIR=./vendor/tecnickcom/tc-lib-pdf-font
TCPDF_FONT_SENTINEL=$(TCPDF_FONT_PKGDIR)/target/fonts/core/helvetica.json

# --- MAKE TARGETS ---

# Display general help about this command
.PHONY: help
help:
	@echo ""
	@echo "$(PROJECT) Makefile."
	@echo "The following commands are available:"
	@echo ""
	@awk '/^## /{desc=substr($$0,4)} /^\.PHONY:/{if(NF>1) {target=$$2; if(desc) printf "  make %-15s: %s\n",target,desc; desc=""}}' Makefile
	@echo ""
	@echo "To test and build everything from scratch, use the shortcut:"
	@echo "    make x"
	@echo ""

# Alias for help target
.PHONY: all
all: help

# Test and build everything from scratch
.PHONY: x
x: buildall

## Test and build everything from scratch
.PHONY: buildall
buildall: deps
	$(MAKE) qa

## Delete vendor and generated directories
.PHONY: clean
clean:
	rm -rf ./vendor ./tests/vendor $(TARGETDIR) ./build ./cache

## Clean all artifacts and download all dependencies
.PHONY: deps
deps: ensuretarget
	rm -rf ./vendor/*
	($(COMPOSER) install -vvv --no-interaction)
	curl --proto '=https' --tlsv1.2 --silent --show-error --fail --location https://carthage.software/mago.sh | bash -s -- --install-dir=./vendor/bin

## Initialize tc-lib-pdf font assets if needed
.PHONY: fonts
fonts:
	@if [ ! -d $(TCPDF_FONT_PKGDIR) ]; then \
		echo "tc-lib-pdf-font is not installed. Run composer install first."; \
		exit 1; \
	fi
	@if [ -f $(TCPDF_FONT_SENTINEL) ]; then \
		echo "tc-lib-pdf font assets already initialized."; \
	else \
		echo "Initializing tc-lib-pdf font assets..."; \
		$(MAKE) -C $(TCPDF_FONT_PKGDIR) clean; \
		$(COMPOSER) --working-dir=$(TCPDF_FONT_PKGDIR) install --no-interaction; \
		$(MAKE) -C $(TCPDF_FONT_PKGDIR) fonts; \
	fi

## Rebuild tc-lib-pdf font assets from scratch
.PHONY: fonts-rebuild
fonts-rebuild:
	@if [ ! -d $(TCPDF_FONT_PKGDIR) ]; then \
		echo "tc-lib-pdf-font is not installed. Run composer install first."; \
		exit 1; \
	fi
	$(MAKE) -C $(TCPDF_FONT_PKGDIR) clean
	$(COMPOSER) --working-dir=$(TCPDF_FONT_PKGDIR) install --no-interaction
	$(MAKE) -C $(TCPDF_FONT_PKGDIR) fonts

## Generate source code documentation with Doctum if available
.PHONY: doc
doc:
	@if [ -x ./vendor/bin/doctum ]; then \
		./vendor/bin/doctum update ./scripts/doctum.php --force; \
	else \
		echo "Doctum is not installed. Run make deps first."; \
		exit 1; \
	fi

## Create missing target directories for test and build artifacts
.PHONY: ensuretarget
ensuretarget:
	mkdir -p $(TARGETDIR)/test
	mkdir -p $(TARGETDIR)/report
	mkdir -p $(TARGETDIR)/doc

## Format the source code
.PHONY: format
format:
	./vendor/bin/mago fmt ./ examples

## Analyze and Lint the source code
.PHONY: lint
lint:
	./vendor/bin/mago --config ./mago.src.toml lint ./tcpdf.php ./scripts
	./vendor/bin/mago --config ./mago.src.toml analyze ./tcpdf.php ./scripts
	./vendor/bin/mago --config ./mago.test.toml lint ./test
	./vendor/bin/mago --config ./mago.test.toml analyze ./test

## Run all checks
.PHONY: qa
qa: version ensuretarget lint test

## Generate quality reports (not implemented in this legacy repository)
.PHONY: report
report: ensuretarget
	@echo "No additional report target is configured for TCPDF."

## Start the development server
.PHONY: server
server:
	$(PHP) -t examples -S localhost:$(PORT)

## Tag this git version
.PHONY: tag
tag:
	git checkout main && \
	git tag -a ${VERSION} -m "Release ${VERSION}" && \
	git push origin --tags && \
	git pull

## Run the PHPUnit test suite
.PHONY: test
test: ensuretarget
	XDEBUG_MODE=off $(PHP) ./vendor/bin/phpunit --configuration phpunit.xml.dist --no-coverage

## Run all examples headless and verify the produced PDF documents
.PHONY: smoke
smoke: ensuretarget
	$(PHP) ./scripts/example_smoke.php

## Generate the public method inventory reports
.PHONY: inventory
inventory: ensuretarget
	$(PHP) ./scripts/inventory.php

## Verify the delegation map and regenerate MAPPING.md
.PHONY: mapping
mapping:
	$(PHP) ./scripts/mapping.php

## Set the code version from the VERSION file
.PHONY: version
version:
	sed $(SEDINPLACE) -E "1,170 s#^// Version[[:space:]]+: .*#// Version      : ${VERSION}#" tcpdf.php
	sed $(SEDINPLACE) -E "1,170 s#^ \* @version .*# * @version ${VERSION}#" tcpdf.php

## Increase the version patch number
.PHONY: versionup
versionup:
	echo ${VERSION} | gawk -F. '{printf("%d.%d.%d\n",$$1,$$2,(($$3+1)));}' > VERSION
	$(MAKE) version
