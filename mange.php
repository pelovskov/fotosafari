<?php
/**
 * mange.php - Bulk-upload til Fotosafari
 *
 * Selvstændig side ved siden af upload.php. Rører IKKE upload.php.
 * Skriver præcis samme filer som upload.php:
 *   uploads/billede_xxxx.jpg  +  uploads/billede_xxxx.txt
 *   .txt indeholder 6 linjer: Navn, Beskrivelse, Lat, Lng, Gruppe, Nøgle
 * Lat og Lng er altid tomme her (Camp Snap-billeder har ingen GPS),
 * så billederne havner i galleriet, men ikke på kortet.
 *
 * Browseren sender ét billede ad gangen, så vi aldrig rammer PHP's
 * grænser for antal filer, POST-størrelse eller køretid.
 */

session_start();
date_default_timezone_set('Europe/Copenhagen');
@ini_set('memory_limit', '256M');

// ============ INDSTILLINGER ============
$ADGANGSKODE = 'skift-mig';   // Sæt til '' (tom) for at slå adgangskoden helt fra
$MAX_BREDDE  = 800;           // Samme som upload.php
// =======================================

/**
 * Øger farvemætningen pixel for pixel (GD har ingen indbygget "saturation"-filter).
 * Identisk med den i upload.php, så "Stærke Farver" ser ens ud begge steder.
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

/** Anvender samme effekter som upload.php. */
function anvendFilter($img, $f) {
    if ($f == 'grayscale') {
        imagefilter($img, IMG_FILTER_GRAYSCALE);
    } elseif ($f == 'sepia') {
        imagefilter($img, IMG_FILTER_GRAYSCALE);
        imagefilter($img, IMG_FILTER_COLORIZE, 90, 60, 40);
    } elseif ($f == 'vivid') {
        boostSaturation($img, 1.6);
        imagefilter($img, IMG_FILTER_CONTRAST, -8);
    } elseif ($f == 'contrast') {
        imagefilter($img, IMG_FILTER_GRAYSCALE);
        imagefilter($img, IMG_FILTER_CONTRAST, -35);
    } elseif ($f == 'vintage') {
        imagefilter($img, IMG_FILTER_BRIGHTNESS, 12);
        imagefilter($img, IMG_FILTER_CONTRAST, 18);
        imagefilter($img, IMG_FILTER_COLORIZE, 20, 10, -12);
    }
}

/** Behandler og gemmer ét billede. Returnerer ['ok'=>bool, 'fejl'=>string, 'fil'=>string]. */
function gemBillede($fil, $navn, $besk, $gruppe, $key, $filter, $maxBredde) {
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

    if ($fil['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'fejl' => 'Filen kom ikke helt frem (kode ' . $fil['error'] . ')'];
    }

    $ext = strtolower(pathinfo($fil['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpe') { $ext = 'jpg'; }
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        return ['ok' => false, 'fejl' => 'Ugyldig filtype'];
    }

    $check = getimagesize($fil['tmp_name']);
    if ($check === false) {
        return ['ok' => false, 'fejl' => 'Filen er ikke et billede'];
    }

    $type = $check[2];
    $src = null;
    if ($type == IMAGETYPE_JPEG)     $src = imagecreatefromjpeg($fil['tmp_name']);
    elseif ($type == IMAGETYPE_PNG)  $src = imagecreatefrompng($fil['tmp_name']);
    elseif ($type == IMAGETYPE_GIF)  $src = imagecreatefromgif($fil['tmp_name']);
    if (!$src) { return ['ok' => false, 'fejl' => 'Kunne ikke åbne billedet' ]; }

    // Drej efter EXIF, så stående billeder ikke ligger ned
    if ($type == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($fil['tmp_name']);
        if (!empty($exif['Orientation'])) {
            $grader = 0;
            if ($exif['Orientation'] == 3) $grader = 180;
            elseif ($exif['Orientation'] == 6) $grader = -90;
            elseif ($exif['Orientation'] == 8) $grader = 90;
            if ($grader !== 0) {
                $roteret = imagerotate($src, $grader, 0);
                imagedestroy($src);
                $src = $roteret;
            }
        }
    }

    // Skalér ned FØRST, så filtrene arbejder på det lille billede
    $w = imagesx($src); $h = imagesy($src);
    if ($w > $maxBredde) {
        $nh = (int)round($h * ($maxBredde / $w));
        $fin = imagecreatetruecolor($maxBredde, $nh);
        imagecopyresampled($fin, $src, 0, 0, 0, 0, $maxBredde, $nh, $w, $h);
    } else {
        $fin = $src;
    }

    anvendFilter($fin, $filter);

    $base   = uniqid('billede_', true);
    $target = $upload_dir . $base . '.' . $ext;

    if ($type == IMAGETYPE_JPEG)     $gemt = imagejpeg($fin, $target, 90);
    elseif ($type == IMAGETYPE_PNG)  $gemt = imagepng($fin, $target, 9);
    else                             $gemt = imagegif($fin, $target);

    if ($fin !== $src) { imagedestroy($fin); }
    imagedestroy($src);

    if (!$gemt) { return ['ok' => false, 'fejl' => 'Kunne ikke skrive filen (rettigheder?)']; }

    // Samme 6-linjers format som upload.php. Lat og Lng er tomme.
    $n = htmlspecialchars($navn);
    $d = htmlspecialchars($besk);
    $g = htmlspecialchars($gruppe);
    $k = htmlspecialchars($key);
    file_put_contents($upload_dir . $base . '.txt', "$n\n$d\n\n\n$g\n$k");

    return ['ok' => true, 'fil' => basename($target)];
}

// ---------- Konfiguration og åbningstid (samme logik som upload.php) ----------
$configFile = 'config.json';
$defaultConfig = ['projektNavn' => 'Mit Projekt', 'underOverskrift' => '', 'footerTekst' => '', 'uploadStart' => '', 'uploadEnd' => ''];
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : $defaultConfig;
$projektNavn     = $config['projektNavn'] ?? 'Mit Projekt';
$underOverskrift = $config['underOverskrift'] ?? '';
$uploadStart     = $config['uploadStart'] ?? '';
$uploadEnd       = $config['uploadEnd'] ?? '';

$isUploadOpen = true; $today = date('Y-m-d');
if (!empty($uploadStart) && $today < $uploadStart) { $isUploadOpen = false; }
if (!empty($uploadEnd)   && $today > $uploadEnd)   { $isUploadOpen = false; }

// ---------- Adgangskode ----------
$login_fejl = '';
if ($ADGANGSKODE === '') {
    $adgang = true;
} else {
    if (isset($_POST['adgangskode'])) {
        if (hash_equals($ADGANGSKODE, $_POST['adgangskode'])) { $_SESSION['bulk_ok'] = true; }
        else { $login_fejl = 'Forkert adgangskode.'; }
    }
    if (isset($_GET['logud'])) { unset($_SESSION['bulk_ok']); }
    $adgang = !empty($_SESSION['bulk_ok']);
}

// ---------- Modtag ét billede (kaldes af browseren, ét ad gangen) ----------
if (isset($_POST['action']) && $_POST['action'] === 'upload') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$adgang)       { echo json_encode(['ok' => false, 'fejl' => 'Ingen adgang']); exit; }
    if (!$isUploadOpen) { echo json_encode(['ok' => false, 'fejl' => 'Upload er lukket']); exit; }
    if (!isset($_FILES['image'])) { echo json_encode(['ok' => false, 'fejl' => 'Ingen fil modtaget']); exit; }

    $svar = gemBilledeWrapper($MAX_BREDDE);
    echo json_encode($svar);
    exit;
}

/** Lille indpakning, så vi kan hente POST-felterne ét sted. */
function gemBilledeWrapper($maxBredde) {
    $navn   = trim($_POST['navn'] ?? '');
    $besk   = trim($_POST['beskrivelse'] ?? '');
    $gruppe = trim($_POST['gruppe'] ?? 'A');
    $key    = trim($_POST['key'] ?? '');
    $filter = $_POST['filter'] ?? 'original';
    if ($navn === '') { return ['ok' => false, 'fejl' => 'Navn mangler']; }
    return gemBillede($_FILES['image'], $navn, $besk, $gruppe, $key, $filter, $maxBredde);
}

// ---------- Navn, gruppe og nøgle (samme kilder som upload.php) ----------
$display_name = '';
if (isset($_GET['navn'])) { $display_name = htmlspecialchars(urldecode($_GET['navn'])); $_SESSION['last_used_name'] = $display_name; }
elseif (isset($_SESSION['last_used_name'])) { $display_name = $_SESSION['last_used_name']; }

$current_group = 'A';
if (isset($_GET['gruppe'])) { $current_group = htmlspecialchars(urldecode($_GET['gruppe'])); $_SESSION['last_used_group'] = $current_group; }
elseif (isset($_SESSION['last_used_group'])) { $current_group = $_SESSION['last_used_group']; }

$user_key = '';
if (isset($_GET['key'])) { $user_key = htmlspecialchars($_GET['key']); $_SESSION['user_key'] = $user_key; }
elseif (isset($_SESSION['user_key'])) { $user_key = $_SESSION['user_key']; }
?>
<!DOCTYPE html>
<html lang="da">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload mange billeder - <?php echo htmlspecialchars($projektNavn); ?></title>
<style>
    :root { --bg: #f4f4f4; --con: #fff; --txt: #333; --lin: #ddd; }
    body { font-family: sans-serif; background: var(--bg); color: var(--txt); padding-top: 80px; margin: 0; }
    .container { max-width: 900px; margin: 20px auto 60px; background: var(--con); padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .sticky-header { position: fixed; top: 0; width: 100%; background: var(--con); height: 80px; display: flex; align-items: center; justify-content: center; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
    .sticky-header h1 { margin: 0; font-size: 1.5em; }
    .sticky-header p { margin: 0; font-size: 0.9em; color: #777; }
    label { display: block; margin-bottom: 6px; font-weight: 600; }
    input[type="text"], input[type="password"], textarea, select, input[type="file"] { width: 100%; padding: 10px; margin-bottom: 15px; box-sizing: border-box; font-size: 16px; }
    textarea { resize: vertical; }
    .hjaelp { font-size: 0.85em; color: #777; margin: -10px 0 15px; }
    .knap { background: #007bff; color: #fff; border: none; padding: 12px 25px; cursor: pointer; border-radius: 5px; font-size: 16px; }
    .knap:disabled { background: #999; cursor: not-allowed; }
    .cta-button { display: inline-block; padding: 12px 25px; background: #28a745; color: #fff; text-decoration: none; border-radius: 5px; margin: 5px 5px 5px 0; }
    .group-badge { display: inline-block; background: #666; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 0.8em; }
    .boks { border: 1px solid var(--lin); border-radius: 6px; padding: 12px; margin-bottom: 20px; }
    .boks p.titel { margin: 0 0 8px; font-weight: 600; }
    .boks label { display: inline-block; font-weight: normal; margin: 0 12px 4px 0; }
    #liste { margin-top: 10px; }
    .raekke { display: flex; gap: 12px; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--lin); }
    .raekke img { width: 64px; height: 64px; object-fit: cover; border-radius: 4px; flex: none; background: #eee; }
    .raekke .felt { flex: 1; min-width: 0; }
    .raekke .filnavn { font-size: 0.78em; color: #888; margin-bottom: 4px; word-break: break-all; }
    .raekke input[type="text"] { margin-bottom: 0; padding: 7px; font-size: 15px; }
    .raekke .status { flex: none; width: 90px; text-align: right; font-size: 0.85em; color: #888; }
    .status.ok { color: #28a745; font-weight: 600; }
    .status.fejl { color: #c00; font-weight: 600; }
    .fjern { flex: none; background: none; border: none; color: #999; font-size: 20px; cursor: pointer; line-height: 1; padding: 0 4px; }
    .fjern:hover { color: #c00; }
    #fremdrift { margin: 15px 0; font-weight: 600; min-height: 24px; }
    .advarsel { background: #fff3cd; border: 1px solid #ffe08a; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
</style>
</head>
<body>
<header class="sticky-header">
    <div><h1><?php echo htmlspecialchars($projektNavn); ?></h1><p>Upload mange billeder</p></div>
</header>

<div class="container">

<?php if (!$adgang): ?>
    <h2>Adgang</h2>
    <p>Denne side er til lærere og hjælpere, der skal lægge mange billeder op ad gangen.</p>
    <?php if ($login_fejl) echo '<p style="color:#c00;">' . htmlspecialchars($login_fejl) . '</p>'; ?>
    <form method="post">
        <label for="kode">Adgangskode:</label>
        <input type="password" id="kode" name="adgangskode" autofocus required>
        <button type="submit" class="knap">Log ind</button>
    </form>

<?php elseif (!$isUploadOpen): ?>
    <div class="advarsel">
        <strong>Upload er lukket.</strong>
        <p style="margin-bottom:0;">Perioden i indstillingerne er
        <?php echo $uploadStart ? 'fra ' . htmlspecialchars($uploadStart) : ''; ?>
        <?php echo $uploadEnd ? 'til og med ' . htmlspecialchars($uploadEnd) : ''; ?>.
        Skal der uploades billeder bagefter, kan slutdatoen rykkes i administrationen.</p>
    </div>

<?php else: ?>
    <p><span class="group-badge">Gruppe: <?php echo $current_group; ?></span></p>

    <label for="navn">Uploaderens navn (står som fotograf på alle billederne):</label>
    <input type="text" id="navn" value="<?php echo $display_name; ?>" required>

    <label for="gruppe">Gruppe:</label>
    <select id="gruppe">
        <option value="A"<?php if($current_group=='A') echo ' selected'; ?>>Gruppe A</option>
        <option value="B"<?php if($current_group=='B') echo ' selected'; ?>>Gruppe B</option>
        <option value="C"<?php if($current_group=='C') echo ' selected'; ?>>Gruppe C</option>
    </select>

    <label for="faelles">Fælles beskrivelse:</label>
    <textarea id="faelles" rows="2" placeholder="Fx: Camp Snap, byvandring i Sydbyen, 12. september"></textarea>
    <p class="hjaelp">Teksten sættes ind på alle billederne herunder. Retter du et enkelt billede, beholder det sin egen tekst - også selvom du bagefter ændrer fællesteksten.</p>

    <div class="boks">
        <p class="titel">Effekt (gælder alle billeder i denne omgang):</p>
        <label><input type="radio" name="filter" value="original" checked> Ingen</label>
        <label><input type="radio" name="filter" value="grayscale"> Sort/Hvid</label>
        <label><input type="radio" name="filter" value="sepia"> Sepia</label>
        <label><input type="radio" name="filter" value="vivid"> Stærke Farver</label>
        <label><input type="radio" name="filter" value="contrast"> Høj Kontrast</label>
        <label><input type="radio" name="filter" value="vintage"> Gammelt Foto</label>
    </div>

    <label for="filer">Vælg billeder (du kan vælge mange på én gang):</label>
    <input type="file" id="filer" accept="image/jpeg,image/png,image/gif" multiple>
    <p class="hjaelp">Vælger du flere gange, lægges billederne til listen i stedet for at erstatte den.</p>

    <div id="liste"></div>
    <div id="fremdrift"></div>

    <button type="button" class="knap" id="startBtn" disabled>Upload billederne</button>
    <div id="afslut" style="margin-top:20px;"></div>

    <p style="margin-top:30px; font-size:0.85em; color:#999;">
        Billederne får ingen position og vises derfor kun i galleriet, ikke på kortet.
        <?php if ($ADGANGSKODE !== ''): ?> &middot; <a href="mange.php?logud=1" style="color:#999;">Log ud</a><?php endif; ?>
    </p>

<script>
(function () {
    const filInput  = document.getElementById('filer');
    const listeEl   = document.getElementById('liste');
    const faellesEl = document.getElementById('faelles');
    const startBtn  = document.getElementById('startBtn');
    const fremdrift = document.getElementById('fremdrift');
    const afslut    = document.getElementById('afslut');

    // Hvert element: { fil, tekst, egenTekst, sendt }
    let poster = [];
    let koerer = false;

    filInput.addEventListener('change', function () {
        for (const fil of filInput.files) {
            poster.push({ fil: fil, tekst: faellesEl.value, egenTekst: false, sendt: false });
        }
        filInput.value = ''; // så samme fil kan vælges igen efter behov
        tegnListe();
    });

    // Fællesteksten opdaterer kun de billeder, man ikke selv har rettet
    faellesEl.addEventListener('input', function () {
        poster.forEach(function (p, i) {
            if (!p.egenTekst && !p.sendt) {
                p.tekst = faellesEl.value;
                const felt = document.getElementById('tekst-' + i);
                if (felt) { felt.value = p.tekst; }
            }
        });
    });

    function tegnListe() {
        listeEl.innerHTML = '';
        poster.forEach(function (p, i) {
            const raekke = document.createElement('div');
            raekke.className = 'raekke';
            raekke.id = 'raekke-' + i;

            const billede = document.createElement('img');
            billede.src = URL.createObjectURL(p.fil);
            billede.onload = function () { URL.revokeObjectURL(billede.src); };
            raekke.appendChild(billede);

            const felt = document.createElement('div');
            felt.className = 'felt';
            const navnEl = document.createElement('div');
            navnEl.className = 'filnavn';
            navnEl.textContent = p.fil.name;
            const tekstEl = document.createElement('input');
            tekstEl.type = 'text';
            tekstEl.id = 'tekst-' + i;
            tekstEl.value = p.tekst;
            tekstEl.placeholder = 'Beskrivelse';
            tekstEl.addEventListener('input', function () {
                p.tekst = tekstEl.value;
                p.egenTekst = true;
            });
            felt.appendChild(navnEl);
            felt.appendChild(tekstEl);
            raekke.appendChild(felt);

            const status = document.createElement('div');
            status.className = 'status';
            status.id = 'status-' + i;
            status.textContent = p.sendt ? 'Uploadet' : 'Klar';
            if (p.sendt) { status.className = 'status ok'; }
            raekke.appendChild(status);

            const fjern = document.createElement('button');
            fjern.type = 'button';
            fjern.className = 'fjern';
            fjern.title = 'Fjern fra listen';
            fjern.innerHTML = '&times;';
            fjern.addEventListener('click', function () {
                poster.splice(i, 1);
                tegnListe();
            });
            raekke.appendChild(fjern);

            listeEl.appendChild(raekke);
        });

        const mangler = poster.filter(function (p) { return !p.sendt; }).length;
        startBtn.disabled = koerer || mangler === 0;
        startBtn.textContent = mangler > 0 ? 'Upload ' + mangler + ' billede' + (mangler === 1 ? '' : 'r') : 'Upload billederne';
    }

    startBtn.addEventListener('click', async function () {
        const navn = document.getElementById('navn').value.trim();
        if (!navn) { alert('Skriv et navn først.'); return; }

        koerer = true;
        startBtn.disabled = true;
        filInput.disabled = true;
        afslut.innerHTML = '';

        const gruppe = document.getElementById('gruppe').value;
        const filter = document.querySelector('input[name="filter"]:checked').value;
        const rest = poster.filter(function (p) { return !p.sendt; }).length;
        let klaret = 0, fejlede = 0, nr = 0;

        for (let i = 0; i < poster.length; i++) {
            const p = poster[i];
            if (p.sendt) { continue; }
            nr++;
            fremdrift.textContent = 'Uploader ' + nr + ' af ' + rest + '...';
            const status = document.getElementById('status-' + i);
            if (status) { status.className = 'status'; status.textContent = 'Sender...'; }

            const data = new FormData();
            data.append('action', 'upload');
            data.append('navn', navn);
            data.append('gruppe', gruppe);
            data.append('key', <?php echo json_encode($user_key); ?>);
            data.append('filter', filter);
            data.append('beskrivelse', p.tekst);
            data.append('image', p.fil);

            try {
                const svar = await fetch('mange.php', { method: 'POST', body: data });
                const resultat = await svar.json();
                if (resultat.ok) {
                    p.sendt = true; klaret++;
                    if (status) { status.className = 'status ok'; status.textContent = 'Uploadet'; }
                } else {
                    fejlede++;
                    if (status) { status.className = 'status fejl'; status.textContent = resultat.fejl || 'Fejl'; }
                }
            } catch (e) {
                fejlede++;
                if (status) { status.className = 'status fejl'; status.textContent = 'Ingen forbindelse'; }
            }
        }

        koerer = false;
        filInput.disabled = false;
        fremdrift.textContent = klaret + ' billede' + (klaret === 1 ? '' : 'r') + ' uploadet'
            + (fejlede ? ', ' + fejlede + ' fejlede - de står tilbage på listen og kan forsøges igen' : '') + '.';

        if (klaret > 0) {
            afslut.innerHTML = '<a href="galleri.php" class="cta-button">Se Galleri</a>';
        }
        tegnListe();
    });
})();
</script>

<?php endif; ?>

</div>
</body>
</html>
