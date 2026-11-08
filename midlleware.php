<?php

/**
 * =====================================================================
 * CATATAN BELAJAR LARAVEL ROUTING (BAGIAN 8: MIDDLEWARE)
 * Rangkuman lengkap dari penjelasan konsep middleware, analogi satpam,
 * alur kerja (Before/After), contoh kode pembuatan, hingga penerapannya.
 * =====================================================================
 */

/*
|--------------------------------------------------------------------------
| 1. APA ITU MIDDLEWARE? (Konsep & Analogi)
|--------------------------------------------------------------------------
| - Penjelasan: Lapisan penyaring (filter) yang berada di antara request 
|   klien dan response aplikasi. Bertindak sebagai "satpam / penjaga gerbang".
| - Analogi: Satpam memeriksa tiket/ID penonton konser sebelum diizinkan masuk 
|   ke dalam ruang utama (Controller/Route).
| - Kondisi Penggunaan:
|   * Autentikasi (Cek apakah user sudah login / auth).
|   * Role & Permission (Cek apakah user adalah admin atau member biasa).
|   * Maintenance Mode (Memblokir akses saat server diperbaiki).
|   * Keamanan & Logging (Mencatat kunjungan IP atau proteksi CSRF).
*/


/*
|--------------------------------------------------------------------------
| 2. ALUR KERJA MIDDLEWARE (BEFORE & AFTER)
|--------------------------------------------------------------------------
| - Before Middleware: Memeriksa request SEBELUM sampai ke Controller 
|   (Contoh paling sering digunakan untuk validasi login).
| - After Middleware: Memeriksa atau memodifikasi response SETELAH dari 
|   Controller sebelum dikembalikan ke browser.
| - Perintah Artisan: php artisan make:middleware NamaMiddleware
*/


/*
|--------------------------------------------------------------------------
| 3. CONTOH KODE LOGIKA MIDDLEWARE (app/Http/Middleware/CekUmur.php)
|--------------------------------------------------------------------------
*/
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekUmurCatatan
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek kondisi aturan
        if ($request->input('umur') < 17) {
            // Tolak dan alihkan jika tidak memenuhi syarat
            return redirect('/')->with('error', 'Khusus 17+!');
        }

        // 2. Lanjutkan perjalanan request jika lolos
        return $next($request);
    }
}


/*
|--------------------------------------------------------------------------
| 4. CONTOH PENERAPAN PADA RUTE (routes/web.php)
|--------------------------------------------------------------------------
| Cara memasang middleware langsung pada rute tertentu:
*/
/*
    use App\Http\Middleware\CekUmur;

    Route::get('/halaman-rahasia', function () {
        return "Isi rahasia!";
    })->middleware(CekUmur::class);
*/































//==========================midelware parameter=================================================

/**
 * =====================================================================
 * CATATAN BELAJAR LARAVEL ROUTING (BAGIAN 9: MIDDLEWARE PARAMETERS)
 * Rangkuman lengkap dari penjelasan konsep middleware parameters, 
 * analogi satpam, aturan penulisan sintaks, contoh kode full end-to-end 
 * (dari bootstrap, middleware class, hingga route), serta bedah fungsi kodenya.
 * =====================================================================
 */

/*
|--------------------------------------------------------------------------
| 1. APA ITU MIDDLEWARE PARAMETERS? (Konsep & Analogi)
|--------------------------------------------------------------------------
| - Penjelasan: Argumen atau catatan tambahan yang dikirimkan ke dalam 
|   middleware saat middleware tersebut dipasang pada sebuah route.
| - Analogi: Bayangkan kamu punya SATU Satpam yang sama untuk menjaga 
|   berbagai ruangan berbeda:
|   * Ruangan A: Satpam diberi instruksi: "Izinkan masuk kalau rolenya admin."
|   * Ruangan B: Satpam diberi instruksi: "Izinkan masuk kalau rolenya owner/kasir."
| - Manfaat: Daripada membuat banyak class middleware (AdminMiddleware, KasirMiddleware), 
|   kita cukup membuat 1 class middleware serbaguna yang menerima parameter dinamis!
*/


/*
|--------------------------------------------------------------------------
| 2. ATURAN PENULISAN SINTAKS
|--------------------------------------------------------------------------
| - Format di Route: Dipisahkan dengan titik dua (:) untuk parameter tunggal, 
|   dan koma (,) jika parameternya banyak.
|   * 1 Parameter : ->middleware('role:admin')
|   * Banyak Param: ->middleware('role:admin,owner')
| 
| - Format di Method handle(): Parameter tambahan WAJIB diletakkan tepat 
|   setelah $next, berurutan sesuai dengan yang dikirim dari route.
*/


