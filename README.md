# ROBLIB – Roboterbibliothek

## Dateien

```
roblib/
├── index.php        Öffentliche Bibliotheksseite
├── manage.php       Upload/Verwaltung (passwortgeschützt)
├── api.php          REST-API für RobModel + RobSimul
├── config.php       Zugangsdaten und Pfade  ← HIER ANPASSEN
├── functions.php    Interne Hilfsfunktionen
├── .htaccess        Sicherheitsregeln
├── data/
│   └── robots.json  Metadaten (automatisch angelegt)
├── robots/          ZIP-Dateien der Roboter
└── thumbs/          Vorschaubilder
```

## Setup

1. Alle Dateien nach `cnc-technik.de/roblib/` hochladen
2. `config.php` anpassen: Zugangsdaten + BASE_URL
3. Verzeichnisse `data/`, `robots/`, `thumbs/` schreibbar machen:
   ```
   chmod 755 data robots thumbs
   ```
4. `data/robots.json` wird automatisch erstellt

---

## API-Dokumentation

### Liste abrufen (RobSimul)
```
GET https://cnc-technik.de/roblib/api.php
GET https://cnc-technik.de/roblib/api.php?action=list
```
Antwort: JSON mit allen Robotern + Download-URLs

### ZIP herunterladen (RobSimul)
```
GET https://cnc-technik.de/roblib/api.php?action=download&id=ROBOT_ID
```
Gibt die ZIP-Datei direkt zurück.

### Roboter hochladen (RobModel)
```
POST https://cnc-technik.de/roblib/api.php?action=upload
Content-Type: multipart/form-data

Felder:
  user                     Benutzername
  pass                     Passwort
  zip                      ZIP-Datei (Pflicht)
  thumb                    Vorschaubild PNG/JPG (optional)
  name                     z.B. "KUKA KR8 R1420 HW"
  marke                    z.B. "KUKA"
  modell                   z.B. "KR8 R1420"
  achsen                   z.B. "6"
  reichweite_mm            z.B. "1420"
  nutzlast_kg              z.B. "8"
  gewicht_kg               z.B. "235"
  wiederholgenauigkeit_mm  z.B. "0.030"
  beschreibung             optional
```
Antwort: `{"ok": true, "robot": {...}}`

### Roboter löschen (RobModel / Admin)
```
POST https://cnc-technik.de/roblib/api.php?action=delete

Felder:
  user   Benutzername
  pass   Passwort
  id     Roboter-ID
```

---

## RobModel-Integration (Beispiel cURL)

```python
import requests

r = requests.post(
    'https://cnc-technik.de/roblib/api.php?action=upload',
    data={
        'user': 'admin',
        'pass': 'geheim',
        'name': 'KUKA KR8 R1420 HW',
        'marke': 'KUKA',
        'modell': 'KR8 R1420',
        'achsen': 6,
        'reichweite_mm': 1420,
        'nutzlast_kg': 8.0,
        'gewicht_kg': 235.0,
        'wiederholgenauigkeit_mm': 0.030,
    },
    files={
        'zip':   open('robot.zip', 'rb'),
        'thumb': open('robot.png', 'rb'),  # optional
    }
)
print(r.json())
```

## RobSimul-Integration (Beispiel)

```python
import requests, zipfile, io

# Liste laden
robots = requests.get('https://cnc-technik.de/roblib/api.php').json()['robots']

# Ersten Roboter herunterladen
robot_id = robots[0]['id']
r = requests.get(f'https://cnc-technik.de/roblib/api.php?action=download&id={robot_id}')
z = zipfile.ZipFile(io.BytesIO(r.content))
z.extractall('/tmp/robot/')
```
