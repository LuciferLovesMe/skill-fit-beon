# Backend - Sistem Informasi Administrasi RT

Ini adalah repository bagian **Backend (REST API)** untuk Sistem Administrasi RT, dikembangkan menggunakan framework Laravel dan DBMS MySQL. (Tanpa Docker).

_Repository Frontend (React) dapat diakses di:_ `https://github.com/LuciferLovesMe/skill-fit-beon-react`

## 🛠️ Prasyarat Sistem

Sebelum menjalankan aplikasi, pastikan komputer Anda telah terinstal:

1. **PHP** (Minimal versi 8.1+)
2. **Composer** (Package manager PHP)
3. **MySQL Database** (Bisa menggunakan XAMPP, Laragon, atau native MySQL)

## ⚙️ Panduan Instalasi (Langkah demi Langkah)

**1. Clone Repository**

```bash
git clone https://github.com/LuciferLovesMe/skill-fit-beon
cd skill-fit-beon
```

**2. Install Dependencies**

```bash
composer install
```

**3. Setup Environment (.env)**

Duplikat file konfigurasi environment bawaan:

```bash
cp .env.example .env
```

**4. Generate Application Key**

```bash
php artisan key:generate
```

**5. Konfigurasi Database**

- Buka aplikasi XAMPP/Laragon Anda dan pastikan service MySQL berjalan.
- Buat database baru (kosong) bernama sistem_rt.
- Buka file .env di text editor Anda, lalu sesuaikan kredensial database berikut:

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_rt
DB_USERNAME=root
DB_PASSWORD=
```

(Isi DB_PASSWORD jika MySQL Anda menggunakan password, biarkan kosong jika default).

**6. Jalankan Migrasi dan Database Seeder**

```bash
php artisan migrate
php artisan module:migrate Resident
php artisan module:migrate House
php artisan module:migrate Finance
php artisan db:seed
```

**7. Hubungkan Storage Local (Untuk Foto KTP Warga)**

```bash
php artisan storage:link
```

**8. Jalankan Server Backend**

```bash
php artisan serve
```

Backend API sekarang berjalan di http://127.0.0.1:8000. Biarkan terminal ini tetap terbuka

## 🔑 Kredensial Pengujian (Digenerate oleh Seeder)

Gunakan kredensial ini nanti saat Anda login di aplikasi Frontend:

- Email: admin@rt.com
- Password: password123
