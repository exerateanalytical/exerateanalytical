<script setup>
import { ref, computed } from 'vue';
import AnalyticsLayout from '@/Layouts/AnalyticsLayout.vue';
import ActionModal from '@/Components/Executive/ActionModal.vue';
import ScenarioModal from '@/Components/Executive/ScenarioModal.vue';
import axios from 'axios';

const props = defineProps({
    hero:                 { type: Object, default: null },
    regions:              { type: Array,  default: () => [] },
    policy_radar:         { type: Array,  default: () => [] },
    civic_momentum:       { type: Object, default: null },
    contagion_forecast:   { type: Object, default: null },
    representation_index: { type: Array,  default: () => [] },
    recommendations:      { type: Array,  default: () => [] },
    influences:           { type: Array,  default: () => [] },
    actors:               { type: Array,  default: () => [] },
    trajectory:           { type: Object, default: null },
    civic_priorities:     { type: Array,  default: () => [] },
    generated_at:         { type: String, default: null },
});

// ── Live-refreshable state seeded from Inertia props ─────────────────────────
const hero                = ref(props.hero);
const regions             = ref(props.regions);
const policyRadar         = ref(props.policy_radar);
const civicMomentum       = ref(props.civic_momentum);
const contagionForecast   = ref(props.contagion_forecast);
const representationIndex = ref(props.representation_index);
const recommendations     = ref(props.recommendations);
const influences          = ref(props.influences);
const actors              = ref(props.actors);
const trajectory          = ref(props.trajectory);
const civicPriorities     = ref(props.civic_priorities);
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
    refreshing.value   = true;
    refreshError.value = null;
    try {
        const [ctRes, recRes, infRes, actorRes, trajRes, prioRes] = await Promise.all([
            axios.get('/api/v1/executive/control-tower'),
            axios.get('/api/v1/executive/recommendations'),
            axios.get('/api/v1/executive/influences'),
            axios.get('/api/v1/executive/actors/influence').catch(() => ({ data: { data: [] } })),
            axios.get('/api/v1/executive/trajectory'),
            axios.get('/api/v1/executive/civic-priorities').catch(() => ({ data: { data: [] } })),
        ]);
        const d = ctRes.data.data ?? {};
        hero.value                = d.hero                 ?? null;
        regions.value             = d.regions              ?? [];
        policyRadar.value         = d.policy_radar         ?? [];
        civicMomentum.value       = d.civic_momentum       ?? null;
        contagionForecast.value   = d.contagion_forecast   ?? null;
        representationIndex.value = d.representation_index ?? [];
        generatedAt.value         = ctRes.data.meta?.generated_at ?? null;
        recommendations.value     = recRes.data.data ?? [];
        influences.value          = infRes.data.data ?? [];
        actors.value              = actorRes.data.data ?? [];
        trajectory.value          = trajRes.data ?? null;
        civicPriorities.value     = prioRes.data.data ?? [];
    } catch {
        refreshError.value = 'Refresh failed. Data may be stale.';
    } finally {
        refreshing.value = false;
    }
}

// ── Governance Advisor ────────────────────────────────────────────────────────
const recLoading  = ref({});  // { [id]: 'accept' | 'dismiss' | null }
const scenarioRec = ref(null); // recommendation whose scenarios to show

function openScenario(rec) {
    scenarioRec.value = rec;
}

async function acceptRec(rec) {
    recLoading.value = { ...recLoading.value, [rec.id]: 'accept' };
    try {
        await axios.post(`/api/v1/executive/recommendations/${rec.id}/accept`);
        recommendations.value = recommendations.value.filter(r => r.id !== rec.id);
        showToast('Recommendation accepted — action proposal created.');
    } catch (err) {
        showToast(err.response?.data?.message ?? 'Accept failed. Please try again.');
    } finally {
        const next = { ...recLoading.value };
        delete next[rec.id];
        recLoading.value = next;
    }
}

async function dismissRec(rec) {
    recLoading.value = { ...recLoading.value, [rec.id]: 'dismiss' };
    try {
        await axios.post(`/api/v1/executive/recommendations/${rec.id}/dismiss`);
        recommendations.value = recommendations.value.filter(r => r.id !== rec.id);
    } catch (err) {
        showToast(err.response?.data?.message ?? 'Dismiss failed. Please try again.');
    } finally {
        const next = { ...recLoading.value };
        delete next[rec.id];
        recLoading.value = next;
    }
}

// ── Advisor display helpers ───────────────────────────────────────────────────
const SEVERITY_META = {
    critical: { label: 'Critical', cls: 'bg-red-100    text-red-700    border-red-200',    dot: 'bg-red-500'    },
    high:     { label: 'High',     cls: 'bg-orange-100 text-orange-700 border-orange-200', dot: 'bg-orange-500' },
    medium:   { label: 'Medium',   cls: 'bg-amber-100  text-amber-700  border-amber-200',  dot: 'bg-amber-400'  },
    low:      { label: 'Low',      cls: 'bg-blue-100   text-blue-700   border-blue-200',   dot: 'bg-blue-400'   },
};

const sevMeta  = (s) => SEVERITY_META[s] ?? SEVERITY_META.low;
const truncate = (str, max = 140) => str && str.length > max ? str.slice(0, max) + '…' : (str ?? '');

