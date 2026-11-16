<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// O componente emitirá um evento para o pai quando o usuário quiser trocar para o modal de login
const emit = defineEmits(['openLogin']);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        // Se o registo for bem-sucedido, o Inertia irá redirecionar para o Dashboard
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <Label for="name">Nome</Label>
            <Input id="name" type="text" v-model="form.name" required autofocus autocomplete="name" />
            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
        </div>
        <div>
            <Label for="email">Email</Label>
            <Input id="email" type="email" v-model="form.email" required autocomplete="username" />
            <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</div>
        </div>
        <div>
            <Label for="password">Senha</Label>
            <Input id="password" type="password" v-model="form.password" required autocomplete="new-password" />
            <div v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</div>
        </div>
        <div>
            <Label for="password_confirmation">Confirmar Senha</Label>
            <Input id="password_confirmation" type="password" v-model="form.password_confirmation" required
                autocomplete="new-password" />
            <div v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-600">{{
                form.errors.password_confirmation }}</div>
        </div>
        <div class="flex items-center justify-end">
            <button type="button" @click="$emit('openLogin')" class="text-sm text-primary hover:underline">
                Já tem uma conta?
            </button>
        </div>
        <div>
            <Button class="w-full" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Registar
            </Button>
        </div>
    </form>
</template>
