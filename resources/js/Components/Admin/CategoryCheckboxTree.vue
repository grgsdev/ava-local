<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    category: any;
    modelValue: number[]; // Array de IDs selecionados (v-model)
    level?: number;
}>();

const emit = defineEmits(['update:modelValue']);

const depth = props.level || 0;

// Verifica se este ID está no array de selecionados
const isChecked = computed(() => props.modelValue.includes(props.category.id));

// Função para atualizar o array
const handleChange = (event: Event) => {
    const checked = (event.target as HTMLInputElement).checked;
    let newValue = [...props.modelValue];

    if (checked) {
        newValue.push(props.category.id);
    } else {
        newValue = newValue.filter(id => id !== props.category.id);
    }

    emit('update:modelValue', newValue);
};

// Função para propagar mudanças dos filhos para o pai (bubble up)
const onChildChange = (newArray: number[]) => {
    emit('update:modelValue', newArray);
};
</script>

<template>
    <div class="flex flex-col">
        <div class="flex items-center gap-2 py-1" :style="{ paddingLeft: (depth * 20) + 'px' }">
            <input type="checkbox" :value="category.id" :checked="isChecked" @change="handleChange"
                class="w-4 h-4 border-gray-300 rounded text-primary focus:ring-primary" />
            <span class="text-sm">{{ category.name }}</span>
        </div>

        <template v-if="category.children && category.children.length > 0">
            <CategoryCheckboxTree v-for="child in category.children" :key="child.id" :category="child"
                :model-value="modelValue" :level="depth + 1" @update:modelValue="onChildChange" />
        </template>
    </div>
</template>
