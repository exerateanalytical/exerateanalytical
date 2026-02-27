<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AnalyticsLayout from '@/Layouts/AnalyticsLayout.vue';

const props = defineProps({
    countries: { type: Object, default: () => ({ data: [] }) },
    filters:   { type: Object, default: () => ({}) },
    riskTiers: { type: Array,  default: () => [] },
});

const search   = ref(props.filters.search   ?? '');
const riskTier = ref(props.filters.risk_tier ?? '');

let debounceTimer = null;

const applyFilters = () => {
    router.get('/countries', {
        search:    search.value    || undefined,
        risk_tier: riskTier.value  || undefined,
    }, { preserveState: true, replace: true });
};

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
});

watch(riskTier, () => {
    applyFilters();
});

const riskTierColor = {
    low:      'bg-green-100 text-green-700',
    moderate: 'bg-amber-100 text-amber-700',
    high:     'bg-red-100 text-red-700',
};

const riskTierLabel = {
    low:      'Low',
    moderate: 'Moderate',
    high:     'High',
};
</script>

<template>
    <AnalyticsLayout title="Countries">
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Countries</h1>
                <p class="text-sm text-gray-500 mt-0.5">Browse and filter countries tracked across the federation.</p>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Filter bar -->
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-3 flex flex-wrap gap-3 items-center shadow-sm">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search countries or ISO code…"
                    class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 min-w-0 flex-1"
                />
                <select
                    v-model="riskTier"
                    class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                >
                    <option value="">All risk tiers</option>
                    <option v-for="tier in riskTiers" :key="tier" :value="tier">
                        {{ riskTierLabel[tier] ?? tier }}
                    </option>
                </select>
                <span class="ms-auto text-xs text-gray-400">
                    {{ countries.total ?? countries.data.length }}
                    {{ (countries.total ?? countries.data.length) === 1 ? 'country' : 'countries' }}
                </span>
            </div>

            <!-- Country grid -->
            <div v-if="countries.data.length" class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <a
                    v-for="country in countries.data"
                    :key="country.id"
                    :href="`/countries/${country.id}`"
                    class="block bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md hover:border-indigo-200 transition group"
                >
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-700 transition truncate">
                                {{ country.name }}
                            </h3>
                            <span class="text-xs text-gray-400 font-mono">{{ country.iso_code }}</span>
                        </div>
                        <span
                            v-if="country.risk_tier"
                            :class="['shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full', riskTierColor[country.risk_tier] ?? 'bg-gray-100 text-gray-600']"
                        >
                            {{ riskTierLabel[country.risk_tier] ?? country.risk_tier }}
                        </span>
                    </div>

                    <div v-if="country.federation_region" class="text-xs text-gray-400 mt-2">
                        {{ country.federation_region.name }}
                    </div>
                </a>
            </div>

            <!-- Empty state -->
            <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm py-16 text-center">
                <p class="text-gray-400 text-sm font-medium">No countries found.</p>
                <p class="text-gray-400 text-xs mt-1">Try adjusting your search or filters.</p>
            </div>

            <!-- Pagination -->
            <div v-if="countries.last_page > 1" class="flex justify-center gap-1 flex-wrap">
                <a
                    v-for="link in countries.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="[
                        'px-3 py-1.5 text-sm rounded-lg border transition',
                        link.active
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300',
                        !link.url ? 'opacity-40 pointer-events-none' : '',
                    ]"
                />
            </div>

        </div>
    </AnalyticsLayout>
</template>
