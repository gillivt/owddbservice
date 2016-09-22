<?php
// If it's going to need the database, then it's 
// probably smart to require it before we start.
require_once(LIB_PATH . DS . 'class.database_object.php');

class DiaryView extends DatabaseObject {

    protected static $table_name = "diary_view";
    protected static $db_fields = array('fullname', 'housenumber', 'address1', 
        'town', 'postcode', 'mobile', 'landline', 'date', 'time', 'description', 'traderid', 'id', 'clientid');
    public $firstname;
    public $lastname;
    public $housenumber;
    public $address1;
    public $town;
    public $postcode;
    public $mobile;
    public $landline;
    public $date;
    public $time;
    public $description;
    public $traderid;
    public $id;
    public $clientid;
}
?>