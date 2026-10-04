// =========================
// SUBMISSION WIZARD
// One page, five steps switched with JavaScript (markup: resources/views/submit/wizard.blade.php).
// The whole form is posted at once; the server validates again.
// =========================
document.addEventListener("DOMContentLoaded", () => {
  const root = document.querySelector("[data-wizard]");
  if (!root) return;

  const form = root.querySelector("#wizard-form");
  const steps = [...root.querySelectorAll("[data-wizard-step]")];
  const items = [...root.querySelectorAll("[data-wizard-item]")];
  const prevBtn = root.querySelector("[data-wizard-prev]");
  const nextBtn = root.querySelector("[data-wizard-next]");
  const sendBtn = root.querySelector("[data-wizard-send]");
  const total = steps.length;

  let current = 1;
  let reached = 1;
  let submitting = false;
  let dirty = false;

  // ---------- helpers ----------
  const stepEl = (n) => steps[n - 1];
  const stepError = (n) => stepEl(n).querySelector("[data-step-error]");
  const field = (name) => form.elements[name];

  const showError = (n, message) => {
    const box = stepError(n);
    if (box) box.textContent = message || "";
  };

  const firstInvalid = (container) =>
    [...container.querySelectorAll("input, select, textarea")].find(
      (el) => el.type !== "file" && el.type !== "hidden" && !el.checkValidity(),
    );

  // ---------- per-step validation ----------
  const validators = {
    1() {
      showError(1, "");
      const language = field("language");
      if (!language.value) {
        language.focus();
        return false;
      }
      const boxes = [...form.querySelectorAll('input[name="checklist[]"]')];
      if (!boxes.every((box) => box.checked)) {
        showError(1, root.dataset.msgChecklist);
        boxes.find((box) => !box.checked).focus();
        return false;
      }
      if (!field("copyright").checked) {
        showError(1, root.dataset.msgCopyright);
        field("copyright").focus();
        return false;
      }
      return true;
    },
    2() {
      showError(2, "");
      const file = form.querySelector('input[type="file"]');
      const hasNewFile = file.files && file.files.length > 0;
      if (!hasNewFile && root.dataset.hasFile !== "1") {
        showError(2, root.dataset.msgFile);
        return false;
      }
      // The drop zone clears the input itself when a file is rejected, so a file here is valid.
      return true;
    },
    3() {
      showError(3, "");
      const bad = firstInvalid(stepEl(3));
      if (bad) {
        bad.reportValidity();
        return false;
      }
      // minlength is only enforced by browsers after the user types, so check it explicitly.
      const abstract = field("abstract");
      if (abstract.value.trim().length < 50) {
        showError(3, root.dataset.msgAbstract);
        abstract.focus();
        return false;
      }
      return true;
    },
    4() {
      showError(4, "");
      const rows = [...form.querySelectorAll("[data-author-row]")];
      const named = rows.filter((row) => row.querySelector('input[name$="[name]"]').value.trim() !== "");
      if (named.length === 0) {
        showError(4, root.dataset.msgAuthors);
        rows[0].querySelector('input[name$="[name]"]').focus();
        return false;
      }
      const bad = firstInvalid(stepEl(4));
      if (bad) {
        bad.reportValidity();
        return false;
      }
      return true;
    },
    5() {
      return true;
    },
  };

  // ---------- summary (step 5) ----------
  const text = (value) => (value && value.trim() ? value.trim() : "—");

  const fillSummary = () => {
    const set = (key, value) => {
      const el = root.querySelector(`[data-summary="${key}"]`);
      if (el) el.textContent = value;
    };

    const language = field("language");
    set("language", language.options[language.selectedIndex].text);
    set("title", text(field("title").value));
    set("abstract", text(field("abstract").value));
    set("keywords", text(field("keywords").value));

    const file = form.querySelector('input[type="file"]');
    const existing = root.querySelector('[data-wizard-step="2"] .profile-notification-group strong');
    set("file", file.files.length ? file.files[0].name : text(existing ? existing.textContent.split(": ").slice(1).join(": ") : ""));

    const authors = [...form.querySelectorAll("[data-author-row]")]
      .filter((row) => row.querySelector('input[name$="[name]"]').value.trim() !== "")
      .map((row) => {
        const name = row.querySelector('input[name$="[name]"]').value.trim();
        const institution = row.querySelector('input[name$="[institution]"]').value.trim();
        const main = row.querySelector('input[type="radio"]').checked ? " ★" : "";
        return name + (institution ? ` (${institution})` : "") + main;
      });
    set("authors", authors.length ? authors.join("; ") : "—");
  };

  // ---------- navigation ----------
  const render = () => {
    steps.forEach((el, i) => {
      el.hidden = i + 1 !== current;
    });

    items.forEach((li, i) => {
      const n = i + 1;
      li.classList.toggle("is-current", n === current);
      li.classList.toggle("is-done", n < current);
      li.querySelector("button").disabled = n > reached;
      if (n === current) li.querySelector("button").setAttribute("aria-current", "step");
      else li.querySelector("button").removeAttribute("aria-current");
    });

    prevBtn.hidden = current === 1;
    nextBtn.hidden = current === total;
    sendBtn.hidden = current !== total;

    if (current === total) fillSummary();
  };

  const go = (n, { push = true, focus = true } = {}) => {
    current = Math.min(Math.max(n, 1), total);
    reached = Math.max(reached, current);
    render();

    const hash = `#step-${current}`;
    if (push && location.hash !== hash) history.pushState({ step: current }, "", hash);

    if (focus) {
      // A user-triggered step change always returns to the top of the page.
      window.scrollTo({ top: 0, behavior: "smooth" });
      const target = stepEl(current).querySelector("h2");
      if (target) {
        target.setAttribute("tabindex", "-1");
        target.focus({ preventScroll: true });
      }
    }
  };

  const next = () => {
    if (validators[current]()) go(current + 1);
  };

  // Validates every earlier step; jumps to the first one that fails.
  const validateUpTo = (limit) => {
    for (let n = 1; n <= limit; n += 1) {
      if (!validators[n]()) {
        if (n !== current) go(n, { focus: false });
        // Re-run on the now visible step so the browser can focus and report the field.
        validators[n]();
        return false;
      }
    }
    return true;
  };

  // ---------- events ----------
  nextBtn.addEventListener("click", next);
  prevBtn.addEventListener("click", () => go(current - 1));

  items.forEach((li, i) => {
    li.querySelector("button").addEventListener("click", () => {
      const target = i + 1;
      if (target <= current || validateUpTo(target - 1)) go(target);
    });
  });

  window.addEventListener("popstate", () => {
    const match = /^#step-(\d)$/.exec(location.hash);
    go(match ? Number(match[1]) : 1, { push: false, focus: false });
  });

  form.addEventListener("submit", (event) => {
    const mode = event.submitter ? event.submitter.value : "submit";

    if (mode === "draft") {
      // A draft only needs a language; nothing else has to be complete yet.
      submitting = true;
      return;
    }

    // Pressing Enter inside a field means "next" until the last step.
    if (current < total) {
      event.preventDefault();
      next();
      return;
    }

    if (!validateUpTo(total - 1)) {
      event.preventDefault();
      return;
    }
    submitting = true;
  });

  form.addEventListener("input", () => {
    dirty = true;
  });
  form.addEventListener("change", () => {
    dirty = true;
  });

  window.addEventListener("beforeunload", (event) => {
    if (dirty && !submitting) {
      event.preventDefault();
      event.returnValue = "";
    }
  });

  // ---------- abstract counter ----------
  const abstract = field("abstract");
  const counter = root.querySelector("[data-abstract-counter]");
  const updateCounter = () => {
    if (counter) counter.textContent = `${abstract.value.length}/5000 (min 50)`;
  };
  abstract.addEventListener("input", updateCounter);
  updateCounter();

  // ---------- authors: add another blank row ----------
  document.getElementById("add-author").addEventListener("click", () => {
    const rows = form.querySelectorAll("[data-author-row]");
    const index = rows.length;
    const clone = rows[rows.length - 1].cloneNode(true);
    clone.querySelectorAll("input").forEach((input) => {
      if (input.type === "radio") {
        input.value = index;
        input.checked = false;
      } else {
        input.name = input.name.replace(/authors\[\d+\]/, `authors[${index}]`);
        input.value = "";
      }
    });
    document.getElementById("author-rows").appendChild(clone);
    clone.querySelector('input[name$="[name]"]').focus();
  });

  // ---------- initial state ----------
  const initial = Number(root.dataset.initialStep || 1);
  const fromHash = /^#step-(\d)$/.exec(location.hash);
  // Server-side errors win; otherwise honour a deep link, but only up to the step already completed.
  const start = initial > 1 ? initial : fromHash ? Number(fromHash[1]) : 1;
  reached = start;
  go(start, { push: false, focus: false });
  dirty = false;
});
