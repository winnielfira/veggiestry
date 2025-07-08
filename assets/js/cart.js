let isUpdating = false;
let updateTimeout = null;

async function updateQuantity(productId, newQuantity) {
  if (isUpdating) return;

  if (newQuantity < 1) {
    removeItem(productId);
    return;
  }

  const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
  const availableStock = parseInt(
    cartItem?.querySelector(".quantity-controls input")?.max || 0
  );

  if (newQuantity > availableStock) {
    showNotification(
      `Stok tidak mencukupi! Maksimal ${availableStock} item tersedia`,
      "error",
      5000
    );

    const quantityInput = cartItem.querySelector(".quantity-controls input");
    if (quantityInput) {
      quantityInput.value = availableStock;
    }
    return;
  }

  isUpdating = true;
  showNotification("Mengupdate jumlah...", "info", 2000);

  try {
    const response = await fetch("cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=update_quantity&product_id=${productId}&quantity=${newQuantity}`,
    });

    const result = await response.json();
    console.log("Update quantity result:", result);

    if (result.success) {
      showNotification(
        `Jumlah berhasil diupdate menjadi ${newQuantity}`,
        "success",
        3000
      );

      if (result.new_total) {
        updateCartTotals(result.new_total, result.shipping_cost || 0);
      } else {
        setTimeout(() => location.reload(), 1000);
      }
    } else {
      const errorMsg = result.message || "Gagal mengupdate jumlah produk";
      showNotification(`❌ ${errorMsg}`, "error", 5000);

      if (result.available_stock !== undefined) {
        const quantityInput = cartItem.querySelector(
          ".quantity-controls input"
        );
        if (quantityInput) {
          quantityInput.max = result.available_stock;
          quantityInput.value = Math.min(newQuantity, result.available_stock);
        }
      }
    }
  } catch (error) {
    console.error("Error updating quantity:", error);
    showNotification("Terjadi kesalahan saat mengupdate", "error", 5000);
  } finally {
    isUpdating = false;
  }
}

async function removeItem(productId) {
  if (isUpdating) return;

  if (
    !confirm("Apakah Anda yakin ingin menghapus produk ini dari keranjang?")
  ) {
    return;
  }

  isUpdating = true;
  const cartItem = document.querySelector(`[data-product-id="${productId}"]`);

  try {
    const response = await fetch("cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=remove_item&product_id=${productId}`,
    });

    const result = await response.json();

    if (result.success) {
      showNotification(
        "Produk berhasil dihapus dari keranjang",
        "success",
        3000
      );

      if (cartItem) {
        cartItem.style.transition = "all 0.3s ease";
        cartItem.style.opacity = "0";
        cartItem.style.transform = "translateX(-100%)";
        cartItem.style.height = "0";
        cartItem.style.marginBottom = "0";
        cartItem.style.paddingTop = "0";
        cartItem.style.paddingBottom = "0";
      }

      setTimeout(() => {
        location.reload();
      }, 300);
    } else {
      showNotification("Gagal menghapus produk", "error", 4000);
    }
  } catch (error) {
    console.error("Error removing item:", error);
    showNotification("Terjadi kesalahan", "error", 4000);
  } finally {
    isUpdating = false;
  }
}

async function clearCart() {
  if (isUpdating) return;

  if (!confirm("Apakah Anda yakin ingin mengosongkan keranjang?")) {
    return;
  }

  isUpdating = true;
  showNotification("Mengosongkan keranjang...", "info", 2000);

  try {
    const response = await fetch("cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "action=clear_cart",
    });

    const result = await response.json();

    if (result.success) {
      showNotification("Keranjang berhasil dikosongkan", "success", 3000);

      const cartItems = document.querySelectorAll(".cart-item");
      cartItems.forEach((item, index) => {
        setTimeout(() => {
          item.style.opacity = "0";
          item.style.transform = "translateX(-100%)";
        }, index * 100);
      });

      setTimeout(() => {
        location.reload();
      }, 800);
    } else {
      showNotification("Gagal mengosongkan keranjang", "error", 4000);
    }
  } catch (error) {
    console.error("Error clearing cart:", error);
    showNotification("Terjadi kesalahan", "error", 4000);
  } finally {
    isUpdating = false;
  }
}

function proceedToCheckout() {
  if (isUpdating) {
    showNotification("Mohon tunggu update selesai", "warning", 3000);
    return;
  }

  const cartItems = document.querySelectorAll(".cart-item");
  if (cartItems.length === 0) {
    showNotification("Keranjang kosong!", "error", 3000);
    return;
  }

  window.location.href = "checkout.php";
}

