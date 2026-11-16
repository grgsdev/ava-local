<script setup lang="ts">
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { useFilters } from "@/Composables/useFilters";
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Paginated, UserTeam } from '@/types'; // Importa as nossas interfaces
import { MoreHorizontal } from 'lucide-vue-next';

defineProps<{
    teamMembers: Paginated<UserTeam>;
}>();

const isInviteModalOpen = ref(false);
const openInviteModal = () => {
    isInviteModalOpen.value = true;
};
const form = useForm({
    name: '',
    email: '',
    role: 'Editor', // Perfil padrão
});

const submit = () => {
    form.post(route('team.invitations.store'), {
        onSuccess: () => {
            // Agora podemos fechar o modal manualmente após o sucesso.
            isInviteModalOpen.value = false;
            form.reset();
        },
    });
};

// --- Lógica para o Modal de Edição ---
const isEditModalOpen = ref(false);
const memberToEdit = ref<UserTeam | null>(null);

const editForm = useForm({
    role: '',
});

// Função para abrir o modal de edição
const openEditModal = (member: UserTeam) => {
    memberToEdit.value = member;
    editForm.role = member.role; // Preenche o formulário com o perfil atual
    isEditModalOpen.value = true;
};

// Função para submeter a atualização
const submitUpdate = () => {
    if (!memberToEdit.value) return;
    editForm.put(route('team.members.update', memberToEdit.value.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
        }
    });
};
</script>

<template>

    <Head title="Gestão de Equipe" />

    <AuthenticatedLayout>
        <IndexPageLayout :data="teamMembers.meta">
            <template #title>Gestão de Equipe</template>
            <template #actions>
                <Button @click="openInviteModal">Convidar Participante</Button>
            </template>
            <template #filters>

            </template>

            <template #table-header>

                <TableRow>
                    <TableHead class="w-16"></TableHead>
                    <TableHead>Nome</TableHead>
                    <TableHead>Email</TableHead>
                    <TableHead>Perfil</TableHead>
                    <TableHead class="text-right">Ações</TableHead>
                </TableRow>
            </template>
            <template #table-body>
                <TableRow v-for="member in teamMembers.data" :key="member.id">
                    <TableCell>
                        <Avatar>
                            <AvatarImage :src="member.avatar_url ?? ''" :alt="member.name" />
                            <AvatarFallback>{{ member.name.substring(0, 2) }}</AvatarFallback>
                        </Avatar>
                    </TableCell>
                    <TableCell class="font-medium">
                        {{ member.name }}
                        <span v-if="member.status === 'inactive'"
                            class="ml-2 text-xs font-bold text-red-500">[INATIVO]</span>

                    </TableCell>
                    <TableCell>{{ member.email }}</TableCell>
                    <TableCell class="capitalize">{{ member.role }}</TableCell>
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
                                <DropdownMenuItem @select="openEditModal(member)">
                                    Editar
                                </DropdownMenuItem>
                                <template v-if="member.status === 'active'">
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem as-child class="text-red-600 focus:text-red-600">
                                        <Link :href="route('team.members.deactivate', member.id)" method="patch"
                                            as="button">
                                        Desativar</Link>
                                    </DropdownMenuItem>
                                </template>
                                <template v-else>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem as-child class="text-green-600 focus:text-green-600">
                                        <Link :href="route('team.members.reactivate', member.id)" method="patch"
                                            as="button">
                                        Reativar</Link>
                                    </DropdownMenuItem>
                                </template>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child class="text-red-600 focus:text-red-600">
                                    <AlertDialog>
                                        <AlertDialogTrigger as-child>
                                            <Button variant="destructive" size="sm">Remover</Button>
                                        </AlertDialogTrigger>
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>Tem a certeza?</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Esta ação irá remover {{ member.name }} da sua equipe. O
                                                    utilizador
                                                    não perderá a sua conta, apenas o acesso a esta empresa. Esta
                                                    ação
                                                    não pode ser desfeita.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>Cancelar</AlertDialogCancel>
                                                <AlertDialogAction as-child>
                                                    <Link :href="route('team.members.destroy', member.id)"
                                                        method="delete" as="button">
                                                    Sim, remover membro
                                                    </Link>
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>

                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </TableCell>

                </TableRow>
                <TableRow v-if="teamMembers.data.length === 0">
                    <TableCell colspan="5" class="py-10 text-center text-muted-foreground">
                        Nenhum usuário encontrado.
                    </TableCell>
                </TableRow>
            </template>
        </IndexPageLayout>
        <Dialog :open="isInviteModalOpen" @update:open="isInviteModalOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Convidar Novo Membro</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label for="name">Nome</Label>
                        <Input id="name" v-model="form.name" required />
                    </div>
                    <div>
                        <Label for="email">Email</Label>
                        <Input id="email" type="email" v-model="form.email" required />
                    </div>
                    <div>
                        <Label for="role">Perfil</Label>
                        <select id="role" v-model="form.role" class="w-full border-gray-300 rounded-md">
                            <option value="Editor">Editor</option>
                            <option value="Manager">Manager</option>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing">Enviar Convite</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog :open="isEditModalOpen" @update:open="isEditModalOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Editar Perfil de {{ memberToEdit?.name }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitUpdate" class="space-y-4">
                    <div>
                        <Label for="edit_role">Perfil</Label>
                        <select id="edit_role" v-model="editForm.role" class="w-full border-gray-300 rounded-md">
                            <option value="Editor">Editor</option>
                            <option value="Manager">Manager</option>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="editForm.processing">Salvar Alterações</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
