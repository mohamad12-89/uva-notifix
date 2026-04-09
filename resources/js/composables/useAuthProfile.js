import { computed, ref } from "vue";
import { supabase } from "../lib/supabase";

const authProfile = ref(null);
const authReady = ref(false);
const authError = ref("");
let initialized = false;
let authSubscription = null;

function normalizeEmail(email) {
  return String(email || "")
    .trim()
    .toLowerCase();
}

function namesFromUser(user) {
  const meta = user?.user_metadata || {};
  return {
    firstName: meta.first_name || meta.firstName || "",
    lastName: meta.last_name || meta.lastName || "",
  };
}

export async function fetchRoleByEmail(email) {
  const e = normalizeEmail(email);
  if (!e) return "student";

  const { data, error } = await supabase
    .from("roles")
    .select("role")
    .eq("email", e)
    .maybeSingle();

  // PGRST116 = no rows returned; default to student.
  if (error && error.code !== "PGRST116") {
    console.error("Failed to fetch role:", error);
  }

  return data?.role === "ta_professor" ? "ta_professor" : "student";
}

export async function refreshAuthProfile() {
  const {
    data: { session },
    error,
  } = await supabase.auth.getSession();

  if (error) {
    authError.value = error.message;
    authProfile.value = null;
    return null;
  }

  if (!session?.user) {
    authProfile.value = null;
    authError.value = "";
    return null;
  }

  const user = session.user;
  const { firstName, lastName } = namesFromUser(user);
  const role = await fetchRoleByEmail(user.email);

  authProfile.value = {
    id: user.id,
    email: normalizeEmail(user.email),
    firstName,
    lastName,
    role,
    verified: Boolean(user.email_confirmed_at),
    verifiedAt: user.email_confirmed_at || null,
  };
  authError.value = "";
  return authProfile.value;
}

export async function initializeAuth() {
  if (initialized) return;
  initialized = true;

  await refreshAuthProfile();
  authReady.value = true;

  const { data } = supabase.auth.onAuthStateChange(async () => {
    await refreshAuthProfile();
    authReady.value = true;
  });
  authSubscription = data.subscription;
}

export function disposeAuth() {
  if (authSubscription) {
    authSubscription.unsubscribe();
    authSubscription = null;
  }
  initialized = false;
}

/** For router guards / non-reactive checks */
export async function getStoredAuthProfile() {
  if (!initialized) {
    await initializeAuth();
  }
  return authProfile.value;
}

export async function isUserVerified() {
  const p = await getStoredAuthProfile();
  return Boolean(p?.verified);
}

export async function signOutAuth() {
  await supabase.auth.signOut();
  authProfile.value = null;
}

export function useAuthProfile() {
  const initials = computed(() => {
    const p = authProfile.value;
    if (!p?.firstName || !p?.lastName) return "";
    return `${p.firstName[0]}${p.lastName[0]}`.toUpperCase();
  });

  const isTaProfessor = computed(
    () => authProfile.value?.role === "ta_professor",
  );
  const isStudent = computed(
    () => Boolean(authProfile.value) && authProfile.value?.role !== "ta_professor",
  );

  return {
    authProfile,
    authReady,
    authError,
    initials,
    isTaProfessor,
    isStudent,
  };
}
