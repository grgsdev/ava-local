<script setup lang="ts">
import { ref, watch, onUnmounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(false);
let timeout: number | null | undefined = null;


watch(() => page.props.flash?.message, (newMessage) => {
    // Usamos o 'optional chaining' (?.) para segurança, caso 'flash' seja nulo.
    if (newMessage) {
        show.value = true;
        if (timeout) {
            clearTimeout(timeout);
        }

        timeout = setTimeout(() => {
            show.value = false;
        }, 5000);
    } else {
        show.value = false;
    }
}, {
    immediate: true
});
const bgColorClass = computed(() => {
    const type = page.props.flash?.type || 'success';
    return {
        success: 'bg-green-500 hover:bg-green-600',
        error: 'bg-red-600 hover:bg-red-700',
    }[type];
});
onUnmounted(() => {
    if (timeout) {
        clearTimeout(timeout);
    }
});
</script>

<template>
    <Transition name="fade">
        <div v-if="show"
            class="fixed z-50 flex items-center justify-between max-w-sm px-4 py-3 text-white rounded-md shadow-lg top-5 right-5"
            :class="bgColorClass">

            <span>{{ page.props.flash.message }}</span>

            <button @click="show = false"
                class="p-1 -mr-1 rounded-full focus:outline-none focus:ring-2 focus:ring-white" :class="bgColorClass">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </Transition>
</template>
