<?php
// If it's going to need the database, then it's 
// probably smart to require it before we start.
require_once(LIB_PATH . DS . 'class.database_object.php');

class OwdUser extends DatabaseObject {

    protected static $table_name = "owduser";
    protected static $db_fields = array('id', 'fullname', 'email', 
        'databaseuserid');
    public $id;
    public $fullname;
    public $email;
    public $databaseuserid;
    
    public static function owdUser($userid){
        global $database;
        
        $sql = "SELECT * FROM ".$table_name." ";
        $sql .= "WHERE $databaseuserid = '{$userid}' ";
        $sql .= "LIMIT 1";
        $result_array = self::find_by_sql($sql);
        return array_shift($result_array);
    }
}
?>