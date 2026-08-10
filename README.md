# Fotosafari
Alle filer til fotosafari
Der arbejdes lige nu med at få finpudset systemet. Det indebærer blandt andet at der skal laves en komplet tejnisk vejledning, så andre end mig selv kan installere og bruge Fotosafari.
## Vejledninger
Ud over den tekniske vejledning så er der ved at blive udarbejdet en egentlig brugervejledning.

Desværre er det meget varmt i denne sommer, så der går nok et par dage - :)

## Projektets filer

```
 |-- admin.php
 |-- config.json
 |-- download_zip.php
 |-- galleri_masonry.php
 |-- galleri.php
 |-- LICENSE
 |-- login.php
 |-- logout.php
 |-- mange.php
 |-- minside.php
 |-- qr-upload-2.html
 |-- qr-upload.html
 |-- rate_image.php
 |-- ratings.json
 |-- README.md
 |-- script.css
 |-- style.css
 |-- upload.php
 |-- uploads
 | |-- billede_68e9017da22ff5.74065224.jpg
 | |-- billede_68e9017da22ff5.74065224.txt
 | |-- billede_68e914830ee7e8.81396228.jpg
 | |-- billede_68e914830ee7e8.81396228.txt
 |-- users.php
```
----

## Det bruges filerne til

- **admin.php**
Det er her det hele kan administreres. Admin kan slette og rette uploads, men også tilpasse navn o funktioner for fotosafari systemet. Der kan indstilles hvornår systemet er åbent for upload, om der skal være stjerne ratings, tilføje CTA knapper, downloade alle billeder mm. der kan være flere administratorere.
- **config.json** Det er her indstillingerne fra admin.php gemems.
- **download_zip.php** Dette er en systemfil som sørger for at billeder og beskrivelse kan downloades aom en enkelt zipfil. Den aktiveres inde fra adminsiden.
- **galleri_masonry.php** Det er de seekundære album. Albummet har masonry visning.
- **galleri.php** Dette er standard gallerivisningen. Hvis kortvisning er aktiveret, så er der en knap til kortet herfra.
- **LICENSE** Dette er info om den licens der er for systemet. I tilfældes her er det en MIT licens.
- **login.php** og **logout.php** Det er systemfiler som sørger for at man kan logge ind og logge ud. De tilgås ikke direkte, men via adminsiden.
- **mange.php** Det er en ny funktion, som giver mulighed for at uploade flere billeder på en gang. Den er tiltænkt en administrator eller leder på fotosafariprojektet.
- **minside.php** Det er brugerens egen side. Kan tilgås via QR kode eller linket som danner QR koden. Herfra kan den enkelte bruger rette lidt på beskrivelsen eller slette et billede.
- **qr-upload-2.html** Det er en særlig side, som en admin eller leder kan undskrive deltagernes navne og så få en zipfil med villedfiler med QR kode og særligt link til "min side".
- **qr-upload.html** En mere simpel udgave af QR kode generatoren.
- **rate_image.php** En systemfil, som styrer ratings - hvis det er slået til i systemet. Det er admin der kan sætte ratings til eller fra.
- **rate_image.php** Det er denne fil.
- **script.css og style.css** systemfiler der sørger for at alt virker og ser pænt ud.
- **upload.php** Det er siden som man kommer til via QR koden eller link. Kan også tilgås direkte, så skal man selv skrive sit navn og har ingen "Min Side".
- **uploads** Det er mappen hvor alle billeder og beskrivelser findes.
- **users.php** Filen hvor admin brugere oprettes og deres hashede adgangskode befinder sig.

## Det var systemet
Det er hvad systemet består af. Hver gang der startes en ny fotosafari, laver vi en klon af disse filer og overdrager brugernavn og adgangskoder til en elle rflere administratorer.

God fornøjelse med Fotosafari

**Poul Erik Løvskov**

