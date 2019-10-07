#! /bin/bash

set -x

PHPVERSION=`phpenv version-name`

if [ "${PHPVERSION}" = 'hhvm' ]
then
	PHPINI=/etc/hhvm/php.ini
	echo "hhvm.enable_zend_compat = true" >> $PHPINI
fi

originalDirectory=$(pwd)

cd ..

wget https://github.com/manicki/mediawiki/archive/travis-sqlite-debug.tar.gz
tar -zxf travis-sqlite-debug.tar.gz
mv mediawiki-travis-sqlite-debug phase3

cd phase3/extensions

if [ "$WB" != "repo" ]; then
	git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Scribunto.git --depth 1
fi
git clone https://gerrit.wikimedia.org/r/mediawiki/extensions/cldr --depth 1

cp -r $originalDirectory Wikibase

cd ..

cp $originalDirectory/build/travis/composer.local.json composer.local.json

composer self-update
composer install

# Try composer install again... this tends to fail from time to time
if [ $? -gt 0 ]; then
	composer install
fi

mysql -e 'create database its_a_mw;'
sqlite3 --version
which sqlite3
whereis sqlite3
php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin
