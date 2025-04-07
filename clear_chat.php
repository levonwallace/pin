<?php
include("admin_auth.php");
file_put_contents("chat.txt", "");
header("Location: admin_dashboard.php");
exit;
