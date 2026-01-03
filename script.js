/**
 * Employee Management System - Main JavaScript File
 * Handles all interactive functionality with proper error handling and accessibility
 */

'use strict';

// ===================================================================
// Application Configuration
// ===================================================================
const CONFIG = {
    ANIMATION_DURATION: 300,
    DEBOUNCE_DELAY: 250,
    AUTO_SAVE_DELAY: 1000,
    MAX_RETRY_ATTEMPTS: 3,
    API_TIMEOUT: 5000,
    API_BASE_URL: 'http://localhost:8001/api',
    ENDPOINTS: {
        LEAVE_REQUESTS: 'http://localhost:8001/api/leave_requests',
        LEAVE_TYPES: 'http://localhost:8001/api/leave_requests?leave_types=1',
        LEAVE_STATS: 'http://localhost:8001/api/leave_requests?stats=1',
        HEALTH_CHECK: 'http://localhost:8001/api/health',
        PAYROLL: 'http://localhost:8001/api/payroll'
    }
};

// ===================================================================
// Utility Functions
// ===================================================================
const Utils = {
    /**
     * Debounce function to limit function calls
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @returns {Function} Debounced function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function to limit function calls
     * @param {Function} func - Function to throttle
     * @param {number} limit - Limit in milliseconds
     * @returns {Function} Throttled function
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Check if element is visible in viewport
     * @param {Element} element - Element to check
     * @returns {boolean} True if element is visible
     */
    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    },

    /**
     * Animate element with CSS classes
     * @param {Element} element - Element to animate
     * @param {string} animationClass - CSS class for animation
     * @param {number} duration - Animation duration
     */
    animate(element, animationClass, duration = CONFIG.ANIMATION_DURATION) {
        return new Promise(resolve => {
            element.classList.add(animationClass);
            setTimeout(() => {
                element.classList.remove(animationClass);
                resolve();
            }, duration);
        });
    },

    /**
     * Format date for display
     * @param {Date|string} date - Date to format
     * @returns {string} Formatted date string
     */
    formatDate(date) {
        if (!date) return '-';
        const d = new Date(date);
        if (isNaN(d.getTime())) return '-';
        return d.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    },

    /**
     * Calculate days between two dates
     * @param {Date} startDate - Start date
     * @param {Date} endDate - End date
     * @returns {number} Number of days
     */
    calculateDays(startDate, endDate) {
        const oneDay = 24 * 60 * 60 * 1000;
        return Math.round(Math.abs((endDate - startDate) / oneDay)) + 1;
    },

    /**
     * Show loading state
     * @param {boolean} show - Whether to show loading
     */
    showLoading(show = true) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.toggle('active', show);
            overlay.setAttribute('aria-hidden', !show);
        }
    },

    /**
     * Show notification (could be expanded to use a toast library)
     * @param {string} message - Message to show
     * @param {string} type - Type of notification (success, error, warning, info)
     */
    showNotification(message, type = 'info') {
        // For now, using alert - could be replaced with a toast system
        if (type === 'error') {
            console.error(message);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
        
        // Simple alert for demo - replace with proper notification system
        alert(message);
    }
};

// ===================================================================
// API Integration
// ===================================================================
const ApiClient = {
    /**
     * Make HTTP request to API
     * @param {string} url - API endpoint URL
     * @param {Object} options - Request options
     * @returns {Promise} Response promise
     */
    async request(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            timeout: CONFIG.API_TIMEOUT
        };

        const finalOptions = { ...defaultOptions, ...options };

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), finalOptions.timeout);

            const response = await fetch(url, {
                ...finalOptions,
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'API request failed');
            }

            return data;
        } catch (error) {
            if (error.name === 'AbortError') {
                throw new Error('Request timeout');
            }
            throw error;
        }
    },

    /**
     * GET request
     */
    async get(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const finalUrl = queryString ? `${url}?${queryString}` : url;
        return this.request(finalUrl);
    },

    /**
     * POST request
     */
    async post(url, data = {}) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    /**
     * PUT request
     */
    async put(url, data = {}) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    /**
     * DELETE request
     */
    async delete(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const finalUrl = queryString ? `${url}?${queryString}` : url;
        return this.request(finalUrl, {
            method: 'DELETE'
        });
    }
};

// ===================================================================
// Leave Management API
// ===================================================================
const LeaveAPI = {
    /**
     * Get leave requests
     */
    async getLeaveRequests(filters = {}) {
        try {
            const response = await ApiClient.get(CONFIG.ENDPOINTS.LEAVE_REQUESTS, filters);
            return response.data;
        } catch (error) {
            console.error('Error fetching leave requests:', error);
            throw error;
        }
    },

    /**
     * Get leave types
     */
    async getLeaveTypes() {
        try {
            const response = await ApiClient.get(CONFIG.ENDPOINTS.LEAVE_TYPES);
            return response.data;
        } catch (error) {
            console.error('Error fetching leave types:', error);
            throw error;
        }
    },

    /**
     * Get leave statistics
     */
    async getLeaveStats() {
        try {
            const response = await ApiClient.get(CONFIG.ENDPOINTS.LEAVE_STATS);
            return response.data;
        } catch (error) {
            console.error('Error fetching leave statistics:', error);
            throw error;
        }
    },

    /**
     * Submit leave request
     */
    async submitLeaveRequest(requestData) {
        try {
            const response = await ApiClient.post(CONFIG.ENDPOINTS.LEAVE_REQUESTS, requestData);
            return response.data;
        } catch (error) {
            console.error('Error submitting leave request:', error);
            throw error;
        }
    },

    /**
     * Update leave request (approve/reject)
     */
    async updateLeaveRequest(requestId, updateData) {
        try {
            const response = await ApiClient.put(`${CONFIG.ENDPOINTS.LEAVE_REQUESTS}?id=${requestId}`, updateData);
            return response.data;
        } catch (error) {
            console.error('Error updating leave request:', error);
            throw error;
        }
    },

    /**
     * Cancel leave request
     */
    async cancelLeaveRequest(requestId) {
        try {
            const response = await ApiClient.delete(`${CONFIG.ENDPOINTS.LEAVE_REQUESTS}?id=${requestId}`);
            return response.data;
        } catch (error) {
            console.error('Error cancelling leave request:', error);
            throw error;
        }
    },

    /**
     * Check API health
     */
    async healthCheck() {
        try {
            const response = await ApiClient.get(CONFIG.ENDPOINTS.HEALTH_CHECK);
            return response.data;
        } catch (error) {
            console.error('API health check failed:', error);
            return { status: 'unhealthy', error: error.message };
        }
    }
};

