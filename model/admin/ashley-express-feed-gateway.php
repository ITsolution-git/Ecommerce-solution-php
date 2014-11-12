<?php
/**
 * Handles ashley import
 *
 * @package Grey Suit Retail
 * @since 1.0
 */
class AshleyExpressFeedGateway extends ActiveRecordBase {
	const FTP_URL = 'ftp.ashleyfurniture.com';
    const USER_ID = 353; // Ashley
    const COMPLETE_CATALOG_MINIMUM = 10485760; // 10mb In bytes

    protected $omit_sites = array( 161, 187, 296, 343, 341, 345, 371, 404, 456, 461, 464, 468, 492, 494, 501, 557, 572
        , 582, 588, 599, 606, 614, 641, 644, 649, 660, 667, 668, 702, 760, 928, 897, 911, 926, 972, 1011, 1016, 1032
        , 1034, 1071, 1088, 1091, 1105, 1112, 1117, 1118, 1119, 1152, 1156, 1204
    );

    /**
     * @var SimpleXMLElement
     */
    private $xml;

	/**
	 * Creates new Database instance
	 */
	public function __construct() {
		// Load database library into $this->db (can be omitted if not required)
		parent::__construct('');

        // Set specs to last longer
        ini_set( 'max_execution_time', 3600 ); // 1 hour
		ini_set( 'memory_limit', '512M' );
		set_time_limit( 3600 );

        if ( !class_exists( 'WebsiteOrder' ) ) {
            require_once MODEL_PATH . '../account/website-order.php';
            require_once MODEL_PATH . '../account/website-shipping-method.php';
        }
    }

    /**
     * Get Feed Accounts
     *
     * @return Account[]
     */
    protected function get_feed_accounts() {
        $accounts = $this->get_results( "SELECT ws.`website_id` FROM `website_settings` AS ws LEFT JOIN `websites` AS w ON ( w.`website_id` = ws.`website_id` ) LEFT JOIN `website_settings` AS ws2 ON ( ws2.`website_id` = w.`website_id` AND ws2.`key` = 'feed-last-run' ) WHERE ws.`key` = 'ashley-ftp-password' AND ws.`value` <> '' AND w.`status` = 1 ORDER BY ws2.`value`", PDO::FETCH_CLASS, 'Account' );
        foreach ( $accounts as $k => $account ) {
            $is_ashley_express = (bool)$account->get_settings( 'ashley-express' );
            if ( !$is_ashley_express ) {
                unset( $accounts[$k] );
            }
        }
        return $accounts;
    }

    /**
     * Get FTP
     *
     * @param Account $account
     * @return Ftp
     */
    public function get_ftp( Account $account ) {
        // Initialize variables
        $settings = $account->get_settings( 'ashley-ftp-username', 'ashley-ftp-password', 'ashley-alternate-folder' );
        $username = security::decrypt( base64_decode( $settings['ashley-ftp-username'] ), ENCRYPTION_KEY );
        $password = security::decrypt( base64_decode( $settings['ashley-ftp-password'] ), ENCRYPTION_KEY );

        $folder = str_replace( 'CE_', '', $username );

        // Modify variables as necessary
        if ( '-' != substr( $folder, -1 ) )
            $folder .= '-';

        $subfolder = ( '1' == $settings['ashley-alternate-folder'] ) ? 'Outbound/Items' : 'Outbound';

        // Setup FTP
        $ftp = new Ftp( "/CustEDI/$folder/$subfolder/" );

        // Set login information
        $ftp->host     = self::FTP_URL;
        $ftp->username = $username;
        $ftp->password = $password;
        $ftp->port     = 21;

        // Connect
        $ftp->connect();

        return $ftp;
    }

    /**
     * Get XML
     *
     * @param Account $account
     * @param string $prefix
     * @param bool $archive
     * @return SimpleXMLElement
     */
    private function get_xml( $account, $prefix = null, $archive = false ) {
        // Get FTP

        $ftp = $this->get_ftp( $account );

        // Figure out what file we're getting
        if( empty( $file ) ) {
            // Get al ist of the files
            $files = array_reverse( $ftp->raw_list() );

            foreach ( $files as $f ) {
                if ( 'xml' != f::extension( $f['name'] ) )
                    continue;

                $file_name = f::name( $f['name'] );
                if ( $prefix && strpos( $file_name, $prefix ) === false )
                    continue;

                $file = $f['name'];
            }
        }

        // Can't do anything without a file
        if ( empty( $file ) )
            return null;

        // Make sure the folder has been created
        $local_folder = sys_get_temp_dir() . '/';

        // Grab the latest file
        if( !file_exists( $local_folder . $file ) )
            $ftp->get( $file, '', $local_folder );

        $this->xml = simplexml_load_file( $local_folder . $file );

        // Now remove the file
        unlink( $local_folder . $file );

        if ( $archive ) {
            $dir_parts = explode( '/', trim( $ftp->cwd, '/' ) );
            array_pop( $dir_parts );
            $dir_parts[] = 'Archive';
            $archive_folder = '/' . implode( '/', $dir_parts ) . '/';

            @$ftp->mkdir( $archive_folder );
            $ftp->rename( $file, $archive_folder . $file );
        }

        return $this->xml;

    }

