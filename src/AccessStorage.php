<?php

namespace Drupal\permissions_by_term_per_content_type;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Form\FormState;

/**
 * Class AccessStorage.
 *
 * Defines an API to the database in the term access context.
 *
 * The "protected" class methods are meant for protection regarding Drupal's
 * forms and presentation layer.
 *
 * The "public" class methods can be used for extensions.
 *
 * @package Drupal\permissions_by_term
 */
class AccessStorage implements AccessStorageInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $oDatabase;

  /**
   * The term name for which the access is set.
   *
   * @var string
   */
  protected $sTermName;

  /**
   * The user ids which gain granted access.
   *
   * @var array
   */
  //protected $aUserIdsGrantedAccess;

  /**
   * The roles with granted access.
   *
   * @var array
   */
  protected $aSubmittedRolesGrantedAccess;

  /**
   * AccessStorageService constructor.
   *
   * @param \Drupal\Core\Database\Driver\mysql\Connection $database
   *   The connection to the database.
   */
  public function __construct(Connection $database) {
    $this->oDatabase  = $database;
  }

  /**
   * Gets submitted roles with granted access from form.
   *
   * @return array
   *   An array with chosen roles.
   */
  public function getSubmittedRolesGrantedAccess(FormState $form_state) {
    /*$aRoles       = $form_state->getValue('access')['role'];
    $aChosenRoles = array();
    foreach ($aRoles as $sRole) {
      if ($sRole !== 0) {
        $aChosenRoles[] = $sRole;
      }
    }*/
    $aRoles_per_content_type = $form_state->getValue('access');
    $aChosenRoles = array();
    foreach ($aRoles_per_content_type as $content_type => $values) {
      foreach ($values['role'] as $key => $role) {
        if ($role !== 0) {
          $aChosenRoles[$content_type][] = $role;
        }
      }
    }
    return $aChosenRoles;
  }

  /**
   * {@inheritdoc}
   */
 /* public function checkIfUsersExists(FormState $form_state) {
    $sAllowedUsers = $form_state->getValue('access')['user'];
    $aAllowedUsers = Tags::explode($sAllowedUsers);
    foreach ($aAllowedUsers as $sUserId) {
      $aUserId = \Drupal::entityQuery('user')
        ->condition('uid', $sUserId)
        ->execute();
      if (empty($aUserId)) {
        $form_state->setErrorByName('access][user',
          t('The user with ID %user_id does not exist.',
          array('%user_id' => $sUserId)));
      }
    }
  }*/

  /**
   * {@inheritdoc}
   */
 /* public function getExistingUserTermPermissionsByTid($term_id) {
    return $this->oDatabase->select('permissions_by_term_user_content_type', 'pu')
      ->condition('tid', $term_id)
      ->fields('pu', ['uid'])
      ->execute()
      ->fetchCol();
  }*/

  /**
   * {@inheritdoc}
   */
  public function getExistingRoleTermPermissionsByTid($term_id) {
    return $this->oDatabase->select('permissions_by_term_role_content_type', 'pr')
      ->condition('tid', $term_id)
      ->fields('pr', [ 'content_type', 'rid'])
      ->execute()
      ->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
 /* public function getUserIdByName($sUsername) {
    return $this->oDatabase->select('users_field_data', 'ufd')
      ->condition('name', $sUsername)
      ->fields('ufd', ['uid'])
      ->execute()
      ->fetchAssoc();
  }*/

  /**
   * {@inheritdoc}
   */
 /* public function getUserIdsByNames($aUserNames) {
    $aUserIds = array();
    foreach ($aUserNames as $userName) {
      $iUserId    = $this->getUserIdByName($userName)['uid'];
      $aUserIds[] = $iUserId['uid'];
    }
    return $aUserIds;
  }*/

  /**
   * {@inheritdoc}
   */
  /*public function getAllowedUserIds($term_id) {
    $query = $this->oDatabase->select('permissions_by_term_user_content_type', 'p')
      ->fields('p', ['uid'])
      ->condition('p.tid', $term_id);

    // fetchCol() returns all results, fetchAssoc() only "one" result.
    return $query->execute()
      ->fetchCol();
  }*/

  /**
   * {@inheritdoc}
   */
 /* public function deleteTermPermissionsByUserIds($aUserIdsAccessRemove, $term_id) {
    foreach ($aUserIdsAccessRemove as $iUserId) {
      $this->oDatabase->delete('permissions_by_term_user_content_type')
        ->condition('uid', $iUserId, '=')
        ->condition('tid', $term_id, '=')
        ->execute();
    }
  }*/

  /**
   * {@inheritdoc}
   */
  public function deleteTermPermissionsByRoleIds($aRoleIdsAccessRemove, $term_id) {
    //kint($aRoleIdsAccessRemove);exit;
    foreach ($aRoleIdsAccessRemove as $key => $sRoleId) {
      foreach($sRoleId as $role) {
        $this->oDatabase->delete('permissions_by_term_role_content_type')
          ->condition('rid', $role, '=')
          ->condition('tid', $term_id, '=')
          ->condition('content_type', $key, '=')
          ->execute();
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function addTermPermissionsByRoleIds($aRoleIdsGrantedAccess, $term_id) {
    foreach ($aRoleIdsGrantedAccess as $key => $sRoleIdGrantedAccess) {
      foreach ($sRoleIdGrantedAccess as $role) {
        $this->oDatabase->insert('permissions_by_term_role_content_type')
          ->fields(['tid', 'rid', 'content_type'], [
            $term_id,
            $role,
            $key])
          ->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTermIdByName($sTermName) {
    $aTermId = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', $sTermName)
      ->execute();
    return key($aTermId);
  }

  /**
   * {@inheritdoc}
   */
  public function getTermNameById($term_id) {
    $term_name = \Drupal::entityQuery('taxonomy_term')
      ->condition('id', $term_id)
      ->execute();
    return key($term_name);
  }


  /**
   * {@inheritdoc}
   */
  public function saveTermPermissions(FormState $form_state, $term_id) {
    $aExistingRoleIdsGrantedAccess = $this->getExistingRoleTermPermissionsByTid($term_id);
    $aSubmittedRolesGrantedAccess  = $this->getSubmittedRolesGrantedAccess($form_state);

    $aRet = $this->getPreparedDataForDatabaseQueries($aExistingRoleIdsGrantedAccess, $aSubmittedRolesGrantedAccess);

    // Run the database queries.
    $this->deleteTermPermissionsByRoleIds($aRet['UserRolePermissionsToRemove'], $term_id);
    $this->addTermPermissionsByRoleIds($aRet['aRoleIdPermissionsToAdd'], $term_id);

    return $aRet;
  }

  /**
   * Get array items to remove.
   *
   * The array items which aren't in the new items array, but are in old items
   * array, will be returned.
   *
   * @param array $aExistingItems
   *   The existing array items.
   * @param array|bool $aNewItems
   *   Either false if there're no new items or an array with items.
   *
   * @return array
   *   The array items to remove.
   */
  private function getArrayItemsToRemove($aExistingItems, $aNewItems) {
    $aRet = array();
    foreach ($aExistingItems as $role) {
      $bExistingItems[$role->content_type][] = $role->rid;
    }

    foreach ($bExistingItems as $key => $existingItem) {
      foreach ($existingItem as $existingRole) {
        if (!in_array($existingRole, $aNewItems[$key])) {
          $aRet[$key][] = $existingRole;
        }
      }
    }

    return $aRet;
  }

  /**
   * Get the array items to add.
   *
   * The items in the new items array, which aren't in the existing items array,
   * will be returned.
   *
   * @param array $aNewItems
   *   The new array items.
   * @param array $aExistingItems
   *   The existing array items.
   *
   * @return array
   *   The items which needs to be added.
   */
  private function getArrayItemsToAdd($aNewItems, $aExistingItems) {
    $aRet = array();
    foreach ($aExistingItems as $role) {
      $bExistingItems[$role->content_type][] = $role->rid;
    }

    foreach ($aNewItems as $key => $newItem) {
      foreach ($newItem as $newItemRole) {
        if (!in_array($newItemRole, $bExistingItems[$key])) {
          $aRet[$key][] = $newItemRole;
        }
      }
    }

    return $aRet;
  }
  /**
   * {@inheritdoc}
   */
  public function getPreparedDataForDatabaseQueries($aExistingRoleIdsGrantedAccess,
                                                    $aSubmittedRolesGrantedAccess) {
    // Fill array with user roles to remove permission.
    $aRet['UserRolePermissionsToRemove'] =
      $this->getArrayItemsToRemove($aExistingRoleIdsGrantedAccess,
        $aSubmittedRolesGrantedAccess);

    // Fill array with user roles to add permission.
    $aRet['aRoleIdPermissionsToAdd'] =
      $this->getArrayItemsToAdd($aSubmittedRolesGrantedAccess,
        $aExistingRoleIdsGrantedAccess);

    return $aRet;
  }

  /**
   * @return array
   */
  public function getAllNids() {
    $query = $this->oDatabase->select('node', 'n')
        ->fields('n', ['nid']);

    return $query->execute()
        ->fetchCol();
  }

  public function getTidsByNid($nid) {
    $node = $this->entityManager->getStorage('node')->load($nid);
    $tids = [];

    foreach ($node->getFields() as $field) {
      if ($field->getFieldDefinition()->getType() == 'entity_reference' && $field->getFieldDefinition()->getSetting('target_type') == 'taxonomy_term') {
        $aReferencedTaxonomyTerms = $field->getValue();
        if (!empty($aReferencedTaxonomyTerms)) {
          foreach ($aReferencedTaxonomyTerms as $aReferencedTerm) {
            if (isset($aReferencedTerm['target_id'])) {
              $tids[] = $aReferencedTerm['target_id'];
            }
          }
        }
      }
    }

    return $tids;
  }

  public function getAllUids() {
    $nodes = \Drupal::entityQuery('user')
      ->execute();

    return array_values($nodes);
  }

  public function getNodeType($nid) {
    $query = $this->oDatabase->select('node', 'n')
      ->fields('n', ['type'])
      ->condition('n.nid', $nid);

    return $query->execute()
      ->fetchAssoc()['type'];
  }

  public function getLangCode($nid) {
    $query = $this->oDatabase->select('node', 'n')
      ->fields('n', ['langcode'])
      ->condition('n.nid', $nid);

    return $query->execute()
      ->fetchAssoc()['langcode'];
  }

  public function getGidsByRealm($realm) {
    $query = $this->oDatabase->select('node_access', 'na')
      ->fields('na', ['gid'])
      ->condition('na.realm', $realm);

    $gids = $query->execute()->fetchCol();

    foreach ($gids as $gid) {
      $grants[$realm][] = $gid;
    }

    return $grants;
  }

  /*public function getAllNidsUserCanAccess($uid) {
    $query = $this->oDatabase->select('node_access', 'na')
      ->fields('na', ['nid'])
      ->condition('na.realm', 'permissions_by_term__uid_' . $uid);

    return $query->execute()
      ->fetchCol();
  }*/

  public function getNidsByTid($tid) {
      $query = $this->oDatabase->select('taxonomy_index', 'ti')
        ->fields('ti', ['nid'])
        ->condition('ti.tid', $tid);

      return $query->execute()->fetchCol();
  }

  public function deleteAllRolesForContentTypeAndTerm($contentType, $termId) {
    $this->oDatabase->delete('permissions_by_term_role_content_type')
      ->condition('tid', $termId, '=')
      ->condition('content_type', $contentType, '=')
      ->execute();
  }

}
