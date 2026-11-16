<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle2 } from 'lucide-vue-next';

</script>

<template>
    <GuestLayout>
        <Head title="Conta Apagada" />

        <Card class="w-full max-w-md text-center">
            <CardHeader>
                <div class="flex justify-center">
                    <CheckCircle2 class="w-16 h-16 text-green-500" />
                </div>
                <CardTitle class="mt-4">Conta Apagada com Sucesso</CardTitle>
                <CardDescription>
                    A sua conta e todos os seus dados foram removidos do Lupa. Esperamos vê-lo novamente no futuro.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <!-- <Button as-child class="w-full">
                    <Link :href="route('landing')">Voltar à Página Inicial</Link>
                </Button> -->
            </CardContent>
        </Card>
    </GuestLayout>
</template>
