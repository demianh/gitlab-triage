import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

export default new Vuex.Store({
	state: {
		labels: {},
		project: null,
	},
	mutations: {
		SET_LABELS(state, value) {
			state.labels = value
		},
		SET_PROJECT(state, value) {
			state.project = value
		},
	},
	actions: {},
});
