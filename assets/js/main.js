let cart = [];
let wishlistCount = 0;

async function updateCartCount() {
  const cartCount = document.getElementById("cartCount");
  if (!cartCount) {
    console.log("Cart count element not found");
    return;
  }

  try {
    const basePath = '/';
    const response = await fetch(`${basePath}api/get-cart-count.php`);
    const result = await response.json();

    if (result.success) {
      cartCount.textContent = result.count;
      console.log("Cart count updated from server:", result.count);
    } else {
      console.error("Failed to get cart count:", result.message);
      cartCount.textContent = "0";
    }
  } catch (error) {
    console.error("Error updating cart count:", error);
    cartCount.textContent = "0";
  }
}

async function updateWishlistCount() {
  console.log("🔍 updateWishlistCount called");

  const wishlistCountElement = document.getElementById("wishlistCount");
  if (!wishlistCountElement) {
    console.error(
      "Wishlist count element not found! Check if user is logged in and element exists in header."
    );
    return;
  }

  console.log("Wishlist count element found:", wishlistCountElement);

  try {
    const basePath = '/';
    const apiUrl = `${basePath}api/wishlist.php?action=getCount`;
    console.log("Fetching from:", apiUrl);

    const response = await fetch(apiUrl);
    console.log("Response status:", response.status);

    const result = await response.json();
    console.log("API Response:", result);

    if (result.success) {
      wishlistCountElement.textContent = result.count;
      wishlistCount = result.count;
      console.log("Wishlist count updated successfully:", result.count);

      if (result.count > 0) {
        console.log("User has " + result.count + " items in wishlist");
      } else {
        console.log("Wishlist is empty");
      }
    } else {
      console.error("API returned error:", result.error);
      wishlistCountElement.textContent = "0";
    }
  } catch (error) {
    console.error("Error updating wishlist count:", error);
    wishlistCountElement.textContent = "0";
  }
}

async function addToCart(productId) {
  console.log("Adding product to cart:", productId);

  try {
    const basePath = '/';
    const response = await fetch(`${basePath}api/add-to-cart.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `product_id=${productId}&quantity=1`,
    });

    const result = await response.json();
    console.log("Add to cart result:", result);

    if (result.success) {
      await updateCartCount();
      showNotification("Produk berhasil ditambahkan ke keranjang!");
    } else {
      showNotification(
        result.message || "Gagal menambahkan ke keranjang",
        "error"
      );
    }
  } catch (error) {
    console.error("Error adding to cart:", error);
    showNotification(
      "Terjadi kesalahan saat menambahkan ke keranjang",
      "error"
    );
  }
}

async function toggleWishlist(productId) {
  console.log("toggleWishlist called for product:", productId);

  try {
    const basePath = '/';
    const apiUrl = `${basePath}api/wishlist.php`;
    console.log("Posting to:", apiUrl);

    const response = await fetch(apiUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=toggle&product_id=${productId}`,
    });

    console.log("Toggle response status:", response.status);
    const result = await response.json();
    console.log("Toggle wishlist result:", result);

    if (result.success) {
      console.log("Wishlist toggle successful, action:", result.action);

      console.log("Updating wishlist count...");
      await updateWishlistCount();

      const wishlistBtn = document.querySelector(
        `[data-product-id="${productId}"]`
      );
      console.log("Found wishlist button:", wishlistBtn);

      if (wishlistBtn) {
        const icon = wishlistBtn.querySelector("i");
        const text = wishlistBtn.querySelector(".wishlist-text");

        console.log("Updating button appearance for action:", result.action);

        if (result.action === "added") {
          icon.classList.remove("far");
          icon.classList.add("fas");
          wishlistBtn.classList.add("active");
          if (text) text.textContent = "Hapus dari Wishlist";
          showNotification(
            "Produk berhasil ditambahkan ke wishlist!",
            "success"
          );
        } else {
          icon.classList.remove("fas");
          icon.classList.add("far");
          wishlistBtn.classList.remove("active");
          if (text) text.textContent = "Tambah ke Wishlist";
          showNotification("Produk berhasil dihapus dari wishlist!", "success");
        }
      }

      const allWishlistBtns = document.querySelectorAll(
        `[data-product-id="${productId}"]`
      );
      console.log(
        "Updating",
        allWishlistBtns.length,
        "buttons with product ID:",
        productId
      );

      allWishlistBtns.forEach((btn) => {
        const icon = btn.querySelector("i");
        if (icon) {
          if (result.action === "added") {
            icon.classList.remove("far");
            icon.classList.add("fas");
            btn.classList.add("active");
          } else {
            icon.classList.remove("fas");
            icon.classList.add("far");
            btn.classList.remove("active");
          }
        }
      });
    } else {
      console.error("Wishlist toggle failed:", result.error);
      showNotification(result.error || "Gagal mengubah wishlist", "error");
    }
  } catch (error) {
    console.error("Error toggling wishlist:", error);
    showNotification("Terjadi kesalahan saat mengubah wishlist", "error");
  }
}

