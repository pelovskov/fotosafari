<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$configFile = 'config.json';
$defaultConfig = [
    'projektNavn' => 'Mit Fantastiske Projekt', 'underOverskrift' => 'En samling af vidunderlige billeder',
    'footerTekst' => '© 2025 - Alle rettigheder forbeholdes', 'uploadStart' => '', 'uploadEnd' => '',
    'ratings_enabled' => false,
    'map_enabled' => true,
    'cta_buttons' => [['active' => false, 'text' => '', 'link' => ''],['active' => false, 'text' => '', 'link' => ''],['active' => false, 'text' => '', 'link' => '']]
];
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : $defaultConfig;
if (!isset($config['cta_buttons'])) { $config['cta_buttons'] = $defaultConfig['cta_buttons']; }
if (!isset($config['ratings_enabled'])) { $config['ratings_enabled'] = $defaultConfig['ratings_enabled']; }
if (!isset($config['map_enabled'])) { $config['map_enabled'] = $defaultConfig['map_enabled']; }

// GEM INDSTILLINGER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $config['projektNavn'] = $_POST['projektNavn']; $config['underOverskrift'] = $_POST['underOverskrift'];
    $config['footerTekst'] = $_POST['footerTekst']; $config['uploadStart'] = $_POST['uploadStart'];
    $config['uploadEnd'] = $_POST['uploadEnd']; 
    $config['ratings_enabled'] = isset($_POST['ratings_enabled']);
    $config['map_enabled'] = isset($_POST['map_enabled']);
    for ($i = 0; $i < 3; $i++) {
        $config['cta_buttons'][$i]['active'] = isset($_POST['cta_active_'.($i+1)]);
        $config['cta_buttons'][$i]['text'] = $_POST['cta_text_'.($i+1)];
        $config['cta_buttons'][$i]['link'] = $_POST['cta_link_'.($i+1)];
    }
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    header('Location: admin.php');
    exit;
}

// GEM REDIGERING AF BILLEDE (Nu med GRUPPE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit'])) {
    $image_file_to_edit = basename($_POST['edit_image_file']);
    $text_file_path = 'uploads/' . pathinfo($image_file_to_edit, PATHINFO_FILENAME) . '.txt';
    if (file_exists($text_file_path)) {
        $newName = $_POST['edit_name'] ?? 'Ukendt';
        $newDescription = $_POST['edit_description'] ?? 'Ingen beskrivelse.';
        $newLatitude = $_POST['edit_latitude'] ?? '';
        $newLongitude = $_POST['edit_longitude'] ?? '';
        $newGroup = $_POST['edit_group'] ?? 'A'; // Gem gruppen

        // Bevar brugerens personlige nøgle (linje 6), så de stadig kan se/redigere
        // billedet på "Mine Billeder" efter en admin-redigering.
        $existingLines = file($text_file_path, FILE_IGNORE_NEW_LINES);
        $existingKey = $existingLines[5] ?? '';

        // Gemmer 6 linjer: Navn, Beskrivelse, Lat, Lng, Gruppe, Nøgle
        $newContent = $newName . "\n" . $newDescription . "\n" . $newLatitude . "\n" . $newLongitude . "\n" . $newGroup . "\n" . $existingKey;
        file_put_contents($text_file_path, $newContent);
    }
    header('Location: admin.php');
    exit;
}

// SLET BILLEDE (fuldstændigt - billede, tekstfil og evt. ratings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $deleted_basename = basename($_POST['delete_image']);
    $image_to_delete = 'uploads/' . $deleted_basename;
    $text_to_delete = 'uploads/' . pathinfo($image_to_delete, PATHINFO_FILENAME) . '.txt';
    if (file_exists($image_to_delete)) { unlink($image_to_delete); }
    if (file_exists($text_to_delete)) { unlink($text_to_delete); }

    // Fjern evt. gemte stjernebedømmelser for det slettede billede,
    // så intet spor af det ligger tilbage i ratings.json
    $ratingsFilePath = 'ratings.json';
    if (file_exists($ratingsFilePath)) {
        $allRatingsData = json_decode(file_get_contents($ratingsFilePath), true) ?: [];
        if (isset($allRatingsData[$deleted_basename])) {
            unset($allRatingsData[$deleted_basename]);
            file_put_contents($ratingsFilePath, json_encode($allRatingsData, JSON_PRETTY_PRINT));
        }
    }

    header('Location: admin.php');
    exit;
}

