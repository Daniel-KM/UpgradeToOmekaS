---
layout: page
title: Omeka S Selections
lang: en
order: 6
---

{% include css_js.html %}

These curated selections of modules and themes can be installed via the module [Easy Admin](https://gitlab.com/Daniel-KM/Omeka-S-module-EasyAdmin). New suggestions of selections are welcomed!

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
    </div>
    <p><em>
    Type some letters to filter the list. Click on row headers to sort. Get the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_data/omeka_s_selections.csv">csv source file</a>.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-name">Name</span></th>
                    <th><span class="sort" data-sort="addon-author">Author</span></th>
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                    <th><span class="sort" data-sort="addon-updated">Updated</span></th>
                    <th><span class="sort" data-sort="addon-addons">Modules and themes</span></th>
                </tr>
            </thead>
            {% include omeka_s_selections_table_body.md %}
        </table>
    </div>
</div>
</div>

{% include omeka_s_selections_script.html %}
