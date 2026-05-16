# Editor V5 Widget Library Pro — Guida utente

Repository: `manuelericci1969/cms_r4software_demo`  
Branch di sviluppo: `feature/editor-v5-widget-library-pro`  
Data: 08/05/2026  
Stato: guida operativa per test locale e pre-produzione

---

## 1. Obiettivo della libreria Widget Pro

La libreria **Editor V5 Widget Library Pro** estende l'Editor V5 con una serie di componenti professionali pensati per costruire pagine web, landing page e sezioni commerciali senza dover scrivere manualmente HTML, CSS e JavaScript.

L'obiettivo è rendere l'Editor V5 più vicino a un page builder professionale, mantenendo però il controllo diretto del codice e la compatibilità con il CMS R4Software.

La libreria introduce:

- widget editoriali;
- sezioni commerciali già pronte;
- sezioni extra per landing page;
- preset completi di pagina;
- runtime CSS/JS condiviso tra editor e frontend pubblico;
- supporto FAQ accordion;
- supporto contatori numerici;
- miglioramento responsive per griglie e sezioni complesse.

---

## 2. Categorie disponibili nell'Editor V5

Dopo il caricamento corretto degli asset, nel pannello Widget dell'Editor V5 devono essere disponibili le seguenti categorie:

- Base;
- Layout;
- Marketing;
- Media;
- Contenuti;
- Sezioni Pro;
- Sezioni Pro Extra;
- Liste Pro;
- Preset Landing.

La categoria più importante per la costruzione rapida di nuove pagine è:

```text
Preset Landing
```

---

## 3. Preset Landing disponibili

La categoria **Preset Landing** contiene pagine precompilate complete, pensate come base di partenza per creare rapidamente una pagina professionale.

### 3.1 Landing completa R4Software

Preset generale pensato per una landing aziendale R4Software.

Contiene:

- Hero principale;
- Problema / Soluzione;
- Servizi;
- Statistiche / Counter;
- Portfolio / lavori;
- FAQ Accordion;
- CTA finale.

Uso consigliato:

- pagina servizi R4Software;
- landing istituzionale;
- pagina consulenza software;
- pagina sviluppo siti web;
- pagina CRM o app.

---

### 3.2 Sito aziendale corporate

Preset pensato per un sito aziendale classico, professionale e ordinato.

Contiene:

- Hero corporate scuro;
- sezione chi siamo;
- punti di forza;
- aree operative / servizi;
- CTA finale.

Uso consigliato:

- aziende locali;
- studi professionali;
- consulenti;
- imprese di servizi;
- società B2B.

---

### 3.3 Landing lead generation

Preset pensato per generare richieste di contatto.

Contiene:

- Hero orientata alla conversione;
- box contatto / richiesta consulenza;
- vantaggi;
- offerta;
- FAQ;
- CTA forte.

Uso consigliato:

- campagne Google Ads;
- campagne Meta Ads;
- campagne LinkedIn;
- landing per preventivi;
- acquisizione lead qualificati.

Nota:

Il modulo presente nel preset è una struttura HTML di base e va collegato alla logica reale del CMS o sostituito con un componente form gestito dal sistema.

---

### 3.4 Portfolio / Agency

Preset pensato per mostrare lavori, casi studio, competenze e risultati.

Contiene:

- Hero portfolio;
- progetto principale in evidenza;
- box approccio / risultato;
- griglia case study;
- competenze;
- CTA finale.

Uso consigliato:

- web agency;
- software house;
- freelance;
- architetti;
- fotografi;
- studi creativi;
- portfolio professionali.

---

### 3.5 Attività locale / Servizi

Preset pensato per attività locali e servizi territoriali.

Contiene:

- Hero attività locale;
- perché sceglierci;
- servizi principali;
- area mappa / sede;
- CTA telefonica o WhatsApp.

Uso consigliato:

- hotel;
- ristoranti;
- professionisti locali;
- attività commerciali;
- servizi alla persona;
- aziende con presenza territoriale.

---

## 4. Widget editoriali disponibili

I widget editoriali sono pensati per migliorare la qualità dei contenuti testuali.

Sono disponibili:

- elenco puntato;
- elenco numerato;
- lista check avanzata;
- articolo / testo lungo;
- citazione / quote;
- badge / label.

Uso consigliato:

- pagine SEO;
- articoli;
- sezioni descrittive;
- blocchi informativi;
- elenchi di vantaggi;
- spiegazioni tecniche.

---

## 5. Sezioni Pro disponibili

Le sezioni Pro sono blocchi commerciali già pronti per costruire una pagina in modo modulare.

Sono disponibili:

- Hero Pro;
- Servizi Pro;
- Perché sceglierci;
- Processo 4 step;
- FAQ Accordion;
- CTA finale Pro.

Uso consigliato:

- costruzione pagina manuale se non si vuole partire da un preset completo;
- sostituzione di sezioni dentro un preset;
- creazione landing personalizzate;
- pagine prodotto o servizio.

---

## 6. Sezioni Pro Extra disponibili

Le sezioni Pro Extra aggiungono blocchi avanzati per landing più complete.

Sono disponibili:

