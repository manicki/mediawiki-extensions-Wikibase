<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;
use Wikibase\Repo\RestApi\Domain\Services\SitelinkTargetTitleResolver;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkDeserializer {

	private string $invalidTitleRegex;
	private array $allowedBadgeItemIds;
	private SitelinkTargetTitleResolver $titleResolver;

	/**
	 * @param string $invalidTitleRegex
	 * @param string[] $allowedBadgeItemIds
	 * @param SitelinkTargetTitleResolver $titleResolver
	 */
	public function __construct(
		string $invalidTitleRegex,
		array $allowedBadgeItemIds,
		SitelinkTargetTitleResolver $titleResolver
	) {
		$this->invalidTitleRegex = $invalidTitleRegex;
		$this->allowedBadgeItemIds = $allowedBadgeItemIds;
		$this->titleResolver = $titleResolver;
	}

	/**
	 * @throws SitelinkTargetNotFound
	 */
	public function deserialize( string $siteId, array $serialization ): SiteLink {
		if ( !array_key_exists( 'title', $serialization ) ) {
			throw new MissingFieldException( 'title' );
		}

		if ( !is_string( $serialization[ 'title' ] ) ) {
			throw new InvalidFieldTypeException( 'title' );
		}

		$trimmedTitle = trim( $serialization[ 'title' ] );
		if ( empty( $trimmedTitle ) ) {
			throw new EmptySitelinkException( 'title', $trimmedTitle );
		}
		if ( preg_match( $this->invalidTitleRegex, $trimmedTitle ) === 1 ) {
			throw new InvalidFieldException( 'title', $trimmedTitle );
		}

		$serialization[ 'badges' ] ??= [];
		if ( !is_array( $serialization[ 'badges' ] ) ) {
			throw new InvalidFieldTypeException( 'badges' );
		}

		$badges = [];
		foreach ( $serialization[ 'badges' ] as $badge ) {
			try {
				$badgeItemId = new ItemId( $badge );
			} catch ( InvalidArgumentException $e ) {
				throw new InvalidSitelinkBadgeException( $badge );
			}
			if ( !in_array( (string)$badgeItemId, $this->allowedBadgeItemIds ) ) {
				throw new BadgeNotAllowed( $badgeItemId );
			}
			$badges[] = $badgeItemId;
		}

		return new SiteLink(
			$siteId,
			$this->titleResolver->resolveTitle( $siteId, $trimmedTitle, $badges ),
			$badges
		);
	}

}
