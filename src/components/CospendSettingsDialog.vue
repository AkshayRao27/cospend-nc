<!--
  - @copyright Copyright (c) 2021 Julien Veyssier <julien-nc@posteo.net>
  -
  - @author Julien Veyssier <julien-nc@posteo.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<div id="settings-container">
		<NcAppSettingsDialog
			class="cospend-settings-dialog"
			:name="t('cospend', 'Cospend settings')"
			:title="t('cospend', 'Cospend settings')"
			:open.sync="showSettings"
			:show-navigation="true"
			container="#settings-container">
			<NcAppSettingsSection
				id="about"
				:name="t('cospend', 'About Cospend')"
				:title="t('cospend', 'About Cospend')"
				class="app-settings-section">
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'Thanks for using Cospend') + ' ♥' }}
				</h3>
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'App version: {version}', { version: cospendVersion }) }}
				</h3>
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'Bug/issue tracker') + ': ' }}
				</h3>
				<a href="https://github.com/julien-nc/cospend-nc/issues"
					target="_blank"
					class="external">
					https://github.com/julien-nc/cospend-nc/issues
					<OpenInNewIcon :size="16" />
				</a>
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'Translation') + ': ' }}
				</h3>
				<a href="https://crowdin.com/project/moneybuster"
					target="_blank"
					class="external">
					https://crowdin.com/project/moneybuster
					<OpenInNewIcon :size="16" />
				</a>
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'User documentation') + ': ' }}
				</h3>
				<a href="https://github.com/julien-nc/cospend-nc/blob/master/docs/user.md"
					target="_blank"
					class="external">
					https://github.com/julien-nc/cospend-nc/blob/master/docs/user.md
					<OpenInNewIcon :size="16" />
				</a>
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'Admin documentation') + ': ' }}
				</h3>
				<a href="https://github.com/julien-nc/cospend-nc/blob/master/docs/admin.md"
					target="_blank"
					class="external">
					https://github.com/julien-nc/cospend-nc/blob/master/docs/admin.md
					<OpenInNewIcon :size="16" />
				</a>
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'Developer documentation') + ': ' }}
				</h3>
				<a href="https://github.com/julien-nc/cospend-nc/blob/master/docs/dev.md"
					target="_blank"
					class="external">
					https://github.com/julien-nc/cospend-nc/blob/master/docs/dev.md
					<OpenInNewIcon :size="16" />
				</a>
			</NcAppSettingsSection>
			<NcAppSettingsSection v-if="!pageIsPublic"
				id="import"
				:name="t('cospend', 'Import projects')"
				:title="t('cospend', 'Import projects')"
				class="app-settings-section">
				<div class="oneLine">
					<NcButton @click="onImportClick">
						<template #icon>
							<NcLoadingIcon v-if="importingProject" />
							<FileImportIcon v-else :size="20" />
						</template>
						{{ t('cospend', 'Import csv project') }}
					</NcButton>
					<NcButton @click="onImportSWClick">
						<template #icon>
							<NcLoadingIcon v-if="importingSWProject" />
							<FileImportIcon v-else :size="20" />
						</template>
						{{ t('cospend', 'Import SplitWise project') }}
					</NcButton>
				</div>
			</NcAppSettingsSection>
			<NcAppSettingsSection v-if="!pageIsPublic"
				id="export"
				:name="t('cospend', 'Export location')"
				:title="t('cospend', 'Export location')"
				class="app-settings-section">
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'Select export directory') }}
				</h3>
				<input
					type="text"
					class="app-settings-section__input"
					:value="outputDir"
					:disabled="false"
					:readonly="true"
					@click="onOutputDirClick">
			</NcAppSettingsSection>
			<NcAppSettingsSection
				id="sort"
				:name="t('cospend', 'Sort criteria')"
				:title="t('cospend', 'Sort criteria')"
				class="app-settings-section">
				<div v-if="!pageIsPublic">
					<h3 class="app-settings-section__hint">
						{{ t('cospend', 'How projects are sorted in navigation sidebar') }}
					</h3>
					<label for="sort-select">
						{{ t('cospend', 'Projects order') }}
					</label>
					<select id="sort-select" v-model="sortOrder" @change="onSortOrderChange">
						<option value="name">
							{{ t('cospend', 'Name') }}
						</option>
						<option value="change">
							{{ t('cospend', 'Last activity') }}
						</option>
					</select>
				</div>
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'How members are sorted') }}
				</h3>
				<label for="sort-member-select">
					{{ t('cospend', 'Members order') }}
				</label>
				<select id="sort-member-select" v-model="memberOrder" @change="onMemberOrderChange">
					<option value="name">
						{{ t('cospend', 'Name') }}
					</option>
					<option value="balance">
						{{ t('cospend', 'Balance') }}
					</option>
				</select>
			</NcAppSettingsSection>
			<NcAppSettingsSection
				id="cumulative-balance"
				:name="t('cospend', 'Cumulative balances')"
				:title="t('cospend', 'Cumulative balances')"
				class="app-settings-section">
				<NcCheckboxRadioSwitch
					:checked.sync="showMyBalance"
					@update:checked="onCheckboxChange($event, 'showMyBalance')">
					{{ t('cospend', 'Show cumulative balances') }}
				</NcCheckboxRadioSwitch>
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'Cumulative balances view customisation') }}
				</h3>
				<!-- Section Ordering Control
					Allows users to choose whether Balance Summary or People sections appear first
					in the cumulative balances view for better user experience customization
				-->
				<label for="display-order-select">
					{{ t('cospend', 'First Section: ') }}
				</label>
				<select id="display-order-select" v-model="displayOrder" @change="onDisplayOrderChange">
					<option value="summary">
						{{ t('cospend', 'Balance Summary') }}
					</option>
					<option value="people">
						{{ t('cospend', 'Balances by People') }}
					</option>
				</select>
				<br>
				<!-- Project Details Visibility Control
					Controls whether project breakdowns are expanded or collapsed by default
					Uses dropdown instead of checkbox for better reliability
				-->
				<label for="hide-projects-select">
					{{ t('cospend', 'Project details: ') }}
				</label>
				<select id="hide-projects-select" v-model="hideProjectsVisibility" @change="onHideProjectsChange">
					<option value="show">
						{{ t('cospend', 'Expand by default') }}
					</option>
					<option value="hide">
						{{ t('cospend', 'Collapse by default') }}
					</option>
				</select>
				<br>
				<!-- Sorting Controls for Cumulative Balances
					These settings control how items are ordered in the balance view
					Settings persist in global state and are used by CrossProjectBalanceView
				-->
				<h4 class="sort-section-title">
					{{ t('cospend', 'Sort Options') }}
				</h4>
				<!-- Person Balances Sorting Options -->
				<label for="person-sort-by-select">
					{{ t('cospend', 'Sort Balances by People by: ') }}
				</label>
				<select id="person-sort-by-select" v-model="personSortBy" @change="onPersonSortChange">
					<option value="balance">
						{{ t('cospend', 'Balance Amount') }}
					</option>
					<option value="name">
						{{ t('cospend', 'Name') }}
					</option>
				</select>
				<select v-model="personSortOrder" @change="onPersonSortChange">
					<option value="desc">
						{{ personSortBy === 'balance' ? t('cospend', 'High to Low') : t('cospend', 'Z to A') }}
					</option>
					<option value="asc">
						{{ personSortBy === 'balance' ? t('cospend', 'Low to High') : t('cospend', 'A to Z') }}
					</option>
				</select>
				<br>
				<!-- Summary sort options -->
				<label for="summary-sort-by-select">
					{{ t('cospend', 'Sort Summary by: ') }}
				</label>
				<select id="summary-sort-by-select" v-model="summarySortBy" @change="onSummarySortChange">
					<option value="amount">
						{{ t('cospend', 'Amount') }}
					</option>
					<option value="currency">
						{{ t('cospend', 'Currency') }}
					</option>
				</select>
				<select v-model="summarySortOrder" @change="onSummarySortChange">
					<option value="desc">
						{{ summarySortBy === 'amount' ? t('cospend', 'High to Low') : t('cospend', 'Z to A') }}
					</option>
					<option value="asc">
						{{ summarySortBy === 'amount' ? t('cospend', 'Low to High') : t('cospend', 'A to Z') }}
					</option>
				</select>
			</NcAppSettingsSection>
			<NcAppSettingsSection
				id="misc"
				:name="t('cospend', 'Misc')"
				:title="t('cospend', 'Misc')"
				class="app-settings-section">
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'Maximum decimal precision to show in balances') }}
				</h3>
				<label for="precision">
					{{ t('cospend', 'Maximum precision') }}
				</label>
				<input id="precision"
					v-model.number="maxPrecision"
					type="number"
					min="2"
					max="10"
					step="1"
					@input="onMaxPrecisionChange">
				<h3 class="app-settings-section__hint">
					{{ t('cospend', 'Do you want to see and choose time in bill dates?') }}
				</h3>
				<NcCheckboxRadioSwitch
					:checked.sync="useTime"
					@update:checked="onCheckboxChange($event, 'useTime')">
					{{ t('cospend', 'Use time in dates') }}
				</NcCheckboxRadioSwitch>
			</NcAppSettingsSection>
		</NcAppSettingsDialog>
	</div>
