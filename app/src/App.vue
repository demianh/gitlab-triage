<template>
	<div id="app">
		<div class="container">
			<div class="nav-menue">
				<div class="row">
					<div class="col">
						<button @click="previousIssue()" class="btn btn-outline-secondary">Zurück</button>
					</div>
					<div class="col text-center">
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
									class="btn mb-1"
									:disabled="issueState === 'closed'"
									:class="{'btn-primary': selectedIssueAssignee === user.id, 'btn-outline-primary': selectedIssueAssignee !== user.id}"
									@click="toggleAssignIssue(user.id)"
							>
								<img :src="user.avatar_url" class="avatar"/>
								<span class="username">
									{{user.name}}
									<span v-if="weightPerPerson[user.id]" class="badge badge-light">{{weightPerPerson[user.id]}}</span>
								</span>
							</button>
						&nbsp;
						</span>
						<button
								type="button"
								class="btn mb-1"
								:disabled="issueState === 'closed'"
								:class="{'btn-secondary': selectedIssueAssignee === 0, 'btn-outline-secondary': selectedIssueAssignee !== 0}"
								@click="assignIssue(0)"
						>
							Unassigned
						</button>
						&nbsp;
						<button v-if="issueState === 'opened'" class="btn btn-outline-danger mb-1" @click="closeIssue()">Close</button>
						<button v-if="issueState === 'closed'" class="btn btn-outline-success mb-1" @click="reopenIssue()">Reopen</button>
					</div>
				</div>
			</div>
			<issue-viewer :issue="selectedIssue"></issue-viewer>
			<div class="text-muted text-center">
				Issue {{selectedIndex + 1}} von {{issues.length}} &middot;
				<a @click="reloadIssue()">Reload Issue</a> &middot;
				Sort by:
				<a @click="sortById()">Age</a>&nbsp;
				<a @click="sortByWeight()">Weight</a>&nbsp;
				<a @click="sortRandom()">Random</a>&nbsp;
			</div>
		</div>
	</div>
</template>

<script lang="ts">
	import {Component, Vue} from 'vue-property-decorator';
	import IssueViewer from "@/components/IssueViewer.vue";
	import axios from 'axios';
	import IIssue from "@/interfaces/IIssue";

	@Component({
		components: {
			IssueViewer,
		},
	})
	export default class App extends Vue {
		public selectedMilestone: number = 0;
		public selectedIndex: number = 0;
		public keyHandler: any = null;

		public sortInverse: boolean = false;

		// TODO: make configurable or more generic
		public API_PATH: string = 'http://localhost/projects/gitlab-triage/backend/api.php';

		get issues(): IIssue[] {
			return this.$store.state.issues;
		}

		get users(): any[] {
			return this.$store.state.users;
		}

		get milestones(): any[] {
			return this.$store.state.milestones;
		}

		get selectedIssue(): IIssue {
			return this.issues[this.selectedIndex];
		}

		get issueState(): string {
			if (this.selectedIssue) {
				return this.selectedIssue.state
			} else {
				return '';
			}
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

		get weightPerPerson(): {[key: number]: number} {
			let weights: {[key: number]: number} = {};
			if (this.issues) {
				this.issues.forEach((issue) => {
					if (issue.weight) {
						if (issue.assignees.length > 0) {
							let userid = issue.assignees[0].id;
							if (weights[userid]) {
								weights[userid] += issue.weight
							} else {
								weights[userid] = issue.weight
							}
						}
					}
				})
			}
			return weights;
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
				this.$store.commit('SET_ISSUES', response.data);
			})
		}

		public loadUsers() {
			axios.get(this.API_PATH + '/users').then((response) => {
				this.$store.commit('SET_USERS', response.data);
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
				this.$store.commit('SET_MILESTONES', response.data);
				if (this.milestones.length) {
					this.selectedMilestone = this.milestones[0].id;
				}
			})
		}

		public closeIssue() {
			let index = this.selectedIndex;
			axios.post(this.API_PATH + '/close_issue/' + this.selectedIssue.iid).then((response) => {
				this.$store.commit('SET_ISSUE', { index: index, value: response.data })
			})
		}

		public reopenIssue(issueId: number) {
			let index = this.selectedIndex;
			axios.post(this.API_PATH + '/reopen_issue/' + this.selectedIssue.iid).then((response) => {
				this.$store.commit('SET_ISSUE', { index: index, value: response.data })
			})
		}

		public toggleAssignIssue(userId: number) {
			if (this.selectedIssueAssignee === userId) {
				this.assignIssue(0);
			} else {
				this.assignIssue(userId);
			}
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
				this.$store.commit('SET_ISSUE', { index: index, value: response.data })
			})
		}

		public reloadIssue() {
			let index = this.selectedIndex;
			axios.get(this.API_PATH + '/issue/' + this.selectedIssue.iid).then((response) => {
				this.$store.commit('SET_ISSUE', { index: index, value: response.data })
			});
			return false;
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

		public sortById() {
			let sorted = this.issues.sort((a: IIssue, b: IIssue) => {
				if (this.sortInverse) {
					return a.id - b.id;
				} else {
					return b.id - a.id;
				}
			});
			this.$store.commit('SET_ISSUES', sorted);
			this.sortInverse = !this.sortInverse;
		}

		public sortByWeight() {
			let sorted = this.issues.sort((a: IIssue, b: IIssue) => {
				if (this.sortInverse) {
					return a.weight - b.weight;
				} else {
					return b.weight - a.weight;
				}
			});
			this.$store.commit('SET_ISSUES', sorted);
			this.sortInverse = !this.sortInverse;
		}

		public sortRandom() {
			this.$store.commit('SET_ISSUES', this.shuffleArray(this.issues));
		}

		private shuffleArray(array: any[]) {
			let currentIndex = array.length, temporaryValue, randomIndex;

			// While there remain elements to shuffle...
			while (0 !== currentIndex) {

				// Pick a remaining element...
				randomIndex = Math.floor(Math.random() * currentIndex);
				currentIndex -= 1;

				// And swap it with the current element.
				temporaryValue = array[currentIndex];
				Vue.set(array, currentIndex, array[randomIndex]);
				Vue.set(array, randomIndex, temporaryValue);
			}
			return array;
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

	// overwrite default bootstrap styles
	a:not([href]):not([tabindex]) {
		color: #007bff;
		cursor: pointer;
	}
	a:not([href]):not([tabindex]):focus,
	a:not([href]):not([tabindex]):hover {
		color: #0056b3;
		text-decoration: underline;
	}
</style>

<style lang="less" scoped>
	.nav-menue {
		padding: 20px;

		@media (max-width: 767px) {
			padding: 10px;
		}
	}

	@media (max-width: 767px) {
		.username {
			display: none;
		}
	}

	.avatar {
		width: 16px;
		border-radius: 10px;
	}
</style>
