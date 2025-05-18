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
    <title>AgriVision Pro | Livestock Analytics</title>
    <link rel="icon" href="./../images/logo.png" type="image/png">
    <link href="./../dist/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/controls/OrbitControls.js"></script>
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
        .status-healthy {
            color: #10B981;
            background-color: #ECFDF5;
        }
        .status-pregnant {
            color: #8B5CF6;
            background-color: #F5F3FF;
        }
        .status-sick {
            color: #EF4444;
            background-color: #FEE2E2;
        }
        .status-injured {
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
        .animal-card {
            transition: all 0.3s ease;
        }
        .animal-card:hover {
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
        /* Image upload preview */
        #preview-image {
            max-height: 200px;
            max-width: 100%;
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
        
        /* 3D Chart Container */
        #livestock3dChart {
            width: 100%;
            height: 400px;
            background-color: #f8fafc;
            border-radius: 0.5rem;
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
                            <input id="search-input" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search analytics..." type="search">
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
                        <span class="relative">
                            Home
                            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-600 transition-all duration-300 group-hover:w-full"></span>
                        </span>
                        <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-green-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="tasks-analytics.php" 
                class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300 ">
                    <i class="fas fa-tasks mr-2 transition-all duration-300 group-hover:text-blue-600"></i>
                    <span class="relative">
                        Task Analytics
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-blue-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="crop-analytics.php" 
                class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                    <i class="fas fa-leaf mr-2 transition-all duration-300 group-hover:text-green-600"></i>
                    <span class="relative">
                        Crop Analytics
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-600 transition-all duration-300 group-hover:w-full"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-green-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="livestock-analytics.php" 
                    class="nav-item active relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                        <i class="fas fa-dog mr-2 text-blue-600"></i>
                        <span class="relative text-blue-600">
                        Livestock Analytics
                            <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600"></span>
                        </span>
                        <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-blue-400 via-blue-600 to-blue-400"></span>
                </a>
                
                <a href="inventory-analytics.php" 
                class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                    <i class="fas fa-boxes mr-2 transition-all duration-300 group-hover:text-purple-600"></i>
                    <span class="relative">
                        Inventory Analytics
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-purple-600 transition-all duration-300 group-hover:w-full"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-purple-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="financial-analytics.php" 
                class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                    <i class="fas fa-chart-line mr-2 transition-all duration-300 group-hover:text-indigo-600"></i>
                    <span class="relative">
                        Financial Analytics
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-indigo-600 transition-all duration-300 group-hover:w-full"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-indigo-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
            </nav>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Livestock Analytics</h2>
                        <p class="text-sm text-gray-500">Comprehensive insights and performance metrics for your livestock</p>
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
                    <!-- Total Animals -->
                    <div class="analytics-card bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i class="fas fa-cow text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Animals</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="total-animals-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="flex items-center mr-2">
                                        <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                                        <span class="text-green-500">12%</span>
                                    </span>
                                    <span>vs last period</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Healthy Animals -->
                    <div class="analytics-card bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <i class="fas fa-heart text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Healthy</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="healthy-animals-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="flex items-center mr-2">
                                        <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                                        <span class="text-green-500">5%</span>
                                    </span>
                                    <span>vs last period</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pregnant Animals -->
                    <div class="analytics-card bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                    <i class="fas fa-baby text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Pregnant</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="pregnant-animals-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="flex items-center mr-2">
                                        <i class="fas fa-arrow-down text-red-500 mr-1"></i>
                                        <span class="text-red-500">3%</span>
                                    </span>
                                    <span>vs last period</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sick/Injured Animals -->
                    <div class="analytics-card bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                    <i class="fas fa-band-aid text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Needs Attention</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="sick-animals-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="flex items-center mr-2">
                                        <i class="fas fa-arrow-down text-green-500 mr-1"></i>
                                        <span class="text-green-500">8%</span>
                                    </span>
                                    <span>vs last period</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row 1 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Livestock Type Distribution -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Livestock Distribution</h3>
                            <p class="mt-1 text-sm text-gray-500">Number of animals by type</p>
                        </div>
                        <div class="p-4">
                            <canvas id="typeChart" class="w-full h-64"></canvas>
                        </div>
                    </div>
                    
                    <!-- Production Trends -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Production Trends</h3>
                            <p class="mt-1 text-sm text-gray-500">Milk, egg, meat production by type</p>
                        </div>
                        <div class="p-4">
                            <canvas id="productionChart" class="w-full h-64"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row 2 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Health Analysis -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Health Analysis</h3>
                            <p class="mt-1 text-sm text-gray-500">Common diseases and treatments</p>
                        </div>
                        <div class="p-4">
                            <canvas id="healthChart" class="w-full h-64"></canvas>
                        </div>
                    </div>
                    
                    <!-- Feed Conversion Ratio -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Feed Conversion Ratio</h3>
                            <p class="mt-1 text-sm text-gray-500">Efficiency by animal group</p>
                        </div>
                        <div class="p-4">
                            <canvas id="feedChart" class="w-full h-64"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- 3D Visualization -->
                <div class="analytics-card bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">3D Livestock Population</h3>
                        <p class="mt-1 text-sm text-gray-500">Interactive visualization of your livestock distribution</p>
                    </div>
                    <div class="p-4">
                        <div id="livestock3dChart"></div>
                    </div>
                </div>
                
                <!-- Detailed Tables -->
                <div class="grid grid-cols-1 gap-6">
                    <!-- Animal Population Analysis -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Animal Population Analysis</h3>
                            <p class="mt-1 text-sm text-gray-500">Number of animals by type, birth, death, and sale rates</p>
                        </div>
                        <div class="p-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Birth Rate</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Death Rate</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale Rate</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trend</th>
                                    </tr>
                                </thead>
                                <tbody id="population-table-body" class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            <i class="fas fa-spinner fa-spin mr-2"></i> Loading data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Production Analysis -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Production Analysis</h3>
                            <p class="mt-1 text-sm text-gray-500">Milk, egg, meat production by type and average production per animal</p>
                        </div>
                        <div class="p-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Production</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg per Animal</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Period</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                                    </tr>
                                </thead>
                                <tbody id="production-table-body" class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            <i class="fas fa-spinner fa-spin mr-2"></i> Loading data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Health Analysis Table -->
                    <div class="analytics-card bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Health Analysis</h3>
                            <p class="mt-1 text-sm text-gray-500">Common diseases, treatments, and vaccination status</p>
                        </div>
                        <div class="p-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disease</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Affected Animals</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Common Treatment</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Cost</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vaccine Available</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vaccination %</th>
                                    </tr>
                                </thead>
                                <tbody id="health-table-body" class="bg-white divide-y divide-gray-200">
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
            init3DChart();
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
            // Mock data for demonstration
            document.getElementById('total-animals-count').textContent = '142';
            document.getElementById('healthy-animals-count').textContent = '118';
            document.getElementById('pregnant-animals-count').textContent = '15';
            document.getElementById('sick-animals-count').textContent = '9';
        }
        
        // Initialize all charts
        function initCharts() {
            initTypeChart();
            initProductionChart();
            initHealthChart();
            initFeedChart();
        }
        
        // Initialize type distribution chart
        function initTypeChart() {
            const ctx = document.getElementById('typeChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Cattle', 'Poultry', 'Goats', 'Sheep', 'Pigs', 'Buffaloes'],
                    datasets: [{
                        data: [42, 65, 18, 8, 5, 4],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(255, 205, 86, 0.7)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(255, 205, 86, 1)'
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
                                    return `${context.label}: ${context.raw} animals`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize production trends chart
        function initProductionChart() {
            const ctx = document.getElementById('productionChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        {
                            label: 'Milk (liters)',
                            data: [420, 450, 480, 520, 550, 580, 600, 620, 610, 590, 560, 530],
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Eggs (dozens)',
                            data: [85, 90, 95, 100, 105, 110, 115, 120, 118, 115, 110, 105],
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Meat (kg)',
                            data: [120, 125, 130, 140, 150, 160, 170, 180, 175, 170, 165, 160],
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            tension: 0.3,
                            fill: true
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
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Initialize health analysis chart
        function initHealthChart() {
            const ctx = document.getElementById('healthChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Mastitis', 'Foot Rot', 'Worms', 'Respiratory', 'Brucellosis', 'Other'],
                    datasets: [{
                        label: 'Cases in Last 6 Months',
                        data: [12, 8, 15, 6, 3, 5],
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
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Initialize feed conversion chart
        function initFeedChart() {
            const ctx = document.getElementById('feedChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['Dairy Cows', 'Beef Cattle', 'Layers', 'Broilers', 'Goats', 'Sheep'],
                    datasets: [
                        {
                            label: 'Feed Conversion Ratio',
                            data: [1.5, 6.0, 2.2, 1.8, 4.5, 5.0],
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgba(54, 162, 235, 1)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        r: {
                            angleLines: {
                                display: true
                            },
                            suggestedMin: 0,
                            suggestedMax: 8
                        }
                    }
                }
            });
        }
        
        // Initialize 3D chart
        function init3DChart() {
            const container = document.getElementById('livestock3dChart');
            
            // Create scene
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0xf8fafc);
            
            // Create camera
            const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.z = 30;
            
            // Create renderer
            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            container.appendChild(renderer.domElement);
            
            // Add controls
            const controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.25;
            
            // Add lights
            const ambientLight = new THREE.AmbientLight(0x404040);
            scene.add(ambientLight);
            
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
            directionalLight.position.set(1, 1, 1);
            scene.add(directionalLight);
            
            // Create animal models (simplified with basic shapes)
            const animalTypes = [
                { name: 'Cattle', color: 0x8b5cf6, count: 42, size: 1.5 },
                { name: 'Poultry', color: 0xef4444, count: 65, size: 0.8 },
                { name: 'Goats', color: 0x10b981, count: 18, size: 1.0 },
                { name: 'Sheep', color: 0x3b82f6, count: 8, size: 1.0 },
                { name: 'Pigs', color: 0xf59e0b, count: 5, size: 1.2 },
                { name: 'Buffaloes', color: 0x64748b, count: 4, size: 1.8 }
            ];
            
            // Position animals in a circular pattern
            animalTypes.forEach((type, index) => {
                const angle = (index / animalTypes.length) * Math.PI * 2;
                const radius = 15;
                
                for (let i = 0; i < type.count / 4; i++) { // Reduce number for performance
                    const animalAngle = angle + (Math.random() - 0.5) * 0.5;
                    const animalRadius = radius + (Math.random() - 0.5) * 5;
                    
                    const x = Math.cos(animalAngle) * animalRadius;
                    const z = Math.sin(animalAngle) * animalRadius;
                    const y = (Math.random() - 0.5) * 5;
                    
                    let geometry;
                    if (type.name === 'Poultry') {
                        geometry = new THREE.SphereGeometry(type.size, 16, 16);
                    } else {
                        geometry = new THREE.BoxGeometry(type.size, type.size, type.size);
                    }
                    
                    const material = new THREE.MeshPhongMaterial({ 
                        color: type.color,
                        shininess: 30
                    });
                    
                    const animal = new THREE.Mesh(geometry, material);
                    animal.position.set(x, y, z);
                    animal.rotation.y = Math.random() * Math.PI;
                    
                    scene.add(animal);
                }
            });
            
            // Add central reference point
            const centerGeometry = new THREE.SphereGeometry(2, 32, 32);
            const centerMaterial = new THREE.MeshPhongMaterial({ 
                color: 0x334155,
                emissive: 0x334155,
                emissiveIntensity: 0.2
            });
            const center = new THREE.Mesh(centerGeometry, centerMaterial);
            scene.add(center);
            
            // Animation loop
            function animate() {
                requestAnimationFrame(animate);
                controls.update();
                renderer.render(scene, camera);
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            });
            
            animate();
        }
        
        // Load data for tables
        function loadTableData() {
            // Population table data
            const populationData = [
                { type: 'Cattle', count: 42, birthRate: '8%', deathRate: '2%', saleRate: '5%', trend: 'up' },
                { type: 'Poultry', count: 65, birthRate: '15%', deathRate: '5%', saleRate: '20%', trend: 'up' },
                { type: 'Goats', count: 18, birthRate: '10%', deathRate: '3%', saleRate: '8%', trend: 'neutral' },
                { type: 'Sheep', count: 8, birthRate: '5%', deathRate: '2%', saleRate: '3%', trend: 'down' },
                { type: 'Pigs', count: 5, birthRate: '12%', deathRate: '4%', saleRate: '6%', trend: 'up' },
                { type: 'Buffaloes', count: 4, birthRate: '3%', deathRate: '1%', saleRate: '2%', trend: 'neutral' }
            ];
            
            const populationTable = document.getElementById('population-table-body');
            populationTable.innerHTML = populationData.map(item => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 capitalize">${item.type}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.count}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.birthRate}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.deathRate}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.saleRate}</td>
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
            
            // Production table data
            const productionData = [
                { product: 'Milk', type: 'Dairy Cows', total: '6,200L', avg: '15L', lastPeriod: '5,800L', change: '+6.9%' },
                { product: 'Eggs', type: 'Layers', total: '1,380 doz', avg: '21 doz', lastPeriod: '1,250 doz', change: '+10.4%' },
                { product: 'Meat', type: 'Beef Cattle', total: '1,850 kg', avg: '44 kg', lastPeriod: '1,720 kg', change: '+7.6%' },
                { product: 'Meat', type: 'Broilers', total: '980 kg', avg: '1.8 kg', lastPeriod: '920 kg', change: '+6.5%' },
                { product: 'Milk', type: 'Goats', total: '420L', avg: '2.5L', lastPeriod: '390L', change: '+7.7%' }
            ];
            
            const productionTable = document.getElementById('production-table-body');
            productionTable.innerHTML = productionData.map(item => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${item.product}</td>
                    <td class="px-6 py-4 whitespace-nowrap capitalize">${item.type}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.total}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.avg}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.lastPeriod}</td>
                    <td class="px-6 py-4 whitespace-nowrap ${item.change.startsWith('+') ? 'text-green-500' : 'text-red-500'}">
                        ${item.change}
                    </td>
                </tr>
            `).join('');
            
            // Health table data
            const healthData = [
                { disease: 'Mastitis', affected: 12, treatment: 'Antibiotics', cost: '$12.50', vaccine: 'No', vaccinated: '0%' },
                { disease: 'Foot Rot', affected: 8, treatment: 'Foot baths', cost: '$8.20', vaccine: 'No', vaccinated: '0%' },
                { disease: 'Worms', affected: 15, treatment: 'Dewormer', cost: '$5.80', vaccine: 'No', vaccinated: '0%' },
                { disease: 'Respiratory', affected: 6, treatment: 'Antibiotics', cost: '$15.30', vaccine: 'Yes', vaccinated: '85%' },
                { disease: 'Brucellosis', affected: 3, treatment: 'Test & cull', cost: '$25.00', vaccine: 'Yes', vaccinated: '92%' }
            ];
            
            const healthTable = document.getElementById('health-table-body');
            healthTable.innerHTML = healthData.map(item => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${item.disease}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.affected}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.treatment}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.cost}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.vaccine}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: ${item.vaccinated}"></div>
                            </div>
                            <span class="ml-2 text-xs text-gray-500">${item.vaccinated}</span>
                        </div>
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