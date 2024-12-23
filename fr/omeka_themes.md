---
layout: page
title: Thèmes Omeka
lang: fr
order: 3
---

{% include css_js.html %}

Cette liste rassemble tous les thèmes existants pour [Omeka Classic](https://omeka.org/classic).
Ils peuvent être téléchargés librement sur [omeka.org](https://omeka.org/classic/themes), [github.com](https://github.com) ou [gitlab.com](https://gitlab.com).
N’hésitez pas à signaler des thèmes manquants.

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filtre" />
    </div>
    <p><em>
    Tapez quelques lettres pour filtrer la liste. Cliquez sur les en-têtes pour trier. Obtenez le <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_data/omeka_themes.csv">fichier source en csv</a>, mis à jour une fois par semaine. Les dépôts dérivés ne sont pas affichés.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-link">Plugin</span></th>
                    <th><span class="sort" data-sort="addon-author">Author</span></th>
                    <th><span class="sort" data-sort="addon-updated">Updated</span></th>
                    <th><span class="sort" data-sort="addon-omeka-org">Omeka.org</span></th>
                    <th><span class="sort" data-sort="addon-license">Licence</span></th>
                    <th><span class="sort" data-sort="addon-tags">Tags</span></th>
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                    <th><span class="sort" data-sort="addon-downloads" title="Warning: The number of downloads don’t mean popularity. In particular, some addons have no release and some other ones have many releases.">Downloads</span></th>
                </tr>
            </thead>
            {% include omeka_themes_table_body.md %}
        </table>
    </div>
</div>
</div>

{% include omeka_themes_script.html %}
