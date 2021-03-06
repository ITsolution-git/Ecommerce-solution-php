<?php
class AccountsAndServicesReport extends CustomReport {
    /**
     * @var User
     */
    protected $user;

    /**
     * Setup
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Get the Report
     *
     * @return array
     */
    public function report() {
        $report = $this->get_results( "SELECT w.`title`, c.`name`, COUNT( wp.`product_id` ) AS products, DATE( w.`date_created` ), u.`contact_name`, u.`email`, COALESCE( cp.`name`, '(none)' ), COALESCE( u2.`contact_name`, '(none)' ), IF ( 0 = w.`status`, 'Inactive', IF( 0 = w.`live`, 'Staging', 'Live' ) ), w.`pages`, w.`product_catalog`, w.`blog`, w.`email_marketing`, w.`shopping_cart`, w.`room_planner`, w.`social_media`, if( '' = ws.`value`, 0, COALESCE( ws.`value`, 0 ) ) AS 'ashley_express', w.`domain_registration`, w.`additional_email_addresses`, CONCAT( 'http://', w.`domain`, '/' ) FROM `websites` AS w LEFT JOIN `users` AS u ON ( w.`user_id` = u.`user_id` ) LEFT JOIN `companies` AS c ON ( u.`company_id` = c.`company_id` ) LEFT JOIN `website_products` AS wp ON ( w.`website_id` = wp.`website_id` ) LEFT JOIN `company_packages` AS cp ON ( w.`company_package_id` = cp.`company_package_id` ) LEFT JOIN `users` AS u2 ON ( w.`os_user_id` = u2.`user_id` ) LEFT JOIN `website_settings` AS ws ON ( ws.`website_id` = w.`website_id` AND ws.`key` = 'ashley-express' ) WHERE w.`status` = 1 AND c.`company_id` = 1 AND wp.`blocked` = 0 AND wp.`active` = 1 GROUP BY w.`website_id` ORDER BY `c`.`name` ASC", PDO::FETCH_ASSOC );

        array_unshift( $report, array( 'Website Title', 'Company', 'Products', 'Date Signed Up', 'Store Owner', 'Store Owner Email', 'Package', 'Online Specialist', 'Status', 'Website', 'Product Catalog', 'Blog', 'Email Marketing', 'Shopping Cart', 'Room Planner', 'Social Media', 'Ashley Express', 'Domain Registration', 'Additional Email Addresses', 'Link' ) );

        return $report;
    }
}