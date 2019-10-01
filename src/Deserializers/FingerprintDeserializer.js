( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer,
	datamodel = require( 'wikibase.datamodel' ),
	MultiTermMapDeserializer = require( './MultiTermMapDeserializer.js' );

/**
 * @class wikibase.serialization.FingerprintDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
module.exports = util.inherit( 'WbFingerprintDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {datamodel.Fingerprint}
	 */
	deserialize: function( serialization ) {
		var termMapDeserializer = new MODULE.TermMapDeserializer(),
			multiTermMapDeserializer = new MultiTermMapDeserializer();

		return new datamodel.Fingerprint(
			termMapDeserializer.deserialize( serialization.labels ),
			termMapDeserializer.deserialize( serialization.descriptions ),
			multiTermMapDeserializer.deserialize( serialization.aliases )
		);
	}
} );

}( wikibase, util ) );
