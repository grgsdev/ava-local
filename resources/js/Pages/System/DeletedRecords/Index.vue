<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { useFilters } from "@/Composables/useFilters";
import type { Paginated, DeletedRecord } from '@/types';
import { computed, ref } from "vue";

// Componentes de UI
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { TableCell, TableHead, TableRow } from "@/components/ui/table";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import Datepicker from "@/Components/Datepicker.vue";

// Ícones
import { MoreHorizontal, ChevronUp, ChevronDown, XCircle, History, Trash2 } from "lucide-vue-next";

// Props
interface QueryParams {
    sort?: string;
    filter?: {
        CauserName?: string;
        Search?: string;
        DeletedAtAfter?: string;
        DeletedAtBefore?: string;
    };
    per_page?: string;
}

const props = defineProps<{
    records: Paginated<DeletedRecord>;
    queryParams: QueryParams;
}>();

// Filtros e Ordenação
const initialFilters = {
    Search: props.queryParams?.filter?.Search ?? "",
    CauserName: props.queryParams?.filter?.CauserName ?? "",
    DeletedAtAfter: props.queryParams?.filter?.DeletedAtAfter ?? "",
    DeletedAtBefore: props.queryParams?.filter?.DeletedAtBefore ?? "",
    per_page: props.queryParams?.per_page ?? "15",
};

const { filters, clearFilters, sort, clearFilter } = useFilters(
    props,
    initialFilters,
    "system.deleted-records.index" // Ajuste o nome da rota se necessário
);

const currentSorts = computed(() => props.queryParams?.sort?.split(",") || []);
const isSorted = (field: string) => {
    const asc = currentSorts.value.includes(field);
    const desc = currentSorts.value.includes(`-${field}`);
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};

// Lógica de Ações
const isDialogOpen = ref(false);
const selectedRecord = ref<DeletedRecord | null>(null);
const actionType = ref<'recover' | 'delete' | null>(null);

const confirmAction = (record: DeletedRecord, type: 'recover' | 'delete') => {
    selectedRecord.value = record;
    actionType.value = type;
    isDialogOpen.value = true;
};

const executeAction = () => {
    if (!selectedRecord.value || !actionType.value) return;

    if (actionType.value === 'recover') {
        router.post(route("system.deleted-records.recover", selectedRecord.value.id), {}, { // Ajuste a rota
            preserveScroll: true,
        });
    }

    if (actionType.value === 'delete') {
        router.delete(route("system.deleted-records.delete", selectedRecord.value.id), { // Ajuste a rota
            preserveScroll: true,
        });
    }
    isDialogOpen.value = false;
};

// Computed properties para os datepickers
const dateFromModel = computed<Date | null>({
    get: () => filters.DeletedAtAfter ? new Date(filters.DeletedAtAfter) : null,
    set: (val) => filters.DeletedAtAfter = val ? val.toISOString() : ''
});
const dateToModel = computed<Date | null>({
    get: () => filters.DeletedAtBefore ? new Date(filters.DeletedAtBefore) : null,
    set: (val) => filters.DeletedAtBefore = val ? val.toISOString() : ''
});
</script>

<template>

    <Head title="Registros Excluídos" />
    <AuthenticatedLayout>
        <IndexPageLayout :data="records.meta">
            <template #title>Lixeira de Registros</template>

            <template #filters>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Input v-model="filters.Search" placeholder="Buscar por data..." />
                    <Input v-model="filters.CauserName" placeholder="Nome do responsável..." />
                    <Datepicker v-model="dateFromModel" locale="pt-BR" placeholder="Excluído de..." />
                    <Datepicker v-model="dateToModel" locale="pt-BR" placeholder="Excluído até..." />
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
            </template>

            <template #table-header>
                <TableRow>
                    <TableHead>Tipo do Registro</TableHead>
                    <TableHead>ID do Registro</TableHead>
                    <TableHead>Excluído Por</TableHead>
                    <TableHead>
                        <button @click="sort('deleted_at')" class="flex items-center gap-2 font-semibold">
                            Data da Exclusão
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('deleted_at') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('deleted_at') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead class="text-right">Ações</TableHead>
                </TableRow>
            </template>

            <template #table-body>
                <TableRow v-for="record in records.data" :key="record.id">
                    <TableCell class="font-mono text-xs">{{ record.subject_type.split('\\').pop() }}</TableCell>
                    <TableCell>{{ record.subject_id }}</TableCell>
                    <TableCell>{{ record.causer_name || 'Sistema' }}</TableCell>
                    <TableCell>{{ record.deleted_at.short_with_time }}</TableCell>
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
                                    <Link :href="route('system.deleted-records.show', record.id)">Visualizar Dados
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem @select="confirmAction(record, 'recover')"
                                    class="text-blue-600 focus:text-blue-600">
                                    <History class="w-4 h-4 mr-2" /> Recuperar Registro
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @select="confirmAction(record, 'delete')"
                                    class="text-red-600 focus:text-red-600">
                                    <Trash2 class="w-4 h-4 mr-2" /> Excluir Permanentemente
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </TableCell>
                </TableRow>
                <TableRow v-if="records.data.length === 0">
                    <TableCell colspan="5" class="py-10 text-center text-muted-foreground">
                        Nenhum registro excluído encontrado.
                    </TableCell>
                </TableRow>
            </template>
        </IndexPageLayout>

        <AlertDialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Você tem certeza absoluta?</AlertDialogTitle>
                    <AlertDialogDescription v-if="actionType === 'recover'">
                        Esta ação irá restaurar o registro original usando os dados salvos. O backup será removido após
                        a
                        recuperação.
                    </AlertDialogDescription>
                    <AlertDialogDescription v-if="actionType === 'delete'">
                        Esta ação não pode ser desfeita. O backup dos dados será permanentemente apagado.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="executeAction"
                        :class="actionType === 'delete' ? 'bg-destructive hover:bg-destructive/90' : ''">
                        {{ actionType === 'recover' ? 'Sim, Recuperar' : 'Sim, Excluir' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AuthenticatedLayout>
</template>
