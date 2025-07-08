var currentTab = "dashboard";
var allProducts = [];
var allCategories = [];
var allOrders = [];
var allCustomers = [];

document.addEventListener("DOMContentLoaded", function () {
  console.log("Dashboard loaded");
  loadDashboardData();
  setupEventListeners();
});

function switchTab(tabName) {
  console.log("Switching to tab: " + tabName);

  var tabs = document.querySelectorAll(".tab-content");
  for (var i = 0; i < tabs.length; i++) {
    tabs[i].classList.remove("active");
  }

  var navItems = document.querySelectorAll(".nav-item");
  for (var i = 0; i < navItems.length; i++) {
    navItems[i].classList.remove("active");
  }

  var tabElement = document.getElementById(tabName);
  if (tabElement) {
    tabElement.classList.add("active");
  }

  var navElement = document.querySelector(
    "[onclick=\"switchTab('" + tabName + "')\"]"
  );
  if (navElement) {
    navElement.classList.add("active");
  }

  currentTab = tabName;

  if (tabName === "dashboard") {
    loadDashboardData();
  } else if (tabName === "products") {
    loadProducts();
  } else if (tabName === "categories") {
    loadCategories();
  } else if (tabName === "orders") {
    loadOrders();
  } else if (tabName === "customers") {
    loadCustomers();
  }
}

function loadDashboardData() {
  loadDashboardStats();
  loadOrderHistory();
}

function loadDashboardStats() {
  fetch("../api/get-dashboard-stats.php")
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.success !== false) {
        document.getElementById("total-sales").textContent = formatCurrency(
          data.total_revenue || 0
        );
        document.getElementById("total-orders").textContent =
          data.total_orders || 0;
        document.getElementById("total-customers").textContent =
          data.total_customers || 0;
      }
    })
    .catch(function (error) {
      console.error("Error loading dashboard stats:", error);
    });
}

function loadOrderHistory() {
  var tableBody = document.querySelector("#orderHistoryTable tbody");

  fetch("../api/get-orders.php?action=getAll")
    .then(function (response) {
      return response.json();
    })
    .then(function (orders) {
      if (Array.isArray(orders)) {
        var html = "";

        if (orders.length === 0) {
          html =
            '<tr><td colspan="5" class="empty-state"><i class="fas fa-shopping-cart"></i><h3>Belum ada pesanan</h3><p>Pesanan akan muncul di sini</p></td></tr>';
        } else {
          for (var i = 0; i < orders.length; i++) {
            var order = orders[i];
            var orderId = "#VG" + String(order.id).padStart(6, "0");
            var productName = order.product_names || "Mixed Items";
            var price =
              "Rp " + formatNumber(order.grand_total || order.total_amount);

            html += "<tr>";
            html += "<td>" + orderId + "</td>";
            html +=
              '<td><div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' +
              productName +
              "</div></td>";
            html += "<td>" + formatDate(order.created_at) + "</td>";
            html += "<td>" + price + "</td>";
            html +=
              '<td><span class="status-badge status-' +
              order.status +
              '">' +
              order.status +
              "</span></td>";
            html += "</tr>";
          }
        }

        tableBody.innerHTML = html;
      }
    })
    .catch(function (error) {
      console.error("Error loading order history:", error);
      tableBody.innerHTML =
        '<tr><td colspan="5" class="loading-row">Error loading data</td></tr>';
    });
}

function loadProducts() {
  console.log("Loading products...");

  var tableBody = document.querySelector("#productsTable tbody");

  fetch("../api/get-products.php?action=getAll")
    .then(function (response) {
      return response.json();
    })
    .then(function (products) {
      allProducts = products;
      displayProducts(products);
    })
    .catch(function (error) {
      console.error("Error loading products:", error);
      tableBody.innerHTML =
        '<tr><td colspan="6" class="loading-row">Error loading products</td></tr>';
    });
}

function displayProducts(products) {
  var tableBody = document.querySelector("#productsTable tbody");

  if (products.length === 0) {
    tableBody.innerHTML =
      '<tr><td colspan="6" class="empty-state"><i class="fas fa-box"></i><h3>Belum ada produk</h3><p>Tambahkan produk pertama Anda</p></td></tr>';
    return;
  }

  var html = "";
  for (var i = 0; i < products.length; i++) {
    var product = products[i];
    html += "<tr>";
    html += "<td><strong>" + product.name + "</strong></td>";
    html += "<td>" + (product.category || "-") + "</td>";
    html += "<td>Rp " + formatNumber(product.price) + "</td>";
    html += "<td>" + (product.stock || 0) + "</td>";
    html += "<td>" + formatDate(product.created_at) + "</td>";
    html +=
      '<td><div class="action-dropdown"><button class="action-btn" onclick="toggleDropdown(this)"><i class="fas fa-ellipsis-v"></i></button>';
    html += '<div class="dropdown-menu">';
    html +=
      '<button class="dropdown-item" onclick="editProduct(' +
      product.id +
      ')"><i class="fas fa-edit"></i> Edit</button>';
    html +=
      '<button class="dropdown-item danger" onclick="deleteProduct(' +
      product.id +
      ", '" +
      product.name +
      '\')"><i class="fas fa-trash"></i> Delete</button>';
    html += "</div></div></td>";
    html += "</tr>";
  }

  tableBody.innerHTML = html;
}

