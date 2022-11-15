<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Serialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Repo\RestApi\Serialization\SerializerFactory;
use Wikibase\Repo\RestApi\Serialization\StatementSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\SerializerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SerializerFactoryTest extends TestCase {

	public function testNewStatementSerializer(): void {
		$this->assertInstanceOf(
			StatementSerializer::class,
			$this->newSerializerFactory()->newStatementSerializer()
		);
	}

	private function newSerializerFactory(): SerializerFactory {
		return new SerializerFactory(
			$this->createStub( PropertyDataTypeLookup::class )
		);
	}
}