async function checkWishlistStatus() {
  console.log("Checking wishlist status for products on page...");

  const wishlistBtns = document.querySelectorAll("[data-product-id]");
  console.log("Found", wishlistBtns.length, "wishlist buttons");

  for (const btn of wishlistBtns) {
    const productId = btn.getAttribute("data-product-id");
    if (!productId) continue;

    try {
      const basePath = '/'; 
      const response = await fetch(
        `${basePath}api/wishlist.php?action=check&product_id=${productId}`
      );
      const result = await response.json();

      if (result.success && result.inWishlist) {
        const icon = btn.querySelector("i");
        const text = btn.querySelector(".wishlist-text");

        icon.classList.remove("far");
        icon.classList.add("fas");
        btn.classList.add("active");
        if (text) text.textContent = "Hapus dari Wishlist";

        console.log("Product", productId, "is in wishlist");
      } else {
        console.log("Product", productId, "is not in wishlist");
      }
    } catch (error) {
      console.error(
        "Error checking wishlist status for product",
        productId,
        ":",
        error
      );
    }
  }
}

function showNotification(message, type = "success") {
  const notification = document.createElement("div");
  notification.className = "notification";
  notification.textContent = message;

  const bgColor = type === "error" ? "#dc3545" : "#609966";

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
  }, 3000);
}

function toggleProfileMenu() {
  const dropdown = document.getElementById("profileDropdown");
  if (dropdown) {
    dropdown.classList.toggle("show");
  }
}

document.addEventListener("click", (event) => {
  const profileBtn = document.querySelector(".profile-btn");
  const dropdown = document.getElementById("profileDropdown");

  if (
    profileBtn &&
    dropdown &&
    !profileBtn.contains(event.target) &&
    !dropdown.contains(event.target)
  ) {
    dropdown.classList.remove("show");
  }
});

function searchProducts() {
  const searchInput = document.getElementById("searchInput");
  if (searchInput && searchInput.value.trim()) {
    const basePath = '/pages/'; 
    window.location.href = `${basePath}products.php?search=${encodeURIComponent(
      searchInput.value
    )}`;
  }
}

document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM Content Loaded");

  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        searchProducts();
      }
    });
  }

  console.log("Updating counts on page load...");
  updateCartCount();
  updateWishlistCount();

  setTimeout(() => {
    console.log("Checking wishlist status after delay...");
    checkWishlistStatus();
  }, 1000);
});

function clearCartOnLogout() {
  localStorage.removeItem("cart");
  cart = [];
  const cartCount = document.getElementById("cartCount");
  if (cartCount) {
    cartCount.textContent = "0";
  }
}

if (window.location.search.includes("cart_cleared=1")) {
  clearCartOnLogout();
  window.history.replaceState({}, document.title, window.location.pathname);
}

window.toggleWishlist = toggleWishlist;
window.addToCart = addToCart;
window.updateWishlistCount = updateWishlistCount;
window.updateCartCount = updateCartCount;
window.checkWishlistStatus = checkWishlistStatus;

console.log("Main.js loaded successfully");
console.log("Available functions:", {
  toggleWishlist: typeof toggleWishlist,
  addToCart: typeof addToCart,
  updateWishlistCount: typeof updateWishlistCount,
});
