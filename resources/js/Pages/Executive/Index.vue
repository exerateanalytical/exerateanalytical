<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AnalyticsLayout from '@/Layouts/AnalyticsLayout.vue';

const props = defineProps({
    ranking:   { type: Array, default: () => [] },
    watchlist: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
});

const selectedCountry = ref('');

const onCountrySelect = () => {
    if (selectedCountry.value) {
        router.visit(`/executive/${selectedCountry.value}`);
    }
};

const topFive = computed(() =>
    [...props.ranking]
        .sort((a, b) => (b.national_risk_score ?? 0) - (a.national_risk_score ?? 0))
        .slice(0, 5)
);

const criticalCount = computed(() =>
    props.watchlist.filter(w => w.status === 'critical' || w.status === 'deteriorating' || w.severity === 'critical').length
);

const riskLevelClass = (level) => {
    const map = {
        critical:    'bg-red-100 text-red-700',
        high:        'bg-orange-100 text-orange-700',
        elevated:    'bg-amber-100 text-amber-700',
        moderate:    'bg-yellow-100 text-yellow-700',
        low:         'bg-emerald-100 text-emerald-700',
        minimal:     'bg-green-100 text-green-700',
    };
    return map[level?.toLowerCase()] ?? 'bg-gray-100 text-gray-600';
};

const scoreColor = (score) => {
    if (score == null) return 'text-gray-400';
    if (score >= 70) return 'text-red-600';
    if (score >= 40) return 'text-amber-600';
    return 'text-emerald-600';
};

const trendIcon = (trend) => {
    if (!trend) return '';
    const t = trend.toLowerCase();
    if (t.includes('improv') || t === 'up' || t === 'decreasing') return '↓';
    if (t.includes('deterior') || t === 'down' || t === 'increasing') return '↑';
    if (t === 'stable') return '→';
    return '';
};

const trendClass = (trend) => {
    if (!trend) return 'text-gray-400';
    const t = trend.toLowerCase();
    if (t.includes('improv') || t === 'up' || t === 'decreasing') return 'text-emerald-600';
    if (t.includes('deterior') || t === 'down' || t === 'increasing') return 'text-red-600';
    return 'text-gray-500';
};
</script>

<template>
    <AnalyticsLayout title="Executive Intelligence">
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Executive Intelligence</h1>
                <p class="text-sm text-gray-500 mt-0.5">Strategic overview of federation risk and governance.</p>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <!-- Watchlist Warning Banner -->
            <div
                v-if="watchlist.length"
                class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-5 py-4"
            >
                <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-amber-800">
                        Alert Watchlist: {{ watchlist.length }} countr{{ watchlist.length === 1 ? 'y' : 'ies' }} flagged
                        <template v-if="criticalCount > 0">
                            — <span class="text-red-700">{{ criticalCount }} critical / deteriorating</span>
                        </template>
                    </p>
                    <p class="text-xs text-amber-600 mt-0.5">Review risk profiles for countries requiring immediate executive attention.</p>
                </div>
            </div>

            <!-- Top 5 Country Quick Navigation -->
            <section>
                <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                    <h2 class="text-lg font-semibold text-gray-800">Top Risk Countries</h2>

                    <!-- Jump-to dropdown -->
                    <div class="flex items-center gap-2">
                        <label for="country-jump" class="text-sm text-gray-500 shrink-0">Jump to country:</label>
                        <select
                            id="country-jump"
                            v-model="selectedCountry"
                            @change="onCountrySelect"
                            class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                        >
                            <option value="">Select a country...</option>
                            <option v-for="c in countries" :key="c.id" :value="c.id">
                                {{ c.name }} ({{ c.iso_code }})
                            </option>
                        </select>
                    </div>
                </div>

                <div v-if="topFive.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <a
                        v-for="item in topFive"
                        :key="item.country_id ?? item.id"
                        :href="`/executive/${item.country_id ?? item.id}`"
                        class="block bg-white border border-gray-200 rounded-xl shadow-sm p-5 hover:shadow-md hover:border-indigo-200 transition group"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-mono text-gray-400">{{ item.iso_code }}</span>
                            <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', riskLevelClass(item.risk_level)]">
                                {{ item.risk_level ?? 'N/A' }}
                            </span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-700 transition leading-tight mb-2">
                            {{ item.name }}
                        </p>
                        <p :class="['text-2xl font-bold', scoreColor(item.national_risk_score)]">
                            {{ item.national_risk_score != null ? Number(item.national_risk_score).toFixed(1) : '—' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">Risk Score</p>
                    </a>
                </div>

                <div v-else class="bg-white border border-dashed border-gray-200 rounded-xl p-10 text-center text-gray-400">
                    <p class="text-base font-medium">No ranking data available.</p>
                </div>
            </section>

            <!-- Full Risk Summary Table -->
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Risk Summary Table
                    <span v-if="ranking.length" class="ml-2 text-sm font-normal text-gray-400">({{ ranking.length }})</span>
                </h2>

                <div v-if="ranking.length" class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Country</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Risk Score</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Level</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Trend</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="(item, idx) in ranking"
                                    :key="item.country_id ?? item.id"
                                    class="hover:bg-gray-50 transition"
                                >
                                    <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ idx + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ item.name }}</div>
                                        <div class="text-xs text-gray-400 font-mono">{{ item.iso_code }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="['text-base font-bold', scoreColor(item.national_risk_score)]">
                                            {{ item.national_risk_score != null ? Number(item.national_risk_score).toFixed(1) : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', riskLevelClass(item.risk_level)]">
                                            {{ item.risk_level ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="['font-semibold', trendClass(item.trend)]">
                                            {{ trendIcon(item.trend) }} {{ item.trend ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a
                                            :href="`/executive/${item.country_id ?? item.id}`"
                                            class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold underline-offset-2 hover:underline"
                                        >
                                            View
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else class="bg-white border border-dashed border-gray-200 rounded-xl p-10 text-center text-gray-400">
                    <p class="text-base font-medium">No risk ranking data available.</p>
                    <p class="text-sm mt-1">Risk rankings are generated by the Executive Risk Dashboard service.</p>
                </div>
            </section>

        </div>
    </AnalyticsLayout>
</template>
