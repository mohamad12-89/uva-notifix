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

      <form v-if="authMode === 'signup'" class="space-y-3" @submit.prevent="startVerification">
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

    <div
      v-if="showVerificationModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4"
    >
      <div class="w-full max-w-md rounded-2xl border border-white/20 bg-slate-900 p-6 shadow-2xl">
        <h3 class="text-xl font-bold text-uva-orange">Verify your email</h3>
        <p class="mt-2 text-sm text-slate-200">
          We sent a verification code to
          <span class="font-semibold">{{ verificationEmail }}</span>.
          Enter it below to continue.
        </p>
        <input
          v-model.trim="verificationCode"
          type="text"
          maxlength="6"
          class="input mt-4 w-full"
          placeholder="6-digit verification code"
        />

        <div class="mt-4 grid grid-cols-1 gap-2">
          <button class="button-primary w-full" type="button" @click="confirmVerificationCode">
            Verify and continue
          </button>
          <button class="button-secondary w-full" type="button" @click="resendVerificationEmail">
            Resend verification email
          </button>
          <button class="button-secondary w-full" type="button" @click="changeSignupEmail">
            Change email
          </button>
        </div>

        <p v-if="verificationError" class="mt-4 rounded-lg bg-red-100 p-3 text-sm text-red-700">
          {{ verificationError }}
        </p>
        <p v-if="verificationNote" class="mt-4 rounded-lg bg-green-100 p-3 text-sm text-green-700">
          {{ verificationNote }}
        </p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import {
  confirmSignUpCode,
  getStoredAuthProfile,
  initializeAuth,
  refreshAuthProfile,
  resendSignUpCode,
  signInWithEmailPassword,
  signUpWithEmailPassword,
} from "../composables/useAuthProfile";

const router = useRouter();

const authMode = ref("signup");
const error = ref("");
const message = ref("");
const showVerificationModal = ref(false);
const verificationEmail = ref("");
const verificationCode = ref("");
const verificationError = ref("");
const verificationNote = ref("");

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
  error.value = "";
  message.value = "";
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

  if (!form.password) {
    error.value = "Password is required.";
    return;
  }

  try {
    await signUpWithEmailPassword({
      firstName: form.firstName,
      lastName: form.lastName,
      email: form.email,
      password: form.password,
    });
    verificationEmail.value = form.email.trim().toLowerCase();
    verificationCode.value = "";
    showVerificationModal.value = true;
    verificationError.value = "";
    verificationNote.value = "Verification code sent. Check your UVA inbox.";
  } catch (err) {
    verificationError.value = "";
    error.value =
      err?.name === "UsernameExistsException"
        ? "This email already has an account. Try logging in."
        : err?.message || "Could not start sign-up. Please try again.";
  }
}

function closeVerificationModal() {
  showVerificationModal.value = false;
  verificationError.value = "";
  verificationNote.value = "";
  verificationCode.value = "";
}

async function confirmVerificationCode() {
  if (!verificationCode.value.trim()) {
    verificationError.value = "Please enter the verification code from your email.";
    return;
  }
  try {
    await confirmSignUpCode(verificationEmail.value, verificationCode.value);
    await signInWithEmailPassword({
      email: verificationEmail.value,
      password: form.password,
    });
    await refreshAuthProfile();
    closeVerificationModal();
    message.value = "Email verified successfully.";
    router.replace("/");
  } catch (err) {
    verificationError.value =
      err?.name === "CodeMismatchException"
        ? "The verification code is incorrect."
        : err?.name === "ExpiredCodeException"
          ? "That code expired. Request a new one."
          : err?.message || "Verification failed.";
  }
}

async function resendVerificationEmail() {
  try {
    verificationError.value = "";
    await resendSignUpCode(verificationEmail.value);
    verificationNote.value = "A new verification code was sent.";
  } catch (err) {
    verificationError.value = err?.message || "Could not resend code.";
  }
}

function changeSignupEmail() {
  closeVerificationModal();
  verificationEmail.value = "";
  message.value = "Update your email and sign up again.";
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

  try {
    await signInWithEmailPassword({
      email: loginForm.email.trim().toLowerCase(),
      password: loginForm.password,
    });
    await refreshAuthProfile();
    router.replace("/");
  } catch (err) {
    error.value =
      err?.name === "UserNotConfirmedException"
        ? "Your email is not verified yet. Please complete verification first."
        : err?.message || "Could not sign in. Check your email and password.";
  }
}

onMounted(async () => {
  await initializeAuth();
  const profile = await getStoredAuthProfile();
  if (profile?.verified) {
    router.replace("/");
  }
});

onBeforeUnmount(() => {
  closeVerificationModal();
});
</script>
