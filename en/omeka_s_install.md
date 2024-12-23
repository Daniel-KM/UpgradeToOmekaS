---
layout: page
title: Omeka S Easy Install
lang: en
order: 1
---

{% include css_js.html %}

## Step 1: Copy the script to the server

The file can be copied either via ftp or the web host’s web interface, or via the server’s command line.
In both cases, the folder must be writeable by the web server.

### Via ftp or the web host’s web interface

Copy this <a href="https://raw.githubusercontent.com/Daniel-KM/UpgradeToOmekaS/refs/heads/master/_scripts/install_omeka_s.php" download="install_omeka_s.php" target="_self">file</a> to the root of the folder in which you wish to install Omeka S.
In the event of a security alert, right-click to download the file. You can also view the contents of the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_scripts/install_omeka_s.php" target="_blank" rel="noopener">file on Github</a> and download it manually using the "Download raw file" button.

### Via the command line on the server

```sh
# Go to the root of the web folder where you want to install Omeka
cd /var/www/html
# Download the file into the folder via php
php -r 'file_put_contents("install_omeka_s.php", file_get_contents("https://raw.githubusercontent.com/Daniel-KM/UpgradeToOmekaS/refs/heads/master/_scripts/install_omeka_s.php"));'
```
If the command returns an error, Omeka cannot be installed: php is not available on the server; there is a problem with rights to the folder; the network is not accessible.

## Step 2: Go to the script page

Open your browser to the address corresponding to this file, for example:
- `https://example.org/install_omeka_s.php` (change the domain)
- `http://127.0.0.1/install_omeka_s.php` (indicate the ip of your server instead of 127.0.0.1)

Don’t forget to add the path if the installation is in a sub-folder:
- `https://example.org/chemin/a/definir/install_omeka_s.php` (change the path)

Finally, follow the instructions!
