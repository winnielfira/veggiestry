let availableStock = 0;
let currentProductId = 0;
let isOutOfStock = false;
let isUpdatingStock = false;
let stockCheckInterval = null;

document.addEventListener("DOMContentLoaded", function () {
  console.log("Product detail page loaded - Enhanced version");

  if (window.productData) {
    availableStock = parseInt(window.productData.availableStock) || 0;
    currentProductId = parseInt(window.productData.id) || 0;
    isOutOfStock = window.productData.isOutOfStock;

    console.log("Initial stock data:", {
      productId: currentProductId,
      availableStock: availableStock,
      isOutOfStock: isOutOfStock,
    });
  } else {
    const stockInfo = document.querySelector(".stock-info");
    if (stockInfo) {
      const stockText = stockInfo.textContent;
      const stockMatch = stockText.match(/Stok Tersedia: (\d+)/);
      if (stockMatch) {
        availableStock = parseInt(stockMatch[1]);
      }
    }

    const addToCartBtn = document.querySelector(".btn-add-cart");
    if (addToCartBtn) {
      const onclickAttr = addToCartBtn.getAttribute("onclick");
      const idMatch = onclickAttr
        ? onclickAttr.match(/addToCartDetail\((\d+)\)/)
        : null;
      if (idMatch) {
        currentProductId = parseInt(idMatch[1]);
      }
    }
  }

  const quantityInput = document.getElementById("quantity");
  if (quantityInput) {
    quantityInput.addEventListener("input", debounce(validateQuantity, 300));
    quantityInput.addEventListener("change", validateQuantity);
    quantityInput.addEventListener("focus", () => checkRealTimeStock());

    quantityInput.max = availableStock;

    if (availableStock <= 0) {
      quantityInput.value = 0;
      quantityInput.disabled = true;
    } else {
      quantityInput.value = Math.min(1, availableStock);
    }
  }

  validateQuantity();

  initImageZoom();

  if (currentProductId > 0) {
    startRealTimeStockMonitoring();
  }

  console.log("Enhanced product detail initialization complete");
});

async function checkRealTimeStock(showNotification = false) {
  if (isUpdatingStock || currentProductId <= 0) return;

  isUpdatingStock = true;

  try {
    const basePath = window.location.pathname.includes("/pages/") ? "../" : "";
    const response = await fetch(
      `${basePath}api/get-stock.php?product_id=${currentProductId}`
    );
    const result = await response.json();

    if (result.success) {
      const newAvailableStock = parseInt(result.available_stock);
      const hasStockChanged = newAvailableStock !== availableStock;

      if (hasStockChanged) {
        console.log(`Stock updated: ${availableStock} → ${newAvailableStock}`);

        availableStock = newAvailableStock;
        isOutOfStock = result.is_out_of_stock;

        updateStockDisplay();
        validateQuantity();

        if (showNotification) {
          if (result.is_out_of_stock) {
            showStockNotification("Produk ini sekarang habis!", "error", 6000);
          } else if (result.is_low_stock) {
            showStockNotification(
              `Stok terbatas! Tersisa ${newAvailableStock} item`,
              "warning",
              5000
            );
          } else if (newAvailableStock > availableStock) {
            showStockNotification(
              `Stok bertambah! Sekarang tersedia ${newAvailableStock} item`,
              "success",
              4000
            );
          }
        }
      }
    } else {
      console.error("Failed to get real-time stock:", result.message);
    }
  } catch (error) {
    console.error("Error checking real-time stock:", error);
  } finally {
    isUpdatingStock = false;
  }
}

function startRealTimeStockMonitoring() {
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) {
      checkRealTimeStock(true);
    }
  });

  stockCheckInterval = setInterval(() => {
    if (!document.hidden) {
      checkRealTimeStock(true);
    }
  }, 30000);

  console.log("Real-time stock monitoring started");
}

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

