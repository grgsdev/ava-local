<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useFilters } from "@/Composables/useFilters";
import type { Paginated, ModelDefinition } from '@/types';
import GenerateCodeModal from './Partials/GenerateCodeModal.vue'; // <-- 1. Importe o novo modal

// Componentes de UI
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { TableCell, TableHead, TableRow } from "@/components/ui/table";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator } from "@/components/ui/dropdown-menu";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { RocketIcon } from 'lucide-vue-next';
// Ícones
import { MoreHorizontal, ChevronUp, ChevronDown } from "lucide-vue-next";
interface QueryParams {
    sort?: string;
    filter?: { search?: string };
    per_page?: string;
}
const props = defineProps<{
    models: Paginated<ModelDefinition>;
    queryParams: QueryParams;
}>();

const { filters, sort } = useFilters(
    props,
    { search: props.queryParams.filter?.search ?? "" },
    "system.generator.index"
);

const currentSorts = computed(() => props.queryParams?.sort?.split(",") || []);
const isSorted = (field: string) => {
    const asc = currentSorts.value.includes(field);
    const desc = currentSorts.value.includes(`-${field}`);
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};

const isDeleteDialogOpen = ref(false);
const modelToDelete = ref<ModelDefinition | null>(null);

const confirmDelete = (model: ModelDefinition) => {
    modelToDelete.value = model;
    isDeleteDialogOpen.value = true;
};

const deleteModel = () => {
    if (modelToDelete.value) {
        // Precisamos criar esta rota
        router.delete(route("system.generator.destroy", modelToDelete.value.name), {
            preserveScroll: true,
        });

    }
};

const isGenerateModalOpen = ref(false);
const modelToGenerate = ref<string | null>(null);

const openGenerateModal = (model: ModelDefinition) => {
    modelToGenerate.value = model.name;
    isGenerateModalOpen.value = true;
};
</script>

<template>

    <Head title="Gerador de Código" />

    <AuthenticatedLayout>
        <IndexPageLayout :data="models">
            <template #title>Gerador de Código</template>

            <template #actions>
                <Link :href="route('system.generator.create')">
                <Button>Novo Modelo</Button>
                </Link>
            </template>

            <template #filters>
                <Input v-model="filters.search" placeholder="Buscar por nome do modelo..." class="max-w-xs" />
            </template>

            <template #table-header>
                <TableRow>
                    <TableHead>
                        <button @click="sort('name')" class="flex items-center gap-2 font-semibold">
                            Nome
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('name') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('name') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead>Versão</TableHead>
                    <TableHead>Atributos</TableHead>
                    <TableHead>Relações</TableHead>
                    <TableHead>
                        <button @click="sort('updated_at')" class="flex items-center gap-2 font-semibold">
                            Última Atualização
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('updated_at') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('updated_at') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead class="text-right">Ações</TableHead>
                </TableRow>
            </template>

            <template #table-body>
                <TableRow v-for="model in models.data" :key="model.name">
                    <TableCell class="font-medium">{{ model.name }}</TableCell>
                    <TableCell>
                        <Badge variant="outline">{{ model.version }}</Badge>
                    </TableCell>
                    <TableCell>{{ model.attributes_count }}</TableCell>
                    <TableCell>{{ model.relations_count }}</TableCell>
                    <TableCell>{{ new Date(model.updated_at).toLocaleString('pt-BR') }}</TableCell>
                    <TableCell class="text-right">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" class="w-8 h-8 p-0">
                                    <MoreHorizontal class="w-4 h-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem as-child>
                                    <Link :href="route('system.generator.show', model.name)">Visualizar</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="route('system.generator.edit', model.name)">Editar</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <a :href="route('system.generator.export.pdf', model.name)">Exportar PDF</a>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @select="openGenerateModal(model)">
                                    <RocketIcon class="w-4 h-4 mr-2" />
                                    Gerar Código
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @select="confirmDelete(model)"
                                    class="text-red-600 focus:text-red-600">
                                    Apagar
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </TableCell>
                </TableRow>
                <TableRow v-if="models.data.length === 0">
                    <TableCell colspan="6" class="py-10 text-center text-muted-foreground">
                        Nenhum modelo JSON encontrado.
                    </TableCell>
                </TableRow>
            </template>
        </IndexPageLayout>

        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Você tem a certeza absoluta?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Esta ação não pode ser desfeita. Isto irá apagar
                        permanentemente o arquivo JSON
                        <span v-if="modelToDelete" class="font-semibold">"{{ modelToDelete.name }}.json"</span>.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="deleteModel" class="bg-destructive hover:bg-destructive/90">
                        Apagar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <GenerateCodeModal v-model:open="isGenerateModalOpen" :model-name="modelToGenerate" />
    </AuthenticatedLayout>
</template>
