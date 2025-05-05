<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php';
checkAuthentication();

// Get user details from session and database
$current_user = [
    'name' => $_SESSION['full_name'] ?? 'Unknown User',
    'email' => $_SESSION['username'] ?? 'No email',
    'role' => $_SESSION['role'] ?? 'Unknown Role',
    'initials' => getInitials($_SESSION['full_name'] ?? 'UU'),
    'profile_picture' => ''
];

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
    <title>AgriVision Pro | Crop Management</title>
    <link rel="icon" href="./images/logo.png" type="image/png">
    <link href="./dist/output.css" rel="stylesheet">
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
        .status-growing {
            color: #10B981;
            background-color: #ECFDF5;
        }
        .status-harvested {
            color: #3B82F6;
            background-color: #EFF6FF;
        }
        .status-planned {
            color: #F59E0B;
            background-color: #FFFBEB;
        }
        .status-problem {
            color: #EF4444;
            background-color: #FEE2E2;
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
        .crop-card {
            transition: all 0.3s ease;
        }
        .crop-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #E5E7EB;
        }
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        .growing .progress-fill {
            background-color: #10B981;
        }
        .harvested .progress-fill {
            background-color: #3B82F6;
        }
        .planned .progress-fill {
            background-color: #F59E0B;
        }
        .problem .progress-fill {
            background-color: #EF4444;
        }
        /* Add these to your existing style section */
        /* Improved modal styling */
        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
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
    </style>
</head>
<body class="h-full overflow-hidden">
    <!-- App Container -->
    <div class="flex h-full">
        <!-- Dynamic Sidebar -->
        <aside id="sidebar" class="w-64 bg-gradient-to-b from-blue-900 to-blue-800 text-white shadow-xl">
            <div class="p-4 flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center">
                    <img src="./images/logo.png" alt="App Logo" class="h-10 w-10 object-contain">
                </div>
                <h1 class="text-xl font-bold">AgriVision Pro</h1>
            </div>
            
            <nav class="mt-8">
                <div class="px-4 space-y-1">
                    <a href="index.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>
                    <a href="crops.php" class="flex items-center px-4 py-3 rounded-lg bg-blue-500 bg-opacity-30 text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        Crop Management
                    </a>
                    <a href="livestock.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Livestock
                    </a>
                    <a href="inventory.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Inventory
                    </a>
                    <a href="tasks.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Tasks
                    </a>
                    <a href="analytics.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Analytics
                    </a>
                </div>
                
                <div class="mt-8 pt-8 border-t border-blue-700">
                    <div class="px-4 space-y-1">
                        <a href="settings.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
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
                            <input id="search-input" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search crops, fields..." type="search">
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
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user-circle mr-2"></i> Your Profile
                                </a>
                                <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-2"></i> Settings
                                </a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Sign out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Crop Management</h2>
                        <p class="text-sm text-gray-500">Track and manage all your crops in one place</p>
                    </div>
                    <button id="add-crop-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i> Add New Crop
                    </button>
                </div>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                    <!-- Total Crops -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <i class="fas fa-leaf text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Crops</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="total-crops-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Growing Crops -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i class="fas fa-seedling text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Growing</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="growing-crops-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Harvested Crops -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <i class="fas fa-wheat-awn text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Harvested</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="harvested-crops-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Problem Crops -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                    <i class="fas fa-bug text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Needs Attention</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="problem-crops-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Crop Yield Chart -->
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Crop Yield Forecast</h3>
                        <p class="mt-1 text-sm text-gray-500">Estimated yield per crop type</p>
                    </div>
                    <div class="p-4">
                        <canvas id="yieldChart" class="w-full h-64"></canvas>
                    </div>
                </div>
                
                <!-- Crop Management -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <div class="relative w-64">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" id="search-crops" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search crops...">
                            </div>
                            
                            <select id="status-filter" title="Filter by crop status" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="all">All Status</option>
                                <option value="growing">Growing</option>
                                <option value="harvested">Harvested</option>
                                <option value="planned">Planned</option>
                                <option value="problem">Needs Attention</option>
                            </select>
                        </div>
                        
                        <div class="flex space-x-2">
                            <select id="sort-by" title="Sort crops by criteria" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="name-asc">Name (A-Z)</option>
                                <option value="name-desc">Name (Z-A)</option>
                                <option value="date-asc">Planted Date (Oldest)</option>
                                <option value="date-desc">Planted Date (Newest)</option>
                                <option value="harvest-asc">Harvest Date (Soonest)</option>
                                <option value="harvest-desc">Harvest Date (Latest)</option>
                            </select>
                            
                            <button id="view-toggle" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-table mr-2"></i> Table View
                            </button>
                        </div>
                    </div>
                    
                    <!-- Card View -->
                    <div id="card-view" class="hidden p-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Cards will be loaded here dynamically -->
                        <div class="text-center py-10">
                            <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                            <p class="mt-2 text-sm text-gray-500">Loading crops...</p>
                        </div>
                    </div>
                    
                    <!-- Table View -->
                    <div id="table-view" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CROP</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">FIELD</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AREA</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PLANTED DATE</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HARVEST DATE</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PROGRESS</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="crops-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Crops will be loaded here dynamically -->
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading crops...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Previous </a>
                            <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Next </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium" id="pagination-start">1</span> to <span class="font-medium" id="pagination-end">10</span> of <span class="font-medium" id="pagination-total">0</span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <a href="#" id="prev-page" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <div id="page-numbers" class="flex">
                                        <!-- Page numbers will be inserted here -->
                                    </div>
                                    <a href="#" id="next-page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Replace the existing modals with these improved versions -->

