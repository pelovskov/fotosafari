<?php

/**
 * BRUGER- OG ADGANGSKODELISTE (HASHED)
 * Adgangskoderne er nu gemt som bcrypt-hash i stedet for klartekst.
 * Du kan ikke se eller genskabe det oprindelige password ud fra hashen -
 * det er meningen. Login sker ved at hashe den indtastede kode og
 * sammenligne hash mod hash (se login.php / password_verify()).
 *
 * For at TILFØJE en ny bruger eller SKIFTE en adgangskode:
 * kør en lille PHP-snippet et sted (fx i en browser, midlertidigt):
 *   echo password_hash('dit-nye-password', PASSWORD_DEFAULT);
 * og sæt den resulterende streng ind som værdien herunder.
 *
 * Format: 'brugernavn' => 'bcrypt-hash',
 */

$users = [
    'lovskov' => '$2b$12$qBDoFNUW3fdVfL7WkHyAs..yYm/v58doIo/tqNvN6.XD4UYiQLphi',
    'anna' => '$2b$12$GQ0MMkQEJzLpxiO0vUoCbujer/yNLflb87xGB5SyOiWYstr8vNBiu',
    'peter' => '$2b$12$jKA4IRowrq4euf9jBixzz.uNo9.wtmvIB8nDCEF4g5j2uxBkCVvia',
];

?>
