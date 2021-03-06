<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use InvalidArgumentException;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitionsTest extends ServiceWiringTestCase {

	private function mockServices( array $settingsArray ) {
		$this->serviceContainer
			->method( 'get' )
			->willReturnCallback( function ( string $id ) use ( $settingsArray ) {
				switch ( $id ) {
					case 'WikibaseRepo.EntityTypeDefinitions':
						return new EntityTypeDefinitions( [] );
					case 'WikibaseRepo.Settings':
						return new SettingsArray( $settingsArray );
					default:
						throw new InvalidArgumentException( "Unexpected service name: $id" );
				}
			} );
	}

	public function testGetEntitySourceDefinitionsFromSettingsParsesSettings() {
		$settingsArray = [
			'federatedPropertiesEnabled' => false,
			'federatedPropertiesSourceScriptUrl' => 'https://www.wikidata.org/w/',
			'localEntitySourceName' => 'local',
			'entitySources' =>
				[
					'local' => [
						'entityNamespaces' => [ 'item' => 100, 'property' => 200 ],
						'repoDatabase' => false,
						'baseUri' => 'http://example.com/entity/',
						'rdfNodeNamespacePrefix' => 'wd',
						'rdfPredicateNamespacePrefix' => 'wdt',
						'interwikiPrefix' => 'localwiki'
					]
				]

		];
		$this->mockServices( $settingsArray );

		$sourceDefinitions = $this->getService( 'WikibaseRepo.EntitySourceDefinitions' );

		if ( $sourceDefinitions instanceof EntitySourceDefinitions ) {

			$itemSource = $sourceDefinitions->getSourceForEntityType( 'item' );

			$this->assertSame( 'local', $itemSource->getSourceName() );
			$this->assertSame( 'http://example.com/entity/', $itemSource->getConceptBaseUri() );
			$this->assertSame( 'wdt', $itemSource->getRdfPredicateNamespacePrefix() );
			$this->assertSame( 'wd', $itemSource->getRdfNodeNamespacePrefix() );
			$this->assertSame( 'localwiki', $itemSource->getInterwikiPrefix() );
			$this->assertSame( [ 'item' => 100, 'property' => 200 ], $itemSource->getEntityNamespaceIds() );
			$this->assertSame( [ 'item' => 'main', 'property' => 'main' ], $itemSource->getEntitySlotNames() );
			$this->assertSame( [ 'item', 'property' ], $itemSource->getEntityTypes() );
		}
	}

	public function testGetEntitySourceDefinitionsFromSettingsInitializesFederatedPropertiesDefaults() {
		$settingsArray = [
			'federatedPropertiesEnabled' => true,
			'federatedPropertiesSourceScriptUrl' => 'https://www.wikidata.org/w/',
			'localEntitySourceName' => 'local',
			'entityNamespaces' => [ 'item' => 120, 'property' => 122 ],
			'changesDatabase' => false,
			'conceptBaseUri' => 'http://localhost/entity/',
			'foreignRepositories' => []
		];
		$this->mockServices( $settingsArray );

		$sourceDefinitions = $this->getService( 'WikibaseRepo.EntitySourceDefinitions' );

		if ( $sourceDefinitions instanceof EntitySourceDefinitions ) {

			$itemSource = $sourceDefinitions->getSourceForEntityType( 'item' );

			$this->assertSame( 'local', $itemSource->getSourceName() );
			$this->assertSame( 'http://localhost/entity/', $itemSource->getConceptBaseUri() );
			$this->assertSame( '', $itemSource->getRdfPredicateNamespacePrefix() );
			$this->assertSame( 'wd', $itemSource->getRdfNodeNamespacePrefix() );
			$this->assertSame( '', $itemSource->getInterwikiPrefix() );
			$this->assertSame( [ 'item' => 120 ], $itemSource->getEntityNamespaceIds() );
			$this->assertSame( [ 'item' => 'main' ], $itemSource->getEntitySlotNames() );
			$this->assertSame( [ 'item' ], $itemSource->getEntityTypes() );

			$propertySource = $sourceDefinitions->getSourceForEntityType( 'property' );

			$this->assertSame( 'fedprops', $propertySource->getSourceName() );
			$this->assertSame( 'http://www.wikidata.org/entity/', $propertySource->getConceptBaseUri() );
			$this->assertSame( 'fpwd', $propertySource->getRdfPredicateNamespacePrefix() );
			$this->assertSame( 'fpwd', $propertySource->getRdfNodeNamespacePrefix() );
			$this->assertSame( 'wikidata', $propertySource->getInterwikiPrefix() );
			$this->assertSame( [ 'property' => 122 ], $propertySource->getEntityNamespaceIds() ); // uses wikidata default not config
			$this->assertSame( [ 'property' => 'main' ], $propertySource->getEntitySlotNames() );
			$this->assertSame( [ 'property' ], $propertySource->getEntityTypes() );
		}
	}

	public function testGetEntitySourceDefinitionsFromSettingsDefaultsToLegacyParser() {
		$settingsArray = [
			'federatedPropertiesEnabled' => false,
			'federatedPropertiesSourceScriptUrl' => 'https://www.wikidata.org/w/',
			'localEntitySourceName' => 'local',
			'entityNamespaces' => [ 'item' => 120, 'property' => 122 ],
			'changesDatabase' => false,
			'conceptBaseUri' => 'http://localhost/entity/',
			'foreignRepositories' => []
		];
		$this->mockServices( $settingsArray );

		$sourceDefinitions = $this->getService( 'WikibaseRepo.EntitySourceDefinitions' );

		if ( $sourceDefinitions instanceof EntitySourceDefinitions ) {

			$itemSource = $sourceDefinitions->getSourceForEntityType( 'item' );

			$this->assertSame( 'local', $itemSource->getSourceName() );
			$this->assertSame( 'http://localhost/entity/', $itemSource->getConceptBaseUri() );
			$this->assertSame( '', $itemSource->getRdfPredicateNamespacePrefix() );
			$this->assertSame( 'wd', $itemSource->getRdfNodeNamespacePrefix() );
			$this->assertSame( '', $itemSource->getInterwikiPrefix() );
			$this->assertSame( [ 'item' => 120, 'property' => 122 ], $itemSource->getEntityNamespaceIds() );
			$this->assertSame( [ 'item' => 'main', 'property' => 'main' ], $itemSource->getEntitySlotNames() );
			$this->assertSame( [ 'item', 'property' ], $itemSource->getEntityTypes() );
		}
	}

}
