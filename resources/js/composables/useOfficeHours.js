import { ref } from "vue";
import { api } from "../lib/api";

/** Single source of truth so deletes/search/home/office views stay in sync */
export const officeHours = ref([]);
export const joinedSessions = ref(
  JSON.parse(localStorage.getItem("joinedSessions") || "[]"),
);

function persistJoinedSessions() {
  localStorage.setItem(
    "joinedSessions",
    JSON.stringify(joinedSessions.value),
  );
}

/** Drop join flags for sessions that no longer exist */
function pruneJoinedForExistingHours(hours) {
  const idSet = new Set(
    hours.flatMap((h) => {
      const id = h.id;
      const n = Number(id);
      return Number.isFinite(n) ? [id, n, String(id)] : [id, String(id)];
    }),
  );
  joinedSessions.value = joinedSessions.value.filter((jid) =>
    idSet.has(jid) || idSet.has(Number(jid)),
  );
  persistJoinedSessions();
}

/**
 * Fresh fetch from API (cache-busted) and prune local join state.
 */
export async function fetchOfficeHours() {
  const { data } = await api.get("/office-hours", {
    params: { _t: Date.now() },
  });
  officeHours.value = data;
  pruneJoinedForExistingHours(data);
  return data;
}

/** Immediate UI update before refetch (and if refetch fails) */
export function removeOfficeHourFromStore(id) {
  officeHours.value = officeHours.value.filter((s) => s.id !== id);
  const n = Number(id);
  joinedSessions.value = joinedSessions.value.filter(
    (jid) => jid !== id && Number(jid) !== n,
  );
  persistJoinedSessions();
}

export function pushJoinedSession(id) {
  if (!joinedSessions.value.includes(id)) {
    joinedSessions.value.push(id);
    persistJoinedSessions();
  }
}

export function removeJoinedSession(id) {
  const n = Number(id);
  joinedSessions.value = joinedSessions.value.filter(
    (jid) => jid !== id && Number(jid) !== n,
  );
  persistJoinedSessions();
}
