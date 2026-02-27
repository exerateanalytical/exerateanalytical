<script setup>
import { computed, ref } from 'vue';
import AnalyticsLayout from '@/Layouts/AnalyticsLayout.vue';

const props = defineProps({
    ranking:   { type: Array, default: () => [] },
    watchlist: { type: Array, default: () => [] },
});

// --- Sorting state ---
const sortKey = ref('national_risk_score');
const sortDir = ref('desc');

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'desc';
    }
};

const sortedRanking = computed(() => {
    return [...props.ranking].sort((a, b) => {
        const av = a[sortKey.value] ?? '';
        const bv = b[sortKey.value] ?? '';
        if (typeof av === 'number' && typeof bv === 'number') {
            return sortDir.value === 'asc' ? av - bv : bv - av;
        }
        return sortDir.value === 'asc'
            ? String(av).localeCompare(String(bv))
            : String(bv).localeCompare(String(av));
    });
});

// --- Summary stats ---
const totalCountries  = computed(() => props.ranking.length);
const criticalCount   = computed(() => props.ranking.filter(r => r.risk_level === 'critical').length);
const improvingCount  = computed(() => props.ranking.filter(r => r.trend === 'improving').length);

// --- Helpers ---
const scoreColor = (score) => {
    if (score == null) return 'text-gray-500';
    if (score < 40)   return 'text-green-600';
    if (score <= 70)  return 'text-amber-600';
    return 'text-red-600';
};

const riskLevelBadge = (level) => {
    const map = {
        critical: 'bg-red-100 text-red-700',
        high:     'bg-orange-100 text-orange-700',
        moderate: 'bg-amber-100 text-amber-700',
        low:      'bg-green-100 text-green-700',
    };
    return map[level] ?? 'bg-gray-100 text-gray-600';
};

const trendIcon = (trend) => {
    if (trend === 'improving')    return { symbol: '↑', cls: 'text-green-600 font-bold' };
    if (trend === 'deteriorating') return { symbol: '↓', cls: 'text-red-600 font-bold' };
    return { symbol: '→', cls: 'text-gray-400' };
};

const sortIndicator = (key) => {
    if (sortKey.value !== key) return '';
    return sortDir.value === 'asc' ? ' ▲' : ' ▼';
};
</script>

<template>
    <AnalyticsLayout title="Risk Intelligence Dashboard">
        <template #header>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Risk Intelligence Dashboard</h1>
                <p class="text-sm text-gray-500 mt-0.5">Global risk overview — country rankings, alert watchlist and trend analysis</p>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <!-- Summary stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total Countries</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ totalCountries }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <p class="text-xs font-semibold text-red-400 uppercase tracking-wide">Critical Risk</p>
                    <p class="mt-1 text-3xl font-bold text-red-600">{{ criticalCount }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <p class="text-xs font-semibold text-green-500 uppercase tracking-wide">Improving</p>
                    <p class="mt-1 text-3xl font-bold text-green-600">{{ improvingCount }}</p>
                </div>
            </div>

            <!-- Alert Watchlist -->
            <div v-if="watchlist.length > 0" class="bg-white rounded-xl border-2 border-red-200 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    <h2 class="font-semibold text-red-700 text-base">Alert Watchlist</h2>
                    <span class="ml-auto text-xs bg-red-100 text-red-700 font-semibold px-2 py-0.5 rounded-full">
                        {{ watchlist.length }} {{ watchlist.length === 1 ? 'country' : 'countries' }}
                    </span>
                </div>
                <div class="divide-y divide-red-50">
                    <div
                        v-for="item in watchlist"
                        :key="item.country_id ?? item.id"
                        class="flex items-center gap-4 py-3"
                    >
                        <span class="text-xs font-mono font-semibold text-gray-400 w-10 shrink-0">
                            {{ item.iso_code }}
                        </span>
                        <span class="flex-1 text-sm font-medium text-gray-900">{{ item.name }}</span>
                        <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full shrink-0', riskLevelBadge(item.risk_level)]">
                            {{ item.risk_level }}
                        </span>
                        <span :class="['text-sm font-bold w-12 text-right shrink-0', scoreColor(item.national_risk_score)]">
                            {{ item.national_risk_score != null ? Number(item.national_risk_score).toFixed(1) : '—' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Risk Ranking Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800 text-base">Country Risk Rankings</h2>
                    <span class="text-xs text-gray-400">{{ sortedRanking.length }} countries</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <th class="px-4 py-3 text-left w-12">#</th>
                                <th
                                    class="px-4 py-3 text-left cursor-pointer hover:text-gray-800 select-none"
                                    @click="toggleSort('name')"
                                >Country{{ sortIndicator('name') }}</th>
                                <th
                                    class="px-4 py-3 text-right cursor-pointer hover:text-gray-800 select-none"
                                    @click="toggleSort('national_risk_score')"
                                >Risk Score{{ sortIndicator('national_risk_score') }}</th>
                                <th
                                    class="px-4 py-3 text-center cursor-pointer hover:text-gray-800 select-none"
                                    @click="toggleSort('risk_level')"
                                >Risk Level{{ sortIndicator('risk_level') }}</th>
                                <th
                                    class="px-4 py-3 text-center cursor-pointer hover:text-gray-800 select-none"
                                    @click="toggleSort('trend')"
                                >Trend{{ sortIndicator('trend') }}</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="(item, index) in sortedRanking"
                                :key="item.country_id"
                                class="hover:bg-gray-50 transition"
                            >
                                <td class="px-4 py-3 text-gray-400 font-mono text-xs">
                                    {{ index + 1 }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono font-semibold text-gray-400 w-8 shrink-0">
                                            {{ item.iso_code }}
                                        </span>
                                        <span class="font-medium text-gray-900">{{ item.name }}</span>
                                        <span v-if="item.alerts_count > 0" class="text-xs bg-red-100 text-red-600 font-semibold px-1.5 py-0.5 rounded-full">
                                            {{ item.alerts_count }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span :class="['text-base font-bold tabular-nums', scoreColor(item.national_risk_score)]">
                                        {{ item.national_risk_score != null ? Number(item.national_risk_score).toFixed(1) : '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', riskLevelBadge(item.risk_level)]">
                                        {{ item.risk_level ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="item.trend" :class="['text-sm', trendIcon(item.trend).cls]">
                                        {{ trendIcon(item.trend).symbol }}
                                        <span class="sr-only">{{ item.trend }}</span>
                                    </span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a
                                        :href="`/risk/${item.country_id}`"
                                        class="inline-flex items-center px-3 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition"
                                    >
                                        View Detail
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="sortedRanking.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">
                                    No risk data available.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AnalyticsLayout>
</template>
