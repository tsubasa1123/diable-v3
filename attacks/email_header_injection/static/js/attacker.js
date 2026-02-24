async function refresh() {
  const m = await fetch("/api/metrics").then(r => r.json());

  const set = (id, v) => {
    const el = document.getElementById(id);
    if (el) el.textContent = v ?? "-";
  };

  set("mInbox", m.inbox_view);
  set("mMailOpened", m.mail_opened);
  set("mPixel", m.pixel_loaded);
  set("mReplyOpened", m.reply_opened);
  set("mReplySubmitted", m.reply_submitted);

  set("mObserved", m.header_injection_observed);
  set("mBlocked", m.header_injection_blocked);
  set("mReplyOk", m.reply_sent_simulated);

  const events = await fetch("/api/events").then(r => r.json());
  const body = document.getElementById("eventsBody");
  if (!body) return;

  body.innerHTML = events.map(ev => `
    <tr>
      <td>${ev.ts}</td>
      <td>${ev.event_type}</td>
      <td>${ev.page}</td>
      <td class="muted">${(ev.detail || "").slice(0, 90)}</td>
      <td>${ev.ip_masked || ""}</td>
      <td>${ev.os || ""}</td>
      <td>${ev.browser || ""}</td>
      <td class="ua">${(ev.user_agent || "").slice(0, 80)}</td>
    </tr>
  `).join("");
}

refresh();
setInterval(refresh, 1500);
