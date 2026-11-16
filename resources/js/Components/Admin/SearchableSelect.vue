<script setup>
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
import axios from 'axios';
import debounce from 'lodash.debounce';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: [Number, Array],
    apiUrl: String,
    multiple: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: 'Selecione uma opção...',
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const isLoading = ref(false);
const searchQuery = ref('');
const options = ref([]);
const page = ref(1);
const lastPage = ref(1);

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

const handleScroll = (event) => {
    const listEl = event.target;
    if (
        listEl.scrollTop + listEl.clientHeight >= listEl.scrollHeight - 50 &&
        !isLoading.value &&
        page.value < lastPage.value
    ) {
        page.value++;
        fetchOptions(searchQuery.value, true);
    }
};

const handleSelect = (option) => {
    if (props.multiple) {
        const selected = new Set(props.modelValue);
        if (selected.has(option.id)) {
            selected.delete(option.id);
        } else {
            selected.add(option.id);
        }
        emit('update:modelValue', Array.from(selected));
    } else {
        emit('update:modelValue', option.id);
        isOpen.value = false;
    }
};

watch(
    searchQuery,
    debounce((newValue) => {
        page.value = 1;
        fetchOptions(newValue, false);
    }, 300),
);

watch(isOpen, (newValue) => {
    if (newValue) {
        page.value = 1;
        fetchOptions('', false);
    }
});

const selectedLabels = computed(() => {
    if (!props.modelValue || props.modelValue.length === 0)
        return props.placeholder;
    if (!props.multiple) {
        return (
            options.value.find((opt) => opt.id === props.modelValue)?.name ||
            props.placeholder
        );
    }
    return options.value
        .filter((opt) => props.modelValue.includes(opt.id))
        .map((opt) => opt.name)
        .join(', ');
});
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                role="combobox"
                class="justify-between w-full"
            >
                <span class="truncate">{{ selectedLabels }}</span>
                <ChevronsUpDown class="w-4 h-4 ml-2 opacity-50 shrink-0" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-[--radix-popover-trigger-width] p-0">
            <Command>
                <CommandInput
                    v-model="searchQuery"
                    placeholder="Pesquisar..."
                />
                <CommandEmpty>Nenhum resultado encontrado.</CommandEmpty>
                <CommandList @scroll="handleScroll">
                    <CommandGroup>
                        <CommandItem
                            v-for="option in options"
                            :key="option.id"
                            :value="option.name"
                            @select="() => handleSelect(option)"
                        >
                            <Check
                                :class="[
                                    'mr-2 h-4 w-4',
                                    (
                                        Array.isArray(modelValue)
                                            ? modelValue.includes(option.id)
                                            : modelValue === option.id
                                    )
                                        ? 'opacity-100'
                                        : 'opacity-0',
                                ]"
                            />
                            {{ option.name }}
                        </CommandItem>
                    </CommandGroup>
                    <div
                        v-if="isLoading"
                        class="p-2 text-center text-muted-foreground"
                    >
                        Carregando...
                    </div>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
