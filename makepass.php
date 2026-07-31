<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Password: " . $password . "<br>";
echo "New Hash: <br>";
echo "<textarea rows='3' cols='80'>" . $hash . "</textarea><br><br>";
echo "Copy this SQL and run in phpMyAdmin:<br>";
echo "<textarea rows='3' cols='100'>";
echo "UPDATE users SET password = '" . $hash . "' WHERE email = 'admin@boarding.com';";
echo "</textarea>";
?>