# **Teknisk og Konceptuel Redegørelse: Fotosafari**

**Et hybridt system til lokalhistorisk dokumentation, generationsbrobygning og borgerinddragelse**

*Projektansvarlig: Foreningen ”Syd for Banen” (Roskilde)* *Udmærkelse: Vinder af Landsby- og Bydelsprisen 2025*

## **1\. Indledning og Projektvision**

**Fotosafari** er et unikt digitalt og analogt formidlingssystem udviklet til at engagere borgere på tværs af generationer – fra folkeskoleelever til seniorer – i at udforske, dokumentere og bevare deres lokalområde.

Selvom systemet rummer et avanceret teknologisk fundament med live GPS-kort, webapplikation og dynamiske visninger, er projektets overordnede filosofi centreret om **processen og nærværet**. Teknologi fungerer her ikke som en skærmbarriere, men som en muliggører for samtaler over kaffebordet, lokalhistorisk detektivarbejde og direkte borgerdrevet byforbedring (Civic Tech).

## **2\. Grundlæggende Systemarkitektur og Teknisk Flow**

Systemets kernefunktionalitet er bygget op omkring et barrierefrit og app-frit workflow, der eliminerer behovet for brugernavne, kodeord eller installationer fra App Store.

 \[ Bruger-oprettelse \]   
         │  
         ▼  
 \[ QR-Generering \] ──► \[ Fysisk Onboarding (Nøglesnor/Kort) \]  
         │  
         ▼  
 \[ QR-Scan med Mobil \] ──► \[ Nul-friktion Session / Pre-authentificeret \]  
         │  
         ├───────────────────────────┬───────────────────────────┐  
         ▼                           ▼                           ▼  
\[ Billed-upload & Tekst \]    \[ Gallerivisninger \]       \[ Interaktivt Kort \]  
         │                   • Tile/Modal (med Deling)  • Leaflet.js  
         ▼                   • Mørkekammer / Slides     • GPS-markører  
 \[ "Min Side" Dashboard \]    • Rating (1-5 stjerner)  
         │  
         ▼  
 \[ Admin Export & Arkiv \] (ZIP, CSV, Metadata)

### **Detaljeret Teknisk Gennemgang af Hovedpunkterne:**

1. **Brugeradministration og Batch-oprettelse**  
   * Administrator opretter brugere via et simpelt administrative interface. Data kan indtastes linje for linje (eller via batch-import), indeholdende **Navn** og **Gruppe-tilknytning** (*Gruppe A, B eller C*).  
2. **Generering af QR-koder og Fysisk Onboarding**  
   * Systemet genererer automatisk en unik QR-kode med en indlejret token for hver oprettet bruger.  
   * QR-koden trykkes/klæbes på robuste manillemærker (5×10 cm) eller laminerede visitkort.  
   * Kortene monteres i farvekodede keystraps (nøglesnore):  
     * **Grøn snor (Gruppe A):** Fokus på natur, bænke og tilgængelighed (Civic Tech).  
     * **Rød snor (Gruppe B/C):** Fokus på lokalhistorie, "Da og Nu"-missioner samt arkitektur.  
3. **Nul-friktion Adgang via QR-Scan**  
   * Når en deltager scanner QR-koden med sin smartphones kamera, åbnes en tilpasset webapplikation direkte i browseren.  
   * Sessionen er præ-godkendt: Brugerens navn og gruppe er automatisk udfyldt i interfacet, hvilket fjerner enhver teknologisk login-barriere.  
4. **Billedupload og EXIF/Geodata-behandling**  
   * Brugeren kan uploade billeder direkte fra mobilens kamera eller fotobibliotek samt tilføje en kort skriftlig beskrivelse/anekdote.  
   * Hvis billedfilen indeholder EXIF-geolokaliseringsdata (GPS-koordinater), udtrækker og indekserer serveren automatisk disse.  
5. **Databaselagring og Strukturering**  
   * Billeder og tilhørende metadata (fotograf, gruppe, tidsstempel, koordinater og beskrivelse) placeres i projektets centrale database og sorteres automatisk ud fra gruppetilhørsforhold.  
6. **Interaktiv Gallerivisning (Tiles, Modaler & Deling)**  
   * Galleriet præsenterer billederne som en responsiv flise-oversigt (thumbnails).  
   * Ved klik på en flise åbnes en popup-modal, som viser billedet i fuld størrelse, fotografens navn samt den tilhørende beskrivelse.  
   * **Integret delingsfunktion:** Hver modal indeholder et direkte delingsikon med et unikt deep-link til den specifikke modal. Linket kan let kopieres eller deles via SMS og e-mail.  
7. **Fleksible Visningsformer ("No One Size Fits All")**  
   * **Klassisk Gallerivisning:** Med fokus på overskuelighed, kortvisning, direkte deling og rating.  
   * **Virtuelt Mørkekammer / Albumvisning:** En æstetisk mørk visning med slider- og masonry-mosaik-funktionalitet samt mulighed for lightbox-præsentation, velegnet til præsentation på storskærme.  
8. **Modulært Rating-system (Optionel)**  
   * Admin kan globalt aktivere et rating-system, der giver lærere, jurymedlemmer eller borgere mulighed for at tildele 1 til 5 stjerner til enkelte billeder.  
9. **Interaktivt Leaflet-Kort**  
   * Et integreret OpenStreetMap/Leaflet-kort viser en geoplaceret markør for hvert billede med GPS-data.  
   * Klik på en markør åbner en informativ kort-modal med billedfremvisning, fotograf og beskrivelse.  
