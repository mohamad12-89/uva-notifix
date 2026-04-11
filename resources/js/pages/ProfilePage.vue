<template>
  <section class="mx-auto grid w-full max-w-2xl gap-6">
    <div
      class="card border-white/25 bg-gradient-to-br from-uva-blue/45 via-slate-900/65 to-uva-orange/15 p-7 shadow-[0_12px_30px_rgba(7,12,24,0.35)]"
    >
      <p
        v-if="roleGreeting"
        class="mb-2 text-center text-sm font-medium text-uva-orange"
      >
        {{ roleGreeting }}
      </p>
      <h2 class="mb-1 text-center text-3xl font-bold text-uva-orange">
        Profile Settings
      </h2>
      <p class="mb-5 text-center text-sm text-slate-300">
        Update your personal information and password.
      </p>

      <form class="space-y-3" @submit.prevent="saveProfile">
        <input
          v-model.trim="form.firstName"
          required
          class="input w-full"
          placeholder="First name"
        />
        <input
          v-model.trim="form.lastName"
          required
          class="input w-full"
          placeholder="Last name"
        />
        <input
          v-model.trim="form.email"
          readonly
          class="input w-full opacity-80"
          placeholder="UVA email"
        />

        <input
          v-model="form.currentPassword"
          class="input w-full"
          type="password"
          placeholder="Current password"
        />
        <input
          v-model="form.newPassword"
          class="input w-full"
          type="password"
          placeholder="New password"
          minlength="6"
        />
        <input
          v-model="form.confirmPassword"
          class="input w-full"
          type="password"
          placeholder="Confirm new password"
          minlength="6"
        />

        <button class="button-primary mt-2 w-full" type="submit">
          Save Changes
        </button>
        <button class="button-secondary w-full" type="button" @click="signOut">
          Sign Out
        </button>
      </form>

      <p v-if="error" class="mt-4 rounded-lg bg-red-100 p-3 text-sm text-red-700">
        {{ error }}
      </p>
      <p v-if="message" class="mt-4 rounded-lg bg-green-100 p-3 text-sm text-green-700">
        {{ message }}
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import {
  refreshAuthProfile,
  signOutAuth,
  updateLocalProfile,
  useAuthProfile,
} from "../composables/useAuthProfile";

const { authProfile, isStudent, isTa, isProfessor } = useAuthProfile();

const roleGreeting = computed(() => {
  if (isProfessor.value) return "Hello, Professor — thanks for using Notifix.";
  if (isTa.value) return "Hello, TA — thanks for supporting students on Notifix.";
  if (isStudent.value) return "Hello, student — glad you are here.";
  return "";
});

const error = ref("");
const message = ref("");
const form = reactive({
  firstName: "",
  lastName: "",
  email: "",
  currentPassword: "",
  newPassword: "",
  confirmPassword: "",
});

onMounted(() => {
  if (!authProfile.value) return;
  form.firstName = authProfile.value.firstName || "";
  form.lastName = authProfile.value.lastName || "";
  form.email = authProfile.value.email || "";
});

async function saveProfile() {
  error.value = "";
  message.value = "";
  if (!authProfile.value) {
    error.value = "No logged-in profile found.";
    return;
  }

  if (!form.firstName || !form.lastName) {
    error.value = "First name and last name are required.";
    return;
  }

  const wantsPasswordChange = Boolean(
    form.currentPassword || form.newPassword || form.confirmPassword,
  );

  if (wantsPasswordChange) {
    if (!form.newPassword || !form.confirmPassword) {
      error.value = "Please fill out all new password fields.";
      return;
    }
    if (form.newPassword.length < 6) {
      error.value = "New password must be at least 6 characters.";
      return;
    }
    if (form.newPassword !== form.confirmPassword) {
      error.value = "New password and confirmation do not match.";
      return;
    }
  }

  await updateLocalProfile({
    firstName: form.firstName.trim(),
    lastName: form.lastName.trim(),
  });
  await refreshAuthProfile();

  form.currentPassword = "";
  form.newPassword = "";
  form.confirmPassword = "";
  message.value = "Profile updated successfully.";
}

async function signOut() {
  await signOutAuth();
  window.location.assign("/signup");
}
</script>
