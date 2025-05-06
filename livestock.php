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


?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriVision Pro | Livestock Management</title>
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
                    <a href="livestock.php" class="flex items-center px-4 py-3 rounded-lg bg-blue-500 bg-opacity-30 text-white group">
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
                            <input id="search-input" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search animals..." type="search">
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
                        <h2 class="text-2xl font-bold text-gray-800">Livestock Management</h2>
                        <p class="text-sm text-gray-500">Track and manage all your farm animals in one place</p>
                    </div>
                    <button id="add-animal-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i> Add New Animal
                    </button>
                </div>
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                    <!-- Total Animals -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
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
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Healthy Animals -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
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
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pregnant Animals -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
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
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sick/Injured Animals -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
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
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="#" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Livestock Type Distribution Chart -->
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Livestock Distribution</h3>
                        <p class="mt-1 text-sm text-gray-500">Number of animals by type</p>
                    </div>
                    <div class="p-4">
                        <canvas id="typeChart" class="w-full h-64"></canvas>
                    </div>
                </div>
                
                <!-- Livestock Management -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <div class="relative w-64">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" id="search-animals" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search animals...">
                            </div>
                            
                            <select id="type-filter" title="Filter by type" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="all">All Types</option>
                                <option value="cattle">Cattle</option>
                                <option value="poultry">Poultry</option>
                                <option value="buffaloes">Buffaloes</option>
                                <option value="sheep">Sheep</option>
                                <option value="pigs">Pigs</option>
                                <option value="goats">Goats</option>
                                <option value="other">Other</option>
                            </select>
                            
                            <select id="status-filter" title="Filter by status" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="all">All Status</option>
                                <option value="healthy">Healthy</option>
                                <option value="pregnant">Pregnant</option>
                                <option value="sick">Sick</option>
                                <option value="injured">Injured</option>
                            </select>
                        </div>
                        
                        <div class="flex space-x-2">
                            <select id="sort-by" title="Sort animals by criteria" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="id-asc">ID (A-Z)</option>
                                <option value="id-desc">ID (Z-A)</option>
                                <option value="age-asc">Age (Youngest)</option>
                                <option value="age-desc">Age (Oldest)</option>
                                <option value="weight-asc">Weight (Lightest)</option>
                                <option value="weight-desc">Weight (Heaviest)</option>
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
                            <p class="mt-2 text-sm text-gray-500">Loading animals...</p>
                        </div>
                    </div>
                    
                    <!-- Table View -->
                    <div id="table-view" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID/TAG</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TYPE</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BREED</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AGE</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WEIGHT</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LOCATION</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="livestock-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Animals will be loaded here dynamically -->
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading animals...
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

    <!-- Add/Edit Animal Modal -->
    <div id="animal-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <!-- Fixed Header -->
            <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-200 sticky top-0 z-10">
                <h3 id="modal-title" class="text-2xl font-bold text-gray-800">Add New Animal</h3>
            </div>
                    <!-- Scrollable content area -->
                <div class="max-h-[calc(100vh-200px)] overflow-y-auto px-6 py-4">
                    <form id="animal-form">
                        <input type="hidden" id="animal-id">
                            <div class="grid grid-cols-1 gap-y-6 gap-x-6 sm:grid-cols-6">
                                <!-- Basic Info Section -->
                                <div class="sm:col-span-6 border-b border-gray-200 pb-4 mb-4">
                                    <h4 class="text-lg font-medium text-gray-800 mb-3">Basic Information</h4>
                                    <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                                        <div class="sm:col-span-3">
                                            <label for="animal-id-tag" class="block text-sm font-medium text-gray-700 flex items-center">
                                                ID/Tag <span class="text-red-500 ml-1">*</span>
                                            </label>
                                            <input type="text" id="animal-id-tag" required 
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        </div>
                                        
                                        <div class="sm:col-span-3">
                                            <label for="animal-type" class="block text-sm font-medium text-gray-700 flex items-center">
                                                Type <span class="text-red-500 ml-1">*</span>
                                            </label>
                                            <select id="animal-type" required 
                                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <option value="cattle">Cattle</option>
                                                <option value="poultry">Poultry</option>
                                                <option value="buffaloes">Buffaloes</option>
                                                <option value="sheep">Sheep</option>
                                                <option value="pigs">Pigs</option>
                                                <option value="goats">Goats</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        
                                        <div class="sm:col-span-6">
                                            <label for="animal-breed" class="block text-sm font-medium text-gray-700">Breed</label>
                                            <input type="text" id="animal-breed" 
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Age & Weight Section -->
                                <div class="sm:col-span-6 border-b border-gray-200 pb-4 mb-4">
                                    <h4 class="text-lg font-medium text-gray-800 mb-3">Age & Weight</h4>
                                    <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                                        <div class="sm:col-span-3">
                                            <label for="animal-birth-date" class="block text-sm font-medium text-gray-700">Birth Date</label>
                                            <input type="date" id="animal-birth-date" 
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        </div>
                                        
                                        <div class="sm:col-span-3">
                                            <label for="animal-age" class="block text-sm font-medium text-gray-700">Age</label>
                                            <div class="flex space-x-2">
                                                <input type="number" id="animal-age-years" placeholder="Years" min="0" 
                                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <input type="number" id="animal-age-months" placeholder="Months" min="0" max="11" 
                                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                        </div>
                                        
                                        <div class="sm:col-span-3">
                                            <label for="animal-weight" class="block text-sm font-medium text-gray-700">Weight</label>
                                            <div class="relative mt-1 rounded-md shadow-sm">
                                                <input type="number" step="0.1" min="0" id="animal-weight" 
                                                    class="block w-full pr-12 border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <div class="absolute inset-y-0 right-0 flex items-center">
                                                    <select id="weight-unit" title="Select weight unit" 
                                                            class="h-full rounded-r-md border-transparent bg-transparent py-0 pl-2 pr-7 text-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                        <option value="kg">kg</option>
                                                        <option value="lbs">lbs</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="sm:col-span-3">
                                            <label for="animal-status" class="block text-sm font-medium text-gray-700 flex items-center">
                                                Status <span class="text-red-500 ml-1">*</span>
                                            </label>
                                            <select id="animal-status" required 
                                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <option value="healthy">Healthy</option>
                                                <option value="pregnant">Pregnant</option>
                                                <option value="sick">Sick</option>
                                                <option value="injured">Injured</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Location & Notes Section -->
                                <div class="sm:col-span-6">
                                    <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                                        <div class="sm:col-span-6">
                                            <label for="animal-location" class="block text-sm font-medium text-gray-700">Location</label>
                                            <input type="text" id="animal-location" 
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        </div>
                                        
                                        <div class="sm:col-span-6">
                                            <label for="animal-notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                            <textarea id="animal-notes" rows="3" 
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                                        </div>
                                        
                                        <div class="sm:col-span-6">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Animal Image</label>
                                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md relative">
                                                <div id="image-upload-area" class="space-y-1 text-center">
                                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                    <div class="flex text-sm text-gray-600 justify-center">
                                                        <label for="animal-image" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                            <span>Upload a file</span>
                                                            <input id="animal-image" name="animal-image" type="file" class="sr-only" accept="image/*">
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
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- Fixed Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 sticky bottom-0">
                    <button type="button" id="save-animal" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Animal
                    </button>
                    <button type="button" id="cancel-animal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Animal Details Modal -->
    <div id="animal-details-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <!-- Fixed Header -->
                <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-200 sticky top-0 z-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 id="animal-details-title" class="text-2xl font-bold text-gray-800">Animal Details</h3>
                            <p id="animal-details-subtitle" class="mt-1 text-sm text-gray-500">Detailed information about this animal</p>
                        </div>
                        <button id="close-details" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Scrollable Content Area -->
                <div class="max-h-[calc(100vh-200px)] overflow-y-auto px-6 py-4">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        
                        <!-- Left Column (Image & Timeline) -->
                        <div class="sm:col-span-2">
                            <div class="h-64 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                                <img id="animal-details-image" src="https://source.unsplash.com/random/300x300/?livestock" alt="Animal image" class="w-full h-full object-cover">
                            </div>
                            
                            <!-- Timeline Section -->
                            <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Animal Timeline</h4>
                                <div class="space-y-4">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                            <i class="fas fa-birthday-cake text-sm"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Born</p>
                                            <p id="animal-details-birth-date" class="text-sm text-gray-500">-</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                            <i class="fas fa-calendar-check text-sm"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Age</p>
                                            <p id="animal-details-age" class="text-sm text-gray-500">-</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                                            <i class="fas fa-weight text-sm"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Weight</p>
                                            <p id="animal-details-weight" class="text-sm text-gray-500">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column (Details) -->
                        <div class="sm:col-span-4">
                            <!-- Basic Info Card -->
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Basic Information</h4>
                                <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">ID/Tag</label>
                                        <p id="animal-details-id-tag" class="mt-1 text-sm font-medium text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Type</label>
                                        <p id="animal-details-type" class="mt-1 text-sm text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Breed</label>
                                        <p id="animal-details-breed" class="mt-1 text-sm text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Location</label>
                                        <p id="animal-details-location" class="mt-1 text-sm text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Status</label>
                                        <p id="animal-details-status" class="mt-1 text-sm font-medium text-gray-900">-</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Notes Card -->
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Notes</h4>
                                <p id="animal-details-notes" class="text-sm text-gray-900">No notes available</p>
                            </div>
                        </div>
                    </div>   
                        <!-- Full Width Activities Section -->
                        
                            <div class="border-t border-gray-200 pt-4">
                                    <div class="px-5 pt-4 pb-4 bg-white sticky top-0 z-50 flex justify-center">
                                        <h4 class="text-2xl font-bold text-gray-800 mb-3">Recent Activities</h4>
                                    </div>
                                <div id="animal-activities" class="space-y-4">
                                    <div class="text-center py-4 text-sm text-gray-500 flex justify-center items-start">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading activities...
                                    </div>
                                </div>
                            </div>
                        
                    
                </div>
                
                <!-- Fixed Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 sticky bottom-0">
                    <button type="button" id="edit-animal-from-details" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-edit mr-2"></i> Edit Animal
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
        // Database configuration
        const DB_NAME = 'AgriVisionProDB';
        const DB_VERSION = 4; // Incremented for livestock
        const STORES = {
            LIVESTOCK: 'livestock',
            ACTIVITY: 'activity'
        };

        let db;
        let currentPage = 1;
        const itemsPerPage = 10;
        let currentView = 'table'; // 'table' or 'card'
        let currentAnimals = [];
        let currentSort = 'id-asc';
        let currentTypeFilter = 'all';
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
                await loadAnimals();
                await updateSummaryCards();
                initTypeChart();
                await updateTypeChart();
                
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
                    
                    // Create livestock object store if it doesn't exist
                    if (!db.objectStoreNames.contains(STORES.LIVESTOCK)) {
                        const livestockStore = db.createObjectStore(STORES.LIVESTOCK, { keyPath: 'id', autoIncrement: true });
                        livestockStore.createIndex('idTag', 'idTag', { unique: true });
                        livestockStore.createIndex('type', 'type', { unique: false });
                        livestockStore.createIndex('status', 'status', { unique: false });
                    }
                    
                    // Create activity store if it doesn't exist
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
                const transaction = db.transaction([STORES.LIVESTOCK, STORES.ACTIVITY], 'readwrite');
                const livestockStore = transaction.objectStore(STORES.LIVESTOCK);
                const activityStore = transaction.objectStore(STORES.ACTIVITY);
                
                // Sample animals
                const sampleAnimals = [
                    {
                        idTag: 'C-001',
                        type: 'cattle',
                        breed: 'Holstein',
                        ageYears: 3,
                        ageMonths: 0,
                        weight: 450,
                        weightUnit: 'kg',
                        location: 'Barn A',
                        status: 'healthy',
                        notes: 'Primary milk producer'
                    },
                    {
                        idTag: 'C-002',
                        type: 'cattle',
                        breed: 'Angus',
                        ageYears: 3,
                        ageMonths: 8,
                        weight: 520,
                        weightUnit: 'kg',
                        location: 'Barn A',
                        status: 'pregnant',
                        notes: 'Expected delivery in 2 months'
                    },
                    {
                        idTag: 'C-003',
                        type: 'cattle',
                        breed: 'Jersey',
                        ageYears: 2,
                        ageMonths: 10,
                        weight: 380,
                        weightUnit: 'kg',
                        location: 'Barn B',
                        status: 'sick',
                        notes: 'Under treatment for mastitis'
                    },
                    {
                        idTag: 'P-001',
                        type: 'poultry',
                        breed: 'Rhode Island Red',
                        ageYears: 1,
                        ageMonths: 2,
                        weight: 2.5,
                        weightUnit: 'kg',
                        location: 'Coop 1',
                        status: 'healthy',
                        notes: 'Good egg production'
                    },
                    {
                        idTag: 'G-001',
                        type: 'goats',
                        breed: 'Boer',
                        ageYears: 2,
                        ageMonths: 5,
                        weight: 65,
                        weightUnit: 'kg',
                        location: 'Pen 3',
                        status: 'healthy',
                        notes: 'Recently dewormed'
                    }
                ];
                
                // Add sample animals
                sampleAnimals.forEach((animal, index) => {
                    livestockStore.add(animal);
                    
                    // Add sample activity
                    activityStore.add({
                        type: 'animal_added',
                        description: `Added new animal: ${animal.idTag} (${animal.breed})`,
                        date: new Date().toISOString().split('T')[0],
                        user: 'System'
                    });
                    
                    if (animal.status === 'pregnant') {
                        activityStore.add({
                            type: 'animal_pregnant',
                            description: `Animal ${animal.idTag} is pregnant`,
                            date: new Date().toISOString().split('T')[0],
                            user: 'System'
                        });
                    }
                    
                    if (animal.status === 'sick') {
                        activityStore.add({
                            type: 'animal_sick',
                            description: `Animal ${animal.idTag} is sick and needs treatment`,
                            date: new Date().toISOString().split('T')[0],
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
            
            // Add animal button
            document.getElementById('add-animal-btn').addEventListener('click', () => {
                document.getElementById('modal-title').textContent = 'Add New Animal';
                document.getElementById('animal-id').value = '';
                document.getElementById('animal-form').reset();
                document.getElementById('animal-modal').classList.remove('hidden');
            });

            // Initialize modals
            document.getElementById('close-details').addEventListener('click', () => {
                document.getElementById('animal-details-modal').classList.add('hidden');
            });

            document.getElementById('close-details-btn').addEventListener('click', () => {
                document.getElementById('animal-details-modal').classList.add('hidden');
            });
            
            // Save animal button
            document.getElementById('save-animal').addEventListener('click', async () => {
                const animalData = {
                    idTag: document.getElementById('animal-id-tag').value,
                    type: document.getElementById('animal-type').value,
                    breed: document.getElementById('animal-breed').value,
                    ageYears: parseInt(document.getElementById('animal-age-years').value) || 0,
                    ageMonths: parseInt(document.getElementById('animal-age-months').value) || 0,
                    birthDate: document.getElementById('animal-birth-date').value || null,
                    weight: parseFloat(document.getElementById('animal-weight').value) || 0,
                    weightUnit: document.getElementById('weight-unit').value,
                    status: document.getElementById('animal-status').value,
                    location: document.getElementById('animal-location').value,
                    notes: document.getElementById('animal-notes').value,
                    image: null // This would be handled with file upload in a real app
                };
                
                const id = document.getElementById('animal-id').value;
                
                try {
                    if (id) {
                        // Update existing animal
                        await updateAnimal(parseInt(id), animalData);
                        
                        // Add activity log
                        await addActivity({
                            type: 'animal_updated',
                            description: `Updated animal: ${animalData.idTag}`,
                            date: new Date().toISOString().split('T')[0],
                            user: '<?= $current_user['name'] ?>'
                        });
                        
                        showSuccessToast('Animal updated successfully');
                    } else {
                        // Add new animal
                        const newId = await addAnimal(animalData);
                        
                        // Add activity log
                        await addActivity({
                            type: 'animal_added',
                            description: `Added new animal: ${animalData.idTag}`,
                            date: new Date().toISOString().split('T')[0],
                            user: '<?= $current_user['name'] ?>'
                        });
                        
                        showSuccessToast('Animal added successfully');
                    }
                    
                    document.getElementById('animal-modal').classList.add('hidden');
                    await loadAnimals(); // Refresh the table
                    await updateSummaryCards();
                    await updateTypeChart();
                } catch (error) {
                    console.error('Error saving animal:', error);
                    showErrorToast('Error saving animal. Please try again.');
                }
            });
            
            // Cancel animal button
            document.getElementById('cancel-animal').addEventListener('click', () => {
                document.getElementById('animal-modal').classList.add('hidden');
            });
            
            // Search animals
            document.getElementById('search-animals').addEventListener('input', async (e) => {
                currentSearchTerm = e.target.value;
                currentPage = 1;
                await loadAnimals();
            });
            
            // Filter by type
            document.getElementById('type-filter').addEventListener('change', async (e) => {
                currentTypeFilter = e.target.value;
                currentPage = 1;
                await loadAnimals();
            });
            
            // Filter by status
            document.getElementById('status-filter').addEventListener('change', async (e) => {
                currentStatusFilter = e.target.value;
                currentPage = 1;
                await loadAnimals();
            });
            
            // Sort animals
            document.getElementById('sort-by').addEventListener('change', async (e) => {
                currentSort = e.target.value;
                await loadAnimals();
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
                    await loadAnimals();
                }
            });
            
            document.getElementById('next-page').addEventListener('click', async (e) => {
                e.preventDefault();
                const totalPages = Math.ceil(currentAnimals.length / itemsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    await loadAnimals();
                }
            });
            
            // Edit from details
            document.getElementById('edit-animal-from-details').addEventListener('click', () => {
                const id = document.getElementById('animal-details-modal').getAttribute('data-animal-id');
                if (id) {
                    openEditModal(parseInt(id));
                }
                document.getElementById('animal-details-modal').classList.add('hidden');
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
                        const results = await searchAnimals(query);
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
        
        // Search animals
        async function searchAnimals(query) {
            const animals = await getAll(STORES.LIVESTOCK);
            const term = query.toLowerCase();
            
            return animals.filter(animal => 
                animal.idTag.toLowerCase().includes(term) || 
                (animal.breed && animal.breed.toLowerCase().includes(term)) ||
                (animal.location && animal.location.toLowerCase().includes(term))
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
            
            searchResults.innerHTML = results.slice(0, 5).map(animal => `
                <a href="#" class="search-item block hover:bg-gray-50 transition-colors duration-150" data-animal-id="${animal.id}">
                    <div class="flex items-center p-3">
                        <div class="flex-shrink-0 p-2 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas ${getAnimalIcon(animal.type)}"></i>
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">${animal.idTag}</p>
                            <p class="text-xs text-gray-500 truncate">${animal.breed || 'No breed'} • ${getStatusText(animal.status)}</p>
                        </div>
                    </div>
                </a>
            `).join('');
            
            searchResults.classList.remove('hidden');
            
            // Add click handlers to search results
            document.querySelectorAll('.search-item[data-animal-id]').forEach(item => {
                item.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const id = parseInt(item.getAttribute('data-animal-id'));
                    await showAnimalDetails(id);
                    searchResults.classList.add('hidden');
                    document.getElementById('search-input').value = '';
                });
            });
        }
        
        // Load animals into the table/cards
        async function loadAnimals() {
            try {
                let animals = await getAll(STORES.LIVESTOCK);
                currentAnimals = animals;
                
                // Apply search filter
                if (currentSearchTerm) {
                    const term = currentSearchTerm.toLowerCase();
                    animals = animals.filter(animal => 
                        animal.idTag.toLowerCase().includes(term) || 
                        (animal.breed && animal.breed.toLowerCase().includes(term)) ||
                        (animal.location && animal.location.toLowerCase().includes(term))
                    );
                }
                
                // Apply type filter
                if (currentTypeFilter !== 'all') {
                    animals = animals.filter(animal => animal.type === currentTypeFilter);
                }
                
                // Apply status filter
                if (currentStatusFilter !== 'all') {
                    animals = animals.filter(animal => animal.status === currentStatusFilter);
                }
                
                // Apply sorting
                animals.sort((a, b) => {
                    switch(currentSort) {
                        case 'id-asc':
                            return a.idTag.localeCompare(b.idTag);
                        case 'id-desc':
                            return b.idTag.localeCompare(a.idTag);
                        case 'age-asc':
                            return (a.ageYears * 12 + a.ageMonths) - (b.ageYears * 12 + b.ageMonths);
                        case 'age-desc':
                            return (b.ageYears * 12 + b.ageMonths) - (a.ageYears * 12 + a.ageMonths);
                        case 'weight-asc':
                            return (a.weight || 0) - (b.weight || 0);
                        case 'weight-desc':
                            return (b.weight || 0) - (a.weight || 0);
                        default:
                            return 0;
                    }
                });
                
                currentAnimals = animals;
                
                // Pagination
                const startIdx = (currentPage - 1) * itemsPerPage;
                const paginatedAnimals = animals.slice(startIdx, startIdx + itemsPerPage);
                
                // Update pagination info
                updatePagination(animals.length);
                
                // Render the appropriate view
                if (currentView === 'table') {
                    renderTableView(paginatedAnimals);
                } else {
                    renderCardView(paginatedAnimals);
                }
                
            } catch (error) {
                console.error('Error loading animals:', error);
                showErrorToast('Failed to load animals');
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
                        loadAnimals();
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
                        loadAnimals();
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
                        loadAnimals();
                    }
                });
                pageNumbers.appendChild(lastPage);
            }
        }
        
        // Render table view
        function renderTableView(animals) {
            const tableBody = document.getElementById('livestock-table-body');
            
            if (animals.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                            No animals found. <button id="add-animal-empty" class="text-blue-600 hover:text-blue-800">Add a new animal</button> to get started.
                        </td>
                    </tr>
                `;
                
                document.getElementById('add-animal-empty').addEventListener('click', () => {
                    document.getElementById('add-animal-btn').click();
                });
                
                return;
            }
            
            tableBody.innerHTML = animals.map(animal => `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${animal.idTag}</td>
                    <td class="px-6 py-4 whitespace-nowrap capitalize">${animal.type}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${animal.breed || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${formatAge(animal.ageYears, animal.ageMonths)}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${animal.weight ? `${animal.weight} ${animal.weightUnit || 'kg'}` : '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${animal.location || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(animal.status)}">
                            ${getStatusText(animal.status)}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button data-id="${animal.id}" class="view-animal text-blue-600 hover:text-blue-900 mr-3" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button data-id="${animal.id}" class="edit-animal text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button data-id="${animal.id}" class="delete-animal text-red-600 hover:text-red-900" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            
            // Add event listeners to action buttons
            addAnimalActionListeners();
        }
        
        // Render card view
        function renderCardView(animals = null) {
            const cardView = document.getElementById('card-view');
            
            if (!animals) {
                // If no animals provided, use the current paginated animals
                const startIdx = (currentPage - 1) * itemsPerPage;
                animals = currentAnimals.slice(startIdx, startIdx + itemsPerPage);
            }
            
            if (animals.length === 0) {
                cardView.innerHTML = `
                    <div class="sm:col-span-3 text-center py-10">
                        <i class="fas fa-cow text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500">No animals found.</p>
                        <button id="add-animal-empty-card" class="mt-3 text-blue-600 hover:text-blue-800">Add a new animal</button> to get started.
                    </div>
                `;
                
                document.getElementById('add-animal-empty-card').addEventListener('click', () => {
                    document.getElementById('add-animal-btn').click();
                });
                
                return;
            }
            
            cardView.innerHTML = animals.map(animal => `
                <div class="animal-card bg-white overflow-hidden shadow rounded-lg">
                    <div class="h-40 bg-gray-100 relative">
                        <img src="https://source.unsplash.com/random/300x200/?${encodeURIComponent(animal.type)}" alt="${animal.type}" class="w-full h-full object-cover">
                        <div class="absolute top-2 right-2">
                            <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full ${getStatusClass(animal.status)}">
                                ${getStatusText(animal.status)}
                            </span>
                        </div>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">${animal.idTag}</h3>
                            <span class="text-sm text-gray-500 capitalize">${animal.type}</span>
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            ${animal.breed || 'No breed'} • ${animal.location || 'No location'}
                        </div>
                        
                        <div class="mt-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500">Age</p>
                                    <p class="text-sm font-medium">${formatAge(animal.ageYears, animal.ageMonths)}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Weight</p>
                                    <p class="text-sm font-medium">${animal.weight ? `${animal.weight} ${animal.weightUnit || 'kg'}` : '-'}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex justify-between">
                            <button data-id="${animal.id}" class="view-animal inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-eye mr-1"></i> View
                            </button>
                            <div>
                                <button data-id="${animal.id}" class="edit-animal inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                                <button data-id="${animal.id}" class="delete-animal ml-2 inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <i class="fas fa-trash mr-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Add event listeners to action buttons
            addAnimalActionListeners();
        }
        
        // Add event listeners to animal action buttons
        function addAnimalActionListeners() {
            // View buttons
            document.querySelectorAll('.view-animal').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const id = parseInt(e.currentTarget.getAttribute('data-id'));
                    await showAnimalDetails(id);
                });
            });
            
            // Edit buttons
            document.querySelectorAll('.edit-animal').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const id = parseInt(e.currentTarget.getAttribute('data-id'));
                    openEditModal(id);
                });
            });
            
            // Delete buttons
            document.querySelectorAll('.delete-animal').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const id = parseInt(e.currentTarget.getAttribute('data-id'));
                    const animal = await getAnimal(id);
                    
                    if (animal && confirm(`Are you sure you want to delete ${animal.idTag}? This action cannot be undone.`)) {
                        try {
                            await deleteAnimal(id);
                            
                            // Add activity log
                            await addActivity({
                                type: 'animal_deleted',
                                description: `Deleted animal: ${animal.idTag}`,
                                date: new Date().toISOString().split('T')[0],
                                user: '<?= $current_user['name'] ?>'
                            });
                            
                            showSuccessToast('Animal deleted successfully');
                            await loadAnimals();
                            await updateSummaryCards();
                            await updateTypeChart();
                        } catch (error) {
                            console.error('Error deleting animal:', error);
                            showErrorToast('Failed to delete animal');
                        }
                    }
                });
            });
        }
        
        // Open edit modal with animal data
        async function openEditModal(id) {
            const animal = await getAnimal(id);
            
            if (animal) {
                document.getElementById('modal-title').textContent = 'Edit Animal';
                document.getElementById('animal-id').value = animal.id;
                document.getElementById('animal-id-tag').value = animal.idTag;
                document.getElementById('animal-type').value = animal.type || 'cattle';
                document.getElementById('animal-breed').value = animal.breed || '';
                document.getElementById('animal-age-years').value = animal.ageYears || '';
                document.getElementById('animal-age-months').value = animal.ageMonths || '';
                document.getElementById('animal-birth-date').value = animal.birthDate || '';
                document.getElementById('animal-weight').value = animal.weight || '';
                document.getElementById('weight-unit').value = animal.weightUnit || 'kg';
                document.getElementById('animal-status').value = animal.status || 'healthy';
                document.getElementById('animal-location').value = animal.location || '';
                document.getElementById('animal-notes').value = animal.notes || '';
                
                document.getElementById('animal-modal').classList.remove('hidden');
            }
        }
        
        // Show animal details
        async function showAnimalDetails(id) {
            try {
                const animal = await getAnimal(id);
                
                if (!animal) {
                    showErrorToast('Animal not found');
                    return;
                }

                // Set modal data
                document.getElementById('animal-details-modal').setAttribute('data-animal-id', animal.id);
                document.getElementById('animal-details-title').textContent = animal.idTag;
                document.getElementById('animal-details-subtitle').textContent = `${animal.breed ? animal.breed + ' • ' : ''}${animal.type ? animal.type.charAt(0).toUpperCase() + animal.type.slice(1) : ''}`;

                // Populate all the details
                document.getElementById('animal-details-id-tag').textContent = animal.idTag;
                document.getElementById('animal-details-type').textContent = animal.type ? animal.type.charAt(0).toUpperCase() + animal.type.slice(1) : '-';
                document.getElementById('animal-details-breed').textContent = animal.breed || '-';
                document.getElementById('animal-details-location').textContent = animal.location || '-';
                document.getElementById('animal-details-notes').textContent = animal.notes || 'No notes available';
                document.getElementById('animal-details-birth-date').textContent = animal.birthDate ? formatDate(animal.birthDate) : '-';
                document.getElementById('animal-details-age').textContent = formatAge(animal.ageYears, animal.ageMonths) || '-';
                document.getElementById('animal-details-weight').textContent = animal.weight ? `${animal.weight} ${animal.weightUnit || 'kg'}` : '-';

                // Set status with colored badge
                const statusElement = document.getElementById('animal-details-status');
                statusElement.textContent = getStatusText(animal.status);
                statusElement.className = 'mt-1 text-sm font-medium px-2 py-1 rounded-full inline-block ' + getStatusColorClass(animal.status);

                // Set image
                const imageUrl = animal.image || `https://source.unsplash.com/random/300x300/?${encodeURIComponent(animal.type)},livestock`;
                document.getElementById('animal-details-image').src = imageUrl;

                // Get and display activities
                const activities = await getAnimalActivities(animal.idTag);
                const activitiesContainer = document.getElementById('animal-activities');

                if (activities.length === 0) {
                    activitiesContainer.innerHTML = '<div class="text-center py-4 text-sm text-gray-500">No activities recorded for this animal</div>';
                } else {
                    activitiesContainer.innerHTML = activities.map(activity => `
                        <div class="activity-item flex items-start p-3 hover:bg-gray-50 rounded-lg transition-colors">
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
                document.getElementById('animal-details-modal').classList.remove('hidden');
            } catch (error) {
                console.error('Error showing animal details:', error);
                showErrorToast('Failed to load animal details');
            }
        }

        // Initialize type chart
        function initTypeChart() {
            const ctx = document.getElementById('typeChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
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
        
        // Update type chart with data
        async function updateTypeChart() {
            const animals = await getAll(STORES.LIVESTOCK);
            const ctx = document.getElementById('typeChart').getContext('2d');
            
            // Group by animal type
            const typeCounts = {};
            animals.forEach(animal => {
                if (!typeCounts[animal.type]) {
                    typeCounts[animal.type] = 0;
                }
                typeCounts[animal.type]++;
            });
            
            // Prepare chart data
            const labels = Object.keys(typeCounts).map(type => type.charAt(0).toUpperCase() + type.slice(1));
            const data = Object.values(typeCounts);
            
            // Update chart
            const chart = Chart.getChart(ctx);
            if (chart) {
                chart.data.labels = labels;
                chart.data.datasets[0].data = data;
                chart.update();
            }
        }
        
        // Update summary cards
        async function updateSummaryCards() {
            const animals = await getAll(STORES.LIVESTOCK);
            
            // Total animals
            document.getElementById('total-animals-count').textContent = animals.length;
            
            // Healthy animals
            const healthyAnimals = animals.filter(animal => animal.status === 'healthy');
            document.getElementById('healthy-animals-count').textContent = healthyAnimals.length;
            
            // Pregnant animals
            const pregnantAnimals = animals.filter(animal => animal.status === 'pregnant');
            document.getElementById('pregnant-animals-count').textContent = pregnantAnimals.length;
            
            // Sick/Injured animals
            const sickAnimals = animals.filter(animal => animal.status === 'sick' || animal.status === 'injured');
            document.getElementById('sick-animals-count').textContent = sickAnimals.length;
        }
        
        // Helper function to format age
        function formatAge(years, months) {
            let result = '';
            if (years > 0) result += `${years}y`;
            if (months > 0) {
                if (years > 0) result += ' ';
                result += `${months}m`;
            }
            return result || '-';
        }
        
        // Helper function to get animal icon
        function getAnimalIcon(type) {
            switch(type) {
                case 'cattle': return 'fa-cow';
                case 'poultry': return 'fa-kiwi-bird';
                case 'buffaloes': return 'fa-buffalo';
                case 'sheep': return 'fa-sheep';
                case 'pigs': return 'fa-pig';
                case 'goats': return 'fa-goat';
                default: return 'fa-paw';
            }
        }
        
        // Helper function to get status class
        function getStatusClass(status) {
            switch(status) {
                case 'healthy': return 'status-healthy';
                case 'pregnant': return 'status-pregnant';
                case 'sick': return 'status-sick';
                case 'injured': return 'status-injured';
                default: return 'bg-gray-100 text-gray-800';
            }
        }
        
        // Helper function to get status color class
        function getStatusColorClass(status) {
            switch(status) {
                case 'healthy': return 'text-green-800 bg-green-100';
                case 'pregnant': return 'text-purple-800 bg-purple-100';
                case 'sick': return 'text-red-800 bg-red-100';
                case 'injured': return 'text-orange-800 bg-orange-100';
                default: return 'text-gray-800 bg-gray-100';
            }
        }
        
        // Helper function to get status text
        function getStatusText(status) {
            switch(status) {
                case 'healthy': return 'Healthy';
                case 'pregnant': return 'Pregnant';
                case 'sick': return 'Sick';
                case 'injured': return 'Injured';
                default: return status;
            }
        }
        
        // Helper functions for activities
        function getActivityIcon(type) {
            switch(type) {
                case 'animal_added': return 'fa-plus';
                case 'animal_updated': return 'fa-edit';
                case 'animal_deleted': return 'fa-trash';
                case 'animal_pregnant': return 'fa-baby';
                case 'animal_sick': return 'fa-band-aid';
                case 'animal_healthy': return 'fa-heart';
                default: return 'fa-info-circle';
            }
        }

        function getActivityIconColor(type) {
            switch(type) {
                case 'animal_added': return 'bg-blue-500';
                case 'animal_updated': return 'bg-yellow-500';
                case 'animal_deleted': return 'bg-red-500';
                case 'animal_pregnant': return 'bg-purple-500';
                case 'animal_sick': return 'bg-orange-500';
                case 'animal_healthy': return 'bg-green-500';
                default: return 'bg-gray-500';
            }
        }
        
        // Helper function to format dates
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
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
        
        // Get activities for a specific animal
        async function getAnimalActivities(idTag) {
            const activities = await getAll(STORES.ACTIVITY);
            return activities.filter(activity => 
                activity.description.includes(idTag)
            ).sort((a, b) => new Date(b.date) - new Date(a.date));
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
        
        // CRUD operations for animals
        function addAnimal(animal) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.LIVESTOCK], 'readwrite');
                const store = transaction.objectStore(STORES.LIVESTOCK);
                
                const request = store.add(animal);
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = (event) => reject(event.target.error);
            });
        }
        
        function getAnimal(id) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.LIVESTOCK], 'readonly');
                const store = transaction.objectStore(STORES.LIVESTOCK);
                
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
        
        function updateAnimal(id, updates) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.LIVESTOCK], 'readwrite');
                const store = transaction.objectStore(STORES.LIVESTOCK);
                
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
        
        function deleteAnimal(id) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.LIVESTOCK], 'readwrite');
                const store = transaction.objectStore(STORES.LIVESTOCK);
                
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