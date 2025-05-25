<?php
session_start();
// Assuming db_connect.php and auth_functions.php are needed for session and user authentication
// and are located correctly relative to this file.
require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php';
checkAuthentication();

// Get user details from session and database
// This part remains as it handles user display
$current_user = [
    'name' => $_SESSION['full_name'] ?? 'Unknown User',
    'email' => $_SESSION['username'] ?? 'No email',
    'role' => $_SESSION['role'] ?? 'Unknown Role',
    'initials' => getInitials($_SESSION['full_name'] ?? 'UU'),
    'profile_picture' => ''
];

// Try to get profile data from database
try {
    // Using $pdo from db_connect.php
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
                    <a href="livestock.php" class="flex items-center px-3 py-2 rounded-lg bg-blue-500 bg-opacity-30 text-m text-white-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
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

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i class="fas fa-horse text-white text-xl"></i>
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

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <i class="fas fa-band-aid text-white text-xl"></i>
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

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                    <i class="fas fa-ambulance text-white text-xl"></i>
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

                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Livestock Distribution</h3>
                        <p class="mt-1 text-sm text-gray-500">Number of animals by type</p>
                    </div>
                    <div class="p-4">
                        <canvas id="typeChart" class="w-full h-64"></canvas>
                    </div>
                </div>


                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10 mb-6 overflow-x-auto">
                    <div class="flex items-center space-x-4 w-full sm:w-auto sm:space-x-4">
                        <div class="relative w-full sm:w-80">
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
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div id="card-view" class="hidden p-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="text-center py-10">
                            <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                            <p class="mt-2 text-sm text-gray-500">Loading animals...</p>
                        </div>
                    </div>

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
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading animals...
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


  <div id="animal-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <div class="bg-white px-6 pt-5 pb-4 border-b border-gray-200 sticky top-0 z-10">
                <h3 id="modal-title" class="text-2xl font-bold text-gray-800">Add New Animal</h3>
            </div>
                    <div class="max-h-[calc(100vh-200px)] overflow-y-auto px-6 py-4">
                    <form id="animal-form">
                        <input type="hidden" id="animal-id">
                        <input type="hidden" id="existing-animal-image">
                            <div class="grid grid-cols-1 gap-y-6 gap-x-6 sm:grid-cols-6">
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
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 sticky bottom-0">
                    <button type="submit" form="animal-form" id="save-animal" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Animal
                    </button>
                    <button type="button" id="cancel-animal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="animal-details-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
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

                <div class="max-h-[calc(100vh-200px)] overflow-y-auto px-6 py-4">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">

                        <div class="sm:col-span-2">
                            <div class="h-64 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                                <img id="details-animal-image" src="https://source.unsplash.com/random/300x300/?livestock" alt="Animal image" class="w-full h-full object-cover">
                            </div>

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
                                            <p id="details-age" class="text-sm text-gray-500">-</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                                            <i class="fas fa-weight text-sm"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Weight</p>
                                            <p id="details-weight" class="text-sm text-gray-500">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sm:col-span-4">
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Basic Information</h4>
                                <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">ID/Tag</label>
                                        <p id="details-id-tag" class="mt-1 text-sm font-medium text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Type</label>
                                        <p id="details-type" class="mt-1 text-sm text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Breed</label>
                                        <p id="details-breed" class="mt-1 text-sm text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Location</label>
                                        <p id="details-location" class="mt-1 text-sm text-gray-900">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Status</label>
                                        <p id="details-status" class="mt-1 text-sm font-medium text-gray-900">-</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-3">Notes</h4>
                                <p id="details-notes" class="text-sm text-gray-900">No notes available</p>
                            </div>
                        </div>
                    </div>
                        <div class="border-t border-gray-200 pt-4">
                                    <div class="px-5 pt-4 pb-4 bg-white sticky top-0 z-50 flex justify-center">
                                        <h4 class="text-2xl font-bold text-gray-800 mb-3">Recent Activities</h4>
                                    </div>
                                <div id="activities-list" class="space-y-4">
                                    <div class="text-center py-4 text-sm text-gray-500 flex justify-center items-start">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading activities...
                                    </div>
                                </div>
                            </div>
                </div>

                <div class="flex justify-between items-center px-4 py-3 border-t">
                 <button id="prev-animal-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                     <i class="fas fa-chevron-left mr-1"></i> Previous
                 </button>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 sticky bottom-0">
                    <button type="button" id="edit-details-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-edit mr-2"></i> Edit Animal
                    </button>
                    <button type="button" id="close-details-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-times mr-2"></i> Close
                    </button>
                </div>
                <button id="next-animal-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                     Next <i class="fas fa-chevron-right ml-1"></i>
                 </button>
                 </div>
            </div>
        </div>
    </div>

    <script>
    // =======================================================
    // Global Variables and Constants
    // =======================================================

    // API Endpoint for Livestock operations
    const LIVESTOCK_API_URL = 'livestock_api.php'; // Your PHP API endpoint

    let currentPage = 1;
    const itemsPerPage = 20; // Number of animals per page
    let currentView = 'table'; // 'table' or 'card' - default to table as per your HTML
    let currentAnimals = []; // To store filtered and sorted animals for pagination
    let currentSort = 'id-asc'; // Default sort
    let currentTypeFilter = 'all'; // Default type filter
    let currentStatusFilter = 'all'; // Default status filter
    let currentSearchTerm = ''; // Default search term

    // User ID from PHP Session - Make sure this is correctly passed from PHP
    // This is crucial for multi-user applications to fetch/save data for the correct user.
    const currentUserId = <?= $_SESSION['user_id'] ?? 'null' ?>; // PHP user_id session to JS variable

    let livestockTypeChart; // Variable to hold the Chart.js instance


    // =======================================================
    // Utility and Helper Functions
    // =======================================================

     // --- Global Variable to store the list of animals ---
    // Ensure this array is populated when you fetch/load animals (e.g., in your loadAnimals function)
    let allAnimalsArray = [];

    // --- Modify your loadAnimals (or fetch/render function) to populate allAnimalsArray ---
    async function loadAnimals() {
        console.log("Fetching all animals...");
        try {
             const response = await fetch(LIVESTOCK_API_URL, {
                 method: 'POST', // Assuming GET_ANIMALS action uses POST
                 headers: { 'Content-Type': 'application/json' }, // Assuming JSON for this action
                 body: JSON.stringify({ action: 'get_animals', userId: currentUserId }) // Assuming action and userId are needed
             });

             if (!response.ok) {
                  const errorResponse = await response.json();
                  throw new Error(`Server responded with status ${response.status}: ${errorResponse.message || 'Unknown error'}`);
             }

             const result = await response.json();

             // --- UPDATED: Check for result.livestock instead of result.animals ---
             if (result.success && Array.isArray(result.livestock)) { // Check for 'livestock' key and if it's an array
                  console.log("Animals fetched successfully:", result.livestock.length);
                  // --- Populate the global array with result.livestock ---
                  allAnimalsArray = result.livestock; // --- Corrected Assignment ---

                // --- මෙම Line එක අලුතින් එකතු කරන්න ---
                console.log("allAnimalsArray populated. Content:", allAnimalsArray);
                console.log("allAnimalsArray length:", allAnimalsArray.length);
                // --- End of new lines ---

                  // --- Render the list (Card/Table View) using allAnimalsArray ---
                  renderCardView(allAnimalsArray); // Your existing function
                  renderTableView(allAnimalsArray); // Your existing function

                  // ... other UI updates (e.g., update summary cards, charts) ...
                   updateSummaryCards();
                   updateTypeChart();


             } else {
                  // Handle cases where success is false or livestock is not an array
                  console.error("API returned success: false or 'livestock' is not a valid array:", result.message || result);
                  showErrorToast(result.message || 'Failed to fetch animals data structure.');
             }

        } catch (error) {
            console.error("Fetch error during load animals:", error);
            showErrorToast('Failed to load animal data: ' + error.message);
             // Optionally clear existing list on fetch error
             allAnimalsArray = [];
             renderCardView([]);
             renderTableView([]);
        }
    }

    // --- Ensure loadAnimals() is called when the page loads ---
    // e.g.,
    // document.addEventListener('DOMContentLoaded', () => {
    //     loadAnimals();
    //     // ... other initializations ...
    // });
    

    // --- Modify showAnimalDetails function to find the current animal's index and control buttons ---
    // showAnimalDetails function should ideally receive the animalId
    async function showAnimalDetails(animalId) {
        console.log('Attempting to show details for animal ID:', animalId);

        // --- Find the current animal's index in the global array ---
        const currentAnimalIndex = allAnimalsArray.findIndex(animal => animal.id == animalId); // Use == for comparison as ID might be number/string

        if (currentAnimalIndex === -1) {
             console.error("Animal with ID " + animalId + " not found in the current list.");
             showErrorToast("Selected animal details not found in the list.");
             // Optionally close the modal if it's open or prevent opening
              const animalDetailsModal = document.getElementById('animal-details-modal');
              if (animalDetailsModal) animalDetailsModal.classList.add('hidden');
             return; // Exit the function
        }

        // --- Fetch animal details from the server for the specific animal (This is good practice to get full details) ---
         // Although we have data in allAnimalsArray, fetching full details ensures activities etc. are current
         const animal = await fetchAnimalDetails(animalId); // Call the fetch function


        // Get modal elements - Check for existence
        const animalDetailsModal = document.getElementById('animal-details-modal');
        const detailsIdTag = document.getElementById('details-id-tag');
        const detailsType = document.getElementById('details-type');
        const detailsBreed = document.getElementById('details-breed');
        const detailsAge = document.getElementById('details-age');
        const detailsWeight = document.getElementById('details-weight');
        const detailsLocation = document.getElementById('details-location');
        const detailsStatus = document.getElementById('details-status');
        const detailsNotes = document.getElementById('details-notes');
        const activitiesList = document.getElementById('activities-list');
        const editDetailsBtn = document.getElementById('edit-details-btn');
        const detailsAnimalImage = document.getElementById('details-animal-image');
        const animalDetailsBirthDate = document.getElementById('animal-details-birth-date'); // Birth Date element

        // --- ADDED: Navigation Buttons ---
        const prevAnimalBtn = document.getElementById('prev-animal-btn');
        const nextAnimalBtn = document.getElementById('next-animal-btn');


        // Ensure essential elements are found before proceeding
         if (!animalDetailsModal || !detailsIdTag || !detailsType || !detailsBreed || !detailsAge || !detailsWeight || !detailsLocation || !detailsStatus || !detailsNotes || !activitiesList || !editDetailsBtn || !detailsAnimalImage || !animalDetailsBirthDate || !prevAnimalBtn || !nextAnimalBtn) {
              console.error("One or more essential elements for Animal Details Modal (including navigation buttons) not found.");
               showErrorToast("Modal elements Load කිරීමේදී දෝෂයක්.");
              // Ensure modal is hidden if elements are missing
               if (animalDetailsModal) animalDetailsModal.classList.add('hidden');
              return; // Stop execution if essential elements are missing
         }


        if (animal) {
            // Populate Animal Details Modal with data received
            detailsIdTag.textContent = animal.idTag || 'N/A';
            detailsType.textContent = animal.type || '-';
            detailsBreed.textContent = animal.breed || 'N/A';
            detailsAge.textContent = formatAge(animal.ageYears, animal.ageMonths);
            detailsWeight.textContent = animal.weight ? `${animal.weight} ${animal.weightUnit || 'kg'}` : '-';
            detailsLocation.textContent = animal.location || 'N/A';
            detailsStatus.textContent = getStatusText(animal.status); // Assuming you have getStatusText
            detailsNotes.textContent = animal.notes || 'No notes';

             // Populate Birth Date
             if (animal.birthDate && animal.birthDate !== '0000-00-00') {
                  animalDetailsBirthDate.textContent = animal.birthDate; // Or use formatDate(animal.birthDate)
             } else {
                  animalDetailsBirthDate.textContent = '-';
             }


             // Set the Edit button's data-id to the server ID
             editDetailsBtn.dataset.id = animal.id;


             // Handle Image Display
             detailsAnimalImage.src = animal.image_url || `https://source.unsplash.com/random/600x400/?${encodeURIComponent(animal.type || 'animal')}`;
             detailsAnimalImage.alt = animal.type || 'Animal Image';


            // Populate Activities (as done previously)
            if (animal.activities && animal.activities.length > 0) {
                 activitiesList.innerHTML = animal.activities.map(activity => `
                     <li class="py-3 sm:py-4">
                         <div class="flex items-center space-x-4">
                              <div class="flex-shrink-0">
                                 <div class="p-2 rounded-full ${getActivityIconColor(activity.type)}">
                                      <i class="${getActivityIcon(activity.type)} text-lg"></i>
                                 </div>
                             </div>
                             <div class="flex-1 min-w-0">
                                 <p class="text-sm font-medium text-gray-900 truncate capitalize">
                                     ${activity.type || 'Activity'}
                                 </p>
                                 <p class="text-sm text-gray-500 truncate">
                                     ${activity.notes || 'No notes'}
                                 </p>
                             </div>
                             <div class="inline-flex items-center text-base font-semibold text-gray-900">
                                 ${activity.timestamp ? formatDateTime(activity.timestamp) : '-'}
                             </div>
                         </div>
                     </li>
                 `).join('');
            } else {
                 activitiesList.innerHTML = '<li class="py-3 sm:py-4 text-center text-gray-500">No recent activities recorded.</li>';
            }

            // --- ADDED: Control Previous/Next button states ---
            // Disable Previous button if it's the first animal in the list
            prevAnimalBtn.disabled = currentAnimalIndex === 0;
            // Disable Next button if it's the last animal in the list
            nextAnimalBtn.disabled = currentAnimalIndex === allAnimalsArray.length - 1;

             // Store current index on the modal or buttons (optional, but can be helpful)
             // animalDetailsModal.dataset.currentIndex = currentAnimalIndex;
             // prevAnimalBtn.dataset.currentIndex = currentAnimalIndex;
             // nextAnimalBtn.dataset.currentIndex = currentAnimalIndex;


            // Show the modal
            animalDetailsModal.classList.remove('hidden');


        } else {
            // Handle case where animal is not found by fetchAnimalDetails
            console.log('Animal data not found by fetchAnimalDetails for ID:', animalId);
            // fetchAnimalDetails should handle showing an error toast
             // Ensure the modal is hidden if data fetch failed
              const animalDetailsModal = document.getElementById('animal-details-modal');
              if (animalDetailsModal) {
                  animalDetailsModal.classList.add('hidden');
              }
        }
    }


    // --- ADDED: Event Listeners for Previous and Next Buttons ---
    // Get references to the buttons after the DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        const prevAnimalBtn = document.getElementById('prev-animal-btn');
        const nextAnimalBtn = document.getElementById('next-animal-btn');

        if (prevAnimalBtn && nextAnimalBtn) {

            // Add listener for Previous button
            // Add listener for Previous button
    prevAnimalBtn.addEventListener('click', () => {
         const editDetailsBtn = document.getElementById('edit-details-btn');
         const currentAnimalId = editDetailsBtn ? editDetailsBtn.dataset.id : null;

         // --- UPDATED Condition: Remove && allAnimalsArray.length > 0 ---
         if (currentAnimalId) { // Only check if currentAnimalId is available
             // Find the index of the current animal by its ID
             const currentIndex = allAnimalsArray.findIndex(animal => animal.id === parseInt(currentAnimalId)); // Use == for safety

             // Check if index is valid and not the first animal
             if (currentIndex > 0) { // If currentIndex is 0, it's the first. If -1, not found.
                 const prevAnimal = allAnimalsArray[currentIndex - 1];
                 console.log("Navigating to previous animal ID:", prevAnimal.id);
                 showAnimalDetails(prevAnimal.id); // Call function to show details
             } else {
                 console.log("Already at the first animal or animal not found in list.");
                 // Button should be disabled by showAnimalDetails, this is a fallback log
             }
         } else {
              console.warn("Could not determine current animal ID from edit button for Previous navigation.");
              // This warning might indicate Edit button data-id is not set or Edit button not found
         }
    });

    // Add listener for Next button
    nextAnimalBtn.addEventListener('click', () => {
         const editDetailsBtn = document.getElementById('edit-details-btn');
         const currentAnimalId = editDetailsBtn ? editDetailsBtn.dataset.id : null;

         // --- UPDATED Condition: Remove && allAnimalsArray.length > 0 ---
         if (currentAnimalId) { // Only check if currentAnimalId is available
             // Find the index of the current animal by its ID
             const currentIndex = allAnimalsArray.findIndex(animal => animal.id === parseInt(currentAnimalId)); // Use == for safety

             // Check if index is valid and not the last animal
             // Note: allAnimalsArray.length - 1 is the index of the last item
             if (currentIndex !== -1 && currentIndex < allAnimalsArray.length - 1) { // Check if not -1 (not found) and not the last index
                 const nextAnimal = allAnimalsArray[currentIndex + 1];
                 console.log("Navigating to next animal ID:", nextAnimal.id);
                 showAnimalDetails(nextAnimal.id); // Call function to show details
             } else {
                 console.log("Already at the last animal or animal not found in list.");
                 // Button should be disabled by showAnimalDetails, this is a fallback log
             }
         } else {
             console.warn("Could not determine current animal ID from edit button for Next navigation.");
             // This warning might indicate Edit button data-id is not set or Edit button not found
         }
    });

}
}); // End DOMContentLoaded listener



    // --- Helper function to calculate age from birth date and set age fields ---
        // --- Helper function to calculate age from birth date and set age fields ---
