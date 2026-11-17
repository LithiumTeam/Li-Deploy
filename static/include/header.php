<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logged_in = ($_SESSION['logged_in'] ?? false) === true;

?>

<script>
    const logged_in = <?= json_encode($logged_in) ?>;
</script>

<header>
    <div style="gap: 20px;">
        <img src="/static/image/logo.png" alt="Li-Deploy image">
        <h2>Li-Deploy</h2>
    </div>
    <div style="flex-direction: column;">
        <nav>
            <a id="main-page">صفحه اصلی</a>
            <?php
                if (!$logged_in) {
                    echo '<a id="login-page">ورود</a>';
                } else {
                    echo '<a id="dashboard-page">داشبورد</a>';
                }
            ?>
            <a id="support-page">پشتیبانی</a>
            <a href="https://lithiumteam.github.io" target="_blank">وبسایت لیتیوم</a>
        </nav>
    </div>
</header>