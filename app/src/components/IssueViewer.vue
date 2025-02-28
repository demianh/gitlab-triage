<template>
	<div class="issue-viewer">
		<div v-if="issue">

			<h3>
				{{issue.title}}
				<span v-if="issue.state === 'closed'" class="text-danger">(closed)</span>
			</h3>
			<div>
				<span v-if="project">
					<a :href="project.web_url + '/-/issues/' + issue.iid" target="_blank">
						Open #{{issue.iid}} in Gitlab
					</a>
				</span>
				<span v-for="label in issue.labels">
					<span class="badge badge-pill badge-dark" :style="{backgroundColor: labels[label] ? labels[label].color : 'inherit'}">{{label}}</span>&nbsp;
				</span>
			</div>
			<div class="text-muted">
				Milestone:
				<b v-if="issue.milestone">{{issue.milestone.title}}</b>
				<span v-if="!issue.milestone">
					<a @click="setMilestone()">Set Milestone {{selectedMilestoneName}}</a>
				</span>
			</div>
			<div class="text-muted">
				Weight: <b v-if="issue.weight !== null">🕑 {{issue.weight}}</b>
			</div>
			<hr>
			<div class="issue-description">
				<vue-markdown :source="description"></vue-markdown>
			</div>
			<div v-if="issue.user_notes_count > 0">
				<hr>
				<a :href="project.web_url + '/issues/' + issue.iid" target="_blank">
					{{issue.user_notes_count}} Comments
				</a>
			</div>

		</div>
	</div>
</template>

<script lang="ts">
	import {Component, Prop, Vue} from 'vue-property-decorator';
	import VueMarkdown from 'vue-markdown'
	import axios from 'axios';
	import IIssue from "@/interfaces/IIssue";
	import useStore from '@/useStore';

	@Component({
		components: {
			'vue-markdown': VueMarkdown
		},
	})
	export default class IssueViewer extends Vue {
		@Prop() private issue!: IIssue;

		get API_PATH(): string {
			return useStore.state.API_PATH;
		}

		get labels() {
			return useStore.state.labels;
		}

		get project() {
			return useStore.state.project;
		}

		get milestones(): any[] {
			return useStore.state.milestones;
		}

		get selectedIndex(): number {
			return useStore.state.selectedIssueIndex;
		}

		get selectedMilestone(): number {
			return useStore.state.selectedMilestone;
		}

		get selectedMilestoneName(): string {
			return this.milestones.find(milestone => milestone.id === this.selectedMilestone)?.title || '';
		}

		get description() {
			if (this.issue && this.project) {
				if (this.issue.description) {
					let domain = new URL(this.project.web_url).origin;
					return this.issue.description.replaceAll('(/uploads/', '(' + domain + '/-/project/' + this.project.id + '/uploads/');
				}
			}
			return '';
		}

		public setMilestone() {
			let postdata = {
				milestone: this.selectedMilestone,
			};

			let index = this.selectedIndex;
			axios.post(this.API_PATH + '/assign_issue/' + this.issue.iid, postdata).then((response) => {
				useStore.setIssue(index, response.data);
			})
		}
	}
</script>

<style scoped lang="less">
	.issue-viewer {
		margin: 20px;
		box-shadow: 1px 1px 18px rgba(0, 0, 0, 0.2);
		border-radius: 10px;
		padding: 40px;

		@media (max-width: 767px) {
			margin: 0;
			margin-bottom: 20px;
			box-shadow: none;
			padding: 20px 0;
		}
	}
</style>
<style lang="less">
	.issue-description {
		pre > code {
			display: block;
			border: 1px solid #e5e5e5;
			padding: 8px 12px;
		}
		img {
			max-width: 100%;
		}
	}
</style>
