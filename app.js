// ==================== API INTERCEPTOR ====================
(function() {
    let API_BASE_URL = localStorage.getItem('API_BASE_URL') || 
        (window.location.protocol === 'file:' ? 'http://localhost:8000/api' : '/api');

    const originalFetch = window.fetch;

    window.fetch = async function(resource, init) {
        if (typeof resource === 'string' && (resource.includes('api.php') || resource === 'api.php')) {
            const urlObj = new URL(resource, window.location.origin);
            const action = urlObj.searchParams.get('action') || '';
            const id = urlObj.searchParams.get('id') || '';
            const page = urlObj.searchParams.get('page') || '';
            const statusFilter = urlObj.searchParams.get('status') || '';
            const tingkatFilter = urlObj.searchParams.get('tingkat_kerusakan') || '';
            const kecamatanFilter = urlObj.searchParams.get('kecamatan') || '';
            const rangeFilter = urlObj.searchParams.get('range') || '';
            const searchFilter = urlObj.searchParams.get('search') || '';

            let method = (init && init.method) ? init.method.toUpperCase() : 'GET';
            let path = '';
            let isFormData = false;

            switch (action) {
                case 'login':
                    path = '/login';
                    method = 'POST';
                    break;
                case 'register':
                    path = '/register';
                    method = 'POST';
                    break;
                case 'logout':
                    path = '/logout';
                    method = 'POST';
                    break;
                case 'check_session':
                case 'get_profile':
                    path = '/user';
                    method = 'GET';
                    break;
                case 'update_profile':
                    path = '/user';
                    method = 'PUT';
                    break;
                case 'add_laporan':
                    path = '/laporan';
                    method = 'POST';
                    break;
                case 'get_laporan':
                    path = '/laporan';
                    method = 'GET';
                    break;
                case 'get_laporan_by_id':
                    path = `/laporan/${id}`;
                    method = 'GET';
                    break;
                case 'update_laporan':
                    path = `/laporan/${id || ''}`;
                    method = 'POST';
                    break;
                case 'delete_laporan':
                    path = `/laporan/${id || ''}`;
                    method = 'DELETE';
                    break;
                case 'get_stats':
                    path = '/stats';
                    method = 'GET';
                    break;
                case 'get_user_stats':
                    path = '/stats/user';
                    method = 'GET';
                    break;
                case 'get_all_users':
                case 'get_users':
                    path = '/admin/users';
                    method = 'GET';
                    break;
                case 'update_user_role':
                case 'update_role':
                    path = `/admin/users/${id || ''}/role`;
                    method = 'PUT';
                    break;
                case 'delete_user':
                    path = `/admin/users/${id || ''}`;
                    method = 'DELETE';
                    break;
                case 'get_laporan_admin':
                    let adminParams = [];
                    if (page) adminParams.push(`page=${page}`);
                    if (statusFilter) adminParams.push(`status=${statusFilter}`);
                    if (tingkatFilter) adminParams.push(`tingkat_kerusakan=${tingkatFilter}`);
                    if (kecamatanFilter) adminParams.push(`kecamatan=${kecamatanFilter}`);
                    if (rangeFilter) adminParams.push(`range=${rangeFilter}`);
                    if (searchFilter) adminParams.push(`search=${searchFilter}`);
                    path = '/admin/laporan' + (adminParams.length ? '?' + adminParams.join('&') : '');
                    method = 'GET';
                    break;
                case 'update_status':
                    path = `/admin/laporan/${id || ''}/status`;
                    method = 'PUT';
                    break;
                case 'get_all_laporan_map':
                    path = '/laporan-map';
                    method = 'GET';
                    break;
                case 'get_status_stats_filtered':
                    path = `/stats/status?range=${rangeFilter}`;
                    method = 'GET';
                    break;
                case 'get_kerusakan_stats_filtered':
                    path = `/stats/kerusakan?range=${rangeFilter}`;
                    method = 'GET';
                    break;
                case 'get_kecamatan_stats_filtered':
                    path = `/stats/kecamatan?range=${rangeFilter}`;
                    method = 'GET';
                    break;
                case 'get_kategori':
                    path = '/kategori';
                    method = 'GET';
                    break;
                case 'check_gd':
                    return new Response(JSON.stringify({ success: true, data: { gd_available: true } }), {
                        headers: { 'Content-Type': 'application/json' }
                    });
                default:
                    path = '/laporan';
                    break;
            }

            let body = init ? init.body : null;
            if (body instanceof FormData) {
                isFormData = true;
                if (!id) {
                    const formId = body.get('id') || body.get('laporan_id') || body.get('user_id');
                    if (formId) {
                        if (action === 'update_laporan' || action === 'delete_laporan') {
                            path = `/laporan/${formId}`;
                        } else if (action === 'update_status') {
                            path = `/admin/laporan/${formId}/status`;
                        } else if (action === 'update_user_role' || action === 'update_role' || action === 'delete_user') {
                            path = `/admin/users/${formId}/role`;
                            if (action === 'delete_user') {
                                path = `/admin/users/${formId}`;
                            }
                        }
                    }
                }

                if (method === 'PUT' || action === 'update_profile') {
                    body.append('_method', 'PUT');
                    method = 'POST';
                }
            }

            const newUrl = `${API_BASE_URL}${path}`;
            const token = localStorage.getItem('auth_token');
            const headers = new Headers(init ? init.headers : {});
            
            if (token) {
                headers.set('Authorization', `Bearer ${token}`);
                headers.set('X-Authorization', `Bearer ${token}`);
                headers.set('X-Auth-Token', token);
            }
            headers.set('Accept', 'application/json');

            const newInit = {
                ...init,
                method,
                headers,
            };

            if (newInit.credentials) {
                delete newInit.credentials;
            }

            if (body) {
                newInit.body = body;
            }

            try {
                const res = await originalFetch(newUrl, newInit);
                
                if (res.status === 401) {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user_role');
                    const currentPageName = window.location.pathname.split('/').pop();
                    if (currentPageName !== 'login.html' && currentPageName !== 'index.html' && currentPageName !== 'register.html') {
                        window.location.href = 'login.html';
                    }
                }

                const text = await res.text();
                let json;
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    return new Response(text, {
                        status: res.status,
                        statusText: res.statusText,
                        headers: res.headers
                    });
                }

                if (json.success && json.data && json.data.token) {
                    localStorage.setItem('auth_token', json.data.token);
                    localStorage.setItem('user_role', json.data.user.role);
                }

                if (action === 'logout' && json.success) {
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user_role');
                }

                return new Response(JSON.stringify(json), {
                    status: res.status,
                    statusText: res.statusText,
                    headers: { 'Content-Type': 'application/json' }
                });

            } catch (err) {
                console.error('API Fetch Interceptor error:', err);
                throw err;
            }
        }

        return originalFetch(resource, init);
    };
})();
// ==================== END API INTERCEPTOR ====================

