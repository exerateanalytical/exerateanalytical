<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    templates: { type: Array, default: () => [] },
});

// ── Form state ────────────────────────────────────────────────────────────────
const showForm = ref(false);
const editing  = ref(null);

const BLANK = () => ({
    title:                  '',
    description:            '',
    category:               'general',
    summary_template:       '',
    body_template:          '',
    default_signature_goal: 1000,
    is_premium:             false,
    sort_order:             0,
});

const form   = reactive(BLANK());
const errors = ref({});
const saving = ref(false);

const openCreate = () => {
    Object.assign(form, BLANK());
    editing.value  = null;
    errors.value   = {};
    showForm.value = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openEdit = (t) => {
    Object.assign(form, {
        title:                  t.title,
        description:            t.description ?? '',
        category:               t.category,
        summary_template:       t.summary_template ?? '',
        body_template:          t.body_template ?? '',
        default_signature_goal: t.default_signature_goal,
        is_premium:             t.is_premium,
        sort_order:             t.sort_order,
    });
    editing.value  = t;
    errors.value   = {};
    showForm.value = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const closeForm = () => {
    showForm.value = false;
    editing.value  = null;
    errors.value   = {};
};

const submit = async () => {
    errors.value = {};
    saving.value = true;
    try {
        if (editing.value) {
            await window.axios.put(`/api/v1/petition-templates/${editing.value.id}`, { ...form });
        } else {
            await window.axios.post('/api/v1/petition-templates', { ...form });
        }
        router.reload({ only: ['templates'] });
        closeForm();
    } catch (err) {
        errors.value = err.response?.data?.errors ?? { general: err.response?.data?.meta?.message ?? 'An error occurred.' };
    } finally {
        saving.value = false;
    }
};

// ── Delete ────────────────────────────────────────────────────────────────────
const deleting = ref(null);

const deleteTemplate = async (t) => {
    if (!confirm(`Delete "${t.title}"? This cannot be undone.`)) return;
    deleting.value = t.id;
    try {
        await window.axios.delete(`/api/v1/petition-templates/${t.id}`);
        router.reload({ only: ['templates'] });
    } catch (err) {
        alert(err.response?.data?.meta?.message ?? 'Could not delete template.');
    } finally {
        deleting.value = null;
    }
};

// ── Display helpers ───────────────────────────────────────────────────────────
const CATEGORY_OPTIONS = ['general', 'civic', 'policy', 'community', 'environment', 'rights'];

const CATEGORY_COLORS = {
    civic:       'bg-blue-100 text-blue-700',
    policy:      'bg-violet-100 text-violet-700',
    community:   'bg-teal-100 text-teal-700',
    environment: 'bg-green-100 text-green-700',
    rights:      'bg-rose-100 text-rose-700',
    general:     'bg-gray-100 text-gray-600',
};
const catColor = (cat) => CATEGORY_COLORS[cat] ?? CATEGORY_COLORS.general;

const PLACEHOLDER_RE = /\[([A-Z][A-Z0-9_]*)\]/g;
const countPlaceholders = (t) => {
    const found = new Set();
    const scan = (text) => {
        let m;
        const re = new RegExp(PLACEHOLDER_RE.source, 'g');
        while ((m = re.exec(text ?? '')) !== null) found.add(m[1]);
    };
    scan(t.summary_template);
    scan(t.body_template);
    return found.size;
};
</script>

<template>
    <AppLayout title="Petition Templates">
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Petition Templates</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage curated petition templates available to all users.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/admin" class="text-sm text-gray-500 hover:text-gray-700">← Admin</a>
                    <button @click="openCreate"
                        class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition shadow-sm">
                        + New Template
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- ── Create / Edit form panel ──────────────────────────────── -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
            <div v-if="showForm" class="bg-white rounded-xl border border-emerald-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-base font-semibold text-gray-900">
                        {{ editing ? 'Edit Template' : 'New Petition Template' }}
                    </h2>
                    <button @click="closeForm" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <p v-if="errors.general" class="mb-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-3">{{ errors.general }}</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                    <!-- Title -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Title <span class="text-red-500">*</span></label>
                        <input v-model="form.title" type="text" maxlength="180" required
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="Template title" />
                        <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
                    </div>

                    <!-- Description -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Description</label>
                        <textarea v-model="form.description" rows="2"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="Brief description shown in the gallery card" />
                    </div>

                    <!-- Category + sort order -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Category</label>
                        <select v-model="form.category" class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500">
                            <option v-for="c in CATEGORY_OPTIONS" :key="c" :value="c" class="capitalize">{{ c }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Default signature goal</label>
                        <input v-model.number="form.default_signature_goal" type="number" min="1"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                    </div>

                    <!-- Summary template -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Summary template</label>
                        <textarea v-model="form.summary_template" rows="2"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500 font-mono"
                            placeholder="We, the undersigned, call on [AUTHORITY] to…" />
                        <p class="text-xs text-gray-400 mt-1">Use <span class="font-mono bg-gray-100 px-1 rounded">[UPPER_CASE]</span> for placeholders users will fill in.</p>
                        <p v-if="errors.summary_template" class="text-red-500 text-xs mt-1">{{ errors.summary_template[0] }}</p>
                    </div>

                    <!-- Body template -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Body template</label>
                        <textarea v-model="form.body_template" rows="8"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500 font-mono"
                            placeholder="Full petition text with [AUTHORITY], [LOCATION], etc." />
                        <p v-if="errors.body_template" class="text-red-500 text-xs mt-1">{{ errors.body_template[0] }}</p>
                    </div>

                    <!-- Sort order + toggles -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Sort order</label>
                        <input v-model.number="form.sort_order" type="number" min="0"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500" />
                        <p class="text-xs text-gray-400 mt-1">Lower = appears first</p>
                    </div>

                    <div class="flex flex-col justify-center gap-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input v-model="form.is_premium" type="checkbox"
                                class="rounded border-gray-300 text-amber-500 focus:ring-amber-400" />
                            <span class="text-sm text-gray-700">★ Featured (premium)</span>
                        </label>
                    </div>

                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                    <button type="button" @click="closeForm"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">
                        Cancel
                    </button>
                    <button type="button" @click="submit" :disabled="saving"
                        class="px-5 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition disabled:opacity-60">
                        {{ saving ? 'Saving…' : (editing ? 'Save changes' : 'Create template') }}
                    </button>
                </div>
            </div>
            </Transition>

            <!-- ── Templates table ────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div v-if="templates.length" class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Goal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Placeholders</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Order</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Flags</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="t in templates" :key="t.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900 max-w-xs">
                                    <span class="block truncate" :title="t.title">{{ t.title }}</span>
                                    <span v-if="t.description" class="block text-xs text-gray-400 truncate mt-0.5" :title="t.description">{{ t.description }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['text-xs font-medium px-2 py-0.5 rounded-full capitalize', catColor(t.category)]">
                                        {{ t.category }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ t.default_signature_goal?.toLocaleString() }}</td>
                                <td class="px-4 py-3 text-gray-500">
                                    <span v-if="countPlaceholders(t) > 0"
                                        class="inline-flex items-center gap-1 text-xs font-mono bg-amber-50 text-amber-700 px-2 py-0.5 rounded">
                                        {{ countPlaceholders(t) }} placeholder{{ countPlaceholders(t) !== 1 ? 's' : '' }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ t.sort_order }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="t.is_premium" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">★ Featured</span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <button @click="openEdit(t)"
                                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 transition">
                                            Edit
                                        </button>
                                        <button @click="deleteTemplate(t)"
                                            :disabled="deleting === t.id"
                                            class="text-xs font-semibold text-red-600 hover:text-red-800 transition disabled:opacity-60">
                                            {{ deleting === t.id ? 'Deleting…' : 'Delete' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-10 text-center text-gray-400">
                    <p class="font-medium">No petition templates yet.</p>
                    <p class="text-sm mt-1">Click "+ New Template" to create the first one.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
