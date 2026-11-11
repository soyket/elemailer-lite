# Architecture rules

Full details with file references: docs/architecture.md.

- Entry: `elemailer-lite.php` loads `autoloader.php` and `plugin.php`, registers activation hooks, then on `plugins_loaded` (priority 150) initializes `Plugin::instance()->init()` only if the action `elemailer/after_load` has not already fired. Do not remove or reorder that guard.
- Autoloader mapping: namespace `Elemailer_Lite\` is stripped, CamelCase gets a hyphen, underscores become hyphens, backslashes become directory separators, everything is lowercased. Example: `Elemailer_Lite\App\Form_Template\Base` is `app/form-template/base.php`. A class whose file does not follow this will not load.
- Singletons use the trait `Elemailer_Lite\Traits\Singleton` (`::instance()`).
- CPT classes extend `Core\Cpt`. REST classes extend `Core\Api`, set `$prefix` and `$param` in `config()`, and name action methods `{httpmethod}_{action}` (for example `post_update`, `get_get`). The action name in the URL selects the method.
- Adding an Elementor widget requires all three of: a folder `integrations/elementor/widgets/{slug}/{slug}.php`, the slug in `Widgets\Base::get_all_widgets()`, and a `register(new Elemailer_Widget_X())` line in `Widgets\Base::register_widget()`.
- On the CPTs `em-form-template` and `em-emails-template`, Elemailer unregisters every Elementor widget except the `common` group, then registers only its own. Do not assume core Elementor widgets exist in the template editor.
- The email pipeline is `Util::get_template_content()` (body) and `Util::get_template_styles()` (CSS) then `Util::get_email_html_template()` (wrap and inline CSS). Emails have limited CSS support. `get_template_styles()` removes media queries on purpose.
- `Plugin::enqueue_dequeue_js_css_public()` dequeues theme and non-allowlisted plugin assets on template pages. Treat it as fragile and test with a theme and a few plugins active before changing it.
- CPT registration happens on `after_setup_theme` and the text domain loads on `init`. Both were moved there to avoid early textdomain notices (changelog 2.8). Do not move them earlier.
