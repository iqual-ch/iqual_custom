# iqual_custom

iqual custom additions needed for pages:


- Allows it to override the hreflang in the `<link rel="alternate" ... >` tag so that the locale code (e.g. de-CH) is displayed instead of the language code (e.g. de). This setting can be done for each language (field "Locale Code" under /admin/config/regional/language/edit/*[langcode]*). If no value is set, the language code is used as default.

- Allows it to hide content types on the /node/add page. Configurable at: /admin/config/iqual/settings.