</template>

<script>
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import FileImportIcon from 'vue-material-design-icons/FileImport.vue'

import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcAppSettingsDialog from '@nextcloud/vue/dist/Components/NcAppSettingsDialog.js'
import NcAppSettingsSection from '@nextcloud/vue/dist/Components/NcAppSettingsSection.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

import { subscribe, unsubscribe, emit } from '@nextcloud/event-bus'
import { getFilePickerBuilder, FilePickerType, showSuccess } from '@nextcloud/dialogs'
import cospend from '../state.js'
import { importCospendProject, importSWProject } from '../utils.js'

export default {
	name: 'CospendSettingsDialog',

	components: {
		NcAppSettingsDialog,
		NcAppSettingsSection,
		NcButton,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		FileImportIcon,
		OpenInNewIcon,
	},

	data() {
		return {
			showSettings: false,
			outputDir: cospend.outputDirectory || '/',
			pageIsPublic: cospend.pageIsPublic,
			sortOrder: cospend.sortOrder || 'name',
			memberOrder: cospend.memberOrder || 'name',
			maxPrecision: cospend.maxPrecision || 2,
			useTime: cospend.useTime ?? true,
			showMyBalance: cospend.showMyBalance ?? false,
			// Cross-project balance display settings:
			// Convert boolean showSummaryFirst to dropdown-friendly string value
			displayOrder: cospend.showSummaryFirst ? 'summary' : 'people',
			// Store the actual boolean value for direct cospend state updates
			hideProjectsByDefault: cospend.hideProjectsByDefault ?? true,
			// Convert boolean hideProjectsByDefault to dropdown-friendly string value
			// This allows for intuitive dropdown selection (show/hide) instead of boolean checkbox
			hideProjectsVisibility: (cospend.hideProjectsByDefault ?? true) ? 'hide' : 'show',
			// Cumulative balance sort settings
			personSortBy: cospend.personSortBy || 'balance',
			personSortOrder: cospend.personSortOrder || 'desc',
			summarySortBy: cospend.summarySortBy || 'amount',
			summarySortOrder: cospend.summarySortOrder || 'desc',
			importingProject: false,
			importingSWProject: false,
			cospendVersion: OC.getCapabilities()?.cospend?.version || '??',
		}
	},

	computed: {
	},

	watch: {
		// Sync dropdown values with global cospend state changes
		// This ensures UI stays in sync if settings are changed elsewhere
		'cospend.showSummaryFirst'(newValue) {
			this.displayOrder = newValue ? 'summary' : 'people'
		},
		'cospend.hideProjectsByDefault'(newValue) {
			this.hideProjectsVisibility = newValue ? 'hide' : 'show'
		},
	},

	mounted() {
		subscribe('show-settings', this.handleShowSettings)
	},

	beforeDestroy() {
		unsubscribe('show-settings', this.handleShowSettings)
	},

	methods: {
		handleShowSettings() {
			this.showSettings = true
			// Refresh values from cospend state when dialog opens
			this.displayOrder = cospend.showSummaryFirst ? 'summary' : 'people'
			this.hideProjectsVisibility = cospend.hideProjectsByDefault ? 'hide' : 'show'
		},

		onOutputDirClick() {
			const picker = getFilePickerBuilder(t('cospend', 'Choose where to write output files (stats, settlement, export)'))
				.setMultiSelect(false)
				.setType(FilePickerType.Choose)
				.addMimeTypeFilter('httpd/unix-directory')
				.allowDirectories()
				.startAt(this.outputDir)
				.build()
			picker.pick()
				.then(async (path) => {
					if (path === '') {
						path = '/'
					}
					path = path.replace(/^\/+/, '/')
					this.outputDir = path
					emit('save-option', { key: 'outputDirectory', value: path })
				})
		},
		onSortOrderChange() {
			emit('save-option', { key: 'sortOrder', value: this.sortOrder })
			cospend.sortOrder = this.sortOrder
		},
		onMemberOrderChange() {
			emit('save-option', { key: 'memberOrder', value: this.memberOrder })
			cospend.memberOrder = this.memberOrder
		},
		onMaxPrecisionChange() {
			emit('save-option', { key: 'maxPrecision', value: this.maxPrecision })
			cospend.maxPrecision = this.maxPrecision
			this.$emit('update-max-precision')
		},
		onCheckboxChange(checked, key) {
			emit('save-option', { key, value: checked ? '1' : '0' })
			cospend[key] = checked
		},
		onDisplayOrderChange() {
			// Convert dropdown selection to boolean for cospend state
			// 'summary' = true (show summary first), 'people' = false (show people first)
			const showSummaryFirst = this.displayOrder === 'summary'
			emit('save-option', { key: 'showSummaryFirst', value: showSummaryFirst ? '1' : '0' })
			cospend.showSummaryFirst = showSummaryFirst
		},
		onHideProjectsChange() {
			// Convert dropdown selection to boolean for project details visibility
			// 'hide' = true (hide by default), 'show' = false (show by default)
			const hideProjectsByDefault = this.hideProjectsVisibility === 'hide'
			// Update local data property to keep in sync
			this.hideProjectsByDefault = hideProjectsByDefault
			// Persist setting to server (as string '1'/'0' for database storage)
			emit('save-option', { key: 'hideProjectsByDefault', value: hideProjectsByDefault ? '1' : '0' })
			// Update global cospend state immediately for reactive UI updates
			cospend.hideProjectsByDefault = hideProjectsByDefault
		},
		onPersonSortChange() {
			// Save person sort settings
			emit('save-option', { key: 'personSortBy', value: this.personSortBy })
			emit('save-option', { key: 'personSortOrder', value: this.personSortOrder })
			// Update global cospend state
			cospend.personSortBy = this.personSortBy
			cospend.personSortOrder = this.personSortOrder
		},
		onSummarySortChange() {
			// Save summary sort settings
			emit('save-option', { key: 'summarySortBy', value: this.summarySortBy })
			emit('save-option', { key: 'summarySortOrder', value: this.summarySortOrder })
			// Update global cospend state
			cospend.summarySortBy = this.summarySortBy
			cospend.summarySortOrder = this.summarySortOrder
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
				this.importingSWProject = true
			}, (data) => {
				emit('project-imported', data)
				showSuccess(t('cospend', 'Project imported'))
			}, () => {
				this.importingSWProject = false
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.success {
	color: var(--color-success);
}

.wrapper {
	overflow-y: scroll;
	padding: 20px;
}

button {
	display: inline-flex;
	align-items: center;
	.label {
		padding-left: 8px;
	}
}

a.external {
	display: flex;
	align-items: center;
	> * {
		margin: 0 2px 0 2px;
	}
}

.app-settings-section {
	margin-bottom: 80px;
	&.last {
		margin-bottom: 0;
	}
	&__title {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}
	&__hint {
		color: var(--color-text-lighter);
		padding: 8px 0;
	}
	&__input {
		width: 100%;
	}

	.shortcut-description {
		width: calc(100% - 160px);
	}

	.oneLine {
		display: flex;
		align-items: center;
		> * {
			margin: 0 4px 0 4px;
		}
	}

	.sort-section-title {
		margin-top: 16px;
		margin-bottom: 8px;
		font-weight: 600;
		font-size: 1.1em;
	}

	select {
		margin: 4px 8px 8px 0;
		padding: 4px 8px;
		border: 1px solid var(--color-border);
		border-radius: var(--border-radius);
		background: var(--color-main-background);
		min-width: 120px;

		&:focus {
			border-color: var(--color-primary);
			outline: none;
		}

		@media (max-width: 768px) {
			margin: 4px 0 8px 0;
			width: 100%;
			min-width: unset;
			max-width: 280px;
		}
	}

	label {
		display: inline-block;
		margin: 8px 8px 4px 0;
		font-weight: 500;

		@media (max-width: 768px) {
			margin: 8px 0 4px 0;
			display: block;
		}
	}
}

::v-deep .cospend-settings-dialog .modal-container {
	display: flex !important;
}
</style>
