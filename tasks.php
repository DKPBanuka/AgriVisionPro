<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php';

// Check if this is an AJAX request for a task operation
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    try {
        $jsonInput = file_get_contents('php://input');
        $requestData = json_decode($jsonInput, true);

        if ($requestData === null) {
            throw new Exception('Invalid JSON data received');
        }

        $action = $requestData['action'] ?? null;
        $taskData = $requestData['task'] ?? null;
        $userId = $requestData['userId'] ?? null;

        // Check user session
        if (empty($_SESSION['user_id'])) {
            throw new Exception('User not logged in');
        }

        $response = ['success' => false, 'message' => 'Unknown action'];
        
        // Rest of your code...
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// --- The rest of your existing tasks.php code (HTML generation, etc.) follows here ---
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
                    <a href="crops.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
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
                    <a href="tasks.php" class="flex items-center px-4 py-3 rounded-lg bg-blue-500 bg-opacity-30 text-white group">
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
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
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
                            
                            <!-- Attachments Section -->
                            <div class="sm:col-span-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Attachments</label>
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
    // Database configuration
    const DB_NAME = 'AgriVisionProDB';
    const DB_VERSION = 4;
    const STORE_NAME = 'tasks';
    let db;
    let isOnline = navigator.onLine;
    let pendingSync = [];

    // Initialize the database
    async function initDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        
        request.onerror = (event) => {
            console.error('Database error:', event.target.error);
            reject(event.target.error);
        };

        request.onsuccess = (event) => {
            db = event.target.result;
            
            // Add version change handler to prevent errors
            db.onversionchange = () => {
                db.close();
                console.log('Database is outdated, please reload the page.');
                showAlert('Database updated. Please refresh the page.', 'info');
            };
            
            console.log('Database initialized successfully');
            resolve(db);
        };

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            console.log('Database upgrade needed');
            
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                const store = db.createObjectStore(STORE_NAME, { 
                    keyPath: 'id', 
                    autoIncrement: true 
                });
                
                store.createIndex('title', 'title', { unique: false });
                store.createIndex('status', 'status', { unique: false });
                store.createIndex('priority', 'priority', { unique: false });
                store.createIndex('dueDate', 'dueDate', { unique: false });
                store.createIndex('syncStatus', 'syncStatus', { unique: false });
                
                // Add sample tasks if needed
                const sampleTasks = [
                    {
                        title: 'Harvest corn in Field 3',
                        description: 'Use the combine harvester to collect corn if weather permits',
                        assignedTo: 'John Doe',
                        dueDate: '2025-03-25',
                        status: 'overdue',
                        priority: 'high',
                        createdAt: new Date(),
                        updatedAt: new Date(),
                        syncStatus: 'synced',
                        statusHistory: [
                            {
                                status: 'pending',
                                date: new Date('2025-03-01'),
                                changedBy: 'System'
                            },
                            {
                                status: 'overdue',
                                date: new Date('2025-03-26'),
                                changedBy: 'System'
                            }
                        ]
                    }
                ];
                
                sampleTasks.forEach(task => {
                    store.add(task);
                });
            }
        };
    });
}

    async function verifyDatabase() {
        try {
            const transaction = db.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            const request = store.getAll();
            
            return new Promise((resolve, reject) => {
                request.onsuccess = () => {
                    const tasks = request.result || [];
                    console.log(`Database contains ${tasks.length} tasks`);
                    resolve(true);
                };
                
                request.onerror = (event) => {
                    console.error('Database verification failed:', event.target.error);
                    reject(event.target.error);
                };
            });
        } catch (error) {
            console.error('Error verifying database:', error);
            throw error;
        }
    }


    // ========== CRUD Operations ========== //
    async function addTask(taskData) {
        return new Promise((resolve, reject) => {
            const transaction = db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            
            // Set sync status based on online status
            taskData.syncStatus = isOnline ? 'synced' : 'pending';
            taskData.createdAt = new Date();
            taskData.updatedAt = new Date();
            
            const request = store.add(taskData);
            
            request.onsuccess = async () => {
                const taskId = request.result;
                if (isOnline) {
                    try {
                        await syncTaskToServer({...taskData, id: taskId});
                        showNotification('Task created and synced to server', 'success');
                    } catch (error) {
                        console.error('Sync error:', error);
                        await updateTask(taskId, {syncStatus: 'pending'});
                        pendingSync.push({type: 'add', data: {...taskData, id: taskId}});
                        showNotification('Task created offline. Will sync when online', 'warning');
                    }
                } else {
                    pendingSync.push({type: 'add', data: {...taskData, id: taskId}});
                    showNotification('Task created offline. Will sync when online', 'warning');
                }
                resolve(taskId);
            };
            
            request.onerror = (event) => reject(event.target.error);
        });
    }

    async function getTask(id) {
        return new Promise((resolve, reject) => {
            const transaction = db.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            
            const request = store.get(id);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = (event) => reject(event.target.error);
        });
    }

    async function getAllTasks(filters = {}) {
        return new Promise((resolve, reject) => {
            if (!db) {
                reject('Database not initialized');
                return;
            }

            const transaction = db.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            
            const request = store.getAll();
            
            request.onsuccess = () => {
                let tasks = request.result || [];
                
                // Apply filters
                if (filters.searchTerm) {
                    const term = filters.searchTerm.toLowerCase();
                    tasks = tasks.filter(task => 
                        task.title.toLowerCase().includes(term) || 
                        (task.description && task.description.toLowerCase().includes(term)) ||
                        (task.assignedTo && task.assignedTo.toLowerCase().includes(term))
                    );
                }
                
                if (filters.status && filters.status !== 'all') {
                    tasks = tasks.filter(task => task.status === filters.status);
                }
                
                if (filters.priority && filters.priority !== 'all') {
                    tasks = tasks.filter(task => task.priority === filters.priority);
                }
                
                // Mark overdue tasks
                const today = new Date().toISOString().split('T')[0];
                tasks.forEach(task => {
                    if (task.dueDate < today && task.status !== 'completed') {
                        task.status = 'overdue';
                    }
                });
                
                // Sort tasks
                if (filters.sortBy) {
                    tasks = sortTasks(tasks, filters.sortBy);
                }
                
                resolve(tasks);
            };
            
            request.onerror = (event) => reject(event.target.error);
        });
    }

    async function updateTask(id, updates) {
        return new Promise((resolve, reject) => {
            const transaction = db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            
            const getRequest = store.get(id);
            
            getRequest.onsuccess = async () => {
                const currentData = getRequest.result;
                if (!currentData) {
                    reject('Task not found');
                    return;
                }
                
                const updatedData = { 
                    ...currentData, 
                    ...updates,
                    updatedAt: new Date(),
                    syncStatus: isOnline ? 'synced' : 'pending'
                };
                
                const putRequest = store.put(updatedData);
                
                putRequest.onsuccess = async () => {
                    if (isOnline) {
                        try {
                            await syncTaskToServer(updatedData);
                            showNotification('Task updated and synced to server', 'success');
                        } catch (error) {
                            console.error('Sync error:', error);
                            await updateTask(id, {syncStatus: 'pending'});
                            pendingSync.push({type: 'update', data: updatedData});
                            showNotification('Task updated offline. Will sync when online', 'warning');
                        }
                    } else {
                        pendingSync.push({type: 'update', data: updatedData});
                        showNotification('Task updated offline. Will sync when online', 'warning');
                    }
                    resolve(putRequest.result);
                };
                
                putRequest.onerror = (event) => reject(event.target.error);
            };
            
            getRequest.onerror = (event) => reject(event.target.error);
        });
    }

    async function deleteTask(id) {
        return new Promise((resolve, reject) => {
            const transaction = db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            
            const getRequest = store.get(id);
            
            getRequest.onsuccess = async () => {
                const taskData = getRequest.result;
                if (!taskData) {
                    reject('Task not found');
                    return;
                }
                
                const deleteRequest = store.delete(id);
                
                deleteRequest.onsuccess = async () => {
                    if (isOnline) {
                        try {
                            await deleteTaskFromServer(id);
                            showNotification('Task deleted from server', 'success');
                        } catch (error) {
                            console.error('Sync error:', error);
                            pendingSync.push({type: 'delete', data: {id}});
                            showNotification('Task deleted offline. Will sync when online', 'warning');
                        }
                    } else {
                        pendingSync.push({type: 'delete', data: {id}});
                        showNotification('Task deleted offline. Will sync when online', 'warning');
                    }
                    resolve(true);
                };
                
                deleteRequest.onerror = (event) => reject(event.target.error);
            };
            
            getRequest.onerror = (event) => reject(event.target.error);
        });
    }

    // ========== Server Sync Functions ========== //
    async function syncTaskToServer(taskData) {
    if (!isOnline) {
        throw new Error('Offline - cannot sync with server');
    }
    
    try {
        const response = await fetch('tasks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: taskData.id ? 'update' : 'create',
                task: taskData,
                userId: <?= $_SESSION['user_id'] ?? 0 ?>
            })
        });
        
        // First check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Server returned:', text.substring(0, 200));
            throw new Error('Server returned HTML instead of JSON');
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Server returned error');
        }
        
        return result;
        
    } catch (error) {
        console.error('Sync failed:', error);
        throw error;
    }
}
    async function deleteTaskFromServer(id) {
        if (!isOnline) {
            throw new Error('Offline - cannot sync with server');
        }
        
        try {
            const response = await fetch('tasks.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: id
                })
            });
            
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}`);
            }
            
            const result = await response.json();
            return result;
        } catch (error) {
            console.error('Delete sync to server failed:', error);
            throw error;
        }
    }

    async function syncPendingTasks() {
        if (!isOnline || pendingSync.length === 0) return;
        
        showNotification('Syncing offline changes with server...', 'info');
        
        const successes = [];
        const failures = [];
        
        for (const operation of pendingSync) {
            try {
                if (operation.type === 'add') {
                    await syncTaskToServer(operation.data);
                    await updateTask(operation.data.id, {syncStatus: 'synced'});
                    successes.push(`Added: ${operation.data.title}`);
                } else if (operation.type === 'update') {
                    await syncTaskToServer(operation.data);
                    await updateTask(operation.data.id, {syncStatus: 'synced'});
                    successes.push(`Updated: ${operation.data.title}`);
                } else if (operation.type === 'delete') {
                    await deleteTaskFromServer(operation.data.id);
                    successes.push(`Deleted: Task ID ${operation.data.id}`);
                }
            } catch (error) {
                failures.push(operation);
                console.error(`Failed to sync ${operation.type} operation:`, error);
            }
        }
        
        // Update pending sync array with only failed operations
        pendingSync = failures;
        
        if (successes.length > 0) {
            showNotification(`Successfully synced ${successes.length} task(s)`, 'success');
        }
        if (failures.length > 0) {
            showNotification(`Failed to sync ${failures.length} task(s). Will retry later.`, 'error');
        }
    }

    // ========== UI Functions ========== //
    function setupEventListeners() {
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
    document.addEventListener('click', function(event) {
        if (!userMenu.contains(event.target)) {
            userMenuDropdown.classList.add('hidden');
        }
        if (!document.getElementById('notifications-btn').contains(event.target)) {
            document.getElementById('notifications-dropdown').classList.add('hidden');
        }
    });
    
    // Search functionality
    document.getElementById('search-input').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const searchResults = document.getElementById('search-results');
        
        if (searchTerm.length > 2) {
            // In a real app, you would fetch search results from the server
            // Here we'll simulate it with a timeout
            setTimeout(() => {
                const mockResults = [
                    { id: 1, title: 'Harvest corn in Field 3', type: 'task' },
                    { id: 2, title: 'Feed livestock in Barn A', type: 'task' },
                    { id: 3, title: 'Check irrigation system', type: 'task' }
                ].filter(item => item.title.toLowerCase().includes(searchTerm));
                
                if (mockResults.length > 0) {
                    searchResults.innerHTML = mockResults.map(item => `
                        <div class="search-item" data-id="${item.id}" data-type="${item.type}">
                            <div class="font-medium">${item.title}</div>
                            <div class="text-xs text-gray-500">${item.type}</div>
                        </div>
                    `).join('');
                    searchResults.classList.remove('hidden');
                } else {
                    searchResults.innerHTML = '<div class="search-item text-gray-500">No results found</div>';
                    searchResults.classList.remove('hidden');
                }
            }, 300);
        } else {
            searchResults.classList.add('hidden');
        }
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', function(event) {
        if (!document.getElementById('search-input').contains(event.target)) {
            document.getElementById('search-results').classList.add('hidden');
        }
    });
    
    // Handle search result selection
    document.getElementById('search-results').addEventListener('click', function(e) {
        const item = e.target.closest('.search-item');
        if (item) {
            const id = item.getAttribute('data-id');
            const type = item.getAttribute('data-type');
            
            // In a real app, you would handle different types of search results
            if (type === 'task') {
                showTaskDetails(parseInt(id));
            }
            
            document.getElementById('search-results').classList.add('hidden');
            document.getElementById('search-input').value = '';
        }
    });

    // Add task button
    document.getElementById('add-task-btn').addEventListener('click', showAddTaskModal);
    
    // Save task form
    document.getElementById('save-task').addEventListener('click', handleSaveTask);
    
    // Cancel task form
    document.getElementById('cancel-task').addEventListener('click', () => {
        document.getElementById('task-modal').classList.add('hidden');
    });
    
    // Search tasks
    document.getElementById('search-tasks').addEventListener('input', async (e) => {
        await loadTasksTable({ searchTerm: e.target.value });
    });
    
    // Status filter
    document.getElementById('status-filter').addEventListener('change', async (e) => {
        await loadTasksTable({ status: e.target.value });
    });
    
    // Priority filter
    document.getElementById('priority-filter').addEventListener('change', async (e) => {
        await loadTasksTable({ priority: e.target.value });
    });
    
    // Sort by
    document.getElementById('sort-by').addEventListener('change', async (e) => {
        await loadTasksTable({ sortBy: e.target.value });
    });
    
    // View toggle
    document.getElementById('view-toggle').addEventListener('click', toggleView);
    
    // Close task details modal
    document.getElementById('close-details').addEventListener('click', () => {
        document.getElementById('task-details-modal').classList.add('hidden');
    });
    
    document.getElementById('close-details-btn').addEventListener('click', () => {
        document.getElementById('task-details-modal').classList.add('hidden');
    });
    
    // Edit task from details
    document.getElementById('edit-task-from-details').addEventListener('click', async () => {
        const taskId = document.getElementById('task-details-modal').getAttribute('data-task-id');
        if (taskId) {
            document.getElementById('task-details-modal').classList.add('hidden');
            await showEditTaskModal(parseInt(taskId));
        }
    });
    
    // Online/offline detection
    window.addEventListener('online', handleConnectionChange);
    window.addEventListener('offline', handleConnectionChange);
    
    // File upload handling
    document.getElementById('task-attachments').addEventListener('change', handleFileUpload);
    document.getElementById('remove-attachments').addEventListener('click', clearFileUpload);
    
    // Add related item
    document.getElementById('add-related-item').addEventListener('click', addRelatedItem);
}

    function toggleView() {
        const cardView = document.getElementById('card-view');
        const tableView = document.getElementById('table-view');
        const viewToggle = document.getElementById('view-toggle');
        
        if (cardView.classList.contains('hidden')) {
            cardView.classList.remove('hidden');
            tableView.classList.add('hidden');
            viewToggle.innerHTML = '<i class="fas fa-list mr-2"></i> Table View';
            renderTasksCards();
        } else {
            cardView.classList.add('hidden');
            tableView.classList.remove('hidden');
            viewToggle.innerHTML = '<i class="fas fa-table mr-2"></i> Card View';
        }
    }

    async function loadTasksTable(filters = {}) {
    try {
        const tasks = await getAllTasks(filters);
        updateSummaryCards(tasks);
        renderTasksTable(tasks);
        renderTasksCards(tasks);
        
        // Only update chart if we have tasks
        if (tasks && tasks.length > 0) {
            updateStatusChart(tasks);
        } else {
            updateStatusChart([]); // Clear the chart
        }
    } catch (error) {
        console.error('Error loading tasks:', error);
        showAlert('Failed to load tasks. ' + error.message, 'error');
        
        // Ensure chart is cleared if there's an error
        updateStatusChart([]);
    }
}

    function updateSummaryCards(tasks) {
        if (!tasks) return;
        
        const totalTasks = tasks.length;
        const pendingCount = tasks.filter(t => t.status === 'pending').length;
        const inProgressCount = tasks.filter(t => t.status === 'in-progress').length;
        const overdueCount = tasks.filter(t => t.status === 'overdue').length;
        const completedCount = tasks.filter(t => t.status === 'completed').length;
        
        document.getElementById('total-tasks-count').textContent = totalTasks;
        document.getElementById('pending-count').textContent = pendingCount;
        document.getElementById('in-progress-count').textContent = inProgressCount;
        document.getElementById('overdue-count').textContent = overdueCount;
    }

    function renderTasksTable(tasks) {
        const tableBody = document.getElementById('tasks-table-body');
        
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
        
        tableBody.innerHTML = tasks.map(task => `
            <tr class="hover:bg-gray-50" data-id="${task.id}">
                <td class="px-6 py-4">
                    <div class="font-medium text-gray-900 flex items-center">
                        ${task.title}
                        ${task.syncStatus === 'pending' ? 
                            '<span class="ml-2 text-xs text-yellow-600" title="Pending sync"><i class="fas fa-cloud-upload-alt"></i></span>' : ''}
                    </div>
                    <div class="text-sm text-gray-500 truncate max-w-xs">${task.description || ''}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">${task.assignedTo || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div>${formatDate(task.dueDate)}</div>
                    ${isOverdue(task.dueDate, task.status) ? 
                        `<div class="text-xs text-red-500">${daysOverdue(task.dueDate)} days overdue</div>` : ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full priority-${task.priority}">
                        ${capitalizeFirstLetter(task.priority)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-${task.status}">
                        ${capitalizeFirstLetter(task.status.replace('-', ' '))}
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
        `).join('');
        
        // Add event listeners to all view buttons
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const row = e.target.closest('tr');
                const id = parseInt(row.getAttribute('data-id'));
                await showTaskDetails(id);
            });
        });
        
        // Add event listeners to all edit buttons
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const row = e.target.closest('tr');
                const id = parseInt(row.getAttribute('data-id'));
                await showEditTaskModal(id);
            });
        });
        
        // Add event listeners to all delete buttons
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const row = e.target.closest('tr');
                const id = parseInt(row.getAttribute('data-id'));
                await handleDeleteTask(id);
            });
        });
    }

    function renderTasksCards(tasks) {
        const cardView = document.getElementById('card-view');
        
        if (!tasks || tasks.length === 0) {
            cardView.innerHTML = `
                <div class="sm:col-span-2 lg:col-span-3 text-center py-10">
                    <i class="fas fa-tasks text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No tasks found. Click "Add New Task" to get started.</p>
                </div>
            `;
            return;
        }
        
        cardView.innerHTML = tasks.map(task => `
            <div class="task-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 hover:border-blue-300">
                <div class="p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg text-gray-800 mb-1">${task.title}</h3>
                            <p class="text-sm text-gray-600 mb-2">${task.description || 'No description'}</p>
                        </div>
                        ${task.syncStatus === 'pending' ? 
                            '<span class="text-yellow-500 text-xs" title="Pending sync"><i class="fas fa-cloud-upload-alt"></i></span>' : ''}
                    </div>
                    
                    <div class="flex items-center text-sm text-gray-500 mb-2">
                        <i class="fas fa-user mr-1"></i>
                        <span>${task.assignedTo || 'Unassigned'}</span>
                    </div>
                    
                    <div class="flex items-center text-sm ${isOverdue(task.dueDate, task.status) ? 'text-red-500' : 'text-gray-500'} mb-3">
                        <i class="fas fa-calendar-day mr-1"></i>
                        <span>${formatDate(task.dueDate) || 'No due date'}</span>
                        ${isOverdue(task.dueDate, task.status) ? 
                            `<span class="ml-2 text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">${daysOverdue(task.dueDate)} days overdue</span>` : ''}
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full status-${task.status}">
                            ${capitalizeFirstLetter(task.status.replace('-', ' '))}
                        </span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full priority-${task.priority}">
                            ${capitalizeFirstLetter(task.priority)}
                        </span>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 flex justify-end space-x-2 border-t border-gray-200">
                    <button class="view-btn text-blue-600 hover:text-blue-800 p-1" data-id="${task.id}" title="View details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="edit-btn text-blue-600 hover:text-blue-800 p-1" data-id="${task.id}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="delete-btn text-red-600 hover:text-red-800 p-1" data-id="${task.id}" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
        
        // Add event listeners to card buttons
        document.querySelectorAll('.card-view .view-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = parseInt(e.target.closest('button').getAttribute('data-id'));
                await showTaskDetails(id);
            });
        });
        
        document.querySelectorAll('.card-view .edit-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = parseInt(e.target.closest('button').getAttribute('data-id'));
                await showEditTaskModal(id);
            });
        });
        
        document.querySelectorAll('.card-view .delete-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = parseInt(e.target.closest('button').getAttribute('data-id'));
                await handleDeleteTask(id);
            });
        });
    }

    function updateStatusChart(tasks) {
    if (!tasks || tasks.length === 0) {
        // If no tasks, clear the chart if it exists
        if (window.statusChart && typeof window.statusChart.destroy === 'function') {
            window.statusChart.destroy();
            window.statusChart = null;
        }
        return;
    }
    
    const ctx = document.getElementById('statusChart').getContext('2d');
    
    // Count tasks by status
    const statusCounts = {
        pending: 0,
        'in-progress': 0,
        completed: 0,
        overdue: 0
    };
    
    tasks.forEach(task => {
        statusCounts[task.status] = (statusCounts[task.status] || 0) + 1;
    });
    
    // Destroy previous chart if it exists
    if (window.statusChart && typeof window.statusChart.destroy === 'function') {
        window.statusChart.destroy();
    }
    
    // Create new chart
    window.statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'In Progress', 'Completed', 'Overdue'],
            datasets: [{
                data: [
                    statusCounts.pending,
                    statusCounts['in-progress'],
                    statusCounts.completed,
                    statusCounts.overdue
                ],
                backgroundColor: [
                    '#F59E0B',
                    '#3B82F6',
                    '#10B981',
                    '#EF4444'
                ],
                hoverBackgroundColor: [
                    '#D97706',
                    '#2563EB',
                    '#059669',
                    '#DC2626'
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
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
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
        document.getElementById('related-items-container').innerHTML = '';
        document.getElementById('file-upload-area').classList.remove('hidden');
        document.getElementById('file-preview').classList.add('hidden');
        document.getElementById('task-modal').classList.remove('hidden');
    }

    async function showEditTaskModal(id) {
        try {
            const task = await getTask(id);
            if (!task) {
                throw new Error('Task not found');
            }
            
            document.getElementById('modal-title').textContent = 'Edit Task';
            document.getElementById('task-id').value = task.id;
            document.getElementById('task-title').value = task.title || '';
            document.getElementById('task-description').value = task.description || '';
            document.getElementById('task-assigned-to').value = task.assignedTo || '';
            document.getElementById('task-due-date').value = task.dueDate || '';
            document.getElementById('task-status').value = task.status === 'overdue' ? 'pending' : task.status || 'pending';
            document.getElementById('task-priority').value = task.priority || 'medium';
            
            // Clear and re-add related items
            const relatedItemsContainer = document.getElementById('related-items-container');
            relatedItemsContainer.innerHTML = '';
            if (task.relatedItems && task.relatedItems.length > 0) {
                task.relatedItems.forEach(item => {
                    addRelatedItem(item.type, item.id, item.name);
                });
            }
            
            // Handle attachments preview
            const fileUploadArea = document.getElementById('file-upload-area');
            const filePreview = document.getElementById('file-preview');
            const previewList = document.getElementById('attachment-preview-list');
            
            previewList.innerHTML = '';
            
            if (task.attachments && task.attachments.length > 0) {
                task.attachments.forEach(attachment => {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'flex items-center justify-between p-2 bg-gray-100 rounded';
                    previewItem.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-file-${attachment.type === 'pdf' ? 'pdf' : 'image'} text-${attachment.type === 'pdf' ? 'red' : 'blue'}-500 mr-2"></i>
                            <span class="text-sm truncate max-w-xs">${attachment.name}</span>
                        </div>
                        <a href="${attachment.url}" target="_blank" class="text-blue-500 hover:text-blue-700 ml-2">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    `;
                    previewList.appendChild(previewItem);
                });
                
                fileUploadArea.classList.add('hidden');
                filePreview.classList.remove('hidden');
            } else {
                fileUploadArea.classList.remove('hidden');
                filePreview.classList.add('hidden');
            }
            
            document.getElementById('task-modal').classList.remove('hidden');
        } catch (error) {
            console.error('Error showing edit modal:', error);
            showAlert('Failed to load task data for editing. ' + error.message, 'error');
        }
    }

    async function showTaskDetails(id) {
        try {
            const task = await getTask(id);
            if (!task) {
                throw new Error('Task not found');
            }
            
            // Set basic info
            document.getElementById('task-details-title-text').textContent = task.title || '-';
            document.getElementById('task-details-description').textContent = task.description || 'No description available';
            document.getElementById('task-details-assigned-to').textContent = task.assignedTo || '-';
            document.getElementById('task-details-due-date').textContent = formatDate(task.dueDate) || '-';
            
            // Set status and priority badges
            const statusBadge = document.getElementById('task-details-status');
            statusBadge.textContent = capitalizeFirstLetter(task.status.replace('-', ' ')) || '-';
            statusBadge.className = 'mt-1 text-sm font-medium px-2 py-1 rounded-full inline-block status-' + task.status;
            
            const priorityBadge = document.getElementById('task-details-priority');
            priorityBadge.textContent = capitalizeFirstLetter(task.priority) || '-';
            priorityBadge.className = 'mt-1 text-sm font-medium px-2 py-1 rounded-full inline-block priority-' + task.priority;
            
            // Set timeline dates
            document.getElementById('task-details-created-date').textContent = 
                formatDateTime(task.createdAt) || '-';
            document.getElementById('task-details-updated-date').textContent = 
                formatDateTime(task.updatedAt) || '-';
            
            // Set status history
            const statusHistory = document.getElementById('task-details-status-history');
            if (task.statusHistory && task.statusHistory.length > 0) {
                statusHistory.innerHTML = task.statusHistory.map(entry => `
                    <div class="mb-1">
                        <span class="font-medium">${capitalizeFirstLetter(entry.status.replace('-', ' '))}</span> 
                        on ${formatDateTime(entry.date)}
                    </div>
                `).join('');
            } else {
                statusHistory.textContent = 'No status history available';
            }
            
            // Set related items
            const relatedItemsContainer = document.getElementById('task-details-related-items');
            relatedItemsContainer.innerHTML = '';
            
            if (task.relatedItems && task.relatedItems.length > 0) {
                task.relatedItems.forEach(item => {
                    const itemElement = document.createElement('span');
                    itemElement.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800';
                    itemElement.innerHTML = `
                        <i class="fas fa-${getRelatedItemIcon(item.type)} mr-1"></i>
                        ${item.name}
                    `;
                    relatedItemsContainer.appendChild(itemElement);
                });
            } else {
                relatedItemsContainer.innerHTML = '<p class="text-sm text-gray-500">No related items</p>';
            }
            
            // Set attachments
            const attachmentsContainer = document.getElementById('task-details-attachments');
            attachmentsContainer.innerHTML = '';
            
            if (task.attachments && task.attachments.length > 0) {
                task.attachments.forEach(attachment => {
                    const attachmentElement = document.createElement('div');
                    attachmentElement.className = 'flex items-center justify-between p-2 border border-gray-200 rounded';
                    attachmentElement.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-file-${attachment.type === 'pdf' ? 'pdf' : 'image'} text-${attachment.type === 'pdf' ? 'red' : 'blue'}-500 mr-2"></i>
                            <span class="text-sm">${attachment.name}</span>
                        </div>
                        <a href="${attachment.url}" target="_blank" class="text-blue-500 hover:text-blue-700 ml-4">
                            <i class="fas fa-download"></i>
                        </a>
                    `;
                    attachmentsContainer.appendChild(attachmentElement);
                });
            } else {
                attachmentsContainer.innerHTML = '<p class="text-sm text-gray-500">No attachments</p>';
            }
            
            // Load activities
            loadTaskActivities(id);
            
            // Set task ID on modal for edit button
            document.getElementById('task-details-modal').setAttribute('data-task-id', id);
            
            // Show modal
            document.getElementById('task-details-modal').classList.remove('hidden');
        } catch (error) {
            console.error('Error showing task details:', error);
            showAlert('Failed to load task details. ' + error.message, 'error');
        }
    }

    async function loadTaskActivities(taskId) {
        const activitiesContainer = document.getElementById('task-activities');
        activitiesContainer.innerHTML = `
            <div class="text-center py-4 text-sm text-gray-500 flex justify-center items-start">
                <i class="fas fa-spinner fa-spin mr-2"></i> Loading activities...
            </div>
        `;
        
        try {
            // Simulate loading activities (in a real app, this would come from the server)
            setTimeout(() => {
                const activities = [
                    {
                        id: 1,
                        type: 'status',
                        user: 'John Doe',
                        action: 'changed status to "In Progress"',
                        timestamp: new Date(Date.now() - 3600000),
                        avatar: 'JD'
                    },
                    {
                        id: 2,
                        type: 'comment',
                        user: 'Sarah Johnson',
                        action: 'added a comment: "I\'ll start working on this tomorrow morning"',
                        timestamp: new Date(Date.now() - 86400000),
                        avatar: 'SJ'
                    },
                    {
                        id: 3,
                        type: 'create',
                        user: 'System',
                        action: 'created this task',
                        timestamp: new Date(Date.now() - 172800000),
                        avatar: 'AV'
                    }
                ];
                
                renderActivities(activities);
            }, 800);
        } catch (error) {
            console.error('Error loading activities:', error);
            activitiesContainer.innerHTML = `
                <div class="text-center py-4 text-sm text-red-500">
                    Failed to load activities. Please try again later.
                </div>
            `;
        }
    }

    function renderActivities(activities) {
        const activitiesContainer = document.getElementById('task-activities');
        
        if (!activities || activities.length === 0) {
            activitiesContainer.innerHTML = `
                <div class="text-center py-4 text-sm text-gray-500">
                    No activities found for this task.
                </div>
            `;
            return;
        }
        
        activitiesContainer.innerHTML = activities.map(activity => `
            <div class="activity-item p-4 border-b border-gray-200 last:border-0">
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium">
                        ${activity.avatar}
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">${activity.user}</p>
                            <p class="text-xs text-gray-500">${formatDateTime(activity.timestamp)}</p>
                        </div>
                        <p class="text-sm text-gray-700">
                            ${activity.action}
                        </p>
                        ${activity.type === 'comment' ? `
                            <div class="mt-2 text-xs text-gray-500 italic">
                                <i class="fas fa-comment-alt mr-1"></i> Comment
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    async function handleSaveTask() {
    const id = document.getElementById('task-id').value;
    const formData = {
        title: document.getElementById('task-title').value,
        description: document.getElementById('task-description').value,
        assignedTo: document.getElementById('task-assigned-to').value,
        dueDate: document.getElementById('task-due-date').value,
        status: document.getElementById('task-status').value,
        priority: document.getElementById('task-priority').value,
        updatedAt: new Date(),
        createdAt: id ? undefined : new Date() // Only set for new tasks
    };
    
    if (!formData.title) {
        showAlert('Task title is required', 'error');
        return;
    }
    
    try {
        // Show loading state
        const saveBtn = document.getElementById('save-task');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
        
        if (id) {
            // For existing tasks
            const existingTask = await getTask(parseInt(id));
            if (!existingTask) {
                throw new Error('Task not found');
            }
            
            // Preserve created date for existing tasks
            formData.createdAt = existingTask.createdAt;
            
            // Add status history if status changed
            if (existingTask.status !== formData.status) {
                formData.statusHistory = existingTask.statusHistory || [];
                formData.statusHistory.push({
                    status: formData.status,
                    date: new Date(),
                    changedBy: 'Current User' // Replace with actual user
                });
            }
            
            await updateTask(parseInt(id), formData);
            showAlert('Task updated successfully!', 'success');
        } else {
            // For new tasks
            formData.statusHistory = [{
                status: formData.status,
                date: new Date(),
                changedBy: 'Current User' // Replace with actual user
            }];
            
            await addTask(formData);
            showAlert('Task added successfully!', 'success');
        }
        
        // Close modal and refresh data
        document.getElementById('task-modal').classList.add('hidden');
        await loadTasksTable();
        
    } catch (error) {
        console.error('Error saving task:', error);
        showAlert('Error saving task. ' + error.message, 'error');
    } finally {
        // Reset save button state
        const saveBtn = document.getElementById('save-task');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Task';
    }
}

    async function handleDeleteTask(id) {
        try {
            const task = await getTask(id);
            if (!task) {
                throw new Error('Task not found');
            }
            
            if (confirm(`Are you sure you want to delete "${task.title}"? This action cannot be undone.`)) {
                await deleteTask(id);
                showAlert('Task deleted successfully!', 'success');
                await loadTasksTable();
            }
        } catch (error) {
            console.error('Error deleting task:', error);
            showAlert('Error deleting task. ' + error.message, 'error');
        }
    }

    function handleFileUpload(event) {
        const files = event.target.files;
        if (!files || files.length === 0) return;
        
        const fileUploadArea = document.getElementById('file-upload-area');
        const filePreview = document.getElementById('file-preview');
        const previewList = document.getElementById('attachment-preview-list');
        
        previewList.innerHTML = '';
        
        Array.from(files).forEach(file => {
            const fileType = file.type.includes('pdf') ? 'pdf' : 'image';
            const previewItem = document.createElement('div');
            previewItem.className = 'flex items-center justify-between p-2 bg-gray-100 rounded';
            previewItem.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-file-${fileType} text-${fileType === 'pdf' ? 'red' : 'blue'}-500 mr-2"></i>
                    <span class="text-sm truncate max-w-xs">${file.name}</span>
                    <span class="text-xs text-gray-500 ml-2">(${(file.size / 1024).toFixed(1)} KB)</span>
                </div>
            `;
            previewList.appendChild(previewItem);
        });
        
        fileUploadArea.classList.add('hidden');
        filePreview.classList.remove('hidden');
    }

    function clearFileUpload() {
        document.getElementById('task-attachments').value = '';
        document.getElementById('file-upload-area').classList.remove('hidden');
        document.getElementById('file-preview').classList.add('hidden');
    }

    function addRelatedItem(type = null, id = null, name = null) {
        const container = document.getElementById('related-items-container');
        
        const itemId = id || Math.random().toString(36).substr(2, 9);
        const itemType = type || 'crop';
        const itemName = name || '';
        
        const itemElement = document.createElement('div');
        itemElement.className = 'flex items-center bg-gray-100 rounded-full px-3 py-1';
        itemElement.innerHTML = `
            <select class="bg-transparent border-none text-sm focus:ring-0 focus:border-none p-0 mr-2">
                <option value="crop" ${itemType === 'crop' ? 'selected' : ''}>Crop</option>
                <option value="livestock" ${itemType === 'livestock' ? 'selected' : ''}>Livestock</option>
                <option value="equipment" ${itemType === 'equipment' ? 'selected' : ''}>Equipment</option>
                <option value="field" ${itemType === 'field' ? 'selected' : ''}>Field</option>
            </select>
            <input type="text" class="bg-transparent border-none text-sm focus:ring-0 focus:border-none p-0 w-24" 
                placeholder="ID or name" value="${itemName}">
            <button class="remove-related-item ml-2 text-gray-500 hover:text-red-500">
                <i class="fas fa-times"></i>
            </button>
            <input type="hidden" name="related_item_id" value="${itemId}">
        `;
        
        container.appendChild(itemElement);
        
        // Add event listener to remove button
        itemElement.querySelector('.remove-related-item').addEventListener('click', () => {
            container.removeChild(itemElement);
        });
    }

    function handleConnectionChange() {
        isOnline = navigator.onLine;
        const statusElement = document.getElementById('connection-status');
        
        if (isOnline) {
            statusElement.className = 'mb-4 p-2 rounded-md text-sm flex items-center bg-green-100 text-green-800';
            statusElement.innerHTML = '<i class="fas fa-circle mr-2 text-green-500"></i><span>Online - changes will sync to server</span>';
            
            // Try to sync pending changes
            syncPendingTasks();
        } else {
            statusElement.className = 'mb-4 p-2 rounded-md text-sm flex items-center bg-yellow-100 text-yellow-800';
            statusElement.innerHTML = '<i class="fas fa-circle mr-2 text-yellow-500"></i><span>Offline - changes saved locally and will sync when online</span>';
        }
        
        statusElement.classList.remove('hidden');
        
        // Hide after 5 seconds
        setTimeout(() => {
            statusElement.classList.add('hidden');
        }, 5000);
    }

    // ========== Notification Functions ========== //
    function showNotification(message, type = 'info') {
        const notificationCount = document.getElementById('notification-count');
        const notificationsList = document.getElementById('notifications-list');
        
        // Create notification item
        const notificationItem = document.createElement('div');
        notificationItem.className = `notification-item unread ${type === 'error' ? 'bg-red-50' : type === 'success' ? 'bg-green-50' : 'bg-blue-50'}`;
        notificationItem.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0 mt-0.5">
                    <i class="fas ${type === 'error' ? 'fa-exclamation-circle text-red-500' : type === 'success' ? 'fa-check-circle text-green-500' : 'fa-info-circle text-blue-500'}"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm text-gray-700">${message}</p>
                    <p class="text-xs text-gray-500 mt-1">${formatDateTime(new Date())}</p>
                </div>
            </div>
        `;
        
        // Add to top of list
        if (notificationsList.firstChild) {
            notificationsList.insertBefore(notificationItem, notificationsList.firstChild);
        } else {
            notificationsList.appendChild(notificationItem);
        }
        
        // Update notification count
        const currentCount = parseInt(notificationCount.textContent) || 0;
        notificationCount.textContent = currentCount + 1;
        notificationCount.classList.remove('hidden');
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notificationItem.classList.remove('unread');
        }, 5000);
    }

    function setupNotificationsDropdown() {
        const notificationsBtn = document.getElementById('notifications-btn');
        const notificationsDropdown = document.getElementById('notifications-dropdown');
        
        notificationsBtn.addEventListener('click', function() {
            notificationsDropdown.classList.toggle('hidden');
            
            // Mark notifications as read when dropdown is opened
            if (!notificationsDropdown.classList.contains('hidden')) {
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Reset notification count
                document.getElementById('notification-count').classList.add('hidden');
                document.getElementById('notification-count').textContent = '0';
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!notificationsBtn.contains(event.target) ){
                notificationsDropdown.classList.add('hidden');
            }
        });
    }

    // ========== Helper Functions ========== //
    function formatDate(dateString) {
        if (!dateString) return '-';
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    function formatDateTime(date) {
        if (!date) return '-';
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(date).toLocaleDateString(undefined, options);
    }

    function isOverdue(dueDate, status) {
        if (!dueDate || status === 'completed') return false;
        const today = new Date().toISOString().split('T')[0];
        return dueDate < today;
    }

    function daysOverdue(dueDate) {
        if (!dueDate) return 0;
        const today = new Date();
        const due = new Date(dueDate);
        const diffTime = today - due;
        return Math.floor(diffTime / (1000 * 60 * 60 * 24));
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
            default: return 'link';
        }
    }

    function sortTasks(tasks, sortBy) {
        if (!tasks) return [];
        
        return [...tasks].sort((a, b) => {
            switch(sortBy) {
                case 'title-asc':
                    return (a.title || '').localeCompare(b.title || '');
                case 'title-desc':
                    return (b.title || '').localeCompare(a.title || '');
                case 'due-date-asc':
                    return new Date(a.dueDate || 0) - new Date(b.dueDate || 0);
                case 'due-date-desc':
                    return new Date(b.dueDate || 0) - new Date(a.dueDate || 0);
                case 'priority-asc':
                    const priorityOrder = { 'high': 3, 'medium': 2, 'low': 1 };
                    return (priorityOrder[a.priority] || 0) - (priorityOrder[b.priority] || 0);
                case 'priority-desc':
                    const priorityOrderDesc = { 'high': 3, 'medium': 2, 'low': 1 };
                    return (priorityOrderDesc[b.priority] || 0) - (priorityOrderDesc[a.priority] || 0);
                default:
                    return 0;
            }
        });
    }

    function showAlert(message, type = 'info') {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
            type === 'error' ? 'bg-red-50 text-red-800' : 
            type === 'success' ? 'bg-green-50 text-green-800' : 
            'bg-blue-50 text-blue-800'
        }`;
        alert.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${
                    type === 'error' ? 'fa-exclamation-circle' : 
                    type === 'success' ? 'fa-check-circle' : 
                    'fa-info-circle'
                } mr-2"></i>
                <span>${message}</span>
                <button class="ml-4 text-gray-500 hover:text-gray-700" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Add to body
        document.body.appendChild(alert);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }

    // ========== Initialize Application ========== //
    async function initApp() {
    try {
        // Initialize database
        await initDB();
        
        // Verify database integrity
        await verifyDatabase();
        
        // Setup UI components
        setupEventListeners(); // This calls our updated function
        setupNotificationsDropdown();
        handleConnectionChange();
        
        // Initialize chart
        window.statusChart = null;
        updateStatusChart([]);
        
        // Load initial data
        await loadTasksTable();
        
        // Check for pending sync operations if online
        if (isOnline) {
            await syncPendingTasks();
        }
        
        showNotification('Task management loaded successfully', 'success');
        
        // Add event listener for beforeunload to handle page refresh
        window.addEventListener('beforeunload', () => {
            if (pendingSync.length > 0) {
                return "You have unsynced changes. Are you sure you want to leave?";
            }
        });
        
    } catch (error) {
        console.error('Initialization error:', error);
        showAlert('Failed to initialize task management. ' + error.message, 'error');
        
        // Attempt to recover by reloading
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    }
}

    // Start the application
    initApp();
});
    </script>
</body>
</html>