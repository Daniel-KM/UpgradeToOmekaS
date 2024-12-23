---
layout: page
title: Omeka Classic Plugins
lang: en
order: 1
---

{% include css_js.html %}

This list brings together all the existing [Omeka Classic](https://omeka.org/classic) plugins.

{% include en/intro_extensions.md %}

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
    </div>
    <p><em>
    Type some letters to filter the list. Click on row headers to sort. Get the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_data/omeka_plugins.csv">csv source file</a>, updated once a week. Forks are not displayed, except when they add new features.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-link">Plugin</span></th>
                    <th><span class="sort" data-sort="addon-author">Author</span></th>
                    <th><span class="sort" data-sort="addon-updated">Updated</span></th>
                    <th><span class="sort" data-sort="addon-omeka-org">Omeka.org</span></th>
                    <th><span class="sort" data-sort="addon-upgradable">Upgradable</span></th>
                    <th><span class="sort" data-sort="addon-target">Target version</span></th>
                    <th><span class="sort" data-sort="addon-license">License</span></th>
                    <th><span class="sort" data-sort="addon-tags">Tags</span></th>
                    <!--
                    <th><span class="sort" data-sort="addon-required">Required plugins</span></th>
                    <th><span class="sort" data-sort="addon-optional">Optional plugins</span></th>
                    -->
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                    <th><span class="sort" data-sort="addon-downloads" title="Warning: The number of downloads don’t mean popularity. In particular, some addons have no release and some other ones have many releases.">Downloads</span></th>
                </tr>
            </thead>
            {% include omeka_plugins_table_body.md %}
        </table>
    </div>
</div>
</div>

{% include omeka_plugins_script.html %}
