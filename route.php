<?php

/**
 * =====================================================================
 * CATATAN BELAJAR LARAVEL ROUTING (BAGIAN 1: ROUTE 1 SAMPAI 4)
 * Rangkuman lengkap dari diskusi, penjelasan, kondisi, dan contoh kode.
 * =====================================================================
 */

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. BASIC ROUTE (Route Dasar)
|--------------------------------------------------------------------------
| - Penjelasan: Jalur URL paling dasar yang menerima alamat web dan 
|   langsung mengembalikan respon teks atau tampilan.
| - Kondisi Penggunaan: Dipakai untuk halaman-halaman statis yang sederhana 
|   dan tidak memerlukan logika atau database yang rumit, seperti halaman utama (/), 
|   halaman tentang kami (/tentang), atau halaman kontak.
*/
Route::get('/', function () {
    return "Selamat datang di halaman utama website!";
});

Route::get('/tentang', function () {
    return "Ini adalah halaman tentang kampus kami.";
});


/*
|--------------------------------------------------------------------------
| 2. ROUTE WITH PARAMETERS (Route dengan Parameter Dinamis)
|--------------------------------------------------------------------------
| - Penjelasan: Rute yang di dalamnya menangkap potongan data dari URL 
|   menggunakan kurung kurawal {nama_variabel}.
| - Kondisi Penggunaan: Dipakai ketika halaman web membutuhkan data spesifik 
|   dari URL, seperti melihat profil user berdasarkan ID (/mahasiswa/1) atau 
|   membaca artikel berdasarkan slug.
*/
Route::get('/mahasiswa/{id}', function ($id) {
    return "Menampilkan profil mahasiswa dengan ID: " . $id;
});

// Contoh dengan lebih dari satu parameter
Route::get('/kategori/{namaKategori}/artikel/{slug}', function ($namaKategori, $slug) {
    return "Kategori: {$namaKategori}, Membaca artikel: {$slug}";
});


/*
|--------------------------------------------------------------------------
| 3. OPTIONAL PARAMETERS (Route dengan Parameter Opsional)
|--------------------------------------------------------------------------
| - Penjelasan: Mirip seperti Route Parameter, tetapi variabel di dalam URL 
|   diberi tanda tanya (?) yang artinya tidak wajib diisi. Jika kosong, kita 
|   bisa memberikan nilai bawaan (default value).
| - Kondisi Penggunaan: Dipakai untuk halaman pencarian atau sapaan di mana 
|   pengguna boleh mengisi atau mengosongkan parameter di URL.
*/
Route::get('/sapa/{nama?}', function ($nama = "Pengunjung") {
    return "Halo, selamat datang " . $nama . "!";
});


/*
|--------------------------------------------------------------------------
| 4. NAMED ROUTE (Route yang Diberi Nama)
|--------------------------------------------------------------------------
| - Penjelasan: Memberi "nama panggilan" resmi pada sebuah rute menggunakan 
|   metode chaining ->name('nama.rute').
| - Kondisi Penggunaan: Sangat penting agar kita tidak perlu menulis URL mentah 
|   yang panjang saat melakukan redirect atau pemanggilan di view/Blade. 
|   Jika URL berubah di masa depan, kita cukup mengubahnya sekali di file route.
*/
Route::get('/user/pengaturan/profil-saya', function () {
    return "Ini halaman profil.";
})->name('profile.index');

// Cara menggunakannya untuk pengalihan (redirect) di dalam kode PHP:
Route::get('/arah-baru', function () {
    // Alih-alih menulis URL panjang, cukup panggil namanya!
    return redirect()->route('profile.index');
});


/*
|--------------------------------------------------------------------------
| STUDI KASUS GABUNGAN: Alur Validasi, Parameter, dan Redirect
|--------------------------------------------------------------------------
| Contoh bagaimana rute-rute di atas bekerja bersama dalam satu alur nyata:
| 1. User membuka /khs/99 (semester tidak valid).
| 2. Laravel memproses kondisi if, mendeteksi error, lalu mengalihkan 
|    (redirect) ke Named Route halaman error.
*/

