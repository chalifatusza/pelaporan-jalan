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