(function () {
  "use strict";

  // Модалка заявки
  var modal = document.getElementById("modal");
  function closeModal() {
    if (!modal) return;
    modal.classList.remove("is-open");
  }
  if (modal) {
    document.querySelectorAll("[data-open-modal]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        modal.classList.add("is-open");
      });
    });
    modal.addEventListener("click", function (e) {
      if (e.target === modal || e.target.closest("[data-close-modal]")) closeModal();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("is-open")) closeModal();
    });
  }

  document.addEventListener("click", function (e) {
    document.querySelectorAll("details.price-drop[open]").forEach(function (drop) {
      if (!drop.contains(e.target)) drop.removeAttribute("open");
    });
  });

  // Чипы: одиночный выбор внутри группы
  document.querySelectorAll(".chips[data-group]").forEach(function (group) {
    group.addEventListener("click", function (e) {
      var chip = e.target.closest(".chip");
      if (!chip) return;
      group.querySelectorAll(".chip").forEach(function (c) {
        c.classList.remove("is-on");
      });
      chip.classList.add("is-on");
      var holder = group.closest(".filter-field");
      var input = holder ? holder.querySelector("input[type=hidden]") : null;
      if (input) input.value = chip.dataset.value || chip.textContent.trim();
      if (group.getAttribute("data-group") === "country") {
        var form = group.closest("form.filter-panel");
        if (form && typeof form._aiReloadVendors === "function") {
          form._aiReloadVendors();
        }
      }
    });
  });

  // Марка / модель: выпадающие списки из API
  (function () {
    var cfg = typeof aiFilter !== "undefined" ? aiFilter : null;
    if (!cfg || !cfg.ajaxUrl) return;

    function formatCount(n) {
      return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    function fillSelect(sel, items, placeholder, selected) {
      if (!sel) return;
      var withCount = sel.hasAttribute("data-with-count");
      sel.textContent = "";
      var empty = document.createElement("option");
      empty.value = "";
      empty.textContent = placeholder;
      sel.appendChild(empty);
      (items || []).forEach(function (item) {
        var id = item.id || item.label || "";
        var label = item.label || id;
        var count = parseInt(item.count, 10) || 0;
        var opt = document.createElement("option");
        opt.value = id;
        opt.textContent = withCount && count > 0 ? label + " (" + formatCount(count) + ")" : label;
        if (selected && id && String(selected).toLowerCase() === String(id).toLowerCase()) {
          opt.selected = true;
        }
        sel.appendChild(opt);
      });
    }

    function countryOf(form) {
      var hidden = form.querySelector('input[type=hidden][name="country"]');
      return hidden ? hidden.value : "";
    }

    function fetchDict(params) {
      var url = cfg.ajaxUrl + "?action=ai_filter_dict";
      Object.keys(params).forEach(function (k) {
        if (params[k]) {
          url += "&" + encodeURIComponent(k) + "=" + encodeURIComponent(params[k]);
        }
      });
      return fetch(url, { credentials: "same-origin" }).then(function (r) {
        return r.json();
      });
    }

    function bindForm(form) {
      var makeSel = form.querySelector("[data-filter-make]");
      var modelSel = form.querySelector("[data-filter-model]");
      if (!makeSel || !modelSel) return;

      function resetModel(placeholder) {
        fillSelect(modelSel, [], placeholder || modelSel.getAttribute("data-placeholder") || "Сначала марка", "");
        modelSel.disabled = true;
      }

      function loadModels(make) {
        if (!make) {
          resetModel("Сначала марка");
          return;
        }
        modelSel.disabled = true;
        fillSelect(modelSel, [], "Загрузка…", "");
        fetchDict({ country: countryOf(form), make: make })
          .then(function (res) {
            var models = (res && res.success && res.data && res.data.models) || [];
            fillSelect(modelSel, models, modelSel.getAttribute("data-placeholder") || "Модель", "");
            modelSel.disabled = false;
          })
          .catch(function () {
            fillSelect(modelSel, [], modelSel.getAttribute("data-placeholder") || "Модель", "");
            modelSel.disabled = false;
          });
      }

      form._aiReloadVendors = function () {
        makeSel.disabled = true;
        fetchDict({ country: countryOf(form) })
          .then(function (res) {
            var vendors = (res && res.success && res.data && res.data.vendors) || [];
            fillSelect(makeSel, vendors, "Марка", "");
            makeSel.disabled = false;
            resetModel("Сначала марка");
          })
          .catch(function () {
            makeSel.disabled = false;
          });
      };

      makeSel.addEventListener("change", function () {
        loadModels(makeSel.value);
      });
    }

    document.querySelectorAll("form.filter-panel").forEach(bindForm);
  })();

  // Степперы от/до. Серые стрелки браузера скрыты в CSS — работают только кнопки .arr.
  function stepperBounds(input) {
    var step = Number(input.step);
    if (!step || step <= 0) step = 1;
    var min = input.min === "" ? 0 : Number(input.min);
    var max = input.max === "" ? Infinity : Number(input.max);
    return { step: step, min: min, max: max };
  }
  function stepperDecimals(step) {
    var s = String(step);
    var dot = s.indexOf(".");
    return dot < 0 ? 0 : s.length - dot - 1;
  }
  function formatStepper(n, step) {
    var d = stepperDecimals(step);
    if (d <= 0) return String(Math.round(n));
    return Number(n.toFixed(d)).toString();
  }
  function clampStepper(input, n) {
    var b = stepperBounds(input);
    if (!isFinite(n)) n = b.min;
    n = Math.min(b.max, Math.max(b.min, n));
    input.value = formatStepper(n, b.step);
  }
  document.querySelectorAll(".stepper").forEach(function (st) {
    var input = st.querySelector("input");
    if (!input) return;
    var b0 = stepperBounds(input);
    var cur0 = parseFloat(String(input.value).replace(",", "."));
    if (isFinite(cur0) && (cur0 < b0.min || cur0 > b0.max)) clampStepper(input, cur0);
    input.addEventListener("keydown", function (e) {
      if (e.key === "-" || e.code === "Minus" || e.code === "NumpadSubtract") e.preventDefault();
    });
    input.addEventListener("input", function () {
      if (input.value.indexOf("-") === -1) return;
      input.value = input.value.replace(/-/g, "");
    });
    input.addEventListener("blur", function () {
      var raw = String(input.value).trim().replace(",", ".");
      if (raw === "") return;
      clampStepper(input, parseFloat(raw));
    });
    st.querySelectorAll(".arr").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var b = stepperBounds(input);
        var cur = parseFloat(String(input.value).replace(",", "."));
        if (!isFinite(cur)) cur = b.min;
        var next = cur + (btn.dataset.dir === "up" ? b.step : -b.step);
        clampStepper(input, next);
        input.dispatchEvent(new Event("input", { bubbles: true }));
      });
    });
  });

  document.querySelectorAll("[data-price-row]").forEach(function (row) {
    var form = row.closest("form");
    if (!form) return;
    var from = form.querySelector('input[name="budget_from"]');
    var to = form.querySelector('input[name="budget_to"]');
    function mark() {
      var fv = from ? String(from.value).trim() : "";
      var tv = to ? String(to.value).trim() : "";
      row.querySelectorAll(".price-plaque").forEach(function (c) {
        c.classList.toggle("is-on", (c.getAttribute("data-from") || "") === fv && (c.getAttribute("data-to") || "") === tv);
      });
    }
    row.addEventListener("click", function (e) {
      var btn = e.target.closest(".price-plaque");
      if (!btn) return;
      if (from) from.value = btn.getAttribute("data-from") || "";
      if (to) to.value = btn.getAttribute("data-to") || "";
      mark();
    });
    if (from) from.addEventListener("input", mark);
    if (to) to.addEventListener("input", mark);
  });

  document.querySelectorAll("[data-budget-row]").forEach(function (row) {
    var form = row.closest("form");
    if (!form) return;
    var from = form.querySelector("[data-budget-from]");
    var to = form.querySelector("[data-budget-to]");
    row.addEventListener("click", function (e) {
      var btn = e.target.closest(".budget-chip");
      if (!btn) return;
      row.querySelectorAll(".budget-chip").forEach(function (c) { c.classList.remove("is-on"); });
      btn.classList.add("is-on");
      var fv = btn.getAttribute("data-from") || "";
      var tv = btn.getAttribute("data-to") || "";
      if (from) from.value = fv;
      if (to) to.value = tv;
      var panel = document.querySelector("form.filter-panel:not(.filter-quick)");
      if (!panel) return;
      var bf = panel.querySelector('input[name="budget_from"]');
      var bt = panel.querySelector('input[name="budget_to"]');
      if (bf) bf.value = fv;
      if (bt) bt.value = tv;
    });
  });

  // Мобильное меню
  var burger = document.getElementById("burger");
  var drawer = document.getElementById("mobile-menu");
  var overlay = document.getElementById("drawer-overlay");
  function setMenu(open) {
    if (!burger || !drawer || !overlay) return;
    burger.classList.toggle("is-open", open);
    burger.setAttribute("aria-expanded", open ? "true" : "false");
    drawer.classList.toggle("is-open", open);
    overlay.classList.toggle("is-open", open);
    drawer.hidden = !open;
    overlay.hidden = !open;
    drawer.setAttribute("aria-hidden", open ? "false" : "true");
    document.documentElement.classList.toggle("is-menu-open", open);
    document.body.classList.toggle("is-menu-open", open);
  }
  if (burger && drawer && overlay) {
    burger.addEventListener("click", function () {
      setMenu(!drawer.classList.contains("is-open"));
    });
    overlay.addEventListener("click", function () {
      setMenu(false);
    });
    drawer.querySelectorAll("a").forEach(function (a) {
      a.addEventListener("click", function () {
        setMenu(false);
      });
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") setMenu(false);
    });
  }

  var navChat = document.querySelector("[data-nav-chat]");
  var navChatBtn = navChat ? navChat.querySelector("button") : null;
  var navChatMenu = document.getElementById("nav-chat-menu");
  function setChat(open) {
    if (!navChatBtn || !navChatMenu) return;
    navChatBtn.setAttribute("aria-expanded", open ? "true" : "false");
    navChatMenu.hidden = !open;
  }
  if (navChat && navChatBtn && navChatMenu) {
    navChatBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      setChat(navChatMenu.hidden);
    });
    document.addEventListener("click", function (e) {
      if (!navChat.contains(e.target)) setChat(false);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") setChat(false);
    });
  }

  // Маска телефона +7 для заявок (заказ, консультация, футер).
  function aiPhoneNational(value) {
    var d = String(value || "").replace(/\D/g, "");
    if (d.charAt(0) === "8" || d.charAt(0) === "7") d = d.slice(1);
    return d.slice(0, 10);
  }
  function aiPhoneFormat(national) {
    var d = national || "";
    var out = "+7";
    if (!d) return out;
    out += " (" + d.slice(0, 3);
    if (d.length >= 3) out += ")";
    if (d.length > 3) out += " " + d.slice(3, 6);
    if (d.length > 6) out += "-" + d.slice(6, 8);
    if (d.length > 8) out += "-" + d.slice(8, 10);
    return out;
  }
  function aiBindPhone(input) {
    function paint() {
      var national = aiPhoneNational(input.value);
      if (!national) {
        input.value = document.activeElement === input ? "+7" : "";
        return;
      }
      input.value = aiPhoneFormat(national);
    }
    input.addEventListener("focus", function () {
      if (!aiPhoneNational(input.value)) input.value = "+7";
      input.setCustomValidity("");
    });
    input.addEventListener("blur", function () {
      if (!aiPhoneNational(input.value)) {
        input.value = "";
        input.setCustomValidity("");
      }
    });
    input.addEventListener("keydown", function (e) {
      if (e.ctrlKey || e.metaKey || e.altKey) return;
      if (e.key.length === 1 && !/\d/.test(e.key)) e.preventDefault();
    });
    input.addEventListener("paste", function (e) {
      var clip = e.clipboardData || window.clipboardData;
      if (!clip) return;
      e.preventDefault();
      var national = aiPhoneNational(clip.getData("text") || "");
      input.value = national ? aiPhoneFormat(national) : "+7";
      input.setCustomValidity("");
    });
    input.addEventListener("input", paint);
    input.dataset.aiPhone = "1";
    var form = input.form;
    if (!form || form.dataset.aiPhoneBound) return;
    form.dataset.aiPhoneBound = "1";
    form.addEventListener("submit", function (e) {
      var phones = form.querySelectorAll("input[name='phone']");
      for (var i = 0; i < phones.length; i++) {
        var phone = phones[i];
        if (!phone.dataset.aiPhone) continue;
        var national = aiPhoneNational(phone.value);
        if (national.length !== 10) {
          phone.setCustomValidity("Введите номер полностью");
          phone.reportValidity();
          e.preventDefault();
          return;
        }
        phone.setCustomValidity("");
        phone.value = "7" + national;
      }
    });
  }
  document.querySelectorAll("input[name='phone']").forEach(aiBindPhone);

  // Цели Метрики: форма, телефон, Telegram (имена целей — в кабинете Метрики).
  function aiReachGoal(name) {
    var id = window.AI_METRIKA_ID;
    if (!id || typeof window.ym !== "function") return;
    try {
      window.ym(id, "reachGoal", name);
    } catch (e) {}
  }

  // Заявка: блокируем кнопку на отправке + цель lead_form
  var lead = document.getElementById("lead");
  if (lead) {
    lead.addEventListener("submit", function () {
      aiReachGoal("lead_form");
      var btn = lead.querySelector("button[type=submit]");
      if (btn) {
        btn.disabled = true;
        btn.textContent = "Отправляем…";
      }
    });
  }

  document.querySelectorAll('a[href^="tel:"]').forEach(function (a) {
    a.addEventListener("click", function () {
      aiReachGoal("phone_click");
    });
  });
  document.querySelectorAll('a[href*="t.me/"], a[href*="telegram.me/"]').forEach(function (a) {
    a.addEventListener("click", function () {
      aiReachGoal("telegram_click");
    });
  });

  document.querySelectorAll("[data-tabs]").forEach(function (root) {
    var nav = root.querySelectorAll("[data-tab]");
    var panels = root.querySelectorAll("[data-panel]");
    nav.forEach(function (btn) {
      btn.addEventListener("click", function () {
        nav.forEach(function (b) { b.classList.remove("is-on"); });
        panels.forEach(function (p) {
          p.hidden = p.getAttribute("data-panel") !== btn.getAttribute("data-tab");
          p.classList.toggle("is-on", !p.hidden);
        });
        btn.classList.add("is-on");
      });
    });
  });

  document.querySelectorAll("form.filter-quick").forEach(function (form) {
    var chips = form.querySelector("[data-quick-chips]");
    if (chips) {
      chips.addEventListener("click", function (e) {
        var btn = e.target.closest(".qchip");
        if (!btn) return;
        chips.querySelectorAll(".qchip").forEach(function (c) { c.classList.remove("is-on"); });
        btn.classList.add("is-on");
        form.querySelectorAll("[data-qfield]").forEach(function (input) { input.value = ""; });
        var kind = btn.getAttribute("data-quick");
        var value = btn.getAttribute("data-value") || "";
        var field = null;
        if (kind === "hp") field = form.querySelector('[data-qfield="hp"]');
        if (kind === "engine") field = form.querySelector('[data-qfield="engine"]');
        if (kind === "body") field = form.querySelector('[data-qfield="body"]');
        if (kind === "sanction") field = form.querySelector('[data-qfield="sanction"]');
        if (kind === "euro") field = form.querySelector('[data-qfield="euro"]');
        if (field) field.value = value;
      });
    }
    form.addEventListener("submit", function () {
      form.querySelectorAll("input, select").forEach(function (el) {
        if (!el.name || el.value !== "") return;
        el.disabled = true;
      });
    });
  });

  document.querySelectorAll("[data-filter-toggle]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var wrap = btn.closest(".filter-wrap");
      if (!wrap) return;
      var collapsed = wrap.classList.toggle("is-collapsed");
      btn.setAttribute("aria-expanded", collapsed ? "false" : "true");
      btn.textContent = collapsed ? "Показать фильтры" : "Скрыть фильтры";
    });
  });

  document.querySelectorAll("[data-filter-more]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var wrap = btn.closest(".filter-wrap");
      if (!wrap) return;
      var open = wrap.classList.toggle("is-open");
      btn.setAttribute("aria-expanded", open ? "true" : "false");
      btn.textContent = open ? "Скрыть параметры" : "Ещё параметры";
    });
  });

  var mainImg = document.getElementById("lot-main-img");
  var mainBox = document.querySelector(".lot-main-photo");
  var thumbs = Array.prototype.slice.call(document.querySelectorAll(".lot-thumb"));
  var zoomItems = thumbs.map(function (btn) {
    return {
      src: btn.dataset.zoom || btn.dataset.src || "",
      medium: btn.dataset.src || "",
      fit: btn.dataset.fit || "cover",
      btn: btn,
    };
  }).filter(function (item) { return item.src; });
  if (!zoomItems.length && mainImg) {
    zoomItems = [{
      src: mainImg.getAttribute("data-zoom") || mainImg.src || "",
      medium: mainImg.src || "",
      fit: mainBox && mainBox.classList.contains("is-sheet") ? "contain" : "cover",
      btn: null,
    }];
  }
  var zoomIndex = 0;

  function selectThumb(index) {
    var item = zoomItems[index];
    if (!item) return;
    zoomIndex = index;
    thumbs.forEach(function (b) { b.classList.remove("is-on"); });
    if (item.btn) item.btn.classList.add("is-on");
    if (mainImg) {
      if (item.medium) mainImg.src = item.medium;
      mainImg.setAttribute("data-zoom", item.src);
    }
    if (mainBox) mainBox.classList.toggle("is-sheet", item.fit === "contain");
  }

  thumbs.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var idx = zoomItems.findIndex(function (item) { return item.btn === btn; });
      if (idx < 0) return;
      selectThumb(idx);
    });
  });

  var zoom = document.getElementById("lot-zoom");
  var zoomImg = zoom ? zoom.querySelector("img") : null;
  var zoomPrev = zoom ? zoom.querySelector(".lot-zoom-prev") : null;
  var zoomNext = zoom ? zoom.querySelector(".lot-zoom-next") : null;
  var zoomCount = zoom ? zoom.querySelector(".lot-zoom-count") : null;
  var zoomOpen = false;

  function renderZoom() {
    var item = zoomItems[zoomIndex];
    if (!item || !zoomImg) return;
    zoomImg.src = item.src;
    var multi = zoomItems.length > 1;
    if (zoomPrev) zoomPrev.hidden = !multi;
    if (zoomNext) zoomNext.hidden = !multi;
    if (zoomCount) {
      zoomCount.hidden = !multi;
      zoomCount.textContent = (zoomIndex + 1) + " / " + zoomItems.length;
    }
  }

  function openZoom(index) {
    if (!zoom || !zoomItems.length) return;
    zoomIndex = ((index % zoomItems.length) + zoomItems.length) % zoomItems.length;
    selectThumb(zoomIndex);
    renderZoom();
    zoom.hidden = false;
    zoomOpen = true;
    document.body.classList.add("is-menu-open");
  }

  function closeZoom() {
    if (!zoom) return;
    zoom.hidden = true;
    zoomOpen = false;
    document.body.classList.remove("is-menu-open");
  }

  function stepZoom(dir) {
    if (!zoomOpen || zoomItems.length < 2) return;
    openZoom(zoomIndex + dir);
  }

  document.querySelectorAll("[data-zoom-open]").forEach(function (box) {
    box.addEventListener("click", function (e) {
      if (e.target.closest(".lot-thumb")) return;
      var start = 0;
      if (mainImg) {
        var cur = mainImg.getAttribute("data-zoom") || mainImg.src || "";
        var found = zoomItems.findIndex(function (item) { return item.src === cur || item.medium === cur; });
        if (found >= 0) start = found;
      }
      openZoom(start);
    });
  });
  if (zoom) {
    if (zoomPrev) zoomPrev.addEventListener("click", function (e) { e.stopPropagation(); stepZoom(-1); });
    if (zoomNext) zoomNext.addEventListener("click", function (e) { e.stopPropagation(); stepZoom(1); });
    zoom.addEventListener("click", function (e) {
      if (e.target === zoom || e.target.classList.contains("lot-zoom-close")) closeZoom();
    });
    var touchX = null;
    zoom.addEventListener("touchstart", function (e) {
      if (!e.changedTouches || !e.changedTouches.length) return;
      touchX = e.changedTouches[0].clientX;
    }, { passive: true });
    zoom.addEventListener("touchend", function (e) {
      if (touchX === null || !e.changedTouches || !e.changedTouches.length) return;
      var dx = e.changedTouches[0].clientX - touchX;
      touchX = null;
      if (Math.abs(dx) < 40) return;
      stepZoom(dx < 0 ? 1 : -1);
    }, { passive: true });
    document.addEventListener("keydown", function (e) {
      if (!zoomOpen) return;
      if (e.key === "Escape") closeZoom();
      if (e.key === "ArrowLeft") { e.preventDefault(); stepZoom(-1); }
      if (e.key === "ArrowRight") { e.preventDefault(); stepZoom(1); }
    });
  }

  document.querySelectorAll("img").forEach(function (el) {
    el.addEventListener("load", function () {
      if ((el.naturalWidth === 319 || el.naturalWidth === 1) && /tru\.ru|ajes\.com/.test(el.src)) {
        var wrap = el.closest(".lot-thumb");
        if (wrap) wrap.hidden = true;
      }
    });
  });

  function setTheme(theme) {
    var next = theme === "dark" ? "dark" : "light";
    document.documentElement.setAttribute("data-theme", next);
    try { localStorage.setItem("ai-theme", next); } catch (e) {}
    document.querySelectorAll("[data-theme-toggle]").forEach(function (btn) {
      btn.setAttribute("aria-label", next === "light" ? "Тёмная тема" : "Светлая тема");
    });
  }
  document.querySelectorAll("[data-theme-toggle]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var cur = document.documentElement.getAttribute("data-theme") === "dark" ? "dark" : "light";
      setTheme(cur === "light" ? "dark" : "light");
    });
  });
  setTheme(document.documentElement.getAttribute("data-theme") === "dark" ? "dark" : "light");

  if (new URLSearchParams(window.location.search).get("lead") === "sent") {
    if (modal) modal.classList.add("is-open");
    var ok = document.querySelector(".lead-ok");
    if (ok) ok.hidden = false;
    window.history.replaceState({}, "", window.location.pathname);
  }

  // Карусель статьи журнала
  document.querySelectorAll("[data-carousel]").forEach(function (root) {
    var slides = Array.prototype.slice.call(root.querySelectorAll("[data-slide]"));
    var dots = Array.prototype.slice.call(root.querySelectorAll("[data-carousel-goto]"));
    if (!slides.length) return;
    var i = 0;
    function show(n) {
      i = (n + slides.length) % slides.length;
      slides.forEach(function (s, idx) {
        var on = idx === i;
        s.classList.toggle("is-on", on);
        s.hidden = !on;
      });
      dots.forEach(function (d, idx) {
        d.classList.toggle("is-on", idx === i);
      });
    }
    var prev = root.querySelector("[data-carousel-prev]");
    var next = root.querySelector("[data-carousel-next]");
    if (prev) prev.addEventListener("click", function () { show(i - 1); });
    if (next) next.addEventListener("click", function () { show(i + 1); });
    dots.forEach(function (d) {
      d.addEventListener("click", function () {
        show(Number(d.getAttribute("data-carousel-goto")) || 0);
      });
    });
  });

  // Видео-отзывы
  var reviewModal = document.getElementById("review-modal");
  var reviewVideo = document.getElementById("review-video");
  function closeReview() {
    if (!reviewModal) return;
    reviewModal.hidden = true;
    document.body.classList.remove("is-menu-open");
    if (reviewVideo) {
      reviewVideo.pause();
      reviewVideo.removeAttribute("src");
      reviewVideo.load();
    }
  }
  function openReview(btn) {
    if (!reviewModal || !reviewVideo) return;
    var src = btn.getAttribute("data-video") || "";
    if (!src) return;
    reviewVideo.poster = btn.getAttribute("data-poster") || "";
    reviewVideo.src = src;
    var t = document.getElementById("review-modal-title");
    var q = document.getElementById("review-modal-quote");
    var d = document.getElementById("review-modal-desc");
    if (t) t.textContent = btn.getAttribute("data-title") || "";
    if (q) q.textContent = btn.getAttribute("data-quote") ? "«" + btn.getAttribute("data-quote") + "»" : "";
    if (d) d.textContent = btn.getAttribute("data-desc") || "";
    reviewModal.hidden = false;
    document.body.classList.add("is-menu-open");
    reviewVideo.play().catch(function () {});
  }
  document.querySelectorAll("[data-review-open]").forEach(function (btn) {
    btn.addEventListener("click", function () { openReview(btn); });
  });
  if (reviewModal) {
    reviewModal.addEventListener("click", function (e) {
      if (e.target === reviewModal || e.target.classList.contains("review-modal-close")) closeReview();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeReview();
    });
  }

  function bindLotRow(row, prev, next) {
    if (!row || !prev || !next) return;
    function step() {
      var card = row.querySelector(".lot-card");
      var gap = parseFloat(window.getComputedStyle(row).columnGap) || 16;
      return (card ? card.getBoundingClientRect().width : 280) + gap;
    }
    function sync() {
      var max = row.scrollWidth - row.clientWidth;
      prev.disabled = row.scrollLeft <= 2;
      next.disabled = max <= 2 || row.scrollLeft >= max - 2;
    }
    function go(dir) {
      var move = step();
      var max = Math.max(0, row.scrollWidth - row.clientWidth);
      var index = dir > 0
        ? Math.floor((row.scrollLeft + 2) / move)
        : Math.ceil((row.scrollLeft - 2) / move);
      var left = (index + dir) * move;
      if (left < 0) left = 0;
      if (left > max) left = max;
      row.scrollTo({ left: left, behavior: "smooth" });
    }
    prev.addEventListener("click", function () { go(-1); });
    next.addEventListener("click", function () { go(1); });
    row.addEventListener("scroll", sync, { passive: true });
    window.addEventListener("resize", sync);
    sync();
  }
  bindLotRow(
    document.querySelector("#hits .hits-row"),
    document.querySelector('#hits .hits-arrow[data-hits-dir="-1"]'),
    document.querySelector('#hits .hits-arrow[data-hits-dir="1"]')
  );
  bindLotRow(
    document.querySelector("#lots .hits-row"),
    document.querySelector('#lots .hits-arrow[data-lots-dir="-1"]'),
    document.querySelector('#lots .hits-arrow[data-lots-dir="1"]')
  );
})();

