<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import CivicLayout from '@/Layouts/CivicLayout.vue';

const props = defineProps({
    regions:  { type: Array,  default: () => [] },
    template: { type: Object, default: null },
});

const appliedTemplate = ref(props.template?.title ?? null);

const form = reactive({
    title:          '',
    summary:        '',
    body:           '',
    region_id:      '',
    signature_goal: 100,
    deadline:       '',
});

// ── Placeholder substitution ──────────────────────────────────────────────────
// Store the raw template text so substitutions can be applied non-destructively
const rawSummary = ref('');
const rawBody    = ref('');

const extractPlaceholders = (text) => {
    const found = new Set();
    const re = /\[([A-Z][A-Z0-9_]*)\]/g;
    let m;
    while ((m = re.exec(text ?? '')) !== null) found.add(m[1]);
    return [...found];
};

const placeholders = computed(() => [
    ...new Set([
        ...extractPlaceholders(rawSummary.value),
        ...extractPlaceholders(rawBody.value),
    ]),
]);

// One reactive object: { AUTHORITY: '', THRESHOLD: '', … }
const substitutions = reactive({});

// Seed new keys when placeholders change (never delete existing ones mid-session)
watch(placeholders, (ps) => {
    ps.forEach(p => { if (!(p in substitutions)) substitutions[p] = ''; });
}, { immediate: true });

// Reactively re-apply all substitutions whenever any value changes
watch(substitutions, () => {
    if (!rawSummary.value && !rawBody.value) return;
    let s = rawSummary.value;
    let b = rawBody.value;
    Object.entries(substitutions).forEach(([key, val]) => {
        const re = new RegExp(`\\[${key}\\]`, 'g');
        s = s.replace(re, val || `[${key}]`);
        b = b.replace(re, val || `[${key}]`);
    });
    form.summary = s;
    form.body    = b;
}, { deep: true });

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
    if (props.template) {
        form.title          = props.template.title ?? '';
        rawSummary.value    = props.template.summary_template ?? '';
        rawBody.value       = props.template.body_template    ?? '';
        form.summary        = rawSummary.value;
        form.body           = rawBody.value;
        form.signature_goal = props.template.default_signature_goal ?? 100;
    }
});

const clearTemplate = () => {
    appliedTemplate.value = null;
    rawSummary.value = '';
    rawBody.value    = '';
    Object.keys(substitutions).forEach(k => delete substitutions[k]);
    router.visit(route('civic.petitions.create'), { replace: true });
};

// ── Form submit ───────────────────────────────────────────────────────────────
const errors  = ref({});
const saving  = ref(false);

const BODY_MAX = 20000;
const bodyCharsLeft = computed(() => BODY_MAX - (form.body?.length ?? 0));

const submit = async () => {
    errors.value = {};
    saving.value = true;
    try {
        const payload = {
            ...form,
            region_id: form.region_id || undefined,
            deadline:  form.deadline  || undefined,
        };
        const res = await window.axios.post('/api/v1/petitions', payload);
        router.visit(route('civic.petitions.show', res.data.data.id));
    } catch (err) {
        errors.value = err.response?.data?.errors ?? { general: err.response?.data?.meta?.message ?? 'An error occurred.' };
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <CivicLayout title="Create Petition">
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">Create Petition</h1>
        </template>

        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Template applied banner -->
            <div v-if="appliedTemplate"
                class="mb-4 flex items-center justify-between gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg px-4 py-3">
                <span><strong>Template applied:</strong> {{ appliedTemplate }}</span>
                <button type="button" @click="clearTemplate"
                    class="text-emerald-500 hover:text-emerald-700 transition font-medium">
                    × Clear
                </button>
            </div>

            <!-- Browse templates link -->
            <div v-else class="mb-4 text-right">
                <a :href="route('civic.petitions.templates')"
                    class="text-sm text-emerald-600 hover:text-emerald-800 font-medium transition">
                    Browse templates →
                </a>
            </div>

            <!-- ── Placeholder substitution panel ───────────────────────────── -->
            <div v-if="appliedTemplate && placeholders.length > 0"
                class="mb-4 bg-amber-50 border border-amber-200 rounded-xl p-5">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="text-xs font-semibold text-amber-700 uppercase tracking-wider">
                        Customise your template
                    </h3>
                </div>
                <p class="text-xs text-amber-600 mb-4">
                    Fill in the placeholders below — the summary and body update in real time.
                    Leave any blank to keep the <span class="font-mono bg-amber-100 px-1 rounded">[BRACKET]</span> as a reminder.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div v-for="p in placeholders" :key="p">
                        <label class="block text-xs font-semibold text-amber-800 mb-1 capitalize">
                            {{ p.replace(/_/g, ' ').toLowerCase() }}
                        </label>
                        <input
                            v-model="substitutions[p]"
                            type="text"
                            :placeholder="`e.g. your ${p.replace(/_/g, ' ').toLowerCase()}`"
                            class="w-full rounded-lg text-sm border-amber-300 focus:ring-amber-500 focus:border-amber-500 bg-white"
                        />
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">

                <div v-if="errors.general" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                    {{ errors.general }}
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input v-model="form.title" type="text" maxlength="180" required
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="A clear, compelling title" />
                    <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
                </div>

                <!-- Summary -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Summary <span class="text-red-500">*</span></label>
                    <textarea v-model="form.summary" rows="2" required
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="One or two sentences explaining the petition" />
                    <p v-if="errors.summary" class="text-red-500 text-xs mt-1">{{ errors.summary[0] }}</p>
                </div>

                <!-- Body -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full text <span class="text-red-500">*</span></label>
                    <textarea v-model="form.body" rows="8" required :maxlength="BODY_MAX"
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="Explain the issue, the ask, and the expected impact in full…" />
                    <div class="flex justify-between mt-1">
                        <p v-if="errors.body" class="text-red-500 text-xs">{{ errors.body[0] }}</p>
                        <p :class="['text-xs ml-auto', bodyCharsLeft < 500 ? 'text-amber-500' : 'text-gray-400']">
                            {{ bodyCharsLeft.toLocaleString() }} characters remaining
                        </p>
                    </div>
                </div>

                <!-- Signature goal + deadline -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Signature goal</label>
                        <input v-model.number="form.signature_goal" type="number" min="1" max="10000000"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                        <p v-if="errors.signature_goal" class="text-red-500 text-xs mt-1">{{ errors.signature_goal[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deadline</label>
                        <input v-model="form.deadline" type="datetime-local"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                    <select v-model="form.region_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">All regions (global)</option>
                        <option v-for="r in regions" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a :href="route('civic.petitions.index')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</a>
                    <button type="submit" :disabled="saving"
                        class="px-6 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition disabled:opacity-60">
                        {{ saving ? 'Publishing…' : 'Publish Petition' }}
                    </button>
                </div>
            </form>
        </div>
    </CivicLayout>
</template>
