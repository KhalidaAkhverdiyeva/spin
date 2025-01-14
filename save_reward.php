<?php
session_start();

class RewardProcessor {
    private PDO $pdo;
    private int $userId;
    private const ENCRYPTION_KEY = 5;

    public function __construct(PDO $pdo, int $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    public function processReward(string $encryptedType, string $encryptedNum): array {
        try {
            $this->pdo->beginTransaction();

            $rewardType = $this->decryptData($encryptedType);
            $rewardNum = (float)$this->decryptData($encryptedNum);

            $currentCoins = $this->getCurrentCoins();
            $result = $this->handleReward($rewardType, $rewardNum, $currentCoins);

            $this->pdo->commit();
            return $this->createSuccessResponse($result);

        } catch (Exception $e) {
            $this->handleError($e);
            return $this->createErrorResponse($e->getMessage());
        }
    }

    private function decryptData(string $encrypted): string {
        $decoded = base64_decode($encrypted);
        $decrypted = '';
        for ($i = 0; $i < strlen($decoded); $i++) {
            $decrypted .= chr(ord($decoded[$i]) - self::ENCRYPTION_KEY);
        }
        return $decrypted;
    }

    private function getCurrentCoins(): int {
        $stmt = $this->pdo->prepare("SELECT coins FROM users WHERE id = :user_id FOR UPDATE");
        $stmt->execute(['user_id' => $this->userId]);
        return (int)$stmt->fetchColumn();
    }

    private function handleReward(string $rewardType, float $rewardNum, int $currentCoins): array {
        $result = [
            'coinsAdded' => 0,
            'newTickets' => null,
            'newCoins' => $currentCoins
        ];

        switch ($rewardType) {
            case 'COINS':
                $result['coinsAdded'] = $rewardNum;
                $this->updateCoins($rewardNum);
                $result['newCoins'] = $this->getUpdatedCoins();
                break;

            case 'TICKET':
                $this->updateTickets($rewardNum);
                $result['newTickets'] = $this->getUpdatedTickets();
                break;
        }

        return $result;
    }

    private function updateCoins(float $amount): void {
        $stmt = $this->pdo->prepare("UPDATE users SET coins = coins + :amount WHERE id = :user_id");
        $stmt->execute([
            'amount' => $amount,
            'user_id' => $this->userId
        ]);
    }

    private function updateTickets(float $amount): void {
        $stmt = $this->pdo->prepare("UPDATE users SET tickets = tickets + :amount WHERE id = :user_id");
        $stmt->execute([
            'amount' => $amount,
            'user_id' => $this->userId
        ]);
    }

    private function getUpdatedCoins(): int {
        $stmt = $this->pdo->prepare("SELECT coins FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $this->userId]);
        return (int)$stmt->fetchColumn();
    }

    private function getUpdatedTickets(): int {
        $stmt = $this->pdo->prepare("SELECT tickets FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $this->userId]);
        return (int)$stmt->fetchColumn();
    }

    private function createSuccessResponse(array $result): array {
        return [
            'success' => true,
            'message' => 'Reward saved successfully',
            'newBalance' => [
                'coins' => $result['newCoins'],
                'coinsAdded' => $result['coinsAdded'],
                'tickets' => $result['newTickets']
            ]
        ];
    }

    private function createErrorResponse(string $errorMessage): array {
        return [
            'success' => false,
            'message' => 'Error: ' . $errorMessage
        ];
    }

    private function handleError(Exception $e): void {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        error_log("Error in save_reward.php: " . $e->getMessage());
    }
}

// Usage
try {
    $pdo = new PDO('mysql:host=localhost;dbname=spin_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userId = $_SESSION['user_id'] ?? 1;
    $rewardProcessor = new RewardProcessor($pdo, $userId);

    $postData = json_decode(file_get_contents('php://input'), true);
    $response = $rewardProcessor->processReward(
        $postData['reward_type'],
        $postData['reward_num']
    );

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
