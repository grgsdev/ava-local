<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Input } from "@/components/ui/input";
import type { Paginated, Project } from '@/types';
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { useFilters } from "@/Composables/useFilters";
import { computed } from 'vue';
import { ChevronUp, ChevronDown, XCircle } from "lucide-vue-next";

interface QueryParams {
    sort?: string;
    filter?: { title?: string, search?: string };
    per_page?: string;
}

const props = defineProps<{
    projects: Paginated<Project>;
    queryParams: QueryParams;
}>();

const initialFilters = {
    title: props.queryParams?.filter?.title ?? "",
    Search: props.queryParams?.filter?.search ?? "",
    per_page: props.queryParams?.per_page ?? "15",
};

const { filters, clearFilters, sort, clearFilter } = useFilters(
    props,
    initialFilters,
    "projects.index"
);

const currentSorts = computed(() => props.queryParams?.sort?.split(",") || []);
const isSorted = (field: string) => {
    const asc = currentSorts.value.includes(field);
    const desc = currentSorts.value.includes(`-${field}`);
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};

</script>

<template>
    <Head title="Meus Projetos" />

    <AuthenticatedLayout>
        <IndexPageLayout :data="projects.meta">
            <template #title>Meus Projetos</template>

            <template #actions>
                <div>
                    <Button as-child>
                        <Link :href="route('projects.trashed')" class="mr-4 text-sm text-gray-600 hover:text-gray-900">
                            Lixeira
                        </Link>
                    </Button>

                    <Button as-child>
                        <Link :href="route('projects.create')">Criar Novo Projeto</Link>
                    </Button>
                </div>
            </template>

            <template #filters>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <div class="relative">
                        <Input type="text" v-model="filters.Search" placeholder="Pesquisar..." />
                        <Button v-if="filters.Search" @click="clearFilter('Search')" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-8">
                            <XCircle class="w-4 h-4" />
                        </Button>
                    </div>
                    <div class="relative">
                        <Input type="text" v-model="filters.title" placeholder="Pesquisar por título..." />
                        <Button v-if="filters.title" @click="clearFilter('title')" variant="ghost" size="icon"
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
                    <TableHead>
                        <button @click="sort('type')" class="flex items-center gap-2 font-semibold">
                            Tipo
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('type') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('type') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead>
                        <button @click="sort('status')" class="flex items-center gap-2 font-semibold">
                            Status
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('status') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('status') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead>
                        <button @click="sort('created_at')" class="flex items-center gap-2 font-semibold">
                            Criado Em
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('created_at') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('created_at') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead>
                        <button @click="sort('updated_at')" class="flex items-center gap-2 font-semibold">
                            Alterado Em
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
                <TableRow v-for="project in projects.data" :key="project.id">
                    <TableCell class="font-medium">{{ project.title }}</TableCell>
                    <TableCell>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="{
                            'bg-blue-100 text-blue-800': project.type === 'single_video',
                            'bg-yellow-100 text-yellow-800': project.type === 'flow_video',
                        }">
                            {{ project.type === 'single_video' ? 'Vídeo Único' : 'Fluxo de Vídeos' }}
                        </span>
                    </TableCell>
                    <TableCell>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="{
                            'bg-blue-100 text-blue-800': project.status === 'Em Edição',
                            'bg-yellow-100 text-yellow-800': project.status === 'Aguardando Aprovação',
                            'bg-green-100 text-green-800': project.status === 'Aprovado',
                            'bg-red-100 text-red-800': project.status === 'Reprovado',
                        }">
                            {{ project.status }}
                        </span>
                    </TableCell>
                    <TableCell>{{ project.created_at?.short_with_time }}</TableCell>
                    <TableCell>{{ project.updated_at?.short_with_time }}</TableCell>
                    <TableCell class="text-right">
                        <div class="flex min-w-max flex-wrap justify-end gap-2">
                            <Button as-child variant="outline" size="sm">
                                <Link :href="route('projects.edit', project.id)">Editar</Link>
                            </Button>
                            <AlertDialog>
                                <AlertDialogTrigger as-child>
                                    <Button variant="destructive" size="sm">Inativar</Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Tem a certeza que deseja inativar?</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            Esta ação irá arquivar o projeto "{{ project.title }}". Ele ficará oculto da lista principal, mas poderá ser restaurado a partir da lixeira.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                        <AlertDialogAction as-child>
                                            <Link :href="route('projects.destroy', project.id)" method="delete" as="button">
                                                Sim, inativar projeto
                                            </Link>
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    </TableCell>
                </TableRow>
                <TableRow v-if="projects.data.length === 0">
                    <TableCell colspan="6" class="py-10 text-center text-muted-foreground">
                        Nenhum projeto encontrado.
                    </TableCell>
                </TableRow>
            </template>
        </IndexPageLayout>
    </AuthenticatedLayout>
</template>
