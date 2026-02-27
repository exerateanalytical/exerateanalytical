<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    country:          { type: Object, default: null },
    federationRegions:{ type: Array,  default: () => [] },
    riskTiers:        { type: Array,  default: () => ['low', 'moderate', 'high'] },
});

const isEdit = computed(() => !!props.country);

const form = reactive({
    name:              props.country?.name ?? '',
    iso_code:          props.country?.iso_code ?? '',
    continent_region:  props.country?.continent_region ?? '',
    region_id:         props.country?.region_id ?? '',
    risk_tier:         props.country?.risk_tier ?? 'low',
    is_active:         props.country?.is_active ?? true,
});

const errors  = ref({});
const saving  = ref(false);

const submit = async () => {
    errors.value = {};
    saving.value = true;
    try {
        const payload = {
            ...form,
            iso_code:  form.iso_code.toUpperCase(),
            region_id: form.region_id || undefined,
        };
        if (isEdit.value) {
            await window.axios.put(`/api/v1/countries/${props.country.id}`, payload);
        } else {
            await window.axios.post('/api/v1/countries', payload);
        }
        router.visit('/admin/countries');
    } catch (err) {
        errors.value = err.response?.data?.errors
            ?? { general: err.response?.data?.meta?.message ?? err.response?.data?.message ?? 'An error occurred.' };
    } finally {
        saving.value = false;
    }
};

const tierColor = { low: 'text-green-600', moderate: 'text-amber-600', high: 'text-red-600' };
</script>

<template>
    <AppLayout :title="isEdit ? `Edit — ${country.name}` : 'Create Country'">
        <template #header>
            <div class="flex items-center gap-3 flex-wrap">
                <a href="/admin/countries" class="text-sm text-gray-500 hover:text-gray-700">← Countries</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm font-semibold text-gray-800">{{ isEdit ? `Edit ${country.name}` : 'Create Country' }}</span>
            </div>
        </template>

        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">

                <div v-if="errors.general" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                    {{ errors.general }}
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input v-model="form.name" type="text" required maxlength="200"
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="e.g. Federal Republic of Exerate" />
                    <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}</p>
                </div>

                <!-- ISO Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ISO Code <span class="text-red-500">*</span></label>
                    <input v-model="form.iso_code" type="text" required maxlength="3"
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 uppercase font-mono"
                        placeholder="e.g. EXR" />
                    <p class="text-xs text-gray-400 mt-1">2–3 character ISO country code (will be uppercased)</p>
                    <p v-if="errors.iso_code" class="text-red-500 text-xs mt-1">{{ Array.isArray(errors.iso_code) ? errors.iso_code[0] : errors.iso_code }}</p>
                </div>

                <!-- Continent Region -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Continent / Region</label>
                    <input v-model="form.continent_region" type="text" maxlength="100"
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="e.g. Sub-Saharan Africa" />
                    <p v-if="errors.continent_region" class="text-red-500 text-xs mt-1">{{ Array.isArray(errors.continent_region) ? errors.continent_region[0] : errors.continent_region }}</p>
                </div>

                <!-- Federation Region -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Federation Region</label>
                    <select v-model="form.region_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">No federation region</option>
                        <option v-for="r in federationRegions" :key="r.id" :value="r.id">{{ r.name }} ({{ r.code }})</option>
                    </select>
                </div>

                <!-- Risk Tier -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Risk Tier <span class="text-red-500">*</span></label>
                    <select v-model="form.risk_tier" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option v-for="tier in riskTiers" :key="tier" :value="tier">
                            {{ tier.charAt(0).toUpperCase() + tier.slice(1) }} Risk
                        </option>
                    </select>
                    <p v-if="errors.risk_tier" class="text-red-500 text-xs mt-1">{{ Array.isArray(errors.risk_tier) ? errors.risk_tier[0] : errors.risk_tier }}</p>
                </div>

                <!-- Is Active -->
                <div class="flex items-center gap-3">
                    <input id="is_active" v-model="form.is_active" type="checkbox"
                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                    <label for="is_active" class="text-sm font-medium text-gray-700">Active (included in analytics and public listings)</label>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <a href="/admin/countries" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</a>
                    <button type="submit" :disabled="saving"
                        class="px-6 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition disabled:opacity-60">
                        {{ saving ? (isEdit ? 'Saving…' : 'Creating…') : (isEdit ? 'Save Changes' : 'Create Country') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
