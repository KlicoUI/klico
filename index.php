<?php
$getControllerPath = function($name){
    return "Controller" . DIRECTORY_SEPARATOR
    . $name . "Controller.php";
};
$Layout = "Layout";
$_GET = [];
$url = substr($_SERVER["REQUEST_URI"],1);
$url = explode("?",$url);
if(count($url)>1){
    $params = explode("&",$url[1]);
    foreach ($params as $param){
        $param = explode("=", $param);
        $value = null;
        if(count($param)>1){
            $value = urlencode($param[1]);
        }
        $_GET[urlencode($param)[0]] = $value;
    }
}
$url = explode("/",$url[0]);
if(count($url)==0){
    array_unshift($url,"Home");
}
$controllerName = array_shift($url);
$controllerPath = $getControllerPath($controllerName);
if(!file_exists($controllerPath)){
    array_unshift($url,$controllerName);
    $controllerName = "Home";
    $controllerPath = $getControllerPath($controllerName);
}
$controllerName .= "Controller";
require_once $controllerPath;
$controller = new $controllerName();
require_once "View/Layout/$Layout.php";
$layout = new $Layout(null,["content"=>$controller->processRequest($url)]);
$layout->body();
