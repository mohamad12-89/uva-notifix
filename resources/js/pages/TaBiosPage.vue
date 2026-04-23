<template>
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-3xl font-bold text-uva-orange">TA Bios</h2>
      <button v-if="canAddBio" class="button-secondary" @click="openForm">
        Add Your Bio
      </button>
    </div>

    <form
      v-if="showForm && isTaProfessor"
      class="card grid gap-3 p-6 md:grid-cols-2"
      @submit.prevent="submit"
    >
      <input v-model="form.name" required class="input" placeholder="Name" />
      <input v-model="form.year" required class="input" placeholder="Year" />
      <input v-model="form.major" required class="input" placeholder="Major" />
      <input
        v-model="form.email"
        required
        class="input disabled:opacity-50 disabled:cursor-not-allowed"
        placeholder="Email"
        disabled
      />
      <textarea
        v-model="form.notes"
        class="input md:col-span-2"
        placeholder="Notes"
      />
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-200">
          Profile picture (optional)
        </label>
        <div class="flex items-center gap-4">
          <img
            :src="formPreviewUrl || defaultAvatarUrl"
            alt="Profile preview"
            class="h-16 w-16 rounded-full border border-white/20 object-cover"
          />
          <div class="flex-1 space-y-2">
            <input
              type="file"
              accept="image/png,image/jpeg,image/webp,image/gif"
              class="input w-full"
              @change="onImageSelected"
            />
            <button
              v-if="formPreviewUrl"
              type="button"
              class="rounded bg-slate-700 px-3 py-1 text-xs text-white"
              @click="clearSelectedImage"
            >
              Remove selected image
            </button>
          </div>
        </div>
      </div>
      <button class="button-primary md:col-span-2" type="submit">
        Save TA Bio
      </button>
    </form>

    <div class="grid grid-cols-1 gap-4">
      <div v-for="bio in bios" :key="bio.id" class="card p-5">
        <div class="flex w-full items-start justify-between gap-4">
          <div class="flex items-start gap-3">
            <img
              :src="bio.profile_image_url || defaultAvatarUrl"
              :alt="`${bio.name} avatar`"
              class="h-14 w-14 rounded-full border border-white/20 object-cover"
            />
            <div>
            <p class="text-lg font-semibold text-white">{{ bio.name }}</p>
            <p class="text-sm text-slate-200">
              {{ bio.year }} | {{ bio.major }}
            </p>
          </div>
          </div>
          <div class="ml-auto text-right text-sm text-slate-100">
            <p>{{ bio.email }}</p>
            <p class="mt-1 text-slate-300">{{ bio.notes }}</p>
          </div>
        </div>
        <div
          v-if="isProfessor || bio.email === authProfile?.email"
          class="mt-3 flex gap-2"
        >
          <button
            class="rounded bg-slate-700 px-3 py-1 text-sm text-white"
            @click="startEdit(bio)"
          >
            Edit
          </button>
          <button
            class="rounded bg-red-600 px-3 py-1 text-sm text-white"
            @click="remove(bio.id)"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from "vue";
import { api } from "../lib/api";
import { useAuthProfile } from "../composables/useAuthProfile";

const { isTaProfessor, authProfile, isProfessor } = useAuthProfile();
const defaultAvatarUrl = "/default-ta-avatar.svg";

const showForm = ref(false);
const editingId = ref(null);
const bios = ref([]);
const form = reactive({ name: "", year: "", major: "", email: "", notes: "" });
const selectedImage = ref(null);
const formPreviewUrl = ref("");
const clearExistingImage = ref(false);

const hasExistingBio = computed(() => {
  if (!authProfile.value?.email) return false;
  return bios.value.some((bio) => bio.email === authProfile.value.email);
});

const canAddBio = computed(() => {
  return isTaProfessor.value && !hasExistingBio.value;
});

const load = async () => {
  const response = await api.get("/ta-bios");
  bios.value = response.data;
};

const openForm = () => {
  resetForm();
  showForm.value = true;
};

const resetForm = () => {
  form.name = "";
  form.year = "";
  form.major = "";
  form.email = authProfile.value?.email || "";
  form.notes = "";
  editingId.value = null;
  selectedImage.value = null;
  formPreviewUrl.value = "";
  clearExistingImage.value = false;
};

const onImageSelected = (event) => {
  const file = event.target.files?.[0];
  if (!file) return;
  selectedImage.value = file;
  formPreviewUrl.value = URL.createObjectURL(file);
  clearExistingImage.value = false;
};

const clearSelectedImage = () => {
  selectedImage.value = null;
  formPreviewUrl.value = "";
  clearExistingImage.value = true;
};

const submit = async () => {
  const payload = new FormData();
  payload.append("name", form.name);
  payload.append("year", form.year);
  payload.append("major", form.major);
  payload.append("email", form.email);
  payload.append("notes", form.notes ?? "");
  payload.append("clear_profile_image", clearExistingImage.value ? "1" : "0");
  if (selectedImage.value) {
    payload.append("profile_image", selectedImage.value);
  }

  if (editingId.value) {
    const bioToUpdate = bios.value.find((b) => b.id === editingId.value);
    if (
      !bioToUpdate ||
      (!isProfessor.value && bioToUpdate.email !== authProfile.value?.email)
    ) {
      console.error("Permission denied to update this bio.");
      return;
    }
    await api.post(`/ta-bios/${editingId.value}?_method=PUT`, payload);
  } else {
    if (!canAddBio.value) {
      console.error("Permission denied to create a bio.");
      return;
    }
    await api.post("/ta-bios", payload);
  }
  resetForm();
  showForm.value = false;
  await load();
};

const startEdit = (bio) => {
  if (!isProfessor.value && bio.email !== authProfile.value?.email) {
    console.error("Permission denied to edit this bio.");
    return;
  }
  showForm.value = true;
  editingId.value = bio.id;
  form.name = bio.name;
  form.year = bio.year;
  form.major = bio.major;
  form.email = bio.email;
  form.notes = bio.notes ?? "";
  selectedImage.value = null;
  clearExistingImage.value = false;
  formPreviewUrl.value = bio.profile_image_url || "";
};

const remove = async (id) => {
  const bioToRemove = bios.value.find((b) => b.id === id);
  if (
    !bioToRemove ||
    (!isProfessor.value && bioToRemove.email !== authProfile.value?.email)
  ) {
    console.error("Permission denied to delete this bio.");
    return;
  }
  await api.delete(`/ta-bios/${id}`);
  await load();
};

onMounted(load);
</script>
