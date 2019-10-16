import { MutationTree } from 'vuex';
import clone from '@/store/clone';
import Application from '@/store/Application';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET,
	APPLICATION_STATUS_SET,
	TARGET_LABEL_SET,
	WIKIBASE_REPO_CONFIGURATION_SET,
	ORIGINAL_STATEMENT_SET,
} from '@/store/mutationTypes';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import Term from '@/datamodel/Term';
import Statement from '@/datamodel/Statement';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';

export const mutations: MutationTree<Application> = {
	[ PROPERTY_TARGET_SET ]( state: Application, targetProperty: string ): void {
		state.targetProperty = targetProperty;
	},

	[ EDITFLOW_SET ]( state: Application, editFlow: string ): void {
		state.editFlow = editFlow;
	},

	[ APPLICATION_STATUS_SET ]( state: Application, status: ApplicationStatus ): void {
		state.applicationStatus = status;
	},

	[ TARGET_LABEL_SET ]( state: Application, label: Term ): void {
		state.targetLabel = label;
	},

	[ WIKIBASE_REPO_CONFIGURATION_SET ]( state: Application, config: WikibaseRepoConfiguration ): void {
		state.wikibaseRepoConfiguration = config;
	},

	[ ORIGINAL_STATEMENT_SET ]( state: Application, revision: Statement ): void {
		state.originalStatement = clone( revision );
	},
};
