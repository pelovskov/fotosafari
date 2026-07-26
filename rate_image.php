<?php
session_start();
header('Content-Type: application/json'); // Vi svarer altid i JSON-format

$ratingsFile = 'ratings.json';

// Sikkerhedstjek: Accepter kun anmodninger sendt via POST-metoden
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Ugyldig anmodning.']);
    exit;
}

// Hent data sendt fra gallerisiden
$data = json_decode(file_get_contents('php://input'), true);
$imageFile = basename($data['image']); // basename() for ekstra sikkerhed
$rating = intval($data['rating']);

// Validering af data
if (empty($imageFile) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Ugyldige data.']);
    exit;
}

// Læs den eksisterende ratings-fil, eller opret en tom struktur
$all_ratings = file_exists($ratingsFile) ? json_decode(file_get_contents($ratingsFile), true) : [];

// Hent den nuværende brugers IP-adresse
$user_ip = $_SERVER['REMOTE_ADDR'];

// Klargør data for det specifikke billede
$image_ratings = $all_ratings[$imageFile] ?? ['ratings' => [], 'voters' => []];

// Simpel "anti-snyd": Tjek om denne IP-adresse allerede har stemt på dette billede
if (in_array($user_ip, $image_ratings['voters'])) {
    echo json_encode(['success' => false, 'message' => 'Du har allerede stemt på dette billede.']);
    exit;
}

// Tilføj den nye stemme og IP-adressen
$image_ratings['ratings'][] = $rating;
$image_ratings['voters'][] = $user_ip;

// Gem de opdaterede data for billedet tilbage i den overordnede struktur
$all_ratings[$imageFile] = $image_ratings;

// Skriv alle ratings tilbage til filen
file_put_contents($ratingsFile, json_encode($all_ratings, JSON_PRETTY_PRINT));

// Beregn ny gennemsnitlig rating og antal stemmer
$total_ratings = count($image_ratings['ratings']);
$average_rating = $total_ratings > 0 ? array_sum($image_ratings['ratings']) / $total_ratings : 0;

// Send et succes-svar tilbage til gallerisiden med de nye data
echo json_encode([
    'success' => true,
    'average' => round($average_rating, 1),
    'votes' => $total_ratings
]);
?>