// Halaman tujuan akhir (Named Route)
Route::get('/khs/utama', function () {
    return "<h1>Ini adalah Halaman Utama KHS Anda</h1>";
})->name('khs.index');

// Halaman jika terjadi error / semester tidak valid (Named Route)
Route::get('/error-semester', function () {
    return "<h3 style='color: red;'>Maaf, semester yang Anda cari tidak tersedia (Hanya ada semester 1 sampai 8).</h3>";
})->name('khs.error');

// Route dengan Parameter & Proses Tambahan (Validasi & Redirect)
Route::get('/khs/{semester}', function ($semester) {
    
    // Proses Tambahan (Logika Validasi)
    if ($semester < 1 || $semester > 8) {
        // JIKA SALAH: Dialihkan ke Named Route halaman error
        return redirect()->route('khs.error');
    }

    // JIKA BENAR: Tampilkan data KHS
    return "Menampilkan data KHS untuk Semester ke-<b>{$semester}</b>.";
});

// Route dengan Optional Parameter / Default Redirect
Route::get('/khs', function () {
    // Jika user buka /khs tanpa parameter, arahkan otomatis ke semester 1
    return redirect('/khs/1');
});


























/**
 * =====================================================================
 * CATATAN BELAJAR LARAVEL ROUTING (BAGIAN 2: ROUTE 5 SAMPAI 7)
 * Rangkuman lengkap dari diskusi, penjelasan kondisi, middleware custom, 
 * hingga contoh kode nyata.
 * =====================================================================
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Closure;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| 5. ROUTE GROUP (Mengelompokkan Rute)
|--------------------------------------------------------------------------
| - Penjelasan: Fitur untuk membungkus beberapa rute sekaligus yang memiliki 
|   kesamaan prefiks URL (awalan yang sama) atau aturan yang serupa.
| - Kondisi Penggunaan: Sangat berguna saat membuat panel admin (misal: semua 
|   url diawali /admin/dashboard, /admin/mahasiswa, /admin/dosen), sehingga 
|   kita tidak perlu mengetik kata '/admin' secara berulang-ulang.
*/

Route::prefix('admin')->group(function () {
    
    // URL aslinya menjadi: /admin/dashboard
    Route::get('/dashboard', function () {
        return "Halaman Dashboard Admin";
    });

    // URL aslinya menjadi: /admin/mahasiswa
    Route::get('/mahasiswa', function () {
        return "Halaman Kelola Data Mahasiswa oleh Admin";
    });

});


/*
|--------------------------------------------------------------------------
| 6. ROUTE MIDDLEWARE (Gerbang Pengaman / Satpam Rute)
|--------------------------------------------------------------------------
| - Penjelasan: "Satpam" atau pos pemeriksaan yang mencegat request sebelum 
|   masuk ke dalam rute/controller. Berfungsi untuk mengecek hak akses, status 
|   login, atau aturan bisnis tertentu.
| - Jenis Middleware Bawaan Laravel yang Sering Dipakai:
|   1. 'auth'    -> Memeriksa apakah user SUDAH login (jika belum, di-redirect ke /login).
|   2. 'guest'   -> Kebalikan auth, mengecek apakah user BELUM login (untuk halaman login/register).
|   3. 'verified'-> Memeriksa apakah email user sudah diverifikasi.
| 
| - Contoh Penerapan Middleware Bawaan di Route:
*/

// A. Kondisi Guest: Halaman login tidak boleh dibuka oleh orang yang sudah login
Route::get('/login', function () {
    return "Ini adalah form login.";
})->middleware('guest');

// B. Kondisi Auth: Halaman profil rahasia yang wajib sudah login
Route::get('/profil-saya', function () {
    return "Halo, ini halaman profil rahasia kamu.";
})->middleware('auth');


