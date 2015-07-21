<?php

namespace Wikibase\Repo\Api;

use ApiResult;
use InvalidArgumentException;
use Revision;
use SiteStore;
use Status;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\EntityRevision;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\LibSerializerFactory;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Builder for Api Results
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Daniel Kinzler
 */
class ResultBuilder {

	/**
	 * @var ApiResult
	 */
	private $result;

	/**
	 * @var int
	 */
	private $missingEntityCounter;

	/**
	 * @var LibSerializerFactory
	 */
	private $libSerializerFactory;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var SerializationOptions
	 */
	private $options;

	/**
	 * @var SerializationModifier
	 */
	private $modifier;

	/**
	 * @var bool when special elements such as '_element' are needed by the formatter.
	 */
	private $isRawMode;

	/**
	 * @param ApiResult $result
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param LibSerializerFactory $libSerializerFactory
	 * @param SerializerFactory $serializerFactory
	 * @param SiteStore $siteStore
	 * @param bool $isRawMode when special elements such as '_element' are needed by the formatter.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$result,
		EntityTitleLookup $entityTitleLookup,
		LibSerializerFactory $libSerializerFactory,
		SerializerFactory $serializerFactory,
		SiteStore $siteStore,
		$isRawMode
	) {
		if ( !$result instanceof ApiResult ) {
			throw new InvalidArgumentException( 'Result builder must be constructed with an ApiResult' );
		}

		$this->result = $result;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->libSerializerFactory = $libSerializerFactory;
		$this->serializerFactory = $serializerFactory;
		$this->missingEntityCounter = -1;
		$this->isRawMode = $isRawMode;
		$this->siteStore = $siteStore;
		$this->modifier = new SerializationModifier();
	}

	/**
	 * Returns the serialization options used by this ResultBuilder.
	 * This can be used to modify the options.
	 *
	 * @return SerializationOptions
	 */
	public function getOptions() {
		if ( !$this->options ) {
			$this->options = new SerializationOptions();
			$this->options->setIndexTags( $this->isRawMode );
			$this->options->setOption( EntitySerializer::OPT_SORT_ORDER, EntitySerializer::SORT_NONE );
		}

		return $this->options;
	}

	/**
	 * @since 0.5
	 *
	 * @param $success bool|int|null
	 *
	 * @throws InvalidArgumentException
	 */
	public function markSuccess( $success = true ) {
		$value = (int)$success;

		if ( $value !== 1 && $value !== 0 ) {
			throw new InvalidArgumentException(
				'$success must evaluate to either 1 or 0 when casted to integer'
			);
		}

		$this->result->addValue( null, 'success', $value );
	}

	/**
	 * Adds a list of values for the given path and name.
	 * This automatically sets the indexed tag name, if appropriate.
	 *
	 * To set atomic values or records, use setValue() or appendValue().
	 *
	 * @see ApiResult::addValue
	 * @see ApiResult::setIndexedTagName
	 * @see ResultBuilder::setValue()
	 * @see ResultBuilder::appendValue()
	 *
	 * @since 0.5
	 *
	 * @param $path array|string|null
	 * @param $name string
	 * @param $values array
	 * @param string $tag tag name to use for elements of $values
	 *
	 * @throws InvalidArgumentException
	 */
	public function setList( $path, $name, array $values, $tag ) {
		$this->checkPathType( $path );
		$this->checkNameIsString( $name );
		$this->checkTagIsString( $tag );

		if ( $this->isRawMode ) {
			// Unset first, so we don't make the tag name an actual value.
			// We'll be setting this to $tag by calling setIndexedTagName().
			unset( $values['_element'] );

			$values = array_values( $values );
			ApiResult::setIndexedTagName( $values, $tag );
		}

		$this->result->addValue( $path, $name, $values );
	}

