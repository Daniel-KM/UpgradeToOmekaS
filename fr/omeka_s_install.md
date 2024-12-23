---
layout: page
title: Installer facilement Omeka S
lang: fr
order: 7
---

{% include css_js.html %}

## Étape 1 : copier le script sur le serveur

Le fichier peut être copié soit via le ftp ou l’interface web de l’hébergeur, soit via la ligne de commande du serveur.
Dans les deux cas, le dossier doit être accessible en écriture par le serveur web.

### Via ftp ou l’interface web de l’hébergeur

Copier ce <a href="https://raw.githubusercontent.com/Daniel-KM/UpgradeToOmekaS/refs/heads/master/_scripts/install_omeka_s.php" download="install_omeka_s.php" target="_self">fichier</a> à la racine du dossier dans lequel on souhaite installer Omeka S.
En cas d’alerte de sécurité, télécharger le fichier avec un clic droit.
Vous pouvez aussi consulter le contenu du <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_scripts/install_omeka_s.php" target="_blank" rel="noopener">fichier sur Github</a> et le télécharger manuellement avec le bouton « Télécharger le fichier brut ».

### Via la ligne de commande du serveur web

```sh
# Se placer à la racine du dossier web dans lequel on souhaite installer Omeka
cd /var/www/html
# Télécharger le fichier dans le dossier via php
php -r 'file_put_contents("install_omeka_s.php", file_get_contents("https://raw.githubusercontent.com/Daniel-KM/UpgradeToOmekaS/refs/heads/master/_scripts/install_omeka_s.php"));'
```

Si la commande retourne une erreur, Omeka ne pourra pas s’installer : php n’est pas disponible sur le serveur ; il y a un problème de droits sur le dossier ; le réseau n’est pas accessible.

## Étape 2 : se rendre sur la page du script

Ouvrez votre navigateur à l’adresse correspondant à ce fichier, par exemple :
- `https://example.org/install_omeka_s.php` (en modifiant le domaine)
- `http://127.0.0.1/install_omeka_s.php` (en indiquant l’ip de votre serveur à la place de 127.0.0.1)

N’oubliez pas d’ajouter le chemin si l’installation est dans un sous-dossier :
- `https://example.org/chemin/a/definir/install_omeka_s.php` (en modifiant le chemin)

Enfin, suivez les instructions !
