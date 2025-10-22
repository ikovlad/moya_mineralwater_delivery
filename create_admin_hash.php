<?php
// This file generates a new, secure password hash.
// The password to be hashed is 'admin123'.

$password = 'admin123';

// Use PHP's built-in function to create a secure hash.
// PASSWORD_DEFAULT ensures it uses the best algorithm available on your server.
$new_hash = password_hash($password, PASSWORD_DEFAULT);

// Display the new hash.
echo '<h1>New Admin Password Hash</h1>';
echo '<p>Copy the entire string below and paste it into the `password_hash` column in your `admins` table in phpMyAdmin.</p>';
echo '<hr>';
echo '<strong style="font-family: monospace; font-size: 1.2rem;">' . $new_hash . '</strong>';
?>