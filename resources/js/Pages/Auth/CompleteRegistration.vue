<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import Datepicker from "@/Components/Datepicker.vue";

const props = defineProps<{
    email: string;
    name: string;
}>();

const form = useForm({
    password: '',
    password_confirmation: '',
    phone_number: '',
    birth_date: null,
});

// A rota precisa do ID do usuário, que podemos extrair da URL atual
const userId = window.location.pathname.split('/').pop();
const invitationQuery = window.location.search;

const submit = () => {
    form.post(route('registration.store-completion', userId) + invitationQuery);
};
</script>

<template>

    <Head title="Finalizar Cadastro" />
    <div class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="w-full max-w-md p-4">
            <h1 class="mb-6 text-3xl font-bold text-center">Finalize seu Cadastro</h1>
            <Card>
                <CardHeader>
                    <CardTitle>Bem-vindo(a), {{ name }}!</CardTitle>
                    <CardDescription>
                        Seu e-mail é <span class="font-semibold">{{ email }}</span>. Por favor, complete seus dados e
                        defina uma senha para ativar sua conta.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-1.5">
                            <Label for="phone_number">Telefone</Label>
                            <Input id="phone_number" v-model="form.phone_number" required />
                            <p v-if="form.errors.phone_number" class="text-sm text-red-500">{{ form.errors.phone_number
                            }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="birth_date">Data de Nascimento</Label>
                            <Datepicker id="birth_date" v-model="form.birth_date" locale="pt-BR" required />
                            <p v-if="form.errors.birth_date" class="text-sm text-red-500">{{ form.errors.birth_date }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="password">Senha</Label>
                            <Input id="password" type="password" v-model="form.password" required />
                            <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="password_confirmation">Confirmar Senha</Label>
                            <Input id="password_confirmation" type="password" v-model="form.password_confirmation"
                                required />
                        </div>

                        <Button type="submit" class="w-full" :disabled="form.processing">
                            Finalizar Cadastro e Acessar
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
