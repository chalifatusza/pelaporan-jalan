document.addEventListener('DOMContentLoaded', function() {
    loadAdminStats();
    loadRecentLaporan();
    
    // Manage users button
    const manageUsersBtn = document.getElementById('manageUsers');
    if (manageUsersBtn) {
        manageUsersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showAlert('info', 'Fitur kelola pengguna masih dalam pengembangan');
        });
    }
});

async function loadAdminStats() {
    try {
        const response = await fetch('api.php?action=get_stats');
        const data = await response.json();
        
        if (data.success && data.data.stats) {
            const stats = data.data.stats;
            
            document.querySelector('.stat-card:nth-child(1) .stat-number').textContent = stats.total_laporan;
            document.querySelector('.stat-card:nth-child(2) .stat-number').textContent = stats.status_stats?.diproses || 0;
            document.querySelector('.stat-card:nth-child(3) .stat-number').textContent = stats.laporan_selesai;
            document.querySelector('.stat-card:nth-child(4) .stat-number').textContent = stats.total_users;
            
            // Animate counters
            animateAdminCounters();
        }
    } catch (error) {
        console.error('Error loading admin stats:', error);
    }
}

function animateAdminCounters() {
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

async function loadRecentLaporan() {
    try {
        const response = await fetch('api.php?action=get_laporan');
        const data = await response.json();
        
        if (data.success && data.data.laporan) {
            // Get only first 10 laporan for dashboard
            const recentLaporan = data.data.laporan.slice(0, 10);
            displayRecentLaporan(recentLaporan);
        }
    } catch (error) {
        console.error('Error loading recent laporan:', error);
    }
}

function displayRecentLaporan(laporanList) {
    const tbody = document.querySelector('#laporanTable tbody');
    tbody.innerHTML = '';
    
    laporanList.forEach(laporan => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${laporan.id}</td>
            <td><strong>${laporan.judul_laporan}</strong></td>
            <td>${laporan.lokasi_jalan}</td>
            <td>${laporan.kecamatan}</td>
            <td>${laporan.tanggal_laporan_formatted}</td>
            <td><span class="status-badge status-${laporan.status}">${laporan.status}</span></td>
            <td><span class="kerusakan-${laporan.tingkat_kerusakan} status-badge">${laporan.tingkat_kerusakan}</span></td>
            <td>
                <a href="edit-laporan.html?id=${laporan.id}" class="btn btn-outline btn-small">Edit</a>
                <button class="btn btn-danger btn-small" onclick="deleteLaporan(${laporan.id})">Hapus</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Add to existing app.js
function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    
    // Alert content
    alert.innerHTML = `
        <span>${message}</span>
        <button class="close-alert">&times;</button>
    `;
    
    // Add to page
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alert, container.firstChild);
    
    // Add close functionality
    alert.querySelector('.close-alert').addEventListener('click', function() {
        alert.remove();
    });
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}