function searchProducts() {
  var searchTerm = document.getElementById("productSearch").value.toLowerCase();
  var filteredProducts = [];

  for (var i = 0; i < allProducts.length; i++) {
    var product = allProducts[i];
    if (
      product.name.toLowerCase().indexOf(searchTerm) !== -1 ||
      (product.category &&
        product.category.toLowerCase().indexOf(searchTerm) !== -1)
    ) {
      filteredProducts.push(product);
    }
  }

  displayProducts(filteredProducts);
}

function showAddProductModal() {
  var modalHtml = '<div class="modal-overlay" onclick="closeModal()">';
  modalHtml +=
    '<div class="modal-content larger-modal" onclick="event.stopPropagation()">';
  modalHtml += '<div class="modal-header">';
  modalHtml += '<h3><i class="fas fa-plus"></i> Tambah Produk Baru</h3>';
  modalHtml += '<button class="close-btn" onclick="closeModal()">×</button>';
  modalHtml += "</div>";
  modalHtml += '<form onsubmit="saveProduct(event)">';
  modalHtml += '<div class="form-group">';
  modalHtml += "<label>Nama Produk *</label>";
  modalHtml +=
    '<input type="text" name="name" required placeholder="Masukkan nama produk">';
  modalHtml += "</div>";
  modalHtml += '<div class="form-group">';
  modalHtml += "<label>Deskripsi</label>";
  modalHtml +=
    '<textarea name="description" rows="3" placeholder="Deskripsi produk"></textarea>';
  modalHtml += "</div>";
  modalHtml += '<div class="form-row">';
  modalHtml += '<div class="form-group">';
  modalHtml += "<label>Stok *</label>";
  modalHtml +=
    '<input type="number" name="stock" required min="0" placeholder="50">';
  modalHtml += "</div>";
  modalHtml += '<div class="form-group">';
  modalHtml += "<label>Harga *</label>";
  modalHtml +=
    '<input type="number" name="price" required min="0" placeholder="15000">';
  modalHtml += "</div>";
  modalHtml += "</div>";
  modalHtml += '<div class="form-group full-width">';
  modalHtml += "<label>Kategori</label>";
  modalHtml +=
    '<input type="text" name="category" placeholder="Sayuran Hijau, Buah-buahan, dll">';
  modalHtml += "</div>";
  modalHtml += '<div class="form-actions">';
  modalHtml +=
    '<button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>';
  modalHtml +=
    '<button type="submit" class="btn-primary">Simpan Produk</button>';
  modalHtml += "</div>";
  modalHtml += "</form>";
  modalHtml += "</div>";
  modalHtml += "</div>";

  document.body.insertAdjacentHTML("beforeend", modalHtml);
}

function editProduct(productId) {
  var product = null;
  for (var i = 0; i < allProducts.length; i++) {
    if (allProducts[i].id == productId) {
      product = allProducts[i];
      break;
    }
  }

  if (!product) return;

  var modalHtml = '<div class="modal-overlay" onclick="closeModal()">';
  modalHtml +=
    '<div class="modal-content larger-modal" onclick="event.stopPropagation()">';
  modalHtml += '<div class="modal-header">';
  modalHtml += '<h3><i class="fas fa-edit"></i> Edit Produk</h3>';
  modalHtml += '<button class="close-btn" onclick="closeModal()">×</button>';
  modalHtml += "</div>";
  modalHtml += '<form onsubmit="saveProduct(event, ' + productId + ')">';
  modalHtml += '<div class="form-group">';
  modalHtml += "<label>Nama Produk *</label>";
  modalHtml +=
    '<input type="text" name="name" required value="' + product.name + '">';
  modalHtml += "</div>";
  modalHtml += '<div class="form-group">';
  modalHtml += "<label>Deskripsi</label>";
  modalHtml +=
    '<textarea name="description" rows="3">' +
    (product.description || "") +
    "</textarea>";
  modalHtml += "</div>";
  modalHtml += '<div class="form-row">';
  modalHtml += '<div class="form-group">';
  modalHtml += "<label>Stok *</label>";
  modalHtml +=
    '<input type="number" name="stock" required min="0" value="' +
    (product.stock || 0) +
    '">';
  modalHtml += "</div>";
  modalHtml += '<div class="form-group">';
  modalHtml += "<label>Harga *</label>";
  modalHtml +=
    '<input type="number" name="price" required min="0" value="' +
    product.price +
    '">';
  modalHtml += "</div>";
  modalHtml += "</div>";
  modalHtml += '<div class="form-group full-width">';
  modalHtml += "<label>Kategori</label>";
  modalHtml +=
    '<input type="text" name="category" value="' +
    (product.category || "") +
    '">';
  modalHtml += "</div>";
  modalHtml += '<div class="form-actions">';
  modalHtml +=
    '<button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>';
  modalHtml +=
    '<button type="submit" class="btn-primary">Update Produk</button>';
  modalHtml += "</div>";
  modalHtml += "</form>";
  modalHtml += "</div>";
  modalHtml += "</div>";

  document.body.insertAdjacentHTML("beforeend", modalHtml);
}

