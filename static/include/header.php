<?php

session_start();

if (isset($_SESSION['logged_in'])) {
    if ($_SESSION) {
        $logged_in = true;
    } else {
        $logged_in = false;
    }
} else {
    $logged_in = false;
}

?>

<script>
    let logged_in = <?php 
        if ($logged_in===1) {
            echo 'true';
        } else {
            echo 'false';
        }
    ?>
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