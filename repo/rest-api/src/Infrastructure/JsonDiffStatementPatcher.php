<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Exception;
use InvalidArgumentException;
use Swaggest\JsonDiff\JsonPatch;
use Swaggest\JsonDiff\PatchTestOperationFailedException;
use Swaggest\JsonDiff\PathException;
use Throwable;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Domain\Exceptions\InapplicablePatchException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedSerializationException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Services\StatementPatcher;
use Wikibase\Repo\RestApi\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Serialization\StatementSerializer;

/**
 * @license GPL-2.0-or-later
 */
class JsonDiffStatementPatcher implements StatementPatcher {

	private StatementSerializer $serializer;
	private StatementDeserializer $deserializer;

	public function __construct(
		StatementSerializer $serializer,
		StatementDeserializer $deserializer
	) {
		$this->serializer = $serializer;
		$this->deserializer = $deserializer;
	}

	/**
	 * @inheritDoc
	 */
	public function patch( Statement $statement, array $patch ): Statement {
		try {
			$patchDocument = JsonPatch::import( $patch );
		} catch ( Throwable $e ) {
			throw new InvalidArgumentException( 'Invalid patch' );
		}

		$statementSerialization = $this->serializer->serialize( $statement );

		$patchDocument->setFlags( JsonPatch::TOLERATE_ASSOCIATIVE_ARRAYS );

		try {
			$patchDocument->apply( $statementSerialization );
		} catch ( PatchTestOperationFailedException $e ) {
			throw new PatchTestConditionFailedException(
				$e->getMessage(),
				(array)$e->getOperation(),
				$e->getActualValue()
			);
		} catch ( PathException $e ) {
			throw new PatchPathException( $e->getMessage(), (array)$e->getOperation(), $e->getField() );
		} catch ( Exception $e ) {
			throw new InapplicablePatchException();
		}

		try {
			$patchedStatement = $this->deserializer->deserialize( $statementSerialization );
		} catch ( Exception $e ) {
			throw new InvalidPatchedSerializationException( $e->getMessage() );
		}

		return $patchedStatement;
	}

}
