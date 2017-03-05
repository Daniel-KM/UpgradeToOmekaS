---
layout: page
title: Matching extensions
order: 1
---

{% include css_js.html %}

All plugins can be downloaded freely on <https://github.com> or <https://gitlab.com>. Some of them are old, broken or unsupported. Usually, they work at least on one site. But most of them are up-to-date for [Omeka Classic] and useful. Only a part of them are listed in <https://omeka.org/add-ons/plugins>.

{% include stats_upgradable.md %}

See more details on [plugins]({{ site.url | append: '/UpgradeToOmekaS/omeka_plugins.html' }}) and [modules]({{ site.url | append: '/UpgradeToOmekaS/omeka_s_modules.html' }}).

Feel free to add missing plugins, or to create an upgrader processor for the plugin [Upgrade to Omeka S], or contact me.

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
    </div>
    <p><em>
    Type "yes" to filter only upgradable plugins. Click on row headers to sort. Get the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/docs/_data/omeka_plugins.csv">csv source file</a>.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="addon-plugin-link">Plugin</span></th>
                    <th><span class="sort" data-sort="addon-account">Account</span></th>
                    <th><span class="sort" data-sort="addon-version">Last</span></th>
                    <th><span class="sort" data-sort="addon-upgradable">Upgradable</span></th>
                    <th><span class="sort" data-sort="addon-minimum">Min</span></th>
                    <th><span class="sort" data-sort="addon-maximum">Max</span></th>
                    <th><span class="sort" data-sort="addon-module-link">Module</span></th>
                    <th><span class="sort" data-sort="addon-note">Note</span></th>
                </tr>
            </thead>
            <tbody class="list">
            {% for addon in site.data.omeka_plugins %}
                <tr>
                    <td>
                    {% unless addon['Name'] == nil %}
                        <a href="{{ addon['Url'] }}" class="link addon-plugin-link">{{ addon['Name'] }}</a>
                    {% endunless %}
                    </td>
                    <td>
                    {% unless addon['Name'] == nil %}
                        {% assign account_name = addon['Url'] | remove: 'https://github.com/' | remove: 'https://gitlab.com/' | split: '/' | first %}
                        {% assign account_url = addon['Url'] | split: account_name | first | append: account_name %}
                        <a href="{{ account_url }}" class="link addon-account">{{ account_name }}</a>
                    {% endunless %}
                    </td>
                    <td class="addon-version">{{ addon['Last'] }}</td>
                    <td class="addon-upgradable">{{ addon['Upgradable'] }}</td>
                    <td class="addon-minimum">{{ addon['Min Version'] }}</td>
                    <td class="addon-maximum">{{ addon['Max Version'] }}</td>
                    <td>
                    {% if addon['Module Url'] == nil %}
                        <span class="module-link"><em>{{ addon['Module'] }}</em></span>
                    {% else %}
                        <a href="{{ addon['Module Url'] }}" class="link addon-module-link">{{ addon['Module'] }}</a>
                    {% endif %}
                    </td>
                    <td class="addon-note">{{ addon['Note'] }}</td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    </div>
</div>
</div>

<script type="text/javascript">
    var options = {
        valueNames: ['addon-plugin-link', 'addon-account', 'addon-version', 'addon-upgradable', 'addon-minimum', 'addon-maximum', 'addon-module-link', 'addon-note'],
        page: 500
    };
    var entryList = new List('entry-list', options);
</script>


[Upgrade to Omeka S]: https://github.com/Daniel-KM/UpgradeToOmekaS
[Omeka Classic]: https://omeka.org
[Omeka S]: https://omeka.org/s
