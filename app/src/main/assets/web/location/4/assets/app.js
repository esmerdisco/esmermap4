// ================== AUTH GUARD ==================
const authToken = localStorage.getItem("auth_token");
if (!authToken) {
  window.location.href = "login.html";
}

// ================== HELPERS ==================
const $ = (id) => document.getElementById(id);

const addressInput = $("address");
const btnSearch = $("btnSearch");
const btnMyLocation = $("btnMyLocation");
const statusEl = $("status");

let map, view;
let destLayer, myLayer;
let destination = null;
let myLocation = null;

// ================== STATUS ==================
function setStatus(msg) {
  if (statusEl) statusEl.textContent = msg || "";
}

// ================== API FETCH ==================
async function apiFetch(url, options = {}) {
  const token = localStorage.getItem("auth_token");

  const res = await fetch(url, {
    ...options,
    headers: {
      ...(options.headers || {}),
      "Authorization": "Bearer " + token,
      "Accept": "application/json"
    }
  });

  if (res.status === 401) {
    localStorage.removeItem("auth_token");
    window.location.href = "login.html";
    return null;
  }

  return res;
}

// ================== GEOCODE ==================
async function geocodeAddress(q) {
  const res = await apiFetch(
    `api/geocode.php?address=${encodeURIComponent(q)}`
  );
  if (!res) return null;

  if (!res.ok) {
    const txt = await res.text().catch(() => "");
    throw new Error(`خطا از سرور (${res.status}) ${txt}`);
  }

  return res.json();
}

// ================== MAP INIT ==================
function initMap() {
  if (!window.ol) {
    setStatus("خطا در بارگذاری نقشه");
    return;
  }

  const NESHAN_WEB_KEY = "web.a84c222a84af4e1fb4fb76ad1fcf1265";

  view = new ol.View({
    center: ol.proj.fromLonLat([51.3890, 35.6892]),
    zoom: 12
  });

  map = new ol.Map({
    target: "map",
    maptype: "neshan",
    key: NESHAN_WEB_KEY,
    poi: true,
    traffic: false,
    view
  });

  // ---------- Destination Layer ----------
  destLayer = new ol.layer.Vector({
    source: new ol.source.Vector(),
    style: new ol.style.Style({
      image: new ol.style.Icon({
        src:
          "data:image/svg+xml;utf8," +
          encodeURIComponent(`
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48">
            <path fill="#e11d48" d="M24 2c-7 0-13 6-13 13 0 10 13 31 13 31s13-21 13-31c0-7-6-13-13-13z"/>
            <circle cx="24" cy="15" r="5" fill="#fff"/>
          </svg>
        `),
        anchor: [0.5, 1],
        scale: 0.9
      })
    })
  });

  // ---------- My Location Layer ----------
  myLayer = new ol.layer.Vector({
    source: new ol.source.Vector(),
    style: new ol.style.Style({
      image: new ol.style.Circle({
        radius: 7,
        fill: new ol.style.Fill({ color: "#2563eb" }),
        stroke: new ol.style.Stroke({ color: "#ffffff", width: 2 })
      })
    })
  });

  map.addLayer(myLayer);
  map.addLayer(destLayer);

  map.on("singleclick", (evt) => {
    const feature = map.forEachFeatureAtPixel(evt.pixel, (f) => f);
    if (feature && feature.get("kind") === "destination") {
      openNavigation();
    }
  });

  setTimeout(() => map.updateSize(), 0);
  setTimeout(() => map.updateSize(), 300);
  window.addEventListener("resize", () => map.updateSize());

  setStatus("آدرس را وارد کنید");
}

// ================== MARKERS ==================
function setDestination(lat, lng, label) {
  destination = { lat, lng, label };
  destLayer.getSource().clear();

  const f = new ol.Feature({
    geometry: new ol.geom.Point(ol.proj.fromLonLat([lng, lat]))
  });
  f.set("kind", "destination");

  destLayer.getSource().addFeature(f);
  view.animate({ center: ol.proj.fromLonLat([lng, lat]), zoom: 16, duration: 400 });

  setStatus("برای مسیریابی روی پین بزن");
}

function setMyLocation(lat, lng) {
  myLocation = { lat, lng };
  myLayer.getSource().clear();

  const f = new ol.Feature({
    geometry: new ol.geom.Point(ol.proj.fromLonLat([lng, lat]))
  });

  myLayer.getSource().addFeature(f);
}

// ================== NAVIGATION ==================
function openNavigation() {
  if (!destination) return;

  const { lat, lng, label } = destination;

  if (myLocation) {
    window.location.href =
      `https://www.google.com/maps/dir/?api=1` +
      `&origin=${myLocation.lat},${myLocation.lng}` +
      `&destination=${lat},${lng}`;
  } else {
    window.location.href =
      `geo:${lat},${lng}?q=${lat},${lng}(${encodeURIComponent(label)})`;
  }
}

