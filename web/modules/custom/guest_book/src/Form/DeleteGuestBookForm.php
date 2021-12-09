<?php

namespace Drupal\guest_book\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Class Delete Form.
 *
 * @package Drupal\guest_book\Form
 */
class DeleteGuestBookForm extends ConfirmFormBase {

  protected $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'delete_form';
  }

  /**
   * Creating title of delete modal dialog window.
   */
  public function getQuestion(): TranslatableMarkup {
    return t('Delete comment');
  }

  /**
   * Provides a link to redirect on the main page from the delete form.
   */
  public function getCancelUrl(): Url {
    return new Url('guest_book.page');
  }

  /**
   * Provides description to the delete form.
   */
  public function getDescription(): TranslatableMarkup {
    return t('Do you want to delete this comment?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return t('Delete it');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): TranslatableMarkup {
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
   * Provides function for change image file status in table file_managed in database.
   */
  public function changeFileStatus($file) {
    if ($file != NULL) {

      $fileId = intval($file);

      \Drupal::database()
        ->update('file_managed')
        ->fields(['status' => 0])
        ->condition('fid', $fileId)->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = \Drupal::database()->select('guest_book', 'gb');
    $data = $query->condition('id', $this->id)
      ->fields('gb', [
        'id',
        'user_avatar',
        'user_image',
      ])
      ->execute()->fetch();

    $data = json_decode(json_encode($data), TRUE);
    $this->changeFileStatus($data['user_avatar']);
    $this->changeFileStatus($data['user_image']);

    $query = \Drupal::database();
    $query->delete('guest_book')
      ->condition('id', $this->id)
      ->execute();

    \Drupal::messenger()->addStatus('Successfully deleted.');
    $form_state->setRedirect('guest_book.page');
  }

}
