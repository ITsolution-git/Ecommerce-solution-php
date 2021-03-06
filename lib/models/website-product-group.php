<?php
class WebsiteProductGroup extends ActiveRecordBase {
    // The columns we will have access to
    public $id, $website_product_group_id, $website_id, $name;

    /**
     * Setup the account initial data
     */
    public function __construct() {
        parent::__construct( 'website_product_groups' );

        // We want to make sure they match
        if ( isset( $this->website_product_group_id ) )
            $this->id = $this->website_product_group_id;
    }

    /**
     * Get
     *
     * @param int $website_product_group_id
     * @param int $account_id
     */
    public function get( $website_product_group_id, $account_id ) {
        $this->prepare(
            'SELECT * FROM `website_product_groups` WHERE `website_product_group_id` = :website_product_group_id AND `website_id` = :account_id'
            , 'ii'
            , array( ':website_product_group_id' => $website_product_group_id, ':account_id' => $account_id )
        )->get_row( PDO::FETCH_INTO, $this );

        $this->id = $this->website_product_group_id;
    }

    /**
     * Create
     */
    public function create() {
        $this->id = $this->website_product_group_id = $this->insert( array(
            'website_id' => $this->website_id
            , 'name' => strip_tags($this->name)
        ), 'is' );
    }

    /**
     * Add Relations
     *
     * @param array $product_ids
     */
    public function add_relations( array $product_ids ) {
        // Type Juggling
        $website_product_group_id = (int) $this->id;

        // Create values
        $values = '';

        foreach ( $product_ids as $product_id ) {
            if ( !empty( $values ) )
                $values .= ',';

            $values .= "( $website_product_group_id, " . (int) $product_id . ' )';
        }

        // Create new product group relations
        $this->query( "INSERT INTO `website_product_group_relations` ( `website_product_group_id`, `product_id` ) VALUES $values" );
    }

    /**
     * Add relational items by series
     *
     * @param string $series
     */
    public function add_relations_by_series( $series ) {
        // Insert the values
        $this->prepare(
            "INSERT INTO `website_product_group_relations` ( `website_product_group_id`, `product_id` ) SELECT :website_product_group_id, wp.`product_id` FROM `website_products` AS wp LEFT JOIN `products` AS p ON ( p.`product_id` = wp.`product_id` ) LEFT JOIN `website_blocked_category` AS wbc ON ( wbc.`website_id` = wp.`website_id` AND wbc.`category_id` = p.`category_id` ) WHERE wp.`website_id` = :account_id AND wp.`active` = 1 AND wp.`blocked` = 0 AND p.`sku` LIKE :series AND wbc.`category_id` IS NULL GROUP BY wp.`product_id`"
            , 'iis'
            , array(
                ':website_product_group_id' => $this->id
                , ':account_id' => $this->website_id
                , ':series' => $series . '%'
            )
        )->query();
    }


    /**
     * Update
     */
    public function save() {
        $this->update( array(
            'name' => strip_tags($this->name)
        ), array(
            'website_product_group_id' => $this->id
        ), 's', 'i' );
    }

    /**
     * Get Product Relations
     *
     * @return array
     */
    public function get_product_relation_ids() {
        return $this->prepare(
            'SELECT wpgr.`product_id` FROM `website_product_group_relations` AS wpgr LEFT JOIN `website_product_groups` AS wpg ON ( wpg.`website_product_group_id` = wpgr.`website_product_group_id` ) WHERE wpg.`website_product_group_id` = :website_product_group_id ORDER BY `product_id` DESC'
            , 'i'
            , array( ':website_product_group_id' => $this->id )
         )->get_col();
    }

    /**
     * Remove
     */
    public function remove() {
        $this->delete( array(
            'website_product_group_id' => $this->id
        ), 'i' );
    }

    /**
     * Remove Relations
     */
    public function remove_relations() {
        $this->prepare(
            'DELETE FROM `website_product_group_relations` WHERE `website_product_group_id` = :website_product_group_id'
            , 'i'
            , array( ':website_product_group_id' => $this->id )
        )->query();
    }

    /**
	 * List Website Product Groups
	 *
	 * @param $variables array( $where, $order_by, $limit )
	 * @return WebsiteProductGroup[]
	 */
	public function list_all( $variables ) {
        // Get the variables
		list( $where, $values, $order_by, $limit ) = $variables;

        return $this->prepare(
            "SELECT `website_product_group_id`, `name` FROM `website_product_groups` WHERE 1 $where $order_by LIMIT $limit"
            , str_repeat( 's', count( $values ) )
            , $values
        )->get_results( PDO::FETCH_CLASS, 'WebsiteProductGroup' );
	}

    /**
	 * Count all
	 *
	 * @param array $variables
	 * @return int
	 */
	public function count_all( $variables ) {
        // Get the variables
		list( $where, $values ) = $variables;

		// Get the website count
        return $this->prepare(  "SELECT COUNT( `website_product_group_id` ) FROM `website_product_groups` WHERE 1 $where"
            , str_repeat( 's', count( $values ) )
            , $values
        )->get_var();
	}

    /**
     * Copy product groups
     *
     * @param int $template_account_id
     * @param int $account_id
     */
    public function copy_by_account( $template_account_id, $account_id ) {
        $this->copy_groups( $template_account_id, $account_id );
        $this->copy_group_relations( $template_account_id, $account_id );
    }

    /**
     * Copy groups
     *
     * @param int $template_account_id
     * @param int $account_id
     */
    protected function copy_groups( $template_account_id, $account_id ) {
        $this->prepare(
            'INSERT INTO `website_product_groups` (`website_id`, `name`) SELECT :account_id, wpg.`name` FROM `website_product_groups` AS wpg LEFT JOIN `website_product_groups` AS wpg2 ON ( wpg2.`name` = wpg.`name` AND wpg2.`website_id` = :account_id2 ) WHERE wpg.`website_id` = :template_account_id AND wpg2.`website_product_group_id` IS NULL GROUP BY `name`'
            , 'iii'
            , array( ':account_id' => $account_id, ':account_id2' => $account_id, ':template_account_id' => $template_account_id )
        )->query();
    }

    /**
     * Copy group relations
     *
     * @param int $template_account_id
     * @param int $account_id
     */
    protected function copy_group_relations( $template_account_id, $account_id ) {
        $this->prepare(
            'INSERT INTO `website_product_group_relations` (`website_product_group_id`, `product_id`) SELECT wpg2.`website_product_group_id`, wpgr.`product_id` FROM `website_product_group_relations` AS wpgr LEFT JOIN `website_product_groups` AS wpg ON ( wpgr.`website_product_group_id` = wpg.`website_product_group_id` ) LEFT JOIN `website_product_groups` AS wpg2 ON ( wpg2.`name` = wpg.`name` ) WHERE wpg.`website_id` = :template_account_id AND wpg2.`website_id` = :account_id ON DUPLICATE KEY UPDATE `product_id` = VALUES(`product_id`)'
            , 'ii'
            , array( ':account_id' => $account_id, ':template_account_id' => $template_account_id )
        )->query();
    }
}
