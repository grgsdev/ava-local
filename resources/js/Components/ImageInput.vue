<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { ImageUp, X } from 'lucide-vue-next';

const props = defineProps<{
    modelValue: File | null;
    defaultSrc?: string | null;
}>();

const emit = defineEmits(['update:modelValue']);

const previewUrl = ref<string | null>(props.defaultSrc || null);
const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const handleFileChange = (file: File | null) => {
    if (file && file.type.startsWith('image/')) {
        previewUrl.value = URL.createObjectURL(file);
        emit('update:modelValue', file);
    }
};
const onFileSelected = (event: Event) => {
    // Dizemos ao TypeScript que o target é um input de HTML
    const target = event.target as HTMLInputElement;
    // Agora podemos aceder a .files com segurança
    if (target && target.files && target.files.length > 0) {
        handleFileChange(target.files[0]);
    }
};
const removeImage = () => {
    previewUrl.value = null;
    emit('update:modelValue', null);
    if (fileInput.value) fileInput.value.value = '';
};

const onDrop = (event: DragEvent) => {
    isDragging.value = false;
    handleFileChange(event.dataTransfer?.files[0] || null);
};

watch(() => props.modelValue, (newFile) => {
    if (!newFile) previewUrl.value = props.defaultSrc || null;
});
</script>

<template>
    <div @click="fileInput?.click()" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
        @drop.prevent="onDrop"
        class="relative flex items-center justify-center w-full h-40 p-4 overflow-hidden transition-colors border-2 border-dashed rounded-lg cursor-pointer group"
        :class="{ 'border-primary bg-primary/10': isDragging }">
        <!-- <input ref="fileInput" type="file" @change="handleFileChange($event.target.files[0])" class="hidden"
            accept="image/*" /> -->
        <input ref="fileInput" type="file" @change="onFileSelected" class="hidden" accept="image/*" />
        <div v-if="previewUrl" class="absolute inset-0">
            <img :src="previewUrl" alt="Preview" class="object-cover w-full h-full" />
            <div
                class="absolute inset-0 flex items-center justify-center text-sm font-medium text-white transition-opacity bg-black opacity-0 bg-opacity-40 group-hover:opacity-100">
                Alterar Imagem
            </div>
            <Button @click.stop="removeImage" variant="destructive" size="icon" class="absolute w-6 h-6 top-2 right-2">
                <X class="w-4 h-4" />
            </Button>
        </div>
        <div v-else class="flex flex-col items-center text-muted-foreground">
            <ImageUp class="w-8 h-8 mb-2" />
            <span>Clique ou arraste uma imagem</span>
        </div>
    </div>
</template>
