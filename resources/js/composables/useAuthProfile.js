import { computed, ref } from "vue";
import {
  CognitoIdentityProviderClient,
  SignUpCommand,
  ConfirmSignUpCommand,
  InitiateAuthCommand,
  GetUserCommand,
  ResendConfirmationCodeCommand,
  ChangePasswordCommand,
  UpdateUserAttributesCommand,
} from "@aws-sdk/client-cognito-identity-provider";

const AUTH_KEY = "notifix_auth_profile";
const TOKENS_KEY = "notifix_auth_tokens";
const LS_EXTRA_TA = "notifix_extra_ta_emails";
const LS_EXTRA_PROFESSOR = "notifix_extra_professor_emails";

/** Built-in TA accounts (cannot be removed from dashboard). */
export const DEFAULT_TA_EMAILS = [
  "xfw9vp@virginia.edu",
  "uhu5nr@virginia.edu",
  "khg5bj@virginia.edu",
];

/** Built-in professor accounts (cannot be removed from dashboard). */
export const DEFAULT_PROFESSOR_EMAILS = [
  "cdd9sb@virginia.edu",
  "amm8km@virginia.edu",
];

export const authProfile = ref(null);
const authReady = ref(false);
const authError = ref("");
let initialized = false;
let storageListenerAttached = false;

let client = null;

function env(name, fallback = "") {
  return import.meta.env[name] || fallback;
}

const cognitoConfig = {
  region: env("VITE_AWS_REGION", "us-east-1"),
  userPoolId: env("VITE_COGNITO_USER_POOL_ID", ""),
  clientId: env("VITE_COGNITO_APP_CLIENT_ID", ""),
};

function getClient() {
  if (!client) {
    client = new CognitoIdentityProviderClient({ region: cognitoConfig.region });
  }
  return client;
}

function normalizeEmail(email) {
  return String(email || "").trim().toLowerCase();
}

function parseJwt(token) {
  try {
    const payload = token.split(".")[1];
    const base64 = payload.replace(/-/g, "+").replace(/_/g, "/");
    const padded = base64 + "=".repeat((4 - (base64.length % 4)) % 4);
    return JSON.parse(atob(padded));
  } catch {
    return null;
  }
}

