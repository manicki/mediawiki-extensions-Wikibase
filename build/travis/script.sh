#! /bin/bash

set -ex

cd ../phase3/tests/phpunit

php phpunit.php --debug --group Wikibase,Purtle

cd ../../extensions/Wikibase

composer test
