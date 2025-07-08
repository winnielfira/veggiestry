# Website E-commerce

Sebuah website e-commerce lengkap yang dibangun menggunakan HTML, PHP, MySQL, dan  JavaScript.

## Fitur Utama

- **Autentikasi Pengguna**: Login, registrasi, dan logout
- **Manajemen Produk**: Jelajahi produk, lihat detail, dan pencarian
- **Keranjang Belanja**: Tambah/hapus item, kelola jumlah
- **Manajemen Pesanan**: Buat pesanan, lacak status, dan lihat riwayat
- **Dashboard Admin**: Kelola produk, pesanan, pelanggan, dan lihat laporan
- **Sistem Blog**: Baca artikel dan lihat detail blog
- **Wishlist**: Simpan produk favorit
- **Desain Responsif**: Interface yang mobile-friendly

## Penjelasan Struktur Proyek

```
📁 admin/               # Panel administrasi
📁 api/                 # Endpoint API backend
📁 assets/              # Aset statis
  ├── css/             # File stylesheet
  ├── images/          # File gambar
  └── js/              # File JavaScript
📁 auth/                # Sistem autentikasi
📁 config/              # File konfigurasi
📁 includes/            # Komponen yang dapat digunakan kembali
📁 pages/               # Halaman utama aplikasi
📁 scripts/             # Script setup database dan utilitas
📄 index.php            # Halaman utama
```

### Penjelasan Detail Setiap Folder

#### 📁 admin/
- **dashboard.php**: Panel kontrol admin dengan statistik dan manajemen

#### 📁 api/
Berisi semua endpoint API untuk komunikasi backend:
- **add-product.php**: Menambah produk baru
- **add-category.php**: Menambah kategori baru
- **add-to-cart.php**: Menambah item ke keranjang
- **confirm-order.php**: Konfirmasi pesanan
- **create-model.php**: Membuat model data
- **create-order.php**: Membuat pesanan baru
- **delete-product.php**: Menghapus produk
- **get-cart-count.php**: Mendapatkan jumlah item di keranjang
- **get-categories.php**: Mendapatkan daftar kategori
- **get-customers.php**: Mendapatkan daftar pelanggan
- **get-dashboard-stats.php**: Mendapatkan statistik dashboard
- **get-order-details.php**: Mendapatkan detail pesanan
- **get-order-items.php**: Mendapatkan item pesanan
- **get-orders.php**: Mendapatkan daftar pesanan
- **get-product.php**: Mendapatkan detail produk
- **get-products.php**: Mendapatkan daftar produk
- **get-reports.php**: Mendapatkan laporan
- **get-stock.php**: Mendapatkan stok produk
- **save-location.php**: Menyimpan lokasi pengiriman
- **submit-review.php**: Mengirim ulasan produk
- **update-order-status.php**: Mengupdate status pesanan
- **update-product.php**: Mengupdate produk
- **user-orders.php**: Mendapatkan pesanan pengguna
- **wishlist.php**: Manajemen wishlist

#### 📁 assets/
Berisi semua file statis:

**📁 css/**: File stylesheet untuk setiap halaman
- **account.css**: Styling halaman akun pengguna
- **admin-dashboard.css**: Styling dashboard admin
- **auth.css**: Styling halaman login/register
- **blog-detail.css**: Styling detail blog
- **blog.css**: Styling halaman blog
- **cart.css**: Styling keranjang belanja
- **checkout.css**: Styling halaman checkout
- **home.css**: Styling halaman utama
- **order-success.css**: Styling halaman sukses pesanan
- **product-detail.css**: Styling detail produk
- **products.css**: Styling halaman produk
- **style.css**: Styling utama global
- **wishlist.css**: Styling wishlist

**📁 images/**: Semua gambar website (logo, produk, banner, dll)

**📁 js/**: File JavaScript untuk interaktivitas
- **account.js**: Fungsi halaman akun
- **admin-dashboard.js**: Fungsi dashboard admin
- **auth.js**: Fungsi autentikasi
- **blog-detail.js**: Fungsi detail blog
- **cart.js**: Fungsi keranjang belanja
- **checkout.js**: Fungsi checkout
- **home.js**: Fungsi halaman utama
- **main.js**: Fungsi utama global
- **product-detail.js**: Fungsi detail produk

#### 📁 auth/
Sistem autentikasi pengguna:
- **login.php**: Proses login pengguna
- **logout.php**: Proses logout pengguna
- **register.php**: Proses registrasi pengguna baru

#### 📁 config/
- **database.php**: Konfigurasi koneksi database

#### 📁 includes/
Komponen yang dapat digunakan kembali:
- **footer.php**: Footer website
- **functions.php**: Fungsi PHP umum
- **header.php**: Header HTML
- **navbar.php**: Navigation bar

#### 📁 pages/
Halaman utama website:
- **account.php**: Halaman akun pengguna
- **blog-detail.php**: Halaman detail artikel blog
- **blog.php**: Halaman daftar blog
- **cart.php**: Halaman keranjang belanja
- **checkout.php**: Halaman checkout
- **order-success.php**: Halaman konfirmasi pesanan berhasil
- **product-detail.php**: Halaman detail produk
- **products.php**: Halaman daftar produk
- **wishlist.php**: Halaman wishlist

#### 📁 scripts/
Script utilitas dan setup:
- **create_admin_user.sql**: Script SQL untuk membuat user admin
- **create_placeholder_images.sql**: Script SQL untuk gambar placeholder
- **generate_placeholder_images.php**: Script PHP untuk generate gambar placeholder
- **quick_fix_blog.php**: Script perbaikan cepat blog
- **setup_database.js**: Script setup database

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server Apache/Nginx
- Node.js (untuk manajemen package)

## Cara Instalasi

### 1. Clone Repository
```bash
git clone <repository-url>
cd ecommerce-website
```

### 2. Setup Database
- Buat database MySQL baru
- Jalankan script setup:
  ```bash
  node scripts/setup_database.js
  ```
- Eksekusi script SQL:
  ```sql
  source scripts/create_admin_user.sql
  source scripts/create_placeholder_images.sql
  ```

### 3. Konfigurasi Database
Edit file `config/database.php` dengan kredensial database Anda:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nama_database_anda');
define('DB_USER', 'username_anda');
define('DB_PASS', 'password_anda');
?>
```

### 4. Install Dependencies
```bash
npm install
```

### 5. Setup Web Server
- Arahkan document root web server ke direktori proyek
- Pastikan PHP dikonfigurasi dengan benar
- Aktifkan mod_rewrite untuk Apache (jika menggunakan Apache)

### 6. Generate Placeholder Images (Opsional)
```bash
php scripts/generate_placeholder_images.php
```

## Cara Penggunaan

### Untuk Pengguna Umum:
1. **Akses website** di domain/localhost yang sudah dikonfigurasi
2. **Daftar akun baru** atau login dengan akun yang sudah ada
3. **Jelajahi produk** dan tambahkan ke keranjang
4. **Lakukan checkout** dan selesaikan pembayaran
5. **Lacak pesanan** di halaman akun

### Untuk Admin:
1. **Akses panel admin** di `/pages/admin-dashboard.php`
2. **Login dengan kredensial admin**
3. **Kelola produk** (tambah, edit, hapus)
4. **Kelola pesanan** dan update status
5. **Lihat laporan** dan statistik
6. **Kelola pelanggan** dan kategori

## Fitur Admin

- **Dashboard Statistik**: Melihat ringkasan penjualan, pesanan, dan pelanggan
- **Manajemen Produk**: Tambah, edit, hapus produk dengan gambar
- **Manajemen Pesanan**: Lihat semua pesanan dan update status
- **Manajemen Pelanggan**: Lihat data pelanggan dan riwayat pembelian
- **Laporan**: Generate laporan penjualan dan inventori
- **Manajemen Kategori**: Kelola kategori produk

## Endpoint API

### Produk
- `GET api/get-products.php` - Mendapatkan semua produk
- `GET api/get-product.php` - Mendapatkan detail produk
- `POST api/add-product.php` - Menambah produk baru (admin)
- `PUT api/update-product.php` - Update produk (admin)
- `DELETE api/delete-product.php` - Hapus produk (admin)

### Pesanan
- `GET api/get-orders.php` - Mendapatkan semua pesanan (admin)
- `GET api/user-orders.php` - Mendapatkan pesanan pengguna
- `POST api/create-order.php` - Membuat pesanan baru
- `PUT api/update-order-status.php` - Update status pesanan (admin)

### Keranjang
- `POST api/add-to-cart.php` - Tambah item ke keranjang
- `GET api/get-cart-count.php` - Mendapatkan jumlah item di keranjang

## Fitur Keamanan

- **Proteksi SQL Injection**: Menggunakan prepared statements
- **Pencegahan XSS**: Validasi dan sanitasi input
- **Proteksi CSRF**: Token keamanan untuk form
- **Hash Password**: Password di-hash dengan algoritma aman
- **Manajemen Session**: Session yang aman dan expired otomatis
- **Validasi Input**: Semua input divalidasi sebelum diproses

## Kustomisasi

### Menambah Halaman Baru
1. Buat file PHP di direktori `pages/`
2. Tambahkan CSS yang sesuai di `assets/css/`
3. Tambahkan JavaScript di `assets/js/`
4. Update navigasi di `includes/navbar.php`

### Mengubah Desain
- Style utama: `assets/css/style.css`
- Style spesifik halaman di file CSS masing-masing
- Desain responsif sudah termasuk

## Troubleshooting

### Masalah Umum

1. **Koneksi database gagal**
   - Periksa kredensial di `config/database.php`
   - Pastikan service MySQL berjalan

2. **Gambar tidak muncul**
   - Jalankan `php scripts/generate_placeholder_images.php`
   - Periksa permission file di `assets/images/`

3. **Akses admin ditolak**
   - Jalankan `scripts/create_admin_user.sql`
   - Periksa kredensial admin

4. **Error JavaScript**
   - Periksa console browser untuk error
   - Pastikan semua file JS ter-load dengan benar

## Pengembangan Lebih Lanjut

### Fitur yang Bisa Ditambahkan:
- **Payment Gateway**: Integrasi dengan Midtrans, PayPal, dll
- **Email Notification**: Notifikasi email untuk pesanan
- **Multi-language**: Dukungan multi bahasa
- **Live Chat**: Customer service chat
- **Review System**: Sistem ulasan produk
- **Inventory Management**: Manajemen stok otomatis

### Optimasi Performance:
- **Caching**: Redis atau Memcached
- **CDN**: Content Delivery Network
- **Database Optimization**: Query optimization dan indexing
- **Image Optimization**: Compress gambar otomatis

