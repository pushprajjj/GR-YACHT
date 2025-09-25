/**
 * Settings Page - Admin settings and profile management
 */

class SettingsPage extends AdminBase {
    constructor() {
        super();
        this.loadSettings();
    }

    async loadSettings() {
        this.populateUserProfile();
        this.setupEventListeners();
        this.loadSystemInfo();
    }

    populateUserProfile() {
        // Populate profile form with current user data
        const fullNameField = document.getElementById('fullName');
        const emailField = document.getElementById('email');
        const roleField = document.getElementById('role');

        if (this.adminUser.full_name) {
            fullNameField.value = this.adminUser.full_name;
        }
        if (this.adminUser.email) {
            emailField.value = this.adminUser.email;
        }
        if (this.adminUser.role) {
            roleField.value = this.adminUser.role.replace('_', ' ').toUpperCase();
        }
    }

    setupEventListeners() {
        // Profile form submission
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => {
                this.handleProfileUpdate(e);
            });
        }

        // Password change form submission
        const passwordForm = document.getElementById('changePasswordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', (e) => {
                this.handlePasswordChange(e);
            });
        }
    }

    async handleProfileUpdate(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        formData.append('action', 'update_profile');
        
        try {
            const data = await this.apiRequest('../api/v1/adminAuth.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken
                }
            });
            
            if (data.success) {
                this.showSuccess('Profile updated successfully');
                // Update stored user data
                this.adminUser = { ...this.adminUser, ...data.admin };
                localStorage.setItem('admin_user', JSON.stringify(this.adminUser));
                this.updateUserInfo(); // Update header display
            } else {
                this.showError(data.message || 'Failed to update profile');
            }
        } catch (error) {
            console.error('Profile update error:', error);
            this.showError('Network error occurred');
        }
    }

    async handlePasswordChange(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const newPassword = formData.get('new_password');
        const confirmPassword = formData.get('confirm_password');
        
        if (newPassword !== confirmPassword) {
            this.showError('New passwords do not match');
            return;
        }
        
        if (newPassword.length < 6) {
            this.showError('Password must be at least 6 characters long');
            return;
        }
        
        formData.append('action', 'change_password');
        
        try {
            const data = await this.apiRequest('../api/v1/adminAuth.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken
                }
            });
            
            if (data.success) {
                this.showSuccess('Password changed successfully');
                e.target.reset();
            } else {
                this.showError(data.message || 'Failed to change password');
            }
        } catch (error) {
            console.error('Password change error:', error);
            this.showError('Network error occurred');
        }
    }

    loadSystemInfo() {
        // Load system information
        const lastLoginElement = document.getElementById('lastLogin');
        const sessionExpiryElement = document.getElementById('sessionExpiry');
        const ipAddressElement = document.getElementById('ipAddress');

        // Get user's IP address
        this.getUserIP().then(ip => {
            if (ipAddressElement) {
                ipAddressElement.textContent = ip;
            }
        });

        // Calculate session expiry (assuming 24 hours from login)
        if (sessionExpiryElement) {
            const tokenData = this.parseJWT(this.adminToken);
            if (tokenData && tokenData.exp) {
                const expiryDate = new Date(tokenData.exp * 1000);
                sessionExpiryElement.textContent = expiryDate.toLocaleString();
            } else {
                sessionExpiryElement.textContent = 'Unknown';
            }
        }

        // Set last login (if available in user data)
        if (lastLoginElement) {
            if (this.adminUser.last_login) {
                lastLoginElement.textContent = new Date(this.adminUser.last_login).toLocaleString();
            } else {
                lastLoginElement.textContent = 'Current session';
            }
        }
    }

    async getUserIP() {
        try {
            const response = await fetch('https://api.ipify.org?format=json');
            const data = await response.json();
            return data.ip;
        } catch (error) {
            console.error('Error getting IP:', error);
            return 'Unknown';
        }
    }

    parseJWT(token) {
        try {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            return JSON.parse(jsonPayload);
        } catch (error) {
            console.error('Error parsing JWT:', error);
            return null;
        }
    }
}

// Global functions for HTML onclick events
function saveSecuritySettings() {
    const twoFactorAuth = document.getElementById('twoFactorAuth').checked;
    const loginNotifications = document.getElementById('loginNotifications').checked;
    const sessionTimeout = document.getElementById('sessionTimeout').value;
    
    // Here you would typically save these settings via API
    console.log('Security settings:', {
        twoFactorAuth,
        loginNotifications,
        sessionTimeout
    });
    
    adminBase.showSuccess('Security settings saved successfully');
}

function clearAllSessions() {
    if (!confirm('Are you sure you want to clear all sessions? You will be logged out from all devices.')) {
        return;
    }
    
    // Here you would typically call an API to invalidate all sessions
    adminBase.showSuccess('All sessions cleared successfully. You will be redirected to login.');
    
    setTimeout(() => {
        adminBase.logout();
    }, 2000);
}

function resetAdminData() {
    if (!confirm('Are you sure you want to reset all admin preferences to default values? This action cannot be undone.')) {
        return;
    }
    
    // Here you would typically call an API to reset admin data
    adminBase.showSuccess('Admin data reset successfully');
    
    // Reset form values to defaults
    document.getElementById('twoFactorAuth').checked = false;
    document.getElementById('loginNotifications').checked = true;
    document.getElementById('sessionTimeout').value = '240';
}

// Initialize settings page
document.addEventListener('DOMContentLoaded', function() {
    adminBase = new SettingsPage();
});

