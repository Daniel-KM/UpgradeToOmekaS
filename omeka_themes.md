---
layout: page
title: Omeka Themes
order: 3
---

{% include css_js.html %}

This list brings together all the existing [Omeka Classic](https://omeka.org/classic) themes.
They can be downloaded freely on [github.com](https://github.com) or [gitlab.com](https://gitlab.com).
Feel free to add missing themes.

<div class="container-fluid">
<div id="entry-list">
    <div class="row" style="margin-bottom:10px;">
        <input type="text" class="search form-control" placeholder="Filter" />
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
                    <th><span class="sort" data-sort="addon-omeka-org">Omeka.org</span></th>
                    <th><span class="sort" data-sort="addon-license">License</span></th>
                    <th><span class="sort" data-sort="addon-tags">Tags</span></th>
                    <th><span class="sort" data-sort="addon-description">Description</span></th>
                    <th><span class="sort" data-sort="addon-downloads" title="Warning: The number of downloads don’t mean popularity. In particular, some addons have no release and some other ones have many releases.">Downloads</span></th>
                </tr>
            </thead>
            <tbody class="list">
            {% for addon in site.data.omeka_themes %}
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
                    <td class="addon-license">{{ addon['License'] | xml_escape }}</td>
                    <td class="addon-tags">{{ addon['Tags'] | replace: ',', ',<br />' }}</td>
                    <td class="addon-description">{{ addon['Description'] | xml_escape }}</td>
                    <td class="addon-downloads">
                        {% if addon['Total downloads'] %}
                            {{ addon['Total downloads'] }}
                            {% if addon['Count versions'] == '1' %}
                                <br/>
                                ({{ addon['Count versions'] }} version)
                            {% elsif addon['Count versions'] %}
                                <br/>
                                ({{ addon['Count versions'] }} versions)
                            {% endif %}
                        {% endif %}
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
        valueNames: ['addon-link', 'addon-author', 'addon-updated', 'addon-omeka-org', 'addon-license', 'addon-tags', 'addon-description', 'addon-downloads'],
        page: 500
    };
    var entryList = new List('entry-list', options);
    // entryList.sort('addon-updated', { order: "desc" });
</script>
