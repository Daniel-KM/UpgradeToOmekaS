<ul>
{% for lang in site.languages %}
  <li>
    {% assign lang_name = site.data[lang].l10n.lang_name %}
    {% if lang == site.active_lang %}
      {{ lang_name }}
    {% else %}
      {% comment %}
      No default language.
      {% endcomment %}
      <a href="/{{ lang }}{{ page.url }}">{{ lang_name }}</a>
    {% endif %}
  </li>
{% endfor %}
</ul>