// ===================================================================
// Payroll Management API
// ===================================================================
const PayrollAPI = {
    /**
     * Get employee payroll data
     */
    async getEmployeePayroll(employeeId = null) {
        try {
            const params = employeeId ? { employee_id: employeeId } : {};
            const response = await ApiClient.get(CONFIG.ENDPOINTS.PAYROLL, params);
            return response.data;
        } catch (error) {
            console.error('Error fetching payroll data:', error);
            throw error;
        }
    },

    /**
     * Update employee salary (admin only)
     */
    async updateSalary(employeeId, newSalary) {
        try {
            const response = await ApiClient.put(`${CONFIG.ENDPOINTS.PAYROLL}?employee_id=${employeeId}`, {
                salary: newSalary
            });
            return response.data;
        } catch (error) {
            console.error('Error updating salary:', error);
            throw error;
        }
    },

    /**
     * Get all employees payroll data (admin only)
     */
    async getAllPayrollData() {
        try {
            const response = await ApiClient.get(CONFIG.ENDPOINTS.PAYROLL);
            return response.data;
        } catch (error) {
            console.error('Error fetching all payroll data:', error);
            throw error;
        }
    }
};

// ===================================================================
// Application State Management
// ===================================================================
const AppState = {
    isAuthenticated: false,
    currentRole: 'employee',
    currentUser: {
        id: 'E001',
        name: 'John Doe',
        email: 'john.doe@company.com',
        department: 'Engineering',
        position: 'Senior Developer'
    },
    sidebarOpen: false,
    activeModal: null,

    /**
     * Set current role and update UI
     * @param {string} role - Role to set ('employee' or 'admin')
     */
    async setRole(role) {
        if (role === this.currentRole) return;
        
        this.currentRole = role;
        this.updateRoleDisplay();
        await this.switchDashboard(role);
    },

    /**
     * Update role display in UI
     */
    updateRoleDisplay() {
        // Update role buttons
        document.querySelectorAll('.role-btn').forEach(btn => {
            const isActive = btn.dataset.role === this.currentRole;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive);
        });
    },

    /**
     * Switch between dashboards
     * @param {string} role - Role dashboard to show
     */
    async switchDashboard(role) {
        // Hide all dashboards
        document.querySelectorAll('.dashboard').forEach(dashboard => {
            dashboard.classList.remove('active');
        });

        // Show selected dashboard
        const targetDashboard = document.getElementById(`${role}Dashboard`);
        if (targetDashboard) {
            targetDashboard.classList.add('active');
        }

        // Load role-specific data
        if (role === 'admin') {
            try {
                // Load initial admin dashboard data
                const stats = await LeaveAPI.getLeaveStats();
                ModalManager.updateDashboardStats(stats);
                
                // Only load section-specific data if we're on that section
                const currentSection = document.querySelector('.admin-section.active');
                if (currentSection) {
                    const sectionId = currentSection.id.replace('admin-', '');
                    await NavigationManager.loadSectionData(sectionId);
                }
            } catch (error) {
                console.error('Error loading admin data:', error);
            }
        }

        // Update navigation active states
        this.updateNavigation();
    },

    /**
     * Update navigation based on current context
     */
    updateNavigation() {
        const currentDashboard = document.querySelector('.dashboard.active');
        if (currentDashboard) {
            const navLinks = currentDashboard.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
                link.removeAttribute('aria-current');
            });

            // Set first nav item as active (Dashboard)
            const firstNavLink = currentDashboard.querySelector('.nav-link[data-section="dashboard"]');
            if (firstNavLink) {
                firstNavLink.classList.add('active');
                firstNavLink.setAttribute('aria-current', 'page');
            }
        }
    }
};