- Stats Pro counter;
- Testimonial Pro grid;
- Pricing Pro;
- Logo / Clienti grid;
- Team / Profilo Pro;
- Contatto / Lead Pro;
- Portfolio / Lavori Pro;
- Problema / Soluzione Pro;
- Timeline verticale Pro;
- Comparazione Pro.

Uso consigliato:

- rafforzare la prova sociale;
- mostrare prezzi;
- presentare casi studio;
- spiegare processi;
- confrontare soluzioni;
- inserire contatti e lead.

---

## 7. Liste Pro

Le Liste Pro introducono vari stili di elenco più curati rispetto agli elenchi HTML standard.

Sono disponibili diverse varianti grafiche:

- liste con pallini colorati;
- liste con check;
- liste numerate;
- liste card;
- liste box soft.

Nota tecnica:

Le Liste Pro risultano funzionanti, ma sono ancora considerate una bozza migliorabile. Prima della stabilizzazione definitiva si consiglia di:

- ridurre i preset duplicati;
- mantenere solo 2/3 varianti realmente utili;
- migliorare i controlli nell'Inspector;
- uniformare lo stile con il resto della libreria Widget Pro.

---

## 8. Runtime pubblico

I widget Pro utilizzano asset CSS/JS dedicati:

```text
public/assets/editor-v5/runtime/widgets-pro.css
public/assets/editor-v5/runtime/widgets-pro.js
```

Questi asset servono per:

- stile pubblico dei widget;
- comportamento FAQ accordion;
- contatori numerici `data-r4v5-count`;
- responsive delle griglie;
- compatibilità frontend.

Il controller V5 deve iniettare automaticamente questi runtime quando nella pagina sono presenti classi o attributi Pro.

Trigger attesi:

```text
r4v5-pro-
data-r4v5-faq-accordion
data-r4v5-count
```

---

## 9. Test consigliato prima della produzione

Prima del merge su `main` e della produzione, eseguire il test completo in locale.

### 9.1 Aggiornamento branch

```bash
cd /percorso/progetto/cms_r4software_demo

git fetch origin
git switch feature/editor-v5-widget-library-pro
git pull --ff-only
```

### 9.2 Pulizia cache Laravel

Con MAMP PHP 8.4.2:

```bash
/Applications/MAMP/bin/php/php8.4.2/bin/php artisan view:clear
/Applications/MAMP/bin/php/php8.4.2/bin/php artisan optimize:clear
/Applications/MAMP/bin/php/php8.4.2/bin/php artisan config:clear
/Applications/MAMP/bin/php/php8.4.2/bin/php artisan route:clear
```

### 9.3 Hard refresh browser

Aprire l'Editor V5 e fare:

```text
CMD + SHIFT + R
```

### 9.4 Controlli nel pannello widget

Verificare che siano visibili:

- Sezioni Pro;
- Sezioni Pro Extra;
- Liste Pro;
- Preset Landing.

Dentro **Preset Landing** verificare:

- Landing completa R4Software;
- Sito aziendale corporate;
- Landing lead generation;
- Portfolio / Agency;
- Attività locale / Servizi.

### 9.5 Test funzionale

Per ogni preset:

1. inserirlo in una pagina vuota;
2. salvare bozza;
3. ricaricare l'editor;
4. verificare che HTML/CSS/JSON siano conservati;
5. pubblicare;
6. aprire il frontend pubblico;
7. controllare responsive desktop/tablet/mobile;
8. verificare console browser;
9. verificare FAQ accordion;
10. verificare counter numerici.

---

## 10. Checklist pre-produzione

Prima di andare in produzione verificare:

- nessun errore JavaScript in console;
- preset visibili nell'Editor V5;
- salvataggio bozza funzionante;
- pubblicazione funzionante;
- frontend pubblico coerente con editor;
- FAQ funzionanti;
- counter funzionanti;
- responsive accettabile;
- nessuna modifica indesiderata a `composer.lock`;
- nessun file temporaneo committato;
- branch aggiornato con `main`, se necessario;
- backup produzione disponibile.

---

## 11. Note su AI Text Assistant futuro

La libreria Widget Pro può essere estesa con un assistente AI per la generazione testi.

Architettura consigliata:

```text
Editor V5
↓
Rotta Laravel admin protetta
↓
AiTextGenerationService
↓
Provider configurabile
↓
Qwen / DeepSeek / OpenAI / provider locale
↓
Risposta testuale
↓
Inserimento nel blocco selezionato
```

Funzioni consigliate:

- genera titolo hero;
- genera sottotitolo;
- riscrivi testo selezionato;
- migliora testo SEO;
- genera FAQ;
- genera CTA;
- genera descrizione servizi;
- genera meta title e meta description.

Provider consigliati:

- DeepSeek come primo provider per semplicità di integrazione;
- Qwen come provider alternativo;
- configurazione tramite `.env`;
- nessuna API key esposta nel frontend.

---

## 12. Stato finale

La libreria Widget Pro è pronta per test locale completo.

Prima del merge su `main` è consigliato completare almeno un ciclo reale di test su pagina bozza e pagina pubblicata.

Se il test è positivo, il branch può essere portato su `main` e successivamente distribuito in produzione.