window.loginWithOAuth = function(provider) {
    let API_BASE_URL = localStorage.getItem('API_BASE_URL') || 
        (window.location.protocol === 'file:' ? 'http://localhost:8000/api' : '/api');
    
    let frontendUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    if (window.location.protocol === 'file:') {
        frontendUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/'));
    }
    
    window.location.href = `${API_BASE_URL}/auth/${provider}/redirect?frontend_url=${encodeURIComponent(frontendUrl)}`;
};

let currentUser = null;
let allLaporanData = [];
let currentPage = 1;
const itemsPerPage = 10;

document.addEventListener('DOMContentLoaded', function() {
    checkLoginStatus();
    initializeForms();
    initMobileMenu();
    initFileUploadPreview();
    
    const currentPageName = window.location.pathname.split('/').pop();
    
    // Load appropriate data based on page
    switch(currentPageName) {
        case 'dashboard-admin.html':
            loadAdminDashboard();
            break;
        case 'dashboard-user.html':
            loadUserDashboard();
            break;
        case 'daftar-laporan-admin.html':
            loadAllLaporanForAdmin();
            break;
        case 'daftar-laporan.html':
            loadUserLaporan();
            break;
        case 'edit-laporan.html':
            loadEditLaporanForm();
            break;
        case 'edit-profil.html':
        case 'edit-profil-admin.html':
        case 'edit-profil-user.html':
            loadProfileData();
            break;
        case 'kelola-pengguna.html':
            loadAllUsers();
            break;
    }
    
    // Add logout functionality
    document.querySelectorAll('.logout-btn').forEach(btn => {
        btn.addEventListener('click', logout);
    });
    
    initSmoothScroll();
});

