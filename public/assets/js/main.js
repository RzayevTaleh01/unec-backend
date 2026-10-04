// =========================
// SITE-WIDE MOBILE MENU
// =========================
const mobileMenuBtn = document.getElementById("mobileMenuBtn");

function closeMobileMenu() {
  document.querySelector(".nav-row")?.classList.remove("mobile-menu-open");
}

mobileMenuBtn?.addEventListener("click", function (e) {
  e.stopPropagation();
  document.querySelector(".nav-row")?.classList.toggle("mobile-menu-open");
});

// Close mobile menu when clicking outside
document.addEventListener("click", function (e) {
  const navRow = document.querySelector(".nav-row");
  if (
    navRow &&
    mobileMenuBtn &&
    !navRow.contains(e.target) &&
    !mobileMenuBtn.contains(e.target)
  ) {
    navRow.classList.remove("mobile-menu-open");
  }
});

document.addEventListener("DOMContentLoaded", function () {
  document
    .getElementById("mobileCloseBtn")
    ?.addEventListener("click", function (e) {
      e.stopPropagation();
      closeMobileMenu();
    });

  document
    .querySelectorAll(
      ".nav-row > .container > .nav-link-custom, #aboutDropdown .about-dropdown-item a",
    )
    .forEach((link) => link.addEventListener("click", closeMobileMenu));
});

// =========================
// LANGUAGE AND ABOUT DROPDOWNS
// (the language itself is switched server-side via /lang/{locale})
// =========================
function toggleDropdown() {
  document.getElementById("langDropdown")?.classList.toggle("active");
}

function toggleAboutDropdown() {
  document.getElementById("aboutDropdown")?.classList.toggle("active");
}

window.addEventListener("click", function (e) {
  ["langDropdown", "aboutDropdown"].forEach((id) => {
    const dropdown = document.getElementById(id);
    if (dropdown && !dropdown.contains(e.target)) {
      dropdown.classList.remove("active");
    }
  });
});

