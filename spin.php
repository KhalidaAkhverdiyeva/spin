<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// index.php
// include 'core/config.php'; // Commented out due to missing file
// require_once 'core/auth.php'; // Commented out due to missing file

// Get the actual ticket count from database
try {
    $pdo = new PDO('mysql:host=localhost;dbname=spin_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT tickets FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id'] ?? 1]);
    $tickets = $stmt->fetchColumn();
    
    // If no user found or tickets is null, set to 0 instead of 5
    if ($tickets === false) {
        $tickets = 0;
    }
} catch (Exception $e) {
    error_log("Error fetching initial ticket count: " . $e->getMessage());
    $tickets = 0;
}

// Mock data for testing
$lang = [
    "spinner" => "Spinner",
    "start_for_click" => "Click to start",
    "cong" => "Congratulations!",
    "spin_reward_added" => "Your reward has been added!",
    "unsufficient_balance" => "Insufficient balance!",
    "spin_dont_t" => "Do you want to use coins?",
    "error" => "Error",
    "something_went_wrong" => "Something went wrong."
];

// Function to shuffle the rewards array
function shuffleRewards($rewards) {
    shuffle($rewards);
    return $rewards;
}

// Update the rewards array
$rewards = [
    ["spin_reward_type" => "USDT", "spin_reward_num" => 10, "icon" => "", "probability" => 0.15],
    ["spin_reward_type" => "COINS", "spin_reward_num" => 20, "icon" => "", "probability" => 0.20],
    ["spin_reward_type" => "POINTS", "spin_reward_num" => 30, "icon" => "", "probability" => 0.20],
    ["spin_reward_type" => "TICKET", "spin_reward_num" => 1, "icon" => "", "probability" => 0.20],
    ["spin_reward_type" => "GIFT CARD", "spin_reward_num" => 5, "icon" => "", "probability" => 0.15],
    ["spin_reward_type" => "BONUS", "spin_reward_num" => 50, "icon" => "", "probability" => 0.10]
];

// Make sure probabilities sum to 1.0
// Shuffle the rewards to ensure randomness on page load
$rewards = shuffleRewards($rewards);

// Add database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=spin_db', 'root', ''); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit; 
}

$query = "SELECT spin_reward_type, spin_reward_num, icon, probability 
          FROM spin_rewards 
          WHERE is_active = 1
          ORDER BY id ASC 
          LIMIT 6"; 
