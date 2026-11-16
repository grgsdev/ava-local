<script setup lang="ts">
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Table, TableHeader, TableBody } from '@/components/ui/table';
import Pagination from '@/Components/Pagination.vue';
import { Filter } from 'lucide-vue-next';

// Este layout espera receber o objeto de paginação completo do Laravel
defineProps({
    data: Object,
});
</script>

<template>
    <div class="flex flex-col flex-1 min-h-0">
        <Card class="flex flex-col flex-1 overflow-hidden">
            <CardHeader>
                <div class="flex items-center justify-between">
                    <CardTitle>
                        <slot name="title" />
                    </CardTitle>
                    <div class="ml-auto">
                        <slot name="actions" />
                    </div>
                </div>
            </CardHeader>

            <div class="px-6 pb-4 border-b">
                <Collapsible>
                    <CollapsibleTrigger class="w-full">
                        <div class="flex items-center justify-between">
                            <h4 class="text-base font-semibold">Filtros</h4>
                            <Filter class="w-4 h-4 text-muted-foreground" />
                        </div>
                    </CollapsibleTrigger>
                    <CollapsibleContent class="pt-4">
                        <slot name="filters" />
                    </CollapsibleContent>
                </Collapsible>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto">
                <Table>
                    <TableHeader class="sticky top-0 z-10 bg-card">
                        <slot name="table-header" />
                    </TableHeader>
                    <TableBody>
                        <slot name="table-body" />
                    </TableBody>
                </Table>
            </div>
        </Card>

        <div class="flex-shrink-0 pt-2">
            <Pagination v-if="data" :links="data.links" :from="data.from" :to="data.to" :total="data.total" />
        </div>
    </div>
</template>
