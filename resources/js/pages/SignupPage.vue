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

      <form
        v-if="authMode === 'signup' && step === 'signup'"
        class="space-y-3"
        @submit.prevent="startVerification"
      >
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
          Send Verification Code
        </button>
      </form>

      <form
        v-else-if="authMode === 'signup'"
        class="space-y-3"
        @submit.prevent="verifyCode"
      >
        <p class="rounded-lg bg-white/10 p-3 text-sm text-slate-200">
          Verification code sent to <span class="font-semibold">{{ form.email }}</span
          >. Enter any 6-digit code for now.
        </p>
        <p class="text-sm text-slate-300">
          Code expires in: <span class="font-semibold text-uva-orange">{{ timeLeftLabel }}</span>
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
          Verify Account
        </button>
        <button class="button-secondary w-full" type="button" @click="resetSignup">
          Start Over
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
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthProfile } from "../composables/useAuthProfile";

const PENDING_KEY = "notifixPendingSignup";
const EXPIRES_MS = 2 * 60 * 1000;

const router = useRouter();
const { setAuthProfile } = useAuthProfile();

const authMode = ref("signup");
const step = ref("signup");
const error = ref("");
const message = ref("");
const verificationCode = ref("");
const now = ref(Date.now());
let tick = null;

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

const pendingExpiresAt = computed(() => {
  const pending = readPending();
  return pending?.expiresAt ?? 0;
});

const msLeft = computed(() => Math.max(0, pendingExpiresAt.value - now.value));

const timeLeftLabel = computed(() => {
  const secs = Math.ceil(msLeft.value / 1000);
  const mm = String(Math.floor(secs / 60)).padStart(2, "0");
  const ss = String(secs % 60).padStart(2, "0");
  return `${mm}:${ss}`;
});

function readPending() {
  try {
    return JSON.parse(localStorage.getItem(PENDING_KEY) || "null");
  } catch {
    return null;
  }
}

function clearPending() {
  localStorage.removeItem(PENDING_KEY);
}

function isValidUvaEmail(email) {
  return /@virginia\.edu$/i.test(email);
}

function parseNameFromEmail(email) {
  const local = (email.split("@")[0] || "").trim();
  if (!local) return { firstName: "UVA", lastName: "Student" };
  const clean = local.replace(/[^a-zA-Z0-9]/g, "");
  if (clean.length >= 2) {
    return {
      firstName: clean[0].toUpperCase() + clean.slice(1),
      lastName: "Student",
    };
  }
  return { firstName: clean.toUpperCase(), lastName: "Student" };
}

function switchToSignup() {
  authMode.value = "signup";
  error.value = "";
  message.value = "";
}

function switchToLogin() {
  authMode.value = "login";
  error.value = "";
  message.value = "";
}

function startVerification() {
  error.value = "";
  message.value = "";

  if (!isValidUvaEmail(form.email)) {
    error.value = "Email must end with @virginia.edu.";
    return;
  }

  const pending = {
    firstName: form.firstName,
    lastName: form.lastName,
    email: form.email,
    password: form.password,
    // For now verification is mocked (any 6-digit code works), but we keep expiry.
    expiresAt: Date.now() + EXPIRES_MS,
  };

  localStorage.setItem(PENDING_KEY, JSON.stringify(pending));
  step.value = "verify";
  message.value = "Verification email sent (mocked for now).";
}

function verifyCode() {
  error.value = "";
  message.value = "";
  const pending = readPending();

  if (!pending) {
    error.value = "No pending signup found. Please sign up again.";
    step.value = "signup";
    return;
  }

  if (Date.now() > pending.expiresAt) {
    clearPending();
    step.value = "signup";
    error.value = "Verification expired after 2 minutes. Please sign up again.";
    return;
  }

  if (!/^\d{6}$/.test(verificationCode.value)) {
    error.value = "Enter a valid 6-digit verification code.";
    return;
  }

  setAuthProfile({
    firstName: pending.firstName,
    lastName: pending.lastName,
    email: pending.email,
    password: pending.password,
    verified: true,
    verifiedAt: new Date().toISOString(),
  });
  clearPending();
  message.value = "Account verified successfully.";
  router.replace("/");
}

function resetSignup() {
  clearPending();
  verificationCode.value = "";
  step.value = "signup";
  error.value = "";
  message.value = "";
}

function login() {
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

  // Demo mode: accept fake UVA accounts and fake passwords.
  const nameGuess = parseNameFromEmail(loginForm.email);
  setAuthProfile({
    firstName: nameGuess.firstName,
    lastName: nameGuess.lastName,
    email: loginForm.email,
    password: loginForm.password,
    verified: true,
    verifiedAt: new Date().toISOString(),
  });
  router.replace("/");
}

onMounted(() => {
  const pending = readPending();
  if (pending && Date.now() < pending.expiresAt) {
    form.firstName = pending.firstName;
    form.lastName = pending.lastName;
    form.email = pending.email;
    step.value = "verify";
  } else if (pending) {
    clearPending();
  }

  tick = setInterval(() => {
    now.value = Date.now();
    if (step.value === "verify" && msLeft.value === 0) {
      resetSignup();
      error.value = "Verification expired after 2 minutes. Please sign up again.";
    }
  }, 1000);
});

onBeforeUnmount(() => {
  if (tick) clearInterval(tick);
});
</script>
