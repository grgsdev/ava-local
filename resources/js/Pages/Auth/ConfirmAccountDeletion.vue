<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
});

const submit = () => {
    form.post(route('account-deletion.destroy'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirmar Exclusão de Conta" />

        <Card class="w-full max-w-md text-center">
            <CardHeader>
                <CardTitle>Você tem a certeza absoluta?</CardTitle>
                <CardDescription>
                    Esta ação não pode ser desfeita. Todos os seus dados, incluindo pontos, moedas e medalhas, serão permanentemente apagados.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit">
                    <Button variant="destructive" class="w-full" :disabled="form.processing">
                        Sim, apagar a minha conta permanentemente
                    </Button>
                </form>
            </CardContent>
        </Card>
    </GuestLayout>
</template>
