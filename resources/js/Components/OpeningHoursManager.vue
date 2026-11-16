<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PlusCircle, Trash2 } from 'lucide-vue-next';
import { Card, CardContent } from '@/components/ui/card';
import { vMaska } from 'maska/vue'; // Importa a diretiva da máscara

// --- ESTRUTURA DE DADOS ATUALIZADA ---
// Agora 'times' é um array de objetos com 'from' e 'to'
interface TimePair {
    from: string;
    to: string;
}
interface HourEntry {
    day: string;
    times: TimePair[];
}

const props = defineProps<{
    modelValue: Record<string, string[]>;
}>();
const emit = defineEmits(['update:modelValue']);
const timeMaskOptions = reactive({
    mask: '##:##',
    /**
     * postProcess é uma função que executa após cada alteração no input.
     * Ela nos permite corrigir valores inválidos em tempo real.
     */
    postProcess: (value: string) => {
        const parts = value.split(':');
        const hours = parts[0];
        const minutes = parts[1];

        // Se a hora digitada for maior que 23, corrige para 23.
        if (hours && parseInt(hours, 10) > 23) {
            parts[0] = '23';
        }

        // Se os minutos digitados forem maiores que 59, corrige para 59.
        if (minutes && parseInt(minutes, 10) > 59) {
            parts[1] = '59';
        }

        // Retorna o valor corrigido para o input.
        return parts.join(':');
    },
});
const allDays = [
    'segunda-feira', 'terca-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sabado', 'domingo', 'feriados'
];

const hoursList = ref<HourEntry[]>([]);

// --- FUNÇÃO DE CONVERSÃO ATUALIZADA (EXTERNO -> INTERNO) ---
// Converte "09:00-18:00" para { from: '09:00', to: '18:00' }
const objectToArray = (obj: Record<string, string[]>) => {
    if (!obj || Object.keys(obj).length === 0) return [];

    // 1. Pega as chaves (dias) do objeto recebido.
    const receivedDays = Object.keys(obj);

    // 2. Ordena essas chaves com base na nossa ordem pré-definida 'allDays'.
    //    A função de comparação usa o índice de cada dia no array 'allDays' para determinar a ordem.
    const sortedDays = receivedDays.sort((a, b) => allDays.indexOf(a) - allDays.indexOf(b));

    // 3. Mapeia sobre os dias JÁ ORDENADOS para criar o array na ordem correta.
    return sortedDays.map(day => ({
        day,
        times: obj[day].map(t => {
            const parts = t.split('-');
            return { from: parts[0] || '', to: parts[1] || '' };
        })
    }));
};
// --- FUNÇÃO DE CONVERSÃO ATUALIZADA (INTERNO -> EXTERNO) ---
// Converte { from: '09:00', to: '18:00' } de volta para "09:00-18:00"
const arrayToObject = (arr: HourEntry[]) => {
    return arr.reduce((acc, item) => {
        if (item.day) {
            const validTimes = item.times
                .filter(time => time.from.length === 5 && time.to.length === 5) // Garante que ambos os campos estão preenchidos
                .map(time => `${time.from}-${time.to}`);

            if (validTimes.length > 0) {
                acc[item.day] = validTimes;
            }
        }
        return acc;
    }, {} as Record<string, string[]>);
};

watch(() => props.modelValue, (newValue) => {
    const internalStateAsObject = arrayToObject(hoursList.value);
    if (JSON.stringify(newValue) === JSON.stringify(internalStateAsObject)) {
        return;
    }
    hoursList.value = objectToArray(newValue || {});
}, { immediate: true });

watch(hoursList, () => {
    emit('update:modelValue', arrayToObject(hoursList.value));
}, { deep: true });

const addDay = () => {
    if (hoursList.value.length < allDays.length) {
        // Adiciona um par de horários vazio ao criar um novo dia
        hoursList.value.push({ day: '', times: [{ from: '', to: '' }] });
    }
};

const removeDay = (index: number) => {
    hoursList.value.splice(index, 1);
};

const addTime = (dayIndex: number) => {
    if (hoursList.value[dayIndex].times.length < 4) {
        // Adiciona um novo objeto de par de horários
        hoursList.value[dayIndex].times.push({ from: '', to: '' });
    }
};

const removeTime = (dayIndex: number, timeIndex: number) => {
    if (hoursList.value[dayIndex].times.length > 1) {
        hoursList.value[dayIndex].times.splice(timeIndex, 1);
    } else {
        // Apenas limpa o par de horários
        hoursList.value[dayIndex].times[0] = { from: '', to: '' };
    }
};

const availableDays = (currentIndex: number) => {
    const selectedDays = hoursList.value
        .map(h => h.day)
        .filter((day, index) => index !== currentIndex && day);
    return allDays.filter(day => !selectedDays.includes(day));
};
</script>

<template>
    <div class="space-y-4">
        <Card v-for="(hour, dayIndex) in hoursList" :key="dayIndex" class="p-4">
            <CardContent class="p-0">
                <div class="flex flex-col gap-4 md:flex-row md:items-start">
                    <div class="w-full space-y-1.5 md:w-1/3">
                        <Label>Dia da Semana</Label>
                        <Select v-model="hour.day">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecione um dia..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-if="hour.day" :value="hour.day">{{ hour.day }}</SelectItem>
                                <SelectItem v-for="day in availableDays(dayIndex)" :key="day" :value="day">
                                    {{ day }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex-grow space-y-2">
                        <Label>Horários (até 4 pares)</Label>
                        <div v-for="(timePair, timeIndex) in hour.times" :key="timeIndex"
                            class="flex items-center gap-2">
                            <Input v-model="timePair.from" v-maska="timeMaskOptions" placeholder="HH:mm" />
                            <span>até</span>
                            <Input v-model="timePair.to" v-maska="timeMaskOptions" placeholder="HH:mm" />
                            <Button type="button" variant="ghost" size="icon" @click="removeTime(dayIndex, timeIndex)">
                                <Trash2 class="w-4 h-4 text-red-500" />
                            </Button>
                        </div>
                        <Button v-if="hour.times.length < 4" type="button" variant="outline" size="sm"
                            @click="addTime(dayIndex)">
                            <PlusCircle class="w-4 h-4 mr-2" />
                            Adicionar par de horários
                        </Button>
                    </div>

                    <div class="pt-6">
                        <Button type="button" variant="destructive" @click="removeDay(dayIndex)">Remover Dia</Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Button type="button" @click="addDay" variant="secondary" :disabled="hoursList.length >= allDays.length">
            <PlusCircle class="w-4 h-4 mr-2" />
            Adicionar Dia de Funcionamento
        </Button>
    </div>
</template>
