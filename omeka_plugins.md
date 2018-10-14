---
layout: page
title: Omeka Plugins
order: 2
---

{% include css_js.html %}

This list brings together all the existing [Omeka Classic](https://omeka.org/classic) plugins.

{% include intro_extensions.md %}

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
    </div>
    <p><em>
    Type some letters to filter the list. Click on row headers to sort. Get the <a href="https://github.com/Daniel-KM/UpgradeToOmekaS/blob/master/_data/omeka_plugins.csv">csv source file</a>, updated once a week. Forks are not displayed, except when they add new features.
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
                            <a href="{{ account_url }}" class="link addon-author">{{ addon['Author'] | xml_escape }}</a>
                        {% endunless %}
                    {% endunless %}
                    </td>
                    <td class="addon-updated">
                        {% if addon['Last update'] %}
                            {{ addon['Last update'] | slice: 0, 10 }}
                        {% endif %}
                        {% if addon['Last version'] and addon['Last version'] != '' %}
                             <br/>
                            {% assign version = addon['Last version'] %}
                            (v. {%- include addon_version.md version=version -%})
                        {% endif %}
                    </td>
                    <td class="addon-omeka-org">{{ addon['Omeka.org'] }}</td>
                    <td class="addon-upgradable">{{ addon['Upgradable'] }}</td>
                    <td class="addon-target">
                    {% if addon['Omeka target'] %}
                        {{ addon['Omeka target'] }}
                    {% else %}
                        {{ addon['Omeka min'] }}
                    {% endif %}
                    </td>
                    <td class="addon-license">{{ addon['License'] | xml_escape }}</td>
                    <td class="addon-tags">{{ addon['Tags'] | replace: ',', ',<br />' }}</td>
                    <!--
                    <td class="addon-required">{{ addon['Required plugins'] | replace: ',', ',<br />' }}</td>
                    <td class="addon-required">{{ addon['Optional plugins'] | replace: ',', ',<br />' }}</td>
                    -->
                    <td class="addon-description">{{ addon['Description']  | xml_escape }}</td>
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
