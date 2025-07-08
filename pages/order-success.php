<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

requireLogin();

if (!isset($_SESSION['order_success'])) {
    header('Location: account.php');
    exit;
}

$order_id = $_SESSION['order_success'];
$order = getOrderDetails($conn, $order_id, $_SESSION['user_id']);

if (!$order) {
    header('Location: account.php');
    exit;
}

unset($_SESSION['order_success']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Veggiestry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/order-success.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <main class="order-success-main">
        <div class="container">
            <div class="success-card">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>

                <h1>Pesanan Berhasil Dibuat!</h1>
                <p class="success-message">
                    Terima kasih telah berbelanja di Veggiestry. Pesanan Anda sedang diproses.
                </p>

                <div class="order-details">
                    <div class="detail-row">
                        <span class="label">Nomor Pesanan:</span>
                        <span class="value">#<?= $order['order_number'] ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Total Pembayaran:</span>
                        <span class="value">Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Metode Pembayaran:</span>
                        <span class="value"><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status:</span>
                        <span class="value status <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                    </div>
                </div>

                <div class="next-steps">
                    <h3>Langkah Selanjutnya:</h3>
                    <div class="steps-list">
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Konfirmasi Pesanan</h4>
                                <p>Kami akan mengkonfirmasi pesanan Anda dalam 1-2 jam</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Pembayaran</h4>
                                <p>Lakukan pembayaran sesuai metode yang dipilih</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Pengiriman</h4>
                                <p>Pesanan akan dikirim setelah pembayaran dikonfirmasi</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="account.php#orders" class="btn-primary">
                        <i class="fas fa-list"></i>
                        Lihat Pesanan Saya
                    </a>
                    <a href="products.php" class="btn-secondary">
                        <i class="fas fa-shopping-cart"></i>
                        Lanjut Belanja
                    </a>
                </div>

                <div class="contact-info">
                    <p>
                        <i class="fas fa-phone"></i>
                        Butuh bantuan? Hubungi kami di <strong>0800-1234-5678</strong>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script>
        function createConfetti() {
            const colors = ['#40513b', '#609966', '#9dc08b', '#edf1d6'];

            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.style.cssText = `
                    position: fixed;
                    width: 10px;
                    height: 10px;
                    background-color: ${colors[Math.floor(Math.random() * colors.length)]};
                    top: -10px;
                    left: ${Math.random() * 100}vw;
                    animation: confetti-fall ${Math.random() * 3 + 2}s linear forwards;
                    z-index: 9999;
                `;

                document.body.appendChild(confetti);

                setTimeout(() => {
                    if (confetti.parentNode) {
                        confetti.parentNode.removeChild(confetti);
                    }
                }, 5000);
            }
        }

        const style = document.createElement('style');
        style.textContent = `
            @keyframes confetti-fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                }
            }
        `;
        document.head.appendChild(style);

        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(createConfetti, 500);
        });
    </script>
</body>

</html>