	/**
     *  Run Flag Products (all accounts)
     */
    public function run_flag_products_all() {
        // Get Feed Accounts
        $accounts = $this->get_feed_accounts();

        if ( is_array( $accounts ) )
        foreach( $accounts as $account ) {
            $this->run_flag_products( $account );
        }
    }

	/**
	 * Run Flag Products
     * This will flag all Ashley Express products so they can enter the Ashley Express program.
	 *
	 * @param Account $account
	 * @return bool
	 */
	public function run_flag_products( Account $account ) {

        if ( !$this->get_xml( $account, '846-' ) ) {
            // Remove all products from Ashley Express
            $this->flag_bulk( $account, array( ) );
            $this->flag_packages( $account, array( ) );
            return false;
        }

        // Declare array
        $ashley_express_skus = array();
//        // Get Ashley Packages
//        $package_skus = array();
//        $packages = $this->get_ashley_packages();
//        $ashley_package_product_ids = array();

        // Set Settings: Ashley Express Buyer ID from XML
        $ns = $this->xml->getDocNamespaces();
        if ( isset( $this->xml->inquiry->potentialBuyer ) ) {
            $account->set_settings( array(
                'ashley-express-buyer-id' => (string)$this->xml->inquiry->potentialBuyer->children( $ns['fnParty'] )->attributes()->partyIdentifierCode
            ) );
        }

        // Generate array of our items
        /**
         * @var SimpleXMLElement $item
         */
        foreach ( $this->xml->items->itemAdvice as $item ) {

            $sku = $item->itemId->itemIdentifier['itemNumber'];

            foreach ( $item->itemAvailability as $availability ) {
                // Item is Ashley Express only if stock for current availability is greater than 5
                if ( $availability['availability'] == 'current' ) {
                    if ( $availability->availQty['value'] > 5 ) {
                        $ashley_express_skus[] = $sku;
                    }
                    break;
                }
            }
		}

        $account_ae_skus = $this->flag_bulk( $account, $ashley_express_skus );

        // Add Packages -------------------------------------------
        // --------------------------------------------------------
        $packages = $this->get_ashley_packages();
        $package_skus = array();
        $group_items = array();
        foreach( $account_ae_skus as $sku ) {
            // Setup packages
            if ( stristr( $sku, '-' ) ) {
                list( $series, $item ) = explode( '-', $sku, 2 );
            } else if ( strlen( $sku ) == 7 && is_numeric( $sku{0} ) ) {
                $series = substr( $sku, 0, 5 );
                $item = substr( $sku, 5 );
            } else if ( strlen( $sku ) == 8 && ctype_alpha( $sku{0} ) ) {
                $series = substr( $sku, 0, 6 );
                $item = substr( $sku, 6 );
            } else {
                continue;
            }
            $package_skus[$series][] = $item;
        }

        // Add packages if they have all the pieces
        foreach ( $packages as $series => $items ) {
            // Go through each item
            foreach ( $items as $product_id => $package_pieces ) {
                // See if they have all the items necessary
                foreach ( $package_pieces as $item ) {
                    // Check if it is a series such as "W123-45" or "W12345"
                    if ( is_array( $package_skus[$series] ) && in_array( $item, $package_skus[$series] ) ) {
                        $group_items[$series] = true;
                        continue;
                    }

                    if ( in_array( $series . $item, $account_ae_skus ) ) {
                        $group_items[$series] = true;
                        continue;
                    }

                    // If they don't have both, then stop this item
                    unset ( $group_items[$series] );
                    continue 2; // Drop out of both
                }

                // Add to packages list
                $ashley_package_product_ids[] = $product_id;
            }
        }

        $this->flag_packages( $account, $ashley_package_product_ids );

	}