	/**
	 * Set an atomic value (or record) for the given path and name.
	 * If the value is an array, it should be a record (associative), not a list.
	 * For adding lists, use setList().
	 *
	 * @see ResultBuilder::setList()
	 * @see ResultBuilder::appendValue()
	 * @see ApiResult::addValue
	 *
	 * @since 0.5
	 *
	 * @param $path array|string|null
	 * @param $name string
	 * @param $value mixed
	 *
	 * @throws InvalidArgumentException
	 */
	public function setValue( $path, $name, $value ) {
		$this->checkPathType( $path );
		$this->checkNameIsString( $name );
		$this->checkValueIsNotList( $value );

		$this->result->addValue( $path, $name, $value );
	}

	/**
	 * Appends a value to the list at the given path.
	 * This automatically sets the indexed tag name, if appropriate.
	 *
	 * If the value is an array, it should be associative, not a list.
	 * For adding lists, use setList().
	 *
	 * @see ResultBuilder::setList()
	 * @see ResultBuilder::setValue()
	 * @see ApiResult::addValue
	 * @see ApiResult::setIndexedTagName_internal
	 *
	 * @since 0.5
	 *
	 * @param $path array|string|null
	 * @param $key int|string|null the key to use when appending, or null for automatic.
	 * May be ignored even if given, based on $this->result->getIsRawMode().
	 * @param $value mixed
	 * @param string $tag tag name to use for $value in indexed mode
	 *
	 * @throws InvalidArgumentException
	 */
	public function appendValue( $path, $key, $value, $tag ) {
		$this->checkPathType( $path );
		$this->checkKeyType( $key );
		$this->checkTagIsString( $tag );

		$this->checkValueIsNotList( $value );

		if ( $this->isRawMode ) {
			$key = null;
		}

		$this->result->addValue( $path, $key, $value );
		$this->result->addIndexedTagName( $path, $tag );
	}

	/**
	 * @param array|string|null $path
	 *
	 * @throws InvalidArgumentException
	 */
	private function checkPathType( $path ) {
		if ( is_string( $path ) ) {
			$path = array( $path );
		}

		if ( !is_array( $path ) && $path !== null ) {
			throw new InvalidArgumentException( '$path must be an array (or null)' );
		}
	}

	/**
	 * @param string $name
	 *
	 * @throws InvalidArgumentException
	 */
	private function checkNameIsString( $name ) {
		if ( !is_string( $name ) ) {
			throw new InvalidArgumentException( '$name must be a string' );
		}
	}

	/**
	 * @param $key int|string|null the key to use when appending, or null for automatic.
	 *
	 * @throws InvalidArgumentException
	 */
	private function checkKeyType( $key ) {
		if ( $key !== null && !is_string( $key ) && !is_int( $key ) ) {
			throw new InvalidArgumentException( '$key must be a string, int, or null' );
		}
	}

	/**
	 * @param string $tag tag name to use for elements of $values
	 *
	 * @throws InvalidArgumentException
	 */
	private function checkTagIsString( $tag ) {
		if ( !is_string( $tag ) ) {
			throw new InvalidArgumentException( '$tag must be a string' );
		}
	}

	/**
	 * @param mixed $value
	 *
	 * @throws InvalidArgumentException
	 */
	private function checkValueIsNotList( $value ) {
		if ( is_array( $value ) && isset( $value[0] ) ) {
			throw new InvalidArgumentException( '$value must not be a list' );
		}
	}