function saveProduct(event, productId) {
  event.preventDefault();

  var formData = new FormData(event.target);
  var action = productId ? "update" : "create";
  formData.append("action", action);
  if (productId) formData.append("id", productId);

  fetch("../api/get-products.php", {
    method: "POST",
    body: formData,
  })
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.success) {
        closeModal();
        loadProducts();
      } else {
        showAlert(data.error, "error");
      }
    })
    .catch(function (error) {
      console.error("Error saving product:", error);
      showAlert("Terjadi kesalahan saat menyimpan produk", "error");
    });
}

function deleteProduct(productId, productName) {
  if (
    !confirm('Apakah Anda yakin ingin menghapus produk "' + productName + '"?')
  ) {
    return;
  }

  fetch("../api/get-products.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "action=delete&id=" + productId,
  })
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.success) {
        showAlert(data.message, "success");
        loadProducts();
      } else {
        showAlert(data.error, "error");
      }
    })
    .catch(function (error) {
      console.error("Error deleting product:", error);
      showAlert("Terjadi kesalahan saat menghapus produk", "error");
    });
}

function loadCustomers() {
  console.log("Loading customers...");

  var tableBody = document.querySelector("#customersTable tbody");
  tableBody.innerHTML =
    '<tr><td colspan="6" class="loading-row"><i class="fas fa-spinner fa-spin"></i> Memuat pelanggan...</td></tr>';

  fetch("../api/get-customers.php?action=getAll")
    .then(function (response) {
      return response.json();
    })
    .then(function (customers) {
      if (Array.isArray(customers)) {
        allCustomers = customers;
        displayCustomers(customers);
      } else {
        tableBody.innerHTML =
          '<tr><td colspan="6" class="loading-row">Error: Invalid data format</td></tr>';
      }
    })
    .catch(function (error) {
      console.error("Error loading customers:", error);
      tableBody.innerHTML =
        '<tr><td colspan="6" class="loading-row">Error loading customers</td></tr>';
    });
}

