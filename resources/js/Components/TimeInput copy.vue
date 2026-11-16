<script setup lang="ts">
import { ref, watch } from 'vue';
import { vMaska } from "maska/vue";
import { Clock } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    modelValue: number | null; // ← ACEITA NULL
    placeholder?: string;
    class?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const inputValue = ref("");
const maskOptions = {
    mask: "##:##:##.###",
    eager: true
};

// Converte segundos para HH:MM:SS.mmm
const formatTime = (seconds: number | null) => {
    if (seconds === null || seconds === undefined) {
        return "";
    }

    const totalSeconds = Number(seconds);
    if (isNaN(totalSeconds) || totalSeconds < 0) return "";

    const h = Math.floor(totalSeconds / 3600);
    const m = Math.floor((totalSeconds % 3600) / 60);
    const s = Math.floor(totalSeconds % 60);
    const ms = Math.round((totalSeconds % 1) * 1000);

    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}.${ms.toString().padStart(3, '0')}`;
};

// Converte HH:MM:SS.mmm para segundos
const parseTime = (timeStr: string): number | null => {
    if (!timeStr || timeStr.length < 12 || timeStr.includes('_')) {
        return null;
    }

    const parts = timeStr.split(/[:.]/).map(Number);
    if (parts.length !== 4) return null;

    return parts[0] * 3600 + parts[1] * 60 + parts[2] + parts[3] / 1000;
};

// Quando modelValue muda de fora
watch(() => props.modelValue, (newVal) => {
    inputValue.value = formatTime(newVal);
}, { immediate: true });

// Quando usuário digita
const handleInput = (e: Event) => {
    const value = (e.target as HTMLInputElement).value;
    inputValue.value = value;

    // Se tem valor completo (sem placeholders _), emite
    const seconds = parseTime(value);
    emit('update:modelValue', seconds);
};
</script>

<template>
    <div class="relative w-full">
        <div class="absolute z-10 -translate-y-1/2 pointer-events-none left-3 top-1/2 text-muted-foreground">
            <Clock class="w-4 h-4" />
        </div> <!-- "Fake" Input do shadcn - copia as classes mas usa input nativo -->
        <input v-maska="maskOptions" :value="inputValue" @input="handleInput"
            :placeholder="placeholder || '00:00:00.000'" :class="cn(
                'pl-10 pr-3 font-mono tracking-wider w-full h-10 rounded-md border border-input bg-background py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                props.class
            )" type="text" />
    </div>
</template>
