<script setup lang="ts">
import NavLink from '@/Components/NavLink.vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import type { FunctionalComponent } from 'vue';
import { computed, ref } from 'vue';

// --- DEFINIÇÃO DE TIPOS CORRIGIDA ---
interface MenuItem {
    name: string;
    icon: FunctionalComponent;
    route?: string;
    active?: string; // CORREÇÃO 1: 'active' agora é opcional, pois um item pai pode não ter.
    permission?: string;
    companyPermission?: string;
    masterOrCompanyPermission?: string;
    masterOnly?: boolean;
    children?: MenuItem[];
}

// CORREÇÃO 2: Usamos withDefaults para garantir que 'level' sempre tenha um valor.
const props = withDefaults(defineProps<{
    item: MenuItem;
    sidebarOpen: boolean;
    level?: number;
}>(), {
    level: 0,
});

// --- LÓGICA DO COMPONENTE ---
const page = usePage<PageProps>();

const can = (permission: string | undefined): boolean => {
    if (props.item.masterOnly) {
        return page.props.auth.user.is_master ?? false;
    }

    if (props.item.companyPermission) {
        const companyCan = (page.props.auth as typeof page.props.auth & {
            company_can?: string[];
        }).company_can;

        return companyCan?.includes(props.item.companyPermission) ?? false;
    }
    if (props.item.masterOrCompanyPermission) {
        if (page.props.auth.user.is_master ?? false) {
            return true;
        }

        const companyCan = (page.props.auth as typeof page.props.auth & {
            company_can?: string[];
        }).company_can;

        return companyCan?.includes(props.item.masterOrCompanyPermission) ?? false;
    }
    if (!permission) return true;
    return page.props.auth.user.can.includes(permission);
};

const isSubmenuOpen = ref(false);

const isActive = computed(() => {
    // CORREÇÃO 3: Verificamos se 'active' existe antes de usá-lo.
    if (props.item.active) {
        return route().current(props.item.active);
    }
    if (props.item.children) {
        return props.item.children.some(
            (child) => child.active && route().current(child.active),
        );
    }
    return false;
});

if (isActive.value && props.item.children) {
    isSubmenuOpen.value = true;
}
</script>


<template>
    <div v-if="can(item.permission)">
        <TooltipProvider v-if="!item.children && item.route" :delay-duration="100">
            <Tooltip>
                <TooltipTrigger as-child>
                    <NavLink
                        :href="route(item.route)"
                        :active="isActive"
                        :class="{ 'justify-center': !sidebarOpen }"
                    >
                        <component :is="item.icon" :size="20" />
                        <span v-if="sidebarOpen" class="ml-3">{{
                            $t(item.name)
                        }}</span>
                    </NavLink>
                </TooltipTrigger>
                <TooltipContent v-if="!sidebarOpen" side="right">
                    <p>{{ $t(item.name) }}</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>

        <div v-else>
            <TooltipProvider :delay-duration="100">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <button
                            @click="isSubmenuOpen = !isSubmenuOpen"
                            :class="[
                                'group flex w-full items-center rounded-md p-2 text-sm font-medium',
                                isActive
                                    ? 'bg-accent text-accent-foreground'
                                    : 'text-muted-foreground hover:bg-accent/50',
                                { 'justify-center': !sidebarOpen },
                            ]"
                        >
                            <component :is="item.icon" :size="20" />
                            <span
                                v-if="sidebarOpen"
                                class="flex-1 ml-3 text-left"
                                >{{ $t(item.name) }}</span
                            >
                            <ChevronDown
                                v-if="sidebarOpen"
                                :class="[
                                    'h-4 w-4 transition-transform',
                                    { 'rotate-180': isSubmenuOpen },
                                ]"
                            />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent v-if="!sidebarOpen" side="right">
                        <p>{{ $t(item.name) }}</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <div v-show="isSubmenuOpen" class="pt-2 pl-4 space-y-2">
                <SidebarItem
                    v-for="child in item.children"
                    :key="child.name"
                    :item="child"
                    :sidebarOpen="sidebarOpen"
                    :level="level + 1"
                />
            </div>
        </div>
    </div>
</template>
