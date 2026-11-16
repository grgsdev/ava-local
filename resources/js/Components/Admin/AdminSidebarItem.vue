<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { NavItem } from '@/types';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';

const props = defineProps<{
    item: NavItem;
    level?: number;
}>();

const depth = props.level || 0;
const isOpen = ref(false);

// --- CORREÇÃO DA URL ---
// Função auxiliar para decidir se usa route() ou link direto
const resolveHref = (href?: string) => {
    if (!href) return '#';

    // Se contiver '.', assumimos que é uma rota nomeada do Laravel (ex: admin.categories.index)
    if (href.includes('.')) {
        // @ts-ignore: O route() é global no Inertia/Ziggy
        return route(href);
    }

    return href;
};

// Verifica se o item atual está ativo (para pintar de azul)
const isItemActive = () => {
    if (!props.item.href) return false;

    if (props.item.href.includes('.')) {
        // @ts-ignore
        return route().current(props.item.href + '*');
    }

    return usePage().url.startsWith(props.item.href);
};
</script>

<template>
    <Collapsible v-if="item.children && item.children.length" v-model:open="isOpen" class="w-full">
        <CollapsibleTrigger
            class="flex items-center justify-between w-full p-2 text-sm font-medium transition-colors rounded-md text-muted-foreground hover:text-foreground hover:bg-muted/50"
            :class="[depth > 0 ? 'pl-' + (depth * 4 + 2) : '']">
            <div class="flex items-center gap-2">
                <component :is="item.icon" v-if="item.icon" class="w-4 h-4" />
                <span>{{ item.title }}</span>
            </div>
            <ChevronRight class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-90': isOpen }" />
        </CollapsibleTrigger>

        <CollapsibleContent class="mt-1 space-y-1">
            <AdminSidebarItem v-for="(child, index) in item.children" :key="index" :item="child" :level="depth + 1" />
        </CollapsibleContent>
    </Collapsible>

    <Link v-else :href="resolveHref(item.href)"
        class="flex items-center gap-2 p-2 text-sm font-medium transition-colors rounded-md text-muted-foreground hover:text-foreground hover:bg-muted/50"
        :class="[
            depth > 0 ? 'pl-' + (depth * 4 + 2) : '',
            isItemActive() ? 'bg-primary/10 text-primary font-semibold' : ''
        ]">
    <component :is="item.icon" v-if="item.icon" class="w-4 h-4" />
    <span>{{ item.title }}</span>
    </Link>
</template>
