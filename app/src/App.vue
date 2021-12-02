<template>
	<div id="app">
		<div v-if="loading" class="text-center mt-5">
			Loading Data from Gitlab ...
		</div>
		<div v-else>
			<div v-if="view === 'issues'" class="container issues">
				<header class="nav-menue">
					<div class="row">
						<div class="col">
							<button @click="previousIssue()" class="btn btn-outline-secondary">Zurück</button>
						</div>
						<div class="col text-center">
							<span class="d-none d-sm-inline">
								Target Milestone:
							</span>
							<select v-model="selectedMilestone">
								<option v-for="milestone in milestones" :value="milestone.id">{{milestone.title}}</option>
							</select>
							&nbsp;<button @click="view = 'list'" class="btn btn-outline-secondary">Show List</button>
						</div>
						<div class="col text-right">
							<button @click="nextIssue()" class="btn btn-outline-secondary">Weiter</button>
						</div>
					</div>
					<div class="row">
						<div class="col text-center pt-4">
						<span v-for="user in users">
							<button
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
				</header>
				<main>
					<transition :name="animation" v-if="selectedIssue">
						<issue-viewer :issue="selectedIssue" :key="selectedIssue.id"></issue-viewer>
					</transition>
				</main>
				<footer class="text-muted text-center pb-2">
					Issue {{selectedIndex + 1}} von {{issues.length}} &middot;
					<a @click="loadIssues()">Reload All</a> &middot;
					<a @click="reloadIssue()">Reload Issue</a> &middot;
					<a @click="view = 'print'">Print</a> &middot;
					Sort by:
					<a @click="sortById()">Age</a>&nbsp;
					<a @click="sortByWeight()">Weight</a>&nbsp;
					<a @click="sortRandom()">Random</a>&nbsp;
				</footer>
			</div>
			<div v-if="view === 'print'" class="container-fluid">
				<main>
					<print-view></print-view>
				</main>
				<footer class="text-muted text-center d-print-none">
					<a @click="view = 'issues'">Show List</a>&nbsp;
					Sort by:
					<a @click="sortById()">Age</a>&nbsp;
					<a @click="sortByWeight()">Weight</a>&nbsp;
				</footer>
			</div>
			<div v-if="view === 'list'" class="container-fluid">
				<header class="nav-menue">
					<div class="row">
						<div class="col text-center">
							Target Milestone:
							<select v-model="selectedMilestone">
								<option v-for="milestone in milestones" :value="milestone.id">{{milestone.title}}</option>
							</select>
							&nbsp;<button @click="view = 'issues'" class="btn btn-outline-secondary">Show Issues</button>
						</div>
					</div>
				</header>
				<main>
					<issue-list></issue-list>
				</main>
				<footer class="text-muted text-center">
					Sort by:
					<a @click="sortById()">Age</a>&nbsp;
					<a @click="sortByWeight()">Weight</a>&nbsp;
				</footer>
			</div>
		</div>
	</div>
</template>

<script lang="ts">
	import {Component, Vue, Watch} from 'vue-property-decorator';
	import IssueViewer from "@/components/IssueViewer.vue";
	import axios from 'axios';
	import IIssue from "@/interfaces/IIssue";
	import IssueList from "@/components/IssueList.vue";
	import IUser from "@/interfaces/IUser";
	import PrintView from "@/components/PrintView.vue";

	@Component({
		components: {
			PrintView,
			IssueViewer,
			IssueList,
		},
	})
	export default class App extends Vue {
		public keyHandler: any = null;

		public sortInverse: boolean = false;

		public view: string = 'issues';

		public loading: boolean = false;

		public animation: string = 'forward';

		get API_PATH(): string {
			return this.$store.state.API_PATH;
		}

		get issues(): IIssue[] {
			return this.$store.state.issues;
		}

		get users(): IUser[] {
			return this.$store.state.users;
		}

		get milestones(): any[] {
			return this.$store.state.milestones;
		}

		get selectedMilestone(): number {
			return this.$store.state.selectedMilestone;
		}

		set selectedMilestone(milestone: number) {
			this.$store.commit('SET_SELECTED_MILESTONE', milestone);
		}

		get selectedIndex(): number {
			return this.$store.state.selectedIssueIndex;
		}

		set selectedIndex(index: number) {
			this.$store.commit('SET_SELECTED_ISSUE_INDEX', index);
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
			return this.$store.getters.weightPerPerson;
		}

		public previousIssue() {
			this.animation = 'back';
			if (this.selectedIndex > 0) {
				this.selectedIndex--;
			}
		}

		public nextIssue() {
			this.animation = 'forward';
			if (this.selectedIndex < this.issues.length - 1) {
				this.selectedIndex++;
			}
		}

		public async mounted() {
			this.loading = true;
			this.loadUsers();
			this.loadLabels();
			this.loadProject();
			await this.loadMilestones();

			// load issues after milestones
			await this.loadIssues();
			this.loading = false;
		}

		public async loadIssues() {
			let url = this.API_PATH + '/issues';
			if (this.milestones.length) {
				let selected = this.milestones.filter(milestone => milestone.id == this.selectedMilestone);
				if (selected.length > 0) {
					url += '?milestone=' + encodeURIComponent(selected[0].title)
				}
			}
			let response = await axios.get(url);
			this.$store.commit('SET_ISSUES', response.data);
		}

		public async loadUsers() {
			let response = await axios.get(this.API_PATH + '/users');
			let filtered = response.data.filter((user: IUser) => {
				return user.state === 'active';
			});
			this.$store.commit('SET_USERS', filtered);
		}

		public async loadLabels() {
			let response = await axios.get(this.API_PATH + '/labels');
			this.$store.commit('SET_LABELS', response.data)
		}

		public async loadProject() {
			let response = await axios.get(this.API_PATH + '/project');
			this.$store.commit('SET_PROJECT', response.data)
		}

		public async loadMilestones() {
			let response = await axios.get(this.API_PATH + '/milestones');
			this.$store.commit('SET_MILESTONES', response.data);
			if (this.milestones.length) {
				this.selectedMilestone = this.milestones[0].id;
			}
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

		@Watch('selectedMilestone')
		public watchMilestone() {
			this.loadIssues();
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

	.issues {
		display: flex;
		flex-direction: column;
		min-height: 100vh;
		main {
			flex: 1;
			overflow: hidden;
			max-width: 1070px;
		}
	}

	.forward-enter-active,
	.forward-leave-active,
	.back-enter-active,
	.back-leave-active {
		transition: opacity 0.4s, transform 0.4s;
		transform: translateX(0);
	}
	.back-enter,
	.forward-leave-to {
		position: fixed;
		opacity: 0;
		transform: translateX(-1100px);
	}
	.back-leave-to,
	.forward-enter {
		position: fixed;
		opacity: 0;
		transform: translateX(1100px);
	}
</style>
