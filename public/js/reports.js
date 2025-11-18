/**
 * Reports Page Specific JavaScript
 * Handles report generation, filtering, and data visualization
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize reports page features
    initializeReportFilters();
    initializeReportActions();
    initializeReportCharts();
});

/**
 * Initialize report filters (date range, category, type)
 */
function initializeReportFilters() {
    const filterForm = document.querySelector('.filter-form');
    if (!filterForm) return;

    // Date range filter
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');
    
    if (startDate && endDate) {
        [startDate, endDate].forEach(dateInput => {
            dateInput.addEventListener('change', function() {
                generateReport();
            });
        });
    }

    // Category filter
    const categoryFilter = document.querySelector('select[name="category"]');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            generateReport();
        });
    }

    // Report type filter
    const reportType = document.querySelector('select[name="report_type"]');
    if (reportType) {
        reportType.addEventListener('change', function() {
            generateReport();
        });
    }
}

/**
 * Initialize report action buttons
 */
function initializeReportActions() {
    // Export to CSV
    const exportBtn = document.querySelector('.btn-export-csv');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            exportReportToCSV();
        });
    }

    // Print report
    const printBtn = document.querySelector('.btn-print-report');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }

    // Download as PDF
    const pdfBtn = document.querySelector('.btn-download-pdf');
    if (pdfBtn) {
        pdfBtn.addEventListener('click', function() {
            downloadReportAsPDF();
        });
    }
}

/**
 * Initialize report charts and visualizations
 */
function initializeReportCharts() {
    // Initialize any chart.js instances for reports
    const chartElement = document.querySelector('#reportChart');
    if (chartElement) {
        // Chart.js can be initialized here if needed
        console.log('Report charts initialized');
    }
}

/**
 * Generate report based on current filters
 */
function generateReport() {
    const startDate = document.querySelector('input[name="start_date"]')?.value || '';
    const endDate = document.querySelector('input[name="end_date"]')?.value || '';
    const category = document.querySelector('select[name="category"]')?.value || '';
    const reportType = document.querySelector('select[name="report_type"]')?.value || '';

    console.log('Generating report:', { startDate, endDate, category, reportType });
    
    // API call to generate report
    // fetch('/api/reports/generate', { ... })
}

/**
 * Export report to CSV format
 */
function exportReportToCSV() {
    const startDate = document.querySelector('input[name="start_date"]')?.value || '';
    const endDate = document.querySelector('input[name="end_date"]')?.value || '';
    
    console.log('Exporting to CSV:', { startDate, endDate });
    
    // Trigger CSV download
    // window.location.href = `/api/reports/export/csv?start_date=${startDate}&end_date=${endDate}`;
}

/**
 * Download report as PDF
 */
function downloadReportAsPDF() {
    const startDate = document.querySelector('input[name="start_date"]')?.value || '';
    const endDate = document.querySelector('input[name="end_date"]')?.value || '';
    
    console.log('Downloading PDF:', { startDate, endDate });
    
    // Trigger PDF download
    // window.location.href = `/api/reports/export/pdf?start_date=${startDate}&end_date=${endDate}`;
}
