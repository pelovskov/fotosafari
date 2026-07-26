<?php
date_default_timezone_set('Europe/Copenhagen');
$configFile = 'config.json';
$defaultConfig = [
    'projektNavn' => 'Mit Projekt', 'underOverskrift' => '', 'footerTekst' => '',
];
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : $defaultConfig;
$projektNavn = $config['projektNavn'] ?? 'Fotosafari';
$underOverskrift = $config['underOverskrift'] ?? '';

// Samme datastruktur som galleri.php, så begge sider altid viser den samme samling
$all_images_data = [];
$image_files = glob("uploads/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
if ($image_files) {
    array_multisort(array_map('filemtime', $image_files), SORT_DESC, $image_files);
    foreach ($image_files as $image) {
        $text_file = 'uploads/' . pathinfo($image, PATHINFO_FILENAME) . '.txt';
        $name = "Ukendt"; $description = "Ingen beskrivelse.";
        if (file_exists($text_file)) {
            $lines = file($text_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $name = htmlspecialchars($lines[0] ?? 'Ukendt');
            $description = htmlspecialchars($lines[1] ?? 'Ingen beskrivelse.');
        }
        $all_images_data[] = ['src' => $image, 'name' => $name, 'desc' => $description];
    }
}
?>
<!DOCTYPE html>
<html lang="da">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bladre i billeder - <?php echo htmlspecialchars($projektNavn); ?></title>
<style>
  :root{
    --bg:#141310;
    --card:#1d1c18;
    --line:#3a372f;
    --paper:#f4ecd8;
    --accent:#c98a3e;
    --text:#eee6d6;
    --muted:#9c9484;
  }
  *{box-sizing:border-box;}
  body{
    margin:0; background:var(--bg); color:var(--text);
    font-family:'Georgia', 'Iowan Old Style', serif;
    padding:40px 24px 80px;
  }
  header{ max-width:1100px; margin:0 auto 24px; }
  header .backlink{
    font-family:-apple-system,'Segoe UI',sans-serif; font-size:0.85rem;
    color:var(--muted); text-decoration:none; display:inline-block; margin-bottom:12px;
  }
  header .backlink:hover{ color:var(--accent); }
  header h1{
    font-family: -apple-system, 'Segoe UI', sans-serif;
    font-weight:700; font-size:1.6rem; letter-spacing:0.02em; margin:0 0 6px;
  }
  header p{ color:var(--muted); font-size:0.95rem; margin:0; font-family:-apple-system,'Segoe UI',sans-serif;}

  .toolbar{
    max-width:1100px; margin:0 auto 24px; display:flex; gap:10px; align-items:center;
    font-family:-apple-system,'Segoe UI',sans-serif;
  }
  .toolbar button{
    background:var(--card); color:var(--text); border:1px solid var(--line);
    padding:8px 14px; border-radius:20px; font-size:0.85rem; cursor:pointer;
  }
  .toolbar button:hover{ border-color:var(--accent); color:var(--accent); }

  .empty{
    max-width:1100px; margin:60px auto; text-align:center; color:var(--muted);
    font-family:-apple-system,'Segoe UI',sans-serif;
  }

  .masonry{
    max-width:1100px; margin:0 auto;
    column-count:3; column-gap:18px;
  }
  @media (max-width:700px){ .masonry{ column-count:1; } }
  @media (min-width:701px) and (max-width:1000px){ .masonry{ column-count:2; } }

  .tile{
    break-inside:avoid; margin:0 0 18px; background:var(--card);
    border-radius:3px; overflow:hidden; cursor:pointer;
    box-shadow:0 6px 16px rgba(0,0,0,0.35);
    transition:transform .25s ease;
  }
  .tile:hover{ transform:translateY(-3px); }
  .tile img{ display:block; width:100%; height:auto; }

  .tag{
    background:var(--paper); color:#2a2620;
    font-family:-apple-system,'Segoe UI',sans-serif;
    padding:8px 12px 9px; position:relative;
  }
  .tag .name{ font-weight:700; font-size:0.85rem; }
  .tag .desc{ font-size:0.78rem; color:#5a5343; margin-top:2px; }
  .tag::before{
    content:''; position:absolute; top:-9px; left:14px; width:14px; height:14px;
    background:var(--accent); border-radius:50%;
    box-shadow:0 2px 3px rgba(0,0,0,0.4);
  }

  .lightbox{
    position:fixed; inset:0; background:rgba(10,9,7,0.92);
    display:none; align-items:center; justify-content:center; z-index:50;
    padding:30px; font-family:-apple-system,'Segoe UI',sans-serif;
  }
  .lightbox.open{ display:flex; }
  .lightbox-inner{ max-width:600px; width:100%; }
  .lightbox img{ width:100%; border-radius:4px; display:block; max-height:75vh; object-fit:contain; background:#000; }
  .lightbox .meta{ margin-top:14px; }
  .lightbox .meta .name{ font-weight:700; font-size:1.1rem; }
  .lightbox .meta .desc{ color:var(--muted); margin-top:4px; }
  .lightbox .close{
    position:absolute; top:24px; right:30px; color:var(--text);
    font-size:1.8rem; cursor:pointer; background:none; border:none;
  }
  .lightbox .nav{
    position:absolute; top:50%; transform:translateY(-50%);
    background:rgba(255,255,255,0.06); border:1px solid var(--line);
    color:var(--text); font-size:1.4rem; width:48px; height:48px;
    border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;
    transition:background .2s;
  }
  .lightbox .nav:hover{ background:rgba(255,255,255,0.15); border-color:var(--accent); color:var(--accent); }
  .lightbox .prev{ left:24px; }
  .lightbox .next{ right:24px; }
  @media (max-width:700px){
    .lightbox .nav{ width:38px; height:38px; font-size:1.1rem; }
    .lightbox .prev{ left:8px; } .lightbox .next{ right:8px; }
  }
  footer{
    max-width:1100px; margin:32px auto 0; color:var(--muted);
    font-family:-apple-system,'Segoe UI',sans-serif; font-size:0.82rem;
  }
</style>
</head>
<body>

<header>
  <a class="backlink" href="galleri.php">&larr; Klassisk galleri (tiles + kort)</a>
  <h1><?php echo htmlspecialchars($projektNavn); ?> - Bladre i billeder</h1>
  <p><?php echo htmlspecialchars($underOverskrift); ?></p>
</header>

<div class="toolbar">
  <button onclick="shuffleTiles()">🔀 Bland billeder</button>
</div>

<?php if (empty($all_images_data)): ?>
  <div class="empty">Der er ingen billeder endnu.</div>
<?php else: ?>
<div class="masonry" id="masonry"></div>
<?php endif; ?>

<div class="lightbox" id="lightbox">
  <button class="close" onclick="closeLightbox()">&times;</button>
  <button class="nav prev" onclick="navLightbox(-1)" aria-label="Forrige billede">&#10094;</button>
  <button class="nav next" onclick="navLightbox(1)" aria-label="Næste billede">&#10095;</button>
  <div class="lightbox-inner">
    <img id="lb-img" src="">
    <div class="meta">
      <div class="name" id="lb-name"></div>
      <div class="desc" id="lb-desc"></div>
    </div>
  </div>
</div>

<footer><?php echo htmlspecialchars($config['footerTekst'] ?? ''); ?></footer>

<script>
const ITEMS = <?php echo json_encode(array_map(fn($i) => ['src' => $i['src'], 'name' => $i['name'], 'desc' => $i['desc']], $all_images_data), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

let CURRENT_LIST = ITEMS;
let CURRENT_INDEX = 0;

function render(items){
  CURRENT_LIST = items;
  const m = document.getElementById('masonry');
  if (!m) return;
  m.innerHTML = '';
  items.forEach((it, idx) => {
    const tile = document.createElement('div');
    tile.className = 'tile';
    tile.innerHTML = `
      <img src="${it.src}" loading="lazy" alt="${it.name}">
      <div class="tag">
        <div class="name">${it.name}</div>
        <div class="desc">${it.desc}</div>
      </div>`;
    tile.onclick = () => openLightbox(idx);
    m.appendChild(tile);
  });
}

function shuffleTiles(){
  const arr = [...ITEMS];
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  render(arr);
}

function showAt(idx){
  const n = CURRENT_LIST.length;
  if (n === 0) return;
  CURRENT_INDEX = (idx + n) % n;
  const it = CURRENT_LIST[CURRENT_INDEX];
  document.getElementById('lb-img').src = it.src;
  document.getElementById('lb-name').textContent = it.name;
  document.getElementById('lb-desc').textContent = it.desc;
}

function openLightbox(idx){
  showAt(idx);
  document.getElementById('lightbox').classList.add('open');
}
function navLightbox(step){
  showAt(CURRENT_INDEX + step);
}
function closeLightbox(){
  document.getElementById('lightbox').classList.remove('open');
}

document.addEventListener('keydown', (e) => {
  if (!document.getElementById('lightbox').classList.contains('open')) return;
  if (e.key === 'ArrowRight') navLightbox(1);
  if (e.key === 'ArrowLeft') navLightbox(-1);
  if (e.key === 'Escape') closeLightbox();
});

render(ITEMS);
</script>

</body>
</html>
