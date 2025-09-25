/**
 * Dashboard Page - Main dashboard functionality
 */

class DashboardPage extends AdminBase {
    constructor() {
        super();
        this.loadDashboard();
    }

    async loadDashboard() {
        try {
            // Load dashboard stats
            const stats = await this.fetchDashboardStats();
            
            // Update stats grid
            this.renderStatsCards(stats);

            // Load recent tables
            await this.loadRecentTables();

        } catch (error) {
            console.error('Error loading dashboard:', error);
            document.getElementById('statsGrid').innerHTML = '<div class="loading">Error loading dashboard data</div>';
        }
    }

    async fetchDashboardStats() {
        try {
            const data = await this.apiRequest('../api/v1/dashboardData.php?action=stats');
            
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

    renderStatsCards(stats) {
        const statsGrid = document.getElementById('statsGrid');
        statsGrid.innerHTML = `
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
        `;
    }

    async loadRecentTables() {
        // Load recent contacts
        try {
            const contactsData = await this.apiRequest('../api/v1/contactData.php?action=list&limit=5');
            
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

        // Load recent bookings
        try {
            const bookingsData = await this.apiRequest('../api/v1/bookingData.php?action=list&limit=5');
            
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
}

// Initialize dashboard page
document.addEventListener('DOMContentLoaded', function() {
    adminBase = new DashboardPage();
});

