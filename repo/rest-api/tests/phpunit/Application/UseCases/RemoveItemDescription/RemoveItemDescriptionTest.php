<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemoveItemDescription;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemDescription\RemoveItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemDescription\RemoveItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemDescription\RemoveItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemoveItemDescription\RemoveItemDescription
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemoveItemDescriptionTest extends TestCase {

	use EditMetadataHelper;

	private RemoveItemDescriptionValidator $validator;
	private AssertItemExists $assertItemExists;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$languageCode = 'en';
		$description = 'test description';
		$tags = TestValidatingRequestDeserializer::ALLOWED_TAGS;

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )
			->willReturn( NewItem::withId( $itemId )->andDescription( $languageCode, $description )->build() );

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				NewItem::withId( $itemId )->build(),
				$this->expectEquivalentMetadata( $tags, false, 'test', EditSummary::REMOVE_ACTION )
			);

		$request = new RemoveItemDescriptionRequest( (string)$itemId, $languageCode, $tags, false, 'test', null );
		$this->newUseCase()->execute( $request );
	}

	public function testInvalidRequest_throwsException(): void {
		$expectedException = new UseCaseException( 'invalid-remove-description-test' );
		$this->validator = $this->createStub( RemoveItemDescriptionValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( $this->createStub( RemoveItemDescriptionRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q123' );
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[ 0 ] ];

		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertItemExists = $this->createMock( AssertItemExists::class );
		$this->assertItemExists->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId )
			->willThrowException( $expectedException );

		try {
			$request = new RemoveItemDescriptionRequest( (string)$itemId, 'en', $editTags, false, 'test', null );
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenDescriptionDoesNotExist_throws(): void {
		$itemId = new ItemId( 'Q123' );
		$language = 'en';
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[ 1 ] ];

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( NewItem::withId( $itemId )->build() );

		try {
			$request = new RemoveItemDescriptionRequest( (string)$itemId, $language, $editTags, false, 'test', null );
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::DESCRIPTION_NOT_DEFINED, $e->getErrorCode() );
			$this->assertStringContainsString( (string)$itemId, $e->getErrorMessage() );
			$this->assertStringContainsString( $language, $e->getErrorMessage() );
		}
	}

	private function newUseCase(): RemoveItemDescription {
		return new RemoveItemDescription(
			$this->validator,
			$this->assertItemExists,
			$this->itemRetriever,
			$this->itemUpdater
		);
	}

}
