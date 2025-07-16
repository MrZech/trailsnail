<?php
if (isset($_GET['password'])) {
    $hash = crypt($_GET['password'], 'xy');
    echo "Hashed password for '{$_GET['password']}' is: $hash";
} else {
    echo "Usage: generate_hash.php?password=yourpassword";
}