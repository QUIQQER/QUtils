
QUIQQER Utils
========

Hilfs Klassen von und für QUIQQER.
Diese Klassen sind und müssen unabhängig von QUIQQER funktionieren.


Packetname:

    quiqqer/utils


Features
--------

- Datenbank Layer -> einfache Verbindung zu / mit PDO
- QDOM -> DOM ähnliche Klasse für PHP
- PHP QUI Control Klasse
- Request, Security, Math, Sting Utils (Helfer)


Installation
------------

Der Paketname ist: quiqqer/utils


Mitwirken
----------

- Issue Tracker: https://dev.quiqqer.com/quiqqer/qutils/issues
- Source Code: https://dev.quiqqer.com/quiqqer/qutils/tree/master


Support
-------

Falls Sie ein Fehler gefunden haben oder Verbesserungen wünschen,
Dann können Sie gerne an support@pcsg.de eine E-Mail schreiben.


License
-------

LGPL-3.0+


Entwickler
--------

**composer.json**


```javascript
{
    "repositories": [{
        "type": "composer",
        "url": "http://update.quiqqer.com"
    }],

    "require": {
        "quiqqer/utils" : "1.*"
    }
}
```


**PHPUNIT**

```bash
phpunit  -c phpunit/tests.xml
```
