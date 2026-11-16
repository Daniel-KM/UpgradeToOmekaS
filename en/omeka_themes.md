---
layout: page
title: Omeka Classic Themes
lang: en
order: 2
---

{% include css_js.html %}

This list brings together all the existing [Omeka Classic](https://omeka.org/classic) themes.
They can be downloaded freely on [omeka.org](https://omeka.org/classic/themes), [github.com](https://github.com) or [gitlab.com](https://gitlab.com).
Feel free to add missing themes.

<div class="container-fluid">
<div id="entry-list">
    <div class="row addon-filters" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
        {% include addon_stats_filter.html %}
    </div>
    <p><em>
    Type some letters to filter the list. Click on row headers to sort. Get the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_data/omeka_themes.csv">csv source file</a>, updated once a week. Forks are not displayed.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-link">Plugin</span></th>
                    <th><span class="sort" data-sort="addon-author">Author</span></th>
                    <th><span class="sort" data-sort="addon-updated">Updated</span></th>
                    <th><span class="sort" data-sort="addon-target">Omeka</span></th>
                    <th><span class="sort" data-sort="addon-tags">Tags</span></th>
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                    {% include addon_stats_header.html %}
                </tr>
            </thead>
            {% include omeka_themes_table_body.md %}
        </table>
    </div>
</div>
</div>

{% include omeka_themes_script.html %}
