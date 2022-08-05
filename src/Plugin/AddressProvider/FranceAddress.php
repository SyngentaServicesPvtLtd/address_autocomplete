<?php

namespace Drupal\address_autocomplete\Plugin\AddressProvider;

use Drupal\address_autocomplete\Plugin\AddressProviderBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a FranceAddress plugin for address_autocomplete.
 *
 * @AddressProvider(
 *   id = "france_address",
 *   label = @Translation("France Address"),
 * )
 */
class FranceAddress extends AddressProviderBase {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return array_merge(parent::defaultConfiguration(), [
      'endpoint' => 'https://api-adresse.data.gouv.fr/search/',
    ]);
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Data API Address endpoint'),
      '#default_value' => $this->configuration['endpoint'],
      '#description' => $this->t('More information <a href="@api">@apiLabel</a>', [
        '@api' => 'https://api.gouv.fr/les-api/base-adresse-nationale',
        '@apiLabel' => 'Base Adresse Nationale',
      ]),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $configuration['endpoint'] = $form_state->getValue('endpoint');
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritDoc}
   */
  public function processQuery($string) {
    $results = [];

    $exploded_query = explode('||', $string);
    $query = [
      'autocomplete' => 0,
      'limit' => 10,
      'q' => $exploded_query[0],
    ];
    $url = $this->configuration['endpoint'] . '?' . http_build_query($query);

    $response = $this->client->request('GET', $url);
    $content = Json::decode($response->getBody());
    if (!empty($content['features'])) {
      foreach ($content['features'] as $key => $feature) {
        if (!empty($feature['properties'])) {
          $results[$key]['street_name'] = $feature['properties']['name'];
          $results[$key]['town_name'] = $feature['properties']['city'];
          $results[$key]['zip_code'] = $feature['properties']['postcode'];
          $results[$key]['label'] = $feature['properties']['label'];
        }
        if (!empty($feature['geometry'])) {
          $results[$key]['location'] = [
            'longitude' => $feature['geometry']['coordinates'][0],
            'latitude' => $feature['geometry']['coordinates'][1],
          ];
        }
      }
    }

    return $results;
  }

}
