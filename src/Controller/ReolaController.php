<?php declare(strict_types = 1);

namespace Drupal\reola\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Reola routes.
 */
final class ReolaController extends ControllerBase {



/**
 * showUltimoDato function
 *
 * Dato que se muestra tras pulsar en una boya concreta del mapa de showMap
 *
 * @param [type] $idBoya
 * @return void
 */
public function showUltimodato($idBoya, $name){

    $reolaService = \Drupal::service('reola.service');
    $datos = $reolaService->getUltimoDato($idBoya);
    $datos['name'] = $name;

    if ((int)$idBoya>10){
       return[
            '#theme' => 'block_ultimodato',
            '#titulo' => $this->t('Último dato'),
            '#descripcion' => $this->t('Último dato'),
            '#datos' => $datos
        ];
    } else {
        return[
            '#theme' => 'block_lastdatabig',
            '#titulo' => $this->t('Último dato'),
            '#descripcion' => $this->t('Último dato'),
            '#datos' => $datos
        ];
    }

}

/**
 * showMap function
 *
 * Mapa general con todas las boyas.
 *
 * @return void
 */
public function showMap(){

  $reolaService = \Drupal::service('reola.service');
  $data = $reolaService->getDatosMapa();

  return[
      '#theme' => 'block_map',
      '#titulo' => $this->t('Mapa temático'),
      '#descripcion' => $this->t('Mapa temático'),
      '#datos' => $data
  ];
}


}