function calculateAndSetAge(birthDateString) {
     const ageYearsInput = document.getElementById('animal-age-years');
     const ageMonthsInput = document.getElementById('animal-age-months');
     const birthDateInput = document.getElementById('animal-birth-date'); // Get the birth date input itself

     if (!ageYearsInput || !ageMonthsInput || !birthDateInput) {
          console.error("Age or Birth Date input fields not found for calculation.");
          return;
     }

     // Always clear and enable age fields first when the birth date changes or is cleared
     ageYearsInput.value = '';
     ageMonthsInput.value = '';
     ageYearsInput.disabled = false;
     ageMonthsInput.disabled = false;
     ageYearsInput.classList.remove('bg-gray-200', 'cursor-not-allowed'); // Remove styling
     ageMonthsInput.classList.remove('bg-gray-200', 'cursor-not-allowed'); // Remove styling


     if (!birthDateString) {
          // If birth date is empty, just return after enabling age fields
          return;
     }

     try {
          const birthDate = new Date(birthDateString);
          const today = new Date();

          // Set time to midnight for accurate date comparison
          birthDate.setHours(0, 0, 0, 0);
          today.setHours(0, 0, 0, 0);


          if (isNaN(birthDate.getTime())) { // Check for invalid date
               console.warn("Invalid birth date provided for age calculation.");
               // Age fields are already cleared and enabled above
               return; // Stop here if date is invalid
          }

          // Check if the birth date is in the future
          if (birthDate > today) {
               console.warn("Birth date is in the future. Age cannot be calculated.");
                // Age fields are already cleared and enabled above
               return; // Stop here if date is in the future
          }


          // Calculate age more accurately
          let years = today.getFullYear() - birthDate.getFullYear();
          let months = today.getMonth() - birthDate.getMonth();
          let days = today.getDate() - birthDate.getDate();

          // Adjust age based on month and day
          // If today's month is before birth month, or it's the same month but today's day is before birth day
          if (months < 0 || (months === 0 && days < 0)) {
              years--;
              months = (months + 12) % 12; // Correct month calculation (adds 12 if negative)
          }

           // If after adjusting months, days are still negative, it means we haven't completed the current month
           // Example: Birth 2023-01-31, Today 2025-02-28.
           // Years = 2, Months = 1, Days = -3
           // months < 0 is false. (months === 0 && days < 0) is false. No adjustment here based on the above logic.
           // The above logic is primarily for handling the month/year boundary.

           // Let's refine month calculation considering days
           const birthMonthDay = birthDate.getMonth() * 100 + birthDate.getDate();
           const todayMonthDay = today.getMonth() * 100 + today.getDate();

           let calculatedYears = today.getFullYear() - birthDate.getFullYear();
           let calculatedMonths = today.getMonth() - birthDate.getMonth();

            // If the current month/day is before the birth month/day, decrement year and adjust months
            if (todayMonthDay < birthMonthDay) {
                calculatedYears--;
                calculatedMonths = 12 - (birthDate.getMonth() - today.getMonth());
                 // Example: Birth 06/15, Today 05/10 -> Year--, Months = 12 - (5-4) = 11 (Incorrect)
                 // Correct: Months = (12 - birthDate.getMonth()) + today.getMonth()  -- This is complex

                 // A simpler approach: Calculate total months and convert back to years and months
                 let totalMonths = (today.getFullYear() - birthDate.getFullYear()) * 12;
                 totalMonths += today.getMonth() - birthDate.getMonth();

                  // Adjust total months if today's day is before birth day
                  if (today.getDate() < birthDate.getDate()) {
                      totalMonths--;
                  }

                  calculatedYears = Math.floor(totalMonths / 12);
                  calculatedMonths = totalMonths % 12;

                   // Ensure non-negative result
                   if (calculatedYears < 0 || (calculatedYears === 0 && calculatedMonths < 0)) {
                        calculatedYears = 0;
                        calculatedMonths = 0;
                         console.warn("Calculated age is negative or zero. Birth date might be in the future.");
                   }


                   years = calculatedYears;
                   months = calculatedMonths;
            } else {
                 // If current month/day is after or same as birth month/day, use initial year and month difference
                  // Month adjustment based on days might still be needed if it's the same month but earlier day
                   if (months === 0 && days < 0) {
                       // If it's the same month but earlier day, the age in months is effectively 11 from the previous year
                       // This case is handled by the initial `if (months < 0 || (months === 0 && days < 0))` check now.
                        // Let's re-evaluate the initial simple logic which seems more robust.
                   }

                   // Revert to the simpler, standard age calculation logic which is generally correct:
                   years = today.getFullYear() - birthDate.getFullYear();
                   months = today.getMonth() - birthDate.getMonth();
                   days = today.getDate() - birthDate.getDate();

                   if (months < 0 || (months === 0 && days < 0)) {
                       years--;
                       months = (months + 12) % 12;
                   }
                    // If the birth date is today or later in the month, months might be 0.
                    // If birth date is later in the current month, days will be negative, which is handled above.
                    // If birth date is today, years and months should be 0.
                    if (years < 0) { // Should not happen if birthDate <= today
                         years = 0;
                         months = 0;
                    } else if (years === 0 && months < 0) { // Should be handled by the month adjustment above
                         years = 0;
                         months = 0;
                    }


            }

            // Final check for negative age (should ideally be caught by the birthDate > today check)
             if (years < 0 || (years === 0 && months < 0)) {
                  years = 0;
                  months = 0;
                   console.warn("Calculated age resulted in a negative value. Resetting to 0.");
             }


          // Set the calculated values to the age inputs
          ageYearsInput.value = years;
          ageMonthsInput.value = months;

          // Disable age fields after successful calculation
          ageYearsInput.disabled = true;
          ageMonthsInput.disabled = true;
           ageYearsInput.classList.add('bg-gray-200', 'cursor-not-allowed'); // Add styling
           ageMonthsInput.classList.add('bg-gray-200', 'cursor-not-allowed'); // Add styling


     } catch (e) {
          console.error("Error calculating age from birth date:", e);
           // On unexpected error, clear age fields and enable them
           ageYearsInput.value = '';
           ageMonthsInput.value = '';
           ageYearsInput.disabled = false;
           ageMonthsInput.disabled = false;
           ageYearsInput.classList.remove('bg-gray-200', 'cursor-not-allowed');
           ageMonthsInput.classList.remove('bg-gray-200', 'cursor-not-allowed');
     }
}

    // Helper function to format age from years and months
    function formatAge(years, months) {
        if (years === undefined && months === undefined) return '-';
        years = parseInt(years) || 0;
        months = parseInt(months) || 0;
        if (years === 0 && months === 0) return '-';
        if (years > 0 && months > 0) return `${years} yrs ${months} mos`;
        if (years > 0) return `${years} yrs`;
        if (months > 0) return `${months} mos`;
        return '-';
    }

    // Helper function to get status text
    function getStatusText(status) {
        switch (status) {
            case 'healthy':
                return 'Healthy';
            case 'pregnant':
                return 'Pregnant';
            case 'sick':
                return 'Sick';
            case 'injured':
                return 'Injured';
            default:
                return 'Unknown';
        }
    }

    // Helper function to get status CSS class
    function getStatusClass(status) {
        switch (status) {
            case 'healthy':
                return 'status-healthy';
            case 'pregnant':
                return 'status-pregnant';
            case 'sick':
                return 'status-sick';
            case 'injured':
                return 'status-injured';
            default:
                return 'bg-gray-100 text-gray-800'; // Default gray badge
        }
    }

    // Helper function to get animal icon (optional, based on your design)
    function getAnimalIcon(type) {
        switch (type) {
            case 'cattle':
                return 'fas fa-cow';
            case 'poultry':
                return 'fas fa-egg'; // or fa-chicken
            case 'buffaloes':
                 return 'fas fa-paw'; // Or a buffalo specific icon if available
            case 'sheep':
                 return 'fas fa-sheep';
            case 'pigs':
                 return 'fas fa-piggy-bank'; // Or a pig specific icon
            case 'goats':
                 return 'fas fa-horse'; // Or a goat specific icon
            default:
                return 'fas fa-paw'; // Generic animal icon
        }
    }

     // Helper function to get activity icon (optional, based on your design)
     function getActivityIcon(type) {
         switch (type.toLowerCase()) { // Use toLowerCase for case-insensitive matching
              case 'feeding': return 'fas fa-utensils';
              case 'health check': return 'fas fa-stethoscope';
              case 'vaccination': return 'fas fa-syringe';
              case 'breeding': return 'fas fa-heart';
              case 'birth': return 'fas fa-baby';
              case 'weight recorded': return 'fas fa-weight';
              default: return 'fas fa-list-alt';
         }
     }

      // Helper function to get activity icon color (optional, based on your design)
      function getActivityIconColor(type) {
          switch (type.toLowerCase()) { // Use toLowerCase for case-insensitive matching
               case 'feeding': return 'text-yellow-600 bg-yellow-100';
               case 'health check': return 'text-blue-600 bg-blue-100';
               case 'vaccination': return 'text-purple-600 bg-purple-100';
               case 'breeding': return 'text-pink-600 bg-pink-100';
               case 'birth': return 'text-green-600 bg-green-100';
               case 'weight recorded': return 'text-indigo-600 bg-indigo-100';
               default: return 'text-gray-600 bg-gray-100';
          }
      }


    // Helper function to format date (e.g., YYYY-MM-DD)
    function formatDate(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            if (isNaN(date)) return '-'; // Invalid date
            const year = date.getFullYear();
            const month = ('0' + (date.getMonth() + 1)).slice(-2);
            const day = ('0' + date.getDate()).slice(-2);
            return `${year}-${month}-${day}`;
        } catch (e) {
            console.error("Error formatting date:", e);
            return '-';
        }
    }

     // Helper function to format date and time (e.g., YYYY-MM-DD HH:MM)
     function formatDateTime(dateString) {
         if (!dateString) return '-';
         try {
             const date = new Date(dateString);
              if (isNaN(date)) return '-'; // Invalid date
             const year = date.getFullYear();
             const month = ('0' + (date.getMonth() + 1)).slice(-2);
             const day = ('0' + date.getDate()).slice(-2);
             const hours = ('0' + date.getHours()).slice(-2);
             const minutes = ('0' + date.getMinutes()).slice(-2);
             return `${year}-${month}-${day} ${hours}:${minutes}`;
         } catch (e) {
             console.error("Error formatting datetime:", e);
             return '-';
         }
     }


    // Show success toast notification
    function showSuccessToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg flex items-center z-50'; // Add z-index
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

    // Show error toast notification
    function showErrorToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg flex items-center z-60'; // Add z-index
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

    // Show general alert notification (used instead of showNotification)
    function showAlert(message, type = 'info') {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
            type === 'error' ? 'bg-red-100 text-red-800' :
            type === 'success' ? 'bg-green-100 text-green-800' :
            type === 'warning' ? 'bg-yellow-100 text-yellow-800' :
            'bg-blue-100 text-blue-800' // info
        }`;
        alert.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${
                    type === 'error' ? 'fa-exclamation-circle' :
                    type === 'success' ? 'fa-check-circle' :
                    type === 'warning' ? 'fa-exclamation-triangle' :
                    'fa-info-circle'
                } mr-2"></i>
                <span>${message}</span>
                <button class="ml-4 text-gray-500 hover:text-gray-700 focus:outline-none" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        // Add to body
        document.body.appendChild(alert);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    }


    // =======================================================
    // Data Fetching and Saving (Direct to API)
    // =======================================================

    // Fetch livestock data from the server API
    async function fetchLivestock(filters = {}) {
        console.log('Fetching livestock data from server...');
        try {
            const response = await fetch(LIVESTOCK_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'get_livestock', // New action for PHP API
                    userId: currentUserId,
                    ...filters // Pass filters to the API
                })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Server returned status ${response.status}: ${errorText}`);
            }

            const result = await response.json();

            if (result.success) {
                console.log('Livestock data fetched successfully:', result.livestock);
                return result.livestock || [];
            } else {
                console.error('Failed to fetch livestock data:', result.message);
                showErrorToast('Animal records Server එකෙන් ලබාගැනීමේදී දෝෂයක්: ' + (result.message || 'Unknown error'));
                return []; // Return empty array on failure
            }

        } catch (error) {
            console.error('Fetch error:', error);
            showErrorToast('Server වෙත සම්බන්ධ වීමේදී දෝෂයක්.');
            return []; // Return empty array on network error
        }
    }

     // Fetch details for a single animal from the server API (including activities)
    async function fetchAnimalDetails(animalId) {
        console.log('Fetching animal details from server for ID:', animalId);
        try {
            const response = await fetch(LIVESTOCK_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' // Helps server detect AJAX
                },
                body: JSON.stringify({
                    action: 'get_animal_details', // Action to fetch animal details and activities
                    animalId: animalId, // Pass the server ID of the animal
                    userId: currentUserId // Ensure user ID is passed for authorization
                })
            });

            if (!response.ok) {
                // If HTTP status is not OK (e.g., 400, 404, 500)
                const errorText = await response.text();
                 try {
                      const errorJson = JSON.parse(errorText);
                       // Check for specific known database errors from PHP API
                      if (errorJson.message && (
                          errorJson.message.includes("Unknown column 'animal_id'") ||
                          errorJson.message.includes("Base table or view not found") ||
                          errorJson.message.includes("doesn't exist") // More general check for table not found
                      )) {
                          console.warn("Database configuration error detected:", errorJson.message);
                          // Throw a specific error that showAnimalDetails can handle
                          throw new Error("Database Configuration Error: Activities table or 'animal_id' column missing or incorrect. කරුණාකර database tables නිවැරදිව සකස් කරන්න.");
                      }
                       // Throw a generic error if it's not a specific known DB config error
                      throw new Error(`Server returned status ${response.status}: ${errorJson.message || errorText}`);
                 } catch (jsonError) {
                       // If JSON parsing failed, use the raw error text as the error message
                       throw new Error(`Server returned status ${response.status}: ${errorText}`);
                 }
            }

            // If response status is OK, parse the JSON body
            const result = await response.json();

            if (result.success) {
                console.log('Animal details fetched successfully:', result.animal);
                // Return the animal object, which should contain the 'activities' key (empty array if none found)
                return result.animal || null;
            } else {
                console.error('Failed to fetch animal details:', result.message);
                // Return null if the API returned success: false
                return null;
            }

        } catch (error) {
            // Handle network errors (Failed to fetch) or other unexpected errors
            console.error('Fetch error during animal details:', error);

             // Provide user-friendly messages for common errors
            if (error.message.includes("Failed to fetch")) {
                 showErrorToast("Server වෙත සම්බන්ධ වීමේදී දෝෂයක්. Internet Connection පරීක්ෂා කරන්න.");
            } else if (error.message.includes("Database Configuration Error")) {
                 // This specific message comes from the catch block above
                 showErrorToast(error.message); // Show the specific DB config error message
            }
            else {
                 // Show a general error for other fetch issues
                 showErrorToast('Server වෙතින් දත්ත ලබාගැනීමේදී දෝෂයක්: ' + error.message);
            }

            return null; // Return null on any fetch error
        }
    }

    

    // Add Animal Form එක reset කරන Function එක (අවශ්‍ය නම් Add Modal එක වසා දමයි)
