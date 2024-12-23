---
layout: page
title: Mapping extensions
lang: en
order: 7
---

{% include css_js.html %}

This list is a mapping between all [Omeka Classic](https://omeka.org/classic) plugins and some [Omeka S](https://omeka.org/s) modules.

{% include en/intro_extensions.md %}

{% include en/stats_upgradable.md %}

Feel free to add missing plugins or modules, or to create an upgrader for the plugin [Upgrade to Omeka S](https://github.com/Daniel-KM/Omeka-plugin-UpgradeToOmekaS), or an importer for the module [Omeka 2 Importer](https://github.com/omeka-s-modules/Omeka2Importer).

See more details on [plugins]({{ site.baseurl | append: '/en/omeka_plugins.html' }}) and [modules]({{ site.baseurl | append: '/en/omeka_s_modules.html' }}).

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
    </div>
    <p><em>
    Type "yes" to filter only upgradable plugins. Click on row headers to sort. Get the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_data/omeka_plugins.csv">csv source file</a>, updated once a week. Forks are not displayed, except when they add new features.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-plugin-link">Plugin</span></th>
                    <th><span class="sort" data-sort="addon-account">Author</span></th>
                    <th><span class="sort" data-sort="addon-updated">Updated</span></th>
                    <th><span class="sort" data-sort="addon-version">Last</span></th>
                    <th><span class="sort" data-sort="addon-upgradable">Upgradable</span></th>
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
