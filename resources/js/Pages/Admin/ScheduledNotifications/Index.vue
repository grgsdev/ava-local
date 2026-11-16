<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { TableCell, TableHead, TableRow } from "@/components/ui/table";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import { ChevronDown, ChevronUp, PlusCircle, XCircle } from "lucide-vue-next";
import type { Paginated, ScheduledNotifications } from '@/types';
import { useFilters } from "@/Composables/useFilters";
import { computed } from "vue";
import { Input } from "@/components/ui/input";

interface QueryParams {
    sort?: string;
    filter?: { search?: string, title?: string, is_active?: string };
    per_page?: string;
}

const props = defineProps<{
    notifications: Paginated<ScheduledNotifications>;
    queryParams: QueryParams;
}>();

const initialFilters = {
    title: props.queryParams?.filter?.title ?? "",
    is_active: props.queryParams?.filter?.is_active ?? "",
    Search: props.queryParams?.filter?.search ?? "",
    per_page: props.queryParams?.per_page ?? "15",
};
const { filters, clearFilters, sort, clearFilter } = useFilters(
    props,
    initialFilters,
    "admin.scheduled-notifications.index"
);
const currentSorts = computed(() => props.queryParams?.sort?.split(",") || []);
const isSorted = (field: string) => {
    const asc = currentSorts.value.includes(field);
    const desc = currentSorts.value.includes(`-${field}`);
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};

function formatUTCDate(dateString: string): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    const yyyy = date.getUTCFullYear();
    const mm = String(date.getUTCMonth() + 1).padStart(2, '0');
    const dd = String(date.getUTCDate()).padStart(2, '0');
    return `${dd}/${mm}/${yyyy}`;
}
</script>

<template>

    <Head title="Notificações Agendadas" />
    <AuthenticatedLayout>
        <IndexPageLayout :data="notifications.meta">
            <template #title>Gerir Notificações Agendadas</template>
            <template #actions>
                <Link v-if="$page.props.auth.user.can.includes('create permissions')"
                    :href="route('admin.scheduled-notifications.create')">
                <Button class="text-card-foreground">
                    Agendar Notificação
                </Button>
                </Link>
            </template>

            <!-- <template #actions>
                <Button as-child>
                    <Link :href="route('admin.scheduled-notifications.create')">
                    <PlusCircle class="w-4 h-4 mr-2" /> Agendar Nova Notificação</Link>
                </Button>
            </template> -->
            <template #filters>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-7">
                    <!-- <div class="relative">
                        <Input v-model="filters.Search" placeholder="Buscar por nome" class="pr-8" />
                        <Button v-if="filters.Search" @click="clearFilter('Search')" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-1">
                            <XCircle class="w-4 h-4" />
                        </Button>
                    </div> -->
                    <div class="relative">
                        <Input v-model="filters.title" placeholder="Buscar por título" class="pr-8" />
                        <Button v-if="filters.title" @click="clearFilter('title')" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-1">
                            <XCircle class="w-4 h-4" />
                        </Button>
                    </div>
                    <div class="relative">
                        <Select v-model="filters.is_active">
                            <SelectTrigger>
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">Inativo</SelectItem>
                                <SelectItem value="1">Ativo</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button v-if="filters.is_active" @click="filters.is_active = ''" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-8">
                            <XCircle class="w-4 h-4" />
                        </Button>

                    </div>

                    <div class="relative">

                        <Select v-model="filters.per_page">
                            <SelectTrigger class="w-24">
                                <SelectValue placeholder="Qtd" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="10">10</SelectItem>
                                <SelectItem value="15">15</SelectItem>
                                <SelectItem value="30">25</SelectItem>
                                <SelectItem value="50">50</SelectItem>
                                <SelectItem value="100">100</SelectItem>

                            </SelectContent>
                        </Select>

                    </div>
                    <div class="relative">
                        <TooltipProvider :delay-duration="100">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button @click="clearFilters" variant="outline"
                                        class="absolute w-8 h-8 text-muted-foreground top-1.5 right-8">
                                        <XCircle class="w-4 h-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Limpar todos os filtros</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>

                </div>
            </template>
            <template #table-header>
                <TableRow>
                    <TableHead>
                        <button @click="sort('title')" class="flex items-center gap-2 font-semibold">
                            Título
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('title') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('title') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead>Público Alvo</TableHead>
                    <TableHead>Canais</TableHead>
                    <TableHead>Período de Envio</TableHead>
                    <TableHead>
                        <button @click="sort('is_active')" class="flex items-center gap-2 font-semibold">
                            Status
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('is_active') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('is_active') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead class="text-right">Ações</TableHead>
                </TableRow>
            </template>
            <template #table-body>
                <TableRow v-for="notification in notifications.data" :key="notification.id">
                    <TableCell class="font-medium">{{ notification.title }}</TableCell>
                    <TableCell>
                        <div class="flex flex-wrap gap-1">
                            <Badge v-for="role in notification.target_roles" :key="role" variant="secondary">{{ $t(role)
                            }}
                            </Badge>
                        </div>
                    </TableCell>
                    <TableCell>
                        <div class="flex flex-wrap gap-1">
                            <Badge v-for="channel in notification.delivery_channels" :key="channel">{{ channel }}
                            </Badge>
                        </div>
                    </TableCell>
                    <TableCell>{{ notification.start_date.short }} por {{ notification.period_in_days }}
                        dia(s)
                    </TableCell>
                    <TableCell>
                        <Badge :variant="notification.is_active ? 'default' : 'destructive'">{{ notification.is_active ?
                            'Ativa' : 'Inativa' }}</Badge>
                    </TableCell>
                    <TableCell class="text-right">
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="route('admin.scheduled-notifications.edit', notification.id)">Editar
                            </Link>
                        </Button>
                    </TableCell>
                </TableRow>
            </template>
        </IndexPageLayout>
    </AuthenticatedLayout>
</template>
