/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ReferenceListSerializer' );

var datamodel = require( 'wikibase.datamodel' );

var testSets = [
	[
		new datamodel.ReferenceList(),
		[]
	], [
		new datamodel.ReferenceList( [ new datamodel.Reference() ] ),
		[
			{
				snaks: {},
				'snaks-order': []
			}
		]
	], [
		new datamodel.ReferenceList( [
			new datamodel.Reference( null, 'hash1' ),
			new datamodel.Reference( null, 'hash2' )
		] ),
		[
			{
				snaks: {},
				'snaks-order': [],
				hash: 'hash1'
			}, {
				snaks: {},
				'snaks-order': [],
				hash: 'hash2'
			}
		]
	]
];

QUnit.test( 'serialize()', function( assert ) {
	assert.expect( 3 );
	var referenceListSerializer = new wb.serialization.ReferenceListSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			referenceListSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
