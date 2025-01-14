<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Connect to database
    $pdo = new PDO('mysql:host=localhost;dbname=spin_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Begin transaction
    $pdo->beginTransaction();

    // Get or create user with initial balance
    $user_id = $_SESSION['user_id'] ?? 1;
    
    // Try to get current balance
    $stmt = $pdo->prepare("SELECT tickets FROM users WHERE id = :user_id FOR UPDATE");
    $stmt->execute(['user_id' => $user_id]);
    $userBalance = $stmt->fetchColumn();

    // If no tickets found, set to 0
    if ($userBalance === false) {
        $userBalance = 0;
    }

    error_log("Current balance before check: " . $userBalance);

    // For POST requests, check if we have enough tickets before decreasing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userBalance <= 0) {
        $pdo->rollBack();
        $response = [
            'success' => false,
            'message' => 'Insufficient balance'
        ];
    } else {
        // Handle ticket updates
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $pdo->prepare("UPDATE users SET tickets = tickets - 1 WHERE id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $userBalance--;
            error_log("Decreased balance to: " . $userBalance);
            
            // Commit the transaction for POST requests
            $pdo->commit();
        } else {
            // For GET requests, just rollback since we don't need to save any changes
            $pdo->rollBack();
        }

        $response = [
            'success' => true,
            'tickets' => $userBalance
        ];
    }

} catch (Exception $e) {
    // Rollback on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in check_balance.php: " . $e->getMessage());
    
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>