<!-- Improved Add/Edit Crop Modal -->
<div id="crop-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
            <div>
                <h3 id="modal-title" class="text-2xl font-bold text-gray-800 mb-4">Add New Crop</h3>
                <form id="crop-form">
                    <input type="hidden" id="crop-id">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-6 sm:grid-cols-6">
                        <!-- Crop Basic Info Section -->
                        <div class="sm:col-span-6 border-b border-gray-200 pb-4 mb-4">
                            <h4 class="text-lg font-medium text-gray-800 mb-3">Basic Information</h4>
                            <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                                <div class="sm:col-span-6">
                                    <label for="crop-name" class="block text-sm font-medium text-gray-700 flex items-center">
                                        Crop Name <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <input type="text" id="crop-name" required 
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                
                                <div class="sm:col-span-3">
                                    <label for="crop-variety" class="block text-sm font-medium text-gray-700">Variety</label>
                                    <input type="text" id="crop-variety" 
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                
                                <div class="sm:col-span-3">
                                    <label for="crop-field" class="block text-sm font-medium text-gray-700 flex items-center">
                                        Field/Location <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <input type="text" id="crop-field" required 
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Crop Details Section -->
                        <div class="sm:col-span-6 border-b border-gray-200 pb-4 mb-4">
                            <h4 class="text-lg font-medium text-gray-800 mb-3">Crop Details</h4>
                            <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                                <div class="sm:col-span-2">
                                    <label for="crop-area" class="block text-sm font-medium text-gray-700 flex items-center">
                                        Area (ha) <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="number" step="0.01" min="0" id="crop-area" required 
                                               class="block w-full pr-12 border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">ha</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="sm:col-span-2">
                                    <label for="crop-status" class="block text-sm font-medium text-gray-700 flex items-center">
                                        Status <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <select id="crop-status" required 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="growing">Growing</option>
                                        <option value="harvested">Harvested</option>
                                        <option value="planned">Planned</option>
                                        <option value="problem">Needs Attention</option>
                                    </select>
                                </div>
                                
                                <div class="sm:col-span-2">
                                    <label for="crop-yield" class="block text-sm font-medium text-gray-700">Expected Yield</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="number" step="1" min="0" id="crop-yield" 
                                               class="block w-full pr-12 border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">kg/ha</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="sm:col-span-3">
                                    <label for="crop-planted-date" class="block text-sm font-medium text-gray-700 flex items-center">
                                        Planted Date <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <input type="date" id="crop-planted-date" required 
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                
                                <div class="sm:col-span-3">
                                    <label for="crop-harvest-date" class="block text-sm font-medium text-gray-700">Harvest Date</label>
                                    <input type="date" id="crop-harvest-date" 
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notes and Image Section -->
                        <div class="sm:col-span-6">
                            <label for="crop-notes" class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea id="crop-notes" rows="3" 
                                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                        </div>
                        
                        <div class="sm:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Crop Image</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md relative">
                                <div id="image-upload-area" class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label for="crop-image" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>Upload a file</span>
                                            <input id="crop-image" name="crop-image" type="file" class="sr-only" accept="image/*">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG up to 5MB</p>
                                </div>
                                <div id="image-preview" class="hidden absolute inset-0 flex items-center justify-center">
                                    <img id="preview-image" src="#" alt="Preview" class="max-h-full max-w-full object-contain">
                                    <button type="button" id="remove-image" class="absolute top-2 right-2 bg-white rounded-full p-1 shadow-md hover:bg-gray-100">
                                        <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                <button type="button" id="save-crop" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">
                    <i class="fas fa-save mr-2"></i> Save Crop
                </button>
                <button type="button" id="cancel-crop" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                    <i class="fas fa-times mr-2"></i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Improved Crop Details Modal -->
