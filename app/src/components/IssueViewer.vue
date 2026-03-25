<template>
	<div class="issue-viewer">
		<div v-if="issue">
			<div>
				<span class="next-label-container">
					<span class="next next--active" v-if="hasNextStatus">
						{{issue.status.name}}
					</span>
					<span class="next next--inactive" v-else-if="issue.status" :style="{borderColor: issue.status.color, color: issue.status.color}">
						{{issue.status.name}}
					</span>
				</span>
				<h3>
					{{issue.title}}
					<span v-if="issue.state === 'closed'" class="text-danger">(closed)</span>
				</h3>
			</div>
			<div>
				<span v-if="project">
					<a :href="project.web_url + '/-/issues/' + issue.iid" target="_blank">
						Open #{{issue.iid}} in Gitlab
					</a>
				</span>
				<span v-if="issue.labels.nodes">
					<span v-for="label in issue.labels.nodes" :key="label.title">
						<span class="badge badge-pill badge-dark" :style="{backgroundColor: label.color ? label.color : 'inherit'}">{{label.title}}</span>&nbsp;
					</span>
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
			<div v-if="issue.notes && issue.notes.count > 0">
				<hr>
				<a v-if="project" :href="project.web_url + '/issues/' + issue.iid" target="_blank">
					{{issue.notes.count}} Comments
				</a>
			</div>

		</div>
	</div>
</template>

<script setup lang="ts">
	import { computed } from 'vue';
	import VueMarkdown from 'vue-markdown-render'
	import axios from 'axios';
	import IIssue from "@/interfaces/IIssue";
	import useStore from '@/useStore';

	const props = defineProps<{
		issue: IIssue
	}>();

	const API_PATH = computed(() => useStore.state.API_PATH);
	const labels = computed(() => useStore.state.labels);
	const project = computed(() => useStore.state.project);
	const milestones = computed(() => useStore.state.milestones);
	const selectedIndex = computed(() => useStore.state.selectedIssueIndex);
	const selectedMilestone = computed(() => useStore.state.selectedMilestone);

	const selectedMilestoneName = computed(() => {
		return milestones.value.find(milestone => milestone.id === selectedMilestone.value)?.title || '';
	});

	const hasNextStatus = computed(() => {
		if (!props.issue || !props.issue.status) {
			return false;
		}
		return props.issue.status.name === 'Next Release';
	});

	const description = computed(() => {
		if (props.issue && project.value) {
			if (props.issue.description) {
				let domain = new URL(project.value.web_url).origin;
				return props.issue.description.replaceAll('(/uploads/', '(' + domain + '/-/project/' + project.value.id + '/uploads/');
			}
		}
		return '';
	});

	function setMilestone() {
		let postdata = {
			milestone: selectedMilestone.value,
		};

		axios.post(API_PATH.value + '/assign_issue/' + props.issue.iid, postdata).then((response) => {
			useStore.setIssueByIid(props.issue.iid, response.data);
		})
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
	.next {
		border: 1px solid #6c757d;
		font-weight: bold;
		font-size: 12px;
		padding: 4px 8px;
		border-radius: 5px;
		color: #6c757d;

		&.next--active {
			border: 1px solid #28a745;
			background-color: #28a745;
			color: white;
		}
	}

	.next-label-container {
		float: right;
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