document.addEventListener("DOMContentLoaded", () => {
  // =========================
  // PROFILE SIDEBAR
  // =========================
  const profileSidebar = document.querySelector("[data-profile-sidebar]");
  const profileMenuToggle = document.querySelector(
    "[data-profile-menu-toggle]",
  );
  const setProfileSidebarState = (isOpen) => {
    if (!profileSidebar) return;
    profileSidebar.classList.toggle("is-open", isOpen);
    profileSidebar.classList.toggle("is-closed", !isOpen);
    document.body.classList.toggle("sidebar-collapsed", !isOpen);
    profileMenuToggle?.setAttribute("aria-expanded", String(isOpen));
  };

  if (profileSidebar && profileMenuToggle) {
    setProfileSidebarState(!window.matchMedia("(max-width: 767.98px)").matches);
    profileMenuToggle.addEventListener("click", () => {
      setProfileSidebarState(!profileSidebar.classList.contains("is-open"));
    });
  }

  // =========================
  // PROFILE RICH-TEXT EDITORS
  // The contenteditable body is copied into a hidden input on submit.
  // =========================
  document.querySelectorAll("[data-editor-command]").forEach((button) => {
    button.addEventListener("click", () => {
      const editor = button
        .closest(".profile-editor")
        ?.querySelector(".profile-editor-body");
      if (!editor) return;
      editor.focus();
      const command = button.dataset.editorCommand;
      if (command === "code") {
        document.execCommand("formatBlock", false, "pre");
      } else if (command === "createLink") {
        const url = window.prompt("URL:");
        if (url && /^https?:\/\//i.test(url)) {
          document.execCommand("createLink", false, url);
        }
      } else {
        document.execCommand(command, false);
      }
    });
  });

  document.querySelectorAll("[data-editor-form]").forEach((form) => {
    form.addEventListener("submit", () => {
      form.querySelectorAll(".profile-editor").forEach((editor) => {
        const body = editor.querySelector(".profile-editor-body");
        const input = editor.querySelector("[data-editor-input]");
        if (body && input) input.value = body.innerHTML.trim();
      });
    });
  });

  // =========================
  // ARTICLE SEARCH (server-side filtering; this only drives the form UI)
  // =========================
  const searchForm = document.getElementById("searchForm");
  const dateTrigger = document.getElementById("datePickerTrigger");
  const datePopover = document.getElementById("datePickerPopover");
  const dateLabel = document.getElementById("dateRangeLabel");
  const startPreview = document.getElementById("startDatePreview");
  const endPreview = document.getElementById("endDatePreview");
  const monthSelect = document.getElementById("dateMonthSelect");
  const yearSelect = document.getElementById("dateYearSelect");
  const dateGrid = document.getElementById("dateGrid");
  const cancelDateBtn = document.getElementById("cancelDate");
  const applyDateBtn = document.getElementById("applyDate");
  const fromInput = document.getElementById("searchFrom");
  const toInput = document.getElementById("searchTo");

  document.querySelectorAll(".clear-btn").forEach((button) => {
    button.addEventListener("click", (event) => {
      event.preventDefault();
      const target = button.closest(".search-field")?.querySelector("input");
      if (target) {
        target.value = "";
        target.focus();
      }
    });
  });

  if (!searchForm || !dateTrigger || !datePopover) return;

  // Local-date helpers: avoid toISOString(), which shifts days across timezones.
  const toKey = (date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
  const fromKey = (key) => {
    if (!key) return null;
    const [y, m, d] = key.split("-").map(Number);
    return new Date(y, m - 1, d);
  };
  // Month names come from the server (lang files); Intl lacks data for some locales.
  const monthNames = JSON.parse(dateTrigger.dataset.months || "[]");
  const formatDate = (date) =>
    `${date.getDate()} ${(monthNames[date.getMonth()] || "").slice(0, 3)} ${date.getFullYear()}`;

  const defaultLabel = dateLabel.textContent;
  const defaultStart = "—";
  const range = {
    start: fromKey(dateTrigger.dataset.initialStart),
    end: fromKey(dateTrigger.dataset.initialEnd),
    month: new Date(),
  };
  range.month = new Date(
    (range.start || range.month).getFullYear(),
    (range.start || range.month).getMonth(),
    1,
  );

  const syncLabels = () => {
    startPreview.textContent = range.start ? formatDate(range.start) : defaultStart;
    endPreview.textContent = range.end ? formatDate(range.end) : defaultStart;
    dateLabel.textContent =
      range.start && range.end
        ? `${formatDate(range.start)} — ${formatDate(range.end)}`
        : defaultLabel;
    fromInput.value = range.start && range.end ? toKey(range.start) : "";
    toInput.value = range.start && range.end ? toKey(range.end) : "";
  };

  const updateDateSelectors = () => {
    monthSelect.innerHTML = "";
    monthNames.forEach((name, month) => {
      monthSelect.appendChild(new Option(name, month));
    });
    const selectedYear = range.month.getFullYear();
    yearSelect.innerHTML = "";
    for (let year = selectedYear - 10; year <= selectedYear + 10; year += 1) {
      yearSelect.appendChild(new Option(year, year));
    }
    monthSelect.value = String(range.month.getMonth());
    yearSelect.value = String(selectedYear);
  };

  const buildCalendar = () => {
    const month = range.month.getMonth();
    const year = range.month.getFullYear();
    const startWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const prevMonthDays = new Date(year, month, 0).getDate();
    const days = [];

    for (let i = startWeekday - 1; i >= 0; i--) {
      days.push({ muted: true, date: new Date(year, month - 1, prevMonthDays - i) });
    }
    for (let i = 1; i <= daysInMonth; i++) {
      days.push({ muted: false, date: new Date(year, month, i) });
    }
    while (days.length % 7 !== 0) {
      const next = days.length - (daysInMonth + startWeekday) + 1;
      days.push({ muted: true, date: new Date(year, month + 1, next) });
    }

    updateDateSelectors();
    dateGrid.innerHTML = "";

    days.forEach(({ muted, date }) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = `date-picker-day ${muted ? "muted" : ""}`;
      btn.textContent = date.getDate();

      if (range.start && range.end && date >= range.start && date <= range.end) {
        btn.classList.add("in-range");
      }
      if (
        (range.start && date.getTime() === range.start.getTime()) ||
        (range.end && date.getTime() === range.end.getTime())
      ) {
        btn.classList.add("selected");
      }

      btn.addEventListener("click", (event) => {
        event.stopPropagation();
        if (!range.start || (range.start && range.end)) {
          range.start = date;
          range.end = null;
        } else if (date < range.start) {
          range.end = range.start;
          range.start = date;
        } else {
          range.end = date;
        }
        syncLabels();
        buildCalendar();
      });

      dateGrid.appendChild(btn);
    });
  };

  const togglePopover = () => datePopover.classList.toggle("open");

  dateTrigger.addEventListener("click", togglePopover);
  dateTrigger.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      togglePopover();
    }
  });

  document.addEventListener("click", (event) => {
    if (
      !dateTrigger.contains(event.target) &&
      !datePopover.contains(event.target)
    ) {
      datePopover.classList.remove("open");
    }
  });

  document.querySelectorAll(".date-nav").forEach((button) => {
    button.addEventListener("click", () => {
      const direction = button.dataset.nav === "next" ? 1 : -1;
      range.month = new Date(
        range.month.getFullYear(),
        range.month.getMonth() + direction,
        1,
      );
      buildCalendar();
    });
  });

  monthSelect.addEventListener("change", () => {
    range.month = new Date(range.month.getFullYear(), Number(monthSelect.value), 1);
    buildCalendar();
  });

  yearSelect.addEventListener("change", () => {
    range.month = new Date(Number(yearSelect.value), range.month.getMonth(), 1);
    buildCalendar();
  });

  cancelDateBtn.addEventListener("click", () => {
    range.start = null;
    range.end = null;
    syncLabels();
    datePopover.classList.remove("open");
    buildCalendar();
  });

  applyDateBtn.addEventListener("click", () => {
    datePopover.classList.remove("open");
    searchForm.requestSubmit();
  });

  // Empty fields are not sent, keeping the search URL short.
  searchForm.addEventListener("submit", () => {
    searchForm.querySelectorAll("input").forEach((input) => {
      if (input.value === "") input.disabled = true;
    });
  });

  syncLabels();
  buildCalendar();
});

