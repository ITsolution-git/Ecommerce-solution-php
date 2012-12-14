<?php
/**
 * Handles All package feed gateways
 *
 * @package Grey Suit Retail
 * @since 1.0
 */
class AshleyPackageProductFeedGateway extends ProductFeedGateway {
    const FTP_URL = 'ftp.ashleyfurniture.com';
    const IMAGE_URL = 'https://www.ashleydirect.com/graphics/';
    const USER_ID = 1477; // Ashley

    /**
     * Hold ashley items
     */
    protected $packages, $series, $package_templates;

    /**
     * Holds product
     * @var Product
     */
    protected $product;

    /**
     * Hold all the non existent groups
     * @var array
     */
    protected $non_existent_groups = array();

    /**
     * Hold the file to XML data from
     * @var string|null
     */
    protected $file = null;

    /**
     * Hold Ashley API
     * @var Ashley_API
     */
    protected $ashley;

    /**
     * Hold Ashley Products by sku
     * @var array
     */
    protected $ashley_products;

    /**
     * Hold the ashley brands
     */
    protected $brands = array(
        'Ashley' => 8
        , 'Signature Design' => 170
        , 'Benchcraft' => 8
        , 'Millennium' => 171
        , 'Room Solutions' => 8
    );

    /**
     * Hold the ashley categories
     */
    protected $categories = array(
        'Accents' => 360
        , 'Stationary Upholstery' => 218
        , 'Motion Upholstery' => 348
        , 'Sectionals' => 226
        , 'Chairs' => 221
        , 'Stationary Leather' => 255
        , 'Recliners' => 222
        , 'Motion Leather' => 255
        , 'Dining' => 347
        , 'Master Bedroom' => 228 // Bedroom > Bedroom Groups
        , 'Metal Beds' => 685
        , 'Youth Bedroom' => 267
        , 'Top of Bed' => 463
        , 'Curios' => 434
        , 'Home Office' => 328
        , 'Lamps' => 194
        , 'Mattresses' => 0
        , 'Rugs' => 338
        , 'Occasional' => 382
        , 'Walls' => 336
        , 'Entertainment' => 335
    );

