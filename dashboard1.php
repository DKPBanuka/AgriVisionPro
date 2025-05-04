<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// අනෙකුත් අන්තර්ගතය
$pageTitle = "Dashboard";
include 'includes/header.php';

?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriVision Pro | Modern Farm Management</title>
    <link href="./dist/output.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.8.0/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/p5@1.4.0/lib/p5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    
    <!-- Three.js -->
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
        #farm-visualization {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
    </style>
</head>
<body class="h-full overflow-hidden">
    <!-- App Container -->
    <div class="flex h-full">
        <!-- Dynamic Sidebar -->
        <aside id="sidebar" class="w-64 bg-gradient-to-b from-blue-900 to-blue-800 text-white shadow-xl">
            <div class="p-4 flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold">AgriVision Pro</h1>
            </div>
            
            <nav class="mt-8">
                <div class="px-4 space-y-1">
                    <a href="index.php" class="flex items-center px-4 py-3 rounded-lg bg-blue-500 bg-opacity-30 text-white-100 hover:text-white group">
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
                            <input class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search..." type="search">
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
                                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">JD</div>
                                <svg class="h-5 w-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            
                            <div id="user-menu-dropdown" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-1 z-50">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                                <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <!-- Content will be loaded dynamically based on the page -->
                <div id="content-area">
                    <!-- Dashboard content will be loaded here by default -->
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Farm Dashboard</h2>
                        <div class="flex space-x-3">
                            <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Record
                            </button>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                        <!-- Active Crops -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Active Crops</dt>
                                            <dd class="flex items-baseline">
                                                <div class="text-2xl font-semibold text-gray-900" id="active-crops-count">0</div>
                                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                                    <svg class="self-center flex-shrink-0 h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="sr-only">Increased by</span>
                                                    12%
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-5 py-3">
                                <div class="text-sm">
                                    <a href="crops.php" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                                </div>
                            </div>
                        </div>

                        <!-- Livestock Count -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Livestock Count</dt>
                                            <dd class="flex items-baseline">
                                                <div class="text-2xl font-semibold text-gray-900" id="livestock-count">0</div>
                                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                                    <svg class="self-center flex-shrink-0 h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="sr-only">Increased by</span>
                                                    8%
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-5 py-3">
                                <div class="text-sm">
                                    <a href="livestock.php" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Tasks -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Pending Tasks</dt>
                                            <dd class="flex items-baseline">
                                                <div class="text-2xl font-semibold text-gray-900" id="pending-tasks-count">0</div>
                                                <div class="ml-2 flex items-baseline text-sm font-semibold text-red-600">
                                                    <svg class="self-center flex-shrink-0 h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="sr-only">Increased by</span>
                                                    2 overdue
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-5 py-3">
                                <div class="text-sm">
                                    <a href="tasks.php" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                                </div>
                            </div>
                        </div>

                        <!-- Low Inventory -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">Low Inventory</dt>
                                            <dd class="flex items-baseline">
                                                <div class="text-2xl font-semibold text-gray-900" id="low-inventory-count">0</div>
                                                <div class="ml-2 flex items-baseline text-sm font-semibold text-red-600">
                                                    <svg class="self-center flex-shrink-0 h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="sr-only">Increased by</span>
                                                    Needs attention
                                                </div>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-5 py-3">
                                <div class="text-sm">
                                    <a href="inventory.php" class="font-medium text-blue-700 hover:text-blue-900">View all</a>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Charts and Data -->
                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <!-- Crop Yield Forecast -->
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-200">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Crop Yield Forecast</h3>
                                <p class="mt-1 text-sm text-gray-500">Predicted yields for current crops</p>
                            </div>
                            <div class="p-4">
                                <canvas id="yieldChart" class="w-full h-64"></canvas>
                            </div>
                        </div>

                        <!-- Task Completion -->
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-200">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Task Completion</h3>
                                <p class="mt-1 text-sm text-gray-500">Weekly task completion rate</p>
                            </div>
                            <div class="p-4">
                                <canvas id="taskChart" class="w-full h-64"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="mt-6">
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-200">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">Recent Activity</h3>
                                <p class="mt-1 text-sm text-gray-500">Latest farm operations and events</p>
                            </div>
                            <div class="divide-y divide-gray-200" id="recent-activity">
                                <!-- Recent activities will be loaded here -->
                            </div>
                            <div class="bg-gray-50 px-6 py-4">
                                <div class="text-sm">
                                    <a href="#" class="font-medium text-blue-700 hover:text-blue-900">View all activity</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

   

    <!-- All Modals -->
    <!-- Add/Edit Crop Modal -->
    <div id="crop-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <h3 id="modal-title" class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Crop</h3>
                    <form id="crop-form">
                        <input type="hidden" id="crop-id">
                        <div class="mb-4">
                            <label for="crop-name" class="block text-sm font-medium text-gray-700">Crop Name</label>
                            <input type="text" id="crop-name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="mb-4">
                            <label for="crop-type" class="block text-sm font-medium text-gray-700">Crop Type</label>
                            <select id="crop-type" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="Vegetable">Vegetable</option>
                                <option value="Fruit">Fruit</option>
                                <option value="Grain">Grain</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="crop-area" class="block text-sm font-medium text-gray-700">Area (acres)</label>
                            <input type="number" id="crop-area" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="mb-4">
                            <label for="crop-planted-date" class="block text-sm font-medium text-gray-700">Planted Date</label>
                            <input type="date" id="crop-planted-date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </form>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="button" id="save-crop" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">
                        Save
                    </button>
                    <button type="button" id="cancel-crop" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Database configuration
        const DB_NAME = 'AgriVisionProDB';
        const DB_VERSION = 2; // Incremented to fix version change issues
        const STORES = {
            CROPS: 'crops',
            LIVESTOCK: 'livestock',
            INVENTORY: 'inventory',
            TASKS: 'tasks',
            ACTIVITY: 'activity'
        };

        let db;
        let dbInitialized = false;
        
        // Initialize the application
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                // Initialize database
                await initDB();
                
                // Setup UI interactions
                setupUI();
                
                // Load dashboard data
                loadDashboardData();
                
                // Initialize 3D visualization if on dashboard
                if (document.getElementById('farm-visualization')) {
                    initFarmVisualization();
                }
                
                // Initialize charts if they exist
                if (document.getElementById('yieldChart')) {
                    initCharts();
                }
                
            } catch (error) {
                console.error('Initialization error:', error);
                showErrorToast('Failed to initialize application');
            }
        });

        function initFarmVisualization() {
    const container = document.getElementById('farm-visualization');
    if (!container || !window.THREE) {
        console.error('Three.js not loaded or container not found');
        return;
    }

    try {
        // Set up Three.js scene
        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0xf0fdf4);
        
        const camera = new THREE.PerspectiveCamera(
            75, 
            container.clientWidth / container.clientHeight, 
            0.1, 
            1000
        );
        
        const renderer = new THREE.WebGLRenderer({ 
            antialias: true, 
            alpha: true 
        });
        
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.shadowMap.enabled = true;
        container.innerHTML = '';
        container.appendChild(renderer.domElement);
        
        // Add lights
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        scene.add(ambientLight);
        
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(200, 500, 300);
        directionalLight.castShadow = true;
        directionalLight.shadow.mapSize.width = 1024;
        directionalLight.shadow.mapSize.height = 1024;
        scene.add(directionalLight);
        
        // Create farm ground
        const groundGeometry = new THREE.PlaneGeometry(100, 100);
        const groundMaterial = new THREE.MeshStandardMaterial({ 
            color: 0x8B4513,
            roughness: 0.8,
            metalness: 0.2
        });
        const ground = new THREE.Mesh(groundGeometry, groundMaterial);
        ground.rotation.x = -Math.PI / 2;
        ground.receiveShadow = true;
        scene.add(ground);
        
        // Add fields and crops
        addFieldsAndCrops(scene);
        
        // Add barn and animals
        addBarnAndAnimals(scene);
        
        // Position camera
        camera.position.set(0, 50, 50);
        camera.lookAt(0, 0, 0);
        
        // Initialize OrbitControls
        let controls;
        if (typeof THREE.OrbitControls !== 'undefined') {
            controls = new THREE.OrbitControls(camera, renderer.domElement);
        } else if (typeof THREE.OrbitControls === 'undefined' && typeof THREE !== 'undefined') {
            // Fallback to basic controls if OrbitControls not available
            console.warn('OrbitControls not found, using basic mouse controls');
            controls = {
                update: function() {
                    // Simple rotation based on mouse movement
                    camera.position.x = 50 * Math.sin(Date.now() * 0.0005);
                    camera.position.z = 50 * Math.cos(Date.now() * 0.0005);
                    camera.lookAt(0, 0, 0);
                }
            };
        }
        
        if (controls) {
            controls.enableDamping = true;
            controls.dampingFactor = 0.25;
            controls.minDistance = 20;
            controls.maxDistance = 100;
        }
        
        // Animation loop
        function animate() {
            requestAnimationFrame(animate);
            if (controls) controls.update();
            renderer.render(scene, camera);
        }
        
        animate();
        
        // Handle window resize
        function handleResize() {
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        }
        
        window.addEventListener('resize', handleResize);
        
        // Cleanup
        return function cleanup() {
            window.removeEventListener('resize', handleResize);
            if (container.contains(renderer.domElement)) {
                container.removeChild(renderer.domElement);
            }
        };
        
    } catch (error) {
        console.error('Error initializing 3D visualization:', error);
        container.innerHTML = `
            <div class="flex items-center justify-center h-full">
                <div class="text-center p-4 bg-yellow-50 border-l-4 border-yellow-400">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                3D visualization could not be loaded. 
                                <a href="#" onclick="location.reload()" class="font-medium text-yellow-700 underline hover:text-yellow-600">Try reloading</a> 
                                or check your browser settings.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
}

// Helper function to add fields and crops
function addFieldsAndCrops(scene) {
    const fieldColors = [0x7CFC00, 0x32CD32, 0x228B22, 0x006400];
    
    for (let i = 0; i < 4; i++) {
        const fieldGeometry = new THREE.BoxGeometry(15, 0.5, 15);
        const fieldMaterial = new THREE.MeshStandardMaterial({ 
            color: fieldColors[i],
            roughness: 0.7,
            metalness: 0.1
        });
        const field = new THREE.Mesh(fieldGeometry, fieldMaterial);
        field.position.set(
            Math.random() * 60 - 30,
            0.25,
            Math.random() * 60 - 30
        );
        field.receiveShadow = true;
        scene.add(field);
        
        // Add crops to each field
        const cropGeometry = new THREE.ConeGeometry(0.5, 2, 4);
        const cropMaterial = new THREE.MeshStandardMaterial({ color: 0x2E8B57 });
        for (let j = 0; j < 5; j++) {
            const crop = new THREE.Mesh(cropGeometry, cropMaterial);
            crop.position.set(
                field.position.x + Math.random() * 10 - 5,
                1.5,
                field.position.z + Math.random() * 10 - 5
            );
            crop.castShadow = true;
            scene.add(crop);
        }
    }
}

// Helper function to add barn and animals
function addBarnAndAnimals(scene) {
    // Add barn
    const barnGeometry = new THREE.BoxGeometry(10, 5, 8);
    const barnMaterial = new THREE.MeshStandardMaterial({ 
        color: 0xA0522D,
        roughness: 0.5
    });
    const barn = new THREE.Mesh(barnGeometry, barnMaterial);
    barn.position.set(20, 2.5, 20);
    barn.castShadow = true;
    scene.add(barn);
    
    // Add roof to barn
    const roofGeometry = new THREE.ConeGeometry(6, 4, 4);
    const roofMaterial = new THREE.MeshStandardMaterial({ color: 0x8B0000 });
    const roof = new THREE.Mesh(roofGeometry, roofMaterial);
    roof.position.set(20, 7, 20);
    roof.rotation.y = Math.PI / 4;
    roof.castShadow = true;
    scene.add(roof);
    
    // Add animals
    const animalGeometry = new THREE.SphereGeometry(0.5, 16, 16);
    const animalMaterial = new THREE.MeshStandardMaterial({ color: 0xFFFFFF });
    for (let i = 0; i < 10; i++) {
        const animal = new THREE.Mesh(animalGeometry, animalMaterial);
        animal.position.set(
            15 + Math.random() * 10,
            0.5,
            15 + Math.random() * 10
        );
        animal.castShadow = true;
        scene.add(animal);
    }
}

        // Initialize IndexedDB with proper version handling
        function initDB() {
            return new Promise((resolve, reject) => {
                if (dbInitialized && db) {
                    resolve(db);
                    return;
                }

                const request = indexedDB.open(DB_NAME, DB_VERSION);
                
                request.onerror = (event) => {
                    console.error('Database error:', event.target.error);
                    reject(event.target.error);
                };
                
                request.onblocked = (event) => {
                    console.error('Database blocked:', event);
                    reject('Database blocked - please close other tabs using this database');
                };
                
                request.onsuccess = (event) => {
                    db = event.target.result;
                    dbInitialized = true;
                    
                    // Add version change listener
                    db.onversionchange = (event) => {
                        db.close();
                        console.log('Database closed due to version change');
                    };
                    
                    console.log('Database initialized successfully');
                    resolve(db);
                };
                
                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    console.log(`Database upgrade needed from ${event.oldVersion} to ${event.newVersion}`);
                    
                    // Create object stores if they don't exist
                    if (!db.objectStoreNames.contains(STORES.CROPS)) {
                        const cropsStore = db.createObjectStore(STORES.CROPS, { keyPath: 'id', autoIncrement: true });
                        cropsStore.createIndex('name', 'name', { unique: false });
                        cropsStore.createIndex('type', 'type', { unique: false });
                        cropsStore.createIndex('area', 'area', { unique: false });
                        cropsStore.createIndex('plantedDate', 'plantedDate', { unique: false });
                    }
                    
                    if (!db.objectStoreNames.contains(STORES.LIVESTOCK)) {
                        const livestockStore = db.createObjectStore(STORES.LIVESTOCK, { keyPath: 'id', autoIncrement: true });
                        livestockStore.createIndex('type', 'type', { unique: false });
                        livestockStore.createIndex('count', 'count', { unique: false });
                        livestockStore.createIndex('location', 'location', { unique: false });
                    }
                    
                    if (!db.objectStoreNames.contains(STORES.INVENTORY)) {
                        const inventoryStore = db.createObjectStore(STORES.INVENTORY, { keyPath: 'id', autoIncrement: true });
                        inventoryStore.createIndex('name', 'name', { unique: false });
                        inventoryStore.createIndex('quantity', 'quantity', { unique: false });
                        inventoryStore.createIndex('threshold', 'threshold', { unique: false });
                    }
                    
                    if (!db.objectStoreNames.contains(STORES.TASKS)) {
                        const tasksStore = db.createObjectStore(STORES.TASKS, { keyPath: 'id', autoIncrement: true });
                        tasksStore.createIndex('title', 'title', { unique: false });
                        tasksStore.createIndex('dueDate', 'dueDate', { unique: false });
                        tasksStore.createIndex('status', 'status', { unique: false });
                        tasksStore.createIndex('priority', 'priority', { unique: false });
                    }
                    
                    if (!db.objectStoreNames.contains(STORES.ACTIVITY)) {
                        const activityStore = db.createObjectStore(STORES.ACTIVITY, { keyPath: 'id', autoIncrement: true });
                        activityStore.createIndex('type', 'type', { unique: false });
                        activityStore.createIndex('date', 'date', { unique: false });
                        activityStore.createIndex('user', 'user', { unique: false });
                    }
                    
                    // Add sample data only if this is a new database
                    if (event.oldVersion < 1) {
                        addSampleData(db);
                    }
                };
            });
        }
        
        // Add sample data to the database
        function addSampleData(db) {
            return new Promise((resolve, reject) => {
                // Wait for any existing transactions to complete
                setTimeout(() => {
                    try {
                        // Sample crops
                        const crops = [
                            { name: 'Rice Field A', type: 'Grain', area: 5.2, plantedDate: '2023-06-15' },
                            { name: 'Corn Field B', type: 'Grain', area: 3.8, plantedDate: '2023-06-20' },
                            { name: 'Tomato Greenhouse', type: 'Vegetable', area: 0.5, plantedDate: '2023-07-01' }
                        ];
                        
                        const cropsTx = db.transaction([STORES.CROPS], 'readwrite');
                        const cropsStore = cropsTx.objectStore(STORES.CROPS);
                        crops.forEach(crop => cropsStore.add(crop));
                        
                        // Sample livestock
                        const livestock = [
                            { type: 'Cattle', count: 42, location: 'North Pasture' },
                            { type: 'Chickens', count: 120, location: 'Coop A' },
                            { type: 'Goats', count: 15, location: 'East Pasture' }
                        ];
                        
                        const livestockTx = db.transaction([STORES.LIVESTOCK], 'readwrite');
                        const livestockStore = livestockTx.objectStore(STORES.LIVESTOCK);
                        livestock.forEach(animal => livestockStore.add(animal));
                        
                        // Sample inventory
                        const inventory = [
                            { name: 'Fertilizer', quantity: 15, threshold: 5, unit: 'bags' },
                            { name: 'Seeds', quantity: 8, threshold: 10, unit: 'kg' },
                            { name: 'Animal Feed', quantity: 25, threshold: 15, unit: 'bags' }
                        ];
                        
                        const inventoryTx = db.transaction([STORES.INVENTORY], 'readwrite');
                        const inventoryStore = inventoryTx.objectStore(STORES.INVENTORY);
                        inventory.forEach(item => inventoryStore.add(item));
                        
                        // Sample tasks
                        const tasks = [
                            { title: 'Fertilize Rice Field', dueDate: '2023-07-20', status: 'pending', priority: 'high' },
                            { title: 'Harvest Tomatoes', dueDate: '2023-07-25', status: 'pending', priority: 'medium' },
                            { title: 'Vaccinate Cattle', dueDate: '2023-07-15', status: 'completed', priority: 'high' }
                        ];
                        
                        const tasksTx = db.transaction([STORES.TASKS], 'readwrite');
                        const tasksStore = tasksTx.objectStore(STORES.TASKS);
                        tasks.forEach(task => tasksStore.add(task));
                        
                        // Sample activity
                        const activity = [
                            { type: 'crop_added', description: 'Added new crop: Rice Field A', date: '2023-07-10', user: 'John Doe' },
                            { type: 'task_completed', description: 'Completed task: Vaccinate Cattle', date: '2023-07-12', user: 'Jane Smith' },
                            { type: 'inventory_updated', description: 'Updated inventory: Fertilizer', date: '2023-07-08', user: 'John Doe' }
                        ];
                        
                        const activityTx = db.transaction([STORES.ACTIVITY], 'readwrite');
                        const activityStore = activityTx.objectStore(STORES.ACTIVITY);
                        activity.forEach(act => activityStore.add(act));
                        
                        resolve();
                    } catch (error) {
                        console.error('Error adding sample data:', error);
                        reject(error);
                    }
                }, 100); // Small delay to ensure previous transactions complete
            });
        }
        
        // Setup UI interactions
        function setupUI() {
            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('hidden');
                    sidebar.classList.toggle('block');
                });
            }
            
            // User menu dropdown
            const userMenu = document.getElementById('user-menu');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');
            
            if (userMenu && userMenuDropdown) {
                userMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenuDropdown.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    userMenuDropdown.classList.add('hidden');
                });
            }
            
            // Highlight current page in sidebar
            const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
            document.querySelectorAll('aside a').forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('bg-gray-700', 'text-white');
                    link.classList.remove('text-gray-300');
                }
            });
        }
        
        // Load dashboard data
        async function loadDashboardData() {
            try {
                // Ensure database is ready
                if (!dbInitialized) {
                    await initDB();
                }
                
                // Get counts for dashboard cards
                const [cropsCount, livestockCount, pendingTasksCount, lowInventoryCount] = await Promise.all([
                    getCount(STORES.CROPS),
                    getLivestockTotalCount(),
                    getCountByIndex(STORES.TASKS, 'status', 'pending'),
                    getLowInventoryCount()
                ]);
                
                // Get overdue tasks count
                const overdueTasksCount = await getOverdueTasksCount();
                
                // Update dashboard cards
                document.getElementById('active-crops-count').textContent = cropsCount;
                document.getElementById('livestock-count').textContent = livestockCount;
                document.getElementById('pending-tasks-count').textContent = pendingTasksCount;
                document.getElementById('low-inventory-count').textContent = lowInventoryCount;
                
                // Update the status indicators
                updateStatusIndicator('active-crops-status', '↑ 12%', 'green');
                updateStatusIndicator('livestock-status', '↑ 8%', 'green');
                
                if (overdueTasksCount > 0) {
                    updateStatusIndicator('pending-tasks-status', `${overdueTasksCount} ↓ overdue`, 'red');
                } else {
                    updateStatusIndicator('pending-tasks-status', 'On track', 'green');
                }
                
                if (lowInventoryCount > 0) {
                    updateStatusIndicator('inventory-status', `${lowInventoryCount} need attention`, 'red');
                } else {
                    updateStatusIndicator('inventory-status', 'All good', 'green');
                }
                
                // Load recent activity
                await loadRecentActivity();
                
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                showErrorToast('Failed to load dashboard data');
            }
        }
        
        // Helper function to update status indicators
        function updateStatusIndicator(elementId, text, color) {
            const element = document.getElementById(elementId);
            if (element) {
                // Clear existing content
                element.innerHTML = '';
                
                // Create icon based on color
                let iconClass;
                if (color === 'green') {
                    iconClass = 'fas fa-arrow-up';
                } else if (color === 'red') {
                    iconClass = 'fas fa-exclamation-circle';
                } else {
                    iconClass = 'fas fa-info-circle';
                }
                
                // Create new content
                element.innerHTML = `
                    <i class="${iconClass} mr-1"></i> ${text}
                `;
                
                // Update classes
                element.className = `mt-1 text-sm flex items-center font-semibold text-${color}-600`;
            }
        }
        
        // Get count of overdue tasks
        function getOverdueTasksCount() {
            return new Promise((resolve, reject) => {
                if (!db) {
                    reject('Database not initialized');
                    return;
                }
                
                const transaction = db.transaction([STORES.TASKS], 'readonly');
                const store = transaction.objectStore(STORES.TASKS);
                const request = store.getAll();
                
                request.onsuccess = () => {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    const overdueTasks = request.result.filter(task => {
                        if (task.status === 'completed') return false;
                        if (!task.dueDate) return false;
                        
                        const dueDate = new Date(task.dueDate);
                        return dueDate < today;
                    });
                    
                    resolve(overdueTasks.length);
                };
                
                request.onerror = (event) => {
                    console.error('Error getting overdue tasks:', event.target.error);
                    reject(event.target.error);
                };
            });
        }
        
        // Get total count from an object store
        function getCount(storeName) {
            return new Promise((resolve, reject) => {
                if (!db) {
                    reject('Database not initialized');
                    return;
                }
                
                const transaction = db.transaction([storeName], 'readonly');
                const store = transaction.objectStore(storeName);
                const request = store.count();
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = (event) => {
                    console.error(`Error counting ${storeName}:`, event.target.error);
                    reject(event.target.error);
                };
            });
        }
        
        // Get total livestock count
        function getLivestockTotalCount() {
            return new Promise((resolve, reject) => {
                if (!db) {
                    reject('Database not initialized');
                    return;
                }
                
                const transaction = db.transaction([STORES.LIVESTOCK], 'readonly');
                const store = transaction.objectStore(STORES.LIVESTOCK);
                const request = store.getAll();
                
                request.onsuccess = () => {
                    const total = request.result.reduce((sum, animal) => sum + (animal.count || 0), 0);
                    resolve(total);
                };
                
                request.onerror = (event) => {
                    console.error('Error getting livestock count:', event.target.error);
                    reject(event.target.error);
                };
            });
        }
        
        // Get count by index
        function getCountByIndex(storeName, indexName, value) {
            return new Promise((resolve, reject) => {
                if (!db) {
                    reject('Database not initialized');
                    return;
                }
                
                const transaction = db.transaction([storeName], 'readonly');
                const store = transaction.objectStore(storeName);
                const index = store.index(indexName);
                const request = index.count(value);
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = (event) => {
                    console.error(`Error counting ${indexName}=${value}:`, event.target.error);
                    reject(event.target.error);
                };
            });
        }
        
        // Get low inventory count (quantity < threshold)
        function getLowInventoryCount() {
            return new Promise((resolve, reject) => {
                if (!db) {
                    reject('Database not initialized');
                    return;
                }
                
                const transaction = db.transaction([STORES.INVENTORY], 'readonly');
                const store = transaction.objectStore(STORES.INVENTORY);
                const request = store.getAll();
                
                request.onsuccess = () => {
                    const lowItems = request.result.filter(item => 
                        item.quantity !== undefined && 
                        item.threshold !== undefined && 
                        item.quantity < item.threshold
                    );
                    resolve(lowItems.length);
                };
                
                request.onerror = (event) => {
                    console.error('Error getting low inventory:', event.target.error);
                    reject(event.target.error);
                };
            });
        }
        
        // Load recent activity
        async function loadRecentActivity() {
            try {
                if (!db) {
                    await initDB();
                }
                
                const activity = await getAll(STORES.ACTIVITY, 'date', 'prev');
                const activityList = document.getElementById('recent-activity');
                
                if (activityList) {
                    activityList.innerHTML = activity.slice(0, 5).map(act => `
                        <div class="px-6 py-4 border-b border-gray-200 last:border-0">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                        ${getInitials(act.user)}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${act.user || 'System'}</div>
                                    <div class="text-sm text-gray-500">${act.description || 'Activity recorded'}</div>
                                </div>
                                <div class="ml-auto text-sm text-gray-500">
                                    <time datetime="${act.date}">${formatDate(act.date)}</time>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading recent activity:', error);
            }
        }
        
        // Helper to get user initials
        function getInitials(name) {
            if (!name) return 'S';
            return name.split(' ').map(n => n[0]).join('').toUpperCase();
        }
        
        // Helper to format date
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString();
        }
        
        // Get all records from a store with optional sorting
        function getAll(storeName, indexName = null, direction = 'next') {
            return new Promise((resolve, reject) => {
                if (!db) {
                    reject('Database not initialized');
                    return;
                }
                
                const transaction = db.transaction([storeName], 'readonly');
                const store = transaction.objectStore(storeName);
                
                let request;
                if (indexName) {
                    const index = store.index(indexName);
                    request = index.getAll();
                } else {
                    request = store.getAll();
                }
                
                request.onsuccess = () => {
                    let results = request.result;
                    // Sort by date if needed
                    if (indexName === 'date') {
                        results.sort((a, b) => {
                            const dateA = new Date(a.date);
                            const dateB = new Date(b.date);
                            return direction === 'next' ? dateB - dateA : dateA - dateB;
                        });
                    }
                    resolve(results);
                };
                
                request.onerror = (event) => {
                    console.error(`Error getting ${storeName} data:`, event.target.error);
                    reject(event.target.error);
                };
            });
        }
        
        // Initialize charts
        function initCharts() {
            // Yield Chart
            const yieldCtx = document.getElementById('yieldChart');
            if (yieldCtx) {
                new Chart(yieldCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Rice', 'Corn', 'Wheat', 'Vegetables', 'Fruits'],
                        datasets: [{
                            label: 'Expected Yield (tons)',
                            data: [12, 19, 8, 5, 3],
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.7)',
                                'rgba(16, 185, 129, 0.7)',
                                'rgba(234, 179, 8, 0.7)',
                                'rgba(245, 158, 11, 0.7)',
                                'rgba(239, 68, 68, 0.7)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Task Chart
            const taskCtx = document.getElementById('taskChart');
            if (taskCtx) {
                new Chart(taskCtx, {
                    type: 'line',
                    data: {
                        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Current'],
                        datasets: [{
                            label: 'Task Completion %',
                            data: [65, 59, 80, 81, 92],
                            fill: false,
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
        }
        
        // Initialize 3D farm visualization
        function initFarmVisualization() {
            const container = document.getElementById('farm-visualization');
            if (!container || !window.THREE) return;
            
            try {
                // Set up Three.js scene
                const scene = new THREE.Scene();
                scene.background = new THREE.Color(0xf0fdf4);
                
                const camera = new THREE.PerspectiveCamera(
                    75, 
                    container.clientWidth / container.clientHeight, 
                    0.1, 
                    1000
                );
                
                const renderer = new THREE.WebGLRenderer({ 
                    antialias: true, 
                    alpha: true 
                });
                
                renderer.setSize(container.clientWidth, container.clientHeight);
                renderer.shadowMap.enabled = true;
                container.innerHTML = '';
                container.appendChild(renderer.domElement);
                
                // Add lights
                const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
                scene.add(ambientLight);
                
                const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
                directionalLight.position.set(200, 500, 300);
                directionalLight.castShadow = true;
                directionalLight.shadow.mapSize.width = 1024;
                directionalLight.shadow.mapSize.height = 1024;
                scene.add(directionalLight);
                
                // Create farm ground
                const groundGeometry = new THREE.PlaneGeometry(100, 100);
                const groundMaterial = new THREE.MeshStandardMaterial({ 
                    color: 0x8B4513,
                    roughness: 0.8,
                    metalness: 0.2
                });
                const ground = new THREE.Mesh(groundGeometry, groundMaterial);
                ground.rotation.x = -Math.PI / 2;
                ground.receiveShadow = true;
                scene.add(ground);
                
                // Add some fields
                const fieldColors = [0x7CFC00, 0x32CD32, 0x228B22, 0x006400];
                for (let i = 0; i < 4; i++) {
                    const fieldGeometry = new THREE.BoxGeometry(15, 0.5, 15);
                    const fieldMaterial = new THREE.MeshStandardMaterial({ 
                        color: fieldColors[i],
                        roughness: 0.7,
                        metalness: 0.1
                    });
                    const field = new THREE.Mesh(fieldGeometry, fieldMaterial);
                    field.position.set(
                        Math.random() * 60 - 30,
                        0.25,
                        Math.random() * 60 - 30
                    );
                    field.receiveShadow = true;
                    scene.add(field);
                    
                    // Add some crops
                    const cropGeometry = new THREE.ConeGeometry(0.5, 2, 4);
                    const cropMaterial = new THREE.MeshStandardMaterial({ color: 0x2E8B57 });
                    for (let j = 0; j < 5; j++) {
                        const crop = new THREE.Mesh(cropGeometry, cropMaterial);
                        crop.position.set(
                            field.position.x + Math.random() * 10 - 5,
                            1.5,
                            field.position.z + Math.random() * 10 - 5
                        );
                        crop.castShadow = true;
                        scene.add(crop);
                    }
                }
                
                // Add a barn
                const barnGeometry = new THREE.BoxGeometry(10, 5, 8);
                const barnMaterial = new THREE.MeshStandardMaterial({ 
                    color: 0xA0522D,
                    roughness: 0.5
                });
                const barn = new THREE.Mesh(barnGeometry, barnMaterial);
                barn.position.set(20, 2.5, 20);
                barn.castShadow = true;
                scene.add(barn);
                
                // Add roof to barn
                const roofGeometry = new THREE.ConeGeometry(6, 4, 4);
                const roofMaterial = new THREE.MeshStandardMaterial({ color: 0x8B0000 });
                const roof = new THREE.Mesh(roofGeometry, roofMaterial);
                roof.position.set(20, 7, 20);
                roof.rotation.y = Math.PI / 4;
                roof.castShadow = true;
                scene.add(roof);
                
                // Add some animals
                const animalGeometry = new THREE.SphereGeometry(0.5, 16, 16);
                const animalMaterial = new THREE.MeshStandardMaterial({ color: 0xFFFFFF });
                for (let i = 0; i < 10; i++) {
                    const animal = new THREE.Mesh(animalGeometry, animalMaterial);
                    animal.position.set(
                        15 + Math.random() * 10,
                        0.5,
                        15 + Math.random() * 10
                    );
                    animal.castShadow = true;
                    scene.add(animal);
                }
                
                // Position camera
                camera.position.set(0, 50, 50);
                camera.lookAt(0, 0, 0);
                
                // Add orbit controls
                const controls = new THREE.OrbitControls(camera, renderer.domElement);
                controls.enableDamping = true;
                controls.dampingFactor = 0.25;
                controls.minDistance = 20;
                controls.maxDistance = 100;
                
                // Animation loop
                function animate() {
                    requestAnimationFrame(animate);
                    controls.update();
                    renderer.render(scene, camera);
                }
                
                animate();
                
                // Handle window resize
                function handleResize() {
                    camera.aspect = container.clientWidth / container.clientHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(container.clientWidth, container.clientHeight);
                }
                
                window.addEventListener('resize', handleResize);
                
                // Cleanup on unmount
                return () => {
                    window.removeEventListener('resize', handleResize);
                    container.removeChild(renderer.domElement);
                };
                
            } catch (error) {
                console.error('Error initializing 3D visualization:', error);
                container.innerHTML = '<p class="text-center text-gray-500">Could not load 3D visualization</p>';
            }
        }
        
        // Show error toast
        function showErrorToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>