// ===================================================================
// Modal Management System
// ===================================================================
const ModalManager = {
    activeModal: null,
    focusableSelectors: 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
    previousFocusElement: null,

    /**
     * Open a modal
     * @param {string} modalId - ID of modal to open
     */
    open(modalId) {
        try {
            const modal = document.getElementById(modalId);
            if (!modal) {
                console.error(`Modal with ID '${modalId}' not found`);
                return;
            }

            // Close any existing modal first
            if (this.activeModal) {
                this.close(this.activeModal.id);
            }

            // Store current focus
            this.previousFocusElement = document.activeElement;

            // Show modal
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            // Set focus to first focusable element or close button
            const closeButton = modal.querySelector('.close-modal');
            if (closeButton) {
                closeButton.focus();
            }

            this.activeModal = modal;
            AppState.activeModal = modalId;

            // Add escape key listener
            this.addKeyListeners();

            // Populate modal data if needed
            this.populateModalData(modalId);

        } catch (error) {
            console.error('Error opening modal:', error);
            Utils.showNotification('Error opening modal', 'error');
        }
    },

    /**
     * Close active modal
     * @param {string} modalId - Optional specific modal ID to close
     */
    close(modalId = null) {
        try {
            const modal = modalId ? document.getElementById(modalId) : this.activeModal;
            if (!modal) return;

            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';

            // Restore focus
            if (this.previousFocusElement) {
                this.previousFocusElement.focus();
                this.previousFocusElement = null;
            }

            this.activeModal = null;
            AppState.activeModal = null;

            // Remove event listeners
            this.removeKeyListeners();

        } catch (error) {
            console.error('Error closing modal:', error);
        }
    },

    /**
     * Add keyboard event listeners for modal
     */
    addKeyListeners() {
        document.addEventListener('keydown', this.handleKeyDown.bind(this));
    },

    /**
     * Remove keyboard event listeners for modal
     */
    removeKeyListeners() {
        document.removeEventListener('keydown', this.handleKeyDown.bind(this));
    },

    /**
     * Handle keyboard events in modal
     * @param {KeyboardEvent} event - Keyboard event
     */
    handleKeyDown(event) {
        if (!this.activeModal) return;

        switch (event.key) {
            case 'Escape':
                event.preventDefault();
                this.close();
                break;
            case 'Tab':
                this.trapFocus(event);
                break;
        }
    },

    /**
     * Trap focus within modal
     * @param {KeyboardEvent} event - Tab key event
     */
    trapFocus(event) {
        if (!this.activeModal) return;

        const focusableElements = this.activeModal.querySelectorAll(this.focusableSelectors);
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        if (event.shiftKey) {
            if (document.activeElement === firstFocusable) {
                lastFocusable.focus();
                event.preventDefault();
            }
        } else {
            if (document.activeElement === lastFocusable) {
                firstFocusable.focus();
                event.preventDefault();
            }
        }
    },

    /**
     * Populate modal with dynamic data
     * @param {string} modalId - Modal ID
     */
    populateModalData(modalId) {
        switch (modalId) {
            case 'profileModal':
                this.populateProfileModal();
                break;
            case 'attendanceModal':
                this.populateAttendanceModal();
                break;
            case 'leaveModal':
                this.populateLeaveModal();
                break;
        }
    },

    /**
     * Populate profile modal with user data
     */
    populateProfileModal() {
        const modal = document.getElementById('profileModal');
        if (!modal) return;

        const user = AppState.currentUser;
        const infoRows = modal.querySelectorAll('.info-row');
        
        const userData = {
            'Employee ID:': `#${user.id}`,
            'Name:': user.name,
            'Email:': user.email,
            'Department:': user.department,
            'Position:': user.position,
            'Join Date:': 'Jan 15, 2023',
            'Phone:': '+1 (555) 123-4567'
        };

        infoRows.forEach(row => {
            const label = row.querySelector('.info-label');
            const value = row.querySelector('.info-value');
            if (label && value && userData[label.textContent]) {
                value.textContent = userData[label.textContent];
            }
        });
    },

    /**
     * Populate attendance modal with attendance data
     */
    populateAttendanceModal() {
        const modal = document.getElementById('attendanceModal');
        if (!modal) return;

        // Simulate getting attendance data
        const attendanceData = this.getAttendanceData();
        
        // Update summary values
        const summaryRows = modal.querySelectorAll('.attendance-summary .info-row');
        summaryRows.forEach(row => {
            const label = row.querySelector('.info-label');
            const value = row.querySelector('.info-value');
            if (label && value) {
                const labelText = label.textContent;
                if (labelText.includes('Present')) {
                    value.textContent = attendanceData.daysPresent;
                } else if (labelText.includes('Absent')) {
                    value.textContent = attendanceData.daysAbsent;
                } else if (labelText.includes('Late')) {
                    value.textContent = attendanceData.lateArrivals;
                }
            }
        });

        // Update check-out button
        const checkOutBtn = modal.querySelector('#checkOutBtn');
        if (checkOutBtn) {
            if (attendanceData.checkedOut) {
                checkOutBtn.textContent = 'Checked Out';
                checkOutBtn.disabled = true;
            } else {
                checkOutBtn.textContent = 'Check Out';
                checkOutBtn.disabled = false;
                checkOutBtn.onclick = () => this.handleCheckOut();
            }
        }
    },

    /**
     * Populate leave modal and setup form
     */
    async populateLeaveModal() {
        const modal = document.getElementById('leaveModal');
        if (!modal) return;

        try {
            // Load leave types from API
            Utils.showLoading(true);
            const leaveTypes = await LeaveAPI.getLeaveTypes();
            
            // Populate leave type dropdown
            const leaveTypeSelect = modal.querySelector('#leaveType');
            if (leaveTypeSelect) {
                // Clear existing options except the first one
                leaveTypeSelect.innerHTML = '<option value="">Select leave type</option>';
                
                leaveTypes.forEach(leaveType => {
                    const option = document.createElement('option');
                    option.value = leaveType.id;
                    option.textContent = `${leaveType.name}${leaveType.is_paid ? ' (Paid)' : ' (Unpaid)'}`;
                    option.dataset.maxDays = leaveType.max_days_per_year || 0;
                    option.dataset.description = leaveType.description || '';
                    leaveTypeSelect.appendChild(option);
                });
            }
            
            Utils.showLoading(false);
        } catch (error) {
            Utils.showLoading(false);
            console.error('Error loading leave types:', error);
            Utils.showNotification('Error loading leave types', 'error');
        }

        const form = modal.querySelector('#leaveRequestForm');
        if (form) {
            // Reset form
            form.reset();
            
            // Set minimum date to today
            const startDateInput = form.querySelector('#startDate');
            const endDateInput = form.querySelector('#endDate');
            const today = new Date().toISOString().split('T')[0];
            
            if (startDateInput) {
                startDateInput.min = today;
                startDateInput.addEventListener('change', this.handleStartDateChange.bind(this));
            }
            
            if (endDateInput) {
                endDateInput.min = today;
            }

            // Setup form submission
            form.onsubmit = this.handleLeaveFormSubmit.bind(this);
        }
    },

    /**
     * Handle start date change in leave form
     * @param {Event} event - Change event
     */
    handleStartDateChange(event) {
        const endDateInput = document.getElementById('endDate');
        if (endDateInput && event.target.value) {
            endDateInput.min = event.target.value;
            if (endDateInput.value && endDateInput.value < event.target.value) {
                endDateInput.value = event.target.value;
            }
        }
    },

    /**
     * Handle leave form submission
     * @param {Event} event - Form submit event
     */
    async handleLeaveFormSubmit(event) {
        event.preventDefault();
        
        try {
            const form = event.target;
            const formData = new FormData(form);
            const leaveData = Object.fromEntries(formData.entries());
            
            // Validate form data
            if (!this.validateLeaveForm(leaveData)) {
                return;
            }

            // Prepare API request data
            const requestData = {
                leave_type_id: parseInt(leaveData.leaveType),
                start_date: leaveData.startDate,
                end_date: leaveData.endDate,
                reason: leaveData.reason.trim()
            };

            // Show loading
            Utils.showLoading(true);

            // Submit to API
            const response = await LeaveAPI.submitLeaveRequest(requestData);
            
            Utils.showLoading(false);
            
            // Calculate days for display
            const startDate = new Date(leaveData.startDate);
            const endDate = new Date(leaveData.endDate);
            const days = Utils.calculateDays(startDate, endDate);
            
            Utils.showNotification(`Leave request submitted successfully for ${days} day${days > 1 ? 's' : ''}`, 'success');
            this.close('leaveModal');
            
            // Refresh leave requests data
            await this.refreshLeaveData();

        } catch (error) {
            console.error('Error submitting leave request:', error);
            Utils.showLoading(false);
            
            // Handle API validation errors
            if (error.message.includes('validation') || error.message.includes('overlap')) {
                Utils.showNotification(error.message, 'error');
            } else {
                Utils.showNotification('Error submitting leave request. Please try again.', 'error');
            }
        }
    },

    /**
     * Validate leave form data
     * @param {Object} data - Form data
     * @returns {boolean} True if valid
     */
    validateLeaveForm(data) {
        if (!data.leaveType) {
            Utils.showNotification('Please select a leave type', 'error');
            return false;
        }
        
        if (!data.startDate || !data.endDate) {
            Utils.showNotification('Please select both start and end dates', 'error');
            return false;
        }
        
        const startDate = new Date(data.startDate);
        const endDate = new Date(data.endDate);
        
        if (startDate > endDate) {
            Utils.showNotification('End date cannot be before start date', 'error');
            return false;
        }
        
        if (!data.reason.trim()) {
            Utils.showNotification('Please provide a reason for your leave', 'error');
            return false;
        }
        
        return true;
    },

    /**
     * Handle check-out action
     */
    handleCheckOut() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });

        Utils.showLoading(true);

        setTimeout(() => {
            Utils.showLoading(false);
            Utils.showNotification(`Successfully checked out at ${timeString}`, 'success');
            
            // Update button state
            const checkOutBtn = document.getElementById('checkOutBtn');
            if (checkOutBtn) {
                checkOutBtn.textContent = 'Checked Out';
                checkOutBtn.disabled = true;
            }
        }, 500);
    },

    /**
     * Get mock attendance data
     * @returns {Object} Attendance data
     */
    getAttendanceData() {
        return {
            daysPresent: 22,
            daysAbsent: 1,
            lateArrivals: 3,
            checkedOut: false
        };
    },

    /**
     * Refresh leave-related data in the UI
     */
    async refreshLeaveData() {
        try {
            // Refresh statistics
            const stats = await LeaveAPI.getLeaveStats();
            this.updateDashboardStats(stats);
            
            // Refresh leave requests if on admin dashboard
            if (AppState.currentRole === 'admin') {
                await AdminManager.refreshLeaveRequests();
            }
            
            // Update activity feed
            await this.updateActivityFeed();
            
        } catch (error) {
            console.error('Error refreshing leave data:', error);
        }
    },
    
    /**
     * Update dashboard statistics
     */
    updateDashboardStats(stats) {
        // Update employee dashboard stats
        const employeeCards = document.querySelectorAll('#employeeDashboard .card-value');
        if (stats.days_used_this_year !== undefined) {
            // Update employee-specific stats if needed
        }
        
        // Update admin dashboard stats
        if (AppState.currentRole === 'admin') {
            const pendingCard = document.querySelector('.card-value[aria-label*="pending"]');
            if (pendingCard && stats.pending_requests !== undefined) {
                pendingCard.textContent = stats.pending_requests;
                pendingCard.setAttribute('aria-label', `${stats.pending_requests} pending leave requests`);
            }
            
            const onLeaveCard = document.querySelector('.card-value[aria-label*="on leave"]');
            if (onLeaveCard && stats.employees_on_leave !== undefined) {
                onLeaveCard.textContent = stats.employees_on_leave;
                onLeaveCard.setAttribute('aria-label', `${stats.employees_on_leave} employees on leave`);
            }
        }
    },
    
    /**
     * Update activity feed with recent leave requests
     */
    async updateActivityFeed() {
        try {
            // Get recent leave requests for activity feed
            const requests = await LeaveAPI.getLeaveRequests({ limit: 5 });
            const activityList = document.querySelector('.activity-list');
            
            if (activityList && requests) {
                // Clear existing items
                activityList.innerHTML = '';
                
                // Add recent requests
                requests.forEach(request => {
                    this.addActivityItem(activityList, request);
                });
            }
        } catch (error) {
            console.error('Error updating activity feed:', error);
        }
    },
    
    /**
     * Add activity item to feed
     */
    addActivityItem(container, request) {
        const item = document.createElement('article');
        item.className = 'activity-item';
        item.setAttribute('role', 'listitem');
        
        const statusClass = `status-${request.status}`;
        const statusText = request.status.charAt(0).toUpperCase() + request.status.slice(1);
        const actionText = request.status === 'pending' ? 'submitted' : request.status;
        
        item.innerHTML = `
            <div class="activity-title">
                Leave Request ${actionText}
                <span class="activity-status ${statusClass}" aria-label="Status: ${statusText}">${statusText}</span>
            </div>
            <time class="activity-time" datetime="${request.applied_at}">${Utils.formatDate(request.applied_at)}</time>
        `;
        
        container.appendChild(item);
    }
};