    /**
     * Hold Name Translation
     */
    protected $names = array(
        'LAFSectional,RAFSectional' => 'Sectional'
        , 'Sofa, Love Seat' => 'Sofa & Loveseat'
        , 'Recl Sofa' => 'Reclining Sofa'
        , 'Rec Sofa, Rec LoveSeat, Wdge' => 'Reclining Sofa, Reclining Loveseat, Wedge'
        , 'Sofa' => 'Sofa'
        , 'LAF, RAF, Wdge' => 'Sectional with wedge'
        , 'Rec Sofa, Rec LoveSeat' => 'Reclining Sofa & Reclining Loveseat'
        , 'Table, 4 Sides' => 'Table & 4 Side Chairs'
        , 'Table, 4 Sides, 2 Arms' => 'Table, 4 side chairs & 2 arm chairs'
        , 'Desk and Top - Roll Top / Hutch top' => 'Desk & Top - Roll Top/Hutch Top'
        , 'Rec Sofa, LoveSeat' => 'Reclining Sofa & Loveseat'
        , 'Sofa, Love Seat, Reclining Chair' => 'Sofa, Loveseat & Chair'
        , 'Sofa, Love Seat, Chair' => 'Reclining Sofa, Loveseat and Chair'
        , 'Sofa, Sleeper, Chair' => 'Sleeper Sofa & Chair'
        , 'Throw' => 'Throw'
        , 'Twin Bed w/Trundle, Dresser,Mirror' => 'Twin Bed w/Trundle, Dresser & Mirror'
        , 'Bunk Bed - Twin/Twin, Dresser, Mirror' => 'Bunk Bed (twin/twin), Dresser & Mirror'
        , 'Full Bed, Dresser, Mirror' => 'Full Bed, Dresser & Mirror'
        , 'Twin Bed w/Trundle' => 'Twin bed w/Trundle'
        , 'Dresser, Mirror' => 'Dresser & Mirror'
        , 'Twin Bed' => 'Twin Bed (Headboard, footboard, rails)'
        , 'Full Bed' => 'Full Bed (Headboard, footboard, rails)'
        , 'Twin Bed, Dresser, Mirror' => 'Twin Bed, Dresser & Mirror'
        , 'Full Bed w/ Trundle' => 'Full Bed w/Trundle'
        , 'Bunk Bed - Twin/Twin' => 'Bunk Bed (twin/twin)'
        , 'Q Bed' => 'Queen Bedroom Group'//'Queen Bed (Headboard, Footboard, Rails)'
        , 'Q Bed, Dresser, Mirror' => 'Queen Bedroom Group'//'Queen Bed (Headboard, Footboard, Rails), Dresser & Mirror'
        , 'Q Hdbd, Dresser, Mirror' => 'Queen Bedroom Group'//'Queen Headboard, Dresser & Mirror'
        , 'Q HdBd, Dresser, Mirror, 1NS' => 'Queen Bedroom Group'//'Queen Headboard, Dresser, Mirror & Nightstand'
        , 'Q Bed, Dresser, Mirror, Chest, 1NS' => 'Queen Bedroom Group'//'Queen Bed (Headboard, Footboard, Rails), Mirror, Chest & Nightstand'
        , 'Q Hdbd, Dresser, Mirror, Chest' => 'Queen Bedroom Group'//'Queen Headboard, Dresser, Mirror & Chest'
        , 'Q Bed w/Storage, Dresser, Mirror' => 'Queen Bedroom Group'//'Queen Storage Bed, Dresser & Mirror'
        , 'Bunk Bed' => 'Bunk Bed'
        , 'Loft Bed' => 'Loft Bed'
        , 'Queen Metal Bed' => 'Queen Metal Bed'
        , 'King Metal Bed' => 'King Metal Bed'
        , 'Twin Bed w/ Storage, Dresser, Mirror' => 'Twin Storage Bed, Dresser & Mirror'
        , 'Full Bed w/ Storage, Dresser, Mirror' => 'Full Storage Bed, Dresser & Mirror'
        , 'Twin Bed w/ Storage' => 'Twin Headboard, Dresser, Mirror & Chest'
        , 'Twin Hdbd, Dresser, Mirror, Chest' => 'Twin Headboard, Dresser, Mirror & Chest'
        , 'Twin Hdbd, Dresser, Mirror' => 'Twin Headboard, Dresser & Mirror'
        , 'Full Captain Storage Bed' => 'Full Captains Bed with Storage'
        , 'Full Bed w/Trundle, Dresser,Mirror' => 'Full Bed w/Trundle, Dresser & Mirror'
        , 'Full Capt Bed, Storage 2 Sides' => 'Full Captains Bed with 2 sides Storage'
        , 'Twin Captain Storage Bed' => 'Twin Captains Bed with storage'
        , 'Full Bed w/ Storage' => 'Full Bed (Headboard, footboard, rails) with storage'
        , 'Q Bed, Dresser, Mirror, 2NS' => 'Queen Bed (Headboard, Footboard, Rails), Dresser, Mirror & 2 Nightstands'
        , 'K Bed' => 'King Bed (Headboard, Footboard, Rails)'
        , 'King Bed w/Storage' => 'King Bed with Storage'
        , 'Q Bed w/Storage' => 'Queen Bed with Storage'
        , 'Desk' => 'Desk'
        , 'Bunk Bed - Twin/Full' => 'Bund Bed (Twin/Full)'
        , 'Bed' => 'Bed (Headboard, Footboard, Rails)'
        , 'Day Bed w/Trundle' => 'Day Bed, Dresser & Mirror'
        , 'DayBed, Dresser, Mirror' => 'Day Bed, Dresser & Mirror'
        , 'Full Hdbd, Dresser, Mirror' => 'Full Headboard, Dresser & Mirror'
        , 'Twin Bed w/Canopy' => 'Twin Bed w/Canopy'
        , 'Full Bed w/Canopy' => 'Full Bed w/Canopy'
        , 'Day Bed' => 'Day Bed'
        , 'Full Bed, Dresser, Mirror, Chest' => 'Full Bed (Headboard, footboard, rails), Dresser, Mirror & Chest'
        , 'Twin Bed, Dresser, Mirror, Chest' => 'Twin Bed, Dresser, Mirror & Chest'
        , 'Armoire' => 'Armoire'
        , 'Q Bed, Dresser, Mirror, 1NS' => 'Queen Bedroom Group'//'Queen Bed, Dresser, Mirror & Nightstand'
        , 'Q Bed, Dresser, Mirror, Chest' => 'Queen Bedroom Group'//'Queen Bed, Dresser, Mirror & Chest'
        , 'HdBd, Dresser, Mirror' => 'Headboard, Dresser & Mirror'
        , 'Q HdBd, Dresser, Mirror, 2NS' => 'Queen Bedroom Group'//'Queen Headboard, Dresser, Mirror & 2 Nightstands'
        , 'Q Hdbd, Dresser, Mirror, Chest, 1NS' => 'Queen Bedroom Group'//'Queen Headboard, Dresser, Mirror, Chest & Nighstand'
        , 'SprdHdBd, 2 NS' => 'Spread Headboard & 2 Nightstands'
        , 'Bunk Bed - Twin/Full, Dresser, Mirror' => 'Bunk Bed (twin/full), Dresser & Mirror'
        , 'QBed,Dressr,Mrror,Chest,2NS' => 'Queen Bedroom Group'//'Queen Bed, Dresser, Mirror, Chest & 2 Nightstands'
        , 'HdBd, Dresser, Mirror, Chest' => 'Headboard, Dresser, Mirror & Chest'
        , 'Cal King Bed' => 'Cal King Bed'
        , 'Q Bed, Dresser, Mirror, Armoire' => 'Queen Bedroom Group'//'Queen Bed, Dresser, Mirror & Armoire'
        , 'QBed,Dressr,Mirror,Armr,2NS' => 'Queen Bed, Dresser, Mirror, Armoire, 2 Nightstands'
        , 'KBed,Dressr,Mirror,Armr,2NS' => 'King Bed, Dresser, Mirror, Armoire, 2 Nightstands'
        , 'KBed,Dressr,Mrror,Chest,2NS' => 'King Bed, Dresser, Mirror, Chest, 2 Nightstands'
        , 'K Bed, Dresser, Mirror, 1 NS' => 'King Bed, Dresser, Mirror, Nightstand'
        , 'K Bed, Dresser, Mirror' => 'King Bed, Dresser & Mirror'
        , 'Bunk Bed, Dresser, Mirror' => 'Bunk Bed, Dresser & Mirror'
        , 'Twin Bed, Dresser, Mirror, NS' => 'Twin Bed, Dresser, Mirror & Nighstand'
        , 'Full/Full Bunk, Dresser, Mirror' => 'Full/Full Bunk Bed, Dresser & Mirror'
        , 'Q Hdbd' => 'Queen Headboard'
        , 'Bunk Bed - Twin/Full, Dresser, Mirror, Chest' => 'Bunk Bed (twin/full), Dresser, Mirror & Chest'
        , 'Bunk Bed - Twin/Full, Dresser, Mirror, NS' => 'Bunk Bed (twin/full), Dresser, Mirror & Nighstand'
        , 'K Bed, Dresser, Mirror, Chest' => 'King Bed, Dresser, Mirror & Chest'
        , 'K Bed, Dresser, Mirror, Armoire' => 'King Bed, Dresser, Mirror & Armoire'
        , 'Cal King Bed, Dresser, Mirror' => 'Cal King Bed, Dresser & Mirror'
        , 'Cal King, Dresser, Mirror, Chest' => 'Cal King Bed, Dresser, Mirror & Chest'
        , 'Cal King, Dresser, Mirror, Armoire' => 'Cal King Bed, Dresser, Mirror & Armoire'
        , 'Cal King, Dresser, Mirror, 1NS' => 'Cal King Bed, Dresser, Mirror & Nighstand'
        , 'Media Chest' => 'Media Chest'
        , 'Pub / Breakfast Table, 4 Bar Stools' => 'Pub Table & 4 Bar Stools'
        , 'Pub / Breakfast Table, 2 Bar Stools' => 'Pub Table & 2 Bar Stools'
        , 'Pub/Breakfast Table, 5 Bar Stools' => 'Pub Table & 5 Bar Stools'
        , 'Table, 2 Sides, 2 Double Chairs, Corner Chair' => 'Table, 2 Side Chairs, 2 Double Chairs, Corner Chair'
        , 'Table, 2 Sides, 2 Double Chairs' => 'Table, 2 Side Chairs, 2 Double Chairs'
        , 'Table, 6 Sides' => 'Table & 6 Side Chairs'
        , 'Table, 6 Sides, Bench' => 'Table, 6 Side Chairs & Bench'
        , 'Table, 8 Sides' => 'Table & 8 Side Chairs'
        , 'Storage Piece' => 'Storage Piece'
        , 'Table, 4 Sides, Bench' => 'Table, 4 Side Chairs & Bench'
        , 'Table, 2 Sides' => 'Table & 2 Side Chairs'
        , 'Bar Stools' => 'Bar Stools'
        , 'Table, 4 Sides, Storage' => 'Table, 4 Side Chairs & Storage'
        , 'Table, 6 Sides, Storage' => 'Table, 6 Side Chairs & Storage'
        , 'Dining Table' => 'Dining Table'
        , 'Pub Table / 2 Dbl Bar Stools / Corner Stool' => 'Pub Table, 2 Bar Stools & Corner Stool'
        , 'Table, 2 Sides, Bench' => 'Table, 2 side chairs & bench'
        , 'Pub / Breakfast Table, Bench, 4 Bar Stools' => 'Pub Table, Bench & 4 Bar Stools'
        , 'Pub Table, 2 Bar Stools, 2 Double Bar Stools, Corner Stool' => 'Pub Table, 2 Bar Stools, 2 Double Bar Stools, Corner Stool'
        , 'Pub / Breakfast Table, 6 Bar Stools' => 'Pub Table & 6 Bar Stools'
        , 'Bar, 2 Bar Stools' => 'Bar & 2 Bar Stools'
        , 'Butterfly Table, 4 Barstools' => 'Butterfly Table & 4 Bar Stools'
        , 'Pub/Breakfast Table, 3 Bar Stools' => 'Pub Table & 3 Bar Stools'
        , 'Table, 4 Sides, 2 Arms, Storage' => 'Table, 4 Side Chairs, 2 Arm Chairs, Storage'
        , 'Pub / Breakfast Table, 2 Benches, 2 Bar Stools' => 'Table, 2 Benches, 2 Bar Stools'
        , 'Desk Chair' => 'Desk Chair'
        , 'Desk, Hutch' => 'Desk & Hutch'

    );

