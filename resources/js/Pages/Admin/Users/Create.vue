<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import type { Paginated, Role } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps<{
    //allRoles: Role[];
    roles: Paginated<Role>;
}>();

const form = useForm({
    name: '',
    email: '',
    roles: [],
});

const submit = () => {
    form.post(route('admin.users.store'));
};
</script>

<template>

    <Head title="Novo Usuário" />
    <AuthenticatedLayout>
        <form @submit.prevent="submit" class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Pré-Cadastro de Usuário</h1>
                    <p class="text-muted-foreground">Insira os dados básicos. O usuário receberá um e-mail para
                        finalizar.</p>
                </div>
                <Button :disabled="form.processing">Salvar e Enviar Convite</Button>
            </div>

            <Card>
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
                            :initial-options="roles.data" :multiple="true" placeholder="Selecione os papéis..." />
                        <!-- <SearchableSelect v-model="form.roles" :initial-options="allRoles" :multiple="true"
                            placeholder="Selecione os papéis..." /> -->
                        <p v-if="form.errors.roles" class="text-sm text-red-500">{{ form.errors.roles }}</p>
                    </div>
                </CardContent>
            </Card>
        </form>
    </AuthenticatedLayout>
</template>
