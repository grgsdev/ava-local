<script setup lang="ts">
import { useFont, type FontTheme } from '@/Composables/useFont';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Check, Type } from 'lucide-vue-next'; // Usaremos o ícone 'Type' como gatilho
import { computed } from 'vue';

const { fontTheme, setFontTheme } = useFont();

// Definimos nossas opções de fonte aqui para usar no v-for
const fontOptions: { name: FontTheme; label: string }[] = [
    { name: 'sans', label: 'Padrão (Inter)' },
    { name: 'serif', label: 'Serifado (Lora)' },
    { name: 'mono', label: 'Mono (JetBrains)' },
    { name: 'jakarta', label: 'Geométrica (Jakarta)' },
    { name: 'newsreader', label: 'Editorial (Newsreader)' },
    { name: 'nunito', label: 'Arredondada (Nunito)' },
    { name: 'roboto', label: 'Moderna (Roboto Flex)' },
];

// Computada para mostrar o nome da fonte selecionada
const currentFontLabel = computed(() => {
    return fontOptions.find(f => f.name === fontTheme.value)?.label || 'Fonte';
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" aria-label="Alterar fonte">
                <Type class="w-5 h-5" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem v-for="option in fontOptions" :key="option.name" @click="setFontTheme(option.name)"
                class="cursor-pointer">
                <div class="flex items-center w-full">
                    <span>{{ option.label }}</span>
                    <Check v-if="fontTheme === option.name" class="w-4 h-4 ml-auto" />
                </div>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