// =========================
// FILE INPUTS (drop zone, validation, preview)
// Markup: resources/views/partials/file-input.blade.php
// =========================
document.addEventListener("DOMContentLoaded", () => {
  const formatSize = (bytes) =>
    bytes >= 1048576
      ? `${(bytes / 1048576).toFixed(2)} MB`
      : `${Math.max(1, Math.round(bytes / 1024))} KB`;

  const extensionOf = (name) => (name.split(".").pop() || "").toLowerCase();

  document.querySelectorAll("[data-file-drop]").forEach((root) => {
    const input = root.querySelector(".file-drop__input");
    const selected = root.querySelector(".file-drop__selected");
    const thumb = root.querySelector("[data-file-thumb]");
    const nameEl = root.querySelector("[data-file-name]");
    const sizeEl = root.querySelector("[data-file-size]");
    const noteEl = root.querySelector("[data-file-note]");
    const previewBox = root.querySelector("[data-file-preview]");
    const previewBtn = root.querySelector("[data-file-toggle-preview]");
    const errorEl = root.querySelector("[data-file-error]");

    const allowed = (root.dataset.accept || "")
      .split(",")
      .map((ext) => ext.trim().replace(".", "").toLowerCase())
      .filter(Boolean);
    const maxBytes = Number(root.dataset.maxMb || 20) * 1048576;
    const isImageField = root.dataset.kind === "image";
    let blobUrl = null;

    const reset = () => {
      if (blobUrl) URL.revokeObjectURL(blobUrl);
      blobUrl = null;
      input.value = "";
      selected.hidden = true;
      previewBox.hidden = true;
      previewBox.replaceChildren();
      thumb.replaceChildren();
      previewBtn.hidden = true;
      noteEl.textContent = "";
      root.classList.remove("has-file");
    };

    const fail = (message) => {
      reset();
      errorEl.textContent = message;
      root.classList.add("has-error");
    };

    const buildPreview = (file, ext) => {
      blobUrl = URL.createObjectURL(file);

      if (isImageField) {
        const img = document.createElement("img");
        img.src = blobUrl;
        img.alt = "";
        thumb.replaceChildren(img);
        return;
      }

      const icon = document.createElement("div");
      icon.innerHTML = `<i class="bi ${ext === "pdf" ? "bi-file-earmark-pdf" : "bi-file-earmark-text"}"></i><small></small>`;
      icon.querySelector("small").textContent = ext;
      thumb.replaceChildren(icon);

      if (ext === "pdf") {
        // Browsers render PDFs natively; other formats cannot be previewed client-side.
        const object = document.createElement("object");
        object.data = blobUrl;
        object.type = "application/pdf";
        object.setAttribute("aria-label", file.name);
        previewBox.replaceChildren(object);
        previewBox.hidden = false;
        previewBtn.hidden = false;
      } else {
        noteEl.textContent = root.dataset.msgPreview || "";
      }
    };

    const accept = (file) => {
      errorEl.textContent = "";
      root.classList.remove("has-error");

      const ext = extensionOf(file.name);
      if (allowed.length && !allowed.includes(ext)) {
        fail(root.dataset.msgType);
        return;
      }
      if (file.size > maxBytes) {
        fail(root.dataset.msgSize);
        return;
      }

      if (blobUrl) URL.revokeObjectURL(blobUrl);
      nameEl.textContent = file.name;
      sizeEl.textContent = formatSize(file.size);
      noteEl.textContent = "";
      previewBox.hidden = true;
      previewBox.replaceChildren();
      buildPreview(file, ext);
      selected.hidden = false;
      root.classList.add("has-file");
    };

    input.addEventListener("change", () => {
      if (input.files && input.files[0]) accept(input.files[0]);
    });

    ["dragenter", "dragover"].forEach((type) =>
      root.addEventListener(type, (event) => {
        event.preventDefault();
        root.classList.add("is-dragover");
      }),
    );

    ["dragleave", "drop"].forEach((type) =>
      root.addEventListener(type, (event) => {
        event.preventDefault();
        if (type === "dragleave" && root.contains(event.relatedTarget)) return;
        root.classList.remove("is-dragover");
      }),
    );

    root.addEventListener("drop", (event) => {
      const file = event.dataTransfer?.files?.[0];
      if (!file) return;
      try {
        // Put the dropped file into the real input so the form submits it.
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
      } catch (e) {
        return;
      }
      accept(file);
    });

    root.querySelector("[data-file-clear]").addEventListener("click", () => {
      reset();
      errorEl.textContent = "";
      root.classList.remove("has-error");
      input.focus();
    });

    previewBtn.addEventListener("click", () => {
      previewBox.hidden = !previewBox.hidden;
    });
  });
});