// ROTER BILLEDE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rotate_image'])) {
    $file_to_rotate = 'uploads/' . basename($_POST['rotate_image_file']);
    if (file_exists($file_to_rotate)) {
        $image_info = getimagesize($file_to_rotate); $image_type = $image_info[2]; $source_image = null;
        switch ($image_type) {
            case IMAGETYPE_JPEG: $source_image = imagecreatefromjpeg($file_to_rotate); break;
            case IMAGETYPE_PNG: $source_image = imagecreatefrompng($file_to_rotate); break;
            case IMAGETYPE_GIF: $source_image = imagecreatefromgif($file_to_rotate); break;
        }
        if ($source_image) {
            $rotated_image = imagerotate($source_image, -90, 0);
            if ($image_type == IMAGETYPE_PNG) { imagealphablending($rotated_image, false); imagesavealpha($rotated_image, true); }
            switch ($image_type) {
                case IMAGETYPE_JPEG: imagejpeg($rotated_image, $file_to_rotate, 90); break;
                case IMAGETYPE_PNG: imagepng($rotated_image, $file_to_rotate, 9); break;
                case IMAGETYPE_GIF: imagegif($rotated_image, $file_to_rotate); break;
            }
            imagedestroy($source_image); imagedestroy($rotated_image);
        }
    }
    header('Location: admin.php');
    exit;
}

