<template>
	<div class="d-inline-flex align-items-center">
		<span class="d-none d-sm-inline mx-2">
			Filter:
		</span>
		<select :value="value" @input="$emit('input', $event.target.value)" class="ml-2 mr-4 form-control">
			<option value="all">All</option>
			<option value="next">NEXT</option>
			<option value="ready">READY & NEXT</option>
		</select>
	</div>
</template>

<script lang="ts">
	import {Component, Prop, Vue} from 'vue-property-decorator';

	@Component({})
	export default class StatusFilter extends Vue {
		@Prop({ default: 'all' }) public value!: string;
	}
</script>

<style scoped lang="less">
</style>
