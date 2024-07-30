<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Helpers;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\RestApi\Infrastructure\DataValuesValueDeserializer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class TestPropertyValuePairDeserializerFactory {

	private PropertyDataTypeLookup $dataTypeLookup;

	public function __construct() {
		$this->dataTypeLookup = new InMemoryDataTypeLookup();
	}

	public function setDataTypeForProperty( PropertyId $propertyId, string $dataTypeId ): void {
		$this->dataTypeLookup->setDataTypeForProperty( $propertyId, $dataTypeId );
	}

	public function createPropertyValuePairDeserializer(
		EntityIdParser $entityIdParser = null,
		DataValuesValueDeserializer $dataValuesValueDeserializer = null
	): PropertyValuePairDeserializer {
		return new PropertyValuePairDeserializer(
			$entityIdParser ?? new BasicEntityIdParser(),
			$this->dataTypeLookup,
			$dataValuesValueDeserializer ?? new DataValuesValueDeserializer(
				new DataTypeFactoryValueTypeLookup( WikibaseRepo::getDataTypeFactory() ),
				WikibaseRepo::getSnakValueDeserializer(),
				WikibaseRepo::getDataTypeValidatorFactory()
			)
		);
	}

}
