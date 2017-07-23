---
layout: page
title: Omeka Plugins
order: 2
---

{% include css_js.html %}

All plugins can be downloaded freely on <https://github.com> or <https://gitlab.com>. Some of them are old, broken or unsupported. Usually, they work at least on one site. But most of them are up-to-date for [Omeka Classic] and useful. Only a part of them are listed in <https://omeka.org/add-ons/plugins>.

{% include stats_upgradable.md %}

See the page of [matching extensions]({{ site.url | append: '/UpgradeToOmekaS' }}) to check the upgradable ones and [themes]({{ site.url | append: '/UpgradeToOmekaS/omeka_themes.html' }}).

Feel free to add missing plugins, or to create an upgrader processor for the plugin [Upgrade to Omeka S], or contact me.

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
    </div>
    <p><em>
    Type some letters to filter the list. Click on row headers to sort. Get the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/docs/_data/omeka_plugins.csv">csv source file</a>, updated once a week. Forks are not displayed.
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
                    <th><span class="sort" data-sort="addon-target">Target</span></th>
                    <th><span class="sort" data-sort="addon-license">License</span></th>
                    <th><span class="sort" data-sort="addon-tags">Tags</span></th>
                    <!--
                    <th><span class="sort" data-sort="addon-required">Required plugins</span></th>
                    <th><span class="sort" data-sort="addon-optional">Optional plugins</span></th>
                    -->
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                </tr>
            </thead>
            <tbody class="list">
            {% for addon in site.data.omeka_plugins %}
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
                            {% assign account_name = addon['Url'] | remove: 'https://github.com/' | remove: 'https://gitlab.com/' | split: '/' | first %}
                            {% assign account_url = addon['Url'] | split: account_name | first | append: account_name %}
                            <a href="{{ account_url }}" class="link addon-author">{{ addon['Author'] }}</a>
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
                    <td class="addon-omeka-org">{{ addon['Omeka.org'] }}</td>
                    <td class="addon-upgradable">{{ addon['Upgradable'] }}</td>
                    <td class="addon-target">
                    {% if addon['Omeka Target'] %}
                        {{ addon['Omeka Target'] }}
                    {% else %}
                        {{ addon['Omeka Min'] }}
                    {% endif %}
                    </td>
                    <td class="addon-license">{{ addon['License'] }}</td>
                    <td class="addon-tags">{{ addon['Tags'] | replace: ',', ',<br />' }}</td>
                    <!--
                    <td class="addon-required">{{ addon['Required Plugins'] | replace: ',', ',<br />' }}</td>
                    <td class="addon-required">{{ addon['Optional Plugins'] | replace: ',', ',<br />' }}</td>
                    -->
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
        valueNames: ['addon-link', 'addon-author', 'addon-updated', 'addon-omeka-org', 'addon-upgradable', 'addon-target', 'addon-license', 'addon-tags', 'addon-required', 'addon-optional', 'addon-description'],
        page: 500
    };
    var entryList = new List('entry-list', options);
    entryList.sort('addon-updated', { order: "desc" });
</script>


[Upgrade to Omeka S]: https://github.com/Daniel-KM/UpgradeToOmekaS
[Omeka Classic]: https://omeka.org
[Omeka S]: https://omeka.org/s
