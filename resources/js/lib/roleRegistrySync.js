import { api } from "./api";
import {
  authProfile,
  getTaEmailSet,
  getProfessorEmailSet,
} from "../composables/useAuthProfile";

/**
 * When a professor loads the app, push TA/professor lists to Laravel so API role checks
 * match the Instructor Dashboard (localStorage) and office-hour routes are not 403.
 */
export async function syncProfessorRoleRegistryToApi() {
  if (authProfile.value?.role !== "professor") {
    return;
  }
  try {
    await api.post("/instructor/sync-role-registry", {
      ta: [...getTaEmailSet()],
      professor: [...getProfessorEmailSet()],
    });
  } catch (e) {
    console.warn(
      "Role registry sync failed (professor should ensure storage/app is writable on the server):",
      e,
    );
  }
}