// ===================================================================
// Admin Functions
// ===================================================================
const AdminManager = {
    /**
     * Handle leave approval/rejection
     * @param {string} action - 'approve' or 'reject'
     * @param {Element} button - Button that was clicked
     */
    async handleLeaveAction(action, button) {
        try {
            const row = button.closest('tr');
            const requestId = row.dataset.requestId;
            const employeeName = row.querySelector('td').textContent;
            const leaveType = row.querySelector('td:nth-child(2)').textContent;

            if (!requestId) {
                console.error('Request ID not found');
                return;
            }

            // Show confirmation with comment input
            const actionText = action === 'approve' ? 'approve' : 'reject';
            const confirmed = confirm(`Are you sure you want to ${actionText} the ${leaveType} request for ${employeeName}?`);
            
            if (!confirmed) return;
            
            // Get admin comments
            const adminComments = prompt(`Optional comments for ${actionText}ing this request:`, '');

            // Show loading
            Utils.showLoading(true);

            // API call to update leave request
            const updateData = {
                status: action === 'approve' ? 'approved' : 'rejected',
                admin_comments: adminComments || null
            };
            
            const response = await LeaveAPI.updateLeaveRequest(requestId, updateData);
            
            Utils.showLoading(false);
            
            // Update UI
            const actionButtons = row.querySelector('td:last-child');
            const statusClass = action === 'approve' ? 'status-approved' : 'status-rejected';
            const statusText = action === 'approve' ? 'Approved' : 'Rejected';
            
            actionButtons.innerHTML = `<span class="status-badge ${statusClass}">${statusText}</span>`;
            
            // Show notification
            Utils.showNotification(`Leave request ${statusText.toLowerCase()} successfully`, 'success');
            
            // Update statistics
            this.updateLeaveStatistics(action);
            
            // Refresh data
            await this.refreshLeaveRequests();

        } catch (error) {
            console.error('Error handling leave action:', error);
            Utils.showLoading(false);
            Utils.showNotification('Error processing request: ' + error.message, 'error');
        }
    },

    /**
     * Handle employee view action
     * @param {string} employeeId - Employee ID
     */
    handleEmployeeView(employeeId) {
        // For now, just show an alert - could open a detailed modal
        Utils.showNotification(`Viewing details for employee ${employeeId}`, 'info');
    },

    /**
     * Refresh leave requests table
     */
    async refreshLeaveRequests() {
        const tableBody = document.getElementById('leaveRequestsBody');
        if (!tableBody) {
            console.warn('Leave requests table body not found');
            return;
        }
        
        // Show loading state
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading leave requests...</td></tr>';
        
        try {
            const requests = await LeaveAPI.getLeaveRequests();
            console.log('Leave requests received:', requests);
            
            // Clear existing rows
            tableBody.innerHTML = '';
            
            if (!requests) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No data available</td></tr>';
                return;
            }
            
            // Add new rows
            let requestsArray = [];
            if (Array.isArray(requests)) {
                requestsArray = requests;
            } else if (requests.requests && Array.isArray(requests.requests)) {
                requestsArray = requests.requests;
            }
            
            if (requestsArray.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No leave requests found</td></tr>';
                return;
            }
            
            requestsArray.forEach(request => {
                this.addLeaveRequestRow(tableBody, request);
            });
            
        } catch (error) {
            console.error('Error refreshing leave requests:', error);
            if (tableBody) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center error">Error loading leave requests</td></tr>';
            }
        }
    },
    
    /**
     * Add leave request row to table
     */
    addLeaveRequestRow(tableBody, request) {
        const row = document.createElement('tr');
        row.dataset.requestId = request.id;
        
        // Determine action buttons based on status
        let actionButtons;
        if (request.status === 'pending') {
            actionButtons = `
                <button class="btn-sm btn-approve" data-action="approve" aria-label="Approve ${request.employee_name}'s leave request">Approve</button>
                <button class="btn-sm btn-reject" data-action="reject" aria-label="Reject ${request.employee_name}'s leave request">Reject</button>
            `;
        } else {
            const statusClass = request.status === 'approved' ? 'status-approved' : 'status-rejected';
            const statusText = request.status.charAt(0).toUpperCase() + request.status.slice(1);
            actionButtons = `<span class="status-badge ${statusClass}">${statusText}</span>`;
        }
        
        row.innerHTML = `
            <td>${request.employee_name}</td>
            <td>${request.leave_type}</td>
            <td>${Utils.formatDate(request.start_date)}</td>
            <td>${Utils.formatDate(request.end_date)}</td>
            <td>${request.total_days}</td>
            <td>${actionButtons}</td>
        `;
        
        tableBody.appendChild(row);
    },

    /**
     * Update leave statistics after approval/rejection
     * @param {string} action - Action taken
     */
    updateLeaveStatistics(action) {
        const pendingCard = document.querySelector('.card-value[aria-label*="pending"]');
        if (pendingCard && action) {
            const currentValue = parseInt(pendingCard.textContent) || 0;
            const newValue = Math.max(0, currentValue - 1);
            pendingCard.textContent = newValue;
            pendingCard.setAttribute('aria-label', `${newValue} pending leave requests`);
        }
    }
};

