<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php';
checkAuthentication();

// Get user details
$current_user = [
    'name' => $_SESSION['full_name'] ?? 'Unknown User',
    'email' => $_SESSION['username'] ?? 'No email',
    'role' => $_SESSION['role'] ?? 'Unknown Role',
    'initials' => getInitials($_SESSION['full_name'] ?? 'UU'),
    'profile_picture' => ''
];

// Fetch profile data
try {
    $stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($profile) {
        $current_user['name'] = $profile['full_name'] ?? $current_user['name'];
        $current_user['email'] = $profile['email'] ?? $current_user['email'];
        $current_user['profile_picture'] = $profile['profile_picture'] ?? '';
    }
} catch (PDOException $e) {
    error_log("Error fetching profile: " . $e->getMessage());
}

function getInitials($name) {
    $names = explode(' ', $name);
    $initials = '';
    foreach ($names as $n) {
        $initials .= strtoupper(substr($n, 0, 1));
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: 'UU';
}

// --- Fetch Real Analytics Data ---
$userId = $_SESSION['user_id'];
$analytics = [];

try {
    // 1. Crops Analytics
    $cropStats = $pdo->prepare("SELECT 
        COUNT(*) as total_crops,
        SUM(CASE WHEN status = 'growing' THEN 1 ELSE 0 END) as active_crops,
        SUM(area) as total_area,
        SUM(expected_yield) as total_yield
        FROM crops WHERE user_id = ?");
    $cropStats->execute([$userId]);
    $analytics['crops'] = $cropStats->fetch(PDO::FETCH_ASSOC);

    // Crop Types for Chart
    $cropTypes = $pdo->prepare("SELECT crop_type, COUNT(*) as count FROM crops WHERE user_id = ? GROUP BY crop_type");
    $cropTypes->execute([$userId]);
    $analytics['crop_types'] = $cropTypes->fetchAll(PDO::FETCH_ASSOC);

    // 2. Livestock Analytics
    $livestockStats = $pdo->prepare("SELECT 
        COUNT(*) as total_animals,
        SUM(CASE WHEN status = 'healthy' THEN 1 ELSE 0 END) as healthy,
        SUM(CASE WHEN status = 'sick' THEN 1 ELSE 0 END) as sick
        FROM livestock WHERE user_id = ?");
    $livestockStats->execute([$userId]);
    $analytics['livestock'] = $livestockStats->fetch(PDO::FETCH_ASSOC);

    // Livestock Types for Chart
    $livestockTypes = $pdo->prepare("SELECT type, COUNT(*) as count FROM livestock WHERE user_id = ? GROUP BY type");
    $livestockTypes->execute([$userId]);
    $analytics['livestock_types'] = $livestockTypes->fetchAll(PDO::FETCH_ASSOC);

    // 3. Tasks Analytics
    $taskStats = $pdo->prepare("SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM tasks WHERE user_id = ?");
    $taskStats->execute([$userId]);
    $analytics['tasks'] = $taskStats->fetch(PDO::FETCH_ASSOC);

    // 4. Inventory Analytics
    $inventoryStats = $pdo->prepare("SELECT 
    <link rel="icon" href="./images/logo1.png" type="image/png">
    <link href="./dist/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .card-hover { transition: transform 0.2s; }
        .card-hover:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="h-full overflow-hidden">
    <div class="flex h-full">
        <!-- Sidebar (Reused) -->
        <aside class="w-64 bg-gradient-to-b from-blue-900 to-blue-800 text-white shadow-xl h-screen flex flex-col overflow-y-auto">
            <div class="p-5 flex items-center space-x-3 flex-shrink-0 bg-gradient-to-b from-blue-900 to-blue-900 sticky top-0 z-10">
                <div class="w-10 h-10 rounded-full flex items-center justify-center">
                    <img src="./images/logo5.png" alt="App Logo" class="h-10 w-10 object-contain">
                </div>
                <h1 class="text-xl font-bold">AgriVision Pro</h1>
            </div>
            <nav class="flex-grow pt-2 px-3 space-y-1">
                <a href="dashboard.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-50 text-blue-100 hover:text-white transition-colors">
                    <i class="fas fa-home w-5 mr-2"></i> Dashboard
                </a>
                <a href="crops.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-50 text-blue-100 hover:text-white transition-colors">
                    <i class="fas fa-seedling w-5 mr-2"></i> Crops
                </a>
                <a href="livestock.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-50 text-blue-100 hover:text-white transition-colors">
                    <i class="fas fa-paw w-5 mr-2"></i> Livestock
                </a>
                <a href="inventory.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-50 text-blue-100 hover:text-white transition-colors">
                    <i class="fas fa-boxes w-5 mr-2"></i> Inventory
                </a>
                <a href="tasks.php" class="flex items-center px-3 py-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-50 text-blue-100 hover:text-white transition-colors">
                    <i class="fas fa-tasks w-5 mr-2"></i> Tasks
                </a>
                <div class="pt-4 pb-2">
                    <p class="px-3 text-xs font-semibold text-blue-300 uppercase tracking-wider">Analytics</p>
                </div>
                <a href="analytics.php" class="flex items-center px-3 py-2 rounded-lg bg-blue-700 text-white font-medium">
                    <i class="fas fa-chart-line w-5 mr-2"></i> Overview
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm z-10">
                <div class="flex items-center justify-between px-6 py-3">
                    <h2 class="text-xl font-semibold text-gray-800">Analytics Overview</h2>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <?php if (!empty($current_user['profile_picture'])): ?>
                                <img src="<?= htmlspecialchars($current_user['profile_picture']) ?>" class="h-8 w-8 rounded-full object-cover">
                            <?php else: ?>
                                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                                    <?= $current_user['initials'] ?>
                                </div>
                            <?php endif; ?>
                            <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($current_user['name']) ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                
                <!-- Key Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Crops -->
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover border-l-4 border-green-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Crops</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= $analytics['crops']['total_crops'] ?? 0 ?></h3>
                            </div>
                            <div class="p-2 bg-green-50 rounded-lg">
                                <i class="fas fa-seedling text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-gray-600">
                            <span class="text-green-600 font-medium mr-2"><?= $analytics['crops']['active_crops'] ?? 0 ?></span> Active
                            <span class="mx-2">•</span>
                            <span><?= $analytics['crops']['total_area'] ?? 0 ?> Acres</span>
                        </div>
                    </div>

                    <!-- Livestock Health -->
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover border-l-4 border-blue-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Livestock</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= $analytics['livestock']['total_animals'] ?? 0 ?></h3>
                            </div>
                            <div class="p-2 bg-blue-50 rounded-lg">
                                <i class="fas fa-paw text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-gray-600">
                            <span class="text-green-600 font-medium mr-2"><?= $analytics['livestock']['healthy'] ?? 0 ?></span> Healthy
                            <span class="mx-2">•</span>
                            <span class="text-red-500 font-medium"><?= $analytics['livestock']['sick'] ?? 0 ?></span> Sick
                        </div>
                    </div>

                    <!-- Task Efficiency -->
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover border-l-4 border-yellow-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pending Tasks</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= $analytics['tasks']['pending'] ?? 0 ?></h3>
                            </div>
                            <div class="p-2 bg-yellow-50 rounded-lg">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-gray-600">
                            <span class="text-blue-600 font-medium mr-2"><?= $analytics['tasks']['in_progress'] ?? 0 ?></span> In Progress
                            <span class="mx-2">•</span>
                            <span class="text-green-600 font-medium"><?= $analytics['tasks']['completed'] ?? 0 ?></span> Done
                        </div>
                    </div>

                    <!-- Inventory Status -->
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover border-l-4 border-red-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Low Stock Items</p>
                                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= $analytics['inventory']['low_stock'] ?? 0 ?></h3>
                            </div>
                            <div class="p-2 bg-red-50 rounded-lg">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-gray-600">
                            <span>Total Items: <?= $analytics['inventory']['total_items'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Crop Distribution Chart -->
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Crop Distribution</h3>
                        <div class="h-64">
                            <canvas id="cropChart"></canvas>
                        </div>
                    </div>

                    <!-- Livestock Distribution Chart -->
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Livestock Composition</h3>
                        <div class="h-64">
                            <canvas id="livestockChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Task Progress Section -->
                <div class="bg-white p-6 rounded-xl shadow-sm mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Task Status Overview</h3>
                    <div class="relative pt-1">
                        <div class="flex mb-2 items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                                    Completion Rate
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold inline-block text-blue-600">
                                    <?php 
                                        $total = $analytics['tasks']['total_tasks'] ?: 1;
                                        $completed = $analytics['tasks']['completed'];
                                        echo round(($completed / $total) * 100) . "%";
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
                            <div style="width:<?= round(($completed / $total) * 100) ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Chart Initialization -->
    <script>
        // Prepare Data from PHP
        const cropData = <?= json_encode($analytics['crop_types']) ?>;
        const livestockData = <?= json_encode($analytics['livestock_types']) ?>;

        // Crop Chart
        const cropCtx = document.getElementById('cropChart').getContext('2d');
        new Chart(cropCtx, {
            type: 'doughnut',
            data: {
                labels: cropData.map(item => item.crop_type),
                datasets: [{
                    data: cropData.map(item => item.count),
                    backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        // Livestock Chart
        const livestockCtx = document.getElementById('livestockChart').getContext('2d');
        new Chart(livestockCtx, {
            type: 'bar',
            data: {
                labels: livestockData.map(item => item.type),
                datasets: [{
                    label: 'Count',
                    data: livestockData.map(item => item.count),
                    backgroundColor: '#3B82F6',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
</body>
</html>