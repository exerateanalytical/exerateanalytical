<script setup>
import { ref } from 'vue';
import AnalyticsLayout from '@/Layouts/AnalyticsLayout.vue';
import ActionModal from '@/Components/Executive/ActionModal.vue';
import axios from 'axios';

const props = defineProps({
    hero:                 { type: Object, default: null },
    regions:              { type: Array,  default: () => [] },
    policy_radar:         { type: Array,  default: () => [] },
    civic_momentum:       { type: Object, default: null },
    contagion_forecast:   { type: Object, default: null },
    representation_index: { type: Array,  default: () => [] },
    generated_at:         { type: String, default: null },
});

// ── Live-refreshable state seeded from Inertia props ─────────────────────────
const hero                = ref(props.hero);
const regions             = ref(props.regions);
const policyRadar         = ref(props.policy_radar);
const civicMomentum       = ref(props.civic_momentum);
const contagionForecast   = ref(props.contagion_forecast);
const representationIndex = ref(props.representation_index);
const generatedAt         = ref(props.generated_at);
const refreshing          = ref(false);
const refreshError        = ref(null);

// ── Quick Actions / Modal ─────────────────────────────────────────────────────
const showModal        = ref(false);
const activeActionType = ref('');

function openModal(type) {
    activeActionType.value = type;
    showModal.value        = true;
}

function onActionSuccess() {
    showModal.value = false;
    showToast('Action executed successfully.');
    refresh();
}

// ── Toast ─────────────────────────────────────────────────────────────────────
const toastVisible = ref(false);
const toastMessage = ref('');
let   toastTimer   = null;

function showToast(msg) {
    toastMessage.value = msg;
    toastVisible.value = true;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toastVisible.value = false; }, 3500);
}

async function refresh() {
    refreshing.value  = true;
    refreshError.value = null;
    try {
        const { data } = await axios.get('/api/v1/executive/control-tower');
        const d = data.data ?? {};
        hero.value                = d.hero                 ?? null;
        regions.value             = d.regions              ?? [];
        policyRadar.value         = d.policy_radar         ?? [];
        civicMomentum.value       = d.civic_momentum       ?? null;
        contagionForecast.value   = d.contagion_forecast   ?? null;
        representationIndex.value = d.representation_index ?? [];
        generatedAt.value         = data.meta?.generated_at ?? null;
    } catch {
        refreshError.value = 'Refresh failed. Data may be stale.';
    } finally {
        refreshing.value = false;
    }
}

// ── Display helpers ───────────────────────────────────────────────────────────
const stabilityBorder = (val) => {
    if (val == null) return 'border-gray-200 bg-gray-50';
    if (val >= 70)   return 'border-emerald-200 bg-emerald-50';
    if (val >= 40)   return 'border-amber-200 bg-amber-50';
    return 'border-red-200 bg-red-50';
};

const stabilityText = (val) => {
    if (val == null) return 'text-gray-400';
    if (val >= 70)   return 'text-emerald-600';
    if (val >= 40)   return 'text-amber-600';
    return 'text-red-600';
};

const trustText = (val) => {
    if (val == null || val === 0) return 'text-gray-500';
    return val > 0 ? 'text-emerald-600' : 'text-red-500';
};

const riskBadge = (level) => ({
    LOW:    'bg-emerald-100 text-emerald-700',
    MEDIUM: 'bg-amber-100   text-amber-700',
    HIGH:   'bg-red-100     text-red-700',
})[level] ?? 'bg-gray-100 text-gray-600';

const scoreText = (score) => {
    if (score == null) return 'text-gray-400';
    if (score >= 70)   return 'text-red-600';
    if (score >= 40)   return 'text-amber-600';
    return 'text-emerald-600';
};

const scoreBar = (score) => {
    if (score >= 70) return 'bg-red-500';
    if (score >= 40) return 'bg-amber-400';
    return 'bg-emerald-500';
};

