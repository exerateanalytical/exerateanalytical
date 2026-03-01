<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// ── Props / Emits ─────────────────────────────────────────────────────────────
const props = defineProps({
    recommendation: { type: Object, required: true },
});

const emit = defineEmits(['close']);

// ── Fetch state ───────────────────────────────────────────────────────────────
const loading   = ref(true);
const error     = ref(null);
const scenarios = ref([]);
const recMeta   = ref(null);
const rankings  = ref([]);

onMounted(async () => {
    try {
        const [scenRes, rankRes] = await Promise.all([
            axios.get(`/api/v1/executive/recommendations/${props.recommendation.id}/scenarios`),
            axios.get(`/api/v1/executive/recommendations/${props.recommendation.id}/decisions`)
                .catch(() => ({ data: { data: [] } })),
        ]);
        scenarios.value = scenRes.data.data           ?? [];
        recMeta.value   = scenRes.data.recommendation ?? null;
        rankings.value  = rankRes.data.data           ?? [];
    } catch {
        error.value = 'Could not load scenario projections. Please try again.';
    } finally {
        loading.value = false;
    }
});

// ── Keyboard: Escape to close ─────────────────────────────────────────────────
function onKey(e) { if (e.key === 'Escape') emit('close'); }
onMounted(() => document.addEventListener('keydown', onKey));
onUnmounted(() => document.removeEventListener('keydown', onKey));

// ── Scenario display metadata ─────────────────────────────────────────────────
const SCENARIO_META = {
    accept_action:  {
        label:   'Accept Action',
        sublabel: 'Full intervention',
        headerCls: 'bg-emerald-50 border-emerald-200',
        titleCls:  'text-emerald-800',
        iconPath:  'M5 13l4 4L19 7',
    },
    delayed_action: {
        label:    'Delay Action',
        sublabel: '50% effect (deferred)',
        headerCls: 'bg-amber-50 border-amber-200',
        titleCls:  'text-amber-800',
        iconPath:  'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    ignore_action: {
        label:    'Ignore',
        sublabel: 'No intervention',
        headerCls: 'bg-red-50 border-red-200',
        titleCls:  'text-red-800',
        iconPath:  'M6 18L18 6M6 6l12 12',
    },
};

const smeta = (type) => SCENARIO_META[type] ?? {
    label: type, sublabel: '', headerCls: 'bg-gray-50 border-gray-200', titleCls: 'text-gray-700',
    iconPath: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};

// ── Delta helpers ─────────────────────────────────────────────────────────────

function stabilityDelta(scenario) {
    const base = scenario.projection_payload?.baseline_stability ?? null;
    if (base === null) return null;
    return round2(scenario.projected_stability - base);
}

function cascadeDelta(scenario) {
    const base = scenario.projection_payload?.baseline_cascade_index ?? null;
    if (base === null) return null;
    return round3(scenario.projected_cascade_index - base);
}

function trustDelta(scenario) {
    return scenario.projected_trust_delta ?? null;
}

function round2(v) { return Math.round(v * 100) / 100; }
function round3(v) { return Math.round(v * 1000) / 1000; }

// Color logic — positive stability/trust is good; negative cascade is good.
function stabilityColor(delta) {
    if (delta === null) return 'text-gray-400';
    if (delta > 0.5)  return 'text-emerald-600';
    if (delta > -0.5) return 'text-amber-600';
    return 'text-red-600';
}

function cascadeColor(delta) {
    if (delta === null) return 'text-gray-400';
    if (delta < -0.005) return 'text-emerald-600';   // lower cascade = better
    if (delta < 0.005)  return 'text-amber-600';
    return 'text-red-600';
}

function trustColor(delta) {
    if (delta === null) return 'text-gray-400';
    if (delta > 0.05)  return 'text-emerald-600';
    if (delta > -0.05) return 'text-amber-600';
    return 'text-red-600';
}

function fmtStability(delta) {
    if (delta === null) return '—';
    const sign = delta > 0 ? '+' : '';
    return sign + delta.toFixed(1);
}

function fmtCascade(delta) {
    if (delta === null) return '—';
    if (delta < 0) return '↓ ' + Math.abs(delta).toFixed(3);
    if (delta > 0) return '↑ ' + delta.toFixed(3);
    return '→ 0.000';
}

