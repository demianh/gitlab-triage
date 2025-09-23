// useStore.js
import { reactive, computed, UnwrapNestedRefs } from 'vue'
import IIssue from '@/interfaces/IIssue';
import IUser from '@/interfaces/IUser';

// State (reactive)
const state: UnwrapNestedRefs<{
	labels: any;
	project: any;
	issues: IIssue[];
	users: IUser[];
	milestones: any[];
	selectedMilestone: number;
	selectedIssueIndex: number;
	API_PATH: string
}> = reactive({
	labels: {},
	project: null,
	issues: [],
	users: [],
	milestones: [],
	selectedMilestone: 0,
	selectedIssueIndex: 0,
	API_PATH: process.env.VUE_APP_API_PATH || 'http://localhost/projects/gitlab-triage/backend/api.php',
})

// Mutations (plain functions that modify state)
function setLabels(value: any) {
	state.labels = value
}

function setProject(value: any) {
	state.project = value
}

function setIssues(value: any[]) {
	state.issues = value
}

function setUsers(value: any[]) {
	state.users = value
}

function setMilestones(value: any) {
	state.milestones = value
}

function setSelectedMilestone(value: number) {
	state.selectedMilestone = value
}

function setSelectedIssueIndex(value: number) {
	state.selectedIssueIndex = value
}

function setIssue(index: number, value: IIssue): void {
	// Because Vue 2 reactivity may not catch some changes in arrays/objects,
	// you might want to replace the entire array/object (like your original code).
	const copy = JSON.parse(JSON.stringify(state.issues))
	copy[index] = value
	state.issues = copy
}

function setIssueByIid(iid: number, value: IIssue): void {
	const index = state.issues.findIndex(issue => issue.iid === iid);
	if (index !== -1) {
		setIssue(index, value);
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

// Getter equivalent (use computed)
const weightPerPerson = computed(() => {
	const weights: {[userId: number]: number } = {}
	if (state.issues) {
		state.issues.forEach(issue => {
			if (issue.weight) {
				if (issue.assignees && issue.assignees.nodes && issue.assignees.nodes.length > 0) {
					issue.assignees.nodes.forEach(assignee => {
						const userId = idFromGid(assignee.id)
						let weight = issue.weight || 0;
						if (weights[userId]) {
							weights[userId] += weight
						} else {
							weights[userId] = weight
						}
					})
				}
			}
		})
	}
	return weights
})

// Export everything we need to interact with our “store”
export default {
	state,
	setLabels,
	setProject,
	setIssues,
	setUsers,
	setMilestones,
	setSelectedMilestone,
	setSelectedIssueIndex,
	setIssue,
	setIssueByIid,
	weightPerPerson
}