// ================== UI EVENTS ==================
btnSearch.addEventListener("click", async () => {
  const q = addressInput.value.trim();
  if (!q) return setStatus("آدرس را وارد کنید");

  setStatus("در حال جستجو...");
  btnSearch.disabled = true;

  try {
    const data = await geocodeAddress(q);
    if (!data) return;

    if (data.status === "OK" && data.location) {
      setDestination(
        data.location.y,
        data.location.x,
        data.formatted_address || q
      );
    } else {
      setStatus("نتیجه‌ای پیدا نشد");
    }
  } catch (e) {
    setStatus(e.message || "خطا رخ داد");
  } finally {
    btnSearch.disabled = false;
  }
});

addressInput.addEventListener("keydown", (e) => {
  if (e.key === "Enter") btnSearch.click();
});

btnMyLocation.addEventListener("click", () => {
  if (!navigator.geolocation) {
    setStatus("GPS پشتیبانی نمی‌شود");
    return;
  }

  setStatus("در حال گرفتن لوکیشن...");
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      setMyLocation(pos.coords.latitude, pos.coords.longitude);
      view.animate({
        center: ol.proj.fromLonLat([pos.coords.longitude, pos.coords.latitude]),
        zoom: 15,
        duration: 400
      });
      setStatus("لوکیشن شما ثبت شد");
    },
    () => setStatus("اجازه GPS داده نشد"),
    { enableHighAccuracy: true }
  );
});

// ================== ADDRESS BOTTOM SHEET ==================
const addrSheet = document.getElementById("addrSheet");
const addrSheetHandle = document.getElementById("addrSheetHandle");
const addrSheetList = document.getElementById("addrSheetList");
const addrSheetEmpty = document.getElementById("addrSheetEmpty");
const addrSheetRefresh = document.getElementById("addrSheetRefresh");

// Selected customer bar (top)
const selectedCustomerEl = document.getElementById("selectedCustomer");
const scNameEl = document.getElementById("scName");
const scPhoneEl = document.getElementById("scPhone");
const scAddrEl = document.getElementById("scAddr");

function escHtml(s) {
  return String(s).replace(/[&<>"']/g, (m) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;"
  }[m]));
}

function normalizePhone(raw) {
  const s = String(raw || "").trim();
  if (!s) return "";
  return s.replace(/[^0-9+]/g, "");
}

function setSelectedCustomer({ name, phoneRaw, phoneNorm, address }) {
  if (!selectedCustomerEl || !scNameEl || !scPhoneEl || !scAddrEl) return;

  const safeName = String(name || "بدون نام").trim() || "بدون نام";
  const safeAddr = String(address || "").trim();
  const safePhoneRaw = String(phoneRaw || "").trim();
  const safePhoneNorm = normalizePhone(phoneNorm || phoneRaw);

  scNameEl.textContent = safeName;
  scAddrEl.textContent = safeAddr;

  if (safePhoneNorm) {
    scPhoneEl.style.display = "inline-block";
    scPhoneEl.textContent = safePhoneRaw || safePhoneNorm;
    scPhoneEl.setAttribute("href", `tel:${safePhoneNorm}`);
  } else {
    scPhoneEl.style.display = "none";
    scPhoneEl.textContent = "";
    scPhoneEl.setAttribute("href", "#");
  }

  selectedCustomerEl.style.display = "block";
}

async function loadAddressSheet() {
  if (!addrSheetList) return;

  try {
    const res = await apiFetch("api/addresses.php");
    if (!res) return;

    if (!res.ok) {
      const t = await res.text().catch(() => "");
      console.warn("addresses.php not ok", res.status, t);
      return;
    }

    const data = await res.json();
    const items = Array.isArray(data.items) ? data.items : [];
    renderAddressSheet(items);
  } catch (e) {
    console.error("loadAddressSheet error:", e);
  }
}

function renderAddressSheet(items) {
  if (!addrSheetList || !addrSheetEmpty) return;

  if (!items.length) {
    addrSheetList.innerHTML = "";
    addrSheetEmpty.style.display = "block";
    return;
  }

  addrSheetEmpty.style.display = "none";

  addrSheetList.innerHTML = items.map(it => {
    const addrHidden = escHtml(it.address || "");
    const customerText = it.tag ? escHtml(it.tag) : "بدون نام";

    const phoneRaw = it.city ? String(it.city) : "";
    const phoneNorm = normalizePhone(phoneRaw);

    const phoneHtml = phoneNorm
      ? `<a class="sheet-phone" href="tel:${escHtml(phoneNorm)}" data-role="tel">${escHtml(phoneRaw)}</a>`
      : `<span style="color:#888; font-weight:900;">—</span>`;

    return `
      <div class="sheet-item">
        <div class="sheet-row">
          <button
            type="button"
            class="sheet-customer-btn"
            data-role="search"
            data-q="${addrHidden}"
            data-name="${customerText}"
            data-phone-raw="${escHtml(phoneRaw)}"
            data-phone-norm="${escHtml(phoneNorm)}"
          >
            ${customerText}
          </button>
          <div class="sheet-phone-pill">
            ${phoneHtml}
          </div>
        </div>
      </div>
    `;
  }).join("");
}

