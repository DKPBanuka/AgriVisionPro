<?php
session_start(); // මෙය ඉහළින්ම යොදන්න
require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php';
checkAuthentication();
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriVision Pro | Settings</title>
    <link href="./dist/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .animation-delay-1 {
            animation-delay: 0.1s;
        }
        
        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.3s ease-in-out forwards;
        }
        
        .slide-up {
            animation: slideUp 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .toggle-bg {
            transition: background-color 0.2s ease;
        }
        
        .toggle-knob {
            transition: all 0.2s ease;
        }
        
        /* Keyframes */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Custom checkbox */
        .custom-checkbox {
            position: relative;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 0.25rem;
            border: 2px solid #D1D5DB;
            transition: all 0.2s ease;
        }
        
        .custom-checkbox:checked {
            background-color: #3B82F6;
            border-color: #3B82F6;
        }
        
        .custom-checkbox:checked::after {
            content: '';
            position: absolute;
            left: 0.25rem;
            top: 0.1rem;
            width: 0.4rem;
            height: 0.7rem;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
    </style>
</head>
<body class="h-full overflow-hidden">
    <!-- App Container -->
    <div class="flex h-full">
        <!-- Sidebar -->
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
                
                <div class="mt-4 pt-4 border-t border-blue-700"> <div class="px-3 space-y-0.5"> <a href="settings.php" class="flex items-center px-3 py-2 rounded-lg bg-blue-500 bg-opacity-30 text-m text-white-100 hover:text-white group"> <svg class="mr-2 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c-.94 1.543.826 3.31 2.37 2.37.996.608 2.296.07 2.572-1.065z" />
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
                            <input class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search settings..." type="search">
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
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-6 slide-up animation-delay-1">

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="mb-6 slide-up" style="animation-delay: 0.1s;">
                    <h2 class="text-2xl font-bold text-gray-800">Settings</h2>
                    <p class="text-sm text-gray-500">Manage your AgriVision Pro account and preferences</p>
                </div>
                
                <!-- Settings Tabs -->
                <div class="mb-6 slide-up" style="animation-delay: 0.2s;">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button id="account-tab" class="border-b-2 border-blue-500 text-blue-600 px-4 py-3 text-sm font-medium">Account</button>
                            <button id="notifications-tab" class="border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 px-4 py-3 text-sm font-medium">Notifications</button>
                            <button id="appearance-tab" class="border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 px-4 py-3 text-sm font-medium">Appearance</button>
                            <button id="privacy-tab" class="border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 px-4 py-3 text-sm font-medium">Privacy</button>
                        </nav>
                    </div>
                </div>
                
                <!-- Account Settings -->
                <div id="account-content" class="space-y-6">
                    <!-- Profile Card -->
                    <div class="card-hover bg-white p-6 rounded-lg shadow fade-in" style="animation-delay: 0.3s;">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-800">Profile Information</h3>
                            <button class="text-sm text-blue-600 hover:text-blue-800">Edit</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                <div class="text-sm text-gray-800">John</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                <div class="text-sm text-gray-800">Doe</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <div class="text-sm text-gray-800">john.doe@example.com</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <div class="text-sm text-gray-800">+1 (555) 123-4567</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password Card -->
                    <div class="card-hover bg-white p-6 rounded-lg shadow fade-in" style="animation-delay: 0.4s;">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-800">Password</h3>
                            <button class="text-sm text-blue-600 hover:text-blue-800">Change</button>
                        </div>
                        <div class="flex items-center">
                            <div class="mr-4">
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-800">Last changed 3 months ago</p>
                                <p class="text-xs text-gray-500">For security, we recommend changing your password regularly</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Farm Details Card -->
                    <div class="card-hover bg-white p-6 rounded-lg shadow fade-in" style="animation-delay: 0.5s;">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-800">Farm Details</h3>
                            <button class="text-sm text-blue-600 hover:text-blue-800">Edit</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Farm Name</label>
                                <div class="text-sm text-gray-800">Sunny Acres</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <div class="text-sm text-gray-800">Springfield, IL</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Farm Size</label>
                                <div class="text-sm text-gray-800">120 acres</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Primary Crops</label>
                                <div class="text-sm text-gray-800">Corn, Soybeans, Wheat</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Danger Zone Card -->
                    <div class="card-hover bg-white p-6 rounded-lg shadow border border-red-100 fade-in" style="animation-delay: 0.6s;">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-red-800">Danger Zone</h3>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Delete Account</p>
                                    <p class="text-xs text-gray-500">Permanently delete your account and all associated data</p>
                                </div>
                                <button class="px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    Delete
                                </button>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Export Farm Data</p>
                                    <p class="text-xs text-gray-500">Download all your farm data in CSV format</p>
                                </div>
                                <button class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications Settings (Hidden by default) -->
                <div id="notifications-content" class="hidden space-y-6">
                    <!-- Notification Preferences -->
                    <div class="card-hover bg-white p-6 rounded-lg shadow fade-in">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Notification Preferences</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Email Notifications</p>
                                    <p class="text-xs text-gray-500">Receive important updates via email</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <label>
                                        <label>
                                            <label>
                                                <label>
                                                    <label>
                                                        <label>
                                                            <label>
                                                                <label>
                                                                    <input type="checkbox" class="sr-only peer" checked title="Enable Push Notifications">
                                                                    <span class="sr-only">Enable Push Notifications</span>
                                                                </label>
                                                                <span class="sr-only">Enable Push Notifications</span>
                                                            </label>
                                                            <span class="sr-only">Enable Push Notifications</span>
                                                        </label>
                                                        <span class="sr-only">Enable Push Notifications</span>
                                                    </label>
                                                    <span class="sr-only">Enable Push Notifications</span>
                                                </label>
                                                <span class="sr-only">Enable Push Notifications</span>
                                            </label>
                                            <span class="sr-only">Enable Push Notifications</span>
                                        </label>
                                        <span class="sr-only">Enable Push Notifications</span>
                                    </label>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer toggle-bg peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all toggle-knob peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">SMS Alerts</p>
                                    <p class="text-xs text-gray-500">Get urgent alerts via text message</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <label>
                                        <label>
                                            <label>
                                                <label>
                                                    <label>
                                                        <input type="checkbox" class="sr-only peer" aria-label="Enable Compact Mode">
                                                        <span class="sr-only">Enable Compact Mode</span>
                                                    </label>
                                                    <span class="sr-only">Enable Compact Mode</span>
                                                </label>
                                                <span class="sr-only">Enable Compact Mode</span>
                                            </label>
                                            <span class="sr-only">Enable Compact Mode</span>
                                        </label>
                                        <span class="sr-only">Enable Compact Mode</span>
                                    </label>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer toggle-bg peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all toggle-knob peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Push Notifications</p>
                                    <p class="text-xs text-gray-500">Receive app notifications on your device</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <label>
                                        <input type="checkbox" class="sr-only peer" checked title="Enable Push Notifications">
                                        <span class="sr-only">Enable Push Notifications</span>
                                    </label>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer toggle-bg peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all toggle-knob peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alert Types -->
                    <div class="card-hover bg-white p-6 rounded-lg shadow fade-in">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Alert Types</h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <label>
                                    <label>
                                        <label>
                                            <label>
                                                <label>
                                                    <label>
                                                        <label>
                                                            <input type="checkbox" id="weather-alerts" class="custom-checkbox mt-1 mr-3" checked>
                                                            <label for="weather-alerts" class="sr-only">Enable Weather Alerts</label>
                                                            <span class="sr-only">Enable Equipment Maintenance</span>
                                                        </label>
                                                        <span class="sr-only">Enable Equipment Maintenance</span>
                                                    </label>
                                                    <span class="sr-only">Enable Equipment Maintenance</span>
                                                </label>
                                                <span class="sr-only">Enable Equipment Maintenance</span>
                                            </label>
                                            <span class="sr-only">Enable Weather Alerts</span>
                                        </label>
                                        <span class="sr-only">Enable Weather Alerts</span>
                                    </label>
                                    <span class="sr-only">Enable Weather Alerts</span>
                                </label>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Weather Alerts</p>
                                    <p class="text-xs text-gray-500">Severe weather warnings for your farm location</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <input type="checkbox" id="checkbox-weather-alerts" class="custom-checkbox mt-1 mr-3" checked>
                                <label for="checkbox-weather-alerts" class="sr-only">Enable Weather Alerts</label>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Equipment Maintenance</p>
                                    <p class="text-xs text-gray-500">Scheduled maintenance reminders</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <input type="checkbox" id="custom-checkbox-1" class="custom-checkbox mt-1 mr-3">
                                <label for="custom-checkbox-1" class="sr-only">Enable this option</label>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Market Prices</p>
                                    <p class="text-xs text-gray-500">Daily commodity price updates</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <input type="checkbox" id="checkbox-task-reminders" class="custom-checkbox mt-1 mr-3" checked>
                                <label for="checkbox-task-reminders" class="sr-only">Enable Task Reminders</label>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Task Reminders</p>
                                    <p class="text-xs text-gray-500">Upcoming and overdue tasks</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Appearance Settings (Hidden by default) -->
                <div id="appearance-content" class="hidden space-y-6">
                    <!-- Theme Selection -->
                    <div class="card-hover bg-white p-6 rounded-lg shadow fade-in">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Theme</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button class="p-4 border-2 border-blue-500 rounded-lg flex flex-col items-center">
                                <div class="w-full h-24 bg-gray-800 rounded mb-2"></div>
                                <span class="text-sm font-medium">Dark</span>
                            </button>
                            <button class="p-4 border border-gray-200 rounded-lg flex flex-col items-center">
                                <div class="w-full h-24 bg-white rounded mb-2"></div>
                                <span class="text-sm font-medium">Light</span>
                            </button>
                            <button class="p-4 border border-gray-200 rounded-lg flex flex-col items-center">
                                <div class="w-full h-24 bg-blue-50 rounded mb-2"></div>
                                <span class="text-sm font-medium">System</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- UI Preferences -->
                    <div class="card-hover bg-white p-6 rounded-lg shadow fade-in">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">UI Preferences</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Compact Mode</p>
                                    <p class="text-xs text-gray-500">Show more content with tighter spacing</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="compact-mode-checkbox" class="sr-only peer">
                                    <label for="compact-mode-checkbox" class="sr-only">Enable Compact Mode</label>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer toggle-bg peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all toggle-knob peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Animations</p>
                                    <p class="text-xs text-gray-500">Enable subtle UI animations</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="checkbox-push-notifications" class="sr-only peer" checked>
                                    <label for="checkbox-push-notifications" class="sr-only">Enable Push Notifications</label>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer toggle-bg peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all toggle-knob peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Privacy Settings (Hidden by default) -->
                <div id="privacy-content" class="hidden space-y-6">
                    <!-- Data Collection -->
                    <div class="card-hover bg-white p-6 rounded-lg shadow fade-in">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Data Collection</h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <input type="checkbox" id="checkbox-1" class="custom-checkbox mt-1 mr-3" checked>
                                <label for="checkbox-1" class="sr-only">Enable this option</label>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Usage Analytics</p>
                                    <p class="text-xs text-gray-500">Help us improve by sharing anonymous usage data</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <input type="checkbox" id="checkbox-crash-reports" class="custom-checkbox mt-1 mr-3">
                                <label for="checkbox-crash-reports" class="sr-only">Enable Crash Reports</label>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Crash Reports</p>
                                    <p class="text-xs text-gray-500">Automatically send crash reports</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Privacy Controls -->
                    <div class="card-hover bg-white p-6 rounded-lg shadow fade-in">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Privacy Controls</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Location Services</p>
                                    <p class="text-xs text-gray-500">Allow access to your location for weather and mapping</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="checkbox-example" class="sr-only peer" checked>
                                    <label for="checkbox-example" class="sr-only">Enable this option</label>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer toggle-bg peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all toggle-knob peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Camera Access</p>
                                    <p class="text-xs text-gray-500">Allow access to your camera for photo documentation</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="checkbox-accessibility-fix" class="sr-only peer">
                                    <label for="checkbox-accessibility-fix" class="sr-only">Enable this option</label>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer toggle-bg peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all toggle-knob peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab Switching
            const tabs = {
                account: {
                    tab: document.getElementById('account-tab'),
                    content: document.getElementById('account-content')
                },
                notifications: {
                    tab: document.getElementById('notifications-tab'),
                    content: document.getElementById('notifications-content')
                },
                appearance: {
                    tab: document.getElementById('appearance-tab'),
                    content: document.getElementById('appearance-content')
                },
                privacy: {
                    tab: document.getElementById('privacy-tab'),
                    content: document.getElementById('privacy-content')
                }
            };

            // Function to switch tabs
            function switchTab(activeTab) {
                // Reset all tabs
                Object.values(tabs).forEach(tab => {
                    tab.tab.classList.remove('border-blue-500', 'text-blue-600');
                    tab.tab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    tab.content.classList.add('hidden');
                });
                
                // Activate selected tab
                activeTab.tab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                activeTab.tab.classList.add('border-blue-500', 'text-blue-600');
                activeTab.content.classList.remove('hidden');
                
                // Animate in the new content
                const elements = activeTab.content.querySelectorAll('.fade-in');
                elements.forEach((el, index) => {
                    el.style.animationDelay = `${0.1 * index}s`;
                    el.classList.remove('fade-in');
                    void el.offsetWidth; // Trigger reflow
                    el.classList.add('fade-in');
                });
            }

            // Add event listeners to tabs
            Object.entries(tabs).forEach(([key, tab]) => {
                tab.tab.addEventListener('click', () => switchTab(tab));
            });

            // Initialize with account tab active
            switchTab(tabs.account);

            // Sidebar toggle
            document.getElementById('sidebar-toggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('hidden');
            });

            // User menu dropdown
            document.getElementById('user-menu').addEventListener('click', function() {
                document.getElementById('user-menu-dropdown').classList.toggle('hidden');
            });

            // Custom checkbox behavior
            document.querySelectorAll('.custom-checkbox').forEach(checkbox => {
                checkbox.addEventListener('click', function() {
                    this.classList.toggle('checked');
                });
            });

            // Theme selection buttons
            document.querySelectorAll('#appearance-content button').forEach(button => {
                button.addEventListener('click', function() {
                    // Remove border from all buttons
                    document.querySelectorAll('#appearance-content button').forEach(btn => {
                        btn.classList.remove('border-blue-500', 'border-2');
                        btn.classList.add('border', 'border-gray-200');
                    });
                    // Add border to clicked button
                    this.classList.remove('border', 'border-gray-200');
                    this.classList.add('border-blue-500', 'border-2');
                });
            });
        });
    </script>
</body>
</html>