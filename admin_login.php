<?php
session_start();
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    if ($username === "scissor" && $password === "LiliesWorld%05!") {
        $_SESSION["admin_logged_in"] = true;
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $error = "Invalid credentials.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        #login-box {
            background: white;
            padding: 24px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            width: 300px;
        }
        input, button {
            font-size: 14px;
            padding: 10px;
            width: 100%;
            margin-bottom: 12px;
            box-sizing: border-box;
        }
        button {
            background: black;
            color: white;
            border: none;
            cursor: pointer;
        }
        .error { color: red; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div id="login-box">
        <h2 style="margin-bottom: 16px;">Admin Login</h2>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
