<script setup>
import { computed } from 'vue';
import AnalyticsLayout from '@/Layouts/AnalyticsLayout.vue';

const props = defineProps({
    country: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    drivers: { type: Object, default: () => ({}) },
    history: { type: Array,  default: () => [] },
    alerts:  { type: Array,  default: () => [] },
});

// --- Score / level helpers ---
const scoreColor = (score) => {
    if (score == null) return 'text-gray-400';
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
    if (trend === 'improving')     return { symbol: '↑', cls: 'text-green-600 font-bold' };
    if (trend === 'deteriorating') return { symbol: '↓', cls: 'text-red-600 font-bold' };
    return { symbol: '→', cls: 'text-gray-400' };
};

// --- Date helpers ---
const formatDate = (value) => {
    if (!value) return '—';
    const d = new Date(value);
    if (isNaN(d.getTime())) return value;
    return d.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatMonth = (ym) => {
    // Accepts "2025-01" → "Jan 2025"
    if (!ym) return ym;
    const parts = String(ym).split('-');
    if (parts.length < 2) return ym;
    const d = new Date(Number(parts[0]), Number(parts[1]) - 1, 1);
    if (isNaN(d.getTime())) return ym;
    return d.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
};

// --- Domain drivers ---
const domainConfig = [
    { key: 'governance',     label: 'Governance',     barColor: 'bg-indigo-500' },
    { key: 'fiscal',         label: 'Fiscal',         barColor: 'bg-amber-500'  },
    { key: 'accountability', label: 'Accountability', barColor: 'bg-emerald-500'},
];

const domainScore = (key) => {
    const val = props.drivers?.[key];
    return val != null ? Number(val) : null;
};

const domainWeight = (key) => {
    const w = props.drivers?.weights?.[key];
    return w != null ? Number(w) : null;
};

// --- History (last 12 months) ---
const recentHistory = computed(() => {
    return [...(props.history ?? [])].slice(-12);
});

const maxHistoryScore = computed(() => {
    const scores = recentHistory.value.map(h => Number(h.weighted_score ?? 0));
    return scores.length ? Math.max(...scores, 1) : 100;
});

// --- Severity badge ---
const severityBadge = (severity) => {
    const map = {
        critical: 'bg-red-100 text-red-700',
        high:     'bg-orange-100 text-orange-700',
        medium:   'bg-amber-100 text-amber-700',
        low:      'bg-green-100 text-green-700',
    };
    return map[severity] ?? 'bg-gray-100 text-gray-600';
};

const historyBarWidth = (score) => {
    const pct = (Number(score ?? 0) / maxHistoryScore.value) * 100;
    return `${Math.min(100, Math.max(0, pct)).toFixed(1)}%`;
};
</script>

<template>
    <AnalyticsLayout :title="`${country.name} — Risk Profile`">
        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="/risk" class="text-sm text-gray-500 hover:text-gray-700 transition">← Risk Dashboard</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-medium text-gray-900">{{ country.name }}</span>
                <span
                    v-if="summary.risk_level"
                    :class="['text-xs font-semibold px-2 py-0.5 rounded-full ml-1', riskLevelBadge(summary.risk_level)]"
                >
                    {{ summary.risk_level }}
                </span>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <!-- Hero card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-6">

                    <!-- Score block -->
                    <div class="text-center sm:text-left shrink-0">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">National Risk Score</p>
                        <span :class="['text-6xl font-extrabold tabular-nums leading-none', scoreColor(summary.national_risk_score)]">
                            {{ summary.national_risk_score != null ? Number(summary.national_risk_score).toFixed(1) : '—' }}
                        </span>
                    </div>

                    <div class="flex-1 flex flex-wrap items-center gap-4">
                        <!-- Risk level badge -->
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Risk Level</p>
                            <span :class="['text-sm font-semibold px-3 py-1 rounded-full', riskLevelBadge(summary.risk_level)]">
                                {{ summary.risk_level ?? '—' }}
                            </span>
                        </div>

                        <!-- Trend -->
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Trend</p>
                            <div class="flex items-center gap-1.5">
                                <span :class="['text-2xl leading-none', trendIcon(summary.trend).cls]">
                                    {{ trendIcon(summary.trend).symbol }}
                                </span>
                                <span class="text-sm text-gray-600 capitalize">{{ summary.trend ?? 'stable' }}</span>
                            </div>
                        </div>

                        <!-- Alerts -->
                        <div v-if="summary.alerts_count != null">
                            <p class="text-xs text-gray-400 mb-1">Active Alerts</p>
                            <span class="text-sm font-bold text-red-600">{{ summary.alerts_count }}</span>
                        </div>

                        <!-- Last assessed -->
                        <div v-if="summary.last_assessed" class="ml-auto">
                            <p class="text-xs text-gray-400 mb-1">Last Assessed</p>
                            <span class="text-sm text-gray-600">{{ formatDate(summary.last_assessed) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Domain Drivers -->
            <div>
                <h2 class="text-base font-semibold text-gray-800 mb-3">Risk Domain Drivers</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div
                        v-for="domain in domainConfig"
                        :key="domain.key"
                        class="bg-white rounded-xl border border-gray-200 shadow-sm p-5"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-sm font-semibold text-gray-700">{{ domain.label }}</p>
                            <span v-if="domainWeight(domain.key) != null" class="text-xs text-gray-400 font-medium">
                                Weight {{ (domainWeight(domain.key) * 100).toFixed(0) }}%
                            </span>
                        </div>
                        <div class="flex items-end gap-2 mb-3">
                            <span :class="['text-3xl font-extrabold tabular-nums leading-none', scoreColor(domainScore(domain.key))]">
                                {{ domainScore(domain.key) != null ? domainScore(domain.key).toFixed(1) : '—' }}
                            </span>
                            <span class="text-xs text-gray-400 mb-0.5">/ 100</span>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div
                                :class="['h-2 rounded-full transition-all duration-500', domain.barColor]"
                                :style="{ width: domainScore(domain.key) != null ? `${Math.min(100, domainScore(domain.key))}%` : '0%' }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Risk History -->
            <div v-if="recentHistory.length > 0" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800 text-base">Risk History</h2>
                    <span class="text-xs text-gray-400">Last {{ recentHistory.length }} months</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <th class="px-6 py-3 text-left">Month</th>
                                <th class="px-6 py-3 text-right w-24">Score</th>
                                <th class="px-6 py-3 text-left">Distribution</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="row in recentHistory"
                                :key="row.month"
                                class="hover:bg-gray-50 transition"
                            >
                                <td class="px-6 py-2.5 text-gray-700 font-medium">
                                    {{ formatMonth(row.month) }}
                                </td>
                                <td class="px-6 py-2.5 text-right">
                                    <span :class="['font-bold tabular-nums', scoreColor(row.weighted_score)]">
                                        {{ row.weighted_score != null ? Number(row.weighted_score).toFixed(1) : '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-2.5 w-1/2">
                                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                                        <div
                                            class="h-1.5 rounded-full bg-indigo-400 transition-all duration-500"
                                            :style="{ width: historyBarWidth(row.weighted_score) }"
                                        ></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Active Alerts -->
            <div v-if="alerts.length > 0" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800 text-base">Active Alerts</h2>
                    <span class="text-xs bg-red-100 text-red-700 font-semibold px-2 py-0.5 rounded-full">
                        {{ alerts.length }} active
                    </span>
                </div>
                <div class="divide-y divide-gray-50">
                    <div
                        v-for="alert in alerts"
                        :key="alert.id"
                        class="px-6 py-4 hover:bg-gray-50 transition"
                    >
                        <div class="flex items-start gap-3">
                            <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full shrink-0 mt-0.5', severityBadge(alert.severity)]">
                                {{ alert.severity }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800">{{ alert.title }}</p>
                                <p v-if="alert.message" class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ alert.message }}</p>
                            </div>
                            <span class="text-xs text-gray-400 shrink-0 mt-0.5">{{ formatDate(alert.triggered_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom nav links -->
            <div class="flex items-center justify-between pt-2">
                <a
                    href="/risk"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition shadow-sm"
                >
                    ← Risk Dashboard
                </a>
                <a
                    :href="`/countries/${country.id}`"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shadow-sm"
                >
                    View Country Profile →
                </a>
            </div>

        </div>
    </AnalyticsLayout>
</template>
