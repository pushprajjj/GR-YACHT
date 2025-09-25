/**
 * GR Yachts Admin Dashboard
 * Main dashboard functionality and UI management
 */

class AdminDashboard {
    constructor() {
        this.currentSection = 'dashboard';
        this.adminToken = localStorage.getItem('admin_token');
        this.adminUser = JSON.parse(localStorage.getItem('admin_user') || '{}');
        this.charts = {};
        
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
        this.loadDashboard();
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

        // Navigation links
        document.querySelectorAll('.nav-link[data-section]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.target.closest('.nav-link').dataset.section;
                this.navigateToSection(section);
            });
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

    navigateToSection(section) {
        // Update active nav link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`[data-section="${section}"]`).classList.add('active');

        // Update page title
        const titles = {
            dashboard: 'Dashboard',
            contacts: 'Contact Messages',
            bookings: 'Yacht Bookings',
            'manage-site': 'Manage Site Content',
            settings: 'Settings'
        };
        document.getElementById('pageTitle').textContent = titles[section] || 'Dashboard';

        // Close mobile sidebar
        this.closeSidebar();

        // Load section content
        this.currentSection = section;
        this.loadSectionContent(section);
    }

    async loadSectionContent(section) {
        const content = document.getElementById('dashboardContent');
        
        switch (section) {
            case 'dashboard':
                await this.loadDashboard();
                break;
            case 'contacts':
                await this.loadContacts();
                break;
            case 'bookings':
                await this.loadBookings();
                break;
            case 'settings':
                await this.loadSettings();
                break;
            case 'manage-site':
                await this.loadManageSite();
                break;
            default:
                content.innerHTML = '<div class="loading">Section not found Or Under Development !</div>';
        }
    }

    async loadDashboard() {
        const content = document.getElementById('dashboardContent');
        content.innerHTML = this.getLoadingHTML();

        try {
            // Load dashboard stats
            const stats = await this.fetchDashboardStats();
            
            content.innerHTML = `
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card contacts">
                        <div class="stat-header">
                            <span class="stat-title">Total Contacts</span>
                            <div class="stat-icon contacts">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                        <div class="stat-value">${stats.contacts.total}</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            ${stats.contacts.change}% from last month
                        </div>
                    </div>

                    <div class="stat-card bookings">
                        <div class="stat-header">
                            <span class="stat-title">Total Bookings</span>
                            <div class="stat-icon bookings">
                                <i class="fas fa-anchor"></i>
                            </div>
                        </div>
                        <div class="stat-value">${stats.bookings.total}</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            ${stats.bookings.change}% from last month
                        </div>
                    </div>

                    <div class="stat-card revenue">
                        <div class="stat-header">
                            <span class="stat-title">Total Revenue</span>
                            <div class="stat-icon revenue">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                        <div class="stat-value">${stats.revenue.total} AED</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            ${stats.revenue.change}% from last month
                        </div>
                    </div>

                    <div class="stat-card pending">
                        <div class="stat-header">
                            <span class="stat-title">Pending Actions</span>
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="stat-value">${stats.pending.total}</div>
                        <div class="stat-change">
                            ${stats.pending.contacts} contacts + ${stats.pending.bookings} bookings
                        </div>
                    </div>
                </div>

                <!-- Management Section -->
                <div class="management-section">
                    <div class="management-card">
                        <div class="management-header">
                            <h3 class="management-title">
                                <i class="fas fa-anchor"></i>
                                Quick Management
                            </h3>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <button class="btn btn-outline-primary w-100 p-3" onclick="dashboard.navigateToSection('contacts')">
                                    <i class="fas fa-envelope fa-2x mb-2 d-block"></i>
                                    <strong>Manage Contacts</strong><br>
                                    <small>View and respond to inquiries</small>
                                </button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button class="btn btn-outline-success w-100 p-3" onclick="dashboard.navigateToSection('bookings')">
                                    <i class="fas fa-anchor fa-2x mb-2 d-block"></i>
                                    <strong>Manage Bookings</strong><br>
                                    <small>Handle yacht reservations</small>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Tables -->
                <div class="tables-section">
                    <div class="table-card">
                        <div class="table-header">
                            <h3 class="table-title">Recent Contacts</h3>
                            <a href="#" class="view-all-btn" onclick="dashboard.navigateToSection('contacts')">View All</a>
                        </div>
                        <div id="recentContactsTable">
                            ${this.getLoadingHTML()}
                        </div>
                    </div>

                    <div class="table-card">
                        <div class="table-header">
                            <h3 class="table-title">Recent Bookings</h3>
                            <a href="#" class="view-all-btn" onclick="dashboard.navigateToSection('bookings')">View All</a>
                        </div>
                        <div id="recentBookingsTable">
                            ${this.getLoadingHTML()}
                        </div>
                    </div>
                </div>
            `;

            // Update navigation badges
            document.getElementById('contactsBadge').textContent = stats.pending.contacts;
            document.getElementById('bookingsBadge').textContent = stats.pending.bookings;
            document.getElementById('notificationBadge').textContent = stats.pending.total;

            // Load recent tables
            await this.loadRecentTables();

        } catch (error) {
            console.error('Error loading dashboard:', error);
            content.innerHTML = '<div class="loading">Error loading dashboard data</div>';
        }
    }

