<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth_functions.php';
checkAuthentication();

// Get user details from session and database
$current_user = [
    'name' => $_SESSION['full_name'] ?? 'Unknown User',
    'email' => $_SESSION['username'] ?? 'No email',
    'role' => $_SESSION['role'] ?? 'Unknown Role',
    'initials' => getInitials($_SESSION['full_name'] ?? 'UU'),
    'profile_picture' => ''
];

// Try to get profile data from database
try {
    $stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($profile) {
        // Update with profile data if available
        $current_user['name'] = $profile['full_name'] ?? $current_user['name'];
        $current_user['email'] = $profile['email'] ?? $current_user['email'];
        $current_user['profile_picture'] = $profile['profile_picture'] ?? '';
    }
} catch (PDOException $e) {
    error_log("Error fetching profile: " . $e->getMessage());
    // Continue with session data if there's an error
}

// Helper function to get initials from name
function getInitials($name) {
    $names = explode(' ', $name);
    $initials = '';
    foreach ($names as $n) {
        $initials .= strtoupper(substr($n, 0, 1));
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: 'UU';
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriVision Pro | Financial Analytics</title>
    <link rel="icon" href="./../images/logo.png" type="image/png">
    <link href="./../dist/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .sidebar-enter {
            transform: translateX(-100%);
        }
        .sidebar-enter-active {
            transform: translateX(0);
            transition: transform 300ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal {
            z-index: 50;
        }
        .status-positive {
            color: #10B981;
            background-color: #ECFDF5;
        }
        .status-negative {
            color: #EF4444;
            background-color: #FEE2E2;
        }
        .status-neutral {
            color: #F97316;
            background-color: #FFF7ED;
        }
        .search-results {
            position: absolute;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .search-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
        }
        .search-item:hover {
            background-color: #f9fafb;
        }
        .user-profile-dropdown {
            min-width: 200px;
        }
        .finance-card {
            transition: all 0.3s ease;
        }
        .finance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        /* Timeline styling */
        .timeline-progress {
            transition: width 0.6s ease;
        }
        /* Activity items */
        .activity-item {
            transition: all 0.2s ease;
        }
        .activity-item:hover {
            transform: translateX(2px);
        }
        /* Form labels */
        .required-field::after {
            content: '*';
            color: #ef4444;
            margin-left: 0.25rem;
        }
        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        /* Modal scrollable area */
        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }
        .max-h-[calc(100vh-200px)] {
            max-height: calc(100vh - 200px);
        }
        /* Sticky header and footer */
        .sticky {
            position: sticky;
        }
        .top-0 {
            top: 0;
        }
        .bottom-0 {
            bottom: 0;
        }
        /* Smooth scrolling */
        .overflow-y-auto {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }
        /* Better scrollbar */
        .overflow-y-auto::-webkit-scrollbar {
            width: 8px;
        }
        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Analytics Card Styles */
        .analytics-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Trend indicators */
        .trend-up {
            color: #10B981;
        }
        
        .trend-down {
            color: #EF4444;
        }
        
        .trend-neutral {
            color: #6B7280;
        }
        
        /* Custom scrollbar for data tables */
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        
        /* Filter dropdowns */
        .filter-dropdown {
            transition: all 0.2s ease;
        }
        
        .filter-dropdown:hover {
            background-color: #f3f4f6;
        }
        
        @media (min-width: 640px) {
            .sm\:max-w-2xl {
                max-width: 40rem;
            }
        }
        @media (min-width: 768px) {
            .md\:max-w-2xl {
                max-width: 50rem;
            }
        }
        @media (min-width: 1024px) {
            .lg\:max-w-2xl {
                max-width: 60rem;
            }
        }
        .nav-item {
            position: relative;
        }
        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #3B82F6;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body class="h-full overflow-hidden">
    <!-- App Container -->
    <div class="flex h-full">
        <!-- Dynamic Sidebar -->
        <aside id="sidebar" class="w-64 bg-gradient-to-b from-blue-900 to-blue-800 text-white shadow-xl">
            <div class="p-4 flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center">
                    <img src="./../images/logo.png" alt="App Logo" class="h-10 w-10 object-contain">
                </div>
                <h1 class="text-xl font-bold">AgriVision Pro</h1>
            </div>
            
            <nav class="mt-8">
                <div class="px-4 space-y-1">
                    <a href="/AgriVisionPro/index.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>
                    <a href="/AgriVisionPro/crops.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        Crop Management
                    </a>
                    <a href="/AgriVisionPro/livestock.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Livestock
                    </a>
                    <a href="/AgriVisionPro/inventory.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Inventory
                    </a>
                    <a href="/AgriVisionPro/tasks.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Tasks
                    </a>
                    <a href="/AgriVisionPro/analytics.php" class="flex items-center px-4 py-3 rounded-lg bg-blue-500 bg-opacity-30 text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Analytics
                    </a>
                </div>
                
                <div class="mt-8 pt-8 border-t border-blue-700">
                    <div class="px-4 space-y-1">
                        <a href="/AgriVisionPro/settings.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                            <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c-.94 1.543.826 3.31 2.37 2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Settings
                        </a>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm z-10">
                <div class="flex items-center justify-between px-6 py-3">
                    <div class="flex items-center">
                        <button id="sidebar-toggle" class="mr-4 text-gray-500 hover:text-gray-600 focus:outline-none" title="Toggle Sidebar">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div class="relative max-w-md w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input id="search-input" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search financial data..." type="search">
                            <div id="search-results" class="search-results hidden"></div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <button class="p-1 text-gray-400 hover:text-gray-500 focus:outline-none" title="Notifications">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>
                        
                        <div class="relative">
                            <button id="user-menu" class="flex items-center space-x-2 focus:outline-none">
                                <?php if (!empty($current_user['profile_picture'])): ?>
                                    <img src="<?= htmlspecialchars($current_user['profile_picture']) ?>" 
                                        alt="Profile Picture" 
                                        class="h-8 w-8 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                                        <?= $current_user['initials'] ?>
                                    </div>
                                <?php endif; ?>
                                <svg class="h-5 w-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            
                            <div id="user-menu-dropdown" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1 z-50 user-profile-dropdown">
                                <div class="px-4 py-3 border-b">
                                    <?php if (!empty($current_user['profile_picture'])): ?>
                                        <img src="<?= htmlspecialchars($current_user['profile_picture']) ?>" 
                                            alt="Profile Picture" 
                                            class="h-10 w-10 rounded-full object-cover mb-2 mx-auto">
                                    <?php endif; ?>
                                    <div class="text-center">
                                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($current_user['name']) ?></p>
                                        <p class="text-xs text-gray-800 truncate"><?= htmlspecialchars($current_user['email']) ?></p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                <?= htmlspecialchars($current_user['role']) ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <a href="/AgriVisionPro/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user-circle mr-2"></i> Your Profile
                                </a>
                                <a href="/AgriVisionPro/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-2"></i> Settings
                                </a>
                                <a href="/AgriVisionPro/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Sign out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                
            </header>

             <!-- Main Navigation -->
            <nav class=" md:flex space-x-6">
                <a href="/AgriVisionPro/analytics.php" 
                    class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                        <i class="fas fa-home mr-2 transition-all duration-300 group-hover:text-green-600"></i>
                        <span class="relative">Home
                            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-600 transition-all duration-300 group-hover:w-full"></span>
                        </span>
                        <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-green-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="tasks-analytics.php" 
                class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300 ">
                    <i class="fas fa-tasks mr-2 transition-all duration-300 group-hover:text-blue-600"></i>
                    <span class="relative">Task Analytics
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-blue-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="crop-analytics.php" 
                class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                    <i class="fas fa-leaf mr-2 transition-all duration-300 group-hover:text-green-600"></i>
                    <span class="relative">Crop Analytics
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-600 transition-all duration-300 group-hover:w-full"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-green-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="livestock-analytics.php" 
                class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                    <i class="fas fa-dog mr-2 transition-all duration-300 group-hover:text-indigo-600"></i>
                    <span class="relative">Livestock Analytics
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-indigo-600 transition-all duration-300 group-hover:w-full"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-indigo-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="inventory-analytics.php" 
                class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                    <i class="fas fa-boxes mr-2 transition-all duration-300 group-hover:text-purple-600"></i>
                    <span class="relative">Inventory Analytics
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-purple-600 transition-all duration-300 group-hover:w-full"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-purple-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="financial-analytics.php" 
                    class="nav-item active relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                        <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                        <span class="relative text-blue-600">Financial Analytics
                            <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600"></span>
                        </span>
                        <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-blue-400 via-blue-600 to-blue-400"></span>
                </a>
            </nav>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Financial Analytics</h2>
                        <p class="text-sm text-gray-500">Comprehensive financial insights and performance metrics for your farm</p>
                    </div>
                    <div class="flex space-x-2">
                        <select id="time-period" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                            <option value="365">Last Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                        <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-download mr-2"></i> Export
                        </button>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                    <!-- Total Revenue -->
                    <div class="analytics-card bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <i class="fas fa-dollar-sign text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="total-revenue-count">$24,580</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="flex items-center mr-2">
                                        <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                                        <span class="text-green-500">15%</span>
                                    </span>
                                    <span>vs last period</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Expenses -->
                    <div class="analytics-card bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                    <i class="fas fa-receipt text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Expenses</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="total-expenses-count">$18,420</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="flex items-center mr-2">
                                        <i class="fas fa-arrow-up text-red-500 mr-1"></i>
                                        <span class="text-red-500">8%</span>
                                    </span>
                                    <span>vs last period</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Net Profit -->
                    <div class="analytics-card bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i class="fas fa-chart-line text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Net Profit</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="net-profit-count">$6,160</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="flex items-center mr-2">
                                        <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                                        <span class="text-green-500">22%</span>
                                    </span>
                                    <span>vs last period</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profit Margin -->
                    <div class="analytics-card bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                    <i class="fas fa-percent text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Profit Margin</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="profit-margin-count">25%</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="flex items-center mr-2">
                                        <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                                        <span class="text-green-500">3.5%</span>
                                    </span>
                                    <span>vs last period</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row 1 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Revenue vs Expenses -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Revenue vs Expenses</h3>
                            <p class="mt-1 text-sm text-gray-500">Monthly comparison of income and costs</p>
                        </div>
                        <div class="p-4">
                            <canvas id="revenueExpensesChart" class="w-full h-64"></canvas>
                        </div>
                    </div>
                    
                    <!-- Profit Trends -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Profit Trends</h3>
                            <p class="mt-1 text-sm text-gray-500">Monthly net profit over time</p>
                        </div>
                        <div class="p-4">
                            <canvas id="profitTrendsChart" class="w-full h-64"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row 2 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Expense Breakdown -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Expense Breakdown</h3>
                            <p class="mt-1 text-sm text-gray-500">Distribution of farm expenses by category</p>
                        </div>
                        <div class="p-4">
                            <canvas id="expenseBreakdownChart" class="w-full h-64"></canvas>
                        </div>
                    </div>
                    
                    <!-- Revenue Sources -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Revenue Sources</h3>
                            <p class="mt-1 text-sm text-gray-500">Distribution of farm income by source</p>
                        </div>
                        <div class="p-4">
                            <canvas id="revenueSourcesChart" class="w-full h-64"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Detailed Tables -->
                <div class="grid grid-cols-1 gap-6">
                    <!-- Top Revenue Products -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Top Revenue Products</h3>
                            <p class="mt-1 text-sm text-gray-500">Highest earning products and their performance</p>
                        </div>
                        <div class="p-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity Sold</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Price</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trend</th>
                                    </tr>
                                </thead>
                                <tbody id="revenue-products-table-body" class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            <i class="fas fa-spinner fa-spin mr-2"></i> Loading data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Expense Analysis -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Expense Analysis</h3>
                            <p class="mt-1 text-sm text-gray-500">Detailed breakdown of farm expenses</p>
                        </div>
                        <div class="p-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% of Total</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Period</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                                    </tr>
                                </thead>
                                <tbody id="expense-analysis-table-body" class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            <i class="fas fa-spinner fa-spin mr-2"></i> Loading data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Financial KPIs -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Financial KPIs</h3>
                            <p class="mt-1 text-sm text-gray-500">Key performance indicators for your farm</p>
                        </div>
                        <div class="p-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KPI</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Value</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trend</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="kpi-table-body" class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            <i class="fas fa-spinner fa-spin mr-2"></i> Loading data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            // Setup UI interactions
            setupUI();
            
            // Load initial data
            loadSummaryData();
            initCharts();
            loadTableData();
        });

        // Setup UI interactions
        function setupUI() {
            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
            });
            
            // User menu dropdown
            const userMenu = document.getElementById('user-menu');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');
            
            userMenu.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenuDropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                userMenuDropdown.classList.add('hidden');
            });
            
            // Time period filter
            document.getElementById('time-period').addEventListener('change', function() {
                if (this.value === 'custom') {
                    // In a real app, you would show a date range picker
                    alert('Custom date range selection would appear here');
                } else {
                    reloadAllData();
                }
            });
        }
        
        // Load summary data for cards
        function loadSummaryData() {
            // In a real app, this would come from an API/database
            // These are mock values for demonstration
            document.getElementById('total-revenue-count').textContent = '$24,580';
            document.getElementById('total-expenses-count').textContent = '$18,420';
            document.getElementById('net-profit-count').textContent = '$6,160';
            document.getElementById('profit-margin-count').textContent = '25%';
        }
        
        // Initialize all charts
        function initCharts() {
            initRevenueExpensesChart();
            initProfitTrendsChart();
            initExpenseBreakdownChart();
            initRevenueSourcesChart();
        }
        
        // Initialize revenue vs expenses chart
        function initRevenueExpensesChart() {
            const ctx = document.getElementById('revenueExpensesChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        {
                            label: 'Revenue',
                            data: [18500, 19500, 21000, 22000, 23000, 24000, 25000, 25500, 24500, 23500, 22500, 24580],
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Expenses',
                            data: [16500, 17000, 17500, 18000, 18500, 19000, 19500, 20000, 19500, 19000, 18500, 18420],
                            backgroundColor: 'rgba(239, 68, 68, 0.7)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: $${context.raw.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize profit trends chart
        function initProfitTrendsChart() {
            const ctx = document.getElementById('profitTrendsChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Net Profit',
                        data: [2000, 2500, 3500, 4000, 4500, 5000, 5500, 5500, 5000, 4500, 4000, 6160],
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: $${context.raw.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize expense breakdown chart
        function initExpenseBreakdownChart() {
            const ctx = document.getElementById('expenseBreakdownChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Feed', 'Labor', 'Veterinary', 'Equipment', 'Utilities', 'Other'],
                    datasets: [{
                        data: [35, 25, 15, 10, 8, 7],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(201, 203, 207, 0.7)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(201, 203, 207, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.raw}%`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize revenue sources chart
        function initRevenueSourcesChart() {
            const ctx = document.getElementById('revenueSourcesChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Dairy', 'Meat', 'Eggs', 'Crops', 'Other Products', 'Services'],
                    datasets: [{
                        data: [40, 25, 15, 12, 5, 3],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(239, 68, 68, 0.7)',
                            'rgba(249, 115, 22, 0.7)',
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(20, 184, 166, 0.7)'
                        ],
                        borderColor: [
                            'rgba(16, 185, 129, 1)',
                            'rgba(239, 68, 68, 1)',
                            'rgba(249, 115, 22, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(139, 92, 246, 1)',
                            'rgba(20, 184, 166, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.raw}%`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Load data for tables
        function loadTableData() {
            // Top Revenue Products table data
            const revenueProductsData = [
                { product: 'Fresh Milk', category: 'Dairy', revenue: '$8,250', quantity: '5,500L', avgPrice: '$1.50/L', trend: 'up' },
                { product: 'Eggs (Dozen)', category: 'Poultry', revenue: '$3,780', quantity: '1,260 doz', avgPrice: '$3.00/doz', trend: 'up' },
                { product: 'Beef', category: 'Meat', revenue: '$3,200', quantity: '800kg', avgPrice: '$4.00/kg', trend: 'neutral' },
                { product: 'Cheese', category: 'Dairy', revenue: '$2,450', quantity: '350kg', avgPrice: '$7.00/kg', trend: 'up' },
                { product: 'Wool', category: 'Sheep', revenue: '$1,850', quantity: '370kg', avgPrice: '$5.00/kg', trend: 'down' }
            ];
            
            const revenueProductsTable = document.getElementById('revenue-products-table-body');
            revenueProductsTable.innerHTML = revenueProductsData.map(item => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${item.product}</td>
                    <td class="px-6 py-4 whitespace-nowrap capitalize">${item.category}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.revenue}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.quantity}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.avgPrice}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="flex items-center">
                            ${item.trend === 'up' ? 
                                '<i class="fas fa-arrow-up text-green-500 mr-1"></i><span class="text-green-500">Increasing</span>' : 
                                item.trend === 'down' ? 
                                '<i class="fas fa-arrow-down text-red-500 mr-1"></i><span class="text-red-500">Decreasing</span>' : 
                                '<i class="fas fa-minus text-gray-500 mr-1"></i><span class="text-gray-500">Stable</span>'}
                        </span>
                    </td>
                </tr>
            `).join('');
            
            // Expense Analysis table data
            const expenseAnalysisData = [
                { category: 'Feed', amount: '$6,447', percent: '35%', lastPeriod: '$5,980', change: '+7.8%', variance: '$467' },
                { category: 'Labor', amount: '$4,605', percent: '25%', lastPeriod: '$4,320', change: '+6.6%', variance: '$285' },
                { category: 'Veterinary', amount: '$2,763', percent: '15%', lastPeriod: '$2,950', change: '-6.3%', variance: '$-187' },
                { category: 'Equipment', amount: '$1,842', percent: '10%', lastPeriod: '$1,650', change: '+11.6%', variance: '$192' },
                { category: 'Utilities', amount: '$1,474', percent: '8%', lastPeriod: '$1,520', change: '-3.0%', variance: '$-46' },
                { category: 'Other', amount: '$1,290', percent: '7%', lastPeriod: '$1,200', change: '+7.5%', variance: '$90' }
            ];
            
            const expenseAnalysisTable = document.getElementById('expense-analysis-table-body');
            expenseAnalysisTable.innerHTML = expenseAnalysisData.map(item => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 capitalize">${item.category}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.amount}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.percent}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.lastPeriod}</td>
                    <td class="px-6 py-4 whitespace-nowrap ${item.change.startsWith('+') ? 'text-green-500' : 'text-red-500'}">
                        ${item.change}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap ${item.variance.startsWith('$') ? 'text-green-500' : 'text-red-500'}">
                        ${item.variance}
                    </td>
                </tr>
            `).join('');
            
            // Financial KPIs table data
            const kpiData = [
                { kpi: 'Gross Profit Margin', current: '32%', target: '30%', variance: '+2%', trend: 'up', status: 'exceeded' },
                { kpi: 'Operating Expense Ratio', current: '65%', target: '70%', variance: '-5%', trend: 'down', status: 'exceeded' },
                { kpi: 'Return on Assets', current: '8.5%', target: '10%', variance: '-1.5%', trend: 'up', status: 'on track' },
                { kpi: 'Debt-to-Equity Ratio', current: '1.2', target: '1.0', variance: '+0.2', trend: 'neutral', status: 'needs attention' },
                { kpi: 'Current Ratio', current: '1.8', target: '2.0', variance: '-0.2', trend: 'up', status: 'on track' }
            ];
            
            const kpiTable = document.getElementById('kpi-table-body');
            kpiTable.innerHTML = kpiData.map(item => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${item.kpi}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.current}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.target}</td>
                    <td class="px-6 py-4 whitespace-nowrap ${item.variance.startsWith('+') ? 'text-green-500' : item.variance.startsWith('-') ? 'text-red-500' : 'text-gray-500'}">
                        ${item.variance}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${item.trend === 'up' ? 
                            '<i class="fas fa-arrow-up text-green-500"></i>' : 
                            item.trend === 'down' ? 
                            '<i class="fas fa-arrow-down text-red-500"></i>' : 
                            '<i class="fas fa-minus text-gray-500"></i>'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full ${item.status === 'exceeded' ? 'bg-green-100 text-green-800' : item.status === 'on track' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'}">
                            ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                        </span>
                    </td>
                </tr>
            `).join('');
        }
        
        // Reload all data when filters change
        function reloadAllData() {
            loadSummaryData();
            // In a real app, you would update charts and tables with filtered data
        }
    </script>
</body>
</html>