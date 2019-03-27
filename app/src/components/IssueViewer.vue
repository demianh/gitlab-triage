<template>
	<div class="issue-viewer">
		<div v-if="issue">

			<h3>{{issue.title}}</h3>
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
			<div class="issue-description">{{issue.description}}</div>

		</div>
	</div>
</template>

<script lang="ts">
	import {Component, Prop, Vue} from 'vue-property-decorator';

	@Component
	export default class IssueViewer extends Vue {
		@Prop() private issue!: any;

		get labels() {
			return this.$store.state.labels;
		}

		get project() {
			return this.$store.state.project;
		}
	}
</script>

<style scoped lang="less">
	.issue-viewer {
		margin: 20px;
		box-shadow: 1px 1px 18px rgba(0, 0, 0, 0.2);
		border-radius: 10px;
		padding: 40px;
	}
	.issue-description {
		white-space: pre-line;
	}
</style>
