/**
 * Chart Utilities Module - Handles all chart creation and management
 */

class ChartUtils {
    constructor() {
        this.chartInstances = new Map();
    }

    // Destroy existing chart if it exists
    destroyChart(chartId) {
        if (this.chartInstances.has(chartId)) {
            this.chartInstances.get(chartId).destroy();
            this.chartInstances.delete(chartId);
        }
    }

    // Create timeline chart for crops
    createCropTimelineChart(canvasId, data) {
        this.destroyChart(canvasId);
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Duration (Days)',
                    data: data.data,
                    backgroundColor: this.getColorPalette('timeline'),
                    borderColor: this.getBorderColorPalette('timeline'),
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.parsed.y} days`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Duration (Days)',
                            color: '#4a5568'
                        },
                        ticks: { color: '#718096' },
                        grid: { color: 'rgba(0,0,0,0.1)' }
                    },
                    x: {
                        ticks: { color: '#718096' },
                        grid: { display: false }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });

        this.chartInstances.set(canvasId, chart);
        return chart;
    }

    // Create water requirement chart for crops
    createCropWaterChart(canvasId, data) {
        this.destroyChart(canvasId);
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Water Requirement',
                    data: data.data,
                    borderColor: '#38a169',
                    backgroundColor: 'rgba(56, 161, 105, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#38a169',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.parsed.y} mm`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Water (mm)',
                            color: '#4a5568'
                        },
                        ticks: { color: '#718096' },
                        grid: { color: 'rgba(0,0,0,0.1)' }
                    },
                    x: {
                        ticks: { color: '#718096' },
                        grid: { display: false }
                    }
                },
                animation: {
                    duration: 1200,
                    easing: 'easeOutQuart'
                }
            }
        });

        this.chartInstances.set(canvasId, chart);
        return chart;
    }

    // Create livestock production charts
    createLivestockChart(canvasId, livestockData) {
        this.destroyChart(canvasId);
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const chartData = livestockData.chartData;
        let chart;

        switch (chartData.type) {
            case 'radar':
                chart = this.createRadarChart(ctx, chartData);
                break;
            case 'line':
                chart = this.createLineChart(ctx, chartData);
                break;
            case 'pie':
                chart = this.createPieChart(ctx, chartData);
                break;
            case 'bar':
                chart = this.createBarChart(ctx, chartData);
                break;
            case 'doughnut':
                chart = this.createDoughnutChart(ctx, chartData);
                break;
            default:
                chart = this.createRadarChart(ctx, chartData);
        }

        this.chartInstances.set(canvasId, chart);
        return chart;
    }

    // Create radar chart
    createRadarChart(ctx, data) {
        return new Chart(ctx, {
            type: 'radar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Performance Metrics',
                    data: data.data,
                    backgroundColor: 'rgba(56, 161, 105, 0.2)',
                    borderColor: '#38a169',
                    pointBackgroundColor: '#38a169',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        pointLabels: { color: '#4a5568' },
                        angleLines: { color: '#cbd5e0' },
                        grid: { color: '#cbd5e0' },
                        ticks: { color: '#718096' }
                    }
                }
            }
        });
    }

    // Create line chart
    createLineChart(ctx, data) {
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Production Rate',
                    data: data.data,
                    borderColor: '#dd6b20',
                    backgroundColor: 'rgba(221, 107, 32, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#dd6b20',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Production (%)', color: '#4a5568' },
                        ticks: { color: '#718096' }
                    },
                    x: { ticks: { color: '#718096' } }
                }
            }
        });
    }

    // Create pie chart
    createPieChart(ctx, data) {
        return new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.data,
                    backgroundColor: ['#38a169', '#dd6b20', '#4299e1', '#805ad5'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#4a5568' }
                    }
                }
            }
        });
    }

    // Create bar chart
    createBarChart(ctx, data) {
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Weight Gain (g)',
                    data: data.data,
                    backgroundColor: ['#dd6b20', '#ed8936', '#f6ad55'],
                    borderColor: '#fff',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Weight Gain (g)', color: '#4a5568' },
                        ticks: { color: '#718096' }
                    },
                    x: { ticks: { color: '#718096' } }
                }
            }
        });
    }

    // Create doughnut chart
    createDoughnutChart(ctx, data) {
        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.data,
                    backgroundColor: ['#38a169', '#dd6b20', '#805ad5'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#4a5568' }
                    }
                }
            }
        });
    }

    // Create market price trend chart using ApexCharts
    createPriceTrendChart(elementId, marketData) {
        const element = document.getElementById(elementId);
        if (!element || typeof ApexCharts === 'undefined') return;

        const options = {
            series: [
                {
                    name: "Rice (Nadu)",
                    data: marketData.priceHistory.rice
                },
                {
                    name: "Tomatoes",
                    data: marketData.priceHistory.tomatoes
                },
                {
                    name: "Chicken (Live)",
                    data: marketData.priceHistory.chicken
                },
                {
                    name: "Eggs",
                    data: marketData.priceHistory.eggs
                },
                {
                    name: "Milk (Fresh)",
                    data: marketData.priceHistory.milk
                }
            ],
            chart: {
                height: '100%',
                type: 'line',
                zoom: { enabled: false },
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 1000
                }
            },
            dataLabels: { enabled: false },
            stroke: { 
                curve: 'smooth', 
                width: 3,
                lineCap: 'round'
            },
            title: { 
                text: 'Price Trends (Last 30 Days)', 
                align: 'left',
                style: { 
                    color: '#4a5568',
                    fontSize: '16px',
                    fontWeight: 'bold'
                }
            },
            grid: { 
                row: { 
                    colors: ['#f7fafc', 'transparent'], 
                    opacity: 0.5 
                },
                borderColor: '#e2e8f0'
            },
            xaxis: {
                categories: Array.from({length: 30}, (_, i) => `Day ${i + 1}`),
                title: { 
                    text: 'Days', 
                    style: { color: '#4a5568' }
                },
                labels: { 
                    style: { colors: '#718096' }
                }
            },
            yaxis: {
                title: { 
                    text: 'Price (LKR)', 
                    style: { color: '#4a5568' }
                },
                labels: { 
                    style: { colors: '#718096' }
                }
            },
            colors: ['#38a169', '#e53e3e', '#dd6b20', '#3490dc', '#f6993f'],
            legend: { 
                labels: { colors: '#4a5568' },
                position: 'bottom'
            },
            tooltip: {
                theme: 'light',
                style: {
                    fontSize: '12px'
                },
                y: {
                    formatter: function (val) {
                        return "LKR " + val.toFixed(2)
                    }
                }
            }
        };

        // Clear previous chart if exists
        if (element.dataset.chart) {
            ApexCharts.exec(element.dataset.chart, 'destroy');
        }

        const chart = new ApexCharts(element, options);
        chart.render();
        element.dataset.chart = chart.id;

        return chart;
    }

    // Helper methods for color palettes
    getColorPalette(type) {
        const palettes = {
            timeline: [
                'rgba(52, 211, 164, 0.8)',
                'rgba(110, 231, 183, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(52, 211, 164, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(20, 184, 166, 0.8)'
            ],
            water: ['rgba(56, 161, 105, 0.8)'],
            livestock: ['rgba(56, 161, 105, 0.8)', 'rgba(221, 107, 32, 0.8)', 'rgba(66, 153, 225, 0.8)']
        };
        return palettes[type] || palettes.timeline;
    }

    getBorderColorPalette(type) {
        const palettes = {
            timeline: [
                'rgba(52, 211, 164, 1)',
                'rgba(110, 231, 183, 1)',
                'rgba(16, 185, 129, 1)',
                'rgba(52, 211, 164, 1)',
                'rgba(16, 185, 129, 1)',
                'rgba(20, 184, 166, 1)'
            ],
            water: ['rgba(56, 161, 105, 1)'],
            livestock: ['rgba(56, 161, 105, 1)', 'rgba(221, 107, 32, 1)', 'rgba(66, 153, 225, 1)']
        };
        return palettes[type] || palettes.timeline;
    }

    // Destroy all charts
    destroyAllCharts() {
        this.chartInstances.forEach(chart => {
            chart.destroy();
        });
        this.chartInstances.clear();
    }

    // Get chart instance
    getChart(chartId) {
        return this.chartInstances.get(chartId);
    }

    // Update chart data
    updateChartData(chartId, newData) {
        const chart = this.chartInstances.get(chartId);
        if (chart) {
            chart.data = newData;
            chart.update();
        }
    }
}