/*
|--------------------------------------------------------------------------
| CARA MEMBUAT CUSTOM MIDDLEWARE SENDIRI (Misal: Satpam Cek Role Dosen)
|--------------------------------------------------------------------------
| Langkah-langkah lengkap di dunia nyata:
| 1. Buat file via terminal: php artisan make:middleware CekRoleDosen
| 2. Tulis logika di dalam file app/Http/Middleware/CekRoleDosen.php:
|
|    class CekRoleDosen {
|        public function handle(Request $request, Closure $next): Response {
|            $user = auth()->user();
|            if ($user && $user->role === 'dosen') {
|                return $next($request); // Silakan lewat
|            }
|            abort(403, 'MAAF! Halaman ini khusus untuk Dosen.'); // Usir!
|        }
|    }
|
| 3. Daftarkan alias di bootstrap/app.php:
|    ->withMiddleware(function (Middleware $middleware) {
|        $middleware->alias([
|            'dosen.only' => \App\Http\Middleware\CekRoleDosen::class,
|        ]);
|    })
|
| 4. Pasang ke Route seperti di bawah ini:
*/

Route::get('/dosen/rekap-nilai', function () {
    return "Ini adalah halaman rekap nilai khusus dosen.";
})->middleware(['auth', 'dosen.only']); // Bisa digabung berurutan!


/*
|--------------------------------------------------------------------------
| 7. REDIRECT ROUTE (Jalur Pengalihan Permanen / Sementara)
|--------------------------------------------------------------------------
| - Penjelasan: Jalur pintas untuk mengalihkan suatu alamat URL lama 
|   langsung menuju ke alamat URL yang baru secara otomatis.
| - Kondisi Penggunaan: Dipakai ketika struktur website berubah (misal alamat 
|   '/hubungi-kami' diubah menjadi '/kontak-kami'), sehingga pengguna atau 
|   bot mesin pencari yang mengakses alamat lama tidak mendapati error 404, 
|   melainkan langsung dipindahkan secara mulus ke halaman baru.
*/

// Jika ada user buka /hubungi-kami, langsung dialihkan ke /kontak-kami
Route::redirect('/hubungi-kami', '/kontak-kami');

Route::get('/kontak-kami', function () {
    return "Silakan hubungi kami di email: admin@kampus.ac.id";
});



























// =============================put dan patch==================================================================


/**
 * =====================================================================
 * CATATAN BELAJAR LARAVEL ROUTING (BAGIAN 3: METODE PUT & PATCH)
 * Rangkuman lengkap dari diskusi, penjelasan kondisi, method spoofing,
 * perbedaan PUT vs PATCH, dan tips keamanan data saat update.
 * =====================================================================
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| 1. APA ITU METODE PUT DAN PATCH?
|--------------------------------------------------------------------------
| - PUT: Digunakan untuk MEMPERBARUI atau MENGGANTI seluruh isi dari sebuah 
|   data/resource secara total (Full Replacement).
| - PATCH: Digunakan untuk MEMPERBARUI sebagian resource saja (Partial Update), 
|   misalnya hanya mengubah satu kolom tertentu (seperti status keaktifan).
|
| - Kondisi Penggunaan: 
|   * Gunakan PUT untuk form edit lengkap (misal: Edit Profil Mahasiswa).
|   * Gunakan PATCH untuk ubah data parsial (misal: Tombol Aktif/Non-aktif status).
*/


/*
|--------------------------------------------------------------------------
| 2. TANTANGAN HTML & SOLUSI "METHOD SPOOFING"
|--------------------------------------------------------------------------
| - Masalah: Browser di HTML form secara default HANYA mendukung metode GET dan POST. 
|   Tidak ada tombol kirim berlabel PUT atau DELETE di HTML murni.
| - Solusi Laravel: Tetap menggunakan method="POST" pada form HTML, lalu menyisipkan 
|   fungsi blade khusus yaitu @method('PUT') atau @method('PATCH') di dalamnya.
*/


/*
|--------------------------------------------------------------------------
| 3. CONTOH KODE RUTE (routes/web.php)
|--------------------------------------------------------------------------
*/

