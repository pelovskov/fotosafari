<?php
date_default_timezone_set('Europe/Copenhagen');
$configFile = 'config.json';
$defaultConfig = [
    'projektNavn' => 'Mit Projekt', 'underOverskrift' => '', 'footerTekst' => '',
    'ratings_enabled' => false, 'map_enabled' => true, 
    'cta_buttons' => [['active' => false, 'text' => '', 'link' => ''],['active' => false, 'text' => '', 'link' => ''],['active' => false, 'text' => '', 'link' => '']]
];
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : $defaultConfig;
$projektNavn = $config['projektNavn']; $underOverskrift = $config['underOverskrift']; $footerTekst = $config['footerTekst'];
$ratings_enabled = $config['ratings_enabled'] ?? false;
$map_enabled = $config['map_enabled'] ?? true; 
$ratingsFile = 'ratings.json';
$all_ratings = file_exists($ratingsFile) ? json_decode(file_get_contents($ratingsFile), true) : [];

$all_images_data = [];
$image_files = glob("uploads/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
if ($image_files) {
    array_multisort(array_map('filemtime', $image_files), SORT_DESC, $image_files);
    foreach ($image_files as $image) {
        $text_file = 'uploads/' . pathinfo($image, PATHINFO_FILENAME) . '.txt';
        $name = "Ukendt"; $description = "Ingen beskrivelse."; $lat = null; $lng = null; $group = 'A';

        if (file_exists($text_file)) {
            $lines = file($text_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $name = htmlspecialchars($lines[0] ?? 'Ukendt');
            $description = htmlspecialchars($lines[1] ?? 'Ingen beskrivelse.');
            if (isset($lines[2]) && is_numeric($lines[2]) && isset($lines[3]) && is_numeric($lines[3])) {
                $lat = floatval($lines[2]); $lng = floatval($lines[3]);
            }
            if (isset($lines[4])) { $group = trim($lines[4]); }
        }
        if (!in_array($group, ['A', 'B', 'C'])) { $group = 'A'; }

        $image_key = basename($image);
        $rating_info = $all_ratings[$image_key] ?? ['ratings' => []];
        $vote_count = count($rating_info['ratings']);
        $avg_rating = $vote_count > 0 ? array_sum($rating_info['ratings']) / $vote_count : 0;

        $all_images_data[] = [
            'src' => $image, 'name' => $name, 'description' => $description,
            'avg_rating' => round($avg_rating, 1), 'vote_count' => $vote_count,
            'latitude' => $lat, 'longitude' => $lng, 'group' => $group
        ];
    }
}
$has_gps_images = !empty(array_filter($all_images_data, fn($img) => $img['latitude'] !== null));
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galleri - <?php echo htmlspecialchars($projektNavn); ?></title>
    
    <?php if ($map_enabled && $has_gps_images): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <?php endif; ?>

    <style>
        :root { --bg-color: #f4f4f4; --container-bg: #ffffff; --text-color: #333; --border-color: #eee; --header-shadow: rgba(0,0,0,0.1); --modal-bg: rgba(20, 20, 20, 0.95); }
        [data-theme="dark"] { --bg-color: #121212; --container-bg: #1e1e1e; --text-color: #e0e0e0; --border-color: #333; --header-shadow: rgba(255,255,255,0.1); --modal-bg: rgba(0, 0, 0, 0.95); }
        
        body { font-family: sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-color); padding-top: 80px; transition: background-color 0.3s, color 0.3s; }
        .container { max-width: 1200px; margin: 20px auto; background-color: var(--container-bg); padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); transition: background-color 0.3s; }
        h1, h2 { text-align: center; color: var(--text-color); }
        
        /* Header */
        .sticky-header { position: fixed; top: 0; left: 0; width: 100%; background-color: var(--container-bg); box-shadow: 0 2px 5px var(--header-shadow); z-index: 1001; display: flex; align-items: center; justify-content: space-between; padding: 0 15px; height: 80px; box-sizing: border-box; transition: background-color 0.3s; }
        .header-title { text-align: center; }
        .header-title h1 { margin: 0; font-size: 1.5em; }
        .header-title p { margin: 4px 0 0 0; color: #666; font-size: 0.9em; }
        [data-theme="dark"] .header-title p { color: #aaa; }
        .header-actions { display: flex; align-items: center; gap: 5px; }
        .header-icon { cursor: pointer; text-decoration: none; color: var(--text-color); padding: 8px; border: none; background: none; display: flex; align-items: center; justify-content: center; }
        .header-icon svg { width: 22px; height: 22px; stroke: currentColor; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
        
        /* Galleri Grid */
        .gallery-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
        .gallery-item { text-align: center; }
        .gallery-item-name { font-size: 0.9em; margin-top: 5px; font-weight: bold; color: var(--text-color); }
        .gallery-item img { width: 200px; height: 200px; object-fit: cover; border-radius: 5px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .gallery-item img:hover { transform: scale(1.05); }
        
        /* Modal Styling */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: hidden; background-color: var(--modal-bg); justify-content: center; align-items: center; }
        .modal-content-wrapper { position: relative; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; }
        .modal-content { display: block; width: auto; height: auto; max-width: 95vw; max-height: 80vh; object-fit: contain; box-shadow: 0 5px 20px rgba(0,0,0,0.5); pointer-events: auto; background-color: #000; }
        #caption { margin-top: 15px; padding: 15px; text-align: center; color: var(--text-color); background-color: var(--container-bg); border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); max-width: 90%; pointer-events: auto; }
        #caption a, #caption button { text-decoration: none; color: #ffffff; background-color: #007bff; padding: 8px 15px; border-radius: 4px; margin: 5px; border: none; cursor: pointer; font-size: 14px; display: inline-block; transition: background-color 0.2s; }
        #caption a:hover, #caption button:hover { background-color: #0056b3; }
        .close { position: absolute; top: 20px; right: 20px; color: #fff; background-color: rgba(0,0,0,0.5); border-radius: 50%; width: 40px; height: 40px; font-size: 30px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; z-index: 2001; pointer-events: auto; }
        .close:hover { background-color: rgba(0,0,0,0.8); }

        /* Footer & Diverse */
        .site-footer { text-align: center; padding: 20px; margin-top: 30px; background-color: #343a40; color: #ddd; font-size: 0.9em; }
        .no-images { text-align: center; padding: 40px; font-style: italic; color: #777; }
        .cta-container { text-align: center; padding: 10px 0 30px 0; border-bottom: 1px solid var(--border-color); margin-bottom: 30px; }
        .cta-button-gallery { display: inline-block; padding: 10px 20px; font-size: 1em; color: #ffffff; background-color: #007bff; text-decoration: none; border-radius: 5px; margin: 5px; transition: background-color 0.2s; }
        .cta-button-gallery:hover { background-color: #0056b3; }
        
        /* Rating */
        .rating-display { font-size: 0.8em; color: #888; margin-top: 4px; }
        .stars-interactive { font-size: 2em; cursor: pointer; color: #888; user-select: none; }
        .stars-interactive .star:hover, .stars-interactive .star.hovered { color: orange; }
        .stars-interactive .star.selected { color: gold; }
        #caption .rating-summary { margin-top: 15px; font-size: 0.9em; color: var(--text-color); }

        /* Visningsknapper */
        .view-toggle-buttons { text-align: center; margin-bottom: 20px; }
        .view-toggle-buttons button { background-color: #f0f0f0; color: #333; border: 1px solid #ccc; padding: 10px 20px; cursor: pointer; font-size: 1em; margin: 0 5px; border-radius: 5px; transition: all 0.2s; }
        [data-theme="dark"] .view-toggle-buttons button { background-color: #333; color: #e0e0e0; border-color: #555; }
        .view-toggle-buttons button.active { background-color: #007bff; color: white; border-color: #007bff; }
        [data-theme="dark"] .view-toggle-buttons button.active { background-color: #0056b3; border-color: #0056b3; }
        .view-toggle-buttons button:disabled { background-color: #e9ecef; color: #6c757d; cursor: not-allowed; opacity: 0.65; }
        
        /* Kort Styling */
        #map { height: 70vh; width: 100%; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 20px; }
        .map-popup-image { max-width: 150px; height: auto; display: block; margin: 0 auto 5px auto; border-radius: 3px; }
        .map-popup-name { font-weight: bold; margin-bottom: 3px; }
        .map-popup-description { font-size: 0.9em; color: #555; }
        [data-theme="dark"] .map-popup-description { color: #aaa; }
        .leaflet-popup-content-wrapper, .leaflet-popup-tip { background: var(--container-bg); color: var(--text-color); }
        .locate-btn { background-color: #fff; border: 2px solid rgba(0,0,0,0.2); border-radius: 4px; cursor: pointer; padding: 5px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 5px rgba(0,0,0,0.65); }
        .locate-btn:hover { background-color: #f4f4f4; }
        .locate-btn svg { width: 22px; height: 22px; fill: #333; }
    </style>
</head>
<body>
    <header class="sticky-header">
        <div class="header-actions left"><a href="admin.php" title="Administration" class="header-icon" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24"><path d="M19.14,12.94a2,2,0,0,1,0-1.88l1.4-1.12a1,1,0,0,0,.36-1.25l-1.73-3a1,1,0,0,0-1.25-.36l-1.8.72a6.31,6.31,0,0,0-2.1-1.23L13.43,3.1a1,1,0,0,0-1-.8H10.57a1,1,0,0,0-1,.8L8.71,5.82A6.31,6.31,0,0,0,6.61,7.05l-1.8-.72a1,1,0,0,0-1.25.36l-1.73,3a1,1,0,0,0,.36,1.25l1.4,1.12a2,2,0,0,1,0,1.88l-1.4,1.12a1,1,0,0,0-.36,1.25l1.73,3a1,1,0,0,0,1.25.36l1.8-.72a6.31,6.31,0,0,0,2.1,1.23l.86,2.72a1,1,0,0,0,1,.8h2.86a1,1,0,0,0,1-.8l.86-2.72a6.31,6.31,0,0,0,2.1-1.23l1.8.72a1,1,0,0,0,1.25-.36l1.73-3a1,1,0,0,0-.36-1.25ZM12,14.5a2.5,2.5,0,1,1,2.5-2.5A2.5,2.5,0,0,1,12,14.5Z"/></svg></a></div>
        <div class="header-title"><h1><?php echo htmlspecialchars($projektNavn); ?></h1><p><?php echo htmlspecialchars($underOverskrift); ?></p></div>
        <div class="header-actions right">
            <button id="theme-toggle" class="header-icon" title="Skift tema">
                <svg id="theme-icon-moon" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                <svg id="theme-icon-sun" viewBox="0 0 24 24" style="display:none;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
            </button>
        </div>
    </header>
    <div class="container">
        <?php 
        $active_buttons_exist = false;
        if (isset($config['cta_buttons'])) {
            foreach ($config['cta_buttons'] as $button) {
                if ($button['active'] && !empty($button['text']) && !empty($button['link'])) { $active_buttons_exist = true; break; }
            }
        }
        if ($active_buttons_exist): ?>
        <div class="cta-container">
            <?php foreach ($config['cta_buttons'] as $button): if ($button['active'] && !empty($button['text']) && !empty($button['link'])): ?>
                <a href="<?php echo htmlspecialchars($button['link']); ?>" class="cta-button-gallery" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($button['text']); ?></a>
            <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($map_enabled): ?>
        <div class="view-toggle-buttons">
            <button id="show-gallery-btn" class="active">Galleri</button>
            <button id="show-map-btn" <?php if (!$has_gps_images) echo 'disabled title="Ingen billeder har GPS-data tilgængelig"'; ?>>Kortvisning</button>
        </div>
        <?php endif; ?>

        <p style="margin: 4px 0 0;"><a href="galleri_masonry.php" style="font-size:0.85em;">🖼️ Prøv den nye "Bladre i billeder"-visning &rarr;</a></p>

        <h2 id="current-view-title">Galleri</h2>

        <div id="gallery-view" class="gallery-container">
            <?php
            if (!empty($all_images_data)) {
                foreach ($all_images_data as $image_data) {
                    echo '<div class="gallery-item">';
                    echo '<img src="' . htmlspecialchars($image_data['src']) . '" alt="' . htmlspecialchars($image_data['name']) . '" onclick="openModal(\'' . htmlspecialchars($image_data['src']) . '\', \'' . addslashes(htmlspecialchars($image_data['name'])) . '\', \'' . addslashes(htmlspecialchars($image_data['description'])) . '\')">';
                    echo '<div class="gallery-item-name">' . htmlspecialchars($image_data['name']) . ' <span style="font-size:0.8em;color:#777;">(' . $image_data['group'] . ')</span></div>';
                    if ($ratings_enabled) { echo '<div class="rating-display">Rating: ' . $image_data['avg_rating'] . '/5 (' . $image_data['vote_count'] . ' stemmer)</div>'; }
                    echo '</div>';
                }
            } else { echo '<p class="no-images">Der er endnu ingen billeder i galleriet.</p>'; }
            ?>
        </div>

        <?php if ($map_enabled): ?>
        <div id="map-view" style="display:none;">
            <?php if ($has_gps_images): ?>
                <div id="map"></div>
            <?php else: ?>
                <p class="no-images">Ingen billeder med GPS-koordinater at vise på kortet.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div id="myModal" class="modal">
        <div class="modal-content-wrapper">
            <span class="close">&times;</span>
            <img class="modal-content" id="modalImg">
            <div id="caption">
                <h3 id="modalName"></h3><p id="modalDescription"></p>
                <?php if ($ratings_enabled): ?>
                <div id="rating-section">
                    <div id="rating-interactive" class="stars-interactive" data-image-file="">
                        <span class="star" data-value="1">☆</span><span class="star" data-value="2">☆</span><span class="star" data-value="3">☆</span><span class="star" data-value="4">☆</span><span class="star" data-value="5">☆</span>
                    </div>
                    <div id="rating-summary" class="rating-summary"></div>
                </div>
                <?php endif; ?>
                <a id="downloadLink" href="#" download>Download</a>
                <button id="shareBtn">Del</button>
            </div>
        </div>
    </div>
    
    <footer class="site-footer"><p><?php echo htmlspecialchars($footerTekst); ?></p></footer>

    <script>
        const themeToggle = document.getElementById('theme-toggle');
        const htmlEl = document.documentElement;
        const sunIcon = document.getElementById('theme-icon-sun');
        const moonIcon = document.getElementById('theme-icon-moon');
        function setTheme(theme) {
            if (theme === 'dark') {
                htmlEl.setAttribute('data-theme', 'dark'); sunIcon.style.display = 'block'; moonIcon.style.display = 'none';
            } else {
                htmlEl.setAttribute('data-theme', 'light'); sunIcon.style.display = 'none'; moonIcon.style.display = 'block';
            }
            localStorage.setItem('theme', theme);
        }
        const savedTheme = localStorage.getItem('theme') || 'light';
        setTheme(savedTheme);
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlEl.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            setTheme(currentTheme);
        });

        var modal = document.getElementById("myModal");
        var modalImg = document.getElementById("modalImg");
        var modalName = document.getElementById("modalName");
        var modalDescription = document.getElementById("modalDescription");
        var downloadLink = document.getElementById("downloadLink");
        var shareBtn = document.getElementById("shareBtn");
        var closeBtn = document.getElementsByClassName("close")[0];
        const ratingSection = document.getElementById('rating-section');
        const interactiveStars = document.querySelectorAll('#rating-interactive .star');
        const ratingSummary = document.getElementById('rating-summary');
        const interactiveRatingContainer = document.getElementById('rating-interactive');

        function openModal(imgSrc, name, description) {
            modal.style.display = "flex"; 
            modalImg.src = imgSrc;
            modalName.textContent = name;
            modalDescription.textContent = description;
            downloadLink.href = imgSrc;
            shareBtn.onclick = function() { shareContent(name, description, imgSrc); };
            if (ratingSection) {
                const imageFile = imgSrc.split('/').pop();
                interactiveRatingContainer.dataset.imageFile = imageFile;
                updateRatingDisplay(imageFile);
            }
        }
        function closeModal() { modal.style.display = "none"; }
        closeBtn.onclick = closeModal;
        window.onclick = function(event) { if (event.target == modal) { closeModal(); } }

        function shareContent(name, description, imgSrc) {
            const uniqueUrl = window.location.href.split('?')[0] + '?image=' + encodeURIComponent(imgSrc.split('/').pop());
            if (navigator.share) {
                navigator.share({ title: name, text: description, url: uniqueUrl });
            } else {
                navigator.clipboard.writeText(uniqueUrl).then(() => {
                    alert('Link kopieret til udklipsholder.');
                });
            }
        }
        
        if (ratingSection) {
            interactiveStars.forEach(star => {
                star.addEventListener('mouseover', () => {
                    const value = star.dataset.value;
                    interactiveStars.forEach(s => s.classList.toggle('hovered', s.dataset.value <= value));
                });
                star.addEventListener('mouseout', () => { interactiveStars.forEach(s => s.classList.remove('hovered')); });
                star.addEventListener('click', () => {
                    const rating = star.dataset.value;
                    const imageFile = interactiveRatingContainer.dataset.imageFile;
                    ratingSummary.textContent = 'Sender stemme...';
                    fetch('rate_image.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ image: imageFile, rating: rating })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayStars(data.average, data.votes);
                        } else {
                            alert(data.message);
                            updateRatingDisplay(imageFile);
                        }
                    });
                });
            });
        }
        function displayStars(average, votes) {
            ratingSummary.textContent = `Gennemsnit: ${average}/5 (${votes} stemmer)`;
            const roundedAvg = Math.round(average);
            interactiveStars.forEach(star => {
                star.classList.toggle('selected', star.dataset.value <= roundedAvg);
                star.textContent = (star.dataset.value <= roundedAvg) ? '★' : '☆';
            });
        }
        function updateRatingDisplay(imageFile) {
            const galleryItem = document.querySelector(`img[src$="${imageFile}"]`);
            if (!galleryItem) return;
            const galleryItemContainer = galleryItem.closest('.gallery-item');
            const display = galleryItemContainer.querySelector('.rating-display');
            ratingSummary.textContent = display ? display.textContent.replace('Rating: ', 'Nuværende gennemsnit: ') : 'Vælg din bedømmelse';
            interactiveStars.forEach(star => {
                star.classList.remove('selected', 'hovered');
                star.textContent = '☆';
            });
        }
        
        <?php if ($map_enabled && $has_gps_images): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const showGalleryBtn = document.getElementById('show-gallery-btn');
            const showMapBtn = document.getElementById('show-map-btn');
            const galleryView = document.getElementById('gallery-view');
            const mapView = document.getElementById('map-view');
            const currentViewTitle = document.getElementById('current-view-title');
            
            let mymap = null; 
            const allImagesWithCoords = <?php echo json_encode(array_values(array_filter($all_images_data, fn($img) => $img['latitude'] !== null)), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

            if (showGalleryBtn) showGalleryBtn.addEventListener('click', () => showView('gallery'));
            if (showMapBtn && !showMapBtn.disabled) showMapBtn.addEventListener('click', () => showView('map'));

            function showView(view) {
                if (view === 'gallery') {
                    galleryView.style.display = 'flex';
                    if (mapView) mapView.style.display = 'none';
                    if (showGalleryBtn) showGalleryBtn.classList.add('active');
                    if (showMapBtn) showMapBtn.classList.remove('active');
                    currentViewTitle.textContent = 'Galleri';
                } else {
                    galleryView.style.display = 'none';
                    if (mapView) mapView.style.display = 'block';
                    if (showGalleryBtn) showGalleryBtn.classList.remove('active');
                    if (showMapBtn) showMapBtn.classList.add('active');
                    currentViewTitle.textContent = 'Kortvisning';
                    if (!mymap && allImagesWithCoords.length > 0) { initializeMap(); }
                    else if (mymap) { setTimeout(() => mymap.invalidateSize(), 10); }
                }
            }

            var userMarker = null; var userCircle = null;

            function initializeMap() {
                mymap = L.map('map').setView([55.6416, 12.0811], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mymap);

                // GRUPPERING AF LAG
                const groupA = L.markerClusterGroup();
                const groupB = L.markerClusterGroup();
                const groupC = L.markerClusterGroup();

                allImagesWithCoords.forEach(image => {
                    const latLng = [image.latitude, image.longitude];
                    const safeName = image.name.replace(/'/g, "\\'").replace(/"/g, "&quot;");
                    const safeDescription = image.description.replace(/'/g, "\\'").replace(/"/g, "&quot;");
                    const popupContent = `
                        <div class="map-popup-content">
                            <img src="${image.src}" alt="${safeName}" class="map-popup-image" onclick="openModal('${image.src}', '${safeName}', '${safeDescription}')" style="cursor: pointer;">
                            <div class="map-popup-name">${image.name}</div>
                            <div class="map-popup-description">${image.description}</div>
                        </div>`;
                    const marker = L.marker(latLng).bindPopup(popupContent);

                    // Sorter i grupper
                    if (image.group === 'B') { groupB.addLayer(marker); }
                    else if (image.group === 'C') { groupC.addLayer(marker); }
                    else { groupA.addLayer(marker); } // Default A
                });

                // Tilføj grupper til kort
                mymap.addLayer(groupA);
                mymap.addLayer(groupB);
                mymap.addLayer(groupC);

                // Kontrolpanel til lag
                const overlays = { "Gruppe A": groupA, "Gruppe B": groupB, "Gruppe C": groupC };
                L.control.layers(null, overlays, { collapsed: false }).addTo(mymap);

                // Zoom til data
                const bounds = L.latLngBounds([]);
                if(groupA.getBounds().isValid()) bounds.extend(groupA.getBounds());
                if(groupB.getBounds().isValid()) bounds.extend(groupB.getBounds());
                if(groupC.getBounds().isValid()) bounds.extend(groupC.getBounds());
                if(bounds.isValid()) mymap.fitBounds(bounds, { padding: [50, 50] });

                // "Her er jeg" knap
                var locateControl = L.Control.extend({
                    options: { position: 'topleft' }, 
                    onAdd: function(map) {
                        var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control locate-btn');
                        container.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3c-.46-4.17-3.77-7.48-7.94-7.94V1h-2v2.06C6.83 3.52 3.52 6.83 3.06 11H1v2h2.06c.46 4.17 3.77 7.48 7.94 7.94V23h2v-2.06c4.17-.46 7.48-3.77 7.94-7.94H23v-2h-2.06zM12 19c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/></svg>';
                        container.onclick = function(e) { e.preventDefault(); e.stopPropagation(); locateUserAndFilter(); }
                        return container;
                    }
                });
                mymap.addControl(new locateControl());
            }

            function locateUserAndFilter() {
                if (!navigator.geolocation) { alert("Geolocation ikke understøttet."); return; }
                mymap.getContainer().style.cursor = 'wait';
                navigator.geolocation.getCurrentPosition(function(position) {
                    mymap.getContainer().style.cursor = '';
                    var lat = position.coords.latitude; var lng = position.coords.longitude;
                    if (userMarker) mymap.removeLayer(userMarker);
                    if (userCircle) mymap.removeLayer(userCircle);
                    var userIcon = L.divIcon({ className: 'user-location-marker', html: '<div style="background-color: #4285F4; width: 14px; height: 14px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);"></div>', iconSize: [20, 20], iconAnchor: [10, 10] });
                    userMarker = L.marker([lat, lng], {icon: userIcon}).addTo(mymap).bindPopup("Du er her").openPopup();
                    userCircle = L.circle([lat, lng], { color: '#4285F4', fillColor: '#4285F4', fillOpacity: 0.1, radius: 100 }).addTo(mymap);
                    mymap.setView([lat, lng], 16);
                }, function(err) { mymap.getContainer().style.cursor = ''; alert("Kunne ikke finde position: " + err.message); }, { enableHighAccuracy: true });
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>