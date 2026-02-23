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
        <td class="addon-stats">
            <span class="addon-stats-sort" style="display:none">
                {{- addon['Total downloads'] | default: '0' -}}
            </span>
            {%- comment %} Line 1: Downloads | Releases | Tags {% endcomment -%}
            {%- if addon['Total downloads'] and addon['Total downloads'] != '0' -%}
                ⬇ {{ addon['Total downloads'] }}
            {%- endif -%}
            {%- if addon['Count versions'] and addon['Count versions'] != '0' -%}
                {%- if addon['Total downloads'] and addon['Total downloads'] != '0' %} | {% endif -%}
                📦 {{ addon['Count versions'] }}
            {%- endif -%}
            {%- if addon['Count tags'] and addon['Count tags'] != '0' -%}
                {%- if addon['Total downloads'] and addon['Total downloads'] != '0'
                    or addon['Count versions'] and addon['Count versions'] != '0' %} | {% endif -%}
                🏷 {{ addon['Count tags'] }}
            {%- endif -%}
            {%- comment %} Line 2: Stars | Forks | Watchers {% endcomment -%}
            {%- if addon['Stars'] and addon['Stars'] != '0'
                or addon['Forks'] and addon['Forks'] != '0'
                or addon['Watchers'] and addon['Watchers'] != '0' -%}
                <br/>
            {%- endif -%}
            {%- if addon['Stars'] and addon['Stars'] != '0' -%}
                ★ {{ addon['Stars'] }}
            {%- endif -%}
            {%- if addon['Forks'] and addon['Forks'] != '0' -%}
                {%- if addon['Stars'] and addon['Stars'] != '0' %} | {% endif -%}
                ⑂ {{ addon['Forks'] }}
            {%- endif -%}
            {%- if addon['Watchers'] and addon['Watchers'] != '0' -%}
                {%- if addon['Stars'] and addon['Stars'] != '0'
                    or addon['Forks'] and addon['Forks'] != '0' %} | {% endif -%}
                👁 {{ addon['Watchers'] }}
            {%- endif -%}
            {%- comment %} Line 3: Issues | PRs {% endcomment -%}
            {%- if addon['Total issues'] and addon['Total issues'] != '0'
                or addon['Total PRs'] and addon['Total PRs'] != '0' -%}
                <br/>
            {%- endif -%}
            {%- if addon['Total issues'] and addon['Total issues'] != '0' -%}
                ⚠ {{ addon['Open issues'] | default: '0' }}/{{ addon['Total issues'] }}
            {%- endif -%}
            {%- if addon['Total PRs'] and addon['Total PRs'] != '0' -%}
                {%- if addon['Total issues'] and addon['Total issues'] != '0' %} | {% endif -%}
                PR {{ addon['Open PRs'] | default: '0' }}/{{ addon['Total PRs'] }}
            {%- endif -%}
        </td>
    </tr>
    {% endif %}
{% endfor %}
</tbody>
