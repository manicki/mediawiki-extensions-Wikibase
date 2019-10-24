import { HookHandler } from '@/HookHandler';
import { Store } from 'vuex';
import Application from '@/store/Application';
import { STATEMENT_TAINTED_STATE_TAINT, STATEMENT_TAINTED_STATE_UNTAINT } from '@/store/actionTypes';
import { HookRegistry } from '@/@types/mediawiki/MwWindow';
import TaintedChecker from '@/TaintedChecker';
import { Statement } from '@/definitions/wikibase-js-datamodel/Statement';

export default class MWHookHandler implements HookHandler {
	private mwHooks: HookRegistry;
	private taintedChecker: TaintedChecker;

	public constructor( mWHooks: HookRegistry, taintedChecker: TaintedChecker ) {
		this.mwHooks = mWHooks;
		this.taintedChecker = taintedChecker;
	}

	public addStore( store: Store<Application> ): void {
		this.addEditHook( store );
		this.addSaveHook( store );
	}

	private addEditHook( store: Store<Application> ): void {
		this.mwHooks( 'wikibase.statement.startEditing' ).add( ( guid: string ) => {
			store.dispatch( STATEMENT_TAINTED_STATE_UNTAINT, guid );
		} );
	}

	private addSaveHook( store: Store<Application> ): void {
		this.mwHooks( 'wikibase.statement.saved' ).add(
			( _entityId: string, statementId: string, oldStatement: Statement, newStatement: Statement ) => {
				if ( this.taintedChecker.check( oldStatement, newStatement ) ) {
					store.dispatch( STATEMENT_TAINTED_STATE_TAINT, statementId );
				}
			},
		);
	}
}
