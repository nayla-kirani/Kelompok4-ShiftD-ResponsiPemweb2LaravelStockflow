# StockFlow

## Sistem Manajemen Inventori Berbasis Web

StockFlow adalah aplikasi manajemen inventori berbasis web yang digunakan untuk mengelola data produk, stok barang, supplier, pembelian, serta pergerakan stok secara terpusat.

Aplikasi ini dibuat untuk membantu proses pencatatan dan pemantauan persediaan agar lebih mudah dilakukan melalui satu dashboard.

## Fitur

- Dashboard ringkasan kondisi inventori
- Manajemen produk
- Manajemen kategori
- Manajemen supplier
- Purchase Order
- Pencatatan pergerakan stok
- Penyesuaian stok
- Informasi stok rendah
- Informasi barang habis
- Laporan inventori
- Grafik dan statistik
- Login dan autentikasi pengguna
- Hak akses berdasarkan role pengguna

## Teknologi

- Laravel 13
- PHP 8.4
- Vue.js 3
- TypeScript
- MySQL 8
- Docker
- Docker Compose
- Nginx
- Tailwind CSS
- Chart.js

## Struktur Project

stockflow/
├── api/
├── frontend/
├── docker/
├── screenshots/
├── docker-compose.yml
├── .dockerignore
├── .gitignore
├── LICENSE
└── README.md

## Menjalankan Project

Pastikan Docker Desktop sudah berjalan.

Jalankan perintah:

docker compose up -d --build

Cek status container:

docker compose ps

Akses aplikasi melalui:

http://localhost:3001

## Konfigurasi Local

Frontend : localhost:3001
Backend  : localhost:8006
MySQL    : localhost:3309

Port tersebut digunakan agar tidak bentrok dengan project lain pada komputer.

## Akun Demo

Admin
Email    : admin@inventory.local
Password : password

Manager
Email    : manager@inventory.local
Password : password

Staff
Email    : staff@inventory.local
Password : password

## Tampilan Aplikasi

Dashboard:
screenshots/01.png

Produk:
screenshots/02.png

Kategori:
screenshots/03.png

Supplier:
screenshots/04.png

Purchase Order:
screenshots/05.png

## Pengembangan

Untuk menghentikan seluruh service:

docker compose down

Untuk menjalankan kembali:

docker compose up -d

## Deployment

Project dapat dijalankan pada server yang mendukung Docker dan Docker Compose.

Konfigurasi environment dan database perlu disesuaikan dengan server sebelum deployment production.

## Referensi

Project ini dikembangkan dengan menggunakan project Inventory Management System sebagai referensi awal.

Referensi proyek: Inventory Management System — AndrejWeb.

Ketentuan penggunaan dan atribusi mengikuti file LICENSE yang terdapat pada repository ini.
