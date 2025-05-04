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
    <title>AgriVision Pro | Crop Management</title>
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
        .status-growing {
            color: #10B981;
            background-color: #ECFDF5;
        }
        .status-harvested {
            color: #3B82F6;
            background-color: #EFF6FF;
        }
        .status-planned {
            color: #F59E0B;
            background-color: #FFFBEB;
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
                    <a href="crops.php" class="flex items-center px-4 py-3 rounded-lg bg-blue-500 bg-opacity-30 text-white group">
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
                        <h2 class="text-2xl font-bold text-gray-800">Crop Management</h2>
                        <p class="text-sm text-gray-500">Manage your crop planting and harvesting</p>
                    </div>
                    <button id="add-crop-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i> Add New Crop
                    </button>
                </div>
                
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="relative w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="search-crops" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search crops...">
                        </div>
                        
                        <div class="flex space-x-2">
                            <select id="status-filter" title="Filter by crop status" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="all">All Status</option>
                                <option value="growing">Growing</option>
                                <option value="harvested">Harvested</option>
                                <option value="planned">Planned</option>
                            </select>
                            
                            <select id="sort-by" title="Sort crops by criteria" class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="name-asc">Sort by Name (A-Z)</option>
                                <option value="name-desc">Sort by Name (Z-A)</option>
                                <option value="date-asc">Sort by Date (Oldest)</option>
                                <option value="date-desc">Sort by Date (Newest)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CROP</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">FIELD</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AREA (HA)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PLANTED DATE</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HARVEST DATE</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="crops-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Crops will be loaded here dynamically -->
                            </tbody>
                        </table>
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
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <h3 id="modal-title" class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Crop</h3>
                    <form id="crop-form">
                        <input type="hidden" id="crop-id">
                        <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                            <div class="sm:col-span-6">
                                <label for="crop-name" class="block text-sm font-medium text-gray-700">Crop Name</label>
                                <input type="text" id="crop-name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="crop-variety" class="block text-sm font-medium text-gray-700">Variety</label>
                                <input type="text" id="crop-variety" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="crop-field" class="block text-sm font-medium text-gray-700">Field</label>
                                <input type="text" id="crop-field" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="crop-area" class="block text-sm font-medium text-gray-700">Area (ha)</label>
                                <input type="number" step="0.1" id="crop-area" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="crop-status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select id="crop-status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="growing">Growing</option>
                                    <option value="harvested">Harvested</option>
                                    <option value="planned">Planned</option>
                                </select>
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="crop-planted-date" class="block text-sm font-medium text-gray-700">Planted Date</label>
                                <input type="date" id="crop-planted-date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="crop-harvest-date" class="block text-sm font-medium text-gray-700">Harvest Date</label>
                                <input type="date" id="crop-harvest-date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-6">
                                <label for="crop-notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea id="crop-notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                            </div>
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
        const DB_VERSION = 2;
        const STORES = {
            CROPS: 'crops',
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
                
                // Load crops data
                await loadCrops();
                
            } catch (error) {
                console.error('Initialization error:', error);
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
                    
                    // Create object stores if they don't exist
                    if (!db.objectStoreNames.contains(STORES.CROPS)) {
                        const cropsStore = db.createObjectStore(STORES.CROPS, { keyPath: 'id', autoIncrement: true });
                        cropsStore.createIndex('name', 'name', { unique: false });
                        cropsStore.createIndex('variety', 'variety', { unique: false });
                        cropsStore.createIndex('field', 'field', { unique: false });
                        cropsStore.createIndex('area', 'area', { unique: false });
                        cropsStore.createIndex('status', 'status', { unique: false });
                        cropsStore.createIndex('plantedDate', 'plantedDate', { unique: false });
                        cropsStore.createIndex('harvestDate', 'harvestDate', { unique: false });
                    }
                    
                    if (!db.objectStoreNames.contains(STORES.ACTIVITY)) {
                        db.createObjectStore(STORES.ACTIVITY, { keyPath: 'id', autoIncrement: true });
                    }
                };
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
            
            userMenu.addEventListener('click', function() {
                userMenuDropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!userMenu.contains(event.target) && !userMenuDropdown.contains(event.target)) {
                    userMenuDropdown.classList.add('hidden');
                }
            });
            
            // Add crop button
            document.getElementById('add-crop-btn').addEventListener('click', () => {
                document.getElementById('modal-title').textContent = 'Add New Crop';
                document.getElementById('crop-id').value = '';
                document.getElementById('crop-form').reset();
                document.getElementById('crop-modal').classList.remove('hidden');
            });
            
            // Save crop button
            document.getElementById('save-crop').addEventListener('click', async () => {
                const cropData = {
                    name: document.getElementById('crop-name').value,
                    variety: document.getElementById('crop-variety').value,
                    field: document.getElementById('crop-field').value,
                    area: parseFloat(document.getElementById('crop-area').value),
                    status: document.getElementById('crop-status').value,
                    plantedDate: document.getElementById('crop-planted-date').value,
                    harvestDate: document.getElementById('crop-harvest-date').value,
                    notes: document.getElementById('crop-notes').value
                };
                
                const id = document.getElementById('crop-id').value;
                
                try {
                    if (id) {
                        // Update existing crop
                        await updateCrop(parseInt(id), cropData);
                        
                        // Add activity log
                        await addActivity({
                            type: 'crop_updated',
                            description: `Updated crop: ${cropData.name}`,
                            date: new Date().toISOString().split('T')[0],
                            user: 'Current User'
                        });
                    } else {
                        // Add new crop
                        const newId = await addCrop(cropData);
                        
                        // Add activity log
                        await addActivity({
                            type: 'crop_added',
                            description: `Added new crop: ${cropData.name}`,
                            date: new Date().toISOString().split('T')[0],
                            user: 'Current User'
                        });
                    }
                    
                    document.getElementById('crop-modal').classList.add('hidden');
                    await loadCrops(); // Refresh the table
                } catch (error) {
                    console.error('Error saving crop:', error);
                    alert('Error saving crop. Please try again.');
                }
            });
            
            // Cancel crop button
            document.getElementById('cancel-crop').addEventListener('click', () => {
                document.getElementById('crop-modal').classList.add('hidden');
            });
            
            // Search crops
            document.getElementById('search-crops').addEventListener('input', async (e) => {
                await loadCrops(e.target.value);
            });
            
            // Filter by status
            document.getElementById('status-filter').addEventListener('change', async (e) => {
                await loadCrops(null, e.target.value);
            });
            
            // Sort crops
            document.getElementById('sort-by').addEventListener('change', async (e) => {
                await loadCrops(null, null, e.target.value);
            });
        }
        
        // Load crops into the table
        async function loadCrops(searchTerm = null, statusFilter = 'all', sortOption = 'name-asc') {
            try {
                let crops = await getAll(STORES.CROPS);
                
                // Apply search filter
                if (searchTerm) {
                    const term = searchTerm.toLowerCase();
                    crops = crops.filter(crop => 
                        crop.name.toLowerCase().includes(term) || 
                        (crop.variety && crop.variety.toLowerCase().includes(term)) ||
                        (crop.field && crop.field.toLowerCase().includes(term))
                    );
                }
                
                // Apply status filter
                if (statusFilter !== 'all') {
                    crops = crops.filter(crop => crop.status === statusFilter);
                }
                
                // Apply sorting
                crops.sort((a, b) => {
                    switch(sortOption) {
                        case 'name-asc':
                            return a.name.localeCompare(b.name);
                        case 'name-desc':
                            return b.name.localeCompare(a.name);
                        case 'date-asc':
                            return new Date(a.plantedDate) - new Date(b.plantedDate);
                        case 'date-desc':
                            return new Date(b.plantedDate) - new Date(a.plantedDate);
                        default:
                            return 0;
                    }
                });
                
                const tableBody = document.getElementById('crops-table-body');
                
                if (tableBody) {
                    tableBody.innerHTML = crops.map(crop => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">${crop.name}</div>
                                <div class="text-sm text-gray-500">${crop.variety || '-'}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${crop.field || '-'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${crop.area ? `${crop.area} ha` : '-'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${crop.plantedDate ? new Date(crop.plantedDate).toLocaleDateString() : '-'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${crop.harvestDate ? new Date(crop.harvestDate).toLocaleDateString() : '-'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(crop.status)}">
                                    ${getStatusText(crop.status)}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button data-id="${crop.id}" class="edit-crop text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button data-id="${crop.id}" class="delete-crop text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                    
                    // Add event listeners to edit buttons
                    document.querySelectorAll('.edit-crop').forEach(btn => {
                        btn.addEventListener('click', async (e) => {
                            const id = parseInt(e.target.getAttribute('data-id'));
                            const crop = await getCrop(id);
                            
                            if (crop) {
                                document.getElementById('modal-title').textContent = 'Edit Crop';
                                document.getElementById('crop-id').value = crop.id;
                                document.getElementById('crop-name').value = crop.name;
                                document.getElementById('crop-variety').value = crop.variety || '';
                                document.getElementById('crop-field').value = crop.field || '';
                                document.getElementById('crop-area').value = crop.area || '';
                                document.getElementById('crop-status').value = crop.status || 'growing';
                                document.getElementById('crop-planted-date').value = crop.plantedDate || '';
                                document.getElementById('crop-harvest-date').value = crop.harvestDate || '';
                                document.getElementById('crop-notes').value = crop.notes || '';
                                
                                document.getElementById('crop-modal').classList.remove('hidden');
                            }
                        });
                    });
                    
                    // Add event listeners to delete buttons
                    document.querySelectorAll('.delete-crop').forEach(btn => {
                        btn.addEventListener('click', async (e) => {
                            const id = parseInt(e.target.getAttribute('data-id'));
                            const crop = await getCrop(id);
                            
                            if (crop && confirm(`Are you sure you want to delete ${crop.name}?`)) {
                                await deleteCrop(id);
                                
                                // Add activity log
                                await addActivity({
                                    type: 'crop_deleted',
                                    description: `Deleted crop: ${crop.name}`,
                                    date: new Date().toISOString().split('T')[0],
                                    user: 'Current User'
                                });
                                
                                await loadCrops(); // Refresh the table
                            }
                        });
                    });
                }
            } catch (error) {
                console.error('Error loading crops:', error);
            }
        }
        
        // Helper function to get status class
        function getStatusClass(status) {
            switch(status) {
                case 'growing':
                    return 'status-growing';
                case 'harvested':
                    return 'status-harvested';
                case 'planned':
                    return 'status-planned';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        }
        
        // Helper function to get status text
        function getStatusText(status) {
            switch(status) {
                case 'growing':
                    return 'Growing';
                case 'harvested':
                    return 'Harvested';
                case 'planned':
                    return 'Planned';
                default:
                    return status;
            }
        }
        
        // CRUD operations for crops
        function addCrop(crop) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.CROPS], 'readwrite');
                const store = transaction.objectStore(STORES.CROPS);
                
                const request = store.add(crop);
                
                request.onsuccess = () => resolve(request.result);
                request.onerror = (event) => reject(event.target.error);
            });
        }
        
        function getCrop(id) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.CROPS], 'readonly');
                const store = transaction.objectStore(STORES.CROPS);
                
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
        
        function updateCrop(id, updates) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.CROPS], 'readwrite');
                const store = transaction.objectStore(STORES.CROPS);
                
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
        
        function deleteCrop(id) {
            return new Promise((resolve, reject) => {
                const transaction = db.transaction([STORES.CROPS], 'readwrite');
                const store = transaction.objectStore(STORES.CROPS);
                
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