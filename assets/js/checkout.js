console.log("NUCLEAR NOTIFICATION KILLER ACTIVATED");

document.addEventListener("DOMContentLoaded", function () {
  destroyAllNotifications();

  overrideNotificationFunctions();

  startAggressiveMonitoring();

  setupCleanCheckout();
});

function destroyAllNotifications() {
  console.log("Destroying all notifications...");

  const selectors = [
    ".alert",
    ".alert-success",
    ".alert-info",
    ".alert-warning",
    ".alert-error",
    ".notification",
    ".toast",
    ".message",
    ".popup",
    ".modal",
    ".swal2-container",
    ".toastr",
    ".noty_bar",
    ".growl",
    '[class*="success"]',
    '[class*="notification"]',
    '[class*="alert"]',
    '[class*="toast"]',
    '[class*="message"]',
    '[class*="popup"]',
  ];

  selectors.forEach((selector) => {
    document.querySelectorAll(selector).forEach((el) => {
      const text = (el.textContent || el.innerText || "").toLowerCase();
      if (
        text.includes("berhasil") ||
        text.includes("success") ||
        text.includes("undefined") ||
        text.includes("pesanan")
      ) {
        console.log("Destroyed:", text.substring(0, 50));
        el.remove();
      }
    });
  });

  document.querySelectorAll("*").forEach((el) => {
    const style = window.getComputedStyle(el);
    if (style.position === "fixed" || style.position === "absolute") {
      const text = (el.textContent || el.innerText || "").toLowerCase();
      if (
        text.includes("berhasil") ||
        text.includes("undefined") ||
        text.includes("success") ||
        text.includes("pesanan")
      ) {
        console.log("Destroyed fixed element:", text.substring(0, 50));
        el.remove();
      }
    }
  });

  try {
    sessionStorage.removeItem("notification");
    sessionStorage.removeItem("success");
    sessionStorage.removeItem("message");
    sessionStorage.removeItem("alert");
  } catch (e) {}
}

function overrideNotificationFunctions() {
  console.log("Overriding notification functions...");

  window.alert = function (msg) {
    if (
      msg &&
      (msg.includes("berhasil") ||
        msg.includes("undefined") ||
        msg.includes("success"))
    ) {
      console.log("Blocked alert:", msg);
      return;
    }
    console.log("Alert:", msg);
  };

  [
    "showNotification",
    "showAlert",
    "showMessage",
    "showToast",
    "notify",
    "toast",
  ].forEach((fn) => {
    if (window[fn]) {
      const original = window[fn];
      window[fn] = function (msg, type) {
        if (
          type === "success" ||
          (msg && (msg.includes("berhasil") || msg.includes("undefined")))
        ) {
          console.log(`Blocked ${fn}:`, msg);
          return;
        }
        return original.apply(this, arguments);
      };
    }
  });

  if (window.$) {
    $.fn.notify = function () {
      console.log("Blocked jQuery notify");
      return this;
    };
  }
}

function startAggressiveMonitoring() {
  console.log("Starting aggressive monitoring...");

  const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      mutation.addedNodes.forEach(function (node) {
        if (node.nodeType === 1) {
          const text = (node.textContent || node.innerText || "").toLowerCase();
          const className = (node.className || "").toLowerCase();

          if (
            text.includes("berhasil") ||
            text.includes("undefined") ||
            text.includes("success") ||
            text.includes("pesanan berhasil") ||
            className.includes("success") ||
            className.includes("alert")
          ) {
            console.log("Auto-destroyed new element:", text.substring(0, 50));
            node.remove();
            return;
          }

          const successChildren =
            node.querySelectorAll && node.querySelectorAll("*");
          if (successChildren) {
            successChildren.forEach((child) => {
              const childText = (
                child.textContent ||
                child.innerText ||
                ""
              ).toLowerCase();
              if (
                childText.includes("berhasil") ||
                childText.includes("undefined") ||
                childText.includes("success")
              ) {
                console.log(
                  "Auto-destroyed child element:",
                  childText.substring(0, 50)
                );
                child.remove();
              }
            });
          }
        }
      });
    });
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });

  setInterval(destroyAllNotifications, 100);
}

function setupCleanCheckout() {
  const form = document.getElementById("checkoutForm");
  if (!form) return;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const submitBtn = document.getElementById("submitOrder");
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    }

    try {
      const formData = new FormData(form);

      const paymentMethod = document.querySelector(
        'input[name="payment_method"]:checked'
      );
      if (!paymentMethod) {
        throw new Error("Silakan pilih metode pembayaran");
      }

      console.log("Clean checkout submission...");

      const response = await fetch(window.location.href, {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: formData,
      });

      if (!response.ok) {
        throw new Error(`Server error: ${response.status}`);
      }

      const result = await response.json();
      console.log("Result:", result);

      if (result.success) {
        console.log("Success - immediate redirect");

        destroyAllNotifications();
        document.body.innerHTML =
          '<div style="text-align:center;padding:50px;">Redirecting...</div>';

        const redirectUrl =
          result.redirect || `order-success.php?order_id=${result.order_id}`;

        setTimeout(() => (window.location.href = redirectUrl), 10);
        setTimeout(() => window.location.replace(redirectUrl), 50);
        window.location.href = redirectUrl;
      } else {
        const errorMsg =
          result.message || result.error || "Gagal memproses pesanan";
        showCleanError(errorMsg);
      }
    } catch (error) {
      console.error("Error:", error);
      showCleanError(error.message || "Terjadi kesalahan");
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-lock"></i> Buat Pesanan';
      }
    }
  });
}

function showCleanError(message) {
  document.querySelectorAll(".clean-error").forEach((el) => el.remove());

  const errorDiv = document.createElement("div");
  errorDiv.className = "clean-error";
  errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #dc3545;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        z-index: 99999;
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    `;

  errorDiv.innerHTML = `❌ ${message}`;
  document.body.appendChild(errorDiv);

  setTimeout(() => {
    if (errorDiv.parentNode) {
      errorDiv.remove();
    }
  }, 5000);
}

destroyAllNotifications();

console.log(
  "NUCLEAR NOTIFICATION KILLER READY - ALL SUCCESS MESSAGES WILL BE DESTROYED!"
);
