<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { format, parse, isAfter, isBefore, startOfDay } from 'date-fns'
import { ptBR, enUS, de } from 'date-fns/locale'
import { vMaska } from 'maska/vue'
import {
    type DateValue,
    getLocalTimeZone,
    CalendarDate,
} from '@internationalized/date'

import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'

const props = withDefaults(defineProps<{
    modelValue?: { start: Date | null, end: Date | null } | null
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
const dateFormat = computed(() => localeMap[props.locale].format)
const dateMask = computed(() => localeMap[props.locale].mask)

const isPopoverOpen = ref(false)
const displayValueStart = ref('')
const displayValueEnd = ref('')

function handleDateSelect(value: DateValue | undefined) {
    if (!value) return

    const selectedDate = startOfDay(value.toDate(getLocalTimeZone()))
    const startDate = props.modelValue?.start
    const endDate = props.modelValue?.end

    if (startDate && endDate) {
        emits('update:modelValue', { start: selectedDate, end: null })
        return
    }

    if (!startDate || isBefore(selectedDate, startOfDay(startDate))) {
        emits('update:modelValue', { start: selectedDate, end: null })
    }
    else {
        emits('update:modelValue', { start: new Date(startDate.getTime()), end: selectedDate })
        isPopoverOpen.value = false
    }
}

watch(() => props.modelValue, (val) => {
    displayValueStart.value = val?.start ? format(val.start, dateFormat.value) : ''
    displayValueEnd.value = val?.end ? format(val.end, dateFormat.value) : ''
}, { immediate: true, deep: true })

watch(displayValueStart, (val) => {
    if (val.length !== 10) return
    try {
        const parsedDate = startOfDay(parse(val, dateFormat.value, new Date()))
        if (parsedDate.getTime() !== props.modelValue?.start?.getTime()) {
            emits('update:modelValue', { start: parsedDate, end: props.modelValue?.end ?? null })
        }
    } catch (e) {/* ignora */ }
})
watch(displayValueEnd, (val) => {
    if (val.length !== 10) return
    try {
        const parsedDate = startOfDay(parse(val, dateFormat.value, new Date()))
        if (parsedDate.getTime() !== props.modelValue?.end?.getTime()) {
            emits('update:modelValue', { start: props.modelValue?.start ?? null, end: parsedDate })
        }
    } catch (e) {/* ignora */ }
})

// ## A CORREÇÃO ##
// Em vez de retornar um array (que causa o erro), agora retornamos apenas a última data selecionada.
// Isso usa a API estável do componente para destacar uma única data, evitando o bug do modo de intervalo.
const calendarValue = computed(() => {
    const dateToHighlight = props.modelValue?.end || props.modelValue?.start
    if (!dateToHighlight) return undefined

    return new CalendarDate(
        dateToHighlight.getFullYear(),
        dateToHighlight.getMonth() + 1,
        dateToHighlight.getDate(),
    )
})

const displayRangeText = computed(() => {
    const start = props.modelValue?.start
    const end = props.modelValue?.end
    if (start && end) {
        return `${format(start, dateFormat.value)} - ${format(end, dateFormat.value)}`
    }
    if (start) {
        return `${format(start, dateFormat.value)} - `
    }
    return 'Selecione o período'
})
</script>

<template>
    <Popover v-model:open="isPopoverOpen">
        <PopoverTrigger as-child>
            <Button variant="outline" :class="cn(
                'w-full justify-start text-left font-normal',
                !modelValue?.start && 'text-muted-foreground',
            )">
                <CalendarIcon class="w-4 h-4 mr-2" />
                <span>{{ displayRangeText }}</span>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0">
            <div class="grid gap-3 p-3 pb-0">
                <div class="grid grid-cols-2 gap-2">
                    <div class="grid gap-1.5">
                        <Label for="start-date">Data Inicial</Label>
                        <Input id="start-date" v-model="displayValueStart" v-maska :data-maska="dateMask"
                            :placeholder="dateFormat" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="end-date">Data Final</Label>
                        <Input id="end-date" v-model="displayValueEnd" v-maska :data-maska="dateMask"
                            :placeholder="dateFormat" />
                    </div>
                </div>
            </div>
            <Calendar :model-value="calendarValue" @update:model-value="handleDateSelect" :locale="props.locale"
                initial-focus :number-of-months="2" />
        </PopoverContent>
    </Popover>
</template>
