<?php include("admin_auth.php"); ?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: white;
      margin: 0;
      padding: 20px;
      color: black;
    }

    h1 {
      font-size: 22px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    h2 {
      font-size: 18px;
      margin-top: 0;
      margin-bottom: 16px;
    }

    .block {
      background: white;
      border: 1px solid black;
      padding: 16px;
      margin-bottom: 24px;
    }

    .item {
      margin-bottom: 10px;
      font-size: 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      word-wrap: break-word;
    }

    button {
      background: black;
      color: white;
      border: none;
      padding: 6px 12px;
      cursor: pointer;
      font-family: 'Inter', sans-serif;
      border-radius: 0;
      font-size: 13px;
    }

    input[type="text"] {
      padding: 8px;
      font-size: 14px;
      font-family: 'Inter', sans-serif;
      width: 100%;
      margin-bottom: 10px;
      box-sizing: border-box;
    }

    a.logout {
      text-decoration: none;
      color: #000;
      font-size: 14px;
      font-weight: 500;
    }

    form {
      margin-top: 10px;
    }

    hr {
      border: none;
      border-top: 1px solid #ccc;
      margin: 20px 0;
    }

@media (max-width: 600px) {
  body {
    padding: 16px;
  }

  .item {
    font-size: 13px;
    /* Removed flex-direction: column so buttons stay inline */
  }

  .item button {
    font-size: 11px;
    padding: 4px 8px;
    width: auto; /* prevent full width */
    margin-top: 0;
  }

  button {
    font-size: 13px;
    padding: 6px 10px;
    width: auto;
  }

  a.logout {
    display: block;
    margin-top: 10px;
  }

  h1 {
    font-size: 20px;
    flex-direction: column;
    align-items: flex-start;
  }
}

  </style>
</head>
<body>

  <h1>Pin Admin <a href="logout.php" class="logout">Logout</a></h1>

  <div class="block">
    <h2>Pins</h2>
    <?php
    $pins = file_exists("pins.json") ? json_decode(file_get_contents("pins.json"), true) : [];
    foreach ($pins as $id => $pin) {
      echo "<div class='item'><div><strong>{$pin["title"]}</strong> by {$pin["user"]}</div> <button onclick=\"deletePin('$id')\">Delete</button></div>";
    }
    ?>
  </div>

  <div class="block">
    <h2>Users</h2>
    <?php
    $positions = file_exists("positions.json") ? json_decode(file_get_contents("positions.json"), true) : [];
    foreach ($positions as $user => $pos) {
      echo "<div class='item'><div>{$user}</div> <button onclick=\"deleteUser('$user')\">Delete</button></div>";
    }
    ?>
    <form onsubmit="event.preventDefault(); deleteUserManual();">
      <input type="text" id="manualUser" placeholder="Enter username to delete" />
      <button type="submit">Delete User</button>
    </form>
  </div>

  <div class="block">
    <h2>Chat Log</h2>
    <form method="post" action="clear_chat.php">
      <button type="submit">Clear Chat</button>
    </form>
  </div>

  <div class="block">
    <h2>Banned Users</h2>
    <?php
    $banned = file_exists("banned.json") ? json_decode(file_get_contents("banned.json"), true) : ["users" => [], "ips" => [], "cookies" => []];

    echo "<strong>Usernames:</strong><br>";
    foreach ($banned["users"] as $bannedUser) {
      echo "<div class='item'><div>{$bannedUser}</div> <button onclick=\"unbanUser('$bannedUser')\">Unban</button></div>";
    }

    echo "<hr><strong>IP Addresses:</strong><br>";
    foreach ($banned["ips"] as $ip) {
      echo "<div class='item'>{$ip}</div>";
    }

    echo "<hr><strong>Cookie IDs:</strong><br>";
    foreach ($banned["cookies"] as $cid) {
      echo "<div class='item'>{$cid}</div>";
    }
    ?>
  </div>

  <script>
    function deletePin(id) {
      if (confirm("Are you sure you want to delete this pin?")) {
        fetch("delete_pin.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "id=" + encodeURIComponent(id)
        }).then(() => window.location.reload());
      }
    }

    function deleteUser(username) {
      if (confirm("Are you sure you want to delete this user and all their data?")) {
        fetch("admin_delete_user.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "user=" + encodeURIComponent(username)
        }).then(() => window.location.reload());
      }
    }

    function deleteUserManual() {
      const username = document.getElementById("manualUser").value.trim();
      if (!username) return alert("Enter a username.");
      if (confirm("Delete user '" + username + "' from the entire system?")) {
        fetch("admin_delete_user.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "user=" + encodeURIComponent(username)
        }).then(() => window.location.reload());
      }
    }

    function unbanUser(username) {
      if (confirm("Unban " + username + "?")) {
        fetch("unban_user.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "user=" + encodeURIComponent(username)
        }).then(() => window.location.reload());
      }
    }
  </script>

</body>
</html>
