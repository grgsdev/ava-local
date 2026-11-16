<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { useFilters } from "@/Composables/useFilters";
import type { Paginated, User, Role } from '@/types';
import { computed, ref } from "vue";
// Componentes de UI
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { TableCell, TableHead, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
// Ícones
import { MoreHorizontal, ChevronUp, ChevronDown, XCircle } from "lucide-vue-next";
import SearchableSelect from "@/Components/SearchableSelect.vue";
interface QueryParams {
    sort?: string;
    filter?: { name?: string, email?: string, is_active?: string, roles: number[], search?: string };
    per_page?: string;
}
const props = defineProps<{
    users: Paginated<User>;
    queryParams: QueryParams;
    allRoles: Role[];
}>();

const subscriptionStatusMap: Record<string, { text: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
    active: { text: "Ativa", variant: "default" },
    cancellation: { text: "Cancelada", variant: "destructive" },
    pending: { text: "Pendente", variant: "secondary" },
    expiration: { text: "Expirada", variant: "outline" },
    // Adicione outros status conforme necessário
};

const getSubscriptionStatusInfo = (status: string | undefined) => {
    if (!status) return null;
    return subscriptionStatusMap[status] || { text: status, variant: "secondary" };
};
const initialFilters = {
    name: props.queryParams?.filter?.name ?? "",
    email: props.queryParams?.filter?.email ?? "",
    is_active: props.queryParams?.filter?.is_active ?? "",
    roles: props.queryParams?.filter?.roles ?? [],
    Search: props.queryParams?.filter?.search ?? "",
    per_page: props.queryParams?.per_page ?? "15",
};
const { filters, clearFilters, sort, clearFilter } = useFilters(
    props,
    initialFilters,
    "admin.users.index"
);

const currentSorts = computed(() => props.queryParams?.sort?.split(",") || []);

const isSorted = (field: string) => {
    const asc = currentSorts.value.includes(field);
    const desc = currentSorts.value.includes(`-${field}`);
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};
// Lógica de exclusão
const isDeleteDialogOpen = ref(false);
const userToDelete = ref<User | null>(null);

const confirmDelete = (user: User) => {
    userToDelete.value = user;
    isDeleteDialogOpen.value = true;
};

const deleteUser = () => {
    if (userToDelete.value) {
        router.delete(route("admin.users.destroy", userToDelete.value.id), {
            preserveScroll: true,
            preserveState: false,
        });
    }
};

const toggleIsActive = (user: User) => {
    router.patch(route('admin.users.toggleActive', user.id), {}, {
        preserveScroll: true,
        preserveState: false,
    });
};
const statusColors: Record<string, string> = {
    '1': 'bg-yellow-500 hover:bg-yellow-600', // Análise
    '2': 'bg-green-500 hover:bg-green-600',   // Aprovado
    '3': 'bg-red-500 hover:bg-red-600',       // Reprovado
    '4': 'bg-gray-700 hover:bg-gray-800',     // Bloqueado
    '5': 'bg-gray-500 hover:bg-gray-600',     // Cancelado
};
const approveUser = (user: User) => {
    router.patch(route('admin.users.approve', user.id), {}, { preserveScroll: true, preserveState: false, });
};

const rejectUser = (user: User) => {
    router.patch(route('admin.users.reject', user.id), {}, { preserveScroll: true, preserveState: false, });
};
</script>

<template>

    <Head title="Gerir Usuários" />

    <AuthenticatedLayout>
        <IndexPageLayout :data="users.meta">
            <template #title>Gerir Usuários</template>

            <template #actions>
                <Link v-if="$can('create users')" :href="route('admin.users.create')">
                <Button>Novo Usuário</Button>
                </Link>
            </template>
            <template #filters>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">
                    <div class="relative">
                        <Input v-model="filters.Search" placeholder="Buscar por nome, e-mail..." class="pr-8" />
                        <Button v-if="filters.Search" @click="clearFilter('Search')" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-1">
                            <XCircle class="w-4 h-4" />
                        </Button>
                    </div>
                    <div class="relative">
                        <Input v-model="filters.name" placeholder="Buscar por nome" class="pr-8" />
                        <Button v-if="filters.name" @click="clearFilter('name')" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-1">
                            <XCircle class="w-4 h-4" />
                        </Button>
                    </div>
                    <div class="relative">
                        <Input v-model="filters.email" placeholder="Buscar por email" class="pr-8" />
                        <Button v-if="filters.email" @click="clearFilter('email')" variant="ghost" size="icon"
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
                    <!-- <div class="relative">
                        <SearchableSelect v-model="filters.roles" :api-url="route('admin.roles.selection')"
                            :initial-options="allRoles.map(r => ({ id: r.id, name: r.name }))"
                            placeholder="Filtrar por Papéis..." />
                        <Button v-if="filters.roles" @click="clearFilter('roles')" variant="ghost" size="icon"
                            class="absolute w-6 h-6 text-muted-foreground top-1.5 right-8">
                            <XCircle class="w-4 h-4" />
                        </Button>
                    </div> -->
                    <div class="relative">

                        <Select v-model="filters.roles">
                            <SelectTrigger>
                                <SelectValue placeholder="Papel" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="role in allRoles" :key="role.id" :value="role.name">
                                    {{ $t(role.name) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Button v-if="filters.roles" @click="filters.roles = []" variant="ghost" size="icon"
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
                        <button @click="sort('is_active')" class="flex items-center gap-2 font-semibold">
                            Status
                            <div class="flex items-center">
                                <ChevronUp v-if="isSorted('is_active') === 'asc'" class="w-4 h-4" />
                                <ChevronDown v-if="isSorted('is_active') === 'desc'" class="w-4 h-4" />
                            </div>
                        </button>
                    </TableHead>
                    <TableHead>Papéis (Roles)</TableHead>
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
                    <TableCell>
                        <Badge :variant="user.is_active ? 'default' : 'destructive'">{{ user.is_active ?
                            'Ativo' : 'Inativo' }}</Badge>
                    </TableCell>

                    <TableCell>
                        <div class="flex flex-wrap gap-1">
                            <Badge v-for="role in user.roles" :key="role.id" variant="secondary">
                                {{ $t(role.name) }}
                            </Badge>
                        </div>
                    </TableCell>
                    <TableCell>{{ user.created_at?.short_with_time }}</TableCell>
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
                                    <Link :href="route('admin.users.show', user.id)">Visualizar</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="route('admin.users.edit', user.id)">Editar</Link>
                                </DropdownMenuItem>
                                <template v-if="user.is_active === true">
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @select="toggleIsActive(user)"
                                        class="text-red-600 focus:text-red-600">
                                        Inativar
                                    </DropdownMenuItem>
                                </template>
                                <template v-else>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @select="toggleIsActive(user)"
                                        class="text-green-600 focus:text-green-600">
                                        Ativar
                                    </DropdownMenuItem>
                                </template>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @select="confirmDelete(user)" class="text-red-600 focus:text-red-600">
                                    Apagar
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </TableCell>

                </TableRow>
                <TableRow v-if="users.data.length === 0">
                    <TableCell colspan="6" class="py-10 text-center text-muted-foreground">
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
                        <span v-if="userToDelete" class="font-semibold">"{{ userToDelete.name
                        }}"</span>.
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
