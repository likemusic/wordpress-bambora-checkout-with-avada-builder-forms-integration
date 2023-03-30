<?php

namespace Likemusic\WordPress\Bambora\AvadaBuilder\Forms;

class BamboraPaymentWithAvadaBuilderFormsIntegrationPlugin
{
    /** @var AjaxFormSubmitHandler */
    private $ajaxFormSubmitHandler;

    public function __construct()
    {
        $this->ajaxFormSubmitHandler = new AjaxFormSubmitHandler();

        add_action('after_setup_theme', [$this, 'afterSetupTheme']);
        add_action('admin_menu', [$this, 'addAdminMenu']);

        if (!get_option('bambora-checkout-with-avada-builder-form-integration')) {
            update_option('bambora-checkout-with-avada-builder-form-integration', ['forms_ids' => []]);
        }
    }

    public function addAdminMenu(): void
    {
        add_menu_page('Bambora Checkout with Avada Builder Form Integration', 'Bambora Checkout with Avada Builder Form Integration', 'manage_options',
            'bambora-checkout-with-avada-builder-form-integration', [$this, 'adminPageProcessor'], 'dashicons-forms'
        );
    }

    public function adminPageProcessor(): void
    {
        $this->processPost();
        $this->renderAdminPage();
    }

    private function renderAdminPage(): void
    {
        $config = get_option('bambora-checkout-with-avada-builder-form-integration');

        $commaSeparatedFormIds = implode(', ', $config ? $config['forms_ids'] : []);

        echo <<<CONTENT
<div class="wrap">
    <h1>Bambora Checkout With Avada Builder Forms Settings</h1>
    <form method="post">
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="form_ids">Apply payment redirect to forms with ids</label></th>
                    <td>
                        <input name="forms_ids" type="text" id="forms_ids" value="{$commaSeparatedFormIds}" class="regular-text">
                        <p class="description" id="forms_ids-description">Comma-separated form's ids.</p>
                    </td>
                </tr>
            </tbody>
        </table>
CONTENT;
        submit_button(__('Update'), 'primary large', 'save', false);

        echo <<<CONTENT
</form>
</div>
CONTENT;
    }

    private function processPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $redirectFormIdsString = $_POST['forms_ids'];

        $parts = explode(',', $redirectFormIdsString);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts);

        $newConfig = [
            'forms_ids' => $parts,
        ];

        update_option('bambora-checkout-with-avada-builder-form-integration', $newConfig);
    }

    public function afterSetupTheme(): void
    {
        $this->overrideFormSubmitHandlers();
        $this->addJsScripts();
    }

    private function overrideFormSubmitHandlers(): void
    {
        $priority = 9; // default priority is 10

        foreach (['database', 'email', 'url', 'database_email'] as $method) {
            add_action("wp_ajax_fusion_form_submit_form_to_$method", [$this, "{$method}AjaxSubmitFormHandler"], $priority);
            add_action("wp_ajax_nopriv_fusion_form_submit_form_to_$method", [$this, "{$method}AjaxSubmitFormHandler"], $priority);
        }
    }

    private function addJsScripts()
    {
        add_action('wp_enqueue_scripts', [$this, 'addOverrideScripts']);
    }

    public function addOverrideScripts()
    {
        wp_enqueue_script('fusion-form-override', plugins_url('bambora-checkout-with-avada-builder-forms-integration/src/assets/js/fusion-form-override.js'));
        //todo: fusion-form-js depends not works on prod - fix it
    }

    public function databaseAjaxSubmitFormHandler()
    {
        return $this->ajaxFormSubmitHandler->ajax_submit_form_to_database();
    }

    public function emailAjaxSubmitFormHandler()
    {
        return $this->ajaxFormSubmitHandler->ajax_submit_form_to_email();
    }

    public function urlAjaxSubmitFormHandler()
    {
        return $this->ajaxFormSubmitHandler->ajax_submit_form_to_url();
    }

    //todo: rename and bind to renamed
    public function database_emailAjaxSubmitFormHandler()
    {
        return $this->ajaxFormSubmitHandler->ajax_submit_form_to_database();
    }
}
