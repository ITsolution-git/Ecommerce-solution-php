<?php
class ChecklistWebsiteItem extends ActiveRecordBase {
    // The columns we will have access to
    public $id, $checklist_website_item_id, $checklist_id, $checklist_item_id, $checked, $date_checked;

    /**
     * Setup the account initial data
     */
    public function __construct() {
        parent::__construct( 'checklist_website_items' );

        if ( isset( $this->checklist_website_item_id ) )
            $this->id = $this->checklist_website_item_id;
    }

    /**
     * Get
     *
     * @param int $checklist_website_item_id
     */
    public function get( $checklist_website_item_id ) {
		$this->prepare(
            'SELECT * FROM `checklist_website_items` WHERE `checklist_website_item_id` = :checklist_website_item_id'
            , 'i'
            , array( ':checklist_website_item_id' => $checklist_website_item_id )
        )->get_row( PDO::FETCH_INTO, $this );

        $this->id = $this->checklist_website_item_id;
    }

    /**
     * Update an item
     */
    public function update() {
        parent::update(
            array(
                'checked' => $this->checked
                , 'date_checked' => $this->date_checked
            )
            , array( 'checklist_website_item_id' => $this->id )
            , 'is'
            , 'i'
        );
    }
}
