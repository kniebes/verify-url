# kniebes/verify-url

Prüft, ob eine URL noch erreichbar ist und brauchbaren Inhalt liefert. Ist sie tot oder inzwischen von Spam übernommen, sucht die Bibliothek in der Wayback Machine nach einem archivierten Snapshot als Ersatz.

## Voraussetzungen

- PHP 8.3 oder neuer
- `ext-dom` (für die Spam-Erkennung)
- `guzzlehttp/guzzle` ^7.9

## Installation

Das Paket liegt nicht auf Packagist, sondern wird direkt aus dem Git-Repository installiert:

```json
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/kniebes/verify-url.git"
        }
    ]
}
```

```bash
composer require kniebes/verify-url:dev-main
```

## Verwendung

```php
use Kniebes\VerifyUrl\VerifyUrl;

$verifyUrl = new VerifyUrl();
$result = $verifyUrl->verify('https://example.com/artikel');

if ($result->isValid() && $result->getReplacement() === null) {
    // URL ist in Ordnung
}

if ($result->getReplacement() !== null) {
    // Original ist kaputt, Snapshot aus der Wayback Machine verwenden
    $archiveUrl = $result->getReplacement();
}

foreach ($result->getErrors() as $error) {
    // Gründe, warum die Original-URL verworfen wurde
}
```

## Ablauf

`VerifyUrl::verify()` arbeitet in drei Schritten:

1. **Abrufen:** Die URL wird per `GET` geladen. Bis zu fünf Weiterleitungen (HTTP und HTTPS) werden verfolgt. Ein Statuscode ab 400 gilt als Fehler.
2. **Inhalt prüfen:** Nur HTML (`text/html`, `application/xhtml+xml` oder fehlender `Content-Type`) wird gelesen, höchstens 5 MB. Danach durchsucht der `SpamDetector` den sichtbaren Text nach Spam-Begriffen.
3. **Ersatz suchen:** Bei Netzwerkfehler, ungültigem Status oder erkanntem Spam wird die Wayback Machine (`archive.org/wayback/available`) nach dem nächstgelegenen Snapshot gefragt.

## Ergebnis

Zurück kommt immer ein `Kniebes\VerifyUrl\Model\Result`, Exceptions werden intern abgefangen.

| Situation | `isValid()` | `getReplacement()` | `getErrors()` |
|---|---|---|---|
| URL erreichbar, kein Spam | `true` | `null` | leer |
| Kein HTML (z. B. PDF, Bild) | `true` | `null` | `Invalid content type …` |
| URL kaputt/Spam, Snapshot gefunden | `false` | Snapshot-URL | Fehlergrund der Original-URL |
| URL kaputt/Spam, kein Snapshot | `false` | `null` | Fehlergrund plus Wayback-Fehler |

Ein gefundener Ersatz ändert nichts an `isValid()`: Die Original-URL bleibt ungültig.

## Spam-Erkennung

Der `SpamDetector` entfernt zunächst `script`, `style`, `noscript`, `svg`, `template` und HTML-Kommentare und zählt im verbleibenden Text folgende Begriffe (ganze Wörter, ohne Groß-/Kleinschreibung):

`casino`, `porn`, `viagra`, `sex`, `slots`, `gambling`, `adult`, `crypto`, `bitcoin`

Als Spam gilt eine Seite, wenn beides zutrifft:

- mindestens 3 verschiedene Begriffe kommen vor, und
- die Trefferdichte liegt bei mindestens 5 Treffern pro 1000 Wörter.

Die Prüfung des Seitentitels ist derzeit deaktiviert.

## HTTP-Client

Alle Anfragen laufen über einen gemeinsamen Guzzle-Client (`Kniebes\VerifyUrl\Tools\Client`) mit diesen Voreinstellungen:

| Option | Wert |
|---|---|
| User-Agent | aktueller Desktop-Chrome |
| Connect-Timeout | 5 s |
| Read-Timeout | 5 s |
| Gesamt-Timeout | 20 s |
| `http_errors` | `false` |

Wer andere Werte braucht, kann den Client vor dem ersten `verify()` neu erzeugen:

```php
use Kniebes\VerifyUrl\Tools\Client;

Client::getClient(timeout: 60, isFresh: true);
```

Anfragen an die Wayback Machine werden auf höchstens eine alle zwei Sekunden gedrosselt, damit archive.org bei Massenprüfungen nicht blockiert.