// ===================================================================
// Payroll Management
// ===================================================================
const PayrollManager = {
    currentEmployee: null,

    /**
     * Load employee payroll data for modal
     */
    async loadEmployeePayroll(employeeId = 'E001') {
        try {
            Utils.showLoading(true);
            const payrollData = await PayrollAPI.getEmployeePayroll(employeeId);
            this.populateEmployeePayrollModal(payrollData);
            Utils.showLoading(false);
        } catch (error) {
            Utils.showLoading(false);
            console.error('Error loading employee payroll:', error);
            Utils.showNotification('Error loading payroll data', 'error');
        }
    },

    /**
     * Populate employee payroll modal with data
     */
    populateEmployeePayrollModal(data) {
        document.getElementById('employeeAnnualSalary').textContent = `$${data.annual_salary?.toLocaleString() || '0'}`;
        document.getElementById('employeeMonthlySalary').textContent = `$${data.monthly_salary?.toLocaleString() || '0'}`;
        document.getElementById('employeeWeeklySalary').textContent = `$${data.weekly_salary?.toLocaleString() || '0'}`;
        document.getElementById('employeeHourlyRate').textContent = `$${data.hourly_rate?.toFixed(2) || '0.00'}`;
        document.getElementById('employeeYearsOfService').textContent = `${data.years_of_service || 0} years`;
        document.getElementById('employeeHealthInsurance').textContent = `$${data.health_insurance?.toLocaleString() || '0'}/month`;
        document.getElementById('employee401k').textContent = `$${data.retirement_401k?.toLocaleString() || '0'}/month`;
        document.getElementById('employeeTotalCompensation').textContent = `$${data.total_monthly_compensation?.toLocaleString() || '0'}`;
    },

    /**
     * Load all payroll data for admin dashboard
     */
    async loadAllPayrollData() {
        const tableBody = document.getElementById('payrollTableBody');
        if (!tableBody) {
            console.warn('Payroll table body not found');
            return;
        }
        
        // Show loading state
        tableBody.innerHTML = '<tr><td colspan="8" class="text-center">Loading payroll data...</td></tr>';
        
        try {
            const payrollData = await PayrollAPI.getAllPayrollData();
            console.log('Payroll data received:', payrollData);
            this.populatePayrollTable(payrollData);
        } catch (error) {
            console.error('Error loading all payroll data:', error);
            this.showPayrollTableError();
        }
    },

    /**
     * Populate payroll table with data
     */
    populatePayrollTable(employees) {
        const tableBody = document.getElementById('payrollTableBody');
        if (!tableBody) return;

        tableBody.innerHTML = '';

        if (!employees || employees.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="8" class="text-center">No payroll data available</td>';
            tableBody.appendChild(row);
            return;
        }

        employees.forEach(employee => {
            this.addPayrollTableRow(tableBody, employee);
        });
    },

    /**
     * Add payroll row to table
     */
    addPayrollTableRow(tableBody, employee) {
        const row = document.createElement('tr');
        row.dataset.employeeId = employee.id;
        
        row.innerHTML = `
            <td>${employee.id}</td>
            <td>${employee.name}</td>
            <td>${employee.department}</td>
            <td>${employee.position}</td>
            <td>$${employee.annual_salary?.toLocaleString() || '0'}</td>
            <td>$${employee.monthly_salary?.toLocaleString() || '0'}</td>
            <td>${employee.years_of_service || 0} years</td>
            <td>
                <button class="btn-sm btn-edit-salary" data-employee-id="${employee.id}" aria-label="Edit ${employee.name}'s salary">Edit Salary</button>
                <button class="btn-sm btn-view-payroll" data-employee-id="${employee.id}" aria-label="View ${employee.name}'s payroll details">View Details</button>
            </td>
        `;
        
        tableBody.appendChild(row);
    },

    /**
     * Show payroll table error
     */
    showPayrollTableError() {
        const tableBody = document.getElementById('payrollTableBody');
        if (tableBody) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center error">Error loading payroll data</td></tr>';
        }
    },

    /**
     * Handle salary update modal
     */
    openSalaryUpdateModal(employeeId) {
        // Find employee data from current payroll data
        const tableRow = document.querySelector(`[data-employee-id="${employeeId}"]`).closest('tr');
        if (!tableRow) return;

        const employeeName = tableRow.children[1].textContent;
        const currentSalary = tableRow.children[4].textContent.replace(/[$,]/g, '');

        // Populate update modal
        document.getElementById('updateEmployeeName').textContent = employeeName;
        document.getElementById('updateCurrentSalary').textContent = `$${parseFloat(currentSalary).toLocaleString()}`;
        document.getElementById('updateEmployeeId').value = employeeId;
        document.getElementById('newSalary').value = currentSalary;

        this.currentEmployee = { id: employeeId, name: employeeName, currentSalary: parseFloat(currentSalary) };

        // Update preview
        this.updateSalaryPreview(parseFloat(currentSalary));

        ModalManager.open('salaryUpdateModal');
    },

    /**
     * Update salary preview in real-time
     */
    updateSalaryPreview(annualSalary) {
        const monthly = annualSalary / 12;
        const weekly = annualSalary / 52;
        const hourly = annualSalary / 2080;

        document.getElementById('previewMonthlySalary').textContent = `$${monthly.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('previewWeeklySalary').textContent = `$${weekly.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('previewHourlySalary').textContent = `$${hourly.toFixed(2)}`;
    },

    /**
     * Handle salary update submission
     */
    async handleSalaryUpdate(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const employeeId = formData.get('employeeId');
        const newSalary = parseFloat(formData.get('newSalary'));

        if (!employeeId || !newSalary || newSalary <= 0) {
            Utils.showNotification('Please enter a valid salary amount', 'error');
            return;
        }

        try {
            Utils.showLoading(true);
            const updatedEmployee = await PayrollAPI.updateSalary(employeeId, newSalary);
            
            Utils.showLoading(false);
            Utils.showNotification(`Salary updated successfully for ${updatedEmployee.name}`, 'success');
            
            ModalManager.close('salaryUpdateModal');
            
            // Refresh payroll table
            await this.loadAllPayrollData();
            
        } catch (error) {
            Utils.showLoading(false);
            console.error('Error updating salary:', error);
            Utils.showNotification('Error updating salary: ' + error.message, 'error');
        }
    }
};

