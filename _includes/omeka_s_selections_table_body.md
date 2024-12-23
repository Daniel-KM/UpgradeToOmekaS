<tbody class="list">
{% for selection in site.data.omeka_s_selections %}
    {% if selection['Name'] %}
    <tr>
        <td class="addon-name">
            {{ selection['Name'] }}
        </td>
        <td class="addon-author">
            {{ selection['Author'] }}
        </td>
        <td class="addon-description">
            {{ selection['Description'] | xml_escape }}
        </td>
        <td class="addon-updated">
            {{ selection['Last update'] | slice: 0, 10 }}
        </td>
        <td class="addon-addons">
            {{ selection['Modules and themes'] }}
        </td>
    </tr>
    {% endif %}
{% endfor %}
</tbody>
