---
layout: page
title: Omeka S Themes
lang: en
order: 5
---

{% include css_js.html %}

This list brings together all the existing [Omeka S](https://omeka.org/s) themes.
They can be downloaded freely on [omeka.org](https://omeka.org/s/themes), [github.com](https://github.com) or [gitlab.com](https://gitlab.com).
Feel free to add missing themes.

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
    </div>
    <p><em>
    Type some letters to filter the list. Click on row headers to sort. Get the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_data/omeka_s_themes.csv">csv source file</a>, updated once a week. Forks are not displayed.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-link">Module</span></th>
                    <th><span class="sort" data-sort="addon-author">Author</span></th>
                    <th><span class="sort" data-sort="addon-updated">Updated</span></th>
                    <th><span class="sort" data-sort="addon-license">License</span></th>
                    <th><span class="sort" data-sort="addon-tags">Tags</span></th>
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                    <th><span class="sort" data-sort="addon-downloads" title="Warning: The number of downloads don’t mean popularity. In particular, some addons have no release and some other ones have many releases.">Downloads</span></th>
                </tr>
            </thead>
            {% include omeka_s_themes_table_body.md %}
        </table>
    </div>
</div>
</div>

{% include omeka_s_themes_script.html %}
