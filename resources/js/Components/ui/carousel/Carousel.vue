<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import emblaCarouselVue from 'embla-carousel-vue'
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps<{
    images: { id: number, url: string }[]
}>()

const [emblaRef, emblaApi] = emblaCarouselVue({
    loop: true, // Permite que o carrossel volte ao início
})

const canScrollPrev = ref(false)
const canScrollScrollNext = ref(false)

const scrollPrev = () => emblaApi.value?.scrollPrev()
const scrollNext = () => emblaApi.value?.scrollNext()
const scrollTo = (index: number) => emblaApi.value?.scrollTo(index)

const onSelect = () => {
    if (!emblaApi.value) return
    canScrollPrev.value = emblaApi.value.canScrollPrev()
    canScrollScrollNext.value = emblaApi.value.canScrollNext()
}

onMounted(() => {
    if (!emblaApi.value) return
    onSelect()
    emblaApi.value.on('select', onSelect)
    emblaApi.value.on('reInit', onSelect)
})
</script>

<template>
    <div class="relative">
        <div ref="emblaRef" class="overflow-hidden rounded-lg">
            <div class="flex">
                <div v-for="image in images" :key="image.id" class="relative flex-shrink-0 w-full aspect-video">
                    <img :src="image.url" class="object-cover w-full h-full" />
                </div>
            </div>
        </div>

        <Button @click="scrollPrev" variant="outline" size="icon"
            class="absolute w-8 h-8 -translate-y-1/2 rounded-full left-4 top-1/2">
            <ChevronLeft class="w-4 h-4" />
        </Button>
        <Button @click="scrollNext" variant="outline" size="icon"
            class="absolute w-8 h-8 -translate-y-1/2 rounded-full right-4 top-1/2">
            <ChevronRight class="w-4 h-4" />
        </Button>

        <div class="absolute bottom-0 left-0 right-0 flex items-center justify-center p-4 space-x-2">
            <button v-for="(image, index) in images" :key="`dot-${image.id}`" @click="scrollTo(index)"
                class="w-2 h-2 transition-colors rounded-full"
                :class="emblaApi?.selectedScrollSnap() === index ? 'bg-primary' : 'bg-muted-foreground/50'" />
        </div>
    </div>
</template>