function showNotification(message, type = "success", duration = 4000) {
  clearNotifications();

  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.textContent = message;

  const styles = {
    info: { backgroundColor: "#609966", icon: "ℹ️" },
    success: { backgroundColor: "#28a745", icon: "✅" },
    error: { backgroundColor: "#dc3545", icon: "❌" },
    warning: { backgroundColor: "#ffc107", icon: "⚠️" },
  };

  const style = styles[type] || styles.info;

  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: ${style.backgroundColor};
        color: white;
        padding: 15px 25px;
        border-radius: 12px;
        z-index: 10000;
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        font-size: 14px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        transform: translateX(100%);
        transition: all 0.1s cubic-bezier(0.4, 0, 0.2, 1);
        max-width: 400px;
        word-wrap: break-word;
        line-height: 1.4;
    `;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.transform = "translateX(0)";
  }, 50);

  setTimeout(() => {
    notification.style.transform = "translateX(100%)";
    notification.style.opacity = "0";
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 400);
  }, duration);

  console.log(`Cart Notification [${type}]: ${message} (${duration}ms)`);
}

function clearNotifications() {
  const existingNotifications = document.querySelectorAll(".notification");
  existingNotifications.forEach((notif) => {
    notif.style.transition = "all 0.2s ease";
    notif.style.transform = "translateX(100%)";
    notif.style.opacity = "0";
    setTimeout(() => {
      if (notif.parentNode) {
        notif.parentNode.removeChild(notif);
      }
    }, 200);
  });
}

function updateCartTotals(subtotal, shippingCost = 0) {
  const subtotalElement = document.querySelector(".cart-subtotal .price");
  const shippingElement = document.querySelector(".cart-shipping .price");
  const totalElement = document.querySelector(".cart-total .price");

  if (subtotalElement) {
    subtotalElement.textContent = `Rp ${formatNumber(subtotal)}`;
  }

  if (shippingElement) {
    shippingElement.textContent = `Rp ${formatNumber(shippingCost)}`;
  }

  if (totalElement) {
    const grandTotal = subtotal + shippingCost;
    totalElement.textContent = `Rp ${formatNumber(grandTotal)}`;
  }
}

function formatNumber(num) {
  return new Intl.NumberFormat("id-ID").format(num);
}

function setupQuantityInputs() {
  const quantityInputs = document.querySelectorAll(".quantity-controls input");

  quantityInputs.forEach((input) => {
    let previousValue = input.value;

    input.addEventListener("input", function () {
      clearTimeout(updateTimeout);

      updateTimeout = setTimeout(() => {
        const productId = this.closest(".cart-item").dataset.productId;
        const newQuantity = parseInt(this.value) || 1;
        const maxStock = parseInt(this.max) || 999;

        if (newQuantity < 1) {
          this.value = 1;
          updateQuantity(productId, 1);
        } else if (newQuantity > maxStock) {
          this.value = maxStock;
          updateQuantity(productId, maxStock);
          showNotification(`Stok maksimal ${maxStock} item`, "error", 4000);
        } else if (newQuantity !== parseInt(previousValue)) {
          updateQuantity(productId, newQuantity);
          previousValue = newQuantity;
        }
      }, 1000);
    });

    input.addEventListener("blur", function () {
      clearTimeout(updateTimeout);

      const productId = this.closest(".cart-item").dataset.productId;
      const newQuantity = parseInt(this.value) || 1;
      const maxStock = parseInt(this.max) || 999;

      if (newQuantity < 1) {
        this.value = 1;
        updateQuantity(productId, 1);
      } else if (newQuantity > maxStock) {
        this.value = maxStock;
        updateQuantity(productId, maxStock);
        showNotification(`Stok maksimal ${maxStock} item`, "error", 4000);
      } else if (newQuantity !== parseInt(previousValue)) {
        updateQuantity(productId, newQuantity);
        previousValue = newQuantity;
      }
    });

    input.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        this.blur();
      }
    });
  });
}

document.addEventListener("DOMContentLoaded", () => {
  console.log("🛒 Cart page loaded - Enhanced version");

  setupQuantityInputs();

  const buttons = document.querySelectorAll("button:not(.quantity-btn)");
  buttons.forEach((button) => {
    button.addEventListener("click", function () {
      if (this.classList.contains("loading") || isUpdating) return;

      this.classList.add("loading");
      const originalText = this.innerHTML;
      this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

      setTimeout(() => {
        this.classList.remove("loading");
        this.innerHTML = originalText;
      }, 2000);
    });
  });

  document.addEventListener("keydown", (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
      const checkoutBtn = document.querySelector(".checkout-btn");
      if (checkoutBtn && !isUpdating) {
        checkoutBtn.click();
      }
    }

    if (e.key === "Escape") {
      clearNotifications();
    }
  });

  const cartItems = document.querySelectorAll(".cart-item");
  cartItems.forEach((item, index) => {
    item.style.opacity = "0";
    item.style.transform = "translateY(20px)";

    setTimeout(() => {
      item.style.transition = "all 0.3s ease";
      item.style.opacity = "1";
      item.style.transform = "translateY(0)";
    }, index * 100);
  });

  console.log("Enhanced cart functionality initialized");
});

const style = document.createElement("style");
style.textContent = `
    .cart-item {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 8px;
        overflow: hidden;
    }
    
    .cart-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    }
    
    .loading {
        opacity: 0.6;
        pointer-events: none;
        cursor: wait;
    }
    
    .quantity-controls input:focus {
        outline: 2px solid #007bff;
        outline-offset: 2px;
    }
    
    .notification {
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .cart-item.updating {
        animation: pulse 1s infinite;
    }
    
    .quantity-controls button:hover {
        background-color: #007bff;
        color: white;
        transform: scale(1.1);
    }
    
    .quantity-controls button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
`;
document.head.appendChild(style);

console.log("Enhanced cart JavaScript loaded with improved stock validation");
