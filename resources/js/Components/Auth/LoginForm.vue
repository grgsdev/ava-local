<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';

// O componente emitirá um evento 'close' para o pai fechar o modal
const emit = defineEmits(['close']);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            // Se o login for bem sucedido, o Inertia irá redirecionar.
            // Se falhar, os erros aparecerão, mas não fechamos o modal.
        },
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div v-if="form.errors.email" class="text-sm font-medium text-destructive">{{ form.errors.email }}</div>

        <div>
            <Label for="email">{{ $t('Email') }}</Label>
            <Input id="email" type="email" v-model="form.email" required autofocus autocomplete="username" />
        </div>
        <div>
            <Label for="password">{{ $t('Password') }}</Label>
            <Input id="password" type="password" v-model="form.password" required autocomplete="current-password" />
        </div>
        <div class="flex items-center justify-between">
            <label class="flex items-center">
                <Checkbox name="remember" v-model:checked="form.remember" /><span
                    class="ml-2 text-sm text-muted-foreground">Lembrar-me</span>
            </label>
            <Link v-if="route().has('password.request')" :href="route('password.request')"
                class="text-sm text-primary hover:underline">Esqueceu a senha?</Link>
        </div>
        <div class="flex items-center justify-end">
            <Button type="submit" class="w-full" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Entrar
            </Button>
        </div>
    </form>
</template>
