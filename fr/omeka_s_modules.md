---
layout: page
title: Modules Omeka S
lang: fr
order: 4
---

{% include css_js.html %}

Cette liste rassemble tous les modules existants pour [Omeka S](https://omeka.org/s).

{% include fr/intro_extensions.md %}

<div class="container-fluid">
<div id="entry-list">
    <div class="row addon-filters" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filtre" />
        {% include addon_stats_filter.html %}
    </div>
    <p><em>
    Tapez quelques lettres pour filtrer la liste. Cliquez sur les en-têtes pour trier. Obtenez le <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_data/omeka_s_modules.csv">fichier source en csv</a>, mis à jour une fois par semaine. Les dépôts dérivés ne sont pas affichés, sauf lorsqu’ils ajoutent de nouvelles fonctionnalités.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-link">Module</span></th>
                    <th><span class="sort" data-sort="addon-author">Auteur</span></th>
                    <th><span class="sort" data-sort="addon-updated">Mis à jour</span></th>
                    <th><span class="sort" data-sort="addon-target">Omeka S</span></th>
                    <th><span class="sort" data-sort="addon-tags">Mots-clés</span></th>
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                    {% include addon_stats_header.html %}
                </tr>
            </thead>
            {% include omeka_s_modules_table_body.md %}
        </table>
    </div>
</div>
</div>

{% include omeka_s_modules_script.html %}
