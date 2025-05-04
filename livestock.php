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
    <title>AgriVision Pro | Livestock Management</title>
    <link href="./dist/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
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
            background-color: #FEF2F2;
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
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
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
                        <p class="text-sm text-gray-500">Manage your farm animals and their health</p>
                    </div>
                    <button id="add-animal-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i> Add New Animal
                    </button>
                </div>
                
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="relative w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="search-livestock" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search livestock...">
                        </div>
                        
                        <div class="flex space-x-2">
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
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID/TAG</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BREED</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AGE</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WEIGHT</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LOCATION</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="livestock-table-body" class="bg-white divide-y divide-gray-200">
                                <!--<tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">C-002</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Angus</td>
                                    <td class="px-6 py-4 whitespace-nowrap">3y 8m</td>
                                    <td class="px-6 py-4 whitespace-nowrap">520 kg</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Barn A</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-pregnant">
                                            Pregnant
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">C-001</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Holstein</td>
                                    <td class="px-6 py-4 whitespace-nowrap">3y 0m</td>
                                    <td class="px-6 py-4 whitespace-nowrap">450 kg</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Barn A</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-healthy">
                                            Healthy
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">C-003</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Jersey</td>
                                    <td class="px-6 py-4 whitespace-nowrap">2y 10m</td>
                                    <td class="px-6 py-4 whitespace-nowrap">380 kg</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Barn B</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-sick">
                                            Sick
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>-->
                                </tr>
                            </tbody>
                        </table>
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
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <h3 id="modal-title" class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Animal</h3>
                    <form id="animal-form">
                        <input type="hidden" id="animal-id">
                        <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                            <div class="sm:col-span-3">
                                <label for="animal-id-tag" class="block text-sm font-medium text-gray-700">ID/Tag</label>
                                <input type="text" id="animal-id-tag" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="animal-type" class="block text-sm font-medium text-gray-700">Type</label>
                                <select id="animal-type" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
                                <input type="text" id="animal-breed" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="animal-age" class="block text-sm font-medium text-gray-700">Age</label>
                                <div class="flex space-x-2">
                                    <input type="number" id="animal-age-years" placeholder="Years" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <input type="number" id="animal-age-months" placeholder="Months" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="animal-birth-date" class="block text-sm font-medium text-gray-700">Birth Date</label>
                                <input type="date" id="animal-birth-date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="animal-weight" class="block text-sm font-medium text-gray-700">Weight</label>
                                <div class="relative mt-1 rounded-md shadow-sm">
                                    <input type="number" id="animal-weight" class="block w-full pr-12 border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <div class="absolute inset-y-0 right-0 flex items-center">
                                        <select id="weight-unit" title="Select weight unit" class="h-full rounded-r-md border-transparent bg-transparent py-0 pl-2 pr-7 text-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option>kg</option>
                                            <option>lbs</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="animal-status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select id="animal-status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="healthy">Healthy</option>
                                    <option value="pregnant">Pregnant</option>
                                    <option value="sick">Sick</option>
                                    <option value="injured">Injured</option>
                                </select>
                            </div>
                            
                            <div class="sm:col-span-6">
                                <label for="animal-location" class="block text-sm font-medium text-gray-700">Location</label>
                                <input type="text" id="animal-location" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-6">
                                <label for="animal-notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea id="animal-notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="button" id="save-animal" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">
                        Save
                    </button>
                    <button type="button" id="cancel-animal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                        Cancel
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
    const STORE_NAME = 'livestock';
    let db;
    let DB_VERSION = 2; // Start with version 40

    // Add this at the very beginning of your script (before initApp())