// A. Rute untuk menerima proses UPDATE total dengan PUT
Route::put('/mahasiswa/{id}', function (Request $request, $id) {
    $namaBaru   = $request->input('nama');
    $nimBaru    = $request->input('nim');
    $alamatBaru = $request->input('alamat');

    return "Data mahasiswa ID {$id} berhasil diperbarui secara total!<br>" .
           "Nama: {$namaBaru} | NIM: {$nimBaru} | Alamat: {$alamatBaru}";
});

// B. Rute untuk menerima proses UPDATE sebagian dengan PATCH
Route::patch('/mahasiswa/{id}/status', function (Request $request, $id) {
    $statusBaru = $request->input('status');

    return "Status mahasiswa ID {$id} berhasil diubah menjadi: " . $statusBaru;
});


/*
|--------------------------------------------------------------------------
| 4. CONTOH PENULISAN FORM HTML (Di file Blade / .blade.php)
|--------------------------------------------------------------------------
| Perhatikan bahwa action form tetap mengarah ke URL, method-nya POST, 
| tapi diselipi @method('PUT') atau @method('PATCH').
*/
/*
    <form action="/mahasiswa/1" method="POST">
        @csrf <!-- Pengaman wajib CSRF -->
        @method('PUT') <!-- Trik Method Spoofing agar terbaca sebagai PUT -->

        <label>Nama:</label>
        <input type="text" name="nama" value="Budi Santoso">

        <label>NIM:</label>
        <input type="text" name="nim" value="25241010">

        <button type="submit">Perbarui Data</button>
    </form>
*/


/*
|--------------------------------------------------------------------------
| 5. CATATAN KRITIS / FAQ SEPUTAR PUT & PATCH
|--------------------------------------------------------------------------
| Q: Bukankah kita tetap bisa pakai POST saja untuk semua hal (termasuk update)?
| A: Secara teknis bisa, tapi melanggar standar global web (REST API) dan membuat 
|    kita kehilangan fitur otomatisasi keren Laravel seperti Route::resource().
|
| Q: Apakah PUT yang mengosongkan kolom form otomatis membuat data di database jadi NULL?
| A: Tidak otomatis. Tergantung cara kita menulis kodenya di Controller/Model. 
|    Jika menggunakan fungsi update() Laravel yang benar (seperti $model->update($request->all()) 
|    atau memfilter data yang masuk), kolom yang tidak dikirim dari form akan aman 
|    dan tidak tertimpa nilai null.
*/














































//========================================delete=================================================================
/**
 * =====================================================================
 * CATATAN BELAJAR LARAVEL ROUTING (BAGIAN 4: METODE DELETE)
 * Rangkuman lengkap dari diskusi, penjelasan kondisi, method spoofing,
 * serta contoh kode rute dan form HTML untuk penghapusan data.
 * =====================================================================
 */

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. APA ITU METODE DELETE?
|--------------------------------------------------------------------------
| - Penjelasan: Metode HTTP yang digunakan khusus untuk MENGHAPUS atau 
|   MENYINGKIRKAN sebuah resource (data) dari server atau database.
| - Kondisi Penggunaan: Saat pengguna menekan tombol "Hapus" atau "Delete" 
|   pada sebuah data (misal: menghapus data mahasiswa, menghapus produk, 
|   atau menghapus artikel postingan). Bersifat permanen atau soft delete.
*/


/*
|--------------------------------------------------------------------------
| 2. TANTANGAN HTML & SOLUSI "METHOD SPOOFING"
|--------------------------------------------------------------------------
| - Masalah: Browser web standar melalui tag HTML <form> secara default 
|   HANYA mendukung dua metode saja, yaitu GET dan POST. Browser tidak 
|   memiliki tombol kirim berlabel DELETE.
| - Solusi Laravel: Menggunakan trik "Method Spoofing", yaitu tetap 
|   menggunakan method="POST" pada form HTML, lalu menyisipkan fungsi 
|   Blade khusus yaitu @method('DELETE').
*/


/*
|--------------------------------------------------------------------------
| 3. CONTOH KODE RUTE DELETE (routes/web.php)
|--------------------------------------------------------------------------
*/

