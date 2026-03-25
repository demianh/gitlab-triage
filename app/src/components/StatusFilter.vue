<template>
	<div class="d-inline-flex align-items-center">
		<span class="d-none d-sm-inline mx-2">
			Filter:
		</span>
		<select :value="modelValue" @input="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)" class="ml-2 mr-4 form-control">
			<option value="all">All</option>
			<option value="next">NEXT</option>
			<option value="ready">READY & NEXT</option>
		</select>
	</div>
</template>

<script setup lang="ts">
	defineProps({
		modelValue: {
			type: String,
			default: 'all'
		}
	});
	defineEmits(['update:modelValue']);
</script>

<style scoped lang="less">
</style>