	/**
	 * Get serialized entity for the EntityRevision and add it to the result
	 *
	 * @param string|null $sourceEntityIdSerialization EntityId used to retreive $entityRevision
	 *        Used as the key for the entity in the 'entities' structure and for adding redirect info
	 *        Will default to the entity's serialized ID if null.
	 *        If given this must be the entity id before any redirects were resolved.
	 * @param EntityRevision $entityRevision
	 * @param SerializationOptions|null $options
	 * @param array|string $props a list of fields to include, or "all"
	 * @param array $siteIds A list of site IDs to filter by
	 *
	 * @since 0.5
	 */
	public function addEntityRevision(
		$sourceEntityIdSerialization,
		EntityRevision $entityRevision,
		SerializationOptions
		$options = null,
		$props = 'all',
		$siteIds = array()
	) {
		$entity = $entityRevision->getEntity();
		$entityId = $entity->getId();

		if ( $sourceEntityIdSerialization === null ) {
			$sourceEntityIdSerialization = $entityId->getSerialization();
		}

		$record = array();

		$serializerOptions = $this->getOptions();
		if ( $options ) {
			$serializerOptions->merge( $options );
		}

		//if there are no props defined only return type and id..
		if ( $props === array() ) {
			$record['id'] = $entityId->getSerialization();
			$record['type'] = $entityId->getEntityType();
		} else {
			if ( $props == 'all' || in_array( 'info', $props ) ) {
				$title = $this->entityTitleLookup->getTitleForId( $entityId );
				$record['pageid'] = $title->getArticleID();
				$record['ns'] = $title->getNamespace();
				$record['title'] = $title->getPrefixedText();
				$record['lastrevid'] = $entityRevision->getRevisionId();
				$record['modified'] = wfTimestamp( TS_ISO_8601, $entityRevision->getTimestamp() );
			}
			if ( $sourceEntityIdSerialization !== $entityId->getSerialization() ) {
				$record['redirects'] = array(
					'from' => $sourceEntityIdSerialization,
					'to' => $entityId->getSerialization()
				);
			}

			//FIXME: $props should be used to filter $entitySerialization!
			// as in, $entitySerialization = array_intersect_key( $entitySerialization, array_flip( $props ) )
			$entitySerializer = $this->libSerializerFactory->newSerializerForObject( $entity, $serializerOptions );
			$entitySerialization = $entitySerializer->getSerialized( $entity );

			if ( !empty( $siteIds ) && array_key_exists( 'sitelinks', $entitySerialization ) ) {
				foreach ( $entitySerialization['sitelinks'] as $siteId => $siteLink ) {
					if ( is_array( $siteLink ) && !in_array( $siteLink['site'], $siteIds ) ) {
						unset( $entitySerialization['sitelinks'][$siteId] );
					}
				}
			}

			$record = array_merge( $record, $entitySerialization );
		}

		$this->appendValue( array( 'entities' ), $sourceEntityIdSerialization, $record, 'entity' );
	}

	/**
	 * Get serialized information for the EntityId and add them to result
	 *
	 * @param EntityId $entityId
	 * @param string|array|null $path
	 *
	 * @since 0.5
	 */
	public function addBasicEntityInformation( EntityId $entityId, $path ) {
		$this->setValue( $path, 'id', $entityId->getSerialization() );
		$this->setValue( $path, 'type', $entityId->getEntityType() );
	}

	/**
	 * Get serialized labels and add them to result
	 *
	 * @since 0.5
	 *
	 * @param TermList $labels the labels to insert in the result
	 * @param array|string $path where the data is located
	 */
	public function addLabels( TermList $labels, $path ) {
		$this->addTermList( $labels, 'labels', 'label', $path );
	}

	/**
	 * Adds fake serialization to show a label has been removed
	 *
	 * @since 0.5
	 *
	 * @param string $language
	 * @param array|string $path where the data is located
	 */
	public function addRemovedLabel( $language, $path ) {
		$this->addRemovedTerm( $language, 'labels', 'label', $path );
	}

	/**
	 * Get serialized descriptions and add them to result
	 *
	 * @since 0.5
	 *
	 * @param TermList $descriptions the descriptions to insert in the result
	 * @param array|string $path where the data is located
	 */
	public function addDescriptions( TermList $descriptions, $path ) {
		$this->addTermList( $descriptions, 'descriptions', 'description', $path );
	}

	/**
	 * Adds fake serialization to show a label has been removed
	 *
	 * @since 0.5
	 *
	 * @param string $language
	 * @param array|string $path where the data is located
	 */
	public function addRemovedDescription( $language, $path ) {
		$this->addRemovedTerm( $language, 'descriptions', 'description', $path );
	}

