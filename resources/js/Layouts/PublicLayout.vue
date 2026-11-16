<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

// Esta é a parte crucial: quando este layout for montado,
// ele irá forçar o tema da página para o nosso tema fixo.
onMounted(() => {
    document.documentElement.setAttribute('data-theme', 'lupa-theme');
});
</script>

<template>
    <div class="flex flex-col min-h-screen bg-background text-foreground">
        <header
            class="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container flex items-center h-14 max-w-screen-2xl">
                <div class="flex mr-4">
                    <Link href="/" class="flex items-center space-x-2">
                    <ApplicationLogo class="w-auto h-8" />
                    <span class="font-bold">Clube Lupa</span>
                    </Link>
                </div>
                <div class="flex items-center justify-end flex-1 space-x-4">
                    <nav class="flex items-center space-x-2">
                        <Button variant="ghost" as-child>
                            <Link :href="route('login')">Entrar</Link>
                        </Button>
                        <Button as-child>
                            <Link :href="route('register.affiliate.create')">Seja um Afiliado</Link>
                        </Button>
                    </nav>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="py-6 border-t md:px-8 md:py-0">
            <div class="container flex flex-col items-center justify-between gap-4 md:h-24 md:flex-row">
                <p class="text-sm leading-loose text-center text-muted-foreground md:text-left">
                    Construído com ❤️ pela nossa equipe. &copy; {{ new Date().getFullYear() }} Clube Lupa. Todos os
                    direitos
                    reservados.
                </p>
            </div>
        </footer>
    </div>
</template>
