<template>
	<div class="print-view">
		<div class="issue-print-card" v-for="(issue, index) in issues">
			<div>
				<span class="issue-number">
					#{{issue.iid}}
				</span>
				<span class="badge badge-secondary float-right mr-1">{{issue.weight}}</span>
			</div>
			<div class="issue-title">
				{{issue.title}}
			</div>
			<div>
				<span v-for="label in issue.labels" class="badge badge-light mr-1 issue-label">{{label}}</span>
			</div>
		</div>
	</div>
</template>

<script lang="ts">
	import {Component, Vue} from 'vue-property-decorator';
	import IIssue from "@/interfaces/IIssue";
	import axios from 'axios';
	import useStore from '@/useStore';

	@Component({})
	export default class PrintView extends Vue {
		get issues(): IIssue[] {
			return useStore.state.issues;
		}

		get project() {
			return useStore.state.project;
		}
	}
</script>

<style scoped lang="less">
.print-view {
	display: table;
	padding: 10px 0;
}
.issue-print-card {
	border: 1px solid #eee;
	margin-bottom: 2px;
	margin-right: 2px;
	padding: 15px;
	width: 200px;
	overflow: hidden;
	text-overflow: ellipsis;
	display: inline-flex;
	flex-direction: column;
	min-height: 155px;
	vertical-align: top;
}
.issue-number {
	color: #ccc;
}
.issue-title {
	flex: 1;
}
.issue-label {
	margin-top: 5px;
}
</style>
