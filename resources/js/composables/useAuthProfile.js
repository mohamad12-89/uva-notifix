import { computed, ref } from "vue";

const AUTH_STORAGE_KEY = "notifixAuthProfile";
const AUTH_EVENT = "notifix-auth-updated";

const authProfile = ref(readProfile());

function readProfile() {
  try {
    return JSON.parse(localStorage.getItem(AUTH_STORAGE_KEY) || "null");
  } catch {
    return null;
  }
}

function writeProfile(profile) {
  authProfile.value = profile;
  localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(profile));
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

  return {
    authProfile,
    initials,
    setAuthProfile: writeProfile,
    clearAuthProfile: clearProfile,
  };
}

export function isUserVerified() {
  const p = readProfile();
  return Boolean(p?.verified);
}
