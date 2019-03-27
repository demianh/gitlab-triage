import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

export default new Vuex.Store({
	state: {
		labels: {},
	},
	mutations: {
		SET_LABELS(state, value) {
			state.labels = value
		},
	},
	actions: {},
});