function changeQuantity(change) {
  const quantityInput = document.getElementById("quantity");
  if (!quantityInput || isOutOfStock) return;

  let currentValue = parseInt(quantityInput.value) || 1;
  let newValue = currentValue + change;

  if (newValue < 1) {
    newValue = 1;
  } else if (newValue > availableStock) {
    newValue = availableStock;
    showStockNotification(
      `Maksimal pembelian adalah ${availableStock} item (stok tersedia)`,
      "warning",
      5000
    );
  }

  quantityInput.value = newValue;
  validateQuantity();

  console.log(`Quantity changed to: ${newValue}/${availableStock}`);
}

async function validateQuantity() {
  const quantityInput = document.getElementById("quantity");
  if (!quantityInput) return;

  await checkRealTimeStock();

  let value = parseInt(quantityInput.value) || 1;

  if (value < 1) {
    quantityInput.value = 1;
    value = 1;
  }

  if (value > availableStock) {
    quantityInput.value = availableStock;
    value = availableStock;
    showStockNotification(
      `Maksimal pembelian adalah ${availableStock} item (stok tersedia)`,
      "warning",
      5000
    );
  }

  quantityInput.max = availableStock;

  updateButtonStates(value);

  updateStockDisplay();
}

function updateButtonStates(quantity) {
  const addToCartBtn = document.querySelector(".btn-add-cart");
  const buyNowBtn = document.querySelector(".btn-buy-now");
  const quantityControls = document.querySelectorAll(
    ".quantity-controls button"
  );
  const quantityInput = document.getElementById("quantity");

  if (availableStock <= 0 || isOutOfStock) {
    if (addToCartBtn) {
      addToCartBtn.disabled = true;
      addToCartBtn.innerHTML = '<i class="fas fa-times"></i> Stok Habis';
      addToCartBtn.classList.add("disabled");
    }
    if (buyNowBtn) {
      buyNowBtn.disabled = true;
      buyNowBtn.textContent = "Stok Habis";
      buyNowBtn.classList.add("disabled");
    }
    if (quantityInput) {
      quantityInput.disabled = true;
      quantityInput.value = 0;
    }
    quantityControls.forEach((btn) => {
      btn.disabled = true;
    });
  } else {
    if (addToCartBtn) {
      addToCartBtn.disabled = false;
      addToCartBtn.innerHTML =
        '<i class="fas fa-shopping-cart"></i> Tambah ke Keranjang';
      addToCartBtn.classList.remove("disabled");
    }
    if (buyNowBtn) {
      buyNowBtn.disabled = false;
      buyNowBtn.textContent = "Beli Sekarang";
      buyNowBtn.classList.remove("disabled");
    }
    if (quantityInput) {
      quantityInput.disabled = false;
    }
    quantityControls.forEach((btn) => {
      btn.disabled = false;
    });

    const decreaseBtn = document.querySelector(
      ".quantity-controls button:first-child"
    );
    const increaseBtn = document.querySelector(
      ".quantity-controls button:last-child"
    );

    if (decreaseBtn) {
      decreaseBtn.disabled = quantity <= 1;
    }
    if (increaseBtn) {
      increaseBtn.disabled = quantity >= availableStock;
    }
  }
}

function updateStockDisplay() {
  const stockDisplay = document.getElementById("availableStockDisplay");
  if (stockDisplay) {
    stockDisplay.textContent = availableStock;

    stockDisplay.parentElement.className = "stock-info";
    if (availableStock <= 0) {
      stockDisplay.parentElement.classList.add("stock-out");
    } else if (availableStock <= 5) {
      stockDisplay.parentElement.classList.add("stock-low");
    } else {
      stockDisplay.parentElement.classList.add("stock-good");
    }
  }
}

