NOW=$(shell date +%Y%m%d_%H%M%S)
CURRENT_VERSION=$(shell sed -n "s,^Version: \(.*\)-dev.*,\1-$(NOW),p" atompub-plugin.php)

all:
	@echo Targets:
	@echo  - tag
	@echo    Releases a new version of the plugin. Performs the following steps:
	@echo      1) Set the version in atompub-plugin.php to VERSION and commits it.
	@echo      2) Creates a tag in version calles "vVERSION".
	@echo      3) Creates a zip file that can be uploaded to a wordpress installation.
	@echo      4) Set the version in atompub-plugin.php to NEXT_VERSION and commits it.
	@echo
	@echo  - snapshot
	@echo    Creates a new snapshot of the plugin for deployment to wordpress instances.

tag:
	@if [ -z "$(VERSION)" ]; then echo "Example: make VERSION=1.2.3 NEXT_VERSION=1.2.3 release"; exit 1; fi
	@if [ -z "$(NEXT_VERSION)" ]; then echo "Example: make VERSION=1.2.3 NEXT_VERSION=1.2.3 release"; exit 1; fi
	@echo Step 1..
	@cat atompub-plugin.php | sed "s,^\(Version:\).*,\1 $(VERSION)," > x
	@mv x atompub-plugin.php
	@git commit -m "o Releasing version $(VERSION)." atompub-plugin.php
	@echo Step 2..
	@git tag v$(VERSION)
	@echo Step 3..
	@git archive --format=zip HEAD > ../atompub-$(VERSION).zip
	@echo Step 4..
	@cat atompub-plugin.php | sed "s,^\(Version:\).*,\1 $(NEXT_VERSION)-dev," > x
	@mv x atompub-plugin.php
	@git commit -m "o Back to development version." atompub-plugin.php
	@echo tada!

snapshot:
	@if [ "$(shell git status --porcelain | wc -l)" -gt 0 ]; then echo You have changes and/or new files, please commit or remove before creating a snapshot; exit 1; fi
	cat atompub-plugin.php | sed "s,^\(Version: .*\)-dev.*,\1-$(NOW)," > x
	mv x atompub-plugin.php
	git add atompub-plugin.php
	git checkout-index -a -f --prefix=atompub/
	git checkout atompub-plugin.php
	zip -q -r ../atompub-$(CURRENT_VERSION).zip atompub
	rm -rf atompub
	git reset -q atompub-plugin.php
	git checkout atompub-plugin.php

clean:
	rm -f ../atompub-$(VERSION).zip
