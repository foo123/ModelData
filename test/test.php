<?php

include "../src/php/ModelData.php";

$model = new Modeldata(array(
    'a' => '1',
    'b' => array('1', '2'),
    'c' => '2015-40-12'
));

$model
->defaults(array(
    'b' => array('2','3','4')
))
->types(array(
    'a' => ModelData::TYPE_INT(),
    'b.*' => ModelData::TYPE_INT()
))
->validators(array(
    'c' => ModelData::VALIDATE_DATETIME('Y-m-d')
));

$result = $model->typecast( )->validate( );

//print_r( $model->Types );

//print_r( $model->Validators );

print_r( $result );

echo PHP_EOL . PHP_EOL;

echo json_encode($model->data( ));