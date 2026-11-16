<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { PlayCircle, Clock, BarChart, CheckCircle2, Lock, Unlock } from 'lucide-vue-next';

const props = defineProps<{ course: any }>();

// Helper: Pegar a primeira aula para o botão "Começar"
const firstLesson = props.course.modules[0]?.lessons[0];

const formatDuration = (min: number) => {
    const h = Math.floor(min / 60);
    const m = min % 60;
    return h > 0 ? `${h}h ${m}min` : `${m} min`;
};
</script>

<template>
    <AppLayout :title="course.title">

        <div class="relative pt-16 pb-24 text-white bg-slate-950">
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-slate-800 via-slate-950 to-slate-950 opacity-50">
            </div>

            <div class="container relative z-10 grid gap-12 px-4 mx-auto lg:grid-cols-3">

                <div class="lg:col-span-2">
                    <div class="flex gap-3 mb-6">
                        <Badge class="font-bold border-none bg-amber-500 text-slate-900 hover:bg-amber-600">Projeto
                            Online
                        </Badge>
                        <Badge variant="outline" class="capitalize text-slate-300 border-slate-700">{{ course.level }}
                        </Badge>
                    </div>

                    <h1 class="mb-6 text-4xl font-black leading-tight tracking-tight md:text-5xl">
                        {{ course.title }}
                    </h1>
                    <p class="max-w-2xl mb-8 text-xl leading-relaxed text-slate-400">
                        {{ course.headline }}
                    </p>

                    <div class="flex flex-wrap gap-6 mb-8 text-sm font-medium text-slate-400">
                        <div class="flex items-center gap-2">
                            <Clock class="w-5 h-5 text-amber-500" />
                            {{ formatDuration(course.duration_minutes || 0) }} de conteúdo
                        </div>
                        <div class="flex items-center gap-2">
                            <BarChart class="w-5 h-5 text-amber-500" />
                            {{ course.modules.length }} Módulos / {{ course.lessons_count }} Vídeos
                        </div>
                        <div class="flex items-center gap-2">
                            <CheckCircle2 class="w-5 h-5 text-amber-500" />
                            Certificado Incluso
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex items-center justify-center w-10 h-10 font-bold border rounded-full bg-slate-800 text-amber-500 border-slate-700">
                                {{ course.author?.name.charAt(0) }}
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase text-slate-500">Instrutor</div>
                                <div class="font-medium">{{ course.author?.name }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative lg:mt-12">
                    <div
                        class="sticky p-6 bg-white border shadow-2xl dark:bg-slate-900 rounded-2xl border-slate-200 dark:border-slate-800 top-24">
                        <div
                            class="relative mb-6 overflow-hidden rounded-lg cursor-pointer aspect-video bg-slate-100 group">
                            <img v-if="course.thumbnail_url" :src="course.thumbnail_url"
                                class="object-cover w-full h-full" />
                            <div
                                class="absolute inset-0 flex items-center justify-center transition-colors bg-black/20 group-hover:bg-black/40">
                                <PlayCircle
                                    class="w-12 h-12 text-white transition-transform transform drop-shadow-lg group-hover:scale-110" />
                            </div>
                        </div>

                        <div class="mb-2 text-3xl font-black text-slate-900 dark:text-white">
                            {{ parseFloat(course.price) > 0 ? `R$ ${course.price}` : 'Grátis' }}
                        </div>
                        <p class="mb-6 text-sm text-muted-foreground">Acesso vitalício e atualizações.</p>

                        <Link v-if="firstLesson"
                            :href="route('lms.lesson.play', { course: course.slug, lesson: firstLesson.slug })"
                            class="block w-full">
                            <Button size="lg"
                                class="w-full text-lg font-bold shadow-lg h-14 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-900 shadow-amber-500/20">
                                Começar Agora
                            </Button>
                        </Link>
                        <Button v-else disabled size="lg" class="w-full h-14 rounded-xl">
                            Em Breve
                        </Button>

                        <div class="mt-4 text-xs text-center text-muted-foreground">
                            Garantia de 7 dias ou seu dinheiro de volta.
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="py-20 bg-white dark:bg-background">
            <div class="container grid gap-12 px-4 mx-auto lg:grid-cols-3">

                <div class="space-y-12 lg:col-span-2">

                    <section>
                        <h2 class="mb-6 text-2xl font-bold text-slate-900 dark:text-white">Sobre este projeto</h2>
                        <div class="prose prose-slate dark:prose-invert max-w-none"
                            v-html="course.description || '<p>Sem descrição detalhada.</p>'"></div>
                    </section>

                    <section>
                        <h2 class="mb-6 text-2xl font-bold text-slate-900 dark:text-white">Conteúdo do Projeto</h2>
                        <div class="overflow-hidden bg-white border shadow-sm rounded-xl dark:bg-slate-900">
                            <Accordion type="multiple" class="w-full" :default-value="[String(course.modules[0]?.id)]">
                                <AccordionItem v-for="module in course.modules" :key="module.id"
                                    :value="String(module.id)" class="border-b last:border-0">
                                    <AccordionTrigger class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <div class="text-left">
                                            <div class="text-base font-bold">{{ module.title }}</div>
                                            <div class="mt-1 text-xs font-normal text-muted-foreground">
                                                {{ module.lessons.length }} vídeos
                                            </div>
                                        </div>
                                    </AccordionTrigger>
                                    <AccordionContent class="px-6 py-2 bg-slate-50 dark:bg-slate-800/20">
                                        <div class="flex flex-col py-2 space-y-3">
                                            <div v-for="lesson in module.lessons" :key="lesson.id"
                                                class="flex items-center justify-between group">
                                                <div class="flex items-center gap-3">
                                                    <PlayCircle
                                                        class="w-4 h-4 transition-colors text-slate-400 group-hover:text-amber-500" />
                                                    <span
                                                        class="text-sm font-medium transition-colors cursor-default text-slate-700 dark:text-slate-300 group-hover:text-amber-600 dark:group-hover:text-amber-400">
                                                        {{ lesson.title }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <Badge v-if="lesson.is_free_preview" variant="outline"
                                                        class="text-[10px] border-green-200 text-green-700 bg-green-50 h-5">
                                                        Grátis</Badge>
                                                    <span class="w-12 text-xs text-right text-muted-foreground">{{
                                                        lesson.duration_minutes || 5 }} min</span>
                                                    <Unlock
                                                        v-if="lesson.is_free_preview || parseFloat(course.price) === 0"
                                                        class="w-3 h-3 text-green-500" />
                                                    <Lock v-else class="w-3 h-3 text-slate-400" />
                                                </div>
                                            </div>
                                        </div>
                                    </AccordionContent>
                                </AccordionItem>
                            </Accordion>
                        </div>
                    </section>

                </div>
            </div>
        </div>

    </AppLayout>
</template>
