<?php

require_once __DIR__ . "/../../CONFIG.php";
class Repository
{
    private static $repositories = [];
    private $map=[];
    private $columns = [];
    private $properties = [];
    private $PrimaryKey = [];
    private $AutoIncrement = [];
    private $mysqli;
    private $table;
    private $name;

    /**Devuelve un array de anotaciones del elemento
     * @param $element
     * @return array
     */
    protected  function getAnnotations($element){
        return explode("@",str_replace("/","",str_replace("*","" ,$element->getDocComment())));
    }

    /** Devuelve una instancia de repositorio para una entidad de determinado nombre
     * @param $entityName
     * @return Repository
     */
    public static function InstanceFor($entityName){
        return Repository::$repositories[$entityName];
    }
    /**
     * Inicia el repositorio para todas las entidades contenidas en la carpeta Entidades
     */
    public  static function Init(){
        $entityFiles = scandir("../Entities");
        foreach($entityFiles as $file){
            require_once $file;
            $entityName = str_replace(".php","",$file);
            Repository::$repositories[$entityName] = new Repository($entityName);
        }
    }
    /** Establece la configuración dependiendo del doccoment de la clase
     * @param $reflector ReflectionClass
     */
    private function configurarClase($reflector){
        $anotaciones = $this->getAnnotations($reflector);
        foreach ($anotaciones as $anotacion){
            $anotacion = explode("=",$anotacion);
            $clave = trim($anotacion[0]);
            $valor = (isset($anotacion[1])?trim($anotacion[1]):null);
            $nombre = $reflector->getName();
            switch ($clave){
                case "Tabla":
                    $nombre=$valor;
                    break;
            }
            $this->table = $nombre;
        }
        $propiedades = $reflector->getProperties();
        foreach ($propiedades as $propiedad){
            $this->configurarPropiedad($propiedad);
        }
    }
    protected function EntidadDeFila($fila){
        $entidad = new $this->name;
        foreach ($this->map as $columna => $propiedad){
            $this->properties[$propiedad]->setValue($entidad,$fila[$columna]);
        }
        return $entidad;
    }
    /** Establece la configuracion de la propiedad
     * @param $propiedad ReflectionProperty
     */
    private function configurarPropiedad($propiedad){
        $anotaciones = $this->getAnnotations($propiedad);
        if(in_array("Ignore",$anotaciones)){
            return;
        }
        $tieneNombre = false;
        $esClavePrimaria = false;
        $esAutoIncrement = false;

        $nombre = $propiedad->getName();
        foreach ($anotaciones as $anotacion){
            $anotacion = explode("=",$anotacion);
            $nombreAnotacion = trim($anotacion[0]);
            $valor = null;
            if(isset($anotacion[1])){
                $valor = trim($anotacion[1]);
            }
            switch($nombreAnotacion){
                case "Columna":
                    $nombre = $valor;
                    $tieneNombre = true;
                    $this->columns[$valor] = $propiedad->name;
                    $this->map[$propiedad->name] = $valor;
                    break;
                case "PrimaryKey":
                    $esClavePrimaria = true;
                    break;
                case "AutoIncrement":
                    $esAutoIncrement = true;
                    break;
            }
        }
        if($tieneNombre===false){
            $this->columns[$nombre] = $nombre;
            $this->map[$nombre] = $nombre;
        }
        $this->properties[$nombre] = $propiedad;
        if($esAutoIncrement){
            array_push($this->AutoIncrement,$nombre);
        }
        if($esClavePrimaria){
            array_push($this->PrimaryKey,$nombre);
        }
    }
    protected function __construct($nombreClase,$mysqli = null)
    {
        $this->name = $nombreClase;
        $refector = new ReflectionClass($nombreClase);
        $this->configurarClase($refector);
        if($mysqli!==null){
            $this->mysqli = $mysqli;
        }else{
            $this->mysqli = new mysqli(HOST,USER,PASS,DBNAME);
            if($this->mysqli->connect_errno){
                throw new Exception("Error de Conexión:(".
                    $this->mysqli->connect_errno . ":" .
                $this->mysqli->connect_error.")");
            }
        }

    }
    public function __destruct()
    {
        $this->mysqli->close();
    }
    public function get($opts=null){
        $entities = [];
        $sql = $this->CrearSelect() . $this->CrearWhere($opts);
        $result = $this->mysqli->query($sql);
        if($this->mysqli->errno){
            throw new Exception($this->mysqli->error);
        }
        $row = $result->fetch_assoc();
        while($row !== null){
            $entity = $this->EntidadDeFila($row);
            array_push($entities, $entity);
            $row = $result->fetch_assoc();
        }
        $result->close();
        return $entities;
    }
    public function add($entity){
        $sql = $this->CrearInsert($entity);
        $this->query($sql);
        if($this->mysqli->errno){
            throw new Exception("Error al insertar: (" . $this->mysqli->errno .": " . $this->mysqli->error . ")");
        }
        return $this->mysqli->affected_rows;
    }
    public function replace($entity){
        $sql = $this->CrearReplace($entity);
        $this->query($sql);
        if($this->mysqli->errno){
            throw new Exception("Error al insertar: (" . $this->mysqli->errno .": " . $this->mysqli->error . ")");
        }
        return $this->mysqli->affected_rows;
    }
    public function update($entity,$oldEntity){
        $opts = [];
        foreach ($this->PrimaryKey as $key){
            array_push($opts, ["EQ"=>
                ["col"=>$key,"val"=>($this->mysqli->real_escape_string($this->properties[$key]->getValue($oldEntity)))]
            ]);
        }
        $opts = ["AND"=>$opts];
        $sql = $this->CrearUpdate($entity) . $this->CrearWhere($opts);
        $result = $this->mysqli->query($sql);
        if($this->mysqli->errno){
            throw new Exception("Error al actualizar: " . $this->mysqli->error);
        }
        return $this->mysqli->affected_rows;
    }
    public function query($sql){
        return $this->mysqli->query($sql);
    }
    protected function CrearUpdate($entity){
        $upd = "UPDATE " . $this->table ." SET ";
        $tieneValores = false;
        foreach ($this->properties as $col => $propiedad){
            if(!in_array($col, $this->PrimaryKey)) {
                if ($tieneValores) {
                    $upd .= ", ";
                }
                $tieneValores = true;
                $upd .= $col . "='" . $this->mysqli->real_escape_string($propiedad->getValue($entity)) . "'";
            }
        }
        return $upd;
    }
    protected function CrearReplace($entity){
        $insert = "REPLACE INTO " . $this->table . " (";
        $insert2 = ") VALUES (";
        $cols = array_diff(array_values($this->columns),$this->AutoIncrement );
        $tieneValores = false;
        foreach ($cols as $col){
            if($tieneValores){
                $insert2 .= ", ";
                $insert .= ", ";
            }
            $tieneValores = true;
            $insert .= $col;
            $insert2 .= "'".$this->mysqli->real_escape_string($this->properties[$col]->getValue($entity))."'";
        }
        return $insert . $insert2 . ")";
    }
    protected function CrearSelect(array $projection=[]){
        $columns = "(";
        $tieneValores = false;
        foreach ($projection as $column){
            if($tieneValores){
                $column .= ", ";
            }
            $tieneValores = true;
            $columns .= $column;
        }
        $columns = ($columns==="("?"*":$columns.")");
        return "SELECT $columns FROM $this->table";
    }
    protected function CrearWhere($opts=null){
        return $opts===null?"":(" WHERE " . $this->AdministrarOpcion($opts));
    }
    
