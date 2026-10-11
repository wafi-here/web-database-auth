<!DOCTYPE html>
<html lang="id" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk & Pendaftaran - Analytics System</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['-apple-system', 'BlinkMacSystemFont', 'SF Pro Display', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        apple: {
                            blue: '#0071E3',
                            blueHover: '#0077ED',
                            darkBg: '#0B0D12',
                            cardBg: 'rgba(22, 27, 38, 0.65)',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* Ambient Background Blur Glows */
        .ambient-glow-1 {
            background: radial-gradient(circle, rgba(0, 113, 227, 0.25) 0%, rgba(0, 0, 0, 0) 70%);
        }
        .ambient-glow-2 {
            background: radial-gradient(circle, rgba(147, 51, 234, 0.18) 0%, rgba(0, 0, 0, 0) 70%);
        }

        /* Apple Glassmorphism Effect */
        .glass-panel {
            background: rgba(20, 24, 35, 0.65);
            backdrop-filter: blur(32px) saturate(180%);
            -webkit-backdrop-filter: blur(32px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.5),
                        inset 0 1px 0 0 rgba(255, 255, 255, 0.1);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #0071E3;
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.25);
            outline: none;
        }

        /* Apple Segmented Control Animation */
        .tab-active-pill {
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), width 0.3s ease;
        }
    </style>

    <!-- 1. PRE-CHECK AUTH SCRIPT (Anti-Flash via HttpOnly Cookie) -->
    <script>
        (async function preCheckAuth() {
            try {
                const res = await fetch('verify.php', {
                    method: 'POST',
                    credentials: 'same-origin' // Browser otomatis mengirim cookie HttpOnly
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    // Cookie valid -> Langsung alihkan ke index.php
                    window.location.replace('index.php');
                    return;
                }
            } catch (e) {
                // Abaikan jika jaringan error/unauthenticated, lanjut tampilkan formulir login
            }
            // Jika tidak terautentikasi, tampilkan antarmuka login
            document.documentElement.classList.add('auth-checked');
        })();
    </script>

    <style>
        /* Sembunyikan konten secara total sampai verifikasi cookie selesai */
        html:not(.auth-checked) body {
            opacity: 0;
            pointer-events: none;
        }
        body {
            transition: opacity 0.3s ease-out;
        }
    </style>
</head>
<body class="bg-apple-darkBg text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-apple-blue selection:text-white relative overflow-x-hidden">

    <!-- Ambient Background Lighting -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] ambient-glow-1 pointer-events-none rounded-full blur-3xl"></div>
    <div class="absolute bottom-10 right-1/3 w-[450px] h-[450px] ambient-glow-2 pointer-events-none rounded-full blur-3xl"></div>

    <!-- Main Container Card -->
    <div class="w-full max-w-md z-10">
        
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-xl border border-white/15 mb-4 shadow-xl">
                <i data-lucide="shield-check" class="w-7 h-7 text-apple-blue"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Sistem Evaluasi Siswa</h1>
            <p class="text-xs text-slate-400 mt-1 font-medium">Masuk untuk mengelola & menganalisis data akademik</p>
        </div>

        <!-- Glassmorphism Card Panel -->
        <div class="glass-panel rounded-3xl p-7 md:p-8 relative">
            
            <!-- Apple Segmented Control (Tab Bar) -->
            <div class="relative bg-black/40 p-1 rounded-2xl border border-white/5 flex mb-6">
                <div id="tabIndicator" class="tab-active-pill absolute top-1 bottom-1 left-1 w-[calc(50%-4px)] bg-apple-blue rounded-xl shadow-md"></div>
                
                <button type="button" onclick="switchTab('login')" id="btnLoginTab" class="relative z-10 w-1/2 py-2 text-xs font-semibold text-white transition-colors duration-200">
                    Masuk
                </button>
                <button type="button" onclick="switchTab('signup')" id="btnSignupTab" class="relative z-10 w-1/2 py-2 text-xs font-semibold text-slate-400 hover:text-slate-200 transition-colors duration-200">
                    Daftar Akun
                </button>
            </div>

            <!-- FORM LOGIN -->
            <form id="formLogin" onsubmit="handleAuthSubmit(event, 'login')" class="space-y-4">
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 uppercase tracking-wider mb-1.5">Email / Username</label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="email" id="loginEmail" required placeholder="nama@sekolah.sch.id" class="glass-input w-full pl-10 pr-4 py-3 rounded-xl text-xs text-white placeholder-slate-500">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-300 uppercase tracking-wider mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="password" id="loginPassword" required placeholder="••••••••" class="glass-input w-full pl-10 pr-4 py-3 rounded-xl text-xs text-white placeholder-slate-500">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="btnLoginSubmit" class="w-full py-3 px-4 bg-apple-blue hover:bg-apple-blueHover active:scale-[0.98] text-white font-semibold text-xs rounded-xl shadow-lg shadow-apple-blue/25 transition-all duration-200 flex items-center justify-center gap-2">
                        <span>Masuk ke Dashboard</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </form>

            <!-- FORM SIGN UP -->
            <form id="formSignup" onsubmit="handleAuthSubmit(event, 'register')" class="space-y-4 hidden">
                <div>
                    <label class="block text-[11px] font-medium text-slate-300 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                    <div class="relative">
                        <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" id="signupName" placeholder="Ahmad Sanjaya" class="glass-input w-full pl-10 pr-4 py-3 rounded-xl text-xs text-white placeholder-slate-500">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-300 uppercase tracking-wider mb-1.5">Email</label>
                    <div class="relative">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="email" id="signupEmail" placeholder="nama@sekolah.sch.id" class="glass-input w-full pl-10 pr-4 py-3 rounded-xl text-xs text-white placeholder-slate-500">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-slate-300 uppercase tracking-wider mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="password" id="signupPassword" placeholder="Minimal 6 karakter" class="glass-input w-full pl-10 pr-4 py-3 rounded-xl text-xs text-white placeholder-slate-500">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="btnSignupSubmit" class="w-full py-3 px-4 bg-apple-blue hover:bg-apple-blueHover active:scale-[0.98] text-white font-semibold text-xs rounded-xl shadow-lg shadow-apple-blue/25 transition-all duration-200 flex items-center justify-center gap-2">
                        <span>Buat Akun & Auto-Login</span>
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                    </button>
                </div>
            </form>

        </div>

        <!-- Footer Caption -->
        <p class="text-center text-[11px] text-slate-500 mt-6 font-medium">
            Protected with Apple-grade Cookie HttpOnly Security & Encryption
        </p>
    </div>

    <!-- Toast Notification (Frosted Glass) -->
    <div id="toastNotification" class="fixed bottom-6 right-6 z-50 hidden max-w-sm bg-rose-500/15 backdrop-blur-xl border border-rose-500/30 text-rose-200 text-xs font-medium px-4 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 transition-all duration-300 transform translate-y-2">
        <i data-lucide="alert-circle" id="toastIcon" class="w-5 h-5 flex-shrink-0 text-rose-400"></i>
        <span id="toastMessage">Terjadi kesalahan pada sistem.</span>
    </div>

    <script>
        // Inisialisasi Lucide Icons
        lucide.createIcons();

        let currentTab = 'login';

        // Switch Tab Segmented Control Animation
        function switchTab(tab) {
            currentTab = tab;
            const indicator = document.getElementById('tabIndicator');
            const loginTabBtn = document.getElementById('btnLoginTab');
            const signupTabBtn = document.getElementById('btnSignupTab');
            const formLogin = document.getElementById('formLogin');
            const formSignup = document.getElementById('formSignup');

            if (tab === 'login') {
                indicator.style.transform = 'translateX(0%)';
                loginTabBtn.className = 'relative z-10 w-1/2 py-2 text-xs font-semibold text-white transition-colors duration-200';
                signupTabBtn.className = 'relative z-10 w-1/2 py-2 text-xs font-semibold text-slate-400 hover:text-slate-200 transition-colors duration-200';
                
                formLogin.classList.remove('hidden');
                formSignup.classList.add('hidden');
            } else {
                indicator.style.transform = 'translateX(100%)';
                signupTabBtn.className = 'relative z-10 w-1/2 py-2 text-xs font-semibold text-white transition-colors duration-200';
                loginTabBtn.className = 'relative z-10 w-1/2 py-2 text-xs font-semibold text-slate-400 hover:text-slate-200 transition-colors duration-200';
                
                formSignup.classList.remove('hidden');
                formLogin.classList.add('hidden');
            }
        }

        // Handle Form Submission (Login & Sign Up)
        async function handleAuthSubmit(event, action) {
            event.preventDefault();

            let payload = {};
            let submitBtn = null;

            if (action === 'login') {
                submitBtn = document.getElementById('btnLoginSubmit');
                payload = {
                    email: document.getElementById('loginEmail').value.trim(),
                    password: document.getElementById('loginPassword').value
                };
            } else {
                submitBtn = document.getElementById('btnSignupSubmit');
                payload = {
                    name: document.getElementById('signupName').value.trim(),
                    email: document.getElementById('signupEmail').value.trim(),
                    password: document.getElementById('signupPassword').value
                };
            }

            // State Loading pada Tombol
            const originalBtnContent = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Memproses...</span>`;

            try {
                const response = await fetch(`auth.php?action=${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin', // Mengizinkan browser menerima & menyimpan HttpOnly cookie dari server
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    showToast(action === 'login' ? 'Login berhasil! Mengalihkan...' : 'Akun berhasil dibuat! Mengalihkan...', 'success');

                    // Redirect ke Dashboard index.php
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 800);
                } else {
                    showToast(result.message || 'Gagal memproses autentikasi.', 'error');
                }
            } catch (err) {
                console.error('Auth Error:', err);
                showToast('Terjadi kesalahan koneksi ke server backend.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
                lucide.createIcons();
            }
        }

        // Toast Notification System
        let toastTimer = null;
        function showToast(message, type = 'error') {
            const toast = document.getElementById('toastNotification');
            const toastMsg = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');

            toastMsg.innerText = message;

            if (type === 'success') {
                toast.className = "fixed bottom-6 right-6 z-50 max-w-sm bg-emerald-500/15 backdrop-blur-xl border border-emerald-500/30 text-emerald-200 text-xs font-medium px-4 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 transition-all duration-300 transform translate-y-0";
                toastIcon.setAttribute('data-lucide', 'check-circle-2');
                toastIcon.className = 'w-5 h-5 flex-shrink-0 text-emerald-400';
            } else {
                toast.className = "fixed bottom-6 right-6 z-50 max-w-sm bg-rose-500/15 backdrop-blur-xl border border-rose-500/30 text-rose-200 text-xs font-medium px-4 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 transition-all duration-300 transform translate-y-0";
                toastIcon.setAttribute('data-lucide', 'alert-circle');
                toastIcon.className = 'w-5 h-5 flex-shrink-0 text-rose-400';
            }

            lucide.createIcons();
            toast.classList.remove('hidden');

            if (toastTimer) clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                toast.classList.add('hidden');
            }, 4000);
        }
    </script>
</body>
</html>