async function addToCartDetail(productId) {
  await checkRealTimeStock();

  const quantityInput = document.getElementById("quantity");
  const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

  if (availableStock <= 0) {
    showStockNotification(
      "Produk sedang habis, tidak dapat ditambahkan ke keranjang",
      "error",
      6000
    );
    return;
  }

  if (quantity > availableStock) {
    showStockNotification(
      `Tidak dapat menambahkan ${quantity} item. Stok tersedia: ${availableStock}`,
      "error",
      6000
    );
    return;
  }

  if (quantity <= 0) {
    showStockNotification("Jumlah harus lebih dari 0", "error", 4000);
    return;
  }

  console.log(
    `Adding ${quantity} of product ${productId} to cart (available: ${availableStock})`
  );

  const addToCartBtn = document.querySelector(".btn-add-cart");
  const originalText = addToCartBtn.innerHTML;
  addToCartBtn.disabled = true;
  addToCartBtn.innerHTML =
    '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
  addToCartBtn.classList.add("loading");

  try {
    const basePath = window.location.pathname.includes("/pages/") ? "../" : "";
    const response = await fetch(`${basePath}api/add-to-cart.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `product_id=${productId}&quantity=${quantity}`,
    });

    const result = await response.json();
    console.log("Add to cart result:", result);

    if (result.success) {
      await updateCartCount();

      showStockNotification(
        `✅ ${quantity} produk berhasil ditambahkan ke keranjang!`,
        "success",
        5000
      );

      if (quantityInput && availableStock > 0) {
        quantityInput.value = 1;
        validateQuantity();
      }

      if (result.available_stock !== undefined) {
        availableStock = parseInt(result.available_stock);
        updateStockDisplay();
        validateQuantity();
      }

      setTimeout(() => checkRealTimeStock(true), 1000);
    } else {
      const errorMsg = result.message || "Gagal menambahkan ke keranjang";
      showStockNotification(`❌ ${errorMsg}`, "error", 7000);

      if (result.available_stock !== undefined) {
        availableStock = parseInt(result.available_stock);
        updateStockDisplay();
        validateQuantity();
      }
    }
  } catch (error) {
    console.error("Error adding to cart:", error);
    showStockNotification(
      "Terjadi kesalahan saat menambahkan ke keranjang",
      "error",
      6000
    );
  } finally {
    addToCartBtn.disabled = false;
    addToCartBtn.innerHTML = originalText;
    addToCartBtn.classList.remove("loading");
  }
}

function buyNow(productId) {
  const quantityInput = document.getElementById("quantity");
  const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

  if (availableStock <= 0) {
    showStockNotification(
      "Produk sedang habis, tidak dapat dibeli",
      "error",
      6000
    );
    return;
  }

  if (quantity > availableStock) {
    showStockNotification(
      `Tidak dapat membeli ${quantity} item. Stok tersedia: ${availableStock}`,
      "error",
      6000
    );
    return;
  }

  if (quantity <= 0) {
    showStockNotification("Jumlah harus lebih dari 0", "error", 4000);
    return;
  }

  console.log(
    `Buying ${quantity} of product ${productId} (available: ${availableStock})`
  );

  showStockNotification("Memproses pembelian...", "info", 3000);

  addToCartDetail(productId).then(() => {
    setTimeout(() => {
      const basePath = window.location.pathname.includes("/pages/")
        ? ""
        : "pages/";
      window.location.href = `${basePath}checkout.php`;
    }, 1500);
  });
}

function showStockNotification(message, type = "info", duration = 5000) {
  const existingNotifications = document.querySelectorAll(
    ".stock-notification"
  );
  existingNotifications.forEach((notif) => {
    notif.style.transform = "translateX(100%)";
    setTimeout(() => notif.remove(), 300);
  });

  const notification = document.createElement("div");
  notification.className = `stock-notification notification-${type}`;

  const styles = {
    info: { backgroundColor: "#17a2b8", borderLeft: "5px solid #0c6674" },
    success: { backgroundColor: "#28a745", borderLeft: "5px solid #1e7e34" },
    error: { backgroundColor: "#dc3545", borderLeft: "5px solid #bd2130" },
    warning: {
      backgroundColor: "#ffc107",
      borderLeft: "5px solid #d39e00",
      color: "#212529",
    },
  };

  const style = styles[type] || styles.info;

  Object.assign(notification.style, {
    position: "fixed",
    top: "80px",
    right: "20px",
    padding: "20px 25px",
    borderRadius: "12px",
    boxShadow: "0 8px 32px rgba(0,0,0,0.3)",
    zIndex: "10001",
    fontSize: "15px",
    fontWeight: "600",
    maxWidth: "450px",
    wordWrap: "break-word",
    transform: "translateX(100%)",
    transition: "all 0.1s cubic-bezier(0.4, 0, 0.2, 1)",
    fontFamily: "'Poppins', sans-serif",
    lineHeight: "1.4",
    backdropFilter: "blur(10px)",
    border: "1px solid rgba(255,255,255,0.2)",
    color: style.color || "white",
    ...style,
  });

  notification.textContent = message;
  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.transform = "translateX(0)";
  }, 100);

  setTimeout(() => {
    notification.style.transform = "translateX(100%)";
    notification.style.opacity = "0";
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, 400);
  }, duration);

  console.log(`Stock Notification [${type}]: ${message} (${duration}ms)`);
}

function changeMainImage(src) {
  const mainImage = document.getElementById("mainImage");
  if (mainImage) {
    mainImage.src = src;
  }

  document.querySelectorAll(".thumbnail").forEach((thumb) => {
    thumb.classList.remove("active");
  });

  if (event && event.target) {
    event.target.classList.add("active");
  }
}

function showTab(tabName) {
  document.querySelectorAll(".tab-pane").forEach((pane) => {
    pane.classList.remove("active");
  });

  document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.classList.remove("active");
  });

  const selectedPane = document.getElementById(tabName);
  if (selectedPane) {
    selectedPane.classList.add("active");
  }

  if (event && event.target) {
    event.target.classList.add("active");
  }
}

function initImageZoom() {
  const mainImage = document.getElementById("mainImage");
  if (!mainImage) return;

  let isZoomed = false;

  mainImage.style.cursor = "zoom-in";

  mainImage.addEventListener("click", function () {
    if (!isZoomed) {
      this.style.transform = "scale(2)";
      this.style.cursor = "zoom-out";
      this.style.transition = "transform 0.3s ease";
      isZoomed = true;
    } else {
      this.style.transform = "scale(1)";
      this.style.cursor = "zoom-in";
      isZoomed = false;
    }
  });

  document.querySelectorAll(".thumbnail").forEach((thumb) => {
    thumb.addEventListener("click", () => {
      mainImage.style.transform = "scale(1)";
      mainImage.style.cursor = "zoom-in";
      isZoomed = false;
    });
  });
}

window.addEventListener("beforeunload", () => {
  if (stockCheckInterval) {
    clearInterval(stockCheckInterval);
  }
});

const stockStyles = document.createElement("style");
stockStyles.textContent = `
    .stock-info.stock-out #availableStockDisplay {
        color: #dc3545;
        font-weight: bold;
    }
    
    .stock-info.stock-low #availableStockDisplay {
        color: #ffc107;
        font-weight: bold;
    }
    
    .stock-info.stock-good #availableStockDisplay {
        color: #28a745;
        font-weight: bold;
    }
    
    .quantity-controls input:focus {
        outline: 2px solid #007bff;
        outline-offset: 2px;
        border-color: #007bff;
    }
    
    .btn-add-cart.loading {
        background: linear-gradient(90deg, #007bff, #0056b3);
        animation: loading-pulse 1s infinite;
    }
    
    @keyframes loading-pulse {
        0% { opacity: 0.8; }
        50% { opacity: 1; }
        100% { opacity: 0.8; }
    }
`;
document.head.appendChild(stockStyles);

window.changeQuantity = changeQuantity;
window.addToCartDetail = addToCartDetail;
window.buyNow = buyNow;
window.changeMainImage = changeMainImage;
window.showTab = showTab;
window.checkRealTimeStock = checkRealTimeStock;

console.log(
  "Enhanced product detail JavaScript loaded with real-time stock monitoring"
);
