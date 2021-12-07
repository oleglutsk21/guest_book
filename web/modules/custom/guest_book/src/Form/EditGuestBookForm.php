<?php

namespace Drupal\guest_book\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form for edit comments.
 */

class EditGuestBookForm extends GuestBookForm {
  /**
   * {@inheritDoc}
   */

  public function getFormId(): string {
    return 'edit_form';
  }

  protected $userId;

  public function getConfirmText() {
    return t('Edit it!');
  }
  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $userId = NULL): array {
    $this->userId = \Drupal::routeMatch()->getParameter('id');
    $connection = Database::getConnection();
    $data = [];
    if (isset($this->userId)) {
      $query = $connection->select('guest_book', 'gb')
        ->condition('id', $this->userId)
        ->fields('gb');
      $data = $query->execute()->fetchAssoc();
    }
    $form = parent::buildForm($form, $form_state);
    $form['user_name']['#default_value'] = (isset($data['user_name'])) ? $data['user_name'] : '';
    $form['user_name']['#ajax']['callback'] = '::ajaxNameValidate';
    $form['user_email']['#default_value'] = (isset($data['user_email'])) ? $data['user_email'] : '';
    $form['user_phone']['#default_value'] = (isset($data['user_phone'])) ? $data['user_phone'] : '';
    $form['user_message']['#default_value'] = (isset($data['user_message'])) ? $data['user_message'] : '';
    $form['user_avatar']['#default_value'][] = (isset($data['user_avatar'])) ? $data['user_avatar'] : '';
    $form['user_image']['#default_value'][] = (isset($data['user_image'])) ? $data['user_image'] : '';
    $form['submit']['#value'] = $this->t('Edit');
    $form['submit']['#ajax'] = NULL;
    return $form;
  }

  public function ajaxNameValidate(array $form, FormStateInterface $form_state) {
    $userName = $form_state->getValue('user_name');
    $response = new AjaxResponse();
    if (strlen($userName) < 2 || strlen($userName) > 100) {
      $response->addCommand(
        new MessageCommand($this->t('Sorry, the name you entered is incorrect, please enter a valid name.'), '.user__name-validation-message', ['type' => 'error'], TRUE)
      );
    }
    else {
      $response->addCommand(new HtmlCommand('.user__name-validation-message', ''));
    }
    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    //parent::submitForm($form, $form_state);

    if (isset($this->userId) && !$form_state->hasAnyErrors()) {
      // Update data in database.
      \Drupal::database()->update('guest_book')->fields([
        'user_name' => $form_state->getValue('user_name'),
        'user_email' => $form_state->getValue('user_email'),
        'user_phone' => $form_state->getValue('user_phone'),
        'user_message' => $form_state->getValue('user_message'),
        'user_avatar' => $this->userAvatar[0],
        'user_image' => $this->userImage[0],
      ])->condition('id', $this->userId)->execute();
      \Drupal::messenger()->addStatus($this->t('Successfully saved'));
      $url = new Url('guest_book.page');
      $response = new RedirectResponse($url->toString());
      $response->send();
    }
    else {
      \Drupal::messenger()->addError($this->t('Sorry, the name you entered is not correct, please enter the correct name.'));
    }

  }
}
