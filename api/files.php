<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['puter_user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$baseDir = __DIR__ . '/../';

try {
    switch ($action) {
        case 'list':
            $files = listFiles($baseDir);
            echo json_encode(['success' => true, 'files' => $files]);
            break;
            
        case 'read':
            $path = $_GET['path'] ?? '';
            $content = readFile($baseDir, $path);
            echo json_encode(['success' => true, 'content' => $content]);
            break;
            
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            $result = createFile($baseDir, $input['name'], $input['type'], $input['content'] ?? '');
            echo json_encode($result);
            break;
            
        case 'update':
            $input = json_decode(file_get_contents('php://input'), true);
            $result = updateFile($baseDir, $input['path'], $input['content']);
            echo json_encode($result);
            break;
            
        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true);
            $result = deleteFile($baseDir, $input['path']);
            echo json_encode($result);
            break;
            
        case 'move':
            $input = json_decode(file_get_contents('php://input'), true);
            $result = moveFile($baseDir, $input['path'], $input['newPath']);
            echo json_encode($result);
            break;
            
        case 'copy':
            $input = json_decode(file_get_contents('php://input'), true);
            $result = copyFile($baseDir, $input['path'], $input['newPath']);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function listFiles($baseDir) {
    $files = [];
    $items = scandir($baseDir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === 'history') {
            continue;
        }
        
        $path = $baseDir . $item;
        $files[] = [
            'name' => $item,
            'path' => $item,
            'type' => is_dir($path) ? 'folder' : 'file'
        ];
    }
    
    return $files;
}

function readFile($baseDir, $path) {
    $fullPath = realpath($baseDir . $path);
    
    // Security check - ensure path is within baseDir
    if (strpos($fullPath, realpath($baseDir)) !== 0) {
        throw new Exception('Access denied');
    }
    
    if (!file_exists($fullPath)) {
        throw new Exception('File not found');
    }
    
    if (is_dir($fullPath)) {
        throw new Exception('Cannot read directory content');
    }
    
    return file_get_contents($fullPath);
}

function createFile($baseDir, $name, $type, $content = '') {
    if (empty($name)) {
        return ['success' => false, 'error' => 'Name is required'];
    }
    
    // Sanitize name
    $name = basename($name);
    $path = $baseDir . $name;
    
    if (file_exists($path)) {
        return ['success' => false, 'error' => 'Already exists'];
    }
    
    if ($type === 'folder') {
        if (mkdir($path, 0755, true)) {
            return ['success' => true, 'message' => 'Folder created'];
        }
    } else {
        if (file_put_contents($path, $content) !== false) {
            return ['success' => true, 'message' => 'File created'];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to create'];
}

function updateFile($baseDir, $path, $content) {
    $fullPath = realpath($baseDir . $path);
    
    // Security check
    if (strpos($fullPath, realpath($baseDir)) !== 0) {
        return ['success' => false, 'error' => 'Access denied'];
    }
    
    if (!file_exists($fullPath) || is_dir($fullPath)) {
        return ['success' => false, 'error' => 'Invalid file'];
    }
    
    if (file_put_contents($fullPath, $content) !== false) {
        return ['success' => true, 'message' => 'File updated'];
    }
    
    return ['success' => false, 'error' => 'Failed to update'];
}

function deleteFile($baseDir, $path) {
    $fullPath = realpath($baseDir . $path);
    
    // Security check
    if (strpos($fullPath, realpath($baseDir)) !== 0) {
        return ['success' => false, 'error' => 'Access denied'];
    }
    
    // Prevent deleting important files
    $protected = ['index.php', 'login.php', 'auth_callback.php', 'api', 'assets', 'history'];
    if (in_array(basename($path), $protected) || in_array($path, $protected)) {
        return ['success' => false, 'error' => 'Protected file/folder'];
    }
    
    if (is_dir($fullPath)) {
        if (rmdir($fullPath)) {
            return ['success' => true, 'message' => 'Folder deleted'];
        }
    } else {
        if (unlink($fullPath)) {
            return ['success' => true, 'message' => 'File deleted'];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to delete'];
}

function moveFile($baseDir, $path, $newPath) {
    $fullPath = realpath($baseDir . $path);
    $newFullPath = $baseDir . basename($newPath);
    
    // Security checks
    if ($fullPath && strpos($fullPath, realpath($baseDir)) !== 0) {
        return ['success' => false, 'error' => 'Access denied'];
    }
    
    if (!file_exists($fullPath)) {
        return ['success' => false, 'error' => 'Source not found'];
    }
    
    if (rename($fullPath, $newFullPath)) {
        return ['success' => true, 'message' => 'Moved successfully'];
    }
    
    return ['success' => false, 'error' => 'Failed to move'];
}

function copyFile($baseDir, $path, $newPath) {
    $fullPath = realpath($baseDir . $path);
    $newFullPath = $baseDir . basename($newPath);
    
    // Security checks
    if ($fullPath && strpos($fullPath, realpath($baseDir)) !== 0) {
        return ['success' => false, 'error' => 'Access denied'];
    }
    
    if (!file_exists($fullPath)) {
        return ['success' => false, 'error' => 'Source not found'];
    }
    
    if (is_dir($fullPath)) {
        if (copyDirectory($fullPath, $newFullPath)) {
            return ['success' => true, 'message' => 'Copied successfully'];
        }
    } else {
        if (copy($fullPath, $newFullPath)) {
            return ['success' => true, 'message' => 'Copied successfully'];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to copy'];
}

function copyDirectory($src, $dst) {
    if (!mkdir($dst, 0755, true)) {
        return false;
    }
    
    $files = scandir($src);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $srcFile = $src . '/' . $file;
        $dstFile = $dst . '/' . $file;
        
        if (is_dir($srcFile)) {
            copyDirectory($srcFile, $dstFile);
        } else {
            copy($srcFile, $dstFile);
        }
    }
    
    return true;
}
?>