/* Линия «Как идёт сделка»: маркер едет 01→02→03, затем 04→05. */
(function () {
  "use strict";

  var root = document.querySelector(".steps.steps-deal");
  if (!root) return;

  var NS = "http://www.w3.org/2000/svg";
  var rail = ensureSvg("deal-rail");
  var markerRail = ensureSvg("deal-marker-rail");
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)");
  var segments = [];
  var total = 0;
  var marker = null;
  var steps = root.querySelectorAll(".step");
  var current = -1;
  var origin = 0;
  var running = false;
  var seen = false;
  var CYCLE = 12000;
  var FADE = 480;

  function ensureSvg(cls) {
    var svg = root.querySelector("svg." + cls);
    if (!svg) {
      svg = document.createElementNS(NS, "svg");
      svg.setAttribute("class", cls);
      svg.setAttribute("aria-hidden", "true");
      svg.setAttribute("focusable", "false");
      root.appendChild(svg);
    }
    return svg;
  }

  function motionOk() {
    return !reduce.matches;
  }

  function n(v) {
    return Math.round(v * 10) / 10;
  }

  function clear(svg) {
    while (svg.firstChild) svg.removeChild(svg.firstChild);
  }

  function measure() {
    var box = root.getBoundingClientRect();
    var chips = root.querySelectorAll(".step .chip-num");
    var items = [];
    Array.prototype.forEach.call(chips, function (chip, index) {
      var r = chip.getBoundingClientRect();
      items.push({
        index: index,
        left: r.left - box.left,
        right: r.right - box.left,
        top: r.top - box.top,
        bottom: r.bottom - box.top,
        cx: r.left - box.left + r.width / 2,
        cy: r.top - box.top + r.height / 2
      });
    });
    var rows = [];
    items.forEach(function (item) {
      var row = rows.length ? rows[rows.length - 1] : null;
      if (!row || Math.abs(item.cy - row.cy) > 12) {
        rows.push({ cy: item.cy, chips: [item] });
      } else {
        row.chips.push(item);
      }
    });
    return { items: items, rows: rows, w: box.width, h: box.height };
  }

  function addSeg(list, x1, y1, x2, y2, to, dir) {
    var len = Math.hypot(x2 - x1, y2 - y1);
    if (len < 18) return;
    list.push({ x1: x1, y1: y1, x2: x2, y2: y2, len: len, to: to, dir: dir });
  }

  function build(geo) {
    var list = [];
    var items = geo.items;
    var rows = geo.rows;
    var edge = 8;
    if (!items.length) return list;

    var stacked = rows.length === items.length && items.length > 1;
    if (stacked) {
      for (var i = 0; i < items.length - 1; i++) {
        addSeg(list, items[i].cx, items[i].bottom - 1, items[i + 1].cx, items[i + 1].top + 1, items[i + 1].index, "down");
      }
      return list;
    }

    rows.forEach(function (row, ri) {
      var chips = row.chips;
      if (ri > 0) {
        var first = chips[0];
        var prev = rows[0].chips;
        var gap = prev.length > 1 ? prev[1].left - prev[0].right : 140;
        var lead = Math.min(Math.max(0, first.left - 12), gap * 0.78);
        if (lead >= 48) {
          addSeg(list, first.left - lead, first.cy, first.left + 1, first.cy, first.index, "right");
        }
      }
      for (var c = 0; c < chips.length - 1; c++) {
        var a = chips[c];
        var b = chips[c + 1];
        if (b.left > a.right + 16) {
          addSeg(list, a.right - 1, a.cy, b.left + 1, b.cy, b.index, "right");
        }
      }
      if (ri < rows.length - 1) {
        var last = chips[chips.length - 1];
        var nextFirst = rows[ri + 1].chips[0];
        var nextGap = rows[0].chips.length > 1 ? rows[0].chips[1].left - rows[0].chips[0].right : 140;
        var nextLead = Math.min(Math.max(0, nextFirst.left - 12), nextGap * 0.78);
        var x2 = Math.min(geo.w - edge, last.right + Math.max(56, nextGap * 0.92));
        if (x2 < last.right + 28) x2 = Math.min(geo.w - edge, last.right + 72);
        addSeg(list, last.right - 1, last.cy, x2, last.cy, nextLead >= 48 ? null : nextFirst.index, "right");
      }
    });

    items.forEach(function (item) {
      if (item.index === 0) return;
      var linked = list.some(function (seg) {
        var points = [[seg.x1, seg.y1], [seg.x2, seg.y2]];
        return points.some(function (point) {
          var nearX = Math.abs(point[0] - item.left) < 8 || Math.abs(point[0] - item.right) < 8 || Math.abs(point[0] - item.cx) < 8;
          var nearY = Math.abs(point[1] - item.cy) < 8 || Math.abs(point[1] - item.top) < 8 || Math.abs(point[1] - item.bottom) < 8;
          return nearX && nearY;
        });
      });
      if (linked) return;
      var room = geo.w - item.right;
      if (room < 48) return;
      var x2 = Math.min(geo.w - edge, item.right + Math.max(88, room * 0.72));
      addSeg(list, item.right - 1, item.cy, x2, item.cy, item.index, "right");
    });
    return list;
  }

  function chevron(seg) {
    if (seg.len < 36) return;
    var mx = (seg.x1 + seg.x2) / 2;
    var my = (seg.y1 + seg.y2) / 2;
    var path = document.createElementNS(NS, "path");
    var d;
    if (seg.dir === "down") {
      d = "M " + n(mx - 4.5) + " " + n(my - 2.5) + " L " + n(mx) + " " + n(my + 4) + " L " + n(mx + 4.5) + " " + n(my - 2.5);
    } else if (seg.dir === "left") {
      d = "M " + n(mx + 2.5) + " " + n(my - 4.5) + " L " + n(mx - 4) + " " + n(my) + " L " + n(mx + 2.5) + " " + n(my + 4.5);
    } else {
      d = "M " + n(mx - 2.5) + " " + n(my - 4.5) + " L " + n(mx + 4) + " " + n(my) + " L " + n(mx - 2.5) + " " + n(my + 4.5);
    }
    path.setAttribute("class", "deal-chevron");
    path.setAttribute("d", d);
    rail.appendChild(path);
  }

  function setCurrent(index) {
    if (!motionOk() || index === current) return;
    current = index;
    Array.prototype.forEach.call(steps, function (step, i) {
      step.classList.toggle("is-current", i === index);
    });
  }

  function clearCurrent() {
    current = -1;
    Array.prototype.forEach.call(steps, function (step) {
      step.classList.remove("is-current");
    });
  }

  function stepAt(dist) {
    var acc = 0;
    var step = 0;
    for (var i = 0; i < segments.length; i++) {
      var seg = segments[i];
      var end = acc + seg.len;
      if (dist < end || i === segments.length - 1) {
        var along = seg.len ? (dist - acc) / seg.len : 1;
        if (seg.to != null && along >= 0.84) return seg.to;
        return step;
      }
      acc = end;
      if (seg.to != null) step = seg.to;
    }
    return step;
  }

  function capsule(dist) {
    var acc = 0;
    var seg = segments[0];
    var local = 0;
    for (var i = 0; i < segments.length; i++) {
      seg = segments[i];
      if (dist <= acc + seg.len || i === segments.length - 1) {
        local = Math.min(seg.len, Math.max(0, dist - acc));
        break;
      }
      acc += seg.len;
    }
    var half = Math.min(11, seg.len / 2);
    var c = Math.max(half, Math.min(seg.len - half, local));
    var t1 = (c - half) / seg.len;
    var t2 = (c + half) / seg.len;
    return {
      x1: seg.x1 + (seg.x2 - seg.x1) * t1,
      y1: seg.y1 + (seg.y2 - seg.y1) * t1,
      x2: seg.x1 + (seg.x2 - seg.x1) * t2,
      y2: seg.y1 + (seg.y2 - seg.y1) * t2
    };
  }

  function place(dist, opacity) {
    if (!marker) return;
    var cap = capsule(Math.max(0, Math.min(total, dist)));
    marker.setAttribute("x1", n(cap.x1));
    marker.setAttribute("y1", n(cap.y1));
    marker.setAttribute("x2", n(cap.x2));
    marker.setAttribute("y2", n(cap.y2));
    marker.style.opacity = String(opacity);
  }

  function draw() {
    steps = root.querySelectorAll(".step");
    var geo = measure();
    if (geo.w < 40 || geo.h < 40 || !geo.items.length) return;
    segments = build(geo);
    total = segments.reduce(function (sum, seg) { return sum + seg.len; }, 0);
    var box = "0 0 " + n(geo.w) + " " + n(geo.h);
    rail.setAttribute("viewBox", box);
    markerRail.setAttribute("viewBox", box);
    clear(rail);
    clear(markerRail);
    marker = null;
    if (!segments.length) return;
    var line = document.createElementNS(NS, "path");
    line.setAttribute("class", "deal-rail-line");
    line.setAttribute("d", segments.map(function (seg) {
      return "M " + n(seg.x1) + " " + n(seg.y1) + " L " + n(seg.x2) + " " + n(seg.y2);
    }).join(" "));
    rail.appendChild(line);
    segments.forEach(chevron);
    if (!motionOk()) {
      clearCurrent();
      return;
    }
    marker = document.createElementNS(NS, "line");
    marker.setAttribute("class", "deal-marker");
    markerRail.appendChild(marker);
    place(0, 1);
    setCurrent(0);
  }

  function frame(now) {
    if (!running) return;
    if (!motionOk() || !segments.length || !marker) {
      running = false;
      return;
    }
    var t = (now - origin) % CYCLE;
    var travel = CYCLE - FADE;
    var opacity = 1;
    var dist;
    if (t < 220) opacity = t / 220;
    if (t >= travel) {
      dist = total;
      opacity = Math.max(0, 1 - (t - travel) / FADE);
      var last = segments[segments.length - 1];
      setCurrent(last.to != null ? last.to : steps.length - 1);
    } else {
      dist = (t / travel) * total;
      setCurrent(stepAt(dist));
    }
    place(dist, opacity);
    requestAnimationFrame(frame);
  }

  function start() {
    if (running || !motionOk()) return;
    running = true;
    origin = performance.now();
    requestAnimationFrame(frame);
  }

  function stop() {
    running = false;
  }

  var resizeTick = 0;
  if (window.ResizeObserver) {
    var observer = new ResizeObserver(function () {
      cancelAnimationFrame(resizeTick);
      resizeTick = requestAnimationFrame(draw);
    });
    observer.observe(root);
  } else {
    window.addEventListener("resize", function () {
      cancelAnimationFrame(resizeTick);
      resizeTick = requestAnimationFrame(draw);
    });
  }

  if (window.IntersectionObserver) {
    var io = new IntersectionObserver(function (entries) {
      seen = entries.some(function (entry) { return entry.isIntersecting; });
      if (seen && !document.hidden) start();
      else stop();
    }, { threshold: 0.2 });
    io.observe(root);
  } else {
    seen = true;
    start();
  }

  document.addEventListener("visibilitychange", function () {
    if (document.hidden) stop();
    else if (seen) start();
  });

  if (reduce.addEventListener) {
    reduce.addEventListener("change", function () {
      draw();
      if (motionOk() && seen && !document.hidden) start();
      else stop();
    });
  }

  draw();
})();
