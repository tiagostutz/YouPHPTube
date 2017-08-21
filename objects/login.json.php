<?php
header('Content-Type: application/json');
if (empty($global['systemRootPath'])) {
    $global['systemRootPath'] = "../";
}
require_once $global['systemRootPath'] . 'videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/hybridauth/autoload.php';
require_once $global['systemRootPath'] . 'objects/user.php';

use Hybridauth\Hybridauth;
use Hybridauth\HttpClient;

if (!empty($_GET['type'])) {
    switch ($_GET['type']) {
        case "Google":
            if(!$config->getAuthGoogle_enabled()){
                die(__("Google Login is not enabled"));
            }
            $id = $config->getAuthGoogle_id();
            $key = $config->getAuthGoogle_key();
            break;
        case "Facebook":
            if(!$config->getAuthFacebook_enabled()){
                die(__("Facebook Login is not enabled"));
            }
            $id = $config->getAuthFacebook_id();
            $key = $config->getAuthFacebook_key();
            break;

        default:
            die(__("Login error"));
            break;
    }
    if(empty($id)){
        die(sprintf(__("%s ERROR: You must set a ID on config"), $_GET['type']));
    }
    
    if(empty($key)){
        die(sprintf(__("%s ERROR: You must set a KEY on config"), $_GET['type']));
    }
    $config = [
        'callback' => HttpClient\Util::getCurrentUrl()."?type={$_GET['type']}",
        'providers' => [
            $_GET['type'] => [
                'enabled' => true,
                'keys' => ['id' => $id, 'secret' => $key],
            ]
        ],
            /* optional : set debug mode
              'debug_mode' => true,
              // Path to file writeable by the web server. Required if 'debug_mode' is not false
              'debug_file' => __FILE__ . '.log', */
    ];
    try {
        $hybridauth = new Hybridauth($config);

        $adapter = $hybridauth->authenticate($_GET['type']);

        $tokens = $adapter->getAccessToken();
        $userProfile = $adapter->getUserProfile();

        //print_r($tokens);
        //print_r($userProfile);
        
        $user = $userProfile->email;
        $name = $userProfile->displayName;
        $photoURL = $userProfile->photoURL;
        $email = $userProfile->email;        
        $pass = rand();
        User::createUserIfNotExists($user, $pass, $name, $email, $photoURL);
        $userObject = new User(0, $user, $pass);
        $userObject->login(true);
        $adapter->disconnect();
        header("Location: {$global['webSiteRootURL']}");
        
    } catch (\Exception $e) {
        header("Location: {$global['webSiteRootURL']}user?error=".urlencode($e->getMessage()));
        //echo $e->getMessage();
    }
    return;
}

$object = new stdClass();
if(empty($_POST['user']) || empty($_POST['pass'])){
    $object->error = __("User and Password can not be blank");
     die(json_encode($object));
}
$user = new User(0, $_POST['user'], $_POST['pass']);
$user->login(false, @$_POST['encodedPass']);
$object->isLogged = User::isLogged();
$object->isAdmin = User::isAdmin();
$object->canUpload = User::canUpload();
$object->canComment = User::canComment();
echo json_encode($object);
