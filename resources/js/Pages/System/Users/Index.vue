<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useFilters } from "@/Composables/useFilters";
import type { Paginated, User } from '@/types';

// Componentes de UI
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { TableCell, TableHead, TableRow } from "@/components/ui/table";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { ChevronDown, ChevronUp, MoreHorizontal } from "lucide-vue-next";

interface QueryParams {
    sort?: string;
    filter?: { name?: string, email?: string, is_active?: boolean, search?: string };
    per_page?: string;
}
const props = defineProps<{
    users: Paginated<User>;
    queryParams: QueryParams;
}>();

const initialFilters = {
    name: props.queryParams?.filter?.name ?? "",
    email: props.queryParams?.filter?.email ?? "",
    is_active: props.queryParams?.filter?.is_active ?? "",
    Search: props.queryParams?.filter?.search ?? "",
    per_page: props.queryParams?.per_page ?? "15",
};

const { filters, clearFilters, sort } = useFilters(
    props,
    initialFilters,
    "system.users.index"
);


const currentSorts = computed(() => props.queryParams?.sort?.split(",") || []);

const isSorted = (field: string) => {
    const asc = currentSorts.value.includes(field);
    const desc = currentSorts.value.includes(`-${field}`);
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};
const isDeleteDialogOpen = ref(false);
const userToDelete = ref<User | null>(null);

const confirmDelete = (user: User) => {
    userToDelete.value = user;
    isDeleteDialogOpen.value = true;
};

const deleteUser = () => {
    console.log('User to Delete', userToDelete.value)
    if (userToDelete.value) {
        router.delete(route("system.users.destroy", userToDelete.value.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>

    <Head title="Usuários de Sistema" />

    <AuthenticatedLayout>
        <IndexPageLayout :data="users.meta">
            <template #title>Gerenciar Usuários de Sistema</template>

            <template #actions>
                <Link v-if="$can('create system_developers')" :href="route('system.users.create')">
                <Button>Novo Usuário</Button>
                </Link>
            </template>

            <template #filters>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <div class="space-y-1">
                        <label class="text-sm font-medium">{{ $t('Search') }}</label>
                        <Input type="text" v-model="filters.Search" placeholder="Pesquisar..." />
                    </div>
                    <div class="space-y-1 xl:col-span-1">
                        <label class="text-sm font-medium">{{ $t('Name') }}</label>
                        <Input type="text" v-model="filters.name" placeholder="Pesquisar por nome..." />
                    </div>
                    <div class="space-y-1 xl:col-span-1">
                        <label class="text-sm font-medium">{{ $t('Email') }}</label>
                        <Input type="text" v-model="filters.email" placeholder="Pesquisar por email..." />
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
                    <TableHead class="w-16"></TableHead>
                    <TableHead>
                        <button @click="sort('name')" class="flex items-center gap-2 font-semibold">
                            Nome
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('name') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('name') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead>
                        <button @click="sort('email')" class="flex items-center gap-2 font-semibold">
                            E-Mail
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('email') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('email') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
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
                <TableRow v-for="user in users.data" :key="user.id">
                    <TableCell>
                        <Avatar>
                            <AvatarImage :src="user.avatar_url ?? ''" :alt="user.name" />
                            <AvatarFallback>{{ user.name.substring(0, 2) }}</AvatarFallback>
                        </Avatar>
                    </TableCell>
                    <TableCell class="font-medium">{{ user.name }}</TableCell>
                    <TableCell>{{ user.email }}</TableCell>
                    <TableCell>{{ user.created_at?.short_with_time }}</TableCell>
                    <TableCell class="text-right">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" class="w-8 h-8 p-0">
                                    <MoreHorizontal class="w-4 h-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem as-child
                                    v-if="$page.props.auth.user.can.includes('update system_developers')">
                                    <Link :href="route('system.users.edit', user.id)">Editar</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem v-if="$page.props.auth.user.can.includes('delete system_developers')"
                                    @select="confirmDelete(user)" class="text-red-600 focus:text-red-600">
                                    Apagar
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </TableCell>
                </TableRow>
                <TableRow v-if="users.data.length === 0">
                    <TableCell colspan="5" class="py-10 text-center text-muted-foreground">
                        Nenhum usuário encontrado.
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
                        permanentemente o usuário
                        <span v-if="userToDelete" class="font-semibold">"{{ userToDelete.name }}"</span>.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="deleteUser" class="bg-destructive hover:bg-destructive/90">
                        Apagar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AuthenticatedLayout>
</template>
