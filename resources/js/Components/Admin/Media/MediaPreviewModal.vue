<script setup>
import { computed } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

const props = defineProps({
    mediaItem: {
        type: Object,
        default: null,
    },
    open: Boolean,
});

const emit = defineEmits(['update:open']);

const isImage = computed(() => props.mediaItem?.mime_type.startsWith('image/'));
const isVideo = computed(() => props.mediaItem?.mime_type.startsWith('video/'));
const isPdf = computed(() => props.mediaItem?.mime_type === 'application/pdf');

const closeModal = () => {
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="props.open" @update:open="closeModal">
        <DialogContent class="max-w-4xl h-[85vh] flex flex-col">
            <DialogHeader>
                <DialogTitle>{{ mediaItem?.file_name }}</DialogTitle>
                <DialogDescription>
                    Pré-visualização do arquivo. <a :href="mediaItem?.original_url" target="_blank" class="underline text-primary">Abrir em nova aba</a>.
                </DialogDescription>
            </DialogHeader>

            <div class="flex-grow min-h-0">
                <div v-if="isImage" class="flex items-center justify-center w-full h-full">
                    <img :src="mediaItem.original_url" :alt="mediaItem.file_name" class="object-contain max-w-full max-h-full">
                </div>

                <div v-else-if="isVideo" class="w-full h-full">
                    <video :src="mediaItem.original_url" controls class="w-full h-full"></video>
                </div>

                <div v-else-if="isPdf" class="w-full h-full">
                    <iframe :src="mediaItem.original_url" class="w-full h-full border-0"></iframe>
                </div>

                <div v-else class="flex flex-col items-center justify-center w-full h-full text-center border rounded-lg bg-muted">
                    <p class="mb-4 text-muted-foreground">Não há pré-visualização disponível para este tipo de arquivo.</p>
                    <a :href="mediaItem?.original_url" target="_blank" download>
                        <Button>Baixar Arquivo</Button>
                    </a>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
