<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AvatarInput from '@/Components/AvatarInput.vue'; // <-- Nosso novo componente!
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    avatar: null as File | null,
});

const submit = () => {
    // O Inertia detecta o objeto File e envia como multipart/form-data automaticamente
    form.post(route('system.users.store'));
};
</script>

<template>

    <Head title="Novo Usuário de Sistema" />
    <AuthenticatedLayout>
        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle>Novo Usuário de Sistema</CardTitle>
                        <CardDescription>Crie um novo usuário com o papel 'System Developer'.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="flex flex-col items-center space-y-1.5">
                            <Label>Avatar</Label>
                            <AvatarInput v-model="form.avatar" />
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
                            <Label for="password">Senha</Label>
                            <Input id="password" v-model="form.password" type="password" />
                            <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="password_confirmation">Confirmar Senha</Label>
                            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-end">
                        <Button :disabled="form.processing">Salvar Usuário</Button>
                    </CardFooter>
                </Card>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
