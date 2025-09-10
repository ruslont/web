// Admin panel JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js initialization
    initializeCharts();
    
    // Table functionality
    initializeTables();
    
    // Form handling
    initializeForms();
    
    // Dashboard widgets
    initializeDashboard();
});

// Charts initialization
function initializeCharts() {
    const salesChart = document.getElementById('salesChart');
    const ordersChart = document.getElementById('ordersChart');
    
    if (salesChart) {
        // Sales chart implementation
        new Chart(salesChart, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#c8a97e',
                    tension: 0.1
                }]
            }
        });
    }
    
    if (ordersChart) {
        // Orders chart implementation
        new Chart(ordersChart, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending', 'Cancelled'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: ['#4caf50', '#ff9800', '#f44336']
                }]
            }
        });
    }
}

// Tables functionality
function initializeTables() {
    // DataTables initialization
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        // Simple sorting functionality
        const headers = table.querySelectorAll('th[data-sort]');
        
        headers.forEach(header => {
            header.addEventListener('click', () => {
                const column = header.dataset.sort;
                const direction = header.dataset.sortDirection || 'asc';
                
                // Sort table logic here
                sortTable(table, column, direction);
                
                // Toggle direction
                header.dataset.sortDirection = direction === 'asc' ? 'desc' : 'asc';
            });
        });
    });
}

// Form handling
function initializeForms() {
    // Image preview
    const imageInputs = document.querySelectorAll('input[type="file"][data-preview]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const preview = document.getElementById(this.dataset.preview);
            if (preview && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    
    // Rich text editors
    const textEditors = document.querySelectorAll('.rich-text-editor');
    textEditors.forEach(editor => {
        // Simple rich text functionality
        editor.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
}

// Dashboard widgets
function initializeDashboard() {
    // Real-time updates
    if (typeof EventSource !== 'undefined') {
        const eventSource = new EventSource('api/dashboard-events.php');
        
        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            updateDashboard(data);
        };
        
        eventSource.onerror = function() {
            console.error('EventSource failed.');
        };
    }
    
    // Statistics cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('click', function() {
            this.classList.toggle('expanded');
        });
    });
}

// Utility functions
function showAdminNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `admin-notification ${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Export functionality
function exportTable(tableId, format = 'csv') {
    const table = document.getElementById(tableId);
    let data = [];
    
    // Get headers
    const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent);
    data.push(headers);
    
    // Get rows
    table.querySelectorAll('tbody tr').forEach(row => {
        const rowData = Array.from(row.querySelectorAll('td')).map(td => td.textContent);
        data.push(rowData);
    });
    
    // Create download
    let content, mimeType, extension;
    
    if (format === 'csv') {
        content = data.map(row => row.join(',')).join('\n');
        mimeType = 'text/csv';
        extension = 'csv';
    } else {
        content = JSON.stringify(data);
        mimeType = 'application/json';
        extension = 'json';
    }
    
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `export-${new Date().toISOString().slice(0,10)}.${extension}`;
    a.click();
    URL.revokeObjectURL(url);
}

// Modal functionality
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});
