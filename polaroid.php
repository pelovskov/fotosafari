<?php
/* ============================================================
   POLAROID LAB - vælg billede, filter og placering,
   download et printklart "Polaroidbillede" (10 x 15 cm, 300 dpi)
   med hvid ramme og ekstra plads nederst til håndskrift.
   ------------------------------------------------------------
   Samme princip som fotolab.php: intet gemmes eller ændres på
   serveren. Alt sker i browseren, kun download til sidst.
   ============================================================ */

$mappe = is_dir('fotolab-billeder') ? 'fotolab-billeder/' : 'uploads/';

$billeder = [];
foreach (glob($mappe . '*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE) as $fil) {
    $titel = pathinfo($fil, PATHINFO_FILENAME);
    $navn  = '';
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
<title>Polaroid Lab</title>
<style>
    :root {
        --bg: #eef1f4; --kort: #ffffff; --kant: #d8dde3;
        --tekst: #2c3540; --dæmpet: #6b7683; --accent: #2f6fb2;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: -apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; background: var(--bg); color: var(--tekst); }
    .ramme { max-width: 920px; margin: 0 auto; padding: 20px 14px 40px; }
    header { text-align: center; margin-bottom: 18px; }
    header h1 { margin: 0 0 4px; font-size: 1.7em; }
    header p { margin: 0; color: var(--dæmpet); font-size: .95em; }

    .lag { display: flex; gap: 22px; flex-wrap: wrap; align-items: flex-start; justify-content: center; }
    .kort { background: var(--kort); border: 1px solid var(--kant); border-radius: 12px; padding: 16px; box-shadow: 0 2px 10px rgba(0,0,0,.06); }

    /* --- Polaroid preview --- */
    .polaroidKort { width: 300px; }
    .polaroidPapir {
        background: #fff; padding: 16px 16px 90px; border-radius: 3px;
        box-shadow: 0 3px 12px rgba(0,0,0,.18);
        touch-action: none;
    }
    .fotoBoks {
        width: 100%; aspect-ratio: 1/1; background: #1d232a;
        overflow: hidden; position: relative; cursor: grab; border-radius: 2px;
    }
    .fotoBoks.trækker { cursor: grabbing; }
    #forhåndsvisning { width: 100%; height: 100%; display: block; }
    .tomBoks { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#7c8794; font-size:.85em; text-align:center; padding: 0 10px; }

    /* --- Kontroller --- */
    .kontrolKort { flex: 1 1 340px; max-width: 420px; }
    .kontrolKort h3 { font-size: .95em; margin: 0 0 8px; color: var(--tekst); }
    .kontrolGruppe { margin-bottom: 18px; }

    .filtre { display: flex; flex-wrap: wrap; gap: 6px; }
    .filtre button {
        border: 1px solid var(--kant); background: #f6f8fa; color: var(--tekst);
        padding: 6px 12px; border-radius: 18px; font-size: .85em; cursor: pointer;
    }
    .filtre button.aktiv { background: var(--accent); border-color: var(--accent); color: #fff; }

    .zoomRække { display: flex; align-items: center; gap: 10px; }
    .zoomRække input[type=range] { flex: 1; }

    .stribeWrap { display: flex; align-items: center; gap: 6px; margin-top: 4px; }
    .pil { flex: 0 0 auto; border: 1px solid var(--kant); background: #fff; width: 30px; height: 56px; border-radius: 8px; font-size: 1.1em; cursor: pointer; color: var(--dæmpet); }
    .pil:hover { color: var(--accent); border-color: var(--accent); }
    .stribe { flex: 1; display: flex; gap: 6px; overflow-x: auto; padding: 4px 2px; }
    .thumb { flex: 0 0 auto; width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 3px solid transparent; }
    .thumb.aktiv { border-color: var(--accent); }

    .egetBillede { font-size: .88em; color: var(--dæmpet); margin-top: 6px; }
    .egetBillede label { color: var(--accent); cursor: pointer; text-decoration: underline; }
    .egetBillede input { display: none; }

    #hentKnap {
        width: 100%; border: none; border-radius: 8px; padding: 12px;
        font-size: 1em; cursor: pointer; background: var(--accent); color: #fff; margin-top: 4px;
    }
    #hentKnap:hover { background: #275c94; }
    #hentKnap:disabled { background: #b7c2cd; cursor: not-allowed; }

    .hjælpetekst { font-size: .82em; color: var(--dæmpet); margin-top: 4px; }
    footer { text-align: center; color: var(--dæmpet); font-size: .8em; margin-top: 22px; }
</style>
</head>
<body>
<div class="ramme">
    <header>
        <h1>&#127968; Polaroid Lab</h1>
        <p>V&aelig;lg billede, filter og placering &ndash; hent et printklart Polaroidbillede til Canon-printeren (10 &times; 15 cm).</p>
    </header>

    <div class="lag">
        <div class="kort polaroidKort">
            <div class="polaroidPapir">
                <div class="fotoBoks" id="fotoBoks">
                    <canvas id="forhåndsvisning" width="600" height="600"></canvas>
                    <div class="tomBoks" id="tomBoks">V&aelig;lg et billede herunder &rarr;</div>
                </div>
            </div>
            <p class="hjælpetekst" style="text-align:center;">Tr&aelig;k i billedet for at flytte det rundt.</p>
        </div>

        <div class="kort kontrolKort">
            <div class="kontrolGruppe">
                <h3>1. V&aelig;lg billede</h3>
                <div class="stribeWrap">
                    <button class="pil" id="pilVenstre">&#8249;</button>
                    <div class="stribe" id="stribe"></div>
                    <button class="pil" id="pilHøjre">&#8250;</button>
                </div>
                <div class="egetBillede">
                    Eller <label for="egenFil">upload dit eget billede</label> &ndash; det uploades ikke, det bliver kun i din browser.
                    <input type="file" id="egenFil" accept="image/*">
                </div>
            </div>

            <div class="kontrolGruppe">
                <h3>2. V&aelig;lg filter</h3>
                <div class="filtre" id="filterBar"></div>
            </div>

            <div class="kontrolGruppe">
                <h3>3. Zoom og placering</h3>
                <div class="zoomRække">
                    <span style="font-size:.85em; color:var(--dæmpet);">&#128269;</span>
                    <input type="range" id="zoomSlider" min="100" max="250" value="100">
                </div>
                <p class="hjælpetekst">Zoom, og tr&aelig;k i billedet ovenfor for at placere det.</p>
            </div>

            <button id="hentKnap" disabled>&#11015;&#65039; Hent Polaroidbillede (10&times;15 cm)</button>
            <p class="hjælpetekst">Billedet f&aring;r hvid ramme og ekstra plads nederst til at skrive p&aring; i h&aring;nden.</p>
        </div>
    </div>

    <footer>Polaroid Lab gemmer eller &aelig;ndrer ingenting p&aring; serveren &ndash; alt sker i din browser.</footer>
</div>

<script>
const BILLEDER = <?php echo json_encode($billeder, JSON_UNESCAPED_UNICODE); ?>;

/* ---- Filtre: samme funktioner som i fotolab.php / upload.php ---- */
function gdGrayscale(d) {
    for (let i = 0; i < d.length; i += 4) {
        const g = Math.round(0.299*d[i] + 0.587*d[i+1] + 0.114*d[i+2]);
        d[i] = d[i+1] = d[i+2] = g;
    }
}
function gdColorize(d, r, g, b) {
    for (let i = 0; i < d.length; i += 4) {
        d[i]   = Math.max(0, Math.min(255, d[i]   + r));
        d[i+1] = Math.max(0, Math.min(255, d[i+1] + g));
        d[i+2] = Math.max(0, Math.min(255, d[i+2] + b));
    }
}
function gdBrightness(d, v) { gdColorize(d, v, v, v); }
function gdContrast(d, arg) {
    let c = (100 - arg) / 100; c *= c;
    for (let i = 0; i < d.length; i += 4) {
        for (let k = 0; k < 3; k++) {
            let v = d[i+k] / 255;
            v = (v - 0.5) * c + 0.5;
            d[i+k] = Math.max(0, Math.min(255, Math.round(v * 255)));
        }
    }
}
function boostSaturation(d, factor) {
    for (let i = 0; i < d.length; i += 4) {
        const r = d[i], g = d[i+1], b = d[i+2];
        const max = Math.max(r,g,b), min = Math.min(r,g,b);
        if (max === min) continue;
        const l = (max+min)/2, diff = max-min;
        let s = l > 127.5 ? diff/(510-max-min) : diff/(max+min);
        s = Math.min(1, s*factor);
        const newD = (l > 127.5 ? (1-Math.abs(2*l/255-1)) : (2*l/255)) * s * 255;
        const avg = (max+min)/2, scale = diff>0 ? newD/diff : 1;
        d[i]   = Math.max(0, Math.min(255, Math.round(avg + (r-avg)*scale)));
        d[i+1] = Math.max(0, Math.min(255, Math.round(avg + (g-avg)*scale)));
        d[i+2] = Math.max(0, Math.min(255, Math.round(avg + (b-avg)*scale)));
    }
}
const FILTRE = [
    { id: 'original',  navn: 'Ingen',            kør: d => {} },
    { id: 'grayscale', navn: 'Sort/Hvid',         kør: d => gdGrayscale(d) },
    { id: 'sepia',     navn: 'Sepia',              kør: d => { gdGrayscale(d); gdColorize(d, 90, 60, 40); } },
    { id: 'vivid',     navn: 'St&aelig;rke Farver', kør: d => { boostSaturation(d, 1.6); gdContrast(d, -8); } },
    { id: 'contrast',  navn: 'H&oslash;j Kontrast', kør: d => { gdGrayscale(d); gdContrast(d, -35); } },
    { id: 'vintage',   navn: 'Gammelt Foto',       kør: d => { gdBrightness(d, 12); gdContrast(d, 18); gdColorize(d, 20, 10, -12); } },
];

/* ---- Tilstand ---- */
let aktivtBillede = null;
let aktivIndex = -1;
let egetNavn = null;
let aktivtFilter = 'original';
let zoom = 1;          // 1.0 - 2.5
let panX = 0, panY = 0; // pixels i forhåndsvisnings-koordinater

const canvas = document.getElementById('forhåndsvisning');
const ctx = canvas.getContext('2d', { willReadFrequently: true });
const fotoBoks = document.getElementById('fotoBoks');
const tomBoks = document.getElementById('tomBoks');
const stribe = document.getElementById('stribe');
const filterBar = document.getElementById('filterBar');
const hentKnap = document.getElementById('hentKnap');

BILLEDER.forEach((b, i) => {
    const img = document.createElement('img');
    img.src = b.fil; img.className = 'thumb'; img.loading = 'lazy'; img.title = b.titel;
    img.addEventListener('click', () => vælgBillede(i));
    stribe.appendChild(img);
});
FILTRE.forEach(f => {
    const knap = document.createElement('button');
    knap.innerHTML = f.navn; knap.dataset.id = f.id;
    knap.addEventListener('click', () => { aktivtFilter = f.id; opdaterFilterKnapper(); tegn(); });
    filterBar.appendChild(knap);
});
function opdaterFilterKnapper() {
    [...filterBar.children].forEach(k => k.classList.toggle('aktiv', k.dataset.id === aktivtFilter));
}
opdaterFilterKnapper();

function vælgBillede(i) {
    aktivIndex = i; egetNavn = null;
    [...stribe.children].forEach((t, j) => t.classList.toggle('aktiv', j === i));
    const img = new Image();
    img.onload = () => { aktivtBillede = img; nulstilPlacering(); tegn(); };
    img.src = BILLEDER[i].fil;
}
document.getElementById('egenFil').addEventListener('change', e => {
    const fil = e.target.files[0]; if (!fil) return;
    const img = new Image();
    img.onload = () => {
        aktivtBillede = img; aktivIndex = -1; egetNavn = fil.name;
        [...stribe.children].forEach(t => t.classList.remove('aktiv'));
        nulstilPlacering(); tegn();
    };
    img.src = URL.createObjectURL(fil);
});

function nulstilPlacering() { zoom = 1; panX = 0; panY = 0; document.getElementById('zoomSlider').value = 100; }

/* ---- Layout: cover-fit + clamp (matcher testet matematik) ---- */
function beregnLayout(boxSize) {
    const iw = aktivtBillede.naturalWidth, ih = aktivtBillede.naturalHeight;
    const base = Math.max(boxSize/iw, boxSize/ih);
    const scale = base * zoom;
    const dw = iw*scale, dh = ih*scale;
    let x = (boxSize-dw)/2 + panX;
    let y = (boxSize-dh)/2 + panY;
    x = Math.min(0, Math.max(boxSize-dw, x));
    y = Math.min(0, Math.max(boxSize-dh, y));
    return { dw, dh, x, y };
}

function tegn() {
    if (!aktivtBillede) { tomBoks.style.display = 'flex'; hentKnap.disabled = true; return; }
    tomBoks.style.display = 'none'; hentKnap.disabled = false;
    const box = canvas.width; // 600x600 - fast intern opløsning for skarp forhåndsvisning
    const L = beregnLayout(box);
    ctx.clearRect(0,0,box,box);
    ctx.drawImage(aktivtBillede, L.x, L.y, L.dw, L.dh);
    const f = FILTRE.find(f => f.id === aktivtFilter);
    if (f.id !== 'original') {
        const data = ctx.getImageData(0,0,box,box);
        f.kør(data.data);
        ctx.putImageData(data,0,0);
    }
}

/* ---- Zoom-slider ---- */
document.getElementById('zoomSlider').addEventListener('input', e => {
    zoom = e.target.value / 100;
    // clamp pan igen ved beregnLayout, så billedet ikke "hopper" ud over kanten
    tegn();
});

/* ---- Træk for at placere billedet (mus + touch) ---- */
let trækker = false, startX = 0, startY = 0, startPanX = 0, startPanY = 0;
function trækStart(x, y) {
    if (!aktivtBillede) return;
    trækker = true; startX = x; startY = y; startPanX = panX; startPanY = panY;
    fotoBoks.classList.add('trækker');
}
function trækFlyt(x, y) {
    if (!trækker) return;
    const boksRect = fotoBoks.getBoundingClientRect();
    const skala = canvas.width / boksRect.width; // skærm -> intern 600px opløsning
    panX = startPanX + (x - startX) * skala;
    panY = startPanY + (y - startY) * skala;
    tegn();
}
function trækSlut() { trækker = false; fotoBoks.classList.remove('trækker'); }
fotoBoks.addEventListener('mousedown', e => trækStart(e.clientX, e.clientY));
window.addEventListener('mousemove', e => trækFlyt(e.clientX, e.clientY));
window.addEventListener('mouseup', trækSlut);
fotoBoks.addEventListener('touchstart', e => { const t = e.touches[0]; trækStart(t.clientX, t.clientY); }, {passive:true});
fotoBoks.addEventListener('touchmove', e => { const t = e.touches[0]; trækFlyt(t.clientX, t.clientY); e.preventDefault(); }, {passive:false});
fotoBoks.addEventListener('touchend', trækSlut);

/* ---- Pile i filmstriben ---- */
document.getElementById('pilVenstre').addEventListener('click', () => stribe.scrollBy({left:-200}));
document.getElementById('pilHøjre').addEventListener('click', () => stribe.scrollBy({left:200}));

/* ---- Download: print-klart 10x15 cm ved 300 dpi ---- */
hentKnap.addEventListener('click', () => {
    if (!aktivtBillede) return;

    // 10 x 15 cm ved 300 dpi = 1181 x 1772 px
    const printW = 1181, printH = 1772;
    const margin = Math.round(printW * 0.06);          // hvid kant om siderne/toppen
    const fotoStørrelse = printW - margin * 2;          // det firkantede foto
    const fotoTop = margin;
    // resten af pladsen nedenunder er den hvide skrive-plads
    const finalCanvas = document.createElement('canvas');
    finalCanvas.width = printW; finalCanvas.height = printH;
    const fctx = finalCanvas.getContext('2d');
    fctx.fillStyle = '#ffffff';
    fctx.fillRect(0, 0, printW, printH);

    // Tegn fotoet i fuld opløsning direkte (ikke fra den lille 600px forhåndsvisning)
    const fotoCanvas = document.createElement('canvas');
    fotoCanvas.width = fotoStørrelse; fotoCanvas.height = fotoStørrelse;
    const pctx = fotoCanvas.getContext('2d');
    const L = beregnLayout(fotoStørrelse);
    pctx.drawImage(aktivtBillede, L.x, L.y, L.dw, L.dh);
    const f = FILTRE.find(f => f.id === aktivtFilter);
    if (f.id !== 'original') {
        const data = pctx.getImageData(0,0,fotoStørrelse,fotoStørrelse);
        f.kør(data.data);
        pctx.putImageData(data,0,0);
    }
    fctx.drawImage(fotoCanvas, margin, fotoTop);

    const grundnavn = egetNavn ? egetNavn.replace(/\.[^.]+$/, '') : (aktivIndex >= 0 ? BILLEDER[aktivIndex].titel : 'billede');
    const filnavn = grundnavn.replace(/[^\wæøåÆØÅ\- ]/g, '').trim() + '_polaroid.jpg';
    finalCanvas.toBlob(blob => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filnavn;
        a.click();
        URL.revokeObjectURL(a.href);
    }, 'image/jpeg', 0.95);
});

if (BILLEDER.length > 0) vælgBillede(0);
</script>
</body>
</html>
