<?php

namespace Wikibase\Lib\Tests\Store;

use Psr\Log\NullLogger;
use Wikibase\Lib\Store\HttpUrlPropertyOrderProvider;

/**
 * @covers \Wikibase\Lib\Store\HttpUrlPropertyOrderProvider
 * @covers \Wikibase\Lib\Store\WikiTextPropertyOrderProvider
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class HttpUrlPropertyOrderProviderTest extends \PHPUnit\Framework\TestCase {

	public function provideGetPropertyOrder() {
		yield from WikiTextPropertyOrderProviderTestHelper::provideGetPropertyOrder();
		yield [ false, null ];
	}

	/**
	 * @dataProvider provideGetPropertyOrder
	 */
	public function testGetPropertyOrder( $text, $expected ) {
		$instance = new HttpUrlPropertyOrderProvider(
			'page-url',
			$this->getHttp( $text ),
			new NullLogger()
		);
		$propertyOrder = $instance->getPropertyOrder();
		$this->assertSame( $expected, $propertyOrder );
	}

	private function getHttp( $text ) {
		HttpUrlPropertyOrderProviderTestMockHttp::$response = $text;
		return new HttpUrlPropertyOrderProviderTestMockHttp();
	}

}