if (addrSheetList) {
  addrSheetList.addEventListener("click", (e) => {
    const tel = e.target.closest('a[data-role="tel"]');
    if (tel) return;

    const node = e.target.closest('[data-role="search"]');
    if (!node) return;

    const q = node.getAttribute("data-q") || "";
    if (!q) return;

    // 1) close the bottom sheet
    if (typeof window.addrSheetCollapse === "function") {
      window.addrSheetCollapse();
    }

    // 2) show selected customer's name/phone/address on top
    const name = node.getAttribute("data-name") || "";
    const phoneRaw = node.getAttribute("data-phone-raw") || "";
    const phoneNorm = node.getAttribute("data-phone-norm") || "";
    setSelectedCustomer({ name, phoneRaw, phoneNorm, address: q });

    addressInput.value = q;
    btnSearch.click();
  });
}

// ✅ Drag / snap (and start collapsed reliably)
(function initSheetDrag() {
  if (!addrSheet || !addrSheetHandle) return;

  const SNAP_OPEN = 0;
  const PEEK = 64;

  let maxY = 0;
  let startY = 0;
  let startOffset = 0;
  let dragging = false;

  function recalc() {
    const rect = addrSheet.getBoundingClientRect();
    maxY = Math.max(0, rect.height - PEEK);
  }

  function setOffset(y) {
    const clamped = Math.max(0, Math.min(maxY, y));
    addrSheet.style.setProperty("--sheet-y", `${clamped}px`);
  }

  function getOffset() {
    const v = getComputedStyle(addrSheet).getPropertyValue("--sheet-y").trim();
    const n = parseFloat(v.replace("px", ""));
    return Number.isFinite(n) ? n : maxY;
  }

  function collapse() { setOffset(maxY); }
  function expand() { setOffset(0); }

  // expose for other UI actions (e.g. selecting customer from list)
  window.addrSheetCollapse = collapse;
  window.addrSheetExpand = expand;

  function onStart(clientY) {
    dragging = true;
    startY = clientY;
    startOffset = getOffset();
    addrSheet.style.transition = "none";
  }

  function onMove(clientY) {
    if (!dragging) return;
    const dy = clientY - startY;
    setOffset(startOffset + dy);
  }

  function onEnd() {
    if (!dragging) return;
    dragging = false;
    addrSheet.style.transition = "";

    const y = getOffset();
    if (y > maxY * 0.5) collapse();
    else expand();
  }

  addrSheetHandle.addEventListener("click", () => {
    recalc();
    const y = getOffset();
    if (y > maxY * 0.5) expand();
    else collapse();
  });

  addrSheetHandle.addEventListener("touchstart", (e) => onStart(e.touches[0].clientY), { passive: true });
  addrSheetHandle.addEventListener("touchmove", (e) => onMove(e.touches[0].clientY), { passive: true });
  addrSheetHandle.addEventListener("touchend", onEnd);

  addrSheetHandle.addEventListener("mousedown", (e) => { e.preventDefault(); onStart(e.clientY); });
  window.addEventListener("mousemove", (e) => onMove(e.clientY));
  window.addEventListener("mouseup", onEnd);

  // ✅ Reliable start collapsed: run twice after layout settles
  requestAnimationFrame(() => {
    recalc();
    collapse();
    setTimeout(() => { recalc(); collapse(); }, 250);
    setTimeout(() => { recalc(); collapse(); }, 900);
  });

  window.addEventListener("resize", () => {
    recalc();
    collapse();
  });
})();

if (addrSheetRefresh) {
  addrSheetRefresh.addEventListener("click", (e) => {
    e.preventDefault();
    loadAddressSheet();
  });
}

// ================== AUTO SEARCH (?q=) ==================
(function autoSearchFromQuery() {
  try {
    const q = new URLSearchParams(window.location.search).get("q");
    if (!q) return;
    addressInput.value = q;
    setTimeout(() => btnSearch.click(), 250);
  } catch (err) {
    console.error("autoSearchFromQuery error:", err);
  }
})();

// ================== INIT ==================
initMap();
loadAddressSheet();
