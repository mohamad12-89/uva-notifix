import { computed, ref } from "vue";

const AUTH_KEY = "notifix_auth_profile";
const LS_EXTRA_TA = "notifix_extra_ta_emails";
const LS_EXTRA_PROFESSOR = "notifix_extra_professor_emails";

/** Built-in TA accounts (cannot be removed from dashboard). */
export const DEFAULT_TA_EMAILS = [
  "khg5bj@virginia.edu",
  "cdd9sb@virginia.edu",
  "xfw9vp@virginia.edu",
  "uhu5nr@virginia.edu",
];

/** Built-in professor account (cannot be removed from dashboard). */
export const DEFAULT_PROFESSOR_EMAILS = ["amm8km@virginia.edu"];

const authProfile = ref(null);
const authReady = ref(false);
const authError = ref("");
let initialized = false;
let storageListenerAttached = false;

function normalizeEmail(email) {
  return String(email || "").trim().toLowerCase();
}

function readExtraList(key) {
  try {
    const raw = localStorage.getItem(key);
    if (!raw) return [];
    const arr = JSON.parse(raw);
    return Array.isArray(arr) ? arr.map(normalizeEmail).filter(Boolean) : [];
  } catch {
    return [];
  }
}

function writeExtraList(key, list) {
  const unique = [...new Set(list.map(normalizeEmail).filter(Boolean))];
  localStorage.setItem(key, JSON.stringify(unique));
}

export function getTaEmailSet() {
  const extras = readExtraList(LS_EXTRA_TA);
  return new Set([...DEFAULT_TA_EMAILS.map(normalizeEmail), ...extras]);
}

export function getProfessorEmailSet() {
  const extras = readExtraList(LS_EXTRA_PROFESSOR);
  return new Set([...DEFAULT_PROFESSOR_EMAILS.map(normalizeEmail), ...extras]);
}

/** Sorted list for display: { email, isDefault } */
export function listTaEmailsForDisplay() {
  const set = getTaEmailSet();
  const defaults = new Set(DEFAULT_TA_EMAILS.map(normalizeEmail));
  return [...set].sort().map((email) => ({
    email,
    isDefault: defaults.has(email),
  }));
}

export function listProfessorEmailsForDisplay() {
  const set = getProfessorEmailSet();
  const defaults = new Set(DEFAULT_PROFESSOR_EMAILS.map(normalizeEmail));
  return [...set].sort().map((email) => ({
    email,
    isDefault: defaults.has(email),
  }));
}

function isValidUva(email) {
  return /@virginia\.edu$/i.test(email);
}

export function addExtraTaEmail(rawEmail) {
  const email = normalizeEmail(rawEmail);
  if (!isValidUva(email)) {
    return { ok: false, message: "Email must end with @virginia.edu." };
  }
  if (getProfessorEmailSet().has(email)) {
    return { ok: false, message: "That email is already listed as a professor." };
  }
  if (getTaEmailSet().has(email)) {
    return { ok: false, message: "That email is already a TA." };
  }
  const next = [...readExtraList(LS_EXTRA_TA), email];
  writeExtraList(LS_EXTRA_TA, next);
  return { ok: true };
}

export function removeExtraTaEmail(rawEmail) {
  const email = normalizeEmail(rawEmail);
  if (DEFAULT_TA_EMAILS.map(normalizeEmail).includes(email)) {
    return { ok: false, message: "Cannot remove a built-in TA email." };
  }
  const next = readExtraList(LS_EXTRA_TA).filter((e) => e !== email);
  writeExtraList(LS_EXTRA_TA, next);
  return { ok: true };
}

export function addExtraProfessorEmail(rawEmail) {
  const email = normalizeEmail(rawEmail);
  if (!isValidUva(email)) {
    return { ok: false, message: "Email must end with @virginia.edu." };
  }
  if (getTaEmailSet().has(email)) {
    return { ok: false, message: "That email is already listed as a TA." };
  }
  if (getProfessorEmailSet().has(email)) {
    return { ok: false, message: "That email is already a professor." };
  }
  const next = [...readExtraList(LS_EXTRA_PROFESSOR), email];
  writeExtraList(LS_EXTRA_PROFESSOR, next);
  return { ok: true };
}

export function removeExtraProfessorEmail(rawEmail) {
  const email = normalizeEmail(rawEmail);
  if (DEFAULT_PROFESSOR_EMAILS.map(normalizeEmail).includes(email)) {
    return { ok: false, message: "Cannot remove a built-in professor email." };
  }
  const next = readExtraList(LS_EXTRA_PROFESSOR).filter((e) => e !== email);
  writeExtraList(LS_EXTRA_PROFESSOR, next);
  return { ok: true };
}

export function notifyRoleRegistryUpdated() {
  refreshAuthProfile();
}

function roleFromEmail(email) {
  const e = normalizeEmail(email);
  if (!e) return "student";
  if (getProfessorEmailSet().has(e)) return "professor";
  if (getTaEmailSet().has(e)) return "ta";
  if (/@virginia\.edu$/i.test(e)) return "student";
  return "student";
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
    const email = normalizeEmail(p.email);
    return {
      ...p,
      email,
      role: roleFromEmail(email),
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

  if (!storageListenerAttached && typeof window !== "undefined") {
    storageListenerAttached = true;
    window.addEventListener("storage", (e) => {
      if (e.key === LS_EXTRA_TA || e.key === LS_EXTRA_PROFESSOR) {
        refreshAuthProfile();
      }
    });
  }
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

function badgeInitials(profile) {
  const p = profile;
  const fn = p?.firstName?.trim();
  const ln = p?.lastName?.trim();
  if (fn && ln) {
    return `${fn[0]}${ln[0]}`.toUpperCase();
  }
  const local = String(p?.email || "")
    .split("@")[0]
    .replace(/[^a-z0-9]/gi, "");
  if (local.length >= 2) return local.slice(0, 2).toUpperCase();
  if (local.length === 1) return `${local[0]}${local[0]}`.toUpperCase();
  return "?";
}

export function useAuthProfile() {
  const initials = computed(() => {
    const p = authProfile.value;
    if (!p) return "";
    return badgeInitials(p);
  });

  const isTa = computed(() => authProfile.value?.role === "ta");
  const isProfessor = computed(() => authProfile.value?.role === "professor");
  const isStudent = computed(() => authProfile.value?.role === "student");
  /** TA or professor: shared elevated UI (except instructor dashboard). */
  const isStaff = computed(() => isTa.value || isProfessor.value);
  /** @deprecated use isStaff — kept for any stray imports */
  const isTaProfessor = computed(() => isStaff.value);

  return {
    authProfile,
    authReady,
    authError,
    initials,
    isTa,
    isProfessor,
    isStudent,
    isStaff,
    isTaProfessor,
  };
}
