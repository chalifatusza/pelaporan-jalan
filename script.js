// ==================== API INTERCEPTOR ====================
(function() {
    let API_BASE_URL = localStorage.getItem('API_BASE_URL') || 'http://localhost:8000/api';

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

document.addEventListener('DOMContentLoaded', function() {
    checkLoginStatusForHomepage();
    loadStatsFromServer();
    initMobileMenu();
    
    // Add logout functionality
    document.querySelectorAll('.logout-btn-homepage').forEach(btn => {
        btn.addEventListener('click', logout);
    });
});

async function checkLoginStatusForHomepage() {
    try {
        const response = await fetch('api.php?action=check_session');
        const data = await response.json();
        
        const authButtons = document.getElementById('authButtons');
        const heroAuthButtons = document.getElementById('heroAuthButtons');
        const dashboardLink = document.getElementById('dashboardLink');
        const dashboardLinkAnchor = document.getElementById('dashboardLinkAnchor');
        const authSection = document.getElementById('authSection');
        const step1Buttons = document.getElementById('step1Buttons');
        
        if (data.success) {
            // User is logged in
            const user = data.data.user;
            
            // Update navbar
            dashboardLink.style.display = 'block';
            dashboardLinkAnchor.href = user.role === 'admin' ? 'dashboard-admin.html' : 'dashboard-user.html';
            dashboardLinkAnchor.textContent = user.role === 'admin' ? 'Dashboard Admin' : 'Dashboard Saya';
            
            // Update navbar buttons
            authButtons.innerHTML = `
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="text-align: right;">
                        <div style="font-weight: 500; font-size: 0.9rem;">Halo,</div>
                        <div style="font-weight: 600;">${user.nama}</div>
                    </div>
                    <a href="${user.role === 'admin' ? 'dashboard-admin.html' : 'dashboard-user.html'}" class="btn btn-primary">Dashboard</a>
                    <button class="btn btn-outline logout-btn-homepage">Logout</button>
                </div>
            `;
            
            // Update hero buttons
            heroAuthButtons.innerHTML = `
                <a href="${user.role === 'admin' ? 'dashboard-admin.html' : 'dashboard-user.html'}" class="btn btn-primary btn-large">Buka Dashboard</a>
                ${user.role === 'user' ? '<a href="laporan-baru.html" class="btn btn-outline btn-large">Buat Laporan Baru</a>' : ''}
            `;
            
            // Hide login/register section
            if (authSection) authSection.style.display = 'none';
            
            // Update step 1 buttons
            if (step1Buttons) {
                step1Buttons.innerHTML = `
                    <a href="${user.role === 'admin' ? 'dashboard-admin.html' : 'dashboard-user.html'}" class="btn btn-outline btn-small" style="margin-top: 10px;">Dashboard Saya</a>
                `;
            }
            
            // Add logout functionality
            document.querySelectorAll('.logout-btn-homepage').forEach(btn => {
                btn.addEventListener('click', logout);
            });
        } else {
            // User is not logged in
            showDefaultGuestView();
        }
    } catch (error) {
        console.error('Error checking login status:', error);
        showDefaultGuestView();
    }
}

function showDefaultGuestView() {
    const authButtons = document.getElementById('authButtons');
    const heroAuthButtons = document.getElementById('heroAuthButtons');
    const dashboardLink = document.getElementById('dashboardLink');
    const authSection = document.getElementById('authSection');
    const step1Buttons = document.getElementById('step1Buttons');
    
    if (dashboardLink) dashboardLink.style.display = 'none';
    
    if (authButtons) {
        authButtons.innerHTML = `
            <a href="login.html" class="btn btn-outline">Masuk</a>
            <a href="register.html" class="btn btn-primary">Daftar</a>
        `;
    }
    
    if (heroAuthButtons) {
        heroAuthButtons.innerHTML = `
            <a href="login.html" class="btn btn-primary btn-large">Mulai Laporkan</a>
            <a href="#cara-lapor" class="btn btn-outline btn-large">Cara Melapor</a>
        `;
    }
    
    if (authSection) authSection.style.display = 'block';
    
    if (step1Buttons) {
        step1Buttons.innerHTML = `
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <a href="login.html" class="btn btn-outline btn-small">Masuk</a>
                <a href="register.html" class="btn btn-primary btn-small">Daftar</a>
            </div>
        `;
    }
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

function initMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    const navButtons = document.querySelector('.nav-buttons');
    
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            const isVisible = navMenu.style.display === 'flex';
            
            if (navMenu) {
                navMenu.style.display = isVisible ? 'none' : 'flex';
            }
            
            if (navButtons) {
                navButtons.style.display = isVisible ? 'none' : 'flex';
            }
            
            if (!isVisible) {
                if (navMenu) {
                    Object.assign(navMenu.style, {
                        flexDirection: 'column',
                        position: 'absolute',
                        top: '100%',
                        left: '0',
                        width: '100%',
                        backgroundColor: 'white',
                        padding: '20px',
                        boxShadow: '0 10px 20px rgba(0,0,0,0.1)',
                        zIndex: '1000'
                    });
                }
                
                if (navButtons) {
                    Object.assign(navButtons.style, {
                        flexDirection: 'column',
                        position: 'absolute',
                        top: navMenu ? (navMenu.clientHeight + 100) + 'px' : '180px',
                        left: '0',
                        width: '100%',
                        padding: '20px',
                        backgroundColor: 'white',
                        boxShadow: '0 10px 20px rgba(0,0,0,0.1)',
                        zIndex: '1000'
                    });
                }
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.navbar') && 
                !event.target.closest('.hamburger') && 
                navMenu && navMenu.style.display === 'flex') {
                navMenu.style.display = 'none';
                if (navButtons) navButtons.style.display = 'none';
            }
        });
    }
}

async function logout() {
    if (!confirm('Apakah Anda yakin ingin logout?')) return;
    
    try {
        const response = await fetch('api.php?action=logout');
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'index.html';
        } else {
            alert('Gagal logout. Silakan coba lagi.');
        }
    } catch (error) {
        console.error('Error logging out:', error);
        alert('Terjadi kesalahan saat logout. Silakan coba lagi.');
    }
}

async function loadStatsFromServer() {
    try {
        const response = await fetch('api.php?action=get_stats');
        const data = await response.json();
        
        if (data.success && data.data.stats) {
            const stats = data.data.stats;
            document.getElementById('laporan-count').textContent = stats.total_laporan || '0';
            document.getElementById('selesai-count').textContent = stats.laporan_selesai || '0';
            document.getElementById('user-count').textContent = stats.total_users || '0';
            
            animateCounters();
        }
    } catch (error) {
        console.error('Error loading stats:', error);
        // Set default values if error
        document.getElementById('laporan-count').textContent = '1247';
        document.getElementById('selesai-count').textContent = '892';
        document.getElementById('user-count').textContent = '543';
        animateCounters();
    }
}

function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    
    counters.forEach(counter => {
        const target = +counter.textContent;
        const increment = target / 100;
        let current = 0;
        
        const updateCounter = () => {
            if (current < target) {
                current += increment;
                counter.textContent = Math.ceil(current);
                setTimeout(updateCounter, 20);
            } else {
                counter.textContent = target;
            }
        };
        
        updateCounter();
    });
}

// Smooth scrolling for anchor links
document.addEventListener('click', function(e) {
    if (e.target.matches('a[href^="#"]')) {
        e.preventDefault();
        
        const targetId = e.target.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    }
});