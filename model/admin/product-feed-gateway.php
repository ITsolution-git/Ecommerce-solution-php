<?php
/**
 * Handles All product feed gateways
 *
 * @package Grey Suit Retail
 * @since 1.0
 */
abstract class ProductFeedGateway extends ActiveRecordBase {
    /**
     * Setup the array for existing products
     * @var array
     */
    protected $existing_products = array();

    /**
     * Set the UserId Responsible
     * @var int
     */
    protected $user_id;

    /**
     * See how many products we go through
     * @var array
     */
    protected $new_products = array();

    /**
     * See how many products we skip
     * @var array
     */
    protected $skipped = array();

    /**
     * Determine what was not identical
     * @var array
     */
    protected $not_identical = array();

    /**
     * Hold curl
     * @var curl
     */
    protected $curl;

    /**
     * Hold file
     * @var File
     */
    protected $file;

    /**
     * Holds error
     * @var bool
     */
    protected $error = false;

    /**
     * Construct
     */
    public function __construct( $user_id ) {
        parent::__construct('');

        $this->user_id = $user_id;
        $this->curl = new curl;
        $this->file = new File;
    }

    /**
     * Run the gateway
     */
    public final function run() {
        $this->setup();
        $this->get_existing_products();
        $this->get_data();
        $this->process();
        $this->send_report();
    }

    /**
     * See if something exists and return product id if it does
     *
     * @param mixed $key
     * @return Product
     */
    protected function get_existing_product( $key ) {
        $key = (string) $key;

        return ( array_key_exists( $key, $this->existing_products ) ) ? $this->existing_products[$key] : false;
    }

    /**
     * Check
     *
     * @param bool $criteria
     * @return bool
     */
    public function check( $criteria ) {
        if ( !$criteria )
            $this->error = true;

        return $criteria;
    }

    /**
     * Check if something has an error
     *
     * @return bool
     */
    public function has_error() {
        return $this->error;
    }

    /**
     * Is identical -- checks if there any not identical parts
     *
     * @return bool
     */
    public function is_identical() {
        return 0 == count( $this->not_identical );
    }

    /**
     * Reset error
     */
    public function reset_error() {
        $this->error = false;
    }

    /**
     * Reset identical
     */
    public function reset_identical() {
        $this->not_identical = array();
    }

    /**
     * Ticks off a counter for how many products there are
     *
     * @param string $product_string
     */
    protected function new_product( $product_string ) {
        $this->new_products[] = $product_string;
    }

    /**
     * Ticks off a counter for how many products were skipped
     *
     * @param $product_name
     */
    protected function skip( $product_name ) {
        $this->skipped[] = $product_name;
    }

    /**
     * Checks if something is identical, and returns it new one if it's empty
     *
     * @param string $variable
     * @param string $original
     * @param string $type
     * @return mixed
     */
    public function identical( $variable, $original, $type ) {
        // Nothing there, need original
        if ( empty( $variable ) )
            return $original;

        // They're not equal, so we need to mark it down
        if ( $variable != $original ) {
            if ( 'slug' == $type ) {
                $variable = $this->unique_slug( $variable );

                if ( $variable != $original )
                    $this->not_identical[] = $type;
            } else {
                $this->not_identical[] = $type;
            }
        }

        // Return the variable
        return $variable;
    }

    /**
     * Set the existing products
     */
    protected function get_existing_products() {
        $products = $this->prepare(
            "SELECT p.`product_id`, p.`brand_id`, p.`industry_id`, p.`name`, p.`slug`, p.`description`, p.`status`, p.`sku`, p.`price`, p.`weight`, p.`volume`, p.`product_specifications`, p.`publish_visibility`, p.`publish_date`, i.`name` AS industry, GROUP_CONCAT( `image` ORDER BY `sequence` ASC SEPARATOR '|' ) AS images, p.`category_id` FROM `products` AS p LEFT JOIN `industries` AS i ON ( i.`industry_id` = p.`industry_id`) LEFT JOIN `product_images` AS pi ON ( pi.`product_id` = p.`product_id` ) WHERE p.`user_id_created` = :user_id_created GROUP BY p.`sku` ORDER BY `publish_visibility` DESC"
            , 'i'
            , array( ':user_id_created' => $this->user_id )
        )->get_results( PDO::FETCH_CLASS, 'Product' );

        /**
         * @var Product $product
         */
        foreach ( $products as $product ) {
            $this->existing_products[$product->sku] = $product;
        }
    }

    /**
	 * Upload image
	 *
     * @throws InvalidParametersException
     * 
	 * @param string $image_url
	 * @param string $slug
	 * @param int $product_id
	 * @param string $industry
     * @return string
	 */
	protected function upload_image( $image_url, $slug, $product_id, $industry ) {
        if ( is_null( $industry ) )
			throw new InvalidParametersException( _('Industry must not be null') );
        
		$new_image_name = $slug;
		$image_extension = strtolower( f::extension( $image_url ) );
        $full_image_name = "{$new_image_name}.{$image_extension}";
		$image_path = '/gsr/systems/backend/admin/media/downloads/scratchy/' . $full_image_name;

        // If it already exists, no reason to go on
		if( is_file( $image_path ) && curl::check_file( "http://{$industry}.retailcatalog.us/products/{$product_id}/thumbnail/{$full_image_name}" ) )
			return $full_image_name;

        // Open the file to write to it
		$fp = fopen( $image_path, 'wb' );

        // Save the file
		$this->curl->save_file( $image_url, $fp );

        // Close file
		fclose( $fp );
		
		$this->file->upload_image( $image_path, $new_image_name, 350, 350, $industry, "products/{$product_id}/", false, true );
		$this->file->upload_image( $image_path, $new_image_name, 64, 64, $industry, "products/{$product_id}/thumbnail/", false, true );
		$this->file->upload_image( $image_path, $new_image_name, 200, 200, $industry, "products/{$product_id}/small/", false, true );
		$full_image_name = $this->file->upload_image( $image_path, $new_image_name, 1000, 1000, $industry, "products/{$product_id}/large/" );

		if( file_exists( $image_path ) )
			@unlink( $image_path );

		return $full_image_name;
	}

    /**
     * Check to see if a Slug is already being used
     *
     * @param string $slug
     * @return string
     */
    protected function unique_slug( $slug ) {
        $existing_slug = $this->prepare( "SELECT `slug` FROM `products` WHERE `user_id_created` = :user_id_created AND `publish_visibility` <> 'deleted' AND `slug` = :slug"
            , 'is'
            , array( ':user_id_created' => $this->user_id, ':slug' => $slug )
        )->get_var();

        // See if the slug already exists
        if ( $slug == $existing_slug ) {
            // Check to see if it has been incremented before
            if ( preg_match( '/-([0-9]+)$/', $slug, $matches ) > 0 ) {
                // The number to increment it by
                $increment = $matches[1] * 1 + 1;

                // Give it the new increment
                $slug = preg_replace( '/-[0-9]+$/', "-$increment", $slug );

                // Make sure it's unique
                $slug = $this->unique_slug( $slug );
            } else {
                // It has not been incremented before, start with 2
                $slug .= '-2';
            }
        }

        // Return the unique slug
        return $slug;
    }

    // The functions that must be created
    abstract protected function setup();
    abstract protected function get_data();
    abstract protected function process();
    abstract protected function send_report();
}