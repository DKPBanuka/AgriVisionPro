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

// Helper functions for Crop-specific status
// The following functions are implemented in JavaScript below, not in PHP.
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriVision Pro | Crop Management</title>
    <link rel="icon" href="./images/logo1.png" type="image/png">
    <link href="./dist/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js"></script>
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
        /* Crop Status Specific Styles */
        .status-growing {
            color: #10B981; /* Green */
            background-color: #ECFDF5; /* Light Green */
        }
        .status-harvested {
            color: #3B82F6; /* Blue */
            background-color: #EFF6FF; /* Light Blue */
        }
        .status-planned {
            color: #F59E0B; /* Yellow */
            background-color: #FFFBEB; /* Light Yellow */
        }
        .status-problem {
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
         /* Crop Card styles */
        .crop-card {
            transition: all 0.3s ease;
        }
        .crop-card:hover {
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
        /* Progress bar styles */
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
    </style>
</head>
<body class="h-full overflow-hidden">
    <div class="flex h-full">
        <aside id="sidebar" class="w-64 bg-gradient-to-b from-blue-900 to-blue-800 text-white shadow-xl h-screen flex flex-col overflow-y-auto">
            <div class="p-5 flex items-center space-x-3 flex-shrink-0 bg-gradient-to-b from-blue-900 to-blue-900 sticky top-0 z-10"> <div class="w-10 h-10 rounded-full flex items-center justify-center"> <img src="./images/logo5.png" alt="App Logo" class="h-10 w-10 object-contain"> </div>
                <h1 class="text-xl font-bold ">AgriVision Pro</h1> </div>
            
            <nav class="flex-grow pt-2"> <div class="px-3 space-y-0.5"> 
                    <a href="dashboard.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-m text-blue-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>
                    <a href="crops.php" class="flex items-center px-3 py-2 rounded-lg bg-blue-500 bg-opacity-30 text-m text-white-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
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
                            <input id="global-search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search across AgriVision Pro..." type="search">
                            <div id="global-search-results" class="search-results hidden"></div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button id="notification-btn" class="relative p-1 text-gray-500 hover:text-gray-600 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span id="notification-badge" class="notification-badge hidden">0</span>
                        </button>
                        
                        <div id="notification-dropdown" class="notification-dropdown absolute right-16 mt-2 bg-white rounded-md shadow-lg overflow-hidden z-20 hidden">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
                            </div>
                            <div id="notification-list" class="max-h-64 overflow-y-auto">
                                <!-- Notifications will be loaded here -->
                                <div class="py-4 text-center text-sm text-gray-500">
                                    <p>No new notifications</p>
                                </div>
                            </div>
                            <div class="px-4 py-2 border-t border-gray-200 text-center">
                                <a href="#" class="text-sm text-blue-600 hover:text-blue-800">View all notifications</a>
                            </div>
                        </div>
                        
                        <div class="relative">
                            <button id="user-menu" class="flex items-center space-x-2 focus:outline-none">
                                <?php if (!empty($current_user['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($current_user['profile_picture']); ?>" alt="Profile" class="h-8 w-8 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium">
                                        <?php echo htmlspecialchars($current_user['initials']); ?>
                                    </div>
                                <?php endif; ?>
                                <span class="text-sm font-medium text-gray-700 hidden md:block"><?php echo htmlspecialchars($current_user['name']); ?></span>
                                <svg class="h-5 w-5 text-gray-400 hidden md:block" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            
                            <div id="user-menu-dropdown" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1 z-100 user-profile-dropdown">
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

            <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Crop Management</h1>
                        <p class="mt-1 text-sm text-gray-600">Manage your crops, track growth, and plan harvests</p>
                    </div>
                    <button id="add-crop-btn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add New Crop
                    </button>
                </div>
                
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Crop Status Overview</h3>
                            <p class="mt-1 text-sm text-gray-500">Current status of all crops</p>
                        </div>
                        <div class="p-4">
                            <canvas id="statusChart" class="w-full h-64"></canvas>
                        </div>
                    </div>
                    
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Crop Distribution</h3>
                            <p class="mt-1 text-sm text-gray-500">Area allocation by crop type</p>
                        </div>
                        <div class="p-4">
                            <canvas id="distributionChart" class="w-full h-64"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Crop Yield Forecast</h3>
                        <p class="mt-1 text-sm text-gray-500">Estimated yield per crop type</p>
                    </div>
                    <div class="p-4">
                        <canvas id="yieldChart" class="w-full h-64"></canvas>
                    </div>
                </div>
                
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10 mb-6 overflow-x-auto">
                        <div class="flex items-center space-x-4 w-full sm:w-auto sm:space-x-4">
                            <div class="relative w-full sm:w-80">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" id="search-crops" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search crops...">
                            </div>
                            
                            <select id="crop-type-filter" title="Filter by crop type" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="all">All Types</option>
                                <option value="grain">Grain</option>
                                <option value="vegetable">Vegetable</option>
                                <option value="fruit">Fruit</option>
                                <option value="legume">Legume</option>
                                <option value="other">Other</option>
                            </select>
                            
                            <select id="status-filter" title="Filter by status" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
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
                                <option value="planted-asc">Planted Date (Oldest)</option>
                                <option value="planted-desc">Planted Date (Newest)</option>
                                <option value="harvest-asc">Harvest Date (Soonest)</option>
                                <option value="harvest-desc">Harvest Date (Latest)</option>
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
                            <p class="mt-2 text-sm text-gray-500">Loading crops...</p>
                        </div>
                    </div>
                    
                    <div id="table-view" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CROP</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">VARIETY</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">FIELD</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AREA (ha)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PLANTED DATE</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HARVEST DATE</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="crops-table-body" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading crops...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
                        <div class="flex-1 flex justify-between sm:hidden">
                             <a href="#" id="prev-page-mobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Previous </a>
                            <a href="#" id="next-page-mobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Next </a>
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

    <!-- Add/Edit Crop Modal -->
    <div id="crop-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-200 sticky top-0 z-10">
                    <h3 id="modal-title" class="text-2xl font-bold text-gray-800">Add New Crop</h3>
                </div>
                
                <div class="max-h-[calc(100vh-200px)] overflow-y-auto px-6 py-4">
                    <form id="crop-form">
                        <input type="hidden" id="crop-id"> 
                        <input type="hidden" id="existing-crop-image"> 
                        
                        <div class="grid grid-cols-1 gap-y-6 gap-x-6 sm:grid-cols-6">
                            <div class="sm:col-span-6 border-b border-gray-200 pb-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Basic Information</h4>
                                <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <label for="crop-name" class="block text-sm font-medium text-gray-700 flex items-center required-field">
                                            Crop Name
                                        </label>
                                        <input type="text" id="crop-name" required 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="crop-variety" class="block text-sm font-medium text-gray-700 flex items-center">
                                            Variety
                                        </label>
                                        <input type="text" id="crop-variety" 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="crop-type" class="block text-sm font-medium text-gray-700 flex items-center required-field">
                                            Crop Type
                                        </label>
                                        <select id="crop-type" required 
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="grain">Grain</option>
                                            <option value="vegetable">Vegetable</option>
                                            <option value="fruit">Fruit</option>
                                            <option value="legume">Legume</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="crop-field" class="block text-sm font-medium text-gray-700 flex items-center required-field">
                                            Field/Location
                                        </label>
                                        <input type="text" id="crop-field" required 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="sm:col-span-6 border-b border-gray-200 pb-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Planting Details</h4>
                                <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                                    <div class="sm:col-span-2">
                                        <label for="crop-area" class="block text-sm font-medium text-gray-700 flex items-center required-field">
                                            Area
                                        </label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <input type="number" id="crop-area" min="0" step="0.01" required 
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">ha</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="sm:col-span-2">
                                        <label for="crop-planted-date" class="block text-sm font-medium text-gray-700 flex items-center required-field">
                                            Planted Date
                                        </label>
                                        <input type="date" id="crop-planted-date" required 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <div class="sm:col-span-2">
                                        <label for="crop-harvest-date" class="block text-sm font-medium text-gray-700 flex items-center">
                                            Expected Harvest Date
                                        </label>
                                        <input type="date" id="crop-harvest-date" 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="crop-status" class="block text-sm font-medium text-gray-700 flex items-center required-field">
                                            Status
                                        </label>
                                        <select id="crop-status" required 
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="growing">Growing</option>
                                            <option value="harvested">Harvested</option>
                                            <option value="planned">Planned</option>
                                            <option value="problem">Needs Attention</option>
                                        </select>
                                    </div>
                                    
                                    <div class="sm:col-span-3">
                                        <label for="crop-yield" class="block text-sm font-medium text-gray-700 flex items-center">
                                            Expected Yield
                                        </label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <input type="number" id="crop-yield" min="0" step="0.01" 
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">kg/ha</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
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
                                        <div class="flex text-sm text-gray-600">
                                            <label for="crop-image" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                <span>Upload an image</span>
                                                <input id="crop-image" name="crop-image" type="file" accept="image/*" class="sr-only">
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                                    </div>
                                    <div id="image-preview-container" class="hidden absolute inset-0 flex items-center justify-center bg-gray-100">
                                        <img id="preview-image" src="#" alt="Preview" class="max-h-full max-w-full p-2">
                                        <button type="button" id="remove-image" class="absolute top-2 right-2 bg-white rounded-full p-1 shadow-sm hover:bg-gray-100">
                                            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                    
                    <button type="button" id="save-crop" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Crop
                    </button>
                    <button type="button" id="cancel-crop" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Crop Details Modal -->
    <div id="crop-details-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-200 sticky top-0 z-10">
                    <div class="flex justify-between items-center">
                        <h3 id="details-title" class="text-2xl font-bold text-gray-800">Crop Details</h3>
                        <button id="close-details" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="max-h-[calc(100vh-200px)] overflow-y-auto px-6 py-4">
                    <div id="crop-details-content">
                        <!-- Crop details will be loaded here -->
                        <div class="text-center py-10">
                            <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                            <p class="mt-2 text-sm text-gray-500">Loading crop details...</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 border-t border-gray-200 pt-6">
                        <h4 class="text-lg font-medium text-gray-800 mb-4">Activity Timeline</h4>
                        <div id="crop-activities" class="space-y-4">
                            <!-- Activities will be loaded here -->
                            <div class="text-center py-4">
                                <p class="text-sm text-gray-500">No activities recorded yet</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between items-center px-4 py-3 border-t">
                 <button id="prev-item-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                     <i class="fas fa-chevron-left mr-1"></i> Previous
                 </button>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 sticky bottom-0">
                    <button type="button" id="delete-crop" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-trash mr-2"></i> Delete
                    </button>
                    
                    <div>
                        <button type="button" id="edit-crop" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                           <i class="fas fa-edit mr-2"></i>  Edit
                        </button>
                    </div>
                </div>
                <button id="next-animal-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                     Next <i class="fas fa-chevron-right ml-1"></i>
                 </button>
                 </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Crop</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Are you sure you want to delete this crop? All data associated with this crop will be permanently removed. This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 sticky bottom-0">
                    <button type="button" id="confirm-delete" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-trash mr-2"></i> Delete
                    </button>
                    
                    <div>
                        <button type="button" id="cancel-delete" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                           <i class="fas fa-cros mr-2"></i>  Cancel
                        </button>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <!-- 3D Visualization Modal -->
    <div id="visualization-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-200 sticky top-0 z-10">
                    <div class="flex justify-between items-center">
                        <h3 id="visualization-title" class="text-2xl font-bold text-gray-800">3D Field Visualization</h3>
                        <button id="close-visualization" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div id="visualization-container" class="w-full h-96 bg-gray-100 rounded-lg">
                        <!-- 3D visualization will be rendered here -->
                    </div>
                    
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <label for="visualization-season" class="block text-sm font-medium text-gray-700 mb-1">Season</label>
                            <select id="visualization-season" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="spring">Spring</option>
                                <option value="summer">Summer</option>
                                <option value="fall">Fall</option>
                                <option value="winter">Winter</option>
                            </select>
                        </div>
                        <div>
                            <label for="visualization-view" class="block text-sm font-medium text-gray-700 mb-1">View</label>
                            <select id="visualization-view" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="overhead">Overhead View</option>
                                <option value="perspective">Perspective View</option>
                                <option value="first-person">First Person View</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-3 flex justify-end items-center border-t border-gray-200 sticky bottom-0">
                    <button type="button" id="close-visualization-btn" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // crops.js - JavaScript for Crop Management functionality

        document.addEventListener('DOMContentLoaded', function() {
            // Global variables
            let currentPage = 1;
            let totalPages = 1;
            let itemsPerPage = 20;
            let currentView = 'table'; // 'table' or 'card'
            let currentCrops = []; // Store the current list of crops
            let currentFilters = {
                search: '',
                cropType: 'all',
                status: 'all',
                sortBy: 'name-asc'
            };

            // Get the loading overlay element
            const loadingOverlay = document.getElementById('loadingOverlay');

            // Function to show the loading indicator
            function showLoading() {
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('hidden'); // Remove 'hidden' to show
                }
            }

            // Function to hide the loading indicator
            function hideLoading() {
                if (loadingOverlay) {
                    loadingOverlay.classList.add('hidden'); // Add 'hidden' to hide
                }
            }

            // DOM Elements
            const searchInput = document.getElementById('search-crops');
            const cropTypeFilter = document.getElementById('crop-type-filter');
            const statusFilter = document.getElementById('status-filter');
            const sortBySelect = document.getElementById('sort-by');
            const viewToggleBtn = document.getElementById('view-toggle');
            const tableView = document.getElementById('table-view');
            const cardView = document.getElementById('card-view');
            const cropsTableBody = document.getElementById('crops-table-body');
            const paginationStart = document.getElementById('pagination-start');
            const paginationEnd = document.getElementById('pagination-end');
            const paginationTotal = document.getElementById('pagination-total');
            const pageNumbers = document.getElementById('page-numbers');
            const prevPageBtn = document.getElementById('prev-page');
            const nextPageBtn = document.getElementById('next-page');
            const prevPageMobileBtn = document.getElementById('prev-page-mobile');
            const nextPageMobileBtn = document.getElementById('next-page-mobile');
            const addCropBtn = document.getElementById('add-crop-btn');
            const sidebarToggleBtn = document.getElementById('sidebar-toggle');
            const userMenuBtn = document.getElementById('user-menu');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');
            
            // Modal Elements
            const cropModal = document.getElementById('crop-modal');
            const cropDetailsModal = document.getElementById('crop-details-modal');
            const deleteModal = document.getElementById('delete-modal');
            const visualizationModal = document.getElementById('visualization-modal');
            const modalTitle = document.getElementById('modal-title');
            const cropForm = document.getElementById('crop-form');
            const saveCropBtn = document.getElementById('save-crop');
            const cancelCropBtn = document.getElementById('cancel-crop');
            const closeDetailsBtn = document.getElementById('close-details');
            const editCropBtn = document.getElementById('edit-crop');
            const deleteCropBtn = document.getElementById('delete-crop');
            const confirmDeleteBtn = document.getElementById('confirm-delete');
            const cancelDeleteBtn = document.getElementById('cancel-delete');
            
            // Chart Elements
            const statusChartCanvas = document.getElementById('statusChart');
            const distributionChartCanvas = document.getElementById('distributionChart');
            const yieldChartCanvas = document.getElementById('yieldChart');
            
            // Initialize charts
            let statusChart, distributionChart, yieldChart;
            
            // Initialize 3D visualization
            let scene, camera, renderer, controls;
            
            // Initialize the page
            init();
            
            // Main initialization function
            function init() {
                // Set up event listeners
                setupEventListeners();
                
                // Load initial data
                loadCrops();
                
                // Load statistics for charts
                loadCropStats();
                
                // Initialize charts with empty data (will be updated when stats load)
                initCharts();
            }
            
            // Set up all event listeners
            function setupEventListeners() {

                // Sidebar toggle
                sidebarToggleBtn.addEventListener('click', function() {
                        sidebar.classList.toggle('hidden');
                    });

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

                // Search and filter events
                searchInput.addEventListener('input', debounce(function() {
                    currentFilters.search = this.value;
                    currentPage = 1; // Reset to first page when filtering
                    loadCrops();
                }, 300));
                
                cropTypeFilter.addEventListener('change', function() {
                    currentFilters.cropType = this.value;
                    currentPage = 1;
                    loadCrops();
                });
                
                statusFilter.addEventListener('change', function() {
                    currentFilters.status = this.value;
                    currentPage = 1;
                    loadCrops();
                });
                
                sortBySelect.addEventListener('change', function() {
                    currentFilters.sortBy = this.value;
                    loadCrops();
                });
                
                // View toggle
                viewToggleBtn.addEventListener('click', function() {
                    toggleView();
                });
                
                // Pagination events
                prevPageBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (currentPage > 1) {
                        currentPage--;
                        loadCrops();
                    }
                });
                
                nextPageBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (currentPage < totalPages) {
                        currentPage++;
                        loadCrops();
                    }
                });
                
                prevPageMobileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (currentPage > 1) {
                        currentPage--;
                        loadCrops();
                    }
                });
                
                nextPageMobileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (currentPage < totalPages) {
                        currentPage++;
                        loadCrops();
                    }
                });
                
                // Add new crop button
                addCropBtn.addEventListener('click', function() {
                    openAddCropModal();
                });
                
                // Modal events
                saveCropBtn.addEventListener('click', function() {
                    saveCrop();
                });
                
                cancelCropBtn.addEventListener('click', function() {
                    closeCropModal();
                });
                
                closeDetailsBtn.addEventListener('click', function() {
                    closeDetailsModal();
                });
                
                editCropBtn.addEventListener('click', function() {
                    const cropId = this.getAttribute('data-crop-id');
                    closeCropDetailsModal();
                    openEditCropModal(cropId);
                });
                
                deleteCropBtn.addEventListener('click', function() {
                    const cropId = this.getAttribute('data-crop-id');
                    openDeleteConfirmation(cropId);
                });
                
                confirmDeleteBtn.addEventListener('click', function() {
                    const cropId = this.getAttribute('data-crop-id');
                    deleteCrop(cropId);
                });
                
                cancelDeleteBtn.addEventListener('click', function() {
                    closeDeleteModal();
                });
                
                // Image upload preview
                const cropImage = document.getElementById('crop-image');
                const previewContainer = document.getElementById('image-preview-container');
                const previewImage = document.getElementById('preview-image');
                const removeImageBtn = document.getElementById('remove-image');
                const imageUploadArea = document.getElementById('image-upload-area');
                
                cropImage.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImage.src = e.target.result;
                            previewContainer.classList.remove('hidden');
                            imageUploadArea.classList.add('hidden');
                        };
                        reader.readAsDataURL(file);
                    }
                });
                
                removeImageBtn.addEventListener('click', function() {
                    cropImage.value = '';
                    previewContainer.classList.add('hidden');
                    imageUploadArea.classList.remove('hidden');
                    document.getElementById('existing-crop-image').value = '';
                });
                
                // Drag and drop for image upload
                const dropArea = document.querySelector('.mt-1.flex.justify-center');
                
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropArea.addEventListener(eventName, preventDefaults, false);
                });
                
                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropArea.addEventListener(eventName, highlight, false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    dropArea.addEventListener(eventName, unhighlight, false);
                });
                
                function highlight() {
                    dropArea.classList.add('border-blue-500');
                    dropArea.classList.add('bg-blue-50');
                }
                
                function unhighlight() {
                    dropArea.classList.remove('border-blue-500');
                    dropArea.classList.remove('bg-blue-50');
                }
                
                dropArea.addEventListener('drop', handleDrop, false);
                
                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    
                    if (files.length) {
                        cropImage.files = files;
                        const event = new Event('change');
                        cropImage.dispatchEvent(event);
                    }
                }
                
                // Global UI events
                document.addEventListener('click', function(e) {
                    // Close modals when clicking outside
                    if (e.target.classList.contains('modal')) {
                        if (cropModal.contains(e.target)) {
                            closeCropModal();
                        } else if (cropDetailsModal.contains(e.target)) {
                            closeDetailsModal();
                        } else if (deleteModal.contains(e.target)) {
                            closeDeleteModal();
                        } else if (visualizationModal.contains(e.target)) {
                            closeVisualizationModal();
                        }
                    }
                });
                
                // Keyboard events
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        // Close any open modals
                        if (!cropModal.classList.contains('hidden')) {
                            closeCropModal();
                        } else if (!cropDetailsModal.classList.contains('hidden')) {
                            closeDetailsModal();
                        } else if (!deleteModal.classList.contains('hidden')) {
                            closeDeleteModal();
                        } else if (!visualizationModal.classList.contains('hidden')) {
                            closeVisualizationModal();
                        }
                    }
                });
            }
            
            // Load crops from the API
            function loadCrops() {
                // Show loading state
                showLoading();
                
                // Prepare data for API request
                const requestData = {
                    action: 'list_crops',
                    search: currentFilters.search,
                    cropType: currentFilters.cropType,
                    status: currentFilters.status,
                    sortBy: currentFilters.sortBy,
                    page: currentPage,
                    itemsPerPage: itemsPerPage
                };
                
                // Make API request
                fetch('crops_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Store the crops data
                        currentCrops = data.crops || [];
                        
                        // Update the UI
                        updateCropsDisplay();
                        updatePagination(data.totalItems || currentCrops.length);
                    } else {
                        // Show error message
                        showErrorMessage(data.message || 'Failed to load crops');
                    }
                })
                .catch(error => {
                    console.error('Error loading crops:', error);
                    showErrorMessage('An error occurred while loading crops');
                })
                .finally(() => {
                    hideLoading();
                });
            }
            
            // Load crop statistics for charts
            function loadCropStats() {
                // Prepare data for API request
                const requestData = {
                    action: 'get_crop_stats'
                };
                
                // Make API request
                fetch('crops_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.stats) {
                        // Update charts with the statistics
                        updateCharts(data.stats);
                    } else {
                        console.error('Failed to load crop statistics');
                    }
                })
                .catch(error => {
                    console.error('Error loading crop statistics:', error);
                });
            }
            
            // Initialize charts with empty data
            function initCharts() {
                // Status Chart (Doughnut)
                statusChart = new Chart(statusChartCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Growing', 'Harvested', 'Planned', 'Needs Attention'],
                        datasets: [{
                            data: [0, 0, 0, 0],
                            backgroundColor: [
                                '#10B981', // Green for Growing
                                '#3B82F6', // Blue for Harvested
                                '#F59E0B', // Yellow for Planned
                                '#EF4444'  // Red for Needs Attention
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
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
                
                // Distribution Chart (Bar)
                distributionChart = new Chart(distributionChartCanvas, {
                    type: 'bar',
                    data: {
                        labels: ['Grain', 'Vegetable', 'Fruit', 'Legume', 'Other'],
                        datasets: [{
                            label: 'Area (ha)',
                            data: [0, 0, 0, 0, 0],
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 206, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(153, 102, 255, 0.2)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)'
                            ],
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
                                    text: 'Area (hectares)'
                                }
                            }
                        }
                    }
                });
                
                // Yield Chart (Line)
                yieldChart = new Chart(yieldChartCanvas, {
                    type: 'line',
                    data: {
                        labels: ['Grain', 'Vegetable', 'Fruit', 'Legume', 'Other'],
                        datasets: [{
                            label: 'Expected Yield (kg)',
                            data: [0, 0, 0, 0, 0],
                            fill: false,
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
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
                                    text: 'Expected Yield (kg)'
                                }
                            }
                        }
                    }
                });
            }
            
            // Update charts with new data
            function updateCharts(stats) {
                // Update Status Chart
                if (stats.status && stats.status.length > 0) {
                    const statusLabels = [];
                    const statusData = [];
                    
                    // Map status values to display names
                    const statusMap = {
                        'growing': 'Growing',
                        'harvested': 'Harvested',
                        'planned': 'Planned',
                        'problem': 'Needs Attention'
                    };
                    
                    stats.status.forEach(item => {
                        statusLabels.push(statusMap[item.status] || item.status);
                        statusData.push(item.count);
                    });
                    
                    statusChart.data.labels = statusLabels;
                    statusChart.data.datasets[0].data = statusData;
                    statusChart.update();
                }
                
                // Update Distribution Chart
                if (stats.area && stats.area.length > 0) {
                    const areaLabels = [];
                    const areaData = [];
                    
                    // Map crop_type values to display names
                    const cropTypeMap = {
                        'grain': 'Grain',
                        'vegetable': 'Vegetable',
                        'fruit': 'Fruit',
                        'legume': 'Legume',
                        'other': 'Other'
                    };
                    
                    stats.area.forEach(item => {
                        areaLabels.push(cropTypeMap[item.crop_type] || item.crop_type);
                        areaData.push(parseFloat(item.total_area));
                    });
                    
                    distributionChart.data.labels = areaLabels;
                    distributionChart.data.datasets[0].data = areaData;
                    distributionChart.update();
                }
                
                // Update Yield Chart
                if (stats.yield && stats.yield.length > 0) {
                    const yieldLabels = [];
                    const yieldData = [];
                    
                    // Use the same crop type mapping
                    const cropTypeMap = {
                        'grain': 'Grain',
                        'vegetable': 'Vegetable',
                        'fruit': 'Fruit',
                        'legume': 'Legume',
                        'other': 'Other'
                    };
                    
                    stats.yield.forEach(item => {
                        yieldLabels.push(cropTypeMap[item.crop_type] || item.crop_type);
                        yieldData.push(parseFloat(item.total_yield));
                    });
                    
                    yieldChart.data.labels = yieldLabels;
                    yieldChart.data.datasets[0].data = yieldData;
                    yieldChart.update();
                }
            }
            
            // Update the crops display based on current view
            function updateCropsDisplay() {
                if (currentView === 'table') {
                    updateTableView();
                } else {
                    updateCardView();
                }
            }
            
            // Update the table view with current crops data
            function updateTableView() {
                // Clear the table body
                cropsTableBody.innerHTML = '';
                
                if (currentCrops.length === 0) {
                    // Show no results message
                    const noResultsRow = document.createElement('tr');
                    noResultsRow.innerHTML = `
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                            No crops found. Try adjusting your filters or <a href="#" class="text-blue-600 hover:text-blue-800" id="add-crop-link">add a new crop</a>.
                        </td>
                    `;
                    cropsTableBody.appendChild(noResultsRow);
                    
                    // Add event listener to the "add a new crop" link
                    document.getElementById('add-crop-link').addEventListener('click', function(e) {
                        e.preventDefault();
                        openAddCropModal();
                    });
                    
                    return;
                }
                
                // Add rows for each crop
                currentCrops.forEach(crop => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50';
                    
                    // Format dates
                    const plantedDate = crop.planted_date ? new Date(crop.planted_date).toLocaleDateString() : 'N/A';
                    const harvestDate = crop.harvest_date ? new Date(crop.harvest_date).toLocaleDateString() : 'N/A';
                    
                    // Get status class
                    const statusClass = getStatusClass(crop.status);
                    
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    ${crop.image_url ? 
                                        `<img class="h-10 w-10 rounded-full object-cover" src="${crop.image_url}" alt="${crop.name}">` : 
                                        `<div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-gray-500 text-xs">${getInitials(crop.name)}</span>
                                        </div>`
                                    }
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${crop.name}</div>
                                    <div class="text-sm text-gray-500">${crop.crop_type || 'N/A'}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${crop.variety || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${crop.field || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${crop.area || '0'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${plantedDate}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${harvestDate}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="status-badge ${statusClass}">${formatStatus(crop.status)}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button class="text-blue-600 hover:text-blue-900 mr-2 view-crop-btn" data-crop-id="${crop.id}"><i class="fas fa-eye"></i></button>
                            <button class="text-green-600 hover:text-green-900 mr-2 edit-crop-btn" data-crop-id="${crop.id}"><i class="fas fa-edit"></i></button>
                            <button class="text-red-600 hover:text-red-900 delete-crop-btn" data-crop-id="${crop.id}"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    `;
                    
                    cropsTableBody.appendChild(row);
                });
                
                // Add event listeners to the action buttons
                document.querySelectorAll('.view-crop-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const cropId = this.getAttribute('data-crop-id');
                        openCropDetailsModal(cropId);
                    });
                });
                
                document.querySelectorAll('.edit-crop-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const cropId = this.getAttribute('data-crop-id');
                        openEditCropModal(cropId);
                    });
                });
                
                document.querySelectorAll('.delete-crop-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const cropId = this.getAttribute('data-crop-id');
                        openDeleteConfirmation(cropId);
                    });
                });
            }
            
            // Update the card view with current crops data
            function updateCardView() {
                // Clear the card container
                cardView.innerHTML = '';
                
                if (currentCrops.length === 0) {
                    // Show no results message
                    const noResultsCard = document.createElement('div');
                    noResultsCard.className = 'sm:col-span-1 lg:col-span-3 text-center py-10';
                    noResultsCard.innerHTML = `
                        <p class="text-gray-500 mb-4">No crops found. Try adjusting your filters or add a new crop.</p>
                        <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="add-crop-card-btn">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add New Crop
                        </button>
                    `;
                    cardView.appendChild(noResultsCard);
                    
                    // Add event listener to the "add a new crop" button
                    document.getElementById('add-crop-card-btn').addEventListener('click', function() {
                        openAddCropModal();
                    });
                    
                    return;
                }
                
                // Add cards for each crop
                currentCrops.forEach(crop => {
                    const card = document.createElement('div');
                    card.className = 'crop-card bg-white shadow rounded-lg overflow-hidden';
                    
                    // Format dates
                    const plantedDate = crop.planted_date ? new Date(crop.planted_date).toLocaleDateString() : 'N/A';
                    const harvestDate = crop.harvest_date ? new Date(crop.harvest_date).toLocaleDateString() : 'N/A';
                    
                    // Calculate progress percentage based on dates
                    let progressPercent = 0;
                    if (crop.planted_date && crop.harvest_date) {
                        const planted = new Date(crop.planted_date);
                        const harvest = new Date(crop.harvest_date);
                        const today = new Date();
                        
                        if (today >= harvest) {
                            progressPercent = 100;
                        } else if (today <= planted) {
                            progressPercent = 0;
                        } else {
                            const totalDays = (harvest - planted) / (1000 * 60 * 60 * 24);
                            const daysPassed = (today - planted) / (1000 * 60 * 60 * 24);
                            progressPercent = Math.round((daysPassed / totalDays) * 100);
                        }
                    }
                    
                    // Get status class
                    const statusClass = getStatusClass(crop.status);
                    
                    card.innerHTML = `
                        <div class="relative">
                            ${crop.image_url ? 
                                `<img class="h-48 w-full object-cover" src="${crop.image_url}" alt="${crop.name}">` : 
                                `<div class="h-48 w-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-gray-500 text-2xl">${getInitials(crop.name)}</span>
                                </div>`
                            }
                            <div class="absolute top-2 right-2">
                                <span class="status-badge ${statusClass}">${formatStatus(crop.status)}</span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">${crop.name}</h3>
                                    <p class="text-sm text-gray-600">${crop.variety || 'No variety specified'}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">${crop.area} ha</p>
                                    <p class="text-xs text-gray-500">${crop.field}</p>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>Planted: ${plantedDate}</span>
                                    <span>Harvest: ${harvestDate}</span>
                                </div>
                                <div class="progress-bar ${crop.status}">
                                    <div class="progress-fill" style="width: ${progressPercent}%"></div>
                                </div>
                                <div class="text-right text-xs text-gray-500 mt-1">${progressPercent}% complete</div>
                            </div>
                            
                            <div class="mt-4 flex justify-between">
                                <button class="text-blue-600 hover:text-blue-900 text-sm view-crop-btn" data-crop-id="${crop.id}">View Details</button>
                                <div>
                                    <button class="text-green-600 hover:text-green-900 text-sm mr-2 edit-crop-btn" data-crop-id="${crop.id}">Edit</button>
                                    <button class="text-red-600 hover:text-red-900 text-sm delete-crop-btn" data-crop-id="${crop.id}">Delete</button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    cardView.appendChild(card);
                });
                
                // Add event listeners to the action buttons
                document.querySelectorAll('.view-crop-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const cropId = this.getAttribute('data-crop-id');
                        openCropDetailsModal(cropId);
                    });
                });
                
                document.querySelectorAll('.edit-crop-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const cropId = this.getAttribute('data-crop-id');
                        openEditCropModal(cropId);
                    });
                });
                
                document.querySelectorAll('.delete-crop-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const cropId = this.getAttribute('data-crop-id');
                        openDeleteConfirmation(cropId);
                    });
                });
            }
            
            // Update pagination controls
            function updatePagination(totalItems) {
                // Calculate total pages
                totalPages = Math.ceil(totalItems / itemsPerPage);
                
                // Update pagination text
                const start = (currentPage - 1) * itemsPerPage + 1;
                const end = Math.min(currentPage * itemsPerPage, totalItems);
                
                paginationStart.textContent = totalItems > 0 ? start : 0;
                paginationEnd.textContent = end;
                paginationTotal.textContent = totalItems;
                
                // Update page numbers
                pageNumbers.innerHTML = '';
                
                // Determine which page numbers to show
                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(totalPages, startPage + 4);
                
                if (endPage - startPage < 4) {
                    startPage = Math.max(1, endPage - 4);
                }
                
                // Add page number buttons
                for (let i = startPage; i <= endPage; i++) {
                    const pageLink = document.createElement('a');
                    pageLink.href = '#';
                    pageLink.className = i === currentPage
                        ? 'relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600'
                        : 'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50';
                    pageLink.textContent = i;
                    pageLink.setAttribute('data-page', i);
                    pageLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        currentPage = parseInt(this.getAttribute('data-page'));
                        loadCrops();
                    });
                    
                    pageNumbers.appendChild(pageLink);
                }
                
                // Update previous/next button states
                prevPageBtn.classList.toggle('opacity-50', currentPage === 1);
                prevPageBtn.classList.toggle('cursor-not-allowed', currentPage === 1);
                nextPageBtn.classList.toggle('opacity-50', currentPage === totalPages);
                nextPageBtn.classList.toggle('cursor-not-allowed', currentPage === totalPages);
                
                prevPageMobileBtn.classList.toggle('opacity-50', currentPage === 1);
                prevPageMobileBtn.classList.toggle('cursor-not-allowed', currentPage === 1);
                nextPageMobileBtn.classList.toggle('opacity-50', currentPage === totalPages);
                nextPageMobileBtn.classList.toggle('cursor-not-allowed', currentPage === totalPages);
            }
            
            // Toggle between table and card views
            function toggleView() {
                if (currentView === 'table') {
                    currentView = 'card';
                    tableView.classList.add('hidden');
                    cardView.classList.remove('hidden');
                    viewToggleBtn.innerHTML = '<i class="fas fa-th-large mr-2"></i> Card View';
                } else {
                    currentView = 'table';
                    cardView.classList.add('hidden');
                    tableView.classList.remove('hidden');
                    viewToggleBtn.innerHTML = '<i class="fas fa-table mr-2"></i> Table View';
                }
                
                // Save preference to localStorage
                localStorage.setItem('cropsViewPreference', currentView);
            }
            
            // Open the add crop modal
            function openAddCropModal() {
                // Reset the form
                cropForm.reset();
                document.getElementById('crop-id').value = '';
                document.getElementById('existing-crop-image').value = '';
                
                // Reset image preview
                const previewContainer = document.getElementById('image-preview-container');
                const imageUploadArea = document.getElementById('image-upload-area');
                previewContainer.classList.add('hidden');
                imageUploadArea.classList.remove('hidden');
                
                // Set default values
                document.getElementById('crop-status').value = 'planned';
                
                // Set today's date as default planted date
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('crop-planted-date').value = today;
                
                // Set modal title
                modalTitle.textContent = 'Add New Crop';
                
                // Show the modal
                cropModal.classList.remove('hidden');
            }
            
            // Open the edit crop modal
            function openEditCropModal(cropId) {
                // Find the crop in the current crops array
                const crop = currentCrops.find(c => c.id == cropId);
                
                if (!crop) {
                    showErrorMessage('Crop not found');
                    return;
                }
                
                // Reset the form
                cropForm.reset();
                
                // Set form values
                document.getElementById('crop-id').value = crop.id;
                document.getElementById('crop-name').value = crop.name;
                document.getElementById('crop-variety').value = crop.variety || '';
                document.getElementById('crop-type').value = crop.crop_type || 'other';
                document.getElementById('crop-field').value = crop.field || '';
                document.getElementById('crop-area').value = crop.area || '';
                document.getElementById('crop-planted-date').value = crop.planted_date || '';
                document.getElementById('crop-harvest-date').value = crop.harvest_date || '';
                document.getElementById('crop-status').value = crop.status || 'planned';
                document.getElementById('crop-yield').value = crop.expected_yield || '';
                document.getElementById('crop-notes').value = crop.notes || '';
                
                // Handle image
                const previewContainer = document.getElementById('image-preview-container');
                const previewImage = document.getElementById('preview-image');
                const imageUploadArea = document.getElementById('image-upload-area');
                
                if (crop.image_url) {
                    document.getElementById('existing-crop-image').value = crop.image_url;
                    previewImage.src = crop.image_url;
                    previewContainer.classList.remove('hidden');
                    imageUploadArea.classList.add('hidden');
                } else {
                    previewContainer.classList.add('hidden');
                    imageUploadArea.classList.remove('hidden');
                }
                
                // Set modal title
                modalTitle.textContent = 'Edit Crop';
                
                // Show the modal
                cropModal.classList.remove('hidden');
            }
            
            // Open the crop details modal
            function openCropDetailsModal(cropId) {
                // Show loading state
                document.getElementById('crop-details-content').innerHTML = `
                    <div class="text-center py-10">
                        <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                        <p class="mt-2 text-sm text-gray-500">Loading crop details...</p>
                    </div>
                `;
                
                // Show the modal
                cropDetailsModal.classList.remove('hidden');
                
                // Prepare data for API request
                const requestData = {
                    action: 'get_crop_details',
                    cropId: cropId
                };
                
                // Make API request
                fetch('crops_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.crop) {
                        updateCropDetailsModal(data.crop);
                    } else {
                        document.getElementById('crop-details-content').innerHTML = `
                            <div class="text-center py-10">
                                <p class="text-red-500">Failed to load crop details: ${data.message || 'Unknown error'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading crop details:', error);
                    document.getElementById('crop-details-content').innerHTML = `
                        <div class="text-center py-10">
                            <p class="text-red-500">An error occurred while loading crop details</p>
                        </div>
                    `;
                });
                // Modal එකේ 'hidden' class එක ඉවත් කිරීමෙන් එය පෙන්වන්න
                if (cropDetailsModal) {
                    cropDetailsModal.classList.remove('hidden');
                }
            }

            // Crop Details Modal එක වැසීමේ function එක
            function closeCropDetailsModal() {
                // Modal එකේ 'hidden' class එක එකතු කිරීමෙන් එය සඟවන්න
                if (cropDetailsModal) {
                    cropDetailsModal.classList.add('hidden');
                }
            }

            // Modal එකෙන් පිටත click කිරීමෙන් එය වැසීමට Event listener (අවශ්‍ය පරිදි, නමුත් හොඳ පරිශීලක අත්දැකීමක්)
            if (cropDetailsModal) {
                cropDetailsModal.addEventListener('click', function(event) {
                    // click එක modal background එක මත කෙලින්ම නම්, එය වසන්න
                    if (event.target === cropDetailsModal) {
                        closeCropDetailsModal();
                    }
                });
            }
            
            // Update the crop details modal with data
            function updateCropDetailsModal(crop) {
                // Set the title
                document.getElementById('details-title').textContent = crop.name;
                
                // Format dates
                const plantedDate = crop.planted_date ? new Date(crop.planted_date).toLocaleDateString() : 'N/A';
                const harvestDate = crop.harvest_date ? new Date(crop.harvest_date).toLocaleDateString() : 'N/A';
                const createdAt = crop.createdAt ? new Date(crop.createdAt).toLocaleString() : 'N/A';
                const updatedAt = crop.updatedAt ? new Date(crop.updatedAt).toLocaleString() : 'N/A';
                
                // Calculate progress percentage based on dates
                let progressPercent = 0;
                if (crop.planted_date && crop.harvest_date) {
                    const planted = new Date(crop.planted_date);
                    const harvest = new Date(crop.harvest_date);
                    const today = new Date();
                    
                    if (today >= harvest) {
                        progressPercent = 100;
                    } else if (today <= planted) {
                        progressPercent = 0;
                    } else {
                        const totalDays = (harvest - planted) / (1000 * 60 * 60 * 24);
                        const daysPassed = (today - planted) / (1000 * 60 * 60 * 24);
                        progressPercent = Math.round((daysPassed / totalDays) * 100);
                    }
                }
                
                // Get status class
                const statusClass = getStatusClass(crop.status);
                
                // Update the content
                document.getElementById('crop-details-content').innerHTML = `
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        <div class="sm:col-span-2 ">
                            <div class="h-64 bg-white rounded-lg overflow-hidden flex items-center justify-center">
                            ${crop.image_url ? 
                                `<img src="${crop.image_url}" alt="${crop.name}" style="width: 250px; height: 250px;" class="object-cover rounded-lg shadow ">` : 
                                `<div class="w-full h-48 bg-gray-200 rounded-lg shadow flex items-center justify-center">
                                    <span class="text-gray-500 text-2xl">${getInitials(crop.name)}</span>
                                </div>`
                            }
                            </div>
                            
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-500"> Status</h4>
                                <div class="mt-1">
                                    <span class="status-badge ${statusClass}">${formatStatus(crop.status)}</span>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-500">Growth Progress</h4>
                                <div class="mt-1">
                                    <div class="progress-bar ${crop.status}">
                                        <div class="progress-fill" style="width: ${progressPercent}%"></div>
                                    </div>
                                    <div class="text-right text-xs text-gray-500 mt-1">${progressPercent}% complete</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sm:col-span-4">
                            <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2 h-64 bg-gray-50 rounded-lg overflow-hidden flex items-center justify-center">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Crop Type</h4>
                                    <p class="mt-1 text-sm text-gray-900">${formatCropType(crop.crop_type)}</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Variety</h4>
                                    <p class="mt-1 text-sm text-gray-900">${crop.variety || 'N/A'}</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Field/Location</h4>
                                    <p class="mt-1 text-sm text-gray-900">${crop.field || 'N/A'}</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Area</h4>
                                    <p class="mt-1 text-sm text-gray-900">${crop.area || '0'} hectares</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Planted Date</h4>
                                    <p class="mt-1 text-sm text-gray-900">${plantedDate}</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Expected Harvest Date</h4>
                                    <p class="mt-1 text-sm text-gray-900">${harvestDate}</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Expected Yield</h4>
                                    <p class="mt-1 text-sm text-gray-900">${crop.expected_yield ? `${crop.expected_yield} kg/ha` : 'N/A'}</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Total Expected Yield</h4>
                                    <p class="mt-1 text-sm text-gray-900">${crop.expected_yield && crop.area ? `${(crop.expected_yield * crop.area).toFixed(2)} kg` : 'N/A'}</p>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <h4 class="text-sm font-medium text-gray-500">Notes</h4>
                                <p class="mt-1 text-sm text-gray-900">${crop.notes || 'No notes available'}</p>
                            </div>
                            
                            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-gray-500">
                                <div>
                                    <span>Created: ${createdAt}</span>
                                </div>
                                <div>
                                    <span>Last Updated: ${updatedAt}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Update activities section
                updateActivitiesSection(crop.activities || []);
                
                // Set crop ID for action buttons
                document.getElementById('edit-crop').setAttribute('data-crop-id', crop.id);
                document.getElementById('delete-crop').setAttribute('data-crop-id', crop.id);
            }
            
            // Update the activities section in the details modal
            function updateActivitiesSection(activities) {
                const activitiesContainer = document.getElementById('crop-activities');
                
                if (!activities || activities.length === 0) {
                    activitiesContainer.innerHTML = `
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-500">No activities recorded yet</p>
                        </div>
                    `;
                    return;
                }
                
                // Sort activities by timestamp (newest first)
                activities.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
                
                // Create the activities list
                let activitiesHTML = '';
                
                activities.forEach((activity, index) => {
                    const timestamp = new Date(activity.timestamp).toLocaleString();
                    const isLast = index === activities.length - 1;
                    
                    // Determine icon based on activity type
                    let icon = '';
                    let iconColor = '';
                    
                    switch (activity.type) {
                        case 'crop_added':
                            icon = '<i class="fas fa-plus"></i>';
                            iconColor = 'bg-green-100 text-green-600';
                            break;
                        case 'crop_updated':
                            icon = '<i class="fas fa-edit"></i>';
                            iconColor = 'bg-blue-100 text-blue-600';
                            break;
                        case 'status_changed':
                            icon = '<i class="fas fa-exchange-alt"></i>';
                            iconColor = 'bg-yellow-100 text-yellow-600';
                            break;
                        case 'note_added':
                            icon = '<i class="fas fa-sticky-note"></i>';
                            iconColor = 'bg-purple-100 text-purple-600';
                            break;
                        default:
                            icon = '<i class="fas fa-info-circle"></i>';
                            iconColor = 'bg-gray-100 text-gray-600';
                    }
                    
                    activitiesHTML += `
                        <div class="activity-item flex items-start space-x-3 bg-white p-3 rounded-lg shadow-sm border border-gray-200 ${!isLast ? 'border-l-2 border-gray-300 pl-8 ml-2' : ''}">
                            <div class="flex-shrink-0 h-8 w-8 rounded-full ${iconColor} flex items-center justify-center border-2 border-white shadow-md z-10">
                                ${icon}
                            </div>
                            <div class="flex-1 min-w-0 ">
                                <div class="absolute left-0 top-4 -translate-x-full w-0 h-0 border-t-8 border-b-8 border-r-8 border-t-transparent border-b-transparent border-r-white"></div>
                                
                                <p class="text-base font-semibold text-gray-800 leading-snug">${activity.notes}</p>
                               
                            </div>
                            <div class="flex-shrink-0 text-xs text-gray-500 text-right">
                                ${timestamp}
                            </div>
                        </div>
                    
                    `;
                });
                
                activitiesContainer.innerHTML = activitiesHTML;
            }
            
            // Open delete confirmation modal
            function openDeleteConfirmation(cropId) {
                // Set crop ID for confirm button
                document.getElementById('confirm-delete').setAttribute('data-crop-id', cropId);
                
                // Show the modal
                deleteModal.classList.remove('hidden');
            }
            
            // Save crop (create or update)
            function saveCrop() {
                // Validate form
                if (!cropForm.checkValidity()) {
                    cropForm.reportValidity();
                    return;
                }
                
                // Get form data
                const cropId = document.getElementById('crop-id').value;
                const isNewCrop = !cropId;
                
                // Create FormData object
                const formData = new FormData();
                
                // Add action
                formData.append('action', isNewCrop ? 'create_crop' : 'update_crop');
                
                // Add crop data
                if (!isNewCrop) {
                    formData.append('id', cropId);
                }
                
                formData.append('name', document.getElementById('crop-name').value);
                formData.append('variety', document.getElementById('crop-variety').value);
                formData.append('cropType', document.getElementById('crop-type').value);
                formData.append('field', document.getElementById('crop-field').value);
                formData.append('area', document.getElementById('crop-area').value);
                formData.append('plantedDate', document.getElementById('crop-planted-date').value);
                formData.append('harvestDate', document.getElementById('crop-harvest-date').value);
                formData.append('status', document.getElementById('crop-status').value);
                formData.append('expectedYield', document.getElementById('crop-yield').value);
                formData.append('notes', document.getElementById('crop-notes').value);
                
                // Add existing image URL if editing
                const existingImageUrl = document.getElementById('existing-crop-image').value;
                if (existingImageUrl) {
                    formData.append('existingImageUrl', existingImageUrl);
                }
                
                // Add image file if selected
                const cropImage = document.getElementById('crop-image');
                if (cropImage.files.length > 0) {
                    formData.append('cropImage', cropImage.files[0]);
                } else if (!existingImageUrl && document.getElementById('image-preview-container').classList.contains('hidden')) {
                    // If no existing image and no new image selected, indicate to remove image
                    formData.append('removeImage', 'true');
                }
                
                // Show loading state
                saveCropBtn.disabled = true;
                saveCropBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
                
                // Make API request
                fetch('crops_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showSuccessMessage(isNewCrop ? 'Crop created successfully!' : 'Crop updated successfully!');
                        
                        // Close the modal
                        closeCropModal();
                        
                        // Reload crops
                        loadCrops();
                        
                        // Reload statistics for charts
                        loadCropStats();
                    } else {
                        // Show error message
                        showErrorMessage(data.message || 'Failed to save crop');
                    }
                })
                .catch(error => {
                    console.error('Error saving crop:', error);
                    showErrorMessage('An error occurred while saving the crop');
                })
                .finally(() => {
                    // Reset button state
                    saveCropBtn.disabled = false;
                    saveCropBtn.innerHTML = 'Save Crop';
                });
            }
            
            // Delete crop
            function deleteCrop(cropId) {
                // Show loading state
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...';
                
                // Prepare data for API request
                const requestData = {
                    action: 'delete_crop',
                    cropId: cropId
                };
                
                // Make API request
                fetch('crops_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showSuccessMessage('Crop deleted successfully!');
                        
                        // Close the modals
                        closeDeleteModal();
                        closeDetailsModal();
                        
                        // Reload crops
                        loadCrops();
                        
                        // Reload statistics for charts
                        loadCropStats();
                    } else {
                        // Show error message
                        showErrorMessage(data.message || 'Failed to delete crop');
                    }
                })
                .catch(error => {
                    console.error('Error deleting crop:', error);
                    showErrorMessage('An error occurred while deleting the crop');
                })
                .finally(() => {
                    // Reset button state
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = 'Delete';
                });
            }
            
            // Close crop modal
            function closeCropModal() {
                cropModal.classList.add('hidden');
            }
            
            // Close crop details modal
            function closeDetailsModal() {
                cropDetailsModal.classList.add('hidden');
            }
            
            // Close delete confirmation modal
            function closeDeleteModal() {
                deleteModal.classList.add('hidden');
            }
            
            // Close visualization modal
            function closeVisualizationModal() {
                visualizationModal.classList.add('hidden');
            }
            
            // Show loading state
            function showLoading() {
                if (currentView === 'table') {
                    cropsTableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Loading crops...
                            </td>
                        </tr>
                    `;
                } else {
                    cardView.innerHTML = `
                        <div class="sm:col-span-1 lg:col-span-3 text-center py-10">
                            <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                            <p class="mt-2 text-sm text-gray-500">Loading crops...</p>
                        </div>
                    `;
                }
            }
            
            // Show success message
            function showSuccessMessage(message) {
                // Create toast notification
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50 animate-fade-in-up';
                toast.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>${message}</span>
                    </div>
                `;
                
                // Add to document
                document.body.appendChild(toast);
                
                // Remove after 3 seconds
                setTimeout(() => {
                    toast.classList.add('animate-fade-out-down');
                    setTimeout(() => {
                        document.body.removeChild(toast);
                    }, 300);
                }, 3000);
            }
            
            // Show error message
            function showErrorMessage(message) {
                // Create toast notification
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50 animate-fade-in-up';
                toast.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>${message}</span>
                    </div>
                `;
                
                // Add to document
                document.body.appendChild(toast);
                
                // Remove after 3 seconds
                setTimeout(() => {
                    toast.classList.add('animate-fade-out-down');
                    setTimeout(() => {
                        document.body.removeChild(toast);
                    }, 300);
                }, 3000);
            }
            
            // Helper function to get initials from name
            function getInitials(name) {
                if (!name) return 'NA';
                
                return name
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase())
                    .slice(0, 2)
                    .join('');
            }
            
            // Helper function to format status
            function formatStatus(status) {
                if (!status) return 'Unknown';
                
                const statusMap = {
                    'growing': 'Growing',
                    'harvested': 'Harvested',
                    'planned': 'Planned',
                    'problem': 'Needs Attention'
                };
                
                return statusMap[status] || status.charAt(0).toUpperCase() + status.slice(1);
            }
            
            // Helper function to get status class
            function getStatusClass(status) {
                if (!status) return '';
                
                const statusClassMap = {
                    'growing': 'status-growing',
                    'harvested': 'status-harvested',
                    'planned': 'status-planned',
                    'problem': 'status-problem'
                };
                
                return statusClassMap[status] || '';
            }
            
            // Helper function to format crop type
            function formatCropType(cropType) {
                if (!cropType) return 'Unknown';
                
                const cropTypeMap = {
                    'grain': 'Grain',
                    'vegetable': 'Vegetable',
                    'fruit': 'Fruit',
                    'legume': 'Legume',
                    'other': 'Other'
                };
                
                return cropTypeMap[cropType] || cropType.charAt(0).toUpperCase() + cropType.slice(1);
            }
            
            // Debounce function for search input
            function debounce(func, wait) {
                let timeout;
                return function() {
                    const context = this;
                    const args = arguments;
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        func.apply(context, args);
                    }, wait);
                };
            }
            
            // Initialize 3D visualization (using Three.js)
            function initVisualization(containerId, cropData) {
                const container = document.getElementById(containerId);
                
                // Clear any existing content
                container.innerHTML = '';
                
                // Create scene
                scene = new THREE.Scene();
                scene.background = new THREE.Color(0xf0f0f0);
                
                // Create camera
                camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
                camera.position.set(0, 10, 20);
                
                // Create renderer
                renderer = new THREE.WebGLRenderer({ antialias: true });
                renderer.setSize(container.clientWidth, container.clientHeight);
                container.appendChild(renderer.domElement);
                
                // Add ambient light
                const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
                scene.add(ambientLight);
                
                // Add directional light
                const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
                directionalLight.position.set(1, 1, 1);
                scene.add(directionalLight);
                
                // Create ground plane
                const groundGeometry = new THREE.PlaneGeometry(50, 50);
                const groundMaterial = new THREE.MeshStandardMaterial({ color: 0x7cbe63 });
                const ground = new THREE.Mesh(groundGeometry, groundMaterial);
                ground.rotation.x = -Math.PI / 2;
                ground.position.y = -0.5;
                scene.add(ground);
                
                // Add grid helper
                const gridHelper = new THREE.GridHelper(50, 50);
                scene.add(gridHelper);
                
                // Add crop visualization based on data
                if (cropData) {
                    // Example: Create a simple representation of crops
                    // This would be expanded based on actual crop data
                    const cropGeometry = new THREE.BoxGeometry(1, 1, 1);
                    const cropMaterial = new THREE.MeshStandardMaterial({ color: 0x00ff00 });
                    const crop = new THREE.Mesh(cropGeometry, cropMaterial);
                    crop.position.set(0, 0, 0);
                    scene.add(crop);
                }
                
                // Animation loop
                function animate() {
                    requestAnimationFrame(animate);
                    renderer.render(scene, camera);
                }
                
                // Start animation
                animate();
                
                // Handle window resize
                window.addEventListener('resize', function() {
                    camera.aspect = container.clientWidth / container.clientHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(container.clientWidth, container.clientHeight);
                });
            }
        });

        // Add CSS animations for toast notifications
        const style = document.createElement('style');
        style.textContent = `
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOutDown {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(20px);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.3s ease-out forwards;
        }

        .animate-fade-out-down {
            animation: fadeOutDown 0.3s ease-in forwards;
        }
        `;
        document.head.appendChild(style);

    </script>
    <!-- JavaScript will be added in the next step -->
</body>
</html>