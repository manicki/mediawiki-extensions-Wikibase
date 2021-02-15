<?php

/**
 * Only to be used when Mediawiki's MockHttpTrait is not available.
 * Stubs the part of the said trait that are used in Federated Properties
 * tests.
 *
 * To be removed when Wikibase is no longer maintaining compatibility
 * with Mediawiki 1.35
 *
 * @license GPL-2.0-or-later
 */
trait MockHttpTrait {

	private function installMockHttp( MWHttpRequest $request = null ) {
	}

	private function makeFakeHttpRequest( string $body = 'Lorem Ipsum', int $statusCode = 200, array $headers = [] ) {
	}

}
