async function refresh() {
  const m = await fetch("/api/metrics").then(r => r.json());
  document.getElementById("mReceived").textContent = m.received;
  document.getElementById("mOpened").textContent = m.opened;
  document.getElementById("mClicked").textContent = m.clicked;
  document.getElementById("mSubmitted").textContent = m.submitted;
  document.getElementById("mOpenRate").textContent = m.open_rate_pct + "%";
  document.getElementById("mClickRate").textContent = m.click_rate_pct + "%";
  document.getElementById("mSubmitRate").textContent = m.submit_rate_pct + "%";

  const events = await fetch("/api/events").then(r => r.json());
  const body = document.getElementById("eventsBody");
  body.innerHTML = events.map(ev => `
    <tr>
      <td>${ev.ts}</td>
      <td>${ev.event_type}</td>
      <td>${ev.page}</td>
      <td>${ev.username_masked ?? ""}</td>
      <td>${ev.password_strength ?? ""}</td>
      <td>${ev.ip_masked ?? ""}</td>
      <td>${ev.os ?? ""}</td>
      <td>${ev.browser ?? ""}</td>
      <td class="ua">${(ev.user_agent || "").slice(0, 90)}</td>
    </tr>
  `).join("");
}

refresh();
setInterval(refresh, 2000);