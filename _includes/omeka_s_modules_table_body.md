<tbody class="list">
{% for addon in site.data.omeka_s_modules %}
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
                    {{ addon['Author'] | xml_escape }}
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
        <td class="addon-constraint">{{ addon['Omeka constraint'] }}</td>
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
        <td class="addon-stats">{% if addon['Stars'] and addon['Stars'] != '0' %}★ {{ addon['Stars'] }}{% endif %}{% if addon['Forks'] and addon['Forks'] != '0' %}{% if addon['Stars'] and addon['Stars'] != '0' %} | {% endif %}⑂ {{ addon['Forks'] }}{% endif %}{% if addon['Watchers'] and addon['Watchers'] != '0' %}{% if addon['Stars'] and addon['Stars'] != '0' or addon['Forks'] and addon['Forks'] != '0' %} | {% endif %}👁 {{ addon['Watchers'] }}{% endif %}{% if addon['Open issues'] and addon['Open issues'] != '0' or addon['Total issues'] and addon['Total issues'] != '0' or addon['Open PRs'] and addon['Open PRs'] != '0' or addon['Total PRs'] and addon['Total PRs'] != '0' %}<br/>{% endif %}{% if addon['Total issues'] and addon['Total issues'] != '0' %}⚠ {{ addon['Open issues'] | default: '0' }}/{{ addon['Total issues'] }}{% endif %}{% if addon['Total PRs'] and addon['Total PRs'] != '0' %}{% if addon['Total issues'] and addon['Total issues'] != '0' %} | {% endif %}PR {{ addon['Open PRs'] | default: '0' }}/{{ addon['Total PRs'] }}{% endif %}</td>
    </tr>
    {% endif %}
{% endfor %}
</tbody>
