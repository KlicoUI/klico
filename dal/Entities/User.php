<?php

/**
 * @Table=user
 */
class User
{
    /**
     * @PrimaryKey
     * @Column=idUser
     */
    public $IdUser;
    /**
     * @Column=login
     */
    public $Login;
    public $pass;
    
}