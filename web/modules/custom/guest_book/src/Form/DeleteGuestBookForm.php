<?php

namespace Drupal\guest_book\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Ajax\CloseModalDialogCommand;
/**
 * Class DeleteForm
 * @package Drupal\guest_book\Form
 */
class DeleteGuestBookForm extends ConfirmFormBase {

  public $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'delete_form';
  }

  public function getQuestion() {
    return t('Delete comment');
  }

  public function getCancelUrl() {
    return new Url('guest_book.page');
  }

  public function getDescription() {
    return t('Do you want to delete this comment?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete it');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->id = $id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = \Drupal::database();
    $query->delete('guest_book')
      ->condition('id', $this->id)
      ->execute();
    \Drupal::messenger()->addStatus('Successfully deleted.');
    $form_state->setRedirect('guest_book.page');
  }
}
