import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

export default new Vuex.Store({
	state: {
		labels: {},
		project: null,
		issues: [],
		users: [],
		milestones: []
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
		SET_ISSUE(state, payload) {
			console.log(state.issues, payload);
			let copy = JSON.parse(JSON.stringify(state.issues));
			copy[payload.index] = payload.value;
			state.issues = copy;
		},
	},
	actions: {},
});
