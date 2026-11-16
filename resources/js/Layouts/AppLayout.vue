<script setup lang="ts">
import { ref } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import NotificationsMenu from '@/Components/NotificationsMenu.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import { Menu, Search, ShoppingCart, User, Heart } from 'lucide-vue-next';

defineProps<{ title?: string }>();

const isMobileMenuOpen = ref(false);
</script>

<template>

    <Head :title="title" />

    <div class="flex flex-col min-h-screen bg-background">
        <div class="items-center justify-between hidden px-4 py-2 text-xs bg-slate-900 text-slate-200 md:flex">
            <div class="flex gap-4">
                <span>Fale Conosco: (00) 0000-0000</span>
                <span>Suporte Técnico</span>
            </div>
            <div class="flex gap-4">
                <Link href="/blog" class="transition hover:text-white">Blog</Link>
                <Link href="/cursos" class="transition hover:text-white">Meus Cursos</Link>
            </div>
        </div>

        <header
            class="border-b sticky top-0 z-30 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="container flex items-center justify-between gap-4 px-4 py-4 mx-auto">

                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon" class="md:hidden">
                            <Menu class="w-6 h-6" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left">
                        <nav class="flex flex-col gap-4 mt-8">
                            <Link href="/" class="text-lg font-medium">Home</Link>
                            <Link href="/products" class="text-lg font-medium">Produtos</Link>
                            <Link href="/cursos" class="text-lg font-medium">Cursos</Link>
                            <Link href="/blog" class="text-lg font-medium">Blog</Link>
                        </nav>
                    </SheetContent>
                </Sheet>

                <Link href="/" class="flex-shrink-0 text-2xl font-bold tracking-tighter">
                    Ava<span class="text-primary">Movie</span>
                </Link>

                <div class="relative flex-1 hidden max-w-2xl mx-4 md:flex">
                    <Input type="search" placeholder="O que você procura hoje?"
                        class="w-full pl-4 pr-10 transition-all border-transparent rounded-full bg-muted/50 focus:bg-background focus:border-primary" />
                    <Button size="icon" variant="ghost"
                        class="absolute right-1 top-0.5 rounded-full hover:bg-transparent">
                        <Search class="w-5 h-5 text-muted-foreground" />
                    </Button>
                </div>

                <div class="flex items-center gap-1 md:gap-2">
                    <Button variant="ghost" size="icon" class="md:hidden">
                        <Search class="w-5 h-5" />
                    </Button>

                    <ThemeToggle />

                    <template v-if="$page.props.auth.user">
                        <NotificationsMenu />

                        <Button variant="ghost" size="icon" class="hidden md:inline-flex">
                            <Heart class="w-5 h-5" />
                        </Button>

                        <Link href="/dashboard">
                            <Button variant="ghost" size="icon">
                                <User class="w-5 h-5" />
                            </Button>
                        </Link>
                    </template>

                    <template v-else>
                        <Link href="/login">
                            <Button variant="ghost" class="hidden md:inline-flex">Entrar</Button>
                        </Link>
                    </template>

                    <Button variant="outline" size="icon"
                        class="relative ml-1 rounded-full border-primary/20 hover:bg-primary/10 hover:text-primary">
                        <ShoppingCart class="w-5 h-5" />
                        <Badge
                            class="absolute -top-2 -right-2 h-5 w-5 flex items-center justify-center rounded-full p-0 text-[10px]">
                            0
                        </Badge>
                    </Button>
                </div>
            </div>
        </header>

        <div class="hidden border-b bg-muted/30 md:block">
            <div class="container px-4 mx-auto">
                <nav class="flex gap-6 py-3 overflow-x-auto text-sm font-medium">
                    <Link href="/products" class="hover:text-primary whitespace-nowrap">Todos os Produtos</Link>
                    <Link href="/category/eletro" class="hover:text-primary whitespace-nowrap">Lavadoras</Link>
                    <Link href="/category/jardinagem" class="hover:text-primary whitespace-nowrap">Aspiradores</Link>
                    <Link href="/category/ferramentas" class="hover:text-primary whitespace-nowrap">Ferramentas</Link>
                    <div class="flex-1"></div>
                    <Link href="/cursos" class="text-primary hover:underline whitespace-nowrap">Cursos AvaMovie Academy
                    </Link>
                </nav>
            </div>
        </div>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="py-12 mt-12 bg-slate-900 text-slate-300">
            <div class="container grid grid-cols-1 gap-8 px-4 mx-auto md:grid-cols-4">
                <div>
                    <h3 class="mb-4 font-bold text-white">AvaMovie</h3>
                    <p class="text-sm text-slate-400">Soluções completas para o seu dia a dia.</p>
                </div>
                <div>
                    <h3 class="mb-4 font-bold text-white">Institucional</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Sobre nós</a></li>
                        <li><a href="#" class="hover:text-white">Trabalhe conosco</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-4 font-bold text-white">Ajuda</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Central de Suporte</a></li>
                        <li><a href="#" class="hover:text-white">Política de Privacidade</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-4 font-bold text-white">Pagamento</h3>
                </div>
            </div>
        </footer>
    </div>
</template>