// ===================================================================
// Navigation & UI Management
// ===================================================================
const NavigationManager = {
    /**
     * Toggle sidebar on mobile
     */
    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            const isOpen = sidebar.classList.contains('active');
            sidebar.classList.toggle('active', !isOpen);
            AppState.sidebarOpen = !isOpen;
            
            // Update aria attributes
            const toggleBtn = document.querySelector('.mobile-menu-toggle');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', !isOpen);
            }
        }
    },

    /**
     * Handle navigation clicks
     * @param {Element} navLink - Navigation link clicked
     */
    handleNavClick(navLink) {
        const section = navLink.dataset.section;
        const modal = navLink.dataset.modal;

        if (modal) {
            // Open modal
            ModalManager.open(modal);
        } else if (section) {
            // Navigate to section
            this.navigateToSection(section);
        }
    },

    /**
     * Navigate to a section
     * @param {string} sectionId - Section to navigate to
     */
    navigateToSection(sectionId) {
        const currentDashboard = document.querySelector('.dashboard.active');
        if (!currentDashboard) return;

        // Update active navigation
        currentDashboard.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            link.removeAttribute('aria-current');
        });

        // Add active class to clicked nav link
        const activeLink = currentDashboard.querySelector(`[data-section="${sectionId}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
            activeLink.setAttribute('aria-current', 'page');
        }

        // Handle admin section navigation
        if (currentDashboard.id === 'adminDashboard') {
            this.navigateAdminSection(sectionId);
        } else {
            // For employee dashboard, just show notification
            Utils.showNotification(`Navigating to ${sectionId} section`, 'info');
        }
    },

    /**
     * Navigate admin sections
     * @param {string} sectionId - Admin section to show
     */
    navigateAdminSection(sectionId) {
        // Hide all admin sections
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.remove('active');
        });

        // Show target section
        const targetSection = document.getElementById(`admin-${sectionId}`);
        if (targetSection) {
            targetSection.classList.add('active');
        }

        // Update page title and subtitle
        const titleElement = document.getElementById('adminSectionTitle');
        const subtitleElement = document.getElementById('adminSectionSubtitle');
        
        const sectionTitles = {
            'dashboard': {
                title: 'Admin Dashboard',
                subtitle: 'Manage your organization efficiently'
            },
            'employees': {
                title: 'Employee Management',
                subtitle: 'Manage employee records and information'
            },
            'attendance': {
                title: 'Attendance Management',
                subtitle: 'Monitor employee attendance and working hours'
            },
            'leave-approval': {
                title: 'Leave Management',
                subtitle: 'Review and approve employee leave requests'
            },
            'payroll': {
                title: 'Payroll Management',
                subtitle: 'Manage employee salaries and compensation'
            }
        };

        const sectionInfo = sectionTitles[sectionId] || sectionTitles['dashboard'];
        if (titleElement) titleElement.textContent = sectionInfo.title;
        if (subtitleElement) subtitleElement.textContent = sectionInfo.subtitle;

        // Load section-specific data
        this.loadSectionData(sectionId);
    },

    /**
     * Load data for specific admin section
     * @param {string} sectionId - Section to load data for
     */
    async loadSectionData(sectionId) {
        console.log(`Loading data for section: ${sectionId}`);
        try {
            switch (sectionId) {
                case 'payroll':
                    console.log('Loading payroll data...');
                    await PayrollManager.loadAllPayrollData();
                    break;
                case 'leave-approval':
                    console.log('Loading leave requests...');
                    await AdminManager.refreshLeaveRequests();
                    break;
                case 'dashboard':
                    console.log('Loading dashboard stats...');
                    // Load dashboard stats
                    const stats = await LeaveAPI.getLeaveStats();
                    ModalManager.updateDashboardStats(stats);
                    break;
                case 'employees':
                    console.log('Loading employee data...');
                    // Could load employee data here if needed
                    break;
                case 'attendance':
                    console.log('Loading attendance data...');
                    // Could load attendance data here if needed
                    break;
            }
        } catch (error) {
            console.error(`Error loading data for ${sectionId}:`, error);
        }
    }
};

// ===================================================================
// Event Handlers Setup
// ===================================================================
const EventHandlers = {
    /**
     * Initialize all event handlers
     */
    init() {
        this.setupLogin();
        this.setupRoleSwitcher();
        this.setupMobileMenu();
        this.setupNavigation();
        this.setupModals();
        this.setupCards();
        this.setupAdminActions();
        this.setupPayrollActions();
        this.setupLogout();
        this.setupKeyboardNavigation();
        this.setupResponsiveHandlers();
    },

    /**
     * Setup login form functionality
     */
    setupLogin() {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const selected = loginForm.querySelector('input[name="role"]:checked');
                const role = selected ? selected.value : 'employee';
                await App.handleLogin(role);
            });
        }
    },

    /**
     * Setup role switcher functionality
     */
    setupRoleSwitcher() {
        document.addEventListener('click', async (event) => {
            const roleBtn = event.target.closest('.role-btn');
            if (roleBtn) {
                if (!AppState.isAuthenticated) {
                    Utils.showNotification('Please login first to switch roles', 'warning');
                    return;
                }
                const role = roleBtn.dataset.role;
                if (role) {
                    await AppState.setRole(role);
                }
            }
        });
    },

    /**
     * Setup mobile menu functionality
     */
    setupMobileMenu() {
        document.addEventListener('click', (event) => {
            const menuToggle = event.target.closest('.mobile-menu-toggle');
            if (menuToggle) {
                NavigationManager.toggleSidebar();
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 768 && AppState.sidebarOpen) {
                const sidebar = document.querySelector('.sidebar');
                const menuToggle = document.querySelector('.mobile-menu-toggle');
                
                if (sidebar && !sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    NavigationManager.toggleSidebar();
                }
            }
        });
    },

    /**
     * Setup navigation functionality
     */
    setupNavigation() {
        document.addEventListener('click', (event) => {
            const navLink = event.target.closest('.nav-link');
            if (navLink) {
                NavigationManager.handleNavClick(navLink);
            }
        });
    },

    /**
     * Setup modal functionality
     */
    setupModals() {
        // Modal trigger buttons
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-modal]');
            if (trigger) {
                const modalId = trigger.dataset.modal;
                ModalManager.open(modalId);
            }
        });

        // Close modal buttons
        document.addEventListener('click', (event) => {
            const closeBtn = event.target.closest('.close-modal');
            if (closeBtn) {
                ModalManager.close();
            }
        });

        // Modal backdrop clicks
        document.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                ModalManager.close();
            }
        });

        // Cancel buttons in forms
        document.addEventListener('click', (event) => {
            const cancelBtn = event.target.closest('[data-action="cancel"]');
            if (cancelBtn) {
                ModalManager.close();
            }
        });
    },

    /**
     * Setup card click functionality
     */
    setupCards() {
        document.addEventListener('click', (event) => {
            const card = event.target.closest('.card[data-modal]');
            if (card) {
                const modalId = card.dataset.modal;
                ModalManager.open(modalId);
            }
        });

        // Handle keyboard navigation for cards
        document.addEventListener('keydown', (event) => {
            const card = event.target.closest('.card[role="button"]');
            if (card && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                card.click();
            }
        });
    },

    /**
     * Setup admin action handlers
     */
    setupAdminActions() {
        document.addEventListener('click', (event) => {
            const button = event.target.closest('button');
            if (!button) return;

            // Leave approval actions
            if (button.dataset.action === 'approve' || button.dataset.action === 'reject') {
                AdminManager.handleLeaveAction(button.dataset.action, button);
            }

            // Employee view actions
            if (button.classList.contains('btn-view') && button.dataset.employee) {
                AdminManager.handleEmployeeView(button.dataset.employee);
            }
        });
    },

    /**
     * Setup payroll functionality
     */
    setupPayrollActions() {
        // Payroll modal opening (for employees)
        document.addEventListener('click', (event) => {
            const payrollTrigger = event.target.closest('[data-modal="payrollModal"]');
            if (payrollTrigger) {
                PayrollManager.loadEmployeePayroll('E001');
            }
        });

        // Admin payroll actions
        document.addEventListener('click', (event) => {
            const editSalaryBtn = event.target.closest('.btn-edit-salary');
            if (editSalaryBtn) {
                const employeeId = editSalaryBtn.dataset.employeeId;
                PayrollManager.openSalaryUpdateModal(employeeId);
            }
        });

        document.addEventListener('click', (event) => {
            const viewPayrollBtn = event.target.closest('.btn-view-payroll');
            if (viewPayrollBtn) {
                const employeeId = viewPayrollBtn.dataset.employeeId;
                PayrollManager.loadEmployeePayroll(employeeId);
                ModalManager.open('payrollModal');
            }
        });

        // Refresh payroll button
        document.addEventListener('click', (event) => {
            const refreshBtn = event.target.closest('#refreshPayrollBtn');
            if (refreshBtn) {
                PayrollManager.loadAllPayrollData();
            }
        });

        // Salary update form
        const salaryForm = document.getElementById('salaryUpdateForm');
        if (salaryForm) {
            salaryForm.addEventListener('submit', (event) => {
                PayrollManager.handleSalaryUpdate(event);
            });
        }

        // Real-time salary preview
        const newSalaryInput = document.getElementById('newSalary');
        if (newSalaryInput) {
            newSalaryInput.addEventListener('input', (event) => {
                const value = parseFloat(event.target.value) || 0;
                PayrollManager.updateSalaryPreview(value);
            });
        }
    },

    /**
     * Setup logout functionality
     */
    setupLogout() {
        document.addEventListener('click', (event) => {
            const logoutBtn = event.target.closest('.btn-logout');
            if (logoutBtn) {
                const confirmed = confirm('Are you sure you want to logout?');
                if (confirmed) {
                    Utils.showLoading(true);
                    setTimeout(() => {
                        Utils.showLoading(false);
                        Utils.showNotification('Logged out successfully', 'success');

                        // Return to login screen
                        App.showLoginScreen();
                    }, 500);
                }
            }
        });
    },

    /**
     * Setup keyboard navigation
     */
    setupKeyboardNavigation() {
        // Enhanced keyboard navigation for better accessibility
        document.addEventListener('keydown', (event) => {
            // Skip if user is typing in input fields
            if (event.target.matches('input, textarea, select')) {
                return;
            }

            switch (event.key) {
                case '/':
                    event.preventDefault();
                    // Focus search if exists
                    const searchInput = document.querySelector('input[type="search"]');
                    if (searchInput) {
                        searchInput.focus();
                    }
                    break;
                    
                case '?':
                    event.preventDefault();
                    // Show keyboard shortcuts help
                    Utils.showNotification('Keyboard shortcuts:\n/ - Focus search\n? - Show help\nEsc - Close modal', 'info');
                    break;
            }
        });
    },

    /**
     * Setup responsive handlers
     */
    setupResponsiveHandlers() {
        // Handle window resize
        const handleResize = Utils.throttle(() => {
            // Close sidebar on desktop
            if (window.innerWidth > 768 && AppState.sidebarOpen) {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar) {
                    sidebar.classList.remove('active');
                    AppState.sidebarOpen = false;
                }
            }
        }, 250);

        window.addEventListener('resize', handleResize);

        // Handle orientation change
        window.addEventListener('orientationchange', () => {
            setTimeout(handleResize, 100);
        });
    }
};

// ===================================================================
// Application Initialization
// ===================================================================
const App = {
    /**
     * Initialize the application
     */
    init() {
        try {
            console.log('Initializing Employee Management System...');
            
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                this.setup();
            }
        } catch (error) {
            console.error('Error initializing application:', error);
        }
    },

    /**
     * Setup the application
     */
    async setup() {
        try {
            // Setup all event handlers (including login)
            EventHandlers.init();

            // Setup accessibility features
            this.setupAccessibility();
            
            // Show login screen by default
            this.showLoginScreen();

            console.log('Employee Management System ready - waiting for login');

        } catch (error) {
            console.error('Error setting up application:', error);
            Utils.showNotification('Error initializing application', 'error');
        }
    },

    /**
     * Show login screen and hide dashboards
     */
    showLoginScreen() {
        AppState.isAuthenticated = false;

        const loginPage = document.getElementById('loginPage');
        const dashboards = document.querySelectorAll('.dashboard');
        const roleSwitcher = document.querySelector('.role-switcher');

        if (loginPage) {
            loginPage.style.display = 'flex';
        }

        dashboards.forEach(dashboard => {
            dashboard.classList.remove('active');
        });

        if (roleSwitcher) {
            roleSwitcher.style.display = 'none';
        }
    },

    /**
     * Handle successful login
     */
    async handleLogin(role) {
        AppState.isAuthenticated = true;
        AppState.currentRole = role === 'admin' ? 'admin' : 'employee';

        const loginPage = document.getElementById('loginPage');
        const roleSwitcher = document.querySelector('.role-switcher');

        // Read name/email from form
        const nameInput = document.getElementById('loginName');
        const emailInput = document.getElementById('loginEmail');
        const name = nameInput ? nameInput.value.trim() : '';
        const email = emailInput ? emailInput.value.trim() : '';

        // Update current user info (simple front-end auth)
        AppState.currentUser.name = name || AppState.currentUser.name;
        AppState.currentUser.email = email || AppState.currentUser.email;

        // Update employee welcome text
        const welcome = document.getElementById('employeeWelcome');
        if (welcome && AppState.currentRole === 'employee') {
            welcome.textContent = `Welcome back, ${AppState.currentUser.name}!`;
        }

        if (loginPage) {
            loginPage.style.display = 'none';
        }

        if (roleSwitcher) {
            roleSwitcher.style.display = 'flex';
        }

        // Initialize role display and dashboard
        AppState.updateRoleDisplay();
        await AppState.switchDashboard(AppState.currentRole);

        // Load initial data for the selected role
        await this.loadInitialData();

        console.log(`Logged in as ${AppState.currentRole} (${AppState.currentUser.email})`);
    },
    
    /**
     * Load initial application data
     */
    async loadInitialData() {
        try {
            // Check API health
            const health = await LeaveAPI.healthCheck();
            if (health.status === 'unhealthy') {
                console.warn('API is not healthy:', health);
                // Continue with offline mode or show warning
                Utils.showNotification('API connection issue - some features may not work', 'warning');
                return;
            }
            
            // Load dashboard statistics
            const stats = await LeaveAPI.getLeaveStats();
            ModalManager.updateDashboardStats(stats);
            
            // Load leave requests for admin dashboard
            if (AppState.currentRole === 'admin') {
                await AdminManager.refreshLeaveRequests();
            }
            
            // Load activity feed
            await ModalManager.updateActivityFeed();
            
        } catch (error) {
            console.error('Error loading initial data:', error);
            // Continue without API data
        }
    },

    /**
     * Setup accessibility features
     */
    setupAccessibility() {
        // Add skip link for screen readers
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.className = 'visually-hidden';
        skipLink.textContent = 'Skip to main content';
        document.body.insertBefore(skipLink, document.body.firstChild);

        // Announce page changes to screen readers
        const announceRegion = document.createElement('div');
        announceRegion.setAttribute('aria-live', 'polite');
        announceRegion.setAttribute('aria-atomic', 'true');
        announceRegion.className = 'visually-hidden';
        announceRegion.id = 'announcements';
        document.body.appendChild(announceRegion);

        // Set initial focus management
        this.manageFocus();
    },

    /**
     * Manage focus for better keyboard navigation
     */
    manageFocus() {
        // Set initial focus on main content
        const mainContent = document.querySelector('.main-content');
        if (mainContent && !mainContent.hasAttribute('tabindex')) {
            mainContent.setAttribute('tabindex', '-1');
        }
    }
};

// ===================================================================
// Error Handling
// ===================================================================
window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    Utils.showNotification('An unexpected error occurred', 'error');
});

window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    Utils.showNotification('An unexpected error occurred', 'error');
});

// ===================================================================
// Initialize Application
// ===================================================================
App.init();

// ===================================================================
// Export for potential module usage
// ===================================================================
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        App,
        AppState,
        ModalManager,
        AdminManager,
        NavigationManager,
        Utils
    };
}