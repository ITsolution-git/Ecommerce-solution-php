<?php
class TicketComment extends ActiveRecordBase {
    // The columns we will have access to
    public $id, $ticket_comment_id, $ticket_id, $user_id, $comment, $private, $date_created;

    /**
     * Setup the account initial data
     */
    public function __construct() {
        parent::__construct( 'ticket_comments' );

        // We want to make sure they match
        if ( isset( $this->ticket_comment_id ) )
            $this->id = $this->ticket_comment_id;
    }

    /**
	 * Get Comments
	 *
	 * @param int $ticket_id
	 * @return array
	 */
	public function get_all( $ticket_id ) {
		return $this->prepare( 'SELECT a.`ticket_comment_id`, a.`user_id`, a.`comment`, a.`private`, a.`date_created`, b.`contact_name` AS name FROM `ticket_comments` AS a LEFT JOIN `users` AS b ON ( a.`user_id` = b.`user_id` ) WHERE a.`ticket_id` = :ticket_id ORDER BY a.`date_created` DESC'
            , 'i'
            , array( ':ticket_id' => $ticket_id)
        )->get_results( PDO::FETCH_CLASS, 'TicketComment' );
	}
}
