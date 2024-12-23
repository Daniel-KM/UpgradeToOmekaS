{%- if page.lang -%}
{%- assign active_lang = page.lang -%}
{%- else -%}
{%- assign active_lang = site.default_lang -%}
{%- endif -%}

<ul>
{% for lang in site.languages %}
  <li>
    {%- assign lang_name = site.data[lang].l10n.lang_name -%}
    {% if lang == active_lang %}
      {{ lang_name }}
    {% else %}
      {% comment %}
      No default language.
      {% endcomment %}
      <a href="{{ page.url | replace_first: active_lang, lang }}">{{ lang_name }}</a>
    {% endif %}
  </li>
{% endfor %}
</ul>
