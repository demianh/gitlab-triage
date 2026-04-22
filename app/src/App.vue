
<template>
	<div id="app">
		<div v-if="loading" class="text-center mt-5">
			Loading Data from Gitlab ...
		</div>
		<div v-else>
			<div v-if="view === 'issues'" class="container issues">
				<header class="nav-menue">
					<div class="row">
						<div class="col-1">
							<button @click="previousIssue()" class="btn btn-outline-secondary">Zurück</button>
						</div>
						<div class="col-12 col-sm-10 text-center form-inline justify-content-center">
							<span class="d-none d-sm-inline mx-2">
								Target Milestone:
							</span>
							<select v-model="selectedMilestone" class="form-control mr-2">
								<option v-for="milestone in milestones" :key="milestone.id" :value="milestone.id">{{milestone.title}}</option>
							</select>
							<status-filter v-model="selectedFilter"></status-filter>
							&nbsp;<button @click="view = 'list'" class="btn btn-outline-secondary">Show List</button>
							&nbsp;<button @click="view = 'search'" class="btn btn-outline-secondary">🔍</button>
						</div>
						<div class="col-1 text-right">
							<button @click="nextIssue()" class="btn btn-outline-secondary">Weiter</button>
						</div>
					</div>
					<div class="row">
						<div class="col text-center pt-4">
						<span v-for="user in users" :key="user.id">
							<button
									type="button"
									class="btn mb-1"
									:disabled="issueState === 'closed'"
									:class="{'btn-primary': selectedIssueAssignee === user.id, 'btn-outline-primary': selectedIssueAssignee !== user.id}"
									@click="toggleAssignIssue(user.id)"
							>
								<img :src="user.avatar_url" class="avatar mr-1"/>
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
					<transition :name="animation">
						<issue-viewer v-if="selectedIssue" :issue="selectedIssue" :key="selectedIssue.id"></issue-viewer>
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
					<print-view :issues="issues"></print-view>
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
						<div class="col text-center form-inline justify-content-center">
							Target Milestone:
							<select v-model="selectedMilestone" class="form-control mx-2">
								<option v-for="milestone in milestones" :key="milestone.id" :value="milestone.id">{{milestone.title}}</option>
							</select>
							&nbsp;<button @click="view = 'issues'" class="btn btn-outline-secondary">Show Issues</button>
						</div>
					</div>
				</header>
				<main>
					<issue-list :issues="issues"></issue-list>
				</main>
				<footer class="text-muted text-center">
					Sort by:
					<a @click="sortById()">Age</a>&nbsp;
					<a @click="sortByWeight()">Weight</a>&nbsp;
				</footer>
			</div>
			<div v-if="view === 'search'" class="container">
				<header class="nav-menue">
					<div class="row">
						<div class="col text-center form-inline justify-content-center">
							<status-filter v-model="selectedFilter"></status-filter>
							<button @click="view = 'issues'" class="btn btn-outline-secondary">Show Issues</button>
						</div>
					</div>
				</header>
				<main>
					<div class="issue-search">
						<input type="search" class="issue-search__input" placeholder="Search" v-model="search" ref="searchInput" autofocus/>
					</div>
					<div class="issue-list">
						<div v-for="issue in filteredIssues" :key="issue.iid">
							<div class="issue-list__item">
								<div class="row">
									<div class="col-11">
										<h5>
											<a @click="goToIssue(issue.iid)">
												#{{issue.iid}}
											</a>
											{{issue.title}}
										</h5>
									</div>
									<div class="col text-right">
										<a :href="issue.web_url" target="_blank">Gitlab</a>
									</div>
								</div>
								<div>
									<span v-if="issue.weight" class="badge badge-light mr-2">🕑 {{issue.weight}}</span>
									<span v-if="issue.assignees.nodes.length > 0" class="mr-2">
										<span v-for="assignee in issue.assignees.nodes" :key="assignee.id">
											<img :src="prepareAvatarUrl(assignee.avatarUrl)" class="avatar"/>
											{{assignee.name}}
										</span>
									</span>
									<span v-if="issue.milestone" class="mr-2">Milestone {{issue.milestone.title}}</span>
									<span v-for="label in issue.labels.nodes" :key="label.title">
										<span class="badge badge-pill badge-dark" :style="{backgroundColor: label.color ? label.color : 'inherit'}">{{label.title}}</span>&nbsp;
									</span>
								</div>
							</div>
						</div>
					</div>
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

