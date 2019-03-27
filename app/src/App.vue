<template>
	<div id="app">
		<div class="container">
			<div class="nav-menue">
				<div class="row">
					<div class="col">
						<button @click="previousIssue()" class="btn btn-outline-secondary">Zurück</button>
					</div>
					<div class="col">
						Target Milestone: <select v-model="selectedMilestone">
						<option v-for="milestone in milestones" :value="milestone.id">{{milestone.title}}</option>
					</select>
					</div>
					<div class="col text-right">
						<button @click="nextIssue()" class="btn btn-outline-secondary">Weiter</button>
					</div>
				</div>
				<div class="row">
					<div class="col text-center pt-4">
						<span v-for="user in users">
							<!-- TODO: make user filter configurable -->
							<button
									v-if="user.username !== 'ghost' && user.username !== 'internalreadonly' && user.state === 'active'"
									type="button"
									class="btn"
									:class="{'btn-primary': selectedIssueAssignee === user.id, 'btn-outline-primary': selectedIssueAssignee !== user.id}"
									@click="assignIssue(user.id)"
							>
								<img :src="user.avatar_url" class="avatar"/>
								{{user.name}}
							</button>
						&nbsp;
						</span>
						<button
								type="button"
								class="btn"
								:class="{'btn-secondary': selectedIssueAssignee === 0, 'btn-outline-secondary': selectedIssueAssignee !== 0}"
								@click="assignIssue(0)"
						>
							Unassigned
						</button>
					</div>
				</div>
			</div>
			<issue-viewer :issue="selectedIssue"></issue-viewer>
			<div class="text-muted text-center">
				Issue {{selectedIndex + 1}} von {{issues.length}}
			</div>
		</div>
	</div>
</template>

<script lang="ts">
	import {Component, Vue} from 'vue-property-decorator';
	import IssueViewer from "@/components/IssueViewer.vue";
	import axios from 'axios';

	@Component({
		components: {
			IssueViewer,
		},
	})
	export default class App extends Vue {
		public issues: any[] = [];
		public users: any[] = [];
		public milestones: any[] = [];

		public selectedMilestone: number = 0;
		public selectedIndex: number = 0;
		public keyHandler: any = null;

		// TODO: make configureable or more generic
		public API_PATH: string = 'http://localhost/projects/gitlab-triage/backend/api.php';

		get selectedIssue(): any {
			return this.issues[this.selectedIndex];
		}

		get selectedIssueAssignee(): number|null {
			if (this.issues[this.selectedIndex]) {
				if (this.issues[this.selectedIndex].assignees.length > 0) {
					return this.issues[this.selectedIndex].assignees[0].id;
				} else {
					return 0;
				}
			}
			return null;
		}

		public previousIssue() {
			if (this.selectedIndex > 0) {
				this.selectedIndex--;
			}
		}

		public nextIssue() {
			if (this.selectedIndex < this.issues.length - 1) {
				this.selectedIndex++;
			}
		}

		public mounted() {
			this.loadIssues();
			this.loadUsers();
			this.loadMilestones();
			this.loadLabels();
			this.loadProject();
		}

		public loadIssues() {
			axios.get(this.API_PATH + '/issues').then((response) => {
				this.issues = response.data;
			})
		}

		public loadUsers() {
			axios.get(this.API_PATH + '/users').then((response) => {
				this.users = response.data;
			})
		}

		public loadLabels() {
			axios.get(this.API_PATH + '/labels').then((response) => {
				this.$store.commit('SET_LABELS', response.data)
			})
		}

		public loadProject() {
			axios.get(this.API_PATH + '/project').then((response) => {
				this.$store.commit('SET_PROJECT', response.data)
			})
		}

		public loadMilestones() {
			axios.get(this.API_PATH + '/milestones').then((response) => {
				this.milestones = response.data;
				if (this.milestones.length) {
					this.selectedMilestone = this.milestones[0].id;
				}
			})
		}

		public assignIssue(userId: number) {
			let postdata = {
				milestone: this.selectedMilestone,
				user: userId
			};
			if (userId == 0) {
				postdata.milestone = 0;
			}

			let index = this.selectedIndex;
			axios.post(this.API_PATH + '/assign_issue/' + this.selectedIssue.iid, postdata).then((response) => {
				this.$set(this.issues, index, response.data)
			})
		}

		// keyboard navigation
		public created() {
			this.keyHandler = (e: KeyboardEvent) => {
				if (e.key === 'ArrowLeft') {
					this.previousIssue();
				}
				if (e.key === 'ArrowRight') {
					this.nextIssue();
				}
			};
			window.addEventListener('keyup', this.keyHandler);
		}

		public beforeDestroy() {
			window.removeEventListener('keyup', this.keyHandler);
		}

	}
</script>

<style lang="less">
	#app {
		font-family: 'Avenir', Helvetica, Arial, sans-serif;
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
		color: #2c3e50;
	}
</style>

<style lang="less" scoped>
	.nav-menue {
		padding: 20px;
	}

	.avatar {
		width: 16px;
		border-radius: 10px;
	}
</style>
