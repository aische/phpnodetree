<?php
/*
    Functions for a tree datastructure based solely on PHP arrays
*/

function makeNode ($name, $value = NULL, $children = array()) {
    return array(
        'name'     => $name,
        'value'    => $value,
        'children' => $children,
    );    
}

function insertNode (&$node, $path, $value) {
    if (count ($path) == 0) {
        if ($node['value']) {
            echo "OVERWRITING: " . implode ("/", $path) . PHP_EOL;
        }
        $node['value'] = $value;
    } else {
        $name = array_shift ($path);
        if (!isset ($node['children'][$name])) {
            $node['children'][$name] = makeNode ($name);
        }
        insertNode ($node['children'][$name], $path, $value);
    }
}

function updateNode (&$node, $path, $func, $force_new = false) {
    if (count($path) == 0) {
        $node['value'] = $func($node['value']);
    } else {
        $name = array_shift($path);
        if (!isset($node['children'][$name])) {
            if ($force_new) {
                $node['children'][$name] = makeNode ($name);
            } else {
                return;
            }                
        }
        updateNode ($node['children'][$name], $path, $func, $force_new);
    }
}

function foldNode (&$node, $func, $depth = 0, $path = array()) {
    $children = array();
    foreach($node['children'] as $name => $ch) {
        $children[$name] = foldNode ($ch, $func, $depth+1, array_merge($path, array($name)));
    }
    return $func($depth, $path, $node['name'], $node['value'], $children);
}

function foldNodes (&$nodes, $func, $depth = 0, $path = array()) {
    $children = array();
    foreach($nodes as $name => $ch) {
        $children[$name] = foldNode ($ch, $func, $depth+1, array_merge($path, array($name)));
    }
    return $children;
}

function mapNode (&$node, $func) {
    return foldNode($node, function($depth, $path, $name, $value, $children) use ($func) {
        $value2 = $func($value, $depth, $path, $name);
        return makeNode($name, $value2, $children);
    });
}

function cloneNode (&$node) {
    return foldNode($node, function($depth, $path, $name, $value, $children) {
        return makeNode($name, $value, $children);
    });
}

function foldlazyNode (&$node, $func, $depth = 0, $path = array()) {
    $closure = function () use ($node, $func, $depth, $path) {
        $children = array();
        foreach($node['children'] as $name => &$ch) {
            $children[$name] = foldlazyNode($ch, $func, $depth+1, array_merge($path, array($name)));
        }
        return $children;
    };
    return $func($depth, $path, $node['name'], $node['value'], $node['children'], $closure);
}    

function &findNode (&$node, $path, &$breadcrumbs) {
    $breadcrumbs[] =& $node;
    if (count($path) == 0) {
        return $node;
    } else {
        $name = array_shift($path);
        if (!isset($node['children'][$name])) {
            return NULL;      
        }
        return findNode($node['children'][$name], $path, $breadcrumbs);
    }
}

/*
function backlinkNode (&$node, &$parent=NULL) {
    if ($parent) {
        $node['value']['parent'] =& $parent;
    }
    foreach($node['children'] as &$c) {
        backlinkNode($c, $node);
    }
}
*/
