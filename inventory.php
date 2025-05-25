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

// Helper functions for Inventory-specific status
// The following functions are implemented in JavaScript below, not in PHP.
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriVision Pro | Inventory Management</title>
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
        /* Sidebar styles - adapt if needed */
        .sidebar-enter {
            transform: translateX(-100%);
        }
        .sidebar-enter-active {
            transform: translateX(0);
            transition: transform 300ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        /* Modal styles */
        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
            z-index: 50;
        }
        /* Inventory Status Specific Styles */
        .status-in-stock {
            color: #10B981; /* Green */
            background-color: #ECFDF5; /* Light Green */
        }
        .status-low-stock {
            color: #F59E0B; /* Yellow */
            background-color: #FFFBEB; /* Light Yellow */
        }
        .status-out-of-stock {
            color: #EF4444; /* Red */
            background-color: #FEF2F2; /* Light Red */
        }
        /* Search results dropdown */
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
        /* User Profile Dropdown */
        .user-profile-dropdown {
            min-width: 200px;
        }
         /* Inventory Card styles */
        .inventory-card {
            transition: all 0.3s ease;
        }
        .inventory-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        /* Timeline styles */
        .timeline-progress {
            transition: width 0.6s ease;
        }
        /* Activity items styles */
        .activity-item {
            transition: all 0.2s ease;
        }
        .activity-item:hover {
            transform: translateX(2px);
        }
        /* Required field indicator */
        .required-field::after {
            content: '*';
            color: #ef4444;
            margin-left: 0.25rem;
        }
        /* Image upload preview */
        #preview-image {
            max-height: 200px;
            max-width: 100%;
            object-fit: contain; /* Ensure image fits within container */
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
         /* Sticky header and footer for modals */
        .sticky {
            position: sticky;
        }
        .top-0 {
            top: 0;
        }
        .bottom-0 {
            bottom: 0;
        }
        /* Smooth scrolling for main content */
        .overflow-y-auto {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }
        /* Better scrollbar styles */
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

        /* Responsive Modal Size */
        @media (min-width: 640px) {
            .sm\:max-w-2xl {
                max-width: 40rem;
            }
            .sm\:max-w-lg { /* For details modal */
                max-width: 38rem; /* Adjusted for better details layout */
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
            background-color: #f0f9ff; /* Light blue for unread */
        }
        .notification-dropdown {
            width: 350px;
            max-height: 80vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="h-full overflow-hidden">
    <div class="flex h-full">
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
                    <a href="inventory.php" class="flex items-center px-3 py-2 rounded-lg bg-blue-500 bg-opacity-30 text-m text-white-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Inventory
                    </a>
                    <a href="tasks.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
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

        <div class="flex-1 flex flex-col overflow-hidden">
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
                            <input id="search-input" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search inventory..." type="search">
                            <div id="search-results" class="search-results hidden"></div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
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

            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Inventory Management</h2>
                        <p class="text-sm text-gray-500">Track and manage all your farm inventory in one place</p>
                    </div>
                    <button id="add-item-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i> Add New Item
                    </button>
                </div>
                
                <div id="connection-status" class="hidden mb-4 p-2 rounded-md text-sm flex items-center">
                    <i class="fas fa-circle mr-2 text-gray-500"></i>
                    <span>Checking connection...</span>
                </div>
                
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i class="fas fa-boxes text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Items</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="total-items-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                             <a href="#" class="text-sm font-medium text-blue-700 hover:text-blue-900">View all</a>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <i class="fas fa-check-circle text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">In Stock</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="in-stock-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                         <div class="bg-gray-50 px-5 py-3">
                             <a href="#" class="text-sm font-medium text-blue-700 hover:text-blue-900">View In Stock</a>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Low Stock</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="low-stock-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                         <div class="bg-gray-50 px-5 py-3">
                             <a href="#" class="text-sm font-medium text-blue-700 hover:text-blue-900">View Low Stock</a>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                    <i class="fas fa-times-circle text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Out of Stock</dt>
                                        <dd>
                                            <div class="text-2xl font-semibold text-gray-900" id="out-of-stock-count">0</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                         <div class="bg-gray-50 px-5 py-3">
                             <a href="#" class="text-sm font-medium text-blue-700 hover:text-blue-900">View Out of Stock</a>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Inventory Distribution</h3>
                        <p class="mt-1 text-sm text-gray-500">Number of items by category</p>
                    </div>
                    <div class="p-4">
                        <canvas id="categoryChart" class="w-full h-64"></canvas>
                    </div>
                </div>
                
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10 mb-6 overflow-x-auto">
                        <div class="flex items-center space-x-4 w-full sm:w-auto sm:space-x-4">
                            <div class="relative w-full sm:w-80">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" id="search-inventory" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search inventory...">
                            </div>
                            
                            <select id="category-filter" title="Filter by category" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="all">All Categories</option>
                                <option value="equipment">Equipment</option>
                                <option value="seeds">Seeds</option>
                                <option value="fertilizers">Fertilizers</option>
                                <option value="tools">Tools</option>
                                <option value="other">Other</option>
                                </select>
                            
                            <select id="status-filter" title="Filter by status" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="all">All Status</option>
                                <option value="in-stock">In Stock</option>
                                <option value="low-stock">Low Stock</option>
                                <option value="out-of-stock">Out of Stock</option>
                            </select>
                        </div>
                        
                        <div class="flex space-x-2">
                            <select id="sort-by" title="Sort items by criteria" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="name-asc">Name (A-Z)</option>
                                <option value="name-desc">Name (Z-A)</option>
                                <option value="quantity-asc">Quantity (Lowest)</option>
                                <option value="quantity-desc">Quantity (Highest)</option>
                                <option value="updatedAt-desc">Last Updated (Newest)</option>
                                <option value="updatedAt-asc">Last Updated (Oldest)</option>
                                </select>
                            
                            <button id="view-toggle" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-table mr-2"></i> Table View
                            </button>
                        </div>
                    </div>
                    
                    <div id="card-view" class="hidden p-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="sm:col-span-1 lg:col-span-3 text-center py-10">
                            <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                            <p class="mt-2 text-sm text-gray-500">Loading inventory...</p>
                        </div>
                    </div>
                    
                    <div id="table-view" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ITEM</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CATEGORY</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QUANTITY</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UNIT</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LOCATION</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="inventory-table-body" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading inventory...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
                        <div class="flex-1 flex justify-between sm:hidden">
                             <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Previous </a>
                            <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Next </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                 <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium" id="pagination-start">0</span> to <span class="font-medium" id="pagination-end">0</span> of <span class="font-medium" id="pagination-total">0</span> results
                                </p>
                            </div>
                            <div>
                                 <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <a href="#" id="prev-page" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <div id="page-numbers" class="flex">
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

    <div id="item-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-200 sticky top-0 z-10">
                    <h3 id="modal-title" class="text-2xl font-bold text-gray-800">Add New Item</h3>
                </div>
                
                <div class="max-h-[calc(100vh-200px)] overflow-y-auto px-6 py-4">
                    <form id="item-form">
                        <input type="hidden" id="item-id"> <input type="hidden" id="existing-item-image"> <div class="grid grid-cols-1 gap-y-6 gap-x-6 sm:grid-cols-6">
                            <div class="sm:col-span-6 border-b border-gray-200 pb-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Basic Information</h4>
                                <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                                    <div class="sm:col-span-6">
                                        <label for="item-name" class="block text-sm font-medium text-gray-700 flex items-center required-field">
                                            Item Name
                                        </label>
                                        <input type="text" id="item-name" required 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="item-category" class="block text-sm font-medium text-gray-700 flex items-center required-field">
                                            Category
                                        </label>
                                        <select id="item-category" required 
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="equipment">Equipment</option>
                                            <option value="seeds">Seeds</option>
                                            <option value="fertilizers">Fertilizers</option>
                                            <option value="tools">Tools</option>
                                            <option value="other">Other</option>
                                            </select>
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="item-quantity" class="block text-sm font-medium text-gray-700 flex items-center required-field">
                                            Quantity
                                        </label>
                                        <input type="number" id="item-quantity" min="0" required 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="item-unit" class="block text-sm font-medium text-gray-700 flex items-center required-field">
                                            Unit
                                        </label>
                                        <select id="item-unit" required 
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="pieces">Pieces</option>
                                            <option value="kg">Kilograms</option>
                                            <option value="liters">Liters</option>
                                            <option value="bags">Bags</option>
                                            <option value="units">Units</option>
                                            <option value="other">Other</option>
                                            </select>
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="item-location" class="block text-sm font-medium text-gray-700">Location</label>
                                        <input type="text" id="item-location" 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="sm:col-span-6">
                                <label for="item-notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea id="item-notes" rows="3" 
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                            </div>
                            
                            <div class="sm:col-span-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Item Image</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md relative">
                                    <div id="image-upload-area" class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <label for="item-image" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                <span>Upload a file</span>
                                                <input id="item-image" name="item-image" type="file" class="sr-only" accept="image/*">
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
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 sticky bottom-0">
                    <button type="button" id="save-item" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Item
                    </button>
                    <button type="button" id="cancel-item" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="item-details-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-200 sticky top-0 z-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 id="item-details-title" class="text-2xl font-bold text-gray-800">Item Details</h3>
                            <p id="item-details-subtitle" class="mt-1 text-sm text-gray-500">Detailed information about this item</p>
                        </div>
                        <button id="close-icon-btn" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <div class="max-h-[calc(100vh-200px)] overflow-y-auto px-6 py-4">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        
                        <div class="sm:col-span-2">
                            <div class="h-64 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                                <img id="item-details-image" src="https://source.unsplash.com/random/300x300/?farm,equipment" alt="Item image" class="w-full h-full object-cover">
                            </div>
                            
                            <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Item Timeline</h4>
                                <div class="space-y-4">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                            <i class="fas fa-box text-sm"></i> </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Added</p>
                                            <p id="item-details-added-date" class="text-sm text-gray-500">-</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                            <i class="fas fa-sync-alt text-sm"></i> </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Last Updated</p>
                                            <p id="item-details-updated-date" class="text-sm text-gray-500">-</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                                            <i class="fas fa-cubes text-sm"></i> </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Quantity</p>
                                            <p id="item-details-quantity" class="text-sm text-gray-500">-</p>
                                        </div>
                                    </div>
                                     <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                                            <i class="fas fa-exclamation-triangle text-sm"></i> </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Low Stock Threshold</p>
                                            <p class="text-sm text-gray-500">Set in configuration</p> </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sm:col-span-4">
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Basic Information</h4>
                                <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Item Name</label>
                                        <p id="item-details-name" class="mt-1 text-sm font-medium text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Category</label>
                                        <p id="item-details-category" class="mt-1 text-sm text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Unit</label>
                                        <p id="item-details-unit" class="mt-1 text-sm text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Location</label>
                                        <p id="item-details-location" class="mt-1 text-sm text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Status</label>
                                        <p id="item-details-status" class="mt-1 text-sm font-medium text-gray-900">-</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Notes</h4>
                                <p id="item-details-notes" class="text-sm text-gray-900">No notes available</p>
                            </div>
                        </div>
                    </div>   
                    
                    <div class="border-t border-gray-200 pt-4">
                        <div class="px-5 pt-4 pb-4 bg-white sticky top-0 z-50 flex justify-center">
                            <h4 class="text-2xl font-bold text-gray-800 mb-3">Recent Activities</h4>
                        </div>
                        <div id="item-activities-list" class="space-y-4">
                            <div class="text-center py-4 text-sm text-gray-500 flex justify-center items-start">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Loading activities...
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 sticky bottom-0 flex justify-between items-center">
                     <button id="prev-item-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-chevron-left mr-1"></i> Previous
                    </button>

                     <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" id="edit-item-from-details"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm
                                    px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700
                                    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                                    sm:ml-3 sm:w-auto sm:text-sm transition ease-in-out duration-150">
                            <i class="fas fa-edit mr-2"></i>Edit</button>

                        <button type="button" id="close-details-btn"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm
                                    px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50
                                    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                                    sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition ease-in-out duration-150">
                            <i class="fas fa-times mr-2"></i>Close</button>
                    </div>
                    
                     <button id="next-item-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next <i class="fas fa-chevron-right ml-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // =======================================================
    // Constants and Global Variables
    // =======================================================

    // API Endpoint for Inventory
    const INVENTORY_API_URL = 'inventory_api.php'; // Make sure this path is correct

    // Global variable to store the list of inventory items fetched from the server
    // This holds ALL items (subject to server-side filters if any)
    let allInventoryItems = [];
    // Array to store items currently displayed based on frontend search, filter, sort, pagination
    let currentItems = [];

    let itemsPerPage = 20; // Number of items per page
    let currentPage = 1; // Current active page

    // Keep track of current view mode
    let currentViewMode = 'table'; // 'table' or 'card'

    // Keep track of the currently displayed item ID in the details modal for navigation
    let currentItemDetailsId = null;

    // Get the current user ID from a global variable set by PHP (assuming this exists)
    // Ensure `currentUserId` is defined before this script block runs.
    const currentUserId = <?php echo $_SESSION['user_id'] ?? 'null'; ?> // Using PHP inline to get user ID


    // =======================================================
    // DOM Element References
    // =======================================================

    // Sidebar and Top Nav
    const sidebar = document.getElementById('sidebar');
    const sidebarToggleBtn = document.getElementById('sidebar-toggle');
    const userMenuBtn = document.getElementById('user-menu');
    const userMenuDropdown = document.getElementById('user-menu-dropdown');
    const notificationsBtn = document.getElementById('notifications-btn');
    const notificationsDropdown = document.getElementById('notifications-dropdown');
    const notificationsList = document.getElementById('notifications-list');
    const notificationCountBadge = document.getElementById('notification-count');
    const topSearchInput = document.getElementById('search-input');
    const topSearchResults = document.getElementById('search-results');

    // Main Inventory Page Elements
    const addItemBtn = document.getElementById('add-item-btn');
    const searchInventoryInput = document.getElementById('search-inventory');
    const categoryFilter = document.getElementById('category-filter');
    const statusFilter = document.getElementById('status-filter');
    const sortBySelect = document.getElementById('sort-by');
    const viewToggleButton = document.getElementById('view-toggle');
    const cardViewContainer = document.getElementById('card-view');
    const tableViewContainer = document.getElementById('table-view');
    const inventoryTableBody = document.getElementById('inventory-table-body');

    // Pagination Elements
    const paginationStartSpan = document.getElementById('pagination-start');
    const paginationEndSpan = document.getElementById('pagination-end');
    const paginationTotalSpan = document.getElementById('pagination-total');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const pageNumbersContainer = document.getElementById('page-numbers');

    // Summary Card Elements
    const totalItemsCount = document.getElementById('total-items-count');
    const inStockCount = document.getElementById('in-stock-count');
    const lowStockCount = document.getElementById('low-stock-count');
    const outOfStockCount = document.getElementById('out-of-stock-count');

    // Chart Element
    const categoryChartCanvas = document.getElementById('categoryChart');
    let categoryChartInstance = null; // To hold the Chart.js instance

    // Connection Status Element
    const connectionStatusDiv = document.getElementById('connection-status');


    // =======================================================
    // Add/Edit Item Modal Elements
    // =======================================================

    const itemModal = document.getElementById('item-modal'); // Main modal container (Add/Edit)
    const modalTitle = document.getElementById('modal-title'); // Modal title element
    const itemIdInput = document.getElementById('item-id'); // Hidden input for item ID
    const itemNameInput = document.getElementById('item-name');
    const itemCategorySelect = document.getElementById('item-category');
    const itemQuantityInput = document.getElementById('item-quantity');
    const itemUnitSelect = document.getElementById('item-unit');
    const itemLocationInput = document.getElementById('item-location');
    const itemNotesTextarea = document.getElementById('item-notes');
    const itemImageInput = document.getElementById('item-image'); // File input for image
    const existingItemImageInput = document.getElementById('existing-item-image'); // Hidden input for existing image URL
    const imageUploadArea = document.getElementById('image-upload-area'); // Area to show upload icon
    const imagePreview = document.getElementById('image-preview'); // Area to show image preview
    const previewImageElement = document.getElementById('preview-image'); // <img> tag for preview
    const removeItemBtn = document.getElementById('remove-image'); // Button to remove image

    const saveItemBtn = document.getElementById('save-item'); // Save button in modal
    const cancelItemBtn = document.getElementById('cancel-item'); // Cancel button in modal
    const cancelIconBtn = document.getElementById('close-icon-btn'); // Cancel button in modal header


    // =======================================================
    // Item Details Modal Elements
    // =======================================================

    const itemDetailsModal = document.getElementById('item-details-modal'); // Details modal container
    const itemDetailsTitle = document.getElementById('item-details-title'); // Item name in details header
    const itemDetailsSubtitle = document.getElementById('item-details-subtitle'); // Subtitle (e.g., Category)
    const itemDetailsImage = document.getElementById('item-details-image'); // <img> tag for details image
    const itemDetailsAddedDate = document.getElementById('item-details-added-date'); // Timeline added date
    const itemDetailsUpdatedDate = document.getElementById('item-details-updated-date'); // Timeline updated date
    const itemDetailsQuantityTimeline = document.getElementById('item-details-quantity'); // Timeline quantity display
    const itemDetailsNameDisplay = document.getElementById('item-details-name'); // Basic Info - Name
    const itemDetailsCategoryDisplay = document.getElementById('item-details-category'); // Basic Info - Category
    const itemDetailsUnitDisplay = document.getElementById('item-details-unit'); // Basic Info - Unit
    const itemDetailsLocationDisplay = document.getElementById('item-details-location'); // Basic Info - Location
    const itemDetailsStatusDisplay = document.getElementById('item-details-status'); // Basic Info - Status
    const itemDetailsNotesDisplay = document.getElementById('item-details-notes'); // Notes display
    const itemActivitiesList = document.getElementById('item-activities-list'); // Activities list container

    const prevItemBtn = document.getElementById('prev-item-btn'); // Details modal Previous button
    const nextItemBtn = document.getElementById('next-item-btn'); // Details modal Next button
    const editItemFromDetailsBtn = document.getElementById('edit-item-from-details'); // Edit button in details modal
    const closeDetailsBtn = document.getElementById('close-details-btn'); // Close button in details modal


    // =======================================================
    // Helper Functions (Replicated from PHP for JS use)
    // =======================================================

    // Helper function to calculate status
    function calculateStatus(quantity) {
        // Assuming low stock threshold is 10 (matches PHP logic)
        const lowStockThreshold = 10;
        const qty = parseInt(quantity) || 0; // Ensure quantity is treated as a number
        if (qty <= 0) return 'out-of-stock';
        if (qty < lowStockThreshold) return 'low-stock';
        return 'in-stock';
    }

    // Helper function to get status class
    function getStatusClass(status) {
        switch(status) {
            case 'in-stock': return 'status-in-stock';
            case 'low-stock': return 'status-low-stock';
            case 'out-of-stock': return 'status-out-of-stock';
            default: return 'bg-gray-100 text-gray-800';
        }
    }

    // Close Item Details Modal (New Function)
    function closeDetailsModal() {
        itemDetailsModal.classList.add('hidden'); // Hide the modal
        document.body.classList.remove('overflow-hidden'); // Restore body scrolling
        currentItemDetailsId = null; // Clear current details item ID
        console.log("Item details modal closed.");
    }


    // =======================================================
    // API Interaction Functions
    // =======================================================

    // Helper function to handle API requests
    async function apiRequest(action, method = 'POST', data = null) {
        console.log(`Sending API request: ${action}`, data);

        const options = {
            method: method,
            headers: {}, // Headers will be set based on data type
            body: null,
        };

        // Create FormData for all POST requests to handle files consistently
        const formData = new FormData();
        formData.append('action', action);
        formData.append('userId', currentUserId); // Ensure user ID is always included

        // Append data object properties to FormData
        if (data && typeof data === 'object') {
             for (const key in data) {
                 if (data.hasOwnProperty(key) && key !== 'imageFile') { // Exclude imageFile for now
                     formData.append(key, data[key] === null ? '' : data[key]); // Append null as empty string
                 }
             }
        }

         // Append the file if it exists in the data object
        if (data && data.imageFile) {
            formData.append('itemImage', data.imageFile);
        }

        options.body = formData;
         // Note: Browser sets Content-Type 'multipart/form-data' automatically with FormData


        try {
            const response = await fetch(INVENTORY_API_URL, options);

            // Check for HTTP errors (status codes outside 2xx range)
            if (!response.ok) {
                 let errorDetail = `Server responded with status ${response.status}`;
                 try {
                      const errorBody = await response.json();
                      errorDetail += `: ${errorBody.message || JSON.stringify(errorBody)}`;
                 } catch (e) {
                      errorDetail += `: ${await response.text() || response.statusText}`;
                 }
                 console.error(`API Error for action "${action}": ${errorDetail}`);
                 // Throw a specific error with detailed info
                 throw new Error(`API request failed for action "${action}": ${errorDetail}`);
            }

            // Attempt to parse JSON response
            const result = await response.json();

            // Check the 'success' flag in the JSON response
            if (!result.success) {
                console.error(`API returned success: false for action "${action}":`, result.message);
                // Throw an error based on the API's message
                throw new Error(`Operation failed: ${result.message || 'Unknown error'}`);
            }

            console.log(`API request "${action}" successful:`, result);
            return result; // Return the successful result

        } catch (error) {
            console.error(`Error during API request for action "${action}":`, error);
            // Re-throw the error to be caught by the calling function
            throw error;
        }
    }

    // Fetch all inventory items for the current user
    async function fetchInventory() {
        console.log("Fetching inventory data from server...");
        try {
            // Use the 'list_items' action to get the list
            const result = await apiRequest('list_items', 'POST', { userId: currentUserId }); // Use POST with FormData implicitly

            if (result.success && Array.isArray(result.inventory)) { // Ensure key is 'inventory' as per expected API response
                console.log("Inventory data fetched successfully:", result.inventory.length);
                return result.inventory; // Return the array of items
            } else {
                 console.error("API returned success: false or 'inventory' is not a valid array:", result.message || result);
                 showErrorToast(result.message || 'Failed to fetch inventory data structure.');
                 return []; // Return empty array on unexpected structure
            }

        } catch (error) {
            console.error("Fetch error during fetchInventory:", error);
            showErrorToast('Failed to load inventory data: ' + error.message);
            return []; // Return empty array on fetch error
        }
    }

    // Fetch details for a single inventory item (including activities)
    async function fetchItemDetails(itemId) {
        console.log("Fetching item details from server for ID:", itemId);
        try {
            // Use the 'get_item_details' action
            const result = await apiRequest('get_item_details', 'POST', { itemId: itemId, userId: currentUserId }); // Use POST with FormData implicitly

            if (result.success && result.item) { // Ensure key is 'item' and it's not null
                console.log("Item details fetched successfully:", result.item);
                // The item object should include an 'activities' array if the API provides it
                return result.item;
            } else {
                 console.error("API returned success: false or 'item' not found:", result.message || result);
                 showErrorToast(result.message || `Failed to fetch details for item ID ${itemId}.`);
                 return null; // Return null if item not found or fetch failed
            }
        } catch (error) {
            console.error(`Error during fetchItemDetails for ID ${itemId}:`, error);
            showErrorToast(`Failed to load item details: ${error.message}`);
            return null; // Return null on fetch error
        }
    }

    // Send item data to the server for adding or updating
    async function saveItemToServer(itemData, isUpdate) {
        console.log(`${isUpdate ? 'Updating' : 'Adding'} item via server API:`, itemData);

        // itemData object already contains fields and potentially imageFile or removeExistingImage flags
        // The apiRequest helper will handle creating FormData and appending fields/files

        try {
            const result = await apiRequest(isUpdate ? 'update_item' : 'create_item', 'POST', itemData);

            if (result.success) {
                showSuccessToast(result.message || `Item ${isUpdate ? 'updated' : 'added'} successfully!`);
                return result; // Return the successful result (might contain new item ID for create)
            } else {
                // apiRequest already logs the error if success is false
                throw new Error(result.message || `Failed to ${isUpdate ? 'update' : 'add'} item.`);
            }
        } catch (error) {
            console.error(`Error saving item to server (${isUpdate ? 'update' : 'create'}):`, error);
            showErrorToast(`Failed to save item: ${error.message}`);
            throw error; // Re-throw to calling function if needed
        }
    }

    // Send delete request to the server
    async function deleteItemFromServer(itemId) {
        console.log("Deleting item via server API for ID:", itemId);
        try {
            // Use the 'delete_item' action
            const result = await apiRequest('delete_item', 'POST', { itemId: itemId, userId: currentUserId });

            if (result.success) {
                showSuccessToast(result.message || `Item ID ${itemId} deleted successfully!`);
                return result; // Return the successful result
            } else {
                 // apiRequest already logs the error if success is false
                throw new Error(result.message || `Failed to delete item ID ${itemId}.`);
            }
        } catch (error) {
            console.error(`Error deleting item ${itemId}:`, error);
            showErrorToast(`Failed to delete item: ${error.message}`);
            throw error; // Re-throw to calling function if needed
        }
    }


    // =======================================================
    // Load Inventory Function (Modified to use only API)
    // =======================================================

    async function loadInventory() {
        console.log("Loading inventory items from server...");
        connectionStatusDiv.classList.remove('hidden');
        connectionStatusDiv.className = 'mb-4 p-2 rounded-md text-sm flex items-center bg-blue-100 text-blue-800'; // Blue for checking
        connectionStatusDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>Loading data from server...</span>'; // Indicate server loading

        let data = []; // Initialize data as empty array

        // Always attempt to fetch from server
        try {
            data = await fetchInventory(); // This will show error toasts if fetch fails
            console.log("Inventory data fetched successfully from server.");
            connectionStatusDiv.className = 'mb-4 p-2 rounded-md text-sm flex items-center bg-green-100 text-green-800'; // Green for success
            connectionStatusDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i><span>Data loaded successfully.</span>';


        } catch (error) {
            // Server fetch failed
            console.error("Failed to load inventory from server.", error);
            showErrorToast('Failed to load inventory data from server.');
            data = []; // Ensure data is empty on server fetch error
             connectionStatusDiv.className = 'mb-4 p-2 rounded-md text-sm flex items-center bg-red-100 text-red-800'; // Red for failure
             connectionStatusDiv.innerHTML = '<i class="fas fa-times-circle mr-2"></i><span>Failed to load data from server.</span>';

        }

        // Update global array and render UI
        allInventoryItems = data; // Update the global array that holds the master list
        console.log("Global allInventoryItems updated. Total:", allInventoryItems.length);

        // Apply current filters/sort and render the first page
        currentPage = 1; // Reset to first page on reload
        applyFiltersAndSort();

        // Hide connection status after a delay
         setTimeout(() => {
             connectionStatusDiv.classList.add('hidden');
         }, 5000); // Hide after 5 seconds

    }


    // =======================================================
    // Rendering Functions
    // =======================================================

    // Apply current search, filter, sort, and pagination
    function applyFiltersAndSort() {
        console.log("Applying filters and sort...");
        let itemsToDisplay = [...allInventoryItems]; // Start with all items from the master list

        // 1. Apply Search (on the master list)
        const searchTerm = searchInventoryInput.value.toLowerCase();
        if (searchTerm) {
            itemsToDisplay = itemsToDisplay.filter(item =>
                (item.name && item.name.toLowerCase().includes(searchTerm)) ||
                (item.category && item.category.toLowerCase().includes(searchTerm)) ||
                (item.location && item.location.toLowerCase().includes(searchTerm)) ||
                (item.notes && item.notes.toLowerCase().includes(searchTerm))
                 // Add other searchable fields
            );
        }

        // 2. Apply Filters (Category and Status) (on the searched results)
        const selectedCategory = categoryFilter.value;
        if (selectedCategory !== 'all') {
            itemsToDisplay = itemsToDisplay.filter(item => item.category === selectedCategory);
        }

        const selectedStatus = statusFilter.value;
        if (selectedStatus !== 'all') {
             // Need to calculate status based on quantity for filtering
            itemsToDisplay = itemsToDisplay.filter(item => {
                 // Ensure quantity is treated as a number
                 const quantity = parseInt(item.quantity) || 0;
                 return calculateStatus(quantity) === selectedStatus;
             });
        }

        // 3. Apply Sort (on the filtered results)
        const sortValue = sortBySelect.value;
        itemsToDisplay.sort((a, b) => {
            const [criteria, order] = sortValue.split('-');
            let aValue, bValue;

            switch (criteria) {
                case 'name':
                    aValue = a.name || '';
                    bValue = b.name || '';
                    return order === 'asc' ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
                case 'quantity':
                    // Ensure quantity is number for sorting
                    aValue = parseInt(a.quantity) || 0;
                    bValue = parseInt(b.quantity) || 0;
                    return order === 'asc' ? aValue - bValue : bValue - aValue;
                case 'updatedAt':
                     // Convert dates to compare (assuming ISO 8601 format or similar)
                     // Use createdAt if updatedAt is missing or invalid
                     aValue = new Date(a.updatedAt || a.createdAt || '').getTime();
                     bValue = new Date(b.updatedAt || b.createdAt || '').getTime();
                     // Handle invalid dates (getTime() returns NaN) by treating them equally or putting them last
                     if (isNaN(aValue) && isNaN(bValue)) return 0;
                     if (isNaN(aValue)) return order === 'asc' ? 1 : -1; // Invalid comes after valid for asc
                     if (isNaN(bValue)) return order === 'asc' ? -1 : 1; // Invalid comes after valid for asc
                     return order === 'asc' ? aValue - bValue : bValue - aValue;
                default:
                    return 0; // No sorting
            }
        });

        currentItems = itemsToDisplay; // Update the array of currently displayed items (after filtering/sorting)

        // 4. Apply Pagination (on the sorted, filtered results)
        const totalItems = currentItems.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage);

        // Adjust current page if it's out of bounds after filtering/sorting
        if (currentPage > totalPages && totalPages > 0) {
            currentPage = totalPages;
        } else if (totalPages === 0) {
             currentPage = 1; // If no items, show page 1 (empty)
        }
        if (currentPage < 1) {
             currentPage = 1;
        }


        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        const paginatedItems = currentItems.slice(startIndex, endIndex);

        // 5. Render the current page based on view mode
        if (currentViewMode === 'card') {
            renderCardView(paginatedItems);
        } else { // Default is table view
            renderTableView(paginatedItems);
        }

        // 6. Update Pagination Controls
        updatePaginationControls(totalItems, totalPages, startIndex, endIndex);

        // 7. Update Summary Cards and Chart based on ALL filtered items (currentItems)
        updateSummaryCards(); // Update counts based on currentItems (the filtered/searched list)
        updateCategoryChart(currentItems); // Update chart based on currentItems categories

         // Show appropriate message if no items found after filtering/search
         if (totalItems === 0) {
             const noResultsHtml = '<div class="sm:col-span-full lg:col-span-full text-center py-10"><p class="text-gray-500">No items found matching your criteria.</p></div>';

             if (currentViewMode === 'card') {
                 cardViewContainer.innerHTML = noResultsHtml;
                 tableViewContainer.classList.add('hidden'); // Hide table if empty
                 cardViewContainer.classList.remove('hidden'); // Show card message
             } else { // Table view
                 inventoryTableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No items found matching your criteria.</td></tr>';
                 cardViewContainer.classList.add('hidden'); // Hide card message
                 tableViewContainer.classList.remove('hidden'); // Show table
             }
         }


        console.log(`Rendered page ${currentPage} of ${totalPages} (${startIndex}-${endIndex}) from ${totalItems} filtered items.`);
    }

    // Render inventory items in Card View
    function renderCardView(items) {
        tableViewContainer.classList.add('hidden');
        cardViewContainer.classList.remove('hidden');
        cardViewContainer.innerHTML = ''; // Clear previous cards

        if (items.length === 0) {
            // Handled by applyFiltersAndSort if totalItems === 0
            return;
        }

        items.forEach(item => {
            // Calculate status based on quantity
             const quantity = parseInt(item.quantity) || 0; // Ensure quantity is number
            const itemStatus = calculateStatus(quantity);
            const statusClass = getStatusClass(itemStatus);

            const cardHtml = `
                <div data-id="${item.id}" class="inventory-card bg-white rounded-lg shadow-md p-6 flex flex-col justify-between border border-gray-200 hover:border-blue-500 cursor-pointer">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">${item.name || 'Unnamed Item'}</h3>
                            <p class="text-sm text-gray-600 mb-2">Category: ${item.category || 'N/A'}</p>
                            <p class="text-sm text-gray-600 mb-2">Location: ${item.location || 'N/A'}</p>
                            <p class="text-lg font-bold text-gray-900 mb-4">Qty: ${item.quantity || 0} ${item.unit || ''}</p>
                             <span class="status-badge ${statusClass}">${itemStatus.replace('-', ' ').toUpperCase()}</span>
                        </div>
                        <div class="flex-shrink-0 ml-4">
                            <img src="${item.image_url || './images/default_inventory.png'}" alt="${item.name || 'Item'} image" class="w-24 h-24 object-cover rounded-md shadow-sm"style="width: 110px; height: 110px;">
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end space-x-3">
                         <button data-id="${item.id}" data-action="view" class="action-btn text-blue-600 hover:text-blue-800 focus:outline-none" title="View Details">
                            <i class="fas fa-eye"></i> View
                        </button>
                         <button data-id="${item.id}" data-action="edit" class="action-btn text-yellow-600 hover:text-yellow-800 focus:outline-none" title="Edit Item">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                         <button data-id="${item.id}" data-action="delete" class="action-btn text-red-600 hover:text-red-800 focus:outline-none" title="Delete Item">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </div>
                </div>
            `;
            cardViewContainer.innerHTML += cardHtml;
        });
    }

    // Render inventory items in Table View
    function renderTableView(items) {
        cardViewContainer.classList.add('hidden');
        tableViewContainer.classList.remove('hidden');
        inventoryTableBody.innerHTML = ''; // Clear previous rows

        if (items.length === 0) {
            // Handled by applyFiltersAndSort if totalItems === 0
             // inventoryTableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No items found matching your criteria.</td></tr>'; // Moved to applyFiltersAndSort
            return;
        }

        items.forEach(item => {
             // Calculate status based on quantity
            const quantity = parseInt(item.quantity) || 0; // Ensure quantity is number
            const itemStatus = calculateStatus(quantity);
            const statusClass = getStatusClass(itemStatus);

            const rowHtml = `
                <tr data-id="${item.id}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                         <img src="${item.image_url || './images/default_inventory.png'}" alt="${item.name || 'Item'} image" class="w-8 h-8 object-cover rounded-full mr-3">
                        ${item.name || 'Unnamed Item'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 capitalize">${item.category || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">${item.quantity || 0}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">${item.unit || ''}</td>
                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">${item.location || 'N/A'}</td>
                     <td class="px-6 py-4 whitespace-nowrap">
                        <span class="status-badge ${statusClass}">${itemStatus.replace('-', ' ').toUpperCase()}</span>
                     </td>
                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium space-x-3">
                         <button data-id="${item.id}" data-action="view" class="action-btn text-blue-600 hover:text-blue-800 focus:outline-none" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                         <button data-id="${item.id}" data-action="edit" class="action-btn text-yellow-600 hover:text-yellow-800 focus:outline-none" title="Edit Item">
                            <i class="fas fa-edit"></i>
                        </button>
                         <button data-id="${item.id}" data-action="delete" class="action-btn text-red-600 hover:text-red-800 focus:outline-none" title="Delete Item">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
            inventoryTableBody.innerHTML += rowHtml;
        });
    }

    // Update pagination controls (numbers, arrows, info text)
    function updatePaginationControls(totalItems, totalPages, startIndex, endIndex) {
        paginationTotalSpan.textContent = totalItems;
        paginationStartSpan.textContent = totalItems > 0 ? startIndex + 1 : 0;
        paginationEndSpan.textContent = endIndex;

        // Enable/disable prev/next buttons
        prevPageBtn.classList.toggle('disabled', currentPage === 1 || totalPages === 0);
        nextPageBtn.classList.toggle('disabled', currentPage === totalPages || totalPages === 0);
         prevPageBtn.disabled = currentPage === 1 || totalPages === 0;
         nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;


        // Render page number buttons
        pageNumbersContainer.innerHTML = '';
        const maxPagesToShow = 5; // Max number of page buttons to display
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

        // Adjust startPage if endPage hits the total
        if (endPage - startPage + 1 < maxPagesToShow && totalPages >= maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

         // Show first page dot if not in visible range
         if (startPage > 1) {
             const dot = document.createElement('span');
             dot.textContent = '...';
             dot.classList.add('relative', 'inline-flex', 'items-center', 'px-4', 'py-2', 'text-sm', 'font-medium', 'text-gray-700');
              pageNumbersContainer.appendChild(dot);

             const firstPageButton = document.createElement('a');
             firstPageButton.href = "#";
             firstPageButton.textContent = 1;
              firstPageButton.classList.add('relative', 'inline-flex', 'items-center', 'px-4', 'py-2', 'border', 'border-gray-300', 'bg-white', 'text-sm', 'font-medium', 'text-gray-700', 'hover:bg-gray-50');
              firstPageButton.dataset.page = 1;
              pageNumbersContainer.appendChild(firstPageButton);
         }


        for (let i = startPage; i <= endPage; i++) {
            const pageButton = document.createElement('a');
            pageButton.href = "#"; // Prevent default link behavior
            pageButton.textContent = i;
            pageButton.classList.add('relative', 'inline-flex', 'items-center', 'px-4', 'py-2', 'border', 'border-gray-300', 'bg-white', 'text-sm', 'font-medium', 'text-gray-700', 'hover:bg-gray-50');
            if (i === currentPage) {
                pageButton.classList.add('z-10', 'bg-blue-50', 'border-blue-500', 'text-blue-600');
            }
            pageButton.dataset.page = i; // Store page number
            pageNumbersContainer.appendChild(pageButton);
        }

         // Show last page dot if not in visible range
         if (endPage < totalPages) {
              const dot = document.createElement('span');
              dot.textContent = '...';
              dot.classList.add('relative', 'inline-flex', 'items-center', 'px-4', 'py-2', 'text-sm', 'font-medium', 'text-gray-700');
               pageNumbersContainer.appendChild(dot);

               const lastPageButton = document.createElement('a');
               lastPageButton.href = "#";
               lastPageButton.textContent = totalPages;
               lastPageButton.classList.add('relative', 'inline-flex', 'items-center', 'px-4', 'py-2', 'border', 'border-gray-300', 'bg-white', 'text-sm', 'font-medium', 'text-gray-700', 'hover:bg-gray-50');
               lastPageButton.dataset.page = totalPages;
               pageNumbersContainer.appendChild(lastPageButton);
          }
    }

    // Update Summary Card counts based on filtered items
    function updateSummaryCards() {
         const total = currentItems.length; // Base on the filtered list
         let inStock = 0;
         let lowStock = 0;
         let outOfStock = 0;

         currentItems.forEach(item => {
              // Ensure quantity is treated as a number
             const quantity = parseInt(item.quantity) || 0;
             const status = calculateStatus(quantity);
             if (status === 'in-stock') inStock++;
             else if (status === 'low-stock') lowStock++;
             else if (status === 'out-of-stock') outOfStock++;
         });

         totalItemsCount.textContent = total;
         inStockCount.textContent = inStock;
         lowStockCount.textContent = lowStock;
         outOfStockCount.textContent = outOfStock;

         console.log("Summary cards updated. Total filtered:", total, "In Stock:", inStock, "Low Stock:", lowStock, "Out of Stock:", outOfStock);
    }

    // Initialize or update the Category Distribution Chart
    function updateCategoryChart(items) {
        const categoryCounts = {};
        items.forEach(item => {
            const category = item.category || 'Uncategorized';
            categoryCounts[category] = (categoryCounts[category] || 0) + 1;
        });

        const labels = Object.keys(categoryCounts);
        const data = Object.values(categoryCounts);

         // Define some consistent colors (can be expanded) - Using a mix of Tailwind colors
         const chartColors = [
             'rgba(59, 246, 168, 0.8)', // blue-500
             'rgba(27, 144, 222, 0.8)', // green-500
             'rgba(245, 158, 11, 0.8)', // yellow-500
             'rgba(239, 68, 68, 0.8)', // red-500
             'rgba(234, 241, 99, 0.8)', // indigo-500
             'rgba(147, 51, 234, 0.8)', // purple-500
             'rgba(236, 72, 153, 0.8)', // pink-500
             'rgba(107, 114, 128, 0.8)' // gray-500 (for Uncategorized or others)
         ];
         const borderColors = chartColors.map(color => color.replace('0.8', '1')); // Solid border

        if (categoryChartInstance) {
            // Update existing chart
            categoryChartInstance.data.labels = labels;
            categoryChartInstance.data.datasets[0].data = data;
             // Ensure we have enough colors for the data points
            categoryChartInstance.data.datasets[0].backgroundColor = chartColors.slice(0, labels.length);
            categoryChartInstance.data.datasets[0].borderColor = borderColors.slice(0, labels.length);

            categoryChartInstance.update();
            console.log("Category chart updated.");
        } else {
            // Initialize new chart
            const ctx = categoryChartCanvas.getContext('2d');
            categoryChartInstance = new Chart(ctx, {
                type: 'doughnut', // Pie chart for distribution
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                         // Ensure we have enough colors for the data points
                        backgroundColor: chartColors.slice(0, labels.length),
                        borderColor: borderColors.slice(0, labels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right', // Position legend on the right
                        },
                        title: {
                             display: false, // Title handled by parent div
                             text: 'Inventory Distribution by Category'
                        },
                         tooltip: { // Use tooltip for Chart.js v3+
                             callbacks: {
                                 label: function(tooltipItem) {
                                     const dataset = categoryChartInstance.data.datasets[tooltipItem.datasetIndex];
                                     const total = dataset.data.reduce((previousValue, currentValue) => previousValue + currentValue);
                                     const currentValue = dataset.data[tooltipItem.dataIndex];
                                     const percentage = total > 0 ? Math.round(((currentValue/total) * 100)) : 0;
                                     return `${categoryChartInstance.data.labels[tooltipItem.dataIndex]}: ${currentValue} (${percentage}%)`;
                                 }
                             }
                         }
                    }
                     // For Chart.js v2, use 'tooltips' instead of 'tooltip'
                     // tooltips: {
                     //      callbacks: { ... }
                     // }
                }
            });
            console.log("Category chart initialized.");
        }
    }


    // =======================================================
    // Modal Handling (Add/Edit and Details)
    // =======================================================

    // Open Add/Edit Item Modal
    function openItemModal(item = null) {
        // Reset form and title
        resetItemForm();
        if (item) {
            modalTitle.textContent = 'Edit Item';
            populateItemForm(item); // Populate form for editing
            itemIdInput.value = item.id; // Set hidden ID
        } else {
            modalTitle.textContent = 'Add New Item';
            itemIdInput.value = ''; // Clear hidden ID for new item
        }
        itemModal.classList.remove('hidden'); // Show the modal
         // Prevent body scrolling when modal is open
         document.body.classList.add('overflow-hidden');
    }

    // Close Add/Edit Item Modal
    function closeItemModal() {
        itemModal.classList.add('hidden'); // Hide the modal
        resetItemForm(); // Reset the form
         // Restore body scrolling
         document.body.classList.remove('overflow-hidden');

         // Reset image preview specifically
         resetImagePreview();
    }

    // Populate Add/Edit form with item data (for editing)
    function populateItemForm(item) {
        itemIdInput.value = item.id || '';
        itemNameInput.value = item.name || '';
        itemCategorySelect.value = item.category || 'other'; // Default if not set
        itemQuantityInput.value = item.quantity || 0;
        itemUnitSelect.value = item.unit || 'pieces'; // Default if not set
        itemLocationInput.value = item.location || '';
        itemNotesTextarea.value = item.notes || '';

        // Handle image preview
        if (item.image_url) {
            showImagePreview(item.image_url);
             existingItemImageInput.value = item.image_url; // Store existing image URL
        } else {
            resetImagePreview();
             existingItemImageInput.value = '';
        }
         // Ensure remove existing flag is reset
         removeItemBtn.dataset.remove = 'false'; // Reset the remove flag when populating for edit
    }

    // Reset Add/Edit form to default empty state
    function resetItemForm() {
        itemIdInput.value = '';
        itemNameInput.value = '';
        itemCategorySelect.value = 'equipment'; // Default value
        itemQuantityInput.value = 0; // Default value
        itemUnitSelect.value = 'pieces'; // Default value
        itemLocationInput.value = '';
        itemNotesTextarea.value = '';
        itemImageInput.value = ''; // Clear file input value
        existingItemImageInput.value = ''; // Clear hidden existing image field
        resetImagePreview(); // Reset image preview area
         removeItemBtn.dataset.remove = 'false'; // Reset remove flag
    }

    // Show selected image preview in the modal form
    function showImagePreview(src) {
        previewImageElement.src = src;
        imageUploadArea.classList.add('hidden');
        imagePreview.classList.remove('hidden');
    }

    // Reset image preview area in the modal form
    function resetImagePreview() {
         previewImageElement.src = '#'; // Reset src
        imageUploadArea.classList.remove('hidden');
        imagePreview.classList.add('hidden');
         itemImageInput.value = ''; // Clear file input value
         existingItemImageInput.value = ''; // Clear hidden existing image field
         removeItemBtn.dataset.remove = 'false'; // Reset remove flag
    }


    // Open View Item Details Modal
    async function showItemDetails(itemId) {
        console.log('Attempting to show details for item ID:', itemId);

         // Set the global variable for current item ID being viewed
         currentItemDetailsId = itemId;

        // Fetch full item details (including activities) from the server
        const item = await fetchItemDetails(itemId);

        if (item) {
            // Populate Item Details Modal with data
            itemDetailsTitle.textContent = item.name || 'Item Details'; // Use name for title
            itemDetailsSubtitle.textContent = `Category: ${item.category || 'N/A'}`;

             // Basic Info
            itemDetailsNameDisplay.textContent = item.name || 'N/A';
            itemDetailsCategoryDisplay.textContent = item.category || 'N/A';
            itemDetailsUnitDisplay.textContent = item.unit || '-';
            itemDetailsLocationDisplay.textContent = item.location || 'N/A';

             // Status - Calculate and display with color class
             const quantity = parseInt(item.quantity) || 0; // Ensure quantity is number
             const itemStatus = calculateStatus(quantity); // Use JS helper function
             const statusClass = getStatusClass(itemStatus); // Use JS helper function
             itemDetailsStatusDisplay.innerHTML = `<span class="status-badge ${statusClass}">${itemStatus.replace('-', ' ').toUpperCase()}</span>`;


            itemDetailsQuantityTimeline.textContent = `${item.quantity || 0} ${item.unit || ''}`; // Display quantity in timeline

            itemDetailsNotesDisplay.textContent = item.notes || 'No notes available';

            // Handle Image Display
            itemDetailsImage.src = item.image_url || './images/default_inventory.png';
            itemDetailsImage.alt = item.name || 'Item Image';

            // Populate Timeline Dates
            itemDetailsAddedDate.textContent = item.createdAt ? formatDateTime(item.createdAt) : '-';
            itemDetailsUpdatedDate.textContent = item.updatedAt ? formatDateTime(item.updatedAt) : '-';


            // Populate Activities (assuming 'activities' array is in the item response)
            renderItemActivities(item.activities || []); // Pass the activities array


             // Set the Edit button's data-id to the server ID for quick access
            editItemFromDetailsBtn.dataset.id = item.id; // Ensure this button exists in your HTML


             // --- ADDED & FIXED: Control Previous/Next button states based on currentItems array ---
             // Find the current item's index in the list of currently displayed items (currentItems)
             // This array represents the filtered/sorted/paginated view the user is Browse
             // Use parseInt for comparison as item.id might be number and currentItemDetailsId is string
             const currentItemIndex = currentItems.findIndex(item => item.id === parseInt(currentItemDetailsId));

             if (currentItemIndex !== -1) { // Ensure the item is found in the current list
                  // Disable Previous button if it's the first item in the current list
                  prevItemBtn.disabled = currentItemIndex === 0;
                  prevItemBtn.classList.toggle('disabled:opacity-50', currentItemIndex === 0);
                  prevItemBtn.classList.toggle('disabled:cursor-not-allowed', currentItemIndex === 0);

                  // Disable Next button if it's the last item in the current list
                  nextItemBtn.disabled = currentItemIndex === currentItems.length - 1;
                  nextItemBtn.classList.toggle('disabled:opacity-50', currentItemIndex === currentItems.length - 1);
                  nextItemBtn.classList.toggle('disabled:cursor-not-allowed', currentItemIndex === currentItems.length - 1);

             } else {
                 // If the item is not found in the current list (e.g., opened from search result not in current page)
                 console.warn("Current item ID " + currentItemDetailsId + " not found in the current list (currentItems). Disabling navigation buttons.");
                 prevItemBtn.disabled = true; // Disable both if not found
                 nextItemBtn.disabled = true;
                  prevItemBtn.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');
                  nextItemBtn.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');
             }

            itemDetailsModal.classList.remove('hidden'); // Show the details modal
             document.body.classList.add('overflow-hidden'); // Prevent body scrolling

        } else {
            // fetchItemDetails will already show a toast if it fails
            console.log('Item data not found or fetch failed for ID:', itemId);
             // Ensure the modal is hidden if data fetch failed
             closeDetailsModal(); // Use the new function to close modal on fetch failure
             // itemDetailsModal.classList.add('hidden'); // Removed - handled by closeDetailsModal
             // document.body.classList.remove('overflow-hidden'); // Removed - handled by closeDetailsModal
             // currentItemDetailsId = null; // Removed - handled by closeDetailsModal
        }
    }


    // Render activities list for a single item
    function renderItemActivities(activities) {
        itemActivitiesList.innerHTML = ''; // Clear previous activities

        if (!activities || activities.length === 0) {
            itemActivitiesList.innerHTML = `
                <div class="text-center py-4 text-sm text-gray-500">
                    <i class="fas fa-info-circle mr-2"></i> No recent activities recorded for this item.
                </div>`;
            return;
        }

         // Sort activities by timestamp (newest first)
         activities.sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime());


        activities.forEach(activity => {
            const activityType = activity.type || 'activity';
            const activityIcon = getInventoryActivityIcon(activityType); // Use inventory specific icon function
            const iconColorClass = getInventoryActivityIconColor(activityType); // Use inventory specific color function
            const notes = activity.notes || 'No notes';
            const timestamp = activity.timestamp ? formatDateTime(activity.timestamp) : '-';
            const quantityChange = activity.quantity_change !== null ? `Quantity Change: ${activity.quantity_change}` : ''; // Display quantity change if available

            const activityHtml = `
                <div class="activity-item flex items-start space-x-3 bg-white p-3 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex-shrink-0 h-8 w-8 rounded-full ${iconColorClass} flex items-center justify-center text-white">
                        <i class="${activityIcon} text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 capitalize">${activityType.replace(/_/g, ' ')}</p>
                        <p class="text-xs text-gray-700">${notes}</p>
                         ${quantityChange ? `<p class="text-xs text-gray-700 font-semibold">${quantityChange}</p>` : ''}
                    </div>
                    <div class="flex-shrink-0 text-xs text-gray-500 text-right">
                        ${timestamp}
                    </div>
                </div>
            `;
            itemActivitiesList.innerHTML += activityHtml;
        });
    }

    // Get icon class for inventory activity type
    function getInventoryActivityIcon(type) {
        switch (type) {
            case 'item_added': return 'fas fa-box';
            case 'item_updated': return 'fas fa-sync-alt';
            case 'item_deleted': return 'fas fa-trash-alt';
            case 'stock_added': return 'fas fa-plus-square'; // New activity type for stock changes
            case 'stock_removed': return 'fas fa-minus-square'; // New activity type for stock changes
            case 'item_damaged': return 'fas fa-broken'; // Example icon for damaged
            case 'item_low_stock': return 'fas fa-exclamation-triangle';
            case 'item_out_of_stock': return 'fas fa-times-circle';
            default: return 'fas fa-clipboard-list'; // Default icon
        }
    }

    // Get color class for inventory activity icon
    function getInventoryActivityIconColor(type) {
         switch (type) {
            case 'item_added': return 'bg-blue-500';
            case 'item_updated': return 'bg-green-500';
            case 'item_deleted': return 'bg-gray-500'; // Gray for deleted? Or Red?
            case 'stock_added': return 'bg-purple-500'; // Purple for stock increase
            case 'stock_removed': return 'bg-orange-500'; // Orange for stock decrease
            case 'item_damaged': return 'bg-red-500'; // Red for damage
            case 'item_low_stock': return 'bg-yellow-500'; // Yellow for low stock alert
            case 'item_out_of_stock': return 'bg-red-600'; // Darker red for out of stock alert
            default: return 'bg-blue-500'; // Default color
        }
    }


    // Format date and time
    function formatDateTime(timestamp) {
        if (!timestamp) return '-';
        // Assuming timestamp is in a format Date() can parse (e.g., "YYYY-MM-DD HH:MM:SS" from PHP)
        const date = new Date(timestamp);
        if (isNaN(date)) {
            console.warn("Invalid timestamp received:", timestamp);
             return timestamp; // Return original if invalid
        }
         // Format as "MMM DD,YYYY HH:MM AM/PM"
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    // Show success toast notification
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
        }, 3000); // Hide after 3 seconds
    }

    // Show error toast notification
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
        }, 5000); // Hide after 5 seconds
    }


    // =======================================================
    // Event Listener Setup
    // =======================================================

    function setupEventListeners() {
        console.log("Setting up event listeners...");

        // Sidebar toggle
        sidebarToggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
            });

         // Close sidebar when clicking outside on smaller screens (optional)
         // document.addEventListener('click', (event) => {
         //     if (!sidebar.contains(event.target) && !sidebarToggleBtn.contains(event.target) && !sidebar.classList.contains('-translate-x-full')) {
         //         sidebar.classList.add('-translate-x-full');
         //     }
         // });


        // User profile dropdown toggle
        userMenuBtn.addEventListener('click', (event) => {
            userMenuDropdown.classList.toggle('hidden');
            event.stopPropagation(); // Prevent click from closing dropdown immediately
        });

        // Close user profile dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!userMenuDropdown.contains(event.target) && !userMenuBtn.contains(event.target)) { // Use target here
                userMenuDropdown.classList.add('hidden');
            }
        });

        // Notifications dropdown toggle (Basic)
        notificationsBtn.addEventListener('click', (event) => {
            notificationsDropdown.classList.toggle('hidden');
             // Ideally, fetch notifications here or mark as read
             loadNotifications(); // Call function to load/display notifications
            event.stopPropagation();
        });

         // Close notifications dropdown when clicking outside
        document.addEventListener('click', (event) => {
             const target = event.target; // Define target here
            if (!notificationsDropdown.contains(target) && !notificationsBtn.contains(target)) {
                 notificationsDropdown.classList.add('hidden');
            }
        });

         // Basic Notification Loading (Placeholder)
         // You need a server API endpoint for fetching notifications
         async function loadNotifications() {
             notificationsList.innerHTML = '<div class="text-center py-4 text-sm text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading notifications...</div>';
             // Example: Fetch from a new API endpoint
             try {
                // const result = await apiRequest('get_notifications', 'GET', { userId: currentUserId });
                 // Assuming a successful response looks like { success: true, notifications: [...] }
                // if (result.success && Array.isArray(result.notifications)) {
                    // Use dummy data for now
                     const dummyNotifications = [
                         { id: 1, message: "Item 'Seeds' is low in stock.", timestamp: "2025-05-17 09:00:00", read: false },
                         { id: 2, message: "Item 'Fertilizer X' is out of stock.", timestamp: "2025-05-17 10:30:00", read: true },
                         { id: 3, message: "New item 'Pesticide Y' added.", timestamp: "2025-05-16 15:45:00", read: false },
                     ];
                    renderNotifications(dummyNotifications);
                    // Update badge count for unread notifications
                     const unreadCount = dummyNotifications.filter(n => !n.read).length;
                     if(unreadCount > 0) {
                         notificationCountBadge.textContent = unreadCount;
                         notificationCountBadge.classList.remove('hidden');
                     } else {
                         notificationCountBadge.classList.add('hidden');
                     }

                // } else {
                    // renderNotifications([]); // Render empty if fetch fails
                // }
             } catch (error) {
                 console.error("Failed to load notifications:", error);
                 renderNotifications([]); // Render empty on error
             }
         }

         // Render notifications in the dropdown
         function renderNotifications(notifications) {
             notificationsList.innerHTML = ''; // Clear previous
             if (notifications.length === 0) {
                  notificationsList.innerHTML = '<div class="text-center py-4 text-sm text-gray-500">No new notifications.</div>';
                  return;
             }
             notifications.forEach(notification => {
                 const notificationHtml = `
                     <div class="notification-item ${notification.read ? '' : 'unread'}" data-id="${notification.id}">
                         <p class="text-sm text-gray-800">${notification.message}</p>
                         <p class="text-xs text-gray-500">${formatDateTime(notification.timestamp)}</p>
                     </div>
                 `;
                 notificationsList.innerHTML += notificationHtml;
             });
              // Add event listeners to notification items if needed (e.g., to mark as read)
         }


        // Add New Item button click
        addItemBtn.addEventListener('click', () => {
            openItemModal(); // Open modal for adding
        });

        // Save Item button click (in Add/Edit modal)
        saveItemBtn.addEventListener('click', async () => {
            // Gather data from the form
            const itemId = itemIdInput.value;
            const isUpdate = !!itemId; // True if item-id has a value (editing)

            const itemData = {
                id: isUpdate ? parseInt(itemId) : null, // Send ID as number for update
                name: itemNameInput.value.trim(),
                category: itemCategorySelect.value,
                quantity: parseInt(itemQuantityInput.value) || 0, // Ensure quantity is number
                unit: itemUnitSelect.value,
                location: itemLocationInput.value.trim(),
                notes: itemNotesTextarea.value.trim(),
                // userId will be added in apiRequest helper
            };

             // Add image file if selected
            if (itemImageInput.files && itemImageInput.files[0]) {
                itemData.imageFile = itemImageInput.files[0];
                 // Also signal if an existing image should be removed and replaced
                 if (existingItemImageInput.value) {
                      itemData.removeExistingImage = true;
                      itemData.existingImage = existingItemImageInput.value; // Send URL to server for deletion
                 }
            } else if (isUpdate && removeItemBtn.dataset.remove === 'true') {
                 // Signal to remove existing image if the remove button was clicked
                 itemData.removeExistingImage = true;
                  itemData.existingImage = existingItemImageInput.value; // Send URL to server for deletion
             } else if (isUpdate && existingItemImageInput.value) {
                 // If editing and no new file or remove clicked, send existing path back
                 itemData.existingImage = existingItemImageInput.value;
             }


            // Basic form validation
            if (!itemData.name || itemData.quantity === null || itemData.quantity < 0 || !itemData.unit) { // Check quantity strictly for null/undefined and negativity
                showErrorToast('Please fill in required fields (Name, Quantity, Unit) and ensure Quantity is non-negative.');
                return; // Stop if validation fails
            }

            try {
                // apiRequest helper handles FormData creation from itemData
                const result = await saveItemToServer(itemData, isUpdate);

                if (result.success) {
                    closeItemModal(); // Close modal on success
                    await loadInventory(); // Reload inventory list from server
                }
                // apiRequest and saveItemToServer handle showing error toasts

            } catch (error) {
                console.error("Error during saveItemBtn click:", error);
                // Error toast is shown by saveItemToServer
            }
        });

        cancelIconBtn.addEventListener('click', () => {
            closeDetailsModal(); // Close the modal
        });

        // Cancel Item button click (in Add/Edit modal)
        cancelItemBtn.addEventListener('click', () => {
            closeItemModal(); // Close the modal
        });

         // Close Details Modal button click
        closeDetailsBtn.addEventListener('click', () => {
             closeDetailsModal(); // Call the dedicated function
        });


        // Delegate click events for View, Edit, Delete buttons (using event delegation)
        // This is more efficient than adding listeners to each button individually
        document.addEventListener('click', async (event) => {
            const target = event.target;
            const actionButton = target.closest('.action-btn'); // Find the closest button with class 'action-btn'

            if (actionButton) {
                const action = actionButton.dataset.action;
                const itemId = actionButton.dataset.id;

                if (!itemId) {
                    console.warn("Action button clicked without an item ID:", actionButton);
                    return; // Exit if no ID
                }

                console.log(`Action "${action}" clicked for item ID: ${itemId}`);

                switch (action) {
                    case 'view':
                        showItemDetails(itemId); // Open details modal
                        break;
                    case 'edit':
                        // Find the item data in the currentItems array (the filtered/paginated list)
                        const itemToEdit = currentItems.find(item => item.id == itemId); // Use == for comparison

                        if (itemToEdit) {
                             console.log("Editing item:", itemToEdit);
                            // Close details modal if open before opening edit modal
                            if (!itemDetailsModal.classList.contains('hidden')) {
                                closeDetailsModal(); // Use the dedicated close function
                            }
                            openItemModal(itemToEdit); // Open modal for editing

                        } else {
                            console.error("Item not found in current list for editing:", itemId);
                            // Optionally re-fetch from server or show error
                            showErrorToast('Item data not found in the current list. Please reload the page.');
                             // Fallback: Fetch details from server if not in currentItems
                             // const itemFromServer = await fetchItemDetails(itemId);
                             // if (itemFromServer) { openItemModal(itemFromServer); } else { showErrorToast(...); }
                        }
                        break;
                    case 'delete':
                        // Ask for confirmation before deleting
                        if (confirm(`Are you sure you want to delete item ID ${itemId}? This action cannot be undone.`)) {
                            try {
                                await deleteItemFromServer(itemId); // Delete from server
                                await loadInventory(); // Reload list after deletion
                                 // Close details modal if the deleted item was being viewed
                                if (currentItemDetailsId == itemId && !itemDetailsModal.classList.contains('hidden')) {
                                     closeDetailsModal(); // Use the dedicated close function
                                     // itemDetailsModal.classList.add('hidden'); // Removed - handled by closeDetailsModal
                                     // document.body.classList.remove('overflow-hidden'); // Removed - handled by closeDetailsModal
                                     // currentItemDetailsId = null; // Removed - handled by closeDetailsModal
                                }
                            } catch (error) {
                                console.error("Error during delete action:", error);
                                // Error toast is shown by deleteItemFromServer
                            }
                        }
                        break;
                     // Add other actions if needed
                }
            }

             // Close dropdowns if clicking outside
             if (!userMenuDropdown.classList.contains('hidden') && !userMenuBtn.contains(target)) {
                 userMenuDropdown.classList.add('hidden');
             }
             if (!notificationsDropdown.classList.contains('hidden') && !notificationsBtn.contains(target)) {
                  notificationsDropdown.classList.add('hidden');
             }
             // Close search results if clicking outside
              if (!topSearchResults.classList.contains('hidden') && !topSearchInput.contains(target)) {
                 topSearchResults.classList.add('hidden');
             }
        });

         // Event listener for Search Input (Top Nav - for quick search/jump)
        topSearchInput.addEventListener('input', async () => {
             const searchTerm = topSearchInput.value.trim();

             if (searchTerm.length < 2) { // Require at least 2 characters to search
                 topSearchResults.classList.add('hidden');
                 topSearchResults.innerHTML = '';
                 return;
             }

             // Perform a quick search via API
             try {
                // Assuming a specific API action for quick search
                 // This search should ideally be faster and return minimal item data (id, name, category, qty)
                 const result = await apiRequest('search', 'POST', { query: searchTerm, userId: currentUserId });

                 if (result.success && Array.isArray(result.items)) { // Expect 'items' array in response
                     renderTopSearchResults(result.items);
                 } else {
                      renderTopSearchResults([]); // Render empty if no results or error
                 }
             } catch (error) {
                 console.error("Error during top search:", error);
                 renderTopSearchResults([]); // Render empty on error
             }
        });

         // Render results in the top search dropdown
        function renderTopSearchResults(items) {
             topSearchResults.innerHTML = ''; // Clear previous results

             if (items.length === 0) {
                 topSearchResults.classList.add('hidden'); // Hide if no results
                 return;
             }

             items.forEach(item => {
                 // Use item.id, item.name, etc. based on what the 'search' API action returns
                 const itemHtml = `
                     <div class="search-item" data-id="${item.id}">
                         <p class="text-sm font-medium text-gray-900">${item.name || 'Unnamed Item'}</p>
                         <p class="text-xs text-gray-500">Category: ${item.category || 'N/A'} | Qty: ${item.quantity || 0}</p>
                     </div>
                 `;
                 topSearchResults.innerHTML += itemHtml;
             });

             topSearchResults.classList.remove('hidden'); // Show results dropdown
        }

        // Event listener for clicking on a search result item in the top nav dropdown
         topSearchResults.addEventListener('click', (event) => {
             const searchItemElement = event.target.closest('.search-item');
             if (searchItemElement) {
                 const itemId = searchItemElement.dataset.id;
                 if (itemId) {
                     // Open the details modal directly from the search result
                     showItemDetails(itemId);
                     topSearchResults.classList.add('hidden'); // Hide results after selection
                     topSearchInput.value = ''; // Clear search input
                 }
             }
         });


        // Event listeners for Filters and Sort
        searchInventoryInput.addEventListener('input', applyFiltersAndSort); // Main search input
        categoryFilter.addEventListener('change', applyFiltersAndSort);
        statusFilter.addEventListener('change', applyFiltersAndSort);
        sortBySelect.addEventListener('change', applyFiltersAndSort);

        // Event listeners for View Toggle
        viewToggleButton.addEventListener('click', () => {
            if (currentViewMode === 'table') {
                currentViewMode = 'card';
                viewToggleButton.innerHTML = '<i class="fas fa-list-alt mr-2"></i> Card View';
            } else {
                currentViewMode = 'table';
                viewToggleButton.innerHTML = '<i class="fas fa-table mr-2"></i> Table View';
            }
            // Preserve current page and filters when toggling view
            // Reset to page 1 might be better UX if the list changes drastically
            // currentPage = 1; // Option: reset page on view toggle
            applyFiltersAndSort(); // Re-render with the new view mode
             // Scroll the main content area to the top
             document.querySelector('main.overflow-y-auto').scrollTop = 0;
        });

        // Event listener for Pagination (using event delegation on the page numbers container)
        pageNumbersContainer.addEventListener('click', (event) => {
            const target = event.target;
            // Check if the click was on a page number button (which are <a> tags with data-page)
            if (target.tagName === 'A' && target.dataset.page) {
                event.preventDefault(); // Prevent default link behavior (#)
                const page = parseInt(target.dataset.page);
                if (page !== currentPage) {
                    currentPage = page;
                    applyFiltersAndSort(); // Re-render for the new page
                     // Scroll the main content area to the top of the list section
                     document.querySelector('main.overflow-y-auto').scrollTop = tableViewContainer.parentElement.offsetTop; // Scroll to the top of the section containing the list
                }
            }
        });

        // Event listeners for Previous and Next Page buttons
        prevPageBtn.addEventListener('click', (event) => {
            event.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                applyFiltersAndSort(); // Re-render for the previous page
                 // Scroll the main content area to the top
                 document.querySelector('main.overflow-y-auto').scrollTop = tableViewContainer.parentElement.offsetTop;
            }
        });

        nextPageBtn.addEventListener('click', (event) => {
            event.preventDefault();
            const totalItems = currentItems.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                applyFiltersAndSort(); // Re-render for the next page
                 // Scroll the main content area to the top
                 document.querySelector('main.overflow-y-auto').scrollTop = tableViewContainer.parentElement.offsetTop;
            }
        });

         // Event listener for Image File Input change (in Add/Edit modal)
        itemImageInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                // Validate file type and size
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; // Add allowed types
                const maxSize = 5 * 1024 * 1024; // 5MB in bytes

                if (!allowedTypes.includes(file.type)) {
                    showErrorToast('Invalid file type. Only JPG, PNG, and GIF images are allowed.');
                    resetImagePreview(); // Clear input and preview
                    return;
                }

                if (file.size > maxSize) {
                    showErrorToast('Image file size exceeds the maximum limit (5MB).');
                    resetImagePreview(); // Clear input and preview
                    return;
                }

                // Read the file and show a preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    showImagePreview(e.target.result);
                     // Mark existing image for removal if a new one is selected
                     if (existingItemImageInput.value) {
                          removeItemBtn.dataset.remove = 'true';
                     }
                };
                reader.readAsDataURL(file);
            } else {
                // If file selection was cancelled, reset preview only if no existing image
                 if (!existingItemImageInput.value) {
                    resetImagePreview();
                 }
            }
        });

         // Event listener for Remove Image button (in Add/Edit modal)
         removeItemBtn.addEventListener('click', () => {
             // Set a flag to indicate the existing image should be removed on save
             removeItemBtn.dataset.remove = 'true';
             resetImagePreview(); // Clear the preview
         });


         // --- Event Listeners for Previous and Next Buttons in Details Modal ---
        // Ensure buttons exist in HTML before adding listeners
        if (prevItemBtn && nextItemBtn && editItemFromDetailsBtn) {

             prevItemBtn.addEventListener('click', () => {
                  console.log("Previous button clicked in details modal.");
                  // Check array state and current item ID just before navigation
                  console.log("allInventoryItems length:", allInventoryItems.length); // Should be populated by loadInventory
                  console.log("currentItems length:", currentItems.length); // Should be populated by applyFiltersAndSort
                  console.log("currentItemDetailsId:", currentItemDetailsId); // ID of the currently displayed item

                  if (!currentItemDetailsId) {
                       console.warn("Cannot navigate Previous: No current item ID determined.");
                       return; // Exit if no ID
                  }

                  // Use currentItems array (which is filtered/sorted/paginated view) for navigation
                  // This is the array the user sees and expects to navigate within
                  if (currentItems.length === 0) {
                      console.warn("Cannot navigate Previous: currentItems array is empty.");
                       // This shouldn't happen if details modal is open from a list that has items
                       // Maybe close modal if array is empty unexpectedly?
                       // closeDetailsModal(); // Use the dedicated close function
                       return; // Exit if array is empty
                  }

                  // Find the index of the currently displayed item in the currentItems array
                  // Use parseInt for comparison as item.id might be number and currentItemDetailsId is string from data-id
                  const currentIndex = currentItems.findIndex(item => item.id === parseInt(currentItemDetailsId));

                  if (currentIndex === -1) {
                       console.warn(`Cannot navigate Previous: Current item ID ${currentItemDetailsId} not found in the currentItems array (length: ${currentItems.length}).`);
                       // This indicates a data mismatch or issue with currentItems state
                        // Optionally close modal if the item isn't in the list
                        // closeDetailsModal(); // Use the dedicated close function
                       return; // Exit if current item not found in the list
                  }

                  console.log(`Current item ID ${currentItemDetailsId} found at index ${currentIndex} in currentItems.`);

                  // Check if we are not at the first item in the currentItems array
                  // currentIndex > 0 means there is a previous item (index 0 is the first)
                  if (currentIndex > 0) {
                      // Safe to access currentItems[currentIndex - 1] here because currentIndex is valid and > 0
                      const prevItem = currentItems[currentIndex - 1];
                      console.log("Navigating to previous item ID:", prevItem.id);
                      // Call showItemDetails to load and show details for the previous item
                      showItemDetails(prevItem.id);
                  } else {
                      console.log("Already at the first item in the current list.");
                      // Button should be disabled by showItemDetails - this log confirms we are at the boundary
                  }
             });

             // Add listener for Next button in Details Modal
             nextItemBtn.addEventListener('click', () => {
                  console.log("Next button clicked in details modal.");
                  // Check array state and current item ID just before navigation
                  console.log("allInventoryItems length:", allInventoryItems.length); // Should be populated by loadInventory
                  console.log("currentItems length:", currentItems.length); // Should be populated by applyFiltersAndSort
                  console.log("currentItemDetailsId:", currentItemDetailsId); // ID of the currently displayed item


                   if (!currentItemDetailsId) {
                       console.warn("Cannot navigate Next: No current item ID determined.");
                       return; // Exit if no ID
                  }

                   // Use currentItems array for navigation
                  if (currentItems.length === 0) {
                      console.warn("Cannot navigate Next: currentItems array is empty.");
                       // Optionally close modal if array is empty unexpectedly?
                       // closeDetailsModal(); // Use the dedicated close function
                       return; // Exit if array is empty
                  }

                   // Find the index of the currently displayed item in the currentItems array
                  const currentIndex = currentItems.findIndex(item => item.id === parseInt(currentItemDetailsId));

                   if (currentIndex === -1) {
                       console.warn(`Cannot navigate Next: Current item ID ${currentItemDetailsId} not found in the currentItems array (length: ${currentItems.length}).`);
                        // Optionally close modal if the item isn't in the list
                        // closeDetailsModal(); // Use the dedicated close function
                       return; // Exit if current item not found in the list
                   }

                  console.log(`Current item ID ${currentItemDetailsId} found at index ${currentIndex} in currentItems.`);


                  // Check if we are not at the last item in the currentItems array
                  // currentItems.length - 1 is the index of the last item
                  if (currentIndex < currentItems.length - 1) {
                      // Safe to access currentItems[currentIndex + 1] here because currentIndex is valid and not the last
                      const nextItem = currentItems[currentIndex + 1];
                      console.log("Navigating to next item ID:", nextItem.id);
                      // Call showItemDetails to load and show details for the next item
                      showItemDetails(nextItem.id);
                  } else {
                      console.log("Already at the last item in the current list.");
                      // Button should be disabled by showItemDetails - this log confirms we are at the boundary
                  }
             });

             // Add listener for the Edit button inside the Details Modal
             // This button should open the Add/Edit modal populated with current item data
             editItemFromDetailsBtn.addEventListener('click', async () => {
                  console.log("Edit button clicked inside Details Modal.");
                   // The data-id is already set by showItemDetails
                  const itemId = editItemFromDetailsBtn.dataset.id;

                  if (itemId) {
                       // Find the item in the currentItems array to populate the form
                       const itemToEdit = currentItems.find(item => item.id == itemId); // Use ==

                       if (itemToEdit) {
                           console.log("Opening edit modal for item:", itemToEdit);
                           // Close the Details modal before opening the Edit modal
                           closeDetailsModal(); // Use the dedicated close function

                           // Open the Add/Edit modal with the item data
                           openItemModal(itemToEdit);
                       } else {
                           console.error("Item not found in current list for editing from details modal:", itemId);
                           showErrorToast('Item data not found in the current list. Please reload the page.');
                            // Fallback: Fetch details from server if not in currentItems
                            // const itemFromServer = await fetchItemDetails(itemId);
                            // if (itemFromServer) { openItemModal(itemFromServer); closeDetailsModal(); } else { showErrorToast(...); }
                       }
                  } else {
                      console.warn("Edit button in details modal clicked without data-id.");
                  }
             });

        } else {
             console.warn("Previous, Next, or Edit button in Details Modal not found. Navigation and direct edit from details disabled.");
             // Handle case where these buttons are not in HTML (less likely based on provided code)
             // Maybe hide or disable the entire footer section of the details modal?
        }


    } // End setupEventListeners


     // =======================================================
    // Application Initialization and Entry Point
    // =======================================================

    // Initialize the application when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', async function() {
        console.log('DOM fully loaded. Initializing inventory application...');

        // Ensure currentUserId is available
        if (currentUserId === null || currentUserId === 'null' || typeof currentUserId === 'undefined') {
            console.error("User ID is not available. Cannot initialize inventory.");
            // Redirect to login or show an error message to the user
            showErrorToast("User not authenticated. Please log in.");
            // Example: Redirect to login page after a delay
             setTimeout(() => { window.location.href = 'login.php'; }, 3000); // Redirect after 3 seconds
            return; // Stop initialization
        }


        try {
            // 1. Setup UI event listeners
            setupEventListeners();

            // 2. Initialize the category chart (pass an empty array initially)
            updateCategoryChart([]); // Initialize with empty data

            // 3. Load initial data from Server API and render UI
            console.log('Loading initial inventory data...');
            await loadInventory(); // This function handles fetching, and initial rendering

            console.log('Inventory application initialization complete.');

        } catch (error) {
            console.error('Inventory Application Initialization error:', error);
            showErrorToast('Inventory Application initialization failed: ' + error.message);
            // Optionally attempt to reload the page after a delay if initialization fails critically
            // setTimeout(() => { window.location.reload(); }, 5000); // 5 seconds delay before reload
        }
    });

    </script>

    </body>
</html>