<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    data: {
        coordinates: {
            top: number
            left: number
            width: number
            height: number
        }
        background_color?: string
        background_image?: string | null
        text?: string
        text_style: {
            color: string
            size: number
            family: string
            align: 'left' | 'center' | 'right'
            vertical: 'top' | 'center' | 'bottom'
        }
    }
    editable?: boolean
}>()

const emit = defineEmits<{
    (e: 'drag-start', ev: MouseEvent): void
    (e: 'resize-start', ev: MouseEvent): void
}>()

const alignClass = computed(() => {
    const h = props.data.text_style.align
    const v = props.data.text_style.vertical

    return {
        // vertical
        'items-start': v === 'top',
        'items-center': v === 'center',
        'items-end': v === 'bottom',

        // horizontal (texto)
        'text-left': h === 'left',
        'text-center': h === 'center',
        'text-right': h === 'right',
    }
})

const onMouseDown = (e: MouseEvent) => {
    const target = e.target as HTMLElement

    // 🔥 se clicou no resize, NÃO move
    if (target.closest('[data-resize-handle]')) return

    e.stopPropagation()
    e.preventDefault()
    emit('drag-start', e)
}

</script>

<template>
    <!-- ROOT = SEM pointer-events-none -->
    <div class="absolute z-50 overflow-hidden cursor-move rounded-xl" :style="{
        top: data.coordinates.top + '%',
        left: data.coordinates.left + '%',
        width: data.coordinates.width + '%',
        height: data.coordinates.height + '%',
        backgroundColor: data.background_color || 'transparent',
        backgroundImage: data.background_image
            ? `url(${data.background_image})`
            : undefined,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
    }" @mousedown="onMouseDown">

        <!-- CONTEÚDO -->
        <div class="relative z-10 flex w-full h-full px-4 py-2" :class="alignClass" style="word-break: break-word">
            <!-- TEXTO NÃO CAPTURA EVENTO -->
            <div class="w-full pointer-events-none" :style="{
                color: data.text_style.color,
                fontSize: data.text_style.size + 'px',
                fontFamily: data.text_style.family,
            }">
                {{ data.text }}
            </div>
        </div>

        <!-- HANDLE DE RESIZE -->
        <div v-if="editable" data-resize-handle
            class="absolute bottom-0 right-0 z-20 w-4 h-4 pointer-events-auto bg-amber-500 cursor-nwse-resize"
            @mousedown.stop.prevent="$emit('resize-start', $event)"></div>
    </div>
</template>
