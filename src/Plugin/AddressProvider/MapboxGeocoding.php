<?php

namespace Drupal\address_autocomplete\Plugin\AddressProvider;

use Drupal\address_autocomplete\Plugin\AddressProviderBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a Mapbox Geocoding plugin for address_autocomplete
 *
 * @AddressProvider(
 *   id = "mapbox_geocoding",
 *   label = @Translation("Mapbox Geocoding"),
 * )
 */
class MapboxGeocoding extends AddressProviderBase {

  /**
   * @inheritDoc
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
        'token' => '',
      ];
  }

  /**
   * @inheritDoc
   */
  public function processQuery($string) {
    $results = [];

    $token = $this->configuration['token'];
    $exploded_query = explode('||', $string);
    $address = $exploded_query[0].'.json?';
    $query = [
      'autocomplete' => 'true',
      'types' => 'address',
      'limit' => 10,
      'access_token' => $token,
      'language' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
    ];
    if (!empty($exploded_query[1])) {
      $query['country'] = $exploded_query[1];
    }
    $url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . $address . http_build_query($query) ;

    $response = $this->client->request('GET', $url);
    $content = Json::decode($response->getBody());

    //some country have format Street number street name
    $country_format_special = ['FR','CA','IE','IN','IL','HK','MY','OM','NZ','PH','SA','SE','SG','LK','TH','UK','US','VN'];
    foreach ($content["features"] as $key => $feature) {
      $results[$key]['street_name'] = !empty($feature["text_".$query['language']]) ? $feature["text_".$query['language']] : $feature["text"];
      if(!empty($feature["address"])){
        if (!empty($query['country']) && in_array($query['country'], $country_format_special)) {
          $results[$key]['street_name'] = $feature["address"] . ' ' . $results[$key]['street_name'];
        } else {
          $results[$key]['street_name'] .= ', ' . $feature["address"];
        }
      }
      if(!empty($feature["context"])){
        foreach ($feature["context"] as $context) {
          if (strpos($context['id'], 'region') !== FALSE) {
            $results[$key]['administrative_area'] = $context['text'];
            if(!empty($context['short_code'])){
              $explode_region = explode('-',$context['short_code']);
              $results[$key]['administrative_area'] = end($explode_region);
            }
          }
          if (strpos($context['id'], 'postcode') !== FALSE) {
            $results[$key]['zip_code'] = $context["text"];
          }
          if (strpos($context['id'], 'locality') !== FALSE) {
            $results[$key]['town_name'] = $context["text"];
          }
          if (strpos($context['id'], 'place') !== FALSE) {
            $results[$key]['town_name'] = $context["text"];
          }
        }
      }
      if(!empty( $feature["center"])){
        $results[$key]['location'] = [
          'longitude' => $feature["center"][0],
          'latitude' => $feature["center"][1],
        ];
      }
      $results[$key]['label'] = $feature["place_name"];
    }

    return $results;
  }

  /**
   * @inheritDoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => t('Token'),
      '#default_value' => $this->configuration['token'],
      '#attributes' => [
        'autocomplete' => 'off',
      ],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $configuration['token'] = $form_state->getValue('token');
    $this->setConfiguration($configuration);
  }

}
