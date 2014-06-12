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
                ->join('tb_section', 'pa.sectionId = s.id', 's', 
                        array('s.title' => 'sectionTitle'));

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

        $view->set('sections', $sections);

        if (RequestMethods::post('submitAddPartner')) {
            $this->checkToken();
            $errors = array();

            try {
                $im = new ImageManager(array(
                    'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                    'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                    'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                    'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                    'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
                ));

                $photoArr = $im->uploadWithoutThumb('logo', 'partners');
                $uploaded = ArrayMethods::toObject($photoArr);
            } catch (Exception $ex) {
                $errors['logo'] = $ex->getMessage();
            }

            $partner = new App_Model_Partner(array(
                'sectionId' => RequestMethods::post('section'),
                'title' => RequestMethods::post('title'),
                'address' => RequestMethods::post('address', ''),
                'email' => RequestMethods::post('email', ''),
                'web' => RequestMethods::post('web'),
                'logo' => trim($uploaded->photo->filename, '.'),
                'mobile' => RequestMethods::post('mobile', '')
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

        $view->set('sections', $sections)
                ->set('partner', $partner);

        if (RequestMethods::post('submitEditPartner')) {
            $this->checkToken();

            if ($partner->logo == '') {
                try {
                    $im = new ImageManager(array(
                        'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                        'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                        'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                        'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                        'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
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
            $partner->address = RequestMethods::post('address', '');
            $partner->email = RequestMethods::post('email', '');
            $partner->web = RequestMethods::post('web');
            $partner->mobile = RequestMethods::post('mobile', '');
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
        $this->checkToken();

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
            $this->checkToken();
            $ids = RequestMethods::post('partnerids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $partners = App_Model_Partner::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $partners) {
                        foreach ($partners as $partner) {
                            if (unlink($partner->getUnlinkLogoPath())) {
                                if (!$partner->delete()) {
                                    $errors[] = 'An error occured while deleting ' . $partner->getTitle();
                                }
                            } else {
                                $errors[] = 'An error occured while deleting logo of ' . $partner->getTitle();
                            }
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

                    if (NULL !== $partners) {
                        foreach ($partners as $partner) {
                            $partner->active = true;

                            if ($partner->validate()) {
                                $partner->save();
                            } else {
                                $errors[] = "Partner id {$partner->getId()} - "
                                        . "{$partner->getTitle()} errors: "
                                        . join(', ', array_shift($partner->getErrors()));
                            }
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

                    if (NULL !== $partners) {
                        foreach ($partners as $partner) {
                            $partner->active = false;

                            if ($partner->validate()) {
                                $partner->save();
                            } else {
                                $errors[] = "Partner id {$partner->getId()} - "
                                        . "{$partner->getTitle()} errors: "
                                        . join(', ', array_shift($partner->getErrors()));
                            }
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

    /**
     * @before _secured, _admin
     */
    public function sections()
    {
        $view = $this->getActionView();

        $sections = App_Model_Section::all(array('parentId = ?' => 6));

        $view->set('sections', $sections);
    }

    /**
     * @before _secured, _admin
     */
    public function sectionEdit($id)
    {
        $view = $this->getActionView();

        $section = App_Model_Section::first(array('id = ?' => $id));

        if (NULL === $section) {
            $view->errorMessage('Section not found');
            self::redirect('/admin/partner/sections/');
        }

        $view->set('section', $section);

        if (RequestMethods::post('submitEditPartnerSection')) {
            $this->checkToken();

            $section->title = RequestMethods::post('title');
            $section->urlKey = RequestMethods::post('urlkey');
            $section->rank = RequestMethods::post('rank', 1);
            $section->supportVideo = $section->getSupportVideo();
            $section->supportPhoto = $section->getSupportPhoto();
            $section->supportCollection = $section->getSupportCollection();
            $section->parentId = $section->getParentId();
            $section->active = RequestMethods::post('active');

            if ($section->validate()) {
                $section->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/partner/sections/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                $view->set('errors', $section->getErrors());
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function sectionMassAction()
    {
        $view = $this->getActionView();
        $errors = array();

        if (RequestMethods::post('performPartnerSectionAction')) {
            $this->checkToken();
            $ids = RequestMethods::post('sectionids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'activate':
                    $sections = App_Model_Section::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $sections) {
                        foreach ($sections as $section) {
                            $section->active = true;

                            if ($section->validate()) {
                                $section->save();
                            } else {
                                $errors[] = "Section id {$section->getId()} - "
                                        . "{$section->getTitle()} errors: "
                                        . join(', ', array_shift($section->getErrors()));
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Sections have been activated');
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/sections/');

                    break;
                case 'deactivate':
                    $sections = App_Model_Section::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $sections) {
                        foreach ($sections as $section) {
                            $section->active = false;

                            if ($section->validate()) {
                                $section->save();
                            } else {
                                $errors[] = "Section id {$section->getId()} - "
                                        . "{$section->getTitle()} errors: "
                                        . join(', ', array_shift($section->getErrors()));
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Sections have been deactivated');
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/sections/');
                    break;
                default:
                    self::redirect('/admin/partner/sections/');
                    break;
            }
        }
    }

}