function displayCustomers(customers) {
  var tableBody = document.querySelector("#customersTable tbody");

  if (customers.length === 0) {
    tableBody.innerHTML =
      '<tr><td colspan="6" class="empty-state"><i class="fas fa-users"></i><h3>Belum ada pelanggan</h3><p>Pelanggan akan muncul di sini</p></td></tr>';
    return;
  }

  var html = "";
  for (var i = 0; i < customers.length; i++) {
    var customer = customers[i];
    var customerName = customer.full_name || customer.username;
    var totalOrders =
      customer.total_orders > 0
        ? customer.total_orders + " pesanan"
        : "Belum ada pesanan";
    var orderColor =
      customer.total_orders > 0 ? "var(--success)" : "var(--gray)";

    html += "<tr>";
    html +=
      "<td><div><strong>" +
      customerName +
      '</strong><br><small style="color: ' +
      orderColor +
      ';">' +
      totalOrders +
      "</small></div></td>";
    html += "<td>" + customer.email + "</td>";
    html +=
      "<td>" + (customer.last_order_formatted || "Belum pernah") + "</td>";
    html += "<td>" + (customer.phone || "-") + "</td>";
    html +=
      '<td><span style="font-weight: 600; color: var(--primary-color);">Rp ' +
      formatNumber(customer.total_spent || 0) +
      "</span></td>";
    html +=
      '<td><div class="action-dropdown"><button class="action-btn" onclick="toggleDropdown(this)"><i class="fas fa-ellipsis-v"></i></button>';
    html += '<div class="dropdown-menu">';
    html +=
      '<button class="dropdown-item" onclick="editCustomer(' +
      customer.id +
      ')"><i class="fas fa-edit"></i> Edit</button>';
    html +=
      '<button class="dropdown-item danger" onclick="deleteCustomer(' +
      customer.id +
      ", '" +
      customerName.replace(/'/g, "\\'") +
      '\')"><i class="fas fa-trash"></i> Delete</button>';
    html += "</div></div></td>";
    html += "</tr>";
  }

  tableBody.innerHTML = html;
}

function searchCustomers() {
  var searchTerm = document
    .getElementById("customerSearch")
    .value.toLowerCase();
  var filteredCustomers = [];

  for (var i = 0; i < allCustomers.length; i++) {
    var customer = allCustomers[i];
    if (
      (customer.full_name &&
        customer.full_name.toLowerCase().indexOf(searchTerm) !== -1) ||
      (customer.username &&
        customer.username.toLowerCase().indexOf(searchTerm) !== -1) ||
      (customer.email &&
        customer.email.toLowerCase().indexOf(searchTerm) !== -1) ||
      (customer.phone &&
        customer.phone.toLowerCase().indexOf(searchTerm) !== -1)
    ) {
      filteredCustomers.push(customer);
    }
  }

  displayCustomers(filteredCustomers);
}

function editCustomer(customerId) {
  console.log("Editing customer ID:", customerId);

  var customer = null;
  for (var i = 0; i < allCustomers.length; i++) {
    if (allCustomers[i].id == customerId) {
      customer = allCustomers[i];
      break;
    }
  }

  if (!customer) {
    showAlert("Customer tidak ditemukan", "error");
    return;
  }

  var modalHtml = '<div class="modal-overlay" onclick="closeModal()">';
  modalHtml +=
    '<div class="modal-content larger-modal" onclick="event.stopPropagation()">';
  modalHtml += '<div class="modal-header">';
  modalHtml += '<h3><i class="fas fa-user-edit"></i> Edit Customer</h3>';
  modalHtml += '<button class="close-btn" onclick="closeModal()">×</button>';
  modalHtml += "</div>";
  modalHtml += '<form onsubmit="saveCustomer(event, ' + customerId + ')">';
  modalHtml += '<div class="form-group">';
  modalHtml += '<label for="editCustomerFullName">Nama Lengkap *</label>';
  modalHtml +=
    '<input type="text" id="editCustomerFullName" name="full_name" required value="' +
    (customer.full_name || "") +
    '" maxlength="100">';
  modalHtml += "</div>";
  modalHtml += '<div class="form-group">';
  modalHtml += '<label for="editCustomerEmail">Email *</label>';
  modalHtml +=
    '<input type="email" id="editCustomerEmail" name="email" required value="' +
    (customer.email || "") +
    '" maxlength="100">';
  modalHtml += "</div>";
  modalHtml += '<div class="form-row">';
  modalHtml += '<div class="form-group">';
  modalHtml += '<label for="editCustomerPhone">No. Telepon</label>';
  modalHtml +=
    '<input type="tel" id="editCustomerPhone" name="phone" value="' +
    (customer.phone || "") +
    '" maxlength="20" placeholder="08xxxxxxxxxx">';
  modalHtml += "</div>";
  modalHtml += '<div class="form-group">';
  modalHtml += '<label for="editCustomerTotalSpent">Total Spent</label>';
  modalHtml +=
    '<input type="text" value="Rp ' +
    formatNumber(customer.total_spent || 0) +
    '" disabled style="background-color: var(--light-color); color: var(--gray);">';
  modalHtml += "</div>";
  modalHtml += "</div>";
  modalHtml += '<div class="form-group">';
  modalHtml += '<label for="editCustomerAddress">Alamat</label>';
  modalHtml +=
    '<textarea id="editCustomerAddress" name="address" rows="3" maxlength="500" placeholder="Alamat lengkap customer">' +
    (customer.address || "") +
    "</textarea>";
  modalHtml += "</div>";
  modalHtml +=
    '<div class="customer-stats" style="padding: 15px; background-color: var(--light-color); border-radius: 8px; margin-bottom: 20px;">';
  modalHtml +=
    '<h4 style="margin-bottom: 10px; color: var(--primary-color);">Statistik Customer:</h4>';
  modalHtml +=
    '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">';
  modalHtml +=
    "<div><strong>Total Pesanan:</strong> " +
    (customer.total_orders || 0) +
    "</div>";
  modalHtml +=
    "<div><strong>Member Sejak:</strong> " +
    (customer.created_at_formatted || "-") +
    "</div>";
  modalHtml +=
    "<div><strong>Pesanan Terakhir:</strong> " +
    (customer.last_order_formatted || "Belum pernah") +
    "</div>";
  modalHtml +=
    "<div><strong>Username:</strong> " + (customer.username || "-") + "</div>";
  modalHtml += "</div>";
  modalHtml += "</div>";
  modalHtml += '<div class="form-actions">';
  modalHtml +=
    '<button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>';
  modalHtml +=
    '<button type="submit" class="btn-primary" id="saveCustomerBtn">Update Customer</button>';
  modalHtml += "</div>";
  modalHtml += "</form>";
  modalHtml += "</div>";
  modalHtml += "</div>";

  document.body.insertAdjacentHTML("beforeend", modalHtml);

  setTimeout(function () {
    document.getElementById("editCustomerFullName").focus();
  }, 100);
}

function saveCustomer(event, customerId) {
  event.preventDefault();

  var submitBtn = document.getElementById("saveCustomerBtn");
  var originalBtnText = submitBtn.innerHTML;

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

  var formData = new FormData(event.target);
  formData.append("action", "update");
  formData.append("id", customerId);

  fetch("../api/get-customers.php", {
    method: "POST",
    body: formData,
  })
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.success) {
        closeModal();
        loadCustomers();
      } else {
        showAlert(data.error || "Gagal mengupdate customer", "error");
      }
    })
    .catch(function (error) {
      showAlert("Error: " + error.message, "error");
    })
    .finally(function () {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    });
}

function deleteCustomer(customerId, customerName) {
  if (
    !confirm(
      'Apakah Anda yakin ingin menghapus customer "' +
        customerName +
        '"?\n\nPeringatan: Customer yang memiliki pesanan tidak dapat dihapus.'
    )
  ) {
    return;
  }

  fetch("../api/get-customers.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "action=delete&id=" + customerId,
  })
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.success) {
        showAlert(data.message || "Customer berhasil dihapus", "success");
        loadCustomers();
      } else {
        showAlert(data.error || "Gagal menghapus customer", "error");
      }
    })
    .catch(function (error) {
      showAlert("Error: " + error.message, "error");
    });
}