// ==================== AUTHENTICATION ====================
function checkLoginStatus() {
    const currentPage = window.location.pathname.split('/').pop();
    const protectedPages = [
        'dashboard-user.html', 
        'dashboard-admin.html', 
        'laporan-baru.html', 
        'edit-profil-admin.html',
        'edit-profil-user.html',
        'edit-profil.html',
        'edit-laporan.html', 
        'daftar-laporan.html', 
        'daftar-laporan-admin.html',
        'kelola-pengguna.html',
        'peta-laporan.html',
        'detail-laporan.html'
    ];
    
    // Only check for protected pages
    if (!protectedPages.includes(currentPage)) return;
    
    fetch('api.php?action=check_session')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentUser = data.data.user;
                updateUserInfo(data.data.user);
                applyRoleSidebar(data.data.user.role);
                
                // Check if user is on wrong dashboard
                if (currentPage === 'dashboard-admin.html' && data.data.user.role !== 'admin') {
                    window.location.href = 'dashboard-user.html';
                } else if (currentPage === 'dashboard-user.html' && data.data.user.role === 'admin') {
                    window.location.href = 'dashboard-admin.html';
                }
            } else {
                // Not logged in - redirect to login
                window.location.href = 'login.html';
            }
        })
        .catch(error => {
            console.error('Error checking session:', error);
            window.location.href = 'login.html';
        });
}

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function updateUserInfo(user) {
    const name = user.nama_lengkap || user.nama || user.username;
    
    // Update all user name elements
    document.querySelectorAll('.user-name').forEach(el => {
        el.textContent = name;
    });
    
    // Update avatar
    document.querySelectorAll('.user-avatar').forEach(el => {
        el.textContent = name.charAt(0).toUpperCase();
    });
    
    // Update greeting
    document.querySelectorAll('.greeting').forEach(el => {
        el.textContent = `Halo, ${name}`;
    });
    
    // Update role
    document.querySelectorAll('.user-role').forEach(el => {
        el.textContent = user.role === 'admin' ? 'Administrator' : 'Pengguna';
        if (user.role === 'admin') {
            el.style.color = '#dc3545';
        }
    });
}

function applyRoleSidebar(role) {
    const sidebarMenu = document.querySelector('.sidebar-menu');
    if (!sidebarMenu) return;
    
    const page = window.location.pathname.split('/').pop();
    
    // Dynamically update sidebar title text based on role
    const sidebarTitle = document.querySelector('.dashboard-sidebar h3');
    if (sidebarTitle) {
        sidebarTitle.textContent = role === 'admin' ? 'Menu Admin' : 'Menu';
    }
    
    if (role === 'admin') {
        sidebarMenu.innerHTML = `
            <li><a href="dashboard-admin.html" class="${page === 'dashboard-admin.html' ? 'active' : ''}">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a></li>
            <li><a href="daftar-laporan-admin.html" class="${page === 'daftar-laporan-admin.html' ? 'active' : ''}">
                <i class="fas fa-list"></i> Semua Laporan
            </a></li>
            <li><a href="peta-laporan.html" class="${page === 'peta-laporan.html' ? 'active' : ''}">
                <i class="fas fa-map-marked-alt"></i> Peta Laporan
            </a></li>
            <li><a href="kelola-pengguna.html" class="${page === 'kelola-pengguna.html' ? 'active' : ''}">
                <i class="fas fa-users-cog"></i> Kelola Pengguna
            </a></li>
            <li><a href="edit-profil.html" class="${page.includes('edit-profil') ? 'active' : ''}">
                <i class="fas fa-user-edit"></i> Edit Profil
            </a></li>
            <li><a href="index.html">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a></li>
        `;
    } else {
        sidebarMenu.innerHTML = `
            <li><a href="dashboard-user.html" class="${page === 'dashboard-user.html' ? 'active' : ''}">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a></li>
            <li><a href="laporan-baru.html" class="${page === 'laporan-baru.html' ? 'active' : ''}">
                <i class="fas fa-plus-circle"></i> Buat Laporan
            </a></li>
            <li><a href="daftar-laporan.html" class="${page === 'daftar-laporan.html' ? 'active' : ''}">
                <i class="fas fa-list"></i> Laporan Saya
            </a></li>
            <li><a href="peta-laporan.html" class="${page === 'peta-laporan.html' ? 'active' : ''}">
                <i class="fas fa-map-marked-alt"></i> Peta Laporan
            </a></li>
            <li><a href="edit-profil.html" class="${page.includes('edit-profil') ? 'active' : ''}">
                <i class="fas fa-user-edit"></i> Edit Profil
            </a></li>
            <li><a href="index.html">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a></li>
        `;
    }
}

// ==================== FORM HANDLERS ====================
function initializeForms() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
        // Clear username & password inputs on load to prevent auto-fill
        const uInput = document.getElementById('username');
        const pInput = document.getElementById('password');
        if (uInput) uInput.value = '';
        if (pInput) pInput.value = '';
        setTimeout(() => {
            if (uInput) uInput.value = '';
            if (pInput) pInput.value = '';
        }, 100);
        setTimeout(() => {
            if (uInput) uInput.value = '';
            if (pInput) pInput.value = '';
        }, 500);
    }
    
    // Register form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    // Laporan form
    const laporanForm = document.getElementById('laporanForm');
    if (laporanForm) {
        laporanForm.addEventListener('submit', handleLaporan);
    }
    
    // Edit laporan form
    const editLaporanForm = document.getElementById('editLaporanForm');
    if (editLaporanForm) {
        editLaporanForm.addEventListener('submit', handleEditLaporan);
    }
    
    // Edit profil form
    const editProfilForm = document.getElementById('editProfilForm');
    if (editProfilForm) {
        editProfilForm.addEventListener('submit', handleEditProfil);
    }
}

