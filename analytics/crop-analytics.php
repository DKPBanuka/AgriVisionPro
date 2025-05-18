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
    'profile_picture' => $_SESSION['profile_picture'] ?? ''
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

// Get analytics type from URL
$analyticsType = isset($_GET['type']) ? $_GET['type'] : 'crop';
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriVision Pro | Crop Analytics</title>
    <link rel="icon" href="./../images/logo.png" type="image/png">
    <link href="./../dist/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        :root {
            --sidebar-width: 16rem;
            --sidebar-collapsed-width: 5rem;
            --transition-speed: 0.3s;
        }
        
        .sidebar-transition {
            transition: all var(--transition-speed) ease;
        }
        
        .sidebar-collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar-expanded {
            width: var(--sidebar-width);
        }
        
        .main-content-expanded {
            margin-left: var(--sidebar-width);
        }
        
        .main-content-collapsed {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        .sidebar-item-text {
            opacity: 1;
            transition: opacity var(--transition-speed) ease;
        }
        
        .sidebar-collapsed .sidebar-item-text {
            opacity: 0;
            width: 0;
            position: absolute;
        }
        
        .sidebar-tooltip {
            @apply invisible absolute bg-gray-800 text-white text-sm rounded py-1 px-2 left-full ml-2 whitespace-nowrap;
        }
        
        .sidebar-collapsed .sidebar-item:hover .sidebar-tooltip {
            @apply visible z-50;
        }
        
        .dark-mode {
            background-color: #1a202c;
            color: #f7fafc;
        }
        
        .dark-mode .bg-white {
            background-color: #2d3748;
        }
        
        .dark-mode .text-gray-800 {
            color: #f7fafc;
        }
        
        .dark-mode .text-gray-500 {
            color: #a0aec0;
        }
        
        .progress-ring__circle {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        
        .dropdown-enter {
            opacity: 0;
            transform: translateY(-10px);
        }
        
        .dropdown-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: all 0.2s ease;
        }
        
        .dropdown-exit {
            opacity: 1;
            transform: translateY(0);
        }
        
        .dropdown-exit-active {
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.2s ease;
        }
        
        .analytics-card {
            transition: all 0.3s ease;
            @apply hover:shadow-xl hover:-translate-y-1;
        }
        
        .toggle-checkbox:checked {
            @apply right-0 border-green-400;
            right: 0;
            border-color: #68D391;
        }
        
        .toggle-checkbox:checked + .toggle-label {
            @apply bg-green-400;
            background-color: #68D391;
        }
    </style>
</head>
<body class="h-full overflow-hidden">
    <!-- App Container -->
    <div class="flex h-full">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar-expanded sidebar-transition fixed h-full bg-gradient-to-b from-blue-900 to-blue-800 text-white shadow-lg flex flex-col z-50">
            <div class="p-4 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center">
                        <img src="./../images/logo5.png" alt="App Logo" class="h-10 w-10 object-contain">
                    </div>
                    <h1 class="text-xl font-bold whitespace-nowrap">AgriVision Pro</h1>
                </div>
                <button id="sidebar-toggle" class="text-blue-200 hover:text-white focus:outline-none">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
            
            <nav class="flex-grow pt-2 overflow-y-auto">
                <div class="px-2 space-y-1">
                    <a href="/AgriVisionPro/dashboard.php" class="sidebar-item flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group">
                        <div class="flex items-center">
                            <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="sidebar-item-text">Dashboard</span>
                        </div>
                        <div class="sidebar-tooltip">Dashboard</div>
                    </a>
                    <a href="/AgriVisionPro/crops.php" class="sidebar-item flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group">
                        <div class="flex items-center">
                            <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                            <span class="sidebar-item-text">Crop Management</span>
                        </div>
                        <div class="sidebar-tooltip">Crop Management</div>
                    </a>
                    <a href="/AgriVisionPro/livestock.php" class="sidebar-item flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group">
                        <div class="flex items-center">
                            <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span class="sidebar-item-text">Livestock</span>
                        </div>
                        <div class="sidebar-tooltip">Livestock</div>
                    </a>
                    <a href="/AgriVisionPro/inventory.php" class="sidebar-item flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group">
                        <div class="flex items-center">
                            <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span class="sidebar-item-text">Inventory</span>
                        </div>
                        <div class="sidebar-tooltip">Inventory</div>
                    </a>
                    <a href="/AgriVisionPro/tasks.php" class="sidebar-item flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group">
                        <div class="flex items-center">
                            <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="sidebar-item-text">Tasks</span>
                        </div>
                        <div class="sidebar-tooltip">Tasks</div>
                    </a>
                </div>
                
                <div class="mt-4 pt-4 border-t border-blue-700">
                    <div class="px-3">
                        <h3 class="text-xs font-semibold text-blue-300 uppercase tracking-wider mb-1 sidebar-item-text">Analytics</h3>
                        <div class="space-y-1">
                            <a href="/AgriVisionPro/analytics/crop-analytics.php" class="sidebar-item flex items-center px-3 py-2 rounded-lg bg-blue-500 bg-opacity-30 text-sm text-white-100 hover:text-white group">
                                <div class="flex items-center">
                                    <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="sidebar-item-text">Crop Analytics</span>
                                </div>
                                <div class="sidebar-tooltip">Crop Analytics</div>
                            </a>
                            <a href="/AgriVisionPro/analytics/livestock-analytics.php" class="sidebar-item flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-sm text-blue-100 hover:text-white group">
                                <div class="flex items-center">
                                    <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="sidebar-item-text">Livestock Analytics</span>
                                </div>
                                <div class="sidebar-tooltip">Livestock Analytics</div>
                            </a>
                            <a href="/AgriVisionPro/analytics/inventory-analytics.php" class="sidebar-item flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-sm text-blue-100 hover:text-white group">
                                <div class="flex items-center">
                                    <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="sidebar-item-text">Inventory Analytics</span>
                                </div>
                                <div class="sidebar-tooltip">Inventory Analytics</div>
                            </a>
                            <a href="/AgriVisionPro/analytics/financial-analytics.php" class="sidebar-item flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-sm text-blue-100 hover:text-white group">
                                <div class="flex items-center">
                                    <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="sidebar-item-text">Financial Analytics</span>
                                </div>
                                <div class="sidebar-tooltip">Financial Analytics</div>
                            </a>
                            <a href="/AgriVisionPro/analytics/tasks-analytics.php" class="sidebar-item flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-sm text-blue-100 hover:text-white group">
                                <div class="flex items-center">
                                    <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="sidebar-item-text">Tasks Analytics</span>
                                </div>
                                <div class="sidebar-tooltip">Tasks Analytics</div>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-blue-700">
                    <div class="px-2 space-y-1">
                        <a href="/AgriVisionPro/settings.php" class="sidebar-item flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group">
                            <div class="flex items-center">
                                <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c-.94 1.543.826 3.31 2.37 2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="sidebar-item-text">Settings</span>
                            </div>
                            <div class="sidebar-tooltip">Settings</div>
                        </a>
                    </div>
                </div>
            </nav>
            
            <!-- Collapse button at bottom -->
            <div class="p-4 border-t border-blue-700">
                <button id="sidebar-collapse-btn" class="w-full flex items-center justify-center text-blue-200 hover:text-white focus:outline-none">
                    <i class="fas fa-chevron-left mr-2 sidebar-item-text"></i>
                    <span class="sidebar-item-text">Collapse</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div id="main-content" class="main-content-expanded sidebar-transition flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <div class="bg-white shadow-sm z-40">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <!-- Left side (empty for now) -->
                        <div class="flex"></div>
                        
                        <!-- Right side -->
                        <div class="flex items-center">
                            <!-- Dark mode toggle -->
                            <div class="flex items-center mr-4">
                                <span class="mr-2 text-sm text-gray-600 dark-mode-toggle-text">Light</span>
                                <div class="relative inline-block w-10 mr-2 align-middle select-none">
                                    <input type="checkbox" id="dark-mode-toggle" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                                    <label for="dark-mode-toggle" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                                <span class="ml-2 text-sm text-gray-600 dark-mode-toggle-text">Dark</span>
                            </div>
                            
                            <!-- Notifications -->
                            <button class="p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 relative">
                                <span class="sr-only">View notifications</span>
                                <i class="fas fa-bell h-6 w-6"></i>
                                <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
                            </button>
                            
                            <!-- User dropdown -->
                            <div class="ml-4 relative" x-data="{ open: false }">
                                <div>
                                    <button @click="open = !open" type="button" class="max-w-xs flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                        <span class="sr-only">Open user menu</span>
                                        <?php if (!empty($current_user['profile_picture'])): ?>
                                            <img class="h-8 w-8 rounded-full object-cover" src="../../uploads/profiles/<?= htmlspecialchars($current_user['profile_picture']) ?>" alt="Profile Picture">
                                        <?php else: ?>
                                            <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                                                <?= $current_user['initials'] ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="ml-2 text-sm font-medium text-gray-700 hidden md:inline"><?= htmlspecialchars($current_user['name']) ?></span>
                                        <svg class="ml-1 h-4 w-4 text-gray-500 hidden md:inline" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Dropdown menu -->
                                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                                    <div class="px-4 py-3 border-b">
                                        <?php if (!empty($current_user['profile_picture'])): ?>
                                            <img src="../../uploads/profiles/<?= htmlspecialchars($current_user['profile_picture']) ?>" alt="Profile Picture" class="h-10 w-10 rounded-full object-cover mb-2 mx-auto">
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
                                    <a href="/AgriVisionPro/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1" id="user-menu-item-0">
                                        <i class="fas fa-user-circle mr-2"></i> Your Profile
                                    </a>
                                    <a href="/AgriVisionPro/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1" id="user-menu-item-1">
                                        <i class="fas fa-cog mr-2"></i> Settings
                                    </a>
                                    <a href="/AgriVisionPro/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1" id="user-menu-item-2">
                                        <i class="fas fa-sign-out-alt mr-2"></i> Sign out
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50 dark:bg-gray-800 transition-colors duration-300">
                <div class="max-w-7xl mx-auto">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Crop Analytics</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Data-driven insights for better crop management</p>
                        </div>
                        <div class="flex space-x-2">
                            <select id="time-period" class="block pl-3 pr-10 py-2 text-base border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                <option value="current">Current Season</option>
                                <option value="last-month">Last Month</option>
                                <option value="last-year">Last Year</option>
                                <option value="all">All Time</option>
                            </select>
                            <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-download mr-2"></i> Export
                            </button>
                        </div>
                    </div>
                    
                    <!-- Analytics Tabs -->
                    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                        <nav class="-mb-px flex space-x-8">
                            <a href="analytics.php?type=crop" class="<?= $analyticsType === 'crop' ? 'border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Crop Analytics</a>
                            <a href="analytics.php?type=field" class="<?= $analyticsType === 'field' ? 'border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Field Analytics</a>
                            <a href="analytics.php?type=resource" class="<?= $analyticsType === 'resource' ? 'border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Resource Usage</a>
                            <a href="analytics.php?type=profitability" class="<?= $analyticsType === 'profitability' ? 'border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Profitability</a>
                        </nav>
                    </div>

                    <?php if ($analyticsType === 'crop'): ?>
                        <!-- Crop Analytics Content -->
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Summary Cards -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                                <!-- Total Crops Card -->
                                <div class="analytics-card bg-white dark:bg-gray-700 overflow-hidden shadow rounded-lg">
                                    <div class="p-5">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                                <i class="fas fa-leaf text-white text-xl"></i>
                                            </div>
                                            <div class="ml-5 w-0 flex-1">
                                                <dl>
                                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Crops</dt>
                                                    <dd>
                                                        <div class="text-2xl font-semibold text-gray-900 dark:text-white" id="total-crops-count">0</div>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                                                <span>Last 30 days</span>
                                                <span class="text-green-600 dark:text-green-400 font-medium">+5.2%</span>
                                            </div>
                                            <div class="mt-1">
                                                <div class="h-1 w-full bg-gray-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-indigo-500 rounded-full" style="width: 72%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Growing Crops Card -->
                                <div class="analytics-card bg-white dark:bg-gray-700 overflow-hidden shadow rounded-lg">
                                    <div class="p-5">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                                <i class="fas fa-seedling text-white text-xl"></i>
                                            </div>
                                            <div class="ml-5 w-0 flex-1">
                                                <dl>
                                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Growing</dt>
                                                    <dd>
                                                        <div class="text-2xl font-semibold text-gray-900 dark:text-white" id="growing-crops-count">0</div>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                                                <span>Last 30 days</span>
                                                <span class="text-green-600 dark:text-green-400 font-medium">+12.7%</span>
                                            </div>
                                            <div class="mt-1">
                                                <div class="h-1 w-full bg-gray-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-green-500 rounded-full" style="width: 58%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Harvested Crops Card -->
                                <div class="analytics-card bg-white dark:bg-gray-700 overflow-hidden shadow rounded-lg">
                                    <div class="p-5">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                                <i class="fas fa-wheat-awn text-white text-xl"></i>
                                            </div>
                                            <div class="ml-5 w-0 flex-1">
                                                <dl>
                                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Harvested</dt>
                                                    <dd>
                                                        <div class="text-2xl font-semibold text-gray-900 dark:text-white" id="harvested-crops-count">0</div>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                                                <span>Last 30 days</span>
                                                <span class="text-green-600 dark:text-green-400 font-medium">+8.3%</span>
                                            </div>
                                            <div class="mt-1">
                                                <div class="h-1 w-full bg-gray-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-yellow-500 rounded-full" style="width: 45%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Problem Crops Card -->
                                <div class="analytics-card bg-white dark:bg-gray-700 overflow-hidden shadow rounded-lg">
                                    <div class="p-5">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                                <i class="fas fa-bug text-white text-xl"></i>
                                            </div>
                                            <div class="ml-5 w-0 flex-1">
                                                <dl>
                                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Needs Attention</dt>
                                                    <dd>
                                                        <div class="text-2xl font-semibold text-gray-900 dark:text-white" id="problem-crops-count">0</div>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                                                <span>Last 30 days</span>
                                                <span class="text-red-600 dark:text-red-400 font-medium">-3.9%</span>
                                            </div>
                                            <div class="mt-1">
                                                <div class="h-1 w-full bg-gray-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-red-500 rounded-full" style="width: 18%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Yield Analysis Section -->
                            <div class="bg-white dark:bg-gray-700 shadow rounded-lg overflow-hidden">
                                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-600 flex justify-between items-center">
                                    <div>
                                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Yield Analysis</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Expected and actual yield comparison</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button class="inline-flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-calendar-alt mr-2"></i> Date Range
                                        </button>
                                        <button class="inline-flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-filter mr-2"></i> Filter
                                        </button>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Expected Yield by Crop</h4>
                                            <div id="expectedYieldChart" class="w-full h-64"></div>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Actual vs Expected Yield</h4>
                                            <div id="yieldComparisonChart" class="w-full h-64"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Crop Performance Table -->
                            <div class="bg-white dark:bg-gray-700 shadow rounded-lg overflow-hidden">
                                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-600 flex justify-between items-center">
                                    <div>
                                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Crop Performance</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Detailed performance metrics by crop type</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="relative">
                                            <input type="text" placeholder="Search crops..." class="pl-8 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                        </div>
                                        <button class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-sliders-h mr-2"></i> Columns
                                        </button>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                        <thead class="bg-gray-50 dark:bg-gray-600">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Crop</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Area</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Avg Yield</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Avg Growth Days</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Success Rate</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="crop-performance-body" class="bg-white dark:bg-gray-700 divide-y divide-gray-200 dark:divide-gray-600">
                                            <tr>
                                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                                    <i class="fas fa-spinner fa-spin mr-2"></i> Loading data...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-600 flex items-center justify-between">
                                    <div class="flex-1 flex justify-between sm:hidden">
                                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Previous </a>
                                        <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Next </a>
                                    </div>
                                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                Showing <span class="font-medium">1</span> to <span class="font-medium">5</span> of <span class="font-medium">12</span> results
                                            </p>
                                        </div>
                                        <div>
                                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"> <span class="sr-only">Previous</span> <i class="fas fa-chevron-left"></i> </a>
                                                <a href="#" aria-current="page" class="z-10 bg-blue-50 border-blue-500 text-blue-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium"> 1 </a>
                                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium"> 2 </a>
                                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium"> 3 </a>
                                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"> <span class="sr-only">Next</span> <i class="fas fa-chevron-right"></i> </a>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <?php elseif ($analyticsType === 'field'): ?>
                        <!-- Field Analytics Content -->
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Field Performance Cards -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                <!-- Cards will be loaded dynamically -->
                            </div>
                            
                            <!-- Field Comparison Chart -->
                            <div class="bg-white dark:bg-gray-700 shadow rounded-lg overflow-hidden">
                                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-600">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Field Performance Comparison</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Yield and efficiency across different fields</p>
                                </div>
                                <div class="p-4">
                                    <div id="fieldComparisonChart" class="w-full h-96"></div>
                                </div>
                            </div>
                        </div>
                    
                    <?php elseif ($analyticsType === 'resource'): ?>
                        <!-- Resource Usage Content -->
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Resource Usage Overview -->
                            <div class="bg-white dark:bg-gray-700 shadow rounded-lg overflow-hidden">
                                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-600">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Resource Usage</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Water, fertilizer, and labor utilization</p>
                                </div>
                                <div class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Water Usage</h4>
                                            <div id="waterUsageChart" class="w-full h-64"></div>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fertilizer Usage</h4>
                                            <div id="fertilizerUsageChart" class="w-full h-64"></div>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Labor Hours</h4>
                                            <div id="laborUsageChart" class="w-full h-64"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <?php elseif ($analyticsType === 'profitability'): ?>
                        <!-- Profitability Content -->
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Profitability Overview -->
                            <div class="bg-white dark:bg-gray-700 shadow rounded-lg overflow-hidden">
                                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-600">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Crop Profitability</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Revenue, costs, and profit margins</p>
                                </div>
                                <div class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Profit by Crop</h4>
                                            <div id="profitByCropChart" class="w-full h-96"></div>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cost Breakdown</h4>
                                            <div id="costBreakdownChart" class="w-full h-96"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        // Database configuration
        const DB_NAME = 'AgriVisionProDB';
        const DB_VERSION = 6;
        const STORES = {
            CROPS: 'crops',
            ACTIVITY: 'activity',
            FIELDS: 'fields',
            RESOURCES: 'resources'
        };

        let db;
        let isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        let darkMode = localStorage.getItem('darkMode') === 'true';
        
        // Initialize the application
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                // Initialize UI state
                initSidebar();
                initDarkMode();
                
                // Initialize database
                await initDB();
                
                // Load initial data based on current tab
                const analyticsType = '<?= $analyticsType ?>';
                
                // Always load summary data
                await updateSummaryCards();
                
                // Load specific analytics based on tab
                switch(analyticsType) {
                    case 'crop':
                        initCropAnalytics();
                        break;
                    case 'field':
                        initFieldAnalytics();
                        break;
                    case 'resource':
                        initResourceAnalytics();
                        break;
                    case 'profitability':
                        initProfitabilityAnalytics();
                        break;
                }
                
            } catch (error) {
                console.error('Initialization error:', error);
                showErrorToast('Failed to initialize analytics');
            }
        });

        // Initialize sidebar state
        function initSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const collapseBtn = document.getElementById('sidebar-collapse-btn');
            
            if (isSidebarCollapsed) {
                sidebar.classList.remove('sidebar-expanded');
                sidebar.classList.add('sidebar-collapsed');
                mainContent.classList.remove('main-content-expanded');
                mainContent.classList.add('main-content-collapsed');
                toggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            }
            
            // Toggle sidebar
            toggleBtn.addEventListener('click', function() {
                isSidebarCollapsed = !isSidebarCollapsed;
                localStorage.setItem('sidebarCollapsed', isSidebarCollapsed);
                
                if (isSidebarCollapsed) {
                    sidebar.classList.remove('sidebar-expanded');
                    sidebar.classList.add('sidebar-collapsed');
                    mainContent.classList.remove('main-content-expanded');
                    mainContent.classList.add('main-content-collapsed');
                    toggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
                } else {
                    sidebar.classList.remove('sidebar-collapsed');
                    sidebar.classList.add('sidebar-expanded');
                    mainContent.classList.remove('main-content-collapsed');
                    mainContent.classList.add('main-content-expanded');
                    toggleBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
                }
            });
            
            // Collapse button
            collapseBtn.addEventListener('click', function() {
                isSidebarCollapsed = true;
                localStorage.setItem('sidebarCollapsed', isSidebarCollapsed);
                
                sidebar.classList.remove('sidebar-expanded');
                sidebar.classList.add('sidebar-collapsed');
                mainContent.classList.remove('main-content-expanded');
                mainContent.classList.add('main-content-collapsed');
                toggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            });
        }
        
        // Initialize dark mode
        function initDarkMode() {
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            const darkModeTexts = document.querySelectorAll('.dark-mode-toggle-text');
            
            if (darkMode) {
                document.body.classList.add('dark-mode');
                darkModeToggle.checked = true;
                darkModeTexts[0].classList.add('text-gray-400');
                darkModeTexts[1].classList.add('text-gray-200');
            } else {
                document.body.classList.remove('dark-mode');
                darkModeToggle.checked = false;
                darkModeTexts[0].classList.remove('text-gray-400');
                darkModeTexts[1].classList.remove('text-gray-200');
            }
            
            darkModeToggle.addEventListener('change', function() {
                darkMode = this.checked;
                localStorage.setItem('darkMode', darkMode);
                
                if (darkMode) {
                    document.body.classList.add('dark-mode');
                    darkModeTexts[0].classList.add('text-gray-400');
                    darkModeTexts[1].classList.add('text-gray-200');
                } else {
                    document.body.classList.remove('dark-mode');
                    darkModeTexts[0].classList.remove('text-gray-400');
                    darkModeTexts[1].classList.remove('text-gray-200');
                }
            });
        }

        // Initialize database
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
                        cropsStore.createIndex('status', 'status', { unique: false });
                    }
                    
                    if (!db.objectStoreNames.contains(STORES.ACTIVITY)) {
                        db.createObjectStore(STORES.ACTIVITY, { keyPath: 'id', autoIncrement: true });
                    }
                    
                    if (!db.objectStoreNames.contains(STORES.FIELDS)) {
                        const fieldsStore = db.createObjectStore(STORES.FIELDS, { keyPath: 'id', autoIncrement: true });
                        fieldsStore.createIndex('name', 'name', { unique: true });
                    }
                    
                    if (!db.objectStoreNames.contains(STORES.RESOURCES)) {
                        const resourcesStore = db.createObjectStore(STORES.RESOURCES, { keyPath: 'id', autoIncrement: true });
                        resourcesStore.createIndex('type', 'type', { unique: false });
                        resourcesStore.createIndex('cropId', 'cropId', { unique: false });
                    }
                };
            });
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
        
        // Initialize crop analytics
        async function initCropAnalytics() {
            // Initialize charts
            initExpectedYieldChart();
            initYieldComparisonChart();
            
            // Load crop performance data
            await loadCropPerformanceData();
        }
        
        // Initialize expected yield chart with ApexCharts
        function initExpectedYieldChart() {
            const options = {
                series: [{
                    name: 'Expected Yield',
                    data: []
                }],
                chart: {
                    type: 'bar',
                    height: '100%',
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: true,
                            reset: true
                        }
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: false,
                        columnWidth: '55%',
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: [],
                    labels: {
                        style: {
                            colors: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Yield (kg/ha)',
                        style: {
                            color: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    },
                    labels: {
                        style: {
                            colors: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    }
                },
                fill: {
                    opacity: 1,
                    colors: ['#3B82F6']
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + " kg/ha"
                        }
                    },
                    theme: darkMode ? 'dark' : 'light'
                },
                grid: {
                    borderColor: darkMode ? '#4B5563' : '#E5E7EB'
                }
            };

            const chart = new ApexCharts(document.querySelector("#expectedYieldChart"), options);
            chart.render();
            
            // Load data for the chart
            loadExpectedYieldData(chart);
        }
        
        // Load expected yield data
        async function loadExpectedYieldData(chart) {
            try {
                const crops = await getAll(STORES.CROPS);
                
                // Group by crop name and calculate average expected yield
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
                const categories = Object.keys(yieldData);
                const data = categories.map(name => Math.round(yieldData[name].total / yieldData[name].count));
                
                // Update chart
                chart.updateOptions({
                    series: [{
                        name: 'Expected Yield',
                        data: data
                    }],
                    xaxis: {
                        categories: categories
                    }
                });
            } catch (error) {
                console.error('Error loading yield data:', error);
                showErrorToast('Failed to load yield data');
            }
        }
        
        // Initialize yield comparison chart with ApexCharts
        function initYieldComparisonChart() {
            const options = {
                series: [
                    {
                        name: 'Expected Yield',
                        data: []
                    },
                    {
                        name: 'Actual Yield',
                        data: []
                    }
                ],
                chart: {
                    type: 'bar',
                    height: '100%',
                    stacked: false,
                    toolbar: {
                        show: true
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        endingShape: 'rounded'
                    },
                },
                colors: ['#3B82F6', '#10B981'],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: [],
                    labels: {
                        style: {
                            colors: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Yield (kg/ha)',
                        style: {
                            color: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    },
                    labels: {
                        style: {
                            colors: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    }
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + " kg/ha"
                        }
                    },
                    theme: darkMode ? 'dark' : 'light'
                },
                legend: {
                    position: 'top',
                    labels: {
                        colors: darkMode ? '#E5E7EB' : '#6B7280'
                    }
                },
                grid: {
                    borderColor: darkMode ? '#4B5563' : '#E5E7EB'
                }
            };

            const chart = new ApexCharts(document.querySelector("#yieldComparisonChart"), options);
            chart.render();
            
            // Load data for the chart
            loadYieldComparisonData(chart);
        }
        
        // Load yield comparison data
        async function loadYieldComparisonData(chart) {
            try {
                const crops = await getAll(STORES.CROPS);
                
                // Filter for harvested crops (these would have actual yields in a real system)
                const harvestedCrops = crops.filter(crop => crop.status === 'harvested');
                
                // Prepare chart data
                const categories = harvestedCrops.map(crop => crop.name);
                const expectedYields = harvestedCrops.map(crop => crop.yield || 0);
                
                // In a real system, you would have actual yields stored
                // For this example, we'll simulate actual yields by adding some variance
                const actualYields = expectedYields.map(yield => {
                    // Random variance between -20% and +10%
                    const variance = (Math.random() * 0.3 - 0.2) * yield;
                    return Math.round(yield + variance);
                });
                
                // Update chart
                chart.updateOptions({
                    series: [
                        {
                            name: 'Expected Yield',
                            data: expectedYields
                        },
                        {
                            name: 'Actual Yield',
                            data: actualYields
                        }
                    ],
                    xaxis: {
                        categories: categories
                    }
                });
            } catch (error) {
                console.error('Error loading yield comparison data:', error);
                showErrorToast('Failed to load yield comparison data');
            }
        }
        
        // Load crop performance data for the table
        async function loadCropPerformanceData() {
            try {
                const crops = await getAll(STORES.CROPS);
                
                // Group by crop name and calculate performance metrics
                const performanceData = {};
                crops.forEach(crop => {
                    if (!performanceData[crop.name]) {
                        performanceData[crop.name] = {
                            totalArea: 0,
                            totalYield: 0,
                            count: 0,
                            successCount: 0
                        };
                    }
                    
                    performanceData[crop.name].totalArea += crop.area || 0;
                    performanceData[crop.name].totalYield += crop.yield || 0;
                    performanceData[crop.name].count++;
                    
                    // Consider a crop successful if it was harvested (in a real system, you might have more criteria)
                    if (crop.status === 'harvested') {
                        performanceData[crop.name].successCount++;
                    }
                });
                
                // Prepare table data
                const tableBody = document.getElementById('crop-performance-body');
                tableBody.innerHTML = Object.keys(performanceData).map(cropName => {
                    const data = performanceData[cropName];
                    const avgYield = data.count > 0 ? Math.round(data.totalYield / data.count) : 0;
                    const successRate = data.count > 0 ? Math.round((data.successCount / data.count) * 100) : 0;
                    
                    return `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-500 flex items-center justify-center text-gray-500 dark:text-gray-200">
                                        <i class="fas fa-leaf"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium text-gray-900 dark:text-white">${cropName}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                ${data.totalArea.toFixed(2)} ha
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                ${avgYield} kg/ha
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                ${Math.floor(Math.random() * 30) + 60} days
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5 mr-2">
                                        <div class="h-2.5 rounded-full ${successRate > 70 ? 'bg-green-600' : successRate > 40 ? 'bg-yellow-500' : 'bg-red-600'}" style="width: ${successRate}%"></div>
                                    </div>
                                    <span class="text-sm font-medium ${successRate > 70 ? 'text-green-600 dark:text-green-400' : successRate > 40 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'}">${successRate}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3">
                                    <i class="fas fa-chart-line"></i>
                                </button>
                                <button class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
                
            } catch (error) {
                console.error('Error loading crop performance data:', error);
                showErrorToast('Failed to load crop performance data');
            }
        }
        
        // Initialize field analytics
        async function initFieldAnalytics() {
            // Initialize field comparison chart
            initFieldComparisonChart();
            
            // Load field performance cards
            await loadFieldPerformanceCards();
        }
        
        // Initialize field comparison chart with ApexCharts
        function initFieldComparisonChart() {
            const options = {
                series: [
                    {
                        name: 'Average Yield',
                        type: 'column',
                        data: []
                    },
                    {
                        name: 'Efficiency Score',
                        type: 'line',
                        data: []
                    }
                ],
                chart: {
                    height: '100%',
                    type: 'line',
                    stacked: false,
                    toolbar: {
                        show: true
                    }
                },
                stroke: {
                    width: [0, 4]
                },
                colors: ['#8B5CF6', '#F59E0B'],
                plotOptions: {
                    bar: {
                        columnWidth: '50%'
                    }
                },
                xaxis: {
                    categories: [],
                    labels: {
                        style: {
                            colors: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    }
                },
                yaxis: [
                    {
                        seriesName: 'Average Yield',
                        title: {
                            text: 'Yield (kg/ha)',
                            style: {
                                color: darkMode ? '#E5E7EB' : '#6B7280'
                            }
                        },
                        labels: {
                            style: {
                                colors: darkMode ? '#E5E7EB' : '#6B7280'
                            }
                        }
                    },
                    {
                        seriesName: 'Efficiency Score',
                        opposite: true,
                        title: {
                            text: 'Efficiency (%)',
                            style: {
                                color: darkMode ? '#E5E7EB' : '#6B7280'
                            }
                        },
                        min: 0,
                        max: 100,
                        labels: {
                            style: {
                                colors: darkMode ? '#E5E7EB' : '#6B7280'
                            },
                            formatter: function(val) {
                                return val + "%";
                            }
                        }
                    }
                ],
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (y) {
                            if (typeof y !== "undefined") {
                                return y.toFixed(0) + (this.seriesIndex === 0 ? " kg/ha" : "%");
                            }
                            return y;
                        }
                    },
                    theme: darkMode ? 'dark' : 'light'
                },
                legend: {
                    position: 'top',
                    labels: {
                        colors: darkMode ? '#E5E7EB' : '#6B7280'
                    }
                },
                grid: {
                    borderColor: darkMode ? '#4B5563' : '#E5E7EB'
                }
            };

            const chart = new ApexCharts(document.querySelector("#fieldComparisonChart"), options);
            chart.render();
            
            // Load data for the chart
            loadFieldComparisonData(chart);
        }
        
        // Load field comparison data
        async function loadFieldComparisonData(chart) {
            try {
                // In a real system, you would have fields and their performance data
                // For this example, we'll simulate some data
                const fieldNames = ['North Field', 'South Field', 'East Field', 'West Field', 'Central Field'];
                const avgYields = fieldNames.map(() => Math.floor(Math.random() * 3000) + 2000);
                const efficiencyScores = fieldNames.map(() => Math.floor(Math.random() * 40) + 60);
                
                // Update chart
                chart.updateOptions({
                    series: [
                        {
                            name: 'Average Yield',
                            type: 'column',
                            data: avgYields
                        },
                        {
                            name: 'Efficiency Score',
                            type: 'line',
                            data: efficiencyScores
                        }
                    ],
                    xaxis: {
                        categories: fieldNames
                    }
                });
            } catch (error) {
                console.error('Error loading field comparison data:', error);
                showErrorToast('Failed to load field comparison data');
            }
        }
        
        // Load field performance cards
        async function loadFieldPerformanceCards() {
            try {
                // In a real system, you would fetch field data from the database
                // For this example, we'll simulate some data
                const fieldData = [
                    {
                        name: 'North Field',
                        area: '5.2 ha',
                        currentCrop: 'Rice',
                        avgYield: '3200 kg/ha',
                        efficiency: '78%',
                        status: 'good'
                    },
                    {
                        name: 'South Field',
                        area: '3.8 ha',
                        currentCrop: 'Corn',
                        avgYield: '4500 kg/ha',
                        efficiency: '85%',
                        status: 'excellent'
                    },
                    {
                        name: 'East Field',
                        area: '4.5 ha',
                        currentCrop: 'Wheat',
                        avgYield: '2800 kg/ha',
                        efficiency: '72%',
                        status: 'average'
                    }
                ];
                
                // Update the cards container
                const cardsContainer = document.querySelector('.grid.grid-cols-1.gap-5.sm\\:grid-cols-2.lg\\:grid-cols-3');
                if (cardsContainer) {
                    cardsContainer.innerHTML = fieldData.map(field => {
                        let statusColor, statusIcon;
                        if (field.status === 'excellent') {
                            statusColor = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                            statusIcon = 'fa-check-circle';
                        } else if (field.status === 'good') {
                            statusColor = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
                            statusIcon = 'fa-thumbs-up';
                        } else {
                            statusColor = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
                            statusIcon = 'fa-exclamation-triangle';
                        }
                        
                        return `
                            <div class="analytics-card bg-white dark:bg-gray-700 overflow-hidden shadow rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">${field.name}</h3>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">${field.area} • ${field.currentCrop}</p>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColor}">
                                            <i class="fas ${statusIcon} mr-1"></i> ${field.status}
                                        </span>
                                    </div>
                                    <div class="mt-4 grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Avg Yield</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">${field.avgYield}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Efficiency</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">${field.efficiency}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error('Error loading field performance cards:', error);
                showErrorToast('Failed to load field performance data');
            }
        }
        
        // Initialize resource analytics
        async function initResourceAnalytics() {
            // Initialize resource usage charts
            initWaterUsageChart();
            initFertilizerUsageChart();
            initLaborUsageChart();
        }
        
        // Initialize water usage chart with ApexCharts
        function initWaterUsageChart() {
            const options = {
                series: [35, 25, 20, 15, 5],
                labels: ['Rice', 'Corn', 'Wheat', 'Vegetables', 'Other'],
                chart: {
                    type: 'donut',
                    height: '100%'
                },
                colors: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444'],
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Usage',
                                    color: darkMode ? '#E5E7EB' : '#6B7280',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + '%';
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    position: 'right',
                    labels: {
                        colors: darkMode ? '#E5E7EB' : '#6B7280'
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return value + '%';
                        }
                    },
                    theme: darkMode ? 'dark' : 'light'
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            const chart = new ApexCharts(document.querySelector("#waterUsageChart"), options);
            chart.render();
        }
        
        // Initialize fertilizer usage chart with ApexCharts
        function initFertilizerUsageChart() {
            const options = {
                series: [{
                    name: 'Nitrogen',
                    data: [120, 190, 150, 200, 180, 170]
                }, {
                    name: 'Phosphorus',
                    data: [80, 100, 90, 110, 95, 105]
                }, {
                    name: 'Potassium',
                    data: [70, 90, 80, 100, 85, 95]
                }],
                chart: {
                    type: 'bar',
                    height: '100%',
                    stacked: false,
                    toolbar: {
                        show: true
                    }
                },
                colors: ['#3B82F6', '#10B981', '#F59E0B'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        endingShape: 'rounded'
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    labels: {
                        style: {
                            colors: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Quantity (kg)',
                        style: {
                            color: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    },
                    labels: {
                        style: {
                            colors: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    }
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + " kg"
                        }
                    },
                    theme: darkMode ? 'dark' : 'light'
                },
                legend: {
                    position: 'top',
                    labels: {
                        colors: darkMode ? '#E5E7EB' : '#6B7280'
                    }
                },
                grid: {
                    borderColor: darkMode ? '#4B5563' : '#E5E7EB'
                }
            };

            const chart = new ApexCharts(document.querySelector("#fertilizerUsageChart"), options);
            chart.render();
        }
        
        // Initialize labor usage chart with ApexCharts
        function initLaborUsageChart() {
            const options = {
                series: [{
                    name: 'Planting',
                    data: [120, 150, 180, 200, 170, 140]
                }, {
                    name: 'Maintenance',
                    data: [80, 90, 100, 110, 105, 95]
                }, {
                    name: 'Harvesting',
                    data: [40, 60, 80, 120, 150, 130]
                }],
                chart: {
                    type: 'area',
                    height: '100%',
                    stacked: false,
                    toolbar: {
                        show: true
                    }
                },
                colors: ['#3B82F6', '#10B981', '#F59E0B'],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    labels: {
                        style: {
                            colors: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Hours',
                        style: {
                            color: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    },
                    labels: {
                        style: {
                            colors: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + " hours"
                        }
                    },
                    theme: darkMode ? 'dark' : 'light'
                },
                legend: {
                    position: 'top',
                    labels: {
                        colors: darkMode ? '#E5E7EB' : '#6B7280'
                    }
                },
                grid: {
                    borderColor: darkMode ? '#4B5563' : '#E5E7EB'
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3,
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector("#laborUsageChart"), options);
            chart.render();
        }
        
        // Initialize profitability analytics
        async function initProfitabilityAnalytics() {
            // Initialize profitability charts
            initProfitByCropChart();
            initCostBreakdownChart();
        }
        
        // Initialize profit by crop chart with ApexCharts
        function initProfitByCropChart() {
            const options = {
                series: [{
                    name: 'Revenue',
                    type: 'column',
                    data: [12000, 18000, 10000, 15000, 13000]
                }, {
                    name: 'Cost',
                    type: 'column',
                    data: [8000, 12000, 7000, 9000, 8500]
                }, {
                    name: 'Profit',
                    type: 'line',
                    data: [4000, 6000, 3000, 6000, 4500]
                }],
                chart: {
                    height: '100%',
                    type: 'line',
                    stacked: false,
                    toolbar: {
                        show: true
                    }
                },
                stroke: {
                    width: [0, 0, 4]
                },
                colors: ['#10B981', '#EF4444', '#3B82F6'],
                plotOptions: {
                    bar: {
                        columnWidth: '50%'
                    }
                },
                xaxis: {
                    categories: ['Rice', 'Corn', 'Wheat', 'Tomatoes', 'Potatoes'],
                    labels: {
                        style: {
                            colors: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Amount ($)',
                        style: {
                            color: darkMode ? '#E5E7EB' : '#6B7280'
                        }
                    },
                    labels: {
                        style: {
                            colors: darkMode ? '#E5E7EB' : '#6B7280'
                        },
                        formatter: function(val) {
                            return '$' + val.toLocaleString();
                        }
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (y) {
                            if (typeof y !== "undefined") {
                                return '$' + y.toLocaleString();
                            }
                            return y;
                        }
                    },
                    theme: darkMode ? 'dark' : 'light'
                },
                legend: {
                    position: 'top',
                    labels: {
                        colors: darkMode ? '#E5E7EB' : '#6B7280'
                    }
                },
                grid: {
                    borderColor: darkMode ? '#4B5563' : '#E5E7EB'
                }
            };

            const chart = new ApexCharts(document.querySelector("#profitByCropChart"), options);
            chart.render();
        }
        
        // Initialize cost breakdown chart with ApexCharts
        function initCostBreakdownChart() {
            const options = {
                series: [15, 25, 20, 30, 5, 5],
                labels: ['Seeds', 'Fertilizers', 'Pesticides', 'Labor', 'Equipment', 'Other'],
                chart: {
                    type: 'pie',
                    height: '100%'
                },
                colors: ['#EF4444', '#3B82F6', '#F59E0B', '#10B981', '#8B5CF6', '#EC4899'],
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    color: darkMode ? '#E5E7EB' : '#6B7280',
                                    formatter: function (w) {
                                        return '100%';
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    position: 'right',
                    labels: {
                        colors: darkMode ? '#E5E7EB' : '#6B7280'
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return value + '%';
                        }
                    },
                    theme: darkMode ? 'dark' : 'light'
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            const chart = new ApexCharts(document.querySelector("#costBreakdownChart"), options);
            chart.render();
        }
        
        // Helper function to get all records from a store
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
    </script>
</body>
</html>