        <td class="addon-stats">
            <span class="addon-stat-values" hidden>
                <span class="addon-stat-downloads">{{ addon['Total downloads'] | default: '0' }}</span>
                <span class="addon-stat-versions">{{ addon['Count versions'] | default: '0' }}</span>
                <span class="addon-stat-tags">{{ addon['Count tags'] | default: '0' }}</span>
                <span class="addon-stat-stars">{{ addon['Stars'] | default: '0' }}</span>
                <span class="addon-stat-watchers">{{ addon['Watchers'] | default: '0' }}</span>
                <span class="addon-stat-forks">{{ addon['Forks'] | default: '0' }}</span>
                <span class="addon-stat-issues">{{ addon['Open issues'] | default: '0' }}</span>
                <span class="addon-stat-prs">{{ addon['Open PRs'] | default: '0' }}</span>
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
