<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { UserPlus, Store, PartyPopper } from 'lucide-vue-next';

</script>

<template>

    <Head title="Bem-vindo" />
    <PublicLayout>
        <section class="py-20 text-center bg-secondary">
            <div class="container">
                <div class="max-w-3xl mx-auto">
                    <h1 class="text-4xl font-extrabold tracking-tight lg:text-5xl">
                        O primeiro do país
                    </h1>
                    <p class="mt-4 text-lg text-muted-foreground">
                        Focado em .
                    </p>
                    <div class="mt-8">
                        <Button size="lg" as-child class="rounded-full">
                            <Link href="#">Quero ser Sócio</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-20">
            <div class="container">
                <div class="max-w-5xl mx-auto">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold tracking-tight">Como Funciona?</h2>
                        <p class="mt-2 text-lg text-muted-foreground">
                            Tudo o que você precisa para viver experiências incríveis em 3 passos simples.
                        </p>
                    </div>

                    <div class="grid gap-12 mt-12 md:grid-cols-3">
                        <Card
                            class="p-8 text-center transition-all duration-300 border-2 shadow-lg rounded-xl hover:shadow-2xl hover:-translate-y-2">
                            <div
                                class="inline-flex items-center justify-center w-16 h-16 mx-auto mb-6 rounded-full bg-secondary">
                                <UserPlus class="w-8 h-8 text-secondary-foreground" />
                            </div>
                            <h3 class="mb-2 text-xl font-bold">1. Faça a Assinatura</h3>
                            <p class="text-muted-foreground">Escolha o seu plano e junte-se à nossa comunidade
                                exclusiva.</p>
                        </Card>

                        <Card
                            class="p-8 text-center transition-all duration-300 border-2 shadow-lg rounded-xl hover:shadow-2xl hover:-translate-y-2">
                            <div
                                class="inline-flex items-center justify-center w-16 h-16 mx-auto mb-6 rounded-full bg-secondary">
                                <Store class="w-8 h-8 text-secondary-foreground" />
                            </div>
                            <h3 class="mb-2 text-xl font-bold">2. Conheça os Afiliados</h3>
                            <p class="text-muted-foreground">Explore mais de 120 estabelecimentos selecionados na nossa
                                app.</p>
                        </Card>

                        <Card
                            class="p-8 text-center transition-all duration-300 border-2 shadow-lg rounded-xl hover:shadow-2xl hover:-translate-y-2">
                            <div
                                class="inline-flex items-center justify-center w-16 h-16 mx-auto mb-6 rounded-full bg-secondary">
                                <PartyPopper class="w-8 h-8 text-secondary-foreground" />
                            </div>
                            <h3 class="mb-2 text-xl font-bold">3. Aproveite</h3>
                            <p class="text-muted-foreground">Use os seus benefícios e viva experiências únicas quantas
                                vezes quiser.</p>
                        </Card>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-20 bg-secondary">
            <div class="container">
                <div class="max-w-3xl mx-auto">
                    <h2 class="mb-12 text-3xl font-bold text-center">O que dizem sobre nós</h2>
                    <div class="space-y-8">
                        <blockquote class="p-6 text-center border-l-4 border-primary bg-background">
                            <p class="italic">"O Lupa transformou a forma como descubro a minha cidade. Cada fim de
                                semana é uma nova aventura!"</p>
                            <footer class="mt-4 font-semibold">- Ana, Sócia Lupa</footer>
                        </blockquote>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
