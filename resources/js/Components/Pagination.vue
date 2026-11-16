<template>
    <div class="flex items-center justify-between">
        <div v-if="total > 0" class="text-sm text-muted-foreground">
            Mostrando de <span class="font-semibold">{{ from }}</span> a <span class="font-semibold">{{ to }}</span> de
            <span class="font-semibold">{{ total }}</span> resultados
        </div>
        <div v-else></div>

        <div v-if="links?.length > 3" class="flex flex-wrap -mb-1">
            <template v-for="(link, key) in processedLinks" :key="key">
                <div v-if="link.is_spacer" class="px-3 py-2 mb-1 mr-1 text-sm border rounded text-muted-foreground">
                    ...
                </div>
                <div v-else-if="link.url === null"
                    class="px-3 py-2 mb-1 mr-1 text-sm border rounded text-muted-foreground" v-html="link.label" />
                <Link v-else
                    class="px-3 py-2 mb-1 mr-1 text-sm border rounded hover:bg-accent focus:border-primary focus:text-primary"
                    :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }" :href="link.url"
                    v-html="link.label" />
            </template>
        </div>
    </div>
</template>

<script setup lag="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    links: Array,
    from: Number,
    to: Number,
    total: Number,
});
const processedLinks = computed(() => {
    // Se não houver links suficientes para precisar de compactação, retorna a lista original.
    // O valor 9 é um bom ponto de partida (Anterior + 1, 2, 3, 4, 5, 6, 7 + Próximo)
    if (props.links.length <= 9) {
        return props.links;
    }

    const currentPage = props.links.find(link => link.active)?.label;
    if (!currentPage) return props.links;

    const firstPageLink = props.links[1];
    const lastPageLink = props.links[props.links.length - 2];

    // Define a "janela" de links a serem mostrados ao redor da página atual
    const windowSize = 2; // Mostra 2 links antes e 2 depois da página atual
    const windowStart = Math.max(2, currentPage - windowSize);
    const windowEnd = Math.min(lastPageLink.label - 1, parseInt(currentPage) + windowSize);

    let linksToShow = props.links.filter(link => {
        const pageNumber = parseInt(link.label);
        return pageNumber >= windowStart && pageNumber <= windowEnd;
    });

    const newLinks = [];
    // Adiciona o link "Anterior"
    newLinks.push(props.links[0]);
    // Adiciona o link da "Página 1"
    newLinks.push(firstPageLink);

    // Adiciona reticências se houver um buraco entre a página 1 e o início da janela
    if (windowStart > 2) {
        newLinks.push({ url: null, label: '...', active: false, is_spacer: true });
    }

    // Adiciona os links da janela
    newLinks.push(...linksToShow);

    // Adiciona reticências se houver um buraco entre o fim da janela e a última página
    if (windowEnd < lastPageLink.label - 1) {
        newLinks.push({ url: null, label: '...', active: false, is_spacer: true });
    }

    // Adiciona o link da "Última Página"
    newLinks.push(lastPageLink);
    // Adiciona o link "Próximo"
    newLinks.push(props.links[props.links.length - 1]);

    return newLinks;
});
</script>