// Rute untuk MENERIMA dan MEMPROSES penghapusan data berdasarkan {id}
Route::delete('/mahasiswa/{id}', function ($id) {
    
    // Simulasi pencarian dan penghapusan data di database
    // Contoh dengan Eloquent Model:
    // $mahasiswa = Mahasiswa::findOrFail($id);
    // $mahasiswa->delete();

    return "Data mahasiswa dengan ID <b>{$id}</b> berhasil dihapus dari database!";
});


/*
|--------------------------------------------------------------------------
| 4. CONTOH PENULISAN FORM HTML (Di file Blade / .blade.php)
|--------------------------------------------------------------------------
| Tombol hapus biasanya dibungkus dalam form kecil dan dipadukan dengan 
| konfirmasi JavaScript (confirm()) agar data tidak terhapus tidak sengaja.
*/
/*
    <form action="/mahasiswa/1" method="POST" onsubmit="return confirm('Apakah kamu yakin ingin menghapus data ini?');">
        @csrf <!-- Pengaman wajib CSRF -->
        @method('DELETE') <!-- Trik Method Spoofing agar terbaca sebagai DELETE -->

        <button type="submit" style="background-color: red; color: white;">
            Hapus Data
        </button>
    </form>
*/

































































//==================================HEAD====================================================================
/**
 * =====================================================================
 * CATATAN BELAJAR LARAVEL ROUTING (BAGIAN 5: METODE HEAD & HTTP HEADER)
 * Rangkuman lengkap dari diskusi, penjelasan konsep header, kondisi,
 * serta contoh kode full dari sisi server (Laravel) hingga klien.
 * =====================================================================
 */

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. MENGENAL APA ITU "HTTP HEADER" (Konsep Dasar)
|--------------------------------------------------------------------------
| - Analogi Sederhana: Bayangkan mengirim paket surat pos.
|   * Bagian Isi Surat (Body) = Pesan teks atau data asli di dalam amplop.
|   * Bagian Luar Amplop (Header) = Informasi metadata seperti ukuran, 
|     jenis file, tanggal pengiriman, dan aturan akses.
| - Contoh Header:
|   * Content-Type: text/html (Memberitahu format data)
|   * Content-Length: 15728640 (Ukuran file dalam bytes)
|   * Authorization: Bearer token_xyz (Token akses)
*/


/*
|--------------------------------------------------------------------------
| 2. APA ITU METODE HEAD DAN KONDISI PENGGUNAANNYA?
|--------------------------------------------------------------------------
| - Penjelasan: Metode HTTP yang persis seperti GET, TETAPI server HANYA 
|   mengirimkan bagian Header-nya saja (metadata luar amplop) dan BAGIAN 
|   ISINYA (Body) DIKOSONGKAN.
| - Kondisi Penggunaan:
|   * Cek Keberadaan File / Link Checker: Ingin mengecek apakah file besar 
|     (misal video 2 GB atau PDF) masih ada di server (tidak 404) tanpa harus 
|     mengunduh file fisiknya yang bisa menghabiskan kuota/bandwidth.
|   * Pengecekan Cache (Last-Modified): Browser ingin mengecek apakah suatu 
|     data/gambar di server sudah berubah sejak terakhir kali dibuka user.
*/


/*
|--------------------------------------------------------------------------
| 3. BAGAIMANA LARAVEL MENANGANI METODE HEAD?
|--------------------------------------------------------------------------
| - Keajaiban Laravel: Kamu TIDAK PERLU repot-repot membuat rute HEAD secara manual.
|   Setiap kali kamu membuat rute GET, Laravel secara otomatis sudah mendaftarkan 
|   rute HEAD untuk URL tersebut di balik layar!
*/


/*
|--------------------------------------------------------------------------
| 4. CONTOH KODE FULL: SISI SERVER & SISI KLIEN (Laravel Murni / Monolith)
|--------------------------------------------------------------------------
*/

