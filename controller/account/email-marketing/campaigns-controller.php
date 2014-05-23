<?php

class CampaignsController extends BaseController {

    /**
     * Setup the base for creating template responses
     */
    public function __construct() {
        parent::__construct();

//        if ( !$this->user->account->email_marketing )
//            return new RedirectResponse('/email-marketing/subscribers/');

        $this->view_base = 'email-marketing/campaigns/';
        $this->section = 'email-marketing';
        $this->title = _('Campaigns') . ' | ' . _('Email Marketing');
    }

    /**
     * List Email Messages
     *
     * @return TemplateResponse|RedirectResponse
     */
    protected function index() {
        if ( !$this->user->account->email_marketing )
            return new RedirectResponse('/email-marketing/subscribers/');

        return $this->get_template_response( 'index' )
            ->kb( 74 )
            ->add_title( _('Emails') )
            ->select( 'campaigns', 'view' );
    }

    /**
     * List All
     *
     * @return DataTableResponse
     */
    protected function list_all() {
        // Get response
        $dt = new DataTableResponse( $this->user );

        $email_message = new EmailMessage();

        // Set Order by
        $dt->order_by( '`subject`', '`status`', 'date_sent' );
        $dt->add_where( ' AND `website_id` = ' . (int) $this->user->account->id );
        $dt->search( array( '`subject`' => false ) );

        // Get items
        $messages = $email_message->list_all( $dt->get_variables() );
        $dt->set_row_count( $email_message->count_all( $dt->get_count_variables() ) );

        // Set initial data
        $data = false;
        $confirm = _('Are you sure you want to delete this email? This cannot be undone.');
        $delete_nonce = nonce::create( 'delete' );
        $statuses = array( 'Draft', 'Scheduled', 'Sent' );
        $timezone = $this->user->account->get_settings( 'timezone' );
        $server_timezone = Config::setting('server-timezone');

        /**
         * @var EmailMessage $message
         */
        if ( is_array( $messages ) )
            foreach ( $messages as $message ) {
                $message->date_sent = dt::adjust_timezone( $message->date_sent, $server_timezone, $timezone );
                $date = new DateTime( $message->date_sent );

                if ( $message->status != EmailMessage::STATUS_SENT ) {
                    $actions = '<a href="' . url::add_query_arg( 'id', $message->id, '/email-marketing/campaigns/create/' ) . '" title="' . _('Edit') . '">' . _('Edit') . '</a> | ';
                    $actions .= '<a href="' . url::add_query_arg( array( 'id' => $message->id, '_nonce' => $delete_nonce ), '/email-marketing/campaigns/delete/' ) . '" title="' . _('Delete') . '" ajax="1" confirm="' . $confirm . '">' . _('Delete') . '</a>';
                } else {
                    $actions = '<a href="' . url::add_query_arg( 'eid', $message->id, '/analytics/email/' ) . '" title="' . _('Analytics') . '">' . _('Analytics') . '</a>';
                }

                $data[] = array(
                    format::limit_chars( $message->subject, 50, '...' ) . '<br /><div class="actions">' . $actions . '</div>',
                    $statuses[$message->status],
                    $date->format( 'F jS, Y g:ia' )
                );
            }

        // Send response
        $dt->set_data( $data );

        return $dt;
    }

    /**
     * Delete
     *
     * @return AjaxResponse
     */
    protected function delete() {
        // Make sure it's a valid ajax call
        $response = new AjaxResponse( $this->verified() );

        // Make sure we have everything right
        $response->check( isset( $_GET['id'] ), _('You cannot delete this email message') );

        if ( $response->has_error() )
            return $response;

        // Remove
        $email_message = new EmailMessage();
        $email_message->get( $_GET['id'], $this->user->account->id );
        $email_message->remove_all( $this->user->account );

        // Redraw the table
        jQuery('.dt:first')->dataTable()->fnDraw();

        $response->add_response( 'jquery', jQuery::getResponse() );

        return $response;
    }


    public function create() {
        $campaign = new EmailMessage();
        $campaign->get( $_GET['id'], $this->user->account->id );

        if ( $campaign->id ) {
            // Selected Email Lists
            $campaign->get_associations();

            // Scheduled to future time?
            if ( $campaign->date_sent > $campaign->date_created ) {
                $scheduled_datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $campaign->date_sent );
            }
        }

