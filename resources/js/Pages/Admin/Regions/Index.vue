<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    country: { type: Object, required: true },
    regions: { type: Array,  default: () => [] },
});

const levelLabel = (level) => {
    const labels = { 1: 'State/Province', 2: 'District', 3: 'Municipality', 4: 'Ward' };
    return labels[level] ?? `Level ${level}`;
};
</script>

<template>
    <AppLayout :title="`Regions — ${country.name}`">
        <template #header>
            <div class="flex items-center gap-3 flex-wrap">
                <a href="/admin/countries" class="text-sm text-gray-500 hover:text-gray-700">← Countries</a>
                <span class="text-gray-300">/</span>
                <a :href="`/admin/countries/${country.id}/edit`" class="text-sm text-gray-500 hover:text-gray-700">{{ country.name }}</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-semibold text-gray-800">Regions</span>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Regions: {{ country.name }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ regions.length }} region{{ regions.length !== 1 ? 's' : '' }} defined</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div v-if="regions.length" class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Level</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Population</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Area km²</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="region in regions" :key="region.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ region.name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ levelLabel(region.administrative_level) }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ region.population ? region.population.toLocaleString() : '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ region.area_km2 ? Number(region.area_km2).toLocaleString() : '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-10 text-center text-gray-400">
                    <p class="text-base font-medium">No regions defined for {{ country.name }}.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
