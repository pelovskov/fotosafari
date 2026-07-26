<?php
session_start();
require 'users.php'; // Inkluderer din nye, simple brugerfil
$error_message = '';

// Hvis brugeren allerede er logget ind, send dem til admin-siden
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Sammenligner den indtastede adgangskode mod den gemte bcrypt-hash.
    // password_verify() hasher $password internt og sammenligner hash-til-hash;
    // det oprindelige password er aldrig gemt eller genskabt nogen steder.
    if (isset($users[$username]) && password_verify($password, $users[$username])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header('Location: admin.php');
        exit;
    } else {
        $error_message = 'Forkert brugernavn eller adgangskode.';
    }
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style> /* Simpel styling for login-boksen */
        body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; }
        h2 { text-align: center; margin-bottom: 20px; }
        input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; border: none; background-color: #007bff; color: white; border-radius: 4px; cursor: pointer; }
        .error { color: red; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        <?php if ($error_message): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <input type="text" name="username" placeholder="Brugernavn" required>
            <input type="password" name="password" placeholder="Adgangskode" required>
            <button type="submit">Log ind</button>
        </form>
    </div>
</body>
</html>