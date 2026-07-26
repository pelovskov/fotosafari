<?php
session_start();
date_default_timezone_set('Europe/Copenhagen');

/**
 * Øger farvemætningen pixel for pixel (GD har ingen indbygget "saturation"-filter).
 * Køres først EFTER at billedet er skaleret ned til maks. 800px bredde,
 * så det forbliver hurtigt selv på store mobilbilleder.
 */
function boostSaturation($image, $factor) {
    $width = imagesx($image);
    $height = imagesy($image);
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;

            $max = max($r, $g, $b); $min = min($r, $g, $b);
            $l = ($max + $min) / 2;

            if ($max == $min) { continue; } // ren gråtone - intet at mætte

            $d = $max - $min;
            $s = $l > 127.5 ? $d / (510 - $max - $min) : $d / ($max + $min);
            $s = min(1, $s * $factor);

            // Konverter tilbage til RGB med den nye mætning (samme lysstyrke/hue)
            $newD = ($l > 127.5 ? (1 - abs(2 * $l / 255 - 1)) : (2 * $l / 255)) * $s * 255;
            $avg = ($max + $min) / 2;
            $scale = $d > 0 ? $newD / $d : 1;
            $nr = $avg + ($r - $avg) * $scale;
            $ng = $avg + ($g - $avg) * $scale;
            $nb = $avg + ($b - $avg) * $scale;

            $color = imagecolorallocate(
                $image,
                max(0, min(255, (int)$nr)),
                max(0, min(255, (int)$ng)),
                max(0, min(255, (int)$nb))
            );
            imagesetpixel($image, $x, $y, $color);
        }
    }
}

$configFile = 'config.json';
$defaultConfig = [ 'projektNavn' => 'Mit Projekt', 'underOverskrift' => '', 'footerTekst' => '', 'uploadStart' => '', 'uploadEnd' => '' ];
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : $defaultConfig;
$projektNavn = $config['projektNavn']; $underOverskrift = $config['underOverskrift']; $footerTekst = $config['footerTekst'];
$uploadStart = $config['uploadStart']; $uploadEnd = $config['uploadEnd'];

$isUploadOpen = true; $today = date('Y-m-d');
if (!empty($uploadStart) && $today < $uploadStart) { $isUploadOpen = false; }
if (!empty($uploadEnd) && $today > $uploadEnd) { $isUploadOpen = false; }

// Håndter NAVN
$display_name = '';
if (isset($_GET['navn'])) { $display_name = htmlspecialchars(urldecode($_GET['navn'])); $_SESSION['last_used_name'] = $display_name; } 
elseif (isset($_SESSION['last_used_name'])) { $display_name = $_SESSION['last_used_name']; }

// Håndter GRUPPE
$current_group = 'A';
if (isset($_GET['gruppe'])) { $current_group = htmlspecialchars(urldecode($_GET['gruppe'])); $_SESSION['last_used_group'] = $current_group; } 
elseif (isset($_SESSION['last_used_group'])) { $current_group = $_SESSION['last_used_group']; }

// Håndter KEY (Nøglen til "Min Side")
$user_key = '';
if (isset($_GET['key'])) { $user_key = htmlspecialchars($_GET['key']); $_SESSION['user_key'] = $user_key; }
elseif (isset($_SESSION['user_key'])) { $user_key = $_SESSION['user_key']; }

