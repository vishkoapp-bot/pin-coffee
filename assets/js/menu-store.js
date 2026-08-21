window.MenuStore = (() => {
  const API_BASE = "api";
  const FALLBACK_MENU = {
    brandLogo: "",
    showcaseImage: "",
    heroDescription: "یک منوی گرم و مینیمال با ظاهر لوکس برای کافه پین.",
    showcaseTitle: "فضای گرم، منوی پویا",
    showcaseDescription: "ترکیبی از هویت بصری خاص، تصاویر تمیز و آیتم‌های کاملاً قابل مدیریت.",
    footerBrandTitle: "میان",
    footerInfo: "ساعت کاری: شنبه تا پنجشنبه 8 صبح تا 11 شب\nجمعه 9 صبح تا 11 شب - قیمت‌ها به تومان",
    footerLinks: [
      { label: "اینستاگرام", href: "#" },
      { label: "تلگرام", href: "#" },
      { label: "لوکیشن", href: "#" }
    ],
    sections: [
      {
        id: "espresso",
        icon: "☕",
        en: "Espresso Based",
        fa: "اسپرسو بیس",
        items: [
          { id: "espresso", fa: "اسپرسو", en: "Espresso", price: "65,000", desc: "شات خالص، تلخ و پرانرژی برای شروع روز.", tags: ["hot"], featured: true, emoji: "☕", image: "" },
          { id: "doppio", fa: "دوپیو", en: "Doppio", price: "85,000", desc: "دو شات اسپرسو برای دوست‌داران قهوه پررنگ.", tags: ["hot"], featured: false, emoji: "☕", image: "" }
        ]
      },
      {
        id: "cold",
        icon: "🧊",
        en: "Cold Coffee",
        fa: "نوشیدنی سرد",
        items: [
          { id: "coldbrew", fa: "کولد برو", en: "Cold Brew", price: "145,000", desc: "دم‌آوری طولانی‌مدت با طعمی نرم و بدون تلخی اضافه.", tags: ["cold"], featured: true, emoji: "🧊", image: "" },
          { id: "icedlatte", fa: "آیس لاته", en: "Iced Latte", price: "135,000", desc: "اسپرسو روی یخ با شیر سرد و بافتی ملایم.", tags: ["cold"], featured: false, emoji: "🥤", image: "" }
        ]
      },
      {
        id: "dessert",
        icon: "🥐",
        en: "Dessert & Pastry",
        fa: "خوراکی و شیرینی",
        items: [
          { id: "croissant", fa: "کرواسان کره‌ای", en: "Butter Croissant", price: "115,000", desc: "تازه از فر، ترد و کره‌ای.", tags: ["new"], featured: true, emoji: "🥐", image: "" },
          { id: "cheesecake", fa: "چیزکیک", en: "Basque Cheesecake", price: "135,000", desc: "چیزکیک کرمی با بافت لطیف و پخت روزانه.", tags: ["sweet"], featured: false, emoji: "🍰", image: "" }
        ]
      }
    ]
  };

  function clone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function slugify(value) {
    return (value || "")
      .toString()
      .trim()
      .toLowerCase()
      .replace(/[^\p{L}\p{N}\s-]/gu, "")
      .replace(/\s+/g, "-")
      .replace(/-+/g, "-")
      .replace(/^-|-$/g, "");
  }

  function createSectionTemplate() {
    return {
      id: `section-${Date.now()}`,
      icon: "☕",
      en: "New Section",
      fa: "دسته‌بندی جدید",
      items: []
    };
  }

  function createItemTemplate(sectionId) {
    return {
      id: `item-${Date.now()}`,
      fa: "آیتم جدید",
      en: "New Item",
      price: "0",
      desc: "توضیحات آیتم جدید",
      tags: [],
      featured: false,
      emoji: "☕",
      image: "",
      sectionId
    };
  }

  function normalizeImage(value) {
    if (!value) return { default: "", tags: {} };
    if (typeof value === "string") return { default: value, tags: {} };
    if (typeof value === "object") {
      return {
        default: value.default || "",
        tags: value.tags && typeof value.tags === "object" ? { ...value.tags } : {}
      };
    }
    return { default: "", tags: {} };
  }

  function normalizeMenuResponse(payload) {
    const source = payload && typeof payload === "object" && payload.data && typeof payload.data === "object"
      ? payload.data
      : payload;
    const base = clone(FALLBACK_MENU);
    if (!source || typeof source !== "object") return base;

    if (Array.isArray(source.sections)) {
      base.sections = source.sections
        .filter(section => section && typeof section === "object")
        .map(section => ({
          id: section.id || slugify(section.fa || section.en || "section"),
          icon: section.icon || "☕",
          en: section.en || "",
          fa: section.fa || "",
          items: Array.isArray(section.items)
            ? section.items.filter(item => item && typeof item === "object").map(item => ({
                id: item.id || slugify(item.fa || item.en || "item"),
                fa: item.fa || "",
                en: item.en || "",
                price: item.price || "0",
                desc: item.desc || "",
                tags: Array.isArray(item.tags) ? item.tags : [],
                featured: Boolean(item.featured),
                emoji: item.emoji || "☕",
                image: normalizeImage(item.image)
              }))
            : []
        }));
    }

    base.brandLogo = source.brandLogo || "";
    base.showcaseImage = source.showcaseImage || "";
    base.heroDescription = source.heroDescription || base.heroDescription;
    base.showcaseTitle = source.showcaseTitle || base.showcaseTitle;
    base.showcaseDescription = source.showcaseDescription || base.showcaseDescription;
    base.footerBrandTitle = source.footerBrandTitle || base.footerBrandTitle;
    base.footerInfo = source.footerInfo || base.footerInfo;
    base.footerLinks = Array.isArray(source.footerLinks) ? source.footerLinks.filter(link => link && typeof link === "object") : base.footerLinks;
    return base;
  }

  function serializeImage(image) {
    if (!image) return null;
    if (typeof image === "string") return image ? { default: image, tags: {} } : null;
    const tags = {};
    if (image.tags && typeof image.tags === "object") {
      for (const [key, value] of Object.entries(image.tags)) {
        if (value) tags[key] = value;
      }
    }
    return {
      default: image.default || "",
      tags
    };
  }

  function toApiPayload(menu) {
    const data = normalizeMenuResponse(menu);
    return {
      brandLogo: data.brandLogo || "",
      showcaseImage: data.showcaseImage || "",
      heroDescription: data.heroDescription || "",
      showcaseTitle: data.showcaseTitle || "",
      showcaseDescription: data.showcaseDescription || "",
      footerBrandTitle: data.footerBrandTitle || "",
      footerInfo: data.footerInfo || "",
      footerLinks: Array.isArray(data.footerLinks) ? data.footerLinks : [],
      sections: (data.sections || []).map(section => ({
        id: section.id || slugify(section.fa || section.en || "section"),
        icon: section.icon || "☕",
        en: section.en || "",
        fa: section.fa || "",
        items: (section.items || []).map(item => ({
          id: item.id || slugify(item.fa || item.en || "item"),
          fa: item.fa || "",
          en: item.en || "",
          price: item.price || "0",
          desc: item.desc || "",
          tags: Array.isArray(item.tags) ? item.tags : [],
          featured: Boolean(item.featured),
          emoji: item.emoji || "☕",
          image: serializeImage(item.image)
        }))
      }))
    };
  }

  function toAdminModel(apiData) {
    return normalizeMenuResponse(apiData);
  }

  async function fetchJson(url, options = {}) {
    const response = await fetch(url, {
      credentials: "same-origin",
      ...options,
      headers: {
        Accept: "application/json",
        ...(options.headers || {})
      }
    });

    const contentType = response.headers.get("content-type") || "";
    const payload = contentType.includes("application/json") ? await response.json() : await response.text();
    if (!response.ok) {
      const message = typeof payload === "object" && payload && payload.error ? payload.error : `Request failed with status ${response.status}`;
      throw new Error(message);
    }
    return payload;
  }

  async function fetchMenu() {
    try {
      const payload = await fetchJson(`${API_BASE}/get_menu.php`);
      const normalized = normalizeMenuResponse(payload);
      normalized.__meta = {
        isFallback: Boolean(payload && payload.isFallback),
        source: "api"
      };
      return normalized;
    } catch (error) {
      console.error("Failed to load menu from API:", error);
      const fallback = clone(FALLBACK_MENU);
      fallback.__meta = { isFallback: true, source: "fallback" };
      return fallback;
    }
  }

  async function saveMenu(menu, token) {
    const body = {
      token: token || "",
      menuData: toApiPayload(menu)
    };

    return fetchJson(`${API_BASE}/save_menu.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body)
    });
  }

  function flattenItems(data) {
    return (data.sections || []).flatMap(section =>
      (section.items || []).map(item => ({ ...item, sectionId: section.id, sectionFa: section.fa }))
    );
  }

  function findSection(data, sectionId) {
    return (data.sections || []).find(section => section.id === sectionId) || null;
  }

  function findItem(data, itemId) {
    for (const section of data.sections || []) {
      const item = (section.items || []).find(entry => entry.id === itemId);
      if (item) return { item, section };
    }
    return null;
  }

  return {
    API_BASE,
    FALLBACK_MENU,
    clone,
    slugify,
    createSectionTemplate,
    createItemTemplate,
    normalizeMenuResponse,
    toApiPayload,
    toAdminModel,
    fetchMenu,
    saveMenu,
    flattenItems,
    findSection,
    findItem
  };
})();
