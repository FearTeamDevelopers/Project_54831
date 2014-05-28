<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Core\ArrayMethods;
use THCFrame\Filesystem\ImageManager;
use THCFrame\Events\Events as Event;

/**
 * Description of UserController
 *
 * @author Tomy
 */
class Admin_Controller_Partner extends Controller
{

    /**
     * @before _secured, _publisher
     */
    public function index()
    {
        $view = $this->getActionView();

        $query = App_Model_Partner::getQuery(array('pa.*'))
                ->join('tb_section', 'pa.sectionId = s.id', 's', array('s.title' => 'sectionTitle'));

        $partners = App_Model_Partner::initialize($query);

        $view->set('partners', $partners);
    }

    /**
     * @before _secured, _publisher
     */
    public function add()
    {
        $view = $this->getActionView();
        $sections = App_Model_Section::all(array(
                    'active = ?' => true,
                    'parentId = ?' => 6
                        ), array('id', 'parentId', 'title')
        );

        if (RequestMethods::post('submitAddPartner')) {
            $errors = array();

            try {
                $im = new ImageManager(array(
                    'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                    'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                    'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby')
                ));

                $photoArr = $im->uploadWithoutThumb('logo', 'partners');
                $uploaded = ArrayMethods::toObject($photoArr);
            } catch (Exception $ex) {
                $errors['logo'] = $ex->getMessage();
            }

            $partner = new App_Model_Partner(array(
                'sectionId' => RequestMethods::post('section'),
                'title' => RequestMethods::post('title'),
                'address' => RequestMethods::post('address'),
                'email' => RequestMethods::post('email'),
                'web' => RequestMethods::post('web'),
                'logo' => trim($uploaded->photo->filename, '.'),
                'mobile' => RequestMethods::post('mobile')
            ));

            if (empty($errors) && $partner->validate()) {
                $id = $partner->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Partner has been successfully created');
                self::redirect('/admin/partner/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $partner->getErrors())
                        ->set('partner', $partner);
            }
        }

        $view->set('sections', $sections);
    }

    /**
     * @before _secured, _publisher
     * @param type $id
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $errors = array();

        $sections = App_Model_Section::all(array(
                    'active = ?' => true,
                    'parentId = ?' => 6
                        ), array('id', 'parentId', 'title')
        );

        $partner = App_Model_Partner::first(array('id = ?' => $id));

        if (NULL === $partner) {
            $view->errorMessage('Partner not found');
            self::redirect('/admin/partner/');
        }

        if (RequestMethods::post('submitEditPartner')) {
            if ($partner->logo == '') {
                try {
                    $im = new ImageManager(array(
                        'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                        'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                        'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby')
                    ));

                    $photoArr = $im->uploadWithoutThumb('logo', 'partners');
                    $uploaded = ArrayMethods::toObject($photoArr);
                    $logo = trim($uploaded->photo->filename, '.');
                } catch (Exception $ex) {
                    $errors['logo'] = $ex->getMessage();
                }
            } else {
                $logo = $partner->logo;
            }

            $partner->sectionId = RequestMethods::post('section');
            $partner->title = RequestMethods::post('title');
            $partner->address = RequestMethods::post('address');
            $partner->email = RequestMethods::post('email');
            $partner->web = RequestMethods::post('web');
            $partner->mobile = RequestMethods::post('mobile');
            $partner->logo = $logo;
            $partner->active = RequestMethods::post('active');

            if (empty($errors) && $partner->validate()) {
                $partner->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/partner/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                $view->set('errors', $errors + $partner->getErrors());
            }
        }

        $view->set('sections', $sections)
                ->set('partner', $partner);
    }

    /**
     * 
     * @before _secured, _admin
     * @param type $id
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $partner = App_Model_Partner::first(
                        array('id = ?' => $id), array('id', 'logo')
        );

        if (NULL === $partner) {
            echo 'Partner not found';
        } else {
            if (unlink($partner->getUnlinkLogoPath()) && $partner->delete()) {
                Event::fire('admin.log', array('success', 'ID: ' . $id));
                echo 'ok';
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                echo 'Unknown error eccured';
            }
        }
    }

    /**
     * Ajax
     * 
     * @before _secured, _publisher
     * @param type $id
     */
    public function deleteLogo($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $partner = App_Model_Partner::first(
                        array('id = ?' => $id)
        );

        if (NULL !== $partner) {
            $path = $partner->getUnlinkLogoPath();
            $partner->logo = '';
            if ($partner->validate() && unlink($path)) {
                $partner->save();
                Event::fire('admin.log', array('success', 'ID: ' . $id));
                echo 'ok';
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                echo 'Required fields are not valid';
            }
        } else {
            Event::fire('admin.log', array('fail', 'ID: ' . $id));
            echo 'Partner not found';
        }
    }

    /**
     * @before _secured, _admin
     */
    public function massAction()
    {
        $view = $this->getActionView();
        $errors = array();

        if (RequestMethods::post('performPartnerAction')) {
            $ids = RequestMethods::post('partnerids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $partners = App_Model_Partner::all(array(
                                'id IN ?' => $ids
                    ));

                    foreach ($partners as $partner) {
                        if (NULL !== $partner) {
                            if (unlink($partner->getUnlinkLogoPath())) {
                                if (!$partner->delete()) {
                                    $errors[] = 'An error occured while deleting ' . $partner->getTitle();
                                }
                            } else {
                                $errors[] = 'An error occured while deleting logo of ' . $partner->getTitle();
                            }
                        } else {
                            $errors[] = "Partner with id {$partner->getId()} not found<br/>";
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Partners have been deleted');
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/');

                    break;
                case 'activate':
                    $partners = App_Model_Partner::all(array(
                                'id IN ?' => $ids
                    ));

                    foreach ($partners as $partner) {
                        if (NULL !== $partner) {
                            $partner->active = true;

                            if ($partner->validate()) {
                                $partner->save();
                            } else {
                                $errors[] = "Partner id {$partner->getId()} - "
                                        . "{$partner->getTitle()} errors: "
                                        . join(', ', array_shift($partner->getErrors()));
                            }
                        } else {
                            $errors[] = "Partner with id {$partner->getId()} not found";
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Partners have been activated');
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/');

                    break;
                case 'deactivate':
                    $partners = App_Model_Partner::all(array(
                                'id IN ?' => $ids
                    ));

                    foreach ($partners as $partner) {
                        if (NULL !== $partner) {
                            $partner->active = false;

                            if ($partner->validate()) {
                                $partner->save();
                            } else {
                                $errors[] = "Partner id {$partner->getId()} - "
                                        . "{$partner->getTitle()} errors: "
                                        . join(', ', array_shift($partner->getErrors()));
                            }
                        } else {
                            $errors[] = "Partner with id {$partner->getId()} not found";
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Partners have been deactivated');
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/');
                    break;
                default:
                    self::redirect('/admin/partner/');
                    break;
            }
        }
    }

}
