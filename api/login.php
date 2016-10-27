<?php
header('Content-type: application/json');
if(!isset($_POST)){

    require_once __DIR__ . "/dal/Repository/Repository.php";
    Repository::Init();
    if(isset($_POST["login"]) && isset($_ROOT["pass"])){
        $login = filter_input(INPUT_POST,"login",FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $pass = filter_input(INPUT_POST,"pass",FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $userRepository = Repository::InstanceFor("User");
        $users = $userRepository->get(["EQ"=>["col"=>"login","val"=>$login]]);
        if(count($users)>0){
            $user = $users[0];
            if(password_verify($pass, $user->pass)){
                $sessionRepository = Repository::InstanceFor("Session");
                $session = new Session();
                $session->IdPHPSession = session_id();
                $session->starts = date()
                $sessionRepository->add($session);
            }
        }
    }else{
        http_response_code(404);
        die('{error:405,message:"Nombre de usuario y contraseña son obligatorios"}');
    }
}
http_response_code(405);

die('{error:405,message:"El servidor no acepta el método}');
