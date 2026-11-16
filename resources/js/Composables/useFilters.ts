import { reactive, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { pickBy, omit } from "lodash";

// Usamos um "tipo genérico" (T). Isso permite que nosso composable funcione
// com qualquer formato de objeto de filtro que passarmos para ele.
export function useFilters<T extends Record<string, any>>(
    props: { queryParams: any }, // Mantemos any aqui por simplicidade, já que os queryParams variam
    initialFilterValues: T,
    routeName: string
) {
    // Dizemos ao Vue que 'filters' é um objeto reativo do tipo T
    const filters = reactive<T>({ ...initialFilterValues });

    let searchTimeout: ReturnType<typeof setTimeout> | null = null;
    watch(filters, (newFilters) => {
        if (searchTimeout) clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            const query = pickBy(omit(newFilters, "per_page"));

            router.get(
                route(routeName),
                {
                    sort: props.queryParams?.sort,
                    filter: query as Record<string, any>,
                    per_page: newFilters.per_page,
                },
                {
                    preserveState: true,
                    replace: true,
                }
            );
        }, 300);
    });

    const sort = (field: string) => {
        const currentSorts: string[] =
            props.queryParams?.sort?.split(",") || [];
        const newSorts = [...currentSorts];

        const idx = newSorts.findIndex((s) => s === field || s === `-${field}`);

        if (idx === -1) {
            newSorts.push(field);
        } else if (newSorts[idx] === field) {
            newSorts[idx] = `-${field}`;
        } else {
            newSorts.splice(idx, 1);
        }

        const query = pickBy(omit(filters, "per_page"));

        router.get(
            route(routeName),
            {
                filter: query as Record<string, any>,
                per_page: filters.per_page,
                sort: newSorts.join(","),
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const clearFilters = () => {
        const defaultPerPage = "15";
        // Convertemos 'filters' para 'any' temporariamente para iterar sobre as chaves
        const tempFilters = filters as any;
        for (const key in tempFilters) {
            if (key === "per_page") {
                tempFilters[key] = defaultPerPage;
            } else {
                tempFilters[key] = "";
            }
        }
    };
    const clearFilter = (filterName: keyof T) => {
        // Redefine o filtro específico para o seu valor inicial
        (filters as any)[filterName] = initialFilterValues[filterName] || "";
    };
    return { filters, clearFilters, sort, clearFilter };
}
