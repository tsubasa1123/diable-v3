const campaignId = "demo-001";
const MAIL_KEY = `phish_mail_${campaignId}`;
const RECEIVED_SESSION_KEY = `phish_received_session_${campaignId}`;
async function sendEvent(event_type, page) {
  try {
    await fetch("/api/event", {
      method: "POST",
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({
        event_type,
        page,
        campaign_id: campaignId,
        client_hint: `${navigator.language} | ${screen.width}x${screen.height}`
      })
    });
  } catch (e) {}
}

function addMailRow() {
  const list = document.getElementById("mailList");

  // évite de l'ajouter deux fois
  if (document.getElementById("mailRow")) return;

  const spamHtml = `
    <div class="mail-row unread" id="mailRow">
      <div class="from">FedEx</div>
      <div class="snippet">
        <b>Mise à jour concernant la livraison</b>
        <span> — Une tentative de livraison n’a pas pu être finalisée…</span>
      </div>
      <div class="time">12:08</div>
    </div>
  `;

  // Ajoute le spam en haut de la liste sans supprimer les anciens mails
  list.insertAdjacentHTML("afterbegin", spamHtml);

  document.getElementById("mailRow").addEventListener("click", openModal);
}

function openModal() {
  document.getElementById("modalBackdrop").classList.remove("hidden");
  document.getElementById("mailSubject").textContent = "Mise à jour concernant la livraison de votre colis";
  sendEvent("email_opened", "/");
}

function closeModal() {
  document.getElementById("modalBackdrop").classList.add("hidden");
}

function goToLogin() {
  sendEvent("link_clicked", "/");
  window.location.href = "/login";
}

document.getElementById("closeModal").addEventListener("click", closeModal);
document.getElementById("modalBackdrop").addEventListener("click", (e) => {
  if (e.target.id === "modalBackdrop") closeModal();
});
document.getElementById("planBtn").addEventListener("click", goToLogin);

(async function init() {
  await sendEvent("page_view", "/");

  // Toujours ajouter la ligne spam (sans doublon)
  addMailRow();

  // Toujours afficher le popup après 5 secondes (même après refresh)
  setTimeout(async () => {
    // Optionnel : log "email_received" une fois par session (onglet)
    if (sessionStorage.getItem(RECEIVED_SESSION_KEY) !== "1") {
      await sendEvent("email_received", "/");
      sessionStorage.setItem(RECEIVED_SESSION_KEY, "1");
    }

    openModal();
  }, 5000);
})();
