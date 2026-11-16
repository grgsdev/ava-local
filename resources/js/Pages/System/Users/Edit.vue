<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AvatarInput from '@/Components/AvatarInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import type { User } from '@/types';

const props = defineProps<{
    user: User;
}>();

const form = useForm({
    _method: 'PATCH', // Importante para o Inertia/Laravel entender que é um update
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    avatar: null as File | null,
});

const submit = () => {
    form.post(route('system.users.update', props.user.id));
};
</script>

<template>

    <Head title="Editar Usuário" />
    <AuthenticatedLayout>
        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle>Editar Usuário: {{ user.name }}</CardTitle>
                        <CardDescription>Ajuste os dados do usuário. Deixe a senha em branco para não alterá-la.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="flex flex-col items-center space-y-1.5">
                            <Label>Avatar</Label>
                            <AvatarInput v-model="form.avatar" :default-src="user.avatar_url ?? undefined" />
                            <p v-if="form.errors.avatar" class="text-sm text-red-500">{{ form.errors.avatar }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="name">Nome</Label>
                            <Input id="name" v-model="form.name" type="text" />
                            <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="email">E-mail</Label>
                            <Input id="email" v-model="form.email" type="email" />
                            <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="password">Nova Senha</Label>
                            <Input id="password" v-model="form.password" type="password" />
                            <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="password_confirmation">Confirmar Nova Senha</Label>
                            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-end">
                        <Button :disabled="form.processing">Salvar Alterações</Button>
                    </CardFooter>
                </Card>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
