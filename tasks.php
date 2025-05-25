<?php
// Enable detailed error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../logs/php_errors.log');
ini_set('display_errors', 1); // Keep for initial debugging, can be turned off for production
ini_set('display_startup_errors', 1); // Keep for initial debugging
error_reporting(E_ALL);

session_start();
require_once 'includes/db_connect.php'; // Still needed for profile data
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
    <title>AgriVision Pro | Task Management</title>
    <link rel="icon" href="./images/logo1.png" type="image/png">
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
        .modal {
            z-index: 50;
        }
        .status-pending {
            color: #F59E0B;
            background-color: #FFFBEB;
        }
        .status-in-progress {
            color: #3B82F6;
            background-color: #EFF6FF;
        }
        .status-completed {
            color: #10B981;
            background-color: #ECFDF5;
        }
        .status-overdue {
            color: #EF4444;
            background-color: #FEF2F2;
        }
        .priority-high {
            color: #EF4444;
            background-color: #FEF2F2;
        }
        .priority-medium {
            color: #F59E0B;
            background-color: #FFFBEB;
        }
        .priority-low {
            color: #10B981;
            background-color: #ECFDF5;
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
        .task-card {
            transition: all 0.3s ease;
        }
        .task-card:hover {
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
        
        /* Notification styles */
        .notification-badge {
            position: absolute;
            top: -0.5rem;
            right: -0.5rem;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 9999px;
            background-color: #EF4444;
            color: white;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notification-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .notification-item:hover {
            background-color: #f9fafb;
        }
        .notification-item.unread {
            background-color: #f0f9ff;
        }
        .notification-dropdown {
            width: 350px;
            max-height: 80vh;
            overflow-y: auto;
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
    </style>
</head>
<body class="h-full overflow-hidden">
    <!-- App Container -->
    <div class="flex h-full">
        <!-- Dynamic Sidebar -->
        <aside id="sidebar" class="w-64 bg-gradient-to-b from-blue-900 to-blue-800 text-white shadow-xl h-screen flex flex-col overflow-y-auto">
            <div class="p-5 flex items-center space-x-3 flex-shrink-0 bg-gradient-to-b from-blue-900 to-blue-900 sticky top-0 z-10"> <div class="w-10 h-10 rounded-full flex items-center justify-center"> <img src="./images/logo5.png" alt="App Logo" class="h-10 w-10 object-contain"> </div>
                <h1 class="text-xl font-bold">AgriVision Pro</h1> </div>
            
            <nav class="flex-grow pt-2"> <div class="px-3 space-y-0.5"> 
                    <a href="dashboard.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>
                    <a href="crops.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        Crop Management
                    </a>
                    <a href="livestock.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Livestock
                    </a>
                    <a href="inventory.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Inventory
                    </a>
                    <a href="tasks.php" class="flex items-center px-3 py-2 rounded-lg bg-blue-500 bg-opacity-30 text-m text-white-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Tasks
                    </a>
                </div>
                
                
                <div class="mt-4 pt-4 border-t border-blue-700"> <div class="px-3"> <h3 class="text-xs font-semibold text-blue-300 uppercase tracking-wider mb-1">Analytics</h3> <div class="space-y-0.5"> <a href="/agrivisionpro/analytics/crop-analytics.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-sm text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Crop Analytics
                            </a>
                            <a href="/agrivisionpro/analytics/livestock-analytics.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-sm text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Livestock Analytics
                            </a>
                            <a href="/agrivisionpro/analytics/inventory-analytics.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-sm text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Inventory Analytics
                            </a>
                            <a href="/agrivisionpro/analytics/financial-analytics.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-sm text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Financial Analytics
                            </a>
                            <a href="/agrivisionpro/analytics/tasks-analytics.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-sm text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Tasks Analytics
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-blue-700"> <div class="px-3 space-y-0.5"> <a href="settings.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c-.94 1.543.826 3.31 2.37 2.37.996.608 2.296.07 2.572-1.065z" />
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
                        <!-- Notifications Dropdown -->
                        <div class="relative">
                            <button id="notifications-btn" class="p-1 text-gray-400 hover:text-gray-500 focus:outline-none relative" title="Notifications">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span id="notification-count" class="notification-badge hidden">0</span>
                            </button>
                            <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-md shadow-lg overflow-hidden z-50 notification-dropdown">
                                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                                    <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
                                </div>
                                <div id="notifications-list" class="divide-y divide-gray-200">
                                    <div class="text-center py-4 text-sm text-gray-500">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading notifications...
                                    </div>
                                </div>
                                <div class="px-4 py-2 border-t border-gray-200 bg-gray-50 text-center">
                                    <a href="#" class="text-xs font-medium text-blue-600 hover:text-blue-800">View all notifications</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Profile Dropdown -->
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
                        <h2 class="text-2xl font-bold text-gray-800">Task Management</h2>
                        <p class="text-sm text-gray-500">Track and manage all farm tasks and activities</p>
                    </div>
                    <button id="add-task-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i> Add New Task
                    </button>
                </div>
                
                <!-- Connection Status Indicator -->
                <div id="connection-status" class="hidden mb-4 p-2 rounded-md text-sm flex items-center">
                    <i class="fas fa-circle mr-2 text-gray-500"></i>
                    <span>Checking connection...</span>
                </div>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                    <!-- Total Tasks -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i class="fas fa-tasks text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Tasks</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="total-tasks-count">0</div>
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
                    
                    <!-- Pending -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <i class="fas fa-clock text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="pending-count">0</div>
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
                    
                    <!-- In Progress -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i class="fas fa-spinner text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="in-progress-count">0</div>
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
                    
                    <!-- Overdue -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Overdue</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="overdue-count">0</div>
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
                
                <!-- Task Status Chart -->
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Task Status Distribution</h3>
                        <p class="mt-1 text-sm text-gray-500">Number of tasks by status</p>
                    </div>
                    <div class="p-4">
                        <canvas id="statusChart" class="w-full h-64"></canvas>
                    </div>
                </div>
                
                <!-- Task Management -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center overflow-x-auto">
                        <div class="flex items-center space-x-4">
                            <div class="relative w-64">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" id="search-tasks" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search tasks...">
                            </div>
                            
                            <select id="status-filter" title="Filter by status" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="in-progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="overdue">Overdue</option>
                            </select>
                            
                            <select id="priority-filter" title="Filter by priority" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="all">All Priorities</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        
                        <div class="flex space-x-2">
                            <select id="sort-by" title="Sort tasks by criteria" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="title-asc">Title (A-Z)</option>
                                <option value="title-desc">Title (Z-A)</option>
                                <option value="due-date-asc">Due Date (Earliest)</option>
                                <option value="due-date-desc">Due Date (Latest)</option>
                                <option value="priority-asc">Priority (Low to High)</option>
                                <option value="priority-desc">Priority (High to Low)</option>
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
                            <p class="mt-2 text-sm text-gray-500">Loading tasks...</p>
                        </div>
                    </div>
                    
                    <!-- Table View -->
                    <div id="table-view" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TASK</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ASSIGNED TO</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DUE DATE</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PRIORITY</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="tasks-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Tasks will be loaded here dynamically -->
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading tasks...
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

    <!-- Add/Edit Task Modal -->
    <div id="task-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <!-- Fixed Header -->
                <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-200 sticky top-0 z-10">
                    <h3 id="modal-title" class="text-2xl font-bold text-gray-800">Add New Task</h3>
                </div>
                
                <!-- Scrollable content area -->
                <div class="max-h-[calc(100vh-200px)] overflow-y-auto px-6 py-4">
                    <form id="task-form">
                        <input type="hidden" id="task-id">
                        <div class="grid grid-cols-1 gap-y-6 gap-x-6 sm:grid-cols-6">
                            <!-- Basic Info Section -->
                            <div class="sm:col-span-6 border-b border-gray-200 pb-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Basic Information</h4>
                                <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                                    <div class="sm:col-span-6">
                                        <label for="task-title" class="block text-sm font-medium text-gray-700 flex items-center">
                                            Task Title <span class="text-red-500 ml-1">*</span>
                                        </label>
                                        <input type="text" id="task-title" required 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <div class="sm:col-span-6">
                                        <label for="task-description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <textarea id="task-description" rows="3" 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="task-assigned-to" class="block text-sm font-medium text-gray-700">Assigned To</label>
                                        <input type="text" id="task-assigned-to" 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="task-due-date" class="block text-sm font-medium text-gray-700">Due Date</label>
                                        <input type="date" id="task-due-date" 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="task-status" class="block text-sm font-medium text-gray-700">Status</label>
                                        <select id="task-status" 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="pending">Pending</option>
                                            <option value="in-progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="task-priority" class="block text-sm font-medium text-gray-700">Priority</label>
                                        <select id="task-priority" 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="high">High</option>
                                            <option value="medium">Medium</option>
                                            <option value="low">Low</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Related Items Section -->
                            <div class="sm:col-span-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Related Items</label>
                                <div class="mt-1 flex flex-wrap gap-2" id="related-items-container">
                                    <!-- Related items will be added here -->
                                </div>
                                <button type="button" id="add-related-item" class="mt-2 inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-plus mr-1"></i> Add Related Item
                                </button>
                            </div>
                            
                            <!-- Related Items Section -->
                            <div class="sm:col-span-6 border-t border-gray-200 pt-6 mt-6">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Related Items</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label for="related-item-type-select" class="block text-sm font-medium text-gray-700">Item Type</label>
                                        <select id="related-item-type-select" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="">-- Select Type --</option>
                                            <option value="crop">Crop</option>
                                            <option value="livestock">Livestock</option>
                                            <option value="inventory">Inventory Item</option>
                                            <!-- Add other types as needed -->
                                        </select>
                                    </div>
                                    <div>
                                        <label for="related-item-id-input" class="block text-sm font-medium text-gray-700">Item ID or Name (Search coming soon)</label>
                                        <input type="text" id="related-item-id-input" placeholder="Enter ID (e.g., 123)" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <!-- Future: This input could be replaced/enhanced with a search/autocomplete component -->
                                    </div>
                                    <button type="button" id="btn-add-related-item-to-task" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <i class="fas fa-plus mr-2"></i> Add Related Item
                                    </button>
                                    <div id="current-related-items-list" class="mt-3 space-y-2">
                                        <!-- Dynamically added related items will appear here -->
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Attachments Section -->
                            <div class="sm:col-span-6 border-t border-gray-200 pt-6 mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Attachments</label>
                                <div id="existing-attachments-list" class="mb-3 space-y-2">
                                    <!-- Existing attachments (when editing) will be listed here by JS -->
                                </div>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md relative">
                                    <div id="file-upload-area" class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <label for="task-attachments" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                <span>Upload files</span>
                                                <input id="task-attachments" name="task-attachments" type="file" class="sr-only" multiple>
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PDF, images up to 10MB</p>
                                    </div>
                                    <div id="file-preview" class="hidden absolute inset-0 p-4 overflow-y-auto">
                                        <div class="grid grid-cols-1 gap-2" id="attachment-preview-list">
                                            <!-- Attachment previews will be added here -->
                                        </div>
                                        <button type="button" id="remove-attachments" class="absolute top-2 right-2 bg-white rounded-full p-1 shadow-md hover:bg-gray-100">
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
                
                <!-- Fixed Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 sticky bottom-0">
                    <button type="button" id="save-task" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Task
                    </button>
                    <button type="button" id="cancel-task" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Task Details Modal -->
    <div id="task-details-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <!-- Fixed Header -->
                <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-200 sticky top-0 z-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 id="task-details-title" class="text-2xl font-bold text-gray-800">Task Details</h3>
                            <p id="task-details-subtitle" class="mt-1 text-sm text-gray-500">Detailed information about this task</p>
                        </div>
                        <button id="close-details" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Scrollable Content Area -->
                <div class="max-h-[calc(100vh-200px)] overflow-y-auto px-6 py-4">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        
                        <!-- Left Column (Basic Info) -->
                        <div class="sm:col-span-2">
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Basic Information</h4>
                                <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Task Title</label>
                                        <p id="task-details-title-text" class="mt-1 text-sm font-medium text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Status</label>
                                        <p id="task-details-status" class="mt-1 text-sm font-medium px-2 py-1 rounded-full inline-block">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Priority</label>
                                        <p id="task-details-priority" class="mt-1 text-sm font-medium px-2 py-1 rounded-full inline-block">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Assigned To</label>
                                        <p id="task-details-assigned-to" class="mt-1 text-sm text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Due Date</label>
                                        <p id="task-details-due-date" class="mt-1 text-sm text-gray-900">-</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description Card -->
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Description</h4>
                                <p id="task-details-description" class="text-sm text-gray-900">No description available</p>
                            </div>
                            
                            <!-- Related Items Card -->
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Related Items</h4>
                                <div class="flex flex-wrap gap-2" id="task-details-related-items">
                                    <!-- Related items will be added here -->
                                    <p class="text-sm text-gray-500">No related items</p>
                                </div>
                            </div>
                            
                            <!-- Attachments Card -->
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Attachments</h4>
                                <div class="grid grid-cols-1 gap-2" id="task-details-attachments">
                                    <!-- Attachments will be added here -->
                                    <p class="text-sm text-gray-500">No attachments</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column (Timeline & Activities) -->
                        <div class="sm:col-span-2">
                            <!-- Timeline Section -->
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Task Timeline</h4>
                                <div class="space-y-4">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                            <i class="fas fa-tasks text-sm"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Created</p>
                                            <p id="task-details-created-date" class="text-sm text-gray-500">-</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                            <i class="fas fa-sync-alt text-sm"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Last Updated</p>
                                            <p id="task-details-updated-date" class="text-sm text-gray-500">-</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                                            <i class="fas fa-flag text-sm"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Status</p>
                                            <p id="task-details-status-history" class="text-sm text-gray-500">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Activities Section -->
                            <div class="border-t border-gray-200 pt-4">
                                <div class="px-5 pt-4 pb-4 bg-white sticky top-0 z-50 flex justify-center">
                                    <h4 class="text-2xl font-bold text-gray-800 mb-3">Recent Activities</h4>
                                </div>
                                <div id="task-activities" class="space-y-4">
                                    <div class="text-center py-4 text-sm text-gray-500 flex justify-center items-start">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading activities...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>   
                </div>
                
                <!-- Fixed Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 sticky bottom-0">
                    <button type="button" id="edit-task-from-details" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-edit mr-2"></i> Edit Task
                    </button>
                    <button type="button" id="close-details-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-times mr-2"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    let isOnline = navigator.onLine;
    const TASKS_API_URL = 'tasks_api.php';
    const currentUserId = <?php echo $_SESSION['user_id'] ?? 'null'; ?>;

    // State variables for Add/Edit modal
    let currentTaskRelatedItems = [];
    let attachmentsToDelete = []; // Stores IDs of attachments marked for deletion


    if (!currentUserId) {
        console.error("User ID not found. Operations will likely fail.");
        showErrorToast("User session error. Please re-login.");
    }

    // ========== Toast Notification Functions ========== //
    function showToast(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `fixed bottom-5 right-5 p-4 rounded-md shadow-lg text-white z-[1000] transition-all duration-300 ease-in-out transform translate-x-full opacity-0`;
        
        const icons = {
            info: '<i class="fas fa-info-circle mr-2"></i>',
            success: '<i class="fas fa-check-circle mr-2"></i>',
            error: '<i class="fas fa-exclamation-circle mr-2"></i>',
        };
        
        const colors = {
            info: 'bg-blue-500',
            success: 'bg-green-600',
            error: 'bg-red-600',
        };

        toast.classList.add(colors[type]);
        toast.innerHTML = `${icons[type]} ${message}`;
        
        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');
        }, 100);
        
        // Animate out and remove
        setTimeout(() => {
            toast.classList.remove('translate-x-0', 'opacity-100');
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000 + (document.querySelectorAll('[id^="toast-"]').length * 500) );
    }

    function showSuccessToast(message) { showToast(message, 'success'); }
    function showErrorToast(message) { showToast(message, 'error'); }
    function showInfoToast(message) { showToast(message, 'info'); }


    // ========== API Wrapper Function ========== //
    async function apiRequest(action, method = 'POST', data = {}) {
        let config;
        if (data instanceof FormData) { 
            if(!data.has('action')) data.append('action', action); 
            config = {
                method: method,
                body: data,
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };
        } else {
            const payload = { ...data, action: action };
            config = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            };
        }
        
        try {
            const response = await fetch(TASKS_API_URL, config);
            
            if (!response.ok) {
                let errorMsg = `HTTP error! status: ${response.status}`;
                try {
                    const errorData = await response.json();
                    errorMsg = errorData.message || errorMsg;
                } catch (e) { 
                    const textResponse = await response.text();
                    errorMsg = `HTTP error ${response.status}: ${textResponse || response.statusText}`;
                }
                console.error(`API Error (${action}): ${errorMsg}`, { status: response.status, response });
                throw new Error(errorMsg);
            }

            const result = await response.json();
            
            if (!result.success) {
                console.error(`API Action Failed (${action}): ${result.message}`, result);
                throw new Error(result.message || `API action '${action}' failed.`);
            }
            return result;
        } catch (error) {
            console.error('API Request Exception:', action, error);
            showErrorToast(error.message || 'An unexpected API error occurred.');
            throw error; 
        }
    }

    // ========== Data Functions using apiRequest ========== //
    async function addTask(formData) { 
        return await apiRequest('create_task', 'POST', formData);
    }

    async function getTask(id) {
        const result = await apiRequest('get_task_details', 'POST', { id: id });
        if (result.task) {
            result.task.related_items = Array.isArray(result.task.related_items) ? result.task.related_items : [];
            result.task.attachments = Array.isArray(result.task.attachments) ? result.task.attachments : [];
        }
        return result.task; 
    }
    
    async function getAllTasks(filters = {}) {
         const apiFilters = {
            search: filters.searchTerm || '',
            status: filters.statusFilter || 'all',
            priority: filters.priorityFilter || 'all',
            sortBy: filters.sortBy || 'createdAt-desc'
        };
        const result = await apiRequest('list_tasks', 'POST', apiFilters);
        const today = new Date().toISOString().split('T')[0];
        (result.tasks || []).forEach(task => {
            if (task.due_date && task.due_date < today && task.status !== 'completed') {
                task.is_overdue_display = true; 
            } else {
                task.is_overdue_display = false;
            }
        });
        return result.tasks || [];
    }

    async function updateTask(formData) { 
        return await apiRequest('update_task', 'POST', formData);
    }

    async function deleteTask(id) {
        return await apiRequest('delete_task', 'POST', { id: id });
    }
    
    async function getTaskStats() {
        return await apiRequest('task_stats', 'POST');
    }

    // ========== UI Functions ========== //
    function setupEventListeners() {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        if(sidebarToggle && sidebar) sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('hidden'));

        const userMenu = document.getElementById('user-menu');
        const userMenuDropdown = document.getElementById('user-menu-dropdown');
        if(userMenu && userMenuDropdown) userMenu.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenuDropdown.classList.toggle('hidden');
        });

        const notificationsBtn = document.getElementById('notifications-btn');
        const notificationsDropdown = document.getElementById('notifications-dropdown');
        if(notificationsBtn && notificationsDropdown) notificationsBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationsDropdown.classList.toggle('hidden');
            if (!notificationsDropdown.classList.contains('hidden')) {
                document.querySelectorAll('.notification-item.unread').forEach(item => item.classList.remove('unread'));
                const countEl = document.getElementById('notification-count');
                if(countEl) {
                    countEl.classList.add('hidden');
                    countEl.textContent = '0';
                }
            }
        });
        
        document.addEventListener('click', function(event) {
            if (userMenu && !userMenu.contains(event.target) && userMenuDropdown) {
                userMenuDropdown.classList.add('hidden');
            }
            if (notificationsBtn && !notificationsBtn.contains(event.target) && notificationsDropdown) {
                notificationsDropdown.classList.add('hidden');
            }
            const searchInputGlobal = document.getElementById('search-input');
            const searchResultsGlobal = document.getElementById('search-results');
            if (searchInputGlobal && !searchInputGlobal.contains(event.target) && searchResultsGlobal) {
               searchResultsGlobal.classList.add('hidden');
            }
        });

        const searchInputGlobal = document.getElementById('search-input');
        if(searchInputGlobal) searchInputGlobal.addEventListener('input', async e => {
            const searchTerm = e.target.value.trim();
            const searchResultsEl = document.getElementById('search-results');
            if (searchTerm.length > 2) {
                try {
                    const results = await apiRequest('search_tasks', 'POST', { query: searchTerm });
                    if (results.tasks && results.tasks.length > 0) {
                        searchResultsEl.innerHTML = results.tasks.map(task => `
                            <div class="search-item" data-id="${task.id}" data-type="task">
                                <div class="font-medium">${task.title}</div>
                                <div class="text-xs text-gray-500">Task</div>
                            </div>
                        `).join('');
                        searchResultsEl.classList.remove('hidden');
                    } else {
                        searchResultsEl.innerHTML = '<div class="search-item text-gray-500">No tasks found</div>';
                        searchResultsEl.classList.remove('hidden');
                    }
                } catch (error) {
                    searchResultsEl.innerHTML = '<div class="search-item text-red-500">Search error</div>';
                    searchResultsEl.classList.remove('hidden');
                }
            } else {
                if(searchResultsEl) searchResultsEl.classList.add('hidden');
            }
        });

        const searchResultsEl = document.getElementById('search-results');
        if(searchResultsEl) searchResultsEl.addEventListener('click', e => {
            const item = e.target.closest('.search-item');
            if (item && item.dataset.type === 'task') {
                showTaskDetails(parseInt(item.dataset.id));
                searchResultsEl.classList.add('hidden');
                const searchInputGlobal = document.getElementById('search-input');
                if(searchInputGlobal) searchInputGlobal.value = '';
            }
        });

        document.getElementById('add-task-btn')?.addEventListener('click', showAddTaskModal);
        document.getElementById('save-task')?.addEventListener('click', handleSaveTask);
        document.getElementById('cancel-task')?.addEventListener('click', () => document.getElementById('task-modal')?.classList.add('hidden'));
        
        document.getElementById('search-tasks')?.addEventListener('input', async e => await loadTasksTable({ searchTerm: e.target.value }));
        document.getElementById('status-filter')?.addEventListener('change', async e => await loadTasksTable({ status: e.target.value }));
        document.getElementById('priority-filter')?.addEventListener('change', async e => await loadTasksTable({ priority: e.target.value }));
        document.getElementById('sort-by')?.addEventListener('change', async e => await loadTasksTable({ sortBy: e.target.value }));
        
        document.getElementById('view-toggle')?.addEventListener('click', toggleView);
        
        document.getElementById('close-details')?.addEventListener('click', () => document.getElementById('task-details-modal')?.classList.add('hidden'));
        document.getElementById('close-details-btn')?.addEventListener('click', () => document.getElementById('task-details-modal')?.classList.add('hidden'));
        
        document.getElementById('edit-task-from-details')?.addEventListener('click', async () => {
            const taskDetailsModal = document.getElementById('task-details-modal');
            const taskId = taskDetailsModal?.getAttribute('data-task-id');
            if (taskId) {
                taskDetailsModal.classList.add('hidden');
                await showEditTaskModal(parseInt(taskId));
            }
        });
        
        window.addEventListener('online', handleConnectionChange);
        window.addEventListener('offline', handleConnectionChange);
        
        document.getElementById('task-attachments')?.addEventListener('change', handleFileUpload);
        document.getElementById('remove-attachments')?.addEventListener('click', clearFileUpload);
        
        // Related Items Event Listeners in Modal
        document.getElementById('btn-add-related-item-to-task')?.addEventListener('click', handleAddRelatedItemToList);
        document.getElementById('current-related-items-list')?.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-related-item-btn') || e.target.closest('.remove-related-item-btn')) {
                const button = e.target.closest('.remove-related-item-btn');
                const indexToRemove = parseInt(button.dataset.itemIndex);
                if (!isNaN(indexToRemove) && indexToRemove >= 0 && indexToRemove < currentTaskRelatedItems.length) {
                    currentTaskRelatedItems.splice(indexToRemove, 1);
                    displayCurrentRelatedItems();
                }
            }
        });
        document.getElementById('existing-attachments-list')?.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-existing-attachment-btn') || e.target.closest('.remove-existing-attachment-btn')) {
                const button = e.target.closest('.remove-existing-attachment-btn');
                const attachmentId = parseInt(button.dataset.attachmentId);
                if (!attachmentsToDelete.includes(attachmentId)) {
                    attachmentsToDelete.push(attachmentId);
                }
                button.parentElement.style.textDecoration = 'line-through';
                button.parentElement.style.opacity = '0.5';
                button.disabled = true;
            }
        });
    }

    function toggleView() {
        const cardView = document.getElementById('card-view');
        const tableView = document.getElementById('table-view');
        const viewToggle = document.getElementById('view-toggle');
        
        if (!cardView || !tableView || !viewToggle) return;

        if (cardView.classList.contains('hidden')) {
            cardView.classList.remove('hidden');
            tableView.classList.add('hidden');
            viewToggle.innerHTML = '<i class="fas fa-list mr-2"></i> Table View';
        } else {
            cardView.classList.add('hidden');
            tableView.classList.remove('hidden');
            viewToggle.innerHTML = '<i class="fas fa-table mr-2"></i> Card View';
        }
    }

    async function loadTasksTable(filters = {}) {
        const tableBody = document.getElementById('tasks-table-body');
        const cardView = document.getElementById('card-view');
        
        if(tableBody) tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading tasks...</td></tr>`;
        if(cardView) cardView.innerHTML = `<div class="sm:col-span-2 lg:col-span-3 text-center py-10"><i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i><p class="mt-2 text-sm text-gray-500">Loading tasks...</p></div>`;

        try {
            const effectiveFilters = {
                searchTerm: document.getElementById('search-tasks')?.value || filters.searchTerm,
                statusFilter: document.getElementById('status-filter')?.value || filters.status,
                priorityFilter: document.getElementById('priority-filter')?.value || filters.priority,
                sortBy: document.getElementById('sort-by')?.value || filters.sortBy,
            };

            const [tasks, statsResult] = await Promise.all([
                getAllTasks(effectiveFilters),
                getTaskStats()
            ]);
            
            const stats = statsResult.stats || {};

            updateSummaryCardsWithStats(stats);
            renderTasksTable(tasks);
            renderTasksCards(tasks);
            updateStatusChartWithStats(stats);

        } catch (error) {
            if(tableBody) tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Error loading tasks.</td></tr>`;
            if(cardView) cardView.innerHTML = `<div class="sm:col-span-2 lg:col-span-3 text-center py-10 text-red-500">Error loading tasks.</div>`;
            updateStatusChartWithStats({});
        }
    }
    
    function isActuallyOverdue(dueDateStr) { 
        if (!dueDateStr) return false;
        const today = new Date();
        today.setHours(0, 0, 0, 0); 
        return new Date(dueDateStr) < today;
    }


    function updateSummaryCardsWithStats(stats = {}) {
        document.getElementById('total-tasks-count').textContent = stats.total_tasks || 0;
        document.getElementById('pending-count').textContent = stats.pending_tasks || 0;
        document.getElementById('in-progress-count').textContent = stats.in_progress_tasks || 0;
        document.getElementById('overdue-count').textContent = stats.overdue_tasks || 0;
    }

    function renderTasksTable(tasks) {
        const tableBody = document.getElementById('tasks-table-body');
        if (!tableBody) return;
        
        if (!tasks || tasks.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        No tasks found. Click "Add New Task" to get started.
                    </td>
                </tr>
            `;
            return;
        }
        
        tableBody.innerHTML = tasks.map(task => {
            const displayStatus = task.is_overdue_display && task.status !== 'completed' ? 'overdue' : task.status;
            return `
            <tr class="hover:bg-gray-50" data-id="${task.id}">
                <td class="px-6 py-4">
                    <div class="font-medium text-gray-900 flex items-center">
                        ${task.title}
                    </div>
                    <div class="text-sm text-gray-500 truncate max-w-xs">${task.description || ''}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">${task.assigned_to || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div>${formatDate(task.due_date)}</div>
                    ${task.is_overdue_display && task.status !== 'completed' ? 
                        `<div class="text-xs text-red-500">${daysOverdue(task.due_date)} days overdue</div>` : ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full priority-${task.priority}">
                        ${capitalizeFirstLetter(task.priority)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-${displayStatus}">
                        ${capitalizeFirstLetter(displayStatus.replace('-', ' '))}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button class="view-btn text-blue-600 hover:text-blue-900 mr-3" title="View details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="edit-btn text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="delete-btn text-red-600 hover:text-red-900" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `}).join('');
        
        tableBody.querySelectorAll('.view-btn').forEach(btn => btn.addEventListener('click', async e => await showTaskDetails(parseInt(e.currentTarget.closest('tr').dataset.id))));
        tableBody.querySelectorAll('.edit-btn').forEach(btn => btn.addEventListener('click', async e => await showEditTaskModal(parseInt(e.currentTarget.closest('tr').dataset.id))));
        tableBody.querySelectorAll('.delete-btn').forEach(btn => btn.addEventListener('click', async e => await handleDeleteTask(parseInt(e.currentTarget.closest('tr').dataset.id))));
    }

    function renderTasksCards(tasks) {
        const cardView = document.getElementById('card-view');
        if (!cardView) return;

        if (!tasks || tasks.length === 0) {
            cardView.innerHTML = `<div class="sm:col-span-2 lg:col-span-3 text-center py-10"><i class="fas fa-tasks text-4xl text-gray-300 mb-3"></i><p class="text-gray-500">No tasks found.</p></div>`;
            return;
        }
    
        cardView.innerHTML = tasks.map(task => {
            const displayStatus = task.is_overdue_display && task.status !== 'completed' ? 'overdue' : task.status;
            return `
            <div class="task-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 hover:border-blue-300">
                <div class="p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg text-gray-800 mb-1">${task.title}</h3>
                            <p class="text-sm text-gray-600 mb-2">${task.description || 'No description'}</p>
                        </div>
                    </div>
                    <div class="flex items-center text-sm text-gray-500 mb-2">
                        <i class="fas fa-user mr-1"></i>
                        <span>${task.assigned_to || 'Unassigned'}</span>
                    </div>
                    <div class="flex items-center text-sm ${task.is_overdue_display && task.status !== 'completed' ? 'text-red-500' : 'text-gray-500'} mb-3">
                        <i class="fas fa-calendar-day mr-1"></i>
                        <span>${formatDate(task.due_date) || 'No due date'}</span>
                        ${task.is_overdue_display && task.status !== 'completed' ? 
                            `<span class="ml-2 text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">${daysOverdue(task.due_date)} days overdue</span>` : ''}
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full status-${displayStatus}">
                            ${capitalizeFirstLetter(displayStatus.replace('-', ' '))}
                        </span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full priority-${task.priority}">
                            ${capitalizeFirstLetter(task.priority)}
                        </span>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 flex justify-end space-x-2 border-t border-gray-200">
                    <button class="view-btn text-blue-600 hover:text-blue-800 p-1" data-id="${task.id}" title="View details"><i class="fas fa-eye"></i></button>
                    <button class="edit-btn text-blue-600 hover:text-blue-800 p-1" data-id="${task.id}" title="Edit"><i class="fas fa-edit"></i></button>
                    <button class="delete-btn text-red-600 hover:text-red-800 p-1" data-id="${task.id}" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        `}).join('');
    
        cardView.querySelectorAll('.view-btn').forEach(btn => btn.addEventListener('click', async e => await showTaskDetails(parseInt(e.currentTarget.dataset.id))));
        cardView.querySelectorAll('.edit-btn').forEach(btn => btn.addEventListener('click', async e => await showEditTaskModal(parseInt(e.currentTarget.dataset.id))));
        cardView.querySelectorAll('.delete-btn').forEach(btn => btn.addEventListener('click', async e => await handleDeleteTask(parseInt(e.currentTarget.dataset.id))));
    }

    function updateStatusChartWithStats(stats = {}) {
        const canvas = document.getElementById('statusChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        if (window.statusChart && typeof window.statusChart.destroy === 'function') {
            window.statusChart.destroy();
        }
        
        const parent = canvas.parentNode;
        const existingNoDataMsg = parent.querySelector('.no-data-message');
        if (existingNoDataMsg) existingNoDataMsg.remove();
        canvas.style.display = 'block';

        if (Object.keys(stats).length === 0 || !stats.total_tasks || stats.total_tasks === 0) {
             window.statusChart = null; 
             canvas.style.display = 'none';
             const noDataMsg = document.createElement('p');
             noDataMsg.className = 'text-center text-gray-500 py-10 no-data-message';
             noDataMsg.textContent = 'No task data to display chart.';
             parent.appendChild(noDataMsg);
            return;
        }
        
        window.statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Completed', 'Overdue'],
                datasets: [{
                    data: [
                        stats.pending_tasks || 0,
                        stats.in_progress_tasks || 0,
                        stats.completed_tasks || 0, 
                        stats.overdue_tasks || 0
                    ],
                    backgroundColor: ['#F59E0B', '#3B82F6', '#10B981', '#EF4444'],
                    hoverBackgroundColor: ['#D97706', '#2563EB', '#059669', '#DC2626'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function showAddTaskModal() {
        document.getElementById('modal-title').textContent = 'Add New Task';
        document.getElementById('task-id').value = '';
        document.getElementById('task-form').reset();
        currentTaskRelatedItems = []; 
        displayCurrentRelatedItems();
        attachmentsToDelete = []; 
        clearExistingAttachmentsDisplay();
        clearFileUploadDisplayOnly(); 
        document.getElementById('task-modal').classList.remove('hidden');
    }

    async function showEditTaskModal(id) {
        try {
            const task = await getTask(id);
            if (!task) throw new Error('Task not found for editing.');
            
            document.getElementById('modal-title').textContent = 'Edit Task';
            document.getElementById('task-id').value = task.id;
            document.getElementById('task-title').value = task.title || '';
            document.getElementById('task-description').value = task.description || '';
            document.getElementById('task-assigned-to').value = task.assigned_to || '';
            document.getElementById('task-due-date').value = task.due_date || '';
            document.getElementById('task-status').value = task.status || 'pending'; 
            document.getElementById('task-priority').value = task.priority || 'medium';
            
            currentTaskRelatedItems = task.related_items || [];
            displayCurrentRelatedItems();
            attachmentsToDelete = []; 
            displayExistingAttachments(task.attachments || []);
            clearFileUploadDisplayOnly(); 

            document.getElementById('task-modal').classList.remove('hidden');
        } catch (error) {
            showErrorToast('Failed to load task for editing: ' + error.message);
        }
    }

    async function showTaskDetails(id) {
        try {
            const task = await getTask(id); 
            if (!task) throw new Error('Task details not found.');
            
            const displayStatus = task.is_overdue_display && task.status !== 'completed' ? 'overdue' : task.status;

            document.getElementById('task-details-title-text').textContent = task.title || '-';
            document.getElementById('task-details-description').textContent = task.description || 'No description available';
            document.getElementById('task-details-assigned-to').textContent = task.assigned_to || '-';
            document.getElementById('task-details-due-date').textContent = formatDate(task.due_date) || '-';
            
            const statusBadge = document.getElementById('task-details-status');
            statusBadge.textContent = capitalizeFirstLetter(displayStatus.replace('-', ' ')) || '-';
            statusBadge.className = `mt-1 text-sm font-medium px-2 py-1 rounded-full inline-block status-${displayStatus}`;
            
            const priorityBadge = document.getElementById('task-details-priority');
            priorityBadge.textContent = capitalizeFirstLetter(task.priority) || '-';
            priorityBadge.className = `mt-1 text-sm font-medium px-2 py-1 rounded-full inline-block priority-${task.priority}`;
            
            document.getElementById('task-details-created-date').textContent = formatDateTime(task.created_at) || '-'; 
            document.getElementById('task-details-updated-date').textContent = formatDateTime(task.updated_at) || '-'; 
            
            document.getElementById('task-details-status-history').textContent = 'Activity log below shows changes.';

            const relatedItemsViewContainer = document.getElementById('task-details-related-items');
            relatedItemsViewContainer.innerHTML = ''; 
            if (task.related_items && task.related_items.length > 0) {
                task.related_items.forEach(item => {
                    const el = document.createElement('span');
                    el.className = 'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 mr-2 mb-2';
                    el.textContent = `${capitalizeFirstLetter(item.related_item_type)}: ID ${item.related_item_id}`;
                    relatedItemsViewContainer.appendChild(el);
                });
            } else {
                relatedItemsViewContainer.innerHTML = '<p class="text-sm text-gray-500">No related items.</p>';
            }

            const attachmentsViewContainer = document.getElementById('task-details-attachments');
            attachmentsViewContainer.innerHTML = ''; 
            if (task.attachments && task.attachments.length > 0) {
                task.attachments.forEach(attachment => {
                    const link = document.createElement('a');
                    link.href = attachment.file_path; 
                    link.textContent = attachment.file_name;
                    link.target = "_blank";
                    link.className = "block text-blue-600 hover:underline";
                    
                    const icon = document.createElement('i');
                    icon.className = `fas fa-file-${getFileIconClass(attachment.file_type)} mr-2`;
                    link.prepend(icon);

                    attachmentsViewContainer.appendChild(link);
                });
            } else {
                attachmentsViewContainer.innerHTML = '<p class="text-sm text-gray-500">No attachments.</p>';
            }
            
            renderActivities(task.activities || []);
            
            document.getElementById('task-details-modal').setAttribute('data-task-id', id);
            document.getElementById('task-details-modal').classList.remove('hidden');
        } catch (error) {
            showErrorToast('Failed to load task details: ' + error.message);
        }
    }

    function getFileIconClass(mimeType) {
        if (mimeType && mimeType.includes('pdf')) return 'pdf text-red-500';
        if (mimeType && mimeType.startsWith('image/')) return 'image text-blue-500';
        if (mimeType && mimeType.includes('word')) return 'word text-blue-700';
        if (mimeType && (mimeType.includes('excel') || mimeType.includes('spreadsheet'))) return 'excel text-green-700';
        return 'alt text-gray-500'; 
    }


    async function loadTaskActivities(taskId) {
        const activitiesContainer = document.getElementById('task-activities');
        if(activitiesContainer) activitiesContainer.innerHTML = `<div class="text-center py-4 text-sm text-gray-500">Activities loaded with task details.</div>`;
    }

    function renderActivities(activities) {
        const activitiesContainer = document.getElementById('task-activities');
        if (!activitiesContainer) return;
        
        if (!activities || activities.length === 0) {
            activitiesContainer.innerHTML = `<div class="text-center py-4 text-sm text-gray-500">No activities found for this task.</div>`;
            return;
        }
        
        activitiesContainer.innerHTML = activities.map(activity => {
            const userDisplay = activity.user_full_name || (activity.user_id ? `User #${activity.user_id}` : 'System');
            const avatarInitials = userDisplay.split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase();

            return `
            <div class="activity-item p-4 border-b border-gray-200 last:border-0">
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium">
                        ${avatarInitials}
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">${userDisplay}</p>
                            <p class="text-xs text-gray-500">${formatDateTime(activity.timestamp)}</p>
                        </div>
                        <p class="text-sm text-gray-700">
                            ${activity.activity_notes || 'No details'} 
                        </p>
                        <div class="mt-1 text-xs text-gray-500 italic">
                            Type: ${activity.activity_type || 'General'}
                        </div>
                    </div>
                </div>
            </div>
        `}).join('');
    }

    async function handleSaveTask() {
        const idInput = document.getElementById('task-id');
        const id = idInput ? idInput.value : null;
        
        const taskData = { // This will be converted to FormData
            title: document.getElementById('task-title')?.value,
            description: document.getElementById('task-description')?.value,
            assignedTo: document.getElementById('task-assigned-to')?.value, 
            dueDate: document.getElementById('task-due-date')?.value,
            status: document.getElementById('task-status')?.value,
            priority: document.getElementById('task-priority')?.value,
            related_items: currentTaskRelatedItems 
        };
    
        if (id) {
            taskData.id = parseInt(id);
        }

        if (!taskData.title) {
            showErrorToast('Task title is required');
            return;
        }
    
        const saveBtn = document.getElementById('save-task');
        if(saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
        }
        
        const formData = new FormData();
        formData.append('action', id ? 'update_task' : 'create_task'); // Set action for API
        if (id) formData.append('id', taskData.id);

        formData.append('title', taskData.title);
        formData.append('description', taskData.description);
        formData.append('assigned_to', taskData.assignedTo);
        formData.append('due_date', taskData.dueDate);
        formData.append('status', taskData.status);
        formData.append('priority', taskData.priority);
        
        if (Array.isArray(taskData.related_items)) {
            taskData.related_items.forEach((item, index) => {
                formData.append(`related_items[${index}][related_item_id]`, item.related_item_id);
                formData.append(`related_items[${index}][related_item_type]`, item.related_item_type);
            });
        }
        
        const attachmentInput = document.getElementById('task-attachments');
        if (attachmentInput && attachmentInput.files.length > 0) {
            for (let i = 0; i < attachmentInput.files.length; i++) {
                formData.append('attachments[]', attachmentInput.files[i]);
            }
        }

        if (id && attachmentsToDelete.length > 0) {
            attachmentsToDelete.forEach(attId => formData.append('deleted_attachment_ids[]', attId));
        }

        try {
            if (id) {
                await updateTask(formData); 
                showSuccessToast('Task updated successfully!');
            } else {
                const result = await addTask(formData); 
                showSuccessToast(`Task added successfully! New ID: ${result.taskId}`);
            }
            
            document.getElementById('task-modal')?.classList.add('hidden');
            await loadTasksTable();
            currentTaskRelatedItems = []; 
            attachmentsToDelete = [];    
        
        } catch (error) {
            // Error already handled by apiRequest and toast shown
        } finally {
            if(saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Task';
            }
        }
    }

    async function handleDeleteTask(id) {
        try {
            const task = await getTask(id); 
            if (!task) {
                 showErrorToast(`Task with ID ${id} not found. Cannot delete.`);
                 return;
            }
            
            if (confirm(`Are you sure you want to delete "${task.title}"? This action cannot be undone.`)) {
                await deleteTask(id);
                showSuccessToast('Task deleted successfully!');
                await loadTasksTable();
            }
        } catch (error) {
            // Error already handled by apiRequest and toast shown
        }
    }
    
    // Related Items UI Functions
    function handleAddRelatedItemToList() {
        const typeSelect = document.getElementById('related-item-type-select');
        const idInput = document.getElementById('related-item-id-input');
        const typeValue = typeSelect.value;
        const idValue = idInput.value.trim();

        if (!typeValue) {
            showErrorToast("Please select a related item type.");
            return;
        }
        if (!idValue) {
            showErrorToast("Please enter a related item ID or name.");
            return;
        }
        if (currentTaskRelatedItems.some(item => item.related_item_type === typeValue && item.related_item_id === idValue)) {
            showInfoToast("This related item is already added.");
            return;
        }

        currentTaskRelatedItems.push({ related_item_id: idValue, related_item_type: typeValue });
        displayCurrentRelatedItems();
        idInput.value = ''; 
        typeSelect.value = ''; 
    }

    function displayCurrentRelatedItems() {
        const listContainer = document.getElementById('current-related-items-list');
        if (!listContainer) return;
        listContainer.innerHTML = ''; 

        currentTaskRelatedItems.forEach((item, index) => {
            const tag = document.createElement('span');
            tag.className = 'inline-flex items-center bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full';
            tag.innerHTML = `
                ${capitalizeFirstLetter(item.related_item_type)}: ${item.related_item_id}
                <button type="button" class="remove-related-item-btn ml-2 text-blue-700 hover:text-blue-900" data-item-index="${index}" title="Remove item">&times;</button>
            `;
            listContainer.appendChild(tag);
        });
    }
    
    // Attachments UI Functions
    function displayExistingAttachments(attachments = []) {
        const listContainer = document.getElementById('existing-attachments-list');
        if (!listContainer) return;
        listContainer.innerHTML = ''; 

        if (attachments.length === 0) {
            listContainer.innerHTML = '<p class="text-sm text-gray-500 mb-2">No existing attachments.</p>';
            return;
        }

        attachments.forEach(attachment => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'flex items-center justify-between p-2 bg-gray-100 rounded text-sm';
            itemDiv.innerHTML = `
                <span>
                    <i class="fas fa-file-${getFileIconClass(attachment.file_type)} mr-2"></i>
                    <a href="${attachment.file_path}" target="_blank" class="hover:underline">${attachment.file_name}</a>
                </span>
                <button type="button" class="remove-existing-attachment-btn text-red-500 hover:text-red-700" data-attachment-id="${attachment.id}" title="Mark for removal">&times;</button>
            `;
            listContainer.appendChild(itemDiv);
        });
    }
    
    function clearExistingAttachmentsDisplay() {
        const listContainer = document.getElementById('existing-attachments-list');
        if (listContainer) listContainer.innerHTML = '';
    }


    function handleFileUpload(event) {
        const files = event.target.files;
        const fileUploadArea = document.getElementById('file-upload-area');
        const filePreview = document.getElementById('file-preview');
        const previewList = document.getElementById('attachment-preview-list');

        if (!files || files.length === 0) {
            if (fileUploadArea) fileUploadArea.classList.remove('hidden');
            if (filePreview) filePreview.classList.add('hidden');
            if (previewList) previewList.innerHTML = '';
            return;
        }
        
        if(previewList) previewList.innerHTML = ''; 
        
        Array.from(files).forEach(file => {
            const fileTypeIcon = getFileIconClass(file.type);
            const previewItem = document.createElement('div');
            previewItem.className = 'flex items-center justify-between p-2 bg-gray-100 rounded';
            previewItem.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-file-${fileTypeIcon} mr-2"></i>
                    <span class="text-sm truncate max-w-xs">${file.name}</span>
                    <span class="text-xs text-gray-500 ml-2">(${(file.size / 1024).toFixed(1)} KB)</span>
                </div>
            `;
            if(previewList) previewList.appendChild(previewItem);
        });
        
        if(fileUploadArea) fileUploadArea.classList.add('hidden');
        if(filePreview) filePreview.classList.remove('hidden');
    }

    function clearFileUploadDisplayOnly() { 
        const fileUploadArea = document.getElementById('file-upload-area');
        const filePreview = document.getElementById('file-preview');
        const attachmentList = document.getElementById('attachment-preview-list');
        
        if(fileUploadArea) fileUploadArea.classList.remove('hidden');
        if(filePreview) filePreview.classList.add('hidden');
        if(attachmentList) attachmentList.innerHTML = '';
    }

    function clearFileUpload() { 
        const fileInput = document.getElementById('task-attachments');
        if (fileInput) fileInput.value = ''; 
        clearFileUploadDisplayOnly();
        clearExistingAttachmentsDisplay();
        attachmentsToDelete = [];
    }

    function addRelatedItem() {
        // This function is deprecated in favor of handleAddRelatedItemToList
        showInfoToast('Please use the form fields to add related items.');
    }

    function handleConnectionChange() {
        isOnline = navigator.onLine;
        const statusElement = document.getElementById('connection-status');
        if(!statusElement) return;
        
        if (isOnline) {
            statusElement.className = 'mb-4 p-2 rounded-md text-sm flex items-center bg-green-100 text-green-800';
            statusElement.innerHTML = '<i class="fas fa-circle mr-2 text-green-500"></i><span>Online</span>';
        } else {
            statusElement.className = 'mb-4 p-2 rounded-md text-sm flex items-center bg-yellow-100 text-yellow-800';
            statusElement.innerHTML = '<i class="fas fa-circle mr-2 text-yellow-500"></i><span>Offline - Some features may be unavailable.</span>';
        }
        
        statusElement.classList.remove('hidden');
        setTimeout(() => statusElement.classList.add('hidden'), 5000);
    }

    function showNotification(message, type = 'info') {
        if (type === 'success') showSuccessToast(message);
        else if (type === 'error') showErrorToast(message);
        else showInfoToast(message);
    }

    function setupNotificationsDropdown() {
        const notificationsBtn = document.getElementById('notifications-btn');
        const notificationsDropdown = document.getElementById('notifications-dropdown');
        
        if(notificationsBtn && notificationsDropdown) {
            notificationsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationsDropdown.classList.toggle('hidden');
                if (!notificationsDropdown.classList.contains('hidden')) {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    const countEl = document.getElementById('notification-count');
                    if(countEl) {
                        countEl.classList.add('hidden');
                        countEl.textContent = '0';
                    }
                }
            });
        }
    }

    // ========== Helper Functions ========== //
    function formatDate(dateString) {
        if (!dateString) return '-';
        const dateParts = dateString.split('-');
        if (dateParts.length === 3) {
            const date = new Date(parseInt(dateParts[0]), parseInt(dateParts[1]) - 1, parseInt(dateParts[2]));
            if (isNaN(date.getTime())) return 'Invalid Date';
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return date.toLocaleDateString(undefined, options);
        }
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Invalid Date';
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString(undefined, options);
    }

    function formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Invalid Date/Time';
        const options = { 
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        };
        return date.toLocaleString(undefined, options);
    }

    function isOverdue(dueDate, status) { 
        if (!dueDate || status === 'completed') return false;
        const today = new Date();
        today.setHours(0,0,0,0); 
        return new Date(dueDate) < today;
    }

    function daysOverdue(dueDate) {
        if (!dueDate) return 0;
        const today = new Date();
        today.setHours(0,0,0,0);
        const due = new Date(dueDate);
        due.setHours(0,0,0,0);
        const diffTime = today - due;
        return Math.max(0, Math.floor(diffTime / (1000 * 60 * 60 * 24)));
    }

    function capitalizeFirstLetter(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function getRelatedItemIcon(type) {
        switch(type) {
            case 'crop': return 'leaf';
            case 'livestock': return 'cow';
            case 'equipment': return 'tractor';
            case 'field': return 'map-marked-alt';
            case 'inventory': return 'boxes';
            default: return 'link';
        }
    }

    function sortTasks(tasks, sortBy) { 
        if (!tasks) return [];
        return [...tasks].sort((a, b) => {
            switch(sortBy) {
                case 'title-asc': return (a.title || '').localeCompare(b.title || '');
                case 'title-desc': return (b.title || '').localeCompare(a.title || '');
                case 'due-date-asc': return new Date(a.due_date || 0) - new Date(b.due_date || 0);
                case 'due-date-desc': return new Date(b.due_date || 0) - new Date(a.due_date || 0);
                case 'priority-asc':
                    const po = { 'low': 1, 'medium': 2, 'high': 3 };
                    return (po[a.priority] || 0) - (po[b.priority] || 0);
                case 'priority-desc':
                    const pod = { 'low': 1, 'medium': 2, 'high': 3 };
                    return (pod[b.priority] || 0) - (pod[a.priority] || 0);
                default: return 0;
            }
        });
    }

    function showAlert(message, type = 'info') {
        showToast(message, type);
    }

    // ========== Initialize Application ========== //
    async function initApp() {
        try {
            setupEventListeners();
            setupNotificationsDropdown();
            handleConnectionChange(); 
            
            window.statusChart = null; 
            updateStatusChartWithStats({});    
            
            await loadTasksTable(); 
            
            showInfoToast('Task management loaded.');
            
        } catch (error) {
            console.error('Initialization error:', error);
            showErrorToast('Failed to initialize task management: ' + error.message);
        }
    }

    // Start the application
    initApp();
});
    </script>
</body>
</html>
