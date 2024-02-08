<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>database-web-ui</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/index.css">
</head>
    <body>
        <div class="container">
            <form action="connect.php" method="post">

                <label for="dbname">Database</label>
                <input type="text" id="dbname" name="dbname" placeholder="Database name" value="postgres" minlength="1" required>
                <label for="user">User</label>
                <input type="text" id="user" name="user" placeholder="User" value="postgres" minlength="1" required>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Password" value="postgres" minlength="8" required>
                <input type="submit" value="Connect">
            </form>
        </div>
    </body>
</html>