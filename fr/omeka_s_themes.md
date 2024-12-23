---
layout: page
title: Thèmes Omeka S
lang: fr
order: 5
---

{% include css_js.html %}

Cette liste rassemble tous les thèmes existants pour [Omeka S](https://omeka.org/s).
Ils peuvent être téléchargés librement sur [omeka.org](https://omeka.org/s/themes), [github.com](https://github.com) ou [gitlab.com](https://gitlab.com).
N’hésitez pas à signaler des thèmes manquants.

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filtre" />
    </div>
    <p><em>
    Tapez quelques lettres pour filtrer la liste. Cliquez sur les en-têtes pour trier. Obtenez le <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_data/omeka_s_themes.csv">fichier source en csv</a>, mis à jour une fois par semaine. Les dépôts dérivés ne sont pas affichés.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-link">Module</span></th>
                    <th><span class="sort" data-sort="addon-author">Auteur</span></th>
                    <th><span class="sort" data-sort="addon-updated">Mis à jour</span></th>
                    <th><span class="sort" data-sort="addon-license">Licence</span></th>
                    <th><span class="sort" data-sort="addon-tags">Mots-clés</span></th>
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                    <th><span class="sort" data-sort="addon-downloads" title="Attention : le nombre de téléchargements ne correspond pas à la popularité. En particulier, certaines extensions n’ont pas de version et d’autres ont de nombreuses versions.">Téléchargements</span></th>
                </tr>
            </thead>
            {% include omeka_s_themes_table_body.md %}
        </table>
    </div>
</div>
</div>

{% include omeka_s_themes_script.html %}