// A. SISI SERVER (routes/web.php)
// Membuat rute GET untuk mengunduh laporan besar. 
// Rute ini otomatis mendukung metode GET dan HEAD sekaligus.
Route::get('/download/laporan.pdf', function () {
    $filePath = storage_path('app/public/laporan.pdf');

    // Jika klien request dengan HEAD, Laravel otomatis hanya mengirimkan 
    // header file (ukuran, tipe, dll) tanpa membebani server mendownload bodi file.
    return response()->download($filePath);
});


// B. SISI KLIEN (Cara Klien Mengirim Request HEAD)
/*
Jika menggunakan terminal (cURL), perintahnya menggunakan parameter -I:
$ curl -I http://localhost:8000/download/laporan.pdf

Hasil Respon dari Server Laravel (Hanya Header / Metadata saja tanpa bodi):
HTTP/1.1 200 OK
Host: localhost:8000
Content-Type: application/pdf
Content-Length: 15728640  <-- Klien langsung tahu ukuran file sekitar 15 MB
Content-Disposition: attachment; filename=laporan.pdf
*/


/*
|--------------------------------------------------------------------------
| 5. KAPAN KITA PERLU MEMBUAT Route::head() SECARA MANUAL?
|--------------------------------------------------------------------------
| Jarang sekali diperlukan. Namun, jika kamu ingin membuat logika khusus 
| (misal mencatat statistik pengecekan file tanpa mengambil fisiknya), 
| sintaks manualnya seperti ini:
*/
Route::head('/info-dokumen', function () {
    return response('', 200)
        ->header('X-Custom-File-Name', 'Laporan-Keuangan.pdf')
        ->header('X-File-Size', '2.5 MB');
});
























































































//======================================match===================================================================
/**
 * =====================================================================
 * CATATAN BELAJAR LARAVEL ROUTING (BAGIAN 6: ROUTE::MATCH)
 * Rangkuman lengkap dari diskusi mengenai Route::match(), perbedaan dengan 
 * Route::any(), contoh kode, kondisi penggunaan yang tepat, serta 
 * alasan mengapa ia dihindari untuk form CRUD standar.
 * =====================================================================
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| 1. APA ITU ROUTE::MATCH()?
|--------------------------------------------------------------------------
| - Penjelasan: Fungsi di Laravel untuk mengizinkan satu URL (endpoint) 
|   agar dapat diakses menggunakan BEBERAPA metode HTTP tertentu secara bersamaan 
|   (misalnya hanya 'GET' dan 'POST').
| - Beda dengan Route::any(): 
|   * Route::match(['GET', 'POST']) bersifat SELEKTIF dan KETAT (hanya metode 
|     yang didaftarkan di dalam array yang diterima, sisanya diblokir 405 Method Not Allowed).
|   * Route::any() membuka SEMUA metode HTTP (`GET`, `POST`, `PUT`, `DELETE`, dll) 
|     tanpa terkecuali.
*/


/*
|--------------------------------------------------------------------------
| 2. CONTOH KODE PENGGUNAAN ROUTE::MATCH (routes/web.php)
|--------------------------------------------------------------------------
| Menggabungkan GET (tampil form) dan POST (proses data) dalam satu URL yang sama.
*/
Route::match(['GET', 'POST'], '/pendaftaran', function (Request $request) {
    
    // 1. Kondisi saat user membuka halaman lewat browser biasa (GET)
    if ($request->isMethod('get')) {
        return '
            <h3>Formulir Pendaftaran</h3>
            <form action="/pendaftaran" method="POST">
                ' . csrf_field() . '
                <label>Nama:</label><br>
                <input type="text" name="nama"><br><br>
                <button type="submit">Kirim</button>
            </form>
        ';
    }

    // 2. Kondisi saat user mengirim data dari form (POST)
    if ($request->isMethod('post')) {
        $nama = $request->input('nama');
        return "Data atas nama <b>{$nama}</b> berhasil diproses via POST!";
    }
});


