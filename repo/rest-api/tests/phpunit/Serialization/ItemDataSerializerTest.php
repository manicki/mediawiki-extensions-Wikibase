<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Serialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Domain\Model\ItemData;
use Wikibase\Repo\RestApi\Domain\Model\ItemDataBuilder;
use Wikibase\Repo\RestApi\Serialization\ItemDataSerializer;
use Wikibase\Repo\RestApi\Serialization\SiteLinkListSerializer;
use Wikibase\Repo\RestApi\Serialization\StatementListSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\ItemDataSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemDataSerializerTest extends TestCase {

	/**
	 * @var MockObject|StatementListSerializer
	 */
	private $statementsSerializer;

	/**
	 * @var MockObject|SiteLinkListSerializer
	 */
	private $siteLinkListSerializer;

	protected function setUp(): void {
		$this->statementsSerializer = $this->createMock( StatementListSerializer::class );
		$this->statementsSerializer
			->method( 'serialize' )
			->willReturn( new ArrayObject( [ 'some' => 'serialization' ] ) );

		$this->siteLinkListSerializer = $this->createMock( SiteLinkListSerializer::class );
		$this->siteLinkListSerializer
			->method( 'serialize' )
			->willReturn( new ArrayObject( [ 'some' => 'serialization' ] ) );
	}

	public function testSerializeId(): void {
		$itemData = ( new ItemDataBuilder( new ItemId( 'Q123' ), [] ) )
			->build();

		$serialization = $this->newSerializer()->serialize( $itemData );

		$this->assertSame( $serialization['id'], $itemData->getId()->getSerialization() );
	}

	public function testSerializeType(): void {
		$itemData = $this->newItemDataBuilderWithSomeId( [ ItemData::FIELD_TYPE ] )
			->setType( Item::ENTITY_TYPE )
			->build();

		$serialization = $this->newSerializer()->serialize( $itemData );

		$this->assertSame( Item::ENTITY_TYPE, $serialization['type'] );
	}

	public function testSerializeLabels(): void {
		$enLabel = 'potato';
		$koLabel = '감자';
		$itemData = $this->newItemDataBuilderWithSomeId( [ ItemData::FIELD_LABELS ] )
			->setLabels( new TermList( [
				new Term( 'en', $enLabel ),
				new Term( 'ko', $koLabel )
			] ) )
			->build();

		$serialization = $this->newSerializer()->serialize( $itemData );

		$this->assertSame( $enLabel, $serialization['labels']['en'] );
		$this->assertSame( $koLabel, $serialization['labels']['ko'] );
	}

	public function testSerializeDescriptions(): void {
		$enDescription = 'root vegetable';
		$deDescription = 'Art der Gattung Nachtschatten (Solanum)';
		$itemData = $this->newItemDataBuilderWithSomeId( [ ItemData::FIELD_DESCRIPTIONS ] )
			->setDescriptions( new TermList( [
				new Term( 'en', $enDescription ),
				new Term( 'de', $deDescription )
			] ) )
			->build();

		$serialization = $this->newSerializer()->serialize( $itemData );

		$this->assertSame( $enDescription, $serialization['descriptions']['en'] );
		$this->assertSame( $deDescription, $serialization['descriptions']['de'] );
	}

	public function testSerializeAliases(): void {
		$enAliases = [ 'spud', 'tater' ];
		$deAliases = [ 'Erdapfel' ];
		$itemData = $this->newItemDataBuilderWithSomeId( [ ItemData::FIELD_ALIASES ] )
			->setAliases( new AliasGroupList( [
				new AliasGroup( 'en', $enAliases ),
				new AliasGroup( 'de', $deAliases ),
			] ) )
			->build();

		$serialization = $this->newSerializer()->serialize( $itemData );

		$this->assertSame( $enAliases, $serialization['aliases']['en'] );
		$this->assertSame( $deAliases, $serialization['aliases']['de'] );
	}

	public function testSerializeStatements(): void {
		$statements = $this->createStub( StatementList::class );
		$expectedSerialization = new ArrayObject( [ 'some' => 'serialization' ] );

		$itemData = $this->newItemDataBuilderWithSomeId( [ ItemData::FIELD_STATEMENTS ] )
			->setStatements( $statements )
			->build();

		$this->statementsSerializer = $this->createMock( StatementListSerializer::class );
		$this->statementsSerializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $statements )
			->willReturn( $expectedSerialization );

		$serialization = $this->newSerializer()->serialize( $itemData );

		$this->assertSame( $expectedSerialization, $serialization['statements'] );
	}

	public function testSerializeSiteLinks(): void {
		$siteLinks = $this->createStub( SiteLinkList::class );
		$expectedSerialization = new ArrayObject( [ 'some' => 'serialization' ] );

		$itemData = $this->newItemDataBuilderWithSomeId( [ ItemData::FIELD_SITELINKS ] )
			->setSiteLinks( $siteLinks )
			->build();

		$this->siteLinkListSerializer = $this->createMock( SiteLinkListSerializer::class );
		$this->siteLinkListSerializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $siteLinks )
			->willReturn( $expectedSerialization );

		$serialization = $this->newSerializer()->serialize( $itemData );

		$this->assertSame( $expectedSerialization, $serialization['sitelinks'] );
	}

	/**
	 * @dataProvider itemDataFieldsProvider
	 */
	public function testSkipsFieldsThatAreNotSet( ItemData $itemData, array $fields ): void {
		$serialization = $this->newSerializer()->serialize( $itemData );
		$serializationFields = array_keys( $serialization );

		$this->assertEqualsCanonicalizing( $fields, $serializationFields );
	}

	public function itemDataFieldsProvider(): Generator {
		yield [
			$this->newItemDataBuilderWithSomeId( [] )->build(),
			[ 'id' ]
		];
		yield [
			$this->newItemDataBuilderWithSomeId( [ ItemData::FIELD_TYPE ] )
				->setType( Item::ENTITY_TYPE )
				->build(),
			[ 'id', 'type' ]
		];
		yield [
			$this->newItemDataBuilderWithSomeId(
				[ ItemData::FIELD_LABELS, ItemData::FIELD_DESCRIPTIONS, ItemData::FIELD_ALIASES ]
			)
				->setLabels( new TermList() )
				->setDescriptions( new TermList() )
				->setAliases( new AliasGroupList() )
				->build(),
			[ 'id', 'labels', 'descriptions', 'aliases' ]
		];
		yield [
			$this->newItemDataBuilderWithSomeId( [ ItemData::FIELD_STATEMENTS ] )
				->setStatements( new StatementList() )
				->build(),
			[ 'id', 'statements' ]
		];
		yield [
			$this->newItemDataBuilderWithSomeId( ItemData::VALID_FIELDS )
				->setType( Item::ENTITY_TYPE )
				->setLabels( new TermList() )
				->setDescriptions( new TermList() )
				->setAliases( new AliasGroupList() )
				->setStatements( new StatementList() )
				->setSiteLinks( new SiteLinkList() )
				->build(),
			[ 'id', 'type', 'labels', 'descriptions', 'aliases', 'statements', 'sitelinks' ]
		];
	}

	private function newSerializer(): ItemDataSerializer {
		return new ItemDataSerializer( $this->statementsSerializer, $this->siteLinkListSerializer );
	}

	private function newItemDataBuilderWithSomeId( array $requestedFields ): ItemDataBuilder {
		return new ItemDataBuilder( new ItemId( 'Q666' ), $requestedFields );
	}

}
