<?php
session_start();
$key = $_GET['key'] ?? $_SESSION['user_key'] ?? '';

if (empty($key)) {
    die("<h1>Adgang nægtet</h1><p>Du skal bruge dit personlige QR-link for at se denne side.</p>");
}

// SIMPEL SAVE/DELETE LOGIK (Ligesom admin, men tjekker nøgle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_image'])) {
        $img = 'uploads/' . basename($_POST['delete_image']);
        $txt = 'uploads/' . pathinfo($img, PATHINFO_FILENAME) . '.txt';
        if (file_exists($txt)) {
            $lines = file($txt);
            $fileKey = trim($lines[5] ?? '');
            if ($fileKey === $key) { // SIKKERHEDSTJEK
                if (file_exists($img)) unlink($img);
                if (file_exists($txt)) unlink($txt);
            }
        }
        header("Location: minside.php?key=$key"); exit;
    }
    if (isset($_POST['save_edit'])) {
        $imgFile = basename($_POST['edit_file']);
        $txtPath = 'uploads/' . pathinfo($imgFile, PATHINFO_FILENAME) . '.txt';
        if (file_exists($txtPath)) {
            $lines = file($txtPath, FILE_IGNORE_NEW_LINES);
            $fileKey = trim($lines[5] ?? '');
            if ($fileKey === $key) { // SIKKERHEDSTJEK
                $n = $_POST['name']; $d = $_POST['desc']; 
                $lat = $lines[2]??''; $lng = $lines[3]??''; $grp = $lines[4]??'A';
                // Bevar lat/lng/gruppe/key, opdater navn/beskrivelse
                $content = "$n\n$d\n$lat\n$lng\n$grp\n$key";
                file_put_contents($txtPath, $content);
            }
        }
        header("Location: minside.php?key=$key"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mine Billeder</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .back-btn { background: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; }
        .card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; display: flex; gap: 15px; align-items: flex-start; }
        .card img { width: 100px; height: 100px; object-fit: cover; border-radius: 4px; }
        .info { flex-grow: 1; }
        .actions { display: flex; gap: 10px; margin-top: 10px; }
        input[type=text], textarea { width: 100%; padding: 5px; margin: 2px 0; border: 1px solid #ccc; border-radius: 3px; }
        button { padding: 5px 10px; cursor: pointer; border: none; border-radius: 3px; color: white; }
        .btn-save { background: #28a745; }
        .btn-del { background: #dc3545; }
        .btn-edit { background: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Mine Billeder</h1>
            <a href="upload.php?key=<?php echo $key; ?>" class="back-btn">Tilbage til Upload</a>
        </div>

        <?php
        $files = glob("uploads/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
        $count = 0;
        if ($files) {
            array_multisort(array_map('filemtime', $files), SORT_DESC, $files);
            foreach ($files as $f) {
                $txt = 'uploads/' . pathinfo($f, PATHINFO_FILENAME) . '.txt';
                if (file_exists($txt)) {
                    $lines = file($txt, FILE_IGNORE_NEW_LINES);
                    $fileKey = trim($lines[5] ?? '');
                    
                    if ($fileKey === $key) { // VIS KUN HVIS NØGLEN MATCHER
                        $count++;
                        $name = htmlspecialchars($lines[0] ?? '');
                        $desc = htmlspecialchars($lines[1] ?? '');
                        $bName = basename($f);
                        echo "
                        <div class='card'>
                            <img src='$f'>
                            <div class='info'>
                                <form method='POST'>
                                    <input type='hidden' name='edit_file' value='$bName'>
                                    <label>Navn:</label>
                                    <input type='text' name='name' value='$name'>
                                    <label>Beskrivelse:</label>
                                    <textarea name='desc' rows='2'>$desc</textarea>
                                    <div class='actions'>
                                        <button type='submit' name='save_edit' class='btn-save'>Gem Ændringer</button>
                                        <button type='submit' name='delete_image' value='$bName' class='btn-del' onclick='return confirm(\"Slet dette billede?\")'>Slet</button>
                                    </div>
                                </form>
                            </div>
                        </div>";
                    }
                }
            }
        }
        if ($count === 0) echo "<p>Du har ikke uploadet nogen billeder med denne nøgle endnu.</p>";
        ?>
    </div>
</body>
</html>