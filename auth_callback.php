<?php
session_start();

// Handle Puter OAuth callback
if (isset($_GET['access_token'])) {
    // Store the token
    $_SESSION['puter_token'] = $_GET['access_token'];
    
    // Fetch user info from Puter
    $ch = curl_init('https://api.puter.com/user');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $_SESSION['puter_token']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $user = json_decode($response, true);
        $_SESSION['puter_user'] = [
            'username' => $user['username'] ?? 'User',
            'email' => $user['email'] ?? '',
            'id' => $user['id'] ?? ''
        ];
        
        // Redirect to main app
        header('Location: index.php');
        exit;
    }
}

// If no token, redirect to login
header('Location: login.php');
exit;
?>