function fmtTrust(delta) {
    if (delta === null) return '—';
    const sign = delta > 0 ? '+' : '';
    return sign + delta.toFixed(2);
}

// ── Severity badge ─────────────────────────────────────────────────────────────
const SEVERITY_CLS = {
    critical: 'bg-red-100    text-red-700    border-red-200',
    high:     'bg-orange-100 text-orange-700 border-orange-200',
    medium:   'bg-amber-100  text-amber-700  border-amber-200',
    low:      'bg-blue-100   text-blue-700   border-blue-200',
};
const sevCls = (s) => SEVERITY_CLS[s] ?? SEVERITY_CLS.low;

// ── CT-13 Decision Ranking helpers ────────────────────────────────────────────
// keyed by scenario_type for O(1) lookup in template
const rankingByType = computed(() => {
    const map = {};
    for (const r of rankings.value) map[r.scenario_type] = r;
    return map;
});

function rankingFor(scenarioType) {
    return rankingByType.value[scenarioType] ?? null;
}

// Normalise decision_score (range -100..+100) to a 0-100% bar width
function decisionScoreWidth(score) {
    return ((score + 100) / 2).toFixed(1) + '%';
}

function decisionScoreBarCls(score) {
    if (score >= 20)  return 'bg-emerald-500';
    if (score >= 0)   return 'bg-amber-400';
    return 'bg-red-400';
}

function decisionScoreTextCls(score) {
    if (score >= 20)  return 'text-emerald-700';
    if (score >= 0)   return 'text-amber-700';
    return 'text-red-600';
}

