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
    <title>AgriVision Pro | Inventory Management</title>
    <!-- Tailwind CSS -->
    <link href="./dist/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .status-in-stock {
            color: #10B981;
            background-color: #ECFDF5;
        }
        .status-low-stock {
            color: #F59E0B;
            background-color: #FFFBEB;
        }
        .status-out-of-stock {
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
                    <a href="livestock.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-blue-700 hover:bg-opacity-30 text-blue-100 hover:text-white group">
                        <svg class="mr-3 h-5 w-5 text-blue-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Livestock
                    </a>
                    <a href="inventory.php" class="flex items-center px-4 py-3 rounded-lg bg-blue-500 bg-opacity-30 text-white group">
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
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

       

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Inventory Management</h2>
                        <p class="text-sm text-gray-500">Manage your farm equipment and supplies</p>
                    </div>
                    <button id="add-item-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i> Add New Item
                    </button>
                </div>
                
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="relative w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="search-inventory" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search inventory...">
                        </div>
                        
                        <div class="flex space-x-2">
                            <select id="category-filter" title="Filter by category" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="all">All Categories</option>
                                <option value="equipment">Equipment</option>
                                <option value="seeds">Seeds</option>
                                <option value="fertilizers">Fertilizers</option>
                                <option value="tools">Tools</option>
                            </select>
                            
                            <select id="status-filter" title="Filter by status" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="all">All Status</option>
                                <option value="in-stock">In Stock</option>
                                <option value="low-stock">Low Stock</option>
                                <option value="out-of-stock">Out of Stock</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ITEM</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CATEGORY</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QUANTITY</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UNIT</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="inventory-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Sample data - will be replaced with dynamic content
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">Tractor</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Equipment</td>
                                    <td class="px-6 py-4 whitespace-nowrap">2</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Units</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-in-stock">
                                            In Stock
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">Rice Seeds</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Seeds</td>
                                    <td class="px-6 py-4 whitespace-nowrap">15</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Bags</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-in-stock">
                                            In Stock
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">NPK Fertilizer</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Fertilizers</td>
                                    <td class="px-6 py-4 whitespace-nowrap">3</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Bags</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-low-stock">
                                            Low Stock
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>-->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Item Modal -->
    <div id="item-modal" class="fixed z-50 inset-0 overflow-y-auto hidden modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <h3 id="modal-title" class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Item</h3>
                    <form id="item-form">
                        <input type="hidden" id="item-id">
                        <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                            <div class="sm:col-span-6">
                                <label for="item-name" class="block text-sm font-medium text-gray-700">Item Name</label>
                                <input type="text" id="item-name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="item-category" class="block text-sm font-medium text-gray-700">Category</label>
                                <select id="item-category" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                                    <option value="equipment">Equipment</option>
                                    <option value="seeds">Seeds</option>
                                    <option value="fertilizers">Fertilizers</option>
                                    <option value="tools">Tools</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="item-quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                                <input type="number" id="item-quantity" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="item-unit" class="block text-sm font-medium text-gray-700">Unit</label>
                                <select id="item-unit" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                                    <option value="pieces">Pieces</option>
                                    <option value="kg">Kilograms</option>
                                    <option value="liters">Liters</option>
                                    <option value="bags">Bags</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="item-location" class="block text-sm font-medium text-gray-700">Location</label>
                                <input type="text" id="item-location" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-6">
                                <label for="item-notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea id="item-notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="button" id="save-item" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">
                        Save
                    </button>
                    <button type="button" id="cancel-item" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
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
        const DB_VERSION = 2; // Incremented version for inventory
        const STORES = {
            INVENTORY: 'inventory',
            ACTIVITY: 'activity'
        };

        let db;
        
        // Initialize the application
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                // Initialize database
                await initDB();
                
                // Setup UI interactions
                setupUI();
                
                // Load inventory data
                await loadInventory();
                
            } catch (error) {
                console.error('Initialization error:', error);
                showAlert('Failed to initialize the application. Please try again.', 'error');
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
                    
                    // Create inventory object store if it doesn't exist
                    if (!db.objectStoreNames.contains(STORES.INVENTORY)) {
                        const inventoryStore = db.createObjectStore(STORES.INVENTORY, { 
                            keyPath: 'id', 
                            autoIncrement: true 
                        });
                        inventoryStore.createIndex('name', 'name', { unique: false });
                        inventoryStore.createIndex('category', 'category', { unique: false });
                        inventoryStore.createIndex('quantity', 'quantity', { unique: false });
                        inventoryStore.createIndex('status', 'status', { unique: false });
                    }
                    
                    if (!db.objectStoreNames.contains(STORES.ACTIVITY)) {
                        db.createObjectStore(STORES.ACTIVITY, { keyPath: 'id', autoIncrement: true });
                    }
                };
            });
        }
        
        // ========== CRUD Operations ========== //
        async function addItem(itemData) {
            // Auto-calculate status based on quantity
            itemData.status = calculateStatus(itemData.quantity);
            
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.INVENTORY], 'readwrite');
                const store = transaction.objectStore(STORES.INVENTORY);
                
                const request = store.add(itemData);
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = (event) => reject(event.target.error);
            });
        }

        async function getItem(id) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.INVENTORY], 'readonly');
                const store = transaction.objectStore(STORES.INVENTORY);
                
                const request = store.get(id);
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = (event) => reject(event.target.error);
            });
        }

        async function getAllItems(filters = {}) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.INVENTORY], 'readonly');
                const store = transaction.objectStore(STORES.INVENTORY);
                
                const request = store.getAll();
                
                request.onsuccess = () => {
                    let items = request.result;
                    
                    // Apply search filter
                    if (filters.searchTerm) {
                        const term = filters.searchTerm.toLowerCase();
                        items = items.filter(item => 
                            item.name.toLowerCase().includes(term) || 
                            (item.category && item.category.toLowerCase().includes(term)) ||
                            (item.location && item.location.toLowerCase().includes(term))
                        );
                    }
                    
                    // Apply category filter
                    if (filters.category && filters.category !== 'all') {
                        items = items.filter(item => item.category === filters.category);
                    }
                    
                    // Apply status filter
                    if (filters.status && filters.status !== 'all') {
                        items = items.filter(item => item.status === filters.status);
                    }
                    
                    resolve(items);
                };
                
                request.onerror = (event) => reject(event.target.error);
            });
        }

        async function updateItem(id, updates) {
            // Recalculate status if quantity is being updated
            if (updates.quantity !== undefined) {
                updates.status = calculateStatus(updates.quantity);
            }
            
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.INVENTORY], 'readwrite');
                const store = transaction.objectStore(STORES.INVENTORY);
                
                const getRequest = store.get(id);
                
                getRequest.onsuccess = () => {
                    const currentData = getRequest.result;
                    if (!currentData) {
                        reject('Item not found');
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

        async function deleteItem(id) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.INVENTORY], 'readwrite');
                const store = transaction.objectStore(STORES.INVENTORY);
                
                const request = store.delete(id);
                
                request.onsuccess = () => resolve(true);
                request.onerror = (event) => reject(event.target.error);
            });
        }

        function calculateStatus(quantity) {
            if (quantity <= 0) return 'out-of-stock';
            if (quantity < 10) return 'low-stock';
            return 'in-stock';
        }

        // ========== UI Functions ========== //
        function setupUI() {
            // Add item button
            document.getElementById('add-item-btn').addEventListener('click', showAddItemModal);
            
            // Save item form
            document.getElementById('save-item').addEventListener('click', handleSaveItem);
            
            // Cancel item form
            document.getElementById('cancel-item').addEventListener('click', () => {
                document.getElementById('item-modal').classList.add('hidden');
            });
            
            // Search input
            document.getElementById('search-inventory').addEventListener('input', async (e) => {
                await loadInventory({ searchTerm: e.target.value });
            });
            
            // Category filter
            document.getElementById('category-filter').addEventListener('change', async (e) => {
                await loadInventory({ category: e.target.value });
            });
            
            // Status filter
            document.getElementById('status-filter').addEventListener('change', async (e) => {
                await loadInventory({ status: e.target.value });
            });
        }
        // User menu dropdown
        const userMenu = document.getElementById('user-menu');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');
            
            userMenu.addEventListener('click', function() {
                userMenuDropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!userMenu.contains(event.target) && !userMenuDropdown.contains(event.target)) {
                    userMenuDropdown.classList.add('hidden');
                }
            });

            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
            });

        async function loadInventory(filters = {}) {
            try {
                const items = await getAllItems(filters);
                renderInventoryTable(items);
            } catch (error) {
                console.error('Error loading inventory:', error);
                showAlert('Failed to load inventory data.', 'error');
            }
        }

        function renderInventoryTable(items) {
            const tableBody = document.getElementById('inventory-table-body');
        
            
            
            tableBody.innerHTML = items.map(item => `
                <tr class="hover:bg-gray-50" data-id="${item.id}">
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${item.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap capitalize">${item.category || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.quantity || '0'}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.unit || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(item.status)}">
                            ${getStatusText(item.status)}
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
                    await showEditItemModal(id);
                });
            });
            
            // Add event listeners to all delete buttons
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const row = e.target.closest('tr');
                    const id = parseInt(row.getAttribute('data-id'));
                    await handleDeleteItem(id);
                });
            });
        }

        function showAddItemModal() {
            document.getElementById('modal-title').textContent = 'Add New Inventory Item';
            document.getElementById('item-id').value = '';
            document.getElementById('item-form').reset();
            document.getElementById('item-modal').classList.remove('hidden');
        }

        async function showEditItemModal(id) {
            try {
                const item = await getItem(id);
                if (!item) {
                    throw new Error('Item not found');
                }
                
                document.getElementById('modal-title').textContent = 'Edit Inventory Item';
                document.getElementById('item-id').value = item.id;
                document.getElementById('item-name').value = item.name;
                document.getElementById('item-category').value = item.category || 'equipment';
                document.getElementById('item-quantity').value = item.quantity || '';
                document.getElementById('item-unit').value = item.unit || 'pieces';
                document.getElementById('item-location').value = item.location || '';
                document.getElementById('item-notes').value = item.notes || '';
                
                document.getElementById('item-modal').classList.remove('hidden');
            } catch (error) {
                console.error('Error showing edit modal:', error);
                showAlert('Failed to load item data for editing.', 'error');
            }
        }

        async function handleSaveItem() {
            const id = document.getElementById('item-id').value;
            const formData = {
                name: document.getElementById('item-name').value,
                category: document.getElementById('item-category').value,
                quantity: parseInt(document.getElementById('item-quantity').value) || 0,
                unit: document.getElementById('item-unit').value,
                location: document.getElementById('item-location').value,
                notes: document.getElementById('item-notes').value
            };
            
            try {
                if (id) {
                    // Update existing item
                    await updateItem(parseInt(id), formData);
                    await addActivityLog({
                        type: 'inventory_updated',
                        description: `Updated inventory item: ${formData.name}`,
                        user: 'Current User'
                    });
                    showAlert('Inventory item updated successfully!', 'success');
                } else {
                    // Add new item
                    const newId = await addItem(formData);
                    await addActivityLog({
                        type: 'inventory_added',
                        description: `Added new inventory item: ${formData.name}`,
                        user: 'Current User'
                    });
                    showAlert('Inventory item added successfully!', 'success');
                }
                
                document.getElementById('item-modal').classList.add('hidden');
                await loadInventory();
            } catch (error) {
                console.error('Error saving item:', error);
                showAlert('Error saving inventory item. Please try again.', 'error');
            }
        }

        async function handleDeleteItem(id) {
            try {
                const item = await getItem(id);
                if (!item) {
                    throw new Error('Item not found');
                }
                
                if (confirm(`Are you sure you want to delete "${item.name}"? This action cannot be undone.`)) {
                    await deleteItem(id);
                    await addActivityLog({
                        type: 'inventory_deleted',
                        description: `Deleted inventory item: ${item.name}`,
                        user: 'Current User'
                    });
                    showAlert('Inventory item deleted successfully!', 'success');
                    await loadInventory();
                }
            } catch (error) {
                console.error('Error deleting item:', error);
                showAlert('Error deleting inventory item. Please try again.', 'error');
            }
        }

        // ========== Helper Functions ========== //
        function getStatusClass(status) {
            switch(status) {
                case 'in-stock': return 'status-in-stock';
                case 'low-stock': return 'status-low-stock';
                case 'out-of-stock': return 'status-out-of-stock';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        function getStatusText(status) {
            switch(status) {
                case 'in-stock': return 'In Stock';
                case 'low-stock': return 'Low Stock';
                case 'out-of-stock': return 'Out of Stock';
                default: return status;
            }
        }

        async function addActivityLog(activityData) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.ACTIVITY], 'readwrite');
                const store = transaction.objectStore(STORES.ACTIVITY);
                
                const request = store.add({
                    ...activityData,
                    timestamp: new Date().toISOString()
                });
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = (event) => reject(event.target.error);
            });
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
    </script>
<?php include 'includes/footer.php'; ?>