const $ = id => document.getElementById(id);

const btn = $("btnLogin");
const msg = $("msg");

btn.onclick = async () => {
  msg.textContent = "";

  const username = $("username").value.trim();
  const password = $("password").value;

  if (!username || !password) {
    msg.textContent = "نام کاربری و رمز را وارد کنید";
    return;
  }

  btn.disabled = true;

  try {
    // چون login.html در /location/4/ است و api هم در /location/4/api/ است:
    const res = await fetch("api/login.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json"
      },
      body: JSON.stringify({ username, password })
    });

    const data = await res.json();

    if (!res.ok) {
      msg.textContent = data.error || "خطا در ورود";
      btn.disabled = false;
      return;
    }

    localStorage.setItem("auth_token", data.token);

    // بعد از لاگین برو صفحه اصلی اپ (همون نقشه)
    window.location.href = "index.html";

  } catch (e) {
    console.error(e);
    msg.textContent = "خطای شبکه";
    btn.disabled = false;
  }
};
