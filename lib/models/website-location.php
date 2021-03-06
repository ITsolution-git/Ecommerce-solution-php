<?php
class WebsiteLocation extends ActiveRecordBase {
    // The columns we will have access to
    public $id, $website_id, $name, $address, $city, $state, $zip, $phone, $fax, $email, $website, $store_hours
        , $lat, $lng, $sequence, $date_created, $store_image;

    /**
     * Setup the account initial data
     */
    public function __construct() {
        parent::__construct( 'website_location' );
    }

    /**
     * Get
     *
     * @param int $id
     * @param int $website_id
     */
    public function get( $id, $website_id ) {
        $this->prepare(
            'SELECT * FROM `website_location` WHERE `id` = :id AND `website_id` = :website_id'
            , 'ii'
            , array( ':id' => $id, ':website_id' => $website_id )
        )->get_row( PDO::FETCH_INTO, $this );
    }

    /**
     * Get by website
     *
     * @param int $website_id
     * @return WebsiteLocation[]
     */
    public function get_by_website( $website_id ) {
        return $this->prepare(
            'SELECT * FROM `website_location` WHERE `website_id` = :website_id ORDER BY `sequence` ASC'
            , 'i'
            , array( ':website_id' => $website_id )
        )->get_results( PDO::FETCH_CLASS, 'WebsiteLocation' );
    }

    /**
     * Count
     *
     * @param int $website_id
     * @return int
     */
    public function count( $website_id ) {
        return $this->prepare(
            'SELECT COUNT(*) FROM `website_location` WHERE `website_id` = :website_id ORDER BY `sequence` ASC'
            , 'i'
            , array( ':website_id' => $website_id )
        )->get_var();
    }

    /**
     * Create
     */
    public function create() {
        // Set a couple of other variables
        $this->sequence = $this->count( $this->website_id );
        $this->date_created = dt::now();

        $this->id = $this->insert( array(
            'website_id' => $this->website_id
            , 'name' => strip_tags($this->name)
            , 'address' => strip_tags($this->address)
            , 'city' => strip_tags($this->city)
            , 'state' => strip_tags($this->state)
            , 'zip' => strip_tags($this->zip)
            , 'phone' => strip_tags($this->phone)
            , 'fax' => strip_tags($this->fax)
            , 'email' => strip_tags($this->email)
            , 'website' => strip_tags($this->website)
            , 'store_hours' => strip_tags( $this->store_hours, '<br><strong><p>' )
            , 'store_image' => strip_tags( $this->store_image )
            , 'lat' => $this->lat
            , 'lng' => $this->lng
            , 'sequence' => $this->sequence
            , 'date_created' => $this->date_created
        ), 'issssssssssssis' );
   }

    /**
     * Save
     */
    public function save() {
        $this->update( array(
            'name' => strip_tags($this->name)
            , 'address' => strip_tags($this->address)
            , 'city' => strip_tags($this->city)
            , 'state' => strip_tags($this->state)
            , 'zip' => strip_tags($this->zip)
            , 'phone' => strip_tags($this->phone)
            , 'fax' => strip_tags($this->fax)
            , 'email' => strip_tags($this->email)
            , 'website' => strip_tags($this->website)
            , 'store_hours' => strip_tags( $this->store_hours, '<br><strong><p>' )
            , 'store_image' => strip_tags( $this->store_image )
            , 'lat' => $this->lat
            , 'lng' => $this->lng
        ), array(
            'id' => $this->id
        ), 'ssssssssssss', 'i' );
   }

    /**
     * Remove
     */
    public function remove() {
        $this->delete( array(
            'id' => $this->id
            , 'website_id' => $this->website_id
        ), 'ii' );
    }

    /**
     * Update the sequence of many locations
     *
     * @param int $account_id
     * @param array $locations
     */
    public function update_sequence( $account_id, array $locations ) {
        // Starting with 0 for a sequence
        $sequence = 0;

        // Prepare statement
        $statement = $this->prepare_raw( 'UPDATE `website_location` SET `sequence` = :sequence WHERE `id` = :id AND `website_id` = :account_id' );
        $statement->bind_param( ':sequence', $sequence, 'i' )
            ->bind_param( ':id', $id, 'i' )
            ->bind_value( ':account_id', $account_id, 'i' );

        // Loop through the statement and update anything as it needs to be updated
        foreach ( $locations as $id ) {
            $statement->query();

            $sequence++;
        }
    }
}
