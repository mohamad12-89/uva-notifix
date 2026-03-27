<template>
  <section class="mx-auto grid w-full max-w-2xl gap-6">
    <div
      class="card border-white/25 bg-gradient-to-br from-uva-blue/45 via-slate-900/65 to-uva-orange/15 p-7 shadow-[0_12px_30px_rgba(7,12,24,0.35)]"
    >
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
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthProfile } from "../composables/useAuthProfile";

const router = useRouter();
const { authProfile, setAuthProfile, clearAuthProfile } = useAuthProfile();

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

function saveProfile() {
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

  let nextPassword = authProfile.value.password || "";
  if (wantsPasswordChange) {
    if (!form.newPassword || !form.confirmPassword) {
      error.value = "Please fill out all new password fields.";
      return;
    }
    if ((authProfile.value.password || "") !== form.currentPassword) {
      error.value = "Current password is incorrect.";
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
    nextPassword = form.newPassword;
  }

  setAuthProfile({
    ...authProfile.value,
    firstName: form.firstName,
    lastName: form.lastName,
    email: form.email,
    password: nextPassword,
  });

  form.currentPassword = "";
  form.newPassword = "";
  form.confirmPassword = "";
  message.value = "Profile updated successfully.";
}

function signOut() {
  clearAuthProfile();
  router.replace("/signup");
}
</script>