function loadCategories() {
  console.log("Loading categories...");

  var tableBody = document.querySelector("#categoriesTable tbody");
  tableBody.innerHTML =
    '<tr><td colspan="3" class="loading-row"><i class="fas fa-spinner fa-spin"></i> Memuat kategori...</td></tr>';

  fetch("../api/get-categories.php?action=getAll")
    .then(function (response) {
      return response.text();
    })
    .then(function (text) {
      try {
        var categories = JSON.parse(text);
        if (Array.isArray(categories)) {
          allCategories = categories;
          displayCategories(categories);
        } else {
          tableBody.innerHTML =
            '<tr><td colspan="3" class="loading-row">Error: Invalid data format</td></tr>';
        }
      } catch (e) {
        tableBody.innerHTML =
          '<tr><td colspan="3" class="loading-row">Error: Invalid JSON response</td></tr>';
      }
    })
    .catch(function (error) {
      console.error("Error loading categories:", error);
      tableBody.innerHTML =
        '<tr><td colspan="3" class="loading-row">Network error loading categories</td></tr>';
    });
}

function displayCategories(categories) {
  var tableBody = document.querySelector("#categoriesTable tbody");

  if (categories.length === 0) {
    tableBody.innerHTML =
      '<tr><td colspan="3" class="empty-state"><i class="fas fa-tags"></i><h3>Belum ada kategori</h3><p>Tambahkan kategori pertama Anda</p></td></tr>';
    return;
  }

  var html = "";
  for (var i = 0; i < categories.length; i++) {
    var category = categories[i];
    html += "<tr>";
    html += "<td><strong>" + category.name + "</strong></td>";
    html += "<td>" + (category.product_count || 0) + " produk</td>";
    html +=
      '<td><div class="action-dropdown"><button class="action-btn" onclick="toggleDropdown(this)"><i class="fas fa-ellipsis-v"></i></button>';
    html += '<div class="dropdown-menu">';
    html +=
      '<button class="dropdown-item" onclick="editCategory(' +
      category.id +
      ')"><i class="fas fa-edit"></i> Edit</button>';
    html +=
      '<button class="dropdown-item danger" onclick="deleteCategory(' +
      category.id +
      ", '" +
      category.name +
      '\')"><i class="fas fa-trash"></i> Delete</button>';
    html += "</div></div></td>";
    html += "</tr>";
  }

  tableBody.innerHTML = html;
}

function showAddCategoryModal() {
  var modalHtml = '<div class="modal-overlay" onclick="closeModal()">';
  modalHtml += '<div class="modal-content" onclick="event.stopPropagation()">';
  modalHtml += '<div class="modal-header">';
  modalHtml += '<h3><i class="fas fa-plus"></i> Tambah Kategori Baru</h3>';
  modalHtml += '<button class="close-btn" onclick="closeModal()">×</button>';
  modalHtml += "</div>";
  modalHtml += '<form onsubmit="saveCategory(event)">';
  modalHtml += '<div class="form-group">';
  modalHtml += '<label for="categoryName">Nama Kategori *</label>';
  modalHtml +=
    '<input type="text" id="categoryName" name="name" required placeholder="Masukkan nama kategori" maxlength="100">';
  modalHtml += "</div>";
  modalHtml += '<div class="form-group">';
  modalHtml += '<label for="categoryDescription">Deskripsi</label>';
  modalHtml +=
    '<textarea id="categoryDescription" name="description" rows="3" placeholder="Deskripsi kategori (opsional)" maxlength="500"></textarea>';
  modalHtml += "</div>";
  modalHtml += '<div class="form-actions">';
  modalHtml +=
    '<button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>';
  modalHtml +=
    '<button type="submit" class="btn-primary" id="saveCategoryBtn">Simpan Kategori</button>';
  modalHtml += "</div>";
  modalHtml += "</form>";
  modalHtml += "</div>";
  modalHtml += "</div>";

  document.body.insertAdjacentHTML("beforeend", modalHtml);

  setTimeout(function () {
    document.getElementById("categoryName").focus();
  }, 100);
}

function saveCategory(event, categoryId) {
  event.preventDefault();

  var submitBtn = document.getElementById("saveCategoryBtn");
  var originalBtnText = submitBtn.innerHTML;

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

  var formData = new FormData(event.target);
  var action = categoryId ? "update" : "create";
  formData.append("action", action);
  if (categoryId) formData.append("id", categoryId);

  fetch("../api/get-categories.php", {
    method: "POST",
    body: formData,
  })
    .then(function (response) {
      return response.text();
    })
    .then(function (text) {
      try {
        var data = JSON.parse(text);
        if (data.success) {
          closeModal();
          loadCategories();
        } else {
          showAlert(data.error || "Gagal menyimpan kategori", "error");
        }
      } catch (e) {
        showAlert("Error: Server returned invalid response", "error");
      }
    })
    .catch(function (error) {
      showAlert("Error: " + error.message, "error");
    })
    .finally(function () {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    });
}

