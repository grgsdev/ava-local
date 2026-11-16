<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Badge } from '@/components/ui/badge';
import axios from 'axios';
import { debounce } from 'lodash';
import { Check, ChevronsUpDown, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
interface Option {
    id: number | string;
    name: string;
}
const props = defineProps<{
    modelValue: number | number[] | null,
    apiUrl: string,
    multiple?: boolean,
    placeholder?: string,
    initialOptions?: Option[],
}>();


const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const isLoading = ref(false);
const searchQuery = ref('');
//const options = ref([]);
const page = ref(1);
const lastPage = ref(1);
const options = ref<Option[]>(props.initialOptions || []);
const fetchOptions = async (query = '', loadMore = false) => {
    isLoading.value = true;
    try {
        const response = await axios.get(props.apiUrl, {
            params: { search: query, page: page.value },
        });
        if (loadMore) {
            options.value.push(...response.data.data);
        } else {
            options.value = response.data.data;
        }
        lastPage.value = response.data.last_page;
    } catch (error) {
        console.error('Erro ao buscar opções:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleScroll = (event: Event) => {
    const listEl = event.target as HTMLElement | null;
    if (
        listEl &&
        listEl.scrollTop + listEl.clientHeight >= listEl.scrollHeight - 50 &&
        !isLoading.value &&
        page.value < lastPage.value
    ) {
        page.value++;
        fetchOptions(searchQuery.value, true);
    }
};

const handleSelect = (option: Option) => {
    if (props.multiple) {
        const selected = new Set(props.modelValue as number[]);
        if (selected.has(option.id as number)) {
            selected.delete(option.id as number);
        } else {
            selected.add(option.id as number);
        }
        emit('update:modelValue', Array.from(selected));
    } else {
        emit('update:modelValue', option.id);
        isOpen.value = false;
    }
};
const removeItem = (idToRemove: number | string) => {
    if (props.multiple) {
        const currentSelection = props.modelValue as number[];
        const newSelection = currentSelection.filter(id => id !== idToRemove);
        emit('update:modelValue', newSelection);
    }
};

// NOVO: Computada para encontrar os OBJETOS completos das opções selecionadas
const selectedOptions = computed(() => {
    if (!props.modelValue || (props.multiple && (props.modelValue as number[]).length === 0)) {
        return [];
    }
    if (props.multiple) {
        const selectedIds = new Set(props.modelValue as number[]);
        return options.value.filter(opt => selectedIds.has(opt.id as number));
    }
    const singleOption = options.value.find(opt => opt.id === props.modelValue);
    return singleOption ? [singleOption] : [];
});

const singleSelectedLabel = computed(() => {
    if (props.multiple || !props.modelValue) return props.placeholder;
    return selectedOptions.value[0]?.name || props.placeholder;
});

watch(
    searchQuery,
    debounce((newValue) => {
        page.value = 1;
        fetchOptions(newValue, false);
    }, 300),
);

watch(isOpen, (newValue) => {
    if (newValue && options.value.length === 0) {
        page.value = 1;
        fetchOptions('', false);
    }
});


const selectedLabels = computed(() => {
    if (
        !props.modelValue ||
        (Array.isArray(props.modelValue) && props.modelValue.length === 0)
    )
        return props.placeholder;
    if (!props.multiple) {
        return (
            options.value.find((opt) => opt.id === props.modelValue)?.name ||
            props.placeholder
        );
    }
    return options.value
        .filter((opt) => Array.isArray(props.modelValue) && props.modelValue.includes(Number(opt.id)))
        .map((opt) => opt.name)
        .join(', ');
});
</script>

<template>
    <!-- <div class="relative"> -->
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button variant="outline" role="combobox" class="justify-between w-full font-normal">
                <template v-if="multiple">
                    <div v-if="selectedOptions.length > 0" class="flex flex-wrap items-center gap-1">
                        <Badge v-for="option in selectedOptions" :key="option.id" variant="secondary"
                            class="flex items-center gap-1.5">
                            {{ $t(option.name) }}
                            <button @click.stop.prevent="removeItem(option.id)"
                                class="rounded-full hover:bg-destructive/20 focus:outline-none">
                                <X class="w-3 h-3 text-destructive" />
                            </button>
                        </Badge>
                    </div>
                    <span v-else class="text-card-foreground">{{ placeholder }}</span>
                </template>
                <template v-else>
                    <span class="truncate text-card-foreground">{{ singleSelectedLabel }}</span>
                </template>
                <ChevronsUpDown class="w-4 h-4 ml-2 opacity-50 shrink-0" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-[--radix-popover-trigger-width] p-0">
            <Command>
                <CommandInput v-model="searchQuery" placeholder="Pesquisar..." />
                <CommandEmpty>Nenhum resultado encontrado.</CommandEmpty>
                <CommandList @scroll="handleScroll">
                    <CommandGroup>
                        <CommandItem v-for="option in options" :key="option.id" :value="$t(option.name)"
                            @select="() => handleSelect(option)">
                            <Check :class="[
                                'mr-2 h-4 w-4',
                                (Array.isArray(modelValue) ? modelValue.includes(Number(option.id)) : modelValue === option.id)
                                    ? 'opacity-100'
                                    : 'opacity-0',
                            ]" />
                            {{ $t(option.name) }}
                        </CommandItem>
                    </CommandGroup>
                    <div v-if="isLoading" class="p-2 text-center text-muted-foreground">Carregando...</div>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
    <!-- </div> -->
</template>
