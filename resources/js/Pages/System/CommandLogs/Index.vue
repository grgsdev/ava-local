<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { useFilters } from "@/Composables/useFilters";
import type { Paginated, CommandLog } from '@/types';
import { computed, ref } from "vue";

// Componentes de UI
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { TableCell, TableHead, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import Datepicker from "@/Components/Datepicker.vue";

// Ícones
import { MoreHorizontal, ChevronUp, ChevronDown, XCircle } from "lucide-vue-next";

// Props
interface QueryParams {
    sort?: string;
    filter?: {
        command?: string;
        status?: string;
        Search?: string;
        ExecutedAtBefore?: string;
        ExecutedAtAfter?: string;
    };
    per_page?: string;
}

const props = defineProps<{
    commands: Paginated<CommandLog>;
    queryParams: QueryParams;
}>();

// Filtros e Ordenação
const initialFilters = {
    Search: props.queryParams?.filter?.Search ?? "",
    status: props.queryParams?.filter?.status ?? "",
    ExecutedAtAfter: props.queryParams?.filter?.ExecutedAtAfter ?? "",
    ExecutedAtBefore: props.queryParams?.filter?.ExecutedAtBefore ?? "",
    per_page: props.queryParams?.per_page ?? "15",
};

const { filters, clearFilters, sort, clearFilter } = useFilters(
    props,
    initialFilters,
    "system.command-log.index" // Ajuste o nome da rota se for diferente
);

const currentSorts = computed(() => props.queryParams?.sort?.split(",") || []);
const isSorted = (field: string) => {
    const asc = currentSorts.value.includes(field);
    const desc = currentSorts.value.includes(`-${field}`);
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};

// Lógica de Exclusão
const isDeleteDialogOpen = ref(false);
const logToDelete = ref<CommandLog | null>(null);

const confirmDelete = (log: CommandLog) => {
    logToDelete.value = log;
    isDeleteDialogOpen.value = true;
};

const deleteLog = () => {
    if (logToDelete.value) {
        router.delete(route("system.command-log.delete", logToDelete.value.id), { // Ajuste o nome da rota
            preserveScroll: true,
        });
    }
};

// Funções de Apoio para UI
const getStatusInfo = (status: boolean) => {
    if (status) {
        return { text: 'Sucesso', variant: 'default' as const };
    }

    return { text: 'Falhou', variant: 'secondary' as const };
};

// Computed properties para os datepickers
const dateFromModel = computed<Date | null>({
    get: () => filters.ExecutedAtAfter ? new Date(filters.ExecutedAtAfter) : null,
    set: (val) => filters.ExecutedAtAfter = val ? val.toISOString() : ''
});
const dateToModel = computed<Date | null>({
    get: () => filters.ExecutedAtBefore ? new Date(filters.ExecutedAtBefore) : null,
    set: (val) => filters.ExecutedAtBefore = val ? val.toISOString() : ''
});
</script>

<template>

    <Head title="Logs de Comandos" />
    <AuthenticatedLayout>
        <IndexPageLayout :data="commands.meta">
            <template #title>Logs de Comandos Agendados</template>

            <template #filters>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                    <!-- Busca Geral -->
                    <div class="relative">
                        <Input v-model="filters.Search" placeholder="Buscar por comando ou data..." class="pr-8" />
                        <Button v-if="filters.Search" @click="clearFilter('Search')" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-1">
                            <XCircle class="w-4 h-4" />
                        </Button>
                    </div>

                    <!-- Filtro de Status -->
                    <div class="relative">
                        <Select v-model="filters.status">
                            <SelectTrigger>
                                <SelectValue placeholder="Todos os status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value=1>Sucesso</SelectItem>
                                <SelectItem value=0>Falhou</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button v-if="filters.status" @click="clearFilter('status')" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-8">
                            <XCircle class="w-4 h-4" />
                        </Button>
                    </div>

                    <!-- Filtro Data De -->
                    <div class="relative">
                        <Datepicker v-model="dateFromModel" locale="pt-BR" placeholder="Executado de..." />
                        <Button v-show="filters.ExecutedAtAfter" @click="clearFilter('ExecutedAtAfter')" variant="ghost"
                            size="icon" class="absolute w-6 h-6 text-muted-foreground top-1.5 right-1">
                            <XCircle class="w-4 h-4" />
                        </Button>
                    </div>

                    <!-- Filtro Data Até -->
                    <div class="relative">
                        <Datepicker v-model="dateToModel" locale="pt-BR" placeholder="Executado até..." />
                        <Button v-show="filters.ExecutedAtBefore" @click="clearFilter('ExecutedAtBefore')"
                            variant="ghost" size="icon" class="absolute w-6 h-6 text-muted-foreground top-1.5 right-1">
                            <XCircle class="w-4 h-4" />
                        </Button>
                    </div>

                    <!-- Qtd por página -->
                    <div class="relative">
                        <Select v-model="filters.per_page">
                            <SelectTrigger class="w-24">
                                <SelectValue placeholder="Qtd" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="15">15</SelectItem>
                                <SelectItem value="30">30</SelectItem>
                                <SelectItem value="50">50</SelectItem>
                                <SelectItem value="100">100</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Limpar Filtros -->
                    <div class="relative">
                        <TooltipProvider :delay-duration="100">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button @click="clearFilters" variant="outline">
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
                    <TableHead> <button @click="sort('command')" class="flex items-center gap-2 font-semibold">
                            Comando
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('command') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('command') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button></TableHead>
                    <TableHead>
                        <button @click="sort('status')" class="flex items-center gap-2 font-semibold">
                            Status
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('status') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('status') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <!-- <TableHead class="w-32">Status</TableHead> -->
                    <TableHead class="w-48">
                        <button @click="sort('executed_at')" class="flex items-center gap-2 font-semibold">
                            Executado em
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('executed_at') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('executed_at') === 'desc'" class="w-4 h-4" />

                            </div>
                        </button>
                    </TableHead>
                    <TableHead class="w-24 text-right">Ações</TableHead>
                </TableRow>
            </template>

            <template #table-body>
                <TableRow v-for="log in commands.data" :key="log.id">
                    <TableCell class="font-mono text-sm font-medium">{{ log.command }}</TableCell>
                    <TableCell>
                        <Badge :variant="getStatusInfo(log.status).variant">{{ getStatusInfo(log.status).text }}</Badge>
                    </TableCell>
                    <TableCell>{{ log.executed_at.short_with_time }}</TableCell>
                    <TableCell class="text-right">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" class="w-8 h-8 p-0">
                                    <span class="sr-only">Abrir menu</span>
                                    <MoreHorizontal class="w-4 h-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuLabel>Ações</DropdownMenuLabel>
                                <DropdownMenuItem as-child>
                                    <Link :href="route('system.command-log.show', log.id)">Visualizar</Link>
                                </DropdownMenuItem>

                                <DropdownMenuSeparator />
                                <DropdownMenuItem @select="confirmDelete(log)" class="text-red-600 focus:text-red-600">
                                    Apagar
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </TableCell>
                </TableRow>
                <TableRow v-if="commands.data.length === 0">
                    <TableCell colspan="4" class="py-10 text-center text-muted-foreground">
                        Nenhum log encontrado.
                    </TableCell>
                </TableRow>
            </template>
        </IndexPageLayout>

        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Você tem certeza?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Esta ação não pode ser desfeita. O log do comando será permanentemente apagado.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="deleteLog" class="bg-destructive hover:bg-destructive/90">
                        Apagar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AuthenticatedLayout>
</template>
