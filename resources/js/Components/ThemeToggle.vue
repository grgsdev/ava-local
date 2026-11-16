<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Sun, Moon } from 'lucide-vue-next';

// O tema continua a ser lido dos props para sabermos o estado atual
const theme = computed(() => usePage().props.theme);

const toggleTheme = () => {
    const newTheme = theme.value === 'dark' ? 'light' : 'dark';
    router.post(route('theme.switch'), { theme: newTheme }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Button @click="toggleTheme" variant="ghost" size="icon">
        <Sun class="h-[1.2rem] w-[1.2rem] rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
        <Moon class="absolute h-[1.2rem] w-[1.2rem] rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
        <span class="sr-only">Alternar tema</span>
    </Button>
</template>
