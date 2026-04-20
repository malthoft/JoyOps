# 📘 Buku Panduan Penggunaan Sistem JoyOps (Laundry)

## 📌 Pendahuluan
JoyOps adalah sistem manajemen operasional laundry modern. Sistem ini dirancang dengan dua jenis hak akses utama:
1. **Admin (Pemilik/Manajer)**: Mengelola seluruh operasional, layanan, mesin, laporan keuangan, dan penjadwalan karyawan.
2. **Karyawan (Staf Operasional)**: Melakukan update status pencucian, melayani transaksi pendaftaran cucian pelanggan dari mesin, dan melihat histori/jadwal shift.

---

## 🔑 1. Akses & Login ke dalam Sistem
Semua pengguna (Admin maupun Karyawan) mengakses lewat situs (web) yang sama.
1. Buka alamat / URL sistem JoyOps di browser Anda.
2. Masukkan **Username** dan **Password** yang telah didaftarkan.
3. Klik tombol **Login**. 
   *(Sistem akan otomatis mengenali apakah akun Anda adalah Admin atau Karyawan dan menampilkan fitur yang sesuai dengan peran Anda.)*


---

## 👤 2. Panduan Karyawan (Staff Portal)
Karyawan berfokus pada kegiatan operasional pencucian harian. Antarmuka karyawan lebih ringkas.

### 2.1. Dashboard Karyawan
Menampilkan informasi singkat mengenai jadwal Anda.
- **Status Shift:** Memperlihatkan jadwal masuk & pulang Anda hari ini, beserta posisi stasiun kerja (Assigned Station).
- **Notifikasi Pintar:** Segala update dari Admin (misal: shift baru) dapat dilihat pada ikon lonceng di pojok kanan atas.


### 2.2. Jadwal & Shift Saya (My Shift)
Menu ini digunakan untuk memeriksa jadwal kerja harian Anda.
- **Weekly Schedule**: Di sini Anda dapat melihat jadwal bekerja dan jam kerja yang sudah dialokasikan oleh Admin untuk minggu berjalan (Senin-Minggu).
- **History**: Melihat catatan shift Anda yang telah selesai pada hari-hari sebelumnya.


### 2.3. Operasional Mesin & Mencatat Transaksi (Penting!)
Ini adalah **fungsi utama Karyawan** untuk memproses cucian dan secara tidak langsung bertindak sebagai kasir.
1. Masuk ke menu **Machines**. Anda akan melihat daftar semua unit mesin cuci/pengering beserta statusnya.
2. **Memulai Cucian Pelanggan (Menambah Pemasukan Baru):**
   - Pilih satu Mesin yang berstatus **Tersedia (Available)**.
   - Klik **Update Status** pada mesin tersebut.
   - Ubah status menjadi **Digunakan (In Use)**.
   - **Form Layanan akan Otomatis Muncul!** Pilih *Service* yang diinginkan (Misal: Cuci Kering Reguler).
   - Masukkan **Kuantitas/Qty** (contoh: 5 jika beratnya 5 Kg).
   - Klik **Save/Simpan**.
   - *Voila! Sistem akan merubah warna mesin dan otomatis mencatat uang transaksi pemasukan ke Keuangan berdasarkan Harga Layanan dikalikan Kuantitas.*
3. **Menyelesaikan Cucian:**
   - Setelah cucian selesai fisik dari mesin, klik lagi mesin yang tadi.
   - Ubah Status kembali menjadi **Tersedia (Available)** agar bisa digunakan pelanggan berikutnya.


### 2.4. Daftar Layanan (Services)
Menu yang berisi katalog layanan dan daftar harganya sebagai referensi tatkala Anda butuh menginformasikan harga kepada pembeli.

---

## 👑 3. Panduan Admin (Admin Portal)
Admin dapat melihat "dapur" aplikasi secara utuh dan memegang kontrol atas seluruh pemasukan dan pelaporan.

### 3.1. Dashboard Admin
Halaman ringkasan bisnis secara komprehensif. Anda bisa memantau beberapa metrik:
- **Pendapatan Hari Ini:** Menampilkan total pemasukan dari transaksi karyawan tanpa harus menghitung ulang (Real-time).
- **Status Mesin:** Cepat memantau mana mesin yang nganggur (Tersedia), mesin yang dipakai, maupun yang sedang rusak (Maintenance).
- **Transaksi & Staf:** Ringkasan jumlah transaksi tercatat di hari itu dan jumlah staf yang berstatus aktif.


### 3.2. Manajemen Mesin (Machines)
Mendatar semua inventaris mesin cuci/pengering yang ada di toko.
- Klik **Add Machine** untuk mendaftarkan alat baru (isi Kode Mesin, seperti MCB-01, dan Tipe Mesin: Washer/Dryer).
- Jika ada mesin yang mogok kerja, Admin bisa menonaktifkannya dengan melakukan *Update Status* menjadi **Maintenance** sehingga tak bisa ditekan oleh Karyawan.

### 3.3. Mengatur Katalog Tarif & Layanan (Services)
- Klik **Add Service** untuk menambah jenis layanan.
- Anda dapat menginput: Nama Layanan, Deskripsi, Harga (Per Kg/Pcs), dan estimasi waktu selesai. (Tarif ini nanti akan terkoneksi langsung dengan Pilihan Karyawan di panel mesin).


### 3.4. Menjadwalkan Pergeseran Karyawan (Shifts)
Fitur untuk merencanakan jam kerja tim.
- Klik opsi Registrasi **New Shift**.
- Pilih nama karyawan dari *dropdown*, tentukan Tanggalnya, mulainya jam berapa dan selesai jam berapa. Tentukan pula ia bertugas di bagian (Station) mana (Misal: Kasir/Lipat/Cuci).
- Shift yang didaftarkan akan berstatus "Scheduled" dan Karyawan akan melihatnya di *gadget* mereka.

### 3.5. Manajemen Pegawai & HR (Employees)
Jika ada karyawan baru yang direkrut, buatkan akun mereka di sini.
- Klik tombol tambah, isi Nama, Alamat, Email, *Username* serta *Password* (Minta karyawan mengingatnya dengan baik).
- Sistem akan mempersiapkan ruang masuk atas nama mereka. Anda pun bisa mengeditnya di hari mendatang jika ada kesalahan.


### 3.6. Buku Kas & Laporan Keuangan (Finance)
Tempat pembukuan uang masuk dan keluar secara mendetail.
- Semua uang yang disubmit di Panel Mesin oleh Karyawan sudah terkumpul secara rapi di sini.
- **Transaksi Manual:** Jika ada pengeluaran seperti (Beli detergen, Tagihan Listrik), klik **Record Transaction**, jadikan tipenya *Pengeluaran*, lalu tulis Jumlah dan Keterangannya.
- **Export Laporan (Download Laporan):** Pada panel *Export Report* (atau *Export Statement*), Anda bisa menentukan rentang tanggal awal dan akhir (misal 1 Januari - 31 Januari) dan sistem akan menghasilkan form Excel/PDF (bila disetting). Sangat cocok diberikan pada investor!


### 3.7. Pengaturan Bisnis (Settings)
Sesuaikan informasi *Company Profile*, Nama Toko/Laundry, Kontak HP, maupun alamat toko yang memengaruhi sistem.
