jest.mock( 'vue', () => {
	return {
		directive: jest.fn(),
		use: jest.fn(),
	};
} );

import Vue from 'vue';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import MessagesPlugin from '@/presentation/plugins/MessagesPlugin';
import WikibaseClientConfiguration from '@/definitions/WikibaseClientConfiguration';
import BridgeConfig from '@/presentation/plugins/BridgeConfigPlugin';

const inlanguageDirective = {};
const mockInlanguage = jest.fn( ( _x: any ) => inlanguageDirective );
jest.mock( '@/presentation/directives/inlanguage', () => ( {
	__esModule: true,
	default: ( languageRepo: any ) => mockInlanguage( languageRepo ),
} ) );

describe( 'extendVueEnvironment', () => {
	it( 'attaches inlanguage directive', () => {
		const languageInfoRepo = new ( jest.fn() )();
		extendVueEnvironment(
			languageInfoRepo,
			new ( jest.fn() )(),
			{} as WikibaseClientConfiguration,
		);
		expect( mockInlanguage ).toHaveBeenCalledWith( languageInfoRepo );
		expect( Vue.directive ).toHaveBeenCalledWith( 'inlanguage', inlanguageDirective );
	} );

	it( 'invokes the Messages plugin', () => {
		const messageRepo = new ( jest.fn() )();
		extendVueEnvironment(
			new ( jest.fn() )(),
			messageRepo,
			{} as WikibaseClientConfiguration,
		);
		expect( Vue.use ).toHaveBeenCalledWith( MessagesPlugin, messageRepo );
	} );

	it( 'invokes the BridgeConfig plugin', () => {
		const config = { usePublish: true };
		extendVueEnvironment(
			new ( jest.fn() )(),
			new ( jest.fn() )(),
			config,
		);

		expect( Vue.use ).toHaveBeenCalledWith( BridgeConfig, config );
	} );
} );
