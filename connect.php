<?php
session_start();
$_SESSION["dbname"] = $_POST["dbname"];
$_SESSION["user"] = $_POST["user"];
$_SESSION["password"] = $_POST["password"];
$conn = pg_connect("host=localhost port=5432 " .
    "dbname=" . $_SESSION["dbname"] . " " .
    "user=" . $_SESSION["user"] . " " .
    "password=" . $_SESSION["password"]
);
if (!$conn) {
    header("Location: index.php?error=invalid", true, 302);
}
else {
    header("Location: edit.php", true, 302);
}
