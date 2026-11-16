<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import type { User, Role } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps<{
    user: User;
    allRoles: Role[];
}>();

// Extrai apenas os IDs dos papéis para o v-model
const roleIds = props.user.roles.map(r => r.id);

const form = useForm({
    _method: 'PATCH',
    name: props.user.name,
    email: props.user.email,
    roles: roleIds,
});

const submit = () => {
    form.put(route('admin.users.update', props.user.id));
};
</script>

<template>

    <Head :title="`Editar Usuário: ${user.name}`" />

    <AuthenticatedLayout>
        <form @submit.prevent="submit" class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-card-foreground">Editar Usuário</h1>
                    <p class="text-card-foreground">Altere os dados e papéis do usuário.</p>
                </div>
                <Button class="text-center" :disabled="form.processing">Salvar
                    Alterações</Button>
            </div>

            <Card class="rounded-xl">
                <CardHeader>
                    <CardTitle>Dados do Usuário</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="space-y-1.5">
                        <Label for="name">Nome</Label>
                        <Input id="name" v-model="form.name" />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="email">E-mail</Label>
                        <Input id="email" type="email" v-model="form.email" />
                        <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="roles">Papéis (Roles)</Label>
                        <SearchableSelect v-model="form.roles" :api-url="route('system.roles.selection')"
                            :initial-options="allRoles" :multiple="true" placeholder="Selecione os papéis..." />
                        <p v-if="form.errors.roles" class="text-sm text-red-500">{{ form.errors.roles }}</p>
                    </div>
                </CardContent>
            </Card>
        </form>
    </AuthenticatedLayout>
</template>
