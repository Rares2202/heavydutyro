# HeavyDutyRO — Ghid Instalare Backend PHP + MySQL (WAMP)

## Structura Folderelor

```
C:\wamp64\www\heavydutyro\
├── index.html
├── login.html
├── dashboard.html
├── workout-planner.html
├── body-fat-estimator.html
├── jurnal-antrenament.html
├── nutritie.html
├── setari.html
├── filosofia-mentzer.html
├── assets/
│   └── css/
│       └── style.css
├── api/
│   ├── auth.php
│   ├── workouts.php
│   ├── nutrition.php
│   └── user.php
├── includes/
│   ├── db.php
│   └── session.php
└── setup.sql
```

## Pași de Instalare

### 1. Pornire WAMP
- Deschide WAMP Server
- Asigură-te că Apache și MySQL sunt verzi (running)

### 2. Copiază fișierele
Copiază tot conținutul acestui arhivă în:
```
C:\wamp64\www\heavydutyro\
```

### 3. Creare Baza de Date
- Deschide **phpMyAdmin**: http://localhost/phpmyadmin
- Click pe **"SQL"** din bara de sus
- Copiază conținutul fișierului `setup.sql` și apasă **Go**
- Baza de date `heavydutyro` va fi creată cu toate tabelele

### 4. Configurare Conexiune (dacă e necesar)
Editează `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'heavydutyro');
define('DB_USER', 'root');
define('DB_PASS', '');   // <-- pune parola WAMP dacă ai setat una
```

### 5. Testare
Deschide: **http://localhost/heavydutyro/**

---

## Endpoints API

| Metodă | URL | Descriere |
|--------|-----|-----------|
| POST | `/api/auth.php?action=login` | Autentificare |
| POST | `/api/auth.php?action=register` | Înregistrare |
| POST | `/api/auth.php?action=logout` | Deconectare |
| GET | `/api/auth.php?action=me` | Utilizator curent |
| GET | `/api/workouts.php` | Lista antrenamente |
| POST | `/api/workouts.php` | Adaugă antrenament |
| DELETE | `/api/workouts.php?id=X` | Șterge antrenament |
| GET | `/api/user.php?action=profile` | Profil + statistici |
| POST | `/api/user.php?action=stats` | Actualizează statistici |
| POST | `/api/user.php?action=password` | Schimbă parola |
| POST | `/api/user.php?action=bodyfat` | Salvează estimare BF |
| GET | `/api/nutrition.php` | Lista nutriție |
| POST | `/api/nutrition.php` | Salvează nutriție |

---

## Depanare Probleme Frecvente

**"Eroare conexiune baza de date"**
→ Verifică că MySQL rulează în WAMP și că `DB_PASS` în `db.php` e corect.

**"Neautentificat" pe orice pagină**
→ Verifică că `session.use_cookies` e activat în `php.ini` și că accesezi prin `http://localhost/...` nu direct din fișiere.

**Paginile nu găsesc API-ul**
→ Asigură-te că fișierele `api/` sunt în același folder cu HTML-urile.
