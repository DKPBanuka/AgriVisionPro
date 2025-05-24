<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriVision Pro - Crop Analytics</title>
    <style>
        :root {
            --primary-color: #283b8a;
            --secondary-color: #f8f9fa;
            --accent-color: #3d7bf4;
            --text-color: #333;
            --border-color: #dee2e6;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-color);
            background-color: #f5f7fa;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: #283b8a;
            color: white;
            padding: 20px 0;
            flex-shrink: 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .logo img {
            width: 40px;
            margin-right: 10px;
        }
        
        .logo-text {
            font-size: 22px;
            font-weight: bold;
        }
        
        .menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .menu-header {
            padding: 10px 20px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }
        
        .menu-item i {
            margin-right: 10px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .search-bar {
            position: relative;
            width: 300px;
        }
        
        .search-bar input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            font-size: 14px;
        }
        
        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .notifications {
            position: relative;
            margin-right: 20px;
        }
        
        .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff4757;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        
        .page-description {
            color: #6c757d;
            margin: 5px 0 0;
        }
        
        .action-button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .action-button i {
            margin-right: 5px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-header {
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .card-subtitle {
            color: #6c757d;
            font-size: 14px;
            margin: 5px 0 0;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
        }
        
        .col-6 {
            grid-column: span 6;
        }
        
        .col-4 {
            grid-column: span 4;
        }
        
        .col-12 {
            grid-column: span 12;
        }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 8px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: white;
            min-width: 150px;
        }
        
        .date-range {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .date-input {
            padding: 8px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        
        .kpi-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .kpi-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 20px;
            text-align: center;
        }
        
        .kpi-title {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .kpi-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .kpi-change {
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .up {
            color: var(--success-color);
        }
        
        .down {
            color: var(--danger-color);
        }
        
        .chart-container {
            height: 300px;
            margin-bottom: 20px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-healthy {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .status-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .status-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .yield-prediction {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .yield-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .high {
            background-color: var(--success-color);
        }
        
        .medium {
            background-color: var(--warning-color);
        }
        
        .low {
            background-color: var(--danger-color);
        }
        
        .insights-list {
            list-style: none;
            padding: 0;
        }
        
        .insight-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .insight-item:last-child {
            border-bottom: none;
        }
        
        .insight-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(61, 123, 244, 0.1);
            color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .insight-content {
            flex: 1;
        }
        
        .insight-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .insight-description {
            color: #6c757d;
            font-size: 14px;
        }
        
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .legend-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
            margin-right: 5px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .tab.active {
            border-bottom: 2px solid var(--accent-color);
            color: var(--accent-color);
        }
        
        .weather-forecast {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        
        .weather-day {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            min-width: 120px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .weather-date {
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .weather-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .weather-temp {
            font-size: 18px;
            font-weight: 600;
        }
        
        .weather-condition {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <img src="/api/placeholder/40/40" alt="AgriVision Logo">
                <div class="logo-text">AgriVision Pro</div>
            </div>
            <ul class="menu">
                <li class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </li>
                <li class="menu-item">
                    <i class="fas fa-seedling"></i>
                    <span>Crop Management</span>
                </li>
                <li class="menu-item">
                    <i class="fas fa-horse"></i>
                    <span>Livestock</span>
                </li>
                <li class="menu-item">
                    <i class="fas fa-box"></i>
                    <span>Inventory</span>
                </li>
                <li class="menu-item">
                    <i class="fas fa-tasks"></i>
                    <span>Tasks</span>
                </li>
                <li class="menu-header">ANALYTICS</li>
                <li class="menu-item active">
                    <i class="fas fa-chart-line"></i>
                    <span>Crop Analytics</span>
                </li>
                <li class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Livestock Analytics</span>
                </li>
                <li class="menu-item">
                    <i class="fas fa-chart-pie"></i>
                    <span>Inventory Analytics</span>
                </li>
                <li class="menu-item">
                    <i class="fas fa-dollar-sign"></i>
                    <span>Financial Analytics</span>
                </li>
                <li class="menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Tasks Analytics</span>
                </li>
                <li class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </li>
            </ul>
        </div>
        <div class="main-content">
            <div class="topbar">
                <div class="hamburger-menu">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search across AgriVision">
                </div>
                <div class="user-menu">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <div class="badge">9</div>
                    </div>
                    <div class="user-avatar">
                        <img src="/api/placeholder/40/40" alt="User Avatar">
                    </div>
                </div>
            </div>
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">Crop Analytics</h1>
                    <p class="page-description">Monitor crop performance, trends, and insights</p>
                </div>
                <button class="action-button">
                    <i class="fas fa-download"></i> Export Data
                </button>
            </div>
            
            <div class="filter-bar">
                <select class="filter-select">
                    <option>All Crop Types</option>
                    <option>Grains</option>
                    <option>Vegetables</option>
                    <option>Fruits</option>
                    <option>Legumes</option>
                </select>
                <select class="filter-select">
                    <option>All Fields</option>
                    <option>North Field</option>
                    <option>South Field</option>
                    <option>East Field</option>
                    <option>West Field</option>
                </select>
                <select class="filter-select">
                    <option>Current Season</option>
                    <option>Previous Season</option>
                    <option>Year to Date</option>
                    <option>Last Year</option>
                </select>
                <div class="date-range">
                    <input type="date" class="date-input" value="2025-01-01">
                    <span>to</span>
                    <input type="date" class="date-input" value="2025-05-21">
                </div>
            </div>
            
            <div class="kpi-container">
                <div class="kpi-card">
                    <div class="kpi-title">Total Crop Area</div>
                    <div class="kpi-value">87.5 ha</div>
                    <div class="kpi-change up"><i class="fas fa-arrow-up"></i> 12% vs Previous Season</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Avg Crop Health</div>
                    <div class="kpi-value">86%</div>
                    <div class="kpi-change up"><i class="fas fa-arrow-up"></i> 3% vs Previous Season</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Est. Yield</div>
                    <div class="kpi-value">6.8 t/ha</div>
                    <div class="kpi-change up"><i class="fas fa-arrow-up"></i> 5% vs Previous Season</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Projected Revenue</div>
                    <div class="kpi-value">$412,500</div>
                    <div class="kpi-change up"><i class="fas fa-arrow-up"></i> 8% vs Previous Season</div>
                </div>
            </div>
            
            <div class="analytics-grid">
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Crop Performance Trends</h2>
                            <p class="card-subtitle">Growth metrics over time</p>
                        </div>
                        <div class="tabs">
                            <div class="tab active">Growth Rate</div>
                            <div class="tab">Yield Projection</div>
                            <div class="tab">Health Index</div>
                        </div>
                        <div class="chart-container">
                            <img src="/api/placeholder/600/300" alt="Crop Performance Line Chart">
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #4CAF50;"></div>
                                <span>Wheat</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #2196F3;"></div>
                                <span>Corn</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #FF9800;"></div>
                                <span>Soybeans</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Crop Distribution Analysis</h2>
                            <p class="card-subtitle">Area allocation by crop type and health status</p>
                        </div>
                        <div class="chart-container">
                            <img src="/api/placeholder/600/300" alt="Crop Distribution Chart">
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #4CAF50;"></div>
                                <span>Healthy</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #FFC107;"></div>
                                <span>Needs Attention</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #F44336;"></div>
                                <span>Critical</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Environmental Factors Impact</h2>
                            <p class="card-subtitle">Correlation between weather conditions and crop performance</p>
                        </div>
                        <div class="chart-container">
                            <img src="/api/placeholder/1200/300" alt="Environmental Impact Chart">
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Weather Forecast</h2>
                            <p class="card-subtitle">Next 5 days prediction</p>
                        </div>
                        <div class="weather-forecast">
                            <div class="weather-day">
                                <div class="weather-date">Today</div>
                                <div class="weather-icon"><i class="fas fa-sun"></i></div>
                                <div class="weather-temp">28°C</div>
                                <div class="weather-condition">Sunny</div>
                            </div>
                            <div class="weather-day">
                                <div class="weather-date">Thu, 22</div>
                                <div class="weather-icon"><i class="fas fa-cloud-sun"></i></div>
                                <div class="weather-temp">26°C</div>
                                <div class="weather-condition">Partly Cloudy</div>
                            </div>
                            <div class="weather-day">
                                <div class="weather-date">Fri, 23</div>
                                <div class="weather-icon"><i class="fas fa-cloud-rain"></i></div>
                                <div class="weather-temp">22°C</div>
                                <div class="weather-condition">Light Rain</div>
                            </div>
                            <div class="weather-day">
                                <div class="weather-date">Sat, 24</div>
                                <div class="weather-icon"><i class="fas fa-cloud-rain"></i></div>
                                <div class="weather-temp">21°C</div>
                                <div class="weather-condition">Rain</div>
                            </div>
                            <div class="weather-day">
                                <div class="weather-date">Sun, 25</div>
                                <div class="weather-icon"><i class="fas fa-cloud-sun"></i></div>
                                <div class="weather-temp">24°C</div>
                                <div class="weather-condition">Partly Cloudy</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-8">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">AI-Powered Insights</h2>
                            <p class="card-subtitle">Personalized recommendations for your crops</p>
                        </div>
                        <ul class="insights-list">
                            <li class="insight-item">
                                <div class="insight-icon">
                                    <i class="fas fa-water"></i>
                                </div>
                                <div class="insight-content">
                                    <div class="insight-title">Irrigation Recommendation</div>
                                    <div class="insight-description">Based on soil moisture levels and weather forecast, increase irrigation in the North Field by 15% for the next 3 days.</div>
                                </div>
                            </li>
                            <li class="insight-item">
                                <div class="insight-icon">
                                    <i class="fas fa-bug"></i>
                                </div>
                                <div class="insight-content">
                                    <div class="insight-title">Pest Alert</div>
                                    <div class="insight-description">Early signs of aphid infestation detected in your soybean crops. Consider preventative treatment within the next 48 hours.</div>
                                </div>
                            </li>
                            <li class="insight-item">
                                <div class="insight-icon">
                                    <i class="fas fa-leaf"></i>
                                </div>
                                <div class="insight-content">
                                    <div class="insight-title">Nutrient Optimization</div>
                                    <div class="insight-description">Your corn crops show signs of nitrogen deficiency. Apply recommended fertilizer to boost growth and yield potential.</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Detailed Crop Performance</h2>
                            <p class="card-subtitle">Field-by-field breakdown of crop health and yield estimates</p>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Crop Type</th>
                                        <th>Field</th>
                                        <th>Area (ha)</th>
                                        <th>Planting Date</th>
                                        <th>Growth Stage</th>
                                        <th>Health Status</th>
                                        <th>Est. Yield</th>
                                        <th>Yield vs Avg</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Wheat</td>
                                        <td>North Field</td>
                                        <td>32.5</td>
                                        <td>Oct 15, 2024</td>
                                        <td>Flowering</td>
                                        <td><span class="status status-healthy">Healthy</span></td>
                                        <td>7.2 t/ha</td>
                                        <td>
                                            <div class="yield-prediction">
                                                <div class="yield-indicator high"></div>
                                                <span>+8%</span>
                                            </div>
                                        </td>
                                        <td><i class="fas fa-ellipsis-v"></i></td>
                                    </tr>
                                    <tr>
                                        <td>Corn</td>
                                        <td>South Field</td>
                                        <td>27.3</td>
                                        <td>Mar 10, 2025</td>
                                        <td>V6</td>
                                        <td><span class="status status-warning">Needs Attention</span></td>
                                        <td>9.1 t/ha</td>
                                        <td>
                                            <div class="yield-prediction">
                                                <div class="yield-indicator medium"></div>
                                                <span>+2%</span>
                                            </div>
                                        </td>
                                        <td><i class="fas fa-ellipsis-v"></i></td>
                                    </tr>
                                    <tr>
                                        <td>Soybeans</td>
                                        <td>East Field</td>
                                        <td>15.8</td>
                                        <td>Apr 5, 2025</td>
                                        <td>V3</td>
                                        <td><span class="status status-warning">Needs Attention</span></td>
                                        <td>3.4 t/ha</td>
                                        <td>
                                            <div class="yield-prediction">
                                                <div class="yield-indicator medium"></div>
                                                <span>+1%</span>
                                            </div>
                                        </td>
                                        <td><i class="fas fa-ellipsis-v"></i></td>
                                    </tr>
                                    <tr>
                                        <td>Canola</td>
                                        <td>West Field</td>
                                        <td>11.9</td>
                                        <td>Sept 20, 2024</td>
                                        <td>Flowering</td>
                                        <td><span class="status status-danger">Critical</span></td>
                                        <td>2.1 t/ha</td>
                                        <td>
                                            <div class="yield-prediction">
                                                <div class="yield-indicator low"></div>
                                                <span>-12%</span>
                                            </div>
                                        </td>
                                        <td><i class="fas fa-ellipsis-v"></i></td>
                                    </tr>
                                    <tr>
                                        <td>Potatoes</td>
                                        <td>South-East Plot</td>
                                        <td>8.4</td>
                                        <td>Mar 25, 2025</td>
                                        <td>Vegetative</td>
                                        <td><span class="status status-healthy">Healthy</span></td>
                                        <td>38.5 t/ha</td>
                                        <td>
                                            <div class="yield-prediction">
                                                <div class="yield-indicator high"></div>
                                                <span>+15%</span>
                                            </div>
                                        </td>
                                        <td><i class="fas fa-ellipsis-v"></i></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Resource Utilization</h2>
                            <p class="card-subtitle">Efficiency analysis of inputs vs yield</p>
                        </div>
                        <div class="chart-container">
                            <img src="/api/placeholder/600/300" alt="Resource Utilization Chart">
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #4CAF50;"></div>
                                <span>Water Usage</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #2196F3;"></div>
                                <span>Fertilizer</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #FF9800;"></div>
                                <span>Pesticides</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Comparative Analysis</h2>
                            <p class="card-subtitle">Your performance vs regional benchmarks</p>
                        </div>
                        <div class="chart-container">
                            <img src="/api/placeholder/600/300" alt="Comparative Analysis Chart">
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #283b8a;"></div>
                                <span>Your Farm</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #aaaaaa;"></div>
                                <span>Regional Average</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Satellite & Drone Imagery</h2>
                            <p class="card-subtitle">Visual crop health monitoring</p>
                        </div>
                        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                            <div style="flex: 1;">
                                <h3 style="font-size: 16px; margin-bottom: 10px;">NDVI (Vegetation Health)</h3>
                                <img src="/api/placeholder/550/300" alt="NDVI Map">
                            </div>
                            <div style="flex: 1;">
                                <h3 style="font-size: 16px; margin-bottom: 10px;">Moisture Analysis</h3>
                                <img src="/api/placeholder/550/300" alt="Moisture Map">
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <span style="margin-right: 15px;"><span style="display: inline-block; width: 15px; height: 15px; background-color: #ff0000; margin-right: 5px;"></span> Low</span>
                                <span style="margin-right: 15px;"><span style="display: inline-block; width: 15px; height: 15px; background-color: #ffff00; margin-right: 5px;"></span> Medium</span>
                                <span><span style="display: inline-block; width: 15px; height: 15px; background-color: #00ff00; margin-right: 5px;"></span> High</span>
                            </div>
                            <div>
                                <select style="padding: 5px 10px; border-radius: 4px; border: 1px solid #ddd;">
                                    <option>Latest (May 19, 2025)</option>
                                    <option>May 12, 2025</option>
                                    <option>May 5, 2025</option>
                                    <option>April 28, 2025</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Profit Projection & ROI Analysis</h2>
                            <p class="card-subtitle">Financial performance by crop type</p>
                        </div>
                        <div class="chart-container">
                            <img src="/api/placeholder/1200/300" alt="Profit Projection Chart">
                        </div>
                        <div class="table-container" style="margin-top: 20px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Crop Type</th>
                                        <th>Total Area (ha)</th>
                                        <th>Est. Yield (t/ha)</th>
                                        <th>Current Market Price ($/t)</th>
                                        <th>Production Cost ($/ha)</th>
                                        <th>Gross Revenue ($)</th>
                                        <th>Gross Profit ($)</th>
                                        <th>ROI (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Wheat</td>
                                        <td>32.5</td>
                                        <td>7.2</td>
                                        <td>258</td>
                                        <td>1,250</td>
                                        <td>60,372</td>
                                        <td>19,747</td>
                                        <td>48.6%</td>
                                    </tr>
                                    <tr>
                                        <td>Corn</td>
                                        <td>27.3</td>
                                        <td>9.1</td>
                                        <td>195</td>
                                        <td>1,450</td>
                                        <td>48,432</td>
                                        <td>8,853</td>
                                        <td>22.4%</td>
                                    </tr>
                                    <tr>
                                        <td>Soybeans</td>
                                        <td>15.8</td>
                                        <td>3.4</td>
                                        <td>450</td>
                                        <td>1,100</td>
                                        <td>24,138</td>
                                        <td>6,758</td>
                                        <td>38.9%</td>
                                    </tr>
                                    <tr>
                                        <td>Canola</td>
                                        <td>11.9</td>
                                        <td>2.1</td>
                                        <td>520</td>
                                        <td>1,320</td>
                                        <td>13,016</td>
                                        <td>2,304</td>
                                        <td>21.4%</td>
                                    </tr>
                                    <tr>
                                        <td>Potatoes</td>
                                        <td>8.4</td>
                                        <td>38.5</td>
                                        <td>215</td>
                                        <td>3,850</td>
                                        <td>69,521</td>
                                        <td>37,181</td>
                                        <td>114.6%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <div class="card-header">
                    <h2 class="card-title">Seasonal Planning Tools</h2>
                    <p class="card-subtitle">Optimize next season's crop allocations based on analytics</p>
                </div>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <h3 style="font-size: 16px; margin-bottom: 10px;">Recommended Crop Rotation</h3>
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <p style="margin-bottom: 10px;"><strong>North Field:</strong> Wheat → <span style="color: #3d7bf4;">Soybeans</span> (Next Season)</p>
                            <p style="margin-bottom: 10px;"><strong>South Field:</strong> Corn → <span style="color: #3d7bf4;">Wheat</span> (Next Season)</p>
                            <p style="margin-bottom: 10px;"><strong>East Field:</strong> Soybeans → <span style="color: #3d7bf4;">Corn</span> (Next Season)</p>
                            <p style="margin-bottom: 0;"><strong>West Field:</strong> Canola → <span style="color: #3d7bf4;">Oats</span> (Next Season)</p>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 300px;">
                        <h3 style="font-size: 16px; margin-bottom: 10px;">Profit Maximization Model</h3>
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <p style="margin-bottom: 5px;">Based on current market trends and historical performance:</p>
                            <ul style="margin-top: 10px; padding-left: 20px;">
                                <li>Increase potato cultivation by 15%</li>
                                <li>Maintain current wheat allocation</li>
                                <li>Reduce corn cultivation by 10%</li>
                                <li>Consider adding specialty crops like quinoa (5 ha)</li>
                            </ul>
                            <button style="background-color: #3d7bf4; color: white; border: none; padding: 8px 15px; border-radius: 4px; margin-top: 10px; font-weight: 500; cursor: pointer;">Run Simulation</button>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 300px;">
                        <h3 style="font-size: 16px; margin-bottom: 10px;">Sustainability Score</h3>
                        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 48px; font-weight: bold; color: #4CAF50; margin-bottom: 10px;">78/100</div>
                            <div style="height: 8px; background-color: #e0e0e0; border-radius: 4px; margin-bottom: 15px;">
                                <div style="height: 100%; width: 78%; background-color: #4CAF50; border-radius: 4px;"></div>
                            </div>
                            <p style="font-size: 14px; color: #666; margin-bottom: 5px;">Your farm's sustainability practices are above regional average.</p>
                            <p style="font-size: 14px; color: #666; margin: 0;">Tip: Consider cover crops to improve soil health further.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>