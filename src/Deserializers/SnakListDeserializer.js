( function( wb, util, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.SnakListDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.SnakListDeserializer = util.inherit( 'WbSnakListDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {Object} serialization
	 * @param {string[]} [order]
	 * @return {wikibase.datamodel.SnakList}
	 *
	 * @throws {Error} if the order parameter is provided but the property id of a snak
	 *         serialization is no represented in the order.
	 * @throws {Error} if the order parameter is provided but no snak exists for a property
	 *         represented in the order.
	 */
	deserialize: function( serialization, order ) {
		var snakList = new wb.datamodel.SnakList();

		if( !order ) {
			// No order specified: Just loop through the json object:
			$.each( serialization, function( propertyId, snaksPerProperty ) {
				addSerializedSnaksToSnakList( snaksPerProperty, snakList );
			} );

		} else {
			// Check whether all property ids that are featured by snaks are specified in the order
			// list:
			$.each( serialization, function( propertyId ) {
				if( $.inArray( propertyId, order ) === -1 ) {
					throw new Error( 'Snak featuring the property id ' + propertyId + ' is not '
						+ 'present within list of property ids defined for ordering' );
				}
			} );

			// Add all snaks grouped by property according to the order specified via the "order"
			// parameter:
			for( var i = 0; i < order.length; i++ ) {
				var propertyId = order[i];

				if( !serialization[propertyId] ) {
					throw new Error( 'Trying to oder by property ' + propertyId + ' without any '
						+ 'snak featuring this property being present' );
				}

				addSerializedSnaksToSnakList( serialization[propertyId], snakList );
			}
		}

		return snakList;
	}
} );

/**
 * @param {Object[]} serializedSnaks
 * @param {wikibase.datamodel.SnakList} snakList
 * @return {wikibase.datamodel.SnakList}
 */
function addSerializedSnaksToSnakList( serializedSnaks, snakList ) {
	var snakDeserializer = new MODULE.SnakDeserializer();

	for( var i = 0; i < serializedSnaks.length; i++ ) {
		snakList.addItem( snakDeserializer.deserialize( serializedSnaks[i] ) );
	}

	return snakList;
}

}( wikibase, util, jQuery ) );