	/**
	 * Get serialized TermList and add it to the result
	 *
	 * @param TermList $termList
	 * @param string $name
	 * @param string $tag
	 * @param array|string $path where the data is located
	 */
	private function addTermList( TermList $termList, $name, $tag, $path ) {
		$serializer = $this->serializerFactory->newTermListSerializer();
		$value = $serializer->serialize( $termList );
		$this->setList( $path, $name, $value, $tag );
	}

	/**
	 * Adds fake serialization to show a term has been removed
	 *
	 * @param string $language
	 * @param array|string $path where the data is located
	 */
	private function addRemovedTerm( $language, $name, $tag, $path ) {
		$value = array(
			$language => array(
				'language' => $language,
				'removed' => '',
			)
		);
		$this->setList( $path, $name, $value, $tag );
	}

	/**
	 * Get serialized aliases and add them to result
	 *
	 * @since 0.5
	 *
	 * @param array $aliases the aliases to set in the result
	 * @param array|string $path where the data is located
	 */
	public function addAliases( array $aliases, $path ) {
		$aliasSerializer = $this->libSerializerFactory->newAliasSerializer( $this->getOptions() );
		$values = $aliasSerializer->getSerialized( $aliases );
		$this->setList( $path, 'aliases', $values, 'alias' );
	}

	/**
	 * Get serialized sitelinks and add them to result
	 *
	 * @since 0.5
	 *
	 * @todo use a SiteLinkListSerializer when created in DataModelSerialization here
	 *
	 * @param SiteLinkList $siteLinkList the site links to insert in the result
	 * @param array|string $path where the data is located
	 * @param bool $addUrl
	 */
	public function addSiteLinkList( SiteLinkList $siteLinkList, $path, $addUrl = false ) {
		$serializer = $this->serializerFactory->newSiteLinkSerializer();

		$values = array();
		foreach ( $siteLinkList->toArray() as $siteLink ) {
			$values[$siteLink->getSiteId()] = $serializer->serialize( $siteLink );
		}

		if ( $addUrl ) {
			$values = $this->getSiteLinkListArrayWithUrls( $values );
		}

		if ( $this->isRawMode ) {
			$values = $this->getRawModeSiteLinkListArray( $values );
		}

		$this->setList( $path, 'sitelinks', $values, 'sitelink' );
	}

	private function getSiteLinkListArrayWithUrls( array $array ) {
		$siteStore = $this->siteStore;
		$addUrlCallback = function( $array ) use ( $siteStore ) {
			$site = $siteStore->getSite( $array['site'] );
			if ( $site !== null ) {
				$array['url'] = $site->getPageUrl( $array['title'] );
			}
			return $array;
		};
		return $this->modifier->modifyUsingCallback( $array, '*', $addUrlCallback );
	}

	private function getRawModeSiteLinkListArray( array $array ) {
		$addIndexedBadgesCallback = function ( $array ) {
			ApiResult::setIndexedTagName( $array, 'badge' );
			return $array;
		};
		$array = array_values( $array );
		return $this->modifier->modifyUsingCallback( $array, '*/badges', $addIndexedBadgesCallback );
	}

	/**
	 * Adds fake serialization to show a sitelink has been removed
	 *
	 * @since 0.5
	 *
	 * @param SiteLinkList $siteLinkList
	 * @param array|string $path where the data is located
	 */
	public function addRemovedSiteLinks( SiteLinkList $siteLinkList, $path ) {
		$serializer = $this->serializerFactory->newSiteLinkSerializer();
		$values = array();
		foreach ( $siteLinkList->toArray() as $siteLink ) {
			$value = $serializer->serialize( $siteLink );
			$value['removed'] = '';
			$values[$siteLink->getSiteId()] = $value;
		}
		$this->setList( $path, 'sitelinks', $values, 'sitelink' );
	}

