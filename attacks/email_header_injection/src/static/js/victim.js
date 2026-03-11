const campaignId = window.CAMPAIGN_ID || "demo-001";

async function sendEvent(event_type, page, detail = "") {
  try {
    await fetch("/api/event", {
      method: "POST",
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({ event_type, page, campaign_id: campaignId, detail })
    });
  } catch (e) {}
}

function qs(id) { return document.getElementById(id); }

async function sendReplyToServer() {
  const to = qs("replyTo")?.value || "";
  const subject = qs("replySubject")?.value || "";
  const message = qs("replyMessage")?.value || "";
const mode = "vulnerable"; // forcé

await sendEvent("reply_submitted", "/", "submitted");


  const r = await fetch("/send-reply", {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({ to, subject, message, mode })
  });

  const data = await r.json().catch(() => ({}));

  const status = qs("replyStatus");
  const rawBox = qs("rawBox");
  const rawPreview = qs("rawPreview");

  rawBox?.classList.remove("hidden");
  if (rawPreview) rawPreview.textContent = data.raw_email || "(vide)";

if (!r.ok || !data.ok) {
  if (status) status.textContent = "Rejeté : CRLF détecté dans un champ d’en-tête.";
  await sendEvent("header_injection_blocked", "/send-reply", "blocked_by_validation");
  return;
}

if (data.injection_observed) {
  if (status) status.textContent = "CRLF détecté : en-têtes supplémentaires possibles (voir email brut).";
  await sendEvent("header_injection_observed", "/send-reply", "crlf_observed");
} else {
  if (status) status.textContent = "Email construit.";
  await sendEvent("reply_sent", "/send-reply", "ok");
}

  }

document.addEventListener("DOMContentLoaded", async () => {
  // log inbox view
  await sendEvent("inbox_view", "/");

  // inject a spam row and auto-open modal
  const mailList = document.getElementById("mailList");
  if (mailList) {
    const spamRow = document.createElement("div");
    spamRow.className = "mail-row";
    spamRow.classList.add("unread");
    spamRow.innerHTML = `
      <div class="from">FedEx</div>
      <div class="snippet"><b>Mise à jour livraison</b> — Action requise : confirmer vos préférences…</div>
      <div class="time">Maintenant</div>
    `;
    mailList.prepend(spamRow);
    spamRow.addEventListener("click", () => openModal());
    // auto-open for demo
    setTimeout(() => openModal(), 700);
  }

  const backdrop = qs("modalBackdrop");
  const closeBtn = qs("closeModal");
  closeBtn?.addEventListener("click", () => closeModal());
  backdrop?.addEventListener("click", (e) => {
    if (e.target === backdrop) closeModal();
  });

  function openModal() {
    qs("mailSubject").textContent = "Mise à jour concernant votre livraison";
    backdrop?.classList.remove("hidden");
    sendEvent("mail_opened", "/mail", "spam_modal_opened");
  }

  function closeModal() {
    backdrop?.classList.add("hidden");
  }

  // Reply button: show reply zone
  const replyBtn = qs("replyBtn");
  const replyZone = qs("replyZone");
  replyBtn?.addEventListener("click", async () => {
    replyZone?.classList.remove("hidden");
    await sendEvent("reply_opened", "/mail", "user_clicked_reply");
  });

  // Send reply
  const sendReplyBtn = qs("sendReplyBtn");
  sendReplyBtn?.addEventListener("click", async () => {
    if (!sendReplyBtn) return;
    sendReplyBtn.disabled = true;
    try {
      await sendReplyToServer();
    } finally {
      sendReplyBtn.disabled = false;
    }
  });
});
