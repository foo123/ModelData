<?php
/**
*   ModelData
*   uses similar API to ModelView.js to typecast and validate model data
*
*   @version 0.1
*   https://github.com/foo123/ModelData
*
**/
if ( !class_exists("ModelData") )
{

/*class ModelField
{
    public $f=null;
    public static function _($f)
    {
        return new self($f);
    }
    public function __construct($f)
    {
        $this->f = $f;
    }
    public function __destruct()
    {
        $this->dispose( );
    }
    public function dispose()
    {
        $this->f = null;
        return $this;
    }
}*/
class ModelTypeCaster
{
    private $v = null;
    public function __construct($v=null)
    {
        $this->v = $v;
    }
    public function __destruct()
    {
        $this->v = null;
    }
    public function typecast_composite( $v, $k, $m )
    {
        $typecasters = $this->v;
        $l = count($typecasters);
        while ( $l-- ) $v = call_user_func($typecasters[$l], $v, $k, $m);
        return $v;
    }
    public function typecast_fields( $v, $k, $m )
    {
        $typesPerField = $this->v;
        foreach ($typesPerField as $field=>$type)
        {
            $v[ $field ] = call_user_func($type, $v[ $field ], $k, $m );
        }
        return $v;
    }
    public function typecast_default( $v, $k, $m )
    {
        $defaultValue = $this->v;
        if ( !$v || (is_string($v) && !strlen(trim($v)))  ) $v = $defaultValue;
        return $v;
    }
    public function typecast_bool( $v, $k, $m )
    {
        // handle string representation of booleans as well
        if ( is_string($v) && strlen($v) )
        {
            $vs = strtolower($v);
            return "true" === $vs || "on" === $vs || "1" === $vs;
        }
        return (bool)($v); 
    }
    
    public function typecast_int( $v, $k, $m ) 
    { 
        // convert NaN to 0 if needed
        $v = intval($v, 10);
        return !$v ? 0 : $v;
    }
    
    public function typecast_float( $v, $k, $m ) 
    { 
        // convert NaN to 0 if needed
        $v = floatval($v, 10);
        return !$v ? 0.0 : $v;
    }
    
    public function typecast_trim( $v, $k, $m ) 
    { 
        return trim(strval($v));
    }
    
    public function typecast_lcase( $v, $k, $m ) 
    { 
        return strtolower(strval($v));
    }
    
    public function typecast_ucase( $v, $k, $m ) 
    { 
        return strtoupper(strval($v));
    }
            
    public function typecast_str( $v, $k, $m ) 
    { 
        return strval($v);
    }
    
    public function typecast_min( $v, $k, $m ) 
    {  
        $min = $this->v;
        return ($v < $min) ? $min : $v; 
    }
    
    public function typecast_max( $v, $k, $m ) 
    {  
        $max = $this->v;
        return ($v > $max) ? $max : $v; 
    }
    
    public function typecast_clamp( $v, $k, $m ) 
    {  
        $min = $this->v[0]; $max = $this->v[1];
        return ($v < $min) ? $min : (($v > $max) ? $max : $v); 
    }
    
    public function typecast_pad( $v, $k, $m ) 
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
            elseif ( 'L' === $pad_type )
            {
                $vs = str_repeat($pad_char, $n) . $vs;
            }
        }
        return $vs;
    }
    
    public function typecast_datetime( $v, $k, $m ) 
    {
        $format = $this->v[0];
        $locale = $this->v[1];
        // TODO: localisation
        return date( $format, $v ); 
    }
}
class ModelValidator
{
    private $v = null;
    public function __construct($v=null)
    {
        $this->v = $v;
    }
    public function __destruct()
    {
        $this->v = null;
    }
    public function validate_fields( $v, $k, $m )
    {
        $validatorsPerField = $this->v;
        foreach ($validatorsPerField as $field=>$validator)
        {
            if ( !call_user_func($validator, $v[ $field ], $k, $m ) )
                return false;
        }
        return true;
    }
    public function validate_numeric( $v, $k, $m )
    {
        return is_numeric( $v );
    }
    public function validate_empty( $v, $k, $m )
    {
        return (bool)(!$v || !strlen(trim(strval($v))));
    }
    public function validate_not_empty( $v, $k, $m )
    {
        return (bool)($v && strlen(trim(strval($v)))>0);
    }
    public function validate_maxlen( $v, $k, $m )
    {
        $len = $this->v;
        return (bool)(strlen($v)<=$len);
    }
    public function validate_minlen( $v, $k, $m )
    {
        $len = $this->v;
        return (bool)(strlen($v)>=$len);
    }
    public function validate_match( $v, $k, $m )
    {
        $regex_pattern = $this->v;
        $matches = array();
        return (bool)preg_match($regex_pattern, $v, $matches);
    }
    public function validate_not_match( $v, $k, $m )
    {
        $regex_pattern = $this->v;
        $matches = array();
        return (bool)(!preg_match($regex_pattern, $v, $matches));
    }
    public function validate_equal( $v, $k, $m )
    {
        $val = $this->v[0]; $strict = $this->v[1];
        return $strict ? ($val === $v) : ($val == $v);
    }
    public function validate_not_equal( $v, $k, $m )
    {
        $val = $this->v[0]; $strict = $this->v[1];
        return $strict ? ($val !== $v) : ($val != $v);
    }
    public function validate_greater_than( $v, $k, $m )
    {
        $min = $this->v[0]; $strict = $this->v[1];
        return $strict ? ($min < $v) : ($min <= $v);
    }
    public function validate_less_than( $v, $k, $m )
    {
        $max = $this->v[0]; $strict = $this->v[1];
        return $strict ? ($max > $v) : ($max >= $v);
    }
    public function validate_between( $v, $k, $m )
    {
        $min =$this->v[0]; $max = $this->v[1]; $strict = $this->v[2];
        return $strict ? (($min < $v) && ($v < $max)) : (($min <= $v) && ($v <= $max));
    }
    public function validate_not_between( $v, $k, $m )
    {
        $min =$this->v[0]; $max = $this->v[1]; $strict = $this->v[2];
        return $strict ? (($min > $v) || ($v > $max)) : (($min >= $v) || ($v >= $max));
    }
    public function validate_in( $v, $k, $m )
    {
        $vals = $this->v;
        return in_array($v, $vals);
    }
    public function validate_not_in( $v, $k, $m )
    {
        $vals = $this->v;
        return !in_array($v, $vals);
    }
    public function validate_min_items( $v, $k, $m )
    {
        $limit = $this->v[0]; $item_filter = $this->v[1];
        return $item_filter ? ($limit <= count(array_filter($v, $item_filter))) : ($limit <= count($v));
    }
    public function validate_max_items( $v, $k, $m )
    {
        $limit = $this->v[0]; $item_filter = $this->v[1];
        return $item_filter ? ($limit >= count(array_filter($v, $item_filter))) : ($limit >= count($v));
    }
    public function validate_email( $v, $k, $m )
    {
        $email_pattern = '/^(([^<>()[\\]\\\\.,;:\\s@\\"]+(\\.[^<>()[\\]\\\\.,;:\\s@\\"]+)*)|(\\".+\\"))@((\\[[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\])|(([a-zA-Z\\-0-9]+\\.)+[a-zA-Z]{2,}))$/';
        $matches = array( );
        return (bool)preg_match($email_pattern, $v, $matches);
    }
    public function validate_url( $v, $k, $m )
    {
        $url_pattern = '/^(?!mailto:)(?:(?:http|https|ftp)://)(?:\\S+(?::\\S*)?@)?(?:(?:(?:[1-9]\\d?|1\\d\\d|2[01]\\d|22[0-3])(?:\\.(?:1?\\d{1,2}|2[0-4]\\d|25[0-5])){2}(?:\\.(?:[0-9]\\d?|1\\d\\d|2[0-4]\\d|25[0-4]))|(?:(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)(?:\\.(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)*(?:\\.(?:[a-z\\u00a1-\\uffff]{2,})))|localhost)(?::\\d{2,5})?(?:(/|\\?|#)[^\\s]*)?$/i';
        $matches = array( );
        return (bool)preg_match($url_pattern, $v, $matches);
    }
    public function validate_datetime( $v, $k, $m )
    {
        $date_pattern = $this->v;
        $matches = array( );
        return (bool)preg_match($date_pattern, $v, $matches);
    }
}

