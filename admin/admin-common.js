/**
 * GR Yachts Admin - Common functionality
 * Shared components and utilities for all admin pages
 */

class AdminBase {
    constructor() {
        this.adminToken = localStorage.getItem('admin_token');
        this.adminUser = JSON.parse(localStorage.getItem('admin_user') || '{}');
        
        this.init();
    }

    async init() {
        // Check authentication
        if (!this.adminToken) {
            window.location.href = 'login.html';
            return;
        }

        await this.verifyAuth();
        this.setupEventListeners();
        this.updateUserInfo();
        this.loadBadges();
    }

    async verifyAuth() {
        try {
            const response = await fetch('../api/v1/adminAuth.php?action=verify', {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            
            if (!data.success) {
                this.logout();
                return;
            }

            this.adminUser = data.admin;
            localStorage.setItem('admin_user', JSON.stringify(data.admin));
            
            // Auto-refresh token if it expires soon
            this.setupTokenRefresh();
        } catch (error) {
            console.error('Auth verification error:', error);
            this.logout();
        }
    }

    setupTokenRefresh() {
        // Refresh token every 23 hours (before 24h expiration)
        setInterval(async () => {
            await this.refreshToken();
        }, 23 * 60 * 60 * 1000);
    }

    async refreshToken() {
        try {
            const response = await fetch('../api/v1/adminAuth.php?action=refresh', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                this.adminToken = data.token;
                localStorage.setItem('admin_token', data.token);
                console.log('Token refreshed successfully');
            }
        } catch (error) {
            console.error('Token refresh error:', error);
        }
    }

    logout() {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_user');
        window.location.href = 'login.html';
    }

    setupEventListeners() {
        // Sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            this.toggleSidebar();
        });

        // Sidebar overlay
        document.getElementById('sidebarOverlay').addEventListener('click', () => {
            this.closeSidebar();
        });

        // Window resize
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }

    updateUserInfo() {
        const userAvatar = document.getElementById('userAvatar');
        const userName = document.getElementById('userName');
        const userRole = document.getElementById('userRole');

        if (this.adminUser.full_name) {
            const initials = this.adminUser.full_name.split(' ')
                .map(name => name.charAt(0))
                .join('')
                .toUpperCase()
                .substring(0, 2);
            
            userAvatar.textContent = initials;
            userName.textContent = this.adminUser.full_name;
            userRole.textContent = this.adminUser.role.replace('_', ' ').toUpperCase();
        }
    }

    async loadBadges() {
        try {
            const response = await fetch('../api/v1/dashboardData.php?action=stats', {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                const stats = data.data;
                document.getElementById('contactsBadge').textContent = stats.pending.contacts || 0;
                document.getElementById('bookingsBadge').textContent = stats.pending.bookings || 0;
                document.getElementById('notificationBadge').textContent = stats.pending.total || 0;
            }
        } catch (error) {
            console.error('Error loading badges:', error);
        }
    }

    toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const overlay = document.getElementById('sidebarOverlay');

        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        } else {
            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('expanded');
        }
    }

    closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }

    handleResize() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const overlay = document.getElementById('sidebarOverlay');

        if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            
            if (sidebar.classList.contains('hidden')) {
                mainContent.classList.add('expanded');
            } else {
                mainContent.classList.remove('expanded');
            }
        }
    }

    // Utility methods for showing messages
    showSuccess(message) {
        this.showAlert(message, 'success');
    }

    showError(message) {
        this.showAlert(message, 'danger');
    }

    showAlert(message, type = 'info') {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }

    getLoadingHTML() {
        return `
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>
        `;
    }

    // Status color utilities
    getStatusColor(status) {
        const colors = {
            'new': 'primary',
            'read': 'info',
            'replied': 'warning',
            'resolved': 'success',
            'confirmed': 'success',
            'completed': 'info',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }

    // API request helper
    async apiRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Authorization': 'Bearer ' + this.adminToken,
                'Content-Type': 'application/json'
            }
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, mergedOptions);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('API request error:', error);
            throw error;
        }
    }
}

// Global functions
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_user');
        window.location.href = 'login.html';
    }
}

function toggleUserMenu() {
    // Implement user dropdown menu
    console.log('User menu clicked');
}

// Initialize base admin functionality
let adminBase;