$upload_success = false; $error_message = '';
if ($isUploadOpen && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {
    if (isset($_POST['name'])) { $_SESSION['last_used_name'] = htmlspecialchars($_POST['name']); }
    if (isset($_POST['group_hidden'])) { $current_group = htmlspecialchars($_POST['group_hidden']); $_SESSION['last_used_group'] = $current_group; }
    if (isset($_POST['key_hidden'])) { $user_key = htmlspecialchars($_POST['key_hidden']); $_SESSION['user_key'] = $user_key; }

    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
    $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $base = uniqid('billede_', true);
    $target = $upload_dir . $base . '.' . $ext;
    
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if($check !== false && in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        $src = null; $type = $check[2];
        if ($type == IMAGETYPE_JPEG) $src = imagecreatefromjpeg($_FILES['image']['tmp_name']);
        elseif ($type == IMAGETYPE_PNG) $src = imagecreatefrompng($_FILES['image']['tmp_name']);
        elseif ($type == IMAGETYPE_GIF) $src = imagecreatefromgif($_FILES['image']['tmp_name']);

        if ($src) {
            // EXIF
            if ($type == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $exif = @exif_read_data($_FILES['image']['tmp_name']);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3: $src = imagerotate($src, 180, 0); break;
                        case 6: $src = imagerotate($src, -90, 0); break;
                        case 8: $src = imagerotate($src, 90, 0); break;
                    }
                }
            }
            // GPS Logic
            $lat = ''; $lng = '';
            if (isset($exif['GPSLatitude'], $exif['GPSLongitude'])) {
                 // (Simpel GPS logik for kortheds skyld - samme som før)
                 // ... Forudsætter din eksisterende GPS logik her ...
                 // Men for at holde koden "ren" her, så lad os antage den virker
                 // Jeg indsætter den fulde blok for en sikkerheds skyld:
                 function toDec($c, $h) {
                    $d = explode('/', $c[0]); $d=$d[0]/$d[1];
                    $m = explode('/', $c[1]); $m=$m[0]/$m[1];
                    $s = explode('/', $c[2]); $s=$s[0]/$s[1];
                    $val = $d+($m/60)+($s/3600); return ($h=='S'||$h=='W')?-$val:$val;
                 }
                 $lat = round(toDec($exif['GPSLatitude'], $exif['GPSLatitudeRef']),6);
                 $lng = round(toDec($exif['GPSLongitude'], $exif['GPSLongitudeRef']),6);
            }

            // Resize FØRST (så filtrene arbejder på det mindre, hurtigere billede)
            $mw = 800; $w = imagesx($src); $h = imagesy($src);
            if ($w > $mw) { $nh = $h*($mw/$w); $fin = imagecreatetruecolor($mw, $nh); imagecopyresampled($fin, $src, 0,0,0,0, $mw, $nh, $w, $h); }
            else { $fin = $src; }

            // Filter (anvendes nu på det færdig-resizede billede)
            $f = $_POST['image_filter'] ?? 'original';
            if ($f == 'grayscale') {
                imagefilter($fin, IMG_FILTER_GRAYSCALE);
            }
            if ($f == 'sepia') {
                imagefilter($fin, IMG_FILTER_GRAYSCALE);
                imagefilter($fin, IMG_FILTER_COLORIZE, 90, 60, 40);
            }
            if ($f == 'vivid') {
                boostSaturation($fin, 1.6); // ægte mætheds-boost, pixel for pixel
                imagefilter($fin, IMG_FILTER_CONTRAST, -8); // et lille ekstra "pop"
            }
            if ($f == 'contrast') {
                imagefilter($fin, IMG_FILTER_GRAYSCALE);
                imagefilter($fin, IMG_FILTER_CONTRAST, -35);
            }
            if ($f == 'vintage') {
                imagefilter($fin, IMG_FILTER_BRIGHTNESS, 12);
                imagefilter($fin, IMG_FILTER_CONTRAST, 18);
                imagefilter($fin, IMG_FILTER_COLORIZE, 20, 10, -12);
            }

            if ($type == IMAGETYPE_JPEG) imagejpeg($fin, $target, 90);
            elseif ($type == IMAGETYPE_PNG) imagepng($fin, $target, 9);
            else imagegif($fin, $target);
            imagedestroy($src); if(isset($fin) && $fin !== $src) imagedestroy($fin);

            // GEM DATA (Inkl. Nøgle på linje 6)
            $txt = $upload_dir . $base . '.txt';
            $n = htmlspecialchars($_POST['name']); $d = htmlspecialchars($_POST['description']);
            // Linjer: Navn, Beskrivelse, Lat, Lng, Gruppe, KEY
            file_put_contents($txt, "$n\n$d\n$lat\n$lng\n$current_group\n$user_key");
            $upload_success = true;
        }
    } else { $error_message = "Ugyldig filtype."; }
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload - <?php echo htmlspecialchars($projektNavn); ?></title>
    <style>
        :root { --bg: #f4f4f4; --con: #fff; --txt: #333; }
        [data-theme="dark"] { --bg: #121212; --con: #1e1e1e; --txt: #e0e0e0; }
        body { font-family: sans-serif; background: var(--bg); color: var(--txt); padding-top: 80px; margin: 0; }
        .container { max-width: 800px; margin: 20px auto; background: var(--con); padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .sticky-header { position: fixed; top: 0; width: 100%; background: var(--con); height: 80px; display: flex; align-items: center; justify-content: center; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .sticky-header h1 { margin: 0; font-size: 1.5em; }
        .sticky-header p { margin: 0; font-size: 0.9em; color: #777; }
        input[type="text"], textarea, input[type="file"] { width: 100%; padding: 10px; margin-bottom: 15px; box-sizing: border-box; }
        .cta-button { display: inline-block; padding: 12px 25px; background: #28a745; color: #fff; text-decoration: none; border-radius: 5px; margin: 5px; }
        input[type="submit"] { background: #007bff; color: white; border: none; padding: 12px 25px; cursor: pointer; border-radius: 5px; font-size: 16px; }
        .group-badge { display: inline-block; background: #666; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.8em; margin-bottom: 10px; }
        .my-page-link { display: block; margin-top: 20px; text-align: center; }
        .my-page-btn { background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <header class="sticky-header">
        <div><h1><?php echo htmlspecialchars($projektNavn); ?></h1><p><?php echo htmlspecialchars($underOverskrift); ?></p></div>
    </header>
    <div class="container">
        <?php if ($user_key): ?>
            <div class="my-page-link">
                <a href="minside.php?key=<?php echo $user_key; ?>" class="my-page-btn">📂 Gå til Mine Billeder</a>
            </div>
            <hr>
        <?php endif; ?>

        <?php if ($upload_success): ?>
            <div style="text-align:center;">
                <h2>Tak!</h2><p>Billedet er uploadet til Gruppe <?php echo $current_group; ?>.</p>
                <a href="galleri.php" class="cta-button">Se Galleri</a>
                <a href="upload.php" class="cta-button" style="background:#007bff;">Upload mere</a>
            </div>
        <?php else: ?>
            <?php if ($error_message) echo "<p style='color:red;text-align:center;'>$error_message</p>"; ?>
            <?php if ($isUploadOpen): ?>
                <form action="upload.php" method="post" enctype="multipart/form-data">
                    <div style="text-align:center;">
                        <span class="group-badge">Gruppe: <?php echo $current_group; ?></span>
                        <input type="hidden" name="group_hidden" value="<?php echo $current_group; ?>">
                        <input type="hidden" name="key_hidden" value="<?php echo $user_key; ?>">
                    </div>
                    <label>Dit Navn:</label>
                    <input type="text" name="name" value="<?php echo $display_name; ?>" required>
                    <label>Beskrivelse:</label>
                    <textarea name="description" rows="3" required></textarea>
                    <label>Vælg billede:</label>
                    <input type="file" name="image" required>
                    
                    <div style="margin: 15px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        <p style="margin-top:0;"><strong>Effekter:</strong></p>
                        <label><input type="radio" name="image_filter" value="original" checked> Ingen</label>
                        <label><input type="radio" name="image_filter" value="grayscale"> Sort/Hvid</label>
                        <label><input type="radio" name="image_filter" value="sepia"> Sepia</label>
                        <label><input type="radio" name="image_filter" value="vivid"> Stærke Farver</label>
                        <label><input type="radio" name="image_filter" value="contrast"> Høj Kontrast</label>
                        <label><input type="radio" name="image_filter" value="vintage"> Gammelt Foto</label>
                    </div>

                    <input type="submit" name="submit" value="Upload Billede">
                </form>
            <?php else: ?>
                <p style="text-align:center;">Upload er lukket.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>