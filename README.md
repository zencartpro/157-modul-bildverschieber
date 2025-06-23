# 157-modul-bildverschieber
Bilder verschieben für Zen Cart 1.5.7 deutsch

Hinweis: 
Freigegebene getestete Versionen für den Einsatz in Livesystemen ausschließlich unter Releases herunterladen:
* https://github.com/zencartpro/157-modul-bildverschieber/releases

Wenn in einem Zen Cart Shop mit vielen Artikeln und daher auch vielen Artikelbildern, die Bilddateien der Artikel immer direkt ins images Verzeichnis gelegt werden, dann ist das ein Performancekiller und der Shop wird immer langsamer werden, je mehr Artikel dazukommen.

* Für die Artikelbilder sollten immer Unterverzeichnisse im Ordner images angelegt und verwendet werden. Dadurch müssen in den Artikellisten nicht immer alle Bilddateien im Ordner images durchsucht werden, da die Bilder nun auf mehrere Unterordner verteilt sind und nur dort gesucht werden.
Das verbessert die Performance in Shops mit vielen Artikeln ganz massiv.

* Hat man bisher seine Artikelbilder nicht so strukturiert und will das jetzt ändern, wäre das ein hoher Aufwand, da ja nicht nur die Bilder einfach per FTP in einen anderen Ordner verschoben werden müssen, sondern bei jedem Artikel der Pfad zum Artikelbild in der Datenbank geändert werden muss.

* Diese Arbeit kann man mit wenigen Mausclicks mit dem Bildverschieber Tool erledigen lassen.
* Via Zen Cart Administration kann für jede Artikelkategorie ein Zielordner (den man vorher per FTP unter images angelegt hat) gewählt werden. Das Tool verschiebt dann die Bilddateien und ändert in der Tabelle products das Feld products_image auf den neuen Pfad ab.

# Installation

1)
Im Shopverzeichnis am Server im Ordner images per FTP die neuen Unterordner anlegen.
Benannt wie es für Sie am besten passt und Sinn macht. Das können Kategorienamen oder Herstellernamen sein. 
Verwenden Sie in Shops mit vielen Artikeln viele Unterordner, damit nicht wieder in einem Unterordner extrem viele Bilddateien liegen.
Verwenden Sie in den Ordnernamen keine Umlaute, Leerzeichen oder Sonderzeichen.

2)
Im Ordner NEUE DATEIEN dieses Downloads den Ordner DEINADMIN auf den Namen Ihres Adminverzeichnisses umbenennen.
Danach die Dateien/Ordner in der vorgegebenen Struktur ins Shopverzeichnis hochladen, dabei werden keine bestehenden Dateien überschrieben

3)
In der Zen Cart Administration auf irgendeinen Menüpunkt clicken, danach ist unter Tools der neue Menüpunkt Bildverschieber vorhanden.
Sehr einfach zu bedienen, die Funktionsweise ist in der Dokumentation.htm beschrieben

# Copyright 
2025 harryg