function editCategory(categoryId) {
  console.log("Editing category ID:", categoryId);

  var category = null;
  for (var i = 0; i < allCategories.length; i++) {
    if (allCategories[i].id == categoryId) {
      category = allCategories[i];
      break;
    }
  }

  if (!category) {
    showAlert("Kategori tidak ditemukan", "error");
    return;
  }

  var modalHtml = '<div class="modal-overlay" onclick="closeModal()">';
  modalHtml += '<div class="modal-content" onclick="event.stopPropagation()">';
  modalHtml += '<div class="modal-header">';
  modalHtml += '<h3><i class="fas fa-edit"></i> Edit Kategori</h3>';
  modalHtml += '<button class="close-btn" onclick="closeModal()">×</button>';
  modalHtml += "</div>";
  modalHtml += '<form onsubmit="saveCategory(event, ' + categoryId + ')">';
  modalHtml += '<div class="form-group">';
  modalHtml += '<label for="editCategoryName">Nama Kategori *</label>';
  modalHtml +=
    '<input type="text" id="editCategoryName" name="name" required value="' +
    (category.name || "") +
    '" maxlength="100">';
  modalHtml += "</div>";
  modalHtml += '<div class="form-group">';
  modalHtml += '<label for="editCategoryDescription">Deskripsi</label>';
  modalHtml +=
    '<textarea id="editCategoryDescription" name="description" rows="3" maxlength="500">' +
    (category.description || "") +
    "</textarea>";
  modalHtml += "</div>";
  modalHtml +=
    '<div class="category-stats" style="padding: 15px; background-color: var(--light-color); border-radius: 8px; margin-bottom: 20px;">';
  modalHtml +=
    "<div><strong>Jumlah Produk:</strong> " +
    (category.product_count || 0) +
    " produk</div>";
  modalHtml += "</div>";
  modalHtml += '<div class="form-actions">';
  modalHtml +=
    '<button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>';
  modalHtml +=
    '<button type="submit" class="btn-primary" id="saveCategoryBtn">Update Kategori</button>';
  modalHtml += "</div>";
  modalHtml += "</form>";
  modalHtml += "</div>";
  modalHtml += "</div>";

  document.body.insertAdjacentHTML("beforeend", modalHtml);

  setTimeout(function () {
    document.getElementById("editCategoryName").focus();
  }, 100);
}

function deleteCategory(categoryId, categoryName) {
  var category = null;
  for (var i = 0; i < allCategories.length; i++) {
    if (allCategories[i].id == categoryId) {
      category = allCategories[i];
      break;
    }
  }

  if (!category) {
    showAlert("Kategori tidak ditemukan", "error");
    return;
  }

  var productCount = parseInt(category.product_count) || 0;

  if (productCount > 0) {
    showAlert(
      'Tidak dapat menghapus kategori "' +
        categoryName +
        '" karena masih memiliki ' +
        productCount +
        " produk aktif",
      "error"
    );
    return;
  }

  if (
    !confirm(
      'Apakah Anda yakin ingin menghapus kategori "' + categoryName + '"?'
    )
  ) {
    return;
  }

  fetch("../api/get-categories.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "action=delete&id=" + categoryId,
  })
    .then(function (response) {
      return response.text();
    })
    .then(function (text) {
      try {
        var data = JSON.parse(text);
        if (data.success) {
          showAlert(
            'Kategori "' + categoryName + '" berhasil dihapus',
            "error"
          );
          loadCategories();
        } else {
          showAlert(data.error || "Gagal menghapus kategori", "error");
        }
      } catch (e) {
        showAlert("Error: Server returned invalid response", "error");
      }
    })
    .catch(function (error) {
      showAlert("Error: " + error.message, "error");
    });
}

function searchCategories() {
  var searchTerm = document
    .getElementById("categorySearch")
    .value.toLowerCase();

  if (!searchTerm) {
    displayCategories(allCategories);
    return;
  }

  var filteredCategories = [];
  for (var i = 0; i < allCategories.length; i++) {
    var category = allCategories[i];
    if (
      category.name.toLowerCase().indexOf(searchTerm) !== -1 ||
      (category.description &&
        category.description.toLowerCase().indexOf(searchTerm) !== -1)
    ) {
      filteredCategories.push(category);
    }
  }

  displayCategories(filteredCategories);
}

function loadOrders() {
  console.log("Loading orders...");

  fetch("../api/get-orders.php?action=getAll")
    .then(function (response) {
      return response.json();
    })
    .then(function (orders) {
      allOrders = orders;
      displayOrderList(orders);
    })
    .catch(function (error) {
      console.error("Error loading orders:", error);
    });
}

