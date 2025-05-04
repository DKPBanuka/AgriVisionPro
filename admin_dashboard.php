<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriVision Pro - Admin Dashboard</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .sidebar {
            transition: all 0.3s ease;
        }
        .dashboard-card {
            transition: all 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1);
        }
        .active-menu {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            color: #047857;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar bg-white w-64 px-4 py-6 shadow-lg">
            <div class="flex items-center justify-center mb-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span class="ml-2 text-xl font-bold text-emerald-600">AgriVision Pro</span>
            </div>
            
            <div class="mb-6 px-2">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Main Menu</div>
                <nav>
                    <a href="#" class="active-menu flex items-center px-4 py-3 text-sm font-medium rounded-lg mb-1">
                        <i class="fas fa-tachometer-alt mr-3"></i> Admin Dashboard
                    </a>
                    <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg mb-1">
                        <i class="fas fa-users mr-3"></i> User Management
                    </a>
                    <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg mb-1">
                        <i class="fas fa-leaf mr-3"></i> Crop Management
                    </a>
                    <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg mb-1">
                        <i class="fas fa-cow mr-3"></i> Livestock Management
                    </a>
                    <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg mb-1">
                        <i class="fas fa-cog mr-3"></i> System Settings
                    </a>
                </nav>
            </div>
            
            <div class="px-2">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Reports</div>
                <nav>
                    <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg mb-1">
                        <i class="fas fa-chart-bar mr-3"></i> Farm Analytics
                    </a>
                    <a href="#" class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg mb-1">
                        <i class="fas fa-file-invoice-dollar mr-3"></i> Financial Reports
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="flex-1 overflow-auto">
            <!-- Topbar -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center">
                        <button class="text-gray-500 focus:outline-none lg:hidden">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1 class="ml-4 text-xl font-semibold text-gray-800">Admin Dashboard</h1>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="relative">
                            <button class="text-gray-500 focus:outline-none mx-4">
                                <i class="fas fa-bell"></i>
                            </button>
                            <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
                        </div>
                        
                        <div class="relative">
                            <button class="flex items-center focus:outline-none">
                                <div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <span class="ml-2 text-sm font-medium text-gray-700 hidden md:block"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
                                <span class="ml-1 text-xs bg-emerald-100 text-emerald-800 px-2 py-1 rounded-full">Admin</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <main class="p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Administration Overview</h2>
                    <p class="text-gray-600">Manage your farm operations and users.</p>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="dashboard-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Total Users</h3>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">24</p>
                                <p class="mt-1 text-sm text-green-600 flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> 3 new this week
                                </p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="#" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">Manage Users</a>
                        </div>
                    </div>
                    
                    <div class="dashboard-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Active Farms</h3>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">8</p>
                                <p class="mt-1 text-sm text-green-600 flex items-center">
                                    <i class="fas fa-check-circle mr-1"></i> All operational
                                </p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                <i class="fas fa-tractor"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="#" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View Farms</a>
                        </div>
                    </div>
                    
                    <div class="dashboard-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">System Alerts</h3>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">3</p>
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Needs attention
                                </p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                                <i class="fas fa-bell"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="#" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View Alerts</a>
                        </div>
                    </div>
                    
                    <div class="dashboard-card bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Pending Tasks</h3>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">12</p>
                                <p class="mt-1 text-sm text-yellow-600 flex items-center">
                                    <i class="fas fa-clock mr-1"></i> 4 overdue
                                </p>
                            </div>
                            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
                                <i class="fas fa-tasks"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="#" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View Tasks</a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Users -->
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Recent Users</h3>
                        <a href="#" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View All</a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mr-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">John Smith</div>
                                                <div class="text-sm text-gray-500">john@example.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Manager</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2 hours ago</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Active</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="#" class="text-emerald-600 hover:text-emerald-900 mr-3">Edit</a>
                                        <a href="#" class="text-red-600 hover:text-red-900">Delete</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mr-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">Sarah Johnson</div>
                                                <div class="text-sm text-gray-500">sarah@example.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">User</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 day ago</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Active</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="#" class="text-emerald-600 hover:text-emerald-900 mr-3">Edit</a>
                                        <a href="#" class="text-red-600 hover:text-red-900">Delete</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 mr-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">Michael Brown</div>
                                                <div class="text-sm text-gray-500">michael@example.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">User</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3 days ago</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">Inactive</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="#" class="text-emerald-600 hover:text-emerald-900 mr-3">Edit</a>
                                        <a href="#" class="text-red-600 hover:text-red-900">Delete</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">System Status</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-3">
                                        <i class="fas fa-server"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">Application Server</p>
                                        <p class="text-xs text-gray-500">Running version 2.4.1</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Online</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-3">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">Database</p>
                                        <p class="text-xs text-gray-500">MySQL 8.0</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Online</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 mr-3">
                                        <i class="fas fa-cloud"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">Cloud Storage</p>
                                        <p class="text-xs text-gray-500">85% capacity used</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Warning</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-3">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">Security</p>
                                        <p class="text-xs text-gray-500">Last scan: 2 hours ago</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Secure</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Admin Actions</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <a href="#" class="p-4 bg-gray-50 rounded-lg text-center hover:bg-purple-50 transition duration-200">
                                <div class="h-10 w-10 mx-auto bg-purple-100 rounded-full flex items-center justify-center text-purple-600 mb-2">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <p class="text-sm font-medium">Add User</p>
                            </a>
                            <a href="#" class="p-4 bg-gray-50 rounded-lg text-center hover:bg-blue-50 transition duration-200">
                                <div class="h-10 w-10 mx-auto bg-blue-100 rounded-full flex items-center justify-center text-blue-600 mb-2">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <p class="text-sm font-medium">Settings</p>
                            </a>
                            <a href="#" class="p-4 bg-gray-50 rounded-lg text-center hover:bg-green-50 transition duration-200">
                                <div class="h-10 w-10 mx-auto bg-green-100 rounded-full flex items-center justify-center text-green-600 mb-2">
                                    <i class="fas fa-database"></i>
                                </div>
                                <p class="text-sm font-medium">Backup DB</p>
                            </a>
                            <a href="#" class="p-4 bg-gray-50 rounded-lg text-center hover:bg-red-50 transition duration-200">
                                <div class="h-10 w-10 mx-auto bg-red-100 rounded-full flex items-center justify-center text-red-600 mb-2">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <p class="text-sm font-medium">View Alerts</p>
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>