const cascadeText = (val) => {
    if (val == null) return 'text-gray-400';
    if (val > 0.6)   return 'text-red-600';
    if (val > 0.3)   return 'text-amber-600';
    return 'text-emerald-600';
};

const cascadeBar = (val) => {
    if (val > 0.6) return 'bg-red-500';
    if (val > 0.3) return 'bg-amber-400';
    return 'bg-emerald-500';
};

const partRate = (val) => {
    if (val == null) return 'text-gray-400';
    if (val >= 0.5)  return 'text-emerald-600';
    if (val >= 0.25) return 'text-amber-600';
    return 'text-red-600';
};

const gapText = (val) => {
    if (val == null) return 'text-gray-400';
    if (val < 0.1)   return 'text-emerald-600';
    if (val < 0.3)   return 'text-amber-600';
    return 'text-red-600';
};

const velocityText = (v) => {
    if (v == null) return 'text-gray-400';
    if (v > 1.0)   return 'text-emerald-600';
    if (v < 0.8)   return 'text-red-500';
    return 'text-amber-600';
};

const velocityLabel = (v) => {
    if (v == null) return '—';
    if (v > 1.5)   return '↑↑ Accelerating';
    if (v > 1.0)   return '↑ Growing';
    if (v >= 1.0)  return '→ Stable';
    return '↓ Slowing';
};

const itemTypeBadge = (type) => ({
    poll:     'bg-blue-100   text-blue-700',
    petition: 'bg-violet-100 text-violet-700',
    policy:   'bg-indigo-100 text-indigo-700',
})[type] ?? 'bg-gray-100 text-gray-600';

