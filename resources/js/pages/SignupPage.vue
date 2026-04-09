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
          We sent a verification link to
          <span class="font-semibold">{{ verificationEmail }}</span>.
          Click the link in your email to continue.
        </p>
        <p class="mt-3 rounded-lg bg-white/10 p-3 text-sm text-slate-200">
          Time remaining: <span class="font-semibold">{{ verificationSecondsRemaining }}s</span>
        </p>

        <div class="mt-4 grid grid-cols-1 gap-2">
          <button class="button-primary w-full" type="button" @click="checkVerificationStatus">
            I clicked the verification link
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
import { supabase } from "../lib/supabase";
import {
  getStoredAuthProfile,
  initializeAuth,
  refreshAuthProfile,
} from "../composables/useAuthProfile";

const router = useRouter();

const authMode = ref("signup");
const error = ref("");
const message = ref("");
const showVerificationModal = ref(false);
const verificationEmail = ref("");
const verificationSecondsRemaining = ref(60);
const verificationError = ref("");
const verificationNote = ref("");
let verificationCountdownInterval = null;
let verificationPollInterval = null;

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

  if (form.password.length < 6) {
    error.value = "Password must be at least 6 characters.";
    return;
  }

  const normalizedEmail = form.email.trim().toLowerCase();
  const redirectTo = `${window.location.origin}/signup`;
  const { error: signUpError } = await supabase.auth.signUp({
    email: normalizedEmail,
    password: form.password,
    options: {
      emailRedirectTo: redirectTo,
      data: {
        first_name: form.firstName.trim(),
        last_name: form.lastName.trim(),
      },
    },
  });

  if (signUpError) {
    error.value = signUpError.message || "Unable to start sign up.";
    return;
  }

  // Force a confirmation email send for cases where signUp succeeds but no email arrives
  // (for example existing unverified accounts).
  await sendConfirmationEmail(normalizedEmail);

  verificationEmail.value = normalizedEmail;
  showVerificationModal.value = true;
  verificationError.value = "";
  verificationNote.value = "Verification email sent.";
  startVerificationCountdown();
  startVerificationPolling();
}

function clearVerificationTimers() {
  if (verificationCountdownInterval) {
    clearInterval(verificationCountdownInterval);
    verificationCountdownInterval = null;
  }
  if (verificationPollInterval) {
    clearInterval(verificationPollInterval);
    verificationPollInterval = null;
  }
}

function closeVerificationModal() {
  showVerificationModal.value = false;
  verificationError.value = "";
  verificationNote.value = "";
  clearVerificationTimers();
}

function startVerificationCountdown() {
  clearVerificationTimers();
  verificationSecondsRemaining.value = 60;
  verificationCountdownInterval = setInterval(() => {
    verificationSecondsRemaining.value = Math.max(
      0,
      verificationSecondsRemaining.value - 1,
    );
    if (verificationSecondsRemaining.value === 0) {
      verificationError.value =
        "Verification timed out. Resend the email or change your address.";
      clearVerificationTimers();
    }
  }, 1000);
}

function startVerificationPolling() {
  verificationPollInterval = setInterval(async () => {
    if (!showVerificationModal.value || verificationSecondsRemaining.value <= 0) {
      return;
    }
    await checkVerificationStatus(false);
  }, 4000);
}

async function checkVerificationStatus(showPendingMessage = true) {
  verificationError.value = "";
  await refreshAuthProfile();
  const profile = await getStoredAuthProfile();
  if (profile?.verified) {
    closeVerificationModal();
    authMode.value = "login";
    message.value = "Email verified successfully. You can now use Notifix.";
    router.replace("/");
    return;
  }
  if (showPendingMessage) {
    verificationNote.value =
      "Still waiting for verification. Click the link in your email, then try again.";
  }
}

async function resendVerificationEmail() {
  verificationError.value = "";
  verificationNote.value = "";
  const resendError = await sendConfirmationEmail(verificationEmail.value);
  if (resendError) {
    verificationError.value =
      resendError ||
      "Unable to resend verification email. Check Supabase email provider settings.";
    return;
  }
  verificationNote.value = "Verification email resent.";
  startVerificationCountdown();
  startVerificationPolling();
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

  const { error: loginError } = await supabase.auth.signInWithPassword({
    email: loginForm.email.trim().toLowerCase(),
    password: loginForm.password,
  });

  if (loginError) {
    const normalizedEmail = loginForm.email.trim().toLowerCase();
    const isUnverifiedLogin =
      /confirm your email|email not confirmed|not confirmed/i.test(
        loginError.message || "",
      );
    if (isUnverifiedLogin) {
      const resendError = await sendConfirmationEmail(normalizedEmail);
      if (resendError) {
        error.value = `Email is not verified yet. We could not resend verification email: ${resendError}`;
      } else {
        error.value =
          "Email is not verified yet. We sent a fresh verification link to your inbox.";
      }
      return;
    }
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

onBeforeUnmount(() => {
  clearVerificationTimers();
});

async function sendConfirmationEmail(email) {
  const redirectTo = `${window.location.origin}/signup`;
  const { error: resendError } = await supabase.auth.resend({
    type: "signup",
    email,
    options: { emailRedirectTo: redirectTo },
  });

  if (!resendError) {
    return "";
  }

  // Fallback for some Supabase setups where resend/signup is restricted:
  // send a magic-link style email users can still click to return and verify session.
  const { error: otpError } = await supabase.auth.signInWithOtp({
    email,
    options: {
      emailRedirectTo: redirectTo,
      shouldCreateUser: false,
    },
  });
  return otpError?.message || resendError.message || "Unknown email delivery error.";
}
</script>
