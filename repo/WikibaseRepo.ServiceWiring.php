<?php

declare( strict_types = 1 );

use MediaWiki\MediaWikiServices;
use ValueParsers\NullParser;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceDefinitionsConfigParser;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Modules\PropertyValueExpertsModule;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\EntitySourceDefinitionsLegacyRepoSettingsParser;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesEntitySourceDefinitionsConfigParser;
use Wikibase\Repo\ValueParserFactory;
use Wikibase\Repo\WikibaseRepo;

return [
	'WikibaseRepo.DataTypeDefinitions' => function ( MediaWikiServices $services ): DataTypeDefinitions {
		$baseDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';
		$repoDataTypes = require __DIR__ . '/WikibaseRepo.datatypes.php';

		$dataTypes = array_merge_recursive( $baseDataTypes, $repoDataTypes );

		$services->getHookContainer()->run( 'WikibaseRepoDataTypes', [ &$dataTypes ] );

		// TODO get $settings from $services
		$settings = WikibaseSettings::getRepoSettings();

		return new DataTypeDefinitions(
			$dataTypes,
			$settings->getSetting( 'disabledDataTypes' )
		);
	},

	'WikibaseRepo.DataTypeFactory' => function ( MediaWikiServices $services ): DataTypeFactory {
		return new DataTypeFactory(
			WikibaseRepo::getDataTypeDefinitions( $services )->getValueTypes()
		);
	},

	'WikibaseRepo.EntityIdParser' => function ( MediaWikiServices $services ): EntityIdParser {
		return new DispatchingEntityIdParser(
			WikibaseRepo::getEntityTypeDefinitions( $services )->getEntityIdBuilders()
		);
	},

	'WikibaseRepo.EntitySourceDefinitions' => function ( MediaWikiServices $services ): EntitySourceDefinitions {
		$settings = WikibaseRepo::getSettings( $services );
		$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );

		if ( $settings->hasSetting( 'entitySources' ) && !empty( $settings->getSetting( 'entitySources' ) ) ) {
			$configParser = new EntitySourceDefinitionsConfigParser();

			return $configParser->newDefinitionsFromConfigArray(
				$settings->getSetting( 'entitySources' ),
				$entityTypeDefinitions
			);
		}

		$parser = new EntitySourceDefinitionsLegacyRepoSettingsParser();

		if ( $settings->getSetting( 'federatedPropertiesEnabled' ) ) {
			$configParser = new FederatedPropertiesEntitySourceDefinitionsConfigParser( $settings );

			return $configParser->initializeDefaults(
				$parser->newDefinitionsFromSettings( $settings, $entityTypeDefinitions ),
				$entityTypeDefinitions
			);
		}

		return $parser->newDefinitionsFromSettings( $settings, $entityTypeDefinitions );
	},

	'WikibaseRepo.EntityTypeDefinitions' => function ( MediaWikiServices $services ): EntityTypeDefinitions {
		$baseEntityTypes = require __DIR__ . '/../lib/WikibaseLib.entitytypes.php';
		$repoEntityTypes = require __DIR__ . '/WikibaseRepo.entitytypes.php';

		$entityTypes = array_merge_recursive( $baseEntityTypes, $repoEntityTypes );

		$services->getHookContainer()->run( 'WikibaseRepoEntityTypes', [ &$entityTypes ] );

		return new EntityTypeDefinitions( $entityTypes );
	},

	'WikibaseRepo.PropertyValueExpertsModule' => function ( MediaWikiServices $services ): PropertyValueExpertsModule {
		return new PropertyValueExpertsModule( WikibaseRepo::getDataTypeDefinitions( $services ) );
	},

	'WikibaseRepo.Settings' => function ( MediaWikiServices $services ): SettingsArray {
		return WikibaseSettings::getRepoSettings();
	},

	'WikibaseRepo.ValueParserFactory' => function ( MediaWikiServices $services ): ValueParserFactory {
		$dataTypeDefinitions = WikibaseRepo::getDataTypeDefinitions( $services );
		$callbacks = $dataTypeDefinitions->getParserFactoryCallbacks();

		// For backwards-compatibility, also register parsers under legacy names,
		// for use with the deprecated 'parser' parameter of the wbparsevalue API module.
		$prefixedCallbacks = $dataTypeDefinitions->getParserFactoryCallbacks(
			DataTypeDefinitions::PREFIXED_MODE
		);
		if ( isset( $prefixedCallbacks['VT:wikibase-entityid'] ) ) {
			$callbacks['wikibase-entityid'] = $prefixedCallbacks['VT:wikibase-entityid'];
		}
		if ( isset( $prefixedCallbacks['VT:globecoordinate'] ) ) {
			$callbacks['globecoordinate'] = $prefixedCallbacks['VT:globecoordinate'];
		}
		// 'null' is not a datatype. Kept for backwards compatibility.
		$callbacks['null'] = function() {
			return new NullParser();
		};

		return new ValueParserFactory( $callbacks );
	},
];