const fmt = (iso) => {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString(undefined, {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    } catch { return iso; }
};

const pct = (v, decimals = 1) =>
    v != null ? (v * 100).toFixed(decimals) + '%' : '—';
</script>

<template>
    <AnalyticsLayout title="National Control Tower">

        <!-- ── Page header ─────────────────────────────────────────────────── -->
        <template #header>
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <div class="flex items-center gap-2 text-sm mb-1">
                        <a href="/executive" class="text-indigo-600 hover:text-indigo-800 font-medium transition">Executive</a>
                        <span class="text-gray-300">/</span>
                        <span class="font-semibold text-gray-800">Control Tower</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">National Control Tower</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Unified governance intelligence.
                        <span v-if="generatedAt" class="text-gray-400 ml-1">
                            Generated {{ fmt(generatedAt) }}
                        </span>
                    </p>
                </div>

                <div class="flex items-center gap-3 mt-1">
                    <span v-if="refreshError" class="text-xs text-red-600 max-w-[200px]">{{ refreshError }}</span>
                    <button
                        @click="refresh"
                        :disabled="refreshing"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
                    >
                        <svg
                            :class="['w-4 h-4 shrink-0', refreshing && 'animate-spin']"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ refreshing ? 'Refreshing…' : 'Refresh' }}
                    </button>
                </div>
            </div>
        </template>

        <!-- ── Page body ───────────────────────────────────────────────────── -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <!-- ── A. HERO SIGNALS ─────────────────────────────────────────── -->
            <section>
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Hero Signals</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                    <!-- National Stability -->
                    <div :class="['rounded-xl border p-5 shadow-sm transition', stabilityBorder(hero?.national_stability)]">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            National Stability
                        </p>
                        <p :class="['text-4xl font-extrabold leading-none tabular-nums', stabilityText(hero?.national_stability)]">
                            {{ hero?.national_stability != null ? hero.national_stability.toFixed(1) : '—' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-2">/ 100 — higher is stable</p>
                    </div>

                    <!-- Trust Momentum -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            Trust Momentum
                        </p>
                        <p :class="['text-4xl font-extrabold leading-none tabular-nums', trustText(hero?.trust_momentum)]">
                            <template v-if="hero?.trust_momentum != null">
                                {{ hero.trust_momentum > 0 ? '+' : '' }}{{ hero.trust_momentum.toFixed(2) }}
                            </template>
                            <template v-else>—</template>
                        </p>
                        <p class="text-xs text-gray-400 mt-2">avg Δ reputation · last 24 h</p>
                    </div>

                    <!-- Policy Risk Level -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            Policy Risk
                        </p>
                        <div class="mt-1">
                            <span
                                v-if="hero?.policy_risk_level"
                                :class="['inline-block text-xl font-bold px-3 py-1.5 rounded-lg leading-none', riskBadge(hero.policy_risk_level)]"
                            >
                                {{ hero.policy_risk_level }}
                            </span>
                            <span v-else class="text-4xl font-extrabold text-gray-300">—</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">from severity anomaly index</p>
                    </div>

                    <!-- Civic Activity Delta -->
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-2">
                            Civic Activity
                        </p>
                        <p class="text-4xl font-extrabold leading-none text-indigo-700 tabular-nums">
                            {{ hero?.civic_activity_delta_24h ?? '—' }}
                        </p>
                        <p class="text-xs text-indigo-400 mt-2">votes + signatures · last 24 h</p>
                    </div>
                </div>
            </section>

            <!-- ── B + E + F. REGIONS / CONTAGION / REPRESENTATION ────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- B. Regional Status Map (2/3) -->
                <section class="lg:col-span-2">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Regional Status Map</h2>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                        <div v-if="regions.length" class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Region</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Risk</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Fragility</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Alerts</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Bar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="r in regions"
                                        :key="r.region_id"
                                        class="hover:bg-gray-50 transition"
                                    >
                                        <td class="px-4 py-3">
                                            <span class="font-semibold text-gray-800 text-sm">{{ r.region_code ?? r.region_id }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span :class="['font-bold text-base tabular-nums', scoreText(r.mean_risk_score)]">
                                                {{ r.mean_risk_score != null ? r.mean_risk_score.toFixed(1) : '—' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-xs font-mono text-gray-500">
                                            {{ r.mean_fragility_index != null ? pct(r.mean_fragility_index, 0) : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span :class="[
                                                'inline-block px-2 py-0.5 rounded-full text-xs font-bold',
                                                r.active_alert_count > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-400'
                                            ]">
                                                {{ r.active_alert_count }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden w-28">
                                                <div
                                                    :class="['h-full rounded-full transition-all duration-500', scoreBar(r.mean_risk_score)]"
                                                    :style="{ width: `${Math.min(100, Math.max(0, r.mean_risk_score ?? 0))}%` }"
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="px-6 py-10 text-center text-gray-400 text-sm">
                            No regional snapshot data available.
                        </div>
                    </div>
                </section>

                <!-- Right column: Quick Actions + E. Contagion + F. Representation -->
                <section class="space-y-6">

                    <!-- QUICK ACTIONS panel ──────────────────────────────── -->
                    <div>
                        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Governance Actions</h2>
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 grid grid-cols-2 gap-3">

                            <!-- Simulate Contagion (red — risk intervention) -->
                            <button
                                @click="openModal('simulate_contagion')"
                                class="flex flex-col items-center gap-2 px-3 py-4 rounded-xl border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 hover:border-red-300 transition text-center group"
                            >
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span class="text-xs font-semibold leading-tight">Simulate Contagion</span>
                            </button>

                            <!-- Launch Civic Poll (emerald — stabilising) -->
                            <button
                                @click="openModal('launch_poll')"
                                class="flex flex-col items-center gap-2 px-3 py-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300 transition text-center group"
                            >
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <span class="text-xs font-semibold leading-tight">Launch Civic Poll</span>
                            </button>

                            <!-- Open Policy Consultation (amber — consultation) -->
                            <button
                                @click="openModal('open_consultation')"
                                class="flex flex-col items-center gap-2 px-3 py-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 hover:border-amber-300 transition text-center group"
                            >
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
                                </svg>
                                <span class="text-xs font-semibold leading-tight">Open Consultation</span>
                            </button>

                            <!-- Flag Region (amber — warning) -->
                            <button
                                @click="openModal('flag_region')"
                                class="flex flex-col items-center gap-2 px-3 py-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 hover:border-amber-300 transition text-center group"
                            >
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M3 21v-4m0 0V5a2 2 0 012-2h6.5L13 5h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                </svg>
                                <span class="text-xs font-semibold leading-tight">Flag Region</span>
                            </button>

                        </div>
                    </div>

                    <!-- E. Contagion Forecast -->
                    <div>
                        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Contagion Forecast</h2>
                        <div
                            v-if="contagionForecast"
                            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-500">Cascade Index</span>
                                <span :class="['text-2xl font-extrabold tabular-nums', cascadeText(contagionForecast.cascade_index)]">
                                    {{ contagionForecast.cascade_index?.toFixed(3) ?? '—' }}
                                </span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div
                                    :class="['h-full rounded-full transition-all duration-500', cascadeBar(contagionForecast.cascade_index)]"
                                    :style="{ width: `${Math.min(100, (contagionForecast.cascade_index ?? 0) * 100)}%` }"
                                />
                            </div>
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs pt-1">
                                <div>
                                    <dt class="text-gray-400">Iterations</dt>
                                    <dd class="font-semibold text-gray-800 mt-0.5">{{ contagionForecast.iteration_count ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-400">Origin Region</dt>
                                    <dd class="font-semibold text-gray-800 font-mono mt-0.5 truncate">
                                        {{ contagionForecast.origin_region_id ?? '—' }}
                                    </dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="text-gray-400">Executed</dt>
                                    <dd class="font-semibold text-gray-800 mt-0.5">{{ fmt(contagionForecast.executed_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div v-else class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center text-sm text-gray-400">
                            No contagion run recorded.
                        </div>
                    </div>

                    <!-- F. Representation Index -->
                    <div>
                        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Representation Index</h2>
                        <div
                            v-if="representationIndex.length"
                            class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden"
                        >
                            <table class="min-w-full text-xs">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-3 py-2.5 text-left font-semibold text-gray-500 uppercase tracking-wider">Region</th>
                                        <th class="px-3 py-2.5 text-right font-semibold text-gray-500 uppercase tracking-wider">Part. %</th>
                                        <th class="px-3 py-2.5 text-right font-semibold text-gray-500 uppercase tracking-wider">Gap %</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="m in representationIndex"
                                        :key="m.region_id"
                                        class="hover:bg-gray-50 transition"
                                    >
                                        <td class="px-3 py-2 font-mono text-gray-600 truncate max-w-[90px]" :title="m.region_id">
                                            {{ m.region_id }}
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <span :class="['font-semibold', partRate(m.participation_rate)]">
                                                {{ m.participation_rate != null ? pct(m.participation_rate) : '—' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <span :class="['font-semibold', gapText(m.representation_gap)]">
                                                {{ m.representation_gap != null ? pct(m.representation_gap) : '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center text-sm text-gray-400">
                            No representation metrics available.
                        </div>
                    </div>
                </section>
            </div>

            <!-- ── C + D. POLICY RADAR + CIVIC MOMENTUM ───────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                <!-- C. Policy Radar (3/5 cols) -->
                <section class="lg:col-span-3">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        Policy Radar <span class="text-gray-300 font-normal">— top 5 by engagement</span>
                    </h2>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                        <div v-if="policyRadar.length" class="divide-y divide-gray-100">
                            <div
                                v-for="(item, idx) in policyRadar"
                                :key="item.id"
                                class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition"
                            >
                                <!-- Rank -->
                                <span class="text-lg font-bold text-gray-200 w-5 shrink-0 text-center tabular-nums">
                                    {{ idx + 1 }}
                                </span>

                                <!-- Title + type -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate leading-snug">{{ item.title }}</p>
                                    <span
                                        :class="['inline-block text-xs font-semibold px-2 py-0.5 rounded-full mt-1 capitalize', itemTypeBadge(item.type)]"
                                    >
                                        {{ item.type }}
                                    </span>
                                </div>

                                <!-- Score -->
                                <div class="text-right shrink-0">
                                    <p class="text-base font-bold text-gray-700 tabular-nums">
                                        {{ item.score != null ? item.score.toFixed(1) : '—' }}
                                    </p>
                                    <p class="text-xs text-gray-400">engagement</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="px-6 py-10 text-center text-sm text-gray-400">
                            No active civic items found.
                        </div>
                    </div>
                </section>

                <!-- D. Civic Momentum (2/5 cols) -->
                <section class="lg:col-span-2">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Civic Momentum</h2>

                    <div v-if="civicMomentum" class="grid grid-cols-2 gap-3">
                        <!-- New Polls -->
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 text-center">
                            <p class="text-3xl font-extrabold text-blue-600 tabular-nums">
                                {{ civicMomentum.new_polls_24h ?? 0 }}
                            </p>
                            <p class="text-xs font-medium text-gray-500 mt-1.5">New Polls</p>
                            <p class="text-xs text-gray-300">last 24 h</p>
                        </div>

                        <!-- New Petitions -->
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 text-center">
                            <p class="text-3xl font-extrabold text-violet-600 tabular-nums">
                                {{ civicMomentum.new_petitions_24h ?? 0 }}
                            </p>
                            <p class="text-xs font-medium text-gray-500 mt-1.5">New Petitions</p>
                            <p class="text-xs text-gray-300">last 24 h</p>
                        </div>

                        <!-- New Policies -->
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 text-center">
                            <p class="text-3xl font-extrabold text-indigo-600 tabular-nums">
                                {{ civicMomentum.new_policies_24h ?? 0 }}
                            </p>
                            <p class="text-xs font-medium text-gray-500 mt-1.5">New Policies</p>
                            <p class="text-xs text-gray-300">last 24 h</p>
                        </div>

                        <!-- Reaction Velocity -->
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 text-center">
                            <p :class="['text-3xl font-extrabold tabular-nums', velocityText(civicMomentum.reaction_velocity)]">
                                {{ civicMomentum.reaction_velocity != null
                                    ? civicMomentum.reaction_velocity.toFixed(2) + '×'
                                    : '—' }}
                            </p>
                            <p class="text-xs font-medium text-gray-500 mt-1.5">Reaction Velocity</p>
                            <p :class="['text-xs', velocityText(civicMomentum.reaction_velocity)]">
                                {{ velocityLabel(civicMomentum.reaction_velocity) }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="bg-gray-50 border border-gray-100 rounded-xl p-5 text-center text-sm text-gray-400">
                        No civic momentum data.
                    </div>
                </section>
            </div>

        </div>
        <!-- ── Action Modal ─────────────────────────────────────────────── -->
        <Transition
            enter-from-class="opacity-0 scale-95"
            enter-active-class="transition duration-150 ease-out"
            enter-to-class="opacity-100 scale-100"
            leave-from-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0 scale-95"
        >
            <ActionModal
                v-if="showModal"
                :action-type="activeActionType"
                :regions="regions"
                @close="showModal = false"
                @success="onActionSuccess"
            />
        </Transition>

        <!-- ── Toast ───────────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-from-class="opacity-0 translate-y-2"
                enter-active-class="transition duration-200 ease-out"
                enter-to-class="opacity-100 translate-y-0"
                leave-from-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div
                    v-if="toastVisible"
                    class="fixed bottom-6 right-6 z-[60] flex items-center gap-3 px-5 py-3.5 bg-gray-900 text-white text-sm font-semibold rounded-xl shadow-xl"
                >
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ toastMessage }}
                </div>
            </Transition>
        </Teleport>

    </AnalyticsLayout>
</template>
