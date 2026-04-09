<template>
  <section class="mx-auto grid w-full max-w-2xl gap-6">
    <div
      class="card border-white/25 bg-gradient-to-br from-uva-blue/45 via-slate-900/65 to-uva-orange/15 p-7 shadow-[0_12px_30px_rgba(7,12,24,0.35)]"
    >
      <h2 class="mb-1 text-center text-3xl font-bold text-uva-orange">
        Welcome to Notifix
      </h2>
      <p class="mb-5 text-center text-sm text-slate-300">
        Sign up or log in with your UVA email.
      </p>

      <div class="mb-4 grid grid-cols-2 gap-2 rounded-xl bg-white/5 p-1">
        <button
          type="button"
          class="rounded-lg px-3 py-2 text-sm font-semibold transition"
          :class="
            authMode === 'signup'
              ? 'bg-uva-orange text-white'
              : 'text-slate-200 hover:bg-white/10'
          "
          @click="switchToSignup"
        >
          Sign Up
        </button>
        <button
          type="button"
          class="rounded-lg px-3 py-2 text-sm font-semibold transition"
          :class="
            authMode === 'login'
              ? 'bg-uva-orange text-white'
              : 'text-slate-200 hover:bg-white/10'
          "
          @click="switchToLogin"
        >
          Log In
        </button>
      </div>

      <form v-if="authMode === 'signup' && step === 'signup'" class="space-y-3" @submit.prevent="startVerification">
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
          required
          class="input w-full"
          type="email"
          placeholder="UVA email (e.g. abc2de@virginia.edu)"
        />
        <input
          v-model="form.password"
          required
          class="input w-full"
          type="password"
          placeholder="Password"
          minlength="6"
        />
        <button class="button-secondary mt-2 w-full" type="submit">
          Sign Up
        </button>
      </form>

      <form v-else-if="authMode === 'signup'" class="space-y-3" @submit.prevent="finishVerification">
        <p class="rounded-lg bg-white/10 p-3 text-sm text-slate-200">
          A verification code was sent to <span class="font-semibold">{{ form.email }}</span>.
          Enter that code below to verify your email.
        </p>
        <input
          v-model.trim="verificationCode"
          required
          class="input w-full text-center tracking-[0.3em]"
          inputmode="numeric"
          maxlength="6"
          placeholder="123456"
        />
        <button class="button-primary mt-2 w-full" type="submit">
          Verify Code
        </button>
        <button class="button-secondary w-full" type="button" @click="resendCode">
          Resend Code
        </button>
        <button class="button-secondary w-full" type="button" @click="switchToSignup">
          Use a Different Email
        </button>
      </form>

      <form v-else class="space-y-3" @submit.prevent="login">
        <input
          v-model.trim="loginForm.email"
          required
          class="input w-full"
          type="email"
          placeholder="UVA email (e.g. abc2de@virginia.edu)"
        />
        <input
          v-model="loginForm.password"
          required
          class="input w-full"
          type="password"
          placeholder="Password"
          minlength="1"
        />
        <button class="button-secondary mt-2 w-full" type="submit">
          Log In
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
import { supabase } from "../lib/supabase";
import {
  getStoredAuthProfile,
  initializeAuth,
  refreshAuthProfile,
} from "../composables/useAuthProfile";

const router = useRouter();

const authMode = ref("signup");
const step = ref("signup");
const error = ref("");
const message = ref("");
const verificationCode = ref("");

const form = reactive({
  firstName: "",
  lastName: "",
  email: "",
  password: "",
});
const loginForm = reactive({
  email: "",
  password: "",
});

function isValidUvaEmail(email) {
  return /@virginia\.edu$/i.test(email);
}

function switchToSignup() {
  authMode.value = "signup";
  step.value = "signup";
  error.value = "";
  message.value = "";
  verificationCode.value = "";
}

function switchToLogin() {
  authMode.value = "login";
  error.value = "";
  message.value = "";
}

async function startVerification() {
  error.value = "";
  message.value = "";

  if (!isValidUvaEmail(form.email)) {
    error.value = "Email must end with @virginia.edu.";
    return;
  }

  if (form.password.length < 6) {
    error.value = "Password must be at least 6 characters.";
    return;
  }

  const { error: otpError } = await supabase.auth.signInWithOtp({
    email: form.email.trim().toLowerCase(),
    options: {
      shouldCreateUser: true,
    },
  });

  if (otpError) {
    error.value = otpError.message || "Unable to send verification code.";
    return;
  }

  step.value = "verify";
  message.value = "Verification code sent. Check your email.";
}

async function finishVerification() {
  error.value = "";
  message.value = "";

  if (!/^\d{6}$/.test(verificationCode.value)) {
    error.value = "Enter a valid 6-digit verification code.";
    return;
  }

  const email = form.email.trim().toLowerCase();
  const { error: verifyError } = await supabase.auth.verifyOtp({
    email,
    token: verificationCode.value,
    type: "email",
  });
  if (verifyError) {
    error.value =
      verifyError.message || "Invalid verification code. Please try again.";
    return;
  }

  const { error: updateError } = await supabase.auth.updateUser({
    password: form.password,
    data: {
      first_name: form.firstName.trim(),
      last_name: form.lastName.trim(),
    },
  });
  if (updateError) {
    error.value = updateError.message || "Verification succeeded but profile setup failed.";
    return;
  }

  await refreshAuthProfile();
  const profile = await getStoredAuthProfile();
  if (!profile?.verified) {
    error.value = "Verification not confirmed yet. Please try again.";
    return;
  }
  router.replace("/");
}

async function resendCode() {
  error.value = "";
  message.value = "";
  const email = form.email.trim().toLowerCase();
  const { error: otpError } = await supabase.auth.signInWithOtp({
    email,
    options: { shouldCreateUser: true },
  });
  if (otpError) {
    error.value = otpError.message || "Unable to resend verification code.";
    return;
  }
  message.value = "A new verification code has been sent.";
}

async function login() {
  error.value = "";
  message.value = "";
  if (!isValidUvaEmail(loginForm.email)) {
    error.value = "Email must end with @virginia.edu.";
    return;
  }
  if (!loginForm.password) {
    error.value = "Password is required.";
    return;
  }

  const { error: loginError } = await supabase.auth.signInWithPassword({
    email: loginForm.email.trim().toLowerCase(),
    password: loginForm.password,
  });

  if (loginError) {
    error.value = loginError.message || "Invalid email or password.";
    return;
  }

  await refreshAuthProfile();
  const profile = await getStoredAuthProfile();
  if (!profile?.verified) {
    error.value = "Please verify your email before logging in.";
    await supabase.auth.signOut();
    return;
  }
  router.replace("/");
}

onMounted(async () => {
  await initializeAuth();
  const profile = await getStoredAuthProfile();
  if (profile?.verified) {
    router.replace("/");
  }
});
</script>
