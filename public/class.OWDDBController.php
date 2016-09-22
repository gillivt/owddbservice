<?php

use Jacwright\RestServer\RestException;
require('../database/PHPMailer/class.phpmailer.php');
require('../database/PHPMailer/class.smtp.php');

class OWDDBController {

    /**
     * Returns a JSON string object to the browser when hitting the root of the domain
     *
     * @url GET /
     */
    public function help() {
        return array("Success" => "Hello World");
    }

    /**
     * Returns a JSON object - another test
     * 
     * @url GET /hello
     */
    public function hello() {
        return "Hello World";
    }

//    // Main Service

    /**
     * Register OWD staff
     * @param type $data
     * @return type JSOn data containing token
     * @throws RestException
     * @url POST /owd/staff/register
     */
    public function staffRegistration($data) {
        //authenticate user
        $user= $this->authenticate();
        if ($user->role !== "admin") {
            throw new RestException(401, "Unauthorised");
        }
        // parameters
        $username = $data->username;
        $password = $data->password;
        $token = "basic ".base64_encode($username.":".$password);
        $fullname = $data->fullname;
        $email = $data->email;
        $role = 'owd';
        
        //check if user exists
        if (DatabaseUser::exists($username)) {
            throw new RestException(409, 'Username Already Exists');
        }

        //create salted password hash
        $saltLength = 32;
        $bool = true;
        $salt = base64_encode(openssl_random_pseudo_bytes($saltLength, $bool));
        //salt password
        $saltedPassword = hash('sha256', $salt . $password);

        //create user object
        $user = new DatabaseUser();
        $user->username = $username;
        $user->passwordHash = $saltedPassword;
        $user->passwordSalt = $salt;
        $user->role = $role;

        //save user to database
        if (!$user->save()) {
            //will probably die before it ever gets here and die will show the mysqli error
            throw new RestException(400, 'Unknown Error - user not created');
        }
            
        // if we get here have succeeded creating dbuser need to create owduser
        $owduser = new OwdUser();
        $owduser->email = $email;
        $owduser->fullname = $fullname;
        $owduser->databaseuserid = $user->id;

        if (!$owduser->save()) {
            //will probably die before it ever gets here and die will show the mysqli error
            throw new RestException(400, 'Unknown Error - trader not created');
        }
        $owduser->id = (int)$owduser->id;
        return array('token'=>$token);
    }
    
    /**
     * Register Instructor
     * @param type $data
     * @return type
     * @throws RestException
     * @url POST /instructor/register
     */
    public function instructorRegistration($data) {
        //authenticate user
//        $user= $this->authenticate();
//        if ($user->role !== "admin") {
//            throw new RestException(401, "Unauthorised");
//        }
        
        // parameters
        $adinumber = $data->adinumber;
        $password = $data->password;
        $token = "basic ".base64_encode($adinumber.":".$password);
        $role = "instructor";

        //check if user exists
        if (DatabaseUser::exists($adinumber)) {
            throw new RestException(409, 'User Already Exists');
        }

        //check that instructor details have been uploaded
        if (!InstructorUpload::exists($adinumber)) {
            throw new RestException(403, 'Instructor Information Missing');
        }
        
        //Get instructor upload data
        $instructorUpload = InstructorUpload::retrieve($adinumber);
        
        //create salted password hash
        $saltLength = 32;
        $bool = true;
        $salt = base64_encode(openssl_random_pseudo_bytes($saltLength, $bool));
        //salt password
        $saltedPassword = hash('sha256', $salt . $password);

        //create user object
        $user = new DatabaseUser();
        $user->username = $adinumber;
        $user->passwordHash = $saltedPassword;
        $user->passwordSalt = $salt;
        $user->role = $role;

        //save user to database
        if (!$user->save()) {
            //will probably die before it ever gets here and die will show the mysqli error
            throw new RestException(400, 'Unknown Error - user not created');
        }
        $user->id = (int)$user->id;
            
        // if we get here user has been added need to construct instructor record
        $instructor = new Instructor();
        $instructor->fullname = $instructorUpload->fullname;
        $instructor->gender = $instructorUpload->gender;
        $instructor->mobilenumber = $instructorUpload->mobile;
        $instructor->email = $instructorUpload->email;
        $instructor->streetaddress = $instructorUpload->streetaddress;
        $instructor->town = $instructorUpload->town;
        $instructor->county = $instructorUpload->county;
        $instructor->postcode = $instructorUpload->postcode;
        $instructor->adinumber = $instructorUpload->adinumber;
        $instructor->hourlyrate = $instructorUpload->hourlyrate;
        $instructor->hours_5 = $instructorUpload->hours_5;
        $instructor->hours_10 = $instructorUpload->hours_10;
        $instructor->hours_20 = $instructorUpload->hours_20;
        $instructor->hours_30 = $instructorUpload->hours_30;
        $instructor->hours_40 = $instructorUpload->hours_40;
        $instructor->makeandmodel = $instructorUpload->makeandmodel;
        $instructor->transmission = $instructorUpload->transmission;
        $instructor->fueltype = $instructorUpload->fueltype;
        $instructor->areascovered = $instructorUpload->areascovered;
        $instructor->radius = $instructorUpload->radius;
        $instructor->bankdetails = $instructorUpload->bankdetails;
        $instructor->databaseuserid = $user->id;
        if (!$instructor->save()) {
            throw new RestException(400, 'Unknown Error');
        }
        return array("registration"=>"success");
    }
    
    /**
     * OWD Staff Login
     * @param type $data
     * @return type JSON data containing authentication token
     * @throws RestException
     * @url POST /owd/staff/login
     */
    public function staffLogin($data) {
        //authenticate user
        $username = $data->username;
        $password = $data->password;
        if (!empty($username) && (!empty($password))) {
            $user = DatabaseUser::authenticate($username, $password);
            if ($user === false || $user->role !== 'owd') {
                throw new RestException(401, "Unauthorised");
            } 
        }
        $token = "Basic ".base64_encode($username.":".$password);
        
        // get owdUser
        $owdUser = OwdUser::retrieve($user->id);
        return array("token"=>$token, "name"=>$owdUser->fullname);
        //return array("token"=>$token, "name"=>$owdUser->fullname);
    }

