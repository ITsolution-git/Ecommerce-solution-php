<?php

require_once 'base-database-test.php';

class KnowledgeBaseCategoryTest extends BaseDatabaseTest {
    /**
     * @var KnowledgeBaseCategory
     */
    private $kb_category;

    /**
     * Will be executed before every test
     */
    public function setUp() {
        $_SERVER['MODEL_PATH'] = basename( __DIR__ );
        $this->kb_category = new KnowledgeBaseCategory();
    }

    /**
     * Test Get
     */
    public function testGet() {
        // Declare variables
        $name = 'Test Get';

        // Create Category
        $id = $this->db->insert( 'kb_category', compact( 'name' ), 's' );

        // Get category
        $this->kb_category->get( $id );

        // Should be a category
        $this->assertEquals( $this->kb_category->name, $name );

        // Delete Category
        $this->db->delete( 'kb_category', compact( 'id' ), 'i' );
    }

    /**
     * Test getting all the children
     */
    public function testGetAllChildren() {
        // Declare variables
        $name = 'Test Parent';
        $child_name = 'Test Child';

        // Create Category
        $parent_id = $this->db->insert( 'kb_category', compact( 'name' ), 's' );
        $id = $this->db->insert( 'kb_category', array( 'parent_id' => $parent_id, 'name' => $child_name ), 'is' );

        $categories = $this->kb_category->get_all_children( $parent_id );

        $this->assertEquals( $id, $categories[0]->id );

        // Clean Up
        $this->db->delete( 'kb_category', compact( 'id' ), 'i' );
        $this->db->delete( 'kb_category', array( 'id' => $parent_id ), 'i' );
    }

    /**
     * Test getting all the categories by a parent category ID
     */
    public function testGetByParent() {
        // Declare variables
        $name = 'Test Parentt';
        $child_name = 'Test Childd';

        // Create Category
        $parent_id = $this->db->insert( 'kb_category', compact( 'name' ), 's' );
        $id = $this->db->insert( 'kb_category', array( 'parent_id' => $parent_id, 'name' => $child_name ), 'is' );

        // Get the categories
        $categories = $this->kb_category->get_by_parent( $parent_id );

        $this->assertEquals( $id, $categories[0]->id );

        // Clean Up
        $this->db->delete( 'kb_category', compact( 'id' ), 'i' );
        $this->db->delete( 'kb_category', array( 'id' => $parent_id ), 'i' );
    }

    /**
     * Test getting all the categories
     */
    public function testGetAll() {
        // Declare variable
        $name = 'Website';

        // Create
        $id = $this->db->insert( 'kb_category', compact( 'name' ), 's' );

        $categories = $this->kb_category->get_all();

        $this->assertTrue( current( $categories ) instanceof KnowledgeBaseCategory );

        // Clean up
        $this->db->delete( 'kb_category', compact( 'id' ), 'i' );
    }

    /**
     * Create a category
     *
     * @depends testGet
     */
    public function testCreate() {
        // Declare variables
        $name = 'Test Cat';

        // Create
        $this->kb_category->name = $name;
        $this->kb_category->create();

        // Make sure it's in the database
        $this->kb_category->get( $this->kb_category->id );

        $this->assertEquals( $name, $this->kb_category->name );

        // Delete the category
        $this->db->delete( 'kb_category', array( 'id' => $this->kb_category->id ), 'i' );
    }

    /**
     * Save
     *
     * @depends testCreate
     */
    public function testSave() {
        // Declare variables
        $name = 'Test Cat';
        $new_name = 'Cat Test';

        // Save
        $this->kb_category->name = $name;
        $this->kb_category->create();

        // Update test
        $this->kb_category->name = $new_name;
        $this->kb_category->save();

        // Get the name
        $fetched_name = $this->db->get_var( "SELECT `name` FROM `kb_category` WHERE `id` = " . (int) $this->kb_category->id );

        $this->assertEquals( $fetched_name, $new_name );

        // Delete the category
        $this->db->delete( 'kb_category', array( 'id' => $this->kb_category->id ), 'i' );
    }

    /**
     * Test Delete
     *
     * @depends testCreate
     */
    public function testDelete() {
        // Declare variables
        $name = 'Test Cat';

        // Create
        $this->kb_category->name = $name;
        $this->kb_category->create();

        // Delete
        $this->kb_category->delete();

        // Make sure it doesn't exist
        $name = $this->db->get_var( "SELECT `name` FROM `kb_category` WHERE `id` = " . (int) $this->kb_category->id );

        $this->assertFalse( $name );
    }

    /**
     * Test getting all the categories by a parent category ID
     *
     * @depends testGetAll
     */
    public function testSortByHierarchy() {
        // Declare variables
        $name = 'Test Get';
        $greater_than = -1;

        // Create Category
        $id = $this->db->insert( 'kb_category', compact( 'name' ), 's' );

        // Get them
        $this->kb_category->get_all();

        // Sort them
        $categories = $this->kb_category->sort_by_hierarchy();

        $this->assertTrue( current( $categories ) instanceof KnowledgeBaseCategory );
        $this->assertGreaterThan( $greater_than, $categories[0]->depth );

        // Clean up
        $this->db->delete( 'kb_category', compact( 'id' ), 'i' );
    }

    /**
     * Will be executed after every test
     */
    public function tearDown() {
        unset( $_SERVER['MODEL_PATH'] );
        $this->kb_category = KnowledgeBaseCategory::$categories_by_parent = KnowledgeBaseCategory::$categories = null;
    }
}