// Short human-readable impact summary shown under the #1 badge
function impactSummary(ranking) {
    const parts = [];
    if (ranking.projected_stability_delta != null) {
        const v = Number(ranking.projected_stability_delta);
        parts.push((v >= 0 ? '+' : '') + v.toFixed(1) + ' stability');
    }
    if (ranking.projected_trust_delta != null) {
        const v = Number(ranking.projected_trust_delta);
        parts.push((v >= 0 ? '+' : '') + v.toFixed(2) + ' trust');
    }
    if (ranking.projected_cascade_delta != null) {
        const v = Number(ranking.projected_cascade_delta);
        const dir = v < 0 ? '↓' : v > 0 ? '↑' : '→';
        parts.push(dir + ' cascade ' + Math.abs(v).toFixed(3));
    }
    return parts.join(' · ');
}
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <!-- Dimmed overlay -->
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('close')" />

            <!-- Modal card -->
            <div
                class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh]"
                @click.stop
            >
                <!-- Header -->
                <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-gray-100 shrink-0">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1.5">
                            <!-- Sparkle / scenarios icon -->
                            <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <h2 class="text-sm font-bold text-gray-900">Recommended Action Outcomes</h2>
                            <span
                                v-if="recMeta?.severity ?? recommendation.severity"
                                :class="['text-xs font-semibold px-2 py-0.5 rounded-full border', sevCls(recMeta?.severity ?? recommendation.severity)]"
                            >
                                {{ (recMeta?.severity ?? recommendation.severity)?.charAt(0).toUpperCase() + (recMeta?.severity ?? recommendation.severity)?.slice(1) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 leading-snug truncate max-w-xl">
                            {{ recMeta?.title ?? recommendation.title }}
                        </p>
                    </div>
                    <button
                        @click="$emit('close')"
                        class="shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition"
                        aria-label="Close"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 py-5">

                    <!-- Loading -->
                    <div v-if="loading" class="flex items-center justify-center py-16 gap-3 text-gray-400">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span class="text-sm">Loading projections…</span>
                    </div>

                    <!-- Error -->
                    <div v-else-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                        {{ error }}
                    </div>

                    <!-- No scenarios yet -->
                    <div v-else-if="!scenarios.length" class="text-center py-12 text-sm text-gray-400">
                        No scenario projections available yet. They will be generated on the next advisor cycle (≤10 min).
                    </div>

                    <!-- Scenario columns ─────────────────────────────────────── -->
                    <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div
                            v-for="scenario in scenarios"
                            :key="scenario.id"
                            class="rounded-xl border overflow-hidden flex flex-col transition"
                            :class="[
                                smeta(scenario.scenario_type).headerCls,
                                rankingFor(scenario.scenario_type)?.rank_position === 1
                                    ? 'ring-2 ring-emerald-400 ring-offset-1'
                                    : '',
                            ]"
                        >
                            <!-- Scenario header -->
                            <div class="px-4 py-3 border-b" :class="smeta(scenario.scenario_type).headerCls">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <svg class="w-3.5 h-3.5 shrink-0" :class="smeta(scenario.scenario_type).titleCls" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="smeta(scenario.scenario_type).iconPath"/>
                                    </svg>
                                    <span :class="['text-xs font-bold uppercase tracking-wider', smeta(scenario.scenario_type).titleCls]">
                                        {{ smeta(scenario.scenario_type).label }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400 pl-5">{{ smeta(scenario.scenario_type).sublabel }}</p>
                            </div>

                            <!-- Metrics -->
                            <div class="bg-white flex-1 px-4 py-4 space-y-4">

                                <!-- Stability delta -->
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 mb-1">Stability</p>
                                    <p :class="['text-2xl font-extrabold tabular-nums leading-none', stabilityColor(stabilityDelta(scenario))]">
                                        {{ fmtStability(stabilityDelta(scenario)) }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        → {{ scenario.projected_stability?.toFixed(1) }} / 100
                                    </p>
                                </div>

                                <!-- Cascade delta -->
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 mb-1">Cascade Risk</p>
                                    <p :class="['text-2xl font-extrabold tabular-nums leading-none', cascadeColor(cascadeDelta(scenario))]">
                                        {{ fmtCascade(cascadeDelta(scenario)) }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        → {{ scenario.projected_cascade_index?.toFixed(3) }}
                                    </p>
                                </div>

                                <!-- Trust delta -->
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 mb-1">Trust Δ</p>
                                    <p :class="['text-2xl font-extrabold tabular-nums leading-none', trustColor(trustDelta(scenario))]">
                                        {{ fmtTrust(trustDelta(scenario)) }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">avg reputation Δ/day</p>
                                </div>

                                <!-- CT-13 Recommended Action ─────────────────── -->
                                <template v-if="rankingFor(scenario.scenario_type)">
                                    <div class="border-t border-gray-100 pt-3 mt-1">
                                        <!-- #1 Best Choice badge -->
                                        <div
                                            v-if="rankingFor(scenario.scenario_type).rank_position === 1"
                                            class="flex items-center gap-1.5 mb-2"
                                        >
                                            <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-300">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                #1 Best Choice
                                            </span>
                                        </div>
                                        <div v-else class="mb-2">
                                            <span class="text-xs font-semibold text-gray-400">
                                                #{{ rankingFor(scenario.scenario_type).rank_position }} Ranked
                                            </span>
                                        </div>

                                        <!-- Impact summary -->
                                        <p class="text-xs text-gray-500 leading-snug mb-2">
                                            {{ impactSummary(rankingFor(scenario.scenario_type)) }}
                                        </p>

                                        <!-- Decision score bar -->
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Decision Score</span>
                                                <span :class="['text-xs font-bold tabular-nums', decisionScoreTextCls(rankingFor(scenario.scenario_type).decision_score)]">
                                                    {{ Number(rankingFor(scenario.scenario_type).decision_score).toFixed(1) }}
                                                </span>
                                            </div>
                                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                <div
                                                    :class="['h-full rounded-full transition-all duration-500', decisionScoreBarCls(rankingFor(scenario.scenario_type).decision_score)]"
                                                    :style="{ width: decisionScoreWidth(rankingFor(scenario.scenario_type).decision_score) }"
                                                />
                                            </div>
                                            <p class="text-xs text-gray-400 mt-0.5">−100 to +100 scale</p>
                                        </div>
                                    </div>
                                </template>

                            </div>

                            <!-- Confidence footer -->
                            <div class="bg-gray-50 px-4 py-2.5 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-xs text-gray-400">Confidence</span>
                                <span class="text-xs font-bold text-gray-700 tabular-nums">
                                    {{ scenario.confidence?.toFixed(0) }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Safety disclaimer -->
                    <p v-if="!loading && !error && scenarios.length" class="mt-5 text-xs text-gray-400 text-center leading-relaxed">
                        These are projections only. No actions are created or executed until explicitly approved.
                    </p>

                </div>
            </div>
        </div>
    </Teleport>
</template>