    /**
     * Flag a Bulk of Products as Ashley Express
     * Removes Flag for products that are no in $skus
     *
     * @param Account $account
     * @param string[] $skus array of skus
     * @return array with skus really added as AE
     */
    private function flag_bulk( $account, $skus ) {

        $this->prepare("
                DELETE wpae
                FROM `website_product_ashley_express` wpae
                INNER JOIN `products` p ON ( p.`product_id` = wpae.`product_id` )
                WHERE wpae.`website_id` = :website_id
                  AND p.`user_id_created` = :user_id_created
                  " . ( $skus ? ( "AND p.`sku` NOT IN ('". implode("','", $skus) ."')" ) : "" )
            , 'iii'
            , array(
                ':website_id' => $account->website_id
                , ':user_id_created' => self::USER_ID
            )
        )->query();

        // If no skus, there's nothing to add
        if ( !$skus )
            return;

        $this->prepare("
                INSERT IGNORE INTO `website_product_ashley_express` ( website_id, product_id )
                SELECT :website_id, p.product_id
                FROM `products` p
                INNER JOIN `website_product_ashley_express_master` wpaem ON p.`sku` = wpaem.`sku`
                WHERE p.`user_id_created` = :user_id_created
                  AND p.`publish_visibility` = 'public'
                  AND p.`status` = 'in-stock'
                  AND p.`sku` IN ('". implode("','", $skus) ."')"
            , 'iii'
            , array(
                ':website_id' => $account->website_id
                , ':user_id_created' => self::USER_ID
            )
        )->query();

        return $this->get_results("
            SELECT DISTINCT p.`sku`
            FROM products p
            INNER JOIN website_product_shley_express wpae ON p.product_id = wpae.product_id
            WHERE wpae.website_id = {$account->website_id}"
            , PDO::FETCH_COLUMN
        );

    }

    /**
     * Flag Packages
     * @param Account $account
     * @param int[] $product_ids
     */
    private function flag_packages( $account, $product_ids ) {
        $this->prepare("
                DELETE wpae
                FROM `website_product_ashley_express` wpae
                INNER JOIN `products` p ON ( p.`product_id` = wpae.`product_id` )
                WHERE wpae.`website_id` = :website_id
                  AND p.`user_id_created` = :user_id_created
                  " . ( $product_ids ? ( "AND p.`product_id` NOT IN (". implode(",", $product_ids) .")" ) : "" )
            , 'iii'
            , array(
                ':website_id' => $account->website_id
                , ':user_id_created' => 1477
            )
        )->query();

        // If no skus, there's nothing to add
        if ( !$product_ids )
            return;

        $this->prepare("
                INSERT IGNORE INTO `website_product_ashley_express` ( website_id, product_id )
                SELECT :website_id, p.product_id
                FROM `products` p
                WHERE p.`user_id_created` = :user_id_created
                  AND p.`product_id` IN (". implode(",", $product_ids) .")"
            , 'iii'
            , array(
                ':website_id' => $account->website_id
                , ':user_id_created' => 1477
            )
        )->query();
    }

    /**
     *  Run Order Acknowledgement (all accounts)
     */
    public function run_order_acknowledgement_all() {
        // Get Feed Accounts
        $accounts = $this->get_feed_accounts();

        if ( is_array( $accounts ) )
            foreach( $accounts as $account ) {
                $this->run_order_acknowledgement( $account );
            }
    }

    /**
     * Run Order Acknowledgement
     * This will check for Orders response after they are created
     *
     * @param Account $account
     */
    public function run_order_acknowledgement( Account $account ) {

        echo "Working with Account {$account->id}\n";

        while( $this->get_xml( $account, '855-', true ) !== null ) {

            $order_id = (string)$this->xml->ackOrder->orderDocument['id'];
            echo "Order $order_id \n";

            $order = new WebsiteOrder();
            $order->get( $order_id, $account->id );

            echo "Order: ". json_encode($order) ." \n";

            if ( !$order->id )
                continue;

            if ( $order->is_ashley_express() )
                continue;

            if ( $order->status != WebsiteOrder::STATUS_PURCHASED )
                continue;

            echo "Order Updated\n";

            $order->status = WebsiteOrder::STATUS_RECEIVED;
            $order->save();
        }

        echo "Finished with Account\n----\n";

    }

    /**
     * Run Order ASN (Advanced Ship Notice) (all accounts)
     */
    public function run_order_asn_all() {
        // Get Feed Accounts
        $accounts = $this->get_feed_accounts();

        if ( is_array( $accounts ) )
            foreach( $accounts as $account ) {
                $this->run_order_asn( $account );
            }
    }


