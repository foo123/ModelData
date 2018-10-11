<?php
/**
*   ModelData
*   uses similar API to ModelView.js to typecast and validate model data
*
*   @version 0.1.1
*   https://github.com/foo123/ModelData
*
**/
if ( !class_exists("ModelData") )
{
class ModelField
{
    public $f = null;

    public function __construct( $f )
    {
        $this->f = $f;
    }
    public function __destruct( )
    {
        $this->dispose( );
    }
    public function dispose( )
    {
        $this->f = null;
        return $this;
    }
}

class ModelTypeCaster
{
    public static $custom = array( );
    public $typecaster = null;
    public $v = null;
    
    public static function add( $type, $caster )
    {
        if ( $type && $caster && is_callable($caster) )
            self::$custom[ strtolower($type) ] = $caster;
    }
    
    public static function del( $type )
    {
        if ( $type && isset(self::$custom[ strtolower($type) ]) )
            unset(self::$custom[ strtolower($type) ]);
    }
    
    public static function has( $type )
    {
        if ( $type )
        {
            $type = strtolower($type);
            return isset(self::$custom[ $type ]) || method_exists('ModelTypeCaster', "t_{$type}");
        }
        return false;
    }
    
    public function __construct( $typecaster=null, $args=null )
    {
        $this->typecaster = is_string($typecaster) ? strtolower($typecaster) : null;
        
        switch( $this->typecaster )
        {
            case 'composite':
                if ( $args && is_array($args[0]) && !is_callable($args[0]) ) $args = $args[ 0 ];
                break;
            case 'clamp':
                if ( $args  ) 
                {
                    $m = $args[0]; $M = $args[1];
                    // swap
                    if ( $m > $M ) { $args[0] = $M; $args[1] = $m; }
                }
                break;
            case 'pad':
                $args = (array)$args;
                if ( $args ) 
                {
                    if ( !isset($args[2]) || !$args[2] ) $args[2] = "L";
                }
                break;
            case 'datetime':
                $args = (array)$args;
                if ( !$args  ) $args = array("Y-m-d", null);
                if ( !isset($args[0]) || !$args[0] ) $args[0] = "Y-m-d";
                if ( !isset($args[1]) || !$args[1] ) $args[1] = null;
                break;
        }
        
        $this->v = $args;
    }
    
    public function __destruct( )
    {
        $this->typecaster = null;
        $this->v = null;
    }
    
    public function typecast( $v, $k, $m )
    {
        $typecaster = $this->typecaster;
        if ( $typecaster ) 
        {
            if ( isset(self::$custom[$typecaster]) )
            {
                $typecaster = self::$custom[$typecaster];
                return call_user_func( $typecaster, $v, $k, $m );
            }
            elseif ( is_callable(array($this, "t_{$typecaster}")) )
            {
                $typecaster = "t_{$typecaster}";
                return $this->{$typecaster}( $v, $k, $m );
            }
        }
        return $v;
    }
    
    public function t_composite( $v, $k, $m )
    {
        $typecasters = $this->v;
        $l = count($typecasters);
        while ( $l-- ) $v = call_user_func($typecasters[$l], $v, $k, $m);
        return $v;
    }
    
    public function t_fields( $v, $k, $m )
    {
        $typesPerField = $this->v;
        foreach ($typesPerField as $field=>$type)
        {
            $v[ $field ] = call_user_func($type, $v[ $field ], $k, $m );
        }
        return $v;
    }
    
    public function t_default( $v, $k, $m )
    {
        $defaultValue = $this->v;
        if ( !$v || (is_string($v) && !strlen(trim($v)))  ) $v = $defaultValue;
        return $v;
    }
    
    public function t_bool( $v, $k, $m )
    {
        // handle string representation of booleans as well
        if ( is_string($v) && strlen($v) )
        {
            $vs = strtolower($v);
            return "true" === $vs || "on" === $vs || "1" === $vs;
        }
        return (bool)($v); 
    }
    
    public function t_int( $v, $k, $m ) 
    { 
        // convert NaN to 0 if needed
        $v = intval($v, 10);
        return !$v ? 0 : $v;
    }
    
    public function t_float( $v, $k, $m ) 
    { 
        // convert NaN to 0 if needed
        $v = floatval($v, 10);
        return !$v ? 0.0 : $v;
    }
    
    public function t_trim( $v, $k, $m ) 
    { 
        return trim(strval($v));
    }
    
    public function t_lcase( $v, $k, $m ) 
    { 
        return strtolower(strval($v));
    }
    
    public function t_ucase( $v, $k, $m ) 
    { 
        return strtoupper(strval($v));
    }
            
    public function t_str( $v, $k, $m ) 
    { 
        return strval($v);
    }
    
    public function t_min( $v, $k, $m ) 
    {  
        $min = $this->v;
        return ($v < $min) ? $min : $v; 
    }
    
    public function t_max( $v, $k, $m ) 
    {  
        $max = $this->v;
        return ($v > $max) ? $max : $v; 
    }
    
    public function t_clamp( $v, $k, $m ) 
    {  
        $min = $this->v[0]; $max = $this->v[1];
        return ($v < $min) ? $min : (($v > $max) ? $max : $v); 
    }
    
    public function t_pad( $v, $k, $m ) 
    { 
        $pad_char = $this->v[0]; 
        $pad_size = $this->v[1]; 
        $pad_type = $this->v[2];
        $vs = strval($v); 
        $len = strlen($vs); 
        $n = $pad_size-$len;
        if ( $n > 0 )
        {
            if ( 'LR' === $pad_type )
            {
                $r = ~~($n/2); $l = $n-$r;
                $vs = str_repeat($pad_char, $l) . $vs . str_repeat($pad_char, $r);
            }
            elseif ( 'R' === $pad_type )
            {
                $vs .= str_repeat($pad_char, $n);
            }
            else/*if ( 'L' === $pad_type )*/
            {
                $vs = str_repeat($pad_char, $n) . $vs;
            }
        }
        return $vs;
    }
    
    public function t_datetime( $v, $k, $m ) 
    {
        $format = $this->v[0]; $locale = $this->v[1];
        // TODO: localisation
        return date( $format, $v ); 
    }
}

class ModelValidator
{
    public static $custom = array( );
    public $validator = null;
    public $v = null;
    
    public static function add( $type, $validator )
    {
        if ( $type && $validator && is_callable($validator) )
            self::$custom[ strtolower($type) ] = $validator;
    }
    
    public static function del( $type )
    {
        if ( $type && isset(self::$custom[ strtolower($type) ]) )
            unset(self::$custom[ strtolower($type) ]);
    }
    
    public static function has( $type )
    {
        if ( $type )
        {
            $type = strtolower($type);
            return isset(self::$custom[ $type ]) || method_exists('ModelValidator', "v_{$type}");
        }
        return false;
    }
    
    public function __construct( $validator=null, $args=null )
    {
        $this->validator = is_string($validator) ? strtolower($validator) : null;
        
        switch( $this->validator )
        {
            case 'min_filesize':
            case 'max_filesize':
            case 'minlen':
            case 'maxlen':
                $args = $args ? $args : 0;
                break;
            case 'min_items':
            case 'max_items':
                $args = (array)$args;
                $args[0] = intval($args[0], 10);
                if ( !isset($args[1]) || !$args[1] || !is_callable($args[1]) ) $args[1] = null;
                break;
            case 'equal':
            case 'not_equal':
            case 'greater_than':
            case 'less_than':
                $args = (array)$args;
                if ( !isset($args[1]) ) $args[1] = true; // strict
                break;
            case 'between':
            case 'not_between':
                $args = (array)$args;
                if ( !isset($args[2]) ) $args[2] = true; // strict
                $m = $args[0]; $M = $args[1];
                // swap
                if ( !($m instanceof ModelField) && !($M instanceof ModelField) && ($m > $M) ) { $args[0] = $M; $args[1] = $m; }
                break;
            case 'in':
            case 'not_in':
                $args = (array)$args;
                break;
            case 'datetime':
                $args = (array)$args;
                if ( !$args  ) $args = array("Y-m-d", null);
                if ( !isset($args[0]) || !$args[0] ) $args[0] = "Y-m-d";
                if ( !isset($args[1]) || !$args[1] ) $args[1] = null;
                $args = ModelData::get_date_pattern( $args[0], $args[1] );
                break;
        }
        
        $this->v = $args;
    }
    
    public function __destruct( )
    {
        $this->validator = null;
        $this->v = null;
    }
    
    public function AND_( $validator )
    {
        return new ModelValidator('and', array($this, $validator));
    }
    
    public function OR_( $validator )
    {
        return new ModelValidator('or', array($this, $validator));
    }
    
    public function XOR_( $validator )
    {
        return new ModelValidator('xor', array($this, $validator));
    }
    
    public function NOT_( )
    {
        return new ModelValidator('not', $this);
    }
    
    public function validate( $v, $k, $m )
    {
        $validator = $this->validator;
        if ( $validator ) 
        {
            if ( in_array($validator, array('and','or','xor','not')) )
            {
                $validator = "v_{$validator}";
                return $this->{$validator}( $v, $k, $m );
            }
            elseif ( isset(self::$custom[$validator]) )
            {
                $validator = self::$custom[$validator];
                return call_user_func( $validator, $v, $k, $m );
            }
            elseif ( is_callable(array($this, "v_{$validator}")) )
            {
                $validator = "v_{$validator}";
                return $this->{$validator}( $v, $k, $m );
            }
        }
        return true;
    }
    
    public function v_and( $v, $k, $m )
    {
        $validator = $this->v;
        return $validator[0]->validate($v, $k, $m) && $validator[1]->validate($v, $k, $m);
    }
    
    public function v_or( $v, $k, $m )
    {
        $validator = $this->v;
        return $validator[0]->validate($v, $k, $m) || $validator[1]->validate($v, $k, $m);
    }
    
    public function v_xor( $v, $k, $m )
    {
        $validator = $this->v;
        $v0 = $validator[0]->validate($v, $k, $m);
        $v1 = $validator[1]->validate($v, $k, $m);
        return ($v0 && !$v1) || ($v1 && !$v0);
    }
    
    public function v_not( $v, $k, $m )
    {
        $validator = $this->v;
        return !$validator->validate($v, $k, $m);
    }
    
    public function v_fields( $v, $k, $m )
    {
        $validatorsPerField = $this->v;
        foreach ($validatorsPerField as $field=>$validator)
        {
            if ( !call_user_func($validator, $v[ $field ], $k, $m ) )
                return false;
        }
        return true;
    }
    
    public function v_numeric( $v, $k, $m )
    {
        return is_numeric( $v );
    }
    
    public function v_empty( $v, $k, $m )
    {
        return (bool)(!$v || !strlen(trim(strval($v))));
    }
    
    public function v_not_empty( $v, $k, $m )
    {
        return (bool)($v && strlen(trim(strval($v)))>0);
    }
    
    public function v_maxlen( $v, $k, $m )
    {
        $len = $this->v;
        return (bool)(strlen($v)<=$len);
    }
    
    public function v_minlen( $v, $k, $m )
    {
        $len = $this->v;
        return (bool)(strlen($v)>=$len);
    }
    
    public function v_match( $v, $k, $m )
    {
        $regex_pattern = $this->v;
        $matches = array();
        return (bool)preg_match($regex_pattern, $v, $matches);
    }
    
    public function v_not_match( $v, $k, $m )
    {
        $regex_pattern = $this->v;
        $matches = array();
        return (bool)(!preg_match($regex_pattern, $v, $matches));
    }
    
    public function v_equal( $v, $k, $m )
    {
        $val = $this->v[0]; $strict = $this->v[1];
        if ( $val instanceof ModelField ) 
        {
            $val = $m->get( $val->f );
            $val = empty($val) ? null : $val[0];
        }
        return $strict ? ($val === $v) : ($val == $v);
    }
    
    public function v_not_equal( $v, $k, $m )
    {
        $val = $this->v[0]; $strict = $this->v[1];
        if ( $val instanceof ModelField ) 
        {
            $val = $m->get( $val->f );
            $val = empty($val) ? null : $val[0];
        }
        return $strict ? ($val !== $v) : ($val != $v);
    }
    
    public function v_greater_than( $v, $k, $m )
    {
        $min = $this->v[0]; $strict = $this->v[1];
        if ( $min instanceof ModelField ) 
        {
            $min = $m->get( $min->f );
            $min = empty($min) ? null : $min[0];
        }
        return $strict ? ($min < $v) : ($min <= $v);
    }
    
    public function v_less_than( $v, $k, $m )
    {
        $max = $this->v[0]; $strict = $this->v[1];
        if ( $max instanceof ModelField ) 
        {
            $max = $m->get( $max->f );
            $max = empty($max) ? null : $max[0];
        }
        return $strict ? ($max > $v) : ($max >= $v);
    }
    
    public function v_between( $v, $k, $m )
    {
        $min =$this->v[0]; $max = $this->v[1]; $strict = $this->v[2];
        if ( $min instanceof ModelField ) 
        {
            $min = $m->get( $min->f );
            $min = empty($min) ? null : $min[0];
        }
        if ( $max instanceof ModelField ) 
        {
            $max = $m->get( $max->f );
            $max = empty($max) ? null : $max[0];
        }
        return $strict ? (($min < $v) && ($v < $max)) : (($min <= $v) && ($v <= $max));
    }
    
    public function v_not_between( $v, $k, $m )
    {
        $min =$this->v[0]; $max = $this->v[1]; $strict = $this->v[2];
        if ( $min instanceof ModelField ) 
        {
            $min = $m->get( $min->f );
            $min = empty($min) ? null : $min[0];
        }
        if ( $max instanceof ModelField ) 
        {
            $max = $m->get( $max->f );
            $max = empty($max) ? null : $max[0];
        }
        return $strict ? (($min > $v) || ($v > $max)) : (($min >= $v) || ($v >= $max));
    }
    
    public function v_in( $v, $k, $m )
    {
        $vals = $this->v;
        return in_array($v, $vals);
    }
    
    public function v_not_in( $v, $k, $m )
    {
        $vals = $this->v;
        return !in_array($v, $vals);
    }
    
    public function v_min_items( $v, $k, $m )
    {
        $limit = $this->v[0]; $item_filter = $this->v[1];
        return $item_filter ? ($limit <= count(array_filter($v, $item_filter))) : ($limit <= count($v));
    }
    
    public function v_max_items( $v, $k, $m )
    {
        $limit = $this->v[0]; $item_filter = $this->v[1];
        return $item_filter ? ($limit >= count(array_filter($v, $item_filter))) : ($limit >= count($v));
    }
    
    public function v_email( $v, $k, $m )
    {
        $email_pattern = '/^(([^<>()[\\]\\\\.,;:\\s@\\"]+(\\.[^<>()[\\]\\\\.,;:\\s@\\"]+)*)|(\\".+\\"))@((\\[[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\])|(([a-zA-Z\\-0-9]+\\.)+[a-zA-Z]{2,}))$/';
        $matches = array( );
        return (bool)preg_match($email_pattern, $v, $matches);
    }
    
    public function v_url( $v, $k, $m )
    {
        $url_pattern = '/^(?!mailto:)(?:(?:http|https|ftp)://)(?:\\S+(?::\\S*)?@)?(?:(?:(?:[1-9]\\d?|1\\d\\d|2[01]\\d|22[0-3])(?:\\.(?:1?\\d{1,2}|2[0-4]\\d|25[0-5])){2}(?:\\.(?:[0-9]\\d?|1\\d\\d|2[0-4]\\d|25[0-4]))|(?:(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)(?:\\.(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)*(?:\\.(?:[a-z\\u00a1-\\uffff]{2,})))|localhost)(?::\\d{2,5})?(?:(/|\\?|#)[^\\s]*)?$/i';
        $matches = array( );
        return (bool)preg_match($url_pattern, $v, $matches);
    }
    
    public function v_datetime( $v, $k, $m )
    {
        $date_pattern = $this->v;
        $matches = array( );
        return (bool)preg_match($date_pattern, $v, $matches);
    }
    
    public function v_min_filesize( $v, $k, $m )
    {
        $size = (int)$this->v;
        return (bool)((int)$v['size'] >= $size);
    }
    
    public function v_max_filesize( $v, $k, $m )
    {
        $size = (int)$this->v;
        return (bool)((int)$v['size'] <= $size);
    }
}

class ModelData
{
    const VERSION = "0.1.1";
    const TYPECASTER = 1;
    const VALIDATOR = 2;
    const WILDCARD = "*";
    
    const T_BOOL = 4;
    const T_NUM = 8;
    const T_STR = 16;
    const T_CHAR = 17;
    const T_ARRAY = 32;
    const T_OBJ = 64;
    const T_ASSOC = 65;
    const T_FUNC = 128;
    const T_NULL = 256;
    const T_UNKNOWN = 512;
    
    public static $default_date_locale = array(
    'meridian'=> array( 'am'=>'am', 'pm'=>'pm', 'AM'=>'AM', 'PM'=>'PM' ),
    'ordinal'=> array( 'ord'=>array(1=>'st',2=>'nd',3=>'rd'), 'nth'=>'th' ),
    'timezone'=> array( 'UTC','EST','MDT' ),
    'timezone_short'=> array( 'UTC','EST','MDT' ),
    'day'=> array( 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday' ),
    'day_short'=> array( 'Sun','Mon','Tue','Wed','Thu','Fri','Sat' ),
    'month'=> array( 'January','February','March','April','May','June','July','August','September','October','November','December' ),
    'month_short'=> array( 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec' )
    );
    
    public $model_data = null;
    public $model_types = null;
    public $model_validators = null;
    
    // Array multi - sorter utility
    // returns a sorter that can (sub-)sort by multiple (nested) fields 
    // each ascending or descending independantly
    public static function Sorter( /* var args here */ ) 
    {
        $args = func_get_args( ); $l = count($args);
        $ASC = '|^'; $DESC = '|v';
        // |^ after a (nested) field indicates ascending sorting (default), 
        // example "a.b.c|^"
        // |v after a (nested) field indicates descending sorting, 
        // example "b.c.d|v"
        if ( $l )
        {
            $step = 1;
            $sorter = array();
            $variables = array();
            $sorter_args = array();
            $filter_args = array(); 
            for ($i=$l-1; $i>=0; $i--)
            {
                $field = $args[$i];
                // if is array, it contains a filter function as well
                array_unshift($filter_args, '$f'.$i);
                if ( is_array($field) )
                {
                    array_unshift($sorter_args, $field[1]);
                    $field = $field[0];
                }
                else
                {
                    array_unshift($sorter_args, null);
                }
                $dir = substr($field,-2);
                if ( $DESC === $dir ) 
                {
                    $desc = true;
                    $field = substr($field,0,-2);
                }
                elseif ( $ASC === $dir )
                {
                    $desc = false;
                    $field = substr($field,0,-2);
                }
                else
                {
                    // default ASC
                    $desc = false;
                }
                $field = strlen($field) ? '["' . implode('"]["', explode('.', $field)) . '"]' : '';
                $a = '$a'.$field; $b = '$b'.$field;
                if ( $sorter_args[0] ) 
                {
                    $a = 'call_user_func(' . $filter_args[0] . ',' . $a . ')';
                    $b = 'call_user_func(' . $filter_args[0] . ',' . $b . ')';
                }
                $avar = '$a_'.$i; $bvar = '$b_'.$i;
                array_unshift($variables, ''.$avar.'='.$a.';'.$bvar.'='.$b.';');
                $lt = $desc ?(''.$step):('-'.$step); $gt = $desc ?('-'.$step):(''.$step);
                array_unshift($sorter, "(".$avar." < ".$bvar." ? ".$lt." : (".$avar." > ".$bvar." ? ".$gt." : 0))");
                $step <<= 1;
            }
            // use actual php anonynous function/closure
            // needs PHP 5.3+
            // create_function is deprecated as of PHP 7.2+
            $sorter_factory = @create_function(implode(',',$filter_args), implode("\n", array(
                '$sorter = function($a,$b) use('.implode(',',$filter_args).') {',
                '    '.implode("\n", $variables).'',
                '    return '.implode('+', $sorter).';',
                '};',
                'return $sorter;'
            )));
            return call_user_func_array($sorter_factory, $sorter_args);
        }
        else
        {
            $a = '$a'; $b = '$b'; $lt = '-1'; $gt = '1';
            $sorter = "".$a." < ".$b." ? ".$lt." : (".$a." > ".$b." ? ".$gt." : 0)";
            // create_function is deprecated as of PHP 7.2+
            return @create_function('$a,$b', 'return '.$sorter.';');
        }
    }

    public static function get_type( $o )
    {
        if ( null === $o || !isset($o) ) return self::T_NULL;
        if ( true === $o || false === $o ) return self::T_BOOL;
        if ( is_string( $o ) ) return 1===strlen($o) ? self::T_CHAR : self::T_STR;
        if ( is_numeric( $o ) ) return self::T_NUM;
        if ( is_array( $o ) )
        {
            $c = count( $o );
            if ( ($c <= 0) || (array_keys( $o ) === range(0, $c - 1)) )  return self::T_ARRAY;
            return self::T_ASSOC;
        }
        if ( is_callable( $o ) ) return self::T_FUNC;
        if ( $o instanceof \stdClass ) return self::T_OBJ;
        return self::T_UNKNOWN;
    }
    
    public static function is_type( $o, $type )
    {
        return (bool)(self::get_type( $o ) & $type );
    }
    
    public static function Field( $f )
    {
        return new ModelField( $f );
    }
    
    public static function TYPE( $type )
    {
        if ( $type )
        {
            $args = func_get_args( );
            array_shift( $args );
            $argslen = count( $args );
            if ( 1 === $argslen ) $args = $args[0];
            elseif ( !$argslen ) $args = null;
            return new ModelTypeCaster( $type, $args );
        }
        return null;
    }
    
    public static function VALIDATOR( $validator )
    {
        if ( $validator )
        {
            $args = func_get_args( );
            array_shift( $args );
            $argslen = count( $args );
            if ( 1 === $argslen ) $args = $args[0];
            elseif ( !$argslen ) $args = null;
            return new ModelValidator( $validator, $args );
        }
        return null;
    }
    
    public function __construct( $data=array( ) )
    {
        $this->model_types = array( );
        $this->model_validators = array( );
        $this->data( $data );
    }
    
    public function __destruct( )
    {
        $this->dispose( );
    }
    
    public function dispose( )
    {
        $this->model_data = null;
        $this->model_types = null;
        $this->model_validators = null;
        return $this;
    }
    
    public function reset( )
    {
        $this->model_types = array( );
        $this->model_validators = array( );
        $this->model_data = array( );
        return $this;
    }
    
    public function files( $files )
    {
        $o_files = array( );
        foreach ($files as $k1=>$v1)
        {
            foreach ($v1 as $k2=>$v2)
            {
                if ( !isset($o_files[$k2]) ) $o_files[$k2] = array( );
                $o_files[$k2][$k1] = $v2;
            }
        }
        return $o_files;
    }
    
    public function pluck( $collection, $keys )
    {
        $plucked = array( );
        $keys = is_string($keys) ? explode( ',', $keys ) : (array)$keys;
        $keys = array_filter( array_map( 'trim', $keys ), 'strlen' );
        
        if ( count($keys) > 1 )
        {
            foreach((array)$collection as $index=>$entry)
            {
                $selected = array( );
                foreach($keys as $key)
                {
                    if ( isset( $entry[$key] ) ) 
                        $selected[ $key ] = $entry[ $key ];
                }
                if ( $entry instanceof \stdClass ) $selected = (object)$selected;
                $plucked[ $index ] = $selected;
            }
        }
        else
        {
            $key = $keys[ 0 ];
            foreach((array)$collection as $index=>$entry)
            {
                $plucked[ $index ] = isset( $entry[$key] ) ? $entry[ $key ] : null;
            }
        }
        return $plucked;
    }
    
    public function group( $collection, $key )
    {
        $grouped = array( );
        foreach((array)$collection as $index=>$entry)
        {
            if ( isset( $entry[$key] ) )
            {
                $id = $entry[ $key ];
                if ( !isset($grouped[ $id ]) ) $grouped[ $id ] = array( $entry );
                else $grouped[ $id ][ ] = $entry;
            }
        }
        return $grouped;
    }
    
    public function join( $collection1, $collection2, $key1, $key2 )
    {
        foreach($collection1 as &$item)
        {
            $item[ $key1 ] = isset($collection2[ $item[ $key2 ] ]) 
                            ? (array)$collection2[ $item[ $key2 ] ] 
                            : array( );
        }
        return $collection1;
    }
    
    public function flatten( $collection )
    {
        $flattened = array( );
        foreach((array)$collection as $entry)
        {
            if ( is_array($entry) ) $flattened = array_merge( $flattened, $this->flatten( $entry ) );
            else $flattened[] = $entry;
        }
        return $flattened;
    }
    
    public function filter( $data, $filter, $positive=true )
    {
        if ( isset($data[0]) && is_array($data[0]) )
        {
            // array of data
            $collection = array( ); $filter = (array)$filter;
            if ( $positive )
            {
                foreach($data as $datum)
                {
                    $filtered = array( );
                    foreach($filter as $key)
                    {
                        if ( isset( $datum[$key] ) ) $filtered[ $key ] = $datum[ $key ];
                    }
                    $collection[] = $filtered;
                }
            }
            else
            {
                foreach($data as $datum)
                {
                    $filtered = array( );
                    foreach($datum as $key=>$v)
                    {
                        if ( !in_array( $key, $filter ) ) $filtered[ $key ] = $v;
                    }
                    $collection[] = $filtered;
                }
            }
            return $collection;
        }
        else
        {
            // single data
            $filtered = array( ); $filter = (array)$filter;
            if ( $positive )
            {
                foreach($filter as $key)
                {
                    if ( isset( $data[$key] ) ) $filtered[ $key ] = $data[ $key ];
                }
            }
            else
            {
                foreach($data as $key=>$v)
                {
                    if ( !in_array( $key, $filter ) ) $filtered[ $key ] = $v;
                }
            }
            return $filtered;
        }
    }
    
    public function remap( $data, $map )
    {
        if ( empty($data) || empty($map) ) return $data;
        
        if ( isset($data[0]) && is_array($data[0]) )
        {
            // array of data
            $collection = array( );
            foreach($data as $datum)
            {
                $mapped = array( );
                foreach ($datum as $k=>$v)
                {
                    if ( isset($map[$k]) ) 
                    {
                        if ( is_array( $map[$k] ) )
                            $mapped[ $k ] = is_array( $v ) ? $this->remap( $v, $map[$k] ) : $v;
                        else
                            $mapped[ $map[$k] ] = $v;
                    }
                    else
                    {                
                        $mapped[ $k ] = $v;
                    }
                }
                $collection[] = $mapped;
            }
            return $collection;
        }
        else
        {
            // single data
            $mapped = array( );
            foreach ($data as $k=>$v)
            {
                if ( isset($map[$k]) ) 
                {
                    if ( is_array( $map[$k] ) )
                        $mapped[ $k ] = is_array( $v ) ? $this->remap( $v, $map[$k] ) : $v;
                    else
                        $mapped[ $map[$k] ] = $v;
                }
                else
                {                
                    $mapped[ $k ] = $v;
                }
            }
            return $mapped;
        }
    }
    
    public function data( $data=array( ) )
    {
        if ( 0 < func_num_args( ) )
        {
            $this->model_data = $data;
            return $this;
        }
        return $this->model_data;
    }
    
    public function defaults( $defaults, $overwrite=false )
    {
        if ( !empty( $defaults) )
        {
            $overwrite = false !== $overwrite;
            $data =& $this->model_data;
            $is_object = is_object( $data ); $is_array = is_array( $data );
            if ( $is_object || $is_array )
            {
                foreach((array)$defaults as $k=>$v)
                {
                    if ( $is_object ) 
                    {
                        if ( $overwrite || !isset( $data->{$k} ) )
                        {
                            $data->{$k} = $v;
                        }
                        elseif ( (is_array( $data->{$k} ) || is_object( $data->{$k} )) && 
                        (is_array( $v ) || is_object( $v )) )
                        {
                            $data->{$k} = self::do_defaults( $this, $data->{$k}, $v, $overwrite );
                        }
                    }
                    else/*if ( $is_array )*/
                    {
                        if ( $overwrite || !isset($data[$k]) )
                        {
                            $data[$k] = $v;
                        }
                        elseif ( (is_array( $data[$k] ) || is_object( $data[$k] )) && 
                        (is_array( $v ) || is_object( $v )) )
                        {
                            $data[$k] = self::do_defaults( $this, $data[$k], $v, $overwrite );
                        }
                    }
                }
            }
        }
        return $this;
    }
    
    public function get( $fields )
    {
        $results = array( );
        if ( !$fields || empty( $fields ) ) return $results;
        $data =& $this->model_data;
        $is_object = is_object( $data ); $is_array = is_array( $data );
        if ( $is_object || $is_array )
        {
            $WILDCARD = self::WILDCARD;
            foreach((array)$fields as $dottedKey)
            {
                $stack = array( array(&$data, $dottedKey) );
                while ( !empty($stack) )
                {
                    $to_get = array_pop( $stack );
                    $o =& $to_get[0];
                    $key = $to_get[1];
                    $p = explode('.', $key);
                    $i = 0; $l = count($p);
                    while ($i < $l)
                    {
                        $k = $p[$i++];
                        if ( $i < $l )
                        {
                            if ( is_object( $o ) ) 
                            {
                                if ( $WILDCARD === $k ) 
                                {
                                    $k = implode('.', array_slice($p, $i));
                                    foreach(array_keys((array)$o) as $key)
                                        $stack[] = array(&$o, "{$key}.{$k}");
                                    break;
                                }
                                elseif ( isset($o->{$k}) ) 
                                {
                                    $o =& $o->{$k};
                                }
                            }
                            elseif ( is_array( $o ) ) 
                            {
                                if ( $WILDCARD === $k ) 
                                {
                                    $k = implode('.', array_slice($p, $i));
                                    foreach(array_keys($o) as $key)
                                        $stack[] = array(&$o, "{$key}.{$k}");
                                    break;
                                }
                                elseif ( isset($o[$k]) ) 
                                {
                                    $o =& $o[$k];
                                }
                            }
                            else break; // key does not exist
                        }
                        else
                        {
                            if ( is_object($o) ) 
                            {
                                if ( $WILDCARD === $k )
                                {
                                    foreach(array_keys((array)$o) as $k) $results[] = $o->{$k};
                                }
                                elseif ( isset($o->{$k}) )
                                {
                                    $results[] = $o->{$k};
                                }
                            }
                            elseif ( is_array($o) ) 
                            {
                                if ( $WILDCARD === $k )
                                {
                                    foreach(array_keys($o) as $k) $results[] = $o[$k];
                                }
                                elseif ( isset($o[$k]) )
                                {
                                    $results[] = $o[$k];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $results;
    }
    
    public function rem( $fields )
    {
        if ( !$fields || empty( $fields ) ) return $this;
        $data =& $this->model_data;
        $is_object = is_object( $data ); $is_array = is_array( $data );
        if ( $is_object || $is_array )
        {
            $WILDCARD = self::WILDCARD;
            foreach((array)$fields as $dottedKey)
            {
                $stack = array( array(&$data, $dottedKey) );
                while ( !empty($stack) )
                {
                    $to_remove = array_pop( $stack );
                    $o =& $to_remove[0];
                    $key = $to_remove[1];
                    $p = explode('.', $key);
                    $i = 0; $l = count($p);
                    while ($i < $l)
                    {
                        $k = $p[$i++];
                        if ( $i < $l )
                        {
                            if ( is_object( $o ) ) 
                            {
                                if ( $WILDCARD === $k ) 
                                {
                                    $k = implode('.', array_slice($p, $i));
                                    foreach(array_keys((array)$o) as $key)
                                        $stack[] = array(&$o, "{$key}.{$k}");
                                    break;
                                }
                                elseif ( isset($o->{$k}) ) 
                                {
                                    $o =& $o->{$k};
                                }
                            }
                            elseif ( is_array( $o ) ) 
                            {
                                if ( $WILDCARD === $k ) 
                                {
                                    $k = implode('.', array_slice($p, $i));
                                    foreach(array_keys($o) as $key)
                                        $stack[] = array(&$o, "{$key}.{$k}");
                                    break;
                                }
                                elseif ( isset($o[$k]) ) 
                                {
                                    $o =& $o[$k];
                                }
                            }
                            else break; // key does not exist
                        }
                        else
                        {
                            if ( is_object($o) ) 
                            {
                                if ( $WILDCARD === $k )
                                {
                                    foreach(array_keys((array)$o) as $k) 
                                        unset( $o->{$k} );
                                }
                                elseif ( isset($o->{$k}) )
                                {
                                    unset( $o->{$k} );
                                }
                            }
                            elseif ( is_array($o) ) 
                            {
                                if ( $WILDCARD === $k )
                                {
                                    foreach(array_reverse(array_keys($o)) as $k) 
                                    {
                                        if ( is_numeric($k) ) array_splice($o, (int)$k, 1);
                                        else unset( $o[$k] );
                                    }
                                }
                                elseif ( isset($o[$k]) )
                                {
                                    if ( is_numeric($k) ) array_splice( $o, (int)$k, 1 );
                                    else unset( $o[$k] );
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }
    
    public function types( $types )
    {
        if ( !$types || empty( $types ) ) return $this;
        if ( is_array( $types ) || is_object( $types ) )
        {
            foreach ((array)$types as $k=>$t) 
                self::add_type_validator( $this, self::TYPECASTER, $k, $t );
        }
        return $this;
    }
    
    public function validators( $validators )
    {
        if ( !$validators || empty( $validators ) ) return $this;
        if ( is_array( $validators ) || is_object( $validators ) )
        {
            foreach ((array)$validators as $k=>$v) 
                self::add_type_validator( $this, self::VALIDATOR, $k, $v );
        }
        return $this;
    }
    
    public function typecast( )
    {
        $data =& $this->model_data;
        if ( !empty($data) )
        {
            $is_object = is_object( $data ); $is_array = is_array( $data );
            foreach((array)$data as $k=>$v)
            {
                $v = self::do_typecast( $this, $k, $v );
                if ( $is_object ) $data->{$k} = $v;
                else $data[$k] = $v;
            }
        }
        return $this;
    }
    
    public function validate( $breakOnFirstError=false )
    {
        $data =& $this->model_data;
        $result = (object)array(
            'isValid' => true,
            'errors' => array()
        );
        if ( !empty($data) )
        {
            foreach((array)$data as $k=>$v)
            {
                $res = self::do_validate( $this, $k, $v, $breakOnFirstError );
                if ( !$res->isValid )
                {
                    $result->isValid = false;
                    $result->errors = array_merge($result->errors, $res->errors);
                    if ( $breakOnFirstError )
                    {
                        return $result;
                    }
                }
            }
        }
        return $result;
    }
    
    public static function walk_and_get( $obj, $dottedKey ) 
    {
        if ( empty($obj) ) return null;
        $obj = array( $obj );
        $p = explode( '.', $dottedKey );
        $i = 0; $l = count( $p );
        while ( $i < $l )
        {
            $k = $p[$i++];
            if ( $i < $l ) 
            {
                $obj = self::get_next( $obj, $k );
                if ( !$obj || empty($obj) ) return null;
            }
            else 
            {
                $obj = self::get_value( $obj, $k );
                if ( $obj && !empty($obj) ) 
                {
                    return $obj;
                }
            }
        }
        return null;
    }
    
    public static function do_defaults( $model, $data, $defaults, $overwrite )
    {
        $is_object = is_object( $data ); $is_array = is_array( $data );
        if ( $is_object || $is_array )
        {
            foreach((array)$defaults as $k=>$v)
            {
                if ( $is_object ) 
                {
                    if ( $overwrite || !isset($data->{$k}) )
                    {
                        $data->{$k} = $v;
                    }
                    elseif ( (is_array( $data->{$k} ) || is_object( $data->{$k} )) && 
                    (is_array( $v ) || is_object( $v )) )
                    {
                        $data->{$k} = self::do_defaults( $this, $data->{$k}, $v, $overwrite );
                    }
                }
                else/*if ( $is_array )*/
                {
                    if ( $overwrite || !isset($data[$k]) )
                    {
                        $data[$k] = $v;
                    }
                    elseif ( (is_array( $data[$k] ) || is_object( $data[$k] )) && 
                    (is_array( $v ) || is_object( $v )) )
                    {
                        $data[$k] = self::do_defaults( $this, $data[$k], $v, $overwrite );
                    }
                }
            }
        }
        return $data;
    }
    
    public static function do_typecast( $model, $dottedKey, $data )
    {
        $typecaster = self::walk_and_get( $model->model_types, $dottedKey );
        if ( $typecaster /*&& ($typecaster instanceof ModelTypeCaster)*//*is_callable( $typecaster )*/ )
        {
            //return call_user_func( $typecaster, $data, $dottedKey, $model );
            return $typecaster->typecast( $data, $dottedKey, $model );
        }
        else
        {
            $is_object = is_object( $data ); $is_array = is_array( $data );
            if ( !empty($data) && ($is_object || $is_array) )
            {
                foreach((array)$data as $k=>$v)
                {
                    $v = self::do_typecast( $model, "{$dottedKey}.{$k}", $v );
                    if ( $is_object ) $data->{$k} = $v;
                    else $data[$k] = $v;
                }
            }
        }
        return $data;
    }
    
    public static function do_validate( $model, $dottedKey, $data, $breakOnFirstError=false )
    {
        $result = (object)array(
            'isValid' => true,
            'errors' => array()
        );
        
        $validator = self::walk_and_get( $model->model_validators, $dottedKey );
        if ( $validator /*&& ($validator instanceof ModelValidator)*//*is_callable( $validator )*/ )
        {
            //$res = call_user_func( $validator, $data, $dottedKey, $model );
            $res = $validator->validate( $data, $dottedKey, $model );
            if ( !$res )
            {
                $result->isValid = false;
                $result->errors[] = $dottedKey;
            }
        }
        else
        {
            $is_object = is_object( $data ); $is_array = is_array( $data );
            if ( !empty($data) && ($is_object || $is_array) )
            {
                foreach((array)$data as $k=>$v)
                {
                    $res = self::do_validate( $model, "{$dottedKey}.{$k}", $v );
                    if ( !$res->isValid )
                    {
                        $result->isValid = false;
                        $result->errors = array_merge($result->errors, $res->errors);
                        if ( $breakOnFirstError ) return $result;
                    }
                }
            }
        }
        return $result;
    }
    
    public static function walk_and_add( &$obj, $p, $v ) 
    {
        $o =& $obj; 
        $i = 0; $l = count($p);
        while ( $i < $l )
        {
            $k = $p[$i++];
            if ( !isset($o[$k]) ) $o[ $k ] = self::node( );
            $o =& $o[ $k ];
            if ( $i < $l ) 
            {
                $o =& $o->n;
            }
            else 
            {
                $o->v = $v;
            }
        }
    }
    
    public static function add_type_validator( $model, $type, $dottedKey, $value ) 
    {
        if ( !$value || empty( $value ) ) return;
        
        if ( (is_array( $value ) || is_object( $value )) && 
            !($value instanceof ModelTypeCaster) && !($value instanceof ModelValidator)
        )
        {
            // nested keys given, recurse
            foreach ( (array)$value as $k=>$v ) 
                self::add_type_validator( $model, $type, "{$dottedKey}.{$k}", $v );
        }
        
        else
        {
            if ( self::TYPECASTER===$type && ($value instanceof ModelTypeCaster) )
                self::walk_and_add( $model->model_types, explode('.', $dottedKey), $value );
            elseif ( self::VALIDATOR===$type && ($value instanceof ModelValidator) )
                self::walk_and_add( $model->model_validators, explode('.', $dottedKey), $value );
        }
    }
    
    public static function node( $val=null, $next=array() ) 
    {
        return (object)array( 
            'v' => $val ? $val : null,
            'n' => (array)$next
        );
    }
    
    public static function get_next( $a, $k ) 
    {
        if ( !$a || empty($a) ) return null;
        $b = array( ); 
        $l = count( $a );
        $WILDCARD = self::WILDCARD;
        for ($i=0; $i<$l; $i++)
        {
            $ai = $a[ $i ];
            if ( $ai )
            {
                if ( isset($ai[ $k ]) ) $b[] = $ai[ $k ]->n;
                if ( isset($ai[ $WILDCARD ]) ) $b[] = $ai[ $WILDCARD ]->n;
            }
        }
        return empty($b) ? null : $b;
    }
    
    public static function get_value( $a, $k=null ) 
    {
        if ( !$a || empty($a) ) return null;
        $l = count($a);
        $WILDCARD = self::WILDCARD;
        if ( null!==$k )
        {
            for ($i=0; $i<$l; $i++)
            {
                $ai = $a[ $i ];
                if ( $ai )
                {
                    if ( isset($ai[ $k ]) && $ai[ $k ]->v ) return $ai[ $k ]->v;
                    if ( isset($ai[ $WILDCARD ]) && $ai[ $WILDCARD ]->v ) return $ai[ $WILDCARD ]->v;
                }
            }
        }
        else
        {
            for ($i=0; $i<$l; $i++)
            {
                $ai = $a[ $i ];
                if ( $ai && isset($ai->v) ) return $ai->v;
            }
        }
        return null;
    }
    
    public static function by_length_desc( $a, $b )
    {
        return strlen($b)-strlen($a);
    }

    public static function esc_re( $s )
    {
        return preg_quote( $s, '/' );
    }
    
    public static function get_alternate_pattern( $alts ) 
    {
        usort( $alts, array(__CLASS__, 'by_length_desc') );
        return implode( '|', array_map(array(__CLASS__, 'esc_re'), $alts) );
    }
    
    public static function get_date_pattern( $format, $locale=null ) 
    {
        if ( !$locale ) $locale = self::$default_date_locale;
        
        // (php) date formats
        // http://php.net/manual/en/function.date.php
        $D = array(
            // Day --
            // Day of month w/leading 0; 01..31
             'd'=> '(31|30|29|28|27|26|25|24|23|22|21|20|19|18|17|16|15|14|13|12|11|10|09|08|07|06|05|04|03|02|01)'
            // Shorthand day name; Mon...Sun
            ,'D'=> '(' . self::get_alternate_pattern( $locale['day_short'] ) . ')'
            // Day of month; 1..31
            ,'j'=> '(31|30|29|28|27|26|25|24|23|22|21|20|19|18|17|16|15|14|13|12|11|10|9|8|7|6|5|4|3|2|1)'
            // Full day name; Monday...Sunday
            ,'l'=> '(' . self::get_alternate_pattern( $locale['day'] ) . ')'
            // ISO-8601 day of week; 1[Mon]..7[Sun]
            ,'N'=> '([1-7])'
            // Ordinal suffix for day of month; st, nd, rd, th
            ,'S'=> '' // added below
            // Day of week; 0[Sun]..6[Sat]
            ,'w'=> '([0-6])'
            // Day of year; 0..365
            ,'z'=> '([1-3]?[0-9]{1,2})'

            // Week --
            // ISO-8601 week number
            ,'W'=> '([0-5]?[0-9])'

            // Month --
            // Full month name; January...December
            ,'F'=> '(' . self::get_alternate_pattern( $locale['month'] ) . ')'
            // Month w/leading 0; 01...12
            ,'m'=> '(12|11|10|09|08|07|06|05|04|03|02|01)'
            // Shorthand month name; Jan...Dec
            ,'M'=> '(' . self::get_alternate_pattern( $locale['month_short'] ) . ')'
            // Month; 1...12
            ,'n'=> '(12|11|10|9|8|7|6|5|4|3|2|1)'
            // Days in month; 28...31
            ,'t'=> '(31|30|29|28)'
            
            // Year --
            // Is leap year?; 0 or 1
            ,'L'=> '([01])'
            // ISO-8601 year
            ,'o'=> '(\\d{2,4})'
            // Full year; e.g. 1980...2010
            ,'Y'=> '([12][0-9]{3})'
            // Last two digits of year; 00...99
            ,'y'=> '([0-9]{2})'

            // Time --
            // am or pm
            ,'a'=> '(' . self::get_alternate_pattern( array(
                $locale['meridian']['am'],
                $locale['meridian']['pm']
            ) ) . ')'
            // AM or PM
            ,'A'=> '(' . self::get_alternate_pattern( array(
                $locale['meridian']['AM'],
                $locale['meridian']['PM']
            ) ) . ')'
            // Swatch Internet time; 000..999
            ,'B'=> '([0-9]{3})'
            // 12-Hours; 1..12
            ,'g'=> '(12|11|10|9|8|7|6|5|4|3|2|1)'
            // 24-Hours; 0..23
            ,'G'=> '(23|22|21|20|19|18|17|16|15|14|13|12|11|10|9|8|7|6|5|4|3|2|1|0)'
            // 12-Hours w/leading 0; 01..12
            ,'h'=> '(12|11|10|09|08|07|06|05|04|03|02|01)'
            // 24-Hours w/leading 0; 00..23
            ,'H'=> '(23|22|21|20|19|18|17|16|15|14|13|12|11|10|09|08|07|06|05|04|03|02|01|00)'
            // Minutes w/leading 0; 00..59
            ,'i'=> '([0-5][0-9])'
            // Seconds w/leading 0; 00..59
            ,'s'=> '([0-5][0-9])'
            // Microseconds; 000000-999000
            ,'u'=> '([0-9]{6})'

            // Timezone --
            // Timezone identifier; e.g. Atlantic/Azores, ...
            ,'e'=> '(' . self::get_alternate_pattern( $locale['timezone'] ) . ')'
            // DST observed?; 0 or 1
            ,'I'=> '([01])'
            // Difference to GMT in hour format; e.g. +0200
            ,'O'=> '([+-][0-9]{4})'
            // Difference to GMT w/colon; e.g. +02:00
            ,'P'=> '([+-][0-9]{2}:[0-9]{2})'
            // Timezone abbreviation; e.g. EST, MDT, ...
            ,'T'=> '(' . self::get_alternate_pattern( $locale['timezone_short'] ) . ')'
            // Timezone offset in seconds (-43200...50400)
            ,'Z'=> '(-?[0-9]{5})'

            // Full Date/Time --
            // Seconds since UNIX epoch
            ,'U'=> '([0-9]{1,8})'
            // ISO-8601 date. Y-m-d\\TH:i:sP
            ,'c'=> '' // added below
            // RFC 2822 D, d M Y H:i:s O
            ,'r'=> '' // added below
        );
        // Ordinal suffix for day of month; st, nd, rd, th
        $lords = array_values( $locale['ordinal']['ord'] );
        $lords[] = $locale['ordinal']['nth'];
        $D['S'] = '(' . self::get_alternate_pattern( $lords ) . ')';
        // ISO-8601 date. Y-m-d\\TH:i:sP
        $D['c'] = $D['Y'].'-'.$D['m'].'-'.$D['d'].'\\\\'.$D['T'].$D['H'].':'.$D['i'].':'.$D['s'].$D['P'];
        // RFC 2822 D, d M Y H:i:s O
        $D['r'] = $D['D'].',\\s'.$D['d'].'\\s'.$D['M'].'\\s'.$D['Y'].'\\s'.$D['H'].':'.$D['i'].':'.$D['s'].'\\s'.$D['O'];
        
        $re = ''; 
        $l = strlen($format);
        for ($i=0; $i<$l; $i++)
        {
            $f = $format[$i];
            $re .= isset($D[$f]) ? $D[ $f ] : self::esc_re( $f );
        }
        return '/^'.$re.'$/';
    }
    
}
}