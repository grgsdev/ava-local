<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { UploadCloud, X } from 'lucide-vue-next';

const props = defineProps<{
    modelValue: File[];
    existingImages?: { id: number; url: string }[];
}>();

const emit = defineEmits(['update:modelValue', 'remove-existing']);

const fileInput = ref<HTMLInputElement | null>(null);
const previews = ref<string[]>([]);

const handleFileChange = (files: FileList | null) => {
    if (!files) return;
    const newFiles = Array.from(files);
    emit('update:modelValue', [...props.modelValue, ...newFiles]);

    newFiles.forEach(file => {
        if (file.type.startsWith('image/')) {
            previews.value.push(URL.createObjectURL(file));
        }
    });
};
const onFileSelected = (event: Event) => {
    // Dizemos ao TypeScript que o target é um input de HTML
    const target = event.target as HTMLInputElement;
    // Agora podemos aceder a .files com segurança
    if (target && target.files) {
        handleFileChange(target.files);
    }
};
const removeNewImage = (index: number) => {
    const currentFiles = [...props.modelValue];
    currentFiles.splice(index, 1);
    emit('update:modelValue', currentFiles);

    const currentPreviews = [...previews.value];
    currentPreviews.splice(index, 1);
    previews.value = currentPreviews;
};
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-3 gap-4 md:grid-cols-5">
            <div v-for="image in existingImages" :key="image.id" class="relative group aspect-square">
                <img :src="image.url" class="object-cover w-full h-full rounded-md" />
                <Button @click="$emit('remove-existing', image.id)" variant="destructive" size="icon"
                    class="absolute w-6 h-6 opacity-0 top-1 right-1 group-hover:opacity-100">
                    <X class="w-4 h-4" />
                </Button>
            </div>
            <div v-for="(preview, index) in previews" :key="index" class="relative group aspect-square">
                <img :src="preview" class="object-cover w-full h-full rounded-md" />
                <Button @click="removeNewImage(index)" variant="destructive" size="icon"
                    class="absolute w-6 h-6 opacity-0 top-1 right-1 group-hover:opacity-100">
                    <X class="w-4 h-4" />
                </Button>
            </div>
        </div>
        <div @click="fileInput?.click()"
            class="flex flex-col items-center justify-center w-full p-6 border-2 border-dashed rounded-lg cursor-pointer hover:bg-accent">
            <UploadCloud class="w-8 h-8 mb-2 text-muted-foreground" />
            <span class="text-sm font-semibold">Adicionar imagens à galeria</span>
            <!-- <input ref="fileInput" type="file" @change="handleFileChange($event.target.files)" class="hidden"
                accept="image/*" multiple /> -->
            <input ref="fileInput" type="file" @change="onFileSelected" class="hidden" accept="image/*" multiple />
        </div>
    </div>
</template>
