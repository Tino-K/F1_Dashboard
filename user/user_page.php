<?php
session_start();

$limit = 30;

if (!isset($_SESSION['email'])) {

    if (!isset($_SESSION['guest_start'])) {
        $_SESSION['guest_start'] = time();
        $remaining = $limit;
    } else {
        $elapsed = time() - $_SESSION['guest_start'];
        $remaining = $limit - $elapsed;
    }

    if ($remaining <= 0) {
        unset($_SESSION['guest_start']);
        header("Location: ../index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>

<?php if (isset($_SESSION['email'])): ?>

    <h1>Pozdrav <?= htmlspecialchars($_SESSION["name"]) ?></h1>
    <button onclick="window.location.href='../logout.php'">Logout</button>

<?php else: ?>

    <h1>Nisi prijavljen</h1>
    <p>Imaš još <span id="timer"><?= $remaining ?></span> sekundi</p>

    <script>
        let timeLeft = <?= $remaining ?>;

        const timer = setInterval(() => {
            timeLeft--;
            document.getElementById("timer").textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(timer);
                window.location.href = "../index.php";
            }
        }, 1000);
    </script>

<?php endif; ?>

</body>
</html>
