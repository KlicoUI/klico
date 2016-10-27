<?php
require_once "ControllerBase.php";
class HomeController extends ControllerBase{
    public function __construct()
    {
        parent::__construct(["index"]);
    }
    public function index(){
        return $this->View("Index");
    }
}