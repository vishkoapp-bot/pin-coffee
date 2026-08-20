(async function () {
  let data = await MenuStore.fetchMenu();
  let lastLoadMeta = data.__meta || { isFallback: false, source: "api" };
  let currentSectionId = data.sections[0]?.id || "";
  let currentItemId = MenuStore.flattenItems(data)[0]?.id || "";

  const $ = id => document.getElementById(id);

  function setStatus(message, type = "success") {
    const node = $("statusNote");
    if (!node) return;
    node.textContent = message;
    node.dataset.type = type;
    node.classList.add("visible");
    clearTimeout(setStatus.timer);
    setStatus.timer = setTimeout(() => {
      node.textContent = "";
      node.classList.remove("visible");
    }, 2500);
  }

  function handleAction(action) {
    return async event => {
      try {
        await action(event);
      } catch (error) {
        console.error(error);
        setStatus(error?.message || "عملیات با خطا مواجه شد.", "error");
      }
    };
  }

  function showDatabaseStateNotice() {
    const node = $("statusNote");
    if (!node || !lastLoadMeta.isFallback) return;
    node.textContent = "دیتابیس هنوز seed نشده یا خالی است. داده‌های پیش‌فرض نمایش داده می‌شوند. برای ذخیره دائمی، migration را اجرا کن.";
    node.dataset.type = "error";
    node.classList.add("visible");
  }

  function sectionById(id) {
    return data.sections.find(section => section.id === id) || null;
  }

  function itemResultById(id) {
    for (const section of data.sections) {
      const item = section.items.find(entry => entry.id === id);
      if (item) return { section, item };
    }
    return null;
  }

  function uniqueId(base, usedIds) {
    let id = MenuStore.slugify(base) || `id-${Date.now()}`;
    let counter = 2;
    while (usedIds.includes(id)) {
      id = `${MenuStore.slugify(base) || "id"}-${counter}`;
      counter += 1;
    }
    return id;
  }

  function preview(id, src, text) {
    const node = $(id);
    if (!node) return;
    node.innerHTML = src ? `<img src="${src}" alt="preview">` : `<span>${text}</span>`;
  }

  async function uploadImage(file) {
    const formData = new FormData();
    formData.append("image", file);
    formData.append("token", $("adminTokenInput")?.value || "");

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

  function renderGeneral() {
    $("heroDescriptionInput").value = data.heroDescription || "";
    $("showcaseTitleInput").value = data.showcaseTitle || "";
    $("showcaseDescriptionInput").value = data.showcaseDescription || "";
    $("footerBrandTitleInput").value = data.footerBrandTitle || "";
    $("footerInfoInput").value = data.footerInfo || "";
    preview("logoPreview", data.brandLogo, "لوگوی ثبت نشده است.");
    preview("showcasePreview", data.showcaseImage, "تصویری برای کاپ نمایشی ثبت نشده است.");
  }

  function renderSelectors() {
    if (!data.sections.length) {
      const section = MenuStore.createSectionTemplate();
      data.sections.push(section);
      currentSectionId = section.id;
    }

    if (!sectionById(currentSectionId)) {
      currentSectionId = data.sections[0].id;
    }

    const allItems = MenuStore.flattenItems(data);
    if (!allItems.some(item => item.id === currentItemId)) {
      currentItemId = allItems[0]?.id || "";
    }

    $("sectionSelector").innerHTML = data.sections.map(section =>
      `<option value="${section.id}">${section.fa || section.id}</option>`
    ).join("");
    $("sectionSelector").value = currentSectionId;

    $("itemSectionSelector").innerHTML = data.sections.map(section =>
      `<option value="${section.id}">${section.fa || section.id}</option>`
    ).join("");

    $("itemSelector").innerHTML = allItems.map(item =>
      `<option value="${item.id}">${item.sectionFa || ""} - ${item.fa || item.id}</option>`
    ).join("");
    $("itemSelector").value = currentItemId;
    $("itemSelector").disabled = allItems.length === 0;

    fillSectionForm();
    fillItemForm();
  }

  function fillSectionForm() {
    const section = sectionById(currentSectionId);
    if (!section) return;
    $("sectionFaInput").value = section.fa || "";
    $("sectionEnInput").value = section.en || "";
    $("sectionIconInput").value = section.icon || "";
    $("sectionIdInput").value = section.id || "";
  }

  function fillItemForm() {
    const result = itemResultById(currentItemId);
    if (!result) {
      $("itemSectionSelector").value = currentSectionId;
      $("itemIdInput").value = "";
      $("nameFaInput").value = "";
      $("nameEnInput").value = "";
      $("priceInput").value = "";
      $("emojiInput").value = "";
      $("descInput").value = "";
      $("tagsInput").value = "";
      $("featuredInput").checked = false;
      preview("imagePreview", "", "تصویری برای این آیتم ثبت نشده است.");
      return;
    }

    $("itemSectionSelector").value = result.section.id;
    $("itemIdInput").value = result.item.id || "";
    $("nameFaInput").value = result.item.fa || "";
    $("nameEnInput").value = result.item.en || "";
    $("priceInput").value = result.item.price || "";
    $("emojiInput").value = result.item.emoji || "";
    $("descInput").value = result.item.desc || "";
    $("tagsInput").value = (result.item.tags || []).join(",");
    $("featuredInput").checked = Boolean(result.item.featured);
    preview("imagePreview", result.item.image, "تصویری برای این آیتم ثبت نشده است.");
  }

  async function persist(message) {
    await MenuStore.saveMenu(data, $("adminTokenInput")?.value || "");
    renderSelectors();
    setStatus(message);
  }

  async function seedDatabase() {
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
    currentSectionId = data.sections[0]?.id || "";
    currentItemId = MenuStore.flattenItems(data)[0]?.id || "";
    renderGeneral();
    renderSelectors();
    setStatus("دیتابیس با داده‌های پیش‌فرض seed شد.");
  }

  async function bumpCache() {
    const response = await fetch("api/bump_cache.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({ token: $("adminTokenInput")?.value || "" })
    });
    const payload = await response.json();
    if (!response.ok || !payload.success) {
      throw new Error(payload.error || "Cache refresh failed");
    }
    setStatus(`کش مرورگر برای نسخه جدید به‌روزرسانی شد: ${payload.cacheBust}`);
  }

  async function saveGeneral() {
    data.heroDescription = $("heroDescriptionInput").value.trim();
    data.showcaseTitle = $("showcaseTitleInput").value.trim();
    data.showcaseDescription = $("showcaseDescriptionInput").value.trim();
    data.footerBrandTitle = $("footerBrandTitleInput").value.trim();
    data.footerInfo = $("footerInfoInput").value.trim();
    await persist("تنظیمات عمومی ذخیره شد.");
  }

  async function addSection() {
    const usedIds = data.sections.map(section => section.id);
    const section = {
      id: uniqueId("new-section", usedIds),
      icon: "☕",
      en: "New Section",
      fa: "دسته‌بندی جدید",
      items: []
    };
    data.sections.push(section);
    currentSectionId = section.id;
    await persist("دسته‌بندی اضافه شد.");
  }

  async function saveSection() {
    const section = sectionById(currentSectionId);
    if (!section) return;

    const oldId = section.id;
    const usedIds = data.sections.filter(entry => entry !== section).map(entry => entry.id);
    const nextId = uniqueId($("sectionIdInput").value || section.fa || oldId, usedIds);

    section.id = nextId;
    section.fa = $("sectionFaInput").value.trim() || "دسته‌بندی";
    section.en = $("sectionEnInput").value.trim() || "Section";
    section.icon = $("sectionIconInput").value.trim() || "☕";
    currentSectionId = nextId;

    const selectedItem = itemResultById(currentItemId);
    if (selectedItem && selectedItem.section.id === oldId) {
      currentItemId = selectedItem.item.id;
    }

    await persist("دسته‌بندی ذخیره شد.");
  }

  async function deleteSection() {
    if (data.sections.length < 2) {
      setStatus("حداقل یک دسته‌بندی باید باقی بماند.", "error");
      return;
    }
    data.sections = data.sections.filter(section => section.id !== currentSectionId);
    currentSectionId = data.sections[0].id;
    currentItemId = MenuStore.flattenItems(data)[0]?.id || "";
    await persist("دسته‌بندی حذف شد.");
  }

  async function addItem() {
    const section = sectionById(currentSectionId) || data.sections[0];
    if (!section) return;
    const usedIds = MenuStore.flattenItems(data).map(item => item.id);
    const item = {
      id: uniqueId("new-item", usedIds),
      fa: "آیتم جدید",
      en: "New Item",
      price: "0",
      desc: "توضیحات آیتم",
      tags: [],
      featured: false,
      emoji: "☕",
      image: ""
    };
    section.items.push(item);
    currentSectionId = section.id;
    currentItemId = item.id;
    await persist("آیتم اضافه شد.");
  }

  async function saveItem() {
    const result = itemResultById(currentItemId);
    if (!result) {
      setStatus("اول یک آیتم اضافه کن.", "error");
      return;
    }

    const usedIds = MenuStore.flattenItems(data)
      .filter(item => item.id !== result.item.id)
      .map(item => item.id);
    const nextId = uniqueId($("itemIdInput").value || result.item.fa || result.item.id, usedIds);
    const targetSection = sectionById($("itemSectionSelector").value) || result.section;

    result.item.id = nextId;
    result.item.fa = $("nameFaInput").value.trim() || "آیتم";
    result.item.en = $("nameEnInput").value.trim() || "Item";
    result.item.price = $("priceInput").value.trim() || "0";
    result.item.emoji = $("emojiInput").value.trim() || "☕";
    result.item.desc = $("descInput").value.trim();
    result.item.tags = $("tagsInput").value.split(",").map(tag => tag.trim().toLowerCase()).filter(Boolean);
    result.item.featured = $("featuredInput").checked;

    if (targetSection !== result.section) {
      result.section.items = result.section.items.filter(item => item !== result.item);
      targetSection.items.push(result.item);
    }

    currentSectionId = targetSection.id;
    currentItemId = result.item.id;
    await persist("آیتم ذخیره شد.");
  }

  async function deleteItem() {
    const result = itemResultById(currentItemId);
    if (!result) return;
    result.section.items = result.section.items.filter(item => item !== result.item);
    currentSectionId = result.section.id;
    currentItemId = MenuStore.flattenItems(data)[0]?.id || "";
    await persist("آیتم حذف شد.");
  }

  async function uploadImageToCurrent(target, file) {
    if (!file) return;
    if (target === "logo") {
      data.brandLogo = await uploadImage(file);
      preview("logoPreview", data.brandLogo, "لوگوی ثبت نشده است.");
      await persist("لوگو ذخیره شد.");
    } else if (target === "showcase") {
      data.showcaseImage = await uploadImage(file);
      preview("showcasePreview", data.showcaseImage, "تصویری برای کاپ نمایشی ثبت نشده است.");
      await persist("تصویر کاپ ذخیره شد.");
    } else if (target === "item") {
      const result = itemResultById(currentItemId);
      if (!result) return;
      result.item.image = await uploadImage(file);
      preview("imagePreview", result.item.image, "تصویری برای این آیتم ثبت نشده است.");
      await persist("تصویر آیتم ذخیره شد.");
    }
  }

  async function resetAll() {
    data = MenuStore.clone(MenuStore.FALLBACK_MENU);
    currentSectionId = data.sections[0]?.id || "";
    currentItemId = MenuStore.flattenItems(data)[0]?.id || "";
    await persist("همه داده‌ها بازنشانی شد.");
    renderGeneral();
  }

  function bindEvents() {
    $("sectionSelector").addEventListener("change", event => {
      currentSectionId = event.target.value;
      fillSectionForm();
    });

    $("itemSelector").addEventListener("change", event => {
      currentItemId = event.target.value;
      fillItemForm();
    });

    $("addSectionBtn").addEventListener("click", handleAction(addSection));
    $("saveSectionBtn").addEventListener("click", handleAction(saveSection));
    $("deleteSectionBtn").addEventListener("click", handleAction(deleteSection));
    $("addItemBtn").addEventListener("click", handleAction(addItem));
    $("saveItemBtn").addEventListener("click", handleAction(saveItem));
    $("deleteItemBtn").addEventListener("click", handleAction(deleteItem));
    $("saveGeneralBtn").addEventListener("click", handleAction(saveGeneral));

    $("logoUploadBtn").addEventListener("click", () => $("logoInput").click());
    $("showcaseImageUploadBtn").addEventListener("click", () => $("showcaseImageInput").click());
    $("itemImageUploadBtn").addEventListener("click", () => $("itemImageInput").click());

    $("logoInput").addEventListener("change", event =>
      uploadImageToCurrent("logo", event.target.files?.[0])
        .catch(error => setStatus(error?.message || "بارگذاری لوگو ناموفق بود.", "error"))
    );
    $("showcaseImageInput").addEventListener("change", event =>
      uploadImageToCurrent("showcase", event.target.files?.[0])
        .catch(error => setStatus(error?.message || "بارگذاری تصویر کاپ ناموفق بود.", "error"))
    );
    $("itemImageInput").addEventListener("change", event =>
      uploadImageToCurrent("item", event.target.files?.[0])
        .catch(error => setStatus(error?.message || "بارگذاری تصویر آیتم ناموفق بود.", "error"))
    );

    $("removeLogoBtn").addEventListener("click", async () => {
      data.brandLogo = "";
      preview("logoPreview", "", "لوگوی ثبت نشده است.");
      await persist("لوگو حذف شد.");
    });

    $("removeShowcaseImageBtn").addEventListener("click", async () => {
      data.showcaseImage = "";
      preview("showcasePreview", "", "تصویری برای کاپ نمایشی ثبت نشده است.");
      await persist("تصویر کاپ حذف شد.");
    });

    $("removeItemImageBtn").addEventListener("click", async () => {
      const result = itemResultById(currentItemId);
      if (!result) return;
      result.item.image = "";
      preview("imagePreview", "", "تصویری برای این آیتم ثبت نشده است.");
      await persist("تصویر آیتم حذف شد.");
    });

    $("resetAllBtn").addEventListener("click", handleAction(resetAll));
    $("seedDatabaseBtn")?.addEventListener("click", handleAction(seedDatabase));
    $("bumpCacheBtn")?.addEventListener("click", handleAction(bumpCache));
  }

  renderGeneral();
  renderSelectors();
  showDatabaseStateNotice();
  bindEvents();
})();
