<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get user's current coin and ticket balance
try {
    $pdo = new PDO('mysql:host=localhost;dbname=spin_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT tickets, coins FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id'] ?? 1]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $tickets = $userData['tickets'] ?? 0;
    $coins = $userData['coins'] ?? 0;
} catch (Exception $e) {
    $tickets = 0;
    $coins = 0;
}

// Conversion rate
$coinsPerTicket = 100; 

// Calculate maximum tickets user can buy with their coins
$maxTickets = floor($coins / $coinsPerTicket);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Tickets</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-blue-500 text-white p-6 rounded-b-3xl">
        <div class="flex items-center">
            <button onClick="window.location.href='spin.php'" class="w-8 h-8 bg-white rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-arrow-left text-blue-500"></i>
            </button>
            <h1 class="text-xl font-bold">Buy Tickets</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 mt-8">
        <!-- Balance Info -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                    <p class="text-gray-500">Your Coins</p>
                    <p class="text-2xl font-bold text-yellow-500">
                        <i class="fas fa-coins mr-2"></i><?php echo number_format($coins); ?>
                    </p>
                </div>
                <div class="text-center">
                    <p class="text-gray-500">Your Tickets</p>
                    <p class="text-2xl font-bold text-purple-500">
                        <i class="fas fa-ticket-alt mr-2"></i><?php echo number_format($tickets); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Conversion Info -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold mb-4">Convert Coins to Tickets</h2>
            <p class="text-gray-600 mb-4">
                Exchange Rate: <?php echo number_format($coinsPerTicket); ?> Coins = 1 Ticket
            </p>
            
            <div class="flex flex-col space-y-4">
                <?php
                $ticketOptions = [1, 5, 10];
                foreach ($ticketOptions as $amount) {
                    $requiredCoins = $amount * $coinsPerTicket;
                    $canAfford = $coins >= $requiredCoins;
                ?>
                    <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
                        <div>
                            <p class="font-bold"><?php echo $amount; ?> Ticket<?php echo $amount > 1 ? 's' : ''; ?></p>
                            <p class="text-sm text-gray-500"><?php echo number_format($requiredCoins); ?> Coins</p>
                        </div>
                        <button 
                            onclick="buyTickets(<?php echo $amount; ?>)"
                            class="<?php echo $canAfford ? 'bg-blue-500 hover:bg-blue-600' : 'bg-gray-400 cursor-not-allowed'; ?> text-white px-6 py-2 rounded-full transition"
                            <?php echo !$canAfford ? 'disabled' : ''; ?>
                        >
                            <?php echo $canAfford ? 'Buy' : 'Not enough coins'; ?>
                        </button>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
        function buyTickets(amount) {
            fetch('buy_tickets.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    amount: amount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Successfully purchased ' + amount + ' ticket(s)!');
                    window.location.reload();
                } else {
                    alert(data.message || 'Insufficient coins!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Something went wrong!');
            });
        }
    </script>
</body>
</html> 