function displayOrderList(orders) {
  var tableBody = document.querySelector("#orderListTable tbody");

  if (orders.length === 0) {
    tableBody.innerHTML =
      '<tr><td colspan="6" class="empty-state"><i class="fas fa-shopping-cart"></i><h3>Belum ada pesanan</h3><p>Pesanan akan muncul di sini</p></td></tr>';
    return;
  }

  var html = "";
  for (var i = 0; i < orders.length; i++) {
    var order = orders[i];
    var orderId = "#VG" + String(order.id).padStart(6, "0");

    html += "<tr>";
    html += "<td>" + orderId + "</td>";
    html +=
      "<td><div><strong>" +
      (order.customer_name || "Guest") +
      "</strong><br><small>" +
      (order.customer_email || "") +
      "</small></div></td>";
    html += "<td>" + formatDate(order.created_at) + "</td>";
    html += "<td>" + (order.payment_method || "bank_transfer") + "</td>";
    html +=
      '<td><span class="status-badge status-' +
      order.status +
      '">' +
      order.status +
      "</span></td>";
    html +=
      '<td><select class="status-select" onchange="updateOrderStatus(' +
      order.id +
      ', this.value)" data-current="' +
      order.status +
      '">';
    html +=
      '<option value="pending"' +
      (order.status === "pending" ? " selected" : "") +
      ">Pending</option>";
    html +=
      '<option value="confirmed"' +
      (order.status === "confirmed" ? " selected" : "") +
      ">Dikemas</option>";
    html +=
      '<option value="shipped"' +
      (order.status === "shipped" ? " selected" : "") +
      ">Dikirim</option>";
    html +=
      '<option value="delivered"' +
      (order.status === "delivered" ? " selected" : "") +
      ">Selesai</option>";
    html +=
      '<option value="cancelled"' +
      (order.status === "cancelled" ? " selected" : "") +
      ">Dibatalkan</option>";
    html += "</select></td>";
    html += "</tr>";
  }

  tableBody.innerHTML = html;
}

function switchOrderTab(tabName) {
  var tabs = document.querySelectorAll(".tab-btn");
  for (var i = 0; i < tabs.length; i++) {
    tabs[i].classList.remove("active");
  }

  var contents = document.querySelectorAll(".order-tab-content");
  for (var i = 0; i < contents.length; i++) {
    contents[i].classList.remove("active");
  }

  event.target.classList.add("active");
  document.getElementById("order-" + tabName).classList.add("active");
}

function updateOrderStatus(orderId, newStatus) {
  if (!confirm('Ubah status pesanan ke "' + newStatus + '"?')) {
    var select = event.target;
    select.value = select.dataset.current;
    return;
  }

  fetch("../api/get-orders.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "action=updateStatus&id=" + orderId + "&status=" + newStatus,
  })
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.success) {
        showAlert("Status pesanan berhasil diupdate", "success");
        loadOrders();
        loadDashboardData();
      } else {
        showAlert(data.error || "Gagal mengupdate status", "error");
        loadOrders();
      }
    })
    .catch(function (error) {
      console.error("Error updating order status:", error);
      showAlert("Terjadi kesalahan saat mengupdate status", "error");
      loadOrders();
    });
}

function searchOrderById() {
  var orderId = document.getElementById("orderIdSearch").value.trim();
  var container = document.getElementById("orderDetailContainer");

  if (!orderId) {
    container.innerHTML =
      '<div class="empty-state"><i class="fas fa-search"></i><h3>Cari Order by ID</h3><p>Masukkan Order ID untuk melihat detail pesanan</p></div>';
    return;
  }

  container.innerHTML =
    '<div class="loading-state" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 15px;"></i><p>Memuat detail pesanan...</p></div>';

  fetch("../api/get-orders.php?action=getDetails&id=" + orderId)
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.success && data.order) {
        displayOrderDetail(data.order, data.items || []);
      } else {
        container.innerHTML =
          '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><h3>Order Tidak Ditemukan</h3><p>Order dengan ID "' +
          orderId +
          '" tidak ditemukan</p></div>';
      }
    })
    .catch(function (error) {
      console.error("Error fetching order details:", error);
      container.innerHTML =
        '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error</h3><p>Terjadi kesalahan saat memuat detail pesanan: ' +
        error.message +
        "</p></div>";
    });
}

