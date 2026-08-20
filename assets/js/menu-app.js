(async function () {
  const data = await MenuStore.fetchMenu();

  const modal = document.getElementById("itemModal");
  const modalImage = document.getElementById("itemModalImage");
  const modalVisual = document.getElementById("itemModalVisual");
  const modalFallback = document.getElementById("itemModalFallback");
  const modalBadge = document.getElementById("itemModalBadge");
  const modalPrice = document.getElementById("itemModalPrice");
  const modalTitle = document.getElementById("itemModalTitle");
  const modalSubtitle = document.getElementById("itemModalSubtitle");
  const modalDescription = document.getElementById("itemModalDescription");
  const modalTags = document.getElementById("itemModalTags");

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

  function closeModal() {
    if (!modal) return;
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
  }

  function openModal(item) {
    if (!modal) return;
    const title = item.fa || item.en || "آیتم";

    modalTitle.textContent = title;
    modalSubtitle.textContent = item.en || "";
    modalDescription.textContent = item.desc || "توضیحی برای این آیتم ثبت نشده است.";
    modalPrice.textContent = item.price || "";
    modalBadge.textContent = item.featured ? "پیشنهاد ویژه" : "جزئیات آیتم";
    modalTags.innerHTML = (item.tags || []).map(tag => `<span class="tag ${tag}">${tagLabel(tag)}</span>`).join("");
    modalFallback.textContent = item.emoji || "☕";
    modalImage.alt = title;

    if (item.image) {
      modalImage.src = item.image;
      modalVisual.classList.add("has-image");
    } else {
      modalImage.removeAttribute("src");
      modalVisual.classList.remove("has-image");
    }

    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
  }

  function renderLogo() {
    const frame = document.getElementById("brandLogoFrame");
    const image = document.getElementById("brandLogoImage");
    if (!frame || !image) return;
    if (data.brandLogo) {
      image.src = data.brandLogo;
      frame.classList.add("has-image");
    } else {
      image.removeAttribute("src");
      frame.classList.remove("has-image");
    }
  }

  function renderShowcaseImage() {
    const image = document.getElementById("showcaseHeroImage");
    const media = image?.closest(".showcase-media");
    if (!image || !media) return;
    if (data.showcaseImage) {
      image.src = data.showcaseImage;
      media.classList.add("has-image");
    } else {
      image.removeAttribute("src");
      media.classList.remove("has-image");
    }
  }

  function renderHero() {
    const heroDescription = document.getElementById("heroDescription");
    const showcaseTitle = document.getElementById("showcaseTitle");
    const showcaseDescription = document.getElementById("showcaseDescription");
    if (heroDescription) heroDescription.textContent = data.heroDescription || "";
    if (showcaseTitle) showcaseTitle.textContent = data.showcaseTitle || "";
    if (showcaseDescription) showcaseDescription.textContent = data.showcaseDescription || "";
  }

  function renderFooter() {
    const footerBrandTitle = document.getElementById("footerBrandTitle");
    const footerInfo = document.getElementById("footerInfo");
    const footerLinks = document.getElementById("footerLinks");
    if (footerBrandTitle) footerBrandTitle.textContent = data.footerBrandTitle || "میان";
    if (footerInfo) footerInfo.textContent = data.footerInfo || "";
    if (footerLinks) {
      footerLinks.innerHTML = (data.footerLinks || []).map(link => `
        <a class="footer-link" href="${link.href || "#"}">${link.label || "لینک"}</a>
      `).join("");
    }
  }

  function renderNav() {
    const nav = document.getElementById("navButtons");
    if (!nav) return;
    const sections = data.sections || [];
    nav.innerHTML = sections.map((section, index) => `
      <button class="nav-btn ${index === 0 ? "active" : ""}" data-target="${section.id}" type="button">${section.fa || section.en || section.id}</button>
    `).join("");

    nav.querySelectorAll(".nav-btn").forEach(button => {
      button.addEventListener("click", () => {
        document.getElementById(button.dataset.target)?.scrollIntoView({ behavior: "smooth", block: "start" });
        setActiveNav(button.dataset.target);
      });
    });
  }

  function renderItem(item) {
    const tags = (item.tags || []).map(tag => `<span class="tag ${tag}">${tagLabel(tag)}</span>`).join("");
    return `
      <article class="menu-item" data-item-id="${item.id}" tabindex="0" role="button" aria-label="نمایش جزئیات ${item.fa || item.en || ""}">
        <div class="item-action-hint">لمس برای جزئیات</div>
        ${item.featured ? '<div class="badge-popular">پیشنهاد ویژه</div>' : ""}
        <div class="item-visual ${item.image ? "" : "no-image"}">
          <img class="item-image" src="${item.image || ""}" alt="${item.fa || item.en || ""}">
          <div class="item-visual-fallback" aria-hidden="true">${item.emoji || "☕"}</div>
        </div>
        <div class="item-top">
          <div>
            <div class="item-name">${item.fa || ""}</div>
            <div class="item-en">${item.en || ""}</div>
          </div>
          <div class="item-price">${item.price || ""}</div>
        </div>
        <div class="item-tags">${tags}</div>
      </article>
    `;
  }

  function setActiveNav(id) {
    document.querySelectorAll(".nav-btn").forEach(button => {
      button.classList.toggle("active", button.dataset.target === id);
    });
  }

  function observeReveals() {
    if (!("IntersectionObserver" in window)) return;
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add("visible");
      });
    }, { threshold: 0.12 });

    document.querySelectorAll(".reveal").forEach(el => observer.observe(el));
  }

  function observeSectionsForNav() {
    if (!("IntersectionObserver" in window)) return;
    const ids = (data.sections || []).map(section => section.id);
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting && ids.includes(entry.target.id)) {
          setActiveNav(entry.target.id);
        }
      });
    }, { threshold: 0.45 });

    ids.forEach(id => {
      const section = document.getElementById(id);
      if (section) observer.observe(section);
    });
  }

  function bindItemCards() {
    document.querySelectorAll(".menu-item").forEach(card => {
      const itemId = card.dataset.itemId;
      const item = (data.sections || [])
        .flatMap(section => section.items || [])
        .find(entry => entry.id === itemId);

      if (!item) return;

      card.addEventListener("click", () => openModal(item));
      card.addEventListener("keydown", event => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          openModal(item);
        }
      });
    });
  }

  function renderSections() {
    const container = document.getElementById("menuSections");
    if (!container) return;
    const sections = data.sections || [];
    container.innerHTML = sections.map(section => `
      <section class="menu-section reveal" id="${section.id}">
        <div class="section-header">
          <div class="section-icon">${section.icon || "☕"}</div>
          <div class="section-label">
            <small>${section.en || ""}</small>
            <h2>${section.fa || ""}</h2>
          </div>
          <div class="section-line"></div>
        </div>
        <div class="items-grid">
          ${(section.items || []).map(renderItem).join("")}
        </div>
      </section>
    `).join("");

    observeReveals();
    observeSectionsForNav();
    bindItemCards();
  }

  function bindScrollButton() {
    const button = document.getElementById("scrollMenuBtn");
    if (!button) return;
    button.addEventListener("click", () => {
      const first = (data.sections || [])[0];
      if (first) document.getElementById(first.id)?.scrollIntoView({ behavior: "smooth" });
    });
  }

  function bindModalEvents() {
    if (!modal) return;

    modal.addEventListener("click", event => {
      if (event.target?.matches("[data-modal-close]")) {
        closeModal();
      }
    });

    document.addEventListener("keydown", event => {
      if (event.key === "Escape") closeModal();
    });
  }

  renderLogo();
  renderShowcaseImage();
  renderHero();
  renderFooter();
  renderNav();
  renderSections();
  bindScrollButton();
  bindModalEvents();
})();
