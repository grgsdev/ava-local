<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

// Componentes de UI
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { TableCell, TableHead, TableRow } from "@/components/ui/table";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";

// Ícones e Tipos
import { ChevronUp, ChevronDown, MoreHorizontal } from "lucide-vue-next";
import { useFilters } from "@/Composables/useFilters";
import type { Paginated, Role } from '@/types';
interface QueryParams {
    sort?: string;
    filter?: { search?: string };
    per_page?: string;
}
// Props adaptadas para Papéis (Roles)
const props = defineProps<{
    roles: Paginated<Role>;
    queryParams: QueryParams;
}>();

const initialFilters = {
    search: props.queryParams?.filter?.search ?? "",
    per_page: props.queryParams?.per_page ?? "15",
};

// Rota do useFilters atualizada para 'system.roles.index'
const { filters, clearFilters, sort } = useFilters(
    props,
    initialFilters,
    "system.roles.index"
);

const currentSorts = computed(() => props.queryParams?.sort?.split(",") || []);
const isSorted = (field: string) => {
    const asc = currentSorts.value.includes(field);
    const desc = currentSorts.value.includes(`-${field}`);
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};

// Lógica de exclusão adaptada para Papéis (Roles)
const isDeleteDialogOpen = ref(false);
const roleToDelete = ref<Role | null>(null);

const confirmDelete = (role: Role) => {
    roleToDelete.value = role;
    isDeleteDialogOpen.value = true;
};

const deleteRole = () => {
    if (roleToDelete.value) {
        router.delete(route("system.roles.destroy", roleToDelete.value.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>

    <Head title="Gerenciar Papéis" />

    <AuthenticatedLayout>
        <IndexPageLayout :data="roles.meta">
            <template #title>
                Gerenciar Papéis
            </template>

            <template #actions>
                <Link v-if="$page.props.auth.user.can.includes('create roles')" :href="route('system.roles.create')">
                <Button>Novo Papel</Button>
                </Link>
            </template>

            <template #filters>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-1">
                        <label class="text-sm font-medium">Nome do Papel</label>
                        <Input type="text" v-model="filters.search" placeholder="Pesquisar por nome..." />
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm">Itens por página</label>
                        <Select v-model="filters.per_page">
                            <SelectTrigger class="w-24">
                                <SelectValue placeholder="Qtd" />
                            </SelectTrigger>
                            <SelectContent>
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
                    <TableHead>Permissões</TableHead>
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
                <TableRow v-for="role in roles.data" :key="role.id">
                    <TableCell>{{ role.id }}</TableCell>
                    <TableCell class="font-medium">{{ role.name }}</TableCell>
                    <TableCell>{{ role.permissions_count }}</TableCell>
                    <TableCell>{{ role.created_at?.short_with_time }}</TableCell>
                    <TableCell class="text-right">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" class="w-8 h-8 p-0">
                                    <MoreHorizontal class="w-4 h-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuLabel>Ações</DropdownMenuLabel>
                                <DropdownMenuItem as-child v-if="$page.props.auth.user.can.includes('update roles')">
                                    <Link :href="route('system.roles.edit', role.id)">Editar</Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem v-if="$page.props.auth.user.can.includes('delete roles')"
                                    @select="confirmDelete(role)"
                                    class="text-red-600 focus:text-red-600 focus:bg-red-50">
                                    Apagar
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </TableCell>
                </TableRow>
                <TableRow v-if="roles.data.length === 0">
                    <TableCell colspan="4" class="py-10 text-center text-muted-foreground">
                        Nenhum papel encontrado.
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
                        permanentemente o papel
                        <span v-if="roleToDelete" class="font-semibold">"{{ roleToDelete.name }}"</span>.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="deleteRole" class="bg-destructive hover:bg-destructive/90">
                        Apagar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AuthenticatedLayout>
</template>