/*
|--------------------------------------------------------------------------
| 3. CONTOH KODE FULL END-TO-END (DARI AWAL SAMPAI AKHIR)
|--------------------------------------------------------------------------
*/

// =====================================================================
// LANGKAH 1: Mendaftarkan Alias Middleware (bootstrap/app.php)
// =====================================================================
/*
    use App\Http\Middleware\CheckRole;
    use Illuminate\Foundation\Configuration\Middleware;

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CheckRole::class, // Alias 'role' mengarah ke class CheckRole
        ]);
    });
*/


// =====================================================================
// LANGKAH 2: Menulis Logika di File Middleware (app/Http\Middleware\CheckRole.php)
// =====================================================================
/*
    namespace App\Http\Middleware;

    use Closure;
    use Illuminate\Http\Request;
    use Symfony\Component\HttpFoundation\Response;

    class CheckRole
    {
        // Perhatikan: $role1 dan $role2 diletakkan TEPAT SETELAH $next!
        public function handle(Request $request, Closure $next, string $role1, ?string $role2 = null): Response
        {
            // Ambil role user yang sedang aktif (contoh dari database/session)
            $userRole = $request->user()->role ?? 'guest'; 

            // Cek apakah role user cocok dengan salah satu parameter yang dikirim
            if ($userRole === $role1 || $userRole === $role2) {
                // Jika cocok, izinkan lanjut ke Controller tujuan
                return $next($request);
            }

            // Jika tidak cocok, tolak akses
            return response()->json(['message' => 'Akses ditolak!'], 403);
        }
    }
*/


// =====================================================================
// LANGKAH 3: Menerapkannya pada Route (routes/web.php)
// =====================================================================
/*
    use Illuminate\Support\Facades\Route;

    // A. Hanya butuh 1 parameter ("admin")
    Route::get('/admin/dashboard', [AdminController::class, 'index'])
        ->middleware('role:admin'); 
        // Nilai "admin" otomatis masuk ke variabel $role1 di method handle()

    // B. Butuh beberapa parameter ("admin" atau "owner")
    Route::get('/reports/keuangan', [ReportController::class, 'index'])
        ->middleware('role:admin,owner'); 
        // "admin" masuk ke $role1, "owner" masuk ke $role2
*/


/*
|--------------------------------------------------------------------------
| 4. BEDAH PENJELASAN BAGIAN KODE DALAM METHOD handle()
|--------------------------------------------------------------------------
| public function handle(Request $request, Closure $next, string $role1, string $role2)
|
| - `Request $request`     -> Berisi kode untuk menangkap data/informasi dari klien 
|                            (seperti user yang sedang login, token, atau inputan).
| - `Closure $next`        -> Berisi fungsi jembatan wajib untuk melanjutkan 
|                            perjalanan request ke Controller tujuan jika syarat lolos.
| - `string $role1`        -> Berisi kode untuk menangkap argumen PERTAMA yang dikirim 
|                            dari route (contoh: teks "admin" dari role:admin,owner).
| - `string $role2`        -> Berisi kode untuk menangkap argumen KEDUA yang dipisahkan 
|                            dengan koma (contoh: teks "owner" dari role:admin,owner).
*/


















































//======================================VALIDATE MIDLEWARE DAN IMPLEMENTASI==============================

/**
 * =====================================================================
 * CATATAN BELAJAR LARAVEL ROUTING (BAGIAN 10: VALIDASI & VARIADIC PARAMETER MIDDLEWARE)
 * Rangkuman lengkap dari penjelasan validasi role, analogi satpam dinamis,
 * teknik Variadic Parameter (...$roles), bedah kode CheckUserRole.php baris demi baris,
 * hingga penggunaan controller class [ProductController::class, 'index'] pada route.
 * =====================================================================
 */

/*
|--------------------------------------------------------------------------
| 1. ANALOGI KEHIDUPAN NYATA: SATPAM DENGAN DAFTAR NAMA DINAMIS
|--------------------------------------------------------------------------
| - Analogi: Bayangkan kamu memiliki SATU orang Satpam yang menjaga beberapa 
|   ruangan rahasia. 
|   * Pintu Ruangan A boleh dimasuki oleh: admin.
|   * Pintu Ruangan B boleh dimasuki oleh: admin atau owner.
|   * Pintu Ruangan C boleh dimasuki oleh: admin, owner, atau supervisor.
| - Konsep: Alih-alih merekrut banyak satpam berbeda, kita cukup memberi 1 Satpam 
|   "papan daftar nama dinamis" (whitelist) yang isinya menyesuaikan ruangan.
| - Di Laravel, teknik daftar dinamis ini menggunakan **Variadic Parameter (`string ...$roles`)**.
*/


