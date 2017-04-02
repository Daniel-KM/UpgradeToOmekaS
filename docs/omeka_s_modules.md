---
layout: page
title: Omeka S Modules
order: 4
---

{% include css_js.html %}

All modules can be downloaded freely on <https://github.com> or <https://gitlab.com>. Usually, they work at least on one site. They are not listed in <https://omeka.org/s> currently.

{% include stats_upgradable.md %}

See the page of [matching extensions]({{ site.url | append: '/UpgradeToOmekaS' }}) and [themes]({{ site.url | append: '/UpgradeToOmekaS/omeka_s_themes.html' }}).

Feel free to add missing modules, or contact me for new ones.

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
    </div>
    <p><em>
    Type some letters to filter the list. Click on row headers to sort. Get the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/docs/_data/omeka_s_modules.csv">csv source file</a>.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-link">Module</span></th>
                    <th><span class="sort" data-sort="addon-author">Author</span></th>
                    <th><span class="sort" data-sort="addon-updated">Updated</span></th>
                    <th><span class="sort" data-sort="addon-constraint">Constraint</span></th>
                    <th><span class="sort" data-sort="addon-license">License</span></th>
                    <th><span class="sort" data-sort="addon-tags">Tags</span></th>
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                </tr>
            </thead>
            <tbody class="list">
            {% for addon in site.data.omeka_s_modules %}
                {% if addon['Name'] %}
                <tr>
                    <td>
                    {% unless addon['Name'] == nil %}
                        <a href="{{ addon['Url'] }}" class="link addon-link">{{ addon['Name'] }}</a>
                    {% endunless %}
                    </td>
                    <td>
                    {% unless addon['Name'] == nil %}
                        {% unless addon['Author'] == nil %}
                            {% if addon['Author Link'] != nil %}
                                <a href="{{ addon['Author Link'] }}" class="link addon-author">{{ addon['Author'] }}</a>
                            {% else %}
                                {{ addon['Author'] }}
                            {% endif %}
                        {% endunless %}
                    {% endunless %}
                    </td>
                    <td class="addon-updated">
                        {% if addon['Last Update'] %}
                            {{ addon['Last Update'] | slice: 0, 10 }}
                        {% endif %}
                        {% if addon['Last Version'] %}
                            ({{ addon['Last Version'] }})
                        {% endif %}
                    </td>
                    <td class="addon-constraint">{{ addon['Constraint'] }}</td>
                    <td class="addon-license">{{ addon['License'] }}</td>
                    <td class="addon-tags">{{ addon['Tags'] | replace: ',', ',<br />' }}</td>
                    <td class="addon-description">{{ addon['Description'] }}</td>
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
        valueNames: ['addon-link', 'addon-author', 'addon-updated', 'addon-omeka-org', 'addon-constraint', 'addon-license', 'addon-tags', 'addon-description'],
        page: 500
    };
    var entryList = new List('entry-list', options);
    entryList.sort('addon-updated', { order: "desc" });
</script>


[Upgrade to Omeka S]: https://github.com/Daniel-KM/UpgradeToOmekaS
[Omeka Classic]: https://omeka.org
[Omeka S]: https://omeka.org/s