function resetAddForm() {
    console.log("Resetting Add Animal Form");
    const addAnimalForm = document.getElementById('add-animal-form'); // ඔබේ Add Form එකේ ID එක
    if (addAnimalForm) {
        addAnimalForm.reset(); // Form එකේ සියලු input fields හිස් කරයි
        // Form එකේ File input හෝ වෙනත් custom fields ඇත්නම් ඒවාද මෙහිදී manually reset කළ යුතුය
        const imageInput = document.getElementById('animal-image'); // ඔබේ Image Input එකේ ID එක
        if (imageInput) {
             imageInput.value = ''; // Clear the file input
        }
        // Image preview එකක් ඇත්නම් එයද clear කරන්න
         const imagePreview = document.getElementById('add-image-preview'); // ඔබේ Image Preview Element එකේ ID එක
         if(imagePreview) imagePreview.src = ''; // හෝ default image එකට මාරු කරන්න
    } else {
         console.warn("Add Animal Form element not found for reset.");
    }
}

// Add Animal Modal එක වසා දමන Function එක (අවශ්‍ය නම්)
 function closeAddModal() {
     console.log("Closing Add Animal Modal");
     const addModal = document.getElementById('add-animal-modal'); // ඔබේ Add Modal එකේ ID එක
     if (addModal) {
         addModal.classList.add('hidden'); // Modal එක Hide කිරීමට Tailwind 'hidden' class එක භාවිත කරයි
     } else {
          console.warn("Add Animal Modal element not found for closing.");
     }
 }

 // ඔබේ saveAnimal function එක තුළ සාර්ථකව add වූ පසු මේවා Call වේ:
 /*
 if (result.success) {
     // ... show success toast ...
     // ... load data ...
     resetAddForm(); // Call the reset function
     // closeAddModal(); // Call the close modal function if needed separately
     return true;
 }
 */