    /**
     * Run Order ASN (Advanced Ship Notice)
     * This will check for Orders response after they are marked at Received by run_order_acknowledgement()
     *
     * @param Account $account
     */
    public function run_order_asn( Account $account ) {

        echo "Working with Account {$account->id}\n";

        while( $this->get_xml( $account, '856-', true ) !== null ) {

            $order_id = (string)$this->xml->shipment->order->orderReferenceNumber['referenceNumberValue'];
            echo "Order $order_id \n";

            $order = new WebsiteOrder();
            $order->get( $order_id, $account->id );

            echo "Order: ". json_encode($order) ." \n";

            if ( !$order->id )
                continue;

            if ( $order->is_ashley_express() )
                continue;

            if ( $order->status != WebsiteOrder::STATUS_RECEIVED )
                continue;

            echo "Order Updated\n";

            $shipping_track_numbers = array();
            try {
                foreach ( $this->xml->shipment->order->item as $item ) {
                    foreach ( $item->itemQuantity->unitsShipped->pieceIdentification->pieceIdentificationNumber as $identification ) {
                        $shipping_track_numbers[] = (string)$identification;
                    }
                }
            } catch ( Exception $e ) { }

            $order->shipping_track_number = implode( ',', $shipping_track_numbers );
            $order->status = WebsiteOrder::STATUS_SHIPPED;
            $order->save();

        }

        echo "Finished with Account\n----\n";

    }

    /**
     * Run Shipping Prices
     *
     * @param Account $account
     * @param $filename
     * @return int number of updated products
     * @throws Exception
     */
    public function run_shipping_prices( Account $account, $filename ) {
        throw new Exception("Method Removed");

        $file_extension = strtolower( f::extension( $filename ) );

        // get data regarding file extension
        switch ( $file_extension ) {
            case 'xls':
                // Load excel reader
                library('Excel_Reader/Excel_Reader');
                $er = new Excel_Reader();
                // Set the basics and then read in the rows
                $er->setOutputEncoding('ASCII');
                $er->read( $filename );

                $rows = $er->sheets[0]['cells'];

                break;

            case 'csv':
                // Make sure it's opened properly
                $handler = fopen( $filename, 'r' );

                // If there is an error or now user id, return
                if ( !$handler ) {
                    throw new Exception( 'Could not read your file' );
                }

                // Loop through the rows
                while ( $row = fgetcsv( $handler ) ) {
                    $rows[] = $row;
                }

                fclose( $handler );

                break;

            default:
                throw new Exception( 'Could not read your file, unsupported extension' );
        }

        // Get Ashley Products
        $products_result = $this->prepare(
            'SELECT product_id, sku FROM products WHERE user_id_created = :user_id'
            , 'i'
            , array( ':user_id' => self::USER_ID )
        )->get_results( PDO::FETCH_ASSOC );
        $products = ar::assign_key( $products_result, 'sku' );

        // Reset all shipping methods
        $this->prepare(
            'UPDATE website_product_shipping_method SET shipping_price = NULL WHERE website_id = :website_id'
            , 'i'
            , array( ':website_id' => $account->id )
        )->query();

        // Update shipping methods
        $headers = array_shift( $rows );
        $updated = 0;
        foreach ( $rows as $values ) {
            $row = array_combine( $headers, $values );

            $sku = $row['Ashley Item'];
            $shipping_price = (float) $row['Estimated Express Freight Per Carton'];
            $product_id = $products[$sku]['product_id'];

            if ( $product_id && $shipping_price ) {
                $this->prepare(
                    'UPDATE website_product_shipping_method SET shipping_price = :shipping_price WHERE website_id = :website_id AND product_id = :product_id'
                    , 'dii'
                    , array( ':shipping_price' => $shipping_price, ':website_id' => $account->id, ':product_id' => $product_id )
                )->query();
                $updated++;
            }

        }

        return $updated;
    }

    /**
     * Get Ashley Packages
     *
     * @return array
     */
    protected function get_ashley_packages() {
        // Ashley Packages
        $products = ar::assign_key( $this->get_results( 'SELECT `product_id`, `sku` FROM `products` WHERE `user_id_created` = 1477', PDO::FETCH_ASSOC ), 'sku', true );

        $ashley_packages = array();

        // Return all Ashley Packages
        foreach ( $products as $sku => $product_id ) {
            $sku_pieces = explode( '/', $sku );

            // Remove anything within parenthesis on SKU Pieces
            $regex = '/\(([^)]*)\)/';
            foreach ( $sku_pieces as $k => $sp ) {
                $sku_pieces[$k] = preg_replace($regex, '', $sp);
            }

            $series = array_shift( $sku_pieces );

            $ashley_packages[$series][$product_id] = $sku_pieces;
        }

        return $ashley_packages;
    }

}