function setupMessageSystem() {
    // Create message container that will appear in front of everything
    const existingContainer = document.getElementById('message-container');
    
    if (!existingContainer) {
        const messageContainer = document.createElement('div');
        messageContainer.id = 'message-container';
        messageContainer.className = 'fixed top-4 left-0 right-0 z-[1000] flex justify-center';
        document.body.appendChild(messageContainer); // Add to body instead of header
    }

    // Replace existing showAlert function with front-display version
    window.showAlert = function(message, type = 'info') {
        const messageContainer = document.getElementById('message-container') || document.body;
        
        // Remove any existing alerts
        const existingAlert = messageContainer.querySelector('.alert-message');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert-message px-6 py-3 rounded-md shadow-lg text-white ${
            type === 'error' ? 'bg-red-500' : 
            type === 'success' ? 'bg-green-500' : 'bg-blue-500'
        } mb-2`; // Added mb-2 for margin between multiple messages
        
        alertDiv.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">
                    ${type === 'error' ? '⚠️' : type === 'success' ? '✓' : 'ℹ️'}
                </span>
                <span>${message}</span>
                <button class="ml-4 text-white hover:text-gray-200 focus:outline-none" 
                        onclick="this.parentElement.parentElement.remove()">
                    &times;
                </button>
            </div>
        `;
        
        messageContainer.appendChild(alertDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            alertDiv.classList.add('opacity-0', 'transition-opacity', 'duration-500');
            setTimeout(() => alertDiv.remove(), 500);
        }, 5000);
    };
}

