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
    <title>AgriVision Pro | Task Management</title>
    <link href="./dist/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .status-pending {
            color: #F59E0B;
            background-color: #FEF3C7;
        }
        .status-in-progress {
            color: #3B82F6;
            background-color: #DBEAFE;
        }
        .status-completed {
            color: #10B981;
            background-color: #D1FAE5;
        }
        .status-overdue {
            color: #EF4444;
            background-color: #FEE2E2;
        }
        .priority-high {
            color: #EF4444;
        }
        .priority-medium {
            color: #F59E0B;
        }
        .priority-low {
            color: #10B981;
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

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation (same as livestock.php) -->
            <header class="bg-white shadow-sm z-10">
                <!-- Same header content as livestock.php -->
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Task Management</h2>
                        <p class="text-sm text-gray-500">Manage and track farm tasks and activities</p>
                    </div>
                    <button id="add-task-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i> Add New Task
                    </button>
                </div>
                
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="relative w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="search-tasks" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search tasks...">
                        </div>
                        
                        <div class="flex space-x-2">
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
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TASK</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ASSIGNED TO</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DUE DATE</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PRIORITY</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="tasks-table-body" class="bg-white divide-y divide-gray-200">
                                <!-- Tasks will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Task Modal -->
    <div id="task-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <h3 id="modal-title" class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Task</h3>
                    <form id="task-form">
                        <input type="hidden" id="task-id">
                        <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                            <div class="sm:col-span-6">
                                <label for="task-title" class="block text-sm font-medium text-gray-700">Task Title</label>
                                <input type="text" id="task-title" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-6">
                                <label for="task-description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea id="task-description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="task-assigned-to" class="block text-sm font-medium text-gray-700">Assigned To</label>
                                <input type="text" id="task-assigned-to" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="task-due-date" class="block text-sm font-medium text-gray-700">Due Date</label>
                                <input type="date" id="task-due-date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="task-status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select id="task-status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="pending">Pending</option>
                                    <option value="in-progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label for="task-priority" class="block text-sm font-medium text-gray-700">Priority</label>
                                <select id="task-priority" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="button" id="save-task" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">
                        Save
                    </button>
                    <button type="button" id="cancel-task" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
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
            const DB_VERSION = 2;
            const STORE_NAME = 'tasks';
            let db;

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
                            
                            // Add sample tasks
                            const sampleTasks = [
                                {
                                    title: 'Harvest corn in Field 3',
                                    description: 'Use the combine harvester to collect corn if weather permits',
                                    assignedTo: 'John Doe',
                                    dueDate: '2025-03-25',
                                    status: 'overdue',
                                    priority: 'high',
                                    createdAt: new Date()
                                },
                                {
                                    title: 'Repair irrigation system',
                                    description: 'Fix the broken pipe in the irrigation system for Field 2',
                                    assignedTo: 'Sarah Johnson',
                                    dueDate: '2025-03-20',
                                    status: 'overdue',
                                    priority: 'high',
                                    createdAt: new Date()
                                },
                                {
                                    title: 'Vaccinate new cattle',
                                    description: 'Administer vaccines to the newly acquired cattle',
                                    assignedTo: 'Michael Brown',
                                    dueDate: '2025-03-18',
                                    status: 'completed',
                                    priority: 'medium',
                                    createdAt: new Date()
                                }
                            ];
                            
                            sampleTasks.forEach(task => {
                                store.add(task);
                            });
                        }
                    };
                });
            }

            // ========== CRUD Operations ========== //
            async function addTask(taskData) {
                return new Promise((resolve, reject) => {
                    const transaction = db.transaction([STORE_NAME], 'readwrite');
                    const store = transaction.objectStore(STORE_NAME);
                    
                    const request = store.add(taskData);
                    
                    request.onsuccess = () => resolve(request.result);
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
                    
                    getRequest.onsuccess = () => {
                        const currentData = getRequest.result;
                        if (!currentData) {
                            reject('Task not found');
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

            async function deleteTask(id) {
                return new Promise((resolve, reject) => {
                    const transaction = db.transaction([STORE_NAME], 'readwrite');
                    const store = transaction.objectStore(STORE_NAME);
                    
                    const request = store.delete(id);
                    
                    request.onsuccess = () => resolve(true);
                    request.onerror = (event) => reject(event.target.error);
                });
            }

            // ========== UI Functions ========== //
            function setupEventListeners() {
                // Add task button
                document.getElementById('add-task-btn').addEventListener('click', showAddTaskModal);
                
                // Save task form
                document.getElementById('save-task').addEventListener('click', handleSaveTask);
                
                // Cancel task form
                document.getElementById('cancel-task').addEventListener('click', () => {
                    document.getElementById('task-modal').classList.add('hidden');
                });
                
                // Search input
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

            async function loadTasksTable(filters = {}) {
                try {
                    const tasks = await getAllTasks(filters);
                    renderTasksTable(tasks);
                } catch (error) {
                    console.error('Error loading tasks:', error);
                    showAlert('Failed to load tasks. ' + error.message, 'error');
                }
            }

            function renderTasksTable(tasks) {
                const tableBody = document.getElementById('tasks-table-body');
                
                if (!tasks || tasks.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                No tasks found. Click "Add New Task" to get started.
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                tableBody.innerHTML = tasks.map(task => `
                    <tr class="hover:bg-gray-50" data-id="${task.id}">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">${task.title}</div>
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
                                ${task.priority}
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

            function showAddTaskModal() {
                document.getElementById('modal-title').textContent = 'Add New Task';
                document.getElementById('task-id').value = '';
                document.getElementById('task-form').reset();
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
                    
                    document.getElementById('task-modal').classList.remove('hidden');
                } catch (error) {
                    console.error('Error showing edit modal:', error);
                    showAlert('Failed to load task data for editing. ' + error.message, 'error');
                }
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
                    updatedAt: new Date()
                };
                
                if (!formData.title) {
                    showAlert('Task title is required', 'error');
                    return;
                }
                
                try {
                    if (id) {
                        await updateTask(parseInt(id), formData);
                        showAlert('Task updated successfully!', 'success');
                    } else {
                        await addTask(formData);
                        showAlert('Task added successfully!', 'success');
                    }
                    
                    document.getElementById('task-modal').classList.add('hidden');
                    await loadTasksTable();
                } catch (error) {
                    console.error('Error saving task:', error);
                    showAlert('Error saving task. ' + error.message, 'error');
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

            // ========== Helper Functions ========== //
            function formatDate(dateString) {
                if (!dateString) return '-';
                const options = { year: 'numeric', month: 'short', day: 'numeric' };
                return new Date(dateString).toLocaleDateString(undefined, options);
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

            function showAlert(message, type = 'info') {
                // Your existing alert implementation
            }

            // ========== Initialize Application ========== //
            async function initApp() {
                try {
                    await initDB();
                    setupEventListeners();
                    await loadTasksTable();
                    showAlert('Task management loaded successfully', 'success');
                } catch (error) {
                    console.error('Initialization error:', error);
                    showAlert('Failed to initialize task management. Please refresh the page.', 'error');
                }
            }

            // Start the application
            initApp();
        });
    </script>
</body>
</html>