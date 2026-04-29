<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['puter_user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

const HISTORY_DIR = __DIR__ . '/../history/';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $history = loadHistory();
            echo json_encode(['success' => true, 'history' => $history]);
            break;
            
        case 'save':
            $input = json_decode(file_get_contents('php://input'), true);
            $result = saveHistoryEntry($input['user_message'], $input['assistant_message']);
            echo json_encode($result);
            break;
            
        case 'clear':
            $result = clearHistory();
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function loadHistory() {
    $files = scandir(HISTORY_DIR);
    $history = [];
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filePath = HISTORY_DIR . $file;
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $content = file_get_contents($filePath);
            $entry = json_decode($content, true);
            if ($entry) {
                $history[] = $entry;
            }
        }
    }
    
    // Sort by timestamp descending
    usort($history, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    return array_slice($history, 0, 50); // Return last 50 entries
}

function saveHistoryEntry($userMessage, $assistantMessage) {
    if (empty($userMessage) || empty($assistantMessage)) {
        return ['success' => false, 'error' => 'Messages required'];
    }
    
    // Ensure history directory exists
    if (!is_dir(HISTORY_DIR)) {
        mkdir(HISTORY_DIR, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $filename = 'history_' . time() . '_' . md5($userMessage . $timestamp) . '.json';
    
    $entry = [
        'timestamp' => $timestamp,
        'user_message' => $userMessage,
        'assistant_message' => $assistantMessage,
        'user_id' => $_SESSION['puter_user']['id'] ?? 'unknown'
    ];
    
    if (file_put_contents(HISTORY_DIR . $filename, json_encode($entry, JSON_PRETTY_PRINT))) {
        return ['success' => true, 'message' => 'History saved'];
    }
    
    return ['success' => false, 'error' => 'Failed to save'];
}

function clearHistory() {
    $files = scandir(HISTORY_DIR);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filePath = HISTORY_DIR . $file;
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            unlink($filePath);
        }
    }
    
    return ['success' => true, 'message' => 'History cleared'];
}
?>
