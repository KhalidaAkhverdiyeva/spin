<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Decode the POST data
$postData = json_decode(file_get_contents('php://input'), true);
$ticketAmount = $postData['amount'] ?? 0;

// Configuration
$coinsPerTicket = 100;
$requiredCoins = $ticketAmount * $coinsPerTicket;

try {
    $pdo = new PDO('mysql:host=localhost;dbname=spin_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Begin transaction
    $pdo->beginTransaction();

    // Get user's current coin balance from database
    $stmt = $pdo->prepare("SELECT coins FROM users WHERE id = :user_id FOR UPDATE");
    $stmt->execute(['user_id' => $_SESSION['user_id'] ?? 1]);
    $currentCoins = $stmt->fetchColumn();

    if ($currentCoins >= $requiredCoins) {
        // Update user's ticket count and deduct coins
        $stmt = $pdo->prepare("UPDATE users SET 
            tickets = tickets + :amount,
            coins = coins - :coins 
            WHERE id = :user_id");
        $stmt->execute([
            'amount' => $ticketAmount,
            'coins' => $requiredCoins,
            'user_id' => $_SESSION['user_id'] ?? 1
        ]);

        $pdo->commit();

        // Get updated balances
        $stmt = $pdo->prepare("SELECT tickets, coins FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id'] ?? 1]);
        $newBalance = $stmt->fetch(PDO::FETCH_ASSOC);

        $response = [
            'success' => true,
            'message' => 'Tickets purchased successfully',
            'newBalance' => [
                'tickets' => $newBalance['tickets'],
                'coins' => $newBalance['coins']
            ]
        ];
    } else {
        $pdo->rollBack();
        $response = [
            'success' => false,
            'message' => 'Insufficient coins. Required: ' . $requiredCoins . ', Available: ' . $currentCoins
        ];
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response); 