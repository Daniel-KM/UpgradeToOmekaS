---
layout: page
title: Sélections Omeka S
lang: fr
order: 6
---

{% include css_js.html %}

Ces sélections de modules et de thèmes peuvent être installées via le module [Easy Admin](https://gitlab.com/Daniel-KM/Omeka-S-module-EasyAdmin). Les nouvelles suggestions de sélections sont les bienvenues !

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filtre" />
    </div>
    <p><em>
    Tapez quelques lettres pour filtrer la liste. Cliquez sur les en-têtes pour trier. Obtenez le <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_data/omeka_s_selections.csv">fichier source en csv</a>.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-name">Nom</span></th>
                    <th><span class="sort" data-sort="addon-author">Auteur</span></th>
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                    <th><span class="sort" data-sort="addon-updated">Mis à jour</span></th>
                    <th><span class="sort" data-sort="addon-addons">Modules et thèmes</span></th>
                </tr>
            </thead>
            {% include omeka_s_selections_table_body.md %}
        </table>
    </div>
</div>
</div>

{% include omeka_s_selections_script.html %}
