<?php
/**
 *  application apps
 */

$app->get('/{wsid}', \Budgetcontrol\Saving\Http\Controller\SavingController::class . ':get');
$app->get('/{wsid}/{uuid}', \Budgetcontrol\Saving\Http\Controller\SavingController::class . ':show');
$app->post('/{wsid}', \Budgetcontrol\Saving\Http\Controller\SavingController::class . ':create');
$app->put('/{wsid}/{uuid}', \Budgetcontrol\Saving\Http\Controller\SavingController::class . ':update');
$app->delete('/{wsid}/{uuid}', \Budgetcontrol\Saving\Http\Controller\SavingController::class . ':delete');