    /**
     * Categories by template description
     * @var array
     */
    protected $category_by_template_description = array(
        'Dresser, Mirror' => 696
    );

    /**
     * Construct
     */
    public function __construct() {
        parent::__construct( self::USER_ID );
    }

    /**
     * Do the setup to get anything we need
     */
    protected function setup() {
        ini_set( 'max_execution_time', 600 ); // 10 minutes
		ini_set( 'memory_limit', '512M' );
		set_time_limit( 600 );

        // Get libraries
        library('ashley-api/ashley-api');
        $this->ashley = new Ashley_API();
    }

    /**
     * Get Data from Ashley
     */
    protected function get_data() {
        // Get Ashley Products by SKU
        $this->ashley_products = $this->get_ashley_products_by_sku();

        echo str_repeat( ' ', 1000 );
		flush();

        // Get Templates
        $package_template_array = $this->ashley->get_package_templates();

        // Arrange templates
        foreach ( $package_template_array as $pta ) {
            $this->package_templates[(string)$pta->TemplateId] = $pta;
        }

        // Get packages
        $this->packages = $this->ashley->get_packages();

		echo str_repeat( ' ', 1000 );
		flush();

        // Get series
        $series_array = $this->ashley->get_series();

		echo str_repeat( ' ', 1000 );
		flush();

        // Arrange series
        foreach ( $series_array as $sa ) {
            $this->series[(string)$sa->SeriesNo] = $sa;
        }
    }

