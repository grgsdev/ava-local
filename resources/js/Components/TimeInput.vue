<script setup lang="ts">
import { ref, watch, nextTick } from 'vue';
import { Clock } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    modelValue: number | null;
    placeholder?: string;
    class?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const inputRef = ref<HTMLInputElement | null>(null);
const inputValue = ref("00:00:00.000");

// Garante que o input sempre tenha a estrutura rígida de 12 caracteres
const formatTime = (seconds: number | null) => {
    if (seconds === null || seconds === undefined || isNaN(Number(seconds))) {
        return "00:00:00.000";
    }

    const totalSeconds = Math.max(0, Number(seconds));
    const h = Math.floor(totalSeconds / 3600);
    const m = Math.floor((totalSeconds % 3600) / 60);
    const s = Math.floor(totalSeconds % 60);
    const ms = Math.round((totalSeconds % 1) * 1000);

    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}.${ms.toString().padStart(3, '0')}`;
};

const parseTime = (timeStr: string): number | null => {
    if (!timeStr || timeStr.length !== 12) return null;
    const parts = timeStr.split(/[:.]/).map(Number);
    if (parts.length !== 4 || parts.some(isNaN)) return null;
    return parts[0] * 3600 + parts[1] * 60 + parts[2] + parts[3] / 1000;
};

watch(() => props.modelValue, (newVal) => {
    const currentParsed = parseTime(inputValue.value);
    if (newVal !== currentParsed) {
        inputValue.value = formatTime(newVal);
    }
}, { immediate: true });

const emitChange = () => {
    const seconds = parseTime(inputValue.value);
    if (seconds !== null) {
        emit('update:modelValue', seconds);
    }
};

// 🔥 A MÁGICA: Controle das setas para aumentar/diminuir blocos específicos
const handleArrowKeys = (e: KeyboardEvent, delta: number, el: HTMLInputElement) => {
    e.preventDefault();
    let pos = el.selectionStart || 0;
    let currentSeconds = parseTime(inputValue.value) ?? 0;

    let step = 0;
    if (pos <= 2) step = 3600;        // Horas
    else if (pos <= 5) step = 60;     // Minutos
    else if (pos <= 8) step = 1;      // Segundos
    else step = 0.001;                // Milissegundos

    // Shift + Seta = Pula de 10 em 10 (ex: 10 segundos, 10 minutos, ou 10 milissegundos)
    if (e.shiftKey) {
        if (step === 0.001) step = 0.010;
        else step *= 10;
    }

    let newVal = Math.max(0, currentSeconds + (delta * step));
    inputValue.value = formatTime(newVal);
    emitChange();

    // Mantém o cursor travado no bloco que o usuário está editando
    nextTick(() => {
        let s = 0, end = 0;
        if (pos <= 2) { s = 0; end = 2; }
        else if (pos <= 5) { s = 3; end = 5; }
        else if (pos <= 8) { s = 6; end = 8; }
        else { s = 9; end = 12; }
        el.setSelectionRange(s, end);
    });
};

// 🔥 MOTOR DE OVERWRITE: Sobrescreve sem empurrar a string
const handleKeyDown = (e: KeyboardEvent) => {
    const el = inputRef.value;
    if (!el) return;

    if (e.key === 'ArrowUp') return handleArrowKeys(e, 1, el);
    if (e.key === 'ArrowDown') return handleArrowKeys(e, -1, el);

    // Permite atalhos do navegador (Ctrl+C, Tab, ArrowLeft/Right)
    if (e.ctrlKey || e.metaKey || e.altKey || e.key.length > 1) {
        return;
    }

    // Se o usuário digitou um número (0 a 9)
    if (/^\d$/.test(e.key)) {
        e.preventDefault();
        let start = el.selectionStart || 0;

        // Pula os separadores (: ou .) automaticamente
        if (start === 2 || start === 5 || start === 8) start++;

        if (start < 12) {
            const chars = inputValue.value.split('');
            chars[start] = e.key;
            inputValue.value = chars.join('');

            emitChange();

            // Avança o cursor para o próximo caractere livre
            let nextPos = start + 1;
            if (nextPos === 2 || nextPos === 5 || nextPos === 8) nextPos++;

            nextTick(() => { el.setSelectionRange(nextPos, nextPos); });
        }
    }
    // Retrocesso amigável
    else if (e.key === 'Backspace') {
        e.preventDefault();
        let start = el.selectionStart || 0;
        let end = el.selectionEnd || 0;

        // Se estiver apenas piscando o cursor, volta e zera o número anterior
        if (start === end && start > 0) {
            let prevPos = start - 1;
            if (prevPos === 2 || prevPos === 5 || prevPos === 8) prevPos--;

            const chars = inputValue.value.split('');
            chars[prevPos] = '0';
            inputValue.value = chars.join('');

            emitChange();
            nextTick(() => { el.setSelectionRange(prevPos, prevPos); });
        }
        // Se houver um bloco selecionado (ex: o usuário selecionou os minutos inteiros)
        else if (end > start) {
            const chars = inputValue.value.split('');
            for (let i = start; i < end; i++) {
                if (i !== 2 && i !== 5 && i !== 8) chars[i] = '0';
            }
            inputValue.value = chars.join('');
            emitChange();
            nextTick(() => { el.setSelectionRange(start, start); });
        }
    } else {
        // Bloqueia qualquer caractere que não seja número
        e.preventDefault();
    }
};

const handlePaste = (e: ClipboardEvent) => {
    e.preventDefault();
    const text = e.clipboardData?.getData('text');
    if (text) {
        const parsed = parseTime(text);
        if (parsed !== null) {
            inputValue.value = formatTime(parsed);
            emitChange();
        }
    }
};

const handleBlur = () => {
    inputValue.value = formatTime(props.modelValue);
};
</script>

<template>
    <div class="relative w-full">
        <div class="absolute z-10 -translate-y-1/2 pointer-events-none left-3 top-1/2 text-muted-foreground">
            <Clock class="w-4 h-4" />
        </div>
        <input ref="inputRef" :value="inputValue" @keydown="handleKeyDown" @paste="handlePaste" @blur="handleBlur"
            :placeholder="placeholder || '00:00:00.000'" :class="cn(
                'pl-10 pr-3 font-mono tracking-wider w-full h-10 rounded-md border border-input bg-background py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                props.class
            )" type="text" />
    </div>
</template>
