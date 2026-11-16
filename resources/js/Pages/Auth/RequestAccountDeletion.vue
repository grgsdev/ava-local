<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { computed } from 'vue';
// defineProps<{
//     status?: string;
// }>();
const flashMessage = computed(() => usePage().props.flash.message);
const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('account-deletion.send-link'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Solicitar Exclusão de Conta" />

        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <CardTitle>Excluir Conta</CardTitle>
                <CardDescription>
                    Lamentamos vê-lo partir. Insira o seu e-mail para receber um link de confirmação para apagar a sua conta permanentemente.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="flashMessage" class="mb-4 text-sm font-medium text-green-600">
                    {{ flashMessage }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-1.5">
                        <Label for="email">E-mail</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                        />
                        <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        Enviar Link de Exclusão
                    </Button>
                </form>
            </CardContent>
             <CardFooter class="flex justify-center pt-6">
                <!-- <Button variant="link" as-child>
                    <Link :href="route('login')">
                        Voltar para o Login
                    </Link>
                </Button> -->
            </CardFooter>
        </Card>
    </GuestLayout>
</template>
