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
					<!-- Custom navigation item for multi-currency balance display -->
					<div v-if="!pageIsPublic && showMyBalance && Object.keys(currencyBalances).length > 0"
						class="cumulative-balance-item"
						@click="showCrossProjectBalanceView">
						<div class="balance-item-content">
							<div class="balance-item-main">
								<div class="avatar-container">
									<ColoredAvatar :user="currentUserId" class="balance-avatar" />
								</div>
								<div class="title-container">
									<span class="balance-title">{{ t('cospend', 'Cumulative Balance') }}</span>
								</div>
							</div>
							<div class="balance-currencies">
								<!-- Single currency: show inline -->
								<div v-if="topCurrenciesForDisplay.length === 1"
									class="single-currency">
									<div class="currency-chip-container">
										<span class="currency-chip">{{ topCurrenciesForDisplay[0].currency }}</span>
									</div>
									<div class="balance-value-container">
										<span :class="['balance-value', topCurrenciesForDisplay[0].balance >= 0 ? 'positive' : 'negative']">
											{{ topCurrenciesForDisplay[0].formattedBalance }}
										</span>
									</div>
								</div>

								<!-- Multiple currencies: show as clean list -->
								<div v-else class="multi-currency">
									<div v-for="currencyInfo in topCurrenciesForDisplay"
										:key="currencyInfo.currency"
										class="currency-row">
										<div class="currency-chip-container">
											<span class="currency-chip">{{ currencyInfo.currency }}</span>
										</div>
										<div class="balance-value-container">
											<span :class="['balance-value', currencyInfo.balance >= 0 ? 'positive' : 'negative']">
												{{ currencyInfo.formattedBalance }}
											</span>
										</div>
									</div>
									<div v-if="hasMoreCurrencies" class="more-indicator">
										+{{ Object.keys(currencyBalances).length - 3 }} more
									</div>
								</div>
							</div>
						</div>
					</div>
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
		myBalance() {
			return Object.values(this.projects)
				.filter(p => p.archived_ts === null)
				.map(p => {
					const me = p.members.find(m => m.userid === this.currentUserId)
					return me ? me.balance : null
				})
				.filter(b => b !== null)
				.reduce((acc, balance) => acc + balance, 0)
		},

		/**
		 * Get currency information across all user's active projects
		 *
		 * This computed property aggregates balance data from all non-archived projects
		 * where the current user is a member. It calculates the net balance for each
		 * currency by summing individual project balances.
		 *
		 * Key features:
		 * - Only includes active (non-archived) projects for accuracy
		 * - Filters out balances under 0.01 to avoid showing negligible amounts
		 * - Aggregates by currency name to show total exposure per currency
		 *
		 * @return {Record<string, number>} Currency balances keyed by currency name
		 */
		currencyBalances() {
			const balances = {}

			Object.values(this.projects)
				.filter(p => p.archived_ts === null) // Only active projects
				.forEach(project => {
					const me = project.members.find(m => m.userid === this.currentUserId)
					if (me && Math.abs(me.balance) > 0.01) { // Only non-zero balances
						const currency = project.currencyname || 'Unknown'
						balances[currency] = (balances[currency] || 0) + me.balance
					}
				})

			return balances
		},

		/**
		 * Get top currencies for navigation display (maximum 3)
		 *
		 * This computed property implements a smart display strategy for multi-currency
		 * balance information in the navigation sidebar:
		 *
		 * Design decisions:
		 * - Shows maximum 3 currencies to maintain clean UI
		 * - Prioritizes currencies with highest absolute values (most significant)
		 * - Formats balances with appropriate precision (whole numbers vs decimals)
		 * - Provides formatted strings ready for display
		 *
		 * @return {Array<{currency: string, balance: number, formattedBalance: string}>}
		 */
		topCurrenciesForDisplay() {
			const currencies = Object.entries(this.currencyBalances)

			if (currencies.length === 0) {
				return []
			}

			// Sort by absolute balance (descending) and take top 3
			return currencies
				.sort(([, a], [, b]) => Math.abs(b) - Math.abs(a))
				.slice(0, 3)
				.map(([currency, balance]) => ({
					currency,
					balance,
					formattedBalance: Math.abs(balance).toFixed(balance % 1 === 0 ? 0 : 1),
				}))
		},

		/**
		 * Check if there are more currencies than the 3 displayed
		 *
		 * Used to show the "+X more" indicator when user has balances
		 * in more than 3 currencies, helping them understand there's
		 * additional balance information available in the full view.
		 *
		 * @return {boolean} True if more than 3 currencies exist
		 */
		hasMoreCurrencies() {
			return Object.keys(this.currencyBalances).length > 3
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
.cumulative-balance-item {
	cursor: pointer;
	padding: 0;
	padding-right: 5px;
	margin: 0;
	border-radius: 6px;
	transition: background-color 0.1s ease-in-out;

	&:hover {
		background-color: var(--color-background-hover);
	}

	&:active {
		background-color: var(--color-primary-element-light);
	}
}

.balance-item-content {
	display: flex;
	align-items: center;
	justify-content: space-between;
	min-height: 44px;
	gap: 12px;
}

.balance-item-main {
	display: flex;
	align-items: center;
	gap: 12px;
	min-width: 0;
	flex: 1;
}

.avatar-container,
.title-container,
.currency-chip-container,
.balance-value-container {
	display: flex;
	align-items: center;
	flex-shrink: 0;
}

.title-container {
	min-width: 0;
	flex: 1;
	margin-left: -8px;
}

.currency-chip-container {
	justify-content: center;
}

.balance-value-container {
	justify-content: flex-end;
}

.balance-avatar {
	width: 32px;
	height: 32px;
}

.balance-title {
	color: var(--color-main-text);
	font-weight: 400;
	font-size: 16px;
	line-height: 22px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.balance-currencies {
	flex-shrink: 0;
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	gap: 2px;
}

.single-currency,
.currency-row {
	display: flex;
	align-items: center;
	gap: 6px;
	height: 20px;
}

.currency-row {
	justify-content: space-around;
	width: 100%;
}

.multi-currency {
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	gap: 1px;
}

.currency-chip {
	color: var(--color-text-maxcontrast);
	font-size: 12px;
	font-weight: bold;
	background: var(--color-background-dark);
	padding: 2px 4px;
	border-radius: 3px;
	min-width: 28px;
	text-align: center;
	line-height: 1.2;
}

.balance-value {
	font-weight: 600;
	font-size: 14px;
	line-height: 1.2;

	&.positive {
		color: var(--color-success);
	}

	&.negative {
		color: var(--color-error);
	}
}

.more-indicator {
	font-size: 11px;
	color: var(--color-text-maxcontrast);
	opacity: 0.8;
	margin-top: 2px;
	text-align: right;
	line-height: 1;
}
</style>
