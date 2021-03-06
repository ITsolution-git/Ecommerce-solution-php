<?php
class EmailTemplate extends ActiveRecordBase {
    public $id, $email_template_id, $name, $template, $image, $thumbnail, $type, $date_created;

    /**
     * Setup the account initial data
     */
    public function __construct() {
        parent::__construct( 'email_templates' );

        // We want to make sure they match
        if ( isset( $this->email_template_id ) )
            $this->id = $this->email_template_id;
    }

    /**
     * Get Default
     *
     * @param int $email_template_id
     * @param int $account_id
     */
    public function get( $email_template_id, $account_id ) {
        $this->prepare(
            "SELECT et.* FROM `email_templates` AS et LEFT JOIN `email_template_associations` AS eta ON ( eta.`email_template_id` = et.`email_template_id` ) WHERE et.`email_template_id` = :email_template_id AND eta.`website_id` = :account_id"
            , 'ii'
            , array( ':email_template_id' => $email_template_id, ':account_id' => $account_id )
        )->get_row( PDO::FETCH_INTO, $this );

		$this->id = $this->email_template_id;
    }

    /**
     * Get Default
     *
     * @param int $account_id
     */
    public function get_default( $account_id ) {
        $this->prepare(
            "SELECT et.* FROM `email_templates` AS et LEFT JOIN `email_template_associations` AS eta ON ( eta.`email_template_id` = et.`email_template_id` ) WHERE et.`type` = 'default' AND eta.`website_id` = :account_id"
            , 'i'
            , array( ':account_id' => $account_id )
        )->get_row( PDO::FETCH_INTO, $this );

		$this->id = $this->email_template_id;
    }

    /**
     * Get By Account
     *
     * @param int $account_id
     * @return EmailTemplate[]
     */
    public function get_by_account( $account_id ) {
        return $this->prepare(
            "SELECT et.* FROM `email_templates` AS et LEFT JOIN `email_template_associations` AS eta ON ( eta.`email_template_id` = et.`email_template_id` ) WHERE et.`type` <> 'offer' AND eta.`website_id` = :account_id"
            , 'i'
            , array( ':account_id' => $account_id )
        )->get_results( PDO::FETCH_CLASS, 'EmailTemplate' );
    }

    /**
     * Create Email List
     */
    public function create() {
        $this->date_created = dt::now();

        $this->insert( array(
            'name' => strip_tags($this->name)
            , 'template' => format::strip_only( $this->template, '<script>' )
            , 'image' => strip_tags($this->image)
            , 'thumbnail' => strip_tags($this->thumbnail)
            , 'type' => strip_tags($this->type)
            , 'date_created' => $this->date_created
        ), 'ssssss' );

        $this->id = $this->email_template_id = $this->get_insert_id();
    }

    /**
     * Update Email List
     */
    public function save() {
        $this->update( array(
            'name' => strip_tags($this->name)
            , 'template' => format::strip_only( $this->template, '<script>' )
            , 'image' => strip_tags($this->image)
            , 'thumbnail' => strip_tags($this->thumbnail)
            , 'type' => $this->type
        ), array( 'email_template_id' => $this->id, ), 'sssss', 'i' );
    }

    /**
     * Add Association
     *
     * @param int $account_id
     */
    public function add_association( $account_id ) {
        $this->prepare(
            'INSERT INTO `email_template_associations` ( `email_template_id`, `website_id` ) VALUES ( :email_template_id, :account_id ) ON DUPLICATE KEY UPDATE `website_id` = :account_id2'
            , 'iii'
            , array(
                ':email_template_id' => $this->id
                , ':account_id' => $account_id
                , ':account_id2' => $account_id
            )
        )->query();
    }

    /**
     * Gets a template and fills in the variables
     *
     * @param Account $account
     * @param EmailMessage $email_message
     * @return string
     */
    public function get_complete( $account, $email_message ) {
        if ( $email_message->email_template_id ) {
            $this->get( $email_message->email_template_id, $email_message->website_id );
        } else {
            $this->get_default( $email_message->website_id );
        }

        // Format
        $message = trim( format::autop( $email_message->message ) );

        // Email Variables
        $message = str_replace( array( '[website_title]' ), array( $account->title ), $message );
        $subject = str_replace( array( '[website_title]' ), array( $account->title ), $email_message->subject );

        switch ( $email_message->type ) {
            case 'product':
                // Instantiate class
                $category = new Category;

                // Get settings
                $settings = $account->get_settings( 'product-price-color', 'view-product-button', 'product-color' );
                $view_product_image = $settings['view-product-button'];

                // Set variables
                $products_html = '';
                $i = 0;
                $open = false;
                $new_meta = array();

                /**
                 * @var Product $product
                 */
                if ( is_null( $email_message->meta ) )
                    $email_message->get_smart_meta();

                foreach ( $email_message->meta as $product ) {
                    $new_meta[$product->order] = $product;
                }

                // Sort by key
                ksort( $new_meta );

                // Get data
                foreach ( $new_meta as $product ) {
                    $i++;

                    // Every third product
                    if ( 1 == $i % 3 ) {
                        $products_html .= '<tr>';
                        $open = true;
                    }

                    // Set default colors
                    $price_color = ( empty( $settings['product-price-color'] ) ) ? '548557' : $settings['product-price-color'];
                    $product_color = ( empty( $settings['product-color'] ) ) ? '78174c' : $settings['product-color'];

                    // Get product link
                    $product_link = 'http://' . $account->domain . $category->get_url( $product->category_id ) . $product->slug . '/';

                    // Form image
                    $images = $product->get_images();
                    $product_image = 'http://' . $product->industry . '.retailcatalog.us/products/' . $product->id . '/small/' . current( $images );
                    $price = ( '0' == $product->price ) ? '' : 'Price <span style="color:#' . $product_color . '">$' . $product->price . '</span><br />';

                    // Set the products HTML
                    $products_html .= '
                        <td width="33%" style="text-align:center;font-size:14px; line-height:24px; font-weight:bold;" valign="top">
                            <div>
                                <a href="' . $product_link . '" title="' . $product->name . '" style="padding:0 7px 7px 0; width:144px; display:block; margin:0 auto;"><img src="' . $product_image . '" alt="' . $product->name . '" width="144" height="144" border="0" /></a>
                            </div>
                            <a href="' . $product_link . '" title="' . $product->name . '" style="font-size:16px; font-weight:bold; color:#' . $price_color . '; text-decoration:none;">' . $product->name . '</a><br />' . $price . '
                            <a href="' . $product_link . '" title="View Product"><img src="' . $view_product_image . '" alt="View Product" border="0" /></a>
                        </td>';

                    // Close every third product
                    if ( 0 == $i % 3 ) {
                        $products_html .= '</tr>';
                        $open = false;
                    }
                }

                // If it's still open, close it
                if ( $open )
                    $products_html .= '</tr>';

                $html_message = str_replace( array( '[subject]', '[message]', '[products]' ), array( $subject, $message, $products_html ), $this->template );
            break;

            default:
                // Just do a normal message
                $remove_header_footer = $account->get_settings('remove-header-footer');
				$html_message = ( $remove_header_footer ) ? $message : str_replace( array( '[subject]', '[message]' ), array( $subject, $message ), $this->template );
            break;
        }

        // Empty not used placeholders
        $html_message = str_replace( array( '[subject]', '[message]', '[products]' ), '', $html_message );

        return $html_message;
    }
}
