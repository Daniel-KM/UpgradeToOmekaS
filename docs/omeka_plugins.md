---
layout: page
title: All Omeka Classic Plugins
---

{% assign total_plugins = 0 %}
{% assign total_matchings = 0 %}
{% assign total_upgraders = 0 %}
{% assign total_upgradables = 0 %}
{% for plugin in site.data.omeka_plugins %}
    {% unless plugin['Plugin'] == nil %}
        {% assign total_plugins = total_plugins | plus: 1 %}
        {% unless plugin['Module'] == nil %}
            {% assign total_matchings = total_matchings | plus: 1 %}
        {% endunless %}
        {% if plugin['Upgradable'] == 'Yes' %}
            {% assign total_upgradables = total_upgradables | plus: 1 %}
        {% endif %}
        {% if plugin['Upgradable'] == 'Yes (auto)' %}
            {% assign total_upgraders = total_upgraders | plus: 1 %}
        {% endif %}
    {% endunless %}
{% endfor %}


<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<script src="//code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/list.js/1.5.0/list.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

<style media="screen" type="text/css">
    .sort { cursor: pointer; }
    .page-content .wrapper { max-width: inherit; }
    .page-content .wrapper .post-header,
    .page-content .wrapper .post-content p { max-width: calc(800px - 30px * 2); margin-left: auto; margin-right: auto; padding-left: 30px: padding-right: 30px; }
    .page-content .wrapper .post-content .container-fluid { max-width: inherit; }
</style>


All plugins can be downloaded freely on <https://github.com> or <https://gitlab.com>. Some of them are old, broken or unsupported. Usually, they work at least on one site. But most of them are up-to-date for [Omeka Classic] and useful. Only a part of them are listed in <https://omeka.org/add-ons/plugins>.

{% if total_plugins > 0 %}
Already {{ total_matchings }} / {{ total_plugins }} (<strong>{{ total_matchings | times: 100 | divided_by: total_plugins | round }}%</strong>) plugins – the most used ones – have an equivalent module for [Omeka S], and {{ total_upgraders }} automatic upgraders are available. See the page of [matching extensions]({{ site.url | append: '/UpgradeToOmekaS' }}).
{% endif; %}

Feel free to add missing plugins, to update versions or to create an upgrader processor for the plugin [Upgrade To Omeka S], or contact me.

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
    </div>
    <p><em>
    Type some letters to filter the list. Click on row headers to sort. Get the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/docs/_data/omeka_plugins.csv">csv source file</a>.
    </em></p>
    <div class="row">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><span class="sort" data-sort="plugin-link">Plugin</span></th>
                    <th><span class="sort" data-sort="plugin-author">Author</span></th>
                    <th><span class="sort" data-sort="plugin-version">Last</span></th>
                    <th><span class="sort" data-sort="plugin-omeka-org">Omeka.org</span></th>
                    <th><span class="sort" data-sort="plugin-upgradable">Upgradable</span></th>
                    <th><span class="sort" data-sort="plugin-target">Target</span></th>
                    <th><span class="sort" data-sort="plugin-license">License</span></th>
                    <th><span class="sort" data-sort="plugin-tags">Tags</span></th>
                    <!--
                    <th><span class="sort" data-sort="plugin-required">Required plugins</span></th>
                    <th><span class="sort" data-sort="plugin-optional">Optional plugins</span></th>
                    -->
                    <th><span class="sort" data-sort="plugin-description">Description</span></th>
                </tr>
            </thead>
            <tbody class="list">
            {% for plugin in site.data.omeka_plugins %}
                {% if plugin['Plugin'] %}
                <tr>
                    <td>
                    {% unless plugin['Plugin'] == nil %}
                        <a href="{{ plugin['Plugin Url'] }}" class="link plugin-link">{{ plugin['Plugin'] }}</a>
                    {% endunless %}
                    </td>
                    <td>
                    {% unless plugin['Plugin'] == nil %}
                        {% assign account_name = plugin['Plugin Url'] | remove: 'https://github.com/' | remove: 'https://gitlab.com/' | split: '/' | first %}
                        {% assign account_url = plugin['Plugin Url'] | split: account_name | first | append: account_name %}
                        <a href="{{ account_url }}" class="link plugin-author">{{ plugin['Author'] }}</a>
                    {% endunless %}
                    </td>
                    <td class="plugin-version">{{ plugin['Last'] }}</td>
                    <td class="plugin-omeka-org">{{ plugin['Omeka.org'] }}</td>
                    <td class="plugin-upgradable">{{ plugin['Upgradable'] }}</td>
                    <td class="plugin-target">
                    {% if plugin['Plugin'] == nil %}
                        {{ plugin['Omeka Target'] }}
                    {% else %}
                        {{ plugin['Omeka Min'] }}
                    {% endif %}
                    </td>
                    <td class="plugin-license">{{ plugin['License'] }}</td>
                    <td class="plugin-tags">{{ plugin['Tags'] | replace: ',', ',<br />' }}</td>
                    <!--
                    <td class="plugin-required">{{ plugin['Required Plugins'] | replace: ',', ',<br />' }}</td>
                    <td class="plugin-required">{{ plugin['Optional Plugins'] | replace: ',', ',<br />' }}</td>
                    -->
                    <td class="plugin-description">{{ plugin['Description'] }}</td>
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
        valueNames: ['plugin-link', 'plugin-author', 'plugin-version', 'plugin-omeka-org', 'plugin-upgradable', 'plugin-target', 'plugin-license', 'plugin-tags', 'plugin-required', 'plugin-optional', 'plugin-description'],
        page: 500
    };
    var entryList = new List('entry-list', options);
</script>


[Upgrade To Omeka S]: https://github.com/Daniel-KM/UpgradeToOmekaS
[Omeka Classic]: https://omeka.org
[Omeka S]: https://omeka.org/s
