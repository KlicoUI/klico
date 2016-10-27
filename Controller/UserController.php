<?php

require_once "HomeController.php";

class UserController extends HomeController
{
    public function __construct()
    {
        parent::__construct(["login"]);
    }
    
}