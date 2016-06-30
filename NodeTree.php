<?php

class Node {
    public $name;
    public $value;
    public $children;

    public static $count_insertions = 0;
    public static $count_setvalues = 0;

    public function __construct($n, $v=NULL, $ch=array()){
        $this->name = $n;
        $this->value = $v;
        $this->children = $ch;
    }

    public function insert ($path, $value) {
        if (count($path) == 0) {
            if ($this->value) {
                echo "OVERWRITING: OLD=";
                print_r($this->value['uid']);
                echo ", NEW=" . $value['uid'];
                echo PHP_EOL;
            }
            $this->value = $value;
            self::$count_setvalues++;
        } else {
            $name = array_shift($path);
            if (!isset($this->children[$name])) {
                $this->children[$name] = new Node($name);
                self::$count_insertions++;
            }
            $this->children[$name]->insert($path, $value);
        }
    }

    public function update ($path, $func, $force_new = false) {
        if (count($path) == 0) {
            $this->value = $func($this->value);
        } else {
            $name = array_shift($path);
            if (!isset($this->children[$name])) {
                if ($force_new) {
                    $this->children[$name] = new Node($name);                
                } else {
                    return;
                }                
            }
            $this->children[$name]->update($path, $func, $force_new);
        }
    }

    public function backlink (&$parent=NULL) {
        if ($parent) {
            $this->value['parent'] = $parent;
        }
        foreach($this->children as $c) {
            $c->backlink($this);
        }
    }

    public function find ($path) {
        if (count($path) == 0) {
            return $this;
        } else {
            $name = array_shift($path);
            if (!isset($this->children[$name])) {
                return NULL;      
            }
            return $this->children[$name]->find($path);
        }
    }

    // usage: root->fold(f), where f is function with following arguments:
    // f(depth, path, name, value, children)
    public function fold ($func, $depth = 0, $path = array()) {
        $children = array();
        foreach($this->children as $name => $ch) {
            $children[$name] = $ch->fold ($func, $depth+1, array_merge($path, array($name)));
        }
        return $func($depth, $path, $this->name, $this->value, $children);
    }

    // f(depth, path, name, value, children, closure)
    public function foldlazy ($func, $depth = 0, $path = array()) {
        $closure = function () use ($func, $depth, $path) {
            $children = array();
            foreach($this->children as $name => $ch) {
                $children[$name] = $ch->foldlazy ($func, $depth+1, array_merge($path, array($name)));
            }
            return $children;
        };
        return $func($depth, $path, $this->name, $this->value, $this->children, $closure);
    }    

    // f($value, $depth, $path, $name) -> returns new $value
    public function map ($func) {
        return $this->fold(function($depth, $path, $name, $value, $children) use ($func) {
            $value2 = $func($value, $depth, $path, $name);
            return new Node($name, $value2, $children);
        });
    }

    public function clone() {
        return $this->fold(function($depth, $path, $name, $value, $children){
            return new Node($name, $value, $children);
        });
    }
}

