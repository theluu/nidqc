(function () {
  'use strict';

  const elements = Array.from(document.querySelectorAll('[data-online-count]'));
  if (elements.length === 0) {
    return;
  }

  let timer;
  let token;

  function render(count) {
    elements.forEach((element) => {
      if (element.textContent !== String(count)) {
        element.textContent = String(count);
      }
    });
  }

  async function heartbeat() {
    if (document.hidden) {
      return;
    }

    try {
      if (!token) {
        const tokenResponse = await fetch('/api/v1/online/csrf-token', {
          credentials: 'same-origin',
          headers: { Accept: 'text/plain' },
        });
        if (!tokenResponse.ok) {
          return;
        }
        token = (await tokenResponse.text()).trim();
      }

      const response = await fetch('/api/v1/online/heartbeat', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-CSRF-Token': token,
        },
      });
      if (!response.ok) {
        return;
      }

      const body = await response.json();
      if (Number.isInteger(body?.data?.count) && body.data.count >= 0) {
        render(body.data.count);
      }
    }
    catch {
      // The server-rendered value remains available when refresh fails.
    }
  }

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      heartbeat();
    }
  });

  heartbeat();
  timer = window.setInterval(heartbeat, 60000);
  window.addEventListener('pagehide', () => window.clearInterval(timer), { once: true });
}());