<script setup lang="ts">
	import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
	import StatusFilter from "@/components/StatusFilter.vue";
	import IssueViewer from "@/components/IssueViewer.vue";
	import axios from 'axios';
	import IIssue from "@/interfaces/IIssue";
	import IssueList from "@/components/IssueList.vue";
	import IUser from "@/interfaces/IUser";
	import PrintView from "@/components/PrintView.vue";
	import useStore from '@/useStore'

	const searchInput = ref<HTMLInputElement | null>(null);

	const sortInverse = ref(false);
	const view = ref('issues');
	const loading = ref(false);
	const animation = ref('forward');
	const search = ref('');
	const selectedFilter = ref('next');

	const API_PATH = computed(() => useStore.state.API_PATH);

	const issues = computed(() => {
		// filter for issues with the status "Next Release"
		if (selectedFilter.value === 'next') {
			return useStore.state.issues.filter(issue => issue.status?.name === 'Next Release');
		}
		// filter for issues with the status "Ready" and "Next Release"
		if (selectedFilter.value === 'ready') {
			return useStore.state.issues.filter(issue => issue.status?.name === 'Ready' || issue.status?.name === 'Next Release');
		}
		return useStore.state.issues;
	});

	const users = computed(() => useStore.state.users);
	const milestones = computed(() => useStore.state.milestones);
	const project = computed(() => useStore.state.project);

	const selectedMilestone = computed({
		get: () => useStore.state.selectedMilestone,
		set: (value) => useStore.setSelectedMilestone(value)
	});

	const selectedIndex = computed({
		get: () => useStore.state.selectedIssueIndex,
		set: (value) => useStore.setSelectedIssueIndex(value)
	});

	const selectedIssue = computed(() => issues.value[selectedIndex.value]);

	const issueState = computed(() => selectedIssue.value ? selectedIssue.value.state : '');

	const selectedIssueAssignee = computed(() => {
		if (issues.value[selectedIndex.value]) {
			if (issues.value[selectedIndex.value].assignees && issues.value[selectedIndex.value].assignees.nodes.length > 0) {
				return idFromGid(issues.value[selectedIndex.value].assignees.nodes[0].id);
			} else {
				return 0;
			}
		}
		return null;
	});

	const weightPerPerson = computed(() => useStore.weightPerPerson.value);

	function previousIssue() {
		animation.value = 'back';
		if (selectedIndex.value > 0) {
			selectedIndex.value--;
		}
	}

	function nextIssue() {
		animation.value = 'forward';
		if (selectedIndex.value < issues.value.length - 1) {
			selectedIndex.value++;
		}
	}

	function idFromGid(gid: string): number {
		// parse id from GraphQL node id string, example: "gid://gitlab/User/42"
		let parts = gid.split('/');
		if (parts.length > 0) {
			let id = parseInt(parts[parts.length - 1]);
			if (!isNaN(id)) {
				return id;
			}
		}
		return 0;
	}

	function prepareAvatarUrl(url: string): string {
		if (url.startsWith('/')) {
			// relative url, add project domain
			if (project.value) {
				let domain = new URL(project.value.web_url).origin;
				return domain + url;
			}
		}
		return url;
	}

	function goToIssue(issueId: number) {
		selectedIndex.value = issues.value.findIndex(issue => issue.iid === issueId);
		view.value = 'issues';
		window.scrollTo(0, 0);
	}

	async function loadIssues() {
		let url = API_PATH.value + '/graphql/issues';
		if (milestones.value.length) {
			let selected = milestones.value.filter(milestone => milestone.id == selectedMilestone.value);
			if (selected.length > 0) {
				url += '?milestone=' + encodeURIComponent(selected[0].title)
			}
		}
		let response = await axios.get(url);
		useStore.setIssues(response.data);
	}

	async function loadUsers() {
		let response = await axios.get(API_PATH.value + '/users');
		let filtered = response.data.filter((user: IUser) => {
			return user.state === 'active';
		});
		useStore.setUsers(filtered);
	}

	async function loadLabels() {
		let response = await axios.get(API_PATH.value + '/labels');
		useStore.setLabels(response.data);
	}

	async function loadProject() {
		let response = await axios.get(API_PATH.value + '/project');
		useStore.setProject(response.data);
	}

	async function loadMilestones() {
		let response = await axios.get(API_PATH.value + '/milestones');
		useStore.setMilestones(response.data);
		if (milestones.value.length) {
			selectedMilestone.value = milestones.value[0].id;
		}
	}

	function closeIssue() {
		axios.post(API_PATH.value + '/close_issue/' + selectedIssue.value.iid).then((response) => {
			useStore.setIssueByIid(selectedIssue.value.iid, response.data);
		})
	}

	function reopenIssue() {
		axios.post(API_PATH.value + '/reopen_issue/' + selectedIssue.value.iid).then((response) => {
			useStore.setIssueByIid(selectedIssue.value.iid, response.data);
		})
	}

	function toggleAssignIssue(userId: number) {
		if (selectedIssueAssignee.value === userId) {
			assignIssue(0);
		} else {
			assignIssue(userId);
		}
	}

	function assignIssue(userId: number) {
		let postdata = {
			milestone: selectedMilestone.value,
			user: userId
		};
		if (userId == 0) {
			postdata.milestone = 0;
		}

		axios.post(API_PATH.value + '/assign_issue/' + selectedIssue.value.iid, postdata).then((response) => {
			useStore.setIssueByIid(selectedIssue.value.iid, response.data);
		})
	}

	function reloadIssue() {
		axios.get(API_PATH.value + '/graphql/issue/' + selectedIssue.value.iid).then((response) => {
			useStore.setIssueByIid(selectedIssue.value.iid, response.data);
		});
		return false;
	}

	watch(selectedMilestone, () => {
		loadIssues();
	});

	watch(selectedFilter, () => {
		selectedIndex.value = 0;
	});

	watch(view, () => {
		if (view.value === 'search') {
			nextTick(() => {
				if (searchInput.value) {
					searchInput.value.focus();
				}
			});
		}
	});

	const keyHandler = (e: KeyboardEvent) => {
		if (e.key === 'ArrowLeft') {
			previousIssue();
		}
		if (e.key === 'ArrowRight') {
			nextIssue();
		}
	};

	onMounted(async () => {
		loading.value = true;
		loadUsers();
		loadLabels();
		loadProject();
		await loadMilestones();

		// load issues after milestones
		await loadIssues();
		loading.value = false;

		window.addEventListener('keyup', keyHandler);
	});

	onBeforeUnmount(() => {
		window.removeEventListener('keyup', keyHandler);
	});

	function sortById() {
		let sorted = [...issues.value].sort((a: IIssue, b: IIssue) => {
			if (sortInverse.value) {
				return a.iid - b.iid;
			} else {
				return b.iid - a.iid;
			}
		});
		useStore.setIssues(sorted);
		sortInverse.value = !sortInverse.value;
	}

	function sortByWeight() {
		let sorted = [...issues.value].sort((a: IIssue, b: IIssue) => {
			if (sortInverse.value) {
				return (a.weight || 0) - (b.weight || 0);
			} else {
				return (b.weight || 0) - (a.weight || 0);
			}
		});
		useStore.setIssues(sorted);
		sortInverse.value = !sortInverse.value;
	}

	function sortRandom() {
		useStore.setIssues(shuffleArray([...issues.value]));
	}

	function shuffleArray(array: any[]) {
		let currentIndex = array.length, temporaryValue, randomIndex;
		while (0 !== currentIndex) {
			randomIndex = Math.floor(Math.random() * currentIndex);
			currentIndex -= 1;
			temporaryValue = array[currentIndex];
			array[currentIndex] = array[randomIndex];
			array[randomIndex] = temporaryValue;
		}
		return array;
	}

	const filteredIssues = computed(() => {
		if (search.value === '') {
			return issues.value;
		}
		let searchWords = search.value.split(' ');
		return issues.value.filter(issue => {
			return searchWords.every(word => {
				return issue.title.toLowerCase().includes(word.toLowerCase());
			});
		});
	});
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
		position: relative;
		top: -2px;
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
	.back-enter-from,
	.forward-leave-to {
		position: fixed;
		opacity: 0;
		transform: translateX(-1100px);
	}
	.back-leave-to,
	.forward-enter-from {
		position: fixed;
		opacity: 0;
		transform: translateX(1100px);
	}

	.issue-search__input {
		width: 100%;
		padding: 10px;
		font-size: 1.2em;
		border-radius: 6px;
		border: 1px solid #ccc;
		margin-bottom: 20px;
	}

	.issue-list__item {
		box-shadow: 1px 1px 6px rgba(0, 0, 0, 0.2);
		border-radius: 6px;
		padding: 10px;
		margin-bottom: 10px;
	}
</style>
