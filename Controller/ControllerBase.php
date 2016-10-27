<?php

class ControllerBase
{
    private $actions;
    protected function Name(){
        return str_replace("Controller","",get_class($this));
    }
    public function __construct(array $actions = [])
    {
        $this->actions = $actions;
    }
    public function require_view($view){
        $controllerName = $this->Name();
        $paths = [$controllerName,"Shared"];
        foreach ($paths as $path){
            if(file_exists("View/$path/$view.php")){

                require_once "View/$path/$view.php";
                return true;
            }
        }
        return false;
    }
    public function Error404($msg){
        require_once "/../View/Error/ErrorView404.php";
        return new ErrorView404($msg);
    }
    public function Error500($msg){
        require_once "/../View/Error/ErrorView500.php";
        return new ErrorView500($msg);
    }
    public function View($name,$model=null,$children=[]){
        if($this->require_view($name)){
            return new $name($model,$children);
        }
        return $this->Error500("No se encontró la vista solicitada");
    }
    public function processRequest($params){
        $actionName = array_shift($params);
        if($actionName===""){
            $actionName = "index";
        }
        if(in_array($actionName, $this->actions)){
            return call_user_func_array([$this,$actionName], $params);
        }
        return $this->Error404("No se ha encontrado el recurso solicitado");
    }
}