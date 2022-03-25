<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidationResult;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemValidatorTest extends TestCase {

	/**
	 * @dataProvider dataProviderPass
	 */
	public function testValidatePass( GetItemRequest $request ): void {
		$result = ( new GetItemValidator() )->validate( $request );

		$this->assertFalse( $result->hasError() );
	}

	public function dataProviderPass(): \Generator {
		yield "valid ID with empty fields" => [
			new GetItemRequest( "Q123" )
		];

		yield "valid ID and fields" => [
			new GetItemRequest( "Q123", [ 'type', 'labels', 'descriptions' ] )
		];
	}

	/**
	 * @dataProvider dataProviderFail
	 */
	public function testValidateFail( GetItemRequest $request, string $expectedSource ): void {
		$result = ( new GetItemValidator() )->validate( $request );

		$this->assertTrue( $result->hasError() );
		$this->assertEquals( $expectedSource, $result->getError()->getSource() );
	}

	public function dataProviderFail(): \Generator {
		yield "invalid item ID" => [
			new GetItemRequest( "X123" ),
			GetItemValidationResult::SOURCE_ITEM_ID
		];

		yield "invalid field" => [
			new GetItemRequest( "Q123", [ 'type', 'unknown_field' ] ),
			GetItemValidationResult::SOURCE_FIELDS
		];

		yield "invalid item ID and invalid field" => [
			new GetItemRequest( "X123", [ 'type', 'unknown_field' ] ),
			GetItemValidationResult::SOURCE_ITEM_ID
		];
	}
}