10. **Bruger-dashboard ("Min Side") og Rettighedsstyring**  
    * Hvis en bruger re-scanner sin QR-kode, kan vedkommende tilgå **"Min Side"** i stedet for upload-siden.  
    * Herfra kan brugeren redigere sine oplysninger eller slette egne uploadede billeder.  
    * **Admin-rolle:** Administratorer har fulde CRUD-rettigheder (Create, Read, Update, Delete) over alt materiale i systemet.  
11. **Eksportering og Langtidssikret Arkivering**  
    * Admin kan med et enkelt klik eksportere og downloade samtlige billeder og tilhørende beskrivelser/metadata samlet i en struktureret ZIP-fil. Dette sikrer lokal retention og modvirker data-tab.

## **3\. Det "Manglende Led": Integration af Skærmfrie Kameraer (Camp Snap)**

Et særligt innovativt element i Fotosafari-konceptet er integrationen af det skærmfrie, digitale legetøjskamera **Camp Snap**.

  \[ Camp Snap-kamera \]         \[ Holdleder / Smartphone \]  
 (Skærmfri / 8MP / Retro)      (Optager GPS-rute & tidsstempler)  
           │                                 │  
           └────────────────┬────────────────┘  
                            ▼  
              \[ USB-C Import til PC/Server \]  
                            │  
                            ▼  
               \[ Geolokaliserings-Hacket \]  
     (Matcher tidsstempel med GPS-rute for automatisk placering)

* **Nærvær og Mindfulness:** Da Camp Snap-kameraet ikke har nogen skærm, undgås "skærmkiggeri" under gåturen. Deltagerne kigger på hinanden og på byens arkitektur frem for at redigere fotos i feltet.  
* **Fremkaldelses-effekten:** Billederne ses først, når turen er slut og kameraet tilsluttes en PC via USB-C. Det skaber en nostalgisk spænding og en fælles "fremkaldelses-oplevelse".  
* **Geolokaliserings-hacket:** Camp Snap mangler indbygget GPS. Dette løses ved, at holdlederen optager en GPS-rute på sin mobil. Ved import matcher systemet tidsstemplerne på Camp Snap-billederne med holdlederens GPS-spoor og placerer automatisk billederne korrekt på Leaflet-kortet.

## **4\. Det Taktile og Sociale Lag: Scrapbøger og Talende Billeder**

Fotosafari stopper ikke ved den digitale skærm. For at skabe langtidsholdbar værdi og favne alle målgrupper udvides det digitale spor med et fysisk, taktilt spor:

1. **Sublimationsprint og Fysiske Scrapbøger:**  
   * De bedste billeder printes på stedet (f.eks. med en Canon CP1000 fotoprinter) og klæbes ind i fysiske fotoalbums sammen med håndskrevne noter og historier.  
2. **Cirkulerende Album (Social Anledning):**  
   * Albummet overdrages fra borger til borger. Det fungerer som en uformel invitation til at mødes over en kop kaffe, bladre i minderne og føje nye håndskrevne kommentarer til siderne.  
3. **Den Digitale Tvilling & Det Talende Lag:**  
   * **Forrest i albummet:** En QR-kode linker til en digital e-bog, som opdateres løbende, efterhånden som det fysiske album vokser.  
   * **Ved udvalgte billeder:** Små QR-koder linker til "talende booklets" – lydoptagelser af lokale borgere, der fortæller historien eller anekdoten bag det konkrete foto.

## **5\. Målgrupper, Tematiske Missioner og GDPR**

Systemet er skræddersyet til at understøtte specifikke pædagogiske og samfundsmæssige missioner:

* **Civic Tech & Tilgængelighedspatruljen (Gruppe A):**  
  * Seniorer og borgere på el-scootere/kørestole kortlægger bænke, manglende ramper og fysiske barrierer i byrummet. Data leveres direkte til byplanlæggere i Roskilde Kommune.  
* **Lokalhistorie & "Da og Nu" (Gruppe B/C):**  
  * Skoleelever og ældre arbejder som "tidsdetektiver". De genfotograferer historiske arkivfotos (f.eks. af Roskildes historiske kilder som Maglekilde) i præcis samme vinkel.  
* **Integreret GDPR-praksis:**  
  * Systemet understøttes af målrettede GDPR-guides til hhv. 5\. klasse, ungdomsuddannelser og 60+ deltagere. Der undervises i anonymiseringsteknikker (fotografering bagfra, fokus på detaljer/bygninger) samt samtykkeregler for personer under 18 år.

## **6\. Sammenfatning af Systemets Komponenter**

| Komponent | Funktion / Teknisk Løsning | Primær Værdi |
| :---- | :---- | :---- |
| **Webapplikation** | QR-baseret login uden app-installation; geolokaliseret upload. | Fjerner teknologiske tærskler for alle aldre. |
| **Leaflet / OpenStreetMap** | Interaktivt bykort med placering af fotomarkører og modaler. | Giver visuelt overblik over lokalhistorie og infrastruktur. |
| **Camp Snap Integration** | Skærmfri 8MP fotografering \+ tidsstempel-matching via GPS-rute. | Skaber nærvær i feltet og fælles "fremkaldelse". |
| **Fysisk Scrapbook & Print** | Canon CP1000 print, håndskrift og uformelt udlån i foreningen. | Taktilt samlingspunkt og anledning til kaffebordssamtaler. |
| **Det Talende Lag** | QR-koder i albums koblet til lydfiler og e-bøger. | Bevarer den mundtlige fortællekultur og lokalhistorie. |
| **Data-Export** | Fuld ZIP/CSV-ekskludering af alle medier og metadata for Admin. | Garanti for langtidssikring ud over nettes levetid. |

*Fotosafari-konceptet viser, hvordan man ved at koble lavpraktisk analog friktion med moderne webteknologi kan skabe et bæredygtigt, engagerende og generationsforbindende lokalsamfund.*
