import "./bootstrap";
import { createApp } from "vue";
import { createRouter, createWebHistory } from "vue-router";
import App from "./App.vue";
import HomePage from "./pages/HomePage.vue";
import OfficeHoursPage from "./pages/OfficeHoursPage.vue";
import AppointmentsPage from "./pages/AppointmentsPage.vue";
import TaBiosPage from "./pages/TaBiosPage.vue";
import AccountPage from "./pages/AccountPage.vue";
import InstructorDashboard from "./pages/InstructorDashboard.vue";
import AnnouncementsPage from "./pages/AnnouncementsPage.vue";
import SignupPage from "./pages/SignupPage.vue";
import ProfilePage from "./pages/ProfilePage.vue";
import {
  getStoredAuthProfile,
  initializeAuth,
} from "./composables/useAuthProfile";

// Apply a global CSS rule to make all buttons use the pointer cursor
const style = document.createElement("style");
style.textContent = `
    button:not(:disabled) {
        cursor: pointer;
    }
`;
document.head.appendChild(style);

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: "/signup", component: SignupPage },
    { path: "/", component: HomePage },
    { path: "/office-hours", component: OfficeHoursPage },
    { path: "/appointments", component: AppointmentsPage },
    { path: "/ta-bios", component: TaBiosPage },
    { path: "/account", component: AccountPage },
    { path: "/profile", component: ProfilePage },
    { path: "/instructor-dashboard", component: InstructorDashboard },
    { path: "/announcements", component: AnnouncementsPage },
  ],
});

router.beforeEach(async (to) => {
  await initializeAuth();
  const profile = await getStoredAuthProfile();
  const isVerified = Boolean(profile?.verified);

  if (!isVerified && to.path !== "/signup") return "/signup";
  if (isVerified && to.path === "/signup") return "/";

  if (to.path === "/instructor-dashboard") {
    if (profile?.role !== "professor") return "/";
  }
  return true;
});

await i;
