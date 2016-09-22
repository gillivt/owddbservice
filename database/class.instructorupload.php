<?php
// If it's going to need the database, then it's 
// probably smart to require it before we start.
require_once(LIB_PATH . DS . 'class.database_object.php');

class InstructorUpload extends DatabaseObject {

    protected static $table_name = "instructorsupload";
    protected static $db_fields = array('id','adinumber', 'fullname', 'gender', 'mobile',
        'email', 'streetaddress', 'town', 'county', 'postcode', 
        'hourlyrate', 'hours_5', 'hours_10', 'hours_20', 'hours_30', 'hours_40',
        'makeandmodel', 'transmission', 'fueltype', 'areascovered', 'radius',
        'bankdetails');
    public $id;
    public $adinumber;
    public $fullname;
    public $gender;
    public $mobile;
    public $email;
    public $streetaddress;
    public $town;
    public $county;
    public $postcode;
    public $hourlyrate;
    public $hours_5;
    public $hours_10;
    public $hours_20;
    public $hours_30;
    public $hours_40;
    public $makeandmodel;
    public $transmission;
    public $fueltype;
    public $areascovered;
    public $radius;
    public $bankdetails;

    /**
     * Check if record exists
     * @global type $database
     * @param type $adinumber
     * @return type
     */
    public static function exists($adinumber="") {
        global $database;
        
        $sql = "SELECT * FROM ".static::$table_name." ";
        $sql .= "WHERE adinumber = '{$adinumber}' ";
        $sql .= "LIMIT 1";
        $result_array = self::find_by_sql($sql);
        return !empty($result_array) ? true : false;
    }
    
    /**
     * Return Record
     * @global type $database
     * @param type $adinumber
     * @return type
     */
    public static function retrieve($adinumber) {
        global $database;
        global $table_name;
                
        $sql = "SELECT * FROM ".static::$table_name." ";
        $sql .= "WHERE adinumber = '{$adinumber}' ";
        $sql .= "LIMIT 1";
        $result_array = self::find_by_sql($sql);
        return array_shift($result_array);
    }
}
?>