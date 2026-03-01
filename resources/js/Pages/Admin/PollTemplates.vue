<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    templates: { type: Array, default: () => [] },
});

// ── Form state ────────────────────────────────────────────────────────────────
const showForm = ref(false);
const editing  = ref(null);   // null = create mode, object = edit mode

const BLANK = () => ({
    title:                '',
    description:          '',
    category:             'general',
    poll_type:            'standard',
    options:              ['', ''],
    allow_multiple_votes: false,
    verified_only:        false,
    is_premium:           false,
    sort_order:           0,
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
        title:                t.title,
        description:          t.description ?? '',
        category:             t.category,
        poll_type:            t.poll_type,
        options:              [...t.options],
        allow_multiple_votes: t.allow_multiple_votes,
        verified_only:        t.verified_only,
        is_premium:           t.is_premium,
        sort_order:           t.sort_order,
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

const addOption    = () => { if (form.options.length < 10) form.options.push(''); };
const removeOption = (i) => { if (form.options.length > 2) form.options.splice(i, 1); };

const submit = async () => {
    errors.value = {};
    saving.value = true;
    try {
        const payload = { ...form, options: form.options.filter(o => o.trim()) };
        if (editing.value) {
            await window.axios.put(`/api/v1/poll-templates/${editing.value.id}`, payload);
        } else {
            await window.axios.post('/api/v1/poll-templates', payload);
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
        await window.axios.delete(`/api/v1/poll-templates/${t.id}`);
        router.reload({ only: ['templates'] });
    } catch (err) {
        alert(err.response?.data?.meta?.message ?? 'Could not delete template.');
    } finally {
        deleting.value = null;
    }
};

// ── Display helpers ───────────────────────────────────────────────────────────
const CATEGORY_OPTIONS = ['general', 'civic', 'policy', 'election', 'feedback', 'community'];
const POLL_TYPE_OPTIONS = ['standard', 'ranked', 'weighted', 'premium'];

const CATEGORY_COLORS = {
    civic:     'bg-blue-100 text-blue-700',
    policy:    'bg-violet-100 text-violet-700',
    election:  'bg-amber-100 text-amber-700',
    feedback:  'bg-sky-100 text-sky-700',
    community: 'bg-teal-100 text-teal-700',
    general:   'bg-gray-100 text-gray-600',
};
const catColor = (cat) => CATEGORY_COLORS[cat] ?? CATEGORY_COLORS.general;
</script>

<template>
    <AppLayout title="Poll Templates">
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Poll Templates</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage curated poll templates available to all users.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/admin" class="text-sm text-gray-500 hover:text-gray-700">← Admin</a>
                    <button @click="openCreate"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition shadow-sm">
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
            <div v-if="showForm" class="bg-white rounded-xl border border-blue-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-base font-semibold text-gray-900">
                        {{ editing ? 'Edit Template' : 'New Poll Template' }}
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
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Template title" />
                        <p v-if="errors.title" class="text-red-500 text-xs mt-1">{{ errors.title[0] }}</p>
                    </div>

                    <!-- Description -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Description</label>
                        <textarea v-model="form.description" rows="2"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Brief description shown in the gallery card" />
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Category</label>
                        <select v-model="form.category" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option v-for="c in CATEGORY_OPTIONS" :key="c" :value="c" class="capitalize">{{ c }}</option>
                        </select>
                    </div>

                    <!-- Poll type -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Poll type</label>
                        <select v-model="form.poll_type" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option v-for="pt in POLL_TYPE_OPTIONS" :key="pt" :value="pt" class="capitalize">{{ pt }}</option>
                        </select>
                    </div>

                    <!-- Options -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Options <span class="text-red-500">*</span></label>
                        <div class="space-y-2">
                            <div v-for="(_, i) in form.options" :key="i" class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 w-5 text-right shrink-0">{{ i + 1 }}.</span>
                                <input v-model="form.options[i]" type="text"
                                    class="flex-1 border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                                    :placeholder="`Option ${i + 1}`" />
                                <button type="button" @click="removeOption(i)"
                                    class="text-gray-400 hover:text-red-500 transition" :disabled="form.options.length <= 2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <button type="button" @click="addOption"
                            class="mt-2 text-xs text-blue-600 hover:text-blue-800 font-medium">
                            + Add option
                        </button>
                        <p v-if="errors.options" class="text-red-500 text-xs mt-1">{{ errors.options[0] }}</p>
                    </div>

                    <!-- Sort order -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Sort order</label>
                        <input v-model.number="form.sort_order" type="number" min="0"
                            class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" />
                        <p class="text-xs text-gray-400 mt-1">Lower = appears first</p>
                    </div>

                    <!-- Toggles -->
                    <div class="flex flex-col gap-3 justify-center">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input v-model="form.allow_multiple_votes" type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                            <span class="text-sm text-gray-700">Allow multiple votes</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input v-model="form.verified_only" type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                            <span class="text-sm text-gray-700">Verified accounts only</span>
                        </label>
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
                        class="px-5 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition disabled:opacity-60">
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
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Options</th>
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
                                <td class="px-4 py-3 text-gray-600 capitalize">{{ t.poll_type }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ t.options?.length ?? 0 }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ t.sort_order }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-1.5 flex-wrap">
                                        <span v-if="t.is_premium" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">★ Featured</span>
                                        <span v-if="t.verified_only" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Verified</span>
                                        <span v-if="t.allow_multiple_votes" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Multi</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <button @click="openEdit(t)"
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
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
                    <p class="font-medium">No poll templates yet.</p>
                    <p class="text-sm mt-1">Click "+ New Template" to create the first one.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
