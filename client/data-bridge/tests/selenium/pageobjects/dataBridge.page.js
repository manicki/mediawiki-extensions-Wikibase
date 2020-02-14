const Page = require( 'wdio-mediawiki/Page' ),
	Util = require( 'wdio-mediawiki/Util' );

class DataBridgePage extends Page {
	static get OOUI() {
		return '.oo-ui-dialog';
	}

	static get ROOT() {
		return '#data-bridge-app';
	}

	static get HEADER_ELEMENTS() {
		const headerClass = '.wb-ui-processdialog-header';
		return {
			SAVE: `${headerClass} .wb-ui-event-emitting-button--primaryProgressive`,
			CANCEL: `${headerClass} .wb-ui-event-emitting-button--cancel`,
		};
	}

	static get ROOT_SWITCH() {
		return {
			INIT: '.wb-db-init',
			ERROR: '.wb-db-error',
			BRIDGE: '.wb-db-bridge',
		};
	}

	static get STRING_VALUE() {
		return '.wb-db-string-value .wb-db-string-value__input';
	}

	static get PROPERTY_LABEL() {
		return '.wb-db-property-label';
	}

	static get REFERENCES_SECTION() {
		return '.wb-db-references';
	}

	static get REFERENCE() {
		return '.wb-db-references__listItem';
	}

	static get EDIT_DECISION_SECTION() {
		return '.wb-db-edit-decision';
	}

	static get EDIT_DECISION_INPUT() {
		return 'input[name=editDecision]';
	}

	static get PERMISSION_ERROR_CONTAINER() {
		return '.wb-db-error-permission-info';
	}

	static get BAILOUT_ACTIONS() {
		return {
			CONTAINER: '.wb-db-bailout-actions',
			HEADING: '.wb-db-bailout-actions__heading',
			SUGGESTION_GO_TO_REPO: '.wb-db-bailout-actions__suggestion:nth-child(1)',
			SUGGESTION_EDIT_ARTICLE: '.wb-db-bailout-actions__suggestion:nth-child(2)',
		};
	}

	getDummyTitle() {
		return Util.getTestString( 'Talk:Data-bridge-test-page-' );
	}

	open( title ) {
		super.openTitle( title );
		Util.waitForModuleState( 'wikibase.client.data-bridge.app', 'ready', 10000 );
	}

	openBridgeOnPage( title ) {
		this.open( title );
		this.overloadedLink.click();
		this.app.waitForDisplayed( 10000 );
	}

	get overloadedLink() {
		return $( 'a=Edit this on Wikidata' );
	}

	get dialog() {
		return $( '.oo-ui-dialog' );
	}

	get app() {
		return $( '#data-bridge-app' );
	}

	get saveButton() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.HEADER_ELEMENTS.SAVE}`
		);
	}

	get cancelButton() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.HEADER_ELEMENTS.CANCEL}`
		);
	}

	get int() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.INIT}`
		);
	}

	get error() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.ERROR}`
		);
	}

	get permissionErrors() {
		return this.error.$$( DataBridgePage.PERMISSION_ERROR_CONTAINER );
	}

	get bailoutActions() {
		return this.error.$( DataBridgePage.BAILOUT_ACTIONS.CONTAINER );
	}

	get bailoutSuggestionGoToRepo() {
		return this.bailoutActions.$( DataBridgePage.BAILOUT_ACTIONS.SUGGESTION_GO_TO_REPO );
	}

	get bailoutSuggestionEditArticle() {
		return this.bailoutActions.$( DataBridgePage.BAILOUT_ACTIONS.SUGGESTION_EDIT_ARTICLE );
	}

	get bridge() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE}`
		);
	}

	get value() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE} ${DataBridgePage.STRING_VALUE}`
		);
	}

	get propertyLabel() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE} ${DataBridgePage.PROPERTY_LABEL}`
		);
	}

	nthReference( n ) {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE}
				${DataBridgePage.REFERENCES_SECTION} ${DataBridgePage.REFERENCE}:nth-child( ${n} )`
		);
	}

	editDecision( value ) {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE}
				${DataBridgePage.EDIT_DECISION_SECTION} ${DataBridgePage.EDIT_DECISION_INPUT}[value=${value}]`
		);
	}
}

module.exports = new DataBridgePage();