$stmt = $pdo->prepare($query);
$stmt->execute();
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Spin</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap');

        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(to top, #9DB5D1 0%, #4A5B70 50%, #010101 100%);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .wrapper {
            width: 95%;
            max-width: 500px;
            background-color: #ffffff;
            position: relative;
            margin: 0.5rem auto;
            padding: 0.8rem;
            border-radius: 1em;
            box-shadow: 0 4em 5em rgba(27, 8, 53, 0.2);
            flex: 1;
        }

        .main>h1 {
            text-align: center;
            font-size: 30px;
            text-shadow: 2px 2px rgb(200, 200, 200);
            margin-bottom: 20px;
        }

        .container {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
        }

        .reward {
            display: flex;
            align-items: center;
        }

        .reward img {
            margin-right: 10px;
        }

        #wheel-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            aspect-ratio: 1;
            margin: 0 auto;
            filter: drop-shadow(0 0 10px rgba(0,0,0,0.2));
        }

        #wheel {
            width: 100% !important;
            height: 100% !important;
            transition: filter 0.3s ease;
        }

        #spinner-arrow {
            position: absolute;
            width: 12%;
            left: 50%;
            top: -6%;
            transform: translateX(-50%) rotate(-90deg);
            z-index: 5;
            filter: drop-shadow(2px 2px 2px rgba(0, 0, 0, 0.3));
            opacity: 1;
            visibility: visible;
        }

        #spin-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: clamp(50px, 12vw, 70px);
            height: clamp(50px, 12vw, 70px);
            border-radius: 50%;
            background: linear-gradient(145deg, #ffffff, #f0f0f0);
            box-shadow: 
                5px 5px 10px rgba(0,0,0,0.1),
                -5px -5px 10px rgba(255,255,255,0.8);
            color: #2AA800;
            font-weight: bold;
            font-size: clamp(12px, 2.5vw, 16px);
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            border: none;
        }

        #spin-btn:hover {
            background: linear-gradient(145deg, #333333, #444444);
            color: white;
            transform: translate(-50%, -50%) scale(1.05);
        }

        #spin-btn:active {
            transform: translate(-50%, -50%) scale(0.95);
            background: linear-gradient(145deg, #222222, #333333);
        }

        #spin-btn:disabled {
            background: linear-gradient(145deg, #cccccc, #999999);
            color: #666666;
            cursor: not-allowed;
            transform: translate(-50%, -50%) scale(1);
        }

        #final-value {
            font-size: 1.2em;
            text-align: center;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            color: #202020;
            font-weight: 500;
        }

        .ticket-header {
            background: linear-gradient(to bottom, #2a2a2a, #1a1a1a);
            color: white;
            padding: 0.8rem;
            border-radius: 15px;
            margin-top: 0.5rem;
            margin-bottom: 1rem;
            text-align: center;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            border: 2px solid #333;
        }

        .ticket-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            font-size: clamp(32px, 6vw, 48px);
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            letter-spacing: 1px;
        }

        .ticket-info i {
            color: #ff6b6b;
            font-size: 0.85em;
            filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.3));
        }

        .ticket-text {
            font-size: clamp(18px, 4vw, 24px);
            margin-bottom: 1.2rem;
            font-weight: 600;
            color: #aaa;
        }

        .probability-info {
            position: absolute;
            font-size: 10px;
            color: rgba(255, 255, 255, 0.8);
            pointer-events: none;
        }

        .glow-effect {
            position: absolute;
            inset: -20px;
            background: radial-gradient(
                circle,
                rgba(255,255,255,0.2) 0%,
                rgba(255,255,255,0.1) 30%,
                rgba(255,255,255,0) 70%
            );
            opacity: 0;
            pointer-events: none;
            animation: glow 2s ease-in-out infinite;
        }

        .confetti {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1000;
            background: url('game/confetti.gif') repeat;
            background-size: contain;
            animation: fade 2s forwards;
        }

        .sound-control {
            transition: all 0.3s ease;
        }

        .sound-control:hover {
            transform: scale(1.1);
        }

        .sound-control button {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .sound-control i {
            color: #0027a8;
        }

        /* Önce CSS animasyonunu tanımlayalım */
        @keyframes wrapperGlow {
            0% {
                background-color: rgba(212, 247, 211, 0.3);
                box-shadow: 0 4em 5em rgba(27, 8, 53, 0.1);
            }

            50% {
                background-color: rgba(64, 252, 57, 0.2);
                box-shadow: 0 4em 5em rgba(42, 168, 0, 0.2);
            }

            100% {
                background-color: rgba(15, 179, 9, 0.15);
                box-shadow: 0 4em 5em rgba(27, 8, 53, 0.1);
            }
        }

        /* Wrapper için yeni class */
        .wrapper.spinning {
            animation: wrapperGlow 2s ease-in-out infinite;
        }

        @keyframes fade {
            0% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        @keyframes confettiAnimation {
            0% {
                opacity: 1;
                transform: translateY(-100%);
            }

            100% {
                opacity: 0;
                transform: translateY(100%);
            }
        }

        #wheel-container.spinning #wheel {
            filter: drop-shadow(0 0 15px rgba(255,255,255,0.3));
        }

        /* Responsive Styles */
        @media (min-width: 768px) {
            #spin-btn {
                font-size: 1.8em;
            }

            #final-value {
                font-size: 1.5em;
            }
        }

        @media (max-width: 768px) {
            .wrapper {
                width: 98%;
                padding: 0.8rem;
                margin: 0.5rem auto;
            }

            #wheel-container {
                max-width: 320px;
            }

            .ticket-header {
                padding: 0.8rem;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 480px) {
            .wrapper {
                width: 100%;
                padding: 0.5rem;
                margin: 0.5rem auto;
            }

            #wheel-container {
                max-width: 280px;
            }

            .ticket-header {
                padding: 0.6rem;
            }

            #final-value {
                font-size: 1.2em;
                margin-top: 0.5rem;
            }
        }

        /* Header adjustments */
        .bg-blue-500 {
            position: sticky;
            top: 0;
            z-index: 100;
            margin-bottom: 0.5rem;
        }

        /* Chart text adjustments */
        #wheel {
            font-size: clamp(14px, 2.5vw, 20px);
        }
    </style>