async function handleLogin(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        showAlert('error', 'Username dan password harus diisi');
        return;
    }
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        
        const response = await fetch('api.php?action=login', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin' // Important for session cookies
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            
            // Redirect based on role
            setTimeout(() => {
                if (data.data.user.role === 'admin') {
                    window.location.href = 'dashboard-admin.html';
                } else {
                    window.location.href = 'dashboard-user.html';
                }
            }, 1000);
        } else {
            showAlert('error', data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error during login:', error);
        showAlert('error', 'Terjadi kesalahan saat login. Silakan coba lagi.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

async function handleRegister(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const email = document.getElementById('email').value.trim();
    const nama_lengkap = document.getElementById('nama_lengkap').value.trim();
    const alamat = document.getElementById('alamat').value.trim();
    const no_telepon = document.getElementById('no_telepon').value.trim();
    
    if (!username || !password || !email || !nama_lengkap) {
        showAlert('error', 'Semua field wajib diisi');
        return;
    }
    
    if (password !== confirmPassword) {
        showAlert('error', 'Password tidak cocok');
        return;
    }
    
    if (password.length < 6) {
        showAlert('error', 'Password minimal 6 karakter');
        return;
    }
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        formData.append('email', email);
        formData.append('nama_lengkap', nama_lengkap);
        formData.append('alamat', alamat);
        formData.append('no_telepon', no_telepon);
        
        const response = await fetch('api.php?action=register', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                window.location.href = 'dashboard-user.html';
            }, 1000);
        } else {
            showAlert('error', data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error during registration:', error);
        showAlert('error', 'Terjadi kesalahan saat registrasi');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

async function handleLaporan(e) {
    e.preventDefault();
    
    const judul_laporan = document.getElementById('judul_laporan').value.trim();
    const lokasi_jalan = document.getElementById('lokasi_jalan').value.trim();
    const kecamatan = document.getElementById('kecamatan').value;
    const deskripsi_kerusakan = document.getElementById('deskripsi_kerusakan').value.trim();
    const tingkat_kerusakan = document.getElementById('tingkat_kerusakan').value;
    const fotoInput = document.getElementById('foto');
    
    if (!judul_laporan || !lokasi_jalan || !kecamatan || !deskripsi_kerusakan) {
        showAlert('error', 'Semua field wajib diisi kecuali foto');
        return;
    }
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('judul_laporan', judul_laporan);
        formData.append('lokasi_jalan', lokasi_jalan);
        formData.append('kecamatan', kecamatan);
        formData.append('deskripsi_kerusakan', deskripsi_kerusakan);
        formData.append('tingkat_kerusakan', tingkat_kerusakan);
        
        if (fotoInput.files.length > 0) {
            formData.append('foto', fotoInput.files[0]);
        }
        
        const response = await fetch('api.php?action=add_laporan', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            e.target.reset();
            
            const previewImage = document.getElementById('preview-image');
            const uploadArea = document.querySelector('.upload-area');
            if (previewImage) previewImage.style.display = 'none';
            if (uploadArea) uploadArea.style.display = 'block';
            
            setTimeout(() => {
                window.location.href = 'dashboard-user.html';
            }, 1500);
        } else {
            showAlert('error', data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error creating laporan:', error);
        showAlert('error', 'Terjadi kesalahan saat mengirim laporan');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

async function handleEditLaporan(e) {
    e.preventDefault();
    
    const laporanId = new URLSearchParams(window.location.search).get('id');
    if (!laporanId) {
        showAlert('error', 'ID laporan tidak valid');
        return;
    }
    
    const judul_laporan = document.getElementById('judul_laporan').value.trim();
    const lokasi_jalan = document.getElementById('lokasi_jalan').value.trim();
    const kecamatan = document.getElementById('kecamatan').value;
    const deskripsi_kerusakan = document.getElementById('deskripsi_kerusakan').value.trim();
    const tingkat_kerusakan = document.getElementById('tingkat_kerusakan').value;
    const status = document.getElementById('status') ? document.getElementById('status').value : 'dikirim';
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memperbarui...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('laporan_id', laporanId);
        formData.append('judul_laporan', judul_laporan);
        formData.append('lokasi_jalan', lokasi_jalan);
        formData.append('kecamatan', kecamatan);
        formData.append('deskripsi_kerusakan', deskripsi_kerusakan);
        formData.append('tingkat_kerusakan', tingkat_kerusakan);
        formData.append('status', status);
        
        const response = await fetch('api.php?action=update_laporan', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                const isAdmin = currentUser && currentUser.role === 'admin';
                window.location.href = isAdmin ? 'daftar-laporan-admin.html' : 'daftar-laporan.html';
            }, 1500);
        } else {
            showAlert('error', data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error updating laporan:', error);
        showAlert('error', 'Terjadi kesalahan saat mengupdate laporan');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

async function handleEditProfil(e) {
    e.preventDefault();
    
    const nama_lengkap = document.getElementById('nama_lengkap').value.trim();
    const email = document.getElementById('email').value.trim();
    const alamat = document.getElementById('alamat').value.trim();
    const no_telepon = document.getElementById('no_telepon').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (!nama_lengkap || !email) {
        showAlert('error', 'Nama lengkap dan email harus diisi');
        return;
    }
    
    if (password && password !== confirmPassword) {
        showAlert('error', 'Password tidak cocok');
        return;
    }
    
    if (password && password.length < 6) {
        showAlert('error', 'Password minimal 6 karakter');
        return;
    }
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memperbarui...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('nama_lengkap', nama_lengkap);
        formData.append('email', email);
        formData.append('alamat', alamat);
        formData.append('no_telepon', no_telepon);
        
        if (password) {
            formData.append('password', password);
        }
        
        const response = await fetch('api.php?action=update_profile', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            
            // Update user info
            if (currentUser) {
                currentUser.nama_lengkap = nama_lengkap;
                currentUser.email = email;
                updateUserInfo(currentUser);
            }
            
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            // Clear password fields
            if (password) {
                document.getElementById('password').value = '';
                document.getElementById('confirmPassword').value = '';
            }
        } else {
            showAlert('error', data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showAlert('error', 'Terjadi kesalahan saat mengupdate profil');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// ==================== DASHBOARD FUNCTIONS ====================
async function loadAdminDashboard() {
    try {
        await loadAdminStats();
        await loadRecentLaporanForAdmin();
    } catch (error) {
        console.error('Error loading admin dashboard:', error);
        showAlert('error', 'Gagal memuat data dashboard');
    }
}

async function loadUserDashboard() {
    try {
        await loadUserStats();
        await loadRecentLaporanForUser();
    } catch (error) {
        console.error('Error loading user dashboard:', error);
        showAlert('error', 'Gagal memuat data dashboard');
    }
}

async function loadAdminStats() {
    try {
        const response = await fetch('api.php?action=get_stats', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success && data.data.stats) {
            const stats = data.data.stats;
            
            const elements = {
                'admin-total-laporan': stats.total_laporan || 0,
                'admin-laporan-diproses': stats.status_stats?.diproses || 0,
                'admin-laporan-selesai': stats.status_stats?.selesai || 0,
                'admin-total-users': stats.total_users || 0
            };
            
            Object.keys(elements).forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = elements[id];
            });
            
            animateCounters();
        }
    } catch (error) {
        console.error('Error loading admin stats:', error);
    }
}

async function loadUserStats() {
    try {
        const response = await fetch('api.php?action=get_user_stats', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success && data.data.stats) {
            const stats = data.data.stats;
            
            const elements = {
                'total-laporan': stats.total_laporan || 0,
                'laporan-diproses': stats.status_stats?.diproses || 0,
                'laporan-selesai': stats.status_stats?.selesai || 0,
                'laporan-bulan-ini': stats.laporan_bulan_ini || 0
            };
            
            Object.keys(elements).forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = elements[id];
            });
            
            animateCounters();
        }
    } catch (error) {
        console.error('Error loading user stats:', error);
    }
}

async function loadRecentLaporanForAdmin() {
    try {
        const response = await fetch('api.php?action=get_laporan', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success && data.data.laporan) {
            const recentLaporan = data.data.laporan.slice(0, 5);
            displayLaporanTable('#recentLaporanTable', recentLaporan, true);
        }
    } catch (error) {
        console.error('Error loading recent laporan:', error);
    }
}

async function loadRecentLaporanForUser() {
    try {
        const response = await fetch('api.php?action=get_laporan', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success && data.data.laporan) {
            const recentLaporan = data.data.laporan.slice(0, 5);
            displayLaporanTable('#laporanTable', recentLaporan, false);
        }
    } catch (error) {
        console.error('Error loading recent laporan:', error);
    }
}

// ==================== LAPORAN MANAGEMENT ====================
async function loadAllLaporanForAdmin() {
    try {
        const response = await fetch('api.php?action=get_laporan', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success && data.data.laporan) {
            allLaporanData = data.data.laporan;
            displayLaporanTable('#allLaporanTable', allLaporanData, true);
            setupFilterAndSearch();
        } else {
            showAlert('error', data.message || 'Gagal memuat data laporan');
        }
    } catch (error) {
        console.error('Error loading all laporan:', error);
        showAlert('error', 'Terjadi kesalahan saat memuat data laporan');
    }
}

async function loadUserLaporan() {
    try {
        const response = await fetch('api.php?action=get_laporan', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success && data.data.laporan) {
            displayLaporanTable('#laporanTable', data.data.laporan, false);
        } else {
            showAlert('error', data.message || 'Gagal memuat data laporan');
        }
    } catch (error) {
        console.error('Error loading user laporan:', error);
        showAlert('error', 'Terjadi kesalahan saat memuat data laporan');
    }
}

function displayLaporanTable(tableSelector, laporanList, isAdmin = false) {
    const table = document.querySelector(tableSelector);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    if (laporanList.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${isAdmin ? '9' : '8'}" style="text-align: center; padding: 40px; color: #6c757d;">
                    ${isAdmin ? 'Tidak ada laporan' : 'Anda belum membuat laporan'}
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = '';
    
    laporanList.forEach(laporan => {
        const row = document.createElement('tr');
        
        if (isAdmin) {
            row.innerHTML = `
                <td>${laporan.id}</td>
                <td><strong>${laporan.judul_laporan}</strong></td>
                <td>
                    <div>${laporan.nama_lengkap || laporan.username}</div>
                    <small style="color: #6c757d;">@${laporan.username}</small>
                </td>
                <td>${laporan.lokasi_jalan}</td>
                <td>${laporan.kecamatan}</td>
                <td>${formatDate(laporan.tanggal_laporan_formatted)}</td>
                <td><span class="status-badge status-${laporan.status}">${laporan.status}</span></td>
                <td><span class="kerusakan-${laporan.tingkat_kerusakan} status-badge">${laporan.tingkat_kerusakan}</span></td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <a href="edit-laporan.html?id=${laporan.id}" class="btn btn-outline btn-small">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-danger btn-small" onclick="deleteLaporan(${laporan.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
        } else {
            row.innerHTML = `
                <td>${laporan.id}</td>
                <td><strong>${laporan.judul_laporan}</strong></td>
                <td>${laporan.lokasi_jalan}</td>
                <td>${laporan.kecamatan}</td>
                <td>${formatDate(laporan.tanggal_laporan_formatted)}</td>
                <td><span class="status-badge status-${laporan.status}">${laporan.status}</span></td>
                <td><span class="kerusakan-${laporan.tingkat_kerusakan} status-badge">${laporan.tingkat_kerusakan}</span></td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <a href="edit-laporan.html?id=${laporan.id}" class="btn btn-outline btn-small">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-danger btn-small" onclick="deleteLaporan(${laporan.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
        }
        
        tbody.appendChild(row);
    });
}

async function loadEditLaporanForm() {
    const id = new URLSearchParams(window.location.search).get('id');
    if (!id) {
        showAlert('error', 'ID laporan tidak valid');
        setTimeout(() => {
            window.location.href = 'daftar-laporan.html';
        }, 1500);
        return;
    }
    
    try {
        const response = await fetch(`api.php?action=get_laporan_by_id&id=${id}`, {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success && data.data.laporan) {
            const laporan = data.data.laporan;
            
            // Populate form fields
            const fields = ['judul_laporan', 'lokasi_jalan', 'kecamatan', 'deskripsi_kerusakan', 'tingkat_kerusakan', 'status'];
            fields.forEach(field => {
                const el = document.getElementById(field);
                if (el) el.value = laporan[field] || '';
            });
            
            // Set photo preview
            const previewImage = document.getElementById('preview-image');
            const uploadArea = document.querySelector('.upload-area');
            
            if (laporan.foto_path) {
                if (previewImage) {
                    previewImage.src = laporan.foto_path;
                    previewImage.style.display = 'block';
                }
                if (uploadArea) uploadArea.style.display = 'none';
            }
        } else {
            showAlert('error', data.message || 'Gagal memuat data laporan');
            setTimeout(() => {
                window.location.href = 'daftar-laporan.html';
            }, 1500);
        }
    } catch (error) {
        console.error('Error loading laporan:', error);
        showAlert('error', 'Terjadi kesalahan saat memuat data laporan');
    }
}

async function deleteLaporan(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus laporan ini?')) return;
    
    try {
        const formData = new FormData();
        formData.append('laporan_id', id);
        
        const response = await fetch('api.php?action=delete_laporan', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        console.error('Error deleting laporan:', error);
        showAlert('error', 'Terjadi kesalahan saat menghapus laporan');
    }
}

// ==================== PROFILE MANAGEMENT ====================
async function loadProfileData() {
    try {
        const response = await fetch('api.php?action=get_profile', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success && data.data.user) {
            const user = data.data.user;
            
            const fields = ['nama_lengkap', 'email', 'alamat', 'no_telepon'];
            fields.forEach(field => {
                const el = document.getElementById(field);
                if (el) el.value = user[field] || '';
            });
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        showAlert('error', 'Gagal memuat data profil');
    }
}

// ==================== USER MANAGEMENT ====================
async function loadAllUsers() {
    try {
        const response = await fetch('api.php?action=get_all_users', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success && data.data.users) {
            displayUsers(data.data.users);
        } else {
            showAlert('error', data.message || 'Gagal memuat data pengguna');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showAlert('error', 'Gagal memuat data pengguna');
    }
}

function displayUsers(users) {
    const tbody = document.querySelector('#usersTable tbody');
    if (!tbody) return;
    
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: #6c757d;">
                    Belum ada pengguna terdaftar
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td>${user.id}</td>
            <td><strong>${user.username}</strong></td>
            <td>${user.nama_lengkap}</td>
            <td>${user.email}</td>
            <td>
                <span class="status-badge ${user.role === 'admin' ? 'status-selesai' : 'status-diproses'}">
                    ${user.role}
                </span>
            </td>
            <td>${user.total_laporan || 0}</td>
            <td>
                ${user.role !== 'admin' ? `
                    <button class="btn btn-warning btn-small" onclick="toggleUserRole(${user.id}, '${user.role}')">
                        ${user.role === 'user' ? 'Jadikan Admin' : 'Jadikan User'}
                    </button>
                    <button class="btn btn-danger btn-small" onclick="deleteUserAccount(${user.id})">
                        Hapus
                    </button>
                ` : '<span style="color:#6c757d;">Admin Utama</span>'}
            </td>
        </tr>
    `).join('');
}

async function deleteUserAccount(userId) {
    if (!confirm('Apakah Anda yakin ingin menghapus pengguna ini? Semua laporan mereka juga akan terhapus.')) return;
    
    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        
        const response = await fetch('api.php?action=delete_user', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            loadAllUsers();
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showAlert('error', 'Gagal menghapus pengguna');
    }
}

async function toggleUserRole(userId, currentRole) {
    const newRole = currentRole === 'user' ? 'admin' : 'user';
    const confirmMsg = `Apakah Anda yakin ingin mengubah role pengguna ini menjadi ${newRole}?`;
    
    if (!confirm(confirmMsg)) return;
    
    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('new_role', newRole);
        
        const response = await fetch('api.php?action=update_user_role', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            loadAllUsers();
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        console.error('Error updating user role:', error);
        showAlert('error', 'Gagal mengubah role pengguna');
    }
}

// ==================== UTILITY FUNCTIONS ====================
function initMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    if (!hamburger) return;
    
    hamburger.addEventListener('click', function() {
        const navMenu = document.querySelector('.nav-menu');
        const navButtons = document.querySelector('.nav-buttons');
        const isVisible = navMenu && navMenu.style.display === 'flex';
        
        [navMenu, navButtons].forEach(el => {
            if (el) {
                el.style.display = isVisible ? 'none' : 'flex';
                if (!isVisible) {
                    Object.assign(el.style, {
                        flexDirection: 'column',
                        position: 'absolute',
                        top: el === navButtons ? '180px' : '100%',
                        left: '0',
                        width: '100%',
                        backgroundColor: '#020617',
                        padding: '20px',
                        boxShadow: '0 10px 20px rgba(0,0,0,0.3)',
                        zIndex: '1000'
                    });
                }
            }
        });
    });
    
    document.addEventListener('click', e => {
        if (!e.target.closest('.navbar') && !e.target.closest('.hamburger')) {
            ['.nav-menu', '.nav-buttons'].forEach(sel => {
                const el = document.querySelector(sel);
                if (el) el.style.display = 'none';
            });
        }
    });
}

function initFileUploadPreview() {
    const uploadArea = document.querySelector('.upload-area');
    const fileInput = document.getElementById('foto');
    const previewImage = document.getElementById('preview-image');
    
    if (!uploadArea || !fileInput || !previewImage) return;
    
    uploadArea.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        
        if (file.size > 5 * 1024 * 1024) {
            showAlert('error', 'Ukuran file maksimal 5MB');
            this.value = '';
            return;
        }
        
        if (!['image/jpeg', 'image/jpg', 'image/png', 'image/gif'].includes(file.type)) {
            showAlert('error', 'Format file harus JPG, PNG, atau GIF');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = e => {
            previewImage.src = e.target.result;
            previewImage.style.display = 'block';
            uploadArea.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
    
    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, e => {
            e.preventDefault();
            
            if (eventName === 'dragover') {
                uploadArea.style.borderColor = '#00DD00';
                uploadArea.style.backgroundColor = 'rgba(0,221,0,0.05)';
            } else {
                uploadArea.style.borderColor = '#e9ecef';
                uploadArea.style.backgroundColor = '#EFFAFD';
                
                if (eventName === 'drop') {
                    const file = e.dataTransfer.files[0];
                    if (file && file.type.startsWith('image/')) {
                        fileInput.files = e.dataTransfer.files;
                        fileInput.dispatchEvent(new Event('change'));
                    } else {
                        showAlert('error', 'Harap unggah file gambar');
                    }
                }
            }
        });
    });
}

function initSmoothScroll() {
    document.addEventListener('click', e => {
        if (e.target.matches('a[href^="#"]')) {
            e.preventDefault();
            const targetId = e.target.getAttribute('href');
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                window.scrollTo({
                    top: target.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        }
    });
}

function showAlert(type, message) {
    // Remove existing alerts
    document.querySelectorAll('.alert').forEach(a => a.remove());
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <span>${message}</span>
        <button class="close-alert">&times;</button>
    `;
    
    const container = document.querySelector('.dashboard-content') || 
                     document.querySelector('.form-container') || 
                     document.querySelector('.container') || 
                     document.body;
    
    container.insertBefore(alert, container.firstChild);
    
    // Add close functionality
    alert.querySelector('.close-alert').addEventListener('click', () => {
        alert.remove();
    });
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

async function logout() {
    if (!confirm('Apakah Anda yakin ingin logout?')) return;
    
    try {
        const response = await fetch('api.php?action=logout', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'index.html';
        } else {
            showAlert('error', 'Gagal logout');
        }
    } catch (error) {
        console.error('Error during logout:', error);
        window.location.href = 'index.html';
    }
}

function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return dateString;
    }
}

function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    
    counters.forEach(counter => {
        const target = +counter.textContent;
        const increment = target / 50;
        let current = 0;
        
        const updateCounter = () => {
            if (current < target) {
                current += increment;
                counter.textContent = Math.ceil(current);
                setTimeout(updateCounter, 30);
            } else {
                counter.textContent = target;
            }
        };
        
        updateCounter();
    });
}

// Filter and Search functions
function setupFilterAndSearch() {
    const searchInput = document.getElementById('searchLaporan');
    const filterStatus = document.getElementById('filterStatus');
    const filterKecamatan = document.getElementById('filterKecamatan');
    const btnSearch = document.getElementById('btnSearch');
    const btnReset = document.getElementById('btnReset');
    
    if (!searchInput || !btnSearch) return;
    
    // Populate kecamatan filter
    if (filterKecamatan && allLaporanData.length > 0) {
        const kecamatans = [...new Set(allLaporanData.map(l => l.kecamatan))].sort();
        kecamatans.forEach(kecamatan => {
            const option = document.createElement('option');
            option.value = kecamatan;
            option.textContent = kecamatan;
            filterKecamatan.appendChild(option);
        });
    }
    
    if (btnSearch) btnSearch.addEventListener('click', filterLaporan);
    if (btnReset) btnReset.addEventListener('click', resetFilter);
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') filterLaporan();
        });
    }
}

function filterLaporan() {
    const searchTerm = document.getElementById('searchLaporan')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('filterStatus')?.value || '';
    const kecamatanFilter = document.getElementById('filterKecamatan')?.value || '';
    
    let filtered = allLaporanData;
    
    if (searchTerm) {
        filtered = filtered.filter(l => 
            l.judul_laporan.toLowerCase().includes(searchTerm) ||
            l.lokasi_jalan.toLowerCase().includes(searchTerm) ||
            l.nama_lengkap.toLowerCase().includes(searchTerm) ||
            l.username.toLowerCase().includes(searchTerm)
        );
    }
    
    if (statusFilter) {
        filtered = filtered.filter(l => l.status === statusFilter);
    }
    
    if (kecamatanFilter) {
        filtered = filtered.filter(l => l.kecamatan === kecamatanFilter);
    }
    
    displayLaporanTable('#allLaporanTable', filtered, true);
}

function resetFilter() {
    const searchInput = document.getElementById('searchLaporan');
    const filterStatus = document.getElementById('filterStatus');
    const filterKecamatan = document.getElementById('filterKecamatan');
    
    if (searchInput) searchInput.value = '';
    if (filterStatus) filterStatus.value = '';
    if (filterKecamatan) filterKecamatan.value = '';
    
    displayLaporanTable('#allLaporanTable', allLaporanData, true);
}

// Make functions globally available
window.deleteLaporan = deleteLaporan;
window.deleteUserAccount = deleteUserAccount;
window.toggleUserRole = toggleUserRole;
window.loadAllUsers = loadAllUsers;