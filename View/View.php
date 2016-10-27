<?php
class View
{
    protected $children;
    protected $model;
    public function __construct($model=null,$children=[])
    {
        $this->children = $children;
        $this->model = $model;
    }

    public function body($params=[]){
        foreach ($this->children as $child){
            $child->body($params);
        }
    }
    public function css($params=[]){
        foreach ($this->children as $child){
            $child->css($params);
        }
    }
    public function meta($params=[]){
        foreach ($this->children as $child){
            $child->meta();
        }
    }
    public function head_scripts($param=[]){
        foreach ($this->children as $child){
            $child->head_scripts();
        }
    }
    public function body_scripts($params=[]){
        foreach ($this->children as $child){
            $child->body_scripts($params);
        }
    }
}