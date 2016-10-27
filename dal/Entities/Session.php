<?php

/**
 * @Table=session
 */
class Session
{
    /**
     * @PrimaryKey
     */
    public $IdSession;
    public $IdPHPSession;
    public $idUser;
    public $starts;
    public $ends;
}