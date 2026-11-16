<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Wrench } from 'lucide-vue-next';

// A página recebe o título da funcionalidade que está em construção
const props = defineProps<{
    featureTitle?: string;
}>();
</script>

<template>

    <Head :title="`Em Construção: ${featureTitle}`" />

    <AuthenticatedLayout>
        <div class="flex flex-col items-center justify-center h-full text-center">
            <div class="w-full max-w-md">
                <img src="/images/under_construction_cat.jpg" alt="Gatinho fofo pedindo desculpas"
                    class="object-cover w-48 h-48 mx-auto rounded-full shadow-lg" />

                <h1 class="mt-8 text-3xl font-extrabold tracking-tight text-foreground">
                    Oops! Esta área ainda está em construção.
                </h1>

                <p class="mt-4 text-lg text-muted-foreground">
                    Nossa equipe de gatinhos engenheiros está trabalhando duro para finalizar a funcionalidade de
                    <span v-if="featureTitle" class="font-semibold text-primary">{{ featureTitle }}</span>
                    <span v-else>esta página</span>.
                </p>
                <p class="mt-2 text-muted-foreground">
                    Por favor, volte em breve!
                </p>

                <div class="mt-8">
                    <Button as-child variant="outline">
                        <Link :href="route('dashboard')">
                        <Wrench class="w-4 h-4 mr-2" />
                        Voltar para o Dashboard
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>