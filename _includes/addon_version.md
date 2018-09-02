{%- if version contains 'alpha' or version contains 'beta' or version contains 'rc' -%}
    {% assign version_array = include.version | split: '-' %}
    {% assign version_last = version_array.last %}
    {% assign version_last_size = version_array.last | size %}
    {% assign version_size = include.version | size %}
    {% assign version_base_size = version_size | minus: version_last_size | minus: 1 %}
    {{ include.version | slice: 0, version_base_size }}-
    {%- if version contains 'alpha' -%}
        <span class="version-alpha">{{ version_last }}</span>
    {%- elsif version contains 'beta' -%}
        <span class="version-beta">{{ version_last }}</span>
    {%- elsif version contains 'rc' -%}
        <span class="version-rc">{{ version_last }}</span>
    {%- endif -%}
{%- else -%}
    {{ include.version }}
{%- endif -%}