/*
|--------------------------------------------------------------------------
| 2. BEDAH KODE FILE MIDDLEWARE (app/Http/Middleware/CheckUserRole.php)
|--------------------------------------------------------------------------
*/
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRoleCatatan
{
    /**
     * Penjelasan Method Handle:
     * 
     * 1. `string ...$roles` (Variadic Parameter / Operator Titik Tiga)
     *    - Menerima 1 atau banyak argumen sekaligus dari route tanpa batasan jumlah.
     *    - Semua argumen yang dikirim dari route (misal: 'admin,owner,supervisor') 
     *      otomatis dibungkus menjadi sebuah **Array** bernama `$roles`.
     * 
     * 2. `$user = $request->user();`
     *    - Mengambil data pengguna (user) yang saat ini sedang aktif / login di sistem.
     * 
     * 3. `if (! $user) { abort(401, 'Unauthenticated.'); }`
     *    - Memeriksa apakah user belum login (tamu/guest). 
     *    - Jika belum login, hentikan proses dan kembalikan Error 401 (Unauthenticated).
     * 
     * 4. `if (! in_array($user->role, $roles, true)) { abort(403, 'Forbidden.'); }`
     *    - Memeriksa apakah role milik user (misal: "kasir") ada di dalam daftar array `$roles`.
     *    - Jika TIDAK ada, tolak akses dan kembalikan Error 403 (Forbidden).
     * 
     * 5. `return $next($request);`
     *    - Tombol lampu hijau. Jika user sudah login DAN rolenya cocok, 
     *      maka request diizinkan lanjut ke Controller tujuan.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Validasi 1: Cek Autentikasi (Sudah login atau belum)
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Validasi 2: Cek Otorisasi (Apakah rolenya diizinkan di array $roles)
        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Forbidden.');
        }

        return $next($request);
    }
}


/*
|--------------------------------------------------------------------------
| 3. IMPLEMENTASI FLEKSIBEL PADA ROUTE (routes/web.php)
|--------------------------------------------------------------------------
| Dengan `string ...$roles`, satu class middleware dapat menangani 
| berbagai kombinasi role tanpa perlu mengubah kode middleware-nya:
*/
/*
    use Illuminate\Support\Facades\Route;

    // A. Mengirim 1 role -> Array $roles berisi: ['admin']
    Route::get('/admin', [DashboardController::class, 'index'])
        ->middleware('role:admin');

    // B. Mengirim 2 role -> Array $roles berisi: ['admin', 'owner']
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('role:admin,owner');

    // C. Mengirim 3 role -> Array $roles berisi: ['admin', 'owner', 'supervisor']
    Route::get('/pengaturan', [SystemController::class, 'index'])
        ->middleware('role:admin,owner,supervisor');
*/


/*
|--------------------------------------------------------------------------
| 4. CATATAN TAMBAHAN: PENULISAN ROUTE CONTROLLER CLASS `[ProductController::class, 'index']`
|--------------------------------------------------------------------------
| Pertanyaan: Kenapa penulisan route profesional menggunakan syntax array `[ProductController::class, 'index']` 
| dan tidak menggunakan Closure `function() {}`?
| 
| Jawaban & Alasan Utama:
| 1. Kebersihan Kode (Clean Code): Mencegah file routes/web.php menjadi "Spaghetti Code" 
|    karena logika bisnis dipisahkan penuh ke dalam file Controller terpisah.
| 2. Separation of Concerns: Route bertugas sebagai resepsionis (pintu masuk), 
|    sedangkan Controller bertugas sebagai dapur (pengolah data).
| 3. Performa (Route Caching): Perintah `php artisan route:cache` di server produksi 
|    HANYA BISA berjalan jika rute menggunakan Controller. Route yang menggunakan Closure 
|    tidak bisa di-cache oleh Laravel.
| 
| Format `[ProductController::class, 'index']` berarti:
| "Arahkan URL ini ke file ProductController dan eksekusi fungsi/method 'index'."
*/



















































/**
 * =====================================================================
 * CATATAN BELAJAR LARAVEL ROUTING (BAGIAN 11: ROUTE GROUP & PREFIX)
 * Rangkuman lengkap dari penjelasan Middleware pada Route Group, 
 * analogi lantai VIP, serta fungsi dan kegunaan Route Prefix (`->prefix()`).
 * =====================================================================
 */

/*
|--------------------------------------------------------------------------
| 1. APA ITU MIDDLEWARE PADA ROUTE GROUP? (Konsep & Analogi)
|--------------------------------------------------------------------------
| - Penjelasan: Cara untuk menerapkan aturan satpam (middleware) secara massal 
|   ke sekumpulan rute sekaligus tanpa harus menulisnya satu per satu di setiap baris.
| - Analogi: Lantai Khusus VIP di Perkantoran
|   * Tanpa Route Group: Satpam berdiri di setiap depan pintu ruangan (Dashboard, Reports) 
|     untuk memeriksa KTP satu per satu (boros dan melelahkan).
|   * Dengan Route Group: Perusahaan membuat gerbang utama di lift lantai tersebut. 
|     Satpam memeriksa kartu akses sekali di depan, dan jika lolos, user bebas masuk 
|     ke semua ruangan di lantai itu tanpa diperiksa ulang di depan setiap pintu.
*/


/*
|--------------------------------------------------------------------------
| 2. APA ITU ROUTE PREFIX (`->prefix`)? (Konsep & Analogi)
|--------------------------------------------------------------------------
| - Penjelasan: Fungsi untuk memberikan awalan URL otomatis secara massal 
|   kepada seluruh rute yang berada di dalam satu kelompok (group).
| - Analogi: Komplek Perumahan "Komplek Admin"
|   * Kamu punya rumah bernama /dashboard, /reports, dan /settings.
|   * Karena berada di dalam komplek admin, otomatis alamat lengkapnya menjadi 
|     admin/dashboard, admin/reports, dan admin/settings.
|   * Kamu cukup mengetik nama aslinya di dalam grup, dan Laravel otomatis 
|     menggabungkannya dengan nama prefix di depannya.
| 
| - Keuntungan Utama:
|   1. Hemat Pengetikan: Tidak perlu menulis teks yang sama berulang-ulang.
|   2. Maintenance Mudah: Jika suatu saat bos/klien minta ubah URL dari 'admin' 
|      menjadi 'manajemen', kamu cukup mengubah 1 kata saja di bagian prefix, 
|      dan seluruh URL di dalamnya langsung berubah otomatis dalam sedetik!
*/


/*
|--------------------------------------------------------------------------
| 3. CONTOH KODE FULL IMPLEMENTASI (routes/web.php)
|--------------------------------------------------------------------------
*/

/*
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\DashboardController;
    use App\Http\Controllers\ReportController;

    Route::middleware(['auth', 'role:admin,owner'])
        ->prefix('admin')
        ->group(function () {
            
            // Rute ini otomatis dibaca menjadi: /admin/dashboard
            // Dan secara otomatis terproteksi oleh middleware 'auth' & 'role'
            Route::get('/dashboard', [DashboardController::class, 'index']);

            // Rute ini otomatis dibaca menjadi: /admin/reports
            // Dan secara otomatis terproteksi oleh middleware yang sama
            Route::get('/reports', [ReportController::class, 'index']);
            
        });
*/


/*
|--------------------------------------------------------------------------
| 4. BEDAH PENJELASAN TUGAS SETIAP BAGIAN KODE DALAM ROUTE GROUP
|--------------------------------------------------------------------------
|
| 1. `Route::middleware(['auth', 'role:admin,owner'])`
|    - Tugasnya: Menentukan aturan keamanan utama (satpam) yang **wajib dilewati** 
|      oleh semua rute di dalam grup tersebut secara massal.
|      * 'auth' -> Cek apakah user sudah login.
|      * 'role:admin,owner' -> Cek apakah user punya hak akses admin/owner.
|
| 2. `->prefix('admin')`
|    - Tugasnya: Memberikan awalan URL otomatis (misal: 'admin/') di depan 
|      setiap rute yang ada di dalam kurung kurawal group.
|
| 3. `->group(function () { ... })`
|    - Tugasnya: Menjadi wadah kontainer utama yang membungkus daftar rute 
|      agar dikendalikan bersama oleh middleware dan prefix di atasnya.
|
| 4. `Route::get('/dashboard', ...)`
|    - Tugasnya: Rute individual di dalam sistem yang mewarisi perlindungan 
|      dan awalan URL dari kelompoknya secara otomatis.
*/