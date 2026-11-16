<script setup lang="ts">
import { computed } from 'vue'

type Coordinates = {
    top: number
    left: number
    width: number
    height: number
}

const props = defineProps<{
    data: {
        coordinates: Coordinates
        text?: string | null
        text_color?: string | null
        text_font_size?: number | null
        text_font_family?: string | null
        text_align?: string | null
        vertical_align?: string | null
        background_color?: string | null
        background_opacity?: number | null
        background_image?: string | null
    }
    editable?: boolean
}>()

const emit = defineEmits<{
    (e: 'drag-start', ev: MouseEvent): void
    (e: 'resize-start', ev: MouseEvent): void
}>()

// 🎨 CAMADA DE FUNDO (Cor + Imagem + Opacidade)
const backgroundStyle = computed(() => {
    const opacity = typeof props.data.background_opacity === 'number'
        ? props.data.background_opacity
        : 1
    const color = props.data.background_color ?? 'transparent'

    return {
        backgroundColor: color,
        opacity,
        backgroundImage: props.data.background_image
            ? `url(${props.data.background_image})`
            : undefined,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat',
    }
})

// 📐 ALINHAMENTO DO TEXTO (Flexbox)
const alignmentStyle = computed(() => {
    const hAlign = props.data.text_align === 'left' ? 'flex-start' : (props.data.text_align === 'right' ? 'flex-end' : 'center')
    const vAlign = props.data.vertical_align === 'top' ? 'flex-start' : (props.data.vertical_align === 'bottom' ? 'flex-end' : 'center')

    return {
        justifyContent: hAlign,
        alignItems: vAlign
    }
})

// 🔤 ESTILO DA FONTE
const textStyle = computed(() => {
    return {
        color: props.data.text_color || '#000000',
        fontSize: (props.data.text_font_size || 20) + 'px',
        fontFamily: props.data.text_font_family || 'Arial, sans-serif'
    }
})

const onMouseDown = (e: MouseEvent) => {
    const target = e.target as HTMLElement

    // se clicou no resize, não move
    if (target.closest('[data-resize-handle]')) return

    e.stopPropagation()
    e.preventDefault()
    emit('drag-start', e)
}
</script>

<template>
    <div class="absolute z-50 overflow-hidden cursor-move rounded-xl group" :style="{
        top: data.coordinates.top + '%',
        left: data.coordinates.left + '%',
        width: data.coordinates.width + '%',
        height: data.coordinates.height + '%'
    }" @mousedown="onMouseDown">

        <div class="absolute inset-0 pointer-events-none" :style="backgroundStyle"></div>

        <div class="absolute inset-0 z-20 flex p-4 pointer-events-none" style="word-break: break-word"
            :style="alignmentStyle">
            <span v-if="data.text" class="font-bold text-center drop-shadow-md" :style="textStyle">
                {{ data.text }}
            </span>
        </div>

        <div v-if="editable" data-resize-handle
            class="absolute bottom-0 right-0 z-30 hidden w-4 h-4 bg-amber-500 cursor-nwse-resize group-hover:block"
            @mousedown.stop.prevent="$emit('resize-start', $event)" />
    </div>
</template>
