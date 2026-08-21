/**
 * Admin Panel — tab-based UI controller
 * Depends on window.MenuStore (menu-store.js)
 */
(async function () {
  const $ = (id) => document.getElementById(id);

  let data = await MenuStore.fetchMenu();
  let lastLoadMeta = data.__meta || { isFallback: false, source: "api" };
  let currentTab = "settings";

  // Element refs
  const tabButtons = document.querySelectorAll(".tab-btn");
  const tabPanels = document.querySelectorAll(".tab-panel");
  const sectionsList = $("sectionsList");
  const sectionsEmpty = $("sectionsEmpty");
  const itemsList = $("itemsList");
  const itemsEmpty = $("itemsEmpty");
  const itemsFilter = $("itemsFilter");
  const itemsSearch = $("itemsSearch");
  const drawer = $("editDrawer");
  const drawerTitle = $("drawerTitle");
  const drawerSubtitle = $("drawerSubtitle");
  const drawerBody = $("drawerBody");
  const confirmDialog = $("confirmDialog");
  const confirmTitle = $("confirmTitle");
  const confirmMessage = $("confirmMessage");
  const confirmOk = $("confirmOk");
  const toastContainer = $("toastContainer");
  const sectionsCount = $("sectionsCount");
  const itemsCount = $("itemsCount");

  // ---------- Toast ----------
  function toast(message, type = "success", duration = 2600) {
    const node = document.createElement("div");
    node.className = `toast ${type}`;
    const icon = type === "error" ? "⚠️" : type === "warn" ? "⚡" : "✅";
    node.innerHTML = `<span>${icon}</span><span>${escapeHtml(message)}</span>`;
    toastContainer.appendChild(node);
    setTimeout(() => {
      node.classList.add("leaving");
      setTimeout(() => node.remove(), 280);
    }, duration);
  }

  // ---------- Confirm dialog ----------
  function confirm(title, message) {
    return new Promise((resolve) => {
      confirmTitle.textContent = title;
      confirmMessage.textContent = message;
      confirmDialog.classList.add("open");
      confirmDialog.setAttribute("aria-hidden", "false");
      const cleanup = (result) => {
        confirmDialog.classList.remove("open");
        confirmDialog.setAttribute("aria-hidden", "true");
        confirmOk.removeEventListener("click", onOk);
        document.removeEventListener("keydown", onKey);
        document.querySelectorAll("[data-confirm-close]").forEach((el) =>
          el.removeEventListener("click", onCancel)
        );
        resolve(result);
      };
      const onOk = () => cleanup(true);
      const onCancel = () => cleanup(false);
      const onKey = (e) => {
        if (e.key === "Escape") onCancel();
        if (e.key === "Enter") onOk();
      };
      confirmOk.addEventListener("click", onOk);
      document.addEventListener("keydown", onKey);
      document.querySelectorAll("[data-confirm-close]").forEach((el) =>
        el.addEventListener("click", onCancel)
      );
    });
  }

  // ---------- Tabs ----------
  function switchTab(tabId) {
    currentTab = tabId;
    tabButtons.forEach((btn) => {
      const active = btn.dataset.tab === tabId;
      btn.classList.toggle("active", active);
      btn.setAttribute("aria-selected", String(active));
    });
    tabPanels.forEach((panel) => {
      panel.classList.toggle("active", panel.id === `tab-${tabId}`);
    });
  }

  function refreshTabCounts() {
    if (sectionsCount) sectionsCount.textContent = data.sections.length;
    if (itemsCount) {
      itemsCount.textContent = MenuStore.flattenItems(data).length;
    }
  }

  tabButtons.forEach((btn) => {
    btn.addEventListener("click", () => switchTab(btn.dataset.tab));
  });

  // ---------- Render: Settings preview ----------
  function renderSettings() {
    $("heroDescriptionInput").value = data.heroDescription || "";
    $("showcaseTitleInput").value = data.showcaseTitle || "";
    $("showcaseDescriptionInput").value = data.showcaseDescription || "";
    $("footerBrandTitleInput").value = data.footerBrandTitle || "";
    $("footerInfoInput").value = data.footerInfo || "";
    previewImage("logoPreview", data.brandLogo, "لوگوی ثبت نشده");
    previewImage("showcasePreview", data.showcaseImage, "تصویر کاپ ثبت نشده");
  }

  function previewImage(elId, src, fallbackText) {
    const node = $(elId);
    if (!node) return;
    if (src) {
      node.innerHTML = `<img src="${escapeAttr(src)}" alt="preview">`;
    } else {
      node.innerHTML = `<span>${escapeHtml(fallbackText)}</span>`;
    }
  }

  // ---------- Render: Sections list ----------
  function renderSections() {
    sectionsList.innerHTML = "";
    sectionsEmpty.hidden = data.sections.length > 0;

    data.sections.forEach((section, index) => {
      const card = document.createElement("article");
      card.className = "section-card";
      card.draggable = true;
      card.dataset.sectionId = section.id;
      card.innerHTML = `
        <span class="card-handle" aria-hidden="true">⋮⋮</span>
        <div class="section-card-icon">${escapeHtml(section.icon || "☕")}</div>
        <div class="section-card-body">
          <div class="section-card-title">${escapeHtml(section.fa || section.en || section.id)}</div>
          <div class="section-card-meta">
            <span>${escapeHtml(section.en || "—")}</span>
            <span>•</span>
            <span>${(section.items || []).length} آیتم</span>
            <span>•</span>
            <span>${escapeHtml(section.id)}</span>
          </div>
        </div>
        <div class="section-card-actions">
          <button class="icon-btn" data-action="edit" title="ویرایش">✏️</button>
          <button class="icon-btn danger" data-action="delete" title="حذف">🗑</button>
        </div>
      `;
      card.querySelector('[data-action="edit"]').addEventListener("click", () =>
        openSectionDrawer(section.id)
      );
      card.querySelector('[data-action="delete"]').addEventListener("click", () =>
        deleteSection(section.id)
      );
      attachDragHandlers(card, index);
      sectionsList.appendChild(card);
    });
  }

  // ---------- Render: Items list ----------
  function renderItems() {
    const filter = itemsFilter.value;
    const search = (itemsSearch.value || "").trim().toLowerCase();
    const filtered = [];

    data.sections.forEach((section) => {
      if (filter && section.id !== filter) return;
      (section.items || []).forEach((item) => {
        const haystack = `${item.fa} ${item.en} ${item.id} ${item.desc || ""}`.toLowerCase();
        if (search && !haystack.includes(search)) return;
        filtered.push({ item, section });
      });
    });

    itemsList.innerHTML = "";
    itemsEmpty.hidden = filtered.length > 0;

    filtered.forEach(({ item, section }) => {
      const card = document.createElement("article");
      card.className = `item-card${item.featured ? " is-featured" : ""}`;
      card.draggable = true;
      card.dataset.itemId = item.id;
      const tags = (item.tags || []).slice(0, 3).map((t) =>
        `<span class="item-tag">${escapeHtml(t)}</span>`
      ).join("");
      const featuredBadge = item.featured
        ? '<span class="item-tag featured">⭐ ویژه</span>'
        : "";
      card.innerHTML = `
        <span class="card-handle" aria-hidden="true">⋮⋮</span>
        <div class="item-card-icon">${escapeHtml(item.emoji || section.icon || "☕")}</div>
        <div class="item-card-body">
          <div class="item-card-title">${escapeHtml(item.fa || item.en || item.id)}</div>
          <div class="item-card-meta">
            <span class="item-card-price">${escapeHtml(item.price || "0")}</span>
            <span class="dot"></span>
            <span>${escapeHtml(section.fa || section.id)}</span>
          </div>
          ${(tags || featuredBadge) ? `<div class="item-card-tags">${featuredBadge}${tags}</div>` : ""}
        </div>
        <div class="item-card-actions">
          <button class="icon-btn" data-action="edit" title="ویرایش">✏️</button>
          <button class="icon-btn danger" data-action="delete" title="حذف">🗑</button>
        </div>
      `;
      card.querySelector('[data-action="edit"]').addEventListener("click", () =>
        openItemDrawer(section.id, item.id)
      );
      card.querySelector('[data-action="delete"]').addEventListener("click", () =>
        deleteItem(section.id, item.id)
      );
      itemsList.appendChild(card);
    });
  }

  function refreshItemsFilter() {
    const current = itemsFilter.value;
    itemsFilter.innerHTML = '<option value="">همه دسته‌ها</option>' +
      data.sections.map((s) =>
        `<option value="${escapeAttr(s.id)}">${escapeHtml(s.fa || s.id)}</option>`
      ).join("");
    itemsFilter.value = current;
  }

  // ---------- Drag & drop reorder ----------
  let draggedCard = null;
  function attachDragHandlers(card, index) {
    card.addEventListener("dragstart", (e) => {
      draggedCard = card;
      card.classList.add("dragging");
      e.dataTransfer.effectAllowed = "move";
    });
    card.addEventListener("dragend", () => {
      card.classList.remove("dragging");
      draggedCard = null;
    });
    card.addEventListener("dragover", (e) => {
      e.preventDefault();
      if (!draggedCard || draggedCard === card) return;
    });
    card.addEventListener("drop", (e) => {
      e.preventDefault();
      if (!draggedCard || draggedCard === card) return;
      const rect = card.getBoundingClientRect();
      const after = e.clientY > rect.top + rect.height / 2;
      card.parentNode.insertBefore(
        draggedCard,
        after ? card.nextSibling : card
      );
    });
  }

  // Commit reorder after drag ends (sections or items)
  function commitReorder(listEl, kind) {
    const ids = Array.from(listEl.querySelectorAll("[data-section-id], [data-item-id]"))
      .map((el) => el.dataset.sectionId || el.dataset.itemId);
    if (kind === "section") {
      data.sections = ids
        .map((id) => data.sections.find((s) => s.id === id))
        .filter(Boolean);
    } else {
      // item reorder: items keep their own section; only sequence within section
      const map = new Map(data.sections.map((s) => [s.id, s.items]));
      data.sections.forEach((section) => {
        section.items = (map.get(section.id) || []).slice();
      });
    }
    persist(`ترتیب ${kind === "section" ? "دسته‌ها" : "آیتم‌ها"} ذخیره شد.`);
  }

  let dragEndTimer = null;
  function watchDragEnd(listEl, kind) {
    listEl.addEventListener("dragend", () => {
      clearTimeout(dragEndTimer);
      dragEndTimer = setTimeout(() => commitReorder(listEl, kind), 200);
    });
  }

  // ---------- Drawer: open/close ----------
  function openDrawer() {
    drawer.classList.add("open");
    drawer.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
    syncDrawerToViewport();
  }

  function closeDrawer() {
    drawer.classList.remove("open");
    drawer.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
    document.removeEventListener("focusin", syncDrawerToViewport);
  }

  // Resize the drawer panel to the visual viewport so iOS keyboard
  // doesn't cover the action buttons at the bottom.
  function syncDrawerToViewport() {
    if (!drawer.classList.contains("open")) return;
    const panel = drawer.querySelector(".drawer-panel");
    if (!panel) return;
    const vv = window.visualViewport;
    if (vv) {
      panel.style.maxHeight = `${vv.height}px`;
      panel.style.height = `${vv.height}px`;
    }
    document.addEventListener("focusin", syncDrawerToViewport, { once: true });
  }

  if (window.visualViewport) {
    window.visualViewport.addEventListener("resize", syncDrawerToViewport);
    window.visualViewport.addEventListener("scroll", syncDrawerToViewport);
  }

  document.querySelectorAll("[data-drawer-close]").forEach((el) =>
    el.addEventListener("click", closeDrawer)
  );

  // ---------- Section drawer ----------
  function openSectionDrawer(sectionId) {
    const section = data.sections.find((s) => s.id === sectionId);
    if (!section) return;

    drawerTitle.textContent = "ویرایش دسته‌بندی";
    drawerSubtitle.textContent = section.id;
    $("drawerSaveBtn").onclick = () => saveSectionFromDrawer(sectionId);
    $("drawerDeleteBtn").onclick = () => {
      closeDrawer();
      deleteSection(sectionId);
    };

    drawerBody.innerHTML = `
      <div class="field">
        <label>نام فارسی</label>
        <input id="drawerSectionFa" class="admin-input" type="text" value="${escapeAttr(section.fa || "")}">
      </div>
      <div class="field">
        <label>نام انگلیسی</label>
        <input id="drawerSectionEn" class="admin-input" type="text" value="${escapeAttr(section.en || "")}">
      </div>
      <div class="field-row">
        <div class="field">
          <label>آیکون یا ایموجی</label>
          <input id="drawerSectionIcon" class="admin-input" type="text" value="${escapeAttr(section.icon || "")}">
        </div>
        <div class="field">
          <label>شناسه (slug)</label>
          <input id="drawerSectionId" class="admin-input" type="text" value="${escapeAttr(section.id || "")}">
        </div>
      </div>
    `;
    openDrawer();
  }

  async function saveSectionFromDrawer(oldId) {
    const section = data.sections.find((s) => s.id === oldId);
    if (!section) return;

    const fa = $("drawerSectionFa").value.trim();
    const en = $("drawerSectionEn").value.trim();
    const icon = $("drawerSectionIcon").value.trim();
    const newId = $("drawerSectionId").value.trim() || MenuStore.slugify(fa || en || oldId);

    if (newId !== oldId) {
      const collision = data.sections.some((s) => s.id === newId && s !== section);
      if (collision) {
        toast("شناسه تکراری است.", "error");
        return;
      }
      section.id = newId;
    }
    section.fa = fa || "دسته‌بندی";
    section.en = en || "Section";
    section.icon = icon || "☕";

    closeDrawer();
    await persist("دسته‌بندی ذخیره شد.");
  }

  // ---------- Item drawer ----------
  function openItemDrawer(sectionId, itemId) {
    const section = data.sections.find((s) => s.id === sectionId);
    if (!section) return;
    const item = (section.items || []).find((i) => i.id === itemId);
    if (!item) return;

    drawerTitle.textContent = "ویرایش آیتم";
    drawerSubtitle.textContent = `${item.fa || item.en || item.id}`;
    $("drawerSaveBtn").onclick = () => saveItemFromDrawer(sectionId, itemId);
    $("drawerDeleteBtn").onclick = () => {
      closeDrawer();
      deleteItem(sectionId, itemId);
    };

    const sectionOptions = data.sections.map((s) =>
      `<option value="${escapeAttr(s.id)}" ${s.id === sectionId ? "selected" : ""}>${escapeHtml(s.fa || s.id)}</option>`
    ).join("");

    drawerBody.innerHTML = `
      <div class="field">
        <label>تصاویر (پیش‌فرض + اختصاصی برای هر تگ)</label>
        <div class="tag-images">
          <div class="tag-images-header">
            <span class="muted">روی هر تگ کلیک کن تا تصویر مخصوصش را آپلود کنی. اگه تصویری برای تگ نذاری، تصویر پیش‌فرض نمایش داده می‌شود.</span>
          </div>
          <div class="image-edit">
            <div class="image-edit-preview" id="drawerItemImagePreview"></div>
            <div class="image-edit-controls">
              <strong>تصویر پیش‌فرض</strong>
              <span class="muted">این تصویر وقتی نمایش داده می‌شود که تگ فعالی انتخاب نشده باشد.</span>
              <div class="admin-actions">
                <button class="upload-btn" type="button" id="drawerItemUploadBtn">📤 آپلود</button>
                <button class="reset-btn" type="button" id="drawerItemRemoveImageBtn">🗑 حذف</button>
              </div>
            </div>
            <input id="drawerItemImageInput" class="hidden-input" type="file" accept="image/*">
          </div>
          <div class="tag-images-grid" id="drawerTagImagesGrid"></div>
          <input id="drawerTagImageInput" class="hidden-input" type="file" accept="image/*">
        </div>
      </div>
      <div class="field-row">
        <div class="field">
          <label>نام فارسی</label>
          <input id="drawerItemFa" class="admin-input" type="text" value="${escapeAttr(item.fa || "")}">
        </div>
        <div class="field">
          <label>نام انگلیسی</label>
          <input id="drawerItemEn" class="admin-input" type="text" value="${escapeAttr(item.en || "")}">
        </div>
      </div>
      <div class="field-row">
        <div class="field">
          <label>قیمت</label>
          <input id="drawerItemPrice" class="admin-input" type="text" value="${escapeAttr(item.price || "")}">
        </div>
        <div class="field">
          <label>ایموجی جایگزین</label>
          <input id="drawerItemEmoji" class="admin-input" type="text" value="${escapeAttr(item.emoji || "")}">
        </div>
      </div>
      <div class="field">
        <label>توضیحات</label>
        <textarea id="drawerItemDesc" class="admin-textarea" rows="3">${escapeHtml(item.desc || "")}</textarea>
      </div>
      <div class="field">
        <label>تگ‌ها (با کاما جدا کن)</label>
        <input id="drawerItemTags" class="admin-input" type="text" value="${escapeAttr((item.tags || []).join(","))}" placeholder="hot,cold,sweet,new,vegan">
      </div>
      <div class="field-row">
        <div class="field">
          <label>دسته‌بندی</label>
          <select id="drawerItemSection" class="admin-select">${sectionOptions}</select>
        </div>
        <div class="field">
          <label>شناسه (slug)</label>
          <input id="drawerItemId" class="admin-input" type="text" value="${escapeAttr(item.id || "")}">
        </div>
      </div>
      <label class="checkbox-line">
        <input id="drawerItemFeatured" type="checkbox" ${item.featured ? "checked" : ""}>
        آیتم ویژه باشد
      </label>
    `;
    renderTagImageSlots(item);
    previewImage("drawerItemImagePreview", item.image.default || "", "تصویر ثبت نشده");

    $("drawerItemUploadBtn").addEventListener("click", () => $("drawerItemImageInput").click());
    $("drawerItemImageInput").addEventListener("change", async (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      try {
        const uploaded = await uploadImage(file);
        item.image.default = uploaded;
        previewImage("drawerItemImagePreview", uploaded, "تصویر ثبت نشده");
        toast("تصویر پیش‌فرض آپلود شد.");
      } catch (err) {
        toast(err?.message || "آپلود ناموفق بود.", "error");
      }
    });
    $("drawerItemRemoveImageBtn").addEventListener("click", () => {
      item.image.default = "";
      previewImage("drawerItemImagePreview", "", "تصویر ثبت نشده");
    });

    // Tag-image upload: clicked slot stores which tag is targeted
    let pendingTagForUpload = null;
    $("drawerTagImageInput").addEventListener("change", async (e) => {
      const file = e.target.files?.[0];
      e.target.value = "";
      const tag = pendingTagForUpload;
      pendingTagForUpload = null;
      if (!file || !tag) return;
      try {
        const uploaded = await uploadImage(file);
        item.image.tags = item.image.tags || {};
        item.image.tags[tag] = uploaded;
        renderTagImageSlots(item);
        toast(`تصویر برای تگ «${tagLabel(tag)}» آپلود شد.`);
      } catch (err) {
        toast(err?.message || "آپلود ناموفق بود.", "error");
      }
    });
    $("drawerTagImagesGrid").addEventListener("click", (e) => {
      const removeBtn = e.target.closest(".tag-image-slot-remove");
      if (removeBtn) {
        e.stopPropagation();
        const tag = removeBtn.dataset.tag;
        if (tag && item.image.tags) {
          delete item.image.tags[tag];
          renderTagImageSlots(item);
        }
        return;
      }
      const slot = e.target.closest(".tag-image-slot");
      if (!slot) return;
      const tag = slot.dataset.tag;
      if (!tag) return;
      pendingTagForUpload = tag;
      $("drawerTagImageInput").click();
    });

    openDrawer();
  }

  // Render tag-image slots based on item.tags (each tag gets a slot)
  function renderTagImageSlots(item) {
    const grid = $("drawerTagImagesGrid");
    if (!grid) return;
    const tags = item.tags || [];
    if (!tags.length) {
      grid.innerHTML = `<div class="muted" style="grid-column:1/-1;text-align:center;padding:0.6rem;">تگ‌ها را در پایین وارد کن تا بتوانی تصویر اختصاصی برایشان بگذاری.</div>`;
      return;
    }
    grid.innerHTML = tags.map((tag) => {
      const url = item.image.tags?.[tag] || "";
      const hasImage = !!url;
      return `
        <div class="tag-image-slot ${hasImage ? "has-image" : ""}" data-tag="${escapeAttr(tag)}" title="کلیک برای آپلود تصویر «${escapeAttr(tagLabel(tag))}»">
          ${hasImage ? `<img src="${escapeAttr(url)}" alt="${escapeAttr(tag)}">` : `<span class="tag-image-slot-add">+<span>${escapeHtml(tagLabel(tag))}</span></span>`}
          <span class="tag-image-slot-label">${escapeHtml(tagLabel(tag))}</span>
          ${hasImage ? `<button class="tag-image-slot-remove" type="button" data-tag="${escapeAttr(tag)}" aria-label="حذف">×</button>` : ""}
        </div>
      `;
    }).join("");
  }

  function tagLabel(tag) {
    const labels = {
      hot: "گرم",
      cold: "سرد",
      sweet: "شیرین",
      new: "جدید",
      vegan: "وگان"
    };
    return labels[tag] || tag;
  }

  async function saveItemFromDrawer(oldSectionId, oldItemId) {
    const oldSection = data.sections.find((s) => s.id === oldSectionId);
    if (!oldSection) return;
    const item = (oldSection.items || []).find((i) => i.id === oldItemId);
    if (!item) return;

    const fa = $("drawerItemFa").value.trim();
    const en = $("drawerItemEn").value.trim();
    const price = $("drawerItemPrice").value.trim();
    const emoji = $("drawerItemEmoji").value.trim();
    const desc = $("drawerItemDesc").value.trim();
    const tagsRaw = $("drawerItemTags").value;
    const newId = $("drawerItemId").value.trim() || MenuStore.slugify(fa || en || oldItemId);
    const newSectionId = $("drawerItemSection").value;
    const featured = $("drawerItemFeatured").checked;

    if (newId !== oldItemId || newSectionId !== oldSectionId) {
      const targetSection = data.sections.find((s) => s.id === newSectionId);
      const collision = targetSection && (targetSection.items || []).some(
        (i) => i.id === newId && i !== item
      );
      if (collision) {
        toast("شناسه آیتم تکراری است.", "error");
        return;
      }
      oldSection.items = oldSection.items.filter((i) => i !== item);
      targetSection.items.push(item);
    }

    item.id = newId;
    item.fa = fa || "آیتم";
    item.en = en || "Item";
    item.price = price || "0";
    item.emoji = emoji || "☕";
    item.desc = desc;
    item.tags = tagsRaw.split(",").map((t) => t.trim().toLowerCase()).filter(Boolean);
    item.featured = featured;

    closeDrawer();
    await persist("آیتم ذخیره شد.");
  }

  // ---------- CRUD handlers ----------
  async function addSection() {
    const usedIds = data.sections.map((s) => s.id);
    const section = {
      id: uniqueId("new-section", usedIds),
      icon: "☕",
      en: "New Section",
      fa: "دسته‌بندی جدید",
      items: []
    };
    data.sections.push(section);
    await persist("دسته‌بندی اضافه شد.");
    openSectionDrawer(section.id);
  }

  async function addItem() {
    if (!data.sections.length) {
      toast("اول یک دسته‌بندی اضافه کن.", "warn");
      return;
    }
    const section = data.sections[0];
    const usedIds = MenuStore.flattenItems(data).map((i) => i.id);
    const item = {
      id: uniqueId("new-item", usedIds),
      fa: "آیتم جدید",
      en: "New Item",
      price: "0",
      desc: "توضیحات آیتم",
      tags: [],
      featured: false,
      emoji: section.icon || "☕",
      image: ""
    };
    section.items.push(item);
    await persist("آیتم اضافه شد.");
    openItemDrawer(section.id, item.id);
  }

  async function deleteSection(sectionId) {
    if (data.sections.length < 2) {
      toast("حداقل یک دسته‌بندی باید باقی بماند.", "warn");
      return;
    }
    const section = data.sections.find((s) => s.id === sectionId);
    const ok = await confirm(
      "حذف دسته‌بندی",
      `دسته «${section?.fa || sectionId}» و ${(section?.items || []).length} آیتم آن حذف شوند؟`
    );
    if (!ok) return;
    data.sections = data.sections.filter((s) => s.id !== sectionId);
    await persist("دسته‌بندی حذف شد.");
  }

  async function deleteItem(sectionId, itemId) {
    const section = data.sections.find((s) => s.id === sectionId);
    const item = section?.items.find((i) => i.id === itemId);
    const ok = await confirm(
      "حذف آیتم",
      `آیتم «${item?.fa || itemId}» حذف شود؟`
    );
    if (!ok) return;
    section.items = section.items.filter((i) => i.id !== itemId);
    await persist("آیتم حذف شد.");
  }

  // ---------- Settings: general save ----------
  async function saveGeneral() {
    data.heroDescription = $("heroDescriptionInput").value.trim();
    data.showcaseTitle = $("showcaseTitleInput").value.trim();
    data.showcaseDescription = $("showcaseDescriptionInput").value.trim();
    data.footerBrandTitle = $("footerBrandTitleInput").value.trim();
    data.footerInfo = $("footerInfoInput").value.trim();
    await persist("تنظیمات عمومی ذخیره شد.");
  }

  // ---------- Image upload (logo + showcase) ----------
  async function uploadImage(file) {
    const formData = new FormData();
    formData.append("image", file);
    const response = await fetch("api/upload_image.php", {
      method: "POST",
      body: formData,
      credentials: "same-origin"
    });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.error || "Image upload failed");
    }
    return payload.url;
  }

  $("logoUploadBtn").addEventListener("click", () => $("logoInput").click());
  $("logoInput").addEventListener("change", async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    try {
      data.brandLogo = await uploadImage(file);
      previewImage("logoPreview", data.brandLogo, "لوگوی ثبت نشده");
      await persist("لوگو ذخیره شد.");
    } catch (err) {
      toast(err?.message || "آپلود لوگو ناموفق بود.", "error");
    }
  });
  $("removeLogoBtn").addEventListener("click", async () => {
    data.brandLogo = "";
    previewImage("logoPreview", "", "لوگوی ثبت نشده");
    await persist("لوگو حذف شد.");
  });

  $("showcaseImageUploadBtn").addEventListener("click", () => $("showcaseImageInput").click());
  $("showcaseImageInput").addEventListener("change", async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    try {
      data.showcaseImage = await uploadImage(file);
      previewImage("showcasePreview", data.showcaseImage, "تصویر کاپ ثبت نشده");
      await persist("تصویر کاپ ذخیره شد.");
    } catch (err) {
      toast(err?.message || "آپلود تصویر ناموفق بود.", "error");
    }
  });
  $("removeShowcaseImageBtn").addEventListener("click", async () => {
    data.showcaseImage = "";
    previewImage("showcasePreview", "", "تصویر کاپ ثبت نشده");
    await persist("تصویر کاپ حذف شد.");
  });

  // ---------- Operations ----------
  async function resetAll() {
    const ok = await confirm(
      "بازنشانی کل داده‌ها",
      "تمام منو با داده‌های پیش‌فرض جایگزین می‌شود. این عملیات برگشت‌پذیر نیست."
    );
    if (!ok) return;
    data = MenuStore.clone(MenuStore.FALLBACK_MENU);
    await persist("همه داده‌ها بازنشانی شد.");
    renderSettings();
  }

  async function seedDatabase() {
    try {
      const response = await fetch("api/migration.php?force=1", {
        method: "GET",
        credentials: "same-origin"
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) {
        throw new Error(payload.error || "Seed failed");
      }
      data = await MenuStore.fetchMenu();
      lastLoadMeta = data.__meta || { isFallback: false, source: "api" };
      await persist("دیتابیس seed شد.");
      toast("دیتابیس با داده‌های پیش‌فرض پر شد.");
    } catch (err) {
      toast(err?.message || "Seed ناموفق بود.", "error");
    }
  }

  async function bumpCache() {
    try {
      const response = await fetch("api/bump_cache.php", {
        method: "POST",
        credentials: "same-origin"
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) {
        throw new Error(payload.error || "Cache refresh failed");
      }
      toast(`کش مرورگر به‌روزرسانی شد: ${payload.cacheBust}`);
    } catch (err) {
      toast(err?.message || "پاک‌سازی کش ناموفق بود.", "error");
    }
  }

  // ---------- Persist wrapper ----------
  async function persist(message) {
    try {
      await MenuStore.saveMenu(data);
      renderSettings();
      renderSections();
      refreshItemsFilter();
      renderItems();
      refreshTabCounts();
      if (message) toast(message);
    } catch (err) {
      toast(err?.message || "ذخیره ناموفق بود.", "error");
    }
  }

  // ---------- Wire up ----------
  $("addSectionBtn").addEventListener("click", addSection);
  $("addItemBtn").addEventListener("click", addItem);
  $("saveGeneralBtn").addEventListener("click", saveGeneral);
  $("resetAllBtn").addEventListener("click", resetAll);
  $("seedDatabaseBtn").addEventListener("click", seedDatabase);
  $("bumpCacheBtn").addEventListener("click", bumpCache);

  itemsFilter.addEventListener("change", renderItems);
  itemsSearch.addEventListener("input", renderItems);

  watchDragEnd(sectionsList, "section");
  watchDragEnd(itemsList, "item");

  // ---------- Init ----------
  if (lastLoadMeta.isFallback) {
    toast("دیتابیس هنوز seed نشده — داده‌های پیش‌فرض نمایش داده می‌شوند.", "warn", 4500);
  }
  renderSettings();
  renderSections();
  refreshItemsFilter();
  renderItems();
  refreshTabCounts();

  // ---------- Helpers ----------
  function uniqueId(base, usedIds) {
    let id = MenuStore.slugify(base) || `id-${Date.now()}`;
    let counter = 2;
    while (usedIds.includes(id)) {
      id = `${MenuStore.slugify(base) || "id"}-${counter}`;
      counter += 1;
    }
    return id;
  }

  function escapeHtml(value) {
    if (value === null || value === undefined) return "";
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function escapeAttr(value) {
    return escapeHtml(value);
  }
})();