</head>

<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-blue-500 text-white rounded-b-3xl p-3">
        <div class="flex items-center justify-between max-w-screen-lg mx-auto">
            <div class="flex items-center">
                <button onClick="Javascript:window.location.href = 'nz.php';"
                    class="w-8 h-8 bg-white rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-arrow-left text-blue-500 text-xl"></i>
                </button>
                <p class="text-xl font-bold"><?php echo $lang["spinner"]; ?></p>
            </div>
            <div class="sound-control" id="soundControl">
                <button class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                    <i class="fas fa-volume-up text-blue-500 text-lg"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="wrapper">
        <div class="ticket-header">
            <div class="ticket-text">Your tickets</div>
            <div class="ticket-info">
                <span class="text-xl font-bold"><?php echo htmlspecialchars($tickets, ENT_QUOTES, 'UTF-8'); ?></span>
                <i class="fas fa-ticket-alt"></i>
            </div>
        </div>


        <div class="main">
            <div class="container">
                <img src="https://cdn-icons-png.flaticon.com/128/9590/9590006.png" alt="spinner-arrow"
                    id="spinner-arrow">
                <div id="wheel-container">
                    <canvas id="wheel"></canvas>
                    <button id="spin-btn">SPIN</button>
                </div>
                <div id="final-value" class="text-center mt-4 text-xl font-bold">
                    <p><?php echo $lang["start_for_click"]; ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const sounds = {
            spin: new Audio('game/spin.wav'),
            win: new Audio('game/win.mp3')
        };
        let isSoundOn = true;
        const soundControl = document.getElementById('soundControl');

        soundControl.addEventListener('click', () => {
            isSoundOn = !isSoundOn;
            const icon = soundControl.querySelector('i');
            icon.className = isSoundOn ? 'fas fa-volume-up' : 'fas fa-volume-mute';

            // Seslerin durumunu güncelle
            Object.values(sounds).forEach(sound => {
                sound.muted = !isSoundOn;
            });
        });
        // Sesleri önceden yükle
        window.onload = function () {
            sounds.spin.load();
            sounds.win.load();
        }
        // Arrow stilleri
        const wheelAndArrowStyles = `
#spinner-arrow {
    position: absolute;
    width: 4em;
    left: 50%; /* Oku merkezin üstüne hizala */
    top: -2em; /* Oku çarkın üstüne taşı */
    transform: translateX(-50%) rotate(-90deg); /* Oku merkeze hizala ve yukarı baksın */
    z-index: 5;
    filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.3));
    opacity: 1;
    visibility: visible;
}
`;

        // Stili ekle
        const newStyleSheet = document.createElement("style");
        newStyleSheet.innerText = wheelAndArrowStyles;
        document.head.appendChild(newStyleSheet);

        const rewards = <?php echo json_encode($rewards); ?>;
        const translations = <?php echo json_encode($lang); ?>;

        function t(key) {
            return translations[key] || key;
        }

        const wheel = document.getElementById("wheel");
        const spinBtn = document.getElementById("spin-btn");
        const finalValue = document.getElementById("final-value");

        let myChart = new Chart(wheel, {
            plugins: [ChartDataLabels],
            type: "pie",
            data: {
                labels: rewards.map(reward => `${reward.spin_reward_num} ${reward.spin_reward_type}`),
                datasets: [{
                    backgroundColor: rewards.map(reward => {
                        switch (reward.spin_reward_type) {
                            case 'USDT': return '#2ecc71';    
                            case 'COINS': return '#f1c40f';    
                            case 'POINTS': return '#3498db'; 
                            case 'TICKET': return '#e74c3c';  
                            case 'GIFT CARD': return '#9b59b6'; 
                            case 'BONUS': return '#e67e22';   
                            default: return '#95a5a6';        
                        }
                    }),
                    data: new Array(rewards.length).fill(1),
                    borderWidth: 1,
                    borderColor: '#ffffff',
                    hoverBorderWidth: 4,
                    hoverBorderColor: '#ffffff',
                    borderRadius: 2,
                    borderAlign: 'inner',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                rotation: 0,
                animation: { duration: 0 },
                plugins: {
                    tooltip: { enabled: false },
                    legend: { display: false },
                    datalabels: {
                        formatter: (_, context) => {
                            const reward = rewards[context.dataIndex];
                            const isRetry = reward.spin_reward_type === 'TICKET' && Number(reward.spin_reward_num) === 1;
                            const num = reward.spin_reward_num % 1 === 0 ?
                                Math.floor(reward.spin_reward_num) :
                                Number(reward.spin_reward_num).toFixed(1);

                            let icon, text;
                            switch (reward.spin_reward_type) {
                                case 'COINS':
                                    icon = '🪙';
                                    text = 'COINS';
                                    break;
                                case 'USDT':
                                    icon = '💵';
                                    text = 'USDT';
                                    break;
                                case 'POINTS':
                                    icon = '⭐';
                                    text = 'POINTS';
                                    break;
                                case 'TICKET':
                                    icon = isRetry ? '🔄' : '🎫';
                                    text = isRetry ? 'RETRY' : 'TICKET';
                                    break;
                                case 'GIFT CARD':
                                    icon = '🎁';
                                    text = 'GIFT';
                                    break;
                                case 'BONUS':
                                    icon = '🎯';
                                    text = 'BONUS';
                                    break;
                                default:
                                    icon = '•';
                                    text = '';
                            }

                            return isRetry ? 
                                `${icon}\n${text}` : 
                                `${num} ${icon}\n${text}`;
                        },
                        color: "#ffffff",
                        font: {
                            size: 18,
                            weight: 'bold',
                            family: 'Arial',
                        },
                        textStrokeWidth: 1,
                        textStrokeColor: "rgba(0,0,0,0.4)",
                        textShadowBlur: 8,
                        textShadowColor: "rgba(0,0,0,0.4)",
                        align: 'center',
                        anchor: 'center',
                        padding: 2, 
                        rotation: (ctx) => {
                            const totalSlices = ctx.chart.data.labels.length;
                            const sliceAngle = (ctx.dataIndex * (360 / totalSlices)) % 360;
                            const baseAngle = sliceAngle + (360 / totalSlices / 2) + 270 + ctx.chart.options.rotation;
                            return sliceAngle > 90 && sliceAngle < 270 ? baseAngle - 180 : baseAngle;
                        }
                    }
                }
            }
        });
        // Mevcut spin fonksiyonu içinde, başarılı spin sonrası:
        function updateTicketCount(newCount) {
            const ticketElement = document.querySelector('.ticket-info span');
            if (ticketElement) {
                // Animasyonlu geçiş
                ticketElement.style.transition = 'opacity 0.3s';
                ticketElement.style.opacity = '0';

                setTimeout(() => {
                    ticketElement.textContent = newCount;
                    ticketElement.style.opacity = '1';
                }, 300);
            }
        }
        function selectWinnerBasedOnProbability() {
            // Debug için log ekleyelim
            console.log("Tüm ödüller:", rewards);

            // Toplam olasılığı hesapla
            let totalProbability = 0;
            rewards.forEach(reward => {
                totalProbability += parseFloat(reward.probability);
                console.log(`Ödül: ${reward.spin_reward_type} ${reward.spin_reward_num}, Olasılık: ${reward.probability}`);
            });
            console.log("Toplam olasılık:", totalProbability);

            // 0 ile toplam olasılık arasında random sayı üret (8 decimal hassasiyet)
            const random = Number((Math.random() * totalProbability).toFixed(8));
            console.log("Üretilen random sayı:", random);

            let cumulative = 0;

            // Her ödülü kontrol et
            for (let i = 0; i < rewards.length; i++) {
                cumulative += Number(parseFloat(rewards[i].probability).toFixed(8));
                console.log(`Kontrol - Ödül: ${rewards[i].spin_reward_type}, Kümülatif: ${cumulative}, Random: ${random}`);

                if (random <= cumulative) {
                    console.log(`Kazanan ödül: ${rewards[i].spin_reward_type} ${rewards[i].spin_reward_num}`);
                    return i;
                }
            }

            console.log("Varsayılan olarak ilk ödül seçildi");
            return 0;
        }
        // Modal fonksiyonunu ekleyelim
        function showModal(title, message, buttons) {
            // Varsa eski modalı kaldır
            const oldModal = document.getElementById('customModal');
            if (oldModal) {
                oldModal.remove();
            }

            const modalHTML = `
    <div id="customModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">${title}</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">${message}</p>
                </div>
                <div class="items-center px-4 py-3">
                    ${buttons.map(btn => `
                        <button id="${btn.id}" 
                            class="px-4 py-2 bg-${btn.color}-500 text-white text-base font-medium rounded-md w-full 
                            shadow-sm hover:bg-${btn.color}-700 focus:outline-none focus:ring-2 focus:ring-${btn.color}-300 mt-2">
                            ${btn.text}
                        </button>
                    `).join('')}
                </div>
            </div>
        </div>
    </div>
    `;

            // Modal'ı DOM'a ekle
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Button event listener'ları ekle
            buttons.forEach(btn => {
                document.getElementById(btn.id).addEventListener('click', () => {
                    document.getElementById('customModal').remove();
                    if (typeof btn.onClick === 'function') {
                        btn.onClick();
                    }
                });
            });
        }
        // Spin mantığı aynı kalacak, sadece görsel geliştirmeler
        let canSpin = true;
        const minSpins = 5; // Minimum number of spins
        const maxSpins = 10; // Maximum number of spins
        const spinDuration = 14000; // Fixed duration of 14 seconds

        // Randomize the number of spins
        const randomSpins = Math.floor(Math.random() * (maxSpins - minSpins + 1)) + minSpins;

        const easeOut = (t) => {
            return 1 - Math.pow(1 - t, 3);
        };

        // Spin fonksiyonunu düzelt
        const valueGenerator = (finalAngle, selectedReward, winnerIndex) => {
            if (selectedReward) {
                document.querySelector('.wrapper').classList.remove('spinning');
                
                // Create a more descriptive winning message
                let winMessage = '';
                if (selectedReward.spin_reward_type === 'TICKET' && selectedReward.spin_reward_num === 1) {
                    winMessage = 'You got a Free Retry!';
                } else {
                    winMessage = `You won ${selectedReward.spin_reward_num} ${selectedReward.spin_reward_type}!`;
                }
                
                finalValue.innerHTML = `<p>${winMessage}</p>`;

                // Genel şifreleme fonksiyonu - hem string hem number için çalışır
                function encryptData(data, key) {
                    let encrypted = '';
                    for (let i = 0; i < data.toString().length; i++) {
                        encrypted += String.fromCharCode(data.toString().charCodeAt(i) + key);
                    }
                    return btoa(encrypted);
                }

                // Tüm objeyi şifreleyen fonksiyon
                function encryptAllData(obj, key) {
                    const encryptedObj = {};
                    for (let prop in obj) {
                        encryptedObj[prop] = encryptData(obj[prop], key);
                    }
                    return encryptedObj;
                }

                // Kullanımı
                fetch('save_reward.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(
                        encryptAllData({
                            reward_type: selectedReward.spin_reward_type,
                            reward_num: selectedReward.spin_reward_num
                        }, 5)
                    )
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Save reward response:', data); // Debug log

                    if (data.success) {
                        sounds.spin.pause();
                        sounds.win.play();
                        addWinEffects();

                        // Create message including coins if applicable
                        let winMessage = '';
                        if (data.newBalance && data.newBalance.coinsAdded > 0) {
                            winMessage = `You won ${selectedReward.spin_reward_num} ${selectedReward.spin_reward_type}!\nAdded ${data.newBalance.coinsAdded} coins to your balance!`;
                        } else {
                            winMessage = `You won ${selectedReward.spin_reward_num} ${selectedReward.spin_reward_type}!`;
                        }

                        showModal(t('cong'), winMessage, [
                            {
                                id: 'okBtn',
                                text: 'OK',
                                color: 'blue',
                                onClick: () => {
                                    canSpin = true;
                                    spinBtn.disabled = false;
                                    myChart.options.rotation = 0;
                                    myChart.update();
                                    finalValue.innerHTML = `<p>${t("start_for_click")}</p>`;
                                    // Refresh the page to update displayed balances
                                    window.location.reload();
                                }
                            }
                        ]);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    canSpin = true;
                    spinBtn.disabled = false;
                });
            }
        };

        // Replace the existing tickets initialization
        let tickets = <?php echo json_encode($tickets); ?>;

        // Add a function to fetch current ticket count
        function fetchCurrentTickets() {
            fetch('check_balance.php', {
                method: 'GET' // Use GET to not decrease tickets
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    tickets = data.tickets;
                    updateTicketCount(tickets);
                }
            })
            .catch(error => console.error('Error fetching tickets:', error));
        }

        // Call this function when the page loads
        document.addEventListener('DOMContentLoaded', fetchCurrentTickets);

        spinBtn.addEventListener("click", () => {
            if (!canSpin) return;
            
            // Check if we have any tickets left before trying to spin
            if (tickets <= 0) {
                showModal(t('unsufficient_balance'), t('spin_dont_t'), [
                    {
                        id: 'useCoinsBtn',
                        text: t('yes'),
                        color: 'blue',
                        onClick: () => window.location.href = 'nz.php'
                    },
                    {
                        id: 'cancelSpinBtn',
                        text: t('no'),
                        color: 'red',
                        onClick: () => {
                            canSpin = true;
                            spinBtn.disabled = false;
                        }
                    }
                ]);
                return;
            }

            // Fetch the balance first
            fetch('check_balance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log("Server response:", data);
                
                // Update ticket count immediately
                if (data.tickets !== undefined) {
                    tickets = data.tickets;
                    updateTicketCount(tickets);
                }
                
                if (data.success) {
                    canSpin = false;
                    spinBtn.disabled = true;
                    finalValue.innerHTML = `<p>${t("spinning")}</p>`;
                    addSpinEffects();
                    
                    // Continue with the spin animation
                    const startTime = Date.now();
                    const startAngle = myChart.options.rotation;
                    
                    // Play spin sound
                    if (isSoundOn) {
                        sounds.spin.currentTime = 0;
                        sounds.spin.play();
                    }

                    // Select winning reward and animate
                    const winnerIndex = selectWinnerBasedOnProbability();
                    const segmentAngle = 360 / rewards.length;
                    const targetDegree = -(segmentAngle * winnerIndex + segmentAngle / 2);
                    const totalRotation = 360 * randomSpins + targetDegree;

                    const animate = () => {
                        const elapsedTime = Date.now() - startTime;
                        const progress = Math.min(elapsedTime / spinDuration, 1);
                        const easedProgress = easeOut(progress);

                        const currentRotation = startAngle + (totalRotation * easedProgress);
                        myChart.options.rotation = currentRotation;
                        myChart.update();

                        if (progress < 1) {
                            requestAnimationFrame(animate);
                        } else {
                            const selectedReward = rewards[winnerIndex];
                            valueGenerator(currentRotation, selectedReward, winnerIndex);
                        }
                    };

                    requestAnimationFrame(animate);
                    
                } else {
                    // Show insufficient balance modal
                    showModal(t('unsufficient_balance'), t('spin_dont_t'), [
                        {
                            id: 'useCoinsBtn',
                            text: t('yes'),
                            color: 'blue',
                            onClick: () => window.location.href = 'nz.php'
                        },
                        {
                            id: 'cancelSpinBtn',
                            text: t('no'),
                            color: 'red',
                            onClick: () => {
                                canSpin = true;
                                spinBtn.disabled = false;
                            }
                        }
                    ]);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showModal(t('error'), t('something_went_wrong'), [
                    { id: 'okBtn', text: 'OK', color: 'blue' }
                ]);
            });
        });

        function addSpinEffects() {
            sounds.spin.currentTime = 0;
            sounds.spin.play();

            const wheelContainer = document.getElementById('wheel-container');
            const wrapper = document.querySelector('.wrapper');

            // Wrapper'a spinning class'ı ekle
            wrapper.classList.add('spinning');
            wheelContainer.classList.add('spinning');
            wheelContainer.appendChild(document.createElement('div')).className = 'glow-effect';
        }

        function addWinEffects() {
            sounds.spin.pause();
            sounds.win.play();
            if (isSoundOn) {
                sounds.spin.pause();
                sounds.win.play();
            }
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            document.body.appendChild(confetti);
            setTimeout(() => confetti.remove(), 3000);
        }

    </script>
</body>

</html>