    async fetchDashboardStats() {
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
                return data.data;
            } else {
                throw new Error(data.message || 'Failed to fetch dashboard stats');
            }
        } catch (error) {
            console.error('Error fetching dashboard stats:', error);
            // Return fallback data
            return {
                contacts: {
                    total: 0,
                    change: 0
                },
                bookings: {
                    total: 0,
                    change: 0
                },
                revenue: {
                    total: '0',
                    change: 0
                },
                pending: {
                    total: 0,
                    contacts: 0,
                    bookings: 0
                }
            };
        }
    }


    async loadRecentTables() {
        // Load recent contacts from API
        try {
            const contactsResponse = await fetch('../api/v1/contactData.php?action=list&limit=5', {
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken,
                    'Content-Type': 'application/json'
                }
            });
            const contactsData = await contactsResponse.json();
            
            const contactsContainer = document.getElementById('recentContactsTable');
            if (contactsData.success && contactsData.data.length > 0) {
                contactsContainer.innerHTML = `
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${contactsData.data.map(contact => `
                                <tr>
                                    <td>${contact.name}</td>
                                    <td>${contact.email}</td>
                                    <td>${contact.submitted_date}</td>
                                    <td><span class="status-badge status-${contact.status}">${contact.status.charAt(0).toUpperCase() + contact.status.slice(1)}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } else {
                contactsContainer.innerHTML = '<p class="text-muted text-center">No recent contacts</p>';
            }
        } catch (error) {
            console.error('Error loading recent contacts:', error);
            document.getElementById('recentContactsTable').innerHTML = '<p class="text-danger text-center">Error loading contacts</p>';
        }

        // Load recent bookings from API
        try {
            const bookingsResponse = await fetch('../api/v1/bookingData.php?action=list&limit=5', {
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken,
                    'Content-Type': 'application/json'
                }
            });
            const bookingsData = await bookingsResponse.json();
            
            const bookingsContainer = document.getElementById('recentBookingsTable');
            if (bookingsData.success && bookingsData.data.length > 0) {
                bookingsContainer.innerHTML = `
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Yacht</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${bookingsData.data.map(booking => `
                                <tr>
                                    <td>${booking.full_name}</td>
                                    <td>${booking.preferred_date_formatted}</td>
                                    <td>${booking.yacht_name}</td>
                                    <td><span class="status-badge status-${booking.status}">${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } else {
                bookingsContainer.innerHTML = '<p class="text-muted text-center">No recent bookings</p>';
            }
        } catch (error) {
            console.error('Error loading recent bookings:', error);
            document.getElementById('recentBookingsTable').innerHTML = '<p class="text-danger text-center">Error loading bookings</p>';
        }
    }

    async loadContacts() {
        const content = document.getElementById('dashboardContent');
        content.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Contact Messages</h2>
                <div>
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="dashboard.showContactFilters()">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="dashboard.refreshContacts()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            
            <!-- Filters (initially hidden) -->
            <div class="table-card mb-3" id="contactFilters" style="display: none;">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="contactStatusFilter">
                            <option value="">All Status</option>
                            <option value="new">New</option>
                            <option value="read">Read</option>
                            <option value="replied">Replied</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" id="contactSearchFilter" placeholder="Name, email, or message...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" id="contactDateFromFilter">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" id="contactDateToFilter">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary w-100" onclick="dashboard.applyContactFilters()">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="table-card">
                <div class="table-header d-flex justify-content-between align-items-center">
                    <h3 class="table-title mb-0">Contact Messages</h3>
                    <div id="contactsPagination"></div>
                </div>
                <div class="table-responsive">
                    <div id="contactsTableContainer">
                        ${this.getLoadingHTML()}
                    </div>
                </div>
            </div>
        `;

        // Load contacts data
        this.currentContactPage = 1;
        await this.fetchContacts();
    }

    async loadBookings() {
        const content = document.getElementById('dashboardContent');
        content.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Yacht Bookings</h2>
                <div>
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="dashboard.showBookingFilters()">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="dashboard.refreshBookings()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            
            <!-- Booking Filters (initially hidden) -->
            <div class="table-card mb-3" id="bookingFilters" style="display: none;">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="bookingStatusFilter">
                            <option value="">All Status</option>
                            <option value="new">New</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" id="bookingSearchFilter" placeholder="Name, email, or phone...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" id="bookingDateFromFilter">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" id="bookingDateToFilter">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Yacht</label>
                        <select class="form-select" id="bookingYachtFilter">
                            <option value="">All Yachts</option>
                            <option value="BLACK PEARL 95">BLACK PEARL 95</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary w-100" onclick="dashboard.applyBookingFilters()">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="table-card">
                <div class="table-header d-flex justify-content-between align-items-center">
                    <h3 class="table-title mb-0">Yacht Bookings</h3>
                    <div id="bookingsPagination"></div>
                </div>
                <div class="table-responsive">
                    <div id="bookingsTableContainer">
                        ${this.getLoadingHTML()}
                    </div>
                </div>
            </div>
        `;

        // Load bookings data
        this.currentBookingPage = 1;
        await this.fetchBookings();
    }


    async loadSettings() {
        const content = document.getElementById('dashboardContent');
        content.innerHTML = `
            <div class="row">
                <div class="col-12">
                    <h2 class="mb-4">Settings</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="table-card">
                        <h3 class="table-title">Profile Settings</h3>
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" value="${this.adminUser.full_name || ''}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="${this.adminUser.email || ''}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="${this.adminUser.role || ''}" readonly>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="table-card">
                        <h3 class="table-title">Change Password</h3>
                        <form id="changePasswordForm">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-warning">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // Setup password change form
        document.getElementById('changePasswordForm').addEventListener('submit', (e) => {
            this.handlePasswordChange(e);
        });
    }

    renderContactsTable() {
        const container = document.getElementById('contactsTableContainer');
        container.innerHTML = `
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#001</td>
                        <td>John Smith</td>
                        <td>john@example.com</td>
                        <td>Interested in yacht rental for wedding...</td>
                        <td>2 hours ago</td>
                        <td><span class="status-badge status-new">New</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">View</button>
                            <button class="btn btn-sm btn-outline-success">Reply</button>
                        </td>
                    </tr>
                    <tr>
                        <td>#002</td>
                        <td>Sarah Johnson</td>
                        <td>sarah@example.com</td>
                        <td>Corporate event inquiry for 50 people...</td>
                        <td>5 hours ago</td>
                        <td><span class="status-badge status-new">New</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">View</button>
                            <button class="btn btn-sm btn-outline-success">Reply</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        `;
    }

    renderBookingsTable() {
        const container = document.getElementById('bookingsTableContainer');
        container.innerHTML = `
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Date & Time</th>
                        <th>Duration</th>
                        <th>Yacht</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#B001</td>
                        <td>Emma Davis<br><small>emma@example.com</small></td>
                        <td>+971 50 123 4567</td>
                        <td>Dec 25, 2024<br><small>10:00 AM</small></td>
                        <td>4 Hours</td>
                        <td>BLACK PEARL 95</td>
                        <td>4,800 AED</td>
                        <td><span class="status-badge status-new">New</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">View</button>
                            <button class="btn btn-sm btn-outline-success">Confirm</button>
                        </td>
                    </tr>
                    <tr>
                        <td>#B002</td>
                        <td>Alex Brown<br><small>alex@example.com</small></td>
                        <td>+971 55 987 6543</td>
                        <td>Dec 28, 2024<br><small>2:00 PM</small></td>
                        <td>6 Hours</td>
                        <td>BLACK PEARL 95</td>
                        <td>7,200 AED</td>
                        <td><span class="status-badge status-confirmed">Confirmed</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">View</button>
                            <button class="btn btn-sm btn-outline-info">Edit</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        `;
    }


    async handlePasswordChange(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const newPassword = formData.get('new_password');
        const confirmPassword = formData.get('confirm_password');
        
        if (newPassword !== confirmPassword) {
            alert('New passwords do not match');
            return;
        }
        
        formData.append('action', 'change_password');
        
        try {
            const response = await fetch('../api/v1/adminAuth.php', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Password changed successfully');
                e.target.reset();
            } else {
                alert(data.message || 'Failed to change password');
            }
        } catch (error) {
            console.error('Password change error:', error);
            alert('Network error occurred');
        }
    }

    getLoadingHTML() {
        return `
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>
        `;
    }

    // Contact Management Methods
    async fetchContacts(page = 1) {
        try {
            const params = new URLSearchParams({
                action: 'list',
                page: page,
                limit: 20
            });

            // Add filters if they exist
            const statusFilter = document.getElementById('contactStatusFilter');
            const searchFilter = document.getElementById('contactSearchFilter');
            const dateFromFilter = document.getElementById('contactDateFromFilter');
            const dateToFilter = document.getElementById('contactDateToFilter');

            if (statusFilter && statusFilter.value) params.append('status', statusFilter.value);
            if (searchFilter && searchFilter.value) params.append('search', searchFilter.value);
            if (dateFromFilter && dateFromFilter.value) params.append('date_from', dateFromFilter.value);
            if (dateToFilter && dateToFilter.value) params.append('date_to', dateToFilter.value);

            const response = await fetch(`../api/v1/contactData.php?${params}`, {
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            if (data.success) {
                this.renderContactsTable(data.data, data.pagination);
            } else {
                this.showError('Failed to load contacts: ' + data.message);
            }
        } catch (error) {
            console.error('Error fetching contacts:', error);
            this.showError('Network error occurred while loading contacts');
        }
    }

    renderContactsTable(contacts, pagination) {
        const container = document.getElementById('contactsTableContainer');
        
        if (!contacts || contacts.length === 0) {
            container.innerHTML = '<p class="text-muted text-center p-4">No contacts found</p>';
            return;
        }

        container.innerHTML = `
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${contacts.map(contact => `
                        <tr>
                            <td>
                                <strong>${contact.name}</strong>
                            </td>
                            <td>
                                <a href="mailto:${contact.email}" class="text-decoration-none">
                                    ${contact.email}
                                </a>
                            </td>
                            <td>
                                <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                     title="${contact.message}">
                                    ${contact.message}
                                </div>
                            </td>
                            <td>
                                <small>
                                    ${contact.submitted_date}<br>
                                    ${contact.submitted_time}
                                </small>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" 
                                        onchange="dashboard.updateContactStatus(${contact.id}, this.value)"
                                        style="min-width: 100px;">
                                    <option value="new" ${contact.status === 'new' ? 'selected' : ''}>New</option>
                                    <option value="read" ${contact.status === 'read' ? 'selected' : ''}>Read</option>
                                    <option value="replied" ${contact.status === 'replied' ? 'selected' : ''}>Replied</option>
                                    <option value="resolved" ${contact.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                                </select>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" 
                                            onclick="dashboard.viewContactDetails(${contact.id})"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="dashboard.deleteContact(${contact.id})"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;

        // Render pagination
        this.renderContactsPagination(pagination);
    }

    renderContactsPagination(pagination) {
        const container = document.getElementById('contactsPagination');
        if (!pagination || pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        const currentPage = pagination.current_page;
        const totalPages = pagination.total_pages;
        
        let paginationHTML = '<nav><ul class="pagination pagination-sm mb-0">';
        
        // Previous button
        if (pagination.has_prev) {
            paginationHTML += `<li class="page-item">
                <a class="page-link" href="#" onclick="dashboard.fetchContacts(${currentPage - 1})">Previous</a>
            </li>`;
        }
        
        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="dashboard.fetchContacts(${i})">${i}</a>
            </li>`;
        }
        
        // Next button
        if (pagination.has_next) {
            paginationHTML += `<li class="page-item">
                <a class="page-link" href="#" onclick="dashboard.fetchContacts(${currentPage + 1})">Next</a>
            </li>`;
        }
        
        paginationHTML += '</ul></nav>';
        container.innerHTML = paginationHTML;
    }

    showContactFilters() {
        const filtersDiv = document.getElementById('contactFilters');
        if (filtersDiv.style.display === 'none') {
            filtersDiv.style.display = 'block';
        } else {
            filtersDiv.style.display = 'none';
        }
    }

    applyContactFilters() {
        this.currentContactPage = 1;
        this.fetchContacts(1);
    }

    refreshContacts() {
        this.fetchContacts(this.currentContactPage || 1);
    }

    async updateContactStatus(contactId, newStatus) {
        try {
            const formData = new FormData();
            formData.append('contact_id', contactId);
            formData.append('status', newStatus);

            const response = await fetch('../api/v1/contactData.php?action=update_status', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken
                }
            });

            const data = await response.json();
            if (data.success) {
                this.showSuccess('Contact status updated successfully');
            } else {
                this.showError('Failed to update contact status: ' + data.message);
                // Refresh to revert the change
                this.refreshContacts();
            }
        } catch (error) {
            console.error('Error updating contact status:', error);
            this.showError('Network error occurred');
            this.refreshContacts();
        }
    }

    async deleteContact(contactId) {
        if (!confirm('Are you sure you want to delete this contact? This action cannot be undone.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('contact_id', contactId);

            const response = await fetch('../api/v1/contactData.php?action=delete', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken
                }
            });

            const data = await response.json();
            if (data.success) {
                this.showSuccess('Contact deleted successfully');
                this.refreshContacts();
            } else {
                this.showError('Failed to delete contact: ' + data.message);
            }
        } catch (error) {
            console.error('Error deleting contact:', error);
            this.showError('Network error occurred');
        }
    }

    async viewContactDetails(contactId) {
        try {
            // Show the modal with loading state
            const modal = new bootstrap.Modal(document.getElementById('contactModal'));
            modal.show();
            
            // Reset modal content to loading state
            document.getElementById('contactModalBody').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading contact details...</p>
                </div>
            `;
            
            // Fetch contact details
            const response = await fetch(`../api/v1/contactData.php?action=list&search=&limit=1000`, {
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken,
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            if (data.success) {
                const contact = data.data.find(c => c.id === contactId);
                if (contact) {
                    this.displayContactModal(contact);
                } else {
                    throw new Error('Contact not found');
                }
            } else {
                throw new Error(data.message || 'Failed to fetch contact details');
            }
        } catch (error) {
            console.error('Error fetching contact details:', error);
            document.getElementById('contactModalBody').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading contact details: ${error.message}
                </div>
            `;
        }
    }

    displayContactModal(contact) {
        const modalBody = document.getElementById('contactModalBody');
        const modalFooter = document.getElementById('contactModalFooter');
        
        modalBody.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-user me-2"></i>
                                Contact Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Full Name</label>
                                <p class="mb-0">${contact.name}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Email Address</label>
                                <p class="mb-0">
                                    <a href="mailto:${contact.email}" class="text-decoration-none">
                                        <i class="fas fa-envelope me-1"></i>
                                        ${contact.email}
                                    </a>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Submission Date</label>
                                <p class="mb-0">
                                    <i class="fas fa-calendar me-1"></i>
                                    ${contact.submitted_date} at ${contact.submitted_time}
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Status</label>
                                <div>
                                    <span class="badge bg-${this.getStatusColor(contact.status)} fs-6">
                                        ${contact.status.charAt(0).toUpperCase() + contact.status.slice(1)}
                                    </span>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold text-muted">IP Address</label>
                                <p class="mb-0 small text-muted">${contact.ip_address}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-comment-dots me-2"></i>
                                Message Content
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="message-content p-3 bg-white rounded border">
                                <p class="mb-0" style="white-space: pre-wrap; line-height: 1.6;">
                                    ${contact.message}
                                </p>
                            </div>
                            <div class="mt-3">
                                <label class="form-label fw-bold text-muted">Quick Actions</label>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary btn-sm" onclick="window.open('mailto:${contact.email}?subject=Re: Your inquiry to GR Yachts&body=Hello ${contact.name},\\n\\nThank you for contacting GR Yachts...', '_blank')">
                                        <i class="fas fa-reply me-1"></i>
                                        Reply via Email
                                    </button>
                                    <button class="btn btn-outline-success btn-sm" onclick="dashboard.updateContactStatusInModal(${contact.id}, 'read')">
                                        <i class="fas fa-eye me-1"></i>
                                        Mark as Read
                                    </button>
                                    <button class="btn btn-outline-warning btn-sm" onclick="dashboard.updateContactStatusInModal(${contact.id}, 'replied')">
                                        <i class="fas fa-check me-1"></i>
                                        Mark as Replied
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" onclick="dashboard.updateContactStatusInModal(${contact.id}, 'resolved')">
                                        <i class="fas fa-check-double me-1"></i>
                                        Mark as Resolved
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Hide footer (confirm button hidden as requested)
        modalFooter.style.display = 'none';
    }

    getStatusColor(status) {
        const colors = {
            'new': 'primary',
            'read': 'info',
            'replied': 'warning',
            'resolved': 'success'
        };
        return colors[status] || 'secondary';
    }

    async updateContactStatusInModal(contactId, newStatus) {
        try {
            const formData = new FormData();
            formData.append('contact_id', contactId);
            formData.append('status', newStatus);

            const response = await fetch('../api/v1/contactData.php?action=update_status', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken
                }
            });

            const data = await response.json();
            if (data.success) {
                this.showSuccess('Contact status updated successfully');
                // Close modal and refresh the contacts list
                bootstrap.Modal.getInstance(document.getElementById('contactModal')).hide();
                this.refreshContacts();
            } else {
                this.showError('Failed to update contact status: ' + data.message);
            }
        } catch (error) {
            console.error('Error updating contact status:', error);
            this.showError('Network error occurred');
        }
    }

    // Booking Management Methods
    async fetchBookings(page = 1) {
        try {
            const params = new URLSearchParams({
                action: 'list',
                page: page,
                limit: 20
            });

            // Add filters if they exist
            const statusFilter = document.getElementById('bookingStatusFilter');
            const searchFilter = document.getElementById('bookingSearchFilter');
            const dateFromFilter = document.getElementById('bookingDateFromFilter');
            const dateToFilter = document.getElementById('bookingDateToFilter');
            const yachtFilter = document.getElementById('bookingYachtFilter');

            if (statusFilter && statusFilter.value) params.append('status', statusFilter.value);
            if (searchFilter && searchFilter.value) params.append('search', searchFilter.value);
            if (dateFromFilter && dateFromFilter.value) params.append('date_from', dateFromFilter.value);
            if (dateToFilter && dateToFilter.value) params.append('date_to', dateToFilter.value);
            if (yachtFilter && yachtFilter.value) params.append('yacht_name', yachtFilter.value);

            const response = await fetch(`../api/v1/bookingData.php?${params}`, {
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            if (data.success) {
                this.renderBookingsTable(data.data, data.pagination);
            } else {
                this.showError('Failed to load bookings: ' + data.message);
            }
        } catch (error) {
            console.error('Error fetching bookings:', error);
            this.showError('Network error occurred while loading bookings');
        }
    }

    renderBookingsTable(bookings, pagination) {
        const container = document.getElementById('bookingsTableContainer');
        
        if (!bookings || bookings.length === 0) {
            container.innerHTML = '<p class="text-muted text-center p-4">No bookings found</p>';
            return;
        }

        container.innerHTML = `
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Yacht</th>
                        <th>Date & Time</th>
                        <th>Charter Length</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${bookings.map(booking => `
                        <tr>
                            <td>
                                <div>
                                    <strong>${booking.full_name}</strong><br>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope me-1"></i>${booking.email}<br>
                                        <i class="fas fa-phone me-1"></i>${booking.phone}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">${booking.yacht_name}</span>
                            </td>
                            <td>
                                <div>
                                    <strong>${booking.preferred_date_formatted}</strong><br>
                                    <small class="text-muted">${booking.preferred_time_formatted}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">${booking.charter_length}</span>
                            </td>
                            <td>
                                <strong class="text-success">${booking.formatted_price}</strong>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" 
                                        onchange="dashboard.updateBookingStatus(${booking.id}, this.value)"
                                        style="min-width: 120px;">
                                    <option value="new" ${booking.status === 'new' ? 'selected' : ''}>New</option>
                                    <option value="confirmed" ${booking.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                    <option value="completed" ${booking.status === 'completed' ? 'selected' : ''}>Completed</option>
                                    <option value="cancelled" ${booking.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                </select>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" 
                                            onclick="dashboard.viewBookingDetails(${booking.id})"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="dashboard.deleteBooking(${booking.id})"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;

        // Render pagination
        this.renderBookingsPagination(pagination);
    }

    renderBookingsPagination(pagination) {
        const container = document.getElementById('bookingsPagination');
        if (!pagination || pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        const currentPage = pagination.current_page;
        const totalPages = pagination.total_pages;
        
        let paginationHTML = '<nav><ul class="pagination pagination-sm mb-0">';
        
        // Previous button
        if (pagination.has_prev) {
            paginationHTML += `<li class="page-item">
                <a class="page-link" href="#" onclick="dashboard.fetchBookings(${currentPage - 1})">Previous</a>
            </li>`;
        }
        
        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="dashboard.fetchBookings(${i})">${i}</a>
            </li>`;
        }
        
        // Next button
        if (pagination.has_next) {
            paginationHTML += `<li class="page-item">
                <a class="page-link" href="#" onclick="dashboard.fetchBookings(${currentPage + 1})">Next</a>
            </li>`;
        }
        
        paginationHTML += '</ul></nav>';
        container.innerHTML = paginationHTML;
    }

    showBookingFilters() {
        const filtersDiv = document.getElementById('bookingFilters');
        if (filtersDiv.style.display === 'none') {
            filtersDiv.style.display = 'block';
        } else {
            filtersDiv.style.display = 'none';
        }
    }

    applyBookingFilters() {
        this.currentBookingPage = 1;
        this.fetchBookings(1);
    }

    refreshBookings() {
        this.fetchBookings(this.currentBookingPage || 1);
    }

    async updateBookingStatus(bookingId, newStatus) {
        try {
            const formData = new FormData();
            formData.append('booking_id', bookingId);
            formData.append('status', newStatus);

            const response = await fetch('../api/v1/bookingData.php?action=update_status', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken
                }
            });

            const data = await response.json();
            if (data.success) {
                this.showSuccess('Booking status updated successfully');
            } else {
                this.showError('Failed to update booking status: ' + data.message);
                this.refreshBookings();
            }
        } catch (error) {
            console.error('Error updating booking status:', error);
            this.showError('Network error occurred');
            this.refreshBookings();
        }
    }

    async deleteBooking(bookingId) {
        if (!confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('booking_id', bookingId);

            const response = await fetch('../api/v1/bookingData.php?action=delete', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken
                }
            });

            const data = await response.json();
            if (data.success) {
                this.showSuccess('Booking deleted successfully');
                this.refreshBookings();
            } else {
                this.showError('Failed to delete booking: ' + data.message);
            }
        } catch (error) {
            console.error('Error deleting booking:', error);
            this.showError('Network error occurred');
        }
    }

    async viewBookingDetails(bookingId) {
        try {
            // Show the modal with loading state
            const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
            modal.show();
            
            // Reset modal content to loading state
            document.getElementById('bookingModalBody').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading booking details...</p>
                </div>
            `;
            
            // Fetch booking details
            const response = await fetch(`../api/v1/bookingData.php?action=list&search=&limit=1000`, {
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken,
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            if (data.success) {
                const booking = data.data.find(b => b.id === bookingId);
                if (booking) {
                    this.displayBookingModal(booking);
                } else {
                    throw new Error('Booking not found');
                }
            } else {
                throw new Error(data.message || 'Failed to fetch booking details');
            }
        } catch (error) {
            console.error('Error fetching booking details:', error);
            document.getElementById('bookingModalBody').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading booking details: ${error.message}
                </div>
            `;
        }
    }

    displayBookingModal(booking) {
        const modalBody = document.getElementById('bookingModalBody');
        const modalFooter = document.getElementById('bookingModalFooter');
        
        modalBody.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-user me-2"></i>
                                Customer Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Full Name</label>
                                <p class="mb-0">${booking.full_name}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Email Address</label>
                                <p class="mb-0">
                                    <a href="mailto:${booking.email}" class="text-decoration-none">
                                        <i class="fas fa-envelope me-1"></i>
                                        ${booking.email}
                                    </a>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Phone Number</label>
                                <p class="mb-0">
                                    <a href="tel:${booking.phone}" class="text-decoration-none">
                                        <i class="fas fa-phone me-1"></i>
                                        ${booking.phone}
                                    </a>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Submission Date</label>
                                <p class="mb-0">
                                    <i class="fas fa-calendar me-1"></i>
                                    ${booking.submitted_date} at ${booking.submitted_time}
                                </p>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold text-muted">IP Address</label>
                                <p class="mb-0 small text-muted">${booking.ip_address}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-anchor me-2"></i>
                                Booking Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Yacht</label>
                                <p class="mb-0">
                                    <span class="badge bg-info fs-6">${booking.yacht_name}</span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Preferred Date</label>
                                <p class="mb-0">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    ${booking.preferred_date_formatted}
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Preferred Time</label>
                                <p class="mb-0">
                                    <i class="fas fa-clock me-1"></i>
                                    ${booking.preferred_time_formatted}
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Charter Length</label>
                                <p class="mb-0">
                                    <span class="badge bg-secondary fs-6">${booking.charter_length}</span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Total Price</label>
                                <p class="mb-0">
                                    <strong class="text-success fs-5">${booking.formatted_price}</strong>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Status</label>
                                <div>
                                    <span class="badge bg-${this.getBookingStatusColor(booking.status)} fs-6">
                                        ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                                    </span>
                                </div>
                            </div>
                            ${booking.notes ? `
                                <div class="mb-0">
                                    <label class="form-label fw-bold text-muted">Notes</label>
                                    <div class="p-2 bg-white rounded border">
                                        <small>${booking.notes}</small>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">
                                <i class="fas fa-tools me-2"></i>
                                Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <button class="btn btn-outline-success w-100 btn-sm" onclick="dashboard.updateBookingStatusInModal(${booking.id}, 'confirmed')">
                                        <i class="fas fa-check me-1"></i>
                                        Confirm
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-primary w-100 btn-sm" onclick="dashboard.updateBookingStatusInModal(${booking.id}, 'completed')">
                                        <i class="fas fa-check-double me-1"></i>
                                        Complete
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-danger w-100 btn-sm" onclick="dashboard.updateBookingStatusInModal(${booking.id}, 'cancelled')">
                                        <i class="fas fa-times me-1"></i>
                                        Cancel
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-info w-100 btn-sm" onclick="window.open('mailto:${booking.email}?subject=Your Yacht Booking - ${booking.yacht_name}&body=Hello ${booking.full_name},\\n\\nThank you for your yacht booking...', '_blank')">
                                        <i class="fas fa-envelope me-1"></i>
                                        Email
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Hide footer (confirm button hidden as requested)
        modalFooter.style.display = 'none';
    }

    getBookingStatusColor(status) {
        const colors = {
            'new': 'primary',
            'confirmed': 'success',
            'completed': 'info',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }

    async updateBookingStatusInModal(bookingId, newStatus) {
        try {
            const formData = new FormData();
            formData.append('booking_id', bookingId);
            formData.append('status', newStatus);

            const response = await fetch('../api/v1/bookingData.php?action=update_status', {
                method: 'POST',
                body: formData,
                headers: {
                    'Authorization': 'Bearer ' + this.adminToken
                }
            });

            const data = await response.json();
            if (data.success) {
                this.showSuccess('Booking status updated successfully');
                // Close modal and refresh the bookings list
                bootstrap.Modal.getInstance(document.getElementById('bookingModal')).hide();
                this.refreshBookings();
            } else {
                this.showError('Failed to update booking status: ' + data.message);
            }
        } catch (error) {
            console.error('Error updating booking status:', error);
            this.showError('Network error occurred');
        }
    }

    // Content Management Method
    async loadManageSite() {
        const content = document.getElementById('dashboardContent');
        content.innerHTML = `
            <div class="row">
                <div class="col-12">
                    <h2 class="mb-4">Manage Site Content</h2>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Manage all website content including pages, images, and site settings from this central hub.
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="table-card h-100">
                        <div class="management-header">
                            <h3 class="management-title">
                                <i class="fas fa-home text-primary"></i>
                                Home Page
                            </h3>
                        </div>
                        <p class="text-muted mb-3">Manage hero section, yacht fleet showcase, and homepage content.</p>
                        <div class="text-center">
                            <button class="btn btn-outline-primary w-100" onclick="alert('Feature coming soon!')">
                                <i class="fas fa-edit me-2"></i>Edit Home Page
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="table-card h-100">
                        <div class="management-header">
                            <h3 class="management-title">
                                <i class="fas fa-info-circle text-info"></i>
                                About Us
                            </h3>
                        </div>
                        <p class="text-muted mb-3">Manage company information, mission, vision, and team details.</p>
                        <div class="text-center">
                            <button class="btn btn-outline-info w-100" onclick="alert('Feature coming soon!')">
                                <i class="fas fa-edit me-2"></i>Edit About Page
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="table-card h-100">
                        <div class="management-header">
                            <h3 class="management-title">
                                <i class="fas fa-concierge-bell text-success"></i>
                                Services
                            </h3>
                        </div>
                        <p class="text-muted mb-3">Manage yacht services, amenities, and service descriptions.</p>
                        <div class="text-center">
                            <button class="btn btn-outline-success w-100" onclick="alert('Feature coming soon!')">
                                <i class="fas fa-edit me-2"></i>Edit Services
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="table-card h-100">
                        <div class="management-header">
                            <h3 class="management-title">
                                <i class="fas fa-box text-warning"></i>
                                Packages
                            </h3>
                        </div>
                        <p class="text-muted mb-3">Manage yacht rental packages, pricing, and package descriptions.</p>
                        <div class="text-center">
                            <button class="btn btn-outline-warning w-100" onclick="alert('Feature coming soon!')">
                                <i class="fas fa-edit me-2"></i>Edit Packages
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="table-card h-100">
                        <div class="management-header">
                            <h3 class="management-title">
                                <i class="fas fa-ship text-primary"></i>
                                Yacht Details
                            </h3>
                        </div>
                        <p class="text-muted mb-3">Manage individual yacht details, specifications, and images.</p>
                        <div class="text-center">
                            <button class="btn btn-outline-primary w-100" onclick="alert('Feature coming soon!')">
                                <i class="fas fa-edit me-2"></i>Edit Yachts
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="table-card h-100">
                        <div class="management-header">
                            <h3 class="management-title">
                                <i class="fas fa-blog text-secondary"></i>
                                Blog
                            </h3>
                        </div>
                        <p class="text-muted mb-3">Manage blog posts, articles, and news content.</p>
                        <div class="text-center">
                            <button class="btn btn-outline-secondary w-100" onclick="alert('Feature coming soon!')">
                                <i class="fas fa-edit me-2"></i>Edit Blog
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="table-card h-100">
                        <div class="management-header">
                            <h3 class="management-title">
                                <i class="fas fa-envelope text-info"></i>
                                Contact Page
                            </h3>
                        </div>
                        <p class="text-muted mb-3">Manage contact information, address, and contact form settings.</p>
                        <div class="text-center">
                            <button class="btn btn-outline-info w-100" onclick="alert('Feature coming soon!')">
                                <i class="fas fa-edit me-2"></i>Edit Contact
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="table-card h-100">
                        <div class="management-header">
                            <h3 class="management-title">
                                <i class="fas fa-images text-danger"></i>
                                Media Library
                            </h3>
                        </div>
                        <p class="text-muted mb-3">Manage website images, logos, and media files.</p>
                        <div class="text-center">
                            <button class="btn btn-outline-danger w-100" onclick="alert('Feature coming soon!')">
                                <i class="fas fa-folder-open me-2"></i>Manage Media
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="table-card h-100">
                        <div class="management-header">
                            <h3 class="management-title">
                                <i class="fas fa-palette text-purple"></i>
                                Site Appearance
                            </h3>
                        </div>
                        <p class="text-muted mb-3">Customize colors, fonts, and overall site appearance.</p>
                        <div class="text-center">
                            <button class="btn btn-outline-dark w-100" onclick="alert('Feature coming soon!')">
                                <i class="fas fa-paint-brush me-2"></i>Customize
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
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


// Initialize dashboard
const dashboard = new AdminDashboard();