// Then modify your initApp() to include this:
async function initApp() {
    setupMessageSystem(); // Add this line first
    
    try {
        await initDB();
        setupEventListeners();
        await loadLivestockTable();
        showAlert('Application loaded successfully', 'success');
    } catch (error) {
        console.error('Initialization error:', error);
        showAlert('Failed to initialize application. Please refresh the page.', 'error');
    }
}

    // ========== Database Initialization ========== //
    async function initDB() {
        return new Promise((resolve, reject) => {
            // First check current version
            const versionCheck = indexedDB.open(DB_NAME);
            
            versionCheck.onsuccess = (e) => {
                const tempDb = e.target.result;
                const currentVersion = tempDb.version;
                tempDb.close();
                
                const request = indexedDB.open(DB_NAME, DB_VERSION);
                
                request.onerror = (event) => {
                    console.error('Database error:', event.target.error);
                    reject(event.target.error);
                };

                request.onsuccess = (event) => {
                    db = event.target.result;
                    console.log('Database initialized successfully at version', db.version);
                    resolve(db);
                };

                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    console.log('Database upgrade needed from', event.oldVersion, 'to', db.version);

                    if (!db.objectStoreNames.contains(STORE_NAME)) {
                        const store = db.createObjectStore(STORE_NAME, { 
                            keyPath: 'id', 
                            autoIncrement: true 
                        });
                        
                        store.createIndex('idTag', 'idTag', { unique: true });
                        store.createIndex('type', 'type', { unique: false });
                        store.createIndex('status', 'status', { unique: false });
                        
                        // Add sample data if new store
                        if (event.oldVersion === 0) {
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
                                }
                            ];
                            
                            sampleAnimals.forEach(animal => {
                                store.add(animal);
                            });
                        }
                    }
                };
            };
            
            versionCheck.onerror = () => {
                // If version check fails, try to create fresh database
                const request = indexedDB.open(DB_NAME, DB_VERSION);
                
                request.onsuccess = (event) => {
                    db = event.target.result;
                    console.log('New database created');
                    resolve(db);
                };
                
                request.onerror = (event) => {
                    reject(event.target.error);
                };
                
                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    const store = db.createObjectStore(STORE_NAME, { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                    store.createIndex('idTag', 'idTag', { unique: true });
                    store.createIndex('type', 'type', { unique: false });
                    store.createIndex('status', 'status', { unique: false });
                };
            };
        });
    }

    // ========== CRUD Operations ========== //
    async function addAnimal(animalData) {
        return new Promise((resolve, reject) => {
            if (!db) {
                reject('Database not connected');
                return;
            }

            const transaction = db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            
            const request = store.add(animalData);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = (event) => reject(event.target.error);
        });
    }

    async function getAnimal(id) {
        return new Promise((resolve, reject) => {
            if (!db) {
                reject('Database not connected');
                return;
            }

            const transaction = db.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            
            const request = store.get(id);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = (event) => reject(event.target.error);
        });
    }

    async function getAllAnimals(filters = {}) {
        return new Promise((resolve, reject) => {
            if (!db) {
                reject('Database not connected');
                return;
            }

            const transaction = db.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            
            const request = store.getAll();
            
            request.onsuccess = () => {
                let animals = request.result || [];
                
                if (filters.searchTerm) {
                    const term = filters.searchTerm.toLowerCase();
                    animals = animals.filter(animal => 
                        animal.idTag.toLowerCase().includes(term) || 
                        (animal.breed && animal.breed.toLowerCase().includes(term)) ||
                        (animal.location && animal.location.toLowerCase().includes(term))
                    );
                }
                
                if (filters.type && filters.type !== 'all') {
                    animals = animals.filter(animal => animal.type === filters.type);
                }
                
                if (filters.status && filters.status !== 'all') {
                    animals = animals.filter(animal => animal.status === filters.status);
                }
                
                resolve(animals);
            };
            
            request.onerror = (event) => reject(event.target.error);
        });
    }

    async function updateAnimal(id, updates) {
        return new Promise((resolve, reject) => {
            if (!db) {
                reject('Database not connected');
                return;
            }

            const transaction = db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            
            const getRequest = store.get(id);
            
            getRequest.onsuccess = () => {
                const currentData = getRequest.result;
                if (!currentData) {
                    reject('Animal not found');
                    return;
                }
                
                const updatedData = { ...currentData, ...updates };
                const putRequest = store.put(updatedData);
                
                putRequest.onsuccess = () => resolve(putRequest.result);
                putRequest.onerror = (event) => reject(event.target.error);
            };
            
            getRequest.onerror = (event) => reject(event.target.error);
        });
    }

    async function deleteAnimal(id) {
        return new Promise((resolve, reject) => {
            if (!db) {
                reject('Database not connected');
                return;
            }

            const transaction = db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            
            const request = store.delete(id);
            
            request.onsuccess = () => resolve(true);
            request.onerror = (event) => reject(event.target.error);
        });
    }

    // ========== UI Functions ========== //
    function setupEventListeners() {
        // Add animal button
        document.getElementById('add-animal-btn').addEventListener('click', showAddAnimalModal);
        
        // Save animal form
        document.getElementById('save-animal').addEventListener('click', handleSaveAnimal);
        
        // Cancel animal form
        document.getElementById('cancel-animal').addEventListener('click', () => {
            document.getElementById('animal-modal').classList.add('hidden');
        });
        
        // Search input
        document.getElementById('search-livestock').addEventListener('input', async (e) => {
            await loadLivestockTable({ searchTerm: e.target.value });
        });
        
        // Type filter
        document.getElementById('type-filter').addEventListener('change', async (e) => {
            await loadLivestockTable({ type: e.target.value });
        });
        
        // Status filter
        document.getElementById('status-filter').addEventListener('change', async (e) => {
            await loadLivestockTable({ status: e.target.value });
        });
    }

    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
            });

    async function loadLivestockTable(filters = {}) {
        try {
            const animals = await getAllAnimals(filters);
            renderLivestockTable(animals);
        } catch (error) {
            console.error('Error loading livestock:', error);
            showAlert('Failed to load livestock data. ' + error.toString(), 'error');
        }
    }

    function renderLivestockTable(animals) {
        const tableBody = document.getElementById('livestock-table-body');
        
        if (!animals || animals.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        No animals found. Click "Add New Animal" to get started.
                    </td>
                </tr>
            `;
            return;
        }
        
        tableBody.innerHTML = animals.map(animal => `
            <tr class="hover:bg-gray-50" data-id="${animal.id}">
                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${animal.idTag}</td>
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
                    <button class="edit-btn text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                    <button class="delete-btn text-red-600 hover:text-red-900">Delete</button>
                </td>
            </tr>
        `).join('');
        
        // Add event listeners to all edit buttons
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const row = e.target.closest('tr');
                const id = parseInt(row.getAttribute('data-id'));
                await showEditAnimalModal(id);
            });
        });
        
        // Add event listeners to all delete buttons
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const row = e.target.closest('tr');
                const id = parseInt(row.getAttribute('data-id'));
                await handleDeleteAnimal(id);
            });
        });
    }

    function showAddAnimalModal() {
        document.getElementById('modal-title').textContent = 'Add New Animal';
        document.getElementById('animal-id').value = '';
        document.getElementById('animal-form').reset();
        document.getElementById('animal-modal').classList.remove('hidden');
    }

    async function showEditAnimalModal(id) {
        try {
            const animal = await getAnimal(id);
            if (!animal) {
                throw new Error('Animal not found');
            }
            
            document.getElementById('modal-title').textContent = 'Edit Animal';
            document.getElementById('animal-id').value = animal.id;
            document.getElementById('animal-id-tag').value = animal.idTag || '';
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
        } catch (error) {
            console.error('Error showing edit modal:', error);
            showAlert('Failed to load animal data for editing. ' + error.toString(), 'error');
        }
    }

    async function handleSaveAnimal() {
        const id = document.getElementById('animal-id').value;
        const formData = {
            idTag: document.getElementById('animal-id-tag').value,
            type: document.getElementById('animal-type').value,
            breed: document.getElementById('animal-breed').value,
            ageYears: parseInt(document.getElementById('animal-age-years').value) || 0,
            ageMonths: parseInt(document.getElementById('animal-age-months').value) || 0,
            birthDate: document.getElementById('animal-birth-date').value,
            weight: parseFloat(document.getElementById('animal-weight').value) || 0,
            weightUnit: document.getElementById('weight-unit').value,
            status: document.getElementById('animal-status').value,
            location: document.getElementById('animal-location').value,
            notes: document.getElementById('animal-notes').value
        };
        
        if (!formData.idTag) {
            showAlert('ID/Tag is required', 'error');
            return;
        }
        
        try {
            if (id) {
                await updateAnimal(parseInt(id), formData);
                showAlert('Animal updated successfully!', 'success');
            } else {
                await addAnimal(formData);
                showAlert('Animal added successfully!', 'success');
            }
            
            document.getElementById('animal-modal').classList.add('hidden');
            await loadLivestockTable();
        } catch (error) {
            console.error('Error saving animal:', error);
            
            if (error.toString().includes('ConstraintError')) {
                showAlert('An animal with this ID/Tag already exists', 'error');
            } else {
                showAlert('Error saving animal: ' + error.toString(), 'error');
            }
        }
    }

    async function handleDeleteAnimal(id) {
        try {
            const animal = await getAnimal(id);
            if (!animal) {
                throw new Error('Animal not found');
            }
            
            if (confirm(`Are you sure you want to delete ${animal.idTag}? This action cannot be undone.`)) {
                await deleteAnimal(id);
                showAlert('Animal deleted successfully!', 'success');
                await loadLivestockTable();
            }
        } catch (error) {
            console.error('Error deleting animal:', error);
            showAlert('Error deleting animal: ' + error.toString(), 'error');
        }
    }
    

    // ========== Helper Functions ========== //
    function formatAge(years, months) {
        let result = '';
        if (years > 0) result += `${years}y`;
        if (months > 0) {
            if (years > 0) result += ' ';
            result += `${months}m`;
        }
        return result || '-';
    }

    function getStatusClass(status) {
        switch(status) {
            case 'healthy': return 'status-healthy';
            case 'pregnant': return 'status-pregnant';
            case 'sick': return 'status-sick';
            case 'injured': return 'status-sick';
            default: return 'bg-gray-100 text-gray-800';
        }
    }

    function getStatusText(status) {
        switch(status) {
            case 'healthy': return 'Healthy';
            case 'pregnant': return 'Pregnant';
            case 'sick': return 'Sick';
            case 'injured': return 'Injured';
            default: return status || 'Unknown';
        }
    }

    function showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `fixed top-4 right-4 px-6 py-4 rounded-md shadow-md text-white ${
            type === 'error' ? 'bg-red-500' : 
            type === 'success' ? 'bg-green-500' : 'bg-blue-500'
        }`;
        alertDiv.textContent = message;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.classList.add('opacity-0', 'transition-opacity', 'duration-500');
            setTimeout(() => alertDiv.remove(), 500);
        }, 3000);
    }

    // ========== Initialize Application ========== //
    async function initApp() {
        try {
            await initDB();
            setupEventListeners();
            await loadLivestockTable();
            showAlert('Application loaded successfully', 'success');
        } catch (error) {
            console.error('Initialization error:', error);
            showAlert('Failed to initialize application. Please refresh the page.', 'error');
        }
    }

    // Start the application
    initApp();
});
    </script>
<?php include 'includes/footer.php'; ?>