// HENT RATINGS (til visning i toppen)
$rated_images = [];
$ratingsFile = 'ratings.json';
if (file_exists($ratingsFile)) {
    $all_ratings = json_decode(file_get_contents($ratingsFile), true);
    foreach ($all_ratings as $image_key => $rating_info) {
        $image_path = 'uploads/' . $image_key;
        if (file_exists($image_path)) {
            $vote_count = count($rating_info['ratings']);
            $avg_rating = $vote_count > 0 ? array_sum($rating_info['ratings']) / $vote_count : 0;
            $rated_images[] = ['file' => $image_path, 'avg' => $avg_rating, 'votes' => $vote_count];
        }
    }
    usort($rated_images, function($a, $b) { return $b['avg'] <=> $a['avg']; });
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Administration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; background-color: #f9f9f9; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        h1, h2 { color: #333; }
        .logout-link { float: right; }
        .settings-form, .image-list { margin-top: 30px; }
        .settings-form label { display: block; margin-top: 10px; font-weight: bold; }
        .settings-form input[type=text], .settings-form input[type=date], .settings-form input[type=url], .settings-form textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-danger { background-color: #dc3545; color: white; font-size: 0.8em; padding: 5px 10px; }
        .btn-secondary { background-color: #6c757d; color: white; font-size: 0.8em; padding: 5px 10px; }
        .btn-info { background-color: #17a2b8; color: white; font-size: 0.8em; padding: 5px 10px; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .image-card { border: 1px solid #eee; border-radius: 5px; padding: 10px; text-align: center; }
        .image-card img { max-width: 100%; height: 100px; object-fit: cover; border-radius: 4px; }
        .image-card p { font-size: 0.9em; margin: 5px 0; }
        .image-card-actions { display: flex; justify-content: space-around; align-items: center; margin-top: 10px; flex-wrap: wrap; gap: 5px; }
        .settings-form fieldset { border: 1px solid #ddd; padding: 15px; margin-top: 20px; border-radius: 5px; }
        details > summary { cursor: pointer; font-weight: bold; font-size: 1.5em; margin: 15px 0; outline: none; }
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .modal-content label { display: block; margin: 10px 0 5px; }
        .modal-content input, .modal-content textarea, .modal-content select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <a href="logout.php" class="logout-link">Log ud</a>
        <h1>Administration</h1>

        <!-- SETTINGS SEKTION (GENINDSAT) -->
        <details>
            <summary>Vis/Skjul Generelle Indstillinger & Knapper</summary>
            <div class="settings-form">
                <form method="POST" action="admin.php">
                    <h2>Generelle Indstillinger</h2>
                    <label for="projektNavn">Overskrift (Header)</label>
                    <input type="text" id="projektNavn" name="projektNavn" value="<?php echo htmlspecialchars($config['projektNavn']); ?>">
                    <label for="underOverskrift">Under-overskrift (Header)</label>
                    <input type="text" id="underOverskrift" name="underOverskrift" value="<?php echo htmlspecialchars($config['underOverskrift']); ?>">
                    <label for="footerTekst">Tekst i footer</label>
                    <input type="text" id="footerTekst" name="footerTekst" value="<?php echo htmlspecialchars($config['footerTekst']); ?>">
                    <label for="uploadStart">Åben for upload den:</label>
                    <input type="date" id="uploadStart" name="uploadStart" value="<?php echo htmlspecialchars($config['uploadStart']); ?>">
                    <label for="uploadEnd">Luk for upload den:</label>
                    <input type="date" id="uploadEnd" name="uploadEnd" value="<?php echo htmlspecialchars($config['uploadEnd']); ?>">
                    <fieldset>
                        <legend>Funktioner</legend>
                        <label><input type="checkbox" name="ratings_enabled" <?php echo ($config['ratings_enabled'] ?? false) ? 'checked' : ''; ?>> Aktivér stjerne-rating</label>
                        <label><input type="checkbox" name="map_enabled" <?php echo ($config['map_enabled'] ?? false) ? 'checked' : ''; ?>> Aktivér Kortvisning</label>
                    </fieldset>
                    <h2>Galleri Knapper (CTA)</h2>
                    <?php for ($i = 0; $i < 3; $i++): $button = $config['cta_buttons'][$i]; ?>
                    <fieldset>
                        <legend>Knap <?php echo $i + 1; ?></legend>
                        <label><input type="checkbox" name="cta_active_<?php echo $i + 1; ?>" <?php echo $button['active'] ? 'checked' : ''; ?>> Aktivér denne knap</label>
                        <label>Knap Tekst</label>
                        <input type="text" name="cta_text_<?php echo $i + 1; ?>" value="<?php echo htmlspecialchars($button['text']); ?>">
                        <label>Link (URL)</label>
                        <input type="url" name="cta_link_<?php echo $i + 1; ?>" value="<?php echo htmlspecialchars($button['link']); ?>">
                    </fieldset>
                    <?php endfor; ?>
                    <br><button type="submit" name="save_settings" class="btn btn-primary">Gem alle indstillinger</button>
                </form>
            </div>
        </details>
        
        <div style="margin-top: 30px;"><a href="download_zip.php" class="btn btn-success">Download alle billeder (ZIP)</a></div>

        <div class="image-list">
            <h2>Uploadede Billeder</h2>
            <div class="image-grid">
                <?php
                $image_files = glob("uploads/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
                if ($image_files) {
                    array_multisort(array_map('filemtime', $image_files), SORT_DESC, $image_files);
                    foreach ($image_files as $image) {
                        $text_file = 'uploads/' . pathinfo($image, PATHINFO_FILENAME) . '.txt';
                        $name = "Ukendt"; $description = "Ingen beskrivelse."; $lat = ""; $lng = ""; $grp = "A";
                        if (file_exists($text_file)) {
                            $lines = file($text_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            $name = htmlspecialchars($lines[0] ?? 'Ukendt');
                            $description = htmlspecialchars($lines[1] ?? 'Ingen beskrivelse.');
                            $lat = htmlspecialchars($lines[2] ?? '');
                            $lng = htmlspecialchars($lines[3] ?? '');
                            $grp = htmlspecialchars($lines[4] ?? 'A');
                        }
                        echo '<div class="image-card">';
                        echo '<img src="' . $image . '?t=' . time() . '" alt="' . $name . '">';
                        echo '<p><strong>' . $name . '</strong></p>';
                        echo '<p style="font-size:0.8em; color:#666;">Gruppe: ' . $grp . '</p>';
                        echo '<div class="image-card-actions">';
                        echo '<button class="btn btn-info" onclick="openEditModal(\'' . basename($image) . '\', \'' . addslashes($name) . '\', \'' . addslashes($description) . '\', \'' . $lat . '\', \'' . $lng . '\', \'' . $grp . '\')">Redigér</button>';
                        echo '<form method="POST" action="admin.php"><input type="hidden" name="rotate_image_file" value="' . basename($image) . '"><button type="submit" name="rotate_image" class="btn btn-secondary">Rotér</button></form>';
                        echo '<form method="POST" action="admin.php" onsubmit="return confirm(\'Slet?\');"><input type="hidden" name="delete_image" value="' . basename($image) . '"><button type="submit" class="btn btn-danger">Slet</button></form>';
                        echo '</div></div>';
                    }
                } else { echo "<p>Ingen billeder.</p>"; }
                ?>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL MED GRUPPE VALG -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
            <h2>Redigér Billedinformation</h2>
            <form method="POST" action="admin.php">
                <input type="hidden" id="edit_image_file" name="edit_image_file">
                <label>Navn / Uploader</label><input type="text" id="edit_name" name="edit_name">
                <label>Beskrivelse</label><textarea id="edit_description" name="edit_description"></textarea>
                
                <label>Gruppe (Lag på kortet)</label>
                <select id="edit_group" name="edit_group">
                    <option value="A">Gruppe A</option>
                    <option value="B">Gruppe B</option>
                    <option value="C">Gruppe C</option>
                </select>

                <label>Breddegrad (Lat)</label><input type="text" id="edit_latitude" name="edit_latitude">
                <label>Længdegrad (Lng)</label><input type="text" id="edit_longitude" name="edit_longitude">
                <br><br><button type="submit" name="save_edit" class="btn btn-primary">Gem ændringer</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('editModal');
        window.openEditModal = function(file, name, desc, lat, lng, grp) {
            document.getElementById('edit_image_file').value = file;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = desc;
            document.getElementById('edit_latitude').value = lat;
            document.getElementById('edit_longitude').value = lng;
            document.getElementById('edit_group').value = grp || 'A';
            modal.style.display = 'block';
        }
        window.closeEditModal = function() { modal.style.display = 'none'; }
        window.onclick = function(e) { if (e.target == modal) closeEditModal(); }
        
        // Håndter details elementet
        const details = document.querySelector('details');
        const summary = document.querySelector('summary');
        if(summary) {
            summary.addEventListener('click', function(e) {
                e.preventDefault();
                details.hasAttribute('open') ? details.removeAttribute('open') : details.setAttribute('open', '');
            });
        }
    </script>
</body>
</html>