        $email_list = new EmailList();
        $email_lists = $email_list->get_count_by_account( $this->user->account->id );

        $settings = $this->user->account->get_settings( 'timezone', '' );

        $timezones = array_slice( data::timezones( false, false, true ), 4, 4 );

        $account_file = new AccountFile();
        $files = $account_file->get_by_account( $this->user->account->id );

        $this->resources->css( 'email-marketing/campaigns/email', 'email-marketing/campaigns/create', 'jquery.timepicker' )
            ->css_url( Config::resource('jquery-ui') )
            ->javascript( 'jquery.timepicker' , 'email-marketing/campaigns/create', 'jquery.idTabs', 'fileuploader', 'gsr-media-manager' );

        return $this->get_template_response( 'create' )
            ->kb( 0 )
            ->add_title( _('Campaigns') )
            ->select( 'campaigns', 'create' )
            ->set( compact( 'campaign', 'scheduled_datetime', 'email_lists', 'settings', 'timezones', 'files' ) );
    }

    /**
     * Send Test
     * Sends test email
     *
     * @return AjaxResponse
     */
    public function send_test() {
        $response = new AjaxResponse( $this->verified() );

        // Make sure we have everything right
        $response->check( isset( $_POST['message'], $_POST['email'] ), _('An error occurred while trying to test this message. Please refresh the page and try again.') );

        if ( $response->has_error() )
            return $response;

        // Test
        $email_message = new EmailMessage();

        // Set attributes
        $email_message->message = $_POST['message'];
        $email_message->subject = $_POST['subject'];
        if ( $_POST['no_template'] ) {
            $email_message->email_template_id = NULL;
        } else {
            $email_template = new EmailTemplate();
            $email_templates = $email_template->get_by_account( $this->user->account->id );
            $email_template = current( $email_templates );
            $email_message->email_template_id = $email_template->email_template_id;
        }

        // Test message
        try {
            $email_message->test( $_POST['email'], $this->user->account );
            $response->notify( "Test email sent to {$_POST['email']}" );
        } catch ( ModelException $e ) {
            $response->check( false, $e->getMessage() );
        }

        return $response;
    }

    private function validate() {
        $validator = new Validator( 'fCreateCampaign' );
        $validator->add_validation( 'email_lists', 'req', 'Please select at least one Email List where to send' );
        $validator->add_validation( 'name', 'req', 'Campaign "Name" field is required');
        $validator->add_validation( 'subject', 'req', 'Campaign Email "Subject" field is required');
        if ( isset($_POST['schedule'] )) {
            $validator->add_validation( 'date', 'req', 'Scheduling a Campaign needs a valid "Date"' );
            $validator->add_validation( 'date', 'date', 'Scheduling a Campaign needs a valid "Date"' );
            $validator->add_validation( 'time', 'req', 'Scheduling a Campaign needs a valid "Time"' );
            $validator->add_validation( 'timezone', 'req', 'Scheduling a Campaign needs a valid "Timezone"' );
        }
        $validator->add_validation( 'name', 'req', 'Please build a "Message"');

        $errors = $validator->validate();
        // are being shown as an alert, so we delete the <br />
        return strip_tags( $errors );
    }

    /**
     * Save
     * @param EmailMessage $campaign
     */
    private function save( $campaign ) {
        $campaign->website_id = $this->user->account->website_id;
        $campaign->name = $_POST['name'];
        $campaign->subject = $_POST['subject'];
        $campaign->message = $_POST['message'];
        $settings = $this->user->account->get_settings( 'from_name', 'from_email' );
        $from_name = ( empty( $settings['from_name'] ) ) ? $this->user->account->title : $settings['from_name'];
        $from_email = ( empty( $settings['from_email'] ) ) ? 'noreply@' . url::domain( $this->user->account->domain, false ) : $settings['from_email'];
        $campaign->from = $from_name . ' <' . $from_email . '>';
        if ( isset( $_POST['schedule'] ) ) {
            // Date
            $date_sent = $_POST['date'];
            // Time
            if ( !empty( $_POST['time'] ) ) {
                list( $time, $am_pm ) = explode( ' ', $_POST['time'] );
                if ( 'pm' == strtolower( $am_pm ) ) {
                    list( $hour, $minute ) = explode( ':', $time );
                    $date_sent .= ( 12 == $hour ) ? ' ' . $time . ':00' : ' ' . ( $hour + 12 ) . ':' . $minute . ':00';
                } else {
                    $date_sent .= ' ' . $time . ':00';
                }
            }
            // Apply Timezone
            $campaign->date_sent = dt::adjust_timezone( $date_sent, $_POST['timezone'], Config::setting('server-timezone') );
        } else {
            $campaign->date_sent = dt::now();
        }
        if ( isset( $_POST['no_template'] ) ) {
            $campaign->email_template_id = NULL;
        } else {
            $email_template = new EmailTemplate();
            $email_templates = $email_template->get_by_account( $this->user->account->id );
            $email_template = current( $email_templates );
            $campaign->email_template_id = $email_template->email_template_id;
        }

        // Save/Create campaign
        if ( $campaign->id ) {
            $campaign->save();
        } else {
            $campaign->create();
        }

        // Save Associations (email lists)
        $campaign->remove_associations();
        if ( is_array( $_POST['email_lists'] ) )
            $campaign->add_associations( $_POST['email_lists'] );
    }

    /**
     * Save Draft
     *
     * @return AjaxResponse
     */
    public function save_draft() {
        $response = new AjaxResponse( $this->verified() );

        $errors = $this->validate();
        $response->check( empty( $errors ), $errors );
        if ( $response->has_error()) {
            return $response;
        }

        $campaign = new EmailMessage();
        if ( isset( $_POST['id'] ) )
            $campaign->get( $_POST['id'], $this->user->account->id );

        $campaign->status = EmailMessage::STATUS_DRAFT;
        $this->save($campaign);  // sync the other fields and save

        // means it's a new Campaign
        if ( !isset( $_POST['id'] ) ) {
            // tell the form we have a Campaign ID
            jQuery('<input type="hidden" name="id" id="campaign-id" value="'.$campaign->id.'" />')->appendTo('div[data-step=1]');
            $response->add_response( 'campaign_id', $campaign->id );
        }

        jQuery('.save-draft')->removeClass('disabled')->text('Save Draft');;

        $response->add_response( 'jquery', jQuery::getResponse());
        $response->notify( 'Draft Saved!' );
        return $response;
    }

    /**
     * Save Campaign
     *
     * @return AjaxResponse
     */
    public function save_campaign() {
        $response = new AjaxResponse( $this->verified() );

        $errors = $this->validate();
        $response->check( empty( $errors ), $errors );
        if ( $response->has_error()) {
            return $response;
        }

        $campaign = new EmailMessage();
        if ( isset( $_POST['id'] ) )
            $campaign->get( $_POST['id'], $this->user->account->id );

        $campaign->status = EmailMessage::STATUS_SCHEDULED;

        // Save
        $this->save($campaign);  // sync the other fields and save

        // means it's a new Campaign
        if ( !isset( $_POST['id'] ) ) {
            // tell the form we have a Campaign ID
            jQuery('<input type="hidden" name="id" id="campaign-id" value="'.$campaign->id.'" />')->appendTo('div[data-step=1]');
        }

        $email_list = new EmailList();
        $email_lists = $email_list->get_by_message( $campaign->id, $this->user->account->id );

        // Send to SendGrid
        $campaign->schedule( $this->user->account, $email_lists );

        jQuery('.save-draft')->removeClass('disabled')->text('Looks Good! Send it out.');

        $response->add_response( 'jquery', jQuery::getResponse());
        $response->notify( 'Campaign Saved!' );
        return $response;
    }

    /**
     * Preview
     * Load preview iframe content
     *
     * @return CustomResponse|RedirectResponseç
     */
    protected function preview() {
        if ( !$this->verified() )
            return new RedirectResponse('/');

        $response = new CustomResponse( $this->resources, $this->view_base . 'preview' );

        $campaign = new EmailMessage();
        $campaign->get( $_GET['id'], $this->user->account->id );
        $message = $campaign->get_full_message( $this->user->account );

        $response->set( 'message', $message );

        return $response;
    }
}