// =========================
// SUBMIT BUTTON LOADING STATE
// Any form that is really being submitted shows a spinner on its submit button and
// ignores further clicks. The button is not disabled: a disabled submitter would drop
// its name/value (the wizard sends mode=draft|submit that way).
// =========================
document.addEventListener("DOMContentLoaded", () => {
  const clearLoading = () => {
    document.querySelectorAll("form[data-submitting]").forEach((form) => {
      delete form.dataset.submitting;
    });
    document.querySelectorAll(".is-loading").forEach((button) => {
      button.classList.remove("is-loading");
      button.removeAttribute("aria-busy");
      if (button.dataset.originalLabel) {
        button.textContent = button.dataset.originalLabel;
        delete button.dataset.originalLabel;
      }
    });
  };

  document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    if (form.dataset.submitting) {
      // Second click/Enter while the first request is in flight.
      event.preventDefault();
      return;
    }

    // Other handlers (wizard validation, confirm dialogs) run first and may cancel the submit.
    setTimeout(() => {
      if (event.defaultPrevented) return;

      const button =
        event.submitter || form.querySelector('button[type="submit"], button:not([type])');
      form.dataset.submitting = "1";
      if (!button) return;

      button.classList.add("is-loading");
      button.setAttribute("aria-busy", "true");
      if (button.dataset.loadingText) {
        button.dataset.originalLabel = button.textContent;
        button.textContent = button.dataset.loadingText;
      }
    }, 0);
  });

  // Back/forward cache restores the page as it was left: undo any spinner.
  window.addEventListener("pageshow", (event) => {
    if (event.persisted) clearLoading();
  });
});

// =========================
// OTHER-JOURNALS MODAL (profile > roles)
// Opens from the button, from the #journals hash (old link) or when the server re-renders it with errors.
// =========================
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.querySelector("[data-role-modal]");
  if (!modal) return;

  const openBtn = document.querySelector("[data-role-modal-open]");
  const closeBtn = modal.querySelector("header [data-role-modal-close]");
  let lastFocus = null;

  const open = () => {
    lastFocus = document.activeElement;
    modal.hidden = false;
    modal.removeAttribute("data-open");
    closeBtn.focus();
  };

  const close = () => {
    modal.hidden = true;
    if (location.hash === "#journals") history.replaceState(null, "", location.pathname + location.search);
    (lastFocus || openBtn)?.focus?.();
  };

  openBtn?.addEventListener("click", open);
  modal.querySelectorAll("[data-role-modal-close]").forEach((el) => el.addEventListener("click", close));

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) close();

    // Keep Tab inside the dialog while it is open.
    if (event.key === "Tab" && !modal.hidden) {
      const items = [...modal.querySelectorAll("button, input, a[href]")].filter((el) => !el.disabled);
      const first = items[0];
      const last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
  });

  if (location.hash === "#journals" || modal.hasAttribute("data-open")) open();
});
