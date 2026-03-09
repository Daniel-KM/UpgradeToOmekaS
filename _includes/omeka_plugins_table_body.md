<tbody class="list">
{%- if page.lang == 'fr' -%}
    {%- assign t_downloads = 'Téléchargements' -%}
    {%- assign t_tags = 'Tags Git' -%}
    {%- assign t_releases = 'Versions' -%}
    {%- assign t_stars = 'Étoiles' -%}
    {%- assign t_forks = 'Dérivés' -%}
    {%- assign t_watchers = 'Abonnés' -%}
    {%- assign t_issues = 'Tickets ouverts/total' -%}
    {%- assign t_prs = 'Demandes de fusion ouvertes/total' -%}
    {%- assign t_fork = 'Dérivé' -%}
    {%- assign t_archived = 'Archivé' -%}
    {%- assign t_gone = 'Disparu' -%}
{%- else -%}
    {%- assign t_downloads = 'Downloads' -%}
    {%- assign t_tags = 'Git tags' -%}
    {%- assign t_releases = 'Releases' -%}
    {%- assign t_stars = 'Stars' -%}
    {%- assign t_forks = 'Forks' -%}
    {%- assign t_watchers = 'Watchers' -%}
    {%- assign t_issues = 'Issues open/total' -%}
    {%- assign t_prs = 'Pull requests open/total' -%}
    {%- assign t_fork = 'Fork' -%}
    {%- assign t_archived = 'Archived' -%}
    {%- assign t_gone = 'Gone' -%}
{%- endif -%}
{% for addon in site.data.omeka_plugins %}
    {% if addon['Name'] %}
    <tr{% if addon['Status'] and addon['Status'] != '' %} class="addon-inactive"{% elsif addon['Fork source'] and addon['Fork source'] != '' %} class="addon-fork"{% endif %}>
        <td>
        {% unless addon['Name'] == nil %}
            <a href="{{ addon['Url'] }}" class="link addon-link">{{ addon['Name'] }}</a>
            {%- if addon['Fork source'] and addon['Fork source'] != '' -%}
                {%- assign fork_account = addon['Url'] | remove: 'https://github.com/' | remove: 'https://gitlab.com/' | split: '/' | first -%}
                <span class="addon-status">{{ t_fork }} ({{ fork_account }})</span>
            {%- endif -%}
            {%- if addon['Status'] and addon['Status'] != '' -%}
                <span class="addon-status">{%- if addon['Status'] == 'Archived' -%}{{ t_archived }}{%- elsif addon['Status'] == 'Gone' -%}{{ t_gone }}{%- else -%}{{ addon['Status'] }}{%- endif -%}</span>
            {%- endif -%}
        {% endunless %}
        </td>
        <td>
        {% unless addon['Name'] == nil %}
            {% unless addon['Author'] == nil %}
                {% assign account_name = addon['Url'] | remove: 'https://github.com/' | remove: 'https://gitlab.com/' | split: '/' | first %}
                {% assign account_url = addon['Url'] | split: account_name | first | append: account_name %}
                <a href="{{ account_url }}" class="link addon-author">{{ addon['Author'] | xml_escape }}</a>
            {% endunless %}
            {% if addon['License'] and addon['License'] != '' %}
                <br/><span class="addon-license">{{ addon['License'] | xml_escape }}</span>
            {% endif %}
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
        <td class="addon-target">
            {%- if addon['Omeka target'] and addon['Omeka target'] != '' -%}
                <span>{{ addon['Omeka target'] }}</span>
            {%- elsif addon['Omeka min'] and addon['Omeka min'] != '' -%}
                <span>{{ addon['Omeka min'] }}</span>
            {%- endif -%}
            {%- if addon['Omeka.org'] and addon['Omeka.org'] != '' -%}
                <span class="addon-omeka-org" title="Omeka.org">{{ addon['Omeka.org'] }}</span>
            {%- endif -%}
            {%- if addon['Upgradable'] and addon['Upgradable'] != '' -%}
                <span class="addon-upgradable" title="Upgradable">{{ addon['Upgradable'] }}</span>
            {%- endif -%}
        </td>
        <td class="addon-tags">{{ addon['Tags'] | replace: ',', ', ' }}</td>
        <!--
        <td class="addon-required">{{ addon['Required plugins'] | replace: ',', ',<br />' }}</td>
        <td class="addon-required">{{ addon['Optional plugins'] | replace: ',', ',<br />' }}</td>
        -->
        <td class="addon-description">{{ addon['Description']  | xml_escape }}</td>
        <td class="addon-stats">
            <span class="addon-stats-sort" style="display:none">
                {{- addon['Total downloads'] | default: '0' -}}
            </span>
            <span class="stats-grid">
            {%- if addon['Total downloads'] and addon['Total downloads'] != '0'
                or addon['Count versions'] and addon['Count versions'] != '0'
                or addon['Count tags'] and addon['Count tags'] != '0' -%}
                <span title="{{ t_downloads }}">{%- if addon['Total downloads'] and addon['Total downloads'] != '0' -%}⬇ {{ addon['Total downloads'] }}{%- endif -%}</span>
                <span title="{{ t_releases }}">{%- if addon['Count versions'] and addon['Count versions'] != '0' -%}⊡ {{ addon['Count versions'] }}{%- endif -%}</span>
                <span title="{{ t_tags }}">{%- if addon['Count tags'] and addon['Count tags'] != '0' -%}# {{ addon['Count tags'] }}{%- endif -%}</span>
            {%- endif -%}
            {%- if addon['Stars'] and addon['Stars'] != '0'
                or addon['Watchers'] and addon['Watchers'] != '0'
                or addon['Forks'] and addon['Forks'] != '0' -%}
                <span title="{{ t_stars }}">{%- if addon['Stars'] and addon['Stars'] != '0' -%}★ {{ addon['Stars'] }}{%- endif -%}</span>
                <span title="{{ t_watchers }}">{%- if addon['Watchers'] and addon['Watchers'] != '0' -%}⊙ {{ addon['Watchers'] }}{%- endif -%}</span>
                <span title="{{ t_forks }}">{%- if addon['Forks'] and addon['Forks'] != '0' -%}⑂ {{ addon['Forks'] }}{%- endif -%}</span>
            {%- endif -%}
            {%- if addon['Total issues'] and addon['Total issues'] != '0'
                or addon['Total PRs'] and addon['Total PRs'] != '0' -%}
                <span></span>
                <span title="{{ t_issues }}">{%- if addon['Total issues'] and addon['Total issues'] != '0' -%}⚠ {{ addon['Open issues'] | default: '0' }}/{{ addon['Total issues'] }}{%- endif -%}</span>
                <span title="{{ t_prs }}">{%- if addon['Total PRs'] and addon['Total PRs'] != '0' -%}⑃ {{ addon['Open PRs'] | default: '0' }}/{{ addon['Total PRs'] }}{%- endif -%}</span>
            {%- endif -%}
            </span>
        </td>
    </tr>
    {% endif %}
{% endfor %}
</tbody>
