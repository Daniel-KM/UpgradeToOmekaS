---
layout: page
title: Correspondance des extensions
lang: fr
order: 7
---

{% include css_js.html %}

Cette liste indique les correspondances entre les plugins [Omeka Classic](https://omeka.org/classic) et les modules [Omeka S](https://omeka.org/s).

{% include fr/intro_extensions.md %}

{% include fr/stats_upgradable.md %}

N’hésitez pas à signaler des plugins et modules manquants ou à créer un script de mise à jour pour le plugin [Upgrade to Omeka S](https://github.com/Daniel-KM/Omeka-plugin-UpgradeToOmekaS) ou un importeur pour le module [Omeka 2 Importer](https://github.com/omeka-s-modules/Omeka2Importer).

Voir davantage de détals sur les [plugins]({{ site.url | append: '/UpgradeToOmekaS/fr/omeka_plugins.html' }}) et sur les [modules]({{ site.url | append: '/UpgradeToOmekaS/fr/omeka_s_modules.html' }}).

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filtre" />
    </div>
    <p><em>
    Tapez quelques lettres pour filtrer la liste. Cliquez sur les en-têtes pour trier. Obtenez le <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_data/omeka_plugins.csv">fichier source en csv</a>, mis à jour une fois par semaine. Les dépôts dérivés ne sont pas affichés, sauf lorsqu’ils ajoutent de nouvelles fonctionnalités.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-plugin-link">Plugin</span></th>
                    <th><span class="sort" data-sort="addon-account">Auteur</span></th>
                    <th><span class="sort" data-sort="addon-updated">Mis à jour</span></th>
                    <th><span class="sort" data-sort="addon-version">Dernier</span></th>
                    <th><span class="sort" data-sort="addon-upgradable">Omeka S</span></th>
                    <th><span class="sort" data-sort="addon-minimum">Min</span></th>
                    <th><span class="sort" data-sort="addon-maximum">Max</span></th>
                    <th><span class="sort" data-sort="addon-module-link">Module</span></th>
                    <th><span class="sort" data-sort="addon-note">Note</span></th>
                </tr>
            </thead>
            {% include omeka_mapping_table_body.md %}
        </table>
    </div>
</div>
</div>

{% include omeka_mapping_script.html %}