    /**
     * Now process everything with the data we have
     */
    protected function process() {
        $grouped_packages = array();

        // Generate array of our items
		foreach ( $this->packages as $item ) {
			/***** SETUP OF PRODUCT *****/

            // Trick to make sure the page doesn't timeout or segfault
            echo str_repeat( ' ', 50 );
            set_time_limit(30);
			flush();

            // Reset errors
            $this->reset_error();
            $add_category = false;

            /***** CHECK PRODUCT *****/

            // Setup the variables to see if we should continue
            $image = (string) $item->Image;

            // Image can't be empty
			$this->check( !empty( $image ) );

            // If it has an error, don't continue
            if ( $this->has_error() )
                continue;

            /***** GET PRODUCT *****/

            // Get Product
			$product = $this->get_existing_product( (string) $item->PackageName );

            // Now we have the product
            if ( !$product instanceof Product ) {
                $product = new Product();
                $product->website_id = 0;
                $product->user_id_created = self::USER_ID;
                $product->publish_visibility = 'public';
                $product->create();

                // Set publish date
                $product->publish_date = dt::now();

                // Need to add the category
                $add_category = true;
            }

            /***** PREPARE PRODUCT DATA *****/

            $package_series = $this->series[(string)$item->SeriesNo];
            $template = $this->package_templates[(string)$item->TemplateId];
            $sku = (string) $item->PackageName;

            // Get the name -- which may be hard if the description is empty
			if ( empty( $template->Descr ) ) {
				$sku_pieces = explode( '/', $sku );
				$series = array_shift( $sku_pieces );

				$name_pieces = array();

				foreach ( $sku_pieces as $sp ) {
					if ( isset( $this->ashley_products[$series . $sp] ) ) {
						$name_piece = str_replace( (string) $item->SeriesName, '', $this->ashley_products[$series . $sp] );
					} elseif( isset( $this->ashley_products[$series . '-' . $sp] ) ) {
						$name_piece = str_replace( (string) $item->SeriesName, '', $this->ashley_products[$series . '-' . $sp] );
					} else {
						continue;
					}

					$name_pieces[] = preg_replace( '/^ - /', '', $name_piece );
				}

				$name = $item->SeriesName . ' - ' . implode( ', ', $name_pieces );
			} else {
				$name = $item->SeriesName . ' ' . $this->names[(string)$template->Descr];
			}
				
            // Will have to format this
            $style_description = trim( (string) $package_series->StyleDescription );

            // Create description
            $description = format::convert_characters( format::autop( format::unautop( '<p>' . $package_series->Description . "</p>\n\n<p>" . $package_series->Features . "</p>\n\n<p>" . $package_series->SeriesColor . "</p>\n\n<p>" . $style_description . '</p>' ) ) );

            // Set product specs
            if ( empty( $style_description ) ) {
                $product_specs = '';
            } else {
                $product_specs = 'Style Description`' . $style_description . '`0';
            }

            // Now get old specs
            $product_specifications = unserialize( $product->product_specifications );
            $new_product_specifications = '';

            if( is_array( $product_specifications ) )
            foreach( $product_specifications as $ps ) {
                if( !empty( $product_specifications ) )
                    $new_product_specifications .= '|';

                $new_product_specifications .= $ps[0] . '`' . $ps[1] . '`' . $ps[2];
            }

            // Get Category ID
            if ( isset( $this->category_by_template_description[(string)$template->Descr] ) ) {
                $category_id = $this->category_by_template_description[(string)$template->Descr];
            } else {
                $category_id = $this->categories[(string)$package_series->Grouping];
            }

            // If we have to group products
            switch ( $category_id ) {
                // Sectionals
                case 226:
                    if ( isset( $grouped_packages[(string)$item->SeriesNo] ) )
                        $product->publish_visibility = 'deleted';

                    $grouped_packages[(string)$item->SeriesNo] = true;
                    $name = $item->SeriesName . ' Sectional';
                break;

                // Bedroom Groups
                case 228:
                    preg_match( '/([0-9]+)(?:[SN]|\/91|\/92|\/93|\/96S?|\/98|\/99)?$/', $sku, $matches );
					$last_sku = $matches[1];
                    $add = true;
					
                    if ( isset( $grouped_packages[(string)$item->SeriesNo][$last_sku] ) )
                        $product->publish_visibility = 'deleted';

                    switch ( $last_sku ) {
                        case '55':
                            $name = $item->SeriesName . ' Queen Panel Headboard Only Bedroom Group';
                        break;

                        case '57':
                            $name = $item->SeriesName . ' Bedroom Group';
                        break;

                        case '55':
                        case '65':
                            $name = $item->SeriesName . ' Queen Panel Bedroom Group';
                        break;

                        case '67':
                            $name = $item->SeriesName . ' Queen Poster Headboard Only Bedroom Group';
                        break;

                        case '71':
                            $name = $item->SeriesName . ' Queen Poster Bedroom Group';
                        break;

                        case '77':
                            $name = $item->SeriesName . ' Queen Sleigh Bedroom Group';
                        break;

                        case '86':
                            $name = $item->SeriesName . ' Full Panel Bedroom Group';
                        break;

                        case '87':
                        case '88':
                            $name = $item->SeriesName . ' Full Sleigh Bedroom Group';
                        break;

                        default:
                            $add = false;
                        break;
                    }

                    if ( $add )
                        $grouped_packages[(string)$item->SeriesNo][$last_sku] = true;
                break;

                // Lamps
				case 194:
				break;

                default:break;
            }

            /***** ADD PRODUCT DATA *****/

            // Reset the product to being "not" identical
            $this->reset_identical();

            /** Add Category **/
            if ( $add_category ) {
                $product->delete_categories();
                $product->add_category( $category_id );
            }

            /** Set Product Data */
            $product->name = $this->identical( $name, $product->name, 'name' );
            $product->slug = $this->identical( str_replace( '---', '-', format::slug( $name ) ), $product->slug, 'slug' );
            $product->sku = $this->identical( $sku, $product->sku, 'sku' );
            $product->brand_id = $this->identical( $this->brands[(string)$package_series->Showroom], $product->brand_id, 'brand' );
            $product->description = $this->identical( $description, format::autop( format::unautop( $product->description ) ), 'description' );

            /** Product Specs are special */
            $product_specifications = explode( '|', $this->identical( $product_specs, $new_product_specifications, 'product-specifications' ) );

            $product_specifications_array = array();

            foreach ( $product_specifications as $ps ) {
                $product_specifications_array[] = explode( '`', $ps );
            }

            $product->product_specifications = serialize( $product_specifications_array );

            /***** ADD PRODUCT IMAGES *****/

            // Let's hope it's big!
			$image_url = self::IMAGE_URL . $image;

            // Setup images array
            $images = explode( '|', $product->images );

            if ( ( 0 == count( $images ) || empty( $images[0] ) ) && !empty( $image ) && curl::check_file( $image_url ) ) {
                /**
                 * @var string $industry
                 */
                $image_name = $this->upload_image( $image_url, $product->slug, $product->id, $industry );

                if ( !is_array( $images ) || !in_array( $image_name, $images ) ) {
                    $this->not_identical[] = 'images';
                    $images[] = $image_name;

                    $product->add_images( $images );
                }
            }

            // Change publish visibility to private if there are no images
            if ( 0 == count( $images ) && 'private' != $product->publish_visibility ) {
                $this->not_identical[] = 'publish_visibility';
                $product->publish_visibility = 'private';
            }

            /***** SKIP PRODUCT IF IDENTICAL *****/

            // If everything is identical, we don't want to do anything
            if ( $this->is_identical() ) {
                $this->skip( $name );
                continue;
            }

            /***** UPDATE PRODUCT *****/

			$product->save();

            // Increment product count
	        $this->new_product( $name . "\nhttp://admin.greysuitretail.com/products/add-edit/?pid={$product->id}\n" );

            // Add on to lists
            $this->existing_products[$product->sku] = $product;
		}
    }

