<script setup lang="ts">
import { ref, watch } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { User } from 'lucide-vue-next';

const props = defineProps<{
    modelValue: File | null;
    defaultSrc?: string;
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

const openFileDialog = () => {
    fileInput.value?.click();
};

const onDrop = (event: DragEvent) => {
    isDragging.value = false;
    const file = event.dataTransfer?.files[0];
    handleFileChange(file || null);
};

watch(() => props.modelValue, (newFile) => {
    if (!newFile) {
        previewUrl.value = props.defaultSrc || null;
    }
});
</script>

<template>
    <div @click="openFileDialog" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
        @drop.prevent="onDrop"
        class="relative flex flex-col items-center justify-center w-48 h-48 p-4 transition-colors border-2 border-dashed rounded-full cursor-pointer"
        :class="{ 'border-primary bg-primary/10': isDragging }">
        <Avatar class="w-40 h-40">
            <AvatarImage :src="previewUrl ?? ''" alt="Avatar Preview" />
            <AvatarFallback>
                <User class="w-20 h-20 text-muted-foreground" />
            </AvatarFallback>
        </Avatar>
        <input ref="fileInput" type="file"
            @change="handleFileChange(($event.target && ($event.target as HTMLInputElement).files ? ($event.target as HTMLInputElement).files![0] : null))"
            class="hidden" accept="image/*" />
        <div
            class="absolute inset-0 flex items-center justify-center text-sm font-medium text-center text-white transition-opacity bg-black rounded-full opacity-0 bg-opacity-40 hover:opacity-100">
            Clique ou arraste<br>para alterar
        </div>
    </div>
</template>