function displayOrderDetail(order, items) {
  var container = document.getElementById("orderDetailContainer");

  var shippingCost =
    parseFloat(order.shipping_cost) ||
    (parseFloat(order.total_amount) >= 100000 ? 0 : 15000);
  var grandTotal = parseFloat(order.total_amount) + shippingCost;

  var itemsTableHtml = "";

  if (items && items.length > 0) {
    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      var unitPrice =
        parseFloat(item.unit_price) || parseFloat(item.price) || 0;
      var quantity = parseInt(item.quantity) || 1;
      var itemTotal = unitPrice * quantity;

      itemsTableHtml += "<tr>";
      itemsTableHtml +=
        "<td><strong>" + (item.product_name || "Produk") + "</strong>";
      if (item.category) {
        itemsTableHtml +=
          '<br><small style="color: var(--gray);">' +
          item.category +
          "</small>";
      }
      itemsTableHtml += "</td>";
      itemsTableHtml += "<td>Rp " + formatNumber(unitPrice) + "</td>";
      itemsTableHtml += "<td>" + quantity + "</td>";
      itemsTableHtml += "<td>Rp " + formatNumber(itemTotal) + "</td>";
      itemsTableHtml += "</tr>";
    }
  } else {
    itemsTableHtml =
      "<tr><td>Mixed Products</td><td>-</td><td>-</td><td>Rp " +
      formatNumber(order.total_amount) +
      "</td></tr>";
  }

  var detailHtml = '<div class="order-detail-card">';
  detailHtml += '<div class="section-header">';
  detailHtml +=
    "<h3>Detail Pesanan #VG" + String(order.id).padStart(6, "0") + "</h3>";
  detailHtml +=
    '<button class="pdf-btn" onclick="exportToPDF(' +
    order.id +
    ')"><i class="fas fa-file-pdf"></i> Export PDF</button>';
  detailHtml += "</div>";

  detailHtml += '<div class="customer-info">';
  detailHtml +=
    '<div class="info-item"><span class="info-label">Customer:</span><span class="info-value">' +
    (order.customer_name || "Guest") +
    "</span></div>";
  detailHtml +=
    '<div class="info-item"><span class="info-label">Email:</span><span class="info-value">' +
    (order.customer_email || "-") +
    "</span></div>";
  detailHtml +=
    '<div class="info-item"><span class="info-label">Phone:</span><span class="info-value">' +
    (order.customer_phone || "-") +
    "</span></div>";
  detailHtml +=
    '<div class="info-item"><span class="info-label">Order Date:</span><span class="info-value">' +
    formatDate(order.created_at) +
    "</span></div>";
  detailHtml +=
    '<div class="info-item"><span class="info-label">Status:</span><span class="info-value"><span class="status-badge status-' +
    order.status +
    '">' +
    order.status +
    "</span></span></div>";
  detailHtml +=
    '<div class="info-item"><span class="info-label">Payment:</span><span class="info-value">' +
    (order.payment_method || "bank_transfer") +
    "</span></div>";
  detailHtml += "</div>";

  detailHtml += '<div class="order-items-table">';
  detailHtml += "<h4>Items Pesanan</h4>";
  detailHtml += '<table class="data-table">';
  detailHtml +=
    "<thead><tr><th>Produk</th><th>Harga Satuan</th><th>Kuantitas</th><th>Total</th></tr></thead>";
  detailHtml += "<tbody>";
  detailHtml += itemsTableHtml;
  detailHtml +=
    '<tr class="total-row"><td colspan="3"><strong>Sub Total:</strong></td><td><strong>Rp ' +
    formatNumber(order.total_amount) +
    "</strong></td></tr>";
  detailHtml +=
    '<tr class="total-row"><td colspan="3"><strong>Shipping Cost:</strong></td><td><strong>Rp ' +
    formatNumber(shippingCost) +
    "</strong></td></tr>";
  detailHtml +=
    '<tr class="total-row" style="background-color: var(--accent-color); font-size: 16px;"><td colspan="3"><strong>Grand Total:</strong></td><td><strong>Rp ' +
    formatNumber(grandTotal) +
    "</strong></td></tr>";
  detailHtml += "</tbody>";
  detailHtml += "</table>";
  detailHtml += "</div>";

  if (order.notes) {
    detailHtml +=
      '<div class="order-notes" style="margin-top: 20px; padding: 15px; background-color: var(--light-color); border-radius: 8px;">';
    detailHtml += '<h4 style="margin-bottom: 10px;">Catatan Pesanan:</h4>';
    detailHtml +=
      '<p style="margin: 0; color: var(--gray);">' + order.notes + "</p>";
    detailHtml += "</div>";
  }

  detailHtml += "</div>";

  container.innerHTML = detailHtml;
}

function exportToPDF(orderId) {
  window.open(
    "../api/get-orders.php?action=exportPDF&order_id=" + orderId,
    "_blank"
  );
}

function setupEventListeners() {
  document.addEventListener("click", function (event) {
    if (!event.target.closest(".action-dropdown")) {
      var dropdowns = document.querySelectorAll(".dropdown-menu");
      for (var i = 0; i < dropdowns.length; i++) {
        dropdowns[i].classList.remove("show");
      }
    }
  });
}

function toggleDropdown(button) {
  var dropdown = button.nextElementSibling;
  var isOpen = dropdown.classList.contains("show");

  var allDropdowns = document.querySelectorAll(".dropdown-menu");
  for (var i = 0; i < allDropdowns.length; i++) {
    allDropdowns[i].classList.remove("show");
  }

  if (!isOpen) {
    dropdown.classList.add("show");
  }
}

function closeModal() {
  var modal = document.querySelector(".modal-overlay");
  if (modal) {
    modal.remove();
  }
}

function formatCurrency(amount) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}

function formatNumber(number) {
  return new Intl.NumberFormat("id-ID").format(number);
}

function formatDate(dateString) {
  var date = new Date(dateString);
  return date.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

function showAlert(message, type) {
  type = type || "info";

  var alertDiv = document.createElement("div");
  alertDiv.className = "alert alert-" + type;
  alertDiv.style.cssText =
    "position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 20px; border-radius: 8px; color: white; font-weight: 500; max-width: 300px; animation: slideInRight 0.3s ease;";

  var colors = {
    success: "#28a745",
    error: "#dc3545",
    warning: "#ffc107",
    info: "#17a2b8",
  };

  alertDiv.style.backgroundColor = colors[type] || colors.info;
  alertDiv.textContent = message;

  document.body.appendChild(alertDiv);

  setTimeout(function () {
    alertDiv.style.animation = "slideOutRight 0.3s ease";
    setTimeout(function () {
      if (alertDiv.parentNode) {
        alertDiv.parentNode.removeChild(alertDiv);
      }
    }, 300);
  }, 3000);
}

var style = document.createElement("style");
style.textContent =
  "@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } } @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }";
document.head.appendChild(style);
