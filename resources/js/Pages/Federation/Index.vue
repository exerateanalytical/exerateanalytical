<script setup>
import { computed } from 'vue';
import AnalyticsLayout from '@/Layouts/AnalyticsLayout.vue';

const props = defineProps({
    globalSnapshot:    { type: Object, default: null },
    regionalSnapshots: { type: Array,  default: () => [] },
    regions:           { type: Array,  default: () => [] },
});

const formatDate = (iso) =>
    iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';

const globalPayload = computed(() => {
    if (!props.globalSnapshot?.payload) return null;
    const p = props.globalSnapshot.payload;
    return typeof p === 'string' ? JSON.parse(p) : p;
});

const hasGlobalPayload = computed(() => globalPayload.value && Object.keys(globalPayload.value).length > 0);

const riskScoreColor = (score) => {
    if (score == null) return 'text-gray-500';
    if (score >= 70) return 'text-red-600';
    if (score >= 40) return 'text-amber-600';
    return 'text-emerald-600';
};

const riskBadgeClass = (score) => {
    if (score == null) return 'bg-gray-100 text-gray-500';
    if (score >= 70) return 'bg-red-100 text-red-700';
    if (score >= 40) return 'bg-amber-100 text-amber-700';
    return 'bg-emerald-100 text-emerald-700';
};

const getRegionalPayload = (snapshot) => {
    if (!snapshot?.payload) return null;
    const p = snapshot.payload;
    return typeof p === 'string' ? JSON.parse(p) : p;
};

const knownGlobalKeys = ['region_count', 'total_countries', 'average_risk_score', 'high_risk_count', 'last_updated', 'regional_summaries'];

const extraGlobalFields = computed(() => {
    if (!globalPayload.value) return {};
    return Object.fromEntries(
        Object.entries(globalPayload.value).filter(([k]) => !knownGlobalKeys.includes(k))
    );
});
</script>