// ── Governance Influence Map ──────────────────────────────────────────────────
const selectedInfluence = ref(null);

function openInfluenceDrawer(row) {
    selectedInfluence.value = row;
}

function closeInfluenceDrawer() {
    selectedInfluence.value = null;
}

const INFLUENCE_TYPE_LABELS = {
    trust_shift:        'Trust Shift',
    participation_gap:  'Participation Gap',
    contagion_pressure: 'Contagion Pressure',
};

const INFLUENCE_TYPE_EXPLANATIONS = {
    contagion_pressure: 'A high cascade index from the latest risk contagion run is creating systemic pressure. Regions downstream of the origin may see elevated fragility.',
    trust_shift:        'Aggregate reputation delta across the network is trending negative, indicating erosion of institutional trust. Sustained negative momentum signals governance deterioration.',
    participation_gap:  'A measurable gap between eligible and active participants has been detected. Low engagement widens accountability gaps and reduces policy legitimacy.',
};

const infTypeCls = (dir) =>
    dir === 'up'
        ? 'bg-red-100 text-red-700 border-red-200'
        : 'bg-emerald-100 text-emerald-700 border-emerald-200';

const infDirLabel = (dir) => dir === 'up' ? '↑ Risk Rising' : '↓ Risk Falling';

const infScoreCls = (score) => {
    if (score >= 60) return 'text-red-600';
    if (score >= 30) return 'text-amber-600';
    return 'text-emerald-600';
};

const infBarCls = (score) => {
    if (score >= 60) return 'bg-red-500';
    if (score >= 30) return 'bg-amber-400';
    return 'bg-emerald-500';
};

// ── Institutional Influence (CT-8) ────────────────────────────────────────────
const selectedActor = ref(null);

function openActorDrawer(row) {
    selectedActor.value = row;
}

function closeActorDrawer() {
    selectedActor.value = null;
}

const CATEGORY_META = {
    civic_leader:      { label: 'Civic Leader',      cls: 'bg-amber-100  text-amber-700  border-amber-200',   dot: 'bg-amber-500'   },
    policy_driver:     { label: 'Policy Driver',     cls: 'bg-violet-100 text-violet-700 border-violet-200',  dot: 'bg-violet-500'  },
    trust_stabilizer:  { label: 'Trust Stabilizer',  cls: 'bg-emerald-100 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' },
    volatility_source: { label: 'Volatility Source', cls: 'bg-red-100    text-red-700    border-red-200',     dot: 'bg-red-500'     },
};

const catMeta = (cat) => CATEGORY_META[cat] ?? CATEGORY_META.civic_leader;

const trustDeltaCls = (delta) => {
    const d = parseFloat(delta);
    if (isNaN(d) || d === 0) return 'text-gray-400';
    return d > 0 ? 'text-emerald-600' : 'text-red-500';
};

const fmtDelta = (delta) => {
    const d = parseFloat(delta);
    if (isNaN(d)) return '—';
    return (d >= 0 ? '+' : '') + d.toFixed(2);
};

const actorScoreCls = (score) => {
    if (score >= 60) return 'text-red-600';
    if (score >= 30) return 'text-amber-600';
    return 'text-emerald-600';
};

const actorBarCls = (score) => {
    if (score >= 60) return 'bg-red-500';
    if (score >= 30) return 'bg-amber-400';
    return 'bg-emerald-500';
};

// ── Governance Trajectory (CT-9) ──────────────────────────────────────────────
const DIRECTION_META = {
    improving: {
        label:  'Improving',
        icon:   '▲',
        cls:    'text-emerald-600',
        border: 'border-emerald-200 bg-emerald-50',
        stroke: '#10b981',
        badge:  'bg-emerald-100 text-emerald-700 border-emerald-200',
    },
    stable: {
        label:  'Stable',
        icon:   '■',
        cls:    'text-gray-500',
        border: 'border-gray-200 bg-gray-50',
        stroke: '#9ca3af',
        badge:  'bg-gray-100 text-gray-600 border-gray-200',
    },
    declining: {
        label:  'Declining',
        icon:   '▼',
        cls:    'text-red-600',
        border: 'border-red-200 bg-red-50',
        stroke: '#ef4444',
        badge:  'bg-red-100 text-red-700 border-red-200',
    },
};

const trajGlobal    = computed(() => trajectory.value?.global   ?? null);
const trajTrend     = computed(() => trajectory.value?.trend    ?? []);
const trajDirection = computed(() => trajGlobal.value?.direction ?? 'stable');
const trajMeta      = computed(() => DIRECTION_META[trajDirection.value] ?? DIRECTION_META.stable);

const fmtTrajDelta = (val) => {
    const v = parseFloat(val);
    if (isNaN(v)) return '—';
    return (v >= 0 ? '+' : '') + v.toFixed(1);
};

const deltaCls = (val, invert = false) => {
    const v = parseFloat(val);
    if (isNaN(v) || v === 0) return 'text-gray-400';
    const positive = invert ? v < 0 : v > 0;
    return positive ? 'text-emerald-600' : 'text-red-500';
};