    /**
     * Instructor Login
     * @param type $data
     * @return type
     * @throws RestException
     * @url POST /instructor/login
     */
    public function instructorLogin($data) {
        //authenticate user
        $adinumber = $data->adinumber;
        $password = $data->password;
        if (!empty($adinumber) && (!empty($password))) {
            $user = DatabaseUser::authenticate($adinumber, $password);
            if ($user === false || $user->role !== 'instructor') {
                throw new RestException(401, "Unauthorised");
            }
        }
        $token = "Basic ".base64_encode($adinumber.":".$password);
        
        // get instructor
        $instructor = Instructor::retrieve($adinumber);
        return array("token"=>$token, "name"=>$instructor->fullname);
    }
    /**
     * Stores the uploaded csv file for instructors
     * @return JSON success message and store log file
     * @throws RestException
     * @url POST /owd/instructorcsv/upload
     */
    public function instructorUpload($data) {
        //return array("upload"=>$data->upload);
        //authenticate user
        $user= $this->authenticate();
        if ($user->role !== 'owd') {
            throw new RestException(401, 'Unauthorised');
        }
        // save data to table
        foreach($data as $record) {
            foreach($record as $key=>$value) {
                $adinumber = $record->adinumber;
                if (!InstructorUpload::exists($adinumber)) {
                    // instructor doesn't exist so save to table
                    $instructor = new InstructorUpload();
                    $instructor->adinumber = $record->adinumber;
                    $instructor->fullname = $record->fullname;
                    $instructor->gender = $record->gender;
                    $instructor->mobile = $record->mobile;
                    $instructor->email = $record->email;
                    $instructor->streetaddress = $record->streetaddress;
                    $instructor->town = $record->town;
                    $instructor->county = $record->county;
                    $instructor->postcode = $record->postcode;
                    $instructor->hourlyrate = (float)filter_var($record->hourlyrate, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $instructor->hours_5 = (float)filter_var($record->hours_5, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $instructor->hours_10 = (float)filter_var($record->hours_10, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $instructor->hours_20 = (float)filter_var($record->hours_20, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $instructor->hours_30 = (float)filter_var($record->hours_30, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $instructor->hours_40 = (float)filter_var($record->hours_40, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $instructor->makeandmodel = $record->model;
                    $instructor->transmission = trim(strtolower($record->transmission));
                    $instructor->fueltype = trim(strtolower($record->fueltype));
                    $instructor->areascovered = $record->areascovered;                    
                    $instructor->radius = (int)$record->radius;
                    $instructor->bankdetails = $record->bankdetails;
                    if (!$instructor->save()) {
                        //will probably die before it ever gets here and die will show the mysqli error
                        throw new RestException(400, 'Unknown Error - instructor upload not created');
                    }
                }
            }
        }
        return array('upload'=>"success");
    }
    
    /**
     * Stores the uploaded csv file for courses
     * @return JSON success message
     * @throws RestException
     * @url POST /owd/coursecsv/upload
     */
    public function courseUpload($data) {
        //authenticate user
        $user= $this->authenticate();
        if ($user->role !== 'owd') {
            throw new RestException(401, 'Unauthorised');
        }

        // save data to table
        foreach($data as $record) {
            foreach($record as $key=>$value) {
                $courseref = $record->courseref;
                if (!Course::exists($courseref)) {
                    // instructor doesn't exist so save to table
                    $course = new Course();
                    $course->courseref = trim($record->courseref);
                    $course->coursestart = isoDate(trim($record->coursestart));
                    $course->hours = trim($record->hours);
                    $course->lengthofcourse = trim(strtolower($record->lengthofcourse));
                    $course->testtype = trim(strtolower($record->testtype));
                    $course->fullname = trim($record->fullname);
                    $course->streetaddress = trim($record->streetaddress);
                    $course->town = trim($record->town);
                    $course->county = trim($record->county);
                    $course->postcode = trim($record->postcode);
                    $course->pupiltelephone = trim($record->pupiltelephone);
                    $course->drivingexperience = trim($record->drivingexperience);
                    $course->theoryrequired = (trim(strtolower($record->theoryrequired)) === 'yes' || trim(strtolower($record->theoryrequired)) === 'true') ? 1 : 0;
                    $course->testbooked = trim($record->testbooked);
                    $course->courseclaimed = (trim(strtolower($record->courseclaimed)) === 'yes' || trim(strtolower($record->courseclaimed)) === 'true') ? 1 : 0;
                    $course->instructorid = 0;
                    if (!$course->save()) {
                        //will probably die before it ever gets here and die will show the mysqli error
                        throw new RestException(400, 'Unknown Error - course upload not created');
                    }
                }
            }
        }
        return array('upload'=>"success");
    }
    
    /**
     * 
     * @throws RestException
     * @url PUT /owd/instructorsupload/update
     */
    public function instructorsUploadUpdate() {
        //authenticate user
        $user= $this->authenticate();
        if ($user->role !== 'owd') {
            throw new RestException(401, 'Unauthorised');
        }
        
    }
    
    /**
     * Authenticate existing user
     * 
     * @return $role or boolean false
     */
    private function authenticate() {
        $headers = getallheaders();
        $auth = $headers["Authorization"];
        $userpass = explode(":", base64_decode(substr($auth, 6)));
        $username = $userpass[0];
        $password = $userpass[1];
        if (!empty($username) && (!empty($password))) {
            $user = DatabaseUser::authenticate($username, $password);
            if ($user === false) {
                return false;
            } else {
                return $user;
            }
        } else {
            return false;
        }
    }
}    
    
    
//    /**
//     * Register a new Trader - returns trader object
//     * 
//     * @param type $data
//     * @return \Trader
//     * @throws RestException
//     * 
//     * @url POST /register
//     */
//    public function registerTrader($data) {
//        // parameters
//        $username = $data->username;
//        $password = $data->password;
//        $email = $data->email;
//        $role = 'trader';
//
//        //check if user exists
//        if (DatabaseUser::exists($username)) {
//            throw new RestException(409, 'Username Already Exists');
//        }
//
//        //create salted password hash
//        $saltLength = 32;
//        $bool = true;
//        $salt = base64_encode(openssl_random_pseudo_bytes($saltLength, $bool));
//        //salt password
//        $saltedPassword = hash('sha256', $salt . $password);
//
//        //create user object
//        $user = new DatabaseUser();
//        $user->username = $username;
//        $user->passwordHash = $saltedPassword;
//        $user->passwordSalt = $salt;
//        $user->role = $role;
//
//        //save user to database
//        if (!$user->save()) {
//            //will probably die before it ever gets here and die will show the mysqli error
//            throw new RestException(400, 'Unknown Error - user not created');
//        }
//
//        // If we are here then user created ok now create trader
//        $trader = new Trader();
//        $trader->email = $email;
//        $trader->databaseuserid = $user->id;
//
//        if (!$trader->save()) {
//            //will probably die before it ever gets here and die will show the mysqli error
//            throw new RestException(400, 'Unknown Error - trader not created');
//        }
//        $trader->id = (int)$trader->id;
//        return $trader;
//    }
//
//    /**
//     * Login - Returns trader object on success
//     * 
//     * @return type trader object
//     * @throws RestException
//     * @url POST /login
//     */
//    public function login() {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        $traderid = $this->getTraderID($auth);
//        $trader = Trader::find_by_id($traderid);
//        $trader->id = (int)$trader->id;
//        return $trader;
//    }
//    
//    /**
//     * Upload logo dataurl
//     * 
//     * @param type $data
//     * @return type
//     * @throws RestException
//     * @url PUT /trader/uploadlogo
//     */
//    public function uploadLogo($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        //get trader
//        $traderId = (int)$this->getTraderID($auth);
//        $trader = Trader::find_by_id($traderId);
//        if (!$trader) {
//            throw new RestException(400, "trader doesn't exist");
//        }
//        $trader->id = (int)$trader->id;
//        $trader->databaseuserid = (int)$trader->databaseuserid;
//        
//        if (isset($data->logourl)) {
//            $trader->logourl = $data->logourl;
//        }
//        
//        $trader->save();
//        
//        return $trader;
//    }
//    
//    /**
//     * Read Trader - returns trader object
//     * 
//     * Returns Trader Object
//     * @return type
//     * @throws RestException
//     * 
//     * @url GET /trader
//     */
//    public function readTrader() {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        //get trader
//        $traderId = (int)$this->getTraderID($auth);
//        $trader = Trader::find_by_id($traderId);
//        if (!$trader) {
//            throw new RestException(400, "trader doesn't exist");
//        }
//        $trader->id = (int)$trader->id;
//        $trader->databaseuserid = (int)$trader->databaseuserid;
//        return $trader;
//    }
//
//    /**
//     * Update Trader = Returns Trader object
//     * 
//     * @param type $data
//     * @return type
//     * @throws RestException
//     * 
//     * @url PUT /trader
//     */
//    public function updateTrader($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//
//        //get trader id from auth so we can compare it agains id in $data
//        $traderId = (int)($this->getTraderID($auth));
// 
//        if ($traderId !== $data->id) {
//        return array("trader"=>$traderId,"data"=>$data->id);
//            throw new RestException(401, "Unauthorised - Not your ID");
//        }
//        //authorised now
//        if (isset($data->id)) {
//            $trader = Trader::find_by_id($data->id);
//        } else {
//            throw new RestException(400, "id not specified");
//        }
//
//        if (isset($data->firstname)) {
//            $trader->firstname = $data->firstname;
//        }
//        if (isset($data->lastname)) {
//            $trader->lastname = $data->lastname;
//        }
//        if (isset($data->address1)) {
//            $trader->address1 = $data->address1;
//        }
//        if (isset($data->address2)) {
//            $trader->address2 = $data->address2;
//        }
//        if (isset($data->town)) {
//            $trader->town = $data->town;
//        }
//        if (isset($data->county)) {
//            $trader->county = $data->county;
//        }
//        if (isset($data->postcode)) {
//            $trader->postcode = $data->postcode;
//        }
//        if (isset($data->telephone)) {
//            $trader->telephone = $data->telephone;
//        }
//        if (isset($data->tradingname)) {
//            $trader->tradingname = $data->tradingname;
//        }
//        if (isset($data->vatnumber)) {
//            $trader->vatnumber = $data->vatnumber;
//        }
//        if (isset($data->email)) {
//            $trader->email = $data->email;
//        }
//        if (isset($data->bankaccountnumber)) {
//            $trader->bankaccountnumber = $data->bankaccountnumber;
//        }
//        if (isset($data->banksortcode)) {
//            $trader->banksortcode = $data->banksortcode;
//        }
//        if (isset($data->bankname)) {
//            $trader->bankname = $data->bankname;
//        }
//        if (isset($data->bankaccountname)) {
//            $trader->bankaccountname = $data->bankaccountname;
//        }
//        if (isset($data->stripereference)) {
//            $trader->stripereference = $data->stripereference;
//        }
//        if (isset($data->logourl)) {
//            $trader->logourl = $data->logourl;
//        }
//        if (isset($data->lastinvoicenumber)) {
//            $trader->lastinvoicenumber = $data->lastinvoicenumber;
//        }
//        $trader->databaseuserid = $this->getUserID($auth);
//
//        $trader->save();
//        $trader->id = (int)$trader->id;
//        $trader->databaseuserid = (int)$trader->databaseuserid;
//        return $trader;
//    }
//
//    /**
//     * DELETE Trader - returns true
//     * 
//     * @return type
//     * @throws RestException
//     * 
//     * @url DELETE /trader
//     */
//    public function deleteTrader() {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//
//        $traderId = $this->getTraderID($auth);
//        $trader = Trader::find_by_id($traderId);
//        $result = $trader->delete();
//
//        if (!$result) {
//            throw new RestException(400, "Unknown Error - Can not Delete Trader");
//        }
//
//        $userId = $this->getUserID($auth);
//        $user = DatabaseUser::find_by_id($userId);
//        $result = $user->delete();
//
//        return array("delete" => $result);
//    }
//
//    /**
//     * Create Client - Returns Lient Object
//     * 
//     * @param type $data
//     * @return \Client
//     * @throws RestException
//     * 
//     * @url POST /trader/client
//     */
//    public function createClient($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//
//        $client = new Client();
//        if (isset($data->firstname)) {
//            $client->firstname = $data->firstname;
//        } else {
//            throw new RestException(400, "firstname not supplied");
//        }
//        if (isset($data->lastname)) {
//            $client->lastname = $data->lastname;
//        } else {
//            throw new RestException(400, "lastname not supplied");
//        }
//        if (isset($data->housenumber)) {
//            $client->lastname = $data->lastname;
//        } else {
//            throw new RestException(400, "housenumber not supplied");
//        }
//        $client->address1 = isset($data->address1) ? $data->address1 : null;
//        $client->address2 = isset($data->address2) ? $data->address2 : null;
//        $client->town = isset($data->town) ? $data->town : null;
//        $client->county = isset($data->county) ? $data->county : null;
//        if (isset($data->postcode)) {
//            $client->postcode = $data->postcode;
//        } else {
//            throw new RestException(400, "postcode not supplied");
//        }
//        $client->landline = isset($data->landline) ? $data->landline : null;
//        $client->mobile = isset($data->mobile) ? $data->mobile : null;
//        $client->email = isset($data->email) ? $data->email : null;
//        $client->traderid = $this->getTraderID($auth);
//
//        if ($client->save()) {
//            $client->id = (int)$client->id;
//            $client->traderid = (int)$client->traderid;
//            return $client;
//        } else {
//            throw new RestException(400, "Unknown Error - Cannot Create Client");
//        }
//    }
//
//    /**
//     * Read Client from Client ID
//     * 
//     * @param type $id
//     * @return type
//     * @throws RestException
//     * 
//     * @url GET /trader/client/$id
//     */
//    public function readClient($id) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//
//        //get client
//        $client = Client::find_by_id($id);
//        if ($client) {
//            $client->id = (int)$client->id;
//            $client->traderid = (int)$client->traderid;
//            return $client;
//        } else {
//            throw new RestException(400, "client not found");
//        }
//    }
//
//    /**
//     * Read all clients for this trader
//     * 
//     * @return type
//     * @throws RestException
//     * 
//     * @url GET /trader/clients
//     */
//    public function readClients() {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        $traderId = $this->getTraderID($auth);
//        $sql = "SELECT * FROM client WHERE traderid = '{$traderId}' ORDER BY firstname, lastname";
//        $clients = Client::find_by_sql($sql);
//        if ($clients) {
//            foreach ($clients as $client) {
//                $client->id = (int)$client->id;
//                $client->traderid = (int)$client->traderid;
//            }
//            return $clients;
//        } else {
//            throw new RestEception(400, "no clients found");
//        }
//    }
//
//    /**
//     * Update Client table
//     * 
//     * @param type $data
//     * @return \Client
//     * @throws RestException
//     * 
//     * @url PUT /trader/client
//     */
//    public function updateClient($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//
//        if (isset($data->id)) {
//            $client = Client::find_by_id($data->id);
//        } else {
//            throw new RestException(400, "Client id Not Supplied");
//        }
//        if (!$client) {
//            throw new RestException(400, "client does not exist");
//        }
//        if (isset($data->firstname)) {
//            $client->firstname = $data->firstname;
//        }
//        if (isset($data->lastname)) {
//            $client->lastname = $data->lastname;
//        }
//        if (isset($data->housenumber)) {
//            $client->housenumber = $data->housenumber;
//        }
//        if (isset($data->address1)) {
//            $client->address1 = $data->address1;
//        }
//        if (isset($data->address2)) {
//            $client->address2 = $data->address2;
//        }
//        if (isset($data->town)) {
//            $client->town = $data->town;
//        }
//        if (isset($data->county)) {
//            $client->county = $data->county;
//        }
//        if (isset($data->postcode)) {
//            $client->postcode = $data->postcode;
//        }
//        if (isset($data->landline)) {
//            $client->landline = $data->landline;
//        }
//        if (isset($data->mobile)) {
//            $client->mobile = $data->mobile;
//        }
//        if (isset($data->email)) {
//            $client->email = $data->email;
//        }
//        $client->traderid = (int)$this->getTraderId($auth);
//
//        $client->save();
//        $client->id = (int)$client->id;
//        $client->traderid = (int)$client->traderid;
//        return $client;
//    }
//
//    /**
//     * Delete Client
//     * 
//     * @param type $data
//     * @return type
//     * @throws RestException
//     * 
//     * @url DELETE /trader/client/$id
//     */
//    public function deleteClient($id) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        if (!isset($id)) {
//            throw new RestException(400, "id not specified");
//        }
//        $client = Client::find_by_id($id);
//        if (!$client) {
//            throw new RestException(400, "Client not found");
//        }
//
//        $result = $client->delete();
//        return array("delete" => $result);
//    }
//
//    /**
//     * Create Product - Returns Product Object
//     * 
//     * @param type $data
//     * @return \Product
//     * @throws RestException
//     * 
//     * @url POST /trader/product
//     */
//    public function createProduct($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        $product = new Product();
//        if (isset($data->productkey)) {
//            $product->productkey = $data->productkey;
//        } else {
//            throw new RestException(400, "product key not supplied");
//        }
//        if (isset($data->description)) {
//            $product->description = $data->description;
//        } else {
//            throw new RestException(400, "description not supplied");
//        }
//        $product->price = isset($data->price) ? $data->price : NULL;
//        $product->misc = isset($data->misc) ? $data->misc : NULL;
//        $product->traderid = $this->getTraderID($auth);
//
//        if ($product->save()) {
//            $product->id = (int)$product->id;
//            $product->price = (float)$product->price;
//            $product->traderid = (int)$product->traderid;
//            return $product;
//        } else {
//            throw new RestException(400, "Unknown error - cannot create product");
//        }
//    }
//
//    /**
//     * Read Product by id - Returns Product Object
//     * 
//     * @param type $id
//     * @return type
//     * @throws RestException
//     * 
//     * @url GET /trader/product/$id
//     */
//    public function readProduct($id) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        $product = Product::find_by_id($id);
//        if ($product) {
//            $product->id = (int)$product->id;
//            $product->price = (float)$product->price;
//            $product->traderid = (int)$product->traderid;
//            return $product;
//        } else {
//            throw new RestException(400, 'productct not found');
//        }
//    }
//
//    /**
//     * Read Products - Returns array of Products
//     *  
//     * @return type
//     * @throws RestException
//     * @throws RestEception
//     * 
//     * @url GET /trader/products
//     */
//    public function readProducts() {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        $traderId = $this->getTraderID($auth);
//        $sql = "SELECT * FROM product WHERE traderid = '{$traderId}' ORDER BY productkey";
//        $products = Product::find_by_sql($sql);
//        if ($products) {
//            foreach($products as $product){
//                $product->id = (int)$product->id;
//                $product->price = (float)$product->price;
//                $product->traderid = (int)$product->traderid;
//            }
//            return $products;
//        } else {
//            throw new RestEception(400, "no products found");
//        }
//    }
//
//    /**
//     * Update Product - Return Product Object
//     * 
//     * @param type $data
//     * @return type
//     * @throws RestException
//     * 
//     * @url PUT /trader/product
//     */
//    public function updateProduct($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        if (isset($data->id)) {
//            $product = Product::find_by_id($data->id);
//        } else {
//            throw new RestException(400, "product id not supplied");
//        }
//        if (!$product) {
//            throw new RestException(400, "product does not exist");
//        }
//        if (isset($data->productkey)) {
//            $product->productkey = $data->productkey;
//        }
//        if (isset($data->description)) {
//            $product->description = $data->description;
//        }
//        if (isset($data->price)) {
//            $product->price = $data->price;
//        }
//        if (isset($data->misc)) {
//            $product->misc = $data->misc;
//        }
//        $product->save();
//        $product->id = (int)$product->id;
//        $product->price = (float)$product->price;
//        $product->traderid = (int)$product->traderid;
//        return $product;
//    }
//
//    /**
//     * Delete Product
//     * 
//     * @param type $id
//     * @return type
//     * @throws RestException
//     * 
//     * @url DELETE /trader/product/$id
//     */
//    public function deleteProduct($id) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//
//        $product = Product::find_by_id($id);
//
//        if (!$product) {
//            throw new RestException(400, "product doesn't exist");
//        }
//        $result = $product->delete();
//        return array("delete" => $result);
//    }
//
//    /**
//     * Create Invoice - Returns Invoice Object
//     * 
//     * @param type $data
//     * @return \Invoice
//     * @throws RestException
//     * 
//     * @url POST /trader/invoice
//     */
//    public function createInvoice($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//
//        $invoice = new Invoice();
//
//        $invoice->invoicedate = (isset($data->invoicedate) && $this->validMySQLDate($data->invoicedate)) ? $data->invoicedate : date('Y-m-d');
//        if (isset($data->amount)) {
//            $invoice->amount = $data->amount;
//        } else {
//            throw new RestException(400, "amount not specified");
//        }
//        $invoice->vatamount = isset($data->vatamount) ? $data->vatamount : 0;
//        $invoice->paymenttype = isset($data->paymenttype) ? $data->paymenttype : 'cash';
//        $invoice->chequenumber = isset($data->chequenumber) ? $data->chequenumber : 0;
//        if (isset($data->clientid)) {
//            $invoice->clientid = (int)$data->clientid;
//        } else {
//            throw new RestException(400, "clientid not specified");
//        }
//        if (isset($data->productid)) {
//            if(!Product::find_by_id($data->productid)) {
//                throw new RestException(400, "product doesn't exist");
//            }
//            $invoice->productid = (int)$data->productid;
//        } else {
//            throw new RestException(400, "productid not specified");
//        }
//        $invoice->traderid = (int)$this->getTraderId($auth);
////return $invoice;
//        $result = $invoice->save();
//        if ($result) {
//            return $invoice;
//        } else {
//            throw new RestException(400, "Unknown error - Invoice not created");
//        }
//    }
//    
//    /**
//     * Read Invoice Full - Returns an object containing invoice,client,product,trader info
//     *
//     * @param type $id
//     * @return type
//     * @throws RestException
//     *
//     * @url GET /trader/invoicefull/$id
//     */
//     public function readInvoiceFull($id) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        $invoice = InvoiceView::find_by_id($id);
//        if (!$invoice) {
//            throw new RestException(400, "no such invoice");
//        }
//        $invoice->id = (int)$invoice->id;
//        $invoice->invoiceamount = (float)$invoice->invoiceamount;
//        $invoice->invoicevatamount = (float)$invoice->invoicevatamount;
//        $invoice->invoicechequenumber = (int)$invoice->invoicechequenumber;
//        $invoice->clientid = (int)$invoice->clientid;
//        $invoice->productid = (int)$invoice->productid;
//        $invoice->traderid = (int)$invoice->traderid;
//        $invoice->productprice = (float)$invoice->productprice;
//        return $invoice;
//     }
//
//    /**
//     * Read Invoice - Returns Invoice Object
//     * 
//     * @param type $id
//     * @return type
//     * @throws RestException
//     * 
//     * @url GET /trader/invoice/$id
//     */
//    public function readInvoice($id) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        $invoice = Invoice::find_by_id($id);
//        if (!$invoice) {
//            throw new RestException(400, "no such invoice");
//        }
//        $invoice->id = (int)$invoice->id;
//        $invoice->amount = (float)$invoice->amount;
//        $invoice->vatamount = (float)$invoice->vatamount;
//        $invoice->chequenumber = (int)$invoice->chequenumber;
//        $invoice->clientid = (int)$invoice->clientid;
//        $invoice->productid = (int)$invoice->productid;
//        $invoice->traderid = (int)$invoice->traderid;
//        return $invoice;
//    }
//
//    /**
//     * Read Client Invoices - Returns an array of objects
//     * 
//     * @param type $id
//     * @return type
//     * @throws RestException
//     * 
//     * @url GET /trader/invoices/client/$id
//     */
//    public function readClientInvoices($id) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        $sql = "SELECT * FROM invoice WHERE clientid = '{$id}'";
//        $invoices = Invoice::find_by_sql($sql);
//        if (!$invoices) {
//            throw new RestException(400, "no invoices");
//        }
//        foreach($invoices as $invoice) {
//            $invoice->id = (int)$invoice->id;
//            $invoice->amount = (float)$invoice->amount;
//            $invoice->vatamount = (float)$invoice->vatamount;
//            $invoice->chequenumber = (int)$invoice->chequenumber;
//            $invoice->clientid = (int)$invoice->clientid;
//            $invoice->productid = (int)$invoice->productid;
//            $invoice->traderid = (int)$invoice->traderid;
//        }
//        return $invoices;
//    }
//
//    /**
//     * Read all invoices - returns an array of objects
//     * 
//     * @return type
//     * @throws RestException
//     * 
//     * @url GET /trader/invoices
//     */
//    public function readAllInvoices() {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//
//        $sql = "SELECT * FROM invoice_view WHERE traderid = '{$this->getTraderID($auth)}' ORDER BY clientfirstname, clientlastname";
//        $invoices = InvoiceView::find_by_sql($sql);
//        if (!$invoices) {
//            throw new RestException(400, "no invoices");
//        }
//        foreach($invoices as $invoice) {
//            $invoice->id = (int)$invoice->id;
//            $invoice->amount = (float)$invoice->amount;
//            $invoice->vatamount = (float)$invoice->vatamount;
//            $invoice->chequenumber = (int)$invoice->chequenumber;
//            $invoice->clientid = (int)$invoice->clientid;
//            $invoice->productid = (int)$invoice->productid;
//            $invoice->traderid = (int)$invoice->traderid;
//        }
//        return $invoices;
//    }
//    
//    /**
//     * Retrieve Unpaid Invoices
//     *
//     * @return type
//     * @throws RestException
//     *
//     * @url GET /trader/invoices/unpaid
//     */
//    public function readUnpaidInvoices() {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//
//        $sql = "SELECT * FROM invoice_view WHERE invoicepaid = 0 AND traderid = '{$this->getTraderID($auth)}' ORDER BY clientfirstname, clientlastname";
//        $invoices = InvoiceView::find_by_sql($sql);
//        if (!$invoices) {
//            throw new RestException(400, "no invoices");
//        }
//        foreach($invoices as $invoice) {
//            $invoice->id = (int)$invoice->id;
//            $invoice->amount = (float)$invoice->amount;
//            $invoice->vatamount = (float)$invoice->vatamount;
//            $invoice->chequenumber = (int)$invoice->chequenumber;
//            $invoice->clientid = (int)$invoice->clientid;
//            $invoice->productid = (int)$invoice->productid;
//            $invoice->traderid = (int)$invoice->traderid;
//        }
//        return $invoices;
//    }
//
//    /**
//     * Update Invoice - Returns Invoice Object
//     * 
//     * @param type $data
//     * @return type
//     * @throws RestException
//     * 
//     * @url PUT /trader/invoice
//     */
//    public function updateInvoice($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        if ($data->id) {
//            $invoice = Invoice::find_by_id($data->id);
//        } else {
//            throw new RestException(400, "id not received");
//        }
//        if (!$invoice) {
//            throw new RestException(400, "no such invoice");
//        }
//        if (isset($data->invoicedate) && $this->validMySQLDate($data->invoicedate)) {
//            $invoice->invoicedate = $data->invoicedate;
//        }
//        if (isset($data->amount)) {
//            $invoice->amount = $data->amount;
//        }
//        if (isset($data->vatamount)) {
//            $invoice->vatamount = $data->vatamount;
//        }
//        if (isset($data->paymenttype)) {
//            $invoice->paymenttype = $data->paymenttype;
//        }
//        if (isset($data->chequenumber)) {
//            $invoice->chequenumber = $data->chequenumber;
//        }
//        if (isset($data->clientid)) {
//            $invoice->clientid = $data->clientid;
//        }
//        if (isset($data->productid)) {
//            $invoice->productid = $data->productid;
//        }
////        if (isset($data->traderid)) {
////            $invoice->traderid = $data->traderid;
////        }
//
//        $invoice->save();
//        $invoice->id = (int)$invoice->id;
//        $invoice->amount = (float)$invoice->amount;
//        $invoice->vatamount = (float)$invoice->vatamount;
//        $invoice->chequenumber = (int)$invoice->chequenumber;
//        $invoice->clientid = (int)$invoice->clientid;
//        $invoice->productid = (int)$invoice->productid;
//        $invoice->traderid = (int)$invoice->traderid;
//        return $invoice;
//    }
//
//    /**
//     * Delete Invoice
//     * 
//     * @param type $data
//     * @return type
//     * @throws RestException
//     * 
//     * @url DELETE /trader/invoice/$id
//     */
//    public function deleteInvoice($id) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        $invoice = Invoice::find_by_id($id);
//        if (!$invoice) {
//            throw new RestException(400, "no such invoice");
//        }
//        $result = $invoice->delete();
//        return array("delete" => $result);
//    }
//    
//    /**
//     * Email invoice to Customer
//     * @param type $data
//     * @return type
//     * @throws RestException
//     * @url POST /trader/emailinvoice
//     */
//    public function emailInvoice($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        
//        //get email data
//        $to = $data->to;
//        $toName = $data->toName;
//        $from = $data->from;
//        $fromName = $data->fromName;
//        $bcc = $data->bcc;
//        $bccName = $data->bccName;
//        $subject = $data->subject;
//        $body = $data->body;
//        $jpg = $data->jpg;
//        
//        //convert pdf reference to a file
//        $jpgData = str_replace(' ','+',$jpg);
//        $jpgData =  substr($jpgData,strpos($jpgData,",")+1);
//        $jpgData = base64_decode($jpgData);
//        // Path where the image is going to be saved
//        $filePath = $_SERVER['DOCUMENT_ROOT']. 'jpg/'. $fromName .'-invoice.jpg';
//        // Write $imgData into the image file
//        $file = fopen($filePath, 'w');
//        fwrite($file, $jpgData);
//        fclose($file);
//        
//        //$email=true;
//        $email = $this->sendMail($to, $toName, $bcc, $bccName, $from, $fromName, $subject, $body, $filePath);
//        if (!email) {
//            throw new RestException(400,"Email Failed to Send");
//        }
//        return array("email"=>$email);
//        
//    }
//    /**
//     * Convert a PDF To JPG
//     * @param type $data
//     * @return type
//     * @throws RestException
//     * @url POST /trader/invoicejpg
//     */
//    public function getInvoiceJpg($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        
//        //get $data
//        $pdfData = $data->pdf;
//        $traderTradingName = $data->traderTradingName;
//
//        //convert pdf reference to a file
//        $pdfData = str_replace(' ','+',$pdfData);
//        $pdfData =  substr($pdfData,strpos($pdfData,",")+1);
//        $pdfData = base64_decode($pdfData);
//        
//        // Path where the image is going to be saved
//        $pdfFilePath = $_SERVER['DOCUMENT_ROOT']. 'pdf/'. $traderTradingName .'-invoice.pdf';
//        $jpgFilePath = $_SERVER['DOCUMENT_ROOT']. 'jpg/'. $traderTradingName .'-invoice.jpg';
//
//        // Write $pdfData into the pdf file
//        $file = fopen($pdfFilePath, 'w');
//        fwrite($file, $pdfData);
//        fclose($file);
//        
//        //convert to jpeg
//        $this->convertPdfToJpg($pdfFilePath, $jpgFilePath);
//        
//        //get jpeg file as a datauri
//        $imageData = base64_encode(file_get_contents($jpgFilePath));
//        
//        // Format the image SRC:  data:{mime};base64,{data};
//        $jpguri = 'data: '.mime_content_type($jpgFilePath).';base64,'.$imageData;
//        
//        return array(jpg=>$jpguri);
//    }
//
//    /**
//     * Create Diary Event
//     * 
//     * @param type $data
//     * @return \Diary
//     * @throws RestException
//     * 
//     * @url POST /trader/diary
//     */
//    public function createDiary($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        $diary = new Diary();
//        if (isset($data->date) && $this->validMySQLDate($data->date)) {
//            $diary->date = $data->date;
//        } else {
//            throw new RestException(400, "invalid date");
//        }
//        if (isset($data->time) && $this->validMySQLTime($data->time)) {
//            $diary->time = $data->time;
//        } else {
//            throw new RestException(400, "invalid time");
//        }
//        if (isset($data->description)) {
//            $diary->description = $data->description;
//        } else {
//            throw new RestException(400, "no description given");
//        }
//        if (isset($data->clientid)) {
//            $diary->clientid = $data->clientid;
//        } else {
//            throw new RestException(400, " no clientid given");
//        }
//        $diary->traderid = $this->getTraderID($auth);
//        if ($diary->save()) {
//            $diary->id = (int)$diary->id;
//            $diary->clientid = (int)$diary->clientid;
//            $diary->traderid = (int)$diary->traderid;
//            return $diary;
//        } else {
//            throw new RestException(400, "Unknown Error - Can not create diary event");
//        }
//    }
//
//    /**
//     * Return array of Diary events between two dates
//     * 
//     * @param type $from
//     * @param type $to
//     * @return type
//     * @throws RestException
//     * 
//     * @url GET /trader/diary/$from/$to
//     */
//    public function readDiary($from, $to) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        if (!$this->validMySQLDate($to) || (!$this->validMySQLDate($from))) {
//            throw new RestException(400, "invalid dates");
//        }
//        $traderid = $this->getTraderID($auth);
//
//        //make sql string
//        $sql = "SELECT * FROM listdiaryevents_view WHERE traderid = '{$traderid}' AND ";
//        $sql .= "date >= '{$from}' AND ";
//        $sql .= "date <= '{$to}'";
//
//        $diaries = DiaryByDate::find_by_sql($sql);
//        foreach($diaries as $diary) {
//            $diary->id = (int)$diary->id;
//            $diary->clientid = (int)$diary->clientid;
//            $diary->traderid = (int)$diary->traderid;
//        }
//        return $diaries;
//    }
//
//    /**
//     * Read Todays Diary Events
//     * 
//     * @return type
//     * @throws RestException
//     * 
//     * @url GET /trader/diary/today
//     */
//    public function readTodaysDiary() {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        $today = date('Y-m-d');
//        $traderid = $this->getTraderID($auth);
//
//        $sql = "SELECT * FROM listdiaryevents_view WHERE traderid= '{$traderid}' AND ";
//        $sql .= "date = '{$today}'";
//
//        $diaries = DiaryToday::find_by_sql($sql);
//        foreach($diaries as $diary) {
//            $diary->id = (int)$diary->id;
//            $diary->clientid = (int)$diary->clientid;
//            $diary->traderid = (int)$diary->traderid;
//        }
//        return $diaries;
//    }
//
//    /**
//     * Update Diary Event
//     * 
//     * @param type $data
//     * @return type
//     * @throws RestException
//     * 
//     * @url PUT /trader/diary
//     */
//    public function updateDiary($data) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        if (isset($data->id)) {
//            $diary = Diary::find_by_id($data->id);
//            if (!$diary) {
//                throw new RestException(400, "no such diary event");
//            }
//        } else {
//            throw new RestException(400, "no id specified");
//        }
//        if (isset($data->date) && $this->validMySQLDate($data->date)) {
//            $diary->date = $data->date;
//        }
//        if (isset($data->time) && $this->validMySQLTime($data->time)) {
//            $diary->time = $data->time;
//        }
//        if (isset($data->description)) {
//            $diary->description = $data->description;
//        }
//        if (isset($data->clientid)) {
//            $diary->clientid = $data->clientid;
//        }
//        $diary->traderid = $this->getTraderID($auth);
//
//        $diary->save();
//        $diary->id = (int)$diary->id;
//        $diary->clientid = (int)$diary->clientid;
//        $diary->traderid = (int)$diary->traderid;
//        return $diary;
//    }
//    
//    /**
//     * Delete Diary Event
//     * 
//     * @param type $id
//     * @return type
//     * @throws RestException
//     * 
//     * @url DELETE /trader/diary/$id
//     */
//    public function deleteDiary($id) {
//        //get authorisation from headers
//        $headers = getallheaders();
//        $auth = $headers["Authorization"];
//
//        //check authorization
//        $role = $this->authenticate($auth);
//        if ($role !== 'trader') {
//            throw new RestException(401, "Unauthorized");
//        }
//        if (isset($id)) {
//            $diary = Diary::find_by_id($id);
//            if (!$diary) {
//                throw new RestException(400, "no such diary event");
//            }
//        } else {
//            throw new RestException(400, "id not specified");
//        }
//        $result = $diary->delete();
//
//        return array("delete" => $result);
//    }
//
//    // Private Helper Functions
//
//    /**
//     * Returns trader id from authorisation string
//     * 
//     * @param type $auth
//     * @return type
//     */
//    private function getTraderID($auth) {
//        $userId = $this->getUserID($auth);
//        $sql = "SELECT * FROM trader WHERE databaseuserid = '{$userId}' LIMIT 1";
//        $result_array = Trader::find_by_sql($sql);
//        $traderId = array_shift($result_array)->id;
//        return $traderId;
//    }
//
//    /**
//     * returns user id from authorisation string
//     * 
//     * @param type $auth
//     * @return type
//     */
//    private function getUserID($auth) {
//        $username = explode(":", base64_decode(substr($auth, 6)))[0];
//        $sql = "SELECT * FROM databaseuser WHERE username = '{$username}' LIMIT 1";
//        $result_array = DatabaseUser::find_by_sql($sql);
//        $userId = array_shift($result_array)->id;
//        return $userId;
//    }
//
//
//    /**
//     * Checks date string as valid MySQL Date
//     * 
//     * @param type $date
//     * @return boolean
//     */
//    private function validMySQLDate($date) {
//        if (!$this->validMySQLDateFormat($date)) {
//            return false;
//        }
//        $day = substr($date, 8);
//        $month = substr($date, 5, 2);
//        $year = substr($date, 0, 4);
//        return (checkdate($month, $day, $year));
//    }
//
//    /**
//     * Checks for valid MySQL date format ('yyyy-mm-dd') returns true or false
//     * DOES NOT check if date is valid
//     * 
//     * @param type $date
//     * @return type
//     */
//    private function validMySQLDateFormat($date) {
//        return (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date));
//    }
//
//    /**
//     * Validates time in format HH:MM:SS
//     * @param type $time
//     * @return boolean
//     */
//    private function validMySQLTime($time) {
//        if (!$this->validMySQLTimeFormat($time)) {
//            return false;
//        }
//        $time_array = explode(':', $time);
//        $hour = $time_array[0];
//        $min = $time_array[1];
//        $sec = $time_array[2];
//        if ($hour < 0 || $hour > 23 || !is_numeric($hour)) {
//            return false;
//        }
//        if ($min < 0 || $min > 59 || !is_numeric($min)) {
//            return false;
//        }
//        if ($sec < 0 || $sec > 59 || !is_numeric($sec)) {
//            return false;
//        }
//        return true;
//    }
//
//    /**
//     * 
//     * @param type $time
//     * @return type
//     */
//    private function validMySQLTimeFormat($time) {
//        return (preg_match("/^([0-9]{2}:[0-9]{2}:[0-9]{2})$/", $time));
//    }
//    
//    private function sendMail($to, $toName, $bcc, $bccName, $from, $fromName, $subject, $body, $filePath) {
//        $mail = new PHPMailer;
//
//        //$mail->SMTPDebug = 3;                               // Enable verbose debug output
//
//        $mail->isSMTP();                                      // Set mailer to use SMTP
//        $mail->Host = 'mail.comp-solutions.org.uk';  // Specify main and backup SMTP servers
//        $mail->SMTPAuth = true;                               // Enable SMTP authentication
//        $mail->Username = 'web@comp-solutions.org.uk';            // SMTP username
//        $mail->Password = 'ravine123';                           // SMTP password
//        //$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
//        $mail->Port = 25;                                    // TCP port to connect to
//
//        $mail->setFrom('web@comp-solutions.org.uk', $fromName);
//        $mail->addAddress($to, $toName);                      // Add a recipient
//        //$mail->addAddress('ellen@example.com');               // Name is optional
//        //$mail->addReplyTo('info@example.com', 'Information');
//        //$mail->addCC('cc@example.com');
//        $mail->addBCC($bcc, $bccName);
//
//        $mail->addAttachment($filePath);                     // Add attachments
//        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
//        $mail->isHTML(true);                                  // Set email format to HTML
//
//        $mail->Subject = $subject;
//        $mail->Body    = $body;
//        $mail->AltBody = 'Invoice';
//
//        if(!$mail->send()) {
//            return false;
//        } else {
//            return true;
//        }
//    }
//    
//    private function convertPdfToJpg($pdfFilePath, $jpgFilePath) {
//        $imagick = new Imagick();
//        $imagick->setResolution(150,150);
//        $imagick->readImage($pdfFilePath);
//        $imagick->writeimage($jpgFilePath);
//    }
//}
