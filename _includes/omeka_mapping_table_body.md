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
        <td class="addon-updated">{{ addon['Last update'] | slice: 0, 10 }}</td>
        <td class="addon-version">
            {% assign version = addon['Last version'] %}
            {% include addon_version.md version=version %}
        </td>
        <td class="addon-upgradable">{{ addon['Upgradable'] }}</td>
        <td class="addon-minimum">{{ addon['Min version'] }}</td>
        <td class="addon-maximum">{{ addon['Max version'] }}</td>
        <td>
        {% if addon['Module url'] == nil %}
            <span class="module-link"><em>{{ addon['Module'] }}</em></span>
        {% else %}
            <a href="{{ addon['Module url'] }}" class="link addon-module-link">{{ addon['Module'] }}</a>
        {% endif %}
        </td>
        <td class="addon-note">{{ addon['Note'] | xml_escape }}</td>
    </tr>
{% endfor %}
</tbody>
