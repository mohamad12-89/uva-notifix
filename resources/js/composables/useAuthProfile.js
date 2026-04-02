import { computed, ref } from "vue";

const AUTH_STORAGE_KEY = "notifixAuthProfile";
const AUTH_EVENT = "notifix-auth-updated";

/** Only these @virginia.edu accounts get TA/Professor UI (any password in demo mode). */
const TA_PROFESSOR_EMAILS = new Set(
  [
    "khg5bj@virginia.edu",
    "cdd9sb@virginia.edu",
    "xfw9vp@virginia.edu",
    "uhu5nr@virginia.edu",
  ].map((e) => e.toLowerCase()),
);

const authProfile = ref(readProfile());

function readProfile() {
  try {
    const raw = JSON.parse(localStorage.getItem(AUTH_STORAGE_KEY) || "null");
    if (!raw) return null;
    return { ...raw, role: authRoleFromEmail(raw.email) };
  } catch {
    return null;
  }
}

/** For router guards / non-reactive reads */
export function getStoredAuthProfile() {
  return readProfile();
}

function normalizeEmail(email) {
  return String(email || "")
    .trim()
    .toLowerCase();
}

export function isTaProfessorEmail(email) {
  const e = normalizeEmail(email);
  if (!e.endsWith("@virginia.edu")) return false;
  return TA_PROFESSOR_EMAILS.has(e);
}

export function authRoleFromEmail(email) {
  return isTaProfessorEmail(email) ? "ta" : "student";
}

function writeProfile(profile) {
  const withRole = {
    ...profile,
    role: authRoleFromEmail(profile?.email),
  };
  authProfile.value = withRole;
  localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(withRole));
  window.dispatchEvent(new Event(AUTH_EVENT));
}

function clearProfile() {
  authProfile.value = null;
  localStorage.removeItem(AUTH_STORAGE_KEY);
  window.dispatchEvent(new Event(AUTH_EVENT));
}

if (typeof window !== "undefined") {
  window.addEventListener("storage", (e) => {
    if (e.key === AUTH_STORAGE_KEY) {
      authProfile.value = readProfile();
    }
  });

  window.addEventListener(AUTH_EVENT, () => {
    authProfile.value = readProfile();
  });
}

export function useAuthProfile() {
  const initials = computed(() => {
    const p = authProfile.value;
    if (!p?.firstName || !p?.lastName) return "";
    return `${p.firstName[0]}${p.lastName[0]}`.toUpperCase();
  });

  const isTaProfessor = computed(() =>
    isTaProfessorEmail(authProfile.value?.email),
  );

  const isStudent = computed(() => !isTaProfessor.value);

  return {
    authProfile,
    initials,
    isTaProfessor,
    isStudent,
    setAuthProfile: writeProfile,
    clearAuthProfile: clearProfile,
  };
}

export function isUserVerified() {
  const p = readProfile();
  return Boolean(p?.verified);
}
