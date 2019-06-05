<template>
	<div class="issue-viewer">
		<div v-if="issue">

			<h3>
				{{issue.title}}
				<span v-if="issue.state === 'closed'" class="text-danger">(closed)</span>
			</h3>
			<div>
				<span v-if="project">
					<a :href="project.web_url + '/issues/' + issue.iid" target="_blank">
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
			</div>
			<div class="text-muted">
				Weight: <b>{{issue.weight}}</b>
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
	import IIssue from "@/interfaces/IIssue";

	@Component({
		components: {
			'vue-markdown': VueMarkdown
		},
	})
	export default class IssueViewer extends Vue {
		@Prop() private issue!: IIssue;

		get labels() {
			return this.$store.state.labels;
		}

		get project() {
			return this.$store.state.project;
		}

		get description() {
			if (this.issue && this.project) {
				if (this.issue.description) {
					return this.issue.description.replace('(/uploads/', '(' + this.project.web_url + '/uploads/');
				}
			}
			return '';
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
			padding: 20px;
			margin: 0;
			margin-bottom: 20px;
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
	}

	img {
		max-width: 100%;
	}
</style>
