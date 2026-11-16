<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { TableCell, TableHead, TableRow } from "@/components/ui/table";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { ChevronUp, ChevronDown, MoreHorizontal } from "lucide-vue-next";
import { useFilters } from "@/Composables/useFilters";

// --- CORREÇÕES DE TIPO ---
import type { Paginated, Permission } from '@/types'; // 1. Importamos nossos tipos

// 2. Definimos uma interface para os queryParams
interface QueryParams {
    sort?: string;
    filter?: { search?: string };
    per_page?: string;
}

// 3. Tipamos nossas props corretamente
const props = defineProps<{
    permissions: Paginated<Permission>;
    queryParams: QueryParams;
}>();

const initialFilters = {
    Search: props.queryParams?.filter?.search ?? "",
    per_page: props.queryParams?.per_page ?? "15",
};

const { filters, clearFilters, sort } = useFilters(
    props,
    initialFilters,
    "system.permissions.index"
);

const currentSorts = computed(() => props.queryParams?.sort?.split(",") || []);
const isSorted = (field: string) => {
    const asc = currentSorts.value.includes(field);
    const desc = currentSorts.value.includes(`-${field}`);
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};

// 4. Tipamos as variáveis da lógica de exclusão
const isDeleteDialogOpen = ref(false);
const permissionToDelete = ref<Permission | null>(null);

const confirmDelete = (permission: Permission) => {
    permissionToDelete.value = permission;
    isDeleteDialogOpen.value = true;
};

const deletePermission = () => {
    if (permissionToDelete.value) {
        router.delete(route("system.permissions.destroy", permissionToDelete.value.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>

    <Head title="Gerenciar Permissões" />

    <AuthenticatedLayout>
        <IndexPageLayout :data="permissions.meta">
            <template #title>
                Gerenciar Permissões
            </template>

            <template #actions>
                <Link v-if="$page.props.auth.user.can.includes('create permissions')"
                    :href="route('system.permissions.create')">
                <Button class="text-card-foreground">Nova Permissão</Button>
                </Link>
            </template>

            <template #filters>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-1">
                        <label class="text-sm font-medium">Nome da Permissão</label>
                        <Input type="text" v-model="filters.Search" placeholder="Pesquisar por nome..." />
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm">Itens por página</label>
                        <Select v-model="filters.per_page">
                            <SelectTrigger class="w-24">
                                <SelectValue placeholder="Qtd" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="10">10</SelectItem>

                                <SelectItem value="15">15</SelectItem>
                                <SelectItem value="30">30</SelectItem>
                                <SelectItem value="50">50</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex items-end">
                        <Button @click="clearFilters" variant="ghost" class="w-full">Limpar Filtros</Button>
                    </div>
                </div>
            </template>

            <template #table-header>
                <TableRow>
                    <TableHead>
                        <button @click="sort('id')" class="flex items-center gap-2 font-semibold">
                            ID
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('id') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('id') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead>
                        <button @click="sort('name')" class="flex items-center gap-2 font-semibold">
                            Nome
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('name') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('name') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead>Nº de Papéis</TableHead>
                    <TableHead>
                        <button @click="sort('created_at')" class="flex items-center gap-2 font-semibold">
                            Data de Criação
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('created_at') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('created_at') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead class="text-right">Ações</TableHead>
                </TableRow>
            </template>

            <template #table-body>
                <TableRow v-for="permission in permissions.data" :key="permission.id">
                    <TableCell>{{ permission.id }}</TableCell>
                    <TableCell class="font-medium">{{ permission.name }}</TableCell>
                    <TableCell>{{ permission.roles_count }}</TableCell>
                    <TableCell>{{ permission.created_at?.short_with_time }}</TableCell>
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
                                <DropdownMenuItem as-child
                                    v-if="$page.props.auth.user.can.includes('update permissions')">
                                    <Link :href="route('system.permissions.edit', permission.id)">Editar</Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem v-if="$page.props.auth.user.can.includes('delete permissions')"
                                    @select="confirmDelete(permission)"
                                    class="text-red-600 focus:text-red-600 focus:bg-red-50">
                                    Apagar
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </TableCell>
                </TableRow>
                <TableRow v-if="permissions.data.length === 0">
                    <TableCell colspan="4" class="py-10 text-center text-muted-foreground">
                        Nenhuma permissão encontrada.
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
                        permanentemente a permissão
                        <span v-if="permissionToDelete" class="font-semibold">"{{ permissionToDelete.name }}"</span>.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="deletePermission" class="bg-destructive hover:bg-destructive/90">
                        Apagar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AuthenticatedLayout>
</template>
