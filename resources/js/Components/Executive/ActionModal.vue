<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// ── Props / Emits ─────────────────────────────────────────────────────────────
const props = defineProps({
    actionType: { type: String, required: true },
    regions:    { type: Array,  default: () => [] },
});

const emit = defineEmits(['close', 'success']);

// ── Per-type metadata ─────────────────────────────────────────────────────────
const META = {
    simulate_contagion: {
        label:   'Simulate Contagion',
        color:   'red',
        palette: {
            header: 'bg-red-50 border-b border-red-100',
            title:  'text-red-800',
            submit: 'bg-red-600 hover:bg-red-700 focus:ring-red-500 disabled:bg-red-300',
            check:  'text-red-700 border-red-300 checked:bg-red-600',
            banner: 'bg-red-50 border border-red-200 text-red-700',
        },
    },
    launch_poll: {
        label:   'Launch Civic Poll',
        color:   'emerald',
        palette: {
            header: 'bg-emerald-50 border-b border-emerald-100',
            title:  'text-emerald-800',
            submit: 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 disabled:bg-emerald-300',
            check:  '',
            banner: '',
        },
    },
    open_consultation: {
        label:   'Open Policy Consultation',
        color:   'amber',
        palette: {
            header: 'bg-amber-50 border-b border-amber-100',
            title:  'text-amber-800',
            submit: 'bg-amber-500 hover:bg-amber-600 focus:ring-amber-500 disabled:bg-amber-300',
            check:  '',
            banner: '',
        },
    },
    flag_region: {
        label:   'Flag Region',
        color:   'amber',
        palette: {
            header: 'bg-amber-50 border-b border-amber-100',
            title:  'text-amber-800',
            submit: 'bg-amber-500 hover:bg-amber-600 focus:ring-amber-500 disabled:bg-amber-300',
            check:  'text-amber-700 border-amber-300 checked:bg-amber-600',
            banner: 'bg-amber-50 border border-amber-200 text-amber-700',
        },
    },
};

const meta = computed(() => META[props.actionType] ?? {
    label: props.actionType,
    color: 'gray',
    palette: {
        header: 'bg-gray-50 border-b border-gray-200',
        title:  'text-gray-800',
        submit: 'bg-gray-600 hover:bg-gray-700 focus:ring-gray-500 disabled:bg-gray-300',
        check:  '',
        banner: '',
    },
});

const needsConfirmation = computed(() =>
    props.actionType === 'simulate_contagion' || props.actionType === 'flag_region'
);

// ── Form state ────────────────────────────────────────────────────────────────
const form = reactive({
    // shared
    region_id:  '',
    title:      '',

    // simulate_contagion
    shock_magnitude:  25,
    contagion_factor: 0.30,
    iterations:       5,

    // open_consultation
    summary:   '',
    full_text: '',

    // launch_poll
    visibility: 'public',

    // flag_region
    reason: '',
});

// Poll options — minimum 2, user can add/remove
const pollOptions = ref([{ text: '' }, { text: '' }]);

const addOption    = () => pollOptions.value.push({ text: '' });
const removeOption = (idx) => {
    if (pollOptions.value.length > 2) pollOptions.value.splice(idx, 1);
};

// ── Submission state ──────────────────────────────────────────────────────────
const loading   = ref(false);
const error     = ref(null);
const confirmed = ref(false);

const canSubmit = computed(() =>
    !loading.value && (!needsConfirmation.value || confirmed.value)
);

// ── Payload builder ───────────────────────────────────────────────────────────
function buildPayload() {
    switch (props.actionType) {
        case 'simulate_contagion':
            return {
                region_id:        form.region_id,
                shock_magnitude:  Number(form.shock_magnitude),
                contagion_factor: Number(form.contagion_factor),
                iterations:       Number(form.iterations),
            };
        case 'launch_poll':
            return {
                title:      form.title,
                options:    pollOptions.value.map(o => o.text).filter(t => t.trim()),
                visibility: form.visibility,
                type:       'single_choice',
                status:     'active',
            };
        case 'open_consultation':
            return {
                title:     form.title,
                abstract:  form.summary,
                full_text: form.full_text || form.summary,
            };
        case 'flag_region':
            return {
                region_id: form.region_id,
                reason:    form.reason,
            };
        default:
            return {};
    }
}

// ── Submit ────────────────────────────────────────────────────────────────────
async function submit() {
    if (!canSubmit.value) return;
    loading.value = true;
    error.value   = null;
    try {
        await axios.post('/api/v1/internal/governance/actions', {
            action_type: props.actionType,
            payload:     buildPayload(),
        });
        emit('success');
    } catch (err) {
        const data = err.response?.data;
        if (data?.errors) {
            const msgs = Object.values(data.errors).flat();
            error.value = msgs.join(' ');
        } else {
            error.value = data?.message ?? 'Action failed. Please try again.';
        }
    } finally {
        loading.value = false;
    }
}