<div id="crop-details-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
            <div>
                <div class="flex justify-between items-start">
                    <div>
                        <h3 id="crop-details-title" class="text-2xl font-bold text-gray-800">Crop Details</h3>
                        <p id="crop-details-subtitle" class="mt-1 text-sm text-gray-500">Detailed information about this crop</p>
                    </div>
                    <button id="close-details" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <!-- Image Column -->
                    <div class="sm:col-span-2">
                        <div class="h-64 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                            <img id="crop-details-image" src="https://source.unsplash.com/random/300x300/?farm" alt="Crop image" class="w-full h-full object-cover">
                        </div>
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Crop Timeline</h4>
                            <div class="relative pt-1">
                                <div class="flex mb-2 items-center justify-between">
                                    <div>
                                        <span id="timeline-status" class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                                            Planted
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span id="timeline-days" class="text-xs font-semibold inline-block text-blue-600">
                                            0 of 0 days
                                        </span>
                                    </div>
                                </div>
                                <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
                                    <div id="timeline-progress" style="width: 0%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-600">
                                    <div>
                                        <span id="timeline-planted">-</span>
                                    </div>
                                    <div>
                                        <span id="timeline-harvest">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Details Column -->
                    <div class="sm:col-span-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2">
                                <div class="sm:col-span-1">
                                    <label class="block text-sm font-medium text-gray-500">Crop Name</label>
                                    <p id="crop-details-name" class="mt-1 text-sm font-medium text-gray-900">-</p>
                                </div>
                                
                                <div class="sm:col-span-1">
                                    <label class="block text-sm font-medium text-gray-500">Variety</label>
                                    <p id="crop-details-variety" class="mt-1 text-sm text-gray-900">-</p>
                                </div>
                                
                                <div class="sm:col-span-1">
                                    <label class="block text-sm font-medium text-gray-500">Field/Location</label>
                                    <p id="crop-details-field" class="mt-1 text-sm text-gray-900">-</p>
                                </div>
                                
                                <div class="sm:col-span-1">
                                    <label class="block text-sm font-medium text-gray-500">Area</label>
                                    <p id="crop-details-area" class="mt-1 text-sm text-gray-900">-</p>
                                </div>
                                
                                <div class="sm:col-span-1">
                                    <label class="block text-sm font-medium text-gray-500">Status</label>
                                    <p id="crop-details-status" class="mt-1 text-sm text-gray-900">-</p>
                                </div>
                                
                                <div class="sm:col-span-1">
                                    <label class="block text-sm font-medium text-gray-500">Expected Yield</label>
                                    <p id="crop-details-yield" class="mt-1 text-sm text-gray-900">-</p>
                                </div>
                                
                                <div class="sm:col-span-1">
                                    <label class="block text-sm font-medium text-gray-500">Planted Date</label>
                                    <p id="crop-details-planted-date" class="mt-1 text-sm text-gray-900">-</p>
                                </div>
                                
                                <div class="sm:col-span-1">
                                    <label class="block text-sm font-medium text-gray-500">Harvest Date</label>
                                    <p id="crop-details-harvest-date" class="mt-1 text-sm text-gray-900">-</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 bg-gray-50 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-500 mb-2">Notes</label>
                            <p id="crop-details-notes" class="text-sm text-gray-900">No notes available</p>
                        </div>
                    </div>
                    
                    <!-- Activities Section -->
                    <div class="sm:col-span-6">
                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="text-lg font-medium text-gray-800 mb-3">Recent Activities</h4>
                            <div id="crop-activities" class="space-y-4">
                                <div class="text-center py-4 text-sm text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i> Loading activities...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                <button type="button" id="edit-crop-from-details" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">
                    <i class="fas fa-edit mr-2"></i> Edit Crop
                </button>
                <button type="button" id="close-details-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                    <i class="fas fa-times mr-2"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
    </div>

    <!-- JavaScript -->
    <script>
        // Database configuration
        const DB_NAME = 'AgriVisionProDB';
        const DB_VERSION = 3; // Incremented for new fields
        const STORES = {
            CROPS: 'crops',
            ACTIVITY: 'activity'
        };

        let db;
        let currentPage = 1;
        const itemsPerPage = 10;
        let currentView = 'table'; // 'table' or 'card'
        let currentCrops = [];
        let currentSort = 'name-asc';
        let currentStatusFilter = 'all';
        let currentSearchTerm = '';
        
        // Initialize the application
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                // Initialize database
                await initDB();
                
                // Setup UI interactions
                setupUI();
                
                // Load initial data
                await loadCrops();
                await updateSummaryCards();
                initYieldChart();
                await updateYieldChart();
                
            } catch (error) {
                console.error('Initialization error:', error);
                showErrorToast('Failed to initialize application');
            }
        });

        // Initialize IndexedDB
        function initDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, DB_VERSION);
                
                request.onerror = (event) => {
                    console.error('Database error:', event.target.error);
                    reject('Database error');
                };
                
                request.onsuccess = (event) => {
                    db = event.target.result;
                    console.log('Database initialized');
                    resolve(db);
                };
                
                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    
                    // Create object stores if they don't exist
                    if (!db.objectStoreNames.contains(STORES.CROPS)) {
                        const cropsStore = db.createObjectStore(STORES.CROPS, { keyPath: 'id', autoIncrement: true });
                        cropsStore.createIndex('name', 'name', { unique: false });
                        cropsStore.createIndex('variety', 'variety', { unique: false });
                        cropsStore.createIndex('field', 'field', { unique: false });
                        cropsStore.createIndex('area', 'area', { unique: false });
                        cropsStore.createIndex('status', 'status', { unique: false });
                        cropsStore.createIndex('plantedDate', 'plantedDate', { unique: false });
                        cropsStore.createIndex('harvestDate', 'harvestDate', { unique: false });
                        cropsStore.createIndex('yield', 'yield', { unique: false });
                    }
                    
                    if (!db.objectStoreNames.contains(STORES.ACTIVITY)) {
                        db.createObjectStore(STORES.ACTIVITY, { keyPath: 'id', autoIncrement: true });
                    }
                    
                    // Add sample data if this is a new database
                    if (event.oldVersion < 1) {
                        addSampleData(db);
                    }
                };
            });
        }
        
        // Add sample data to the database
        function addSampleData(db) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.CROPS, STORES.ACTIVITY], 'readwrite');
                const cropsStore = transaction.objectStore(STORES.CROPS);
                const activityStore = transaction.objectStore(STORES.ACTIVITY);
                
                // Sample crops
                const sampleCrops = [
                    {
                        name: 'Rice',
                        variety: 'Basmati',
                        field: 'North Field',
                        area: 2.5,
                        status: 'growing',
                        yield: 4500,
                        plantedDate: '2023-06-15',
                        harvestDate: '2023-09-20',
                        notes: 'Fertilized on 2023-07-01'
                    },
                    {
                        name: 'Corn',
                        variety: 'Sweet Corn',
                        field: 'East Field',
                        area: 3.2,
                        status: 'growing',
                        yield: 8000,
                        plantedDate: '2023-06-20',
                        harvestDate: '2023-09-25',
                        notes: 'Needs irrigation'
                    },
                    {
                        name: 'Wheat',
                        variety: 'Winter Wheat',
                        field: 'West Field',
                        area: 5.0,
                        status: 'harvested',
                        yield: 3500,
                        plantedDate: '2023-03-10',
                        harvestDate: '2023-07-05',
                        notes: 'Good yield this season'
                    },
                    {
                        name: 'Tomatoes',
                        variety: 'Cherry',
                        field: 'Greenhouse A',
                        area: 0.5,
                        status: 'problem',
                        yield: 12000,
                        plantedDate: '2023-07-01',
                        harvestDate: '2023-09-15',
                        notes: 'Showing signs of blight - needs treatment'
                    },
                    {
                        name: 'Potatoes',
                        variety: 'Russet',
                        field: 'South Field',
                        area: 1.8,
                        status: 'planned',
                        yield: 25000,
                        plantedDate: '2023-08-15',
                        harvestDate: '2023-11-20',
                        notes: 'Preparing soil for planting'
                    }
                ];
                
                // Add sample crops
                sampleCrops.forEach((crop, index) => {
                    cropsStore.add(crop);
                    
                    // Add sample activity
                    activityStore.add({
                        type: 'crop_added',
                        description: `Added new crop: ${crop.name}`,
                        date: crop.plantedDate,
                        user: 'System'
                    });
                    
                    if (crop.status === 'harvested') {
                        activityStore.add({
                            type: 'crop_harvested',
                            description: `Harvested ${crop.name} from ${crop.field}`,
                            date: crop.harvestDate,
                            user: 'System'
                        });
                    }
                });
                
                transaction.oncomplete = () => resolve();
                transaction.onerror = (event) => reject(event.target.error);
            });
        }
        
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
            
            // Add crop button
            document.getElementById('add-crop-btn').addEventListener('click', () => {
                document.getElementById('modal-title').textContent = 'Add New Crop';
                document.getElementById('crop-id').value = '';
                document.getElementById('crop-form').reset();
                document.getElementById('crop-modal').classList.remove('hidden');
            });
            
            // Save crop button
            document.getElementById('save-crop').addEventListener('click', async () => {
                const cropData = {
                    name: document.getElementById('crop-name').value,
                    variety: document.getElementById('crop-variety').value,
                    field: document.getElementById('crop-field').value,
                    area: parseFloat(document.getElementById('crop-area').value),
                    status: document.getElementById('crop-status').value,
                    yield: document.getElementById('crop-yield').value ? parseInt(document.getElementById('crop-yield').value) : null,
                    plantedDate: document.getElementById('crop-planted-date').value,
                    harvestDate: document.getElementById('crop-harvest-date').value || null,
                    notes: document.getElementById('crop-notes').value,
                    image: null // This would be handled with file upload in a real app
                };
                
                const id = document.getElementById('crop-id').value;
                
                try {
                    if (id) {
                        // Update existing crop
                        await updateCrop(parseInt(id), cropData);
                        
                        // Add activity log
                        await addActivity({
                            type: 'crop_updated',
                            description: `Updated crop: ${cropData.name}`,
                            date: new Date().toISOString().split('T')[0],
                            user: '<?= $current_user['name'] ?>'
                        });
                        
                        showSuccessToast('Crop updated successfully');
                    } else {
                        // Add new crop
                        const newId = await addCrop(cropData);
                        
                        // Add activity log
                        await addActivity({
                            type: 'crop_added',
                            description: `Added new crop: ${cropData.name}`,
                            date: new Date().toISOString().split('T')[0],
                            user: '<?= $current_user['name'] ?>'
                        });
                        
                        showSuccessToast('Crop added successfully');
                    }
                    
                    document.getElementById('crop-modal').classList.add('hidden');
                    await loadCrops(); // Refresh the table
                    await updateSummaryCards();
                    await updateYieldChart();
                } catch (error) {
                    console.error('Error saving crop:', error);
                    showErrorToast('Error saving crop. Please try again.');
                }
            });
            
            // Cancel crop button
            document.getElementById('cancel-crop').addEventListener('click', () => {
                document.getElementById('crop-modal').classList.add('hidden');
            });
            
            // Search crops
            document.getElementById('search-crops').addEventListener('input', async (e) => {
                currentSearchTerm = e.target.value;
                currentPage = 1;
                await loadCrops();
            });
            
            // Filter by status
            document.getElementById('status-filter').addEventListener('change', async (e) => {
                currentStatusFilter = e.target.value;
                currentPage = 1;
                await loadCrops();
            });
            
            // Sort crops
            document.getElementById('sort-by').addEventListener('change', async (e) => {
                currentSort = e.target.value;
                await loadCrops();
            });
            
            // View toggle
            document.getElementById('view-toggle').addEventListener('click', () => {
                toggleView();
            });
            
            // Pagination
            document.getElementById('prev-page').addEventListener('click', async (e) => {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    await loadCrops();
                }
            });
            
            document.getElementById('next-page').addEventListener('click', async (e) => {
                e.preventDefault();
                const totalPages = Math.ceil(currentCrops.length / itemsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    await loadCrops();
                }
            });
            
            // Close details modal
            document.getElementById('close-details').addEventListener('click', () => {
                document.getElementById('crop-details-modal').classList.add('hidden');
            });
            
            document.getElementById('close-details-btn').addEventListener('click', () => {
                document.getElementById('crop-details-modal').classList.add('hidden');
            });
            
            // Edit from details
            document.getElementById('edit-crop-from-details').addEventListener('click', () => {
                const id = document.getElementById('crop-details-modal').getAttribute('data-crop-id');
                if (id) {
                    openEditModal(parseInt(id));
                }
                document.getElementById('crop-details-modal').classList.add('hidden');
            });
            
            // Setup search functionality
            setupSearch();
        }
        
        // Toggle between table and card view
        function toggleView() {
            const viewToggle = document.getElementById('view-toggle');
            
            if (currentView === 'table') {
                currentView = 'card';
                document.getElementById('table-view').classList.add('hidden');
                document.getElementById('card-view').classList.remove('hidden');
                viewToggle.innerHTML = '<i class="fas fa-table mr-2"></i> Table View';
                renderCardView();
            } else {
                currentView = 'table';
                document.getElementById('table-view').classList.remove('hidden');
                document.getElementById('card-view').classList.add('hidden');
                viewToggle.innerHTML = '<i class="fas fa-th-large mr-2"></i> Card View';
            }
        }
        
        // Setup search functionality
        function setupSearch() {
            const searchInput = document.getElementById('search-input');
            const searchResults = document.getElementById('search-results');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                if (query.length === 0) {
                    searchResults.classList.add('hidden');
                    return;
                }
                
                clearTimeout(searchTimeout);
                
                searchTimeout = setTimeout(async () => {
                    if (query.length < 2) {
                        searchResults.classList.add('hidden');
                        return;
                    }
                    
                    try {
                        const results = await searchCrops(query);
                        displaySearchResults(results);
                    } catch (error) {
                        console.error('Search error:', error);
                        searchResults.classList.add('hidden');
                        showErrorToast('Search failed. Please try again.');
                    }
                }, 300);
            });
            
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target)) {
                    searchResults.classList.add('hidden');
                }
            });
        }
        
        // Search crops
        async function searchCrops(query) {
            const crops = await getAll(STORES.CROPS);
            const term = query.toLowerCase();
            
            return crops.filter(crop => 
                crop.name.toLowerCase().includes(term) || 
                (crop.variety && crop.variety.toLowerCase().includes(term)) ||
                (crop.field && crop.field.toLowerCase().includes(term))
            );
        }
        
        // Display search results
        function displaySearchResults(results) {
            const searchResults = document.getElementById('search-results');
            
            if (!results || results.length === 0) {
                searchResults.innerHTML = `
                    <div class="search-item text-gray-500">
                        <i class="fas fa-search mr-2"></i>
                        No results found
                    </div>
                `;
                searchResults.classList.remove('hidden');
                return;
            }
            
            searchResults.innerHTML = results.slice(0, 5).map(crop => `
                <a href="#" class="search-item block hover:bg-gray-50 transition-colors duration-150" data-crop-id="${crop.id}">
                    <div class="flex items-center p-3">
                        <div class="flex-shrink-0 p-2 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">${crop.name}</p>
                            <p class="text-xs text-gray-500 truncate">${crop.field || 'No field'} • ${getStatusText(crop.status)}</p>
                        </div>
                    </div>
                </a>
            `).join('');
            
            searchResults.classList.remove('hidden');
            
            // Add click handlers to search results
            document.querySelectorAll('.search-item[data-crop-id]').forEach(item => {
                item.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const id = parseInt(item.getAttribute('data-crop-id'));
                    await showCropDetails(id);
                    searchResults.classList.add('hidden');
                    document.getElementById('search-input').value = '';
                });
            });
        }
        
        // Load crops into the table/cards
        async function loadCrops() {
            try {
                let crops = await getAll(STORES.CROPS);
                currentCrops = crops;
                
                // Apply search filter
                if (currentSearchTerm) {
                    const term = currentSearchTerm.toLowerCase();
                    crops = crops.filter(crop => 
                        crop.name.toLowerCase().includes(term) || 
                        (crop.variety && crop.variety.toLowerCase().includes(term)) ||
                        (crop.field && crop.field.toLowerCase().includes(term))
                    );
                }
                
                // Apply status filter
                if (currentStatusFilter !== 'all') {
                    crops = crops.filter(crop => crop.status === currentStatusFilter);
                }
                
                // Apply sorting
                crops.sort((a, b) => {
                    switch(currentSort) {
                        case 'name-asc':
                            return a.name.localeCompare(b.name);
                        case 'name-desc':
                            return b.name.localeCompare(a.name);
                        case 'date-asc':
                            return new Date(a.plantedDate) - new Date(b.plantedDate);
                        case 'date-desc':
                            return new Date(b.plantedDate) - new Date(a.plantedDate);
                        case 'harvest-asc':
                            return (a.harvestDate ? new Date(a.harvestDate) : new Date('9999-12-31')) - 
                                   (b.harvestDate ? new Date(b.harvestDate) : new Date('9999-12-31'));
                        case 'harvest-desc':
                            return (b.harvestDate ? new Date(b.harvestDate) : new Date('0001-01-01')) - 
                                   (a.harvestDate ? new Date(a.harvestDate) : new Date('0001-01-01'));
                        default:
                            return 0;
                    }
                });
                
                currentCrops = crops;
                
                // Pagination
                const startIdx = (currentPage - 1) * itemsPerPage;
                const paginatedCrops = crops.slice(startIdx, startIdx + itemsPerPage);
                
                // Update pagination info
                updatePagination(crops.length);
                
                // Render the appropriate view
                if (currentView === 'table') {
                    renderTableView(paginatedCrops);
                } else {
                    renderCardView(paginatedCrops);
                }
                
            } catch (error) {
                console.error('Error loading crops:', error);
                showErrorToast('Failed to load crops');
            }
        }
        
        // Update pagination controls
        function updatePagination(totalItems) {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            const startItem = ((currentPage - 1) * itemsPerPage) + 1;
            const endItem = Math.min(currentPage * itemsPerPage, totalItems);
            
            document.getElementById('pagination-start').textContent = startItem;
            document.getElementById('pagination-end').textContent = endItem;
            document.getElementById('pagination-total').textContent = totalItems;
            
            // Disable/enable prev/next buttons
            const prevButton = document.getElementById('prev-page');
            const nextButton = document.getElementById('next-page');
            
            if (currentPage === 1) {
                prevButton.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                prevButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            
            if (currentPage === totalPages || totalItems === 0) {
                nextButton.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            
            // Update page numbers
            const pageNumbers = document.getElementById('page-numbers');
            pageNumbers.innerHTML = '';
            
            const maxPagesToShow = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
            
            // Adjust if we're at the end
            if (endPage - startPage + 1 < maxPagesToShow) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }
            
            // Always show page 1
            if (startPage > 1) {
                const page1 = document.createElement('a');
                page1.href = '#';
                page1.className = `relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium ${
                    1 === currentPage ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'
                }`;
                page1.textContent = '1';
                page1.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (1 !== currentPage) {
                        currentPage = 1;
                        loadCrops();
                    }
                });
                pageNumbers.appendChild(page1);
                
                if (startPage > 2) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700';
                    ellipsis.textContent = '...';
                    pageNumbers.appendChild(ellipsis);
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const pageLink = document.createElement('a');
                pageLink.href = '#';
                pageLink.className = `relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium ${
                    i === currentPage ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'
                }`;
                pageLink.textContent = i;
                pageLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (i !== currentPage) {
                        currentPage = i;
                        loadCrops();
                    }
                });
                pageNumbers.appendChild(pageLink);
            }
            
            // Always show last page
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700';
                    ellipsis.textContent = '...';
                    pageNumbers.appendChild(ellipsis);
                }
                
                const lastPage = document.createElement('a');
                lastPage.href = '#';
                lastPage.className = `relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium ${
                    totalPages === currentPage ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'
                }`;
                lastPage.textContent = totalPages;
                lastPage.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (totalPages !== currentPage) {
                        currentPage = totalPages;
                        loadCrops();
                    }
                });
                pageNumbers.appendChild(lastPage);
            }
        }
        
        // Render table view
        function renderTableView(crops) {
            const tableBody = document.getElementById('crops-table-body');
            
            if (crops.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                            No crops found. <button id="add-crop-empty" class="text-blue-600 hover:text-blue-800">Add a new crop</button> to get started.
                        </td>
                    </tr>
                `;
                
                document.getElementById('add-crop-empty').addEventListener('click', () => {
                    document.getElementById('add-crop-btn').click();
                });
                
                return;
            }
            
            tableBody.innerHTML = crops.map(crop => `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <div class="ml-4">
                                <div class="font-medium text-gray-900">${crop.name}</div>
                                <div class="text-sm text-gray-500">${crop.variety || '-'}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${crop.field || '-'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${crop.area ? `${crop.area} ha` : '-'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${crop.plantedDate ? formatDate(crop.plantedDate) : '-'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${crop.harvestDate ? formatDate(crop.harvestDate) : '-'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(crop.status)}">
                            ${getStatusText(crop.status)}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="progress-bar ${crop.status}">
                            <div class="progress-fill" style="width: ${getProgressPercentage(crop)}%"></div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button data-id="${crop.id}" class="view-crop text-blue-600 hover:text-blue-900 mr-3" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button data-id="${crop.id}" class="edit-crop text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button data-id="${crop.id}" class="delete-crop text-red-600 hover:text-red-900" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            
            // Add event listeners to action buttons
            addCropActionListeners();
        }
        
        // Render card view
        function renderCardView(crops = null) {
            const cardView = document.getElementById('card-view');
            
            if (!crops) {
                // If no crops provided, use the current paginated crops
                const startIdx = (currentPage - 1) * itemsPerPage;
                crops = currentCrops.slice(startIdx, startIdx + itemsPerPage);
            }
            
            if (crops.length === 0) {
                cardView.innerHTML = `
                    <div class="sm:col-span-3 text-center py-10">
                        <i class="fas fa-seedling text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500">No crops found.</p>
                        <button id="add-crop-empty-card" class="mt-3 text-blue-600 hover:text-blue-800">Add a new crop</button> to get started.
                    </div>
                `;
                
                document.getElementById('add-crop-empty-card').addEventListener('click', () => {
                    document.getElementById('add-crop-btn').click();
                });
                
                return;
            }
            
            cardView.innerHTML = crops.map(crop => `
                <div class="crop-card bg-white overflow-hidden shadow rounded-lg">
                    <div class="h-40 bg-gray-100 relative">
                        <img src="https://source.unsplash.com/random/300x200/?${encodeURIComponent(crop.name)}" alt="${crop.name}" class="w-full h-full object-cover">
                        <div class="absolute top-2 right-2">
                            <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full ${getStatusClass(crop.status)}">
                                ${getStatusText(crop.status)}
                            </span>
                        </div>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">${crop.name}</h3>
                            <span class="text-sm text-gray-500">${crop.area} ha</span>
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            ${crop.variety || 'No variety'} • ${crop.field || 'No field'}
                        </div>
                        
                        <div class="mt-4">
                            <div class="flex justify-between text-sm text-gray-500">
                                <span>Planted: ${crop.plantedDate ? formatDate(crop.plantedDate) : '-'}</span>
                                <span>Harvest: ${crop.harvestDate ? formatDate(crop.harvestDate) : '-'}</span>
                            </div>
                            <div class="mt-1 progress-bar ${crop.status}">
                                <div class="progress-fill" style="width: ${getProgressPercentage(crop)}%"></div>
                            </div>
                            <div class="mt-1 flex justify-between text-xs text-gray-500">
                                <span>Planted</span>
                                <span>${getProgressText(crop)}</span>
                                <span>${crop.harvestDate ? 'Harvested' : 'Harvest'}</span>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex justify-between">
                            <button data-id="${crop.id}" class="view-crop inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-eye mr-1"></i> View
                            </button>
                            <div>
                                <button data-id="${crop.id}" class="edit-crop inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                                <button data-id="${crop.id}" class="delete-crop ml-2 inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <i class="fas fa-trash mr-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Add event listeners to action buttons
            addCropActionListeners();
        }
        
        // Add event listeners to crop action buttons
        function addCropActionListeners() {
            // View buttons
            document.querySelectorAll('.view-crop').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const id = parseInt(e.currentTarget.getAttribute('data-id'));
                    await showCropDetails(id);
                });
            });
            
            // Edit buttons
            document.querySelectorAll('.edit-crop').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const id = parseInt(e.currentTarget.getAttribute('data-id'));
                    openEditModal(id);
                });
            });
            
            // Delete buttons
            document.querySelectorAll('.delete-crop').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const id = parseInt(e.currentTarget.getAttribute('data-id'));
                    const crop = await getCrop(id);
                    
                    if (crop && confirm(`Are you sure you want to delete ${crop.name}? This action cannot be undone.`)) {
                        try {
                            await deleteCrop(id);
                            
                            // Add activity log
                            await addActivity({
                                type: 'crop_deleted',
                                description: `Deleted crop: ${crop.name}`,
                                date: new Date().toISOString().split('T')[0],
                                user: '<?= $current_user['name'] ?>'
                            });
                            
                            showSuccessToast('Crop deleted successfully');
                            await loadCrops();
                            await updateSummaryCards();
                            await updateYieldChart();
                        } catch (error) {
                            console.error('Error deleting crop:', error);
                            showErrorToast('Failed to delete crop');
                        }
                    }
                });
            });
        }
        
        // Open edit modal with crop data
        async function openEditModal(id) {
            const crop = await getCrop(id);
            
            if (crop) {
                document.getElementById('modal-title').textContent = 'Edit Crop';
                document.getElementById('crop-id').value = crop.id;
                document.getElementById('crop-name').value = crop.name;
                document.getElementById('crop-variety').value = crop.variety || '';
                document.getElementById('crop-field').value = crop.field || '';
                document.getElementById('crop-area').value = crop.area || '';
                document.getElementById('crop-status').value = crop.status || 'growing';
                document.getElementById('crop-yield').value = crop.yield || '';
                document.getElementById('crop-planted-date').value = crop.plantedDate || '';
                document.getElementById('crop-harvest-date').value = crop.harvestDate || '';
                document.getElementById('crop-notes').value = crop.notes || '';
                
                document.getElementById('crop-modal').classList.remove('hidden');
            }
        }
        
        // Add this to your existing setupUI() function
        document.getElementById('crop-image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('image-upload-area').classList.add('hidden');
                    document.getElementById('image-preview').classList.remove('hidden');
                    document.getElementById('preview-image').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('remove-image').addEventListener('click', function() {
            document.getElementById('crop-image').value = '';
            document.getElementById('image-upload-area').classList.remove('hidden');
            document.getElementById('image-preview').classList.add('hidden');
        });

        // Update the showCropDetails function with timeline support
        async function showCropDetails(id) {
            const crop = await getCrop(id);
            if (!crop) return;
            
            // Get activities for this crop
            const activities = await getCropActivities(crop.name);
            
            // Set modal data
            document.getElementById('crop-details-modal').setAttribute('data-crop-id', crop.id);
            document.getElementById('crop-details-title').textContent = crop.name;
            document.getElementById('crop-details-subtitle').textContent = `${crop.variety ? crop.variety + ' • ' : ''}${crop.field || 'No field specified'}`;
            
            // Set basic info
            document.getElementById('crop-details-name').textContent = crop.name;
            document.getElementById('crop-details-variety').textContent = crop.variety || '-';
            document.getElementById('crop-details-field').textContent = crop.field || '-';
            document.getElementById('crop-details-area').textContent = crop.area ? `${crop.area} ha` : '-';
            
            // Set status with colored badge
            const statusElement = document.getElementById('crop-details-status');
            statusElement.textContent = getStatusText(crop.status);
            statusElement.className = 'mt-1 text-sm font-medium ' + getStatusClass(crop.status).replace('text-xs', 'text-sm') + ' px-2 py-1 rounded-full inline-block';
            
            document.getElementById('crop-details-yield').textContent = crop.yield ? `${crop.yield} kg/ha` : '-';
            document.getElementById('crop-details-planted-date').textContent = crop.plantedDate ? formatDate(crop.plantedDate) : '-';
            document.getElementById('crop-details-harvest-date').textContent = crop.harvestDate ? formatDate(crop.harvestDate) : '-';
            document.getElementById('crop-details-notes').textContent = crop.notes || 'No notes available';
            
            // Set image (use placeholder if no image)
            const imageUrl = crop.image || `https://source.unsplash.com/random/300x300/?${encodeURIComponent(crop.name)},agriculture`;
            document.getElementById('crop-details-image').src = imageUrl;
            
            // Update timeline
            updateCropTimeline(crop);
            
            // Set activities
            const activitiesContainer = document.getElementById('crop-activities');
            if (activities.length === 0) {
                activitiesContainer.innerHTML = `
                    <div class="text-center py-4 text-sm text-gray-500">
                        No activities recorded for this crop
                    </div>
                `;
            } else {
                activitiesContainer.innerHTML = activities.map(activity => `
                    <div class="flex items-start">
                        <div class="flex-shrink-0 pt-1">
                            <div class="h-8 w-8 rounded-full flex items-center justify-center ${getActivityIconColor(activity.type)}">
                                <i class="fas ${getActivityIcon(activity.type)} text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">${activity.user || 'System'}</p>
                            <p class="text-sm text-gray-600">${activity.description}</p>
                            <p class="text-xs text-gray-400 mt-1">${formatDateTime(activity.date)}</p>
                        </div>
                    </div>
                `).join('');
            }
            
            // Show modal
            document.getElementById('crop-details-modal').classList.remove('hidden');
        }

        function updateCropTimeline(crop) {
            const plantedDate = crop.plantedDate ? new Date(crop.plantedDate) : null;
            const harvestDate = crop.harvestDate ? new Date(crop.harvestDate) : null;
            const today = new Date();
            
            // Set timeline dates
            document.getElementById('timeline-planted').textContent = plantedDate ? formatDate(plantedDate) : '-';
            document.getElementById('timeline-harvest').textContent = harvestDate ? formatDate(harvestDate) : '-';
            
            // Calculate progress
            let progress = 0;
            let statusText = getStatusText(crop.status);
            let daysText = 'N/A';
            
            if (plantedDate && harvestDate) {
                const totalDays = Math.round((harvestDate - plantedDate) / (1000 * 60 * 60 * 24));
                const daysPassed = Math.round((today - plantedDate) / (1000 * 60 * 60 * 24));
                
                if (today >= harvestDate) {
                    progress = 100;
                    daysText = `Harvested (${totalDays} days)`;
                } else if (today <= plantedDate) {
                    progress = 0;
                    daysText = `Planted (0 of ${totalDays} days)`;
                } else {
                    progress = Math.min(100, Math.round((daysPassed / totalDays) * 100));
                    daysText = `${daysPassed} of ${totalDays} days (${progress}%)`;
                }
            }
            
            // Update timeline elements
            document.getElementById('timeline-progress').style.width = `${progress}%`;
            document.getElementById('timeline-days').textContent = daysText;
            document.getElementById('timeline-status').textContent = statusText;
            
            // Update status color
            const statusElement = document.getElementById('timeline-status');
            statusElement.className = `text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full ${getStatusColorClass(crop.status)}`;
            
            // Update progress bar color
            document.getElementById('timeline-progress').className = `shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center ${getStatusProgressClass(crop.status)}`;
        }

        // Helper functions for activities
        function getActivityIcon(type) {
            switch(type) {
                case 'crop_added': return 'fa-seedling';
                case 'crop_updated': return 'fa-edit';
                case 'crop_harvested': return 'fa-wheat-awn';
                case 'crop_deleted': return 'fa-trash';
                default: return 'fa-info-circle';
            }
        }

        function getActivityIconColor(type) {
            switch(type) {
                case 'crop_added': return 'bg-green-500';
                case 'crop_updated': return 'bg-blue-500';
                case 'crop_harvested': return 'bg-yellow-500';
                case 'crop_deleted': return 'bg-red-500';
                default: return 'bg-gray-500';
            }
        }

        function getStatusColorClass(status) {
            switch(status) {
                case 'growing': return 'text-green-800 bg-green-100';
                case 'harvested': return 'text-blue-800 bg-blue-100';
                case 'planned': return 'text-yellow-800 bg-yellow-100';
                case 'problem': return 'text-red-800 bg-red-100';
                default: return 'text-gray-800 bg-gray-100';
            }
        }

        function getStatusProgressClass(status) {
            switch(status) {
                case 'growing': return 'bg-green-500';
                case 'harvested': return 'bg-blue-500';
                case 'planned': return 'bg-yellow-500';
                case 'problem': return 'bg-red-500';
                default: return 'bg-gray-500';
            }
        }

        function formatDateTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Get activities for a specific crop
        async function getCropActivities(cropName) {
            const activities = await getAll(STORES.ACTIVITY);
            return activities.filter(activity => 
                activity.description.includes(cropName)
            ).sort((a, b) => new Date(b.date) - new Date(a.date));
        }
        
        // Update summary cards
        async function updateSummaryCards() {
            const crops = await getAll(STORES.CROPS);
            
            // Total crops
            document.getElementById('total-crops-count').textContent = crops.length;
            
            // Growing crops
            const growingCrops = crops.filter(crop => crop.status === 'growing');
            document.getElementById('growing-crops-count').textContent = growingCrops.length;
            
            // Harvested crops
            const harvestedCrops = crops.filter(crop => crop.status === 'harvested');
            document.getElementById('harvested-crops-count').textContent = harvestedCrops.length;
            
            // Problem crops
            const problemCrops = crops.filter(crop => crop.status === 'problem');
            document.getElementById('problem-crops-count').textContent = problemCrops.length;
        }
        
        // Initialize yield chart
        function initYieldChart() {
            const ctx = document.getElementById('yieldChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Expected Yield (kg/ha)',
                        data: [],
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Yield (kg/ha)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw} kg/ha`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Update yield chart with data
        async function updateYieldChart() {
            const crops = await getAll(STORES.CROPS);
            const ctx = document.getElementById('yieldChart').getContext('2d');
            
            // Group by crop name and calculate average yield
            const yieldData = {};
            crops.forEach(crop => {
                if (crop.yield) {
                    if (!yieldData[crop.name]) {
                        yieldData[crop.name] = {
                            total: 0,
                            count: 0
                        };
                    }
                    yieldData[crop.name].total += crop.yield;
                    yieldData[crop.name].count++;
                }
            });
            
            // Prepare chart data
            const labels = Object.keys(yieldData);
            const data = labels.map(name => Math.round(yieldData[name].total / yieldData[name].count));
            
            // Update chart
            const chart = Chart.getChart(ctx);
            if (chart) {
                chart.data.labels = labels;
                chart.data.datasets[0].data = data;
                chart.update();
            }
        }
        
        // Helper function to get progress percentage
        function getProgressPercentage(crop) {
            if (crop.status === 'harvested') return 100;
            if (crop.status === 'planned') return 0;
            
            if (crop.plantedDate && crop.harvestDate) {
                const planted = new Date(crop.plantedDate);
                const harvest = new Date(crop.harvestDate);
                const today = new Date();
                
                if (today >= harvest) return 100;
                if (today <= planted) return 0;
                
                const totalDays = harvest - planted;
                const daysPassed = today - planted;
                return Math.min(100, Math.round((daysPassed / totalDays) * 100));
            }
            
            return crop.status === 'problem' ? 50 : 30; // Default progress if dates not set
        }
        
        // Helper function to get progress text
        function getProgressText(crop) {
            if (crop.status === 'harvested') return 'Harvested';
            if (crop.status === 'planned') return 'Planned';
            
            if (crop.plantedDate && crop.harvestDate) {
                const planted = new Date(crop.plantedDate);
                const harvest = new Date(crop.harvestDate);
                const today = new Date();
                
                if (today >= harvest) return 'Ready for harvest';
                if (today <= planted) return 'Planted';
                
                const totalDays = Math.round((harvest - planted) / (1000 * 60 * 60 * 24));
                const daysPassed = Math.round((today - planted) / (1000 * 60 * 60 * 24));
                return `${daysPassed} of ${totalDays} days`;
            }
            
            return crop.status === 'problem' ? 'Needs attention' : 'Growing';
        }
        
        // Helper function to get status class
        function getStatusClass(status) {
            switch(status) {
                case 'growing':
                    return 'status-growing';
                case 'harvested':
                    return 'status-harvested';
                case 'planned':
                    return 'status-planned';
                case 'problem':
                    return 'status-problem';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        }
        
        // Helper function to get status text
        function getStatusText(status) {
            switch(status) {
                case 'growing':
                    return 'Growing';
                case 'harvested':
                    return 'Harvested';
                case 'planned':
                    return 'Planned';
                case 'problem':
                    return 'Needs Attention';
                default:
                    return status;
            }
        }
        
        // Helper function to format dates
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }
        
        // Show success toast
        function showSuccessToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg flex items-center';
            toast.innerHTML = `
                <i class="fas fa-check-circle mr-2"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Show error toast
        function showErrorToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg flex items-center';
            toast.innerHTML = `
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // CRUD operations for crops
        function addCrop(crop) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.CROPS], 'readwrite');
                const store = transaction.objectStore(STORES.CROPS);
                
                const request = store.add(crop);
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = (event) => reject(event.target.error);
            });
        }
        
        function getCrop(id) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.CROPS], 'readonly');
                const store = transaction.objectStore(STORES.CROPS);
                
                const request = store.get(id);
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = (event) => reject(event.target.error);
            });
        }
        
        function getAll(storeName, indexName = null) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([storeName], 'readonly');
                const store = transaction.objectStore(storeName);
                
                let request;
                if (indexName) {
                    const index = store.index(indexName);
                    request = index.getAll();
                } else {
                    request = store.getAll();
                }
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = (event) => reject(event.target.error);
            });
        }
        
        function updateCrop(id, updates) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.CROPS], 'readwrite');
                const store = transaction.objectStore(STORES.CROPS);
                
                const getRequest = store.get(id);
                
                getRequest.onsuccess = () => {
                    const data = getRequest.result;
                    if (data) {
                        const updatedData = { ...data, ...updates };
                        const putRequest = store.put(updatedData);
                        
                        putRequest.onsuccess = () => resolve(putRequest.result);
                        putRequest.onerror = (event) => reject(event.target.error);
                    } else {
                        reject('Record not found');
                    }
                };
                
                getRequest.onerror = (event) => reject(event.target.error);
            });
        }
        
        function deleteCrop(id) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.CROPS], 'readwrite');
                const store = transaction.objectStore(STORES.CROPS);
                
                const request = store.delete(id);
                
                request.onsuccess = () => resolve(true);
                request.onerror = (event) => reject(event.target.error);
            });
        }
        
        // Add activity log
        function addActivity(activity) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.ACTIVITY], 'readwrite');
                const store = transaction.objectStore(STORES.ACTIVITY);
                
                const request = store.add(activity);
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = (event) => reject(event.target.error);
            });
        }
    </script>
</body>
</html>