// Edit Animal Modal එක වසා දමන Function එක
function closeEditModal() {
    console.log("Closing Edit Animal Modal");
    const editModal = document.getElementById('edit-animal-modal'); // ඔබේ Edit Modal එකේ ID එක
    if (editModal) {
        editModal.classList.add('hidden'); // Modal එක Hide කිරීමට Tailwind 'hidden' class එක භාවිත කරයි
        // Optional: Clear the form or reset its state if needed
        // document.getElementById('edit-animal-form').reset();
    } else {
         console.warn("Edit Animal Modal element not found for closing.");
    }
}

 // ඔබේ saveAnimal function එක තුළ සාර්ථක හෝ අසාර්ථක update පසු මේවා Call වේ:
 /*
 if (result.success) {
      // ... show success toast ...
      // ... load data ...
      closeEditModal(); // Call the close modal function
      return true;
 } else {
     // ... show error toast ...
     // closeEditModal(); // Optionally close modal even on failure
     return false;
 }
 */


    // Save (Add or Update) animal data via the server API
    async function saveAnimal(animalData, isEditing) {
        console.log(`${isEditing ? 'Updating' : 'Adding'} animal via server API:`, animalData);
        // Determine the action based on whether we are editing or adding
        // Note: Ensure your PHP API accepts 'add_livestock' and 'update_livestock' actions
        const action = isEditing ? 'update_livestock' : 'add_livestock';

        // Use FormData for mixed text and file data (like image upload)
        const formData = new FormData();

        // Append the action and user ID
        formData.append('action', action);
        formData.append('userId', currentUserId); // Ensure currentUserId is available globally or passed


        // Append animal data fields to FormData
        // Iterate over the animalData object and append each key-value pair
        for (const key in animalData) {
             // Check if the key is a direct property of animalData (not inherited)
             // and exclude keys that are not actual form fields or will be handled separately
             if (animalData.hasOwnProperty(key) && key !== 'image' && key !== 'activities' && key !== 'imageData' && key !== 'existing_image_url') { // Exclude image/activity related keys
                 // Append the value. Convert null/undefined to empty string if necessary for PHP
                 formData.append(key, animalData[key] ?? '');
             }
        }

        // Append the selected image file if it exists in the input
        // Assuming your image input field has the ID 'animal-image'
        const imageInput = document.getElementById('animal-image');
         if (imageInput && imageInput.files && imageInput.files[0]) {
             // Append the file object with a name that your PHP script expects (e.g., 'animalImage')
             formData.append('animalImage', imageInput.files[0]);
             console.log("Image file appended to FormData:", imageInput.files[0].name);
         }
         // Handle case where image should be removed (e.g., a checkbox or button)
         // You might need additional logic to detect if the user intended to remove the image
         // For simplicity, let's assume if existing_image_url is sent as empty string or a flag is set
         // This depends on your form's implementation for image removal


        // Include existing image URL if editing and no new image is selected, OR if image is being removed
         // Assuming you have a way in your form/JS to track the existing image URL and if it's being removed
         // If you have a hidden input for the existing URL with ID 'existing-animal-image'
         const existingImageInput = document.getElementById('existing-animal-image');
         // And possibly a flag or check if the user cleared the image input
         const isImageCleared = (imageInput && !imageInput.files) || (imageInput && imageInput.files && imageInput.files.length === 0);


         if (isEditing) {
             if (isImageCleared && existingImageInput && existingImageInput.value) {
                  // If editing, image was cleared, and there was an existing image
                  formData.append('remove_existing_image', 'true'); // Tell PHP to remove the image
                  console.log("Signal to remove existing image appended.");
             } else if (existingImageInput && existingImageInput.value && (!imageInput || !imageInput.files || imageInput.files.length === 0)) {
                 // If editing, no new image selected, and there was an existing image, send its URL
                 // PHP will use this to know not to delete the old image unless remove_existing_image is true
                 formData.append('existing_image_url', existingImageInput.value);
                 console.log("Existing image URL appended to FormData:", existingImageInput.value);
             }
         }


        try {
            const response = await fetch(LIVESTOCK_API_URL, {
                method: 'POST', // Method should be POST
                // --- REMOVED: Content-Type header for FormData ---
                // headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, // Remove Content-Type for FormData
                 headers: {
                     // 'Content-Type' is automatically set by FormData to multipart/form-data
                     'X-Requested-With': 'XMLHttpRequest' // Still good practice for AJAX
                 },
                // --- UPDATED: Send the FormData object directly as the body ---
                body: formData // Send the FormData object
            });

            if (!response.ok) {
                // Handle HTTP errors (e.g., 400, 401, 404, 500)
                const errorText = await response.text(); // Get raw response text first
                 try {
                     const errorJson = JSON.parse(errorText); // Try to parse as JSON
                      // Check if the error message indicates a specific known issue
                      if (errorJson.message && errorJson.message.includes("Unknown action")) {
                           console.error("API Error: Unknown action. Check action name in JS and PHP.");
                           throw new Error(`API Error: Unknown action '${action}'. Check console for details.`);
                      }
                      // Throw a general error with the message from the server
                      throw new Error(`Server responded with status ${response.status}: ${errorJson.message || errorText}`);
                 } catch (jsonError) {
                     // If parsing as JSON failed, the response was not JSON
                      console.error("Server returned non-JSON error response:", errorText);
                      throw new Error(`Server responded with status ${response.status}. Response: ${errorText.substring(0, 100)}...`); // Show snippet of response
                 }
            }

            // If response status is OK, parse the JSON body
            const result = await response.json();

            if (result.success) {
                console.log(`${isEditing ? 'Update' : 'Add'} animal successful:`, result);
                 // Use the message from the server response if available
                 showSuccessToast(result.message || `${isEditing ? 'Animal record එක සාර්ථකව යාවත්කාලීන කරන ලදී.' : 'නව Animal record එක සාර්ථකව එකතු කරන ලදී.'}`);

                 // Reload data and update UI after successful save
                 await loadAnimals(); // Assuming loadAnimals fetches and renders both card/table views
                 await updateSummaryCards(); // Update summary cards
                 await updateTypeChart(); // Update chart

                 // Close the relevant modal (assuming you have functions like closeAddModal, closeEditModal)
                 if (!isEditing) {
                     // If adding, clear and close the add modal
                     resetAddForm(); // Assuming you have a function to reset the add form
                     closeAddModal(); // Assuming you have a function to close the add modal
                 } else {
                     // If editing, close the edit modal
                     closeEditModal(); // Assuming you have a function to close the edit modal
                 }


                return true; // Indicate success
            } else {
                console.error(`${isEditing ? 'Update' : 'Add'} animal failed:`, result.message);
                 // Show the error message from the server response
                 showErrorToast(`${isEditing ? 'Animal record එක යාවත්කාලීන කිරීමේදී' : 'නව Animal record එක එකතු කිරීමේදී'} දෝෂයක්: ${result.message || 'Unknown error'}`);
                return false; // Indicate failure
            }

        } catch (error) {
            console.error(`Fetch error during ${isEditing ? 'update' : 'add'} animal:`, error);
             // Show a generic error message for fetch/network issues or unhandled server errors
             showErrorToast('Server වෙත දත්ත යැවීමට අසමත් විය: ' + error.message);
            return false; // Indicate failure due to network error or unhandled exception
        }
    }

    // --- You will also need functions like resetAddForm, closeAddModal, closeEditModal ---
    // Make sure these functions exist and correctly hide/clear your modals/forms.
    /*
    function resetAddForm() { // Example reset function
        const form = document.getElementById('add-animal-form');
        if (form) form.reset();
        // Also clear any custom fields like image preview
         const imagePreview = document.getElementById('add-image-preview');
         if(imagePreview) imagePreview.src = ''; // Or a default placeholder
    }

    function closeAddModal() { // Example close function
         const modal = document.getElementById('add-animal-modal'); // Assuming your add modal has this ID
         if (modal) modal.classList.add('hidden');
    }

     function closeEditModal() { // Example close function
         const modal = document.getElementById('edit-animal-modal'); // Assuming your edit modal has this ID
         if (modal) modal.classList.add('hidden');
     }
    */

    // Delete animal data via the server API
    async function deleteAnimal(animalId) {
        console.log('Deleting animal via server API:', animalId);
        try {
            const response = await fetch(LIVESTOCK_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'delete_livestock', // Action for PHP API
                    userId: currentUserId,
                    animalId: animalId // Send the animal ID (server ID)
                })
            });

            if (!response.ok) {
                const errorText = await response.text();
                 try {
                      const errorJson = JSON.parse(errorText);
                       throw new Error(`Server returned status ${response.status}: ${errorJson.message || errorText}`);
                 } catch (jsonError) {
                       throw new Error(`Server returned status ${response.status}: ${errorText}`);
                 }
            }

            const result = await response.json();

            if (result.success) {
                console.log('Delete animal successful:', result.message);
                // Reload data after successful deletion
                await loadAnimals(); // Reload all animals from server
                await updateSummaryCards(); // Update summary cards
                await updateTypeChart(); // Update chart
                showSuccessToast('Animal record එක සාර්ථකව මකා දමන ලදී.'); // Show success toast after reload
                return true; // Indicate success
            } else {
                console.error('Delete animal failed:', result.message);
                showErrorToast('Animal record එක මකා දැමීමේදී දෝෂයක්: ' + (result.message || 'Unknown error'));
                return false; // Indicate failure
            }

        } catch (error) {
            console.error('Fetch error during delete animal:', error);
            showErrorToast('Server වෙතින් Animal record එක Delete කිරීමට අසමත් විය.');
            return false; // Indicate failure due to network error
        }
    }


    


    // =======================================================
    // UI Rendering and Data Display Functions
    // =======================================================

    // Update pagination controls based on the total number of items
    function updatePagination(totalItems) {
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        const paginationDiv = document.querySelector('.sm\\:flex-1.sm\\:flex.sm\\:items-center.sm\\:justify-between'); // Select the pagination container
        const paginationInfoSpan = paginationDiv ? paginationDiv.querySelector('.text-sm.text-gray-700') : null;
        const pageNumbersDiv = document.getElementById('page-numbers');
        const prevPageBtn = document.getElementById('prev-page');
        const nextPageBtn = document.getElementById('next-page');

        if (!paginationDiv || !paginationInfoSpan || !pageNumbersDiv || !prevPageBtn || !nextPageBtn) {
            console.warn("Pagination elements not found.");
            return;
        }

        // Update showing info
        const startItem = totalItems === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);
        paginationInfoSpan.innerHTML = `Showing <span class="font-medium" id="pagination-start">${startItem}</span> to <span class="font-medium" id="pagination-end">${endItem}</span> of <span class="font-medium" id="pagination-total">${totalItems}</span> results`;

        // Update page numbers
        pageNumbersDiv.innerHTML = ''; // Clear existing page numbers
        // Limit the number of visible page buttons to avoid clutter
        const maxVisiblePages = 5; // Adjust as needed
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        // Adjust startPage if endPage reaches totalPages but maxVisiblePages are not shown
        if (endPage === totalPages && (endPage - startPage + 1) < maxVisiblePages) {
             startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }


        for (let i = startPage; i <= endPage; i++) {
            const pageButton = document.createElement('a');
            pageButton.href = '#';
            pageButton.className = `relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 ${currentPage === i ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : ''}`;
            pageButton.textContent = i;
             // Event listener is added using delegation on the paginationNav element in setupEventListeners
            pageNumbersDiv.appendChild(pageButton);
        }

        // Update Previous/Next button states
        prevPageBtn.classList.toggle('pointer-events-none', currentPage === 1);
        prevPageBtn.classList.toggle('opacity-50', currentPage === 1);
         prevPageBtn.classList.toggle('text-gray-500', currentPage !== 1);
         prevPageBtn.classList.toggle('hover:bg-gray-50', currentPage !== 1);


        nextPageBtn.classList.toggle('pointer-events-none', currentPage === totalPages || totalItems === 0);
        nextPageBtn.classList.toggle('opacity-50', currentPage === totalPages || totalItems === 0);
         nextPageBtn.classList.toggle('text-gray-500', currentPage < totalPages && totalItems > 0);
          nextPageBtn.classList.toggle('hover:bg-gray-50', currentPage < totalPages && totalItems > 0);
    }

    // Load animals from the server API and render the current view
    async function loadAnimals() {
        console.log('Loading animals from server (via fetchLivestock)...');
         // Show loading indicator
         const tableBody = document.getElementById('livestock-table-body');
          const cardView = document.getElementById('card-view');
          if (tableBody && currentView === 'table') {
               tableBody.innerHTML = `
                   <tr>
                       <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                           <i class="fas fa-spinner fa-spin mr-2"></i> Loading animals...
                       </td>
                   </tr>
               `;
          } else if (cardView && currentView === 'card') {
               cardView.innerHTML = `
                   <div class="sm:col-span-1 lg:col-span-3 text-center py-10">
                       <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                       <p class="mt-2 text-sm text-gray-500">Loading animals...</p>
                   </div>
               `;
                // Ensure card view is visible while loading in card view mode
                 cardView.classList.remove('hidden');
                 if (tableBody) tableBody.classList.add('hidden');
          } else {
               console.warn("Table or Card view container not found for loading indicator.");
          }


        try {
            // Fetch animals from the server with current filters and sort
            const animals = await fetchLivestock({
                 search: currentSearchTerm,
                 type: currentTypeFilter,
                 status: currentStatusFilter,
                 sortBy: currentSort
                 // Pagination is handled client-side after fetching all data here
                 // If server-side pagination is implemented, pass page, itemsPerPage, etc.
            });

            currentAnimals = animals; // Store the fetched list

            // Update pagination info and buttons based on fetched count
            updatePagination(currentAnimals.length);

            // Determine the range of animals to display on the current page
            const startIdx = (currentPage - 1) * itemsPerPage;
            const paginatedAnimals = currentAnimals.slice(startIdx, startIdx + itemsPerPage);


            // Render the appropriate view
            if (currentView === 'table') {
                renderTableView(paginatedAnimals);
            } else { // currentView === 'card'
                renderCardView(paginatedAnimals);
            }

            // Update summary cards and chart AFTER loading data
             await updateSummaryCards();
             await updateTypeChart();


            console.log('Animals loaded and rendered.');

        } catch (error) {
            console.error('Error loading animals:', error);
            // Error handling is already in fetchLivestock, it shows a toast
             // Render empty state or error message in the table/card view
              const tableBody = document.getElementById('livestock-table-body');
               const cardView = document.getElementById('card-view');
              const errorMessage = `<td colspan="8" class="px-6 py-4 text-center text-sm text-red-500">Error loading animals.</td>`;
               const cardErrorMessage = `<div class="sm:col-span-1 lg:col-span-3 text-center py-10 text-red-500">Error loading animals.</div>`;

               if (tableBody && currentView === 'table') {
                   tableBody.innerHTML = `<tr>${errorMessage}</tr>`;
               } else if (cardView && currentView === 'card') {
                   cardView.innerHTML = cardErrorMessage;
                    cardView.classList.remove('hidden'); // Ensure it's visible
                    if (tableBody) tableBody.classList.add('hidden');
               } else {
                   console.warn("Could not display error message in table or card view.");
               }
        }
    }

    // Render animals in Table View
    function renderTableView(animals) {
        const tableBody = document.getElementById('livestock-table-body');
         if(!tableBody) {
              console.error("Table body element (#livestock-table-body) not found.");
              return;
         }


        if (!animals || animals.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                        දැනට Animal records හමු නොවේ.
                         <button id="add-animal-empty-table" class="mt-1 text-blue-600 hover:text-blue-800 focus:outline-none">"Add New Animal" ක්ලික් කර එකතු කරන්න.</button>
                    </td>
                </tr>
            `;
             // Add event listener to the "Add New Animal" button in the empty state message
             const addAnimalEmptyBtn = document.getElementById('add-animal-empty-table');
              if (addAnimalEmptyBtn) {
                  // Remove existing listener if any to prevent duplicates
                   addAnimalEmptyBtn.removeEventListener('click', handleEmptyAddClick);
                   addAnimalEmptyBtn.addEventListener('click', handleEmptyAddClick);
              }

            return;
        }

        tableBody.innerHTML = animals.map(animal => `
            <tr class="hover:bg-gray-50" data-id="${animal.id}">
                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                     <div class="flex items-center">
                        ${animal.idTag || '-'}
                        </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap capitalize">${animal.type || '-'}</td>
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
                    <button data-id="${animal.id}" class="view-animal text-blue-600 hover:text-blue-900 mr-3 focus:outline-none" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button data-id="${animal.id}" class="edit-animal text-yellow-600 hover:text-yellow-900 mr-3 focus:outline-none" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button data-id="${animal.id}" class="delete-animal text-red-600 hover:text-red-900 focus:outline-none" title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `).join('');

         // No need to add listeners here, event delegation is used on the table body.
    }

    // Render animals in Card View
    function renderCardView(animals = null) {
        const cardView = document.getElementById('card-view');
         const tableViewContainer = document.getElementById('table-view');
         if(!cardView || !tableViewContainer) {
             console.error("Card view or Table view element not found.");
             return;
         }

        // Ensure card view is visible and table view is hidden
         cardView.classList.remove('hidden');
         tableViewContainer.classList.add('hidden');


        if (!animals) {
            // If no animals provided, use the current paginated animals from the global array
            const startIdx = (currentPage - 1) * itemsPerPage;
            animals = currentAnimals.slice(startIdx, startIdx + itemsPerPage);
        }

        if (!animals || animals.length === 0) {
            cardView.innerHTML = `
                <div class="sm:col-span-1 lg:col-span-3 text-center py-10"> <i class="fas fa-cow text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500">දැනට Animal records හමු නොවේ.</p>
                    <button id="add-animal-empty-card" class="mt-3 text-blue-600 hover:text-blue-800 focus:outline-none">"Add New Animal" ක්ලික් කර එකතු කරන්න.</button>
                </div>
            `;
             // Add event listener to the "Add New Animal" button in the empty state message
             const addAnimalEmptyCardBtn = document.getElementById('add-animal-empty-card');
              if (addAnimalEmptyCardBtn) {
                  // Remove existing listener if any
                   addAnimalEmptyCardBtn.removeEventListener('click', handleEmptyAddClick);
                   addAnimalEmptyCardBtn.addEventListener('click', handleEmptyAddClick);
              }
            return;
        }

         // Ensure card view is grid layout even when empty state is not shown
         cardView.className = 'p-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3'; // Reset classes


        cardView.innerHTML = animals.map(animal => `
           <div class="animal-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 hover:border-blue-300">
            <div class="p-4">
                <!-- First row with ID, Status, and Type -->
                <div class="flex items-center justify-between">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">${animal.idTag || 'N/A'}</h3>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(animal.status)}">
                        ${getStatusText(animal.status)}
                    </span>
                    <span class="text-sm text-gray-500 capitalize">${animal.type || '-'}</span>
                </div>
                
                <!-- Content with animal info and image positioned to the right -->
                <div class="flex mt-2">
                    <!-- Left column with animal details -->
                    <div class="flex-1">
                        <div class="text-sm text-gray-500">
                            ${animal.breed || 'No breed'} • ${animal.location || 'No location'}
                        </div>
                        
                        <div class="mt-2">
                            <p class="text-xs text-gray-500">Age</p>
                            <p class="text-sm font-medium">${formatAge(animal.ageYears, animal.ageMonths)}</p>
                        </div>
                        
                        <div class="mt-2">
                            <p class="text-xs text-gray-500">Weight</p>
                            <p class="text-sm font-medium">${animal.weight ? `${animal.weight} ${animal.weightUnit || 'kg'}` : '-'}</p>
                        </div>
                    </div>
                    
                    <!-- Right column with image -->
                    <div class="ml-4 flex-shrink-0">
                        <img src="${animal.image_url || `https://source.unsplash.com/random/150x100/?${encodeURIComponent(animal.type || 'animal')},livestock`}" alt="${animal.type || 'Animal'}" class=" object-contain rounded-lg"
                        style="width: 110px; height: 110px;" >
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 flex justify-between">
                    <button data-id="${animal.id}" class="view-animal inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-eye mr-1"></i> View
                    </button>
                    <div>
                        <button data-id="${animal.id}" class="edit-animal inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </button>
                        <button data-id="${animal.id}" class="delete-animal inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        `).join('');

         // No need to add listeners here, event delegation is used on the card view container.
    }

    // Update Summary Cards (Total, Healthy, Pregnant, Needs Attention)
    async function updateSummaryCards() {
        console.log('Updating summary cards...');
        try {
             // Use the currently loaded and filtered animals for counts
            const allAnimals = currentAnimals; // Use the data already fetched by loadAnimals

            const totalCount = allAnimals.length;
            const healthyCount = allAnimals.filter(animal => animal.status === 'healthy').length;
            const pregnantCount = allAnimals.filter(animal => animal.status === 'pregnant').length;
            const needsAttentionCount = allAnimals.filter(animal => animal.status === 'sick' || animal.status === 'injured').length;

            document.getElementById('total-animals-count').textContent = totalCount;
            document.getElementById('healthy-animals-count').textContent = healthyCount;
            document.getElementById('pregnant-animals-count').textContent = pregnantCount;
            document.getElementById('sick-animals-count').textContent = needsAttentionCount; // Renamed to sick-animals-count in HTML

        } catch (error) {
            console.error('Error updating summary cards:', error);
             // Optionally show an error toast
        }
    }

    // Initialize the Livestock Type Distribution Chart
    function initTypeChart() {
         const ctx = document.getElementById('typeChart');
          if (!ctx) {
              console.warn("Chart element (#typeChart) not found.");
              return;
          }

         // Destroy existing chart instance if it exists
          if (livestockTypeChart) {
              livestockTypeChart.destroy();
          }

         livestockTypeChart = new Chart(ctx, {
             type: 'doughnut', // Or 'doughnut'
             data: {
                 labels: [], // Will be populated by updateTypeChart
                 datasets: [{
                     label: 'Number of Animals',
                     data: [], // Will be populated by updateTypeChart
                     backgroundColor: [
                        'rgba(52, 232, 232, 0.7)',
                        'rgba(112, 252, 131, 0.7)',
                        'rgba(224, 93, 254, 0.91)',
                        'rgba(252, 111, 141, 0.93)',
                        'rgba(146, 93, 253, 0.87)',
                        'rgba(251, 170, 89, 0.7)',
                        'rgba(229, 251, 87, 0.87)'
                    ],
                    borderColor: [
                        'rgb(93, 233, 233)',
                        'rgb(89, 255, 95)',
                        'rgba(224, 91, 253, 0.9)',
                        'rgba(252, 111, 142, 0.81)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(224, 245, 89, 0.73)'
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
                     title: {
                         display: false,
                         text: 'Livestock Distribution by Type'
                     },
                     tooltip: {
                          callbacks: {
                              label: function(tooltipItem) {
                                  const label = tooltipItem.label || '';
                                  const value = tooltipItem.raw || 0;
                                  const total = tooltipItem.dataset.data.reduce((sum, current) => sum + current, 0);
                                  const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                  return `${label}: ${value} (${percentage}%)`;
                              }
                          }
                     }
                 }
             }
         });
         console.log('Livestock type chart initialized.');
    }


    // Update the Livestock Type Distribution Chart with data from the current animals list
    async function updateTypeChart() {
        console.log('Updating type chart with data...');
         if (!livestockTypeChart) {
              console.warn("Chart not initialized. Call initTypeChart first.");
              return;
         }
        try {
             // Use the currently loaded and filtered animals for chart data
            const allAnimals = currentAnimals; // Use the data already fetched by loadAnimals


            // Count animals by type
            const typeCounts = allAnimals.reduce((acc, animal) => {
                const type = animal.type ? animal.type.toLowerCase() : 'other'; // Group unknown types and use lowercase for consistency
                acc[type] = (acc[type] || 0) + 1;
                return acc;
            }, {});

            // Prepare data for the chart
            const labels = Object.keys(typeCounts).map(type => type.charAt(0).toUpperCase() + type.slice(1)); // Capitalize labels
            const data = Object.values(typeCounts);

            // Update the chart data
            livestockTypeChart.data.labels = labels;
            livestockTypeChart.data.datasets[0].data = data;

            // Update the chart display
            livestockTypeChart.update();
            console.log('Type chart updated.');

        } catch (error) {
            console.error('Error updating type chart:', error);
             // Optionally show an error toast
        }
    }


    // =======================================================
    // Event Listener Setup
    // =======================================================

    // Setup all necessary UI Event Listeners
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
            document.addEventListener('click', function() {
                userMenuDropdown.classList.add('hidden');
            });


        // --- Top Nav Search Input ---
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(async () => {
                    currentSearchTerm = searchInput.value.trim();
                    currentPage = 1; // Reset to first page on search
                    await loadAnimals(); // Load data with new search term
                }, 300); // Debounce delay
            });
        } else {
            console.warn("Search input (#search-input) not found.");
        }

         // --- Main Content Search Input (if different from top nav search) ---
          // Assuming the main search input uses #search-animals
         const mainSearchInput = document.getElementById('search-animals');
          if (mainSearchInput && mainSearchInput !== searchInput) { // Check if it's a different input
              let mainSearchTimeout;
              mainSearchInput.addEventListener('input', function() {
                  clearTimeout(mainSearchTimeout);
                   mainSearchTimeout = setTimeout(async () => {
                       currentSearchTerm = mainSearchInput.value.trim();
                       currentPage = 1; // Reset to first page on search
                       await loadAnimals(); // Load data with new search term
                   }, 300); // Debounce delay
              });
               // Sync initial value if top search has one
               if (searchInput && searchInput.value) {
                    mainSearchInput.value = searchInput.value;
               }
          } else if (mainSearchInput && mainSearchInput === searchInput) {
               console.log("Main search input is the same as top nav search input.");
          } else {
               console.warn("Main search input (#search-animals) not found.");
          }


        // --- Add New Animal Button ---
        const addAnimalBtn = document.getElementById('add-animal-btn');
         const animalModal = document.getElementById('animal-modal'); // Use the single modal ID
         const modalTitle = document.getElementById('modal-title'); // Get modal title element
         const animalForm = document.getElementById('animal-form'); // Use the form ID


        if (addAnimalBtn && animalModal && modalTitle && animalForm) {
            addAnimalBtn.addEventListener('click', function() {
                // Open Add Animal Modal
                animalModal.classList.remove('hidden');
                modalTitle.textContent = 'Add New Animal';
                animalForm.reset(); // Reset form fields

                 // Clear hidden input for animal ID (this will be the server ID)
                document.getElementById('animal-id').value = ''; // Empty means 'add'

                 // Reset image preview and clear file input value
                 document.getElementById('preview-image').src = '#';
                 document.getElementById('image-preview').classList.add('hidden');
                 document.getElementById('image-upload-area').classList.remove('hidden');
                 document.getElementById('animal-image').value = ''; // Clear selected file

                 // Clear existing image URL hidden input if it exists
                  const existingImageInput = document.getElementById('existing-animal-image');
                   if (existingImageInput) existingImageInput.value = '';


                 // Change save button text/handler if needed (or use data attribute on form)
                 // Here we'll rely on the hidden animal-id field to determine add vs edit
            });
        } else {
            console.warn("Add Animal button or modal elements not found.");
        }

        // --- Add/Edit Animal Form Submit ---
        if (animalForm) {
             animalForm.addEventListener('submit', async function(event) {
                 event.preventDefault(); // Prevent default form submission

                 const animalId = document.getElementById('animal-id').value; // Get animal ID (Server ID)
                 const isEditing = animalId !== ''; // Determine if editing or adding based on ID presence

                 // Get form data manually or using FormData - We will use FormData when submitting
                 const animalData = {
                      // Include the server ID if editing
                      id: isEditing ? parseInt(animalId) : null, // Server ID is an integer
                     idTag: document.getElementById('animal-id-tag').value.trim(),
                     type: document.getElementById('animal-type').value,
                     breed: document.getElementById('animal-breed').value.trim(),
                     birthDate: document.getElementById('animal-birth-date').value,
                      // Use age fields as fallback if birthDate is empty
                      ageYears: parseInt(document.getElementById('animal-age-years').value) || 0,
                      ageMonths: parseInt(document.getElementById('animal-age-months').value) || 0,
                     weight: parseFloat(document.getElementById('animal-weight').value) || 0,
                     weightUnit: document.getElementById('weight-unit').value,
                     status: document.getElementById('animal-status').value,
                     location: document.getElementById('animal-location').value.trim(),
                     notes: document.getElementById('animal-notes').value.trim(),
                      // Image and Activities are not direct form fields, handled separately if needed
                 };

                  // Basic validation
                  if (!animalData.idTag || !animalData.type || !animalData.status) {
                       showAlert('ID/Tag, Type, and Status are required.', 'warning');
                       return;
                  }

                   // If birth date is provided, calculate age from it
                 if (animalData.birthDate) {
                      const birthDate = new Date(animalData.birthDate);
                      const today = new Date();
                      let ageInMonths = (today.getFullYear() - birthDate.getFullYear()) * 12;
                      ageInMonths -= birthDate.getMonth();
                      ageInMonths += today.getMonth();
                       if (today.getDate() < birthDate.getDate()) {
                            ageInMonths--; // Subtract a month if current day is before birth day
                       }
                       // Ensure ageInMonths is not negative and non-fractional
                       ageInMonths = Math.max(0, Math.floor(ageInMonths));


                      animalData.ageYears = Math.floor(ageInMonths / 12);
                      animalData.ageMonths = ageInMonths % 12;
                       // Optionally update the age input fields visually
                       document.getElementById('animal-age-years').value = animalData.ageYears;
                       document.getElementById('animal-age-months').value = animalData.ageMonths;

                 } else {
                      // If no birth date, ensure age fields are treated as numbers
                       animalData.ageYears = parseInt(document.getElementById('animal-age-years').value) || 0;
                       animalData.ageMonths = parseInt(document.getElementById('animal-age-months').value) || 0;
                 }


                 // Call the save function to send data to the server API using FormData
                 const success = await saveAnimal(animalData, isEditing);

                 if (success) {
                     // Close modal and reset form after successful save
                     animalModal.classList.add('hidden');
                     animalForm.reset();
                      // Reset image preview
                      document.getElementById('preview-image').src = '#';
                      document.getElementById('image-preview').classList.add('hidden');
                      document.getElementById('image-upload-area').classList.remove('hidden');
                       document.getElementById('animal-image').value = ''; // Clear file input
                       const existingImageInput = document.getElementById('existing-animal-image');
                       if (existingImageInput) existingImageInput.value = '';
                 }
                  // If save failed, the modal remains open with error toast shown by saveAnimal
             });
        } else {
             console.warn("Animal form (#animal-form) not found.");
        }


        // --- Modal Close Buttons ---
        document.querySelectorAll('.close-modal, #cancel-animal').forEach(button => {
            button.addEventListener('click', function() {
                console.log('Modal close button clicked:', button.id || button.className); // Debugging line
                const modal = button.closest('.modal');
                if (modal) {
                    modal.classList.add('hidden');
                    // Reset the form when animal modal is closed
                     if(modal.id === 'animal-modal') {
                          const form = document.getElementById('animal-form');
                          if(form) form.reset();
                          // Reset image preview in animal modal
                           document.getElementById('preview-image').src = '#';
                           document.getElementById('image-preview').classList.add('hidden');
                           document.getElementById('image-upload-area').classList.remove('hidden');
                            document.getElementById('animal-image').value = ''; // Clear file input
                            const existingImageInput = document.getElementById('existing-animal-image');
                            if (existingImageInput) existingImageInput.value = '';
                     }
                }
            });
        });

        // --- Age & Birth Date Interaction ---
            const birthDateInput = document.getElementById('animal-birth-date');
            const ageYearsInput = document.getElementById('animal-age-years');
            const ageMonthsInput = document.getElementById('animal-age-months');

            if (birthDateInput && ageYearsInput && ageMonthsInput) {
                // Add event listener for when the birth date input value changes
                birthDateInput.addEventListener('change', function() {
                    console.log("Birth date changed:", this.value); // Debugging line
                    calculateAndSetAge(this.value); // Call the helper function with the new value
                });

                // You might want to add an initial call here for cases where
                // the modal opens with a default date (though populateAnimalForm handles this).
                // If you want age fields to be disabled if a default date is *already* in the HTML on load,
                // you could call calculateAndSetAge(birthDateInput.value) here.
                // However, the populateAnimalForm call when editing is the main place for initial setup.


            } else {
                console.warn("Birth date or age input fields not found. Age calculation feature disabled.");
            }


            // ... (Rest of your existing event listeners in setupEventListeners) ...

        // --- Animal Details Modal Close Button ---
         const closeDetailsBtn = document.getElementById('close-details-btn');
         const animalDetailsModal = document.getElementById('animal-details-modal');
          if(closeDetailsBtn && animalDetailsModal) {
               closeDetailsBtn.addEventListener('click', function() {
                   animalDetailsModal.classList.add('hidden');
               });
                // Also add listener to the small 'x' button in the header
               const closeDetailsHeaderBtn = document.getElementById('close-details');
                if(closeDetailsHeaderBtn) {
                     closeDetailsHeaderBtn.addEventListener('click', function() {
                         animalDetailsModal.classList.add('hidden');
                     });
                }
          } else {
               console.warn("Animal details modal close button(s) or modal not found.");
          }
          // ... (Other JavaScript code and event listeners) ...

     // --- Edit button inside Animal Details Modal ---
     // Use the correct ID: edit-details-btn
     const editDetailsModalBtn = document.getElementById('edit-details-btn');

     if (editDetailsModalBtn) {
          // Remove any existing listener to prevent duplicates
          // Use the specific function name handleDetailsModalEditClick or similar for clarity if needed
          // Or you can use your anonymous function directly if you prefer, but removeEventListener requires a reference to the exact function
          // For simplicity and to prevent duplicates if the script runs multiple times, a named function is safer for removeEventListener
          editDetailsModalBtn.removeEventListener('click', handleDetailsModalEditClick); // If using a named function
          editDetailsModalBtn.addEventListener('click', handleDetailsModalEditClick); // If using a named function

          /*
           // Alternative: Using your anonymous function structure, but cannot easily remove it later
           editDetailsModalBtn.addEventListener('click', async function() {
               const animalId = parseInt(this.dataset.id);
               if (!isNaN(animalId)) {
                   console.log('Edit button clicked inside Details Modal for ID:', animalId);
                   const animalDetailsModal = document.getElementById('animal-details-modal');
                   if (animalDetailsModal) animalDetailsModal.classList.add('hidden');
                   // Ensure you call the correct function for showing the Edit Modal
                   await showEditModal(animalId); // Assuming showEditModal is the correct function
               } else {
                   console.error("Invalid animal ID on edit button in details modal.");
                   showErrorToast("Error: Could not find animal ID for editing.");
               }
           });
           */

     } else {
         // This console.warn will trigger if the element with id="edit-details-btn" is not found in the HTML
         console.warn("Edit button element inside details modal (#edit-details-btn) not found. Check modal HTML.");
     }


     // --- Ensure handleDetailsModalEditClick or your anonymous function (depending on which structure you use above) is defined ---
     // Example (if using the named function handleDetailsModalEditClick):
     async function handleDetailsModalEditClick(event) {
         const button = event.target.closest('button'); // Get the button element itself

         if (button) {
             const animalId = parseInt(button.dataset.id); // Get ID from the button's data attribute

             if (!isNaN(animalId)) {
                  console.log('Edit button clicked inside Details Modal for ID:', animalId);

                  // Close the details modal first
                  const animalDetailsModal = document.getElementById('animal-details-modal');
                  if (animalDetailsModal) {
                      animalDetailsModal.classList.add('hidden');
                  }

                  // Call the function to show/populate the Edit Modal
                  await openEditModal(animalId); // Ensure this function exists and works correctly

             } else {
                  console.error("Invalid animal ID on edit button in details modal.");
                  showErrorToast("Error: Could not find animal ID for editing.");
             }
         } else {
              console.warn("Click event on details modal edit area did not originate from a button.");
         }
     }


     // --- Also ensure you have a showEditModal function defined elsewhere in your script ---
     /*
     async function showEditModal(animalId) {
         console.log("Opening edit modal for animal ID:", animalId);
         // Add logic here to:
         // 1. Fetch animal details for editing (if needed, or maybe showAnimalDetails data is sufficient)
         // 2. Populate the edit form fields with the animal data
         // 3. Show the edit modal element (e.g., remove 'hidden' class)
         const editModal = document.getElementById('edit-animal-modal'); // Assuming your edit modal has this ID
         if(editModal) {
              // Fetch animal data for form...
              // Populate form...
             editModal.classList.remove('hidden');
         } else {
              console.error("Edit Animal Modal element (#edit-animal-modal) not found.");
         }
     }
     */

          
         // --- Edit button inside Animal Details Modal ---
          const editAnimalFromDetailsBtn = document.getElementById('edit-animal-from-details');
           if (editAnimalFromDetailsBtn) {
               editAnimalFromDetailsBtn.addEventListener('click', async function() {
                   const animalId = parseInt(this.dataset.id); // Get the animal ID from the button's data attribute
                   if (!isNaN(animalId)) {
                        // Close the details modal first
                        const animalDetailsModal = document.getElementById('animal-details-modal');
                        if (animalDetailsModal) animalDetailsModal.classList.add('hidden');
                       // Open the edit modal with the animal ID
                       await openEditModal(animalId);
                   } else {
                       console.error("Invalid animal ID on edit button in details modal.");
                       showErrorToast("Error: Could not find animal ID for editing.");
                   }
               });
           } else {
               console.warn("Edit animal button from details modal not found.");
           }


        // --- Filter Dropdowns ---
        const typeFilter = document.getElementById('type-filter');
        if (typeFilter) {
            typeFilter.addEventListener('change', async function() {
                currentTypeFilter = typeFilter.value;
                currentPage = 1; // Reset to first page on filter change
                await loadAnimals(); // Reload with filters
            });
        } else {
            console.warn("Type filter dropdown (#type-filter) not found.");
        }

        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', async function() {
                currentStatusFilter = statusFilter.value;
                currentPage = 1; // Reset to first page on filter change
                await loadAnimals(); // Reload with filters
            });
        } else {
            console.warn("Status filter dropdown (#status-filter) not found.");
        }


        // --- Sort Dropdown ---
        const sortDropdown = document.getElementById('sort-by');
        if (sortDropdown) {
            sortDropdown.addEventListener('change', async function() {
                currentSort = sortDropdown.value;
                currentPage = 1; // Reset to first page on sort change
                await loadAnimals(); // Reload with sorting
            });
        } else {
            console.warn("Sort dropdown (#sort-by) not found.");
        }


        // --- View Toggles (Table/Card) ---
        const viewToggle = document.getElementById('view-toggle');
        const tableViewContainer = document.getElementById('table-view');
        const cardViewContainer = document.getElementById('card-view');

        if (viewToggle && tableViewContainer && cardViewContainer) {
            viewToggle.addEventListener('click', async function() {
                if (currentView === 'table') {
                    currentView = 'card';
                    tableViewContainer.classList.add('hidden');
                    cardViewContainer.classList.remove('hidden');
                    viewToggle.innerHTML = '<i class="fas fa-grip-vertical mr-2"></i> Card View'; // Change icon/text
                     viewToggle.classList.remove('bg-white', 'text-gray-700', 'hover:bg-gray-50');
                     viewToggle.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');

                } else { // currentView === 'card'
                    currentView = 'table';
                    cardViewContainer.classList.add('hidden');
                    tableViewContainer.classList.remove('hidden');
                    viewToggle.innerHTML = '<i class="fas fa-table mr-2"></i> Table View'; // Change icon/text
                     viewToggle.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
                     viewToggle.classList.add('bg-white', 'text-gray-700', 'hover:bg-gray-50');
                }
                currentPage = 1; // Reset to first page on view change
                // No need to explicitly call loadAnimals here, as the logic is within renderTableView/renderCardView
                // which are called by updatePagination, which is updated by loadAnimals.
                // However, if loadAnimals is async and takes time, you might want to re-render
                // with the *current* paginated animals immediately while loadAnimals fetches the potentially resorted/filtered data.
                // For simplicity and to ensure latest data is used, let's keep the loadAnimals call here.
                await loadAnimals(); // Reload animals for the new view
            });
             // Set initial button text/style based on default view (should match initial HTML state)
             if(currentView === 'table') {
                 viewToggle.innerHTML = '<i class="fas fa-table mr-2"></i> Table View';
                  viewToggle.classList.add('bg-white', 'text-gray-700', 'hover:bg-gray-50');
             } else { // If initial HTML makes card view visible by default
                  viewToggle.innerHTML = '<i class="fas fa-grip-vertical mr-2"></i> Card View';
                   viewToggle.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
             }
        } else {
            console.warn("View toggle button or containers not found.");
        }


        // --- Pagination Buttons (using event delegation on a parent if possible, or specific IDs) ---
        const paginationNav = document.querySelector('nav[aria-label="Pagination"]');
        if (paginationNav) {
            paginationNav.addEventListener('click', async function(event) {
                const target = event.target.closest('a'); // Use closest to handle clicks on icons/spans inside the anchor
                 if (!target || target.classList.contains('pointer-events-none')) return; // Ignore clicks on disabled buttons

                event.preventDefault(); // Prevent default link behavior

                const totalItems = currentAnimals.length; // Use the count of filtered/sorted animals
                const totalPages = Math.ceil(totalItems / itemsPerPage);

                if (target.id === 'prev-page') {
                    if (currentPage > 1) {
                        currentPage--;
                        await loadAnimals(); // Reload data for previous page
                    }
                } else if (target.id === 'next-page') {
                    if (currentPage < totalPages) {
                        currentPage++;
                        await loadAnimals(); // Reload data for next page
                    }
                } else { // Handle clicks on dynamic page number buttons
                     const pageNumber = parseInt(target.textContent);
                      if (!isNaN(pageNumber) && pageNumber >= 1 && pageNumber <= totalPages && pageNumber !== currentPage) {
                          currentPage = pageNumber;
                          await loadAnimals(); // Reload data for clicked page
                      }
                }
            });
        } else {
             console.warn("Pagination navigation element not found.");
        }


        // --- Event listeners for dynamically added buttons (Edit, Delete, View) ---
        // Using event delegation on the table body and card view container
        const tableBody = document.getElementById('livestock-table-body');
        const cardView = document.getElementById('card-view');

        if (tableBody) {
            tableBody.addEventListener('click', function(event) {
                handleAnimalActionButtonClick(event); // Delegate clicks to handler function
            });
        } else {
            console.warn("Table body (#livestock-table-body) not found. Action buttons might not work.");
        }

        if (cardView) {
            cardView.addEventListener('click', function(event) {
                handleAnimalActionButtonClick(event); // Delegate clicks to handler function
            });
        } else {
            console.warn("Card view (#card-view) not found. Action buttons might not work.");
        }

         // --- Animal Image Upload Preview ---
         const animalImageInput = document.getElementById('animal-image');
         const previewImage = document.getElementById('preview-image');
         const imageUploadArea = document.getElementById('image-upload-area');
         const imagePreview = document.getElementById('image-preview');
         const removeImageButton = document.getElementById('remove-image');

          if(animalImageInput && previewImage && imageUploadArea && imagePreview && removeImageButton) {
               animalImageInput.addEventListener('change', handleImageSelect);
               removeImageButton.addEventListener('click', handleRemoveImage);
          } else {
               console.warn("Image upload elements not found.");
          }

           // Add drag and drop listeners for the upload area (optional)
            if (imageUploadArea) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    imageUploadArea.addEventListener(eventName, preventDefaults, false);
                });
                ['dragenter', 'dragover'].forEach(eventName => {
                    imageUploadArea.addEventListener(eventName, () => imageUploadArea.classList.add('border-blue-500'), false);
                });
                ['dragleave', 'drop'].forEach(eventName => {
                    imageUploadArea.addEventListener(eventName, () => imageUploadArea.classList.remove('border-blue-500'), false);
                });
                imageUploadArea.addEventListener('drop', handleDrop, false);

                function preventDefaults (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    if (files.length > 0) {
                         // Assign dropped files to the file input
                        animalImageInput.files = files;
                        handleImageSelect(); // Trigger the preview
                    }
                }
            }


          // --- User Profile Dropdown (already handled above, ensuring it's here) ---


    } // End of setupEventListeners


    // =======================================================
    // Handlers for Dynamically Added Buttons
    // =======================================================

    // Handle clicks on View, Edit, Delete buttons using event delegation
    async function handleAnimalActionButtonClick(event) {
        const button = event.target.closest('button'); // Find the closest button ancestor
        if (!button) return; // Not a button click

        // Get the animal ID (Server ID) from the data-id attribute
        const animalId = parseInt(button.dataset.id);
        if (isNaN(animalId)) {
            console.error('Invalid animal ID from data attribute.');
            showErrorToast('Error: Could not find animal ID.');
            return;
        }

        if (button.classList.contains('view-animal')) {
            console.log('View Animal clicked for ID:', animalId);
            await showAnimalDetails(animalId); // Open and populate details modal
        } else if (button.classList.contains('edit-animal')) {
            console.log('Edit Animal clicked for ID:', animalId);
            await openEditModal(animalId); // Open and populate edit modal
        } else if (button.classList.contains('delete-animal')) {
            console.log('Delete Animal clicked for ID:', animalId);
            // Confirm deletion
            if (confirm('ඔබට මෙම Animal record එක මකා දැමීමට අවශ්‍ය බව තහවුරු කරන්න?')) {
                await deleteAnimal(animalId); // Delete the animal via API
                 // Success/error toast shown by deleteAnimal function
            }
        }
    }

     // Handle click on the "Add New Animal" button in empty table/card view states
     function handleEmptyAddClick() {
         const addAnimalBtn = document.getElementById('add-animal-btn');
         if (addAnimalBtn) {
              addAnimalBtn.click(); // Simulate click on the main Add New Animal button
         }
     }


    // =======================================================
    // Modal Functions (Open, Populate, Close)
    // =======================================================

    // Open the Add/Edit Animal Modal and populate for editing
    async function openEditModal(animalId) { // animalId is the server ID
        const animalModal = document.getElementById('animal-modal');
        const modalTitle = document.getElementById('modal-title');
        const animalForm = document.getElementById('animal-form');
         const animalIdField = document.getElementById('animal-id'); // Hidden input for server ID


        if (!animalModal || !modalTitle || !animalForm || !animalIdField) {
             console.error("Edit modal elements not found.");
             return;
        }

         // Show loading indicator in the modal if needed
         // modalTitle.textContent = 'Loading Animal Data...'; // Optional loading text


        try {
            // Fetch animal data from the server using its ID
            const animal = await fetchAnimalDetails(animalId); // This function now handles the DB column error and shows a toast

            if (animal) {
                modalTitle.textContent = 'Edit Animal';
                animalModal.classList.remove('hidden'); // Show the modal

                // Populate the form with animal data
                populateAnimalForm(animal);

                 // Store the server ID in the hidden field for form submission
                 animalIdField.value = animalId;

            } else {
                // fetchAnimalDetails already shows an error toast if fetch fails or animal not found
                 // showAlert('Animal record එක සොයාගැනීමට අසමත් විය.', 'error'); // Redundant
                 console.error("Animal data not found for editing.");
                 animalModal.classList.add('hidden'); // Hide modal if data fetch failed
            }
        } catch (error) {
            console.error('Error opening edit modal:', error);
            // Error handling is in fetchAnimalDetails
             animalModal.classList.add('hidden'); // Hide modal on error
        }
    }

     // Populate the Add/Edit Animal form with existing animal data
     function populateAnimalForm(animal) { // animal here is the data fetched from the server
         const animalIdTagInput = document.getElementById('animal-id-tag');
         const animalTypeSelect = document.getElementById('animal-type');
         const animalBreedInput = document.getElementById('animal-breed');
         const animalBirthDateInput = document.getElementById('animal-birth-date');
         const animalAgeYearsInput = document.getElementById('animal-age-years');
         const animalAgeMonthsInput = document.getElementById('animal-age-months');
         const animalWeightInput = document.getElementById('animal-weight');
         const weightUnitSelect = document.getElementById('weight-unit');
         const animalStatusSelect = document.getElementById('animal-status');
         const animalLocationInput = document.getElementById('animal-location');
         const animalNotesTextarea = document.getElementById('animal-notes');
          const previewImage = document.getElementById('preview-image');
          const imageUploadArea = document.getElementById('image-upload-area');
          const imagePreview = document.getElementById('image-preview');
          const animalImageInput = document.getElementById('animal-image'); // The file input itself
           // Add a hidden input to store the existing image URL during edit
           let existingImageInput = document.getElementById('existing-animal-image');
            if (!existingImageInput) {
                 existingImageInput = document.createElement('input');
                 existingImageInput.type = 'hidden';
                 existingImageInput.id = 'existing-animal-image';
                  // Append this hidden input to the form
                  document.getElementById('animal-form').appendChild(existingImageInput);
            }


         if (!animalIdTagInput || !animalTypeSelect || !animalBreedInput || !animalBirthDateInput || !animalAgeYearsInput || !animalAgeMonthsInput || !animalWeightInput || !weightUnitSelect || !animalStatusSelect || !animalLocationInput || !animalNotesTextarea || !previewImage || !imageUploadArea || !imagePreview || !animalImageInput || !existingImageInput) {
              console.error("One or more form elements not found for population.");
              // Ensure age fields are enabled if elements are missing
              if (animalAgeYearsInput) animalAgeYearsInput.disabled = false;
              if (animalAgeMonthsInput) animalAgeMonthsInput.disabled = false;
                if (animalAgeYearsInput) animalAgeYearsInput.classList.remove('bg-gray-200', 'cursor-not-allowed');
                if (animalAgeMonthsInput) animalAgeMonthsInput.classList.remove('bg-gray-200', 'cursor-not-allowed');
              return;
         }

         animalIdTagInput.value = animal.idTag || '';
         animalTypeSelect.value = animal.type || 'cattle'; // Default to cattle if null
         animalBreedInput.value = animal.breed || '';
         animalBirthDateInput.value = animal.birthDate ? formatDate(animal.birthDate) : ''; // Format date for input
         animalAgeYearsInput.value = animal.ageYears || '';
         animalAgeMonthsInput.value = animal.ageMonths || '';
         animalWeightInput.value = animal.weight || '';
         weightUnitSelect.value = animal.weightUnit || 'kg'; // Default to kg
         animalStatusSelect.value = animal.status || 'healthy'; // Default to healthy
         animalLocationInput.value = animal.location || '';
         animalNotesTextarea.value = animal.notes || '';

         // --- Handle Age Population based on Birth Date ---
            if (animal.birthDate) {
                // If a birth date exists, calculate age and set/disable age fields
                calculateAndSetAge(animal.birthDate);
            } else {
                // If no birth date, populate with stored age (if any) and enable age fields
                animalAgeYearsInput.value = animal.ageYears || '';
                animalAgeMonthsInput.value = animal.ageMonths || '';
                animalAgeYearsInput.disabled = false; // Ensure enabled
                animalAgeMonthsInput.disabled = false; // Ensure enabled
                animalAgeYearsInput.classList.remove('bg-gray-200', 'cursor-not-allowed');
                animalAgeMonthsInput.classList.remove('bg-gray-200', 'cursor-not-allowed');
            }

          // Handle image preview (assuming animal.image_url contains a URL from the server)
          if (animal.image_url) { // Assuming your server returns 'image_url'
               previewImage.src = animal.image_url;
               imageUploadArea.classList.add('hidden');
               imagePreview.classList.remove('hidden');
                // Store the existing image URL in the hidden field
                existingImageInput.value = animal.image_url;
                // Clear the file input value when populating, so user has to re-select to change
                animalImageInput.value = ''; // Clear the selected file
          } else {
               previewImage.src = '#';
               imageUploadArea.classList.remove('hidden');
               imagePreview.classList.add('hidden');
                animalImageInput.value = ''; // Clear the file input
                existingImageInput.value = ''; // Clear the existing image URL field
          }
     }


    // Show the details modal for a specific animal
    async function showAnimalDetails(animalId) {
        console.log('Attempting to show details for animal ID:', animalId); // Log the ID being requested
        // Fetch animal details from the server (this includes activities)
        const animal = await fetchAnimalDetails(animalId); // Call the fetch function

        // Get modal elements - Check for existence
        const animalDetailsModal = document.getElementById('animal-details-modal');
        const detailsIdTag = document.getElementById('details-id-tag');
        const detailsType = document.getElementById('details-type');
        const detailsBreed = document.getElementById('details-breed');
        const detailsAge = document.getElementById('details-age');
        const detailsWeight = document.getElementById('details-weight');
        const detailsLocation = document.getElementById('details-location');
        const detailsStatus = document.getElementById('details-status');
        const detailsNotes = document.getElementById('details-notes');
        const activitiesList = document.getElementById('activities-list'); // Ul element for activities
        const editDetailsBtn = document.getElementById('edit-details-btn'); // Edit button inside details modal
        const detailsAnimalImage = document.getElementById('details-animal-image'); // Image element in details modal
        const animalDetailsBirthDate = document.getElementById('animal-details-birth-date');

        // Ensure essential elements are found before proceeding
         if (!animalDetailsModal || !detailsIdTag || !detailsType || !detailsBreed || !detailsAge || !detailsWeight || !detailsLocation || !detailsStatus || !detailsNotes || !activitiesList || !editDetailsBtn || !detailsAnimalImage || !animalDetailsBirthDate) {
              console.error("One or more essential elements for Animal Details Modal not found.");
               showErrorToast("Modal elements Load කිරීමේදී දෝෂයක්.");
              return; // Stop execution if essential elements are missing
         }


        if (animal) {
            // Populate Animal Details Modal with data received from fetchAnimalDetails
            detailsIdTag.textContent = animal.idTag || 'N/A';
            detailsType.textContent = animal.type || '-';
            detailsBreed.textContent = animal.breed || 'N/A';
            detailsAge.textContent = formatAge(animal.ageYears, animal.ageMonths);
            detailsWeight.textContent = animal.weight ? `${animal.weight} ${animal.weightUnit || 'kg'}` : '-';
            detailsLocation.textContent = animal.location || 'N/A';
            detailsStatus.textContent = getStatusText(animal.status);
            detailsNotes.textContent = animal.notes || 'No notes';


            // --- ADDED: Populate Birth Date ---
             // Check if birthDate exists and is not empty, then format or display as is
             if (animal.birthDate && animal.birthDate !== '0000-00-00') { // Check for valid date value
                  // Assuming animal.birthDate is in 'YYYY-MM-DD' format
                  // You might want to format this date for better readability, e.g., 'YYYY-MM-DD' to 'DD/MM/YYYY'
                  // If you have a formatDate function, use it: formatDate(animal.birthDate)
                  animalDetailsBirthDate.textContent = animal.birthDate; // Display the date as is
             } else {
                  animalDetailsBirthDate.textContent = '-'; // Show dash if birthDate is missing or invalid
             }

             // Set the Edit button's data-id to the server ID
             editDetailsBtn.dataset.id = animal.id; // Set server ID for editing


             // Handle Image Display in Details Modal
             detailsAnimalImage.src = animal.image_url || `https://source.unsplash.com/random/600x400/?${encodeURIComponent(animal.type || 'animal')}`; // Use stored image URL or default placeholder
             detailsAnimalImage.alt = animal.type || 'Animal Image'; // Set alt text


            // Populate Activities
            // Check if activities array exists and has items
            if (animal.activities && animal.activities.length > 0) {
                 activitiesList.innerHTML = animal.activities.map(activity => `
                     <li class="py-3 sm:py-4">
                         <div class="flex items-center space-x-4">
                             <div class="flex-shrink-0">
                                  <div class="p-2 rounded-full ${getActivityIconColor(activity.type)}">
                                      <i class="${getActivityIcon(activity.type)} text-lg"></i>
                                 </div>
                             </div>
                             <div class="flex-1 min-w-0">
                                 <p class="text-sm font-medium text-gray-900 truncate capitalize">
                                     ${activity.type || 'Activity'}
                                 </p>
                                 <p class="text-sm text-gray-500 truncate">
                                     ${activity.notes || 'No notes'}
                                 </p>
                             </div>
                             <div class="inline-flex items-center text-base font-semibold text-gray-900">
                                 ${activity.timestamp ? formatDateTime(activity.timestamp) : '-'}
                             </div>
                         </div>
                     </li>
                 `).join('');
            } else {
                 // If no activities or activities array is empty/missing, show 'No records' message
                 activitiesList.innerHTML = '<li class="py-3 sm:py-4 text-center text-gray-500">No recent activities recorded.</li>';
            }


            // Show the modal
            animalDetailsModal.classList.remove('hidden');


        } else {
            // Handle case where animal is not found (fetchAnimalDetails returned null)
            console.log('Animal data not found for details for ID:', animalId);
            // showAnimalDetails already displays a toast message via fetchAnimalDetails's error handling
             // Ensure the modal is hidden if data fetch failed after a click
              if (animalDetailsModal) {
                  animalDetailsModal.classList.add('hidden');
              }
        }
    }

     // Render animal activities in the details modal (assuming activities are stored in the database)
     function renderAnimalActivities(activities, containerElement) {
          if (!containerElement) return;

          if (!activities || activities.length === 0) {
              containerElement.innerHTML = `
                  <div class="text-center py-4 text-sm text-gray-500">No recent activities recorded.</div>
              `;
              return;
          }

          containerElement.innerHTML = activities.map(activity => `
              <div class="flex items-start activity-item p-3 rounded-md hover:bg-gray-100">
                   <div class="flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center text-white ${getActivityIconColor(activity.type).replace('text-', 'bg-').split(' ')[0]}"> <i class="${getActivityIcon(activity.type)} text-sm"></i> </div>
                   <div class="ml-3 flex-1">
                       <p class="text-sm font-medium text-gray-900">${activity.type ? activity.type.charAt(0).toUpperCase() + activity.type.slice(1) : 'Activity'}</p>
                       <p class="text-sm text-gray-600">${activity.notes || 'No notes'}</p>
                       <p class="text-xs text-gray-500 mt-1">${formatDateTime(activity.timestamp)}</p>
                   </div>
                   </div>
          `).join('');
     }


    // Handle image file selection and preview
     function handleImageSelect(event) {
         const fileInput = event ? event.target : document.getElementById('animal-image'); // Get input from event or by ID
         const file = fileInput.files[0];
         const previewImage = document.getElementById('preview-image');
         const imageUploadArea = document.getElementById('image-upload-area');
         const imagePreview = document.getElementById('image-preview');
          const existingImageInput = document.getElementById('existing-animal-image'); // Assume this exists


         if (!fileInput || !previewImage || !imageUploadArea || !imagePreview || !existingImageInput) {
             console.error("Image preview elements not found.");
              if(fileInput) fileInput.value = ''; // Clear selected file if elements are missing
             return;
         }

         if (file) {
             // Basic file type and size validation before reading
             const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
             const maxSize = 5 * 1024 * 1024; // 5MB

             if (!allowedTypes.includes(file.type)) {
                 showAlert('Invalid file type. Only JPG, PNG, and GIF are allowed.', 'warning');
                 fileInput.value = ''; // Clear the selected file
                 handleRemoveImage(); // Reset preview
                 return;
             }

              if (file.size > maxSize) {
                  showAlert('File size exceeds the maximum limit (5MB).', 'warning');
                  fileInput.value = ''; // Clear the selected file
                  handleRemoveImage(); // Reset preview
                  return;
              }

             const reader = new FileReader();
             reader.onload = function(e) {
                 previewImage.src = e.target.result;
                 imageUploadArea.classList.add('hidden');
                 imagePreview.classList.remove('hidden');
                 // Clear the existing image URL if a new file is selected
                 existingImageInput.value = '';
             }
             reader.onerror = function(e) {
                  console.error("FileReader error:", e);
                  showAlert('Error reading the image file.', 'error');
                  fileInput.value = ''; // Clear the selected file on error
                  handleRemoveImage(); // Reset preview
             }
             reader.readAsDataURL(file); // Read the file as a data URL for preview
         } else {
             // No file selected, clear preview unless there's an existing image URL
              if (!existingImageInput.value) {
                 handleRemoveImage(); // Use the remove function to reset if no existing image
              }
               // If there is an existing image URL but no new file is selected, do nothing (keep the existing preview)
         }
     }

     // Handle image removal from preview
     function handleRemoveImage() {
         const previewImage = document.getElementById('preview-image');
         const imageUploadArea = document.getElementById('image-upload-area');
         const imagePreview = document.getElementById('image-preview');
          const animalImageInput = document.getElementById('animal-image'); // The file input itself
          const existingImageInput = document.getElementById('existing-animal-image'); // Assume this exists


         if (!previewImage || !imageUploadArea || !imagePreview || !animalImageInput || !existingImageInput) {
              console.error("Image preview elements not found for removal.");
              return;
         }

         previewImage.src = '#'; // Clear the image source
         imageUploadArea.classList.remove('hidden'); // Show the upload area
         imagePreview.classList.add('hidden'); // Hide the preview area
          animalImageInput.value = ''; // Clear the selected file from the input
          existingImageInput.value = ''; // Clear the existing image URL field
     }


    // =======================================================
    // Application Initialization and Entry Point
    // =======================================================

    // Initialize the application when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', async function() {
        console.log('DOM fully loaded. Initializing application...');
        try {
            // 1. Setup UI event listeners
            setupEventListeners();

            // 2. Initialize the type chart
            initTypeChart();

            // 3. Load initial data from Server API and render UI
            await loadAnimals();


            console.log('Application initialization complete.');

        } catch (error) {
            console.error('Application Initialization error:', error);
            showAlert('Application ආරම්භ කිරීමේදී දෝෂයක් සිදුවිය: ' + error.message, 'error');
            // Attempt to reload the page after a delay if initialization fails
            setTimeout(() => {
                window.location.reload();
            }, 5000); // 5 seconds delay before reload
        }
    });

</script>
</body>
</html>