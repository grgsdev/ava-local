<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head } from "@inertiajs/vue3";
import { computed, ref } from "vue";

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";

// Ícones
import { ChevronUp, ChevronDown, XCircle } from "lucide-vue-next";

// Lógica reutilizável
import { useFilters } from "@/Composables/useFilters";
import { ActivityLog, Paginated, User } from "@/types";
import SearchableSelect from "@/Components/SearchableSelect.vue";
import { Label } from "@/components/ui/label";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import Datepicker from "@/Components/Datepicker.vue";


interface QueryParams {
    sort?: string;
    filter?: { causer_id?: number, subject_type?: string, event?: string, date_from?: string, date_to: string, search?: string };
    per_page?: string;
}
const props = defineProps<{
    activities: Paginated<ActivityLog>;
    users: User[],
    subjectTypes: string[],
    eventTypes: string[],
    queryParams: QueryParams;
}>();

// --- Lógica de Filtros e Ordenação ---
const initialFilters = {
    causer_id: props.queryParams?.filter?.causer_id ?? 0,
    subject_type: props.queryParams?.filter?.subject_type ?? "",
    event: props.queryParams?.filter?.event ?? "",
    date_from: props.queryParams?.filter?.date_from ?? "",
    date_to: props.queryParams?.filter?.date_to ?? "",
    Search: props.queryParams?.filter?.search ?? "",
    per_page: props.queryParams?.per_page ?? "15",
};

const { filters, clearFilters, sort, clearFilter } = useFilters(
    props,
    initialFilters,
    "system.activity-log.index"
);
const dateFromModel = computed<Date | null>({
    get() {
        // Lê a string do filtro e converte para Date para o componente
        return filters.date_from ? new Date(filters.date_from) : null;
    },
    set(newValue) {
        // Recebe um Date do componente e converte para string ISO para o filtro
        filters.date_from = newValue ? newValue.toISOString() : "";
    }
});

const dateToModel = computed<Date | null>({
    get() {
        return filters.date_to ? new Date(filters.date_to) : null;
    },
    set(newValue) {
        filters.date_to = newValue ? newValue.toISOString() : "";
    }
});
const currentSorts = computed(() => props.queryParams?.sort?.split(",") || []);
const isSorted = (field: string) => {
    const asc = currentSorts.value.includes(field);
    const desc = currentSorts.value.includes(`-${field}`);
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};


// --- Lógica do Modal ---
const isModalOpen = ref(false);
const selectedActivity = ref<ActivityLog | null>(null);
const showDetails = (activity: ActivityLog) => {
    if (activity.event === "updated" && activity.properties) {
        selectedActivity.value = activity;
        isModalOpen.value = true;
    }
};
const comparisonData = computed(() => {
    if (!selectedActivity.value) return [];
    const oldProps = selectedActivity.value.properties.old || {};
    const newProps = selectedActivity.value.properties.attributes || {};
    const allKeys = new Set([
        ...Object.keys(oldProps),
        ...Object.keys(newProps),
    ]);
    return Array.from(allKeys).map((key) => ({
        key: key,
        oldValue: oldProps[key] ?? "---",
        newValue: newProps[key] ?? "---",
        changed:
            JSON.stringify(oldProps[key]) !== JSON.stringify(newProps[key]),
    }));
});

// --- Funções Auxiliares para UI ---
const formatSubject = (subject: string | null | undefined): string => {
    if (!subject) return "N/A";
    return subject.split("\\").pop() ?? "N/A";
};
const translateEvent = (eventName: string): string => {
    return (
        { created: "Criado", updated: "Atualizado", deleted: "Apagado" }[
        eventName
        ] ?? eventName
    );
};
const eventBadgeClass = (eventName: string): string | undefined => {
    return {
        created:
            "bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300",
        updated:
            "bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300",
        deleted: "bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300",
    }[eventName];
};

</script>

