function showSection(sectionName) {
  console.log("Switching to section:", sectionName);

  document.querySelectorAll(".section-content").forEach((section) => {
    section.classList.remove("active");
  });

  document.querySelectorAll(".nav-item").forEach((item) => {
    item.classList.remove("active");
  });

  const targetSection = document.getElementById(sectionName);
  if (targetSection) {
    targetSection.classList.add("active");
    console.log("Section found and activated:", sectionName);
  } else {
    console.error("Section not found:", sectionName);
    console.log("Available sections:");
    document.querySelectorAll(".section-content").forEach((section) => {
      console.log("  -", section.id);
    });
    return;
  }

  const navItem = document.querySelector(`[data-section="${sectionName}"]`);
  if (navItem) {
    navItem.classList.add("active");
    console.log("Nav item activated for:", sectionName);
  } else {
    console.error("Nav item not found for:", sectionName);
  }
}

function viewOrderDetail(orderId) {
  const modal = document.getElementById("orderModal");
  const modalBody = document.getElementById("orderModalBody");

  modalBody.innerHTML =
    '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>';
  modal.style.display = "block";

  fetch(`../api/user-orders.php?action=getAll`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const order = data.order;
        const items = data.items;

        let itemsHtml = "";
        items.forEach((item) => {
          itemsHtml += `
            <div class="order-item-detail">
              <div class="item-info">
                <h4>${item.product_name}</h4>
                <p>Harga: Rp ${parseInt(item.price).toLocaleString("id-ID")}</p>
                <p>Jumlah: ${item.quantity}</p>
              </div>
              <div class="item-total">
                <strong>Rp ${parseInt(
                  item.price * item.quantity
                ).toLocaleString("id-ID")}</strong>
              </div>
            </div>
          `;
        });

        modalBody.innerHTML = `
          <div class="order-detail">
            <div class="order-info">
              <h3>Order #${order.order_number}</h3>
              <p><strong>Tanggal:</strong> ${new Date(
                order.created_at
              ).toLocaleDateString("id-ID")}</p>
              <p><strong>Status:</strong> <span class="status ${
                order.status
              }">${
          order.status.charAt(0).toUpperCase() + order.status.slice(1)
        }</span></p>
              <p><strong>Total:</strong> Rp ${parseInt(
                order.total_amount
              ).toLocaleString("id-ID")}</p>
            </div>
            
            <div class="shipping-info">
              <h4>Informasi Pengiriman</h4>
              <p><strong>Nama:</strong> ${order.shipping_name}</p>
              <p><strong>Alamat:</strong> ${order.shipping_address}</p>
              <p><strong>Kota:</strong> ${order.shipping_city}</p>
              <p><strong>Kode Pos:</strong> ${order.shipping_postal_code}</p>
              <p><strong>Telepon:</strong> ${order.shipping_phone}</p>
            </div>
            
            <div class="order-items">
              <h4>Item Pesanan</h4>
              ${itemsHtml}
            </div>
          </div>
        `;
      } else {
        modalBody.innerHTML =
          '<div class="error">Gagal memuat detail pesanan</div>';
      }
    })
    .catch((error) => {
      modalBody.innerHTML =
        '<div class="error">Terjadi kesalahan saat memuat data</div>';
      console.error("Error:", error);
    });
}

function validateProfileForm(form) {
  const fullName = form.querySelector("#full_name").value.trim();

  if (!fullName) {
    showNotification("Nama lengkap harus diisi", "error");
    return false;
  }

  return true;
}

function showNotification(message, type = "success") {
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  notification.innerHTML = `
    <i class="fas ${
      type === "error" ? "fa-exclamation-circle" : "fa-check-circle"
    }"></i>
    ${message}
  `;

  const bgColor = type === "error" ? "#dc3545" : "#28a745";

  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: ${bgColor};
    color: white;
    padding: 15px 20px;
    border-radius: 10px;
    z-index: 9999;
    font-family: 'Poppins', sans-serif;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    transform: translateX(100%);
    transition: transform 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 300px;
  `;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.transform = "translateX(0)";
  }, 100);

  setTimeout(() => {
    notification.style.transform = "translateX(100%)";
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300);
  }, 4000);
}

document.addEventListener("DOMContentLoaded", () => {
  const hash = window.location.hash.substring(1);
  if (hash && document.getElementById(hash)) {
    showSection(hash);
  }

  const profileForm = document.querySelector(".profile-form");
  if (profileForm) {
    profileForm.addEventListener("submit", (e) => {
      if (!validateProfileForm(profileForm)) {
        e.preventDefault();
        return false;
      }

      const submitBtn = profileForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
      submitBtn.disabled = true;

      setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }, 3000);
    });
  }

  const modal = document.getElementById("orderModal");
  const closeBtn = document.querySelector(".close");

  if (closeBtn) {
    closeBtn.addEventListener("click", () => {
      modal.style.display = "none";
    });
  }

  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });

  const logoutLink = document.querySelector(".nav-item.logout");
  if (logoutLink) {
    logoutLink.addEventListener("click", (e) => {
      if (!confirm("Apakah Anda yakin ingin logout?")) {
        e.preventDefault();
      }
    });
  }
});

document.addEventListener("keydown", (e) => {
  if (e.altKey) {
    switch (e.key) {
      case "1":
        showSection("profile");
        break;
      case "2":
        showSection("orders");
        break;
    }
  }

  if ((e.ctrlKey || e.metaKey) && e.key === "s") {
    e.preventDefault();
    const profileForm = document.querySelector(".profile-form");
    if (
      profileForm &&
      document.getElementById("profile").classList.contains("active")
    ) {
      profileForm.querySelector('button[type="submit"]').click();
    }
  }

  if (e.key === "Escape") {
    const modal = document.getElementById("orderModal");
    if (modal && modal.style.display === "block") {
      modal.style.display = "none";
    }
  }
});

const style = document.createElement("style");
style.textContent = `
  .section-content {
    animation: fadeIn 0.3s ease;
  }
  
  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  .nav-item {
    position: relative;
    overflow: hidden;
  }
  
  .nav-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
  }
  
  .nav-item:hover::before {
    left: 100%;
  }
  
  .loading {
    text-align: center;
    padding: 40px;
    color: var(--gray);
  }
  
  .loading i {
    font-size: 24px;
    margin-bottom: 10px;
  }
  
  .error {
    text-align: center;
    padding: 40px;
    color: #dc3545;
  }
`;
document.head.appendChild(style);
