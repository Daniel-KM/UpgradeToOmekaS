---
layout: page
title: Plugins for Omeka 2 and matching modules for Omeka S
---

{% assign total_plugins = 0 %}
{% assign total_matchings = 0 %}
{% assign total_upgraders = 0 %}
{% for plugin in site.data.omeka_plugins %}
    {% unless plugin['Plugin'] == nil %}
        {% assign total_plugins = total_plugins | plus: 1 %}
        {% unless plugin['Module'] == nil %}
            {% assign total_matchings = total_matchings | plus: 1 %}
        {% endunless %}
        {% if plugin['Upgrader'] == 'Yes' %}
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
</style>


All plugins can be downloaded freely on <https://github.com> or <https://gitlab.com>. Some of them are old, broken or unsupported. Usually, they work at least on one site. But most of them are up-to-date for [Omeka Classic] and useful. Only a part of them are listed in <https://omeka.org/add-ons/plugins>.

{% if total_plugins > 0 %}
Already {{ total_matchings }} / {{ total_plugins }} (<strong>{{ total_matchings | times: 100 | divided_by: total_plugins | round }}%</strong>) plugins – the most used ones – have an equivalent module for [Omeka S], and {{ total_upgraders }} automatic upgraders are available.
{% endif; %}

Feel free to add missing plugins, to update versions or to create an upgrader processor for the plugin [Upgrade To Omeka S], or contact me.

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
                    <th><span class="sort" data-sort="plugin-link">Plugin</span></th>
                    <th><span class="sort" data-sort="plugin-account">Account</span></th>
                    <th><span class="sort" data-sort="plugin-version">Current</span></th>
                    <th><span class="sort" data-sort="plugin-upgrader">Upgrader</span></th>
                    <th><span class="sort" data-sort="plugin-minimum">Min</span></th>
                    <th><span class="sort" data-sort="plugin-maximum">Max</span></th>
                    <th><span class="sort" data-sort="module-link">Module</span></th>
                    <th><span class="sort" data-sort="plugin-note">Note</span></th>
                </tr>
            </thead>
            <tbody class="list">
            {% for plugin in site.data.omeka_plugins %}
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
                        <a href="{{ account_url }}" class="link plugin-account">{{ account_name }}</a>
                    {% endunless %}
                    </td>
                    <td class="plugin-version">
                    {% unless plugin['Plugin'] == nil %}
                        {{ plugin['Current Version'] }}
                    {% endunless %}
                    </td>
                    <td class="plugin-upgrader">{{ plugin['Upgrader'] }}</td>
                    <td class="plugin-minimum">{{ plugin['Min Version'] }}</td>
                    <td class="plugin-maximum">{{ plugin['Max Version'] }}</td>
                    <td>
                    {% if plugin['Module Url'] == nil %}
                        <span class="module-link"><em>{{ plugin['Module'] }}</em></span>
                    {% else %}
                        <a href="{{ plugin['Module Url'] }}" class="link module-link">{{ plugin['Module'] }}</a>
                    {% endif %}
                    </td>
                    <td class="plugin-note">{{ plugin['Note'] }}</td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    </div>
</div>
</div>

<script type="text/javascript">
    var options = {
        valueNames: ['plugin-link', 'plugin-account', 'plugin-version', 'plugin-upgrader', 'plugin-minimum', 'plugin-maximum', 'module-link', 'plugin-note'],
        page: 500
    };
    var entryList = new List('entry-list', options);
</script>


[Upgrade To Omeka S]: https://github.com/Daniel-KM/UpgradeToOmekaS
[Omeka Classic]: https://omeka.org
[Omeka S]: https://omeka.org/s