function readTokens() {
  try {
    const raw = sessionStorage.getItem(TOKENS_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (!parsed?.idToken || !parsed?.accessToken) return null;
    return parsed;
  } catch {
    return null;
  }
}

function writeTokens(tokens) {
  if (!tokens) {
    sessionStorage.removeItem(TOKENS_KEY);
    return;
  }
  sessionStorage.setItem(TOKENS_KEY, JSON.stringify(tokens));
}

export function getAccessToken() {
  return readTokens()?.accessToken || "";
}

/** ID token (preferred for Laravel API auth — includes email and standard claims). */
export function getIdToken() {
  return readTokens()?.idToken || "";
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

function roleFromGroupsOrEmail(groups, email) {
  const normalizedGroups = Array.isArray(groups)
    ? groups.map((group) => String(group).trim().toLowerCase())
    : [];
  if (normalizedGroups.includes("professor")) return "professor";
  if (normalizedGroups.includes("ta")) return "ta";
  if (normalizedGroups.includes("student")) return "student";
  return roleFromEmail(email);
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
  const sessionProfile = hydrateProfile();
  const tokens = readTokens();
  authError.value = "";

  if (!tokens) {
    authProfile.value = sessionProfile;
    return authProfile.value;
  }

  const claims = parseJwt(tokens.idToken);
  const exp = claims?.exp ? Number(claims.exp) * 1000 : 0;
  if (!claims || (exp && exp <= Date.now())) {
    await signOutAuth();
    authProfile.value = null;
    authError.value = "Session expired. Please sign in again.";
    return null;
  }

  const email = normalizeEmail(claims.email || sessionProfile?.email);
  const groups = claims["cognito:groups"] || [];
  authProfile.value = {
    id: claims.sub || sessionProfile?.id || email,
    sub: claims.sub || "",
    email,
    firstName: sessionProfile?.firstName || "",
    lastName: sessionProfile?.lastName || "",
    role: roleFromGroupsOrEmail(groups, email),
    groups,
    verified: Boolean(claims.email_verified),
    verifiedAt: sessionProfile?.verifiedAt || new Date().toISOString(),
  };
  persistProfile(authProfile.value);
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

export async function signUpWithEmailPassword({
  firstName = "",
  lastName = "",
  email,
  password,
}) {
  const normalizedEmail = normalizeEmail(email);
  const command = new SignUpCommand({
    ClientId: cognitoConfig.clientId,
    Username: normalizedEmail,
    Password: password,
    UserAttributes: [
      { Name: "email", Value: normalizedEmail },
      { Name: "given_name", Value: firstName.trim() || normalizedEmail.split("@")[0] },
      { Name: "family_name", Value: lastName.trim() || "User" },
    ],
  });
  const response = await getClient().send(command);

  const draftProfile = {
    id: normalizedEmail,
    email: normalizedEmail,
    firstName: firstName.trim(),
    lastName: lastName.trim(),
    role: roleFromEmail(normalizedEmail),
    verified: false,
  };
  persistProfile(draftProfile);
  authProfile.value = draftProfile;
  return response;
}

export async function confirmSignUpCode(email, code) {
  const command = new ConfirmSignUpCommand({
    ClientId: cognitoConfig.clientId,
    Username: normalizeEmail(email),
    ConfirmationCode: String(code || "").trim(),
  });
  return getClient().send(command);
}

export async function resendSignUpCode(email) {
  const command = new ResendConfirmationCodeCommand({
    ClientId: cognitoConfig.clientId,
    Username: normalizeEmail(email),
  });
  return getClient().send(command);
}

export async function signInWithEmailPassword({ email, password }) {
  const normalizedEmail = normalizeEmail(email);
  const command = new InitiateAuthCommand({
    AuthFlow: "USER_PASSWORD_AUTH",
    ClientId: cognitoConfig.clientId,
    AuthParameters: {
      USERNAME: normalizedEmail,
      PASSWORD: String(password || ""),
    },
  });

  const result = await getClient().send(command);
  const auth = result.AuthenticationResult;
  if (!auth?.IdToken || !auth?.AccessToken) {
    const challenge = result?.ChallengeName || "UNKNOWN_CHALLENGE";
    if (challenge === "NEW_PASSWORD_REQUIRED") {
      throw new Error(
        "This account requires a new password before sign-in. Reset or set a permanent password in Cognito first.",
      );
    }
    if (challenge === "USER_NOT_CONFIRMED") {
      throw new Error("Email is not confirmed yet. Complete verification first.");
    }
    throw new Error(
      `Authentication did not return tokens (challenge: ${challenge}).`,
    );
  }

  writeTokens({
    idToken: auth.IdToken,
    accessToken: auth.AccessToken,
    refreshToken: auth.RefreshToken || "",
  });

  const claims = parseJwt(auth.IdToken) || {};
  const userResponse = await getClient().send(
    new GetUserCommand({ AccessToken: auth.AccessToken }),
  );
  const attrs = Object.fromEntries(
    (userResponse.UserAttributes || []).map((a) => [a.Name, a.Value]),
  );
  const groups = claims["cognito:groups"] || [];

  const profile = {
    id: claims.sub || normalizedEmail,
    sub: claims.sub || "",
    email: normalizeEmail(attrs.email || normalizedEmail),
    firstName: attrs.given_name || "",
    lastName: attrs.family_name || "",
    role: roleFromGroupsOrEmail(groups, normalizedEmail),
    groups,
    verified: Boolean(claims.email_verified),
    verifiedAt: new Date().toISOString(),
  };
  authProfile.value = profile;
  persistProfile(profile);
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
  const accessToken = getAccessToken();
  if (accessToken) {
    await getClient().send(
      new UpdateUserAttributesCommand({
        AccessToken: accessToken,
        UserAttributes: [
          { Name: "given_name", Value: next.firstName || "" },
          { Name: "family_name", Value: next.lastName || "" },
        ],
      }),
    );
  }
  return next;
}

export async function changePasswordAuth({ currentPassword, newPassword }) {
  const accessToken = getAccessToken();
  if (!accessToken) {
    throw new Error("No active session.");
  }
  await getClient().send(
    new ChangePasswordCommand({
      AccessToken: accessToken,
      PreviousPassword: String(currentPassword || ""),
      ProposedPassword: String(newPassword || ""),
    }),
  );
}

export async function signOutAuth() {
  authProfile.value = null;
  sessionStorage.removeItem(AUTH_KEY);
  sessionStorage.removeItem(TOKENS_KEY);
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
