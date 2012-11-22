<?php
class ContactUsController extends BaseController {
    const APP_ID = '245607595465926';
    const APP_SECRET = 'b29a7efe3a1329bae0b425de96acd84b';
    const APP_URI = 'op-contact-us';

    /**
     * FB Class
     *
     * @var FB $fb;
     */
    protected $fb;

    /**
     * Setup the base for creating template responses
     */
    public function __construct() {
        // Pass in the base for all the views
        parent::__construct();

        $this->fb = new Fb( self::APP_ID, self::APP_SECRET, self::APP_URI );
        $this->section = _('Contact Us');
    }

    /**
     * About Us
     *
     * @return JsonResponse
     */
    protected function index() {
        $form = new stdClass();
        $success = $website = false;

        // Make sure they are validly editing the app
        if ( isset( $_REQUEST['app_data'] ) ) {
            // Instantiate App
            $contact_us = new ContactUs();

            // Get App Data
            $app_data = url::decode( $_REQUEST['app_data'] );
            $other_user_id = security::decrypt( $app_data['uid'], 'SecREt-Us3r!' );
            $page_id = security::decrypt( $app_data['pid'], 'sEcrEt-P4G3!' );

            if ( $page_id ) {
                $website = $contact_us->get_connected_website( $page_id );
                $website_title = $website->title;
            } else {
                $website_title = 'N/A';
            }

            $form = new FormTable( 'fContactUs' );

            $website_row = $form->add_field( 'row', _('Website'), $website_title );

            $form->add_field( 'text', _('Facebook Connection Key'), 'tFBConnectionKey' )
                ->add_validation( 'req', _('The "Facebook Connection Key" field is required') );

            $form->add_field( 'hidden', 'app_data', $_REQUEST['app_data'] );

            // Make sure it's a valid request
            if( $other_user_id == $this->fb->user_id && $page_id && $form->posted() ) {
                $contact_us->connect( $page_id, $_POST['tFBConnectionKey'] );

                $website = $contact_us->get_connected_website( $page_id );
                $website_row->set_value( $website->title );
                $success = true;
            }
        }

        $response = $this->get_template_response( 'facebook/contact-us/index', 'Connect' );
        $response
            ->set_sub_includes('facebook')
            ->set( array( 'form' => $form, 'app_id' => self::APP_ID, 'success' => $success, 'website' => $website ) );

        return $response;
    }

    /**
     * Show the tab
     *
     * @return TemplateResponse
     */
    public function tab() {
        // Setup variables
        $contact_us = new ContactUs;
        $signed_request = $this->fb->getSignedRequest();
        $tab = $contact_us->get_tab( $signed_request['page']['id'] );

        // If it's secured, make the images secure
        if ( security::is_ssl() )
            $tab = ( stristr( $tab, 'websites.retailcatalog.us' ) ) ? preg_replace( '/(?<=src=")(http:\/\/)/i', 'https://s3.amazonaws.com/', $tab ) : preg_replace( '/(?<=src=")(http:)/i', 'https:', $tab );

        // Add Admin URL
        if( $signed_request['page']['admin'] ) {
            $admin = '<p><strong>Admin:</strong> <a href="#" onclick="top.location.href=' . "'";
            $admin .= url::add_query_arg(
                'app_data'
                , url::encode( array( 'uid' => security::encrypt( $this->fb->user_id, 'SecREt-Us3r!' ), 'pid' => security::encrypt( $signed_request['page']['id'], 'sEcrEt-P4G3!' ) ) )
                , 'http://apps.facebook.com/op-contact-us/'
            );
            $admin .= "'" . ';">Update Settings</a></p>';

            $tab = $admin . $tab;
        }

        $response = $this->get_template_response( 'facebook/contact-us/tab' );
        $response
            ->set_sub_includes( 'facebook/tabs' )
            ->set( compact( 'tab' ) );

        return $response;
    }


    /**
     * Settings
     *
     * @return RedirectResponse
     */
    public function settings() {
        // Redirect to correct location
        return new RedirectResponse( url::add_query_arg(
            'app_data'
            , url::encode( array( 'uid' => security::encrypt( $this->fb->user_id, 'SecREt-Us3r!' ), 'pid' => security::encrypt( $_GET['fb_page_id'], 'sEcrEt-P4G3!' ) ) )
            , '/facebook/contact-us/'
        ) );
    }
}