---
layout: page
title: Plugins Omeka Classic
lang: fr
order: 1
---

{% include css_js.html %}

Cette liste rassemble tous les plugins existants pour [Omeka Classic](https://omeka.org/classic).

{% include fr/intro_extensions.md %}

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
                    <th><span class="sort" data-sort="addon-link">Plugin</span></th>
                    <th><span class="sort" data-sort="addon-author">Auteur</span></th>
                    <th><span class="sort" data-sort="addon-updated">Mis à jour</span></th>
                    <th><span class="sort" data-sort="addon-omeka-org">Omeka.org</span></th>
                    <th><span class="sort" data-sort="addon-upgradable">Omeka S</span></th>
                    <th><span class="sort" data-sort="addon-target">Version cible</span></th>
                    <th><span class="sort" data-sort="addon-license">Licence</span></th>
                    <th><span class="sort" data-sort="addon-tags">Mots-clés</span></th>
                    <!--
                    <th><span class="sort" data-sort="addon-required">Dépendances</span></th>
                    <th><span class="sort" data-sort="addon-optional">Options</span></th>
                    -->
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                    <th><span class="sort" data-sort="addon-downloads" title="Attention : le nombre de téléchargements ne correspond pas à la popularité. En particulier, certaines extensions n’ont pas de version et d’autres ont de nombreuses versions.">Téléchargements</span></th>
                </tr>
            </thead>
            {% include omeka_plugins_table_body.md %}
        </table>
    </div>
</div>
</div>

{% include omeka_plugins_script.html %}
