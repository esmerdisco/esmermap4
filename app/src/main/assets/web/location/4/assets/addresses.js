// ====== Guard: if token missing, go to login
if (!localStorage.getItem("auth_token")) {
  window.location.href = "login.html";
}

const $ = (id) => document.getElementById(id);
const statusEl = $("status");
const listEl = $("list");

function setStatus(msg) {
  statusEl.textContent = msg || "";
}

async function api(url, options = {}) {
  const token = localStorage.getItem("auth_token");

  const res = await fetch(url, {
    ...options,
    headers: {
      ...(options.headers || {}),
      "Authorization": "Bearer " + token,
      "Accept": "application/json",
      ...(options.body ? { "Content-Type": "application/json" } : {})
    }
  });

  if (res.status === 401) {
    localStorage.removeItem("auth_token");
    window.location.href = "login.html";
    return null;
  }

  return res;
}

function esc(s) {
  return String(s).replace(/[&<>"']/g, (m) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;"
  }[m]));
}

function normalizeLines(text) {
  return text.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
}

async function loadList() {
  setStatus("در حال دریافت لیست...");
  const res = await api("api/addresses.php");
  if (!res) return;

  const data = await res.json();
  const items = data.items || [];
  render(items);
  setStatus(items.length ? "" : "لیست خالی است");
}

function render(items) {
  if (!items.length) {
    listEl.innerHTML = `<div style="color:#666;font-size:13px">هنوز آدرسی ثبت نشده.</div>`;
    return;
  }

  listEl.innerHTML = items.map(it => `
    <div class="item">
      <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start">
        <div style="flex:1;line-height:1.7">
          ${esc(it.address)}
          <div class="meta">
            ${it.city ? `شماره تماس: ${esc(it.city)} • ` : ""}
            ${it.tag ? `مشتری: ${esc(it.tag)} • ` : ""}
            ${it.created_at ? `ثبت: ${esc(it.created_at)}` : ""}
          </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btnLite" data-act="go" data-q="${esc(it.address)}">نمایش روی نقشه</button>
          <button class="btnLite btnDanger" data-act="del" data-id="${it.id}">حذف</button>
        </div>
      </div>
    </div>
  `).join("");
}

async function addBulk() {
  const lines = normalizeLines($("bulk").value);
  if (!lines.length) return setStatus("آدرس وارد کنید");

  const tag = $("tag").value.trim();
  const city = $("city").value.trim();

  $("btnAdd").disabled = true;
  setStatus("در حال ذخیره...");

  const payload = {
    items: lines.map(a => ({
      address: a,
      tag: tag || null,
      city: city || null
    }))
  };

  try {
    const res = await api("api/addresses.php", {
      method: "POST",
      body: JSON.stringify(payload)
    });
    if (!res) return;

    const out = await res.json();
    setStatus(`ثبت شد (تعداد: ${out.inserted ?? "?"})`);
    $("bulk").value = "";
    await loadList();
  } catch (e) {
    setStatus("خطا در ذخیره");
  } finally {
    $("btnAdd").disabled = false;
    setTimeout(() => setStatus(""), 1200);
  }
}

async function delItem(id) {
  setStatus("در حال حذف...");
  const res = await api(`api/addresses.php?id=${encodeURIComponent(id)}`, { method: "DELETE" });
  if (!res) return;

  await res.json().catch(() => {});
  await loadList();
  setStatus("");
}

// Events
$("btnBack").onclick = () => window.location.href = "index.html";
$("btnLogout").onclick = () => {
  localStorage.removeItem("auth_token");
  window.location.href = "login.html";
};
$("btnAdd").onclick = addBulk;
$("btnRefresh").onclick = loadList;

listEl.addEventListener("click", (e) => {
  const btn = e.target.closest("button");
  if (!btn) return;

  const act = btn.getAttribute("data-act");
  if (act === "go") {
    const q = btn.getAttribute("data-q") || "";
    window.location.href = "index.html?q=" + encodeURIComponent(q);
  }
  if (act === "del") {
    const id = btn.getAttribute("data-id");
    if (id && confirm("حذف شود؟")) delItem(id);
  }
});

// Init
loadList();
