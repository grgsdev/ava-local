<script setup lang="ts">
import NotificationDropdown from '@/Components/Admin/NotificationDropdown.vue';
import SidebarItem from '@/Components/Admin/SidebarItem.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import CommandMenu from '@/Components/CommandMenu.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import FontSwitcher from '@/Components/FontSwitcher.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { menu } from '@/menu.js';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Building2, ChevronsLeft, Menu } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();

const sidebarOpen = ref(true); // Para telas grandes (recolhido/expandido)
const mobileSidebarOpen = ref(false); // NOVO: Para telas pequenas (aberto/fechado)
const openCommandMenu = ref(false);



const applyTheme = (theme: 'dark' | 'light') => {
    const htmlEl = document.documentElement;
    if (theme === 'dark') {
        htmlEl.classList.add('dark');
    } else {
        htmlEl.classList.remove('dark');
    }
};


watch(
    () => page.props.theme,
    (newTheme) => {
        applyTheme(newTheme);
    },
    {
        immediate: true,
    },
);

const isLoggingOut = ref(false);
const logout = () => {
    router.post(
        route('logout'),
        {},
        {
            onStart: () => {
                isLoggingOut.value = true;
            },
        },
    );
};

// --- LÓGICA DO MENU DE COMANDO ---
const handleKeyDown = (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        openCommandMenu.value = true;
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <template v-if="!isLoggingOut">
        <div class="h-screen bg-zinc-100 dark:bg-zinc-950">
            <div class="flex w-full h-full p-0 overflow-hidden shadow-none md:p-4 lg:p-6 md:rounded-xl md:shadow-2xl">
                <aside :class="[
                    'hidden flex-shrink-0 flex-col lg:flex',
                    'bg-card text-card-foreground',
                    'transition-all duration-300',
                    sidebarOpen ? 'w-64' : 'w-20',
                ]">
                    <div class="flex items-center justify-between h-16 px-4 border-b dark:border-gray-700">
                        <Link :href="route('dashboard')">
                            <template v-if="sidebarOpen">
                                <img v-if="$page.props.platformSettings?.logo_web_url"
                                    :src="$page.props.platformSettings.logo_web_url" alt="Logo"
                                    class="block object-contain w-auto h-20" />
                                <ApplicationLogo v-else
                                    class="block w-auto h-20 text-gray-800 fill-current dark:text-gray-200" />
                            </template>
                            <template v-else>
                                <img v-if="$page.props.platformSettings?.logo_mobile_url"
                                    :src="$page.props.platformSettings.logo_mobile_url" alt="Logo Icon"
                                    class="w-auto h-9" />
                                <ApplicationLogo v-else
                                    class="block w-auto text-gray-800 fill-current h-9 dark:text-gray-200" />
                            </template>
                        </Link>
                        <button @click="sidebarOpen = !sidebarOpen"
                            class="hidden p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 lg:block">
                            <ChevronsLeft :size="20" class="text-gray-500 transition-transform duration-300"
                                :class="{ 'rotate-180': !sidebarOpen }" />
                        </button>
                    </div>
                    <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                        <SidebarItem v-for="item in menu" :key="item.name" :item="item" :sidebarOpen="sidebarOpen" />
                    </nav>
                </aside>

                <div v-if="mobileSidebarOpen" class="fixed inset-0 z-50 flex lg:hidden" role="dialog" aria-modal="true">
                    <div @click="mobileSidebarOpen = false" class="fixed inset-0 bg-black/60"></div>
                    <aside class="relative flex flex-col flex-shrink-0 w-64 bg-card text-card-foreground">
                        <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                            <SidebarItem v-for="item in menu" :key="item.name" :item="item" :sidebarOpen="true" />
                        </nav>
                    </aside>
                </div>

                <div class="flex flex-col flex-1 overflow-hidden">
                    <header
                        class="flex items-center justify-between flex-shrink-0 h-16 px-6 border-b bg-card text-card-foreground dark:border-gray-700">
                        <div class="flex items-center">
                            <button @click="mobileSidebarOpen = true"
                                class="p-2 -ml-2 rounded-md lg:hidden hover:bg-accent hover:text-accent-foreground">
                                <Menu :size="24" />
                            </button>

                            <button @click="openCommandMenu = true"
                                class="items-center justify-between hidden w-full max-w-xs p-2 text-sm text-left border rounded-md text-muted-foreground hover:bg-accent sm:flex">
                                <span>{{ $t('Search anything...') }}</span>
                                <kbd
                                    class="ml-4 hidden rounded border bg-muted px-1.5 py-1 text-xs font-medium sm:inline-block">⌘
                                    K</kbd>
                            </button>
                            <Badge v-if="page.props.active_company" variant="secondary"
                                class="hidden gap-2 ml-3 sm:inline-flex">
                                <Building2 class="w-3.5 h-3.5" />
                                <span>Empresa: {{ page.props.active_company.name }}</span>
                            </Badge>
                        </div>

                        <div class="flex items-center space-x-2 md:space-x-4">
                            <FontSwitcher />
                            <NotificationDropdown />
                            <LanguageSwitcher />
                            <ThemeToggle />
                            <ThemeSwitcher />

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <button
                                        class="flex items-center p-1 rounded-full hover:bg-accent focus:outline-none">
                                        <Avatar class="w-8 h-8">
                                            <AvatarImage :src="page.props.auth.user.avatar_url ?? ''"
                                                :alt="page.props.auth.user.name" />
                                            <AvatarFallback>{{ page.props.auth.user.name.substring(0, 2) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="hidden ml-3 text-sm font-semibold md:block">
                                            {{ page.props.auth.user.name }}
                                        </div>
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-56">
                                    <DropdownMenuLabel>{{ $t('My Account') }}</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem as-child>
                                        <Link :href="route('profile.edit')">{{ $t('Profile') }}</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @select="logout"
                                        class="w-full text-left text-red-600 cursor-pointer focus:text-red-600">
                                        {{ $t('Log Out') }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </header>

                    <main class="relative flex flex-col flex-1 p-4 overflow-y-auto">
                        <Breadcrumbs class="mb-4" />
                        <FlashMessage />
                        <slot />
                    </main>
                </div>
            </div>
        </div>
    </template>
    <CommandMenu v-model:open="openCommandMenu" />
</template>
