<?php
class KnowledgeBaseCategory extends ActiveRecordBase {
    // The columns we will have access to
    public $id, $parent_id, $name;

    // Artificial field
    public $depth;

    // Hold the categories
    public static $categories, $categories_by_parent;

    /**
     * Setup the account initial data
     */
    public function __construct() {
        parent::__construct( 'kb_category' );
    }

    /**
     * Get a category
     *
     * @param int $id
     * @return Category
     */
    public function get( $id ) {
        if ( !isset( self::$categories[$id] ) ) {
            $this->prepare(
                'SELECT `id`, COALESCE( `parent_id`, 0 ) AS parent_id, `name` FROM `kb_category` WHERE `id` = :id'
                , 'i'
                , array( ':id' => $id )
            )->get_row( PDO::FETCH_INTO, $this );
        } else {
            $this->id = $id;
            $this->parent_id = self::$categories[$id]->parent_id;
            $this->name = self::$categories[$id]->name;
        }
    }

    /**
     * Get All Categories
     *
     * @return array
     */
    public function get_all() {
		$categories_array = $this->get_results( "SELECT `id`, COALESCE( `parent_id`, 0 ) AS parent_id, `name` FROM `kb_category` ORDER BY `parent_id` ASC", PDO::FETCH_CLASS, 'KnowledgeBaseCategory' );
        $categories = array();

        foreach ( $categories_array as $c ) {
            $categories[$c->id] = $c;
        }

        KnowledgeBaseCategory::$categories = $categories;

        return KnowledgeBaseCategory::$categories;
    }

    /**
     * Get all children categories
     *
     * @param int $id
     * @param array $child_categories [optional] Pseudo-optional -- shouldn't be filled in
     * @return KnowledgeBaseCategory[]
     */
    public function get_all_children( $id, array $child_categories = array() ) {
        $categories = $this->get_by_parent( $id );

        if ( is_array( $categories ) )
        foreach ( $categories as $category ) {
            $child_categories[] = $category;

            $child_categories = $this->get_all_children( $category->id, $child_categories );
        }

        return $child_categories;
    }

    /**
     * Get Categories By Parent
     *
     * @param int $parent_id
     * @return KnowledgeBaseCategory[]
     */
    public function get_by_parent( $parent_id ) {
        // Get the categories
        $categories_by_parent = KnowledgeBaseCategory::$categories_by_parent;

        if ( is_null( $categories_by_parent ) ) {
            $this->sort_by_parent();

            $categories_by_parent = KnowledgeBaseCategory::$categories_by_parent;
        }

        return ( $this->has_children( $parent_id ) ) ? $categories_by_parent[$parent_id] : array();
    }

    /**
     * Check to see if a category has a parent
     *
     * @param int $id [optional]
     * @return bool
     */
    public function has_children( $id = NULL ) {
        // Get the categories
        $categories_by_parent = KnowledgeBaseCategory::$categories_by_parent;

        if ( is_null( $categories_by_parent ) ) {
            $this->sort_by_parent();

            $categories_by_parent = KnowledgeBaseCategory::$categories_by_parent;
        }

        if ( is_null( $id ) )
            $id = $this->id;

        return isset( $categories_by_parent[$id] );
    }

    /**
     * Create a Category
     */
    public function create() {
        $this->insert( array(
            'parent_id' => $this->parent_id
            , 'name' => $this->name
        ), 'is' );

        $this->id = $this->get_insert_id();
    }

    /**
     * Update a Category
     */
    public function save() {
        // We cannot let this happen
        if ( $this->id == $this->parent_id )
            return;

        parent::update( array(
            'parent_id' => $this->parent_id
            , 'name' => $this->name
        ), array( 'id' => $this->id ), 'is', 'i' );
    }

    /**
     * Delete a category and dependents
     */
    public function delete() {
        if ( is_null( $this->id ) )
            return;

        $this->prepare(
            'DELETE FROM `kb_category` WHERE `id` = :id OR `parent_id` = :parent_id'
            , 'ii'
            , array( ':id' => $this->id, ':parent_id' => $this->id )
        )->query();
    }

    /**
     * Sort by parent
     */
    protected function sort_by_parent() {
        // Get categories if they exist
        $categories = KnowledgeBaseCategory::$categories;

        // If they don't exist, get them
        if ( is_null( $categories ) )
            self::$categories = $categories = $this->get_all();

        // Sort by parent
        $categories_by_parent = array();

        foreach ( $categories as $category ) {
            $categories_by_parent[$category->parent_id][] = $category;
        }

        KnowledgeBaseCategory::$categories_by_parent = $categories_by_parent;
    }

    /**
     * Sort by hierarchy
     *
     * @param int $parent_id [optional]
     * @param int $depth [optional]
     * @param array $hierarchical_categories
     * @return KnowledgeBaseCategory[]
     */
    public function sort_by_hierarchy( $parent_id = 0, $depth = 0, array $hierarchical_categories = array() ) {
        $categories = $this->get_by_parent( $parent_id );

        if ( !is_array( $categories ) )
            return $hierarchical_categories;

        if ( is_array( $categories ) )
        foreach ( $categories as $c ) {
            $c->depth = $depth;

            $hierarchical_categories[] = $c;

            $hierarchical_categories = $this->sort_by_hierarchy( $c->id, $depth + 1, $hierarchical_categories );
        }

        return $hierarchical_categories;
    }
}
