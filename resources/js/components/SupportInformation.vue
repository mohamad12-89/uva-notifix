<template>
  <div :class="wrapperClass">
    <p>
      If you need help, please contact the administrator at
      <a
        class="text-uva-orange underline decoration-uva-orange/50 underline-offset-2 hover:text-white"
        :href="`mailto:${adminEmail}`"
        >{{ adminEmail }}</a
      >.
    </p>
    <div>
      <p :class="subtleClass">For inquiries, you can reach out to:</p>
      <ol :class="listClass">
        <li v-for="(email, idx) in inquiryEmails" :key="idx">
          <a
            class="text-uva-orange underline decoration-uva-orange/50 underline-offset-2 hover:text-white"
            :href="`mailto:${email}`"
            >{{ email }}</a
          >
        </li>
      </ol>
    </div>
    <div class="mt-4">
      <p :class="resourcesLabelClass">Additional Resources:</p>
      <p :class="resourceLineClass">
        Student Disability Access Center (<a
          class="text-uva-orange underline decoration-uva-orange/50 underline-offset-2 hover:text-white"
          :href="sdacUrl"
          target="_blank"
          rel="noopener noreferrer"
          >SDAC</a
        >)
      </p>
      <p :class="resourceLineClass">
        Counseling and Psychological Services (<a
          class="text-uva-orange underline decoration-uva-orange/50 underline-offset-2 hover:text-white"
          :href="capsUrl"
          target="_blank"
          rel="noopener noreferrer"
          >CAPS</a
        >)
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  compact: {
    type: Boolean,
    default: false,
  },
});

const adminEmail = "amm9km@virginia.edu";
const inquiryEmails = [
  "Shivaths Karne: uhu5nr@virginia.edu",
  "Muhammad Hussain: cdd9sb@virginia.edu",
  "Dominic Quinn: khg5bj@virginia.edu",
  "Andrew Lohman: xfw9vp@virginia.edu",
];
const sdacUrl = "https://disabilityservices.studenthealth.virginia.edu/";
const capsUrl = "https://mentalhealthservices.studenthealth.virginia.edu/";

const wrapperClass = computed(() =>
  props.compact
    ? "space-y-4 text-left text-xs leading-relaxed text-slate-100"
    : "space-y-4 text-left text-sm leading-relaxed text-slate-200",
);

const subtleClass = computed(() =>
  props.compact ? "text-slate-300" : "text-slate-300",
);

const listClass = computed(() =>
  props.compact
    ? "mt-2 list-decimal space-y-1.5 pl-5 text-slate-100"
    : "mt-2 list-decimal space-y-2 pl-5 text-slate-100",
);

const resourcesLabelClass = computed(() =>
  props.compact
    ? "font-semibold text-slate-200"
    : "font-semibold text-slate-300",
);

const resourceLineClass = computed(() =>
  props.compact ? "mt-2 text-slate-100" : "mt-2",
);
</script>
