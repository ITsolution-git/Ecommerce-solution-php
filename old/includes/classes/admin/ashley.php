<?php
/**
 * Handles ashley import
 *
 * @package Grey Suit Retail
 * @since 1.0
 */
class Ashley extends Base_Class {
	const FTP_URL = 'ftp.ashleyfurniture.com';
    const IMAGE_URL = 'https://www.ashleydirect.com/graphics/';
	const USERNAME = 'CE_3400-';
	const PASSWORD = 'gRwfUn#';
    const USER_ID = 1477;
	
	private $images = array();

    /**
     * Hold the ashley brands
     */
    private $_brands = array(
        'Ashley' => 8
        , 'Signature Design' => 170
        , 'Benchcraft' => 8
        , 'Millennium' => 171
        , 'Room Solutions' => 8
    );

    /**
     * Hold the ashley categories
     */
    private $_categories = array(
        'Accents' => 360
        , 'Stationary Upholstery' => 218
        , 'Motion Upholstery' => 348
        , 'Sectionals' => 226
        , 'Chairs' => 221
        , 'Stationary Leather' => 255
        , 'Recliners' => 222
        , 'Motion Leather' => 255
        , 'Dining' => 347
        , 'Master Bedroom' => 228
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
	 * Creates new Database instance
	 */
	public function __construct() {
		// Load database library into $this->db (can be omitted if not required)
		parent::__construct();

        set_time_limit(600);
		
		// Time how long we've been on this page
		$this->curl = new curl();
		$this->p = new Products();
		$this->file = new Files();
	}

    /**
     * Search Array
     *
     * @param array $array
     * @param string $value
     * @return string
     */
    public function search_array( $array, $value ) {
        foreach ( $array as $v ) {
            if ( stristr( $v, $value ) )
                return $v;
        }

        return false;
    }

    /**
     * Load Packages
     */
    public function load_packages() {
        // Sets who is updating the products
        global $user;
        $user['user_id'] = self::USER_ID;
		
        // Get librarys
        library('ashley-api/ashley-api');
        $a = new Ashley_API();
		
        // Get existing products
        $existing_products = $this->_get_existing_products();
		
		// Get ashley products
		$ashley_products = $this->get_ashley_products();
		
        // Get Templates
        $package_template_array = $a->get_package_templates();
		
		$package_templates = array();
		
        foreach ( $package_template_array as $pta ) {
            $package_templates[(string)$pta->TemplateId] = $pta;
        }
		
        // Get packages
        $packages = $a->get_packages();
		
		echo str_repeat( ' ', 1000 );
		flush();
		
        // Get Series
		set_time_limit(600);
		
        $series_array = $a->get_series();
		
		echo str_repeat( ' ', 1000 );
		flush();
        $series = array();
		
        foreach ( $series_array as $sa ) {
            $series[(string)$sa->SeriesNo] = $sa;
        }
		
		echo str_repeat( ' ', 1000 );
		flush();

        // Generate array of our items
        $i = $skipped = 0;

        // Initiate product string
        $products_string = $skipped_products_string = '';

        // Any new products get a link
        $links = $categories = array();
		
        foreach ( $packages as $item ) {
            // Ensure that we can keep running
            echo '                                                   ';
            set_time_limit(30);
			flush();

            $image = (string) $item->Image;
			
            // We don't care if they don't have an image
            if ( empty( $image ) )
                continue;

			$this->p = new Products();

            // Count how many products we're dealing with
			$i++;
			
            $package_series = $series[(string)$item->SeriesNo];
			$template = $package_templates[(string)$item->TemplateId];
			$sku = (string) $item->PackageName;
			
			$grouping = (string)$package_series->Grouping;
			$categories[$grouping][] = $item->SeriesName . ' ' . $template->Descr;
			
			
            // Start collecting data
			if ( empty( $template->Descr ) ) {
				$sku_pieces = explode( '/', $sku );
				$series_name = array_shift( $sku_pieces );
				
				$name_pieces = array();
				
				foreach ( $sku_pieces as $sp ) {
					if ( isset( $ashley_products[$series_name . $sp] ) ) {
						$name_piece = str_replace( (string) $item->SeriesName, '', $ashley_products[$series_name . $sp] );
					} elseif( isset( $ashley_products[$series_name . '-' . $sp] ) ) {
						$name_piece = str_replace( (string) $item->SeriesName, '', $ashley_products[$series_name . '-' . $sp] );
					} else {
						continue;
					}
					
					$name_pieces[] = preg_replace( '/^ - /', '', $name_piece );
				}
				
				$name = $item->SeriesName . ' - ' . implode( ', ', $name_pieces );
			} else {
				$name = $item->SeriesName . ' ' . $template->Descr;
			}			

			$slug = str_replace( '---', '-', format::slug( $name ) );
            
			$image = self::IMAGE_URL . $image;
			$weight = $volume = 0;

            // Set item description
			$item_description = '<p>' . $package_series->Description . "</p>\n\n<p>" . $package_series->Features . "</p>\n\n<p>" . $package_series->SeriesColor . "</p>\n\n<p>" . $package_series->StyleDescription . '</p>';
            $description = format::convert_characters( format::autop( format::unautop( $item_description ) ) );

            // Will have to format this
            $style_description = trim( (string) $package_series->StyleDescription );

            // Set product specs
            if ( empty( $style_description ) ) {
                $product_specs = '';
            } else {
                $product_specs = 'Style Description`' . $style_description . '`0';
            }

            // One of the Ashley brands
			$brand_id = $this->_brands[(string)$package_series->Showroom];

            // Get Category ID
            $category_id = $this->_categories[$grouping];
			$images = array();
			
			////////////////////////////////////////////////
			// Get/Create the product
			if ( array_key_exists( $sku, $existing_products ) ) {
				$identical = true;
                
				$product = $existing_products[$sku];
				$product_id = $product['product_id'];

				$product_images = explode( '|', $product['images'] );

				// Override data with existing data
				if( empty( $name ) ) {
					$name = $product['name'];
				} elseif ( $name != $product['name'] ) {
					$identical = false;
				}

				if( empty( $slug ) ) {
					$slug = $product['slug'];
				} elseif ( $slug != $product['slug'] ) {
					$slug = $this->unique_slug( $slug );

					if ( $slug != $product['slug'] )
						$identical = false;
				}

				if( empty( $description ) ) {
					$description = format::autop( format::unautop( $product['description'] ) );
				} elseif ( $description != format::autop( format::unautop( $product['description'] ) ) ) {
					$identical = false;
				}

				$images = $product_images;


				if ( ( 0 == count( $images ) || empty( $images[0] ) ) && !empty( $image ) && curl::check_file( $image ) ) {
					$identical = false;
					$image_name = $this->upload_image( 'http://www.studio98.com/ashley/Images/' . $image, $slug, $product_id );

					if ( !is_array( $images ) || !in_array( $image_name, $images ) )
						$images[] = $image_name;
				}

				$price = 0;//$product_information['price'];
				$list_price = 0;//$product_information['list_price'];
				$product_specifications = '';

				$product['product_specifications'] = unserialize( $product['product_specifications'] );
				if( is_array( $product['product_specifications'] ) )
				foreach( $product['product_specifications'] as $ps ) {
					if( !empty( $product_specifications ) )
						$product_specifications .= '|';

					$product_specifications .= html_entity_decode( $ps[0], ENT_QUOTES, 'UTF-8' ) . '`' . html_entity_decode( $ps[1], ENT_QUOTES, 'UTF-8' ) . '`' . $ps[2];
				}

				if( empty( $product_specs ) ) {
					$product_specs = $product_specifications;
				} elseif ( $product_specs != $product_specifications ) {
					$identical = false;
				}

				if( empty( $brand_id ) ) {
					$brand_id = $product['brand_id'];
				} elseif ( $brand_id != $product['brand_id'] ) {
					$identical = false;
				}

				if( empty( $product_status ) ) {
					$product_status = $product['status'];
					$links['updated-product'][] = $name . "\nhttp://admin.greysuitretail.com/products/add-edit/?pid=$product_id\n";
				} else {
					$links[$product_status][] = $name . "\nhttp://admin.greysuitretail.com/products/add-edit/?pid=$product_id\n";

					if ( $product_status != $product['status'] )
						$identical = false;
				}

				$publish_visibility = $product['publish_visibility'];
				$publish_date = $product['publish_date'];

				if( empty( $weight ) ) {
					$weight = $product['weight'];
				} elseif ( $weight != $product['weight'] ) {
					$identical = false;
				}

				if( empty( $volume ) ) {
					$volume = $product['volume'];
				} elseif ( $volume != $product['volume'] ) {
					$identical = false;
				}

				// If everything is identical, we don't want to do anything
				if ( $identical ) {
					$skipped++;
					continue;
				}
			} else {
                // User "Ashley Packages"
				$product_id = $this->p->create( 1477 );
				
                // Make sure it's a unique slug
                $slug = $this->unique_slug( $slug );
				
				// Upload image if it's not blank
				if ( !empty( $image ) && curl::check_file( $image ) ) {
					$image_name = $this->upload_image( $image, $slug, $product_id );
					
					if ( !in_array( $image_name, $images ) )
						$images[] = $image_name;
				}

				$price = $list_price = 0;
				$publish_visibility = 'private';
				$publish_date = dt::date( 'Y-m-d' );

				$links['new-products'][] = $name . "\nhttp://admin.greysuitretail.com/products/add-edit/?pid=$product_id\n";

				// Add images
				$this->p->empty_product_images( $product_id );

				// Makes the images have the right sequence if they exist
				if ( is_array( $images ) ) {
					$j = 0;

					foreach ( $images as &$image ) {
						$image .= "|$j";
						$j++;
					}
				}

				$this->p->add_product_images( $images, $product_id );

                $product_status = 'private';

                if ( 0 != $category_id )
                    $this->p->add_categories( $product_id, array( $category_id ) );
			}

			// Update the product
			$this->p->update( $name, $slug, $description, $product_status, $sku, $price, $list_price, $product_specs, $brand_id, 1, $publish_visibility, $publish_date, $product_id, $weight, $volume );
			
			// Add images
			$product_ids[] = (int) $product_id;

			if ( 226 == $category_id )
				$products_string .= $name . "<br />\n";
			
			/* Makes the images have the right sequence if they exist
			if ( is_array( $images ) ) {
				$j = 0;

				foreach ( $images as &$image ) {
					$image .= "|$j";
					$j++;
				}
			}

			$this->commit_product_images( $images, $product_id );
			*/

			// We don't want to carry them around in the next loop
			unset( $images );
			
			if ( $i % 1000 == 0 ) {
				$message = memory_get_peak_usage(true) . "\n" . memory_get_usage(true) . "\n\n";

				foreach ( $links as $section => $link_array ) {
					$message .= ucwords( str_replace( '-', ' ', $section ) ) . ": " . count( $link_array ) . "\n";
				}

				$message .= "\n\nSkipped: " . $skipped;

				mail( 'tiamat2012@gmail.com', "Made it to $i", $message );
			}
			//$i++;
			
		}
		
		fn::info( $categories );
		echo 'here';
		echo $products_string;
    }

	/**
	 * Main function, goes to page and grabs everything needed and does required actions.
	 * 
	 * @param string $file (optional|)
	 * @return bool
	 */
	public function run( $file = '' ) {
		$ftp = new FTP( 0, '/CustEDI/3400-/Outbound/', true );
		
		ini_set( 'max_execution_time', 600 ); // 10 minutes
		ini_set( 'memory_limit', '512M' );
		set_time_limit( 600 );
		$start = time();
		
		
		// Set login information
		$ftp->host     = self::FTP_URL;
		$ftp->username = self::USERNAME;
		$ftp->password = self::PASSWORD;
		$ftp->port     = 21;
		
		// Connect
		$ftp->connect();
		
		if( empty( $file ) ) {
			// Get al ist of the files
			$files = $ftp->dir_list();
			
			$file = NULL;
			$count = count( $files );
			
			while ( is_null( $file ) && 0 != $count ) {
				$last_file = array_pop( $files );
				
				if ( 'xml' == f::extension( $last_file ) )
					$file = $last_file;
				
				$count = count( $files );
			}
		}
		
		$xml_reader = new XMLReader();
		
		// Grab the latest file
		if( !file_exists( '/gsr/systems/backend/admin/media/downloads/ashley/' . $file ) )
			$ftp->get( $file, '', '/gsr/systems/backend/admin/media/downloads/ashley/' );
		
		///// About 20mbs of useage /////
		
		$xml_reader->open( '/gsr/systems/backend/admin/media/downloads/ashley/' . $file );
		
		$j = -1;
		
		while( $xml_reader->read() ) {
			switch ( $xml_reader->localName ) {
				case 'item':
					// Make sure we're not dealing with an end element
					if( $xml_reader->nodeType == XMLReader::END_ELEMENT ) { 
						$xml_reader->next();
						continue;
					}
					
					// Increment the item
					$j++;
					
					// Set the dimensions
					$dimensions = 0;
					
					// Create base for items
					$items[$j] = array(
						'status' => ( 'Discontinued' == trim( $xml_reader->getAttribute('itemStatus') ) ) ? 'discontinued' : 'in-stock'
						, 'nodeType' => trim( $xml_reader->nodeType )
						, 'group' => trim( $xml_reader->getAttribute('itemGroupCode') )
						, 'image' => trim( $xml_reader->getAttribute('image') )
						, 'brand_id' => $this->get_brand( trim( $xml_reader->getAttribute('retailSalesCategory') ) )
						, 'specs' => ''
						, 'weight' => 0
						, 'volume' => 0
					);
					
				break;
				
				// SKU
				case 'itemIdentifier':
					if ( !isset( $items[$j]['sku'] ) )
						$items[$j]['sku'] = trim( $xml_reader->getAttribute('itemNumber') );
				break;
				
				// Description
				case 'itemDescription':
					$items[$j]['description'] = trim( $xml_reader->getAttribute('itemFriendlyDescription') );
				break;
				
				// We're in the item dimensions section
				case 'itemDimensions':
					$dimensions = 1;
				break;

                // Turn off so it doesn't get overridden by package characteristics
                case 'packageDimensions':
                    $dimensions = 0;
                break;
				
				// Specifications
				case 'depth':
					if ( $dimensions && 'Inches' == trim( $xml_reader->getAttribute('unitOfMeasure') ) )
						$items[$j]['specs'] = 'Depth`' . trim( $xml_reader->getAttribute('value') );
				break;

				// Specifications
				case 'height':
					if ( $dimensions && 'Inches' == trim( $xml_reader->getAttribute('unitOfMeasure') ) )
						$items[$j]['specs'] .= ' Inches`0|Height`' . trim( $xml_reader->getAttribute('value') );
				break;

				// Specifications
				case 'length':
					if ( $dimensions && 'Inches' == trim( $xml_reader->getAttribute('unitOfMeasure') ) )
						$items[$j]['specs'] .= ' Inches`1|Length`' . trim( $xml_reader->getAttribute('value') ) . ' Inches`2';
					
					$dimensions = 0;
				break;
				
				// Weight
				case 'weight':
					if ( !isset( $items[$j]['weight'] ) )
						$items[$j]['weight'] = trim( $xml_reader->getAttribute('value') );
				break;
				
				// Volumne
				case 'volume':
					if ( !isset( $items[$j]['volume'] ) )
						$items[$j]['volume'] = trim( $xml_reader->getAttribute('value') );
				break;
				
				// Groups
				case 'groupInformation':
					$groups[$xml_reader->getAttribute('groupID')] = array(
						'name' => trim( $xml_reader->getAttribute('groupName') )
						, 'description' => trim( $xml_reader->getAttribute('groupDescription') )
						, 'features' => trim( $xml_reader->getAttribute('groupFeatures') )
					);
				break;
			}
		}
		
		$xml_reader->close();

		// Initalize variables
		$links = $products_string = '';

		$products = $this->get_products();

		$i = 0;
		$skipped = 0;
		
		// Generate array of our items
		foreach( $items as $item ) {
			echo '                    ';
			
			$i++;
			$item_description = $item['description'];
			$sku = $item['sku'];

			// We can't have a SKU like B457B532 -- it means it is international and comes in a container
			if ( preg_match( '/[a-zA-Z]?[0-9-]+[a-zA-Z][0-9-]+/', $sku ) )
				continue;
			
			$product_status = $item['status'];
			$product_specs = $item['specs'];
			$weight = $item['weight'];
			$volume = $item['volume'];
			
			
			if( isset( $groups[$item['group']] ) ) {
				$group = $groups[$item['group']];
			
				$group_name = $group['name'] . ' - ';
				$group_description = '<p>' . $group['description'] . '</p>';
				$group_features = '<p>' . $group['features'] . '</p>';
			} else {
				$item['group'] = preg_replace( '/([^-]+)-.*/', '$1', $item['group'] );
			}
			
			if( isset( $groups[$item['group']] ) ) {
				$group = $groups[$item['group']];
			
				$group_name = $group['name'] . ' - ';
				$group_description = '<p>' . $group['description'] . '</p>';
				$group_features = '<p>' . $group['features'] . '</p>';
			} else {
				fn::mail( 'kerry@greysuitretail.com', 'Missing Group', 'Missing Group: ' . $item['group'] );
                continue;
			}
			
			$name = format::convert_characters( $group_name . $item['description'] );
			$slug = str_replace( '---', '-', format::slug( $name ) );
			$description = format::convert_characters( format::autop( format::unautop( '<p>' . $item['description'] . "</p>{$group_description}{$group_features}" ) ) );
			
			$brand_id = $item['brand_id'];
			
			$image = $item['image'];
			
			$images = array();
			
			////////////////////////////////////////////////
			// Get/Create the product
			if( array_key_exists( $sku, $products ) ) {
				$identical = true;
				
				$product = $products[$sku];
				$product_id = $product['product_id'];
				
				$product_images = explode( '|', $product['images'] );
				
				// Override data with existing data
				if( empty( $name ) ) {
					$name = $product['name'];
				} elseif ( $name != $product['name'] ) { 
					$identical = false;
				}
				
				if( empty( $slug ) ) {
					$slug = $product['slug'];
				} elseif ( $slug != $product['slug'] ) {
					$slug = $this->unique_slug( $slug );
					
					if ( $slug != $product['slug'] )
						$identical = false;
				}
				
				if( empty( $description ) ) {
					$description = format::autop( format::unautop( $product['description'] ) );
				} elseif ( $description != format::autop( format::unautop( $product['description'] ) ) ) { 
					$identical = false;
				}
				
				$images = $product_images;
				
				
				if ( 0 == count( $images ) && !empty( $image ) && 'Blank.gif' != $image && 'NOIMAGEAVAILABLE_BIG.jpg' != $image && curl::check_file( 'http://www.studio98.com/ashley/Images/' . $image ) ) {
                    // mail('kerry.jones@earthlink.net', 'adding image - update', $slug . "\n\n$image")
					$identical = false;
					$image_name = $this->upload_image( 'http://www.studio98.com/ashley/Images/' . $image, $slug, $product_id );
					
					if ( !is_array( $images ) || !in_array( $image_name, $images ) )
						$images[] = $image_name;
				}
				
				$price = 0;//$product_information['price'];
				$list_price = 0;//$product_information['list_price'];
				$product_specifications = '';
				
				$product['product_specifications'] = unserialize( $product['product_specifications'] );
				if( is_array( $product['product_specifications'] ) )
				foreach( $product['product_specifications'] as $ps ) {
					if( !empty( $product_specifications ) )
						$product_specifications .= '|';
					
					$product_specifications .= html_entity_decode( $ps[0], ENT_QUOTES, 'UTF-8' ) . '`' . html_entity_decode( $ps[1], ENT_QUOTES, 'UTF-8' ) . '`' . $ps[2];
				}
				
				if( empty( $product_specs ) ) {
					$product_specs = $product_specifications;
				} elseif ( $product_specs != $product_specifications ) { 
					$identical = false;
				}
				
				if( empty( $brand_id ) ) {
					$brand_id = $product['brand_id'];
				} elseif ( $brand_id != $product['brand_id'] ) { 
					$identical = false;
				}
				
				if( empty( $product_status ) ) {
					$product_status = $product['status'];
					$links['updated-product'][] = $name . "\nhttp://admin.greysuitretail.com/products/add-edit/?pid=$product_id\n";
				} else {
					$links[$product_status][] = $name . "\nhttp://admin.greysuitretail.com/products/add-edit/?pid=$product_id\n";
					
					if ( $product_status != $product['status'] )
						$identical = false;
				}
				
				$publish_visibility = $product['publish_visibility'];
				$publish_date = $product['publish_date'];
				
				if( empty( $weight ) ) {
					$weight = $product['weight'];
				} elseif ( $weight != $product['weight'] ) { 
					$identical = false;
				}
				
				if( empty( $volume ) ) {
					$volume = $product['volume'];
				} elseif ( $volume != $product['volume'] ) { 
					$identical = false;
				}
				
				// If everything is identical, we don't want to do anything
				if ( $identical ) {
					$skipped++;
					$products_string .= $name . "\n";
					continue;
				}
			} else {
				$product_id = $this->p->create( 353 );

                // Make sure it's a unique slug
                $slug = $this->unique_slug( $slug );

				// Upload image if it's not blank
				if ( 'Blank.gif' != $image && 'NOIMAGEAVAILABLE_BIG.jpg' != $image && curl::check_file( 'http://www.studio98.com/ashley/Images/' . $image ) ) {
                    //mail('kerry.jones@earthlink.net', 'adding image', $slug . "\n\n$image")
					$image_name = $this->upload_image( 'http://www.studio98.com/ashley/Images/' . $image, $slug, $product_id );
					
					if ( !in_array( $image_name, $images ) )
						$images[] = $image_name;
				}
				
				$price = $list_price = 0;
				$publish_visibility = 'private';
				$publish_date = dt::date( 'Y-m-d' );
				
				$links['new-products'][] = $name . "\nhttp://admin.greysuitretail.com/products/add-edit/?pid=$product_id\n";
				
				// Add images
				$this->p->empty_product_images( $product_id );
				
				// Makes the images have the right sequence if they exist
				if ( is_array( $images ) ) {
					$j = 0;
					
					foreach ( $images as &$image ) {
						$image .= "|$j";
						$j++;
					}
				}
				
				$this->p->add_product_images( $images, $product_id );
                $products[$sku] = compact( 'name', 'slug', 'description', 'product-status', 'sku', 'price', 'list_price', 'product_specs', 'brand_id', 'publish_visibility', 'publish_date', 'product_id', 'weight', 'volume', 'images' );
			}
			
			// Update the product
			$this->p->update( $name, $slug, $description, $product_status, $sku, $price, $list_price, $product_specs, $brand_id, 1, $publish_visibility, $publish_date, $product_id, $weight, $volume );
			
			// Add images
			//$product_ids[] = (int) $product_id;
			
			/* Makes the images have the right sequence if they exist
			if ( is_array( $images ) ) {
				$j = 0;
				
				foreach ( $images as &$image ) {
					$image .= "|$j";
					$j++;
				}
			}
			
			$this->commit_product_images( $images, $product_id );
			*/
			
			$products_string .= $name . "\n";
			
			// We don't want to carry them around in the next loop
			unset( $images );
			
			if ( $i % 1000 == 0 ) {
				$message = memory_get_peak_usage(true) . "\n" . memory_get_usage(true) . "\n\n";
				
				foreach ( $links as $section => $link_array ) {
					$message .= ucwords( str_replace( '-', ' ', $section ) ) . ": " . count( $link_array ) . "\n";
				}
				
				$message .= "\n\nSkipped: " . $skipped;
				
				mail( 'tiamat2012@gmail.com', "Made it to $i", $message );
			}
			//$i++;
			
		}
		
		//fn::info( $this->images );
		//$this->empty_product_images( $product_ids );
		//$this->add_product_images();
		echo "Skipped: $skipped<br />\n";
		echo $i;
		echo '|' . memory_get_peak_usage(true) . '-' . memory_get_usage(true);
		
		$headers = "From: noreply@greysuitretail.com" . "\r\n" .
			"Reply-to: noreply@greysuitretail.com" . "\r\n" . 
			"X-Mailer: PHP/" . phpversion();
		
		mail( 'kerry@studio98.com', 'Ashley Feed - ' . $file, $products_string, $headers );
		
		if( is_array( $links['new-products'] ) ) {
			$message = '';
			
			$message .= "-----New Products-----\n";
			$message .= implode( "\n", $links['new-products'] );
			
			mail( 'kerry@greysuitretail.com, david@greysuitretail.com, rafferty@greysuitretail.com, chris@greysuitretail.com', 'Ashley Products - ' . $file, $message, $headers );
		}
	}

	/**
	 * Get Products
	 *
	 * @return array
	 */
	public function get_products() {
		$products = $this->db->get_results( "SELECT a.`product_id`, a.`brand_id`, a.`industry_id`, a.`name`, a.`slug`, a.`description`, a.`status`, a.`sku`, a.`price`, a.`weight`, a.`volume`, a.`product_specifications`, a.`publish_visibility`, a.`publish_date`, b.`name` AS industry, GROUP_CONCAT( `image` ORDER BY `sequence` ASC SEPARATOR '|' ) AS images FROM `products` AS a INNER JOIN `industries` AS b ON (a.`industry_id` = b.`industry_id`) LEFT JOIN `product_images` AS c ON ( a.`product_id` = c.`product_id` ) WHERE a.`user_id_created` = 353 GROUP BY a.`product_id`", ARRAY_A );
		
		// Handle any error
		if( $this->db->errno() ) {
			$this->_err( 'Failed to get products.', __LINE__, __METHOD__ );
			return false;
		}
		
		return ar::assign_key( $products, 'sku' );
	}
	
	/**
	 * Empty product images for a specific product ID
	 *
	 * @param array $product_ids
	 * @return bool
	 */
	public function empty_product_images( $product_ids ) {
		// Add Images in bulk
		$product_id_chunks = array_chunk( $product_ids, 500 );
		
		if ( !is_array( $product_id_chunks ) )
			return true;
		
		foreach ( $product_id_chunks as $pids ) {
			$this->db->query( 'DELETE FROM `product_images` WHERE `product_id` IN( ' . implode( ',', $pids ) . ')' );
			
			// Handle any error
			if ( $this->db->errno() ) {
				$this->_err( 'Failed to get delete product images.', __LINE__, __METHOD__ );
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Commits a product image to a product
	 *
	 * @param array $images
     * @param int $product_id
	 * @return bool
	 */
	public function commit_product_images( $images, $product_id ) {
		// No images to work with
		if ( !is_array( $images ) )
			return true;
		
		// Typecast
		$product_id = (int) $product_id;
		
		// Initiate values
		$values = '';
		
		foreach ( $images as $key => $image ) {
			// Putting the definition of $sequence down below (after the list() statement) made it actually not assign zero.  Putting it up here too.
			$sequence = 0;
			
			if ( preg_match( '/^\//', $image ) == 1 )
				$image = substr( $image, 1 );
	
			// Get it's sequence
			if ( stristr( $image, '|' ) )
				list( $image, $sequence ) = explode( '|', $image );
				
			// Give it a value if it was empty
			if ( empty( $sequence ) )
				$sequence = 0;
			
			$this->images[] = "( $product_id, '" . $this->db->escape( $image ) . "', " . (int) $sequence . ' )';
		}
		
		return true;
	}
	
	/**
	 * Adds a product images in bulk
	 *
	 * @return bool
	 */
	public function add_product_images() {
		// Add Images in bulk
		$image_chunks = array_chunk( $this->images, 500 );
		
		if ( !is_array( $image_chunks ) )
			return true;
		
		foreach ( $image_chunks as $images ) {
			$this->db->query( 'INSERT INTO `product_images` ( `product_id`, `image`, `sequence` ) VALUES ' . implode( ',', $images ) );
	
			// Handle any error
			if ( $this->db->errno() ) {
				$this->_err( 'Failed to add product images.', __LINE__, __METHOD__ );
				return false;
			}
		}
		
		return true;
	}
	
    /**
     * Check to see if a Slug is already being used
     *
     * @param string $slug
     * @return string
     */
    private function unique_slug( $slug ) {
        $existing_slug = $this->db->get_var( "SELECT `slug` FROM `products` WHERE `user_id_created` = 353 AND `publish_visibility` <> 'deleted' AND `slug` = '" . $this->db->escape( $slug ) . "'" );

        // Handle any error
		if( $this->db->errno() ) {
			$this->_err( 'Failed to check slug.', __LINE__, __METHOD__ );
			return false;
		}
		
        // See if the slug already exists
        if ( $slug == $existing_slug ) {
            // Check to see if it has been incremented before
            if ( 1 == preg_match( '/-([0-9]+)$/', $slug, $matches ) > 0 ) {
                // The number to increment it by
                $increment = $matches[1] * 1 + 1;
				echo $slug;
                // Give it the new increment
                $slug = preg_replace( '/-[0-9]+$/', "-$increment", $slug );
				echo "|$slug";
				exit;
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

	/**
	 * Get Brand
	 *
	 * @param string $retail_sales_category_code
	 * @return int
	 */
	private function get_brand( $retail_sales_category_code ) {
		$codes = array(
			'AB' => 8,
			'AD' => 8,
			'AS' => 8,
			'AT' => 8,
			'MB' => 171,
			'MD' => 171,
			'BF' => 8,
			'BL' => 8,
			'BV' => 8,
			'DB' => 170,
			'DD' => 170,
			'DT' => 170,
			'SB' => 170,
			'SD' => 170,
			'DH' => 170,
			'DM' => 170,
			'DS' => 170,
			'DC' => 170,
			'SS' => 170,
			'SH' => 170,
			'SM' => 170,
			'SC' => 170,
			'AH' => 8,
			'AM' => 8,
			'AO' => 8,
			'AC' => 8,
			'MH' => 171,
			'MM' => 171,
			'MS' => 171,
			'MC' => 171,
			'UA' => 8,
			'UU' => 8,
			'UO' => 8,
			'MO' => 171,
			'MU' => 171,
			'DA' => 170,
			'DO' => 170,
			'DU' => 170,
			'SO' => 170,
			'SU' => 170,
			'ZZ' => 8
		);
		
		return $codes[$retail_sales_category_code];
	}
	
	/**
	 * Returns product_id
	 *
	 * @param string $sku
	 * @return bool
	 */
	private function get_product_id( $sku ) {
		// Get the product ID
		$product_id = $this->db->get_var( "SELECT `product_id` FROM `products` WHERE `sku` = '" . $this->db->escape( $sku ) . "' AND `publish_visibility` <> 'deleted' AND `user_id_created` = 353" );
		
		// Handle any error
		if( $this->db->errno() ) {
			$this->_err( 'Failed to get product id.', __LINE__, __METHOD__ );
			return false;
		}
		
		return $product_id;
	}

	/**
	 * Upload image
	 *
	 * @param string $image_url
	 * @param string $slug
	 * @param int $product_id
     * @return string
	 */
	public function upload_image( $image_url, $slug, $product_id ) {
		$new_image_name = $slug;
		$image_extension = strtolower( f::extension( $image_url ) );
		
		$image['name'] = "{$new_image_name}.{$image_extension}";
		$image['tmp_name'] = OPERATING_PATH . 'media/downloads/scratchy/' . $image['name'];
		
		if( is_file( $image['tmp_name'] ) && curl::check_file( "http://furniture.retailcatalog.us/products/$product_id/thumbnail/$new_image_name.$image_extension" ) )
			return "$new_image_name.$image_extension";
		
		$fp = fopen( $image['tmp_name'], 'wb' );
		
		$this->curl->save_file( $image_url, &$fp );
		
		fclose( $fp );
		
		$this->file->upload_image( $image, $new_image_name, 320, 320, 'furniture', 'products/' . $product_id . '/', false, true );
		$this->file->upload_image( $image, $new_image_name, 46, 46, 'furniture', 'products/' . $product_id  . '/thumbnail/', false, true );
		$this->file->upload_image( $image, $new_image_name, 200, 200, 'furniture', 'products/' . $product_id . '/small/', false, true );
		$this->file->upload_image( $image, $new_image_name, 700, 700, 'furniture', 'products/' . $product_id . '/large/', false, true );

		if( file_exists( $image['tmp_name'] ) )
			@unlink( $image['tmp_name'] );
		
		return "$new_image_name.$image_extension";
	}
	
	/**
	 * Gives a report of all the information
	 *
	 * @return array|bool
	 */
	public function report() {
		// Get the page count
		$page_count = $this->db->query( "SELECT DISTINCT `url` FROM `scratchy_pages` WHERE `domain_id` = 3" )->count(); 
		
		// Handle any error
		if( $this->db->errno() ) {
			$this->_err( 'Ashley Bot: Failed to count added pages.', __LINE__, __METHOD__ );
			return false;
		}
		
		$page_links = $this->db->query( "SELECT COUNT(`domain_id`) AS page_link_count FROM `scratchy_page_links` WHERE `domain_id` = 3" )->result( FALSE )->current(); 
		
		// Handle any error
		if( $this->db->errno() ) {
			$this->_err( 'Ashley Bot: Failed to count page links.', __LINE__, __METHOD__ );
			return false;
		}
		
		return array( $page_count, $page_links['page_link_count'] );
	}

	/**
	 * Checks to see if a product with this sku already exists
	 *
	 * @param string $sku
	 * @return bool
	 */
	private function check_sku( $sku ) {
		// Check if it exists 
		$result = $this->db->query( sprintf( "SELECT `product_id` FROM `products` WHERE `sku` = '%s' AND `publish_visibility` <> 'deleted'", format::sql_string( $sku ) ) )->current();
		
		// Handle any error
		if( $this->db->errno() ) {
			$this->_err( "Ashley Bot: Failed to check sku: $sku", __LINE__, __METHOD__ );
			return false;
		}
		
		return !$result;
	}

    /**
	 * Get Existing Products
	 *
	 * @return array
	 */
	private function _get_existing_products() {
		$products = $this->db->get_results( "SELECT a.`product_id`, a.`brand_id`, a.`industry_id`, a.`name`, a.`slug`, a.`description`, a.`status`, a.`sku`, a.`price`, a.`weight`, a.`volume`, a.`product_specifications`, a.`publish_visibility`, a.`publish_date`, b.`name` AS industry, GROUP_CONCAT( `image` ORDER BY `sequence` ASC SEPARATOR '|' ) AS images FROM `products` AS a INNER JOIN `industries` AS b ON (a.`industry_id` = b.`industry_id`) LEFT JOIN `product_images` AS c ON ( a.`product_id` = c.`product_id` ) WHERE a.`user_id_created` = " . self::USER_ID . " GROUP BY a.`product_id`", ARRAY_A );

		// Handle any error
		if( $this->db->errno() ) {
			$this->_err( 'Failed to get products.', __LINE__, __METHOD__ );
			return false;
		}

		return ar::assign_key( $products, 'sku' );
	}

    /**
	 * Get Existing Products
	 *
	 * @return array
	 */
	protected function get_ashley_products() {
		$products = $this->db->get_results( "SELECT a.`sku`, a.`name` FROM `products` AS a INNER JOIN `industries` AS b ON (a.`industry_id` = b.`industry_id`) LEFT JOIN `product_images` AS c ON ( a.`product_id` = c.`product_id` ) WHERE a.`user_id_created` = 353 AND a.`publish_visibility` = 'public' GROUP BY a.`product_id`", ARRAY_A );

		// Handle any error
		if( $this->db->errno() ) {
			$this->_err( 'Failed to get products.', __LINE__, __METHOD__ );
			return false;
		}

		return ar::assign_key( $products, 'sku', true );
	}

	/**
	 * Report an error
	 *
	 * Make the parent error function a little less complicated
	 *
	 * @param string $message the error message
	 * @param int $line (optional) the line number
	 * @param string $method (optional) the class method that is being called
     * @return bool
	 */
	private function _err( $message, $line = 0, $method = '' ) {
		return $this->error( $message, $line, __FILE__, dirname(__FILE__), '', __CLASS__, $method );
	}
}