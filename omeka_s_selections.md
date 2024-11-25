---
layout: page
title: Omeka S Selections
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
            <tbody class="list">
            {% for selection in site.data.omeka_s_selections %}
                {% if selection['Name'] %}
                <tr>
                    <td class="addon-name">
                        {{ selection['Name'] }}
                    </td>
                    <td class="addon-author">
                        {{ selection['Author'] }}
                    </td>
                    <td class="addon-description">
                        {{ selection['Description'] | xml_escape }}
                    </td>
                    <td class="addon-updated">
                        {{ selection['Last update'] | slice: 0, 10 }}
                    </td>
                    <td class="addon-addons">
                        {{ selection['Modules and themes'] }}
                    </td>
                </tr>
                {% endif %}
            {% endfor %}
            </tbody>
        </table>
    </div>
</div>
</div>

<script type="text/javascript">
    var options = {
        valueNames: ['addon-name', 'addon-author', 'addon-description', 'addon-updated', 'addon-addons'],
        page: 500
    };
    var entryList = new List('entry-list', options);
    // entryList.sort('addon-updated', { order: "desc" });
</script>
