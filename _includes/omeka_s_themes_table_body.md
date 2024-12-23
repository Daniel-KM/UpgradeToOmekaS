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
