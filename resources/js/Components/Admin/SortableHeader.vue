<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    label: string;
    name: string; // O nome da coluna no banco (ex: 'title', 'published_at')
}>();

// Pega os parâmetros atuais da URL
const currentSort = computed(() => {
    const params = new URLSearchParams(window.location.search);
    return params.get('sort');
});

const toggleSort = () => {
    const params = new URLSearchParams(window.location.search);
    const current = params.get('sort');

    let nextSort = props.name;

    // Ciclo: Nada -> ASC (name) -> DESC (-name) -> Nada
    if (current === props.name) {
        nextSort = `-${props.name}`;
    } else if (current === `-${props.name}`) {
        nextSort = ''; // Remove a ordenação
        params.delete('sort');
    }

    if (nextSort) {
        params.set('sort', nextSort);
    }

    // Mantém os filtros existentes e navega
    router.get(`${window.location.pathname}?${params.toString()}`, {}, {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <Button variant="ghost" size="sm" class="-ml-3 h-8 data-[state=open]:bg-accent hover:bg-muted/50"
        @click="toggleSort">
        <span>{{ label }}</span>

        <ArrowDown v-if="currentSort === name" class="w-3 h-3 ml-2" />
        <ArrowUp v-else-if="currentSort === `-${name}`" class="w-3 h-3 ml-2" />
        <ArrowUpDown v-else class="w-3 h-3 ml-2 text-muted-foreground/50" />
    </Button>
</template>
