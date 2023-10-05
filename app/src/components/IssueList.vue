<template>
	<div class="issue-list">
		<div class="row">
			<div class="col" v-for="user in users" v-if="hasIssues(user.id)">
				<div class="username">
					<img :src="user.avatar_url" class="avatar"/>
					{{user.name}}
					<span v-if="weightPerPerson[user.id]" class="badge badge-primary float-right">{{weightPerPerson[user.id]}}</span>
				</div>
				<div class="list">
					<div v-for="(issue, index) in issues" class="issue" v-if="issue.assignees.length > 0 && issue.assignees[0].id === user.id">
						<span v-if="project">
							<a :href="project.web_url + '/issues/' + issue.iid" target="_blank">
								#{{issue.iid}}
							</a>
						</span>
						<a @click="unassignIssue(index)" class="float-right">&#10005;</a>
						<span class="badge badge-secondary float-right mr-1">{{issue.weight}}</span>
						{{issue.title}}
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script lang="ts">
	import {Component, Vue} from 'vue-property-decorator';
	import IIssue from "@/interfaces/IIssue";
	import axios from 'axios';

	@Component({})
	export default class IssueList extends Vue {
		get users(): any[] {
			return this.$store.state.users;
		}

		get issues(): IIssue[] {
			return this.$store.state.issues;
		}

		get project() {
			return this.$store.state.project;
		}

		get weightPerPerson(): {[key: number]: number} {
			return this.$store.getters.weightPerPerson;
		}

		public hasIssues(userId: number): boolean {
			return this.issues.some(issue => issue.assignees.length > 0 && issue.assignees.some(assignee => assignee.id === userId));
		}

		public unassignIssue(index: number) {
			let selectedIssue = this.issues[index];
			let postdata: any = {
				milestone: 0,
				user: 0
			};

			// remove Prio Labels when unassigning
			if (selectedIssue.labels) {
				postdata.labels = selectedIssue.labels.filter(label => label !== 'Prio 1' && label !== 'Prio 2' && label !== 'Prio 3');
			}

			axios.post(this.$store.state.API_PATH + '/assign_issue/' + selectedIssue.iid, postdata).then((response) => {
				this.$store.commit('SET_ISSUE', { index: index, value: response.data })
			})
		}
	}
</script>

<style scoped lang="less">
	.avatar {
		width: 16px;
		border-radius: 10px;
	}

	.username {
		font-weight: bold;
		margin-bottom: 10px;
	}

	.issue {
		border: 1px solid #eee;
		border-radius: 5px;
		padding: 4px 10px;
		margin-bottom: 3px;
	}
</style>
