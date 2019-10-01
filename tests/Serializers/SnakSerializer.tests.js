/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, dv, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SnakSerializer' );

var datamodel = require( 'wikibase.datamodel' );

var testSets = [
	[
		new datamodel.PropertyNoValueSnak( 'P1' ),
		{
			snaktype: 'novalue',
			property: 'P1'
		}
	], [
		new datamodel.PropertySomeValueSnak( 'P1' ),
		{
			snaktype: 'somevalue',
			property: 'P1'
		}
	], [
		new datamodel.PropertyValueSnak( 'P1', new dv.StringValue( 'some string' ) ),
		{
			snaktype: 'value',
			property: 'P1',
			datavalue: {
				type: 'string',
				value: 'some string'
			}
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	assert.expect( 3 );
	var snakSerializer = new wb.serialization.SnakSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			snakSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, dataValues, QUnit ) );
