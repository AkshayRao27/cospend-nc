<template>
	<NcAppNavigation>
		<template v-if="!pageIsPublic && !loading" #search>
			<NcAppNavigationSearch v-model="projectFilterQuery"
				label="plop"
				:placeholder="t('cospend', 'Search projects')">
				<template #actions>
					<NcActions>
						<template #icon>
							<FolderPlusIcon :title="t('cospend', 'Create a project')" />
						</template>
						<NcActionButton
							:close-after-click="true"
							@click="showCreationModal = true">
							<template #icon>
								<PlusIcon />
							</template>
							{{ t('cospend', 'Create empty project') }}
						</NcActionButton>
						<NcActionButton
							:close-after-click="true"
							@click="onImportClick">
							<template #icon>
								<FileImportIcon />
							</template>
							{{ t('cospend', 'Import csv project') }}
						</NcActionButton>
						<NcActionButton
							:close-after-click="true"
							@click="onImportSWClick">
							<template #icon>
								<FileImportIcon />
							</template>
							{{ t('cospend', 'Import SplitWise project') }}
						</NcActionButton>
					</NcActions>
				</template>
			</NcAppNavigationSearch>
		</template>
		<template #list>
			<NewProjectModal v-if="showCreationModal"
				@close="showCreationModal = false" />
			<NcLoadingIcon v-if="loading" :size="24" />
			<NcEmptyContent v-else-if="sortedProjectIds.length === 0"
				:name="t('cospend', 'No projects yet')"
				:title="t('cospend', 'No projects yet')">
				<template #icon>
					<FolderIcon />
				</template>
			</NcEmptyContent>
			<AppNavigationProjectItem
				v-for="id in filteredProjectIds"
				:key="id"
				:project="projects[id]"
				:members="projects[id].members"
				:selected="id === selectedProjectId"
				:selected-member-id="selectedMemberId"
				:member-order="cospend.memberOrder"
				:trashbin-enabled="trashbinEnabled" />
			<AppNavigationUnreachableProjectItem v-for="invite in unreachableProjects"
				:key="'invite-' + invite.id"
				:invite="invite" />
		</template>
		<template #footer>
			<div id="app-settings">
				<div id="app-settings-header">
					<PendingInvitationsModal v-if="!pageIsPublic && showPendingInvitations"
						:invitations="pendingInvitations"
						@close="showPendingInvitations = false" />
					<!-- Cross-project balance navigation item (GitHub issue #281) -->
					<!-- Clickable item showing user's cumulative balance across all projects -->
					<!-- When clicked, triggers the cross-project balance view -->
					<NcAppNavigationItem v-if="!pageIsPublic && showMyBalance && myBalanceByCurrency && Object.keys(myBalanceByCurrency).length > 0"
						:name="t('cospend', 'Cumulative Balance')"
						@click="showCrossProjectBalanceView">
						<template #icon>
							<ColoredAvatar :user="currentUserId" />
						</template>
						<template #extra>
							<div class="balance-chips">
								<div v-for="(amount, currency) in myBalanceByCurrency"
									:key="currency"
									class="balance-item">
									<span class="currency-chip">{{ currency }}</span>
									<span :class="getBalanceClass(amount)">
										{{ formatBalanceAmount(amount) }}
									</span>
								</div>
							</div>
						</template>
					</NcAppNavigationItem>
					<NcAppNavigationItem v-if="!pageIsPublic && pendingInvitations.length > 0"
						:name="t('cospend', 'Pending share invitations')"
						@click="showPendingInvitations = true">
						<template #icon>
							<WebIcon />
						</template>
						<template #counter>
							<NcCounterBubble>
								{{ pendingInvitations.length }}
							</NcCounterBubble>
						</template>
					</NcAppNavigationItem>
					<NcAppNavigationItem v-if="!pageIsPublic && (archivedProjectIds.length > 0 || showArchivedProjects)"
						:name="showArchivedProjects ? t('cospend', 'Show active projects') : t('cospend', 'Show archived projects')"
						@click="toggleArchivedProjects">
						<template #icon>
							<CalendarIcon v-if="showArchivedProjects" />
							<ArchiveLockIcon v-else />
						</template>
						<template #counter>
							<NcCounterBubble>
								{{ sortedProjectIds.length - filteredProjectIds.length }}
							</NcCounterBubble>
						</template>
					</NcAppNavigationItem>
					<NcAppNavigationItem
						:name="t('cospend', 'Cospend settings')"
						@click="showSettings">
						<template #icon>
							<CogIcon />
						</template>
					</NcAppNavigationItem>
				</div>
			</div>
		</template>
	</NcAppNavigation>
</template>

<script>
import WebIcon from 'vue-material-design-icons/Web.vue'
import FolderPlusIcon from 'vue-material-design-icons/FolderPlus.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import FileImportIcon from 'vue-material-design-icons/FileImport.vue'
import CogIcon from 'vue-material-design-icons/Cog.vue'
import ArchiveLockIcon from 'vue-material-design-icons/ArchiveLock.vue'
import CalendarIcon from 'vue-material-design-icons/Calendar.vue'

import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcAppNavigationSearch from '@nextcloud/vue/dist/Components/NcAppNavigationSearch.js'

import AppNavigationProjectItem from './AppNavigationProjectItem.vue'
import NewProjectModal from './NewProjectModal.vue'
import PendingInvitationsModal from './PendingInvitationsModal.vue'
import AppNavigationUnreachableProjectItem from './AppNavigationUnreachableProjectItem.vue'
import ColoredAvatar from './avatar/ColoredAvatar.vue'

import cospend from '../state.js'
import * as constants from '../constants.js'
import { strcmp, importCospendProject, importSWProject } from '../utils.js'

import { emit } from '@nextcloud/event-bus'
import { showSuccess } from '@nextcloud/dialogs'
import { getCurrentUser } from '@nextcloud/auth'

export default {
	name: 'CospendNavigation',
	components: {
		ColoredAvatar,
		AppNavigationUnreachableProjectItem,
		PendingInvitationsModal,
		NewProjectModal,
		AppNavigationProjectItem,
		NcAppNavigation,
		NcEmptyContent,
		NcAppNavigationItem,
		NcActionButton,
		NcCounterBubble,
		NcLoadingIcon,
		NcActions,
		NcAppNavigationSearch,
		CogIcon,
		FileImportIcon,
		PlusIcon,
		FolderIcon,
		FolderPlusIcon,
		ArchiveLockIcon,
		CalendarIcon,
		WebIcon,
	},
	props: {
		projects: {
			type: Object,
			required: true,
		},
		selectedProjectId: {
			type: String,
			default: '',
		},
		selectedMemberId: {
			type: Number,
			default: null,
		},
		loading: {
			type: Boolean,
			default: false,
		},
		trashbinEnabled: {
			type: Boolean,
			default: false,
		},
		pendingInvitations: {
			type: Array,
			default: () => [],
		},
		unreachableProjects: {
			type: Array,
			default: () => [],
		},
	},
	data() {
		return {
			opened: false,
			creating: false,
			cospend,
			pageIsPublic: cospend.pageIsPublic,
			importMenuOpen: false,
			importingProject: false,
			showCreationModal: false,
			showArchivedProjects: false,
			showPendingInvitations: false,
			projectFilterQuery: '',
			currentUserId: getCurrentUser()?.uid,
		}
	},
	computed: {
		showMyBalance() {
			return cospend.showMyBalance
		},
		myBalanceByCurrency() {
			// Group balances by currency across all non-archived projects
			const balancesByCurrency = {}
			
			Object.values(this.projects)
				.filter(p => p.archived_ts === null)
				.forEach(project => {
					const me = project.members.find(m => m.userid === this.currentUserId)
					if (me && me.balance !== null && me.balance !== undefined) {
						const currency = project.currencyname || 'EUR'
						if (!balancesByCurrency[currency]) {
							balancesByCurrency[currency] = 0
						}
						balancesByCurrency[currency] += me.balance
					}
				})
			
			return balancesByCurrency
		},
		filteredProjectIds() {
			const projectIds = this.showArchivedProjects ? this.archivedProjectIds : this.nonArchivedProjectIds
			return this.projectFilterQuery === ''
				? projectIds
				: projectIds.filter(id => this.projects[id].name.toLowerCase().includes(this.projectFilterQuery.toLowerCase()))
		},
		nonArchivedProjectIds() {
			return this.sortedProjectIds.filter(id => this.projects[id].archived_ts === null)
		},
		archivedProjectIds() {
			return this.sortedProjectIds.filter(id => this.projects[id].archived_ts !== null)
		},
		sortedProjectIds() {
			if (this.cospend.sortOrder === 'name') {
				return Object.keys(this.projects).sort((a, b) => {
					return strcmp(this.projects[a].name, this.projects[b].name)
				})
			} else if (this.cospend.sortOrder === 'change') {
				return Object.keys(this.projects).sort((a, b) => {
					return this.projects[b].lastchanged - this.projects[a].lastchanged
				})
			} else {
				return Object.keys(this.projects)
			}
		},
		editionAccess() {
			return this.selectedProjectId && this.projects[this.selectedProjectId].myaccesslevel >= constants.ACCESS.PARTICIPANT
		},
	},
	beforeMount() {
	},
	methods: {
		toggleArchivedProjects() {
			this.showArchivedProjects = !this.showArchivedProjects
			emit('deselect-project')
		},
		showSettings() {
			emit('show-settings')
		},
		toggleMenu() {
			this.opened = !this.opened
		},
		closeMenu() {
			this.opened = false
		},
		onImportClick() {
			importCospendProject(() => {
				this.importingProject = true
			}, (data) => {
				emit('project-imported', data)
				showSuccess(t('cospend', 'Project imported'))
			}, () => {
				this.importingProject = false
			})
		},
		onImportSWClick() {
			importSWProject(() => {
				this.importingProject = true
			}, (data) => {
				emit('project-imported', data)
				showSuccess(t('cospend', 'Project imported'))
			}, () => {
				this.importingProject = false
			})
		},
		updateImportMenuOpen(isOpen) {
			if (!isOpen) {
				this.importMenuOpen = false
			}
		},
		/**
		 * Format balance amount only (without currency)
		 * @param {number} amount - The balance amount
		 * @return {string} Formatted amount
		 */
		formatBalanceAmount(amount) {
			return new Intl.NumberFormat(navigator.language, {
				minimumFractionDigits: 2,
				maximumFractionDigits: 2,
			}).format(Math.abs(amount))
		},
		/**
		 * Get CSS class for balance text color
		 * @param {number} amount - The balance amount
		 * @return {object} Class object for Vue binding
		 */
		getBalanceClass(amount) {
			return {
				'balance-positive': amount >= 0.01,
				'balance-negative': amount <= -0.01,
			}
		},
		/**
		 * Get NcCounterBubble type based on balance amount
		 * @param {number} amount - The balance amount
		 * @return {string} Type for NcCounterBubble ('success', 'error', or undefined)
		 */
		getBalanceType(amount) {
			if (amount >= 0.01) return 'success'
			if (amount <= -0.01) return 'error'
			return undefined
		},
		/**
		 * Show cross-project balance view
		 *
		 * Emits event to trigger display of cross-project balance aggregation view.
		 * This is called when user clicks on their cumulative balance in the navigation.
		 *
		 * Implementation for the Cross-project balances feature (GitHub issue #281).
		 *
		 * @since 1.6.0
		 */
		showCrossProjectBalanceView() {
			emit('show-cross-project-balances') // Trigger App.vue to switch to cross-project mode
		},
	},
}
</script>

<style scoped lang="scss">
// Properly center the navigation entry vertically
:deep(.app-navigation-entry-wrapper) {
	display: flex !important;
	align-items: center !important;
}

:deep(.app-navigation-entry) {
	display: flex !important;
	align-items: center !important;
	width: 100% !important;
	gap: 0 !important;
}

:deep(.app-navigation-entry__anchor) {
	display: flex !important;
	align-items: center !important;
	flex: 1 !important;
	gap: 12px !important;
}

:deep(.app-navigation-entry__name) {
	white-space: nowrap !important;
	overflow: hidden !important;
	text-overflow: ellipsis !important;
}

:deep(.app-navigation-entry__utils) {
	display: flex !important;
	align-items: center !important;
	justify-content: flex-end !important;
}

.balance-chips {
	display: flex;
	flex-direction: column;
	gap: 2px;
	align-items: flex-end;
}

.balance-item {
	display: flex;
	align-items: center;
	gap: 6px;

	.currency-chip {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		background: var(--color-background-dark);
		color: var(--color-main-text);
		padding: 1px 0;
		min-width: 32px;
		width: 32px;
		border-radius: 3px;
		font-size: 10px;
		font-weight: 700;
		line-height: 1.6;
		white-space: nowrap;
		text-transform: uppercase;
		text-align: center;
	}

	> span:not(.currency-chip) {
		font-family: var(--font-face);
		font-weight: 600;
		font-size: 14px;
		font-variant-numeric: tabular-nums;
		min-width: 50px;
		text-align: right;
	}
}

.balance-positive {
	color: var(--color-success);
}

.balance-negative {
	color: var(--color-error);
}
</style>