class ModelData
{
    const VERSION = "0.1";
    const TYPECASTER = 1;
    const VALIDATOR = 2;
    const WILDCARD = "*";
    
    public static $default_date_locale = null;
    
    public $Data = null;
    public $Types = null;
    public $Validators = null;
    
    public static function init( )
    {
        static $inited = false;
        if ( !$inited )
        {
            self::$default_date_locale = array(
                'meridian'=> array( 'am'=>'am', 'pm'=>'pm', 'AM'=>'AM', 'PM'=>'PM' ),
                'ordinal'=> array( 'ord'=>array(1=>'st',2=>'nd',3=>'rd'), 'nth'=>'th' ),
                'timezone'=> array( 'UTC','EST','MDT' ),
                'timezone_short'=> array( 'UTC','EST','MDT' ),
                'day'=> array( 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday' ),
                'day_short'=> array( 'Sun','Mon','Tue','Wed','Thu','Fri','Sat' ),
                'month'=> array( 'January','February','March','April','May','June','July','August','September','October','November','December' ),
                'month_short'=> array( 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec' )
            );
            $inited = true;
        }
    }
    
    public function __construct( $data=array() )
    {
        $this->Types = array( );
        $this->Validators = array( );
        $this->data( $data );
    }
    
    public function __destruct( )
    {
        $this->dispose( );
    }
    
    public function dispose( )
    {
        $this->Data = null;
        $this->Types = null;
        $this->Validators = null;
        return $this;
    }
    
    public function data( $data=null )
    {
        if ( 0 < func_num_args( ) )
        {
            $this->Data = $data;
            return $this;
        }
        return $this->Data;
    }
    
    public function defaults( $defaults )
    {
        if ( !empty( $defaults) )
        {
            $data =& $this->Data;
            $is_object = is_object( $data ); $is_array = is_array( $data );
            if ( $is_object || $is_array )
            {
                foreach((array)$defaults as $k=>$v)
                {
                    if ( $is_object ) 
                    {
                        if ( !isset( $data->{$k} ) )
                        {
                            $data->{$k} = $v;
                        }
                        elseif ( (is_array( $data->{$k} ) || is_object( $data->{$k} )) && 
                        (is_array( $v ) || is_object( $v )) )
                        {
                            $data->{$k} = self::do_defaults( $this, $data->{$k}, $v );
                        }
                    }
                    else/*if ( $is_array )*/
                    {
                        if ( !isset($data[$k]) )
                        {
                            $data[$k] = $v;
                        }
                        elseif ( (is_array( $data[$k] ) || is_object( $data[$k] )) && 
                        (is_array( $v ) || is_object( $v )) )
                        {
                            $data[$k] = self::do_defaults( $this, $data[$k], $v );
                        }
                    }
                }
            }
        }
        return $this;
    }
    
    public function remove( $fields )
    {
        if ( !$fields || empty( $fields ) ) return $this;
        $data =& $this->Data;
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
                                    foreach(array_keys((array)$o) as $k) unset( $o->{$k} );
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
                                    foreach(array_keys($o) as $k) unset( $o[$k] );
                                }
                                elseif ( isset($o[$k]) )
                                {
                                    unset( $o[$k] );
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
        if ( is_object( $types ) ) $types = (array)$types;
        if ( is_array( $types ) )
        {
            foreach ($types as $k=>$t) 
                self::add_type_validator( $this, self::TYPECASTER, $k, $t );
        }
        return $this;
    }
    
    public function validators( $validators )
    {
        if ( !$validators || empty( $validators ) ) return $this;
        if ( is_object( $validators ) ) $validators = (array)$validators;
        if ( is_array( $validators ) )
        {
            foreach ($validators as $k=>$v) 
                self::add_type_validator( $this, self::VALIDATOR, $k, $v );
        }
        return $this;
    }
    
    public function typecast( )
    {
        $data =& $this->Data;
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
        $data =& $this->Data;
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
    
    public static function do_defaults( $model, $data, $defaults )
    {
        $is_object = is_object( $data ); $is_array = is_array( $data );
        if ( $is_object || $is_array )
        {
            foreach((array)$defaults as $k=>$v)
            {
                if ( $is_object ) 
                {
                    if ( !isset($data->{$k}) )
                    {
                        $data->{$k} = $v;
                    }
                    elseif ( (is_array( $data->{$k} ) || is_object( $data->{$k} )) && 
                    (is_array( $v ) || is_object( $v )) )
                    {
                        $data->{$k} = self::do_defaults( $this, $data->{$k}, $v );
                    }
                }
                else/*if ( $is_array )*/
                {
                    if ( !isset($data[$k]) )
                    {
                        $data[$k] = $v;
                    }
                    elseif ( (is_array( $data[$k] ) || is_object( $data[$k] )) && 
                    (is_array( $v ) || is_object( $v )) )
                    {
                        $data[$k] = self::do_defaults( $this, $data[$k], $v );
                    }
                }
            }
        }
        return $data;
    }
    
    public static function do_typecast( $model, $dottedKey, $data )
    {
        $typecaster = self::walk_and_get( $model->Types, $dottedKey );
        if ( $typecaster && is_callable( $typecaster ) )
        {
            return call_user_func( $typecaster, $data, $dottedKey, $model );
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
        
        $validator = self::walk_and_get( $model->Validators, $dottedKey );
        if ( $validator && is_callable( $validator ) )
        {
            $res = call_user_func( $validator, $data, $dottedKey, $model );
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
            if ( !isset($o[$k]) ) $o[ $k ] = self::Node( );
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
        
        if ( is_object( $value ) ) $value = (array)$value;
        
        if ( is_string( $value ) )
        {
            if ( self::TYPECASTER===$type && is_callable(array(__CLASS__, "TYPE_$value")) )
            {
                $value = array(__CLASS__, "TYPE_$value");
                self::walk_and_add( $model->Types, explode('.', $dottedKey), call_user_func( $value ) );
            }
            elseif ( self::VALIDATOR===$type && is_callable(array(__CLASS__, "VALIDATE_$value")) )
            {
                $value = array(__CLASS__, "VALIDATE_$value");
                self::walk_and_add( $model->Validators, explode('.', $dottedKey), call_user_func( $value ) );
            }
        }
        
        elseif ( is_array( $value ) && 2 === count($value) && is_callable( $value ) )
        {
            if ( self::TYPECASTER===$type )
                self::walk_and_add( $model->Types, explode('.', $dottedKey), $value );
            elseif ( self::VALIDATOR===$type )
                self::walk_and_add( $model->Validators, explode('.', $dottedKey), $value );
        }
        
        else if ( is_array( $value ) )
        {
            // nested keys given, recurse
            foreach ( $value as $k=>$v ) 
                self::add_type_validator( $model, $type, "{$dottedKey}.{$k}", $v );
        }
    }
    
    //
    // TypeCasters
    
    public static function TYPE_COMPOSITE( $typecaster ) 
    {
        $args = func_get_args( );
        if ( is_array($args[0]) && !is_callable($args[0]) ) $args = $args[ 0 ];
        $t = new ModelTypeCaster( (array)$args );
        return array($t, 'typecast_composite');
    }
    
    public static function TYPE_FIELDS( $typesPerField ) 
    {
        $t = new ModelTypeCaster( (array)$typesPerField );
        return array($t, 'typecast_fields');
    }
    
    public static function TYPE_DEFAULT( $defaultValue ) 
    {
        $t = new ModelTypeCaster( $defaultValue );
        return array($t, 'typecast_default');
    }
    
    public static function TYPE_BOOL( )
    {
        $t = new ModelTypeCaster( );
        return array($t, 'typecast_bool');
    }
    
    public static function TYPE_INT( ) 
    { 
        $t = new ModelTypeCaster( );
        return array($t, 'typecast_int');
    }
    
    public static function TYPE_FLOAT( ) 
    { 
        $t = new ModelTypeCaster( );
        return array($t, 'typecast_float');
    }
    
    public static function TYPE_TRIM( ) 
    { 
        $t = new ModelTypeCaster( );
        return array($t, 'typecast_trim');
    }
    
    public static function TYPE_LCASE( ) 
    { 
        $t = new ModelTypeCaster( );
        return array($t, 'typecast_lcase');
    }
    
    public static function TYPE_UCASE( ) 
    { 
        $t = new ModelTypeCaster( );
        return array($t, 'typecast_ucase');
    }
            
    public static function TYPE_STR( ) 
    { 
        $t = new ModelTypeCaster( );
        return array($t, 'typecast_str');
    }
    
    public static function TYPE_MIN( $m ) 
    {  
        $t = new ModelTypeCaster( $m );
        return array($t, 'typecast_min');
    }
    
    public static function TYPE_MAX( $M ) 
    {  
        $t = new ModelTypeCaster( $M );
        return array($t, 'typecast_max');
    }
    
    public static function TYPE_CLAMP( $m, $M ) 
    {  
        // swap
        if ( $m > $M ) { $tmp = $M; $M = $m; $m = $tmp; }
        $t = new ModelTypeCaster( array($m, $M) );
        return array($t, 'typecast_clamp');
    }
    
    public static function TYPE_PAD( $pad_char, $pad_size, $pad_type="L" ) 
    { 
        $t = new ModelTypeCaster( array($pad_char, $pad_size, $pad_type) );
        return array($t, 'typecast_pad');
    }
    
    public static function TYPE_DATETIME( $format="Y-m-d", $locale=null ) {
        if ( !$locale ) $locale = self::$default_date_locale;
        $t = new ModelTypeCaster( array($format, $locale) );
        return array($t, 'typecast_datetime');
    }
    
    //
    // Validators
    
    public static function VALIDATE_FIELDS( $validatorsPerField )
    {
        $v = new ModelValidator( (array)$validatorsPerField );
        return array($v, 'validate_fields');
    }
    
    public static function VALIDATE_NUMERIC( )
    {
        $v = new ModelValidator( );
        return array($v, 'validate_numeric');
    }
    
    public static function VALIDATE_EMPTY( )
    {
        $v = new ModelValidator( );
        return array($v, 'validate_empty');
    }
    
    public static function VALIDATE_NOT_EMPTY( )
    {
        $v = new ModelValidator( );
        return array($v, 'validate_not_empty');
    }
    
    public static function VALIDATE_MAXLEN( $len=0 )
    {
        $v = new ModelValidator( $len );
        return array($v, 'validate_maxlen');
    }
    
    public static function VALIDATE_MINLEN( $len=0 )
    {
        $v = new ModelValidator( $len );
        return array($v, 'validate_minlen');
    }
    
    public static function VALIDATE_MATCH( $regex_pattern )
    {
        $v = new ModelValidator( $regex_pattern );
        return array($v, 'validate_match');
    }
    
    public static function VALIDATE_NOT_MATCH( $regex_pattern )
    {
        $v = new ModelValidator( $regex_pattern );
        return array($v, 'validate_not_match');
    }
    
    public static function VALIDATE_EQUAL( $val, $strict=true )
    {
        $v = new ModelValidator( array($val, $strict) );
        return array($v, 'validate_equal');
    }
    
    public static function VALIDATE_NOT_EQUAL( $val, $strict=true )
    {
        $v = new ModelValidator( array($val, $strict) );
        return array($v, 'validate_not_equal');
    }
    
    public static function VALIDATE_GREATER_THAN( $m, $strict=true )
    {
        $v = new ModelValidator( array($m, $strict) );
        return array($v, 'validate_greater_than');
    }
    
    public static function VALIDATE_LESS_THAN( $M, $strict=true )
    {
        $v = new ModelValidator( array($M, $strict) );
        return array($v, 'validate_less_than');
    }
    
    public static function VALIDATE_BETWEEN( $m, $M, $strict=true )
    {
        if ( is_array($m) ) { $strict = $M; $M = $m[1]; $m = $m[0]; }
        // swap
        if ( $m > $M ) { $tmp = $M; $M = $m; $m = $tmp; }
        $v = new ModelValidator( array($m, $M, $strict) );
        return array($v, 'validate_between');
    }
    
    public static function VALIDATE_NOT_BETWEEN( $m, $M, $strict=true )
    {
        if ( is_array($m) ) { $strict = $M; $M = $m[1]; $m = $m[0]; }
        // swap
        if ( $m > $M ) { $tmp = $M; $M = $m; $m = $tmp; }
        $v = new ModelValidator( array($m, $M, $strict) );
        return array($v, 'validate_not_between');
    }
    
    public static function VALIDATE_IN( /* var args here */ )
    {
        $args = func_get_args( );
        if ( isset($args[0]) && is_array($args[0]) ) $args = $args[0];
        $v = new ModelValidator( $args );
        return array($v, 'validate_in');
    }
    
    public static function VALIDATE_NOT_IN( /* var args here */ )
    {
        $args = func_get_args( );
        if ( isset($args[0]) && is_array($args[0]) ) $args = $args[0];
        $v = new ModelValidator( $args );
        return array($v, 'validate_not_in');
    }
    
    public static function VALIDATE_MIN_ITEMS( $limit, $item_filter=null )
    {
        $v = new ModelValidator( array(intval($limit,10), $item_filter&&is_callable($item_filter)?$item_filter:null) );
        return array($v, 'validate_min_items');
    }
    
    public static function VALIDATE_MAX_ITEMS( $limit, $item_filter=null )
    {
        $v = new ModelValidator( array(intval($limit,10), $item_filter&&is_callable($item_filter)?$item_filter:null) );
        return array($v, 'validate_max_items');
    }
    
    public static function VALIDATE_EMAIL( )
    {
        $v = new ModelValidator( );
        return array($v, 'validate_email');
    }
    
    public static function VALIDATE_URL( )
    {
        $v = new ModelValidator( );
        return array($v, 'validate_url');
    }
    
    public static function VALIDATE_DATETIME( $format="Y-m-d", $locale=null)
    {
        if ( !$locale ) $locale = self::$default_date_locale;
        $v = new ModelValidator( self::get_date_pattern( $format, $locale ) );
        return array($v, 'validate_datetime');
    }
    
    public static function Node( $val=null, $next=array() ) 
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
            ,'z'=> '([0-3]?[0-9]{1,2})'

            // Week --
            // ISO-8601 week number
            ,'W'=> '([0-5][0-9])'

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
            ,'Y'=> '([1-9][0-9]{3})'
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
ModelData::init( );
}