<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyDescriptionException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidDescriptionException;

/**
 * @license GPL-2.0-or-later
 */
class DescriptionsSyntaxValidator {
	public const CODE_DESCRIPTIONS_NOT_ASSOCIATIVE = 'descriptions-syntax-validator-code-descriptions-not-associative';
	public const CODE_EMPTY_DESCRIPTION = 'descriptions-syntax-validator-code-description-empty';
	public const CODE_INVALID_DESCRIPTION_TYPE = 'descriptions-syntax-validator-code-invalid-description-type';

	public const CONTEXT_FIELD_NAME = 'descriptions-syntax-validator-context-field';
	public const CONTEXT_FIELD_VALUE = 'descriptions-syntax-validator-context-value';
	public const CONTEXT_FIELD_DESCRIPTION = 'descriptions-syntax-validator-context-description';
	public const CONTEXT_FIELD_LANGUAGE = 'descriptions-syntax-validator-context-language';

	private DescriptionsDeserializer $deserializer;
	private LanguageCodeValidator $languageCodeValidator;
	private PartiallyValidatedDescriptions $deserializedDescriptions;

	public function __construct( DescriptionsDeserializer $deserializer, LanguageCodeValidator $languageCodeValidator ) {
		$this->deserializer = $deserializer;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	public function validate( array $serialization ): ?ValidationError {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			return new ValidationError( self::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE );
		}

		return $this->validateLanguageCodes( array_keys( $serialization ) )
			?: $this->validateSerialization( $serialization );
	}

	private function validateLanguageCodes( array $languageCodes ): ?ValidationError {
		foreach ( $languageCodes as $languageCode ) {
			$languageValidationError = $this->languageCodeValidator->validate( (string)$languageCode );
			if ( $languageValidationError ) {
				return new ValidationError(
					LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
					array_merge(
						$languageValidationError->getContext(),
						[ LanguageCodeValidator::CONTEXT_PATH => 'descriptions' ]
					)
				);
			}
		}

		return null;
	}

	private function validateSerialization( array $serialization ): ?ValidationError {
		try {
			$this->deserializedDescriptions = new PartiallyValidatedDescriptions( $this->deserializer->deserialize( $serialization ) );
		} catch ( EmptyDescriptionException $e ) {
			return new ValidationError(
				self::CODE_EMPTY_DESCRIPTION,
				[ self::CONTEXT_FIELD_LANGUAGE => $e->getField() ]
			);
		} catch ( InvalidDescriptionException $e ) {
			return new ValidationError(
				self::CODE_INVALID_DESCRIPTION_TYPE,
				[ self::CONTEXT_FIELD_LANGUAGE => $e->getField(), self::CONTEXT_FIELD_DESCRIPTION => $e->getValue() ]
			);
		}

		return null;
	}

	public function getPartiallyValidatedDescriptions(): PartiallyValidatedDescriptions {
		return $this->deserializedDescriptions;
	}
}
