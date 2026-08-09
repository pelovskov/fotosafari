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
    'overadmin' => '$2y$12$1e49LzYQ2j559BSvwJjmXun/HrAcxD2UQa7sVJ4e8II5Ar8gGOmb2',
    'anna' => '$2y$12$1e49LzYQ2j559BSvwJjmXun/HrAcxD2UQa7sVJ4e8II5Ar8gGOmb2',
    'peter' => '$2y$12$1e49LzYQ2j559BSvwJjmXun/HrAcxD2UQa7sVJ4e8II5Ar8gGOmb2',
];

?>
