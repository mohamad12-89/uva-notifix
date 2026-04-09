import { computed, ref } from "vue";

const AUTH_KEY = "notifix_auth_profile";
const TA_EMAILS = new Set([
  "khg5bj@virginia.edu",
  "cdd9sb@virginia.edu",
  "xfw9vp@virginia.edu",
  "uhu5nr@virginia.edu",
]);

const authProfile = ref(null);
const authReady = ref(false);
const authError = ref("");
let initialized = false;

function normalizeEmail(email) {
  return String(email || "").trim().toLowerCase();
}

function roleFromEmail(email) {
  return TA_EMAILS.has(normalizeEmail(email)) ? "ta_professor" : "student";
}

function persistProfile(profile) {
  if (!profile) {
    sessionStorage.removeItem(AUTH_KEY);
    return;
  }
  sessionStorage.setItem(AUTH_KEY, JSON.stringify(profile));
}

function hydrateProfile() {
  const raw = sessionStorage.getItem(AUTH_KEY);
  if (!raw) return null;
  try {
    const p = JSON.parse(raw);
    if (!p?.email) return null;
    return {
      ...p,
      email: normalizeEmail(p.email),
      role: roleFromEmail(p.email),
      verified: true,
    };
  } catch {
    return null;
  }
}

export async function refreshAuthProfile() {
  authProfile.value = hydrateProfile();
  authError.value = "";
  return authProfile.value;
}

export async function initializeAuth() {
  if (initialized) return;
  initialized = true;
  await refreshAuthProfile();
  authReady.value = true;
}

export function disposeAuth() {
  initialized = false;
}

export async function getStoredAuthProfile() {
  if (!initialized) await initializeAuth();
  return authProfile.value;
}

export async function isUserVerified() {
  const p = await getStoredAuthProfile();
  return Boolean(p?.verified);
}

export async function signInLocalProfile({ email, firstName = "", lastName = "" }) {
  const normalizedEmail = normalizeEmail(email);
  const profile = {
    id: normalizedEmail,
    email: normalizedEmail,
    firstName: firstName.trim(),
    lastName: lastName.trim(),
    role: roleFromEmail(normalizedEmail),
    verified: true,
    verifiedAt: new Date().toISOString(),
  };
  authProfile.value = profile;
  persistProfile(profile);
  authReady.value = true;
  return profile;
}

export async function updateLocalProfile(updates = {}) {
  const current = authProfile.value || hydrateProfile();
  if (!current) return null;
  const next = {
    ...current,
    ...updates,
    email: normalizeEmail(updates.email || current.email),
  };
  next.role = roleFromEmail(next.email);
  next.verified = true;
  authProfile.value = next;
  persistProfile(next);
  return next;
}

export async function signOutAuth() {
  authProfile.value = null;
  sessionStorage.removeItem(AUTH_KEY);
}

export function useAuthProfile() {
  const initials = computed(() => {
    const p = authProfile.value;
    if (!p?.firstName || !p?.lastName) return "";
    return `${p.firstName[0]}${p.lastName[0]}`.toUpperCase();
  });

  const isTaProfessor = computed(() => authProfile.value?.role === "ta_professor");
  const isStudent = computed(
    () => Boolean(authProfile.value) && authProfile.value?.role !== "ta_professor",
  );

  return { authProfile, authReady, authError, initials, isTaProfessor, isStudent };
}
