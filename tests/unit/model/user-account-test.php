<?php

require_once 'base-database-test.php';

class UserAccountTest extends BaseDatabaseTest {
    /**
     * @var User
     */
    private $user;

    /**
     * Will be executed before every test
     */
    public function setUp() {
        $this->user = new User();
    }

    /**
     * Tests invalid login
     */
    public function testInvalidLogin() {
        // Make sure it returns false
        $result = $this->user->login( 'blabla', 'bla' );

        $this->assertFalse( $result );
    }

    /**
     * Tests a valid login on the account side
     */
    public function testValidLoginOnAccountRole5() {
        // Account - Role 5
        $valid_email = 'test@studio98.com';
        $valid_pass = 'pass123';

        $result = $this->user->login( $valid_email, $valid_pass );

        $this->assertNotNull( $this->user->id );
        $this->assertTrue( $result );
    }

    /**
     * Get getting a valid email
     */
    public function testValidGetByEmail() {
         // Account - Role 5
        $valid_email = 'test@studio98.com';

        $this->user->get_by_email( $valid_email );

        $this->assertNotNull( $this->user->id );
    }

    /**
     * Get getting an invalid email
     */
    public function testInvalidGetByEmail() {
         // Account - Role 5
        $valid_email = 'testasd8';

        $this->user->get_by_email( $valid_email );

        $this->assertNull( $this->user->id );
    }

    /**
     * Test a valid has_permission
     */
    public function testValidHasPermission() {
        $this->user->role = 5;

        $this->assertFalse( $this->user->has_permission( 7 ) );
    }

    /**
     * Test and invalid has_permission
     */
    public function testInvalidHasPermission() {
        $this->user->role = 5;

        $this->assertTrue( $this->user->has_permission( 5 ) );
    }

    /**
     * Test and invalid record login
     */
    public function testInvalidRecordLogin() {
        // Setup variables
        $datetime = new DateTime();

        // Record login
        $this->user->record_login();

        // Get the last login as a date time
        $last_login = new DateTime( $this->db->get_var( 'SELECT `last_login` FROM `users` WHERE `user_id` = 513' ) );

        // It should be more recent
        $this->assertLessThan( $datetime->getTimestamp() - 5, $last_login->getTimestamp() );
    }

    /**
     * Test and valid record login
     */
    public function testValidRecordLogin() {
        // Setup variables
        $this->user->id = 513;
        $datetime = new DateTime();

        // Record login
        $this->user->record_login();

        // Get the last login as a date time
        $last_login = new DateTime( $this->db->get_var( 'SELECT `last_login` FROM `users` WHERE `user_id` = ' . (int) $this->user->id ) );

        // It should be more recent
        $this->assertGreaterThan( $datetime->getTimestamp() - 5, $last_login->getTimestamp() );
    }

    /**
     * Test listing all accounts
     */
    public function testListAll() {
        $this->user->get_by_email('test@studio98.com');

        // Determine length
        $_GET['iDisplayLength'] = 30;
        $_GET['iSortingCols'] = 1;
        $_GET['iSortCol_0'] = 1;
        $_GET['sSortDir_0'] = 'asc';

        $dt = new DataTableResponse( $this->user );
        $dt->order_by( 'a.`contact_name`', 'a.`email`', 'phone', 'b.`domain`', 'a.`role`' );
        $dt->search( array( 'a.`contact_name`' => true, 'a.`email`' => true, 'b.`domain`' => true ) );

        $users = $this->user->list_all( $dt->get_variables() );

        // Make sure we have an array
        $this->assertTrue( is_array( $users ) );

        // Joe Schmoe is a user with ID 496
        $joe_schmoe_exists = false;

        if ( is_array( $users ) )
        foreach ( $users as $user ) {
            if ( 496 == $user->id ) {
                $joe_schmoe_exists = true;
                break;
            }
        }

        // Make sure they exist
        $this->assertTrue( $joe_schmoe_exists );

        // Get rid of everything
        unset( $user, $_GET, $dt, $users, $account, $joe_schmoe_exists );
    }

    /**
     * Test counting the accounts
     */
    public function testCountAll() {
        $this->user->get_by_email('test@studio98.com');

        // Determine length
        $_GET['iDisplayLength'] = 30;
        $_GET['iSortingCols'] = 1;
        $_GET['iSortCol_0'] = 1;
        $_GET['sSortDir_0'] = 'asc';

        $dt = new DataTableResponse( $this->user );
        $dt->order_by( 'a.`contact_name`', 'a.`email`', 'phone', 'b.`domain`', 'a.`role`' );
        $dt->search( array( 'a.`contact_name`' => true, 'a.`email`' => true, 'b.`domain`' => true ) );

        $count = $this->user->count_all( $dt->get_count_variables() );

        // Make sure they exist
        $this->assertGreaterThan( 1, $count );

        // Get rid of everything
        unset( $user, $_GET, $dt, $count );
    }

    /**
     * Will be executed after every test
     */
    public function tearDown() {
        $this->user = null;
    }
}