    /**
     * Send a report
     */
    protected function send_report() {
        // Report just to CTO
        $user = new User();
        $user->get(1); // Kerry Jones

        $subject = 'Ashley Packages Feed - ' . dt::now();

        $new_products = @implode( PHP_EOL, $this->new_products );

        $message = 'New Products: ' . count( $this->new_products ) . PHP_EOL;
        $message .= 'Skipped/Unadjusted Products: ' . count( $this->skipped ) . PHP_EOL;
        $message .= str_repeat( PHP_EOL, 2 );
        $message .= "List Of New Products:" . PHP_EOL . $new_products;
        $message .= str_repeat( PHP_EOL, 2 );
        $message .= "Groups We Don't Have:" . PHP_EOL . @implode( PHP_EOL, $this->non_existent_groups );

        fn::mail( $user->email, $subject, $message );

        // Send report to everyone else
        if( count( $this->new_products ) > 0 ) {
			$message = "-----New Products-----" . PHP_EOL . $new_products;
			//, david@greysuitretail.com, rafferty@greysuitretail.com, chris@greysuitretail.com
			fn::mail( 'kerry@greysuitretail.com', 'Ashley Products - ' . dt::now(), $message );
		}
    }

    /**
     * Get ashley products by sku
     *
     * @return array
     */
    protected function get_ashley_products_by_sku() {
        return ar::assign_key( $this->get_results( "SELECT `sku`, `name` FROM `products` WHERE `user_id_created` = 353 AND `publish_visibility` = 'public'", PDO::FETCH_ASSOC ), 'sku', true );
    }
}
