---
layout: page
title: Omeka S Themes
order: 5
---

{% include css_js.html %}

All themes can be downloaded freely on <https://github.com> or <https://gitlab.com>. They are not listed in <https://omeka.org/s> currently. New themes can be built with the template engine [Twig] via the [module twig] too.

See the [modules]({{ site.url | append: '/UpgradeToOmekaS/omeka_s_modules.html' }}).

Feel free to add missing themes, or contact me for new ones.

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
    </div>
    <p><em>
    Type some letters to filter the list. Click on row headers to sort. Get the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/docs/_data/omeka_s_themes.csv">csv source file</a>, updated once a week. Forks are not displayed.
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
                </tr>
            </thead>
            <tbody class="list">
            {% for addon in site.data.omeka_s_themes %}
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
                                <a href="{{ addon['Author Link'] }}" class="link addon-author">{{ addon['Author'] | xml_escape }}</a>
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
                    <td class="addon-license">{{ addon['License'] | xml_escape }}</td>
                    <td class="addon-tags">{{ addon['Tags'] | replace: ',', ',<br />' }}</td>
                    <td class="addon-description">{{ addon['Description'] | xml_escape }}</td>
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
    // entryList.sort('addon-updated', { order: "desc" });
</script>


[Upgrade to Omeka S]: https://github.com/Daniel-KM/UpgradeToOmekaS
[Omeka Classic]: https://omeka.org
[Omeka S]: https://omeka.org/s
[Twig]: http://twig.sensiolabs.org
[module Twig]: https://github.com/digihum/omeka-s-twig
