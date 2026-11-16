<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    status: number;
}>();

const title = computed(() => {
    return {
        503: 'Serviço Indisponível',
        500: 'Erro de Servidor',
        404: 'Página Não Encontrada',
        403: 'Acesso Proibido',
    }[props.status] || 'Ocorreu um erro';
});

const description = computed(() => {
    return {
        503: 'Desculpe, estamos em manutenção. Por favor, volte em breve.',
        500: 'Desculpe, algo deu errado nos nossos servidores. Nossa equipe já foi avisada e está trabalhando na soluçao para reestabelecer a situação o mais breve possível.',
        404: 'Desculpe, a página que você está procurando não foi encontrada.',
        403: 'Desculpe, você não tem permissão para acessar esta página.',
        400: 'Desculpe, este é um problema técnico no sistema. Nossa equipe já foi avisada e está trabalhando na soluçao para reestabelecer essa funcionalidade o mais breve possível. Agradecemos sua compreensão.',

    }[props.status] || 'Ocorreu um erro inesperado. Tente novamente mais tarde.';
});
</script>

<template>
    <div class="flex items-center justify-center min-h-screen p-6 bg-background text-foreground">
        <div class="w-full max-w-md text-center">
            <img src="/images/under_construction_cat.jpg" alt="Gatinho fofo pedindo desculpas"
                class="object-cover w-48 h-48 mx-auto rounded-full shadow-lg" />

            <h1 class="text-6xl font-extrabold tracking-tighter text-primary sm:text-8xl">
                {{ status }}
            </h1>
            <p class="mt-4 text-2xl font-semibold tracking-tight">
                {{ title }}
            </p>
            <p class="mt-4 text-muted-foreground">
                {{ description }}
            </p>
            <div class="mt-8">
                <Button as-child>
                    <Link :href="route('dashboard')">Voltar para o Início</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