// SVG sparkline polyline points — min-max normalised within the trend window.
const sparklinePoints = computed(() => {
    const data = trajTrend.value;
    if (!data || data.length < 2) return '';

    const scores = data.map(d => parseFloat(d.trajectory_score ?? 0));
    const min    = Math.min(...scores);
    const max    = Math.max(...scores);
    const range  = Math.max(0.1, max - min);

    return scores.map((s, i) => {
        const x = 10 + (i / (scores.length - 1)) * 80;
        const y = 35 - ((s - min) / range) * 26;   // y: 9 (top) → 35 (bottom)
        return `${x.toFixed(1)},${y.toFixed(1)}`;
    }).join(' ');
});

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

// ── CT-11 Civic Priority Radar helpers ───────────────────────────────────────
const priorityText = (score) => {
    if (score == null) return 'text-gray-400';
    if (score >= 60)   return 'text-red-600';
    if (score >= 30)   return 'text-amber-600';
    return 'text-emerald-600';
};

const priorityBar = (score) => {
    if (score >= 60) return 'bg-red-500';
    if (score >= 30) return 'bg-amber-400';
    return 'bg-emerald-500';
};

const radarVelocityArrow = (v) => {
    if (v == null) return '→';
    if (v >  0.1)  return '↑';
    if (v < -0.1)  return '↓';
    return '→';
};

const radarVelocityLabel = (v) => {
    if (v == null) return '—';
    if (v >  0.5)  return 'Accelerating';
    if (v >  0.1)  return 'Growing';
    if (v < -0.1)  return 'Slowing';
    return 'Stable';
};

const radarVelocityClass = (v) => {
    if (v == null || (v >= -0.1 && v <= 0.1)) return 'text-gray-400';
    return v > 0 ? 'text-emerald-600' : 'text-red-500';
};

// ── CT-12 Civic Causality Explanation Drawer ──────────────────────────────────
const explanationPriority = ref(null);
const explanationData     = ref(null);
const explanationLoading  = ref(false);
const explanationError    = ref(null);

async function openExplanationDrawer(item) {
    explanationPriority.value = item;
    explanationData.value     = null;
    explanationError.value    = null;
    explanationLoading.value  = true;
    try {
        const res = await axios.get(`/api/v1/executive/civic-priorities/${item.id}/explanation`);
        explanationData.value = res.data.data ?? null;
    } catch (err) {
        explanationError.value = err?.response?.status === 404
            ? 'No explanation generated yet for this signal.'
            : 'Failed to load explanation. Please try again.';
    } finally {
        explanationLoading.value = false;
    }
}

function closeExplanationDrawer() {
    explanationPriority.value = null;
    explanationData.value     = null;
    explanationError.value    = null;
}

const DRIVER_LABELS = {
    participation_velocity: 'Participation Momentum',
    trust_delta:            'Trust Dynamics',
    contagion_pressure:     'Risk Contagion Pressure',
    representation_gap:     'Representation Gap',
};