<template>

    <Head title="Logs de Atividade" />

    <AuthenticatedLayout>
        <IndexPageLayout :data="activities.meta">
            <template #title>Logs de Atividade do Sistema</template>
            <template #filters>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-7">

                    <div class="relative">
                        <Select v-model="filters.event">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos os eventos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="event in eventTypes" :key="event" :value="event">{{
                                    translateEvent(event) }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button v-if="filters.event" @click="clearFilter('event')" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-8">
                            <XCircle class="w-4 h-4" />
                        </Button>

                    </div>
                    <div class="relative">
                        <Select v-model="filters.subject_type">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos os alvos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="type in subjectTypes" :key="type" :value="type">{{
                                    formatSubject(type) }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button v-if="filters.subject_type" @click="clearFilter('subject_type')" variant="ghost"
                            size="icon" class="absolute w-6 h-6 text-muted-foreground top-1.5 right-8">
                            <XCircle class="w-4 h-4" />
                        </Button>

                    </div>

                    <div class="relative">
                        <SearchableSelect v-model="filters.causer_id" :api-url="route('admin.users.selection')"
                            :initial-options="users.map(u => ({ id: u.id, name: u.name }))"
                            placeholder="Filtrar por Proprietário..." />
                        <Button v-if="filters.causer_id" @click="clearFilter('causer_id')" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-8">
                            <XCircle class="w-4 h-4" />
                        </Button>
                    </div>
                    <div class="relative">
                        <!-- <Input type="date" v-model="filters.date_from" /> -->
                        <Datepicker v-model="dateFromModel" locale="pt-BR" />
                        <Button v-show="filters.date_from" @click="dateFromModel = null" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-8">
                            <XCircle class="w-4 h-4" />
                        </Button>

                    </div>
                    <div class="relative">
                        <!-- <Input type="date" v-model="filters.date_to" placeholder="Início(dd/mm/aaaa)" /> -->
                        <Datepicker v-model="dateToModel" locale="pt-BR" />
                        <Button v-show="filters.date_to" @click="dateToModel = null" variant="ghost" size="icon"
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
                        <button @click="sort('created_at')" class="flex items-center gap-2 font-semibold">
                            Data
                            <div class="flex items-center">
                                <!-- <Badge v-if="sortOrder('created_at')" variant="secondary" class="h-5">{{
                                    sortOrder('created_at') }}</Badge> -->
                                <ChevronUp v-if="isSorted('created_at') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('created_at') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead><button @click="sort('causer_name')" class="flex items-center gap-2 font-semibold">
                            Realizado Por
                            <div class="flex items-center">
                                <!-- <Badge v-if="sortOrder('causer_name')" variant="secondary" class="h-5">{{
                                    sortOrder('causer_name') }}</Badge> -->
                                <ChevronUp v-if="isSorted('causer_name') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('causer_name') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button></TableHead>
                    <TableHead><button @click="sort('event')" class="flex items-center gap-2 font-semibold">
                            Evento
                            <div class="flex items-center">
                                <!-- <Badge v-if="sortOrder('event')" variant="secondary" class="h-5">{{ sortOrder('event')
                                    }}</Badge> -->
                                <ChevronUp v-if="isSorted('event') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('event') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button></TableHead>
                    <TableHead><button @click="sort('subject_type')" class="flex items-center gap-2 font-semibold">
                            Alvo
                            <div class="flex items-center">
                                <!-- <Badge v-if="sortOrder('subject_type')" variant="secondary" class="h-5">{{
                                    sortOrder('subject_type') }}</Badge> -->
                                <ChevronUp v-if="isSorted('subject_type') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('subject_type') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button></TableHead>
                    <TableHead><button @click="sort('description')" class="flex items-center gap-2 font-semibold">
                            Descrição
                            <div class="flex items-center">
                                <!-- <Badge v-if="sortOrder('description')" variant="secondary" class="h-5">{{
                                    sortOrder('description') }}</Badge> -->
                                <ChevronUp v-if="isSorted('description') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('description') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button></TableHead>



                </TableRow>
            </template>

            <template #table-body>
                <TableRow v-for="activity in activities.data" :key="activity.id" @click="showDetails(activity)" :class="{
                    'cursor-pointer hover:bg-accent':
                        activity.event === 'updated',
                }">
                    <TableCell>{{
                        activity.created_at?.short_with_time
                        }}</TableCell>
                    <TableCell>{{
                        activity.causer?.name ?? "Sistema"
                        }}</TableCell>
                    <TableCell>
                        <Badge :class="eventBadgeClass(activity.event)" variant="outline">{{
                            translateEvent(activity.event) }}</Badge>
                    </TableCell>
                    <TableCell class="font-mono text-xs">{{ formatSubject(activity.subject_type) }} #{{
                        activity.subject_id
                    }}</TableCell>
                    <TableCell>{{ activity.description }}</TableCell>



                </TableRow>
                <TableRow v-if="activities.data.length === 0">
                    <TableCell colspan="5" class="text-center text-gray-500">Nenhum log de atividade encontrado.
                    </TableCell>
                </TableRow>
            </template>
        </IndexPageLayout>

        <Dialog v-model:open="isModalOpen">
            <DialogContent class="max-w-4xl max-h-[400px] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Detalhes da Alteração</DialogTitle>
                    <DialogDescription v-if="selectedActivity">
                        Alterações no
                        {{ formatSubject(selectedActivity.subject_type) }} #{{
                            selectedActivity.subject_id
                        }}
                    </DialogDescription>
                    <div v-if="selectedActivity" class="pt-2 mt-2 text-sm border-t text-muted-foreground">
                        <p>
                            <strong>Realizado por:</strong>
                            {{ selectedActivity.causer?.name ?? "Sistema" }}
                        </p>
                        <p>
                            <strong>Data:</strong>
                            {{

                                selectedActivity.created_at?.short_with_time

                            }}
                        </p>
                    </div>
                </DialogHeader>
                <div class="mt-4">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Campo</TableHead>
                                <TableHead>Valor Antigo</TableHead>
                                <TableHead>Valor Novo</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in comparisonData" :key="item.key">
                                <TableCell class="font-medium align-top">{{
                                    item.key
                                    }}</TableCell>
                                <TableCell class="font-mono text-xs align-top">{{ item.oldValue }}</TableCell>
                                <TableCell class="font-mono text-xs align-top">
                                    <span :class="{
                                        'bg-yellow-200 dark:bg-yellow-700/50 p-1 rounded font-bold':
                                            item.changed,
                                    }">
                                        {{ item.newValue }}
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
