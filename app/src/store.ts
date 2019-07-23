import Vue from 'vue';
import Vuex from 'vuex';
import IIssue from "@/interfaces/IIssue";

Vue.use(Vuex);

export default new Vuex.Store({
	state: {
		labels: {},
		project: null,
		issues: [],
		users: [],
		milestones: [],
		selectedMilestone: 0,
		selectedIssueIndex: 0,
		// TODO: make configurable or more generic
		API_PATH: process.env.API_PATH || 'http://localhost/projects/gitlab-triage/backend/api.php',
	},
	mutations: {
		SET_LABELS(state, value) {
			state.labels = value
		},
		SET_PROJECT(state, value) {
			state.project = value
		},
		SET_ISSUES(state, value) {
			state.issues = value
		},
		SET_USERS(state, value) {
			state.users = value
		},
		SET_MILESTONES(state, value) {
			state.milestones = value
		},
		SET_SELECTED_MILESTONE(state, value) {
			state.selectedMilestone = value
		},
		SET_SELECTED_ISSUE_INDEX(state, value) {
			state.selectedIssueIndex = value
		},
		SET_ISSUE(state, payload) {
			let copy = JSON.parse(JSON.stringify(state.issues));
			copy[payload.index] = payload.value;
			state.issues = copy;
		},
	},
	actions: {},
	getters: {
		weightPerPerson: (state): {[key: number]: number} => {
			let weights: {[key: number]: number} = {};
			if (state.issues) {
				state.issues.forEach((issue: IIssue) => {
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
	}
});