/*
|--------------------------------------------------------------------------
| 3. KAPAN ROUTE::MATCH HARUS DIHINDARI? (Form CRUD Standar)
|--------------------------------------------------------------------------
| - Meskipun terlihat praktis menyatukan semua hal dalam satu tempat, 
|   menggunakan Route::match untuk form CRUD standar (tambah/edit mahasiswa, dll) 
|   sangat dihindari oleh developer profesional karena:
|   1. Spaghetti Code: Kode menjadi sangat panjang, penuh if-else, dan sulit dibaca.
|   2. Melanggar Separation of Concerns: Satu rute memikul terlalu banyak tanggung jawab.
|   3. Masalah Middleware: Sulit menerapkan aturan keamanan (seperti auth atau rate limit) 
|      khusus untuk metode POST-nya saja secara bersih.
| - Solusi Terbaik: Gunakan rute terpisah (GET untuk form, POST/PUT untuk proses) 
|   atau manfaatkan fitur otomatis Resource Controller (Route::resource).
*/


/*
|--------------------------------------------------------------------------
| 4. KONDISI TEPAT PENGGUNAAN ROUTE::MATCH DI DUNIA NYATA
|--------------------------------------------------------------------------
| Route::match sangat sah dan profesional digunakan pada 3 kondisi berikut:
| 
| 1. Menerima Webhook / Callback Pihak Ketiga: 
|    Integrasi payment gateway (Midtrans/Xendit) atau GitHub webhook di mana 
|    server luar mengirim POST, tapi terkadang butuh GET untuk verifikasi awal URL.
| 
| 2. Endpoint API Hybrid / Khusus: 
|    Endpoint kecil yang menerima GET untuk cek status sekaligus POST untuk update instan 
|    dalam fungsi kontrol yang sangat berkaitan erat.
| 
| 3. Halaman Mikro Statis (Landing Page): 
|    Halaman satu halaman sederhana dengan form kontak kilat tanpa database kompleks 
|    yang butuh Controller terpisah.
*/





































































//===========================ANY============================
/**
 * =====================================================================
 * CATATAN BELAJAR LARAVEL ROUTING (BAGIAN 7: ROUTE::ANY)
 * Rangkuman lengkap dari penjelasan Route::any(), perbandingannya,
 * contoh kode, serta risiko keamanannya dalam pengembangan aplikasi.
 * =====================================================================
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| 1. APA ITU ROUTE::ANY()?
|--------------------------------------------------------------------------
| - Penjelasan: Fungsi di Laravel yang mengizinkan satu URL (endpoint) 
|   untuk merespon SEMUA METODE HTTP TANPA TERKECUALI 
|   (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD).
| - Perbedaan dengan Route::match():
|   * Route::match(['GET', 'POST']) = Selektif (hanya metode di array yang diterima).
|   * Route::any() = Terbuka untuk semua metode HTTP sekaligus.
*/


/*
|--------------------------------------------------------------------------
| 2. CONTOH KODE PENGGUNAAN ROUTE::ANY (routes/web.php)
|--------------------------------------------------------------------------
*/
Route::any('/api-serbaguna', function (Request $request) {
    // Mengecek metode apa yang sedang digunakan oleh klien saat ini
    $metodeAktif = $request->method();

    return "Akses diterima! Metode HTTP yang digunakan adalah: " . $metodeAktif;
});


/*
|--------------------------------------------------------------------------
| 3. KONDISI PENGGUNAAN & RISIKO KEAMANANNYA
|--------------------------------------------------------------------------
| - Kondisi Tepat: 
|   * Kebutuhan debugging/testing jaringan.
|   * Sistem catch-all / penangkap segala jenis request (meskipun Route::fallback lebih disarankan).
| 
| - Risiko Utama:
|   * Sangat rentan terhadap celah keamanan jika diterapkan pada halaman CRUD 
|   * atau data penting, karena metode berbahaya seperti DELETE atau PUT bisa masuk 
|   * tanpa filter yang ketat jika tidak dijaga ketat di dalam logika kodenya.
| 
| - Saran Terbaik: Hindari penggunaan Route::any() untuk form atau data utama. 
|   Selalu gunakan metode yang spesifik (GET, POST, PUT, DELETE) demi keamanan.
*/