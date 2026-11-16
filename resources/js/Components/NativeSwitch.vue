<script setup lang="ts">
defineProps<{
    modelValue?: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits(['update:modelValue']);

const onChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.checked);
};
</script>

<template>
    <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" class="sr-only peer" :checked="modelValue" :disabled="disabled" @change="onChange">

        <div
            class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 dark:peer-focus:ring-primary/30 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary">
        </div>

        <div v-if="disabled" class="absolute inset-0 rounded-full cursor-not-allowed bg-white/50 dark:bg-black/50">
        </div>
    </label>
</template>