// ── Keyboard: Escape to close ─────────────────────────────────────────────────
function onKey(e) {
    if (e.key === 'Escape') emit('close');
}
onMounted(() => document.addEventListener('keydown', onKey));
onUnmounted(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <!-- Backdrop -->
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <!-- Dimmed overlay -->
            <div
                class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                @click="$emit('close')"
            />

            <!-- Modal card -->
            <div
                class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh]"
                @click.stop
            >
                <!-- Header -->
                <div :class="['flex items-center justify-between px-6 py-4 rounded-t-2xl', meta.palette.header]">
                    <h2 :class="['text-base font-bold', meta.palette.title]">
                        {{ meta.label }}
                    </h2>
                    <button
                        @click="$emit('close')"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-white/60 transition"
                        aria-label="Close"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Scrollable body -->
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                    <!-- ── simulate_contagion ────────────────────────────────── -->
                    <template v-if="actionType === 'simulate_contagion'">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Origin Region <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.region_id"
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-red-500 focus:border-red-500"
                            >
                                <option value="">Select a region…</option>
                                <option
                                    v-for="r in regions"
                                    :key="r.region_id"
                                    :value="r.region_id"
                                >
                                    {{ r.region_code ?? r.region_id }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-gray-700">
                                    Shock Magnitude
                                </label>
                                <span class="text-sm font-bold text-red-600 tabular-nums w-8 text-right">
                                    {{ form.shock_magnitude }}
                                </span>
                            </div>
                            <input
                                v-model.number="form.shock_magnitude"
                                type="range" min="1" max="100" step="1"
                                class="w-full h-2 rounded-full accent-red-500 cursor-pointer"
                            />
                            <div class="flex justify-between text-xs text-gray-400 mt-1">
                                <span>0</span><span>100</span>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-gray-700">
                                    Contagion Factor
                                </label>
                                <span class="text-sm font-bold text-red-600 tabular-nums w-10 text-right">
                                    {{ form.contagion_factor.toFixed(2) }}
                                </span>
                            </div>
                            <input
                                v-model.number="form.contagion_factor"
                                type="range" min="0" max="0.7" step="0.05"
                                class="w-full h-2 rounded-full accent-red-500 cursor-pointer"
                            />
                            <div class="flex justify-between text-xs text-gray-400 mt-1">
                                <span>0</span><span>0.70</span>
                            </div>
                        </div>
                    </template>

                    <!-- ── launch_poll ───────────────────────────────────────── -->
                    <template v-else-if="actionType === 'launch_poll'">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Poll Title <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Enter poll question…"
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-2">
                                Options <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <div
                                    v-for="(opt, idx) in pollOptions"
                                    :key="idx"
                                    class="flex items-center gap-2"
                                >
                                    <span class="text-xs font-mono text-gray-400 w-4 shrink-0">{{ idx + 1 }}</span>
                                    <input
                                        v-model="opt.text"
                                        type="text"
                                        :placeholder="`Option ${idx + 1}`"
                                        class="flex-1 border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                    />
                                    <button
                                        v-if="pollOptions.length > 2"
                                        @click="removeOption(idx)"
                                        class="text-gray-300 hover:text-red-400 transition shrink-0"
                                        title="Remove"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button
                                @click="addOption"
                                class="mt-2 text-xs font-semibold text-emerald-600 hover:text-emerald-800 transition"
                            >
                                + Add option
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Visibility</label>
                            <select
                                v-model="form.visibility"
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500"
                            >
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                                <option value="regional">Regional</option>
                            </select>
                        </div>
                    </template>

                    <!-- ── open_consultation ─────────────────────────────────── -->
                    <template v-else-if="actionType === 'open_consultation'">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Policy consultation title…"
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Abstract <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="form.summary"
                                rows="3"
                                placeholder="Brief summary of the consultation…"
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-amber-500 focus:border-amber-500 resize-none"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Full Text</label>
                            <textarea
                                v-model="form.full_text"
                                rows="4"
                                placeholder="Extended body text (optional — defaults to abstract)…"
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-amber-500 focus:border-amber-500 resize-none"
                            />
                        </div>
                    </template>

                    <!-- ── flag_region ───────────────────────────────────────── -->
                    <template v-else-if="actionType === 'flag_region'">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Region to Flag <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.region_id"
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-amber-500 focus:border-amber-500"
                            >
                                <option value="">Select a region…</option>
                                <option
                                    v-for="r in regions"
                                    :key="r.region_id"
                                    :value="r.region_id"
                                >
                                    {{ r.region_code ?? r.region_id }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Reason <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="form.reason"
                                rows="4"
                                placeholder="Describe the reason for flagging this region…"
                                class="w-full border-gray-300 rounded-lg text-sm focus:ring-amber-500 focus:border-amber-500 resize-none"
                            />
                        </div>
                    </template>

                    <!-- ── Safety confirmation ───────────────────────────────── -->
                    <div
                        v-if="needsConfirmation"
                        :class="['rounded-xl px-4 py-3.5', meta.palette.banner]"
                    >
                        <label class="flex items-start gap-3 cursor-pointer select-none">
                            <input
                                v-model="confirmed"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 shrink-0"
                            />
                            <span class="text-xs font-medium leading-relaxed">
                                I understand this action affects system risk modelling and may alter
                                federation state. I confirm it is intentional.
                            </span>
                        </label>
                    </div>

                    <!-- Error -->
                    <p
                        v-if="error"
                        class="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2"
                    >
                        {{ error }}
                    </p>

                </div>

                <!-- Footer -->
                <div class="shrink-0 flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="submit"
                        :disabled="!canSubmit"
                        :class="[
                            'inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white rounded-xl transition focus:outline-none focus:ring-2 focus:ring-offset-1',
                            meta.palette.submit,
                        ]"
                    >
                        <!-- Loading spinner -->
                        <svg
                            v-if="loading"
                            class="w-4 h-4 animate-spin"
                            fill="none" viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ loading ? 'Executing…' : 'Execute Action' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
