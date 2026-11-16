<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { format, parse } from 'date-fns'
import { ptBR, enUS, de } from 'date-fns/locale'
// 1. Correção da importação do Maska
import { vMaska } from 'maska/vue'
// 2. Importação dos tipos e funções para conversão de data
import {
    type DateValue,
    getLocalTimeZone,
    today,
    CalendarDate,
} from '@internationalized/date'

import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Input } from '@/components/ui/input'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'

const props = withDefaults(defineProps<{
    modelValue?: Date | null
    locale?: 'pt-BR' | 'en-US' | 'de-DE'
}>(), {
    modelValue: null,
    locale: 'pt-BR',
})

const emits = defineEmits(['update:modelValue'])

const localeMap = {
    'pt-BR': { format: 'dd/MM/yyyy', mask: '##/##/####', dateFnsLocale: ptBR },
    'en-US': { format: 'MM/dd/yyyy', mask: '##/##/####', dateFnsLocale: enUS },
    'de-DE': { format: 'dd.MM.yyyy', mask: '##.##.####', dateFnsLocale: de },
}

const isPopoverOpen = ref(false)
const displayValue = ref<string>('')

const dateFormat = computed(() => localeMap[props.locale].format)
const dateMask = computed(() => localeMap[props.locale].mask)
const dateFnsLocale = computed(() => localeMap[props.locale].dateFnsLocale)

// --- Sincronização: Props -> Input ---
watch(() => props.modelValue, (newDate) => {
    if (newDate instanceof Date && !isNaN(newDate.getTime())) {
        displayValue.value = format(newDate, dateFormat.value, { locale: dateFnsLocale.value })
    } else {
        displayValue.value = ''
    }
}, { immediate: true })

// --- Sincronização: Input -> Props ---
watch(displayValue, (newStr) => {
    if (!newStr) {
        emits('update:modelValue', null)
        return
    }
    if (newStr.length !== dateFormat.value.length) {
        return
    }
    try {
        const parsedDate = parse(newStr, dateFormat.value, new Date())
        if (parsedDate.getTime() !== props.modelValue?.getTime()) {
            emits('update:modelValue', parsedDate)
        }
    } catch (error) {
        // Data inválida, não faz nada
    }
})

// 2. (Continuação) Propriedade computada para converter Date -> DateValue
const calendarDateValue = computed(() => {
    if (props.modelValue) {
        // Converte o objeto Date do v-model para um objeto CalendarDate que o componente Calendar entende
        return new CalendarDate(
            props.modelValue.getFullYear(),
            props.modelValue.getMonth() + 1,
            props.modelValue.getDate()
        )
    }
    return undefined
})

// 3. Correção do manipulador de eventos para receber DateValue
function handleDateSelect(selectedDate: DateValue | undefined) {
    if (selectedDate) {
        // Converte o DateValue do calendário de volta para um objeto Date nativo
        emits('update:modelValue', selectedDate.toDate(getLocalTimeZone()))
        isPopoverOpen.value = false
    }
}
</script>

<template>
    <Popover v-model:open="isPopoverOpen">
        <PopoverTrigger as-child>
            <Button variant="outline" :class="cn(
                'w-full justify-start text-left font-normal',
                !props.modelValue && 'text-muted-foreground',
            )">
                <CalendarIcon class="w-4 h-4 mr-2" />
                <span>{{ displayValue || 'Selecione uma data' }}</span>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0">
            <div class="p-3 border-t border-border">
                <p class="mb-2 text-sm text-muted-foreground">
                    Ou digite manualmente:
                </p>
                <Input v-model="displayValue" v-maska :data-maska="dateMask" :placeholder="dateFormat"
                    @focus="isPopoverOpen = true" />
            </div>
            <Calendar :model-value="calendarDateValue" @update:model-value="handleDateSelect" :locale="props.locale"
                initial-focus />

        </PopoverContent>
    </Popover>
</template>
