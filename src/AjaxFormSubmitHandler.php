<?php

namespace Likemusic\WordPress\Bambora\AvadaBuilder\Forms;

require_once __DIR__ . '/../../fusion-builder/inc/class-fusion-form-submit.php';

use Fusion_Form_DB_Entries;
use Fusion_Form_DB_Forms;
use Fusion_Form_DB_Submissions;
use Fusion_Form_Submit;
use Likemusic\Wordpress\Bambora\Checkout\Core\ConfiguredPaymentUrlGenerator;

class AjaxFormSubmitHandler extends Fusion_Form_Submit
{
    /** @var ConfiguredPaymentUrlGenerator */
    private $urlGenerator;

    private $submissionId;

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
        $formData['trnOrderNumber'] = $this->getLatestSubmissionId();

        return $this->generatePaymentUrl($formData);
    }

    private function getLatestSubmissionId()
    {
        return $this->submissionId;
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

    /**
     * Form submission will be stored in the database.
     *
     * @access protected
     * @since 3.1.1
     * @param array $data Form data array.
     * @return void
     */
    protected function submit_form_to_database( $data ) {

        if ( ! $data ) {
            $data = $this->get_submit_data();
        }

        $fusion_forms  = new Fusion_Form_DB_Forms();
        $submission    = new Fusion_Form_DB_Submissions();
        $this->submissionId = $submission_id = $submission->insert( $data['submission'] );

        foreach ( $data['data'] as $field => $value ) {
            $field_data  = ( is_array( $value ) ) ? implode( ' | ', $value ) : $value;
            $field_label = isset( $data['field_labels'][ $field ] ) ? $data['field_labels'][ $field ] : '';
            $db_field_id = $fusion_forms->insert_form_field( $data['submission']['form_id'], $field, $field_label );

            $entries = new Fusion_Form_DB_Entries();
            $entries->insert(
                [
                    'form_id'       => absint( $data['submission']['form_id'] ),
                    'submission_id' => absint( $submission_id ),
                    'field_id'      => sanitize_key( $db_field_id ),
                    'value'         => $field_data,
                    'privacy'       => in_array( $field, $data['fields_holding_privacy_data'], true ),
                ]
            );
        }
    }

}
