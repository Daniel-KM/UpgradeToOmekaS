---
layout: page
title: Omeka S Themes
order: 5
---

{% include css_js.html %}

This list brings together all the existing [Omeka S](https://omeka.org/s) themes.
They can be downloaded freely on <https://github.com> or <https://gitlab.com>. Some of them are listed in <https://omeka.org/add-ons>.

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
                            {% if addon['Author link'] != nil %}
                                <a href="{{ addon['Author link'] }}" class="link addon-author">{{ addon['Author'] | xml_escape }}</a>
                            {% else %}
                                {{ addon['Author'] }}
                            {% endif %}
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
        valueNames: ['addon-link', 'addon-author', 'addon-updated', 'addon-omeka-org', 'addon-constraint', 'addon-license', 'addon-tags', 'addon-description', 'addon-downloads'],
        page: 500
    };
    var entryList = new List('entry-list', options);
    // entryList.sort('addon-updated', { order: "desc" });
</script>