	/**
	 * Get serialized claims and add them to result
	 *
	 * @since 0.5
	 *
	 * @param Claim[] $claims the labels to set in the result
	 * @param array|string $path where the data is located
	 */
	public function addClaims( array $claims, $path ) {
		$claimsSerializer = $this->libSerializerFactory->newClaimsSerializer( $this->getOptions() );

		$values = $claimsSerializer->getSerialized( new Claims( $claims ) );

		// HACK: comply with ApiResult::setIndexedTagName
		$tag = isset( $values['_element'] ) ? $values['_element'] : 'claim';
		$this->setList( $path, 'claims', $values, $tag );
	}

	/**
	 * Get serialized claim and add it to result
	 *
	 * @param Claim $claim
	 *
	 * @since 0.5
	 */
	public function addClaim( Claim $claim ) {
		$serializer = $this->libSerializerFactory->newClaimSerializer( $this->getOptions() );

		//TODO: this is currently only used to add a Claim as the top level structure,
		//      with a null path and a fixed name. Would be nice to also allow claims
		//      to be added to a list, using a path and a id key or index.

		$value = $serializer->getSerialized( $claim );
		$this->setValue( null, 'claim', $value );
	}

	/**
	 * Get serialized reference and add it to result
	 *
	 * @param Reference $reference
	 *
	 * @since 0.5
	 */
	public function addReference( Reference $reference ) {
		$serializer = $this->libSerializerFactory->newReferenceSerializer( $this->getOptions() );

		//TODO: this is currently only used to add a Reference as the top level structure,
		//      with a null path and a fixed name. Would be nice to also allow references
		//      to be added to a list, using a path and a id key or index.

		$value = $serializer->getSerialized( $reference );
		$this->setValue( null, 'reference', $value );
	}

	/**
	 * Add an entry for a missing entity...
	 *
	 * @param string|null $key The key under which to place the missing entity in the 'entities'
	 *        structure. If null, defaults to the 'id' field in $missingDetails if that is set;
	 *        otherwise, it defaults to using a unique negative number.
	 * @param array $missingDetails array containing key value pair missing details
	 *
	 * @since 0.5
	 */
	public function addMissingEntity( $key, $missingDetails ) {
		if ( $key === null && isset( $missingDetails['id'] ) ) {
			$key = $missingDetails['id'];
		}

		if ( $key === null ) {
			$key = $this->missingEntityCounter;
		}

		$this->appendValue(
			'entities',
			$key,
			array_merge( $missingDetails, array( 'missing' => "" ) ),
			'entity'
		);

		$this->missingEntityCounter--;
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @param string $name
	 *
	 * @since 0.5
	 */
	public function addNormalizedTitle( $from, $to, $name = 'n' ) {
		$this->setValue(
			'normalized',
			$name,
			array( 'from' => $from, 'to' => $to )
		);
	}

	/**
	 * Adds the ID of the new revision from the Status object to the API result structure.
	 * The status value is expected to be structured in the way that EditEntity::attemptSave()
	 * resp WikiPage::doEditContent() do it: as an array, with an EntityRevision or Revision
	 *  object in the 'revision' field.
	 *
	 * If no revision is found the the Status object, this method does nothing.
	 *
	 * @see ApiResult::addValue()
	 *
	 * @since 0.5
	 *
	 * @param Status $status The status to get the revision ID from.
	 * @param string|null|array $path Where in the result to put the revision id
	 */
	public function addRevisionIdFromStatusToResult( Status $status, $path ) {
		$value = $status->getValue();

		if ( isset( $value['revision'] ) ) {
			$revision = $value['revision'];

			if ( $revision instanceof Revision ) {
				$revisionId = $revision->getId();
			} elseif ( $revision instanceof EntityRevision ) {
				$revisionId = $revision->getRevisionId();
			}

			$this->setValue( $path, 'lastrevid', empty( $revisionId ) ? 0 : $revisionId );
		}
	}

}
