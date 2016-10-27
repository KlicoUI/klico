<?php
require_once "/../View.php";
class ErrorView404 extends View
{
    public function __construct($model)
    {
        parent::__construct($model);
    }
    public function body($params = [])
    { 
        global $_VIEWDATA;
        $_VIEWDATA["title"] = "Error 404";
        ?>
        <div class="error-message">
            <h2>Error 404</h2>
            <p><?php echo $this->model; ?></p>
        </div>
    <?php }
}