<template>
    <AnalyticsLayout title="Federation Overview">
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Federation Overview</h1>
                <p class="text-sm text-gray-500 mt-0.5">Systemic snapshots and risk intelligence across all federation regions.</p>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

            <!-- Global Snapshot -->
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Global Snapshot</h2>

                <div v-if="globalSnapshot" class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Last snapshot</span>
                        <span class="text-sm text-gray-600">{{ formatDate(globalSnapshot.snapshot_at) }}</span>
                    </div>

                    <div v-if="hasGlobalPayload" class="space-y-4">
                        <!-- Primary stats -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div v-if="globalPayload.region_count != null" class="bg-indigo-50 rounded-xl p-4 text-center">
                                <p class="text-3xl font-bold text-indigo-700">{{ globalPayload.region_count }}</p>
                                <p class="text-xs text-indigo-500 mt-1 font-medium">Regions</p>
                            </div>
                            <div v-if="globalPayload.total_countries != null" class="bg-blue-50 rounded-xl p-4 text-center">
                                <p class="text-3xl font-bold text-blue-700">{{ globalPayload.total_countries }}</p>
                                <p class="text-xs text-blue-500 mt-1 font-medium">Countries</p>
                            </div>
                            <div v-if="globalPayload.average_risk_score != null" class="bg-amber-50 rounded-xl p-4 text-center">
                                <p :class="['text-3xl font-bold', riskScoreColor(globalPayload.average_risk_score)]">
                                    {{ Number(globalPayload.average_risk_score).toFixed(1) }}
                                </p>
                                <p class="text-xs text-amber-600 mt-1 font-medium">Avg Risk Score</p>
                            </div>
                            <div v-if="globalPayload.high_risk_count != null" class="bg-red-50 rounded-xl p-4 text-center">
                                <p class="text-3xl font-bold text-red-700">{{ globalPayload.high_risk_count }}</p>
                                <p class="text-xs text-red-500 mt-1 font-medium">High Risk</p>
                            </div>
                        </div>

                        <!-- Last updated from payload -->
                        <div v-if="globalPayload.last_updated" class="text-xs text-gray-400">
                            Payload updated: {{ formatDate(globalPayload.last_updated) }}
                        </div>

                        <!-- Regional summaries from payload -->
                        <div v-if="globalPayload.regional_summaries && globalPayload.regional_summaries.length" class="mt-4">
                            <p class="text-sm font-semibold text-gray-700 mb-2">Regional Summaries</p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-100 text-left text-xs text-gray-400 uppercase tracking-wider">
                                            <th class="pb-2 pr-4 font-medium">Region</th>
                                            <th class="pb-2 pr-4 font-medium">Countries</th>
                                            <th class="pb-2 pr-4 font-medium">Risk Score</th>
                                            <th class="pb-2 font-medium">High Risk</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(rs, idx) in globalPayload.regional_summaries"
                                            :key="idx"
                                            class="border-b border-gray-50"
                                        >
                                            <td class="py-2 pr-4 font-medium text-gray-800">{{ rs.name ?? rs.region ?? '—' }}</td>
                                            <td class="py-2 pr-4 text-gray-600">{{ rs.country_count ?? rs.total_countries ?? '—' }}</td>
                                            <td class="py-2 pr-4">
                                                <span :class="['inline-block px-2 py-0.5 rounded-full text-xs font-semibold', riskBadgeClass(rs.average_risk_score ?? rs.risk_score)]">
                                                    {{ rs.average_risk_score ?? rs.risk_score ?? '—' }}
                                                </span>
                                            </td>
                                            <td class="py-2 text-gray-600">{{ rs.high_risk_count ?? '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Any extra unknown fields -->
                        <div v-if="Object.keys(extraGlobalFields).length" class="mt-4 bg-gray-50 border border-gray-100 rounded-xl p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Additional Data</p>
                            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div v-for="(val, key) in extraGlobalFields" :key="key">
                                    <dt class="text-xs text-gray-400 capitalize">{{ String(key).replace(/_/g, ' ') }}</dt>
                                    <dd class="text-sm font-medium text-gray-700 mt-0.5 break-words">
                                        {{ typeof val === 'object' ? JSON.stringify(val) : val }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div v-else class="text-sm text-gray-400 italic">No snapshot data available.</div>
                </div>

                <div v-else class="bg-white border border-dashed border-gray-200 rounded-xl p-10 text-center text-gray-400">
                    <p class="text-base font-medium">No global snapshot recorded yet.</p>
                    <p class="text-sm mt-1">Run the federation aggregation to generate a snapshot.</p>
                </div>
            </section>

            <!-- Regional Snapshots -->
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Regional Snapshots
                    <span v-if="regionalSnapshots.length" class="ml-2 text-sm font-normal text-gray-400">({{ regionalSnapshots.length }})</span>
                </h2>

                <div v-if="regionalSnapshots.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="snap in regionalSnapshots"
                        :key="snap.id"
                        class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
                    >
                        <!-- Region header -->
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <p class="font-semibold text-gray-900">
                                    {{ snap.federationRegion?.name ?? 'Unknown Region' }}
                                </p>
                                <span v-if="snap.federationRegion?.code" class="inline-block mt-0.5 text-xs font-mono bg-gray-100 text-gray-500 px-2 py-0.5 rounded">
                                    {{ snap.federationRegion.code }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ formatDate(snap.snapshot_at) }}</span>
                        </div>

                        <!-- Regional payload -->
                        <template v-if="getRegionalPayload(snap)">
                            <div class="grid grid-cols-2 gap-3">
                                <div
                                    v-for="(val, key) in getRegionalPayload(snap)"
                                    :key="key"
                                    class="bg-gray-50 rounded-lg p-3"
                                >
                                    <p class="text-xs text-gray-400 capitalize mb-0.5">{{ String(key).replace(/_/g, ' ') }}</p>
                                    <p class="text-sm font-semibold text-gray-800 break-words">
                                        {{ typeof val === 'object' ? JSON.stringify(val) : val }}
                                    </p>
                                </div>
                            </div>
                        </template>
                        <div v-else class="text-sm text-gray-400 italic">No payload data.</div>
                    </div>
                </div>

                <div v-else class="bg-white border border-dashed border-gray-200 rounded-xl p-10 text-center text-gray-400">
                    <p class="text-base font-medium">No regional snapshots available.</p>
                </div>
            </section>

            <!-- Federation Regions List -->
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Federation Regions
                    <span v-if="regions.length" class="ml-2 text-sm font-normal text-gray-400">({{ regions.length }})</span>
                </h2>

                <div v-if="regions.length" class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">
                    <div
                        v-for="region in regions"
                        :key="region.id"
                        class="flex items-center justify-between px-5 py-3"
                    >
                        <span class="text-sm font-medium text-gray-800">{{ region.name }}</span>
                        <span class="text-xs font-mono bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded">{{ region.code }}</span>
                    </div>
                </div>

                <div v-else class="bg-white border border-dashed border-gray-200 rounded-xl p-8 text-center text-gray-400">
                    <p class="text-sm">No federation regions defined.</p>
                </div>
            </section>

        </div>
    </AnalyticsLayout>
</template>
