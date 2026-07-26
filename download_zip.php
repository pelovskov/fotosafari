<?php
session_start();
// Sikkerhedstjek: Kun loggede ind brugere kan downloade
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    exit('Adgang nægtet.');
}

$zip_file = 'alle_billeder_med_tekst.zip'; // Nyt navn for at undgå forvirring
$upload_dir = 'uploads/';

// Initialiser ZipArchive
$zip = new ZipArchive();
if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    exit("Kan ikke oprette $zip_file");
}

// Hent alle billedfiler
$image_files = glob($upload_dir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);

// Gennemgå hver billedfil
foreach ($image_files as $image_file) {
    // 1. Tilføj selve billedfilen til ZIP-arkivet
    $zip->addFile($image_file, basename($image_file));
    
    // 2. Konstruer stien til den tilhørende tekstfil
    $text_file = $upload_dir . pathinfo($image_file, PATHINFO_FILENAME) . '.txt';
    
    // 3. Tjek om tekstfilen findes
    if (file_exists($text_file)) {
        // 4. Hvis den findes, tilføj den også til ZIP-arkivet
        $zip->addFile($text_file, basename($text_file));
    }
}

$zip->close();

// Send ZIP-filen til browseren for download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
header('Content-Length: ' . filesize($zip_file));

// Læs filen og send den til output
readfile($zip_file);

// Slet den midlertidige ZIP-fil fra serveren
unlink($zip_file);
exit;
?>
