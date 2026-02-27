<script setup>
const props = defineProps({
    country:    { type: Object, required: true },
    governance: { type: Object, default: null },
    alerts:     { type: Array,  default: () => [] },
});

import AnalyticsLayout from '@/Layouts/AnalyticsLayout.vue';

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

const severityColor = {
    critical: 'bg-red-100 text-red-700',
    high:     'bg-orange-100 text-orange-700',
    moderate: 'bg-yellow-100 text-yellow-700',
    low:      'bg-blue-100 text-blue-700',
};

const formatPopulation = (value) =>
    value != null ? Number(value).toLocaleString() : '—';

const formatScore = (value) =>
    value != null ? Number(value).toFixed(2) : '—';

const pillarLabel = (key) =>
    key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
</script>

<template>
    <AnalyticsLayout :title="country.name">
        <template #header>
            <div class="flex items-center gap-3 flex-wrap">
                <a href="/countries" class="text-sm text-gray-500 hover:text-gray-700 transition">← Countries</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-semibold text-gray-800">{{ country.name }}</span>
                <span class="font-mono text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded">{{ country.iso_code }}</span>
                <span
                    v-if="country.risk_tier"
                    :class="['text-xs font-semibold px-2.5 py-0.5 rounded-full', riskTierColor[country.risk_tier] ?? 'bg-gray-100 text-gray-600']"
                >
                    {{ riskTierLabel[country.risk_tier] ?? country.risk_tier }}
                </span>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Hero card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-3xl font-bold text-gray-900">{{ country.name }}</h1>
                        <p v-if="country.continent_region" class="text-sm text-gray-500 mt-1">{{ country.continent_region }}</p>
                    </div>
                    <div class="shrink-0 flex flex-col items-end gap-2">
                        <span
                            v-if="country.risk_tier"
                            :class="['text-sm font-semibold px-3 py-1 rounded-full', riskTierColor[country.risk_tier] ?? 'bg-gray-100 text-gray-600']"
                        >
                            {{ riskTierLabel[country.risk_tier] ?? country.risk_tier }} Risk
                        </span>
                        <span
                            :class="['text-xs font-semibold px-2.5 py-0.5 rounded-full', country.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']"
                        >
                            {{ country.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-4 border-t border-gray-100 pt-4">
                    <div>
                        <dt class="text-xs text-gray-400 uppercase tracking-wide">ISO Code</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-800 font-mono">{{ country.iso_code ?? '—' }}</dd>
                    </div>
                    <div v-if="country.federation_region">
                        <dt class="text-xs text-gray-400 uppercase tracking-wide">Federation Region</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-800">{{ country.federation_region.name }}</dd>
                    </div>
                    <div v-if="country.continent_region">
                        <dt class="text-xs text-gray-400 uppercase tracking-wide">Continent Region</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-800">{{ country.continent_region }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Stats row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ country.regions?.length ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">Regions</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ country.institutions?.length ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">Institutions</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">
                        {{ governance ? formatScore(governance.composite_score) : '—' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Governance Score</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ alerts.length }}</p>
                    <p class="text-xs text-gray-400 mt-1">Active Alerts</p>
                </div>
            </div>

            <!-- Governance Score card -->
            <div v-if="governance" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-800">Governance Score</h2>
                    <span class="text-xs text-gray-400">{{ governance.year }}</span>
                </div>
                <div class="flex items-end gap-3 mb-6">
                    <span class="text-5xl font-bold text-gray-900">{{ formatScore(governance.composite_score) }}</span>
                    <span class="text-sm text-gray-400 mb-1">composite score</span>
                </div>
                <div v-if="governance.pillar_scores && Object.keys(governance.pillar_scores).length" class="border-t border-gray-100 pt-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Pillar Scores</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div
                            v-for="[pillar, score] in Object.entries(governance.pillar_scores)"
                            :key="pillar"
                            class="bg-gray-50 rounded-lg p-3"
                        >
                            <p class="text-xs text-gray-500 mb-1">{{ pillarLabel(pillar) }}</p>
                            <p class="text-lg font-bold text-gray-900">{{ formatScore(score) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Alerts -->
            <div v-if="alerts.length" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Active Alerts</h2>
                <div class="space-y-3">
                    <div
                        v-for="alert in alerts"
                        :key="alert.id"
                        class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 bg-gray-50"
                    >
                        <span :class="['shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full mt-0.5', severityColor[alert.severity] ?? 'bg-gray-100 text-gray-600']">
                            {{ alert.severity ? alert.severity.charAt(0).toUpperCase() + alert.severity.slice(1) : 'Unknown' }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800">{{ alert.title }}</p>
                            <p v-if="alert.message" class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ alert.message }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Regions table -->
            <div v-if="country.regions && country.regions.length" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Regions</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50 text-left">
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Level</th>
                                <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right">Population</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="region in country.regions"
                                :key="region.id"
                                class="hover:bg-gray-50 transition"
                            >
                                <td class="px-6 py-3 font-medium text-gray-800">{{ region.name }}</td>
                                <td class="px-6 py-3 text-gray-500 capitalize">{{ region.administrative_level ?? '—' }}</td>
                                <td class="px-6 py-3 text-gray-500 text-right">{{ formatPopulation(region.population) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Institutions list -->
            <div v-if="country.institutions && country.institutions.length" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Institutions</h2>
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="institution in country.institutions"
                        :key="institution.id"
                        class="flex items-center justify-between py-3"
                    >
                        <span class="text-sm font-medium text-gray-800">{{ institution.name }}</span>
                        <span v-if="institution.type" class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded capitalize">
                            {{ institution.type }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Footer links -->
            <div class="flex flex-wrap gap-3">
                <a
                    :href="`/risk/${country.id}`"
                    class="px-4 py-2 bg-red-50 text-red-700 border border-red-200 text-sm font-semibold rounded-lg hover:bg-red-100 transition"
                >
                    View Risk Profile →
                </a>
                <a
                    :href="`/transparency/${country.id}`"
                    class="px-4 py-2 bg-indigo-50 text-indigo-700 border border-indigo-200 text-sm font-semibold rounded-lg hover:bg-indigo-100 transition"
                >
                    View Transparency Report →
                </a>
            </div>

        </div>
    </AnalyticsLayout>
</template>
