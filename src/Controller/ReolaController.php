<?php declare(strict_types = 1);

namespace Drupal\reola\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Reola routes.
 */
final class ReolaController extends ControllerBase {


public function showUltimodato($idBoya){

    $reolaService = \Drupal::service('reola.service');
    $datos = $reolaService->getUltimoDato($idBoya);

    return[
        '#theme' => 'block_ultimodato',
        '#titulo' => $this->t('Último dato'),
        '#descripcion' => $this->t('Último dato'),
        '#datos' => $datos
    ];
}

public function showMap(){

  $reolaService = \Drupal::service('reola.service');
  $datos = $reolaService->getDatosMapa();

  return[
      '#theme' => 'block_map',
      '#titulo' => $this->t('Mapa temático'),
      '#descripcion' => $this->t('Mapa temático'),
      '#datos' => $datos
  ];
}

public function showFilteredForm(){

    $reolaService = \Drupal::service('reola.service');
    $datos = $reolaService->getDatosFiltrados('4723', '01-01-2024', '10-01-2024');
   // ksm ($datos);
    return[
        '#theme' => 'filter_form',
        '#titulo' => $this->t('Formulario filtros'),
        '#descripcion' => $this->t('Formulario filtros'),
        '#datos' => $datos

    ];
}

}
