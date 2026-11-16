<script setup lang="ts">
import { useTheme, type Theme } from '@/Composables/useTheme';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Check, Palette } from 'lucide-vue-next';
import { computed } from 'vue';

const { theme, setTheme } = useTheme();

// Definimos uma interface para o nosso objeto de tema
interface ThemeOption {
    name: Theme;
    label: string;
    color: string;
}

// Aplicamos a interface ao nosso array de temas
const themes: ThemeOption[] = [
    { name: 'slate', label: 'Padrão', color: 'bg-slate-500' },
    { name: 'stone', label: 'Pedra', color: 'bg-stone-500' }, // Novo
    { name: 'zinc', label: 'Cinza Frio', color: 'bg-zinc-500' },
    { name: 'green', label: 'Verde', color: 'bg-green-500' },
    { name: 'lime', label: 'Lima', color: 'bg-lime-500' }, // Novo
    { name: 'teal', label: 'Verde-azulado', color: 'bg-teal-500' }, // Novo
    { name: 'sky', label: 'Céu', color: 'bg-sky-500' },
    { name: 'cyan', label: 'Ciano', color: 'bg-cyan-500' },
    { name: 'indigo', label: 'Índigo', color: 'bg-indigo-500' },
    { name: 'violet', label: 'Violeta', color: 'bg-violet-500' },
    { name: 'lavender', label: 'Lavanda', color: 'bg-violet-400' },
    { name: 'rose', label: 'Rosa', color: 'bg-rose-500' },
    { name: 'peach', label: 'Pêssego', color: 'bg-orange-400' },
    { name: 'amber', label: 'Âmbar', color: 'bg-amber-500' }, // Novo
    { name: 'orange', label: 'Laranja', color: 'bg-orange-500' },
];

const currentThemeLabel = computed(() => {
    return themes.find((t) => t.name === theme.value)?.label || 'Tema';
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" aria-label="Alterar tema de cores">
                <Palette class="w-5 h-5" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem v-for="t in themes" :key="t.name" @click="setTheme(t.name)" class="cursor-pointer">
                <div class="flex items-center w-full">
                    <div class="w-4 h-4 mr-2 rounded-full" :class="t.color" />
                    <span>{{ t.label }}</span>
                    <Check v-if="theme === t.name" class="w-4 h-4 ml-auto" />
                </div>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
