<?php
// If it's going to need the database, then it's 
// probably smart to require it before we start.
require_once(LIB_PATH . DS . 'class.database_object.php');

class Course extends DatabaseObject {

    protected static $table_name = "course";
    protected static $db_fields = array('id', 'courseref', 'coursestart', 'hours', 'lengthofcourse',
        'testtype', 'fullname', 'streetaddress', 'town', 'county', 'postcode', 
        'pupiltelephone', 'drivingexperience', 'theoryrequired', 
        'testbooked', 'courseclaimed', 'instructorid');
    public $id;
    public $courseref;
    public $coursestart;
    public $hours;
    public $lengthofcourse;
    public $testtype;
    public $fullname;
    public $streetaddress;
    public $town;
    public $county;
    public $postcode;
    public $pupiltelephone;
    public $drivingexperience;
    public $theoryrequired;
    public $testbooked;
    public $courseclaimed;
    public $instructorid;
    
    public static function exists($courseref="") {
        global $database;
        
        $sql = "SELECT * FROM ".static::$table_name." ";
        $sql .= "WHERE courseref = '{$courseref}' ";
        $sql .= "LIMIT 1";
        $result_array = self::find_by_sql($sql);
        return !empty($result_array) ? true : false;
    }    
}
?>