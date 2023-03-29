<?php

namespace Likemusic\WordPress\Bambora\AvadaBuilder\Forms;

require_once __DIR__ . '/../../fusion-builder/inc/class-fusion-form-submit.php';

use Fusion_Form_Submit;
use Likemusic\Wordpress\Bambora\Checkout\Core\ConfiguredPaymentUrlGenerator;

class AjaxFormSubmitHandler extends Fusion_Form_Submit
{
    /** @var ConfiguredPaymentUrlGenerator */
    private $urlGenerator;

    public function __construct()
    {
        // suppress parent constructor call

        $this->urlGenerator = new ConfiguredPaymentUrlGenerator();
    }


    protected function get_results_from_message($type, $info)
    {
        $parentResult = parent::get_results_from_message($type, $info);

        return $this->addCustomResultsIfRequired($parentResult);
    }

    private function addCustomResultsIfRequired($parentResults)
    {
        if (!$this->addCustomResultsRequired()) {
            return $parentResults;
        }

        return $this->addCustomResults($parentResults);
    }

    private function addCustomResultsRequired(): bool
    {
        $config = get_option('bambora-checkout-with-avada-builder-form-integration');
        $formIds = $config['forms_ids'];

        $currentFormId = $this->getCurrentFormId();

        return in_array($currentFormId, $formIds);
    }

    private function getCurrentFormId()
    {
        return isset($_POST['form_id']) ? absint(sanitize_text_field(wp_unslash($_POST['form_id']))) : 0;
    }

    private function addCustomResults($parentResults): array
    {
        $parentResults['redirect_url'] = $this->getRedirectUrl();

        return $parentResults;
    }

    private function getRedirectUrl(): string
    {
        $formData = $this->getFormData();

        return $this->generatePaymentUrl($formData);
    }

    private function getFormData(): array
    {
        $formDataQueryString = wp_unslash($_POST['formData']); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        parse_str($formDataQueryString, $formData); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

        return $formData;
    }

    private function generatePaymentUrl($data): string
    {
        return $this->urlGenerator->makeByArray($data);
    }
}
