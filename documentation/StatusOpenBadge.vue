<script setup lang="ts">
import { computed } from 'vue';
import type { StatusOpenDetails } from '@/types';

// Tipamos a prop para receber o objeto de detalhes inteiro
const props = defineProps<{
    details: StatusOpenDetails;
}>();

// Propriedade computada para definir a cor do badge
const badgeClass = computed(() => {
    if (!props.details) return 'bg-gray-100 text-gray-800';

    return props.details.status_open === 'Aberto'
        ? 'bg-green-100 text-green-800'
        : 'bg-red-100 text-red-800';
});
</script>

<template>
    <div v-if="details" class="flex items-center gap-2">
        <span :class="badgeClass" class="px-2.5 py-0.5 text-sm font-semibold rounded-full">
            {{ details.status_open }}
        </span>
        <span class="text-sm text-muted-foreground">{{ details.message }}</span>
    </div>
</template>
