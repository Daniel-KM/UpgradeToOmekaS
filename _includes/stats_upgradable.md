{% assign total_addons = 0 %}
{% assign total_matchings = 0 %}
{% assign total_upgraders = 0 %}
{% assign total_upgradables = 0 %}
{% for addon in site.data.omeka_plugins %}
    {% unless addon['Name'] == nil %}
        {% assign total_addons = total_addons | plus: 1 %}
        {% unless addon['Module'] == nil %}
            {% assign total_matchings = total_matchings | plus: 1 %}
        {% endunless %}
        {% if addon['Upgradable'] == 'Yes' %}
            {% assign total_upgradables = total_upgradables | plus: 1 %}
        {% endif %}
        {% if addon['Upgradable'] == 'Yes (auto)' %}
            {% assign total_upgraders = total_upgraders | plus: 1 %}
        {% endif %}
    {% endunless %}
{% endfor %}

{% if total_addons > 0 %}
Already {{ total_matchings }} / {{ total_addons }} (<strong>{{ total_matchings | times: 100 | divided_by: total_addons | round }}%</strong>) plugins – the most used ones – have an equivalent module for [Omeka S], and {{ total_upgraders }} automatic upgraders are available with the plugin [Upgrade to Omeka S](https://github.com/Daniel-KM/UpgradeToOmekaS).
{% endif; %}
