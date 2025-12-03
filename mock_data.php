<?php
// mock_data.php
require_once 'includes/db_connect.php';

try {
    // 1. Get ALL Users
    $stmt = $pdo->query("SELECT id FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$users) {
        die("Error: No users found. Please register a user first.");
    }

    foreach ($users as $user) {
        $userId = $user['id'];
        echo "Processing User ID: " . $userId . "\n";

    // 2. Insert Mock Crops
    echo "Inserting Crops...\n";
    try {
        // crop_type ENUM: 'grain','vegetable','fruit','legume','other'
        // status ENUM: 'growing','harvested','planned','problem'
        // Timestamps: createdAt, updatedAt
        $crops = [
            ['Wheat', 'Winter Wheat', 'grain', 'Field A', 10.5, '2023-10-15', '2024-06-20', 'growing', 5000, 'Main crop for this season'],
            ['Corn', 'Sweet Corn', 'vegetable', 'Field B', 5.0, '2024-04-01', '2024-08-15', 'growing', 2000, 'Experimenting with new variety'],
            ['Soybeans', 'GMO Resistant', 'legume', 'Field C', 8.2, '2024-05-10', '2024-09-30', 'planned', 3500, 'Rotation crop'],
            ['Tomatoes', 'Roma', 'vegetable', 'Greenhouse 1', 0.5, '2024-02-01', '2024-05-20', 'harvested', 800, 'High yield expected']
        ];

        $cropSql = "INSERT INTO crops (user_id, name, variety, crop_type, field, area, planted_date, harvest_date, status, expected_yield, notes, image_url, createdAt, updatedAt) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $cropStmt = $pdo->prepare($cropSql);

        foreach ($crops as $crop) {
            $params = array_merge([$userId], $crop, [null]); 
            $cropStmt->execute($params);
        }
        echo "Inserted " . count($crops) . " crops.\n";
    } catch (PDOException $e) {
        echo "Error inserting crops: " . $e->getMessage() . "\n";
    }

    // 3. Insert Mock Livestock
    echo "Inserting Livestock...\n";
    try {
        // Timestamps: createdAt, updatedAt
        $livestock = [
            ['COW-001', 'Cattle', 'Holstein', 3, 2, '2021-02-15', 650, 'kg', 'healthy', 'Barn A', 'High milk producer'],
            ['COW-002', 'Cattle', 'Jersey', 2, 5, '2022-11-10', 450, 'kg', 'sick', 'Quarantine', 'Showing signs of flu'],
            ['SHP-101', 'Sheep', 'Merino', 1, 0, '2023-04-20', 60, 'kg', 'healthy', 'Pasture 1', 'Wool quality excellent'],
            ['CHK-500', 'Chicken', 'Leghorn', 0, 6, '2023-10-01', 2.5, 'kg', 'healthy', 'Coop 1', 'Laying eggs']
        ];

        $livestockSql = "INSERT INTO livestock (user_id, idTag, type, breed, ageYears, ageMonths, birthDate, weight, weightUnit, status, location, notes, image_url, createdAt, updatedAt) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $livestockStmt = $pdo->prepare($livestockSql);

        foreach ($livestock as $animal) {
            $params = array_merge([$userId], $animal, [null]);
            $livestockStmt->execute($params);
        }
        echo "Inserted " . count($livestock) . " livestock.\n";
    } catch (PDOException $e) {
        echo "Error inserting livestock: " . $e->getMessage() . "\n";
    }

    // 4. Insert Mock Inventory
    echo "Inserting Inventory...\n";
    try {
        // Timestamps: createdAt, updatedAt
        $inventory = [
            ['Fertilizer NPK', 'Fertilizers', 500, 'kg', 'Warehouse A', 'Standard NPK 10-10-10'],
            ['Tractor Fuel', 'Fuel', 200, 'liters', 'Fuel Tank', 'Diesel for tractors'],
            ['Corn Seeds', 'Seeds', 50, 'kg', 'Storage Room', 'Hybrid corn seeds'],
            ['Pesticide X', 'Chemicals', 5, 'liters', 'Chemical Cabinet', 'For pest control'],
            ['Shovels', 'Tools', 10, 'pcs', 'Tool Shed', 'General purpose shovels']
        ];

        $inventorySql = "INSERT INTO inventory (user_id, name, category, quantity, unit, location, notes, image_url, createdAt, updatedAt) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $inventoryStmt = $pdo->prepare($inventorySql);

        foreach ($inventory as $item) {
            $params = array_merge([$userId], $item, [null]);
            $inventoryStmt->execute($params);
        }
        echo "Inserted " . count($inventory) . " inventory items.\n";
    } catch (PDOException $e) {
        echo "Error inserting inventory: " . $e->getMessage() . "\n";
    }

    // 5. Insert Mock Tasks
    echo "Inserting Tasks...\n";
    try {
        // status ENUM: 'pending','in-progress','completed'
        // Timestamps: created_at, updated_at
        $tasks = [
            ['Inspect Field A', 'Check for pest infestation in Wheat', 'John Doe', '2024-05-01', 'pending', 'high'],
            ['Repair Fence', 'Fix broken fence in Pasture 1', 'Jane Smith', '2024-04-25', 'pending', 'medium'], // Changed 'overdue' to 'pending'
            ['Buy Fertilizer', 'Order NPK fertilizer for next season', 'Mike Johnson', '2024-05-10', 'in-progress', 'low'],
            ['Vaccinate Cattle', 'Routine vaccination for herd', 'Vet', '2024-05-15', 'pending', 'high']
        ];

        $taskSql = "INSERT INTO tasks (user_id, title, description, assigned_to, due_date, status, priority, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $taskStmt = $pdo->prepare($taskSql);

        foreach ($tasks as $task) {
            $taskStmt->execute(array_merge([$userId], $task));
        }
        echo "Inserted " . count($tasks) . " tasks.\n";
    } catch (PDOException $e) {
        echo "Error inserting tasks: " . $e->getMessage() . "\n";
    }

    echo "Mock data insertion completed for User ID: " . $userId . "\n";
    } // End foreach user

    echo "All mock data insertion completed!\n";

} catch (PDOException $e) {
    die("General Database Error: " . $e->getMessage());
}
?>