const RISK_META = {
    critical: { label: 'Critical', cls: 'bg-red-100 text-red-700 border-red-200' },
    high:     { label: 'High',     cls: 'bg-orange-100 text-orange-700 border-orange-200' },
    medium:   { label: 'Medium',   cls: 'bg-amber-100 text-amber-700 border-amber-200' },
    low:      { label: 'Low',      cls: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
};

const TRAJ_META = {
    improving:    { arrow: '↑', label: 'Improving',    cls: 'text-emerald-600' },
    stable:       { arrow: '→', label: 'Stable',       cls: 'text-gray-500' },
    deteriorating:{ arrow: '↓', label: 'Deteriorating',cls: 'text-red-600' },
};

const riskMeta = (r) => RISK_META[r] ?? RISK_META.low;
const trajMeta = (d) => TRAJ_META[d] ?? TRAJ_META.stable;

// Normalise a raw driver value to 0-100 for bar display (mirrors service logic)
const normalizeDriver = (key, value) => {
    if (value == null) return 0;
    if (key === 'participation_velocity' || key === 'trust_delta') {
        return Math.min(Math.abs(value), 5.0) / 5.0 * 100;
    }
    return Math.min(Math.max(value, 0), 100);
};

const driverBarCls = (key, isPrimary) => {
    if (isPrimary) return 'bg-indigo-500';
    if (key === 'contagion_pressure') return 'bg-red-400';
    if (key === 'trust_delta')        return 'bg-amber-400';
    return 'bg-blue-400';
};
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

            <!-- ── 0. GOVERNANCE TRAJECTORY RIBBON ────────────────────────── -->
            <div :class="['rounded-xl border shadow-sm px-6 py-4 transition', trajMeta.border]">
                <div class="flex items-center justify-between gap-6 flex-wrap">

                    <!-- Direction + label + score -->
                    <div class="flex items-center gap-5">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
                                Governance Trajectory
                            </p>
                            <div class="flex items-center gap-2">
                                <span :class="['text-3xl font-bold leading-none tabular-nums', trajMeta.cls]">
                                    {{ trajMeta.icon }}
                                </span>
                                <span :class="['text-xl font-bold', trajMeta.cls]">
                                    {{ trajMeta.label }}
                                </span>
                                <span v-if="!trajGlobal" class="text-sm text-gray-400 ml-1">
                                    — no data yet
                                </span>
                            </div>
                        </div>

                        <!-- Trajectory score -->
                        <div v-if="trajGlobal" class="border-l border-gray-200 pl-5">
                            <p class="text-xs text-gray-400 mb-0.5">Score</p>
                            <p :class="['text-2xl font-extrabold tabular-nums leading-none', trajMeta.cls]">
                                {{ fmtTrajDelta(trajGlobal.trajectory_score) }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">out of ±100</p>
                        </div>
                    </div>

                    <!-- Component deltas -->
                    <div v-if="trajGlobal" class="flex flex-wrap items-center gap-2">
                        <span class="text-xs text-gray-400 mr-1 hidden sm:block">Δ signals:</span>

                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full border bg-white border-gray-200">
                            <span class="text-gray-400">Stability</span>
                            <span :class="deltaCls(trajGlobal.stability_delta)">
                                {{ fmtTrajDelta(trajGlobal.stability_delta) }}
                            </span>
                        </span>

                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full border bg-white border-gray-200">
                            <span class="text-gray-400">Trust</span>
                            <span :class="deltaCls(trajGlobal.trust_delta)">
                                {{ fmtTrajDelta(trajGlobal.trust_delta) }}
                            </span>
                        </span>

                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full border bg-white border-gray-200">
                            <span class="text-gray-400">Participation</span>
                            <span :class="deltaCls(trajGlobal.participation_delta)">
                                {{ fmtTrajDelta(trajGlobal.participation_delta) }}
                            </span>
                        </span>

                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full border bg-white border-gray-200">
                            <span class="text-gray-400">Contagion</span>
                            <!-- contagion positive = bad, so invert colour -->
                            <span :class="deltaCls(trajGlobal.contagion_delta, true)">
                                {{ fmtTrajDelta(trajGlobal.contagion_delta) }}
                            </span>
                        </span>
                    </div>

                    <!-- 7-day mini sparkline -->
                    <div class="shrink-0">
                        <p class="text-xs text-gray-400 mb-1 text-right">7-day trend</p>
                        <div class="w-36 h-10">
                            <svg
                                viewBox="0 0 100 44"
                                class="w-full h-full overflow-visible"
                                aria-hidden="true"
                            >
                                <!-- Zero reference line -->
                                <line
                                    x1="8" y1="22" x2="92" y2="22"
                                    :stroke="trajMeta.stroke"
                                    stroke-width="0.5"
                                    stroke-dasharray="2 2"
                                    opacity="0.35"
                                />
                                <!-- Trend polyline -->
                                <polyline
                                    v-if="sparklinePoints"
                                    :points="sparklinePoints"
                                    fill="none"
                                    :stroke="trajMeta.stroke"
                                    stroke-width="2"
                                    stroke-linejoin="round"
                                    stroke-linecap="round"
                                />
                                <!-- No-data placeholder -->
                                <text
                                    v-else
                                    x="50" y="25"
                                    text-anchor="middle"
                                    font-size="7"
                                    fill="#9ca3af"
                                >no history</text>
                            </svg>
                        </div>
                    </div>

                </div>
            </div>

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

                <!-- Right column: AI Advisor + Quick Actions + E. Contagion + F. Representation -->
                <section class="space-y-6">

                    <!-- AI GOVERNANCE ADVISOR panel ─────────────────────── -->
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">AI Governance Advisor</h2>
                            <!-- sparkle icon -->
                            <svg class="w-3.5 h-3.5 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M5 3l1.5 4.5L12 9l-5.5 1.5L5 15l-1.5-4.5L-2 9l6.5-1.5L5 3zm12 9l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/>
                            </svg>
                        </div>

                        <div v-if="recommendations.length" class="space-y-3">
                            <div
                                v-for="rec in recommendations"
                                :key="rec.id"
                                class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 space-y-3"
                            >
                                <!-- Header row: severity badge + confidence -->
                                <div class="flex items-center justify-between gap-2">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border',
                                        sevMeta(rec.severity).cls,
                                    ]">
                                        <span :class="['w-1.5 h-1.5 rounded-full shrink-0', sevMeta(rec.severity).dot]" />
                                        {{ sevMeta(rec.severity).label }}
                                    </span>
                                    <span class="text-xs font-mono text-gray-400 shrink-0">
                                        {{ rec.confidence_score?.toFixed(0) }}% confidence
                                    </span>
                                </div>

                                <!-- Title -->
                                <p class="text-sm font-semibold text-gray-900 leading-snug">
                                    {{ rec.title }}
                                </p>

                                <!-- Rationale preview -->
                                <p class="text-xs text-gray-500 leading-relaxed">
                                    {{ truncate(rec.rationale) }}
                                </p>

                                <!-- Action buttons -->
                                <div class="flex items-center gap-2 pt-1">
                                    <button
                                        @click="acceptRec(rec)"
                                        :disabled="recLoading[rec.id]"
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 rounded-lg transition"
                                    >
                                        <svg v-if="recLoading[rec.id] === 'accept'" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        {{ recLoading[rec.id] === 'accept' ? 'Submitting…' : 'Accept' }}
                                    </button>

                                    <!-- View Futures: opens scenario projection modal -->
                                    <button
                                        @click="openScenario(rec)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-violet-700 bg-violet-50 border border-violet-200 hover:bg-violet-100 rounded-lg transition"
                                        title="View counterfactual projections"
                                    >
                                        <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                        Futures
                                    </button>

                                    <button
                                        @click="dismissRec(rec)"
                                        :disabled="recLoading[rec.id]"
                                        class="px-3 py-1.5 text-xs font-semibold text-gray-500 bg-gray-50 border border-gray-200 hover:bg-gray-100 disabled:opacity-50 rounded-lg transition"
                                    >
                                        {{ recLoading[rec.id] === 'dismiss' ? '…' : 'Dismiss' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="bg-gray-50 border border-gray-100 rounded-xl px-4 py-5 flex items-center gap-3">
                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-xs text-gray-400">No active recommendations. All signals within safe thresholds.</p>
                        </div>
                    </div>

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

            <!-- ── I. CIVIC PRIORITY RADAR (CT-11) ───────────────────────── -->
            <section>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Civic Priority Radar
                    </h2>
                    <span class="text-xs text-gray-400">Top-20 signals · scored every 15 min</span>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div v-if="civicPriorities.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Velocity</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Region</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="item in civicPriorities"
                                    :key="item.id"
                                    class="hover:bg-gray-50 transition"
                                >
                                    <!-- Title -->
                                    <td class="px-4 py-3 font-medium text-gray-900 max-w-xs">
                                        <span class="block truncate" :title="item.signal_title ?? undefined">
                                            {{ item.signal_title ?? '—' }}
                                        </span>
                                    </td>

                                    <!-- Type badge -->
                                    <td class="px-4 py-3">
                                        <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full capitalize', itemTypeBadge(item.signal_type)]">
                                            {{ item.signal_type }}
                                        </span>
                                    </td>

                                    <!-- Priority score + mini bar -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden shrink-0">
                                                <div
                                                    :class="['h-full rounded-full transition-all duration-500', priorityBar(item.priority_score)]"
                                                    :style="{ width: `${Math.min(100, Math.max(0, item.priority_score ?? 0))}%` }"
                                                />
                                            </div>
                                            <span :class="['font-bold tabular-nums text-sm w-10 text-right', priorityText(item.priority_score)]">
                                                {{ item.priority_score != null ? Number(item.priority_score).toFixed(1) : '—' }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Velocity indicator -->
                                    <td class="px-4 py-3">
                                        <span :class="['inline-flex items-center gap-1 text-xs font-semibold', radarVelocityClass(item.participation_velocity)]">
                                            {{ radarVelocityArrow(item.participation_velocity) }}
                                            {{ radarVelocityLabel(item.participation_velocity) }}
                                        </span>
                                    </td>

                                    <!-- Region -->
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ item.signal_region ?? 'Global' }}
                                    </td>

                                    <!-- Explain -->
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            @click="openExplanationDrawer(item)"
                                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline transition"
                                        >
                                            Explain
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-6 py-10 text-center text-gray-400 text-sm">
                        <p class="font-medium">No priority signals computed yet.</p>
                        <p class="text-xs mt-1">Signals are scored every 15 minutes once the scheduler runs.</p>
                    </div>
                </div>
            </section>

            <!-- ── G. GOVERNANCE INFLUENCE MAP ────────────────────────────── -->
            <section>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Governance Influence Map
                    </h2>
                    <span class="text-xs text-gray-400">Click a row for signal details</span>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div v-if="influences.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Region</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Influence Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Direction</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Score</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Bar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="inf in influences"
                                    :key="inf.id"
                                    class="hover:bg-gray-50 transition cursor-pointer"
                                    @click="openInfluenceDrawer(inf)"
                                >
                                    <td class="px-4 py-3">
                                        <span v-if="inf.region" class="font-semibold text-gray-800 text-sm">
                                            {{ inf.region.code ?? inf.region.name ?? inf.region_id }}
                                        </span>
                                        <span v-else class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">
                                            Global
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs font-medium text-gray-700">
                                            {{ INFLUENCE_TYPE_LABELS[inf.influence_type] ?? inf.influence_type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="[
                                            'inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full border',
                                            infTypeCls(inf.impact_direction),
                                        ]">
                                            {{ infDirLabel(inf.impact_direction) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span :class="['font-bold tabular-nums', infScoreCls(inf.influence_score)]">
                                            {{ inf.influence_score != null ? Number(inf.influence_score).toFixed(1) : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden w-28">
                                            <div
                                                :class="['h-full rounded-full transition-all duration-500', infBarCls(inf.influence_score)]"
                                                :style="{ width: `${Math.min(100, Math.max(0, inf.influence_score ?? 0))}%` }"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-6 py-10 text-center text-sm text-gray-400">
                        No influence signals computed yet. Signals are generated every 10 minutes.
                    </div>
                </div>
            </section>

            <!-- ── H. INSTITUTIONAL INFLUENCE ────────────────────────────── -->
            <section>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Institutional Influence
                    </h2>
                    <span class="text-xs text-gray-400">Last 30 days · Click a row for breakdown</span>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div v-if="actors.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actor</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Influence</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Trust Δ</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Bar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="row in actors"
                                    :key="row.id"
                                    class="hover:bg-gray-50 transition cursor-pointer"
                                    @click="openActorDrawer(row)"
                                >
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-gray-900 text-sm truncate max-w-[160px]">
                                            {{ row.actor?.name ?? '—' }}
                                        </p>
                                        <p v-if="row.region" class="text-xs text-gray-400 font-mono mt-0.5">
                                            {{ row.region.code ?? row.region.name }}
                                        </p>
                                        <span v-else class="text-xs text-indigo-400">Global</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="[
                                            'inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border',
                                            catMeta(row.influence_category).cls,
                                        ]">
                                            <span :class="['w-1.5 h-1.5 rounded-full shrink-0', catMeta(row.influence_category).dot]" />
                                            {{ catMeta(row.influence_category).label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span :class="['font-bold tabular-nums', actorScoreCls(row.influence_score)]">
                                            {{ row.influence_score != null ? Number(row.influence_score).toFixed(1) : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span :class="['font-semibold tabular-nums text-sm', trustDeltaCls(row.trust_delta)]">
                                            {{ fmtDelta(row.trust_delta) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden w-28">
                                            <div
                                                :class="['h-full rounded-full transition-all duration-500', actorBarCls(row.influence_score)]"
                                                :style="{ width: `${Math.min(100, Math.max(0, row.influence_score ?? 0))}%` }"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-6 py-10 text-center text-sm text-gray-400">
                        No institutional influence data yet. Signals are computed every 30 minutes from the last 30 days of civic activity.
                    </div>
                </div>
            </section>

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

        <!-- ── Scenario Modal (View Futures) ───────────────────────────── -->
        <ScenarioModal
            v-if="scenarioRec"
            :recommendation="scenarioRec"
            @close="scenarioRec = null"
        />

        <!-- ── Influence Detail Drawer ─────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-from-class="opacity-0"
                enter-active-class="transition duration-150 ease-out"
                enter-to-class="opacity-100"
                leave-from-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="selectedInfluence"
                    class="fixed inset-0 z-40 bg-black/30"
                    @click="closeInfluenceDrawer"
                />
            </Transition>

            <Transition
                enter-from-class="translate-x-full"
                enter-active-class="transition duration-200 ease-out"
                enter-to-class="translate-x-0"
                leave-from-class="translate-x-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="translate-x-full"
            >
                <aside
                    v-if="selectedInfluence"
                    class="fixed right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl z-50 flex flex-col overflow-y-auto"
                >
                    <!-- Drawer header -->
                    <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200 shrink-0">
                        <h3 class="text-base font-bold text-gray-900">Influence Signal Detail</h3>
                        <button
                            @click="closeInfluenceDrawer"
                            class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition"
                            aria-label="Close"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Drawer body -->
                    <div class="flex-1 px-6 py-6 space-y-6">

                        <!-- Influence type + direction badge -->
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Influence Type</p>
                                <p class="text-lg font-bold text-gray-900">
                                    {{ INFLUENCE_TYPE_LABELS[selectedInfluence.influence_type] ?? selectedInfluence.influence_type }}
                                </p>
                            </div>
                            <span :class="[
                                'inline-block text-xs font-semibold px-3 py-1.5 rounded-full border shrink-0 mt-1',
                                infTypeCls(selectedInfluence.impact_direction),
                            ]">
                                {{ infDirLabel(selectedInfluence.impact_direction) }}
                            </span>
                        </div>

                        <!-- Score bar -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Influence Score</p>
                                <span :class="['text-2xl font-extrabold tabular-nums', infScoreCls(selectedInfluence.influence_score)]">
                                    {{ selectedInfluence.influence_score != null ? Number(selectedInfluence.influence_score).toFixed(1) : '—' }}
                                </span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div
                                    :class="['h-full rounded-full', infBarCls(selectedInfluence.influence_score)]"
                                    :style="{ width: `${Math.min(100, Math.max(0, selectedInfluence.influence_score ?? 0))}%` }"
                                />
                            </div>
                            <p class="text-xs text-gray-400 mt-1">0 – 100 normalised scale</p>
                        </div>

                        <!-- Region -->
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Region</p>
                            <p class="text-sm font-semibold text-gray-800" v-if="selectedInfluence.region">
                                {{ selectedInfluence.region.name ?? selectedInfluence.region.code }}
                                <span v-if="selectedInfluence.region.code && selectedInfluence.region.name" class="text-gray-400 font-mono text-xs ml-1">
                                    ({{ selectedInfluence.region.code }})
                                </span>
                            </p>
                            <p class="text-sm font-medium text-indigo-600" v-else>Global (no specific region)</p>
                        </div>

                        <!-- Source signal -->
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Source Signal</p>
                            <p class="text-sm font-semibold text-gray-700 capitalize">{{ selectedInfluence.source_type ?? '—' }}</p>
                            <p v-if="selectedInfluence.source_id" class="text-xs font-mono text-gray-400 mt-0.5 break-all">
                                ref: {{ selectedInfluence.source_id }}
                            </p>
                        </div>

                        <!-- Explanation -->
                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Signal Explanation</p>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                {{ INFLUENCE_TYPE_EXPLANATIONS[selectedInfluence.influence_type] ?? 'This signal was derived from recent civic, risk, or trust data. Review the source record for additional context.' }}
                            </p>
                        </div>

                        <!-- Calculated at -->
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Computed At</p>
                            <p class="text-sm text-gray-600">{{ fmt(selectedInfluence.calculated_at) }}</p>
                        </div>

                    </div>
                </aside>
            </Transition>
        </Teleport>

        <!-- ── Actor Detail Drawer ────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-from-class="opacity-0"
                enter-active-class="transition duration-150 ease-out"
                enter-to-class="opacity-100"
                leave-from-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="selectedActor"
                    class="fixed inset-0 z-40 bg-black/30"
                    @click="closeActorDrawer"
                />
            </Transition>

            <Transition
                enter-from-class="translate-x-full"
                enter-active-class="transition duration-200 ease-out"
                enter-to-class="translate-x-0"
                leave-from-class="translate-x-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="translate-x-full"
            >
                <aside
                    v-if="selectedActor"
                    class="fixed right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl z-50 flex flex-col overflow-y-auto"
                >
                    <!-- Drawer header -->
                    <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200 shrink-0">
                        <h3 class="text-base font-bold text-gray-900">Actor Influence Profile</h3>
                        <button
                            @click="closeActorDrawer"
                            class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition"
                            aria-label="Close"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Drawer body -->
                    <div class="flex-1 px-6 py-6 space-y-6">

                        <!-- Identity -->
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Actor</p>
                                <p class="text-lg font-bold text-gray-900">{{ selectedActor.actor?.name ?? '—' }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ selectedActor.actor?.email ?? '' }}</p>
                            </div>
                            <span :class="[
                                'inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1.5 rounded-full border shrink-0 mt-1',
                                catMeta(selectedActor.influence_category).cls,
                            ]">
                                <span :class="['w-1.5 h-1.5 rounded-full shrink-0', catMeta(selectedActor.influence_category).dot]" />
                                {{ catMeta(selectedActor.influence_category).label }}
                            </span>
                        </div>

                        <!-- Influence score bar -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Influence Score</p>
                                <span :class="['text-2xl font-extrabold tabular-nums', actorScoreCls(selectedActor.influence_score)]">
                                    {{ selectedActor.influence_score != null ? Number(selectedActor.influence_score).toFixed(1) : '—' }}
                                </span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div
                                    :class="['h-full rounded-full', actorBarCls(selectedActor.influence_score)]"
                                    :style="{ width: `${Math.min(100, Math.max(0, selectedActor.influence_score ?? 0))}%` }"
                                />
                            </div>
                            <p class="text-xs text-gray-400 mt-1">0 – 100 normalised · participation 40 pts · engagement 30 pts · trust 30 pts</p>
                        </div>

                        <!-- Trust delta -->
                        <div class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Trust Δ (30 days)</p>
                                <p class="text-xs text-gray-400 mt-0.5">Sum of reputation event deltas</p>
                            </div>
                            <span :class="['text-2xl font-extrabold tabular-nums', trustDeltaCls(selectedActor.trust_delta)]">
                                {{ fmtDelta(selectedActor.trust_delta) }}
                            </span>
                        </div>

                        <!-- Activity breakdown -->
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Activity Breakdown</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-center">
                                    <p class="text-2xl font-bold text-blue-700 tabular-nums">
                                        {{ selectedActor.activity_breakdown?.polls_created ?? 0 }}
                                    </p>
                                    <p class="text-xs font-medium text-blue-500 mt-1">Polls Created</p>
                                </div>
                                <div class="bg-violet-50 border border-violet-100 rounded-xl p-3 text-center">
                                    <p class="text-2xl font-bold text-violet-700 tabular-nums">
                                        {{ selectedActor.activity_breakdown?.petitions_created ?? 0 }}
                                    </p>
                                    <p class="text-xs font-medium text-violet-500 mt-1">Petitions Created</p>
                                </div>
                                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 text-center">
                                    <p class="text-2xl font-bold text-indigo-700 tabular-nums">
                                        {{ selectedActor.activity_breakdown?.petitions_signed ?? 0 }}
                                    </p>
                                    <p class="text-xs font-medium text-indigo-500 mt-1">Petitions Signed</p>
                                </div>
                                <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 text-center">
                                    <p class="text-2xl font-bold text-amber-700 tabular-nums">
                                        {{ selectedActor.activity_breakdown?.policies_created ?? 0 }}
                                    </p>
                                    <p class="text-xs font-medium text-amber-600 mt-1">Policies Created</p>
                                </div>
                                <div class="col-span-2 bg-gray-50 border border-gray-100 rounded-xl p-3 text-center">
                                    <p class="text-2xl font-bold text-gray-700 tabular-nums">
                                        {{ selectedActor.activity_breakdown?.reactions_received ?? 0 }}
                                    </p>
                                    <p class="text-xs font-medium text-gray-500 mt-1">Reactions Received</p>
                                </div>
                            </div>
                        </div>

                        <!-- Region -->
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Region</p>
                            <p class="text-sm font-semibold text-gray-800" v-if="selectedActor.region">
                                {{ selectedActor.region.name ?? selectedActor.region.code }}
                                <span v-if="selectedActor.region.code && selectedActor.region.name" class="text-gray-400 font-mono text-xs ml-1">
                                    ({{ selectedActor.region.code }})
                                </span>
                            </p>
                            <p class="text-sm font-medium text-indigo-600" v-else>Global (no specific region)</p>
                        </div>

                        <!-- Computed at -->
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Computed At</p>
                            <p class="text-sm text-gray-600">{{ fmt(selectedActor.calculated_at) }}</p>
                        </div>

                    </div>
                </aside>
            </Transition>
        </Teleport>

        <!-- ── CT-12 Causality Explanation Drawer ────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-from-class="opacity-0"
                enter-active-class="transition duration-150 ease-out"
                enter-to-class="opacity-100"
                leave-from-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="explanationPriority"
                    class="fixed inset-0 z-40 bg-black/30"
                    @click="closeExplanationDrawer"
                />
            </Transition>

            <Transition
                enter-from-class="translate-x-full"
                enter-active-class="transition duration-200 ease-out"
                enter-to-class="translate-x-0"
                leave-from-class="translate-x-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="translate-x-full"
            >
                <aside
                    v-if="explanationPriority"
                    class="fixed right-0 top-0 h-full w-full max-w-lg bg-white shadow-2xl z-50 flex flex-col overflow-y-auto"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200 shrink-0">
                        <div>
                            <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-0.5">Causality Engine · CT-12</p>
                            <h3 class="text-base font-bold text-gray-900 truncate max-w-xs" :title="explanationPriority.signal_title ?? undefined">
                                {{ explanationPriority.signal_title ?? 'Signal Explanation' }}
                            </h3>
                        </div>
                        <button
                            @click="closeExplanationDrawer"
                            class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition shrink-0 ml-3"
                            aria-label="Close"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 px-6 py-6 space-y-6">

                        <!-- Loading state -->
                        <div v-if="explanationLoading" class="flex items-center justify-center py-12">
                            <svg class="animate-spin w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span class="ml-3 text-sm text-gray-500">Loading explanation…</span>
                        </div>

                        <!-- Error state -->
                        <div v-else-if="explanationError" class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-700">
                            {{ explanationError }}
                        </div>

                        <!-- Data -->
                        <template v-else-if="explanationData">

                            <!-- Primary Driver -->
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Primary Driver</p>
                                <div class="flex items-center gap-3">
                                    <span class="inline-block text-sm font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-full">
                                        {{ DRIVER_LABELS[explanationData.primary_driver] ?? explanationData.primary_driver }}
                                    </span>
                                </div>
                            </div>

                            <!-- Driver Breakdown bars -->
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Driver Breakdown</p>
                                <div class="space-y-3">
                                    <div
                                        v-for="(rawVal, key) in explanationData.driver_breakdown"
                                        :key="key"
                                    >
                                        <div class="flex items-center justify-between mb-1">
                                            <span :class="['text-xs font-semibold', key === explanationData.primary_driver ? 'text-indigo-700' : 'text-gray-600']">
                                                {{ DRIVER_LABELS[key] ?? key }}
                                                <span v-if="key === explanationData.primary_driver" class="ml-1 text-indigo-400 font-normal">· primary</span>
                                            </span>
                                            <span class="text-xs font-mono text-gray-500">{{ Number(rawVal).toFixed(3) }}</span>
                                        </div>
                                        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div
                                                :class="['h-full rounded-full transition-all duration-500', driverBarCls(key, key === explanationData.primary_driver)]"
                                                :style="{ width: `${normalizeDriver(key, rawVal).toFixed(1)}%` }"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Trajectory direction -->
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Governance Trajectory</p>
                                    <p :class="['text-lg font-extrabold', trajMeta(explanationData.trajectory_direction).cls]">
                                        {{ trajMeta(explanationData.trajectory_direction).arrow }}
                                        {{ trajMeta(explanationData.trajectory_direction).label }}
                                    </p>
                                </div>

                                <!-- Risk projection badge -->
                                <div class="text-right">
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Projected Risk</p>
                                    <span :class="['inline-block text-sm font-bold px-3 py-1.5 rounded-full border', riskMeta(explanationData.projected_risk_level).cls]">
                                        {{ riskMeta(explanationData.projected_risk_level).label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Affected regions -->
                            <div v-if="explanationData.affected_regions?.length">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Affected Regions</p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="region in explanationData.affected_regions"
                                        :key="region.code ?? region.name"
                                        class="text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200 px-2.5 py-1 rounded-full"
                                        :title="region.name"
                                    >
                                        {{ region.code ?? region.name }}
                                    </span>
                                </div>
                            </div>

                            <!-- Explanation summary -->
                            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4">
                                <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-2">Explanation Summary</p>
                                <p class="text-sm text-gray-700 leading-relaxed">{{ explanationData.explanation_summary }}</p>
                            </div>

                            <!-- Generated at -->
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Generated At</p>
                                <p class="text-sm text-gray-600">{{ fmt(explanationData.created_at) }}</p>
                            </div>

                        </template>

                    </div>
                </aside>
            </Transition>
        </Teleport>

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
