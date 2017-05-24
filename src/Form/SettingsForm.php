<?php

namespace Drupal\permissions_by_term_per_content_type\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class SettingsForm.
 *
 * @property \Drupal\Core\Config\ConfigFactoryInterface config_factory
 * @property \Drupal\Core\Entity\EntityTypeManagerInterface entity_manager
 * @property  String entity_type_parameter
 * @property  String entity_type_id
 * @package Drupal\permissions_by_term_per_content_type\Controller
 */
class SettingsForm extends ConfigFormBase {
  protected $configFactory;

  protected $route_match;
  /**
   * {@inheritdoc}
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteMatchInterface $route_match) {
    parent::__construct($config_factory);
    $this->route_match = $route_match;
    $route_options = $this->route_match->getRouteObject()->getOptions();
    $array_keys = array_keys($route_options['parameters']);
    $this->entity_type_parameter = array_shift($array_keys);
    $entity_type = $this->route_match->getParameter($this->entity_type_parameter);
    $this->entity_type_id = $entity_type->id();
    $this->entity_type_provider =  $entity_type->getEntityType()->getProvider();
    $this->accessStorageService = \Drupal::service('permissions_by_term_per_content_type.access_storage');
    $this->nodeAccess = \Drupal::service('permissions_by_term_per_content_type.node_access');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'permissions_by_term_per_content_type.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'permissions_by_term_per_content_type_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('permissions_by_term_per_content_type.settings');
    $permissions_by_term_per_content_type_track = $config->get('permissions_by_term_per_content_type_track');

    $content_type = $this->entity_type_id;
    $field_definitions = \Drupal::entityManager()->getFieldDefinitions('node', $content_type);

    foreach ($field_definitions as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && $field_definition->getFieldStorageDefinition()->getSettings()['target_type'] == 'taxonomy_term') {
        $form[$field_definition->getName()] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Manage permissions by taxonomy terms of ') . $field_definition->getLabel(),
          '#default_value' => $permissions_by_term_per_content_type_track[$this->entity_type_id][$field_definition->getName()],
        );
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('permissions_by_term_per_content_type.settings');
    $permissions_by_term_per_content_type_track = $config->get('permissions_by_term_per_content_type_track');

    foreach ($form_state->getValues() as $key => $value) {
      if (stristr($key, 'field_')) {
        if ($value == 0) {
          $vocabId = key(\Drupal::entityManager()
            ->getFieldDefinitions('node', $this->entity_type_id)[$key]->getSettings()['handler_settings']['target_bundles']);
          $terms = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadTree($vocabId);
          foreach ($terms as $term) {
            $this->accessStorageService->deleteAllRolesForContentTypeAndTerm($this->entity_type_id, $term->tid);
            $this->nodeAccess->rebuildByTidForContentType($term->tid);
          }
          unset($permissions_by_term_per_content_type_track[$this->entity_type_id][$key]);
        } else {
          $permissions_by_term_per_content_type_track[$this->entity_type_id][$key] = $value;
        }
      }
    }
    //kint(empty($permissions_by_term_per_content_type_track[$this->entity_type_id]));exit;
    if (empty($permissions_by_term_per_content_type_track[$this->entity_type_id])) {
      unset($permissions_by_term_per_content_type_track[$this->entity_type_id]);
    }
    $config->set('permissions_by_term_per_content_type_track', $permissions_by_term_per_content_type_track)->save();
    parent::submitForm($form, $form_state);
  }

}
