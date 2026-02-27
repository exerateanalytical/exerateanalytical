<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    countries:         { type: Object, default: () => ({ data: [], links: [], last_page: 1, total: 0 }) },
    federationRegions: { type: Array,  default: () => [] },
});

const search             = ref('');
const federationRegionId = ref('');

let debounceTimer = null;

const applyFilters = () => {
    router.get('/admin/countries', {
        search:               search.value             || undefined,
        federation_region_id: federationRegionId.value || undefined,
    }, { preserveState: true, replace: true });
};

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
});

watch(federationRegionId, () => {
    applyFilters();
});

const riskTierBadgeClass = (tier) => {
    const map = {
        low:      'bg-green-100 text-green-700',
        moderate: 'bg-amber-100 text-amber-700',
        high:     'bg-red-100 text-red-700',
    };
    return map[(tier ?? '').toLowerCase()] ?? 'bg-gray-100 text-gray-600';
};

const statusBadgeClass = (isActive) => {
    return isActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500';
};

const deleteCountry = async (country) => {
    const confirmed = window.confirm(
        `Are you sure you want to delete "${country.name}"? This action cannot be undone.`
    );
    if (!confirmed) return;

    try {
        await axios.delete(`/api/v1/countries/${country.id}`);
        router.reload();
    } catch (err) {
        const message = err?.response?.data?.message ?? 'Failed to delete country. Please try again.';
        window.alert(message);
    }
};
</script>

<template>
    <AppLayout title="Admin — Countries">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Countries</h2>
                <a
                    href="/admin/countries/create"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition"
                >
                    + Add Country
                </a>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Filter bar -->
                <div class="bg-white border border-gray-200 rounded-xl px-4 py-3 flex flex-wrap gap-3 items-center shadow-sm">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search name or ISO code&hellip;"
                        class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 min-w-0 flex-1"
                    />
                    <select
                        v-model="federationRegionId"
                        class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">All federation regions</option>
                        <option
                            v-for="fr in federationRegions"
                            :key="fr.id"
                            :value="fr.id"
                        >
                            {{ fr.name }} ({{ fr.code }})
                        </option>
                    </select>
                    <span class="ms-auto text-xs text-gray-400">
                        {{ countries.total ?? countries.data.length }}
                        {{ (countries.total ?? countries.data.length) === 1 ? 'country' : 'countries' }}
                    </span>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    <th class="px-4 py-3 text-left">Name</th>
                                    <th class="px-4 py-3 text-left">ISO Code</th>
                                    <th class="px-4 py-3 text-left">Federation Region</th>
                                    <th class="px-4 py-3 text-center">Risk Tier</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-right">Regions</th>
                                    <th class="px-4 py-3 text-right">Institutions</th>
                                    <th class="px-4 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr
                                    v-for="country in countries.data"
                                    :key="country.id"
                                    class="hover:bg-gray-50 transition"
                                >
                                    <td class="px-4 py-3 font-semibold text-gray-900">
                                        {{ country.name }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-500">
                                        {{ country.iso_code }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ country.federation_region?.name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            v-if="country.risk_tier"
                                            :class="['text-xs font-semibold px-2.5 py-0.5 rounded-full', riskTierBadgeClass(country.risk_tier)]"
                                        >
                                            {{ country.risk_tier }}
                                        </span>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            :class="['text-xs font-semibold px-2.5 py-0.5 rounded-full', statusBadgeClass(country.is_active)]"
                                        >
                                            {{ country.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-600">
                                        {{ country.regions_count ?? 0 }}
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-600">
                                        {{ country.institutions_count ?? 0 }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                            <a
                                                :href="`/admin/countries/${country.id}/edit`"
                                                class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition"
                                            >
                                                Edit
                                            </a>
                                            <a
                                                :href="`/admin/countries/${country.id}/regions`"
                                                class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition"
                                            >
                                                Regions
                                            </a>
                                            <button
                                                @click="deleteCountry(country)"
                                                class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-red-600 bg-red-50 border border-red-100 rounded-lg hover:bg-red-100 transition"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!countries.data.length">
                                    <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-400">
                                        No countries found. Try adjusting your filters or
                                        <a href="/admin/countries/create" class="text-indigo-600 hover:underline">add one</a>.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
        </div>
    </AppLayout>
</template>