    protected function CrearInsert($entity){
        $insert = "INSERT INTO " . $this->table . " (";
        $insert2 = ") VALUES (";
        $cols = array_diff(array_values($this->columns),$this->AutoIncrement );
        $tieneValores = false;
        foreach ($cols as $col){
            if($tieneValores){
                $insert2 .= ", ";
                $insert .= ", ";
            }
            $tieneValores = true;
            $insert .= $col;
            $insert2 .= "'".$this->mysqli->real_escape_string($this->properties[$col]->getValue($entity))."'";
        }
        return $insert . $insert2 . ")";
    }
    private function AdministrarOpcion(array $opts){
        $operacion = function($op,$val){
            return " (".$val["col"] .$op."'".$this->mysqli->real_escape_string($val["val"])."') ";
        };
        $in = function($val){
          $retorno = $val["col"] . " IN (";
            $tieneValores = false;
            foreach ($val["val"] as $value){
                if($tieneValores){
                    $retorno .= " , ";
                }
                $tieneValores = true;
                $retorno .= "'".$this->mysqli->real_escape_string($value)."'";
            }
            $retorno .=") ";
            return $retorno;
        };
        $and = function($val,$op="AND"){
            $retorno = " (";
            $tieneValores = false;
            foreach ($val as $value){
                if($tieneValores) {
                    $retorno .= $op;
                }
                $tieneValores = true;
                $retorno .=  $this->AdministrarOpcion($value);
            }
            return $retorno . ") ";
        };
        $or = function($val) use($and){
            return $and($val, "OR");
        };
        foreach ($opts as $tipo => $valor){
            if(is_array($valor) && key_exists("SUBQUERY",$valor)){
                $repository = Repository::InstanceFor($valor["Entity"]);
                $valor = $repository->CrearSelect($valor["Projection"]) . $repository->CrearWhere($valor["Options"]);
            }
            switch ($tipo){
                case "EQ":
                    return $operacion("=",$valor);
                CASE "GT":
                    return $operacion(">",$valor);
                case "LT":
                    return $operacion("<",$valor);
                case "GTE":
                    return $operacion(">=",$valor);
                case "LTE":
                    return $operacion("<=",$valor);
                case "IN":
                    return $in($valor);
                case "AND":
                    return $and($valor);
                case "OR":
                    return $or($valor);
            }
        }
    }
}