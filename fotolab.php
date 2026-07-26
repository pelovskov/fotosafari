<?php
/* ============================================================
   FOTOLAB - pædagogisk filter-viser
   ------------------------------------------------------------
   Viser billeder som filmstribe + stort billede ovenover.
   Filtrene er de SAMME som i upload.php (samme tal-værdier),
   men beregnes i browseren (canvas) - serveren røres ikke.

   OPSÆTNING: Ret evt. mappen herunder.
   Findes mappen 'fotolab-billeder/' bruges den automatisk
   i stedet for 'uploads/' - så kan du lægge test-billeder
   dér uden at blande dem med rigtige uploads.
   ============================================================ */

$mappe = is_dir('fotolab-billeder') ? 'fotolab-billeder/' : 'uploads/';

$billeder = [];
foreach (glob($mappe . '*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE) as $fil) {
    $titel = pathinfo($fil, PATHINFO_FILENAME);
    $navn  = '';
    // Læs evt. tilhørende .txt (linje 1 = navn, linje 2 = titel) som i Fotosafari
    $txt = preg_replace('/\.(jpe?g|png|gif)$/i', '.txt', $fil);
    if (file_exists($txt)) {
        $linjer = file($txt, FILE_IGNORE_NEW_LINES);
        if (!empty($linjer[0])) $navn  = trim($linjer[0]);
        if (!empty($linjer[1])) $titel = trim($linjer[1]);
    }
    $billeder[] = ['fil' => $fil, 'titel' => $titel, 'navn' => $navn];
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fotolab</title>
<style>
    :root {
        --bg: #eef1f4;
        --kort: #ffffff;
        --kant: #d8dde3;
        --tekst: #2c3540;
        --dæmpet: #6b7683;
        --accent: #2f6fb2;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: var(--bg);
        color: var(--tekst);
    }
    .ramme { max-width: 900px; margin: 0 auto; padding: 20px 14px 40px; }

    header { text-align: center; margin-bottom: 18px; }
    header h1 { margin: 0 0 4px; font-size: 1.7em; letter-spacing: .5px; }
    header p  { margin: 0; color: var(--dæmpet); font-size: .95em; }

    .kort {
        background: var(--kort);
        border: 1px solid var(--kant);
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 2px 10px rgba(0,0,0,.06);
    }

    /* ---- Stort billede ---- */
    .scene {
        position: relative;
        background: #1d232a;
        border-radius: 8px;
        min-height: 260px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    #lærred { max-width: 100%; max-height: 60vh; display: block; border-radius: 4px; }
    #billedInfo {
        text-align: center;
        color: var(--dæmpet);
        font-size: .9em;
        margin: 8px 0 0;
        min-height: 1.2em;
    }
    #billedInfo strong { color: var(--tekst); }

    .arbejder {
        position: absolute; inset: 0;
        display: none; align-items: center; justify-content: center;
        background: rgba(29,35,42,.55); color: #fff; font-size: .95em;
        border-radius: 8px;
    }
    .arbejder.vis { display: flex; }

    /* ---- Filterknapper ---- */
    .filtre {
        display: flex; flex-wrap: wrap; gap: 8px;
        justify-content: center;
        margin: 14px 0 4px;
    }
    .filtre button {
        border: 1px solid var(--kant);
        background: #f6f8fa;
        color: var(--tekst);
        padding: 8px 14px;
        border-radius: 20px;
        font-size: .92em;
        cursor: pointer;
        transition: all .15s;
    }
    .filtre button:hover { border-color: var(--accent); }
    .filtre button.aktiv {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }
    #filterForklaring {
        text-align: center;
        color: var(--dæmpet);
        font-size: .88em;
        min-height: 2.4em;
        margin: 6px auto 10px;
        max-width: 560px;
    }

    /* ---- Handlingsknapper ---- */
    .handlinger { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .handlinger button {
        border: none; border-radius: 8px; padding: 10px 18px;
        font-size: .95em; cursor: pointer;
    }
    #hentKnap { background: var(--accent); color: #fff; }
    #hentKnap:hover { background: #275c94; }
    #sammenlignKnap { background: #e7ebef; color: var(--tekst); }
    #sammenlignKnap:active { background: #d3dae1; }

    /* ---- Filmstribe ---- */
    .stribeWrap {
        display: flex; align-items: center; gap: 6px;
        margin-top: 18px;
    }
    .pil {
        flex: 0 0 auto;
        border: 1px solid var(--kant); background: #fff;
        width: 34px; height: 64px; border-radius: 8px;
        font-size: 1.2em; cursor: pointer; color: var(--dæmpet);
    }
    .pil:hover { color: var(--accent); border-color: var(--accent); }
    .stribe {
        flex: 1;
        display: flex; gap: 8px;
        overflow-x: auto;
        scroll-behavior: smooth;
        padding: 6px 2px;
        /* "filmstribe"-look */
        background:
          repeating-linear-gradient(90deg, transparent 0 14px, #cfd6dd 14px 18px) top / 100% 5px no-repeat,
          repeating-linear-gradient(90deg, transparent 0 14px, #cfd6dd 14px 18px) bottom / 100% 5px no-repeat,
          #f3f5f7;
        border-radius: 6px;
        padding-top: 12px; padding-bottom: 12px;
    }
    .stribe::-webkit-scrollbar { height: 8px; }
    .stribe::-webkit-scrollbar-thumb { background: #c4ccd4; border-radius: 4px; }
    .thumb {
        flex: 0 0 auto;
        width: 92px; height: 68px;
        object-fit: cover;
        border-radius: 4px;
        cursor: pointer;
        border: 3px solid transparent;
        background: #fff;
        transition: border-color .15s, transform .15s;
    }
    .thumb:hover { transform: translateY(-2px); }
    .thumb.aktiv { border-color: var(--accent); }

    /* ---- Eget billede ---- */
    .egetBillede {
        margin-top: 16px;
        text-align: center;
        font-size: .9em;
        color: var(--dæmpet);
    }
    .egetBillede label {
        color: var(--accent);
        cursor: pointer;
        text-decoration: underline;
    }
    .egetBillede input { display: none; }

    footer { text-align: center; color: var(--dæmpet); font-size: .8em; margin-top: 22px; }
</style>
</head>
<body>
<div class="ramme">
    <header>
        <h1>&#128247; Fotolab</h1>
        <p>V&aelig;lg et billede p&aring; filmstriben og pr&oslash;v effekterne &ndash; pr&aelig;cis de samme som ved upload.</p>
    </header>

    <div class="kort">
        <div class="scene">
            <canvas id="lærred"></canvas>
            <div class="arbejder" id="arbejder">Anvender effekt&hellip;</div>
        </div>
        <p id="billedInfo"></p>

        <div class="filtre" id="filterBar"></div>
        <p id="filterForklaring"></p>

        <div class="handlinger">
            <button id="sammenlignKnap" title="Hold knappen nede for at se originalen">&#128064; Hold for original</button>
            <button id="hentKnap">&#11015;&#65039; Hent billede med effekt</button>
        </div>

        <div class="stribeWrap">
            <button class="pil" id="pilVenstre">&#8249;</button>
            <div class="stribe" id="stribe"></div>
            <button class="pil" id="pilHøjre">&#8250;</button>
        </div>

        <div class="egetBillede">
            Eller <label for="egenFil">pr&oslash;v med dit eget billede</label> &ndash; det bliver kun i din browser og uploades ikke.
            <input type="file" id="egenFil" accept="image/*">
        </div>
    </div>

    <footer>Fotolab er en p&aelig;dagogisk visning &ndash; der gemmes eller &aelig;ndres ingenting p&aring; serveren.</footer>
</div>

<script>
// Billedliste fra serveren
const BILLEDER = <?php echo json_encode($billeder, JSON_UNESCAPED_UNICODE); ?>;

/* ============================================================
   FILTRE - tro kopi af GD-filtrene i upload.php
   ============================================================ */

// GD's gråtone: 0.299R + 0.587G + 0.114B
function gdGrayscale(d) {
    for (let i = 0; i < d.length; i += 4) {
        const g = Math.round(0.299*d[i] + 0.587*d[i+1] + 0.114*d[i+2]);
        d[i] = d[i+1] = d[i+2] = g;
    }
}

// GD's colorize: lægger værdier til hver kanal
function gdColorize(d, r, g, b) {
    for (let i = 0; i < d.length; i += 4) {
        d[i]   = Math.max(0, Math.min(255, d[i]   + r));
        d[i+1] = Math.max(0, Math.min(255, d[i+1] + g));
        d[i+2] = Math.max(0, Math.min(255, d[i+2] + b));
    }
}

// GD's brightness: lægger værdi til alle kanaler
function gdBrightness(d, v) { gdColorize(d, v, v, v); }

// GD's contrast: negativ = MERE kontrast (som i PHP)
function gdContrast(d, arg) {
    let c = (100 - arg) / 100;
    c *= c;
    for (let i = 0; i < d.length; i += 4) {
        for (let k = 0; k < 3; k++) {
            let v = d[i+k] / 255;
            v = (v - 0.5) * c + 0.5;
            d[i+k] = Math.max(0, Math.min(255, Math.round(v * 255)));
        }
    }
}

// Tro kopi af boostSaturation() fra upload.php
function boostSaturation(d, factor) {
    for (let i = 0; i < d.length; i += 4) {
        const r = d[i], g = d[i+1], b = d[i+2];
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        if (max === min) continue;
        const l = (max + min) / 2;
        const diff = max - min;
        let s = l > 127.5 ? diff / (510 - max - min) : diff / (max + min);
        s = Math.min(1, s * factor);
        const newD = (l > 127.5 ? (1 - Math.abs(2*l/255 - 1)) : (2*l/255)) * s * 255;
        const avg = (max + min) / 2;
        const scale = diff > 0 ? newD / diff : 1;
        d[i]   = Math.max(0, Math.min(255, Math.round(avg + (r - avg) * scale)));
        d[i+1] = Math.max(0, Math.min(255, Math.round(avg + (g - avg) * scale)));
        d[i+2] = Math.max(0, Math.min(255, Math.round(avg + (b - avg) * scale)));
    }
}

const FILTRE = [
    { id: 'original',  navn: 'Ingen',
      info: 'Billedet som det er &ndash; ingen effekt.',
      kør: d => {} },
    { id: 'grayscale', navn: 'Sort/Hvid',
      info: 'Alle farver omregnes til gr&aring;toner ud fra hvor lyse de er. Gr&oslash;n vejer tungest, fordi &oslash;jet er mest f&oslash;lsomt for gr&oslash;nt.',
      kør: d => gdGrayscale(d) },
    { id: 'sepia',     navn: 'Sepia',
      info: 'F&oslash;rst sort/hvid, derefter t&oslash;r der en varm brunlig tone over &ndash; som gamle fotografier fra 1900-tallet.',
      kør: d => { gdGrayscale(d); gdColorize(d, 90, 60, 40); } },
    { id: 'vivid',     navn: 'St&aelig;rke Farver',
      info: 'Farvem&aelig;tningen skrues op pixel for pixel (60% mere), og kontrasten f&aring;r et lille ekstra "pop".',
      kør: d => { boostSaturation(d, 1.6); gdContrast(d, -8); } },
    { id: 'contrast',  navn: 'H&oslash;j Kontrast',
      info: 'Sort/hvid med kraftigt &oslash;get kontrast &ndash; lyse omr&aring;der bliver lysere, m&oslash;rke bliver m&oslash;rkere. Dramatisk udtryk.',
      kør: d => { gdGrayscale(d); gdContrast(d, -35); } },
    { id: 'vintage',   navn: 'Gammelt Foto',
      info: 'Lidt ekstra lys, mindre kontrast (falmet look) og et gulligt farveskift &ndash; som et &aelig;ldre farvefoto.',
      kør: d => { gdBrightness(d, 12); gdContrast(d, 18); gdColorize(d, 20, 10, -12); } },
];

/* ============================================================
   APP
   ============================================================ */
const lærred = document.getElementById('lærred');
const ctx = lærred.getContext('2d', { willReadFrequently: true });
const stribe = document.getElementById('stribe');
const filterBar = document.getElementById('filterBar');
const forklaring = document.getElementById('filterForklaring');
const billedInfo = document.getElementById('billedInfo');
const arbejder = document.getElementById('arbejder');

let aktivtBillede = null;   // Image-objekt (originalen)
let aktivIndex = -1;
let aktivtFilter = 'original';
let egetNavn = null;        // filnavn hvis eget billede

// --- Filmstribe ---
BILLEDER.forEach((b, i) => {
    const img = document.createElement('img');
    img.src = b.fil;
    img.className = 'thumb';
    img.loading = 'lazy';
    img.title = b.titel;
    img.addEventListener('click', () => vælgBillede(i));
    stribe.appendChild(img);
});

// --- Filterknapper ---
FILTRE.forEach(f => {
    const knap = document.createElement('button');
    knap.innerHTML = f.navn;
    knap.dataset.id = f.id;
    knap.addEventListener('click', () => vælgFilter(f.id));
    filterBar.appendChild(knap);
});

function vælgBillede(i) {
    aktivIndex = i;
    egetNavn = null;
    [...stribe.children].forEach((t, j) => t.classList.toggle('aktiv', j === i));
    const b = BILLEDER[i];
    const img = new Image();
    img.onload = () => {
        aktivtBillede = img;
        billedInfo.innerHTML = '<strong>' + escapeHtml(b.titel) + '</strong>' +
            (b.navn ? ' &ndash; foto: ' + escapeHtml(b.navn) : '');
        tegn();
        stribe.children[i].scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
    };
    img.src = b.fil;
}

function vælgFilter(id) {
    aktivtFilter = id;
    [...filterBar.children].forEach(k => k.classList.toggle('aktiv', k.dataset.id === id));
    const f = FILTRE.find(f => f.id === id);
    forklaring.innerHTML = f.info;
    tegn();
}

function tegn(visOriginal = false) {
    if (!aktivtBillede) return;
    // Skaler ned til maks 800 px bredde - samme grænse som upload.php
    const mw = 800;
    let w = aktivtBillede.naturalWidth, h = aktivtBillede.naturalHeight;
    if (w > mw) { h = Math.round(h * mw / w); w = mw; }
    lærred.width = w; lærred.height = h;
    ctx.drawImage(aktivtBillede, 0, 0, w, h);

    const f = FILTRE.find(f => f.id === aktivtFilter);
    if (visOriginal || f.id === 'original') return;

    arbejder.classList.add('vis');
    // Lille pause så "Anvender effekt..." kan nå at blive vist
    setTimeout(() => {
        const data = ctx.getImageData(0, 0, w, h);
        f.kør(data.data);
        ctx.putImageData(data, 0, 0);
        arbejder.classList.remove('vis');
    }, 30);
}

// --- Hold for original ---
const sammenlign = document.getElementById('sammenlignKnap');
const startOrig = e => { e.preventDefault(); tegn(true); };
const slutOrig  = e => { e.preventDefault(); tegn(false); };
sammenlign.addEventListener('mousedown', startOrig);
sammenlign.addEventListener('mouseup', slutOrig);
sammenlign.addEventListener('mouseleave', slutOrig);
sammenlign.addEventListener('touchstart', startOrig, { passive: false });
sammenlign.addEventListener('touchend', slutOrig);

// --- Download ---
document.getElementById('hentKnap').addEventListener('click', () => {
    if (!aktivtBillede) { alert('Vælg først et billede.'); return; }
    const grundnavn = egetNavn
        ? egetNavn.replace(/\.[^.]+$/, '')
        : (aktivIndex >= 0 ? BILLEDER[aktivIndex].titel : 'billede');
    const filnavn = grundnavn.replace(/[^\wæøåÆØÅ\- ]/g, '').trim() + '_' + aktivtFilter + '.jpg';
    lærred.toBlob(blob => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filnavn;
        a.click();
        URL.revokeObjectURL(a.href);
    }, 'image/jpeg', 0.9);
});

// --- Pile ---
document.getElementById('pilVenstre').addEventListener('click', () => stribe.scrollBy({ left: -300 }));
document.getElementById('pilHøjre').addEventListener('click', () => stribe.scrollBy({ left: 300 }));

// Piletaster skifter billede
document.addEventListener('keydown', e => {
    if (egetNavn || aktivIndex < 0) return;
    if (e.key === 'ArrowLeft'  && aktivIndex > 0) vælgBillede(aktivIndex - 1);
    if (e.key === 'ArrowRight' && aktivIndex < BILLEDER.length - 1) vælgBillede(aktivIndex + 1);
});

// --- Eget billede (kun i browseren) ---
document.getElementById('egenFil').addEventListener('change', e => {
    const fil = e.target.files[0];
    if (!fil) return;
    const img = new Image();
    img.onload = () => {
        aktivtBillede = img;
        aktivIndex = -1;
        egetNavn = fil.name;
        [...stribe.children].forEach(t => t.classList.remove('aktiv'));
        billedInfo.innerHTML = '<strong>' + escapeHtml(fil.name) + '</strong> &ndash; dit eget billede (kun i browseren)';
        tegn();
    };
    img.src = URL.createObjectURL(fil);
});

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// Start: vælg første billede og "Ingen"-filter
vælgFilter('original');
if (BILLEDER.length > 0) vælgBillede(0);
else billedInfo.textContent = 'Ingen billeder fundet i mappen "<?php echo $mappe; ?>".';
</script>
</body>
</html>
