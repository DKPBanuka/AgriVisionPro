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

// Fetch task analytics data
$taskData = [
    'status' => [
        'completed' => 78,
        'in_progress' => 15,
        'overdue' => 7,
        'not_started' => 10
    ],
    'completion_rate' => 78,
    'avg_completion_time' => '2.5 days',
    'trend' => 'up',
    'by_priority' => [
        'high' => 25,
        'medium' => 45,
        'low' => 30
    ],
    'by_assignee' => [
        ['name' => 'John Doe', 'completed' => 32, 'overdue' => 2],
        ['name' => 'Jane Smith', 'completed' => 28, 'overdue' => 3],
        ['name' => 'Robert Johnson', 'completed' => 18, 'overdue' => 2]
    ],
    'recent_tasks' => [
        ['id' => 101, 'title' => 'Harvest Field B', 'due_date' => '2023-06-15', 'status' => 'completed', 'priority' => 'high'],
        ['id' => 102, 'title' => 'Fertilize Field A', 'due_date' => '2023-06-18', 'status' => 'in_progress', 'priority' => 'medium'],
        ['id' => 103, 'title' => 'Vaccinate Cattle', 'due_date' => '2023-06-10', 'status' => 'overdue', 'priority' => 'high'],
        ['id' => 104, 'title' => 'Order Feed Supplies', 'due_date' => '2023-06-20', 'status' => 'not_started', 'priority' => 'medium'],
        ['id' => 105, 'title' => 'Repair Irrigation System', 'due_date' => '2023-06-12', 'status' => 'completed', 'priority' => 'low']
    ],
    'time_series' => [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        'completed' => [45, 52, 60, 65, 70, 78],
        'created' => [50, 55, 58, 62, 68, 75]
    ]
];
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriVision Pro | Task Analytics</title>
    <link rel="icon" href="./../images/logo.png" type="image/png">
    <link href="./../dist/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Include all the styles from your analytics.php file here */
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
        .status-completed {
            color: #10B981;
            background-color: #ECFDF5;
        }
        .status-in_progress {
            color: #3B82F6;
            background-color: #EFF6FF;
        }
        .status-overdue {
            color: #EF4444;
            background-color: #FEE2E2;
        }
        .status-not_started {
            color: #6B7280;
            background-color: #F3F4F6;
        }
        .priority-high {
            color: #EF4444;
            background-color: #FEE2E2;
        }
        .priority-medium {
            color: #F59E0B;
            background-color: #FEF3C7;
        }
        .priority-low {
            color: #10B981;
            background-color: #ECFDF5;
        }
        .analytics-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .trend-up {
            color: #10B981;
        }
        .trend-down {
            color: #EF4444;
        }
        .trend-neutral {
            color: #6B7280;
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
                            <input id="search-input" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search tasks..." type="search">
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

            <!-- Main Navigation -->
            <nav class=" md:flex space-x-6">
                <a href="/AgriVisionPro/analytics.php" 
                    class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                        <i class="fas fa-home mr-2 transition-all duration-300 group-hover:text-blue-600"></i>
                        <span class="relative">
                           Home
                            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                        </span>
                        <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-blue-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                <a href="analystic/tasks-analytics.php" 
                class="nav-item active relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                    <i class="fas fa-tasks mr-2 text-blue-600"></i>
                    <span class="relative text-blue-600">
                        Task Analytics
                        <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-blue-400 via-blue-600 to-blue-400"></span>
                </a>
                
                <a href="analystic/crop-analytics.php" 
                class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                    <i class="fas fa-leaf mr-2 transition-all duration-300 group-hover:text-green-600"></i>
                    <span class="relative">
                        Crop Analytics
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-600 transition-all duration-300 group-hover:w-full"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-green-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="analystic/livestock-analytics.php" 
                class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                    <i class="fas fa-dog mr-2 transition-all duration-300 group-hover:text-green-600"></i>
                    <span class="relative">
                        Livestock Analytics
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-600 transition-all duration-300 group-hover:w-full"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-green-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="analystic/inventory-analytics.php" 
                class="nav-item relative inline-flex items-center px-3 py-2 text-sm font-medium group transition-all duration-300">
                    <i class="fas fa-boxes mr-2 transition-all duration-300 group-hover:text-purple-600"></i>
                    <span class="relative">
                        Inventory Analytics
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-purple-600 transition-all duration-300 group-hover:w-full"></span>
                    </span>
                    <span class="absolute inset-x-1 -bottom-px h-px bg-gradient-to-r from-transparent via-purple-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                </a>
                
                <a href="analystic/financial-analytics.php" 
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
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">Task Analytics</h1>
                    <p class="text-gray-600 mt-2">Detailed insights into task completion, performance, and trends</p>
                </div>
                
                <!-- KPI Cards Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <!-- Completion Rate -->
                    <div class="analytics-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Completion Rate</p>
                                <p class="mt-1 text-3xl font-semibold text-gray-900"><?= $taskData['completion_rate'] ?>%</p>
                                <div class="mt-2 flex items-center">
                                    <span class="text-sm font-medium <?= $taskData['trend'] === 'up' ? 'text-green-600' : ($taskData['trend'] === 'down' ? 'text-red-600' : 'text-gray-500') ?>">
                                        <?= $taskData['trend'] === 'up' ? '5.2% increase' : ($taskData['trend'] === 'down' ? '3.8% decrease' : 'No change') ?>
                                    </span>
                                    <svg class="w-4 h-4 ml-1 <?= $taskData['trend'] === 'up' ? 'text-green-600' : ($taskData['trend'] === 'down' ? 'text-red-600' : 'text-gray-500') ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $taskData['trend'] === 'up' ? 'M5 15l7-7 7 7' : ($taskData['trend'] === 'down' ? 'M19 9l-7 7-7-7' : 'M8 12h8') ?>" />
                                    </svg>
                                </div>
                            </div>
                            <div class="p-3 rounded-full bg-blue-50 text-blue-600">
                                <i class="fas fa-check-circle text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Average Completion Time -->
                    <div class="analytics-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Avg Completion Time</p>
                                <p class="mt-1 text-3xl font-semibold text-gray-900"><?= $taskData['avg_completion_time'] ?></p>
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        1.2 days faster than last month
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 rounded-full bg-green-50 text-green-600">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Task Status Distribution -->
                    <div class="analytics-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Task Status</p>
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">Completed</span>
                                        <span class="text-sm font-medium text-gray-900"><?= $taskData['status']['completed'] ?> <span class="text-gray-500">/ 100</span></span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: <?= $taskData['status']['completed'] ?>%"></div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">In Progress</span>
                                        <span class="text-sm font-medium text-gray-900"><?= $taskData['status']['in_progress'] ?> <span class="text-gray-500">/ 100</span></span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: <?= $taskData['status']['in_progress'] ?>%"></div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">Overdue</span>
                                        <span class="text-sm font-medium text-gray-900"><?= $taskData['status']['overdue'] ?> <span class="text-gray-500">/ 100</span></span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-red-500 h-2 rounded-full" style="width: <?= $taskData['status']['overdue'] ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Priority Distribution -->
                    <div class="analytics-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Priority Distribution</p>
                                <div class="mt-2 flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        High: <?= $taskData['by_priority']['high'] ?>%
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Medium: <?= $taskData['by_priority']['medium'] ?>%
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Low: <?= $taskData['by_priority']['low'] ?>%
                                    </span>
                                </div>
                                <div class="mt-3 h-40">
                                    <canvas id="priorityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Task Completion Trend -->
                    <div class="analytics-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Task Completion Trend</h2>
                            <div class="relative">
                                <select class="appearance-none bg-gray-50 border border-gray-300 text-gray-700 py-1 px-3 pr-8 rounded-md leading-tight focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option>Last 6 Months</option>
                                    <option>Last Year</option>
                                    <option>Last 3 Years</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="h-80">
                            <canvas id="completionTrendChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Assignee Performance -->
                    <div class="analytics-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Assignee Performance</h2>
                            <div class="relative">
                                <select class="appearance-none bg-gray-50 border border-gray-300 text-gray-700 py-1 px-3 pr-8 rounded-md leading-tight focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option>All Assignees</option>
                                    <option>This Month</option>
                                    <option>Last Month</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="h-80">
                            <canvas id="assigneeChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Tasks and Status Distribution -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Status Distribution -->
                    <div class="analytics-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Status Distribution</h2>
                            <button class="text-sm text-blue-600 hover:text-blue-500">View All</button>
                        </div>
                        <div class="h-80">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Recent Tasks -->
                    <div class="analytics-card bg-white rounded-lg shadow p-6 lg:col-span-2">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Recent Tasks</h2>
                            <button class="text-sm text-blue-600 hover:text-blue-500">View All</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($taskData['recent_tasks'] as $task): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($task['title']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500"><?= date('M j, Y', strtotime($task['due_date'])) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-<?= $task['status'] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full priority-<?= $task['priority'] ?>">
                                                <?= ucfirst($task['priority']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Time Tracking -->
                <div class="analytics-card bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Time Tracking</h2>
                        <div class="flex space-x-2">
                            <button class="px-3 py-1 bg-blue-100 text-blue-700 rounded-md text-sm hover:bg-blue-200">This Week</button>
                            <button class="px-3 py-1 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">Last Week</button>
                            <button class="px-3 py-1 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">This Month</button>
                        </div>
                    </div>
                    <div class="h-96">
                        <canvas id="timeTrackingChart"></canvas>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript for Charts -->
    <script>
        // Initialize all charts when the DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Priority Distribution Chart
            const priorityCtx = document.getElementById('priorityChart').getContext('2d');
            const priorityChart = new Chart(priorityCtx, {
                type: 'doughnut',
                data: {
                    labels: ['High', 'Medium', 'Low'],
                    datasets: [{
                        data: [
                            <?= $taskData['by_priority']['high'] ?>,
                            <?= $taskData['by_priority']['medium'] ?>,
                            <?= $taskData['by_priority']['low'] ?>
                        ],
                        backgroundColor: [
                            '#EF4444',
                            '#F59E0B',
                            '#10B981'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    },
                    cutout: '70%'
                }
            });
            
            // Task Completion Trend Chart
            const trendCtx = document.getElementById('completionTrendChart').getContext('2d');
            const trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($taskData['time_series']['labels']) ?>,
                    datasets: [
                        {
                            label: 'Tasks Completed',
                            data: <?= json_encode($taskData['time_series']['completed']) ?>,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Tasks Created',
                            data: <?= json_encode($taskData['time_series']['created']) ?>,
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
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
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Assignee Performance Chart
            const assigneeCtx = document.getElementById('assigneeChart').getContext('2d');
            const assigneeChart = new Chart(assigneeCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($taskData['by_assignee'], 'name')) ?>,
                    datasets: [
                        {
                            label: 'Completed',
                            data: <?= json_encode(array_column($taskData['by_assignee'], 'completed')) ?>,
                            backgroundColor: '#10B981',
                            borderRadius: 4
                        },
                        {
                            label: 'Overdue',
                            data: <?= json_encode(array_column($taskData['by_assignee'], 'overdue')) ?>,
                            backgroundColor: '#EF4444',
                            borderRadius: 4
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
                        x: {
                            stacked: true,
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Status Distribution Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: ['Completed', 'In Progress', 'Overdue', 'Not Started'],
                    datasets: [{
                        data: [
                            <?= $taskData['status']['completed'] ?>,
                            <?= $taskData['status']['in_progress'] ?>,
                            <?= $taskData['status']['overdue'] ?>,
                            <?= $taskData['status']['not_started'] ?>
                        ],
                        backgroundColor: [
                            '#10B981',
                            '#3B82F6',
                            '#EF4444',
                            '#6B7280'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
            
            // Time Tracking Chart
            const timeCtx = document.getElementById('timeTrackingChart').getContext('2d');
            const timeChart = new Chart(timeCtx, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [
                        {
                            label: 'Planned Hours',
                            data: [8, 8, 8, 8, 8, 4, 0],
                            backgroundColor: '#E5E7EB',
                            borderRadius: 4
                        },
                        {
                            label: 'Actual Hours',
                            data: [7.5, 8.2, 6.8, 9.1, 7.5, 3.5, 0],
                            backgroundColor: '#3B82F6',
                            borderRadius: 4
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
                        x: {
                            stacked: true,
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Hours'
                            }
                        }
                    }
                }
            });
        });
        
        // Sidebar toggle functionality
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        });
        
        // User menu dropdown
        document.getElementById('user-menu').addEventListener('click', function() {
            document.getElementById('user-menu-dropdown').classList.toggle('hidden');
        });
        
        // Close dropdowns when clicking outside
        window.addEventListener('click', function(event) {
            if (!event.target.matches('#user-menu') && !event.target.closest('#user-menu-dropdown')) {
                const dropdown = document.getElementById('user-menu-dropdown');
                if (!dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                }
            }
        });
    </script>
</body>
</html>