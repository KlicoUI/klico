<?php
require_once "/../View.php";
class ErrorView500 extends View{
    public function body($params = [])
    { ?>
        <h2>
            Oops! Algo funcionó mal
        </h2>
        <h5>Si está viendo ésto es que algo funcionó mal en nuestros servidores</h5>
        <p><?php echo $this->model; ?></p>
    <?php }
}