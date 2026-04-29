<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['puter_user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

const MINIMAX_API_KEY = 'sk-api-fiKYJEzfZGECNQGiyxWx7X0GHsbNvHzMRfS5tLJlzE3ikhMrggLWwzCndl0XNlCJokXD7j_qRMx7am3UpEtQRzqeQM47PO2_SX-QZh4MJqym5EinBKOwf48';

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';
$provider = $input['provider'] ?? 'puter';
$model = $input['model'] ?? 'claude-4-7-opus';
$history = $input['history'] ?? [];
$systemPrompt = $input['system_prompt'] ?? '';

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

try {
    if ($provider === 'minimax') {
        $response = callMiniMax($model, $message, $history, $systemPrompt);
    } else {
        $response = callPuterAI($model, $message, $history, $systemPrompt);
    }
    
    echo json_encode(['success' => true, 'response' => $response]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function callPuterAI($model, $message, $history, $systemPrompt) {
    // Build messages array
    $messages = [];
    
    // Add system prompt
    if (!empty($systemPrompt)) {
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
    }
    
    // Add conversation history (last 10 messages for context)
    $recentHistory = array_slice($history, -10);
    foreach ($recentHistory as $msg) {
        $messages[] = $msg;
    }
    
    // Prepare request for Puter AI
    $ch = curl_init('https://api.puter.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 4096,
        'temperature' => 0.7
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $_SESSION['puter_token']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        throw new Exception('Puter AI API error: ' . ($response ?: 'Unknown error'));
    }
    
    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? 'No response generated';
}

function callMiniMax($model, $message, $history, $systemPrompt) {
    // Map model IDs to MiniMax format
    $modelMap = [
        'minimax-m2-7' => 'minimax-m2-7',
        'minimax-m2-5' => 'minimax-m2-5'
    ];
    
    $actualModel = $modelMap[$model] ?? 'minimax-m2-5';
    
    // Build messages array
    $messages = [];
    
    // Add system prompt
    if (!empty($systemPrompt)) {
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
    }
    
    // Add conversation history
    foreach ($history as $msg) {
        $messages[] = $msg;
    }
    
    // Prepare request for MiniMax
    $ch = curl_init('https://api.minimaxi.chat/v1/text/chatcompletion_v2');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $actualModel,
        'messages' => $messages,
        'max_tokens' => 4096,
        'temperature' => 0.7
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . MINIMAX_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        throw new Exception('MiniMax API error: ' . ($response ?: 'Unknown error'));
    }
    
    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? $data['reply'] ?? 'No response generated';
}
?>
