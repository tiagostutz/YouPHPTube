<?php

if (empty($global['systemRootPath'])) {
    $global['systemRootPath'] = "../";
}
require_once $global['systemRootPath'] . 'videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/bootGrid.php';

class User {

    private $id;
    private $user;
    private $name;
    private $email;
    private $password;
    private $isAdmin;
    private $status;
    private $photoURL;
    private $backgroundURL;
    private $recoverPass;
    private $userGroups = array();

    function __construct($id, $user = "", $password = "") {
        if (empty($id)) {
            // get the user data from user and pass
            $this->user = $user;
            if ($password !== false) {
                $this->password = $password;
            } else {
                $this->loadFromUser($user);
            }
        } else {
            // get data from id
            $this->load($id);
        }
    }

    function getEmail() {
        return $this->email;
    }

    function getUser() {
        return $this->user;
    }

    private function load($id) {
        $user = self::getUserDb($id);
        if (empty($user))
            return false;
        foreach ($user as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }

    private function loadFromUser($user) {
        $user = self::getUserDbFromUser($user);
        if (empty($user))
            return false;
        foreach ($user as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }

    function loadSelfUser() {
        $this->load($this->getId());
    }

    static function getId() {
        if (self::isLogged()) {
            return $_SESSION['user']['id'];
        } else {
            return false;
        }
    }

    function getBdId() {
        return $this->id;
    }

    static function updateSessionInfo() {
        if (self::isLogged()) {
            $user = self::getUserDb($_SESSION['user']['id']);
            $_SESSION['user'] = $user;
        }
    }

    static function getName() {
        if (self::isLogged()) {
            return $_SESSION['user']['name'];
        } else {
            return false;
        }
    }
    
    static function getUserName() {
        if (self::isLogged()) {
            return $_SESSION['user']['user'];
        } else {
            return false;
        }
    }
    
    static function getUserPass() {
        if (self::isLogged()) {
            return $_SESSION['user']['password'];
        } else {
            return false;
        }
    }
    
    function _getName(){
        return $this->name;
    }

    static function getPhoto($id = "") {
        global $global;
        if (!empty($id)) {
            $user = self::findById($id);
            if (!empty($user)) {
                $photo = $user['photoURL'];
            }
        } else if (self::isLogged()) {
            $photo = $_SESSION['user']['photoURL'];
            
        }
        if(preg_match("/videos\/userPhoto\/.*/", $photo)){
            $photo = $global['webSiteRootURL'].$photo;
        }
        if (empty($photo)) {
            $photo = $global['webSiteRootURL'] . "img/userSilhouette.jpg";
        }
        return $photo;
    }

    static function getMail() {
        if (self::isLogged()) {
            return $_SESSION['user']['email'];
        } else {
            return false;
        }
    }

    function save($updateUserGroups = false) {
        global $global;
        if (empty($this->user) || empty($this->password)) {
            die('Error : ' . __("You need a user and passsword to register"));
        }
        if (empty($this->isAdmin)) {
            $this->isAdmin = "false";
        }
        if (empty($this->status)) {
            $this->status = 'a';
        }
        if (!empty($this->id)) {
            $sql = "UPDATE users SET user = '{$this->user}', password = '{$this->password}', email = '{$this->email}', name = '{$this->name}', isAdmin = {$this->isAdmin}, status = '{$this->status}', photoURL = '{$this->photoURL}', backgroundURL = '{$this->backgroundURL}', recoverPass = '{$this->recoverPass}' , modified = now() WHERE id = {$this->id}";
        } else {
            $sql = "INSERT INTO users (user, password, email, name, isAdmin, status,photoURL,recoverPass, created, modified) VALUES ('{$this->user}','{$this->password}','{$this->email}','{$this->name}',{$this->isAdmin}, '{$this->status}', '{$this->photoURL}', '{$this->recoverPass}', now(), now())";
        }
        //echo $sql;
        $insert_row = $global['mysqli']->query($sql);
            
        if ($insert_row) {
            if (empty($this->id)) {
                $id = $global['mysqli']->insert_id;
            } else {
                $id = $this->id;
            }
            if($updateUserGroups){
                require_once './userGroups.php';
                // update the user groups
                UserGroups::updateUserGroups($id, $this->userGroups);
            }
            return $id;
        } else {
            die($sql . ' Error : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
    }

    function delete() {
        if (!self::isAdmin()) {
            return false;
        }
        // cannot delete yourself
        if (self::getId() === $this->id) {
            return false;
        }

        global $global;
        if (!empty($this->id)) {
            $sql = "DELETE FROM users WHERE id = {$this->id}";
        } else {
            return false;
        }
        $resp = $global['mysqli']->query($sql);
        if (empty($resp)) {
            die('Error : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $resp;
    }

    function login($noPass = false, $encodedPass=false) {
        if ($noPass) {
            $user = $this->find($this->user, false, true);
        } else {
            $user = $this->find($this->user, $this->password, true, $encodedPass);
        }
        if ($user) {
            $_SESSION['user'] = $user;
            $this->setLastLogin($_SESSION['user']['id']);
        } else {
            unset($_SESSION['user']);
        }
    }

    private function setLastLogin($user_id) {
        global $global;
        if (empty($user_id)) {
            die('Error : setLastLogin ');
        }
        $sql = "UPDATE users SET lastLogin = now(), modified = now() WHERE id = {$user_id}";
        return $global['mysqli']->query($sql);
    }

    static function logoff() {
        unset($_SESSION['user']);
    }

    static function isLogged() {
        return !empty($_SESSION['user']);
    }

    static function isAdmin() {
        return !empty($_SESSION['user']['isAdmin']);
    }

    private function find($user, $pass, $mustBeactive = false, $encodedPass=false) {
        global $global;

        $user = $global['mysqli']->real_escape_string($user);
        $sql = "SELECT * FROM users WHERE user = '$user' ";
        
        if($mustBeactive){
            $sql .= " AND status = 'a' ";
        }
        
        if ($pass !== false) {
            if(!$encodedPass || $encodedPass === 'false'){
                $pass = md5($pass);
            }            
            $sql .= " AND password = '$pass' ";
        }
        $sql .= " LIMIT 1";
        $res = $global['mysqli']->query($sql);

        if ($res) {
            $user = $res->fetch_assoc();
        } else {
            $user = false;
        }
        return $user;
    }

    static private function findById($id) {
        global $global;

        $sql = "SELECT * FROM users WHERE id = '$id'  LIMIT 1";
        $res = $global['mysqli']->query($sql);

        if ($res) {
            $user = $res->fetch_assoc();
        } else {
            $user = false;
        }
        return $user;
    }

    static private function getUserDb($id) {
        global $global;
        $id = intval($id);
        $sql = "SELECT * FROM users WHERE  id = $id LIMIT 1";
        $res = $global['mysqli']->query($sql);
        if ($res) {
            $user = $res->fetch_assoc();
        } else {
            $user = false;
        }
        return $user;
    }

    static private function getUserDbFromUser($user) {
        global $global;
        $sql = "SELECT * FROM users WHERE user = '$user' LIMIT 1";
        $res = $global['mysqli']->query($sql);
        if ($res) {
            $user = $res->fetch_assoc();
        } else {
            $user = false;
        }
        return $user;
    }

    function setUser($user) {
        $this->user = strip_tags($user);
    }

    function setName($name) {
        $this->name = strip_tags($name);
    }

    function setEmail($email) {
        $this->email = strip_tags($email);
    }

    function setPassword($password) {
        if (!empty($password)) {
            $this->password = md5($password);
        }
    }

    function setIsAdmin($isAdmin) {
        if (empty($isAdmin) || $isAdmin === "false") {
            $isAdmin = "false";
        } else {
            $isAdmin = "true";
        }
        $this->isAdmin = $isAdmin;
    }

    function setStatus($status) {
        $this->status = strip_tags($status);
    }

    function getPhotoURL() {
        return $this->photoURL;
    }

    function setPhotoURL($photoURL) {
        $this->photoURL = strip_tags($photoURL);
    }

    static function getAllUsers() {
        if (!self::isAdmin()) {
            return false;
        }
        //will receive 
        //current=1&rowCount=10&sort[sender]=asc&searchPhrase=
        global $global;
        $sql = "SELECT * FROM users WHERE 1=1 ";

        $sql .= BootGrid::getSqlFromPost(array('name', 'email', 'user'));

        $res = $global['mysqli']->query($sql);
        $user = array();
            require_once './userGroups.php';
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['groups'] = UserGroups::getUserGroups($row['id']);
                $row['tags'] = self::getTags($row['id']);
                $user[] = $row;
            }
            //$user = $res->fetch_all(MYSQLI_ASSOC);
        } else {
            $user = false;
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $user;
    }

    static function getTotalUsers() {
        if (!self::isAdmin()) {
            return false;
        }
        //will receive 
        //current=1&rowCount=10&sort[sender]=asc&searchPhrase=
        global $global;
        $sql = "SELECT id FROM users WHERE 1=1  ";

        $sql .= BootGrid::getSqlSearchFromPost(array('name', 'email', 'user'));

        $res = $global['mysqli']->query($sql);


        return $res->num_rows;
    }

    static function userExists($user) {
        global $global;
        $user = $global['mysqli']->real_escape_string($user);
        $sql = "SELECT * FROM users WHERE user = '$user' LIMIT 1";
        $res = $global['mysqli']->query($sql);
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            return $user['id'];
        } else {
            return false;
        }
    }

    static function createUserIfNotExists($user, $pass, $name, $email, $photoURL, $isAdmin = false) {
        global $global;
        $user = $global['mysqli']->real_escape_string($user);
        if (!$userId = self::userExists($user)) {
            if (empty($pass)) {
                $pass = rand();
            }
            $pass = md5($pass);
            $userObject = new User(0, $user, $pass);
            $userObject->setEmail($email);
            $userObject->setName($name);
            $userObject->setIsAdmin($isAdmin);
            $userObject->setPhotoURL($photoURL);
            $userId = $userObject->save();
            return $userId;
        }
        return false;
    }

    function getRecoverPass() {
        return $this->recoverPass;
    }

    function setRecoverPass($recoverPass) {
        $this->recoverPass = $recoverPass;
    }

    static function canUpload() {
        global $global, $config;
        if ($config->getAuthCanUploadVideos()) {
            return self::isLogged();
        }
        return self::isAdmin();
    }

    static function canComment() {
        global $global, $config;
        if ($config->getAuthCanComment()) {
            return self::isLogged();
        }
        return self::isAdmin();
    }
    
    function getUserGroups() {
        return $this->userGroups;
    }

    function setUserGroups($userGroups) {
        if(is_array($userGroups)){
            $this->userGroups = $userGroups;
        }
    }

    function getIsAdmin() {
        return $this->isAdmin;
    }

    function getStatus() {
        return $this->status;
    }

    /**
     * 
     * @param type $user_id
     * text
     * label Default Primary Success Info Warning Danger
     */
    static function getTags($user_id){
        $user = new User($user_id);
        $tags = array();
        if($user->getIsAdmin()){
            $obj = new stdClass();
            $obj->type = "info";
            $obj->text = __("Admin");
            $tags[] = $obj;
        }else{            
            $obj = new stdClass();
            $obj->type = "default";
            $obj->text = __("Regular User");
            $tags[] = $obj;
        }
        
        if($user->getStatus() == "a"){
            $obj = new stdClass();
            $obj->type = "success";
            $obj->text = __("Active");
            $tags[] = $obj;            
        }else{            
            $obj = new stdClass();
            $obj->type = "danger";
            $obj->text = __("Inactive");
            $tags[] = $obj;
        }
        
        require_once 'userGroups.php';
        $groups = UserGroups::getUserGroups($user_id);
        foreach ($groups as $value) {
            $obj = new stdClass();
            $obj->type = "warning";
            $obj->text = $value['group_name'];
            $tags[] = $obj;
        }
        
        return $tags;
        
    }
    
    function getBackgroundURL() {
        if(empty($this->backgroundURL)){
            $this->backgroundURL = "view/img/background.png";
        }
        return $this->backgroundURL;
    }

    function setBackgroundURL($backgroundURL) {
        $this->backgroundURL = strip